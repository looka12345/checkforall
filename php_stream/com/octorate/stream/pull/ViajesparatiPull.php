<?php

/*
 * ViajesparatiPull.php
 * Octorate srl. All rights reserved. 2023
 */

namespace com\octorate\stream\pull;

set_time_limit("1800");
ini_set("memory_limit", "1000M");
ini_set('display_errors', 1);
error_reporting(E_ALL);

use com\octorate\stream\common\AbstractPullStream;
use com\octorate\stream\common\PullReservation;
use com\octorate\stream\common\PullReservationRoom;
use com\octorate\stream\common\PullReservationDay;
use com\octorate\stream\common\PullReservationExtra;
use com\octorate\stream\utils\UtilFunc;

class ViajesparatiPull extends AbstractPullStream {

    /**
     * @return array of PullReservation
     */
    public function pullReservations() {
        $this->utilFunc = new UtilFunc();
        $this->allRes = [];
        $this->resaArr = [];
        //        $this->url = 'https://wscontract.xtravelsystem.com/ws-contracts/ContractInsertionService';
        if ($xmlStr = $this->getReservations()) {
            $this->parseReservations($xmlStr);
        }
        //        if ( $this->checkUnprocessedBooking() ) {
        //            foreach ( $this->pendingResaArr as $refer => $value ) {
        //                if ( !( array_key_exists( $refer, $this->resaArr ) ) ) {
        //                    $lastmodify = ( new \DateTime( $value[ 'lastmodify_time' ] ) )->format( 'Y-m-d H:i:s' );
        //                    if ( $value[ 'status' ] == '8' && $value[ 'xml' ] != '' ) {
        //                        $this->allRes[] = $this->retrieveCancellation( $refer, $lastmodify, true );
        //                    } else {
        //                        $this->allRes[] = $this->retrieveReservation( $value[ 'xml' ], $refer, $value[ 'status' ], $lastmodify, true );
        //                    }
        //                    if ( !( $this->test == true ) ) {
        //                        $this->markAsProcessedBooking( $refer );
        //                    }
        //                }
        //            }
        //        }
        //
        echo '<pre>';
        print_r($this->allRes);
        echo '</pre>';
//        return $this->allRes;
        exit();
    }

  
    /**
    * @return xml of parse All bookings
    */
    public function parseReservations($xmlStr) {

        
        $regex = '/(<booking\s.*?<\/booking>)/is';
        while (preg_match($regex, $xmlStr, $match)) {
            $xmlStr = $this->utilFunc->after($match[0], $xmlStr);
            $tempFile = $match[1];
            $refer = $this->utilFunc->parseOneValue('id', $tempFile);
            $lastmodify = (new \DateTime($this->utilFunc->parseXmlValue('reservationDate', $tempFile)))->format('Y-m-d H:i:s');
            $status = $this->utilFunc->parseoneValue('status', $xmlStr);
            $propertyReference = $this->siteUser->hotel_id;
            $this->insertXml($refer, $status, $tempFile, $lastmodify, $propertyReference);

            if ($status == '8') {
                $this->allRes[] = $this->retrieveCancellation($refer, $lastmodify, false);
            } else {
                $this->allRes[] = $this->retrieveReservation($tempFile, $refer, $status, $lastmodify, false);
            }
        }
    }

