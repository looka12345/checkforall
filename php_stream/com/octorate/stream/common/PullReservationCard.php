<?php

/*
 * PullReservationCard.php
 * Octorate srl. All rights reserved. 2020
 */

namespace com\octorate\stream\common;

/**
 * @author gianluca
 */
class PullReservationCard {

    /**
     * @var string Date formatted as Y-m-d (example "2020-12-01")
     */
    public $activationDate;

    /**
     * @var string Date formatted as Y-m-d (example "2020-12-01")
     */
    public $expirationDate;

    /**
     * @var number
     */
    public $currentBalance;

    /**
     * @var boolean (true or false).
     */
    public $isVirtual;

    /**
     * @var string Card token (ccs_token).
     */
    public $token;

}
