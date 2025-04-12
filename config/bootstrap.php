<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$secretsEnv = '/etc/secrets/.env';
$localEnv = dirname(__DIR__) . '/.env';

// Si hay un .env secreto en /etc/secrets/.env, lo carga
if (file_exists($secretsEnv)) {
    (new Dotenv())->load($secretsEnv);
} elseif (file_exists($localEnv)) {
    (new Dotenv())->bootEnv($localEnv);
}
