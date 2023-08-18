<?php

/*
 * AbstractStream.php
 * Octorate srl. All rights reserved. 2019 
 */

namespace com\octorate\stream\common;

abstract class AbstractStream {

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var boolean
     */
    protected $test = false;

    /**
     * @var object
     */
    protected $siteConfig;

    /**
     * @var object
     */
    protected $siteUser;

    /**
     * @var object
     */
    private $siteRooms;

    /**
     * @var Database
     */
    private $logs;

    /**
     * @param object $sc
     */
    public function setSiteUser($sc) {
        $this->siteUser = $sc;
    }

    /**
     * @param object $sc
     */
    public function setSiteConfig($sc) {
        $this->siteConfig = $sc;
    }

    /**
     * @param Database $db
     */
    public function setDatabase($db) {
        $this->database = $db;
    }

    /**
     * @param boolean $test
     */
    public function setTest($test) {
        $this->test = $test;
    }

    /**
     * @return object
     */
    public function getSiteRoom($externalRoomId) {
        $this->getSiteRooms();
    //     echo 'check externalroomid';
    //    print($externalRoomId);
    //    print("\nit is external Room\n");
        foreach ($this->siteRooms as $r) {
            if ($r->ID == $externalRoomId) {
                return $r;
            }
        }
        return null;
    }

    
    /**
     * @return object
     */
    public function getSiteRooms() {
      
        if (!$this->siteRooms) {
            $this->siteRooms = $this->database->fetchAll('select a.*, m.site_int_id,'
                    . ' c.parent_id as derive_parent,'
                    . ' c.avail as derive_avail,'
                    . ' c.stay as derive_stay,'
                    . ' c.restrictions as derive_restrictions,'
                    . ' c.stop as derive_stopsell,'
                    . ' c.price as derive_price'
                    . ' from hot_site_rooms a'
                    . ' join hot_sites_map m on m.site_ext_id = a.id'
                    . ' left join ob_rules_derived c on c.room_id = m.site_int_id'
                    . ' where a.site_id = ?',
                    [$this->siteUser->ID]);
        }
       
        return $this->siteRooms;
        //print($this->siteUser->ID);
        //print("\nthis is site room\n");
    }

    /**
     * @return breakfast value and room occupancy
     */
    //print($site_ext_id);
    //echo "\nthis is site ext id\n");

    public function getBreakfast($site_ext_id) {
       // print($site_ext_id);
        //echo "\nthis is echo\n";
      //  print($this->siteUser->ID);
        //echo "\nthis is echo1\n";
        $hotRoomType = $this->database->fetchObject('SELECT breakfast, no_members'
                . ' FROM hot_room_type WHERE room_id = ('
                . ' SELECT site_int_id FROM hot_sites_map'
                . ' WHERE site_ext_id = ? and ext_site_id = ?)',
                [$site_ext_id, $this->siteUser->ID]);
    //    $hotRoomType = $this->database->fetchObject(SELECT Id FROM hot_sites_map'
    //     .' WHERE site_ext_id = ? and ext_site_id = ?,
    //     [$site_ext_id, $this->siteUser->ID]);

        return [$hotRoomType->breakfast, $hotRoomType->no_members];
     //   print($hotRoomType->breakfast);
       // print("\nthis is breakfast\n");
    }
    
     //print($hotRoomType->no_members);
     //print("\nthis is members\n");
    
    public function getThreatment($site_ext_id) {
        $hotRoomType = $this->database->fetchObject('select orp.threatment FROM ob_rate_plan as orp JOIN ob_room_rate_nm as orrn ON orrn.rate_id=orp.id WHERE orrn.room_id=(SELECT site_int_id FROM hot_sites_map WHERE site_ext_id=? AND ext_site_id=?)', [$site_ext_id, $this->siteUser->ID]);
        return $hotRoomType->threatment;
    }

    /*
     * @return object
     */
    public function getSiteSettings() {
        $q = 'select type, type_val from external_sites_settings where codice = ? and site = ?';
        $p = [$this->siteUser->sites_asso_id, $this->siteConfig->sites_name];
        $rows = $this->database->fetchAll($q, $p);
        $map = [];
        foreach ($rows as $r) {
            $map[$r->type] = $r->type_val;
        }
        return $map;
    }

    /**
     * @return object
     */
    public function getProperty($columns = '*') {
        
        return $this->database->fetchObject('select '
                        . $columns
                        . ' from clpms p'
                        . ' join clienti c on c.codice = p.codice'
                        . ' where c.codice = ?',
                        [$this->siteUser->sites_asso_id]);
    }

