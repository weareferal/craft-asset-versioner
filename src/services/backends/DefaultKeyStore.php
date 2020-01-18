<?php
namespace weareferal\assetversioner\services\backends;

use Craft;
use craft\db\Query;

use weareferal\assetversioner\services\KeyStoreInterface;
use weareferal\assetversioner\services\KeyStore;

class DefaultKeyStore extends KeyStore implements KeyStoreInterface {

    private string $cache_key = "craft_assert_versioner";

    public function get($key) {
        $cache = Craft::$app->cache->get($this->cache_key);
        return $cache.get($key);
    }

    public function update($versioned_files) {
        Craft::$app->cache->set($this->cache_key, $versioned_files);
    }
}
?>