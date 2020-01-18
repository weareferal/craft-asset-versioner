<?php
namespace weareferal\assetversioner\services\backends;

use Craft;
use craft\db\Query;

use weareferal\assetversioner\services\KeyStoreInterface;
use weareferal\assetversioner\services\KeyStore;

class DatabaseKeyStore extends KeyStore implements KeyStoreInterface {
    public function get($key) {
        $record = (new Query())
            ->select([
                'id',
                'key',
                'value'
            ])
            ->from(['{{%assetversioner_keystore}}'])
            -> where(['key' => $key])
            ->one();
        if ($record) {
            return $record["value"];
        } else {
            return false;
        }
    }

    public function update($versioned_files) {
        $connection = Craft::$app->getDb();
        $transaction = $connection->beginTransaction();
        $rows = [];
        foreach($versioned_files as $key=>$value) {
            array_push($rows, [$key, $value]);
        }
        try {
            $connection->createCommand()
                ->delete('{{%assetversioner_keystore}}')
                ->execute();
            $connection->createCommand()
                ->batchInsert(
                    '{{%assetversioner_keystore}}',
                    ['key', 'value'],
                    $rows,
                    false)
                ->execute();
            $transaction->commit();
        } catch(Exception $e) {
            $transaction->rollback();
        }
    }
}
?>