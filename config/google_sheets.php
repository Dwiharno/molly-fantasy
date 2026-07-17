<?php

return [
    'enabled' => env('GOOGLE_SHEETS_SYNC_ENABLED', true),

    'credentials_path' => env('GOOGLE_SHEETS_CREDENTIALS_PATH', storage_path('app/google/service-account.json')),

    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),

    'sheets' => [
        'master_item' => env('GOOGLE_SHEETS_SHEET_MASTER_ITEM', 'Master Item'),
        'redeem' => env('GOOGLE_SHEETS_SHEET_REDEEM', 'Redeem'),
        'stock_opname' => env('GOOGLE_SHEETS_SHEET_STOCK_OPNAME', 'Stock Opname'),
        'user' => env('GOOGLE_SHEETS_SHEET_USER', 'User'),
        'log' => env('GOOGLE_SHEETS_SHEET_LOG', 'Log'),
    ],
];
