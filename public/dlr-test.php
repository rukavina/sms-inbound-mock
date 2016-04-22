<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require dirname(__DIR__) . '/config.php';

// create a log channel
$log = new Logger('clientlog');
$log->pushHandler(new StreamHandler(dirname(__DIR__) . '/' . $config['client']['log_file']));

$log->addInfo('Received DLR', $_GET);