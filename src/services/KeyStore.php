<?php

namespace weareferal\assetversioner\services;

use weareferal\assetversioner\AssetVersioner;
use weareferal\assetversioner\services\backends\DatabaseKeyStore;
use weareferal\assetversioner\services\backends\DefaultKeyStore;

use Craft;
use craft\base\Component;

interface KeyStoreInterface {
    public function get($path);
    public function update($versioned_files);
}

class KeyStore extends Component {
    public static function create() {
        // TODO: Could be a setting
        return DefaultKeyStore::class;
    }
}