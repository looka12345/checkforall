<?php

/*
 * calendar.php
 * Octorate srl. All rights reserved. 2019 
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once ("com/octorate/stream/common/database.php");

// auto load classes
spl_autoload_register(function ($class_name) {
   
    @include str_replace('\\', '/', $class_name) . '.php';
});

$test = @$_REQUEST['test'] == 'true';

// do not print anything else results
if (!$test) {
    ob_start();
}

try {
    // check for hot_site_user id
    $id = intval(@$_REQUEST['id']);
  
    if ($id <= 0) {
        throw new Exception('Invalid request (code: 1)');
    }

    // load site user from database
    //  $db = localhost/php_stream/com/octorate/stream/common/;
    //     $db= new Database('DATA');
    //       $db->getData();
    $db = \com\octorate\stream\common\Database::getData();
    $sc = $db->getSiteUser($id);
    //print("hell");
    //print_r($sc);
    if (!$sc) {
        throw new Exception('Invalid request (code: 2)');
    }
    // load the stream class
    $si = $db->getSiteConfig($sc->sites_id);
    $sn = preg_replace('/[^0-9A-Za-z]/', '', $si->sites_name);
 

    $className = 'com\\octorate\\stream\\site\\' . ucfirst(strtolower($sn)) . 'Stream';
    
    if (!class_exists($className)) {
        throw new Exception('Invalid request (code: 3)');
    }

    // prepare stream instance
    $site = new $className();
    $site->setDatabase($db);
    $site->setSiteConfig($si);
    $site->setSiteUser($sc);
    $site->setTest($test);

    // check for data
    $cud = new com\octorate\stream\common\CalendarUpdateData();

    $data = @$_REQUEST['data'];
    
   
    if ($data) {
       
        $cud->setJson($data);
        
    } else if ($test) {
         //load room data
        $rooms = $site->getSiteRooms();
       
        if (!$rooms) {
            throw new Exception('Invalid request (code: 4)');
        }
        // create dummy data
        $cud->externalRoomId = $rooms[0]->ID;
        $cud->dateIntervals = array(
            new com\octorate\stream\common\CalendarInterval(date('Y-m-d'), date('Y-m-d')),
            new com\octorate\stream\common\CalendarInterval(date('Y-m-d', strtotime('+10 day')), date('Y-m-d', strtotime('+13 day'))),
        );  
        $cud->availability = 1;
        $cud->price = 100;
        $cud->minstay = 1;
        $cud->maxstay = 99;
        $cud->stopSell = false;
        $cud->closeArrival = false;
        $cud->closeDeparture = false;
        $cud->cutOff = 1;
    }
    
    
    if (!$cud->isValid()) {
        throw new Exception('Invalid request (code: 5)');
    }
$res = $site->updateCalendar($cud);

} catch (Exception $ex) {
    // error in request
    $res = new com\octorate\stream\common\CalendarUpdateResult();
    $res->success = false;
    $res->message = $ex->getMessage();
    $res->retry = false;
}

// do not print anything else results
if (!$test) {
    ob_end_clean();
    header('Content-Type: application/json');
}

function is_not_null($var) {
    return !is_null($var);
}

// encode result to json
echo json_encode(array_filter((array) $res, 'is_not_null'));
