<?php

require __DIR__ . '/vendor/autoload.php';

use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;

function _onConnect(ClientSession $session){
  echo "Connected to WAMP router...\n";
  $onFileDownloaded = function($args){
    print_r($args);
  };
  $session->subscribe('io.outlawdesigns.webaccess.fileDownloaded',$onFileDownloaded);
}

$realm = 'realm1';
$wampUrl = 'wss://api.outlawdesigns.io:9700';

$wampClient = new Client($realm);
$wampClient->addTransportProvider(new PawlTransportProvider($wampUrl));

$wampClient->on('open','_onConnect');

$client->start();
