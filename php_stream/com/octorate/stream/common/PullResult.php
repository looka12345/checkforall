<?php

/*
 * PullResult.php
 * Octorate srl. All rights reserved. 2019 
 */

namespace com\octorate\stream\common;

class PullResult {

    /**
     * @var array Array of Reservation.
     */
    public $reservations = [];
    
    /**
     * @var string Error message.
     */
    public $error = null;    
}
