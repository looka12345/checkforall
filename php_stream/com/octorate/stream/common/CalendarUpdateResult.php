<?php

/*
 * CalendarUpdateResult.php
 * Octorate srl. All rights reserved. 2019 
 */

namespace com\octorate\stream\common;

class CalendarUpdateResult {

    /**
     * @var boolean True if all enabled values are sent successfully, False otherwise.
     */
    public $success = false;

    /**
     * @var boolean True if values are ignored and not sent to site, False otherwise.
     */
    public $ignore = false;

    /**
     * @var string Success or errore message
     */
    public $message;

    /**
     * @var int Unix timestamp until the process still wait before try again.
     */
    public $sleepUntil;

    /**
     * @var boolean True to retry again later, False to stop the process.
     */
    public $retry = false;

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

}
