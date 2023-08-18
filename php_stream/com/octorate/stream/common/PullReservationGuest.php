<?php

/*
 * PullReservationGuest.php
 * Octorate srl. All rights reserved. 2020
 */

namespace com\octorate\stream\common;

/**
 * @author gianluca
 */
class PullReservationGuest {

    /**
     * @var string Guest email.
     */
    public $email;

    /**
     * @var string Guest phone.
     */
    public $phone;

    /**
     * @var string Guest firstname.
     */
    public $firstName;

    /**
     * @var string Guest lastname.
     */
    public $lastName;

    /**
     * @var string Guest address.
     */
    public $address;

    /**
     * @var string Guest city.
     */
    public $city;

    /**
     * @var string Guest zip.
     */
    public $zip;

    /**
     * @var string Guest country (2 letters ISO code uppercase).
     */
    public $country;

}
