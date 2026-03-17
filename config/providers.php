<?php

/*
|--------------------------------------------------------------------------
| Multi-Provider Email Configuration
|--------------------------------------------------------------------------
|
| All credentials are read from .env — never hardcode keys here.
|
| Required .env entries:
|
|   # Which provider to use by default: resend | ses | mailtrap
|   MAIL_PROVIDER=resend
|
|   # Fallback chain (comma-separated, tried in order on failure)
|   MAIL_PROVIDER_FALLBACK=ses,mailtrap
|
|   # Resend
|   RESEND_API_KEY=re_xxxx
|
|   # Amazon SES (HTTP API — no SMTP needed)
|   AWS_ACCESS_KEY_ID=AKIAxxxxxxxxxxxxxxxx
|   AWS_SECRET_ACCESS_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
|   AWS_DEFAULT_REGION=us-east-1
|   AWS_SES_FROM_EMAIL=noreply@yourdomain.com
|
|   # Mailtrap (API sending)
|   MAILTRAP_API_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
|   MAILTRAP_FROM_EMAIL=noreply@yourdomain.com
|
*/

return [

    /*
     | Default provider used when no provider is specified on a send call.
     */
    'default' => env('MAIL_PROVIDER', 'resend'),

    /*
     | Fallback chain — tried in order when the primary provider fails.
     | Set to empty string to disable fallback.
     */
    'fallback' => array_filter(
        explode(',', env('MAIL_PROVIDER_FALLBACK', '')),
        fn($v) => !empty(trim($v))
    ),

    /*
     | Provider definitions
     */
    'providers' => [

        'resend' => [
            'label'    => 'Resend',
            'icon'     => '⚡',
            'color'    => '#0d0d14',
            'enabled'  => !empty(env('RESEND_API_KEY')),
            'api_key'  => env('RESEND_API_KEY'),
        ],

        'ses' => [
            'label'          => 'Amazon SES',
            'icon'           => '☁️',
            'color'          => '#FF9900',
            'enabled'        => !empty(env('AWS_ACCESS_KEY_ID')) && !empty(env('AWS_SECRET_ACCESS_KEY')),
            'key'            => env('AWS_ACCESS_KEY_ID'),
            'secret'         => env('AWS_SECRET_ACCESS_KEY'),
            'region'         => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'from_email'     => env('AWS_SES_FROM_EMAIL', env('MAIL_FROM_ADDRESS')),
        ],

        'mailtrap' => [
            'label'      => 'Mailtrap',
            'icon'       => '🪤',
            'color'      => '#00B140',
            'enabled'    => !empty(env('MAILTRAP_API_TOKEN')),
            'api_token'  => env('MAILTRAP_API_TOKEN'),
            'from_email' => env('MAILTRAP_FROM_EMAIL', env('MAIL_FROM_ADDRESS')),
        ],

    ],

];