    /**
     * @return Database
     */
    protected function getLogs() {
        if (!$this->logs) {
            $this->logs = Database::getLogs();
        }
        return $this->logs;
    }

    /**
     * Add xml to log database.
     * @param string $xml
     */
    public function insertLog($xml, $error = NULL) {
        if (defined('CALENDAR_JSON_DATA') && defined('DB_LOGS_TABLE')) {
            try {
                if (!$xml) {
                    $xml = 'empty xml due to something wrong';
                }
                $xmlZip = gzcompress($xml, 9);
                //$q = 'INSERT INTO ' . DB_LOGS_TABLE . ' (site_id, json_data, reqResp, hot_room_id, hot_sites_id, error_msg) VALUES (?, ?, ?, ?, ?, ?)';
                //$p = [$this->siteUser->ID, CALENDAR_JSON_DATA, $xml, $this->roomObj->ID, $this->siteConfig->sites_id, $error];
                $q = 'INSERT INTO ' . DB_LOGS_TABLE . ' (site_id, json_data, reqRespZip, hot_room_id, hot_sites_id, error_msg) VALUES (?, ?, ?, ?, ?, ?)';
                $p = [$this->siteUser->ID, CALENDAR_JSON_DATA, $xmlZip, $this->roomObj->ID, $this->siteConfig->sites_id, $error];
                if ($this->test) {
                    var_dump($p);
                }
                $this->getLogs()->executeQuery($q, $p);
            } catch (Exception $ex) {
                // ignore error
            }
        }
    }

    /**
     * @return boolean
     */
    private function isEnabled($n) {
        return ($this->siteUser->calendar_values & $n) == $n ? 1 : 0;
    }

    /**
     * @return boolean
     */
    public function isEnabledPrice() {
        return $this->isEnabled(1);
    }

    /**
     * @return boolean
     */
    public function isEnabledAvailability() {
        return $this->isEnabled(2);
    }

    /**
     * @return boolean
     */
    public function isEnabledMinstay() {
        return $this->isEnabled(4);
    }

    /**
     * @return boolean
     */
    public function isEnabledMaxstay() {
        return $this->isEnabled(8);
    }

    /**
     * @return boolean
     */
    public function isEnabledCloseToArrival() {
        return $this->isEnabled(16);
    }

    /**
     * @return boolean
     */
    public function isEnabledCloseToDeparture() {
        return $this->isEnabled(32);
    }

    /**
     * @return boolean
     */
    public function isEnabledStopSells() {
        return $this->isEnabled(64);
    }

    /**
     * @return boolean
     */
    public function isEnabledCutOffDays() {
        return $this->isEnabled(128);
    }

    /**
     * @param CalendarUpdateData $cud
     * @return boolean
     */
    public function isSendableAvailability($cud) {
        return $this->isEnabledAvailability() && $cud->isChangedAvailability();
    }

    /**
     * @param CalendarUpdateData $cud
     * @return boolean
     */
    public function isSendablePrice($cud) {
        return $this->isEnabledPrice() && $cud->isChangedPrice();
    }

    /**
     * @param CalendarUpdateData $cud
     * @return boolean
     */
    public function isSendableMinstay($cud) {
        return $this->isEnabledMinstay() && $cud->isChangedMinstay();
    }

    /**
     * @param CalendarUpdateData $cud
     * @return boolean
     */
    public function isSendableMaxstay($cud) {
        return $this->isEnabledMaxstay() && $cud->isChangedMaxstay();
    }

    /**
     * @param CalendarUpdateData $cud
     * @return boolean
     */
    public function isSendableStopSells($cud) {
        return $this->isEnabledStopSells() && $cud->isChangedStopSells();
    }

    /**
     * @param CalendarUpdateData $cud
     * @return boolean
     */
    public function isSendableCloseToArrival($cud) {
        return $this->isEnabledCloseToArrival() && $cud->isChangedCloseToArrival();
    }

    /**
     * @param CalendarUpdateData $cud
     * @return boolean
     */
    public function isSendableCloseToDeparture($cud) {
        return $this->isEnabledCloseToDeparture() && $cud->isChangedCloseToDeparture();
    }

    /**
     * @param CalendarUpdateData $cud
     * @return boolean
     */
    public function isSendableCutOffDays($cud) {
        return $this->isEnabledCutOffDays() && $cud->isChangedCutOffDays();
    }
}
