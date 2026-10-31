<?php

/*
|--------------------------------------------------------------------------
| Slipway: pipeline for a Laravel PACKAGE (library)
|--------------------------------------------------------------------------
|
| Save this file as `slipway.php` in your package ROOT (`slipway:init --preset=package` does
| that). Packages have no `artisan` file, so Slipway's commands run through Orchestra Testbench:
|
|   vendor/bin/testbench slipway:compile
|   vendor/bin/testbench slipway:check
|   vendor/bin/testbench slipway:test-pipeline   write ci-local.sh and run a stage in Docker
|   vendor/bin/testbench slipway:tia             add pest()->tia() to an existing tests/Pest.php
|
| Testbench must discover Slipway's service provider: add this to composer.json "scripts":
|   "post-autoload-dump": ["@php vendor/bin/testbench package:discover --ansi"]
|
| The YAML files are GENERATED from this file. Never edit them by hand.
|
*/

return [

    'name' => 'CI',

    'drivers' => [
        'bitbucket' => ['enabled' => false],
        'circleci' => ['enabled' => false],
        'github' => ['enabled' => true],
        'gitlab' => ['enabled' => false],
    ],

    'strict' => false,
    'guard' => true,

    // Libraries do not commit composer.lock, so key the cache on composer.json and audit what was installed.
    'lockfile' => false,

    // Run Slipway's commands (drift check, secret scan) through Testbench.
    'artisan' => 'vendor/bin/testbench',

    // Production requires PHP 8.3. CI runs 8.3 and 8.4.
    'php' => ['8.3', '8.4'],
    'node' => null,

    'triggers' => [
        'push' => ['main'],
        'tags' => [],
        'pull_request' => true,
        'schedule' => ['0 3 * * 1'],   // weekly: catches breakage from new dependency releases
        'manual' => false,
        'paths_ignore' => [],
    ],

    'concurrency' => ['cancel_superseded' => true],
    'timeouts' => ['default' => 15],
    'cache' => true,
    'env' => [],

    'stages' => [
        'quality' => ['composer-validate', 'composer-audit', 'secret-scan'],

        'test' => [
            'matrix' => true,
            // Packages have no .env.example or app key, so skip the Laravel environment preparation.
            'steps' => ['phpunit' => ['prepare_env' => false]],
        ],
    ],

    'environments' => [],
    'policies' => [],
    'secrets' => [],

];
