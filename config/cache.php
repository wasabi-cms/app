<?php

use Cake\Core\Configure;

return [
    /**
     * Configure the cache adapters.
     */
    'Cache' => [
        'default' => [
            'className' => 'File',
            'path' => CACHE,
            'url' => null,
        ],

        /**
         * Configure the cache used for general framework caching.
         * Translation cache files are stored with this configuration.
         * Duration will be set to '+2 minutes' in bootstrap.php when debug = true
         * If you set 'className' => 'Null' core cache will be disabled.
         */
        '_cake_core_' => [
            'className' => 'File',
            'prefix' => Configure::read('App.cachePrefix') . '_cake_core_',
            'path' => CACHE . 'persistent/',
            'serialize' => true,
            'duration' => '+1 years',
            'url' => null,
        ],

        /**
         * Configure the cache for model and datasource caches. This cache
         * configuration is used to store schema descriptions, and table listings
         * in connections.
         * Duration will be set to '+2 minutes' in bootstrap.php when debug = true
         */
        '_cake_model_' => [
            'className' => 'File',
            'prefix' => Configure::read('App.cachePrefix') . '_cake_model_',
            'path' => CACHE . 'models/',
            'serialize' => true,
            'duration' => '+1 years',
            'url' => null,
        ],
    ],
];
