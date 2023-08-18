<?php

/*
 * CalendarInterval.php
 * Octorate srl. All rights reserved. 2019 
 */

namespace com\octorate\stream\common;

class CalendarInterval {

    /**
     * @var string Start date in yyyy-mm-dd format.
     */
    public $startDate;

    /**
     * @var string End date in yyyy-mm-dd format.
     */
    public $endDate;

    function __construct($startDate, $endDate) {
       
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

}
