<?php

/*
 * CtripStream.php
 * Octorate srl. All rights reserved. 2019
 */

namespace com\octorate\stream\site;

use com\octorate\stream\common\AbstractDateStream;
use com\octorate\stream\common\CalendarUpdateData;
use com\octorate\stream\common\CalendarUpdateResult;
use com\octorate\stream\utils\cURL;
use com\octorate\stream\utils\UtilFunc;

class CtripStream extends AbstractDateStream
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
	
        $roomId = $this->roomObj->site_room_id;

        list($RatePlanCode, $RatePlanCategory) = explode(':', $roomId);
        list($cmpName, $cmpId) = explode('@@', $this->siteConfig->def_orgid);

        if ($this->roomObj->derive_price > 0 && preg_match('/@@/', $this->roomObj->site_room_id)) {
            throw new \Exception('ignored');
        }

        if ($cud->isChangedStopSells() && (!$this->isEnabledStopSells()) && $this->isEnabledAvailability() && $cud->availability > 0 && ($cud->stopSells)) {
            //$cud->availability = 0;
            //$this->result->availability = 0;
        }

        if (preg_match('/(.*?:\/\/.*?)\//is', $this->siteConfig->sites_url, $match)) {
            $site = $match[1];
        }

		if ($this->utlFunc->dateCompare($end, $start) >= 0) {
			$loop = 1;
			$endDtTemp = $end;
			while ($this->utlFunc->dateCompare($end, $endDtTemp) >= 0) {
				if ($loop++ > 3) {
					break;
				}
				$endDtTemp = $this->utlFunc->dateAdd($start, 364);
				if ($this->utlFunc->dateCompare($endDtTemp, $end) >= 0) {
					$endDtTemp = $end;
				}

				$cta_xml = $ctd_xml = $stopsell_xml = $max_xml = $min_xml = '';
				if ($this->isSendableCloseToArrival($cud)) {
					$cta_xml = '</AvailStatusMessage>
					<AvailStatusMessage>
						<StatusApplicationControl RatePlanCategory="' . $RatePlanCategory . '" End="' . $endDtTemp . '" Start="' . $start . '" RatePlanCode="' . $RatePlanCode . '" /><RestrictionStatus Restriction="Arrival" Status="' . ($cud->closeToArrival ? 'Close' : 'Open') . '"/>';
				}

				if ($this->isSendableCloseToDeparture($cud)) {
					$ctd_xml = '</AvailStatusMessage>
					<AvailStatusMessage>
						<StatusApplicationControl RatePlanCategory="' . $RatePlanCategory . '" End="' . $endDtTemp . '" Start="' . $start . '" RatePlanCode="' . $RatePlanCode . '" /><RestrictionStatus Restriction="Departure" Status="' . ($cud->closeToDeparture ? 'Close' : 'Open') . '"/>';
				}

				if ($this->isSendableStopSells($cud)) {
					$stopsell_xml = '</AvailStatusMessage>
					<AvailStatusMessage>
						<StatusApplicationControl RatePlanCategory="' . $RatePlanCategory . '" End="' . $endDtTemp . '" Start="' . $start . '" RatePlanCode="' . $RatePlanCode . '" /><RestrictionStatus Restriction="Master" Status="' . ($cud->stopSells ? 'Close' : 'Open') . '"/>';
				}

				if ($this->isSendableMaxstay($cud)) {
					$max_xml = '<LengthOfStay MinMaxMessageType="SetMaxLOS" Time="' . $cud->maxstay . '" TimeUnit="Day"/>';
				}

				if ($this->isSendableMinstay($cud)) {
					$min_xml = '<LengthOfStay MinMa,mn xMessageType="SetMinLOS" Time="' . $cud->minstay . '" TimeUnit="Day"/>';
				}

				$min_max_xml = '';
				if ($min_xml != '' || $max_xml != '') {
					$min_max_xml = '</AvailStatusMessage>
					<AvailStatusMessage>
						<StatusApplicationControl RatePlanCategory="' . $RatePlanCategory . '" End="' . $endDtTemp . '" Start="' . $start . '" RatePlanCode="' . $RatePlanCode . '" />
						<LengthsOfStay>
							' . $min_xml . $max_xml . '
						</LengthsOfStay>';
				}

				$alot_xml = '';
				if ($this->isSendableAvailability($cud)) {
				
					$alot_xml = 'BookingLimit="' . $cud->availability . '" BookingLimitMessageType="SetLimit"';
				}

				if ($alot_xml != '' || $stopsell_xml != '' || $min_max_xml != '' || $cta_xml || $ctd_xml != '') {
					$alotString = '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
						<soap:Body>
							<OTA_HotelAvailNotifRQ Version="2.1" PrimaryLangID="en-us" TimeStamp="' . date('Y-m-d').'T'.date('H:i:s').'Z' . '" xmlns="http://www.opentravel.org/OTA/2003/05">
								<POS>
									<Source>
										<RequestorID ID="' . $this->siteUser->sites_user . '" MessagePassword="' . $this->siteUser->sites_pass . '" Type="10">
											<CompanyName Code="' . $cmpName . '" CodeContext="' . $cmpId . '">
											</CompanyName>
										</RequestorID>
									</Source>
								</POS>
								<AvailStatusMessages HotelCode="' . $this->siteUser->hotel_id . '">
									<AvailStatusMessage ' . $alot_xml . '>		
										<StatusApplicationControl RatePlanCategory="' . $RatePlanCategory . '" End="' . $endDtTemp . '" Start="' . $start . '" RatePlanCode="' . $RatePlanCode . '" />
										' . $stopsell_xml . '
										' . $cta_xml . '
										' . $ctd_xml . '                                                
										' . $min_max_xml . '
									</AvailStatusMessage>
								</AvailStatusMessages>
							</OTA_HotelAvailNotifRQ>
						</soap:Body>
					</soap:Envelope>';
					//$this->submitXml($site . '/Hotel/OTAReceive/HotelAvailNotif.asmx', $alotString, 'alot');
				}
                 print_r($alotString);
				if ($this->isSendablePrice($cud)) {			
					$numbrk = 1;
					if($this->roomObj->breakfast){
						if($this->roomObj->site_room_occupancy>1){
							$numbrk = $this->roomObj->site_room_occupancy;
						}
					}

					$rateString = '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
						<soap:Body>
							<OTA_HotelRateAmountNotifRQ Version="2.1" PrimaryLangID="en-us" TimeStamp="' . date('Y-m-d').'T'.date('H:i:s').'Z' . '" xmlns="http://www.opentravel.org/OTA/2003/05">
								<POS>
									<Source>
										<RequestorID ID="' . $this->siteUser->sites_user . '" MessagePassword="' . $this->siteUser->sites_pass . '" Type="10">
											<CompanyName Code="' . $cmpName . '" CodeContext="' . $cmpId . '">
											</CompanyName>
										</RequestorID>
									</Source>
								</POS>
								<RateAmountMessages HotelCode="' . $this->siteUser->hotel_id . '">
									<RateAmountMessage>
										<StatusApplicationControl RatePlanCategory="' . $RatePlanCategory . '" RatePlanCode="' . $RatePlanCode . '"/>
										<Rates>
											<Rate Start="' . $start . '" End="' . $endDtTemp . '">
												<BaseByGuestAmts>
													<BaseByGuestAmt Code="Sell" AmountAfterTax="' . $cud->price . '" CurrencyCode="' . $this->currency . '"/>
												</BaseByGuestAmts>
												' . (($this->roomObj->breakfast) ? '<MealsIncluded Breakfast="true" NumberOfBreakfast="' . $numbrk . '"/>' : '<MealsIncluded Breakfast="false" NumberOfBreakfast="0"/>') . '
											</Rate>
										</Rates>
									</RateAmountMessage>
								</RateAmountMessages>
							</OTA_HotelRateAmountNotifRQ>
						</soap:Body>
					</soap:Envelope>';
					//$this->submitXml($site . '/Hotel/OTAReceive/HotelRateAmountNotif.asmx', $rateString, 'price');
				}
				print_r($rateString);
				$endDtTemp = $this->utlFunc->dateAdd($endDtTemp, 1);
				$start = $endDtTemp;
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
		// $result = $this->post($xml,$url);
        // $this->reqRespXml .= date("Y-m-d H:i:s")."::xml=>$xml\nfinalResp=>$result\n\n";
		// if ($this->test) {
		// 	echo $this->reqRespXml;
		// }
        // $this->checkForSuccess($result, $type);
    }

    /**
     * Function check message of final response.
     * @param result, roomName, roomId
     * @return boolean
     */
    public function checkForSuccess($result, $xml_type)
    {
        if (preg_match('/<Errors/', $result, $match)) {
            $errCode = '';$errordet = '';
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
        } elseif(preg_match('/<Success/is',$result,$match)) {
			return 1;
		} else {
			throw new \Exception("Could not update $xml_type");
		}
    }

	function post($xml_data,$url)
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
		if ($resp){
			curl_close ($ch);
		} else {
			if (curl_errno($ch)){
				$resp = curl_error($ch);
			}
			curl_close ($ch);
		}		
		return $resp;
	}
}
