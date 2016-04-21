<?php

use Predis\Client as PredisClient;
use GuzzleHttp\Client as HttpClient;

ob_start();
require dirname(__DIR__) . '/vendor/autoload.php';
$config = require dirname(__DIR__) . '/config.php';

$client = new PredisClient($config['redis_conn_url']);

$mtNum  = (int)$client->get('mt_num');
$mtNum++;
$client->set('mt_num', $mtNum);

//encode utf-8
$encRequest = array();
foreach ($_REQUEST as $key => $value) {
    $encRequest[$key] = utf8_encode($value);
}

$client->publish('premiummockmt', json_encode($encRequest));

$client->quit();


echo '<?xml version="1.0"?>
<report>
    <status>success</status>
    <msg_id>' . $mtNum . '</msg_id>
</report>';

//close http conn. and flush
header('Connection: close');
header('Content-Length: '.ob_get_length());
ob_end_flush();
ob_flush();
flush();



//sleep a while and then send DLR as per:
//https://www.horisen.com/en/help/api-manuals/premium-transit#Delivery-Reports
if(!isset($config['mt']['dlr_url'])){
    return;
}
//sleep a while for dlr
sleep(2);

$httpClient = new HttpClient();
$dlrParams = array(
    'mobile' => $_REQUEST['from'],
    'short_id' => null,
    'int_id' => $mtNum,
    'ext_id' => null,
    'status' => isset($config['mt']['dlr_status'])? $config['mt']['dlr_status']: 1,
    'price' => null,
);

foreach ($dlrParams as $key => $value) {
    if(!isset($value) && isset($_REQUEST[$key])){
        $dlrParams[$key] = $_REQUEST[$key];
    }
}

$response = $httpClient->post($config['mt']['dlr_url'], [
    'body' => $dlrParams
]);
