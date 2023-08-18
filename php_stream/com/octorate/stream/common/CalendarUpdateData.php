<?php

/*
 * CalendarUpdateData.php
 * Octorate srl. All rights reserved. 2019 
 */

namespace com\octorate\stream\common;

class CalendarUpdateData {

    /**
     * @var int External room id.
     */
    public $externalRoomId;

    /**
     * @var array(CalendarInterval)
     */
    public $dateIntervals;

    /**
     * @var array(long)
     */
    public $processIds;

    /**
     * @var int
     */
    public $changeMask;

    /**
     * @var int
     */
    public $availability;

    /**
     * @var double
     */
    public $price;

    /**
     * @var int
     */
    public $minstay;

    /**
     * @var int
     */
    public $maxstay;

    /**
     * @var boolean
     */
    public $closeToArrival;

    /**
     * @var boolean
     */
    public $closeToDeparture;

    /**
     * @var boolean
     */
    public $stopSells;

    /**
     * @var int
     */
    public $cutOffDays;

    /**
     * Read values from json string.
     * @param type $str
     */
    public function setJson($str) {
        $data = json_decode($str);
        if ($data) {
            foreach ($data as $key => $val) {
                if (property_exists(__CLASS__, $key)) {
                    $this->$key = $val;
                }
            }
        }
    }

    /**
     * @return boolean
     */
    public function isValid() {
        
        return $this->externalRoomId && count($this->dateIntervals) > 0;
    }

    /**
     * @return boolean
     */
    private function isChanged($n) {
        return ($this->changeMask & $n) == $n ? 1 : 0;
    }

    /**
     * @return boolean
     */
    public function isChangedAvailability() {
        return $this->isChanged(1);
    }

    /**
     * @return boolean
     */
    public function isChangedPrice() {
        return $this->isChanged(2);
    }

    /**
     * @return boolean
     */
    public function isChangedMinstay() {
        return $this->isChanged(4);
    }

    /**
     * @return boolean
     */
    public function isChangedMaxstay() {
        return $this->isChanged(8);
    }

    /**
     * @return boolean
     */
    public function isChangedStopSells() {
        return $this->isChanged(16);
    }

    /**
     * @return boolean
     */
    public function isChangedCloseToArrival() {
        return $this->isChanged(32);
    }

    /**
     * @return boolean
     */
    public function isChangedCloseToDeparture() {
        return $this->isChanged(64);
    }

    /**
     * @return boolean
     */
    public function isChangedCutOffDays() {
        return $this->isChanged(128);
    }

}
