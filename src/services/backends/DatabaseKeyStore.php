<?php
namespace weareferal\assetversioner\services\backends;

use weareferal\assetversioner\services\KeyStoreInterface;
use weareferal\assetversioner\services\KeyStore;

class DatabaseKeyStore extends KeyStore implements KeyStoreInterface {
    // public function getVersion($path): string {
    //     print_r($this->keystore);
    //     if (array_key_exists($path, $this->keystore)) {
    //         return $this->keystore($path);
    //     }
    //     return $path;
    // }

    // public function updateKeyStore($keystore) {
    //     $this->keystore = $keystore;

    //     // Write to disk
    //     $path = $this->getKeyStoreFilePath();
    //     $fp = fopen($path, 'w');
    //     fwrite($fp, json_encode($keystore));
    //     fclose($fp);
    // }

    // public function loadKeyStore(): bool {
    //     // Read from disk
    //     $keystoreFilePath = $this->getKeyStoreFilePath();
    //     if (! file_exists($keystoreFilePath)) {
    //         return false;
    //     } else {
    //         $keystore = file_get_contents($keystoreFilePath);
    //         $this->keystore = json_decode($keystore);
    //         return true;
    //     }
    // }

    // private function getKeyStoreFilePath() {
    //     $webroot = Craft::getAlias('@webroot');
    //     return $webroot . DIRECTORY_SEPARATOR . 'keystore.json';
    // }

    public function get($key) {
    }

    public function update($versioned_files) {

    }
}
?>