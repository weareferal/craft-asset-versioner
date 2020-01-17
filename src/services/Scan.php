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
    
    // TODO move to utils
    public function create_regex($extensions_array) {
        return "";
    }

    // TODO: remove all existing files that contain a hash
    public function deleteOldVersions($folder) {

    }

    public function createVersions($paths) {
        $hashed_paths = array();
        foreach($paths as $path) {
            $hash = $this->generateHash($path);
            $newPath = $this->generateHashedPath($path, $hash);
            array_push($hashed_paths, $newPath);
            // copy($file, $newPath);
        }
        return $hashed_paths;
    }

    // Find all files in a folder. 
    // TODO: must exclude existing versioned files
    public function findFiles($path) {
        $path = $path . "/";
        $extensions = ["png", "jpeg", "jpg", "svg", "webp", "css", "js", "eot", "woff", "woff2", "tff", "map"];
        $extensions_str = join('|', $extensions);
        $regex_str = "/^.*\.(" . $extensions_str . ")$/i";
        $directory = new RecursiveDirectoryIterator($path);
        $all_files = new RecursiveIteratorIterator($directory);
        $filtered_files = new RegexIterator($all_files, $regex_str);

        $files = array();
        foreach($filtered_files as $file) {
            $path = $file->getPathName();
            array_push($files, $path);
        }
        return $files;
    }

    // /foo/bar/baz.pdf -> /foo/bar/baz.vh5ksubnw.pdf
    public function generateHash($file) {
        return md5_file($file);
    }

    public function generateHashedPath($file, $hash) {
        $parts = pathinfo($file);
        return $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] . '.' . $hash . '.' . $parts['extension'];
    }

    // public function scanFiles($paths)
    // {
    //     // Hash files
    //     $results = [];
    //     foreach($files as $file) {
    //         $hash = $this->generateHash($file);
    //         $newPath = $this->generateHashedPath($file, $hash);
    //         echo $newPath;
    //         array_push($results, $newPath);
    //         // copy($file, $newPath);
    //     }

    //     return $results;
    // }

    // Search the paths for files to version
    private function gatherFiles($paths) {
        $files = array();
        foreach ($paths as $path) {
            $new_files = $this->findFiles($path);
            $files = array_merge($new_files, $files);
        }
        return $files;
    }

    // Get the extensions we are interested in versioning
    // private getExtensions() {
        
    // }

    // Return all valid directories from the web folder 
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

    // Return the folders we want scanned and versioned. If none specified,
    // just use the web folder. Any supplied folders must be within
    private function getPaths() {
        $folders = [];
        if (count($folders) <= 0) {
            $folders = $this->getDefaultPaths();
        }
        return $folders;
    }

    public function scan(): bool {
        $paths = $this->getPaths();

        echo "Paths to search:" . PHP_EOL;
        foreach($paths as $path) {
            echo $path . PHP_EOL;
        }
        echo PHP_EOL;

        $files = $this->gatherFiles($paths);

        echo "Files to hash:" . PHP_EOL;
        foreach($files as $file) {
            echo $file . PHP_EOL;
        }
        echo PHP_EOL;

        $hashed_files = $this->createVersions($files);

        echo "Hashed files:" . PHP_EOL;
        foreach($hashed_files as $file) {
            echo $file . PHP_EOL;
        }
        echo PHP_EOL;

        // $newFiles = array();
        // foreach($files as $file) {
        //     $hash = $this->generateHash($file);
        //     $newPath = $this->generateHashedPath($file, $hash);
        //     array_push($newFiles, $newPath);
        // }

        // $this->hasFiles($files);
        return true;
    }
}
