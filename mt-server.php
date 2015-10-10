<?php

use Predis\Client as PredisClient;

require __DIR__ . '/vendor/autoload.php';

$client = new PredisClient('tcp://127.0.0.1:6379');

$client->publish('premiummock', 'Test publish');

$client->quit();

