<?php

namespace weareferal\assetversioner\twigextensions;

use Craft;

use weareferal\assetversioner\AssetVersioner;

class AssetVersionerTwigExtensions extends \Twig_Extension {
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

    public function getVersion($path) {
        return AssetVersioner::getInstance()->keystore->get($path);
    }
}

?>