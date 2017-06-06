<?php
/**
 * Configure settings.
 *
 * Each setting will be merged via
 * Hash::merge(Configure::read(), Hash::expand($configure))
 *
 * This is an example local development environment configuration.
 * Create a copy of this file as "environment.local.php", adjust it to your local development environment AND
 * exclude it from version control.
 * -> git: via .gitignore "app/Config/Environment/environment.local.php"
 * -> svn: via svn:ignore "app/Config/Environment/environment.local.php"
 */
$configure = [
    'debug' => true,
    'debugJS' => false,

    'Datasources.default' => [
        'host' => 'localhost',
        'username' => 'username',
        'password' => 'secret',
        'database' => 'myapp',
        'prefix' => '',
        'quoteIdentifiers' => true,
    ],

    'Datasources.test' => [
        'host' => 'localhost',
        'username' => 'username',
        'password' => 'secret',
        'database' => 'myapp_test',
        'prefix' => '',
        'quoteIdentifiers' => true,
    ],

    'EmailTransport.default' => [
        'transport' => 'default',
        'from' => 'example@example.com'
    ]
];
