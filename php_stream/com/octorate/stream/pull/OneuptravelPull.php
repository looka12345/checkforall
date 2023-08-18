<?php

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

class OneuptravelPull extends AbstractPullStream {

    var $gettimestamp = '';
    var $flag = 0;
    var $nflag = 0;
    public $strxml = '';

    /**
     * @return array of PullReservation
     */
    public function pullReservations() {
        $this->utilFunc = new UtilFunc();
        $this->allRes = [];
        $this->resaArr = [];
        
        if ($xmlStr = $this->getReservations(1)) {

            $this->parseReservations($xmlStr);
        }
        elseif($xmlStr = $this->getReservations(2)) {

        $this->parseReservations($xmlStr);
        
        }
        
        //        if ( $this->checkUnprocessedBooking() ) {
        //            foreach ( $this->pendingResaArr as $refer => $value ) {
        //                if ( !( array_key_exists( $refer, $this->resaArr ) ) ) {
        //                    $lastmodify = ( new \DateTime( $value[ 'lastmodify_time' ] ) )->format( 'Y-m-d H:i:s' );
        //                    if ( $value[ 'status' ] == 'CNF' || $value[ 'status' ] == 'PND' || $value[ 'status' ] == 'cnf' || $value[ 'status' ] == 'pnd' ) {
        //                      $this->allRes[] = $this->retrieveReservation( $value[ 'xml' ], $refer, $value[ 'status' ], $lastmodify, true );
        //                    } else {
        //                     $this->allRes[] = $this->retrieveCancellation( $refer, $lastmodify, true );
        //                    }
        //                    if ( !( $this->test == true ) ) {
        //                        $this->markAsProcessedBooking( $refer );
        //                    }
        //                }
        //            }
        //        }
        //

        echo'<pre>';
        print_r($this->allRes);
        echo'</pre>';
        exit();
//        return $this->allRes;
    }

    /**
     * @return xml of parse All bookings
     */
    public function parseReservations($xmlStr) {

        $this->strxml = $xmlStr;
        while (preg_match('/(<booking>.*?<\/booking>)/is', $xmlStr, $match)) {

            $xmlStr = $this->utilFunc->after($match[0], $xmlStr);
            $tempFile = $match[1];

            $refer = $this->utilFunc->parseoneValue('supplier reference', $tempFile);
            $lastmodify = (new \DateTime($this->utilFunc->parseoneValue('lastmodified date', $tempFile) . ' ' . $this->utilFunc->parseoneValue('lastmodified time', $tempFile)))->format('Y-m-d H:i:s');

            if ($lastmodify == ' ') {
                $lastmodify = (new \DateTime($this->utilFunc->parseoneValue('creation date', $tempFile) . ' ' . $this->utilFunc->parseoneValue('creation time', $tempFile)))->format('Y-m-d H:i:s');
            }
            $status = $this->utilFunc->parseoneValue('service status', $tempFile);

            $propertyReference = $this->siteUser->hotel_id;
            $this->insertXml($refer, $status, $tempFile, $lastmodify, $propertyReference);

            if ($status == 'CNF' || $status == 'PND' || $status == 'cnf' || $status == 'pnd') {
                $this->allRes[] = $this->retrieveReservation($tempFile, $refer, $status, $lastmodify, false);
            } else {
                $this->allRes[] = $this->retrieveCancellation($refer, $lastmodify, false);
            }
        }
    }

