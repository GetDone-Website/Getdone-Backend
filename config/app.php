<?php
return [
    'name'=>env('APP_NAME','GetDone API'), 'env'=>env('APP_ENV','local'),
    'debug'=>(bool)env('APP_DEBUG',false), 'url'=>env('APP_URL','http://localhost:4000'),
    'timezone'=>env('APP_TIMEZONE','Asia/Makassar'), 'locale'=>'id', 'fallback_locale'=>'en',
    'faker_locale'=>'id_ID', 'key'=>env('APP_KEY'), 'cipher'=>'AES-256-CBC',
    'previous_keys'=>array_filter(explode(',',env('APP_PREVIOUS_KEYS',''))),
    'maintenance'=>['driver'=>env('APP_MAINTENANCE_DRIVER','file'), 'store'=>env('APP_MAINTENANCE_STORE','database')],
];
