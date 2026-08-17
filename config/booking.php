<?php

return [
    'promptpay_id' => env('PROMPTPAY_ID', '0000000000'),
    'promptpay_name' => env('PROMPTPAY_NAME', 'ศูนย์หนังสือ มรส.'),
    /*
     * Static PromptPay Bill Payment EMV string (Tag 30). When set, checkout QR is
     * regenerated from this account with amount_due_now (dynamic Tag 54).
     */
    'promptpay_qr_payload' => env('PROMPTPAY_QR_PAYLOAD'),
    'admin_name' => env('ADMIN_NAME', 'Admin'),
    'admin_email' => env('ADMIN_EMAIL', 'admin@example.com'),
    'admin_password' => env('ADMIN_PASSWORD', 'password'),
    'default_storefront_logo' => 'images/subsinn-logo.png',
];
