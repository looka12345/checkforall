<?php

/*
 * AbstractPullStream.php
 * Octorate srl. All rights reserved. 2019 
 */

namespace com\octorate\stream\common;

use com\octorate\stream\utils\cURL;
use com\octorate\stream\utils\UtilFunc;
use com\octorate\stream\common\AbstractStream;

include 'D:\xampp\htdocs\xampp\Netbeansprojects\php_stream\com\octorate\stream\common\AbstractStream.php';


abstract class AbstractPullStream extends AbstractStream {

    protected $curl;
    protected $utilFunc;

    /**
     * Pull reservations from externale site.
     * @return array of PullReservation.
     */
    public abstract function pullReservations();

    /**
     * Pull reservations from externale site without selecting by property id.
     * @return array of PullReservation.
     */
    public function pullGlobal() {
        return [];
    }

    /**
     * Mark the connection with hotelId to be pulled soon.
     * @param string $hotelId
     */
    protected function schedulePullReservations($hotelId) {
        if ($this->siteConfig && $hotelId) {
            $par = [$this->siteConfig->sites_id, $hotelId];
            $sql = 'UPDATE hot_sites_user SET last_pull = NULL WHERE resa = TRUE AND sites_id = ? AND hotel_id = ?';
            $stm = $this->database->executeQuery($sql, $par);
        }
    }

    /**
     * @return array of refer and resvModificationDateTime
     */
    public function getResaLastModifyRef($allreferStr) {
        $q = 'select `refer_disp`, `ricevutah`, `timestamp`, `resvModificationDateTime` from conferme where codice = ? and sito = ?  and refer_disp IN (' . $allreferStr . ')';
        $p = [$this->siteUser->sites_asso_id, $this->siteConfig->sites_name];
        $rows = $this->database->fetchAll($q, $p);
        $map = [];
        foreach ($rows as $r) {
            $map[$r->refer_disp]['timestamp'] = $r->timestamp;
            $map[$r->refer_disp]['resvModificationDateTime'] = $r->resvModificationDateTime;
            $map[$r->refer_disp]['ricevutah'] = $r->ricevutah;
        }
        return $map;
    }

    /**
     * @return array of refer and resvModificationDateTime
     */
    public function getResaLastModify($lastModifyTm) {
        $q = '';
        $p = '';
        if ($this->siteConfig->sites_id == '249') {
            $q = "select `refer_disp`, `ricevutah`, `timestamp`, `resvModificationDateTime` from conferme where codice = ? and sito = ?  and date_2x > ?";
            $p = [$this->siteUser->sites_asso_id, $this->siteConfig->sites_name, $lastModifyTm];
        } else {
            $q = "select `refer_disp`, `ricevutah`, `timestamp`, `resvModificationDateTime` from conferme where codice = ? and sito = ?  and timestamp > ?";
            $p = [$this->siteUser->sites_asso_id, $this->siteConfig->sites_name, $lastModifyTm];
        }
        $rows = $this->database->fetchAll($q, $p);
        $map = [];
        foreach ($rows as $r) {
            $map[$r->refer_disp]['timestamp'] = $r->timestamp;
            $map[$r->refer_disp]['resvModificationDateTime'] = $r->resvModificationDateTime;
            $map[$r->refer_disp]['ricevutah'] = $r->ricevutah;
        }
        return $map;
    }

    /**
     * @return object
     */
    protected function getInternalRoom($externalRoomId) {
        $this->roomObj = NULL;
        $this->roomObjAll = $this->getSiteRooms();
        if ($this->test) {
            //print "externalRoomId=>$externalRoomId\n";
            //var_dump($this->roomObjAll);
        }
        foreach ($this->roomObjAll as $singleRoomObj) {
            if ($singleRoomObj->site_room_id == $externalRoomId) {
                $this->roomObj = $singleRoomObj;
            } else {
                if ((strpos($singleRoomObj->site_room_id, ":") !== false) && (strpos($externalRoomId, ":") !== false)) {
                    list($dbRoomId, $dbRateId) = explode(":", $singleRoomObj->site_room_id);
                    list($xmlRoomId, $xmlRateId) = explode(":", $externalRoomId);
                    if ($dbRoomId === $xmlRoomId) {
                        $this->roomObj = $singleRoomObj;
                    }
                }
            }
        }
        if ($this->roomObj) {
            $hotRoomType = $this->database->fetchObject('SELECT breakfast, no_members'
                    . ' FROM hot_room_type WHERE room_id = ('
                    . ' SELECT site_int_id FROM hot_sites_map'
                    . ' WHERE site_ext_id = ? and ext_site_id = ?)',
                    [$this->roomObj->ID, $this->siteUser->ID]);
            if ($this->test) {
                //var_dump($hotRoomType);
                //var_dump($this->roomObj->ID);
                //var_dump($this->siteUser->ID);
            }
            return $hotRoomType;
        }
        return NULL;
    }

