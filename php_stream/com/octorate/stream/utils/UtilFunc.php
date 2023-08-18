<?php

/*
 * Database.php
 * Octorate srl. All rights reserved. 2019 
 */

 namespace com\octorate\stream\utils;

class UtilFunc {

    public function dateAdd($d1, $numDays) {
        return date('Y-m-d', strtotime($d1 . ' +' . $numDays . ' days'));
    }

    public function dateSub($d1, $numDays) {
        return date('Y-m-d', strtotime($d1 . ' -' . $numDays . ' days'));
    }

    //date should be in yyyy-mm-dd format
    public function dateCompare($d1, $d2) {
        return ($d1 == $d2) ? 0 : ((strtotime($d1) > strtotime($d2)) ? 1 : -1);
    }

    //date should be in yyyy-mm-dd format
    public function daysInMonth($theMonth, $theYear) {
        return (date("t", mktime(0, 0, 0, $theMonth, '01', $theYear)));
    }

    public function appendFile($fileName, $message) {
        if (gethostname()=='octorate-Creator-Z16-A11UET') {
            // would not work outside server for some path
            return;
        }
        if ($fileName != '') {
            $fh = fopen($fileName, 'a');
            fwrite($fh, $message);
            fclose($fh);
        }
    }

    /**
     * Calculate commission on db price.
     * @param netRate, commission
     * @return netRate
     */
    public function calculateCommisionOnDbPrice($netRate, $commission, $commission_fixed, $commission_round) {
        $commission = trim($commission);
        if ($commission != '' and $commission != 0) {
            $sign = '';
            if (preg_match('/([-+]*)(\d+)\W(\d+)/', $commission, $match)) {
                $sign = $match[1];
                $commission = $match[2] . '.' . $match[3];
            } elseif (preg_match('/([-+]*)(\d+)/', $commission, $match)) {
                $sign = $match[1];
                $commission = $match[2];
            }

            if ($commission_fixed == 1) {
                if ($sign != '') {
                    if ($sign == '-') {
                        $netRate = $netRate - $commission;
                    } elseif ($sign == '+') {
                        $netRate = $netRate + $commission;
                    }
                } else {
                    $netRate = $netRate + $commission;
                }
            } else {
                if ($sign != '') {
                    if ($sign == '-') {
                        $netRate = $netRate - (($netRate * $commission) / 100);
                    } elseif ($sign == '+') {
                        $netRate = $netRate + (($netRate * $commission) / 100);
                    }
                } else {
                    $netRate = $netRate + (($netRate * $commission) / 100);
                }
            }
            if ($commission_round == 1) {
                $netRate = round($netRate);
            }
        }
        $netRate = sprintf('%.2f', $netRate);
        if ($netRate <= 0) {
            $netRate = '';
        }
        return $netRate;
    }

    /**
     * Does some calculations on price.
     * @param netRate, commission
     * @return netRate
     */
    public function calculatePrice($netRate, $commission) {
        if (preg_match('/\%/s', $commission)) {
            $commission = trim($commission);
            if ($commission !== '' and $commission != '0') {
                $sign = '';
                if (preg_match('/([-+]*)(\d+)\W(\d+)/', $commission, $match)) {
                    $sign = $match[1];
                    $commission = $match[2] . '.' . $match[3];
                } elseif (preg_match('/([-+]*)(\d+)/', $commission, $match)) {
                    $sign = $match[1];
                    $commission = $match[2];
                }
                $netRate = (($netRate * $commission) / 100);
                $netRate = sprintf("%.2f", $netRate);
            }
        } else {
            $commission = trim($commission);
            if ($commission != '' and $commission != 0) {
                $sign = '';
                if (preg_match('/([-+]*)(\d+)\W(\d+)/', $commission, $match)) {
                    $sign = $match[1];
                    $commission = $match[2] . '.' . $match[3];
                } elseif (preg_match('/([-+]*)(\d+)/', $commission, $match)) {
                    $sign = $match[1];
                    $commission = $match[2];
                }
                $netRate = sprintf("%.2f", $commission);
            }
        }
        $netRate = sprintf('%.2f', $netRate);
        if ($netRate <= 0) {
            $netRate = '';
        }
        return $netRate;
    }