    /**
     * @return array of Retrieve Reservation
     */
    public function retrieveReservation($resultFile, $refer, $status, $lastmodify, $forceFlg) {

        // Success, parse response and create reservations
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CONFIRMED;
        $res->language = NULL;
        $res->currency = $this->utilFunc->parseOneValue('currencyCode', $resultFile);

        if ($forceFlg) {
            $res->force = \TRUE;
        }
        if ($status == '4') {
            $res->updateDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
            $res->createDate = NULL;
        } else {
            $res->createDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
            $res->updateDate = (new \DateTime('2000-01-01 00:00:00'))->format(DATE_ATOM);
        }
        $res->creditCard->token = NULL;
        $res->guest->email = $this->utilFunc->parseOneValue('clientEmail', $resultFile);
        $res->guest->phone = $this->utilFunc->parseOneValue('clientPhone', $resultFile);
        $firstName = '';
        $lastName = '';
        list($firstName, $lastName) = explode(' ', $this->utilFunc->parseOneValue('clientName', $resultFile) . ',');

        $res->guest->firstName = substr($firstName, 0, 40);
        $res->guest->lastName = substr($lastName, 0, 40);
        $res->guest->address = null;
        $res->guest->city = null;
        $res->guest->zip = null;

        // Create room data
        if (preg_match('/(<rooms.*>(.*?)<\/rooms>)/is', $resultFile, $roomsmatch)) {
            $tempFile = $roomsmatch[1];
            $checkIn = (new \DateTime($this->utilFunc->parseoneValue('dateIn', $resultFile)))->format('Y-m-d');
            $checkOut = (new \DateTime($this->utilFunc->parseoneValue('dateOut', $resultFile)))->format('Y-m-d');
            $totalBuffer = $this->utilFunc->parseoneValue('price', $resultFile);

            preg_match_all('/<room\s(.*?)<\/room>/is', $resultFile, $roomMatches);
            $numofrooms = count($roomMatches);
            foreach ($roomMatches[0] as $roomXML) {
                $room = new PullReservationRoom($this->utilFunc->parseoneValue('room id', $roomXML) . ':' . $this->utilFunc->parseoneValue('ratePlanCode', $roomXML));
                $dateArr = $this->utilFunc->getDatesFromRange($checkIn, $checkOut);
                foreach ($dateArr as $date) {
                    preg_match('/<dailyPrices>(.*?)<\/room>/is', $roomXML, $dailyMatches);
                    $pricePerDay = $this->utilFunc->parseoneValue('price', $dailyMatches[1]);
                    $room->daily[] = new PullReservationDay($date, $pricePerDay, true);
                }

                $room->children = $this->utilFunc->parseoneValue('children', $tempFile);
                $room->pax = $this->utilFunc->parseoneValue('adults', $tempFile) + $this->utilFunc->parseoneValue('children', $tempFile);
                $room->total = round($totalBuffer, 2);
                $room->taxIncluded = true;
                $room->totalPaid = NULL;
                $room->checkIn = $checkIn;
                $room->checkOut = $checkOut;
                //creating remarks data///
                $room->notes = $this->utilFunc->parseXmlValue('remark', $resultFile);
                $room->paidNotes = NULL;
                $voucherJson = $this->createVoucherV2($res, $room);

                $voucherJson['Comments'] = $this->personsName($resultFile);
                $voucherJson['ReservationDate'] = $this->utilFunc->parseOneValue('reservationDate', $resultFile);
                $voucherJson['CancellationDate'] = $this->utilFunc->parseOneValue('cancellationDate', $resultFile);
                $voucherJson['HotelCode'] = $this->utilFunc->parseOneValue('hotelCode', $resultFile);
                $voucherJson['RatePlanCode'] = $this->utilFunc->parseOneValue('ratePlanCode', $resultFile);
                $voucherJson['StatusCode'] = $this->utilFunc->parseOneValue('statusCode', $resultFile);
                $voucherJson['Discount'] = $this->utilFunc->parseOneValue('discount', $resultFile);
                $voucherJson['Mealplans'] = $this->mealplans($resultFile);
                $voucherJson['Childs Age Count'] = $this->childage($roomXML);
                $voucherJson['Number of rooms'] = $numofrooms;
                $voucherJson['rooms data'] = $this->roomstable($roomXML);
                $room->json = $voucherJson;
                $res->rooms[] = $room;
            }
        } else {
            throw new \Exception('Something went wrong, error pulling reservations!');
        }

        return $res;
    }

    /**
     * @return array of Retrieve Cancellation
     */
    public function retrieveCancellation($refer, $lastmodify, $forceFlg) {
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CANCELLED;
        if ($forceFlg) {
            $res->force = \TRUE;
        }
        $res->updateDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
        $res->createDate = NULL;
        // return single reservations object
        return $res;
    }

