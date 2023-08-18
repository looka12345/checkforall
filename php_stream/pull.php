<?php
error_reporting(E_ALL);
ini_set('display_errors', '1'); 

/*
 * pull.php
 * Octorate srl. All rights reserved. 2019 
 */

// include utility function
include 'com\octorate\setup.php';



use com\octorate\stream\common\Database;
use com\octorate\stream\pull\TechnicalitPull;

use com\octorate\stream\common\AbstractStream;
use com\octorate\stream\common\PullResult;




// do not print anything else results
if (!$test) {
    ob_start();
}

$force = filter_input(INPUT_GET, 'force', FILTER_VALIDATE_BOOLEAN);

// prepare result object
$result = new \com\octorate\stream\common\PullResult();


try {
    //check for authorized ip
    // $auth = [
    //     // live server
    //     '127.0.0.1', '172.20.1.30', '172.20.1.31', '172.20.1.32',
    //     // octorate
    //     '5.97.133.55',
    //     // gianluca
    //     '77.39.174.103',
    //     // nick, andrea fixed ip
    //     '14.102.81.98','61.246.5.207','103.47.174.6','103.121.115.94',
    //     // nick, andrea temp ip
    //     //'45.122.120.13'
    //     '192.168.160.251'
    // ];
   // $auth=['Ritik', 'Meghani', 'Raj'];
    
    $ra = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR');
   
    if (!$ra) {
        $ra = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    }
    // if (!in_array($ra, $auth)) {
    //     throw new Exception('Not authorized: ' . $ra);
    // }
   

  $gid = filter_input(INPUT_GET, 'gid', FILTER_SANITIZE_NUMBER_INT);
    //echo "\nthis is gid value\n";
    //print($gid);
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    if ($id <= 0 && $gid <= 0) {
        throw new Exception('Invalid request (code: 1)');
    }

    // load site user from database
     $db = \com\octorate\stream\common\Database::getData();
        
     

  
    if ($id > 0) {
        $sc = $db->getSiteUser($id);
       
        if (!$sc) {
            throw new Exception('Invalid request (code: 2)');
        }
        $gid = $sc->sites_id;
    }
    
    $si = $db->getSiteConfig($gid);
    
    if (!$si) {
        throw new Exception('Invalid request (code: 3)');
    }
 
    // load the stream class
    $sn = preg_replace('/[^0-9A-Za-z]/', '', $si->sites_name);
   
  
    $sn= 'com\\octorate\\stream\\pull\\' . ucfirst(strtolower($sn)) . 'Pull';
   
    
  
   
   

    // prepare stream instance
    $site = new $sn();
    $site->setDatabase($db);
    $site->setSiteConfig($si);
    $site->setTest($test);
    
   
    // call pull method
    if ($sc) {
        $site->setSiteUser($sc);
    
        $result->reservations = $site->pullReservations();
  
    } else {
        $result->reservations = $site->pullGlobal();
    }
    
} catch (Exception $ex) {
    // error in request
    $result->reservations = null;
    $result->error = $ex->getMessage();
}

// do not print anything else results
if (!$test) {
    ob_end_clean();
    header('Content-Type: application/json');
}
// print_r($result->reservations);

// encode result to json
if ($result->reservations) {
   
    $arr = [];
    $s = $si->sites_id;
    foreach ($result->reservations as $r) {
        if ($sc) {
            $r->connectionId = $sc->ID;
        }
        if (!$test) {
            // save into push table
            //print("this is debug=>\n");
            //print_r($r);
           // exit;
             $pid = $db->savePullReservation($s, $r, $force);
            //print("hello");
             //exit;
            if ($pid) {
                //print("hello");
               //exit;
                $pr = new \com\octorate\stream\common\PullReservation();
                $pr->pushImportId = $pid;
                $arr[] = $pr;
            }
        } else if ($force || !$db->isUpToDate($s, $r)) {
            // add to result
            $a = array_filter((array) $r, 'is_not_null');
            $arr[] = $a;
        }
    }
    $result->reservations = $arr;
}

print_json($result);
