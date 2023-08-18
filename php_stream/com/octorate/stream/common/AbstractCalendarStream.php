<?php

/*
 * AbstractCalendarStream.php
 * Octorate srl. All rights reserved. 2019 
 */

namespace com\octorate\stream\common;

abstract class AbstractCalendarStream extends AbstractStream {

    /**
     * Send calendar value to externale site.
     * @param CalendarUpdateData $cud
     * @return CalendarUpdateResult
     */
    public abstract function updateCalendar(CalendarUpdateData $cud);

//    /**
//     * @return Database
//     */
//    protected function getLogs() {
//        if (!$this->logs) {
//            $this->logs = Database::getLogs();
//        }
//        return $this->logs;
//    }

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
