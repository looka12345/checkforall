<?php

/*
 * PullReservationDay.php
 * Octorate srl. All rights reserved. 2020
 */

namespace com\octorate\stream\common;

/**
 * @author gianluca
 */
class PullReservationDay {

    /**
     * @var string Date format
     * ted as Y-m-d (example "2020-12-01")
     */
    public $day;

    /**
     * @var decimal Price amount.
     */
    public $price;

    /**
     * @var boolean True if gross (tax included) or net (tax excluded).
     */
    public $taxIncluded;
    
    /**
     * @param type $day
     * @param type $price
     * @param type $taxIncluded
     */
    public function __construct($day, $price, $taxIncluded=true) {
        $this->day = $day;
        $this->price = $price;
        $this->taxIncluded = $taxIncluded;
    }

}
