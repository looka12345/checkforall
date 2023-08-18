<?php

/*
 * PullReservationExtra.php
 * Octorate srl. All rights reserved. 2020
 */

namespace com\octorate\stream\common;

/**
 * @author gianluca
 */
class PullReservationExtra {

    /**
     * @var string Extra's name.
     */
    public $name;

    /**
     * @var decimal Price.
     */
    public $price;

    /**
     * @var int Quantity.
     */
    public $quantity;

    /**
     * @var string Date formatted as Y-m-d (example "2020-12-01")
     */
    public $day;

    /**
     * @param type $name
     * @param type $price
     * @param type $quantity
     * @param type $day
     */
    public function __construct($name, $price, $quantity, $day = NULL) {
        $this->name = $name;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->day = $day;
    }

}

