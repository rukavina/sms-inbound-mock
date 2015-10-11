<?php

use GuzzleHttp\Client as HttpClient;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require dirname(__DIR__) . '/config.php';

//reply MO
echo '<?xml version="1.0"?>
<report>
	<status>success</status>
</report>';

//send MT
$httpClient = new HttpClient();
$mtParams = array_merge($_POST, $config['mt']);
$response = $httpClient->request('POST', $config['mt']['url'], [
    'form_params' => $mtParams
]);


