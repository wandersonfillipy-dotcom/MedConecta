<?php
return [
    'nome'             => 'MedConecta',
    'tagline'          => 'Cuidado que conecta.',
    'versao'           => '1.0.0',
    'sprint'           => 'Sprint 1',
    'base_url'         => getenv('APP_URL') ?: 'http://localhost/MedConecta/public',
    'session_name'     => 'medconecta_sess',
    'session_lifetime' => 7200,
    'csrf_key'         => 'csrf_token',
    'debug'            => (bool)(getenv('APP_DEBUG') ?: false),
];