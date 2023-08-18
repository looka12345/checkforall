<?php

/*
 * AbstractDateStream.php
 * Octorate srl. All rights reserved. 2019 
 */

namespace com\octorate\stream\common;

use com\octorate\stream\utils\cURL;
use com\octorate\stream\utils\UtilFunc;
use com\octorate\stream\utils\nusoap_client;

abstract class AbstractDateStream extends AbstractCalendarStream
{

    /**
     * @var Database
     */
    protected $curl;
    protected $roomfetch;
    protected $currency;

    /**
     * Send calendar value to externale site.
     * @param CalendarUpdateData $cud
     * @return CalendarUpdateResult
     */
    public function updateCalendar(CalendarUpdateData $cud)
    {
       
        
        if ($this->test) {
            // print_r($cud);
        }
    

        $this->result = new CalendarUpdateResult();


        try {
      
            // prepare utils classes
            $this->curl = new cURL(true);
            $this->utlFunc = new UtilFunc();
            $this->utlFunc->database = $this->database;

            // read values from db from clienti, clpms tables and room tables
            $this->parseValuesFromDb($cud);
         
         if ($this->test) {
                // print "roomfetch:$this->roomfetch<br>";
            }
            if (isset($this->roomfetch) && ($this->roomfetch == 1)) {
                if ($this->test) {
                    print "Going to import room list<br>";
                }
                $this->getRooms();
            }
        
            // loop each period
            foreach ($cud->dateIntervals as $di) {
               
                if ($this->test) {
                 //   echo 'Date interval = ' . $di->startDate . ' -> ' . $di->endDate;
                }
               
            }
            $this->updateInventory($di->startDate, $di->endDate, $cud);
            // no exception raised from checkForSuccess, all fine
            // success
            $this->result->success = true;
            if ($this->test) {
                 $this->reqRespXml;
            }
        } catch (\Exception $ex) {
            // exception raised from checkForSuccess
            // an error occours sending the values
            $this->result->success = false;
            if ($ex->getMessage() == 'network error') {
                $this->result->retry = true;
            } else {
                $this->result->retry = false;
            }
            if ($ex->getMessage() == 'ignored') {
                $this->result->ignore = true;
            } else {
                $this->result->message = $ex->getMessage();
            }
        }

        // insert request-response in log db
        if ($this->result->message) {
            $this->insertLog($this->reqRespXml, $this->result->message);
        } else {
            if ($this->test) {
                print "Going to insert in log:$this->reqRespXml<br><br>";
            }
            $this->insertLog($this->reqRespXml);
        }

        return $this->result;
    }

    /**
     * Parse some value and mapping from db.
     * @param
     * @return 1
     */
    public function parseValuesFromDb($cud)
    {
        if (isset($this->currencyFlg)) {
            $userObj = $this->getProperty('c.currency');
            $this->currency = $userObj->currency;
            if ($this->siteUser->conv_curr != '') {
                $this->currency = $this->siteUser->conv_curr;
            }
            print_r("\n check currency data abailble or not ");
            print_r($this->currency);
            echo 'hiii';

            
        }
        // echo 'checkone';
        $this->roomObjAll = $this->getSiteRooms();
   
        
        if ($this->test) {
            // print "check3=>";
            // var_dump($this->roomObjAll);
        }
        // print_r($this->roomObjAll);
        // print("\nthis is check4\n");
        foreach ($this->roomObjAll as $singleRoomObj) {
            // echo 'check5';
            $this->allRmSnglePrice[$singleRoomObj->site_room_id] = $singleRoomObj->single_price;

            $this->finlMapRmArray[$singleRoomObj->site_room_id] = $singleRoomObj->site_int_id . '##' . $singleRoomObj->site_room_occupancy;
            // echo 'check6';
            // print_r($cud->externalRoomId);
            // print("\nthis check7\n");
            // print($singleRoomObj->ID);
            // print("\nthis is check8\n");
            if ($singleRoomObj->ID == $cud->externalRoomId) {
                $this->roomObj = $singleRoomObj;
            }
            // echo "check9==>";
            // print_r($this->roomObj);
            //        print("\nthis is room\n");
        }
        if ($this->test) {
            //print "allRmSnglePrice=>";
            //var_dump($this->allRmSnglePrice);
            //print "finlMapRmArray=>";
            //var_dump($this->finlMapRmArray);
            //print "roomObj=>";
            //var_dump($this->roomObj);
        }
        // echo "\nthis is room id\n";
        // print($this->roomObj->ID);
        // echo "\nthis is room id\n";
        list($this->roomObj->breakfast, $this->roomObj->no_members) = $this->getBreakfast($this->roomObj->ID);
        // echo 'masktesing';
        // print_r($this->roomObj->breakfast);
        // echo 'masktesing';
        // $this->roomObj->threatment = $this->getThreatment($this->roomObj->ID);
        // if ($this->test) {            
        //     //var_dump($this->roomObj);
        // }
        // print "finlMapRmArray=>";print_r($this->finlMapRmArray);print "\n";
    }

    public function curl_post($url, $xml)
    {
        return $this->curl->post($url, $xml);
    }

    public function curl_get($url)
    {
        return $this->curl->get($url);
    }

    public function curl_put($url, $xml)
    {
        return $this->curl->put($url, $xml);
    }

    public function curl_patch($url, $xml)
    {
        return $this->curl->patch($url, $xml);
    }

    public function curl_delete($url, $xml)
    {
        return $this->curl->delete($url, $xml);
    }

    public function nusoap_post($url, $xml)
    {
        $soapClient = new nusoap_client($url);
        $soapClient->call($xml, $url . '?wsdl');
        /*if ($soapClient->fault) {
            echo '<h2>Fault</h2><pre>';
            print_r($result);
            echo '</pre>';
        }else{
            $err = $soapClient->getError();
            if ($err) {
                // Display the error
                echo '<h2>Error</h2><pre>' . $err . '</pre>';
            } else {
                // Display the result
                echo '<h2>Result</h2><pre>';
                print_r($result);
                echo '</pre>';
            }
        }*/
        return $soapClient->response;
    }

    public function nusoap_client_with_action($url, $xml, $action)
    {
        $soapClient = new nusoap_client($action);
        $soapClient->call($xml, '', '', $url);
        return $soapClient->response;
    }

    public function getDerivedRooms($parent_id)
    {
        return $this->database->fetchAll(
            'SELECT ord.room_id, ord.price_val,'
            . ' ord.price_round, hrt.no_members, ord.price'
            . ' FROM ob_rules_derived as ord'
            . ' INNER JOIN hot_room_type as hrt ON ord.room_id = hrt.room_id'
            . ' WHERE ord.parent_id = ? and ord.price > ?'
            . ' ORDER BY hrt.no_members ASC',
            [$parent_id, 0]
        );
    }

}