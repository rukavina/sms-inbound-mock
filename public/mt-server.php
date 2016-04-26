<?php

use Predis\Client as PredisClient;
use GuzzleHttp\Client as HttpClient;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require dirname(__DIR__) . '/config.php';

$errorMap = [
    1 => 'Authorization failed.',
    2 => 'Invalid international phone number.',
    3 => 'Wrong provider name.',
    4 => 'Wrong short id.',
    5 => 'The text parameter is missing or it is too long.',
    6 => 'Wrong end user price.',
    7 => 'The maximum message count in time (per user) is exceeded.',
    8 => 'WSI is longer than single SMS. Please, make shorter text or wsi_url.',
    9 => 'User is not subscribed on service any more.',
    9 => 'Wrong keyword.',
    98 => 'Message submission to the SMS gate failed.',
    99 => 'Internal error.',    
];

//if error_code set just response with error xml
$errorCode = isset($config['mt']['error_code'])? $config['mt']['error_code']: null;
if($errorCode){
    echo '<?xml version="1.0"?>
    <report>
        <status>error</status>
        <error_code>' . $errorCode . '</error_code>
        <error_desc>' . htmlspecialchars($errorMap[$errorCode]) . '</error_desc>
    </report>';
    exit;
}

ob_start();

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
    'mobile' => $_REQUEST['to'],
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

$response = $httpClient->get($config['mt']['dlr_url'], [
    'query' => $dlrParams
]);
