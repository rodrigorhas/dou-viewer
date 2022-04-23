<?php

return [
    "base_url" => env('INLABS_BASE_URL'),
    "cookie-cache-time" => env('INLABS_COOKIE_CACHE_TIME', 30),
    "credentials" => [
        "email" => env('INLABS_CREDENTIAL_EMAIL'),
        "password" => env('INLABS_CREDENTIAL_PASSWORD')
    ]
];

