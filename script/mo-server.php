<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use PremiumMock\PremiumMockApp;
use Predis\Async\Client as PredisClient;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require dirname(__DIR__) . '/config.php';

$mockApp = new PremiumMockApp($config);
$server = IoServer::factory(new HttpServer(new WsServer($mockApp)), $config['ws_port']);

$client = new PredisClient($config['redis_conn_url'], $server->loop);

$client->connect(function ($client) use ($mockApp){
    echo "Connected to Redis, now listening for incoming messages...\n";

    $client->pubSubLoop('premiummockmt', function ($event) use ($mockApp){
        echo "Received MT `{$event->payload}` from {$event->channel}.\n";
        $mockApp->processMt(json_decode($event->payload, true));
    });
});

$server->run();
