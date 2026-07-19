<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],
        'public' => [
            'driver' => 'local',
            'root' => base_path(env('PUBLIC_STORAGE_PATH', 'storage/app/public')),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
        'items' => [
            'driver' => 'local',
            'root' => base_path(env('PUBLIC_STORAGE_PATH', 'storage/app/public').'/items'),
            'url' => env('APP_URL').'/storage/items',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
