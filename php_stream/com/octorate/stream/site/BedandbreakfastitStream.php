<?php

/*
 * BedandbreakfastITStream.php
 * Octorate srl. All rights reserved. 2019
 */

namespace com\octorate\stream\site;

use com\octorate\stream\common\AbstractDateStream;
use com\octorate\stream\common\CalendarUpdateData;
use com\octorate\stream\common\CalendarUpdateResult;
use com\octorate\stream\utils\cURL;
use com\octorate\stream\utils\UtilFunc;

class BedandbreakfastITStream extends AbstractDateStream
{

    protected $reqRespXml = '';
    protected $curl;
    protected $utlFunc;
    protected $result;
    protected $currencyFlg = 1;
    protected $errorAllRoomArr = [];

    /**
     * Makes xml of inventory.
     * @param start date, end date, cudobj
     * @return
     */
    protected function updateInventory($start, $end, $cud)
    {
        if ($this->roomObj->site_room_occupancy == '0') {
            throw new \Exception('bedandbreakfastIT Room occupancy not defined. Please set and retry.');
        }
        if ($this->test) {
            var_dump($cud);
        }
        if ($cud->isChangedStopSells() && $this->isEnabledAvailability() && $cud->availability > 0 && ($cud->stopSells)) {
            $cud->availability = 0;
            $this->result->availability = 0;
        }
        if ($this->test) {
            var_dump($cud);
        }

        $xml = '';

        if ($this->isSendablePrice($cud)) {
            $xml .= "<Rates>";

            $singlePriceValue = $this->allRmSnglePrice[$this->roomObj->site_room_id];
            if ($singlePriceValue != '') {
                $net2 = $singlePriceValue + $cud->price;
                if ($net2 != '') {
                    $xml .= "<singleRate>" . $net2 . "</singleRate>";
                }
                $xml .= "<doubleRate>" . $cud->price . "</doubleRate></Rates>\n";
            } else if ($this->roomObj->site_room_occupancy == '1') {
                $xml .= "<singleRate>" . $cud->price . "</singleRate></Rates>\n";
            } else {
                $xml .= "<doubleRate>" . $cud->price . "</doubleRate></Rates>\n";
            }
        }

        if ($this->isSendableAvailability($cud) || ($cud->stopSells)) {
            $xml .= '<Avail>' . $cud->availability . '</Avail>';
        }

        if ($this->isSendableMinstay($cud)) {
            $xml .= '<MStay>' . $cud->minstay . '</MStay>';
        }
                
        if ($this->isSendableCloseToArrival($cud)) {            
            if($cud->closeToArrival){
                $xml .= '<ClosedOnArrival>1</ClosedOnArrival>';
            }else{
                $xml .= '<ClosedOnArrival>0</ClosedOnArrival>';
            }
        }

        if ($this->isSendableCloseToDeparture($cud)) {
            if($cud->closeToDeparture){
                $xml .= '<ClosedOnDeparture>1</ClosedOnDeparture>';
            }else{
                $xml .= '<ClosedOnDeparture>0</ClosedOnDeparture>';
            }
        }

        $nights = $this->utlFunc->dateDiff($start, $end);
		$nights++;
        /*if($nights == 0){
            $nights = 1;
        }*/
        if ($this->test) {
            print "xml=>$xml\n";
        }
        if ($xml != '') {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
				<GATE_WriteRQ xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
					<Authentication>
						<Distributor_Username>1bbliverate3</Distributor_Username>
						<Distributor_Password>Xh16iSNL</Distributor_Password>
						<Property_Username>' . $this->siteUser->sites_user . '</Property_Username>
						<Property_Password>' . $this->siteUser->sites_pass . '</Property_Password>
						<Property_Code>' . $this->siteUser->hotel_id . '</Property_Code>
					</Authentication>
					<Message>
                        <Day>
                            <StartDate Format="YYYY-MM-DD">' . $start . '</StartDate>
                            <Length>' . $nights . '</Length>
                            <Currency>' . $this->currency . '</Currency>
                            <RoomType Code="' . $this->roomObj->site_room_id . '">
                                
                                ' . $xml . '
                            </RoomType>
						</Day>
					</Message>							
				</GATE_WriteRQ>';
            //$this->submitXml('https://www.italyreservation.it/b/requests/gate.cfm', $xmlString);
            $this->submitXml('https://www.italyreservation.it/b/requests2/gate.cfm', $xmlString);
        }
    }

    /**
     * Makes final xml of inventory and submits.
     * @param xml, url
     * @return
     */
    public function submitXml($url, $xml)
    {
        $this->curl->headers[3] = 'Content-type: application/xml';
        $result = $this->curl_post($url, $xml);
        $result = $this->utlFunc->replaceHtmlChar($result);
        $submissionTime = date("Y-m-d H:i:s");
        $this->reqRespXml .= "$submissionTime::Url=>$url\nxml=>$xml\n";
        $this->reqRespXml .= "finalResp=>$result\n\n";

        $this->checkForSuccess($result);
    }

    /**
     * Function check message of final response.
     * @param result, roomName, roomId
     * @return boolean
     */
    public function checkForSuccess($result)
    {
        $result = preg_replace('/<!\[CDATA\[/', '', $result);
        $result = preg_replace('/\]\]>/', '', $result);

        if (preg_match('/<Result>OK<\/Result>/is', $result)) {
            return 1;
        } else if (preg_match('/<ErrorDescription>(.*?)<\/ErrorDescription>/is', $result, $match)) {
            $msg = $match[1];
            $msg = preg_replace('/<.*?>/', '', $msg);
            if (array_key_exists($this->roomObj->site_room_id, $this->errorAllRoomArr)) {
                $this->errorAllRoomArr[$this->roomObj->site_room_id] = $this->roomObj->site_room_name . "-" . $msg;
            } else {
                $this->errorAllRoomArr[$this->roomObj->site_room_id] = $this->roomObj->site_room_name . "-" . $msg;
            }
            $newmsg = implode(',', $this->errorAllRoomArr);

            throw new \Exception("Submission failed. $newmsg");
        }
        throw new \Exception("Submission failed.");
    }
}