    /**
     * Replaces some html character.
     * @param result
     * @return return
     */
    public function replaceHtmlChar($result) {
        $result = preg_replace('/\&lt;/', '<', $result);
        $result = preg_replace('/\&gt;/', '>', $result);
        $result = preg_replace('/\&quot;/', '"', $result);
        return $result;
    }

    /**
     * Fetch price from db.
     * @param startDt, endDt, dbRoomId
     * @return array
     */
    public function getdbprice($startDt, $endDt, $dbRoomId, $database, $siteUser, $currency) {
        $endDtTemp = $endDt;
        $dataArr = array();
        while ($this->dateCompare($endDt, $endDtTemp) >= 0) {
            list($yr, $mon, $day) = explode('-', $startDt);
            $endDtTemp = $yr . '-' . $mon . '-' . $this->daysInMonth($mon, $yr);
            if ($this->dateCompare($endDtTemp, $endDt) >= 0) {
                $endDtTemp = $endDt;
            }
            list($yr2, $mon2, $day2) = explode('-', $endDtTemp);
            $monArr = $this->fetchMonStr($day, $day2, $mon, $yr, $dbRoomId, $database, $siteUser, $currency);
            $endDtTemp = $this->dateAdd($endDtTemp, 1);
            $startDt = $endDtTemp;
            foreach ($monArr as $date => $price) {
                $dataArr[$date] = $price;
            }
        }
        //print_r($dataArr);
        return $dataArr;
    }