    /**
     * @return xml of get All Bookings
     */
    public function getReservations() {
        $result = '';
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<BookingDownloadRQ>
    <credentials>
        <username>' . $this->siteUser->sites_user . '</username>
        <password>' . $this->siteUser->sites_pass . '</password>
    </credentials>
    <filter>
         <statusList>
            <status code="2" />
            <status code="4" />
            <status code="8" />
        </statusList>
         <hotels>
            <hotel code="200" />
            <hotel code="205" />
        </hotels>
    </filter>
</BookingDownloadRQ>
';
    //  $result = $this->utilFunc->submitXmlPost( $this->url, $xml, [] );
        $result = $this->tempXml();
        $result = preg_replace('/\&lt\;/is', '<', $result);
        $result = preg_replace('/\&gt\;/is', '>', $result);
        $result = preg_replace('/\&quot\;/is', '"', $result);
        $this->insertResaLog($xml . '------' . $result);
        if (preg_match('/BookingDownloadRS/is', $result)) {
            return $result;
        }
        //no reservation    
        elseif (preg_match('/Warnings/is', $result, $match)) {
            throw new \Exception('Something went wrong, warning pulling reservations!');
        } else {
            throw new \Exception('Something went wrong, error pulling reservations!');
        }

        if ($result) {
            $notify = $this->bookingNotify();
            return $notify;
        }
    }

    /**
     * @return notify of booking
     */
    public function bookingNotify() {
        $requestXML = '<?xml version="1.0" encoding="UTF-8" ?>
                    <BookingDownloadNotifRQ>
    <credentials>
   	 <username>' . $this->siteUser->sites_user . '</username>
   	 <password>' . $this->siteUser->sites_pass . '</password>
    </credentials>
    <bookings>
   	 <book id="36362525"  locator="11111111" />
    </bookings>
</BookingDownloadNotifRQ>
';
                $result = $this->utilFunc->submitXmlPost( $this->url, $xml, [] );

        $result = $this->notifyXML();
        return $result;
    }

    /**
     * @return array of temp
     */
    public function tempXml() {
        return '<?xml version="1.0"?>
<BookingDownloadRS>
    <bookings amount="1">
        <booking id="177584" dateIn="2016-11-11" dateOut="2016-11-15" reservationDate="2016-10-20" cancellationDate="" clientName="Elodie Della-santa " clientFirstName="Elodie" clientFirstSurname=" Della-santa " clientSecondSurname=" " price="329.27" status="1" ratePlanCode="1" hotelCode="562" statusCode="1" currencyCode="EUR"  discount="-35.25" clientEmail="test@test.com" clientPhone="34666666666">
            <mealPlans>
                <mealPlan date="2016-11-12" mealPlanCode="2"/>
            </mealPlans>
            <rooms totalPersons="4">
                <room id="2A0C2" type="2" adults="0" children="2" juniors="0" name="habitacion doble"  minPersons="2" maxPersons="2" price="182.26" childCount="0" adultCount="2" ratePlanCode="1" >
                    <childs>
                        <child age="5"/>
                        <child age="10"/>
                        <child age="2"/>
                        <child age="7"/>
                        <child age="12"/>
                        <child age="10"/>
                    </childs>
                    <dailyPrices>
                        <dayPrice mealPlanCode="2" date="2016-11-12" price="182.26" occupation="2"/>
                    </dailyPrices>
                </room>
                <room id="2A2C0" type="2" adults="2" children="0" juniors="0" name="habitacion doble"  minPersons="2" maxPersons="2" price="182.26" childCount="0" adultCount="2" ratePlanCode="1" >
                    <dailyPrices>
                        <dayPrice mealPlanCode="2" date="2016-11-12" price="182.26" occupation="2"/>
                    </dailyPrices>
                </room>
                <occupancy>
                    <person name="Elodie Della-santa "/>
                    <person name="Cedric Mazars "/>
                    <person name="Coralie Della-santa "/>
                    <person name="Herve Estebanez "/>
                </occupancy>
            </rooms>
            <remarks>
                <remark> XXXXXX </remark>
            </remarks>
        </booking>
        <booking id="177585" dateIn="2016-12-11" dateOut="2016-12-15" reservationDate="2016-10-20" cancellationDate="" clientName="Elodie Della-santa " clientFirstName="Elodie" clientFirstSurname=" Della-santa " clientSecondSurname=" " price="329.27" status="1" ratePlanCode="1" hotelCode="562" statusCode="1" currencyCode="EUR"  discount="-35.25" clientEmail="test@test.com" clientPhone="34666666666">
            <mealPlans>
                <mealPlan date="2016-11-12" mealPlanCode="2"/>
            </mealPlans>
            <rooms totalPersons="4">
                <room id="2A0C2" type="2" adults="0" children="2" juniors="0" name="habitacion doble"  minPersons="2" maxPersons="2" price="182.26" childCount="0" adultCount="2" ratePlanCode="1" >
                    <childs>
                        <child age="5"/>
                        <child age="10"/>
                    </childs>
                    <dailyPrices>
                        <dayPrice mealPlanCode="2" date="2016-11-12" price="182.26" occupation="2"/>
                    </dailyPrices>
                </room>
                <room id="2A2C0" type="2" adults="2" children="0" juniors="0" name="habitacion doble"  minPersons="2" maxPersons="2" price="182.26" childCount="0" adultCount="2" ratePlanCode="1" >
                    <dailyPrices>
                        <dayPrice mealPlanCode="2" date="2016-11-12" price="182.26" occupation="2"/>
                    </dailyPrices>
                </room>
                <occupancy>
                    <person name="Elodie Della-santa "/>
                    <person name="Cedric Mazars "/>
                    <person name="Coralie Della-santa "/>
                    <person name="Herve Estebanez "/>
                </occupancy>
            </rooms>
            <remarks>
                <remark> XXXXXX </remark>
            </remarks>
        </booking>
    </bookings>
    </BookingDownloadRS>';
    }

