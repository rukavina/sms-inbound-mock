<?php

use Predis\Client as PredisClient;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require dirname(__DIR__) . '/config.php';

$client = new PredisClient($config['redis_conn_url']);

$client->publish('premiummockmt', json_encode($_REQUEST));

$client->quit();

echo '<?xml version="1.0"?>
<report>
    <status>success</status>
    <msg_id>65343</msg_id>
</report>';

