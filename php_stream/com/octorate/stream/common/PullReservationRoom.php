<?php

/*
 * PullReservationRoom.php
 * Octorate srl. All rights reserved. 2020
 */

namespace com\octorate\stream\common;

/**
 * @author gianluca
 */
class PullReservationRoom {

    /**
     * @var string Unique reservation's reference for this room.
     */
    public $refer;

    /**
     * @var string External room reference
     */
    public $externalRoom;

    /**
     * @var int Internal room id
     */
    public $internalRoom;

    /**
     * @var int Internal rate id
     */
    public $internalRate;

    /**
     * @var string Date formatted as Y-m-d (example "2020-12-01")
     */
    public $checkIn;

    /**
     * @var string Date formatted as Y-m-d (example "2020-12-01")
     */
    public $checkOut;

    /**
     * @var PullReservationGuest Guest's data.
     */
    public $guest;

    /**
     * @var decimal Total amount.
     */
    public $total;

    /**
     * @var boolean True if gross (tax included) or net (tax excluded).
     */
    public $taxIncluded;

    /**
     * @var decimal Total amount paid by guest.
     */
    public $totalPaid;

    /**
     * @var decimal City tax paid by guest.
     */
    public $cityTaxPaid;

    /**
     * @var decimal Commissions fees.
     */
    public $totalCommissions;

    /**
     * @var decimal Cleaning fees.
     */
    public $totalCleaning;

    /**
     * @var string Payment notes.
     */
    public $paidNotes;

    /**
     * @var int Total number of pax (adults + children)
     */
    public $pax;

    /**
     * @var int Number of children
     */
    public $children;

    /**
     * @var int Number of infants
     */
    public $infants;

    /**
     * @var string Hotel internal notes.
     */
    public $notes;

    /**
     * @var string Guest special request.
     */
    public $specialRequests;

    /**
     * @var string Raw reservation json.
     */
    public $json;

    /**
     * @var array Array of PullReservationDay.
     */
    public $daily = [];

    /**
     * @var array Array of PullReservationExtra.
     */
    public $extras = [];

    /**
     * @param string $externalRoom
     * @param string $refer
     * @param int $internalRoom
     * @param int $internalRate
     */
    public function __construct($externalRoom, $refer = null, $internalRoom = null, $internalRate = null) {
        $this->externalRoom = $externalRoom;
        $this->refer = $refer;
        $this->internalRoom = $internalRoom;
        $this->internalRate = $internalRate;
        $this->guest = new PullReservationGuest();
    }

}
