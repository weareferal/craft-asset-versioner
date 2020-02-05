<?php

return [
    '*' => [
        'staticVersioningEnabled' => false,
        'assetVersioningEnabled' => false,
    ],
    'production' => [
        // Whether or not to enable versioning for static files specifically. 
        // Static files are the JS, CSS, png, jpeg, fonts ... that you use
        // part of your development workflow
        'staticVersioningEnabled' => true,

        // The extensions of the static files you are interested in being
        // hashed. In this example, we are only hashing JS, CSS and map files
        'staticVersioningExtensions' => 'css,js,map',

        // The name of the folder within your webroot to storge copied, versioned
        // files. This makes it easy to add this folder to your gitignore so that
        // generated version files are not in your repo.
        'staticVersioningPrefix' => 'versions',

        // Whether or not to enable versioning for uploaded Craft asset files.
        // This may or may not be something you need.
        'assetVersioningEnabled' => true,

        // The extensions of the asset files you are interested in being hashed.
        'assetVersioningExtensions' => 'png,jpg,jpeg,pdf'
    ],
];