    /**
     * Add reservations XML in log db if not already exists.
     * @param string $refer for reservation id, $status for reservation status, $xml for reservation xml, $lasmodify for last modification time
     * @return NULL.
     */
    public function insertXml($refer, $status, $xml, $lastmodify, $property_id = NULL, $processed = 1, $checkout = NULL) {
        //return 1;//due to server issue not storing
        if (defined('DB_RESA_XML_TABLE')) {
            try {
                $xmlZip = gzcompress($xml, 9);
                if ($lastmodify == NULL) {
                    $q = 'SELECT `id` FROM ' . DB_RESA_XML_TABLE . ' WHERE `hot_sites_id`=? AND `refer`=? AND `status`=?';
                    $p = [$this->siteConfig->sites_id, $refer, $status];
                } else {
                    $q = 'SELECT `id` FROM ' . DB_RESA_XML_TABLE . ' WHERE `hot_sites_id`=? AND `refer`=? AND `status`=? AND `lastmodify_time`=?';
                    $p = [$this->siteConfig->sites_id, $refer, $status, $lastmodify];
                }
                $r = $this->getLogs()->executeQuery($q, $p);
                //$r = $this->database->executeQuery($q, $p);
//                if ($this->test) {
//                    var_dump($p);
//                    var_dump($r);                    
//                }

                if ($r->rowCount() > 0) {
                    $row = $r->fetchObject();
//                    if ($this->test) {
//                        var_dump($row);
//                    }
                    return $row->id; // same record already exists so not inserting double record
                } else {
                    $q = 'INSERT INTO ' . DB_RESA_XML_TABLE . ' (`hot_sites_id`, `refer`, `status`, `xml`, `lastmodify_time`, `property_id`,`processed`,`checkout`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                    $p = [$this->siteConfig->sites_id, $refer, $status, $xmlZip, $lastmodify, $property_id, $processed, $checkout];
//                    if ($this->test) {
//                        var_dump($p);
//                    }
                    $r = $this->getLogs()->executeQuery($q, $p);
                    return $this->getLogs()->lastInsertId();
                    //$r = $this->database->executeQuery($q, $p);
                    //return $this->database->lastInsertId();
                }
            } catch (Exception $ex) {
                // ignore error
            }
        }
    }

    /**
     * Check room existence in db before send to G.
     * @param string $room_id for hot_room_type.room_id
     * @return boolean SUCCESS.
     */
    public function checkInternalRoomExistence($room_id) {
        $r = $this->database->executeQuery('SELECT `room_id` FROM `hot_room_type` WHERE `room_id`=?', [$room_id]);
        if ($r->rowCount() > 0) {
            return 1;
        }
    }
    
