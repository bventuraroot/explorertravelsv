<?php

return [
    'imap' => [
        'host'           => env('EMAIL_PURCHASE_IMAP_HOST', 'imap.gmail.com'),
        'port'           => env('EMAIL_PURCHASE_IMAP_PORT', 993),
        'encryption'     => env('EMAIL_PURCHASE_IMAP_ENCRYPTION', 'ssl'),
        'username'       => env('EMAIL_PURCHASE_IMAP_USERNAME', ''),
        'password'       => env('EMAIL_PURCHASE_IMAP_PASSWORD', ''),
        'validate_cert'  => env('EMAIL_PURCHASE_IMAP_VALIDATE_CERT', true),
        'mailbox'        => env('EMAIL_PURCHASE_IMAP_MAILBOX', 'INBOX'),
    ],
    'download_pdf' => env('EMAIL_PURCHASE_DOWNLOAD_PDF', true),
    'pdf_storage_path' => 'purchases/pdfs',
];
