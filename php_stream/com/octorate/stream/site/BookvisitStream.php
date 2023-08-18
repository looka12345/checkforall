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

class BookvisitStream extends AbstractDateStream
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
        $url = 'https://eaotaservice.citybreak.com/OTAService.svc';
        list($roomId, $invBlockCode, $rateId) = explode(':', $this->roomObj->site_room_id);

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

        if ($this->isSendableAvailability($cud)) {
            $alotString = '<?xml version="1.0" encoding="utf-8"?>
                            <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
								xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
								<soap:Header>
								</soap:Header>
								<soap:Body>
                                    <OTA_HotelInvCountNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1">
                                        <Inventories>
                                            <Inventory>
                                                <StatusApplicationControl InvBlockCode="' . $invBlockCode . '" InvCode="' . $roomId . '" Start="' . $start . '" End="' . $end . '"></StatusApplicationControl>
                                                <InvCounts>
                                                    <InvCount Count="' . $cud->availability . '" ActionType="Remaining"/>
                                                </InvCounts>
                                            </Inventory>
                                        </Inventories>
                                    </OTA_HotelInvCountNotifRQ>
                                    <apikey xmlns="http://ea.citybreak.com">' . $this->siteConfig->def_orgid . '</apikey>
                                    <organizationid xmlns="http://ea.citybreak.com">' . $this->siteUser->hotel_id . '</organizationid>
								</soap:Body>
                            </soap:Envelope>';

            $this->curl->headers[4] = "SOAPAction: http://ea.citybreak.com/IOTAService/UpdateInventory";
            $this->submitXml($url, $alotString, 'alot');
        }

        $allFeatures = $this->getProperty('p.prevyrprice');

        $cta_xml = $ctd_xml = $stopsell_xml = $max_xml = $min_xml = '';
        
        $availMsgStag = '<AvailStatusMessage>
                            <StatusApplicationControl RatePlanID="' . $rateId . '" InvCode="' . $roomId . '" Start="' . $start . '" End="' . $end . '" Mon="1" Tue="1" Weds="1" Thur="1" Fri="1" Sat="1" Sun="1"></StatusApplicationControl>';
        $availMsgEtag = '</AvailStatusMessage>';
        if ($allFeatures->prevyrprice == 1) {
            if ($this->isSendableCloseToArrival($cud)) {
                $cta_xml = $availMsgStag . "\n" . '<RestrictionStatus Restriction="Arrival" Status="' . ($cud->closeToArrival ? 'Close' : 'Open') . '"/>' . "\n" . $availMsgEtag;
            }

            if ($this->isSendableCloseToDeparture($cud)) {
                $ctd_xml =$availMsgStag . "\n" . '<RestrictionStatus Restriction="Departure" Status="' . ($cud->closeToDeparture ? 'Close' : 'Open') . '"/>' . "\n" . $availMsgEtag;
            }

            if ($this->isSendableStopSells($cud)) {
                $stopsell_xml = $availMsgStag . "\n" . '<RestrictionStatus Status="' . ($cud->stopSells ? 'Close' : 'Open') . '"/>' . "\n" . $availMsgEtag;
            }

            if ($this->isSendableMaxstay($cud)) {
                $max_xml = '<LengthOfStay MinMaxMessageType="MaxLOS" Time="' . $cud->maxstay . '"/>';
            }
        } else {
            if ($this->isSendableAvailability($cud)) {
                $stopsell_xml = $availMsgStag . "\n" . '<RestrictionStatus Status="' . ($cud->availability ? 'Open' : 'Close') . '"/>' . "\n" . $availMsgEtag;
            }
            if ($this->isSendableCloseToArrival($cud)) {
                $cta_xml = $availMsgStag . "\n" . '<RestrictionStatus Restriction="Arrival" Status="' . ($cud->closeToArrival ? 'Close' : 'Open') . '"/>' . "\n" . $availMsgEtag;
            }
            if ($this->isSendableCloseToDeparture($cud)) {
                $ctd_xml = $availMsgStag . "\n" . '<RestrictionStatus Restriction="Departure" Status="' . ($cud->closeToDeparture ? 'Close' : 'Open') . '"/>' . "\n" . $availMsgEtag;
            }
        }

        if ($this->isSendableMinstay($cud)) {
            $min_xml = '<LengthOfStay MinMaxMessageType="MinLOS" Time="' . $cud->minstay . '"/>';
        }
        if ($this->isSendableMaxstay($cud)) {
            $max_xml = '<LengthOfStay MinMaxMessageType="MaxLOS" Time="' . $cud->maxstay . '"/>'; 
        }

        if ($min_xml != '' || $max_xml != '' || $cta_xml != '' || $ctd_xml != '' || $stopsell_xml != '') {
            $min_max_xml = '';
            if ($min_xml != '' || $max_xml != '') {
                $min_max_xml =  $availMsgStag . "\n" . '<LengthsOfStay>
                                    ' . $min_xml . '
                                    ' . $max_xml . '
                                </LengthsOfStay>' . "\n" . $availMsgEtag;
            }

            $restiction_xml = '<?xml version="1.0" encoding="utf-8"?>
                                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                    <soap:Header>
                                    </soap:Header>
                                    <soap:Body>
                                        <OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1">
                                            <AvailStatusMessages>
                                                ' . $min_max_xml . $cta_xml . $ctd_xml . $stopsell_xml . '
                                            </AvailStatusMessages>
                                        </OTA_HotelAvailNotifRQ>
                                        <apikey xmlns="http://ea.citybreak.com">' . $this->siteConfig->def_orgid . '</apikey>
                                        <organizationid xmlns="http://ea.citybreak.com">' . $this->siteUser->hotel_id . '</organizationid>
                                    </soap:Body>
                                </soap:Envelope>';

            $this->curl->headers[4] = "SOAPAction: http://ea.citybreak.com/IOTAService/UpdateAvailability";
            $this->submitXml($url, $restiction_xml, 'restriction');
        }

        $rateString = '';
        if ($this->isSendablePrice($cud)) {
            $tempnet = '';
            $price =  sprintf('%.2f', $cud->price);
            if ($singleOccOption != '') {
                $tempnet .= '<BaseByGuestAmt AmountAfterTax="' . $price . '" NumberOfGuests="' . $singleOccOption . '" />';
            } else {
                $otherinfo = $this->utlFunc->getPricingMethod($this->siteUser->pricing_method_id);
                if ($otherinfo == 'occupancy_based_pricing') {
                    $tempnet .= '<BaseByGuestAmt AmountAfterTax="' . $price . '" NumberOfGuests="' . $this->roomObj->site_room_occupancy . '" />';
                } else if ($otherinfo == 'Per_day_Pricing') {
                    $tempnet .= '<BaseByGuestAmt AmountAfterTax="' . $price . '"/>';
                } else {
                    for ($i = 1; $i <= $this->roomObj->site_room_occupancy; $i++) {
                        $tempnet .= '<BaseByGuestAmt AmountAfterTax="' . $price . '" NumberOfGuests="' . $i . '" />';
                    }
                }
            }

            $rateString = '<?xml version="1.0" encoding="utf-8"?>
							<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
								xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
								<soap:Header>
								</soap:Header>
								<soap:Body>
                                    <OTA_HotelRateAmountNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1" >
                                        <RateAmountMessages>
                                            <RateAmountMessage>
                                                <StatusApplicationControl RatePlanID="' . $rateId . '" InvCode="' . $roomId . '"></StatusApplicationControl>
                                                <Rates>
                                                    <Rate Start="' . $start . '" End="' . $end . '" Mon="1" Tue="1" Weds="1" Thur="1" Fri="1" Sat="1" Sun="1"> 
                                                        <BaseByGuestAmts>
                                                            ' . $tempnet . '
                                                        </BaseByGuestAmts>
                                                    </Rate>
                                                </Rates>
                                            </RateAmountMessage>
                                        </RateAmountMessages>
                                    </OTA_HotelRateAmountNotifRQ>
                                    <apikey xmlns="http://ea.citybreak.com">' . $this->siteConfig->def_orgid . '</apikey>
								    <organizationid xmlns="http://ea.citybreak.com">' . $this->siteUser->hotel_id . '</organizationid>
								</soap:Body>
                            </soap:Envelope>';

            $this->curl->headers[4] = "SOAPAction: http://ea.citybreak.com/IOTAService/UpdateRates";
            $this->submitXml($url, $rateString, 'price');
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
