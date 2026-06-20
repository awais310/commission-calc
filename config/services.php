<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'dvla' => [
        'key' => env('DVLA_API_KEY'),
        'url' => 'https://driver-vehicle-licensing.api.gov.uk/vehicle-enquiry/v1/vehicles',
    ],

    'dvsa_mot' => [
        'key'           => env('DVSA_MOT_API_KEY'),
        'client_id'     => env('DVSA_MOT_CLIENT_ID'),
        'client_secret' => env('DVSA_MOT_CLIENT_SECRET'),
        'token_url'     => env('DVSA_MOT_TOKEN_URL'),
        'scope'         => env('DVSA_MOT_SCOPE', 'https://tapi.dvsa.gov.uk/.default'),
        'url'           => 'https://history.mot.api.gov.uk/v1/trade/vehicles/registration',
    ],

];