    /**
     * Fetch price from db.
     * @param day, day2, mon, yr, dbRoomId
     * @return array
     */
    public function fetchMonStr($day, $day2, $mon, $yr, $dbRoomId, $database, $siteUser, $currency) {
        $tmpArr = array();

        if ($dbRoomId != '') {

            $mon = (int) $mon;
            if ($mon < 10) {
                $mon = '0' . $mon;
            }
            if ($mon != '') {
                $table_name_res = $database->fetchAll("SELECT table_name FROM hot_table_lookup WHERE month = ?", [$mon]);
                foreach ($table_name_res as $result) {
                    $tbleNm = $result->table_name;

                    if ($tbleNm != '') {
                        $qry_price_number = $database->fetchAll("select string from " . $tbleNm . " where room_id = ? and year = ?", [$dbRoomId, $yr]);

                        if (count($qry_price_number) > 0) {
                            foreach ($qry_price_number as $res) {
                                $valueArrNew = explode('D', $res->string);
                                foreach ($valueArrNew as $value) {
                                    if ($value != '') {
                                        if (preg_match('/(\d+)R(\d+)P(\d+)M\d+C\d*V(\d+)/is', $value, $match)) {
                                            $date = $match[1];
                                            $alot = (int) $match[2];
                                            $price = (int) $match[3] . '.' . $match[4];
                                            if ($siteUser->conv_curr != '' && $currency != '' && $siteUser->conv_curr != $currency) {
                                                $arr = $database->fetchAll('SELECT ob_convert_price(' . $price . ', ' . $currency . ', ' . $siteUser->conv_curr . ') as price');
                                                $price = $arr[0]->price;
                                            }
                                            $price = $this->calculateCommisionOnDbPrice($price, $siteUser->commission, $siteUser->commission_fixed, $siteUser->commission_round);
                                            if ($date >= $day && $date <= $day2) {
                                                $date1 = "$yr-$mon-$date";
                                                $tmpArr[$date1] = $price;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $tmpArr;
    }

    public function dateDiffNew($d1, $d2) {
        $date1 = new \DateTime($d1);
	$date2 = new \DateTime($d2);
	$interval = $date1->diff($date2);
	return $interval->days;
        /*$flg = $this->dateCompare($d1, $d2);
        if ($flg == -1) {
            $d3 = $d1;
            $d1 = $d2;
            $d2 = $d3;
        } elseif ($flg == 0) {
            return 0;
        }
        return floor((strtotime($d1) - strtotime($d2)) / 86400);*/
    }

    // date should be in yyyy-mm-dd format
    public function dateDiff($d1, $d2) {
        $date1 = new \DateTime($d1);
	$date2 = new \DateTime($d2);
	$interval = $date1->diff($date2);
	return $interval->days;
        /*$flg = $this->dateCompare($d1, $d2);
        if ($flg == -1) {
            $d3 = $d1;
            $d1 = $d2;
            $d2 = $d3;
        } elseif ($flg == 0) {
            return 0;
        }
        return floor((strtotime($d1) - strtotime($d2)) / 86400);*/
    }

    public function changeFormatAll($date, $inputFormat = '', $outputFormat = '') {
        if ($inputFormat == '' or $inputFormat == 'mdy') {
            list ($mon1, $day1, $yr1) = explode('/', $date);
        } elseif ($inputFormat == 'dmy') {
            list ($day1, $mon1, $yr1) = explode('/', $date);
        } elseif ($inputFormat == 'ymd') {
            list ($yr1, $mon1, $day1) = explode('-', $date);
        }
        $day1 = substr('00' . $day1, -2);
        $mon1 = substr('00' . $mon1, -2);
        if ($outputFormat == '' or $outputFormat == 'dmy') {
            return ($day1 . '/' . $mon1 . '/' . $yr1);
        } elseif ($outputFormat == 'mdy') {
            return ($mon1 . '/' . $day1 . '/' . $yr1);
        } elseif ($outputFormat == 'ymd') {
            return ($yr1 . '-' . $mon1 . '-' . $day1);
        }
    }

    public function after($inthis, $inthat) {
        if (!is_bool(strpos($inthat, $inthis)))
            return substr($inthat, strpos($inthat, $inthis) + strlen($inthis));
    }

    public function before($inthis, $inthat) {
        return substr($inthat, 0, strpos($inthat, $inthis));
    }

    public function parseOneValue($label, $tempFile) {
        $regex = "/$label\s*=\s*[\"'](.*?)[\"']/is";
        if (preg_match($regex, $tempFile, $match)) {
            return \trim($match[1]);
        } else {
            return NULL;
        }
    }

    public function parseXmlValue($label, $tempFile) {
        $regex = "/<$label.*?>(.*?)<\/$label>/is";
        if (preg_match($regex, $tempFile, $match)) {
            $value = $match[1];
            if(preg_match('/CDATA/is',$value,$m)) {
                $value = preg_replace('/<!\[CDATA\[/','',$value);
                $value = preg_replace('/\]\]>/','',$value);
            }
            return \trim($value);
        } else {
            return NULL;
        }
    }

    /**
     * @return result
     */
    public function submitXmlPost($url, $content, $headers) {
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_TIMEOUT, 200);
        curl_setopt($process, CURLOPT_POSTFIELDS, $content);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_POST, 1);
        $return = curl_exec($process);
        curl_close($process);
        return $return;
    }

    /**
     * @return result
     */
    public function submitXmlAuthPost($url, $content, $headers, $user, $pass) {
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_CONNECTTIMEOUT, 600);
        curl_setopt($process, CURLOPT_TIMEOUT, 1800);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_POSTFIELDS, $content);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 2);
        //curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($process, CURLOPT_USERPWD, $user . ":" . $pass);
        curl_setopt($process, CURLOPT_POST, 1);
        curl_setopt($process, CURLOPT_HEADER, true);
        $return = curl_exec($process);
        curl_close($process);

        return $return;
    }

    /**
     * @return result
     */
    public function submitXmlSaop($url, $content, $soapaction) {
        $soapClient = new nusoap_client("$url");
        $soapClient->call($content, '', '', '', $soapaction);
        return $soapClient->response;
    }

    /**
     * @return result
     */
    public function submitXmlSaopTime($url, $content, $soapaction='', $connTime='', $timeout='') {
        $soapClient = new nusoap_client("$url", '', '', '', '', '', "$connTime", "$timeout");
        $soapClient->call($content, "$url");
        return $soapClient->response;
    }

    /**
     * @return array of getDatesFromRange
     */
    public function getDatesFromRange($start, $end, $format = 'Y-m-d') {
        $array = array();
        if ((round(abs(strtotime($start) - strtotime($end)) / 86400)) > 1) {
            $interval = new \DateInterval('P1D');
            $realEnd = new \DateTime($end);
            //$realEnd->add($interval);

            $period = new \DatePeriod(new \DateTime($start), $interval, $realEnd);
            foreach ($period as $date) {
                $array[] = $date->format($format);
            }
        } else {
            $date = new \DateTime($start);
            $array[] = $date->format($format);
        }

        return $array;
    }

    /**
     * @param object $res for all reservation details
     * @return json of reservation details
     */
    public function createVoucherV2($res, $room) {
        $voucherJson = Array();
        return $voucherJson; //to avoid duplicate fields of conferme table

        $voucherJson['Reservation_Id'] = $res->refer;
        $voucherJson['Reservation_Status'] = $res->status;
        $voucherJson['CheckIn'] = $room->checkIn;
        $voucherJson['CheckOut'] = $room->checkOut;
        $voucherJson['FirstName'] = $res->guest->firstName;
        $voucherJson['LastName'] = $res->guest->lastName;
        $voucherJson['Email'] = $res->guest->email;
        $voucherJson['Phone'] = $res->guest->phone;
        $voucherJson['Address'] = $res->guest->address;
        $voucherJson['City'] = $res->guest->city;
        $voucherJson['Country'] = $res->guest->country;
        $voucherJson['Zip'] = $res->guest->zip;
        $voucherJson['Number_Of_Guests'] = $room->pax;
        $voucherJson['Number_Of_Childs'] = $room->children;
        $voucherJson['Booking_Date'] = $res->createDate;
        $voucherJson['LastModified_Date'] = $res->updateDate;
        $voucherJson['Total_Price'] = $room->total;
        $voucherJson['Total_Paid'] = $room->totalPaid;
        $voucherJson['Language'] = $res->language;
        $voucherJson['Special_Requests'] = $room->notes;
        $dayPriceArr = Array();
        foreach ($room->daily as $daily) {
            $dayPriceArr[$daily->day] = $daily->price;
        }
        $voucherJson['Day_Prices'] = $dayPriceArr;
        return $voucherJson;
    }

    /**
     * @return PhoneNumber
     */
    public function parsePhoneNum($res) {
        if (preg_match('/<Telephone.*?\/>/is', $res, $match)) {
            $tempFile = $match[0];
            $ccode = $this->parseOneValue('CountryAccessCode', $tempFile);
            $cacode = $this->parseOneValue('AreaCityCode', $tempFile);
            $num = $this->parseOneValue('PhoneNumber', $tempFile);
            $ext = $this->parseOneValue('Extension', $tempFile);
            return $ccode . '-' . $cacode . '-' . $num . '-' . $ext;
        } else {
            return null;
        }
    }

    /**
     * @return string of special request
     */
    public function parseSpecialRequest($res) {
        $returString = '';
        $language = '';
        $country = '';
        while (preg_match("/(<SpecialRequest(.*?)>(.*?)<\/SpecialRequest>)/is", $res, $match)) {
            $res = $this->after($match[0], $res);
            if (preg_match("/Language\s*=\s*[\"'](.*?)[\"']/is", $match[1], $m)) {
                list($language, $country) = explode('-', $m[1]);
            } elseif (preg_match("/Language\s*=\s*[\"'](.*?)[\"']/is", $match[2], $c)) {
                list($language, $country) = explode('-', $c[1]);
            }
            $match[3] = preg_replace('/<.*?>/', '', $match[3]);
            $match[3] = preg_replace('/\s+/', ' ', $match[3]);
            $returString .= $match[3] . ",";
        }
        //$returString = preg_replace('/,$/', '', $returString);
        return trim($returString, ',') . '##' . $language;
    }

    /**
     * @return string of guest name
     */
    public function parseGuestName($res, $parent_tag, $fn_tag, $ln_tag) {
        $firstname = '';
        $surname = '';
        $regex = "/<$parent_tag.*?>(.*?)<\/$parent_tag>/is";
        $guestArr = [];
        $loop = 1;
        while (preg_match($regex, $res, $m)) {
            $res = $this->after($m[0], $res);
            $guestdetail = $m[1];
            //$firstname .= substr($this->parseXmlValue($fn_tag, $guestdetail), 0, 40) . ',';
            $firstnameTmp = $this->parseXmlValue($fn_tag, $guestdetail);
            //$surname .= substr($this->parseXmlValue($ln_tag, $guestdetail), 0, 40) . ',';
            $surnameTmp = $this->parseXmlValue($ln_tag, $guestdetail);
            if(array_key_exists($firstnameTmp . '##' . $surnameTmp, $guestArr)){
                ;//already exists, ingnore for merge again
            } else {
                $guestArr[$firstnameTmp . '##' . $surnameTmp] = 1;
            }
            if($loop++ > 20){
                break;
            }
        }
        foreach($guestArr as $keyTmp => $v){
            list($firstnameTmp, $surnameTmp) = explode('##',$keyTmp);
            $firstname .= $firstnameTmp . ',';
            $surname .= $surnameTmp . ',';
        }
        $firstname = trim($firstname, ',');
        $surname = trim($surname, ',');
        $firstname = substr($firstname, 0, 40);
        $surname = substr($surname, 0, 40);
        
        return $firstname . '##' . $surname;
    }

    /**
     * @return string of guest count
     */
    public function parseGuestCount($res, $parent_tag) {
        $pax = 0;
        $regex = "/<$parent_tag.*?>(.*?)<\/$parent_tag>/is";
        if (preg_match($regex, $res, $match)) {
            $guestDet = $match[1];
            while (preg_match('/Count\s*=\s*"(.*?)"/is', $guestDet, $match)) {
                $guestDet = $this->after($match[0], $guestDet);
                $pax += trim($match[1]);
            }
        }
        return $pax;
    }

    /**
     * @return string of guest count
     */
    public function parseGuestCountNew($res, $parent_tag, $child_tag) {
        $adult = 0;
        $child = 0;
        $infant = 0;
        $regex = "/<$parent_tag.*?>(.*?)<\/$parent_tag>/is";
        if (preg_match($regex, $res, $match)) {
            $guestDet = $match[1];
            $regex = "/<$child_tag(.*?)\/>/is";
            while (preg_match($regex, $guestDet, $match)) {
                $guestDet = $this->after($match[0], $guestDet);
                $ageQualifyingCode = $this->parseOneValue('AgeQualifyingCode', $match[1]);
                $count = $this->parseOneValue('Count', $match[1]);
                if ($ageQualifyingCode == '10' && $count > 0) {
                    $adult += $count;
                } elseif ($ageQualifyingCode == '8' && $count > 0) {
                    $child += $count;
                } elseif (($ageQualifyingCode == '7' || $ageQualifyingCode == '3') && $count > 0) {
                    $infant += $count;
                }
            }
        }
        return ([$adult,$child,$infant]);
    }

    function parseGuestComment($res, $parent_tag, $child_tag1, $child_tag2) {
        $commentinfo = '';
        $comments_details = $this->parseXmlValue($parent_tag, $res);
        $regex = "/<$child_tag1.*?>(.*?)<\/$child_tag1>/is";
        while (preg_match($regex, $comments_details, $match)) {
            $comment = $match[1];
            $comments_details = $this->after($match[0], $comments_details);
            $commentTmp = $this->parseXmlValue($child_tag2, $comment);
            if($commentTmp){
                $commentinfo .= $commentTmp . "\n";
            }
        }
        return $commentinfo;
    }

    /**
     * @return clienti fields
     */
    public function getUserDetail($codice) {
        return $this->database->fetchObject('SELECT `tax_included`,`tasse` from `clienti` where `codice`=?', [$codice]);
    }

    /**
     * @return clienti fields
     */
    public function getSiteDetail($sites_id) {
        return $this->database->fetchObject('SELECT * FROM `hot_sites` where sites_id=?', [$sites_id]);
    }

    /**
     * @return room fields
     */
    public function getRoomDetail($site_room_id, $site_id) {
        return $this->database->fetchObject('SELECT h.site_room_name,h.site_room_occupancy from `hot_site_rooms` as h, `hot_sites_user` as u where h.site_room_id=? and h.site_id=u.ID and u.sites_id=?', [$site_room_id, $site_id]);
    }

    /**
     * @return user fields
     */
    public function getHotelDetail($hotel_id, $site_id) {
        return $this->database->fetchObject('SELECT sites_user,sites_pass,user_org_id from `hot_sites_user` where hotel_id=? and sites_id=?', [$hotel_id, $site_id]);
    }

    /**
     * Mark the connection with hotelId to be pulled soon.
     * @param string $hotelId
     */
    public function schedulePullReservations($sites_id, $hotelId) {
        if ($sites_id && $hotelId) {
            $stm = $this->database->executeQuery('UPDATE hot_sites_user SET last_pull = NULL WHERE resa = TRUE AND sites_id = ? AND hotel_id = ?', [$sites_id, $hotelId]);
        }
    }

    /**
     * Add reservations XML in log db if not already exists.
     * @param string $refer for reservation id, $status for reservation status, $xml for reservation xml, $lasmodify for last modification time
     * @return NULL.
     */
    public function insertXml($refer, $status, $xml, $lastmodify, $property_id = NULL, $sites_id, $processed = 1, $checkout = NULL) {
        //return 1;//due to server issue not storing
        if (defined('DB_RESA_XML_TABLE')) {
            try {
                $xmlZip = gzcompress($xml, 9);
                if ($lastmodify == NULL) {
                    $q = 'SELECT `id` FROM ' . DB_RESA_XML_TABLE . ' WHERE `hot_sites_id`=? AND `refer`=? AND `status`=?';
                    $p = [$sites_id, $refer, $status];
                } else {
                    $q = 'SELECT `id` FROM ' . DB_RESA_XML_TABLE . ' WHERE `hot_sites_id`=? AND `refer`=? AND `status`=? AND `lastmodify_time`=?';
                    $p = [$sites_id, $refer, $status, $lastmodify];
                }
                $r = $this->logs->executeQuery($q, $p);
                //$r = $this->database->executeQuery($q, $p);

                if ($r->rowCount() > 0) {
                    $row = $r->fetchObject();
                    return $row->id; // same record already exists so not inserting double record
                } else {
                    $q = 'INSERT INTO ' . DB_RESA_XML_TABLE . ' (`hot_sites_id`, `refer`, `status`, `xml`, `lastmodify_time`, `property_id`, `processed`, `checkout`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                    $p = [$sites_id, $refer, $status, $xmlZip, $lastmodify, $property_id, $processed, $checkout];
                    $r = $this->logs->executeQuery($q, $p);
                    return $this->logs->lastInsertId();
                    //$r = $this->database->executeQuery($q, $p);
                    //return $this->database->lastInsertId();
                }
            } catch (Exception $ex) {
                // ignore error
            }
        }
    }

    /**
     * Add reservations confirmation request-response xml in log db.
     * @param string $id for php_stream_reservation_xml.id
     * @param string $reqXml for confirmation request xml
     * @param string $respXml for confirmation response xml
     * @return NULL.
     */
    public function insertConfirmationXml($id, $reqXml, $respXml) {
        //return 1;//due to server issue not storing
        if (defined('DB_RESA_CONF_TABLE')) {
            try {
                $reqXmlZip = gzcompress($reqXml, 9);
                $respXmlZip = gzcompress($respXml, 9);
                if ($id > 0) {
                    $q = 'SELECT `id` FROM ' . DB_RESA_CONF_TABLE . ' WHERE `id`=?';
                    $p = [$id];
                    $r = $this->logs->executeQuery($q, $p);
                    //$r = $this->database->executeQuery($q, $p);
                    if ($r->rowCount() > 0) {
                        //already confirmed;
                    } else {
                        $q = 'INSERT INTO ' . DB_RESA_CONF_TABLE . ' (`id`, `confirmation_req`, `confirmation_resp`) VALUES (?, ?, ?)';
                        $p = [$id, $reqXmlZip, $respXmlZip];
                        $this->logs->executeQuery($q, $p);
                        //$this->database->executeQuery($q, $p);
                    }
                }
            } catch (Exception $ex) {
                // ignore error
            }
        }
    }

    public function getPricingMethod($method_id) {
        $pricing = $this->database->fetchObject("select `pricing_name` from `hot_pricing_method` where `id` = ?", [$method_id]);
        $pricing_name = 0;
        if (isset($pricing)) {
            $pricing_name = $pricing->pricing_name;
        }
        return $pricing_name;
    }

    /**
     * @return last change time of refer and return true or false
     */
    public function checklastChange($sites_id, $hotelCode, $refer, $status, $lastChange) {
        //return 1;//due to server issue not storing
        $q = 'SELECT `lastmodify_time` FROM php_stream_reservation_xml WHERE `hot_sites_id`=? AND `refer`=? AND `property_id`=? AND `status`=? order by id DESC limit 1';
        $p = [$sites_id, $refer, $hotelCode, $status];
        //$row = $this->logs->fetchObject($q, $p);
        $row = $this->database->fetchObject($q, $p);
        if ($row->lastmodify_time == $lastChange) {
            return 0;
        } else {
            return 1;
        }
    }
    
    /**
     * @return last change time of refer and return true or false
     */
    public function checklastChangeHN($sites_name, $hotelCode, $refer, $lastChange) {
        //return 1;//due to server issue not storing
        $q = 'SELECT `LastChange` FROM ota_push_dtls WHERE `refer`=? AND `hotelid`=? AND `ota_name`=? order by id DESC limit 1';
        $p = [$refer, $hotelCode, $sites_name];
        //$row = $this->logs->fetchObject($q, $p);
        $row = $this->database->fetchObject($q, $p);
        if ($row->LastChange == $lastChange) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * @return xml according to refer and site id
     */
    public function getXmlFromDb($siteId, $refer) {
        //return 1;//due to server issue not storing
        if (defined('DB_RESA_XML_TABLE')) {
            $q = 'SELECT * FROM ' . DB_RESA_XML_TABLE . ' where hot_sites_id=? and refer=? order by id DESC limit 1';
            $p = [$siteId, $refer];
            $r = $this->logs->fetchObject($q, $p);
            //$r = $this->database->fetchObject($q, $p);
            return gzuncompress($r->xml);
        }
        return NULL;
    }

    /**
     * @return result
     */
    public function get($url, $headers) {
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_COOKIEFILE, '');
        curl_setopt($process, CURLOPT_COOKIEJAR, '');
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_HEADER, false);
        curl_setopt($process, CURLOPT_TIMEOUT, 200);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, 'GET');
        $return = curl_exec($process);
        curl_close($process);

        return $return;
    }

    /**
     * @return pax
     */
    public function parsePaxValue($res) {
        $guest_data = $this->parseXmlValue('GuestCounts', $res);
        $totalChilds = 0;
        $totalAdults = 0;
        $loop = 0;
        while (preg_match("/(<GuestCount.*?\/>)/is", $guest_data, $match)) {
            $guest_data = $this->after($match[0], $guest_data);
            $ageQualifyingCode = $this->parseOneValue('AgeQualifyingCode', $match[1]);
            $count = $this->parseOneValue('Count', $match[1]);
            if($count>0){;} else {$count = 1;}
            if ($ageQualifyingCode == '8') {
                //$totalChilds += 1;
                $totalChilds += $count;
            } elseif ($ageQualifyingCode == '10') {
                //$totalAdults += 1;
                $totalAdults += $count;
            }
            if ($loop++ > 100) {
                break;
            }
        }
        return array($totalAdults, $totalChilds);
    }

    function getCredentialsNew($roomId, $hotelId, $siteid) {
        $codiceId = '';
        $userIdList = '';
        $roomIdTmp = $roomId . 'A';
        list($roomid1, $rateId1) = explode(':', $roomId);

        $hot_sites_user_arr = Array();
        $room_map_restriction_arr = Array();

        $rows = $this->database->fetchAll("SELECT `ID`,`sites_asso_id`,`roomnotmap_resa` FROM `hot_sites_user` WHERE `hotel_id`=? AND `sites_id`=? ORDER BY `ID` DESC limit 20", [$hotelId, $siteid]);
        foreach ($rows as $r) {
            $hot_sites_user_arr[$r->ID] = $r->sites_asso_id;
            $room_map_restriction_arr[$r->sites_asso_id] = $r->roomnotmap_resa;
            $userIdList .= $r->ID . ',';
        }

        if ($userIdList != '') {
            $userIdList = rtrim($userIdList, ',');

            $hot_site_rooms_arr = Array();
            $rows1 = $this->database->fetchAll("SELECT `ID`,`user_id` FROM `hot_site_rooms` WHERE (`site_room_id`=? or `site_room_id`=? or `site_room_id` like '$roomid1:%') AND `site_id` IN ($userIdList) ORDER BY `ID` DESC limit 100", [$roomId, $roomIdTmp]);
            foreach ($rows1 as $r1) {
                $hot_site_rooms_arr[$r1->ID] = $r1->user_id;
            }

            $hot_sites_map_arr = Array();
            foreach ($hot_site_rooms_arr as $id => $user_id) {
                $rows2 = $this->database->fetchAll("SELECT `ext_site_id` FROM `hot_sites_map` WHERE `site_ext_id`=? AND `ext_site_id` IN ($userIdList) ORDER BY `ID` DESC limit 100", [$id]);
                foreach ($rows2 as $r2) {
                    $hot_sites_map_arr[$id] = $r2->ext_site_id;
                }
            }

            if (count($hot_sites_map_arr) > 0) {
                foreach ($hot_sites_map_arr as $id => $ext_site_id) {
                    if (array_key_exists($ext_site_id, $hot_sites_user_arr)) {
                        if ($hot_sites_user_arr[$ext_site_id] != '') {
                            $codiceId = $hot_sites_user_arr[$ext_site_id];
                            break;
                        }
                    }
                }
            }
        }

        if ($codiceId == '') {
            foreach ($hot_sites_user_arr as $id => $sites_asso_id) {
                if (array_key_exists($sites_asso_id, $room_map_restriction_arr)) {
                    $activeVal = $room_map_restriction_arr[$sites_asso_id];
                    if ($activeVal == '0') {
                        $codiceId = $sites_asso_id;
                        break;
                    }
                }
            }
        }

        if ($codiceId == '') {
            foreach ($hot_sites_user_arr as $id => $sites_asso_id) {
                $codiceId = $sites_asso_id;
                break;
            }
        }

        if ($codiceId != '') {
            $r3 = $this->database->fetchObject("select `tax_included`,`tasse` from `clienti` where `codice`=?", [$codiceId]);
            return [$codiceId, $r3->tax_included, $r3->tasse];
        } else {
            return [NULL, NULL, NULL];
        }
    }

    function getCodiceTax($codiceId) {
        $r3 = $this->database->fetchObject("select `tax_included`,`tasse` from `clienti` where `codice`=?", [$codiceId]);
        return [$r3->tax_included, $r3->tasse];
    }

    function get_External_sites_setting_Value($codice,$site,$type) {
        $value = 0;
        $selArr = $this->database->fetchObject("SELECT `type_val`, `type` from `external_sites_settings` where `codice`=? and `site`=? and `type`=?", [$codice, $site, $type]);
        if (!empty($selArr)) {
            if ($selArr->type_val == '1') {
                $value = 1;
            }
        }
        return $value;
    }

    function getCalculatedTax($netPrice, $tasse) {
        if ($netPrice == '') {
            return '0';
        }
        if (preg_match('/(\d+)/', $tasse, $m)) {
            $percVal = $m[1];
            $tasse = ($netPrice * $percVal) / 100;
            $tasse = sprintf('%.2f', $tasse);
            return $tasse;
        } else {
            return '0';
        }
    }

    function getLogPath() {
        if (gethostname() === 'devin-XPS-15-9550') {
            return '/tmp/'.date('Y-m-d') . '/';
        }
        $fullPath = '/home/magneto/sites/admin.octorate.com/logs/';
        $path = $fullPath . date('Y-m-d') . '/';
        if (!file_exists($path) && !is_dir($path)) {
            mkdir($path, 0777);
        }
        return $fullPath . date('Y-m-d') . '/';
    }

    /**
     * Add reservations log in log db.
     * @param string $xml for reservation xml
     * @return NULL.
     */
    public function insertResaLog($sites_id, $xml) {
        //return 1;//due to server issue not storing
        if (defined('DB_RESA_LOGS_TABLE')) {
            try {
                //$xmlZip = gzcompress($xml, 9);
                $xmlZip = utf8_encode($xml); //for testing, later save in zip format
                $q = 'INSERT INTO ' . DB_RESA_LOGS_TABLE . ' (`hot_sites_id`, `reqResp`) VALUES (?, ?)';
                $p = [$sites_id, $xmlZip];
                $r = $this->logs->executeQuery($q, $p);
            } catch (Exception $ex) {
                // ignore error
            }
        }
    }

    /**
     * @return boolean true if hostname cloud
     */
    public static function isCloudTest(){
        return gethostname() == 'octocloud';
    }

    /**
     * @return bool if local test
     */
    public static function isLocalTest() {
        return gethostname() == 'octorate-Creator-Z16-A11UET';
    }

    /**
     * @return mixed|string the path where the main files are located
     */
    public static function getBasePath() {
        switch (gethostname()) {
            case 'octocloud';
                return "/home/git/projects/php-stream";
            case 'octorate-Creator-Z16-A11UET':
                return "/home/octorate/Documenti/php/php-stream";
            default:
                return $_SERVER['DOCUMENT_ROOT'];
        }
    }
    
    /**
     * @return date difference should be in hourly format
     */
    public static function hourdiff($time1, $time2) {
        return round((strtotime($time1) - strtotime($time2))/3600, 1);
    }

}
