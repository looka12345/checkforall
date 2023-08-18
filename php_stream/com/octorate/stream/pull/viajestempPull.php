<?php

namespace com\octorate\stream\pull;

error_reporting(E_ALL);
ini_set('display_errors', '1');

use com\octorate\stream\common\AbstractPullStream;
use com\octorate\stream\common\PullReservation;
use com\octorate\stream\common\PullReservationRoom;
use com\octorate\stream\common\PullReservationDay;
use com\octorate\stream\common\PullReservationExtra;
use com\octorate\stream\utils\UtilFunc;

//include 'D:\xampp\htdocs\xampp\Netbeansprojects\php_stream\com\octorate\stream\common\PullReservationRoom';
//include 'D:\xampp\htdocs\xampp\Netbeansprojects\php_stream\com\octorate\stream\common\AbstractPullStream.php';
//include 'D:\xampp\htdocs\xampp\Netbeansprojects\php_stream\com\octorate\stream\utils\UtilFunc.php';
class viajestempPull extends AbstractPullStream {

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
        //                    if ( $value[ 'status' ] == 'CANCELLED' && $value[ 'xml' ] != '' ) {
        //                        $this->allRes[] = $this->retrieveCancellation( $refer, $lastmodify, true );
        //                    } elseif ( $value[ 'status' ] == 'CLOSED' && $value[ 'xml' ] != '' ) {
        //                        $this->allRes[] = $this->retrieveReservation( $value[ 'xml' ], $refer, $value[ 'status' ], $lastmodify, true );
        //                    }
        //                    if ( !( $this->test == true ) ) {
        //                        $this->markAsProcessedBooking( $refer );
        //                    }
        //                }
        //            }
        //        }
        //
        echo 'allRes=>';
        echo '<pre>';
        print_r($this->allRes);
        echo '</pre>';

//        return $this->allRes;
        exit;
    }

    /**
     * @return array of ParseReservation
     */
    public function parseReservations($xmlStr) {
        $filteredResa = array();
        $regex = '/(<booking\s.*?<\/booking>)/is';
        while (preg_match($regex, $xmlStr, $match)) {
            $xmlStr = $this->utilFunc->after($match[0], $xmlStr);
            $tempFile = $match[1];
            $refer = $this->utilFunc->parseOneValue('id', $tempFile);
            $lastmodify = (new \DateTime($this->utilFunc->parseXmlValue('reservationDate', $tempFile)))->format('Y-m-d H:i:s');
            if (!array_key_exists($refer, $filteredResa)) {
                $filteredResa[$refer] = $tempFile . '@@##' . $lastmodify;
            } else {
                list($tmpFile, $lm) = explode('@@##', $filteredResa[$refer]);
                if (strtotime($lastmodify) > strtotime($lm)) {
                    $filteredResa[$refer] = $tempFile . '@@##' . $lastmodify;
                }
            }
        }
        if (!empty($filteredResa)) {
            foreach ($filteredResa as $refer_id => $tmpf_lmd) {
                list($tmpf, $lmd) = explode('@@##', $tmpf_lmd);
                
                $status = $this->utilFunc->parseoneValue('status', $tmpf);
                $propertyReference = $this->siteUser->hotel_id;
                $this->insertXml($refer_id, $status, $tmpf, $lmd, $propertyReference);
                $tm = $this->resaArr[$refer_id] = $refer_id;
                if ($status == 'CANCELLED') {
                    $this->allRes[] = $this->retrieveCancellation($refer_id, $lmd, false);
                } elseif ($status == '1' || $status == 'CONFIRMED') {
                    $this->allRes[] = $this->retrieveReservation($tmpf, $refer_id, $status, $lmd, false);
                }
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
        if ($status == 'CLOSED') {
            $res->createDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
            $res->updateDate = (new \DateTime('2000-01-01 00:00:00'))->format(DATE_ATOM);
        } else {
            $res->updateDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
            $res->createDate = NULL;
        }
        $res->creditCard->token = NULL;
        $res->guest->email = $this->utilFunc->parseOneValue('clientEmail', $resultFile);
        $res->guest->phone = $this->utilFunc->parseOneValue('clientPhone', $resultFile);
        $firstName = '';
        $lastName = '';
        list($firstName, $lastName) = explode(' ', $this->utilFunc->parseOneValue('clientName', $resultFile) . ',');
        if (empty($firstName) && empty($lastName)) {
            list($firstName, $lastName) = explode(',', $this->utilFunc->parseOneValue('clientName', $resultFile) . ',');
        }
        $res->guest->firstName = substr($firstName, 0, 40);
        $res->guest->lastName = substr($lastName, 0, 40);
        $res->guest->address = null;
        $res->guest->city = null;
        $res->guest->zip = null;

        // Create room data
        if (preg_match('/(<rooms.*>(.*?)<\/rooms>)/is', $resultFile, $roomsmatch)) {
            $tempFile = $roomsmatch[1];
            $checkIn = (new \DateTime($this->utilFunc->parseoneValue('dateIn', $tempFile)))->format('Y-m-d');
            $checkOut = (new \DateTime($this->utilFunc->parseoneValue('dateOut', $tempFile)))->format('Y-m-d');
            $totalBuffer = $this->utilFunc->parseoneValue('price', $resultFile);
            $total = $totalBuffer;

            $roomId = $this->utilFunc->parseoneValue('room id', $tempFile);
            $roomRateplancode = $this->utilFunc->parseoneValue('type', $tempFile);
            $roomdata = $roomId . ':' . $roomRateplancode;
            $res->rooms->room = new PullReservationRoom($roomId . ':' . $roomRateplancode);
          
            preg_match_all('/(<room\s.*?>.*?<\/room>)/is', $resultFile, $matches);
            $numofrooms = count($matches);
            
            preg_match_all('/<room\s(.*?)>/is', $resultFile, $roomMatches);
           
            $roomData = array();
            foreach ($roomMatches[0] as $roomXML) {
                $roomAttributes = [];
                
                preg_match_all('/\b(\w+)="([^"]*)"/', $roomXML, $attributeMatches, PREG_SET_ORDER);
                foreach ($attributeMatches as $attributeMatch) {
                    $attributeName = $attributeMatch[1];
                    $attributeValue = $attributeMatch[2];
                    $roomAttributes[$attributeName] = $attributeValue;
                }
                // Store room attributes in array
                $roomData[] = $roomAttributes;
                
            }
       
            
            
           

            //create occupancy data//
            if (preg_match('/(<occupancy>.*?<\/occupancy>)/is', $resultFile, $match)) {
                $xml = simplexml_load_string($match);
                $names = [];
                foreach ($xml->person as $person) {
                    $name = (string) $person['name'];
                    $names[] = $name;
                }

                $table = '<table>';
                $table .= '<thead><tr><th>Name</th></tr></thead>';
                $table .= '<tbody>';
                foreach ($names as $name) {
                    $table .= '<tr><td>' . $name . '</td></tr>';
                }
                $table .= '</tbody>';
                $table .= '</table>';
            }
            //create meal data
            if (preg_match('/(<mealPlans>.*?<\/mealPlans>)/is', $resultFile, $match)) {
                $date = (new \DateTime($this->utilFunc->parseoneValue('date', $match[0])))->format('Y-m-d');
                $mealplancode = $this->utilFunc->parseOneValue('mealPlanCode', $match[0]);
                $mealPlans->date = $date;
                $mealPlans->mealplancode = $mealplancode;
            }
            //chids count
            preg_match_all('/age="(.*?)"/is', $resultFile, $ageMatch);
            //daily price//
            preg_match_all('/<dayPrice\s(.*?)>/is', $resultFile, $priseMatches);
            $roomData = array();
            foreach ($priseMatches[0] as $priseXML) {
                $priseAttributes = [];
                preg_match_all('/\b(\w+)="([^"]*)"/', $priseXML, $attributeMatches, PREG_SET_ORDER);
                foreach ($attributeMatches as $attributeMatch) {
                    $attributeName = $attributeMatch[1];
                    $attributeValue = $attributeMatch[2];
                    $priseAttributes[$attributeName] = $attributeValue;
                }
                // Store room attributes in array
                $priseData[] = $priseAttributes;
            }

            $res->rooms->room->rooms = $roomData;
            $res->rooms->room->children = $this->utilFunc->parseXmlValue('children', $tempFile);
            $res->rooms->room->pax = $this->utilFunc->parseXmlValue('adults', $tempFile) + $this->utilFunc->parseXmlValue('children', $tempFile);
            $res->rooms->room->total = round($total, 2);
            $res->rooms->room->taxIncluded = true;
            $res->rooms->room->totalPaid = NULL;
            $res->rooms->room->checkIn = $checkIn;
            $res->rooms->room->checkOut = $checkOut;
            //creating remarks data///
            preg_match('/<remark>(.*?)<\/remark>/is', $resultFile, $remarksmatch);
            $res->rooms->room->notes = $remarksmatch[1];
            $res->rooms->room->paidNotes = NULL;
            $res->rooms->room->daily=$priseData;
            $voucherJson = $this->createVoucherV2($res, $room);
            //occupancy data in comments//
//            $voucherJson['Comments'] = $table;

            $voucherJson['ReservationDate'] = $this->utilFunc->parseOneValue('reservationDate', $resultFile);
            $voucherJson['CancellationDate'] = $this->utilFunc->parseOneValue('cancellationDate', $resultFile);
            $voucherJson['HotelCode'] = $this->utilFunc->parseOneValue('hotelCode', $resultFile);
            $voucherJson['RatePlanCode'] = $this->utilFunc->parseOneValue('ratePlanCode', $resultFile);
            $voucherJson['StatusCode'] = $this->utilFunc->parseOneValue('statusCode', $resultFile);
            $voucherJson['Discount'] = $this->utilFunc->parseOneValue('discount', $resultFile);
            $voucherJson['Mealplans'] = $mealPlans;
            $voucherJson['Childs Count'] = $ageMatch[1];

            $res->rooms->room->json = $voucherJson;
        } else {
            throw new \Exception('Something went wrong, error pulling reservations!');
        }
        //print_r( $res );
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

    public function getReservations() {
        $d1 = new \DateTime();
        $d1->sub(new \DateInterval('P1D'));
        $d2 = new \DateTime();
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
//        $result = $this->utilFunc->submitXmlPost($this->url, $xml, []);
        $result = $this->tempXml();
        $result = preg_replace('/\&lt\;/is', '<', $result);
        $result = preg_replace('/\&gt\;/is', '>', $result);
        $result = preg_replace('/\&quot\;/is', '"', $result);
        $this->insertResaLog($xml . '------' . $result);
        //echo 'url=>'.$this->url."\nxml=>$xml\nresult=>$result\n";
        //$result = $this->tempXml();
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
//        $result = $this->utilFunc->submitXmlPost($this->url, $requestXML, []);
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
        <booking id="177584" dateIn="2016-11-12" dateOut="2016-11-13" reservationDate="2016-10-20" cancellationDate="" clientName="Elodie Della-santa " clientFirstName="Elodie" clientFirstSurname=" Della-santa " clientSecondSurname=" " price="329.27" status="1" ratePlanCode="1" hotelCode="562" statusCode="1" currencyCode="EUR"  discount="-35.25" clientEmail="test@test.com" clientPhone="34666666666">
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

}
?>