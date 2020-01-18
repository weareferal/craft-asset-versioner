<?php
/**
 * Asset Versioner plugin for Craft CMS 3.x
 *
 * Automatically create cache-busting versions of all your assets
 *
 * @link      https://weareferal.com
 * @copyright Copyright (c) 2020 Timmy O'Mahony
 */

namespace weareferal\assetversioner\services;

use RecursiveDirectoryIterator;
use DirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use IteratorIterator;

use weareferal\assetversioner\AssetVersioner;
use weareferal\assetversioner\services\KeyStore;
use weareferal\assetversioner\events\FilesVersionedEvent;

use Craft;
use craft\base\Component;
use craft\services\Volumes;


/**
 * Scan Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Timmy O'Mahony
 * @package   AssetVersioner
 * @since     1.0.0
 */
class Scan extends Component
{

    const EVENT_AFTER_FILES_VERSIONED = 'afterFilesVersioned';

    /**
     * 
     * TODO: slow
     */
    public function deleteVersions($paths, $dry_run = false) {
        $deleted_paths = array();
        foreach($paths as $path) {
            $pathinfo = pathinfo($path);
            $regex = "/^" . preg_quote($pathinfo['filename'], '/') . "\.\w{32}\." . preg_quote($pathinfo['extension']) . "$/";
            $directory_files = new DirectoryIterator($pathinfo['dirname']);
            $matched_files = new RegexIterator($directory_files, $regex);
            foreach($matched_files as $file) {
                $path = $file->getPathName();
                if (!$dry_run) {
                    unlink($path);
                }
                array_push($deleted_paths, $path);
            }
        }
        return $deleted_paths;
    }

    /**
     * 
     */
    public function createVersions($paths, $dry_run = false) {
        $webroot = Craft::getAlias('@webroot');
        $version_paths = array();
        foreach($paths as $path) {
            $hash = $this->generateHash($path);
            $version_path = $this->generateHashedPath($path, $hash);
            if (!$dry_run) {
                copy($path, $version_path);
            }
            // Relative paths only
            $key = str_replace($webroot, '', $path);
            $value = str_replace($webroot, '', $version_path);
            $version_paths[$key] = $value;
        }
        return $version_paths;
    }

   

    /**
     * /foo/bar/baz.pdf -> /foo/bar/baz.vh5ksubnw.pdf
     */
    public function generateHash($file) {
        return md5_file($file);
    }

    /**
     * 
     */
    public function generateHashedPath($file, $hash) {
        $parts = pathinfo($file);
        return $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] . '.' . $hash . '.' . $parts['extension'];
    }

     /**
     * 
     */
    public function searchPath($path) {
        $extensions = ["png", "jpeg", "jpg", "svg", "webp", "css", "js", "eot", "woff", "woff2", "tff", "map"];
        $extensions_str = join('|', $extensions);
        // 3 parts
        // - main group (at the end) is simply matching a file path
        // - negative lookahead (first) is saying "don't match if the string
        //   before the extension is 32 chars long
        // - positive lookahead (second) is saying "match if ends in one of 
        //   out extensions
        //$regex_str = "/^.*\.(" . $extensions_str . ")$/i";
        $regex_str = "/(?!^.*\.\w{32}\.[^\.]+$)(?=^.*\.(?:" . $extensions_str . ")$)^((?:\/[^\/]+)+)$/";
        $directory_files = new RecursiveDirectoryIterator($path);
        $all_files = new RecursiveIteratorIterator($directory_files);
        $matched_files = new RegexIterator($all_files, $regex_str);

        $files = array();
        foreach($matched_files as $file) {
            $path = $file->getPathName();
            array_push($files, $path);
        }
        return $files;
    }

    /**
     * 
     */
    private function searchPaths($paths) {
        $files = array();
        foreach ($paths as $path) {
            $new_files = $this->searchPath($path);
            $files = array_merge($new_files, $files);
        }
        return $files;
    }

    /**
     * 
     */
    private function getDefaultPaths() {
        $webroot = Craft::getAlias('@webroot');
        $paths = array();
        $excludes = array(
            $webroot . DIRECTORY_SEPARATOR . "cpresources"
        );

        // Get volume paths for exclusion
        $volumes = AssetVersioner::getInstance()->volumes->getAllVolumes();
        foreach ($volumes as $volume) {
            $path = Craft::getAlias($volume->path);
            array_push($excludes, $path);
        }

        $directories = new DirectoryIterator($webroot);
        foreach ($directories as $fileinfo) {
            if (!$fileinfo->isDir() || $fileinfo->isDot()) {
                continue;
            }
            $is_valid = true;
            $path = $fileinfo->getPathName();
            foreach($excludes as $exclude) {
                if (substr($path, 0, strlen($exclude)) === $exclude) {
                    $is_valid = false;
                    break;
                }
            }
            if ($is_valid) {
                array_push($paths, $path);
            }
        }
        return $paths;
    }

    
    /**
     * 
     */
    private function getPaths() {
        $folders = [];
        if (count($folders) <= 0) {
            $folders = $this->getDefaultPaths();
        }
        return $folders;
    }

    
    /**
     * 
     */
    public function scan($dry_run = false): array {
        $paths = $this->getPaths();
        $files = $this->searchPaths($paths);
        $deleted_files = $this->deleteVersions($files, $dry_run);
        $versioned_files = $this->createVersions($files, $dry_run);

        // Trigger event
        $event = new FilesVersionedEvent([
            'versioned_files' => $versioned_files,
        ]);
        $this->trigger(self::EVENT_AFTER_FILES_VERSIONED, $event);

        return [
            "files" => $files,
            "deleted_files" => $deleted_files,
            "versioned_files" => $versioned_files
        ];
    }
}
