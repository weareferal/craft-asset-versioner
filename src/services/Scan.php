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
     * Search for, and delete, any old versions of a set of paths 
     * 
     * TODO Improve the performance 
     *  
     * @param array $paths An array of paths (without hashes) to search for
     * @param boolean $dry_run If enabled, do everythign except deleting
     * @return array An array of paths that were successfully deleted
     */
    private function deleteVersions($paths, $dry_run = false) : array {
        $deleted_paths = []; 
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
     * Add a hash to a path
     * 
     * @param string $file The absolute path
     * @return string The original path with a suffixed md5 32-char hash
     */
    private function generateHashedPath($path) : string {
        $hash = md5_file($path);
        $parts = pathinfo($path);
        return $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] . '.' . $hash . '.' . $parts['extension'];
    }

    /**
     * Create new hashed versions of file paths
     * 
     * @param array $paths An array of paths to hash and copy
     * @param boolean $dry_run If enabled, do everything except copy paths 
     * @return array An array of new hashed paths
     */
    private function createVersions($paths, $dry_run = false) : array {
        $webroot = Craft::getAlias('@webroot');
        $dst_dir = Craft::getAlias('@webroot');
        $prefix = AssetVersioner::getInstance()->getSettings()->staticVersioningPrefix;
        if ($prefix) {
            $dst_dir = $dst_dir . DIRECTORY_SEPARATOR . $prefix;
        }

        $version_paths = [];
        foreach($paths as $src_abs_path) {
            $src_rel_path = str_replace($webroot, '', $src_abs_path);
            $hash_abs_path = $this->generateHashedPath($src_abs_path);
            $hash_rel_path = str_replace($webroot, '', $hash_abs_path);
            $dst_abs_path = $dst_dir . $hash_rel_path;
            $dst_rel_path = str_replace($webroot, '', $dst_abs_path);

            if (! $dry_run) {
                $parts = pathinfo($dst_abs_path);
                if (! file_exists($parts["dirname"])) {
                    mkdir($parts["dirname"], 0744, true);
                }
                copy($src_abs_path, $dst_abs_path);
            }

            $version_paths[$src_rel_path] = $dst_rel_path;
        }
        return $version_paths;
    }

    /**
     * Search a particular directory recursively for relevant asset files
     * 
     * @param string $dir The absolute path to the directory to search
     * @return array An array of absolute file paths 
     */
    private function searchDir($dir) : array{
        // Create regex to filter folder contents by
        //
        // Regex explanation:
        // - Main group (at the end) is simply matching a file path
        // - Negative lookahead (first) is saying "don't match if the string
        //   before the extension is 32 chars long
        // - Positive lookahead (second) is saying "match if ends in one of 
        //   out extensions 
        $extensions = AssetVersioner::getInstance()->getSettings()->staticVersioningExtensions;
        $extensions = explode(",", $extensions);
        $extensions_str = join('|', $extensions);
        $regex_str = "/(?!^.*\.\w{32}\.[^\.]+$)(?=^.*\.(?:" . $extensions_str . ")$)^((?:\/[^\/]+)+)$/";

        $paths = [];
        $dir_paths = new RecursiveDirectoryIterator($dir);
        $all_paths = new RecursiveIteratorIterator($dir_paths);
        $matched_paths = new RegexIterator($all_paths, $regex_str);
        foreach($matched_paths as $path) {
            array_push($paths, $path->getPathName());
        }
        return $paths;
    }

    /**
     * Search directories for file paths
     * 
     * @param array $dirs An array of directories to search for files
     * @return array An array of file paths
     */
    private function searchDirs($dirs) : array {
        $paths = [];
        foreach ($dirs as $dir) {
            $new_paths = $this->searchDir($dir);
            $paths= array_merge($new_paths, $paths);
        }
        return $paths;
    }

    /**
     * Return eligable folders to search for files within
     * 
     * - Filters all files by the current `staticVersioningExtensions` setting
     * - Ignores volume paths 
     * - Ignores `cpresources` folder
     * 
     * @return array an array of absolute paths to directories
     */
    private function getDefaultDirs(): array {
        $webroot = Craft::getAlias('@webroot');
        $dirs = []; 
        $excludes = [ 
            $webroot . DIRECTORY_SEPARATOR . "cpresources"
        ];

        // Get volume paths for exclusion
        $volumes = AssetVersioner::getInstance()->volumes->getAllVolumes();
        foreach ($volumes as $volume) {
            $dir = Craft::getAlias($volume->path);
            array_push($excludes, $dir);
        }

        $di = new DirectoryIterator($webroot);
        foreach ($di as $fileinfo) {
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
                array_push($dirs, $path);
            }
        }
        return $dirs;
    }

    /**
     * Get the paths of folders we want to scan
     * 
     * TODO allow the user to manually specify folders to search  
     * 
     * @return array a list of folders to search for files within
     */
    private function getDirs() : array {
        $dirs = [];
        if (count($dirs) <= 0) {
            $dirs = $this->getDefaultDirs();
        }
        return $dirs;
    }
    
    /**
     * Perform a scan that will find files and create versions of those files,
     * optionally deleting old version files.
     * 
     * @param boolean dry_run Skip actually deleting and versioning files
     * @param boolean delete Actually delete old version files 
     * @return array An array with details which paths were found, deleted
     * and versioned 
     */
    public function scan($dry_run=false, $delete=false): array {
        $result = [];

        $dirs = $this->getDirs();

        $result["paths"] = $this->searchDirs($dirs);
        if ($delete) {
            $result["deleted_paths"] = $this->deleteVersions($result["paths"], $dry_run);
        }
        $result["versioned_paths"] = $this->createVersions($result["paths"], $dry_run);

        // Trigger event
        $this->trigger(
            self::EVENT_AFTER_FILES_VERSIONED,
            new FilesVersionedEvent([
                'versioned_paths' => $result["versioned_paths"],
            ])
        );

        return $result;
    }
}
