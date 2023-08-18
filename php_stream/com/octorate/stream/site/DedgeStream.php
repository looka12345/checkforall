<?php

/*
 * DedgeStream.php
 * Octorate srl. All rights reserved. 2019
 */

namespace com\octorate\stream\site;

use com\octorate\stream\common\AbstractDateStream;
use com\octorate\stream\common\CalendarUpdateData;
use com\octorate\stream\common\CalendarUpdateResult;
use com\octorate\stream\utils\cURL;
use com\octorate\stream\utils\UtilFunc;

class DedgeStream extends AbstractDateStream {

    protected $reqRespXml = '';
    protected $curl;
    protected $utlFunc;
    protected $result;

    /**
     * Send calendar value to externale site.
     * @param CalendarUpdateData $cud
     * @return CalendarUpdateResult
     */
    public function updateCalendar(CalendarUpdateData $cud) {
        if ($this->test) {
            print_r($cud);
        }

        $this->result = new CalendarUpdateResult();

        try {
            // prepare utils classes
            $this->curl = new cURL(true);
            $this->utlFunc = new UtilFunc();
            $this->utlFunc->database = $this->database;

            // read values from db from clienti, clpms tables and room tables
            $this->parseValuesFromDb($cud);

            $finaXml = '';
            $xml_data = '';
            // loop each period
            $this->url = 'https://octorate-planning.suppliers.availproconnect.com/Update';
            foreach ($cud->dateIntervals as $di) {
                if ($this->test) {
                    echo 'Date interval = ' . $di->startDate . ' -> ' . $di->endDate;
                }
                $xml_data .= $this->updateInventory($di->startDate, $di->endDate, $cud);
            }
            if ($xml_data != '') {
                $finaXml .= $xml_data;
            }
            $this->makeFinalXML($finaXml);
            // no exception raised from checkForSuccess, all fine
            // success
            $this->result->success = true;
            if ($this->test) {
                echo $this->reqRespXml;
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
     * Makes xml of inventory.
     * @param start date, end date, cudobj
     * @return
     */
    protected function updateInventory($start, $end, $cud) {
        list($roomId, $rateId) = explode(':', $this->roomObj->site_room_id);

        if ($cud->isChangedStopSells() && (!$this->isEnabledStopSells()) && $this->isEnabledAvailability() && $cud->availability > 0 && ($cud->stopSells)) {
            //$cud->availability = 0;
            //$this->result->availability = 0;
        }
        $flg = 0;
        $xml = '';
        $xml .= '<room roomCode="' . $roomId . '">';
        if ($this->isSendableAvailability($cud)) {
            $flg = 1;
            $xml .= '<inventory>';
            $xml .= '<availability from="' . $start . '" to="' . $end . '" quantity="' . $cud->availability . '" />';
            $xml .= '</inventory>' . "\n";
        }

        if ($this->isSendablePrice($cud) || $this->isSendableMinstay($cud) || $this->isSendableMaxstay($cud) || $this->isSendableCloseToArrival($cud) || $this->isSendableCloseToDeparture($cud) || $this->isSendableStopSells($cud)) {
            $flg = 1;
            $rxml = '';
            $occ_pr = '';
            if ($this->isSendableStopSells($cud)) {
                $rxml .= ' isClosed="' . ($cud->stopSells ? 'true' : 'false') . '" ';
            }
            if ($this->test) {
                echo "pricing_method_id=>" . $this->siteUser->pricing_method_id;
//                $this->siteUser->pricing_method_id = 3;
            }
            if ($this->isSendablePrice($cud)) {
                if ($this->siteUser->pricing_method_id == 3) {
                    $adult = 0;
                    $child = 0;
                    if (!empty($this->roomObj->site_room_occupancy)) {
                        $adult = $this->roomObj->site_room_occupancy;
                    }
                    if (preg_match('/\@\@/is', $this->roomObj->site_room_id)) {
                        list($site_room_id, $child) = explode('@@', $this->roomObj->site_room_id);
                    }
                    $occ_pr = '<occupancy adultCount="' . $adult . '" childCount="' . $child . '" infantCount="0" unitPrice="' . ($adult + $child) * $cud->price . '" ' . $rxml . '/>';
                } else {
                    $rxml .= ' unitPrice="' . $cud->price . '" ';
                }
            }
            if ($this->isSendableMinstay($cud)) {
                if ($cud->minstay < 1) {
                    $cud->minstay = 1;
                }
                if ($cud->minstay > 99) {
                    $cud->minstay = 99;
                }
                $rxml .= ' minimumStay="' . $cud->minstay . '" ';
            }
            if ($this->isSendableMaxstay($cud)) {
                if ($cud->maxstay < 1) {
                    $cud->maxstay = 1;
                }
                if ($cud->maxstay > 99) {
                    $cud->maxstay = 99;
                }
                $rxml .= ' maximumStay="' . $cud->maxstay . '" ';
            }
            if ($this->isSendableCloseToArrival($cud)) {
                $rxml .= ' noArrival="' . ($cud->closeToArrival ? 'true' : 'false') . '" ';
            }
            if ($this->isSendableCloseToDeparture($cud)) {
                $rxml .= ' noDeparture="' . ($cud->closeToDeparture ? 'true' : 'false') . '" ';
            }
            if (!empty($rxml)) {
                $xml .= '<rate rateCode="' . $rateId . '">';
                if (!empty($occ_pr)) {
                    $xml .= '<planning from="' . $start . '" to="' . $end . '"' . $rxml . '>';
                    $xml .= $occ_pr;
                    $xml .= '</planning>';
                } else {
                    $xml .= '<planning from="' . $start . '" to="' . $end . '"' . $rxml . '/>';
                }
                $xml .= '</rate>';
            }
        }
        $xml .= '</room>';

        if ($flg && !empty($xml)) {
            return $xml;
        }
    }

    /**
     * Makes final xml of inventory and submits.
     * @param xml, roomName, roomId
     * @return 
     */
    public function makeFinalXML($xml) {
//        $sites_pass = htmlentities($this->siteUser->sites_pass, ENT_QUOTES, "UTF-8");
        $finalXml = '<?xml version="1.0" encoding="utf-8"?>
                        <message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                            <authentication login="' . $this->siteUser->sites_user . '" password="' . $this->siteUser->sites_pass . '" />
                            <inventoryUpdate hotelCode="' . $this->siteUser->hotel_id . '">
                                ' . "\n" . $xml . "\n
                            </inventoryUpdate>
                        </message>";
        if ($this->test) {
            echo "finalXml=>$finalXml\n";
//            exit;
        }
        $this->curl->headers[2] = "Content-type: text/xml";
        $result = $this->curl_post($this->url, $finalXml);
        $result = $this->utlFunc->replaceHtmlChar($result);
        $this->reqRespXml .= date("Y-m-d H:i:s") . "::finalXml=>$finalXml\n\nfinalResp=>$result\n";

        $this->checkForSuccess($result);
    }

    /**
     * Function check message of final response.
     * @param result, roomName, roomId
     * @return boolean
     */
    public function checkForSuccess($result) {
        if (preg_match('/<failure.*?>(.*?)<\/failure>/is', $result, $match) || preg_match('/<warning.*?>(.*?)<\/warning>/is', $result, $match)) {
            $result = $match[1];
            $msg = '';
            if (preg_match('/<comment>(.*?)<\/comment>/is', $result, $match)) {
                $msg = $match[1];
            }
            throw new \Exception("Could not update successfully.$msg");
            return 0;
        }
        if (!(preg_match('/<success\s*\/>/is', $result))) {
            throw new \Exception('Could not update successfully.');
            return 0;
        }
    }

}
