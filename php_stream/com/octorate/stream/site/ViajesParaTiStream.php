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

class ViajesParaTiStream extends AbstractDateStream
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
		$roomobj = $this->roomObj->site_room_id;
		list($roomId, $ratePlanCode) = explode(':', $roomobj);

		if ($cud->isChangedStopSells() && (!$this->isEnabledStopSells()) && $this->isEnabledAvailability() && $cud->availability > 0 && ($cud->stopSells)) {
			//$cud->availability = 0;
			//$this->result->availability = 0;
		}
		if ($this->utlFunc->dateCompare($end, $start) >= 0) {

			$cta_xml = $ctd_xml = $stopsell_xml = $max_xml = $min_xml = '';
			$xml = '';
			if ($this->isSendableCloseToArrival($cud)) {

				$cta_xml = $cud->closeToArrival;
				$xml .= ' noCheckIn="' . $cta_xml . ' "';
			}

			if ($this->isSendableCloseToDeparture($cud)) {

				$ctd_xml = $cud->closeToDeparture;
				$xml .= ' noCheckOut="' . $ctd_xml . '"';
			}

			if ($this->isSendableStopSells($cud)) {

				$stopsell_xml = $cud->stopSells;
				$xml .= ' closed="' . $stopsell_xml . '"';
			}

			if ($this->isSendableMaxstay($cud)) {

				$max_xml = $cud->maxstay;
				$xml .= ' maxNights="' . $max_xml . '"';
			}

			if ($this->isSendableMinstay($cud)) {
				$min_xml = $cud->minstay;
				$xml .= ' minNights="' . $min_xml . '"';
			}
			if ($this->isSendableAvailability($cud)) {

				$alot_xml = $cud->availability;
				$xml .= ' BookingLimit="' . $alot_xml . '"';
			}
			if ($this->roomObj->breakfast > 0) {
				$xml .= ' mealPlanCode="' . $this->roomObj->breakfast . '"';
			}
			if ($roomId == '') {
				throw new \Exception("Submission failed due to room invalid. " . $this->roomObj->site_room_id . ".");
			}
			if ($alot_xml != '' || $stopsell_xml != '' || $cta_xml || $ctd_xml != '') {

				$alotString = '<?xml version="1.0" encoding="UTF-8"?>
                    <HotelAvailNotifRQ>
                        <credentials>
                            <username>' . $this->siteUser->sites_user . '</username>
                            <password>' . $this->siteUser->sites_pass . '</password>
                        </credentials>
                        <AvailStatusMessages>
                            <AvailStatusMessage hotelCode="' . $this->siteUser->hotel_id . '">
                            <availStatus ' . $xml . ' roomCode="' . $roomId . '" start="' . $start . '"  end="' . $end . '" ratePlan="' . $ratePlanCode . '"  />
                            </AvailStatusMessage>
                        </AvailStatusMessages>
                    </HotelAvailNotifRQ>
                    ';
				$this->submitXml('https://stack4-channels.viajesparati.com/octorate/request', $alotString, 'alot');
			}
			//print_r($alotString);
			if ($this->isSendablePrice($cud)) {
				$numbrk = 1;

				if ($this->roomObj->site_room_occupancy > 1) {
					$numbrk = $this->roomObj->site_room_occupancy;
					$occupancy = ' occupation="' . $numbrk . '"';
				}

				$rateString = '<?xml version="1.0" encoding="UTF-8"?>
                    <HotelAvailPriceNotifRQ>
                        <credentials>
						<username>' . $this->siteUser->sites_user . '</username>
						<password>' . $this->siteUser->sites_pass . '</password>
                        </credentials>
                        <rules mealPlanCode="' . $this->roomObj->breakfast . '" ratePlanCode="' . $ratePlanCode . '" hotelCode="' . $this->siteUser->hotel_id . '">
                            <rule roomCode="' . $roomId . '" price="' . $cud->price . '"   start="' . $start . '"  end="' . $end . '"  ' . $occupancy . ' />
                        </rules>
                    </HotelAvailPriceNotifRQ>
                    ';
				$this->submitXml('https://stack4-channels.viajesparati.com/octorate/request', $rateString, 'price');
			}
			//  print_r($rateString);
			// }
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
		$this->reqRespXml .= date("Y-m-d H:i:s") . "::xml=>$xml\nfinalResp=>$result\n\n";
		if ($this->test) {
			$this->reqRespXml;

		}
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