<?php

namespace weareferal\assetversioner\services;

use weareferal\assetversioner\AssetVersioner;
use weareferal\assetversioner\services\backends\DatabaseKeyStore;

use Craft;
use craft\base\Component;

interface KeyStoreInterface {
    public function get($path);
    public function update($versioned_files);
}

class KeyStore extends Component {
    public static function create() {
        $backend = AssetVersioner::getInstance()->getSettings()->keystoreBackend;
        switch ($backend) {
            case "database":
                return DatabaseKeyStore::class;
                break;
        } 
    }
}