    public function get_External_sites_setting_Value($codice,$site,$type) {
        $value = 0;
        $selArr = $this->database->fetchObject("SELECT `type_val`, `type` from `external_sites_settings` where `codice`=? and `site`=? and `type`=?", [$codice, $site, $type]);
        if (!empty($selArr)) {
            if ($selArr->type_val == '1') {
                $value = 1;
            }
        }
        return $value;
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
                    $r = $this->getLogs()->executeQuery($q, $p);
                    //$r = $this->database->executeQuery($q, $p);
                    if ($r->rowCount() > 0) {
                        //already confirmed;
                    } else {
                        $q = 'INSERT INTO ' . DB_RESA_CONF_TABLE . ' (`id`, `confirmation_req`, `confirmation_resp`) VALUES (?, ?, ?)';
                        $p = [$id, $reqXmlZip, $respXmlZip];
//                        if ($this->test) {
//                            var_dump($p);
//                        }
                        $this->getLogs()->executeQuery($q, $p);
                        //$this->database->executeQuery($q, $p);
                    }
                }
            } catch (Exception $ex) {
                // ignore error
            }
        }
    }

    /**
     * Add reservations log in log db.
     * @param string $xml for reservation xml
     * @return NULL.
     */
    public function insertResaLog($xml) {
        //return 1;//due to server issue not storing
        if (defined('DB_RESA_LOGS_TABLE')) {
            try {
                if (empty($this->siteUser->ID)) {
                    $this->siteUser->ID = $this->siteConfig->sites_id;
                }
                //$xmlZip = gzcompress($xml, 9);
                $xmlZip = utf8_encode($xml); //for testing, later save in zip format
                $q = 'INSERT INTO ' . DB_RESA_LOGS_TABLE . ' (`hot_sites_id`, `hot_user_id`, `reqResp`) VALUES (?, ?, ?)';
                $p = [$this->siteConfig->sites_id, $this->siteUser->ID, $xmlZip];
                $r = $this->getLogs()->executeQuery($q, $p);
            } catch (Exception $ex) {
                // ignore error
            }
        }
    }

    /**
     * @return xml of mark as processed Bookings
     */
    public function getXmlFromDb($refer) {
        //return 1;//due to server issue not storing
        $q = 'SELECT * FROM ' . DB_RESA_XML_TABLE . ' where hot_sites_id=? and refer=? order by id DESC limit 1';
        $p = [$this->siteConfig->sites_id, $refer];
        $r = $this->getLogs()->fetchObject($q, $p);
        //$r = $this->database->fetchObject($q, $p);
        return gzuncompress($r->xml);
    }

    /**
     * @return xml of check Unprocessed Bookings
     */
    public function checkUnprocessedBooking($flag = 1) {
        //return 0;//due to server issue not storing
        $this->pendingResaArr = [];
        if ($flag == '2') {
            $q = 'SELECT `refer`,`xml`,`status`,`lastmodify_time`,`property_id` FROM ' . DB_RESA_XML_TABLE . ' WHERE `hot_sites_id` = ? AND `processed` = ? limit 1';
            $p = [$this->siteConfig->sites_id, 0];
            //print "q=>$q";print_r($p);
        } elseif ($this->siteConfig->sites_id == '337' || $this->siteConfig->sites_id == '332') {
            $q = 'SELECT `refer`,`xml`,`status`,`lastmodify_time`,`property_id` FROM ' . DB_RESA_XML_TABLE . ' WHERE `hot_sites_id` = ? AND `processed` = ? AND property_id = ? limit 300';
            $p = [$this->siteConfig->sites_id, 0, $this->siteUser->sites_user];
            //print "q=>$q";print_r($p);
        } else {
            $q = 'SELECT `refer`,`xml`,`status`,`lastmodify_time`,`property_id` FROM ' . DB_RESA_XML_TABLE . ' WHERE `hot_sites_id` = ? AND `processed` = ? AND property_id = ? limit 300';
            $p = [$this->siteConfig->sites_id, 0, $this->siteUser->hotel_id];
        }
        $rows = $this->getLogs()->fetchAll($q, $p);
        //$rows = $this->database->fetchAll($q, $p);
        foreach ($rows as $row) {
           // echo "gzuncompress xml=>".gzuncompress($row->xml)."\n";
            $this->pendingResaArr[$row->refer]['xml'] = gzuncompress($row->xml);
            $this->pendingResaArr[$row->refer]['status'] = $row->status;
            $this->pendingResaArr[$row->refer]['lastmodify_time'] = $row->lastmodify_time;
            $this->pendingResaArr[$row->refer]['property_id'] = $row->property_id;
        }
        //print("this is xml\n");
        //print($this->pendingResaArr[$row->refer]['xml']);
        if (count($this->pendingResaArr) > 0) {
            return 1;
        }
    }

    /**
     * @return xml of mark as processed Bookings
     */
    public function markAsProcessedBooking($refer) {
        //return 1;//due to server issue not storing
        $q = 'UPDATE ' . DB_RESA_XML_TABLE . ' SET `processed` = ? WHERE `hot_sites_id` = ? AND `processed` = ? AND `refer` = ?';
        $p = [1, $this->siteConfig->sites_id, 0, $refer];
        $r = $this->getLogs()->executeQuery($q, $p);
        //$r = $this->database->executeQuery($q, $p);
    }
    
    /**
     * @return xml of mark as processed Bookings
     */
    public function markAsProcessedBookingHB($refer) {
        //return 1;//due to server issue not storing
        $q = 'UPDATE ' . DB_RESA_XML_TABLE . ' SET `processed` = ? WHERE `hot_sites_id` = ? AND `processed` = ? AND `refer` = ?';
        $p = [1, $this->siteConfig->sites_id, 2, $refer];
        $r = $this->getLogs()->executeQuery($q, $p);
        //$r = $this->database->executeQuery($q, $p);
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
        /* $dayPriceArr = Array();
          foreach ($room->daily as $daily) {
          $dayPriceArr[$daily->day] = $daily->price;
          }
          $voucherJson['Day_Prices'] = $dayPriceArr; */
        return $voucherJson;
    }

}
