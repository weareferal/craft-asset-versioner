<?php

namespace weareferal\assetversioner\models;

use weareferal\assetversioner\AssetVersioner;

use Craft;
use craft\base\Model;


class Settings extends Model
{
    public $keystoreBackend = 'default';
    public $staticVersioningEnabled = false;
    public $assetVersioningEnabled = false;
    public $staticVersioningExtensions = "png,jpeg,jpg,svg,webp,css,js,eot,woff,woff2,tff,map";
    public $assetVersioningExtensions = "jpg,jpeg,png";

    public function rules()
    {
        return [
            [
                [
                    'staticVersioningEnabled',
                    'assetVersioningEnabled'
                ],
                'boolean'
            ],
            [
                [ 
                    'keystoreBackend',
                    'staticVersioningExtensions',
                    'assetVersioningExtensions',
                ],
                'string',
            ],
            [
                [ 
                    'keystoreBackend',
                    'staticVersioningExtensions',
                    'assetVersioningExtensions',
                ],
                'required',
            ],
            [
                [ 
                    'staticVersioningExtensions',
                    'assetVersioningExtensions',
                ],
                'match',
                'pattern' => '/^(?:\w{2,10})(?:,\w{2,10})*$/',
                'message' => 'Please enter a comma-separated list of extensions'
            ]
        ];
    }
}
