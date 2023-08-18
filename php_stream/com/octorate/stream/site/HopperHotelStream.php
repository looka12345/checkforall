<?php

/*
 * BookvisitStream.php
 * Octorate srl. All rights reserved. 2019
 */

namespace com\octorate\stream\site;

use com\octorate\stream\common\AbstractDateStream;
use com\octorate\stream\common\CalendarUpdateData;
use com\octorate\stream\common\CalendarUpdateResult;
use com\octorate\stream\utils\cURL;
use com\octorate\stream\utils\UtilFunc;

class HopperHotelStream extends AbstractDateStream
{

    protected $reqRespXml = '';
    protected $curl;
    protected $utlFunc;
    protected $result;

    /**
     * Makes xml of inventory.
     * @param start date, end date, cudobj
     * @return
     */
    protected function updateInventory($start, $end, $cud)
    {
        //$url = 'https://eaotaservice.citybreak.com/OTAService.svc';
        echo 'check currency';
        print_r($this->currency);
        echo 'check currency';
        list($roomId,$ratePlancode) = explode(':', $this->roomObj->site_room_id);

        $singleOccOption = '';
        if (preg_match('/\@\@/is', $roomId)) {
            list($roomId, $singleOccOption) = explode('@@', $roomId);
        }

        if ($this->roomObj->derive_price > 0 && preg_match('/@@/', $this->roomObj->site_room_id)) {
            throw new \Exception('ignored');
        }

        if ($this->roomObj->site_room_occupancy == '0') {
            throw new \Exception('bookvisit Room occupancy not defined. Please set and retry.');
        } else if ($this->roomObj->site_room_occupancy > 3) {
            $this->roomObj->site_room_occupancy = 3;
        }

        if ($cud->isChangedStopSells() && (!$this->isEnabledStopSells()) && $this->isEnabledAvailability() && $cud->availability > 0 && ($cud->stopSells)) {
            $cud->availability = 0;
            $this->result->availability = 0;
        }

        // if ($this->isSendableAvailability($cud)) {
        //     $check=array(
        //         "hotel"=>'WDJR4GB',
        //         "availabilityDateRange"=>array("startDate"=>'2023-39-39',"endDate"=>'2033-93-39'),
        //         "aris"=>array(
        //          "kdiuei"=>array("roomTypeId"=>'eo2'),
        //          "ratePlan"=>array("ratePlanCode"=>"1983"),
        //          "currency"=>"USD",
        //          "sellLimit"=> '10',
        //          "freeNights"=>array("day"=>'4',"repeat"=>false)
        //         ),
        //     );
        //     $result=json_encode($check,true);
        //     echo $result;

        // }
        $datads = array();
        if ($this->utlFunc->dateCompare($end, $start) >= 0) {

            $cta_xml = $ctd_xml = $stopsell_xml = $max_xml = $min_xml = '';
            $xml = '';
            if ($this->isSendableCloseToArrival($cud)) {

                 $cta= $cud->closeToArrival == 1 ? 'true' : 'false';
               
            }

            if ($this->isSendableCloseToDeparture($cud)) {

               $ctd=$cud->closeToDeparture == 1 ? 'true' : 'false';
               
            }

            if ($this->isSendableStopSells($cud)) {

                $stopsell_xml = $cud->stopSells == 1 ? 'closed' : 'open';
                $stopsell['stopsell_xml'] = $stopsell_xml;

            }

            if ($this->isSendableMaxstay($cud)) {

                $maxLOS['maxLOSArrival'] = $cud->maxstay;

            }

            if ($this->isSendableMinstay($cud)) {

                $minLOS['minLOSArrival'] = $cud->minstay;
            }
            if ($this->isSendableAvailability($cud)) {

                $alot_xml = $cud->availability;
                $setlimit= $alot_xml;
            }
            if ($roomId == '') {
                throw new \Exception("Submission failed due to room invalid. " . $this->roomObj->site_room_id . ".");
            }
            $days = $this->utlFunc->dateDiff($start, $end);
            $days++;
            print_r($cud);
            if ($alot_xml != '' || $stopsell_xml != '' || $cta_xml || $ctd_xml != '') {
                echo '<pre>';
                $check = array(
                    "hotel" =>$this->siteUser->hotel_id,
                    "availabilityDateRange" => array("startDate" =>$start, "endDate" => $end),
                    "aris" => array(
                        "roomType" => array("roomTypeId" => $roomId),
                        "ratePlan" => array("ratePlanCode" => $ratePlancode),
                        "currency" =>$this->currency,
                        "sellLimit"=>$setlimit,
                        "freeNights" => array("day" =>$days, "repeat" => false),
                        "rates" =>array(
                            "baseRates"=>array(
                                "guestCount"=>$cud->availability,
                                "amountBeforeTax"=>$cud->price,
                                "lengthOfStay"=>array(
                                    "minLOS"=>1,
                                    "maxLOS"=>2,
                                )
                            )
                        ),
                        "availabilityStatuses"=>array(
                            "closed" => false, 
                            "minLOSArrival" => 0, 
                            "maxLOSArrival" => 0, 
                            "closedToArrival" =>$cta, 
                            "closedToDeparture" => $ctd , 
                        )
                    ),
                );
              
                $result = json_encode($check, true);
                print_r($result);
                


            }








        }
    }





    /**
     * Makes final xml of inventory and submits.
     * @param xml, url
     * @return
     */
    public function submitXml($url, $xml, $type)
    {
        $this->curl->headers[3] = 'Content-type: text/xml; charset=utf-8';

        $result = $this->curl_post($url, $xml);

        $result = $this->utlFunc->replaceHtmlChar($result);

        $submissionTime = date("Y-m-d H:i:s");

        $this->reqRespXml .= "$submissionTime::xml=>$xml\n";
        $this->reqRespXml .= "finalResp=>$result\n\n";

        $this->checkForSuccess($result, $type);
    }

    /**
     * Function check message of final response.
     * @param result, roomName, roomId
     * @return boolean
     */
    public function checkForSuccess($result, $xml_type)
    {
        if (preg_match('/<Errors>/', $result, $match)) {
            $msg = '';
            if (preg_match('/<Error\s+(.*?)\/>/is', $result, $match)) {
                $msg = $match[1];
            }
            throw new \Exception("bookvisit Response with error=>$msg  - $xml_type");
        } else if (preg_match('/<Warnings>/', $result, $match)) {
            $msg = '';
            if (preg_match('/<Warning\s+(.*?)\/>/is', $result, $match)) {
                $msg = $match[1];
            }
            throw new \Exception("bookvisit Response Success with warning=>$msg  - $xml_type");
        } else if (!preg_match('/<Success/is', $result, $match)) {
            throw new \Exception("Could not update with Unknown Error for updating - $xml_type");
        }
    }
}