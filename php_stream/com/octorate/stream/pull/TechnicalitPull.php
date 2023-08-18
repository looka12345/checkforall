<?php
error_reporting(E_ALL);
ini_set('display_errors', '1'); 
/*
* JumbotoursPull.php
* Octorate srl. All rights reserved. 2019
*/




namespace com\octorate\stream\pull;

use com\octorate\stream\common\AbstractPullStream;
use com\octorate\stream\common\PullReservation;
use com\octorate\stream\common\PullReservationRoom;
use com\octorate\stream\common\PullReservationDay;
use com\octorate\stream\common\PullReservationExtra;
use com\octorate\stream\utils\UtilFunc;

include 'D:\xampp\htdocs\xampp\Netbeansprojects\php_stream\com\octorate\stream\common\AbstractPullStream.php';
include 'D:\xampp\htdocs\xampp\Netbeansprojects\php_stream\com\octorate\stream\utils\UtilFunc.php';



class  TechnicalitPull extends AbstractPullStream {

    /**
    * @return array of PullReservation
    */
 
    public function pullReservations() {
        $this->utilFunc = new UtilFunc();
        $this->allRes = [];
        $this->resaArr = [];
        
//        $this->url = 'https://wscontract.xtravelsystem.com/ws-contracts/ContractInsertionService';
      

        if ( $xmlStr = $this->tempXml() ) {
            print_r($xmlStr);
           $this->parseReservations( $xmlStr );
          
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
            echo "<pre>";
            print_r( $this->allRes );
            echo "</pre>";
            exit;
        
        // return array with all reservations found
//        header( 'X-Pull-Version: 2' );
       
    }


    /**
    * @return xml of parse All bookings
    */

    public function parseReservations( $xmlStr ) {
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
    
   
    
    if (!empty($filteredResa))
       {
        foreach ($filteredResa as $refer_id => $tmpf_lmd) {
            
            list($tmpf, $lmd) = explode('@@##', $tmpf_lmd);
           
            
            $status = $this->utilFunc->parseoneValue('status', $tmpf);
            
            $propertyReference = $this->siteUser->hotel_id;
            
            
            
            $this->insertXml($refer_id, $status, $tmpf, $lmd, $propertyReference);
            
           
            $tm=$this->resaArr[$refer_id] = $refer_id;
           
            
            if ($status == 'CANCELLED') {
                $this->allRes[] = $this->retrieveCancellation($refer_id, $lmd, false);
            } elseif ($status == 'CLOSED' || $status == 'CONFIRMED') {
               
                $this->allRes[] = $this->retrieveReservation($tmpf, $refer_id, $status, $lmd, false);
                
            }
        }
    }
 }
//
  public function retrieveReservation( $resultFile, $refer, $status, $lastmodify, $forceFlg ) {
       // success, parse response and create reservations
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CONFIRMED;
        if ($forceFlg) {
            $res->force = \TRUE;
        }
         if ( $status == '1' ) {
            $res->createDate = ( new \DateTime( $lastmodify ) )->format( DATE_ATOM );
            $res->updateDate = ( new \DateTime( '2000-01-01 00:00:00' ) )->format( DATE_ATOM );
        } else {
            $res->updateDate = ( new \DateTime( $lastmodify ) )->format( DATE_ATOM );
            $res->createDate = NULL;
        
        //$ccs_token = $this->utilFunc->parseOneValue('ccs_token', $resultFile);
        $res->creditCard->token = NULL;
        $res->guest->email = NULL;
        $res->guest->phone = NULL;
        $res->guest->firstName = substr($this->utilFunc->parseoneValue('clientFirstName', $resultFile), 0, 40);
        $res->guest->lastName = substr($this->utilFunc->parseoneValue('clientFirstSurname', $resultFile), 0, 40);
        $res->guest->address = NULL;
        $res->guest->city = NULL;
        $res->guest->country = NULL;
        $res->guest->zip = NULL;
        $res->currency =null;
        $res->language = strtoupper($this->utilFunc->parseoneValue('Language', $resultFile));
        $res->propertyReference = $this->siteUser->hotel_id;

        // create room data
        if (preg_match("/(<bookings.*?<\/bookings>)/is", $resultFile, $match)) {
            $tempFile = $resultFile;
            $checkIn = (new \DateTime($this->utilFunc->parseOneValue('dateIn', $tempFile)))->format('Y-m-d');
            $checkOut = (new \DateTime($this->utilFunc->parseOneValue('dateIn', $tempFile)))->format('Y-m-d');
            while (preg_match("/(<bookings.*?<\/bookings>)/is", $tempFile, $match)) {
                $tempFile = $this->utilFunc->after($match[0], $tempFile);
                $roomXml = $match[1];
                $dayXml = $roomXml;
                $rateXml = $roomXml;
                $room = new PullReservationRoom($this->utilFunc->parseOneValue('RoomTypeCode', $roomXml) . ':' . $this->utilFunc->parseOneValue('RatePlanCode', $roomXml));
                $adultCount = 0;
                $childCount = 0;
                while (preg_match('/<rooms\s+(.*?)\/>/is', $dayXml, $match)) {
                    $dayXml = $this->utilFunc->after($match[0], $dayXml);
                    $guestDet = $match[1];
                    if (preg_match('/AgeQualifyingCode=\"10\"/is', $guestDet)) {
                        $adult = $match[1];
                        $adultCount = $this->utilFunc->parseOneValue('Count', $adult);
                    } else {
                        $childCount = $this->utilFunc->parseOneValue('Count', $guestDet);
                    }
                }
                $total = 0;
                while (preg_match("/<room(.*?)<\/room>/is", $rateXml, $match)) {
                    $rateXml = $this->utilFunc->after($match[0], $rateXml);
                    $rate = $match[1];
                    $price = $this->utilFunc->parseOneValue('AmountAfterTax', $rate);
                    $date = new \DateTime($this->utilFunc->parseOneValue('EffectiveDate', $rate));
                    $room->daily[] = new PullReservationDay($date->format('Y-m-d'), round($price, 2));
                    $total += $price;
                }
                $room->refer = $refer;
                $room->children = $childCount;
                $room->pax = $adultCount + $childCount;
                $room->totalCommissions = NULL;
                $room->total = round($total, 2);
                $room->checkIn = $checkIn;
                $room->checkOut = $checkOut;
                $room->specialRequests = NULL;
                $room->totalPaid = NULL;
                $room->notes = NULL;
                $room->paidNotes = NULL;

                $voucherJson = $this->createVoucherV2($res, $room);
                $voucherJson['ResGuestRPH'] = $this->utilFunc->parseOneValue('ResGuestRPH', $resultFile);
                $voucherJson['NumberOfUnits'] = $this->utilFunc->parseOneValue('NumberOfUnits', $resultFile);
                $voucherJson['ResID_Type'] = $this->utilFunc->parseOneValue('ResID_Type', $resultFile);
                $voucherJson['UniqueID'] = $this->utilFunc->parseOneValue('UniqueID ID', $resultFile);
                $room->json = $voucherJson;
                $res->rooms[] = $room;
            }
        } else {
            throw new \Exception("Something went wrong, error pulling reservations!");
        }
        return $res;
  }
  
        }

//    /**
//    * @return array of retrieveCancellation
//    */

  public function retrieveCancellation( $refer, $lastmodify, $forceFlg ) {
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CANCELLED;
        if ( $forceFlg ) {
            $res->force = \TRUE;
        }
        $res->updateDate = ( new \DateTime( $lastmodify ) )->format( DATE_ATOM );
        $res->createDate = NULL;
        // return single reservations object
        return $res;
    }

    
    

    
    
    
    
    public function getReservations() {
        $d1 = new \DateTime();
        $d1->sub( new \DateInterval( 'P1D' ) );
        $d2 = new \DateTime();
        $result = '';
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<BookingDownloadRQ>
    <credentials>
        <username>' . $this->siteUser->sites_user . '</username>
        <password>' . $this->siteUser->sites_pass .'</password>
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
        
    
        $result = $this->utilFunc->submitXmlPost( $this->url, $xml, [] );
        $result = preg_replace( '/\&lt\;/is', '<', $result );
        $result = preg_replace( '/\&gt\;/is', '>', $result );
        $result = preg_replace( '/\&quot\;/is', '"', $result );
        $this->insertResaLog( $xml . '------' . $result );
        //echo 'url=>'.$this->url."\nxml=>$xml\nresult=>$result\n";
        //$result = $this->tempXml();

        if ( preg_match( '/bookingLocator/is', $result ) ) {
            return $result;
        } elseif ( preg_match( '/<ns2:searchServicesResponse\s+xmlns:ns2\W+http\W+contracts\.jumbotours.com\W+\/>/is', $result, $match ) ) {
            return 0;
            //no reservation
        } elseif ( preg_match( '/Warnings/is', $result, $match ) ) {
            throw new \Exception( 'Something went wrong, warning pulling reservations!' );
        } else {
            throw new \Exception( 'Something went wrong, error pulling reservations!' );
        }
        if($result){
            $notify=$this->bookingNotify();
            print_r($notify);
        }
    }
    

/**
 * @return notify of booking 
 */

    public function bookingNotify() {
        $requestXML='<?xml version="1.0" encoding="UTF-8" ?>
                    <BookingDownloadNotifRQ>
    <credentials>
   	 <username>'. $this->siteUser->sites_user .'</username>
   	 <password>' . $this->siteUser->sites_pass .'</password>
    </credentials>
    <bookings>
   	 <book id="36362525"  locator="11111111" />
    </bookings>
</BookingDownloadNotifRQ>
';
     $result = $this->utilFunc->submitXmlPost( $this->url, $requestXML, [] );  
    return $result;    
     
    }
    /**
    * @return table of Rates
    */

//    public function parseDetails( $res, $tag ) {
//        $details = $res;
//        $table = '<table border="1" width="50%">';
//        $table .= '<tr>';
//        $table .= '<td align="center">Text</td>';
//        $table .= '<td align="center">Type</td>';
//        $table .= '</tr>';
//        $regex = "/<$tag>(.*?)<\/$tag>/is";
//        while ( preg_match( $regex, $details, $m ) ) {
//            $details = $this->utilFunc->after( $m[ 0 ], $details );
//            $xml = $m[ 1 ];
//            $text = trim( $this->utilFunc->parseXmlValue( 'text', $xml ) );
//            $type = trim( $this->utilFunc->parseXmlValue( 'type', $xml ) );
//            $table .= '<tr>';
//            $table .= '<td align="center">' . $text . '</td>';
//            $table .= '<td align="center">' . $type . '</td>';
//            $table .= '</tr>';
//        }
//        $table .= '</table>';
//        return $table;
//    }



    
    
    
    

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


}




