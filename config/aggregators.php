<?php

return [
    'the_guardian' => [
        'api_key' => env('THE_GUARDIAN_API_KEY', ''),
        'base_url' => env('THE_GUARDIAN_BASE_URL', ''),
    ],
    'new_york_times' => [
        'api_key' => env('NEW_YORK_TIMES_API_KEY', ''),
        'base_url' => env('NEW_YORK_TIMES_BASE_URL', ''),
    ],
    'news_api' => [
        'api_key' => env('NEWS_API_API_KEY', ''),
        'base_url' => env('NEWS_API_BASE_URL', ''),
    ],
    'news_api_ai' => [
        'api_key' => env('NEWS_API_AI_KEY', ''),
        'base_url' => env('NEWS_API_AI_BASE_URL', ''),
    ],
];
