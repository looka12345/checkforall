<?php

/*
 * PullReservation.php
 * Octorate srl. All rights reserved. 2020
 */

namespace com\octorate\stream\common;

/**
 * @author gianluca
 */
class PullReservation {

    const CANCELLED = 'CANCELLED';
    const WAITING = 'WAITING';
    const CONFIRMED = 'CONFIRMED';
    const PAYMENT_CASH = 'CASH';
    const PAYMENT_CREDITCARD = 'CREDITCARD';
    const PAYMENT_PREPAID = 'PREPAID';
    const PAYMENT_BANKTRANSFER = 'BANKTRANSFER';
    const PAYMENT_NOTPAID = 'NOTPAID';
    const PAYMENT_PAYPAL = 'PAYPAL';
    const PAYMENT_CHEQUE = 'CHEQUE';
    const PAYMENT_TRAVELCHEQUE = 'TRAVELCHEQUE';
    const PAYMENT_TREASURY_OFFICE = 'TREASURY_OFFICE';
    const PAYMENT_TREASURY_RECEIPT = 'TREASURY_RECEIPT';
    const COLLECT_NONE = 'NONE';
    const COLLECT_COMPANY = 'COMPANY';
    const COLLECT_HOTEl = 'HOTEL';

    /**
     * @var boolean True to force input again.            
    public $force;
    
    /**
     * @var string External site reservation's id.
     */
    public $refer;

    /**
     * @var string Last update date formatted as iso (example "2020-12-10T14:54:36+01:00")
     */
    public $updateDate;

    /**
     * @var string Last update date formatted as iso (example "2020-12-10T14:54:36+01:00")
     */
    public $createDate;

    /**
     * @var string Reservation status (see constants).
     */
    public $status; //  = PullReservation::CONFIRMED;

    /**
     * @var string Payment modes (see constants PAYMENT_).
     */
    public $paymentMode;

    /**
     * @var string Currency iso code 3 letters (EUR, USD, GBP, ...)
     */
    public $currency;

    /**
     * @var string Company collect (see constants COLLECT_).
     */
    public $companyCollect;

    /**
     * @var PullReservationGuest Guest's data.
     */
    public $guest;

    /**
     * @var string Guest language 2 letters-code (IT, EN, FR, ES, DE, RU, PT, NL, JA, EL, TR, ZH, CA, RO)
     */
    public $language;

    /**
     * @var string Property reference on external site (same in hot_sites_user.hotel_id, required if called by pullGlobal).
     */
    public $propertyReference;

    /**
     * @var int Id of hot_sites_user record.
     */
    public $connectionId;

    /**
     * @var int Id of ob_push_import record.
     */
    public $pushImportId;

    /**
     * @var PullReservationCard Credit card data.
     */
    public $creditCard;

    /**
     * @var array of PullReservationRoom.
     */
    public $rooms ;

    /**
     * @param string $externalRoom
     * @param string $refer
     * @param int $internalRoom
     * @param int $internalRate
     */
    public function __construct($refer = null) {
//        $this->rooms = new PullReservationRoom();
        $this->refer = $refer;
        $this->guest = new PullReservationGuest();
        $this->creditCard = new PullReservationCard();
        
    }

}
