<?php

return [
    'demo_login_enabled' => (bool) env(
        'DEMO_LOGIN_ENABLED',
        env('APP_ENV', 'production') !== 'production'
    ),

    'operator_name' => env('KOLD_OPERATOR_NAME', 'Tedku Solution'),
    'support_email' => env('KOLD_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@kold.tedku.cloud')),
];