    /**
     * @return array of retrieveReservation
     */
    public function retrieveReservation($resultFile, $refer, $status, $lastmodify, $forceFlg) {

        $details = $this->goForDetailResa($refer);
        $this->strxml .= $details;
        // Success, parse response and create reservations
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CONFIRMED;
        $res->language = NULL;
        $res->currency = $this->utilFunc->parseoneValue('currency type', $resultFile);
        $res->paymentMode = NULL;

        if ($forceFlg) {
            $res->force = \TRUE;
        }

        $res->createDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
        $res->updateDate = (new \DateTime('2000-01-01 00:00:00'))->format(DATE_ATOM);

        $res->creditCard->token = NULL;

        $res->guest->email = NULL;

        $res->guest->phone = NULL;

        $firstName = $this->utilFunc->parseoneValue('pax title', $this->strxml) . ' ' . $this->utilFunc->parseoneValue('initial', $this->strxml);
        $lastName = $this->utilFunc->parseoneValue('surname', $this->strxml);

        $res->guest->firstName = substr($firstName, 0, 40);
        $res->guest->lastName = substr($lastName, 0, 40);
        $res->guest->address = $this->utilFunc->parseXmlValue('address', $this->strxml);
        $res->guest->city = $this->utilFunc->parseXmlValue('city', $this->strxml);
        $zip = $this->utilFunc->parseXmlValue('zip', $this->strxml);
        if ($zip) {
            $res->guest->zip = $zip;
        } else {
            $res->guest->zip = NULL;
        }
        $country = $this->utilFunc->parseXmlValue('country', $this->strxml);
        $finalcount = count($country);

        if ($finalcount < 2) {
            $res->guest->country = substr($country, 0, 2);
        } else {
            $res->guest->country = $country;
        }

        // Create room data
        if (preg_match_all('/(<room\s.*>.*?<\/room>)/is', $this->strxml, $roomsmatch)) {

            $numberofrooms = count($roomsmatch[0]);

            $checkIn = (new \DateTime($this->utilFunc->parseoneValue('checkin date', $this->strxml)))->format('Y-m-d');
            $checkOut = (new \DateTime($this->utilFunc->parseoneValue('checkout date', $this->strxml)))->format('Y-m-d');

            foreach ($roomsmatch[0] as $roomXML) {

                $room = new PullReservationRoom($this->utilFunc->parseoneValue('room type', $roomXML) . ':' . $this->utilFunc->parseoneValue('booking code', $this->strxml));
                if ($room) {
                    $totalBuffer = $this->utilFunc->parseoneValue('net amount', $this->strxml);
                   
                   
//                     $data ='';
//                     while(preg_match('/<date\s(.*?)>(.*?)<\/date>/is', $this->strxml, $data)){
//                         echo 'hello';
//                        $resultFile = $this->utilFunc->after($data[0],$this->strxml);
//                        $datadaily =$data[0];
//                        $pricePerDay = $this->utilFunc->parseoneValue('sell value', $datadaily);
//                        $date = $this->utilFunc->parseoneValue('date value', $datadaily);
//                        $room->daily[] = new PullReservationDay($date, $pricePerDay, false);
//                    }

                    $childen = $this->utilFunc->parseoneValue('children', $resultFile);
                    if ($childen) {
                        $room->children = $childen;
                    } else {
                        $room->children = null;
                    }
                    $pax = $this->utilFunc->parseoneValue('adults', $resultFile) + $this->utilFunc->parseoneValue('children', $resultFile);
                    if ($pax) {
                        $room->pax = $pax;
                    } else {
                        $room->pax = NULL;
                    }
                    $room->total = round($totalBuffer, 2);
                    $room->taxIncluded = false;
                    $room->totalPaid = NULL;
                    $room->checkIn = $checkIn;
                    $room->checkOut = $checkOut;
                    $room->paidNotes = NULL;
                    $firstName = $this->utilFunc->parseoneValue('pax title', $this->strxml) . ' ' . $this->utilFunc->parseoneValue('initial', $this->strxml);
                    $lastName = $this->utilFunc->parseoneValue('surname', $this->strxml);

                    $room->guest->firstName = substr($firstName, 0, 40);
                    $room->guest->lastName = substr($lastName, 0, 40);

                    $voucherJson = $this->createVoucherV2($res, $room);
                    $voucherJson['Number of Rooms'] = $numberofrooms;
                    $voucherJson['customer code'] = $this->utilFunc->parseoneValue('customer code', $this->strxml);
                    $voucherJson['office code'] = $this->utilFunc->parseoneValue('office code', $this->strxml);
                    $voucherJson['service date'] = $this->utilFunc->parseoneValue('service date', $this->strxml);
                    $voucherJson['service type'] = $this->utilFunc->parseoneValue('service type', $this->strxml);
                    if (preg_match('/<issuer.*?>(.*?)<\/issuer>/is', $this->strxml, $issuer)) {
                        $voucherJson['issuer code'] = $this->utilFunc->parseoneValue('issuer code', $issuer[0]);

                        $voucherJson['issuer name'] = $this->utilFunc->parsexmlValue('name', $issuer[0]);
                        $voucherJson['issuer address'] = $this->utilFunc->parsexmlValue('address', $issuer[0]);
                    }
                    if (preg_match('/<supplier.*?>(.*?)<\/supplier>/is', $this->strxml, $supplier)) {
                        $voucherJson['supplier code'] = $this->utilFunc->parseoneValue('supplier code', $supplier[0]);

                        $voucherJson['supplier vatnumber'] = $this->utilFunc->parseoneValue('vatnumber', $supplier[0]);
                    }

                    $voucherJson['customereference code'] = $this->utilFunc->parseoneValue('customereference code', $this->strxml);
                    $voucherJson['clerk code'] = $this->utilFunc->parseoneValue('clerk code', $this->strxml);
                    $voucherJson['booking code'] = $this->utilFunc->parseoneValue('booking code', $this->strxml);
                    if (preg_match('/<hotel .*?>/is', $this->strxml, $hotel)) {
                        $voucherJson['hotel code'] = $this->utilFunc->parseoneValue('code', $hotel[0]);
                        $voucherJson['hotel name'] = $this->utilFunc->parseoneValue('name', $hotel[0]);
                        $voucherJson['hotel telephone'] = $this->utilFunc->parseoneValue('telephone', $hotel[0]);
                        $voucherJson['hotel fax'] = $this->utilFunc->parseoneValue('fax', $hotel[0]);
                    }
                    $voucherJson['agreement'] = $this->utilFunc->parsexmlValue('agreement', $this->strxml);
                    $breakfast = $this->utilFunc->parseoneValue('breakfast', $this->strxml);

                    $voucherJson['breakfast'] = $this->parseBreakfast($breakfast);
                    $meal = $this->utilFunc->parseoneValue('meal', $this->strxml);
                    $voucherJson['meal'] = $this->parseMeal($meal);
                    $voucherJson['deadline date'] = $this->utilFunc->parseoneValue('deadline date', $this->strxml);
                    $voucherJson['paymentdue date'] = $this->utilFunc->parseoneValue('paymentdue date', $this->strxml);
                    $voucherJson['remarks'] = $this->remarkTable($this->strxml);

                    $room->json = $voucherJson;
                    $res->rooms[] = $room;
                }
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
    public function getReservations($flag_temp) {
        $this->getCurrTime();
        $d1 = new \DateTime();
        $d1->sub(new \DateInterval('P1D'));
        $d2 = new \DateTime();

        if($flag_temp ='1')
        { 
        $xml = '<envelope>
		   <header>
			<actor>' . $this->siteUser->user_org_id . '</actor>   
			 <user>' . $this->siteUser->sites_user . '</user>
			<password>' . $this->siteUser->sites_pass . '</password>
			<version>1.0.0</version>
			<timestamp>' . $this->gettimestamp . '</timestamp>
		    </header>
					<query type="listall" product="bookings">
						<filters>
							<filter type="booking" from="" to=""/>
							<filter type="services" from="" to=""/>
							<filter type="lastmodified" from="' . $d1->format('Y-m-d') . '" to="' . $d2->format('Y-m-d') . '"/>
							<filter type="customer" code=""/>
							<filter type="dossier" number=""/>
						</filters>
					</query>
				</envelope>';

        $result = $this->tempXml();
//      $result = $this->utilFunc->submitXmlPost(''https://connector.netstorming.net/kalima/twoways/call.php' , $xml, [] );

        if (!(preg_match('/<bookings><booking>/is', $result, $match))) {
            if (preg_match('/<envelope>(.*?)<\/envelope>/', $result)) {
                $mesg = $match[1];
                if (preg_match('/Nessuna prenotazione per la Struttura/', $mesg)) {

                    return 0;
                } elseif (preg_match('/Authentication Failed/', $mesg)) {
                    $this->flag = 2;
                    $this->nflag = 1;

                    return 0;
                } elseif (preg_match('/no data from the request/is', $mesg)) {
                    return 0;
                } else {

                    $this->flag = 1;
                    $this->nflag = 1;

                    return 0;
                }
            }
        }
        }
         if($flag_temp ='2'){
             $xml = '<envelope>
					<header>
						<actor>' . $this->siteUser->user_org_id . '</actor>   
						<user>' . $this->siteUser->sites_user . '</user>
						<password>' . $this->siteUser->sites_pass . '</password>
						<version>1.0.0</version>
						<timestamp>' . $this->gettimestamp . '</timestamp>
					</header>
					<query type="listall" product="bookings">
						<filters>
							<filter type="booking" from="' . $d1->format('Y-m-d') . '" to="' . $d2->format('Y-m-d') . '"/>
							<filter type="services" from="" to=""/>
							<filter type="lastmodified" from="" to=""/>
							<filter type="customer" code=""/>
							<filter type="dossier" number=""/>
						</filters>
					</query>
				</envelope>';

//        $result = $this->utilFunc->submitXmlPost('https://connector.netstorming.net/kalima/twoways/call.php', $xml, []);

        $result = $this->tempXml();
        if (!(preg_match('/<bookings><booking>/is', $result, $match))) {
            if (preg_match('/<envelope>(.*?)<\/envelope>/', $result,$match)) {
                $mesg = $match[1];
                if (preg_match('/Nessuna prenotazione per la Struttura/', $mesg)) {

                    return 0;
                } elseif (preg_match('/Authentication Failed/', $mesg)) {
                    $this->flag = 2;
                    $this->nflag = 1;

                    return 0;
                } elseif (preg_match('/no data from the request/is', $mesg)) {
                    return 0;
                } else {

                    $this->flag = 1;
                    $this->nflag = 1;

                    return 0;
                }
            }
        }
         }
        

        return $result;
    }

    

    /**
     * @return xml of details of bookings //
     */
    function goForDetailResa($refer) {
        $this->getCurrTime();
        $xml = '<envelope>
					<header> 
						<actor>' . $this->siteUser->user_org_id . '</actor>
						<user>' . $this->siteUser->sites_user . '</user>
						<password>' . $this->siteUser->sites_pass . '</password>
						<version>1.0.0</version>
						<timestamp>' . $this->gettimestamp . '</timestamp>
					</header>
					<query type="track" product="bookings">
						<filters>
							<filter type="reference" code="' . $refer . '"/>
						</filters>
					</query>
				</envelope>';

        $detailResa = $this->utilFunc->submitXmlPost($xml, 'https://connector.netstorming.net/kalima/twoways/call.php', []);

        if (preg_match('/<bookings>(.*?)<\/bookings>/is', $detailResa, $m)) {
            $detailResa = $m[1];
        }



        return $detailResa;
    }

    //get current  time//
    public function getCurrTime() {
        $this->gettimestamp = date("Y") . '' . date("m") . '' . date("d") . '' . date("h") . '' . date("i") . '' . date("s");
    }

    //parse breakfast//
    public function parseBreakfast($breakFast) {
        $retStr = '';

        if ($breakFast == 'G') {
            $retStr = 'GALLESE';
        }
        if ($breakFast == 'P') {
            $retStr = 'Piccola colazione al banco';
        }
        if ($breakFast == 'X') {
            $retStr = 'Senza colazione';
        }
        if ($breakFast == 'Z') {
            $retStr = 'Servizio in Camera - Americana';
        }
        if ($breakFast == 'Q') {
            $retStr = 'Italiana';
        }
        if ($breakFast == 'E') {
            $retStr = 'Inglese';
        }
        if ($breakFast == 'H') {
            $retStr = 'Buffet caldo';
        }
        if ($breakFast == 'J') {
            $retStr = 'English Buffet';
        }
        if ($breakFast == 'T') {
            $retStr = 'Scozzese';
        }
        if ($breakFast == 'W') {
            $retStr = 'Gallese';
        }
        if ($breakFast == 'F') {
            $retStr = 'Box breakfast';
        }
        if ($breakFast == 'I') {
            $retStr = 'Israeliana';
        }
        if ($breakFast == 'A') {
            $retStr = 'Americana';
        }
        if ($breakFast == 'D') {
            $retStr = 'Self Catering';
        }
        if ($breakFast == 'C') {
            $retStr = 'Continentale';
        }
        if ($breakFast == 'B') {
            $retStr = 'Buffet freddo';
        }
        if ($breakFast == 'O') {
            $retStr = 'Servizio in camera(Continentale)';
        }
        if ($breakFast == 'S') {
            $retStr = 'Scandinava';
        }
        if ($breakFast == 'R') {
            $retStr = 'Irlandese';
        }
        if ($breakFast == 'L') {
            $retStr = 'Colazione a la carte';
        } else {
            return $breakFast;
        }
        return $retStr;
    }

    // parse meal /

    public function parseMeal($meal) {
        $retStr = '';

        if ($meal == 'AI') {
            $retStr = 'Tutto Incluso';
        }
        if ($meal == 'FB') {
            $retStr = 'Pensione Completa';
        }
        if ($meal == 'RD') {
            $retStr = 'Mezza pensione';
        }
        if ($meal == 'RB') {
            $retStr = 'Pernottamento e prima colazione';
        }
        if ($meal == 'RL') {
            $retStr = 'Pernottamento e pranzo';
        }
        if ($meal == 'D') {
            $retStr = 'Pernottamento e Cena';
        }
        if ($meal == 'P') {
            $retStr = 'Pranzo Pasquale';
        }
        if ($meal == 'RO') {
            $retStr = 'Solo pernottamento';
        }
        if ($meal == 'S') {
            $retStr = 'Self Catering';
        }
        return $retStr;
    }

    public function remarkTable($resultFile) {

        $details = '<table border="1" width="100%">';
        $details .= '<tr>';
        $details .= '<td align="center">code</td>';

        $details .= '<td align="center">text</td>';

        $details .= '</tr>';

        //print "resultFile=>$resultFile\n\n";
        while (preg_match('/<remark\s.*?>/is', $resultFile, $match)) {
            //print "test=>\n\n";
            $resultFile = $this->utilFunc->after($match[0], $resultFile);

            $tempFile = $match[0];

            $code = $this->utilFunc->parseoneValue('code', $tempFile);
            $text = $this->utilFunc->parseoneValue('text', $tempFile);

            $details .= '<tr>';
            $details .= '<td align="center">' . $code . '</td>';
            $details .= '<td align="center">' . $text . '</td>';

            $details .= '</tr>';
        }
        $details .= '</table>';
        return $details;
    }

    /**
     * @return xml of temp. XML
     */
    public function tempXml() {
        return '<bookings>
	<booking>
		<dossier number = "F0723GFYBM"/>
		<service name = "B0723HB7YM"/>
		<service status = "CNF"/>
		<customer code = "logib2b"/>
		<office code = "oneuptravel"/>
		<customertaxcode>00000000000</customertaxcode>
		<service date = "2023-09-09"/>
		<service type = "1"/>
		<lastmodified date = "2023-07-07"/>
		<lastmodified time = "13:28:16"/>
		<creation date = "2023-07-07"/>
		<creation time= "13:27:53"/>
		<issuer code= "oneuptravel">
			<name>One Up Travel</name>
			<address>Sede Legale: Via Vincenzo Mirabella, 18</address>
		</issuer>
		<supplier reference = "B0723HB7YM"/>
		<currency type = "EUR"/>
		<incee>true</incee>
		<currency code="EUR"/>
		<net amount= "647.96"/>
		<sell amount= "647.96"/>
	</booking>
        
	<supplier code="B0723HB7YM" vatnumber="IT05027000289">Solemare srl</supplier>
	<customereference code = "960623255"/>
	<customertaxcode>00000000000</customertaxcode>
	<clerk code="logib2b"/>
	<booking code="B0723HB7YM"/>
	<status code="cnf"/>
	<checkin date="2023-09-09"/>
	<checkout date="2023-09-16"/>
	<hotel code ="190339" name="hotel il monastero" telephone="+39 070802200" fax="+39 0707753004" email=""/>
	<country code ="i">ITALIA</country>
	<city code ="qsel">QUARTU SANT ELENA - CAGLIARI</city>
	<agreement>LCL.30414</agreement>
	<ctype>CLASSIC - VISTA GIARDINO</ctype>
	<distributor code =""></distributor>
	<address>Via Delle Sequoie, 14 – S.P. 17 Km 17,300,09045 Località Geremeas,</address>
	<currency code="EUR"/>
	<net amount= "647.96"/>
	<sell amount= "647.96"/>
	<roombasis meal="RB" breakfast="U"/>
	<deadline date="2023-08-27"/>
	<paymentdue date="2023-09-16"/>
	<details>
		<rooms>
			<room type="dbl"  >
				<date value="2023-09-15">
					<sell value="90.56"/>
				</date>
				<date value="2023-09-14">
					<sell value="90.56"/>
				</date>
				<date value="2023-09-13">
					<sell value="90.56"/>
				</date>
				<date value="2023-09-12">
					<sell value="90.56"/>
				</date>
				<date value="2023-09-11">
					<sell value="90.56"/>
				</date>
				<date value="2023-09-10">
					<sell value="90.56"/>
				</date>
				<date value="2023-09-09">
					<sell value="104.60"/>
				</date>
				<pax title="MR" initial="JESUS MANUEL" surname="FERNANDEZ MORANTE" leader="true"/>
				<pax title="MR" initial="JESUS MANUEL" surname="FERNANDEZ MORANTE"/>
			</room>
		</rooms>
		<configuration>
			<card ccnumber="4609011053753335" startvalidity="2023-09-16" endvalidity="2023-10-16" cvc="981" />
		</configuration>
	</details>
	<remarks>
		<remark code="PROMO42915" text=" Holiday Package -10%"/>
		<remark code="Hotel Il Monastero" text="Check-in from 03:00 pm to 08:30 pm.

MONASTERO CARD: (Mandatory supplement €5.00 per person per day from 3 years of age)
Includes: use of the swimming pool with hydromassage, fitness area, Wi-Fi in the common areas, availability of umbrellas for the beach (subject to availability) and beach towels (with deposit and changed every three days, further change on request with an extra supplement equal to €3.00), use of bicycles, ping-pong, table football, minigolf, discounted rates for use of the riding school, Oceanblue Diving center, excursions in dune buggies and for beach service (subject to availability) to be booked on site at the reception, in addition special rates for transfers by minibus and private car. Use of free luggage storage, large private parking (unattended) for cars, motorcycles and bicycles."/>
	</remarks>
</bookings>';
    }

}
