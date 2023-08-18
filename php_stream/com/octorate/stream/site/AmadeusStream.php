<?php

/*
 * AmadeusStream.php
 * Octorate srl. All rights reserved. 2019
 */

namespace com\octorate\stream\site;

use com\octorate\stream\common\AbstractDateStream;
use com\octorate\stream\common\CalendarUpdateData;
use com\octorate\stream\common\CalendarUpdateResult;
use com\octorate\stream\utils\cURL;
use com\octorate\stream\utils\UtilFunc;
use AllowDynamicProperties;

#[AllowDynamicProperties]
class AmadeusStream extends AbstractDateStream
{

    protected $reqRespXml = '';
    protected $curl;
    protected $utlFunc;
    protected $result;
    protected $currencyFlg = 1;

    /**
     * Makes xml of inventory.
     * @param start date, end date, cudobj
     * @return
     */
    protected function updateInventory($start, $end, $cud)
    {
        $site = '';
        $roomId = $this->roomObj->site_room_id;

        if ($roomId == '') {
            throw new \Exception("Submission failed due to room invalid. " . $this->roomObj->site_room_id . ".");
        }

        list($RatePlanCode, $RatePlanCategory) = explode(':', $roomId);
        $singleOccOption = '';
        if (preg_match('/\@\@/is', $roomId)) {
            list($roomId, $singleOccOption) = explode('@@', $roomId);
        }
        //check availability //
        if ($cud->isChangedStopSells() && (!$this->isEnabledStopSells()) && $this->isEnabledAvailability() && $cud->availability > 0 && ($cud->stopSells)) {
            //$cud->availability = 0;
            //$this->result->availability = 0;
        }

        //compare  date //
        if ($this->utlFunc->dateCompare($end, $start) >= 0) {
            $alot_xml = '';

            //check availability//
            if ($this->isSendableAvailability($cud)) {
                $alot_xml = $cud->availability;
            }

            //create xml for update  Availability//
            if ($alot_xml != '') {
                $alotString = '<?xml version="1.0" encoding="UTF-8"?>
                    <soap:Envelope
                        xmlns:wsa="http://schemas.xmlsoap.org/ws/2004/08/addressing"
                        xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-
                                        wssecurityutility-1.0.xsd"
                        xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-
                                        secext-1.0.xsd"
                        xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                        <soap:Header>
                            <wsse:Security soap:mustUnderstand="1">
                                <wsse:UsernameToken wsu:Id="">
                                    <wsse:Username>' . $this->siteUser->sites_user . '</wsse:Username>
                                    <wsse:Password>' . $this->siteUser->sites_pass . '</wsse:Password>
                                    <wsse:PartnerID>' . $this->siteUser->user_org_id . '</wsse:PartnerID>
                                </wsse:UsernameToken>
                            </wsse:Security>
                            <wsa:MessageID>http://localhost</wsa:MessageID>
                            <wsa:To>http://schemas.xmlsoap.org</wsa:To>
                            <wsa:Action>http://rvng.pegs</wsa:Action>
                            <wsa:From>
                                <wsa:Address>http</wsa:Address>
                                <wsa:Reference ChainCode="CG" BrandCode="CL" HotelCode=' . $this->siteUser->hotel_id . '/>
                            </wsa:From>
                        </soap:Header>
                        <soap:Body>
                            <OTA_HotelAvailNotifRQ EchoToken="/" MessageContentCode="1" PrimaryLangID="en-us" Target="Production" TimeStamp="2009-12-02T09:28:19.000" Version="0.001" xsi:schemaLocation= http://www.opentravel.org/OTA/2003/05 OTA_HotelAvailNotifRQ.xsd xmlns="http://www.opentravel.org/OTA/2003/05 "xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                <AvailStatusMessages ChainCode="WL" BrandCode="WL" HotelCode=' . $this->siteUser->hotel_id . '>
                                    <AvailStatusMessage BookingLimit="' . $alot_xml . '" BookingLimitMessageType="SetLimit">
                                        <StatusApplicationControl End="' . $end . '" InvTypeCode="' . $roomId . '" Start="' . $start . '"/>
                                        <UniqueID ID="' . uniqid() . '" Type="16"/>
                                    </AvailStatusMessage>
                                </AvailStatusMessages>
                            </OTA_HotelAvailNotifRQ>
                        </soap:Body>
                    </soap:Envelope>';
                // $this->submitXml($site, $alotString, 'alot');
                print_r($alotString);
            }
            //check price //
            $rateString = '';
            if ($this->isSendablePrice($cud)) {

                $tempnet = '';
                $price = sprintf('%.2f', $cud->price);
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
                //create xml for update  price//

                $rateString = '<xml version="1.0" encoding="UTF-8"?>
                    <soap:Envelope
                        xmlns:wsa="http://schemas.xmlsoap.org/ws/2004/08/addressing"
                        xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurityutility-1.0.xsd"
                        xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
                        xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/ ">&
                                    
                        <soap:Header>&
                                    
                            <wsse:Security soap:mustUnderstand=" 1">
                                <wsse:UsernameToken wsu:Id="">
                                    <wsse:Username>' . $this->siteUser->sites_user . '</wsse:Username>
                                    <wsse:Password>' . $this->siteUser->sites_pass . '</wsse:Password>
                                    <wsse:PartnerID>' . $this->siteUser->user_org_id . '</wsse:PartnerID>
                                </wsse:UsernameToken>
                            </wsse:Security>
                            <wsa:To>https://hotelplatform.services.amadeus.com/dri</wsa:To>
                            <wsa:Action>https://hotelplatform.services.amadeus.com/dri/htng </wsa:Action>
                            <wsa:From>
                                <wsa:Address>http://schemas.xmlsoap.org/ws/2004/08/addressing/role/anonymous</wsa:Address>
                                <wsa:Reference ChainCode="WL" BrandCode="AB" HotelCode="' . $this->siteUser->hotel_id . '"/>
                            </wsa:From>&
                                    
                        </soap:Header>&
                                    
                        <soap:Body>&
                            <OTA_HotelRatePlanNotifRQ
                                xmlns="http://www.opentravel.org/OTA/2003/05" Version="0.001" TimeStamp="' . date('Y-m-d') . 'T' . date('H:i:s') . 'Z' . '"
                                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" MessageContentCode="8"
                                    xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05 OTA_HotelRatePlanNotifRQ.xsd">
                                <RatePlans ChainCode="WL" BrandCode="WL" HotelCode="' . $this->siteUser->hotel_id . '">
                                    <RatePlan Start="' . $start . '" End="' . $end . '" RatePlanCode="' . $RatePlanCode . '" IsCommissionable="true" RatePlanType="13" RatePlanNotifType="Overlay" RestrictedDisplayIndicator="false">
                                        
                                        <Rates>
                                            <Rate Fri="true" Mon="true" Sat="true" Sun="true" Weds="true" InvTypeCode="' . $roomId . '" CurrencyCode="' . $this->currency . '">
                                            <BaseByGuestAmts>
                                            ' . $tempnet . '
                                            </BaseByGuestAmts>
                                        </Rates>
                                    </Rate>
                        
                                    <UniqueID ID="' . uniqid() . '" Type="16"/>
                                </RatePlan>
                            </RatePlans>
                        </OTA_HotelRatePlanNotifRQ>
                    </soap:Body>
                </soap:Envelope>';
                // $this->submitXml($site, $rateString, 'price');
                print_r($rateString);
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
        $result = $this->post($xml, $url);
        //  $result = $this->resxml();
        $this->reqRespXml .= date("Y-m-d H:i:s") . "::xml=>$xml\nfinalResp=>$result\n\n";

        $this->checkForSuccess($result, $type);
    }

    /**
     * Function check message of final response.
     * @param result, roomName, roomId
     * @return boolean
     */
    public function checkForSuccess($result, $xml_type)
    {
        if (preg_match('/<Errors/', $result, $match)) {
            $errCode = '';
            $errordet = '';
            if (preg_match('/<Errors(.*?)<\/Errors/', $result, $match)) {
                $tempFile = $match[1];
                if (preg_match('/ShortText\s*=\s*[\'\"](.*?)[\'\"]/is', $tempFile, $match)) {
                    $error = $match[1];
                }

                if (preg_match('/<Error.*?>(.*?)<\/Error>/is', $tempFile, $match)) {
                    $errordet = $match[1];
                }
                if (preg_match('/Code\s*=\s*[\'\"](.*?)[\'\"]/', $result, $match)) {
                    $errCode = $match[1];
                }
            }

            throw new \Exception("Could not update $xml_type with error --$error-- code => $errCode, $errordet");
        } elseif (preg_match('/<Success/is', $result, $match)) {
            return 1;
        } else {
            throw new \Exception("Could not update $xml_type");
        }
    }

    function post($xml_data, $url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8"));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
        $resp = curl_exec($ch);
        if ($resp) {
            curl_close($ch);
        } else {
            if (curl_errno($ch)) {
                $resp = curl_error($ch);
            }
            curl_close($ch);
        }
        return $resp;
    }


}