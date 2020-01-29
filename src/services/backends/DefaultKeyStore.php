<?php
namespace weareferal\assetversioner\services\backends;

use Craft;
use craft\db\Query;

use weareferal\assetversioner\services\KeyStoreInterface;
use weareferal\assetversioner\services\KeyStore;

class DefaultKeyStore extends KeyStore implements KeyStoreInterface {

    public function get($key) {
        $cache = Craft::$app->cache->get("craft_assert_versioner");
        return $cache[$key];
    }

    public function update($versioned_paths) {
        Craft::$app->cache->set("craft_assert_versioner", $versioned_paths);
    }
}
?>