<?php

return [
    'access_mode' => env('SUPPLIER_ACCESS_MODE', 'all'),

    'allowed_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('SUPPLIER_ACCESS_EMAILS', ''))
    ))),
];
