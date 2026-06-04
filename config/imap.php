<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IMAP default account
    |--------------------------------------------------------------------------
    |
    | The default account identifier to be used when none is specified.
    |
    |*/
    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | IMAP Accounts Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure multiple IMAP accounts.
    |
    |*/
    'accounts' => [
        'default' => [
            'host'           => env('EMAIL_PURCHASE_HOST', ''),
            'port'           => env('EMAIL_PURCHASE_PORT', 993),
            'protocol'       => env('EMAIL_PURCHASE_PROTOCOL', 'imap'),
            'encryption'     => env('EMAIL_PURCHASE_ENCRYPTION', 'ssl'),
            'validate_cert'  => env('EMAIL_PURCHASE_VALIDATE_CERT', false),
            'username'       => env('EMAIL_PURCHASE_USERNAME', ''),
            'password'       => env('EMAIL_PURCHASE_PASSWORD', ''),
            'authentication' => 'auto',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | IMAP Root/Global Options
    |--------------------------------------------------------------------------
    |
    | Here you can configure root-level options for message fetching.
    |
    |*/
    'options' => [
        'delimiter'        => '/',
        'fetch'            => \Webklex\PHPIMAP\IMAP::FT_UID,
        'fetch_body'       => true,       // Force download body content by default
        'fetch_attachment' => true,       // Force download attachments by default
        'fetch_flags'      => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Available IMAP Flags
    |--------------------------------------------------------------------------
    |
    |*/
    'flags' => [
        'recent'   => 'RECENT',
        'flagged'  => 'FLAGGED',
        'answered' => 'ANSWERED',
        'deleted'  => 'DELETED',
        'draft'    => 'DRAFT',
        'seen'     => 'SEEN',
    ],
];
