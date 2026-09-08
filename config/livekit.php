<?php

return [
    'url' => env('LIVEKIT_URL', 'http://127.0.0.1:7880'),
    'api_key' => env('LIVEKIT_API_KEY', ''),
    'api_secret' => env('LIVEKIT_API_SECRET', ''),
    'url' => env('LIVEKIT_URL', 'http://119.28.138.19:7880'),
    'api_key' => env('LIVEKIT_API_KEY', 'devkey'),
    'api_secret' => env('LIVEKIT_API_SECRET', 'secret_token_for_cloudnews_2026_32chars'),
    'token_ttl' => (int) env('LIVEKIT_TOKEN_TTL', 21600), // 6 hours
];
