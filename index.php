<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/LOEServer/Factory.php';

use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;
use LOE;

function _buildSearchStr($query){
  return Base::WEBROOT . "/LOE" . preg_replace("/%20/"," ",$query);
}

function _onConnect(ClientSession $session){
  echo "Connected to WAMP router...\n";
  $onFileDownloaded = function($args){
    $requestObj = $args[0];
    $models = \LOE\Model::getAll();
    $targetModel = null;
    foreach($models as $model){
      $searchStr = _buildSearchStr($requestObj->query);
      $files = Factory::search($model->label,'file_path',$searchStr);
      if(!count($files)){
        continue;
      }else if(count($files) > 1){
        // throw new Exception(count($files) . ' matches for query ' . $searchStr);
        echo 'Too many (' . count($files) . ') matches for query: ' . $searchStr . "\n";
        break;
      }else{
        $targetModel = $model;
        $file = $files[0];
        break;
      }
    }
    if($targetModel == null){
      echo 'Unable to match file: ' . $searchStr . "\n";
      return;
    }
    $playedClass = $targetModel->namespace . 'Played';
    //we really shouldn't need the recordExists check, but here for extra safety.
    if(!$playedClass::recordExists($file->UID,$requestObj->requestDate)){
      $modelId = strtolower($targetModel->label) . 'Id';
      $played = Factory::createModel($playedClass::TABLE);
      $played->$modelId = $file->UID;
      $played->playDate = $requestObj->requestDate;
      $played->ipAddress = $requestObj->ip_address;
      //$played->create();
      print_r($played);
    }
  };
  $session->subscribe('io.outlawdesigns.webaccess.fileDownloaded',$onFileDownloaded);
}

$realm = 'realm1';
$wampUrl = 'wss://api.outlawdesigns.io:9700/ws';

$wampClient = new Client($realm);
$wampClient->addTransportProvider(new PawlTransportProvider($wampUrl));

$wampClient->on('open','_onConnect');

$wampClient->start();
