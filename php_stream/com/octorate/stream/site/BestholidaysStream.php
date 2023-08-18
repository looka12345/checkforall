<?php

/*
 * BestholidaysStream.php
 * Octorate srl. All rights reserved. 2019
 */

namespace com\octorate\stream\site;

use com\octorate\stream\common\AbstractDateStream;
use com\octorate\stream\common\CalendarUpdateData;
use com\octorate\stream\common\CalendarUpdateResult;
use com\octorate\stream\utils\cURL;
use com\octorate\stream\utils\UtilFunc;

class BestholidaysStream extends AbstractDateStream {

    protected $reqRespXml = '';
    protected $curl;
    protected $utlFunc;
    protected $result;

    /**
     * Makes xml of inventory.
     * @param start date, end date, cudobj
     * @return
     */
    protected function updateInventory($start, $end, $cud) {
        $priceOnly = '';
        list($roomId, $contractId) = explode(':', $this->roomObj->site_room_id);
        if (preg_match('/@@/is', $this->roomObj->site_room_name)) {
            list($v, $priceOnly) = explode('@@', $this->roomObj->site_room_name);
        }
        if ($this->test) {
            //var_dump($this->roomObj);
            print "priceOnly:$priceOnly\n";
        }

        if ($contractId == '' || $roomId == '') {
            throw new \Exception("Submission failed due to room invalid. " . $this->roomObj->site_room_id . ".");
        }

        $url = 'https://bestholidays.netstorming.net/CM';

        if ($cud->isChangedStopSells() && $this->isEnabledAvailability() && $cud->availability > 0 && ($cud->stopSells)) {
            $cud->availability = 0;
            $this->result->availability = 0;
        }

        $days = $this->utlFunc->dateDiff($start, $end);
        $days++;

        $rateString = '';
        if ($this->isSendablePrice($cud)) {
            $rateString = '<envelope>
						<header>
							<actor>' . $this->siteUser->user_org_id . '</actor>   
							<user>' . $this->siteUser->sites_user . '</user>
							<password>' . $this->siteUser->sites_pass . '</password>
							<version>0.1b</version>
							<timestamp>' . date('YmdHis') . '</timestamp>
							<transaction>' . date('YmdHis') . '</transaction>
						</header>
						<query type="setrate" product="hotel" agrement="' . $contractId . '">
							<roomtype roomcode="' . $roomId . '">
							  <dates>
								<date value="' . $start . '" operation="insert">
                                    <days>' . $days . '</days>
                                    <rate>' . $cud->price . '</rate>
                                </date>
							  </dates>
							</roomtype>
						</query>
					</envelope>';

            if ($this->test) {
                print "rateString:$rateString\n";
            } else {
                $this->submitXml($url, $rateString);
            }
        }

        if (($this->isSendableAvailability($cud)) && ($priceOnly != 'price')) {
            if ($this->test) {
                print "priceOnly2:$priceOnly\n";
            }
            $min_xml = '';
            if ($this->isSendableMinstay($cud)) {
                $min_xml .= '<mstay>' . $cud->minstay . '</mstay>';
            }

            $alotString = '<envelope>
						<header>
							<actor>' . $this->siteUser->user_org_id . '</actor>   
							<user>' . $this->siteUser->sites_user . '</user>
							<password>' . $this->siteUser->sites_pass . '</password>
							<version>0.1b</version>
							<timestamp>' . date('YmdHis') . '</timestamp>
							<transaction>' . date('YmdHis') . '</transaction>
						</header>
						<query type="setavail" product="hotel" agrement="' . $contractId . '">
							<roomtype roomcode="' . $roomId . '" number="' . $cud->availability . '" release="20" deadline="05">
							  <dates>
								<date value="' . $start . '" operation="insert">
                                    <days>' . $days . '</days>
                                    ' . $min_xml . '
                                </date>
							  </dates>
							</roomtype>
						</query>
					</envelope>';

            if ($this->test) {
                print "alotString:$alotString\n";
            } else {
                $this->submitXml($url, $alotString);
            }
        }
        if ($this->test) {
            exit; //not insert record in db
        }
    }

    /**
     * Makes final xml of inventory and submits.
     * @param xml, url
     * @return
     */
    public function submitXml($url, $xml) {
        $this->curl->headers[2] = 'Content-type: application/xml';

        $result = $this->curl_post($url, $xml);
        $result = $this->utlFunc->replaceHtmlChar($result);

        $this->reqRespXml .= date("Y-m-d H:i:s") . "::xml=>$xml\nresult=>$result\n\n";

        $this->checkForSuccess($result);
    }

    /**
     * Function check message of final response.
     * @param result, roomName, roomId
     * @return boolean
     */
    public function checkForSuccess($result) {
        if (preg_match('/type\W+error\W/is', $result, $match)) {
            $reason = '';
            if (preg_match('/<response.*?>(.*?)<\/response/is', $result, $match)) {
                $reason = trim($match[1]);
                $reason = preg_replace('/</', '', $reason);
                $reason = preg_replace('/\/>$/', '', $reason);
                throw new \Exception("Submission failed for update. $reason.");
            }
        }

        if (!(preg_match('/>ok<\/response>/is', $result, $match))) {
            $reason = '';
            if (preg_match('/<response>(.*?)<\/response/is', $result, $match)) {
                $reason = trim($match[1]);
                $reason = preg_replace('/</', '', $reason);
                $reason = preg_replace('/\/>$/', '', $reason);
                throw new \Exception("Submission failed for update. $reason.");
            } else if (preg_match('/>\s*Service Temporarily Unavailable\s*</is', $result, $match)) {
                throw new \Exception("Submission failed for update. Service Temporarily Unavailable.");
            } else {
                throw new \Exception("Submission failed for update.");
            }
        }
    }

}
