<?php

return [

    'evolution' => [
        'url' => env('WHATSAPP_API_URL', 'http://evolution-api:8080'),
        'key' => env('WHATSAPP_API_KEY'),
        'instance_id' => env('WHATSAPP_INSTANCE_ID'),
    ],

    'rate_limit' => [
        // Mensagens por minuto por campanha — ajustar conforme aquecimento do número
        'messages_per_minute' => (int) env('WT_MESSAGES_PER_MINUTE', 10),
    ],

];
