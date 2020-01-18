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

use Craft;
use craft\base\Component;


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
        $version_paths = array();
        foreach($paths as $path) {
            $hash = $this->generateHash($path);
            $version_path = $this->generateHashedPath($path, $hash);
            if (!$dry_run) {
                copy($path, $version_path);
            }
            $version_paths[$path] = $version_path;
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
        $paths = array();
        $blacklist = array('cpresources', );
        $webroot = Craft::getAlias('@webroot');
        $directories = new DirectoryIterator($webroot);
        foreach ($directories as $fileinfo) {
            $folder = $fileinfo->getFileName();
            $path = $fileinfo->getPathName();
            if ($fileinfo->isDir() && !$fileinfo->isDot() && !in_array($folder, $blacklist)) {
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
    private function createManifest($hashed_files) {
        $webroot = Craft::getAlias('@webroot');
        $path = $webroot . DIRECTORY_SEPARATOR . 'versions.json';
        $fp = fopen($path, 'w');
        fwrite($fp, json_encode($hashed_files));
        fclose($fp);
    }

    /**
     * 
     */
    public function scan($dry_run = false): bool {
        
        $paths = $this->getPaths();

        echo "Paths searched:" . PHP_EOL;
        foreach($paths as $path) {
            echo $path . PHP_EOL;
        }
        echo PHP_EOL;

        $files = $this->searchPaths($paths);

        echo "Discovered files:" . PHP_EOL;
        foreach($files as $file) {
            echo $file . PHP_EOL;
        }
        echo PHP_EOL;

        $delete_files = $this->deleteVersions($files, $dry_run);

        echo "Deleted files:" . PHP_EOL;
        foreach($delete_files as $file) {
            echo $file . PHP_EOL;
        }
        echo PHP_EOL;

        $hashed_files = $this->createVersions($files, $dry_run);

        echo "Versioned files:" . PHP_EOL;
        foreach($hashed_files as $from => $to) {
            echo $from . ' > ' . $to . PHP_EOL;
        }
        echo PHP_EOL;

        $this->createManifest($hashed_files);

        return true;
    }
}
