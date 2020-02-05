<?php

namespace weareferal\assetversioner\models;

use weareferal\assetversioner\AssetVersioner;

use Craft;
use craft\base\Model;


class Settings extends Model
{
    public $staticVersioningEnabled = false;
    public $assetVersioningEnabled = false;
    public $staticVersioningPrefix = 'versions';
    public $staticVersioningExtensions = "css,js";
    public $assetVersioningExtensions = "png,jpg,jpeg,pdf";

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
                    'staticVersioningExtensions',
                    'staticVersioningPrefix',
                    'assetVersioningExtensions',
                ],
                'string',
            ],
            [
                [ 
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
