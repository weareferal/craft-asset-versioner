<?php

namespace weareferal\assetversioner\services;

use weareferal\assetversioner\services\backends\DefaultKeyStore;

use craft\base\Component;

interface KeyStoreInterface {
    public function get($path);
    public function update($versioned_files);
}

/**
 * A swappable backend for storing key/value mappings of files to their
 * versioned version
 * 
 * TODO: this is designed as a swappable backend but is not currently in use
 */
class KeyStore extends Component {
    public static function create() {
        return DefaultKeyStore::class;
    }
}