<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use PremiumMock\PremiumMockApp;
use Predis\Async\Client as PredisClient;

require __DIR__ . '/vendor/autoload.php';

$mockApp = new PremiumMockApp();
$server = IoServer::factory(new HttpServer(new WsServer($mockApp)), 8080);

$client = new PredisClient('tcp://127.0.0.1:6379', $server->loop);

$client->connect(function ($client) use ($mockApp){
    echo "Connected to Redis, now listening for incoming messages...\n";

    $client->pubSubLoop('premiummock', function ($event) use ($mockApp){
        $mockApp->broadcast(json_encode(array(
            'channel'   => $event->channel,
            'payload'   => $event->payload
        )));
        echo "Stored message `{$event->payload}` from {$event->channel}.\n";
    });
});

$server->run();
