<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/LOEServer/Factory.php';

use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;

function _buildSearchStr($query){
  return \LOE\Base::WEBROOT . "/LOE" . preg_replace("/%20/"," ",$query);
}

$onFileDownloaded = function($args, ClientSession $session){
  $requestObj = $args[0];
  $models = \LOE\Model::getAll();
  $targetModel = null;
  foreach($models as $model){
    $searchStr = _buildSearchStr($requestObj->query);
    $files = \LOE\Factory::search($model->label,'file_path',$searchStr);
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
    $played = \LOE\Factory::createModel($playedClass::TABLE);
    $played->$modelId = $file->UID;
    $played->playDate = date('Y-m-d H:i:s',strtotime($requestObj->requestDate));
    $played->ipAddress = $requestObj->ip_address;
    //print_r($played);
    $played->create();
    $session->publish('io.outlawdesigns.loe.' . strtolower($modelLabel) . '.played',array($played,$file));
  }
}

$onConnect = function(ClientSession $session) use ($onFileDownloaded){
  echo "Connected to WAMP router...\n";
  $session->subscribe('io.outlawdesigns.webaccess.fileDownloaded',function($args) use ($onFileDownloaded,$session){
    $onFileDownloaded($args,$session);
  });
}

$realm = 'realm1';
$wampUrl = 'wss://api.outlawdesigns.io:9700/ws';

$wampClient = new Client($realm);
$wampClient->addTransportProvider(new PawlTransportProvider($wampUrl));

$wampClient->on('open',$onConnect);

$wampClient->start();