    public function notifyXML() {
        return '<?xml version="1.0" encoding="UTF-8" ?>
<BookingDownloadNotifRS>
             <booking  id="36362525"  status="success" />
</BookingDownloadNotifRS>';
    }

    /**
     * @return table of roomsData
     */
    public function roomstable($xml) {
        preg_match_all('/<room\s(.*?)>/is', $xml, $roomMatches);

        $table = '<table border="1" width="50%">';
        $table .= '<tr><th>ID</th><th>Type</th><th>Min-person</th><th>Max-persons</th><th>Price</th><th>Juniors</th></tr>';

        foreach ($roomMatches[0] as $roomXML) {

            $idMatch = $this->utilFunc->parseOneValue('id', $roomXML);
            $typeMatch = $this->utilFunc->parseOneValue('type', $roomXML);
            $minMatch = $this->utilFunc->parseOneValue('minPersons', $roomXML);
            $maxMatch = $this->utilFunc->parseOneValue('maxPersons', $roomXML);
            $price = $this->utilFunc->parseOneValue('price', $roomXML);
            $junior = $this->utilFunc->parseOneValue('juniors', $roomXML);
            $table .= '<tr>';
            $table .= '<td>' . $idMatch . '</td>';
            $table .= '<td>' . $typeMatch . '</td>';
            $table .= '<td>' . $minMatch . '</td>';
            $table .= '<td>' . $maxMatch . '</td>';
            $table .= '<td>' . $price . '</td>';
            $table .= '<td>' . $junior . '</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    /**
     * @return table of mealPlans
     */
    public function mealplans($xml) {
        preg_match_all('/<mealPlan\b[^>]*>/', $xml, $mealPlanMatches);

        $table = '<table border="2px" >';
        $table .= '<tr><th>Date</th><th>Meal Plan Code</th></tr>';
        foreach ($mealPlanMatches[0] as $mealPlanXML) {
            preg_match('/date="([^"]*)"/', $mealPlanXML, $dateMatch);

            preg_match('/mealPlanCode="([^"]*)"/', $mealPlanXML, $mealPlanCodeMatch);

            $table .= '<tr>';
            $table .= '<td>' . $dateMatch[1] . '</td>';
            $table .= '<td>' . $mealPlanCodeMatch[1] . '</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    /**
     * @return table of childAge
     */
    public function childage($xml) {
        preg_match_all('/<child\s(.*?)>/is', $xml, $childMatches);
        $ages = array();
        $table = '<table border="2px">';
        $table .= '<tr><th>Child Age</th></tr>';
        foreach ($childMatches[0] as $childXML) {
            preg_match('/age="(.*?)"/', $childXML, $ageMatch);
            $table .= '<tr>';
            $table .= '<td>' . $ageMatch[1] . '</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    /**
     * @return table of personName
     */
    public function personsName($xml) {
        preg_match_all('(<person.*?>)', $xml, $personMatches);

        $table = '<table border="1" width="20%" >';
        $table .= '<tr><th>Person Name</th></tr>';

        foreach ($personMatches[0] as $personXML) {
            preg_match('/name="(.*?)"/', $personXML, $nameMatch);
            $table .= '<tr>';
            $table .= '<td>' . $nameMatch[1] . '</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';

        return $table;
    }

}

?>