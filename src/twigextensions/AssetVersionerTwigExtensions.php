<?php

namespace weareferal\assetversioner\twigextensions;

use Craft;

use weareferal\assetversioner\AssetVersioner;

class AssetVersionerTwigExtensions extends \Twig_Extension
{
    public function getName()
    {
        return 'AssetVersioner';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('version', [$this, 'getVersion'])
        ];
    }

    public function getVersion($path)
    {
        try {
            $version = AssetVersioner::getInstance()->keystore->get($path);
            if ($version) {
                return $version;
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
        }
        return $path;
    }
}
