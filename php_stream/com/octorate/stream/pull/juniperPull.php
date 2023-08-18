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

class juniperPull extends AbstractPullStream {

    /**
     * @return array of PullReservation
     */
    public function pullReservations() {
        $this->utilFunc = new UtilFunc();
        $this->allRes = [];
        $this->resaArr = [];
        //        $this->url = ;
        if ($xmlStr = $this->getReservations()) {
            $this->parseReservations($xmlStr);
        }
        //        if ( $this->checkUnprocessedBooking() ) {
        //            foreach ( $this->pendingResaArr as $refer => $value ) {
        //                if ( !( array_key_exists( $refer, $this->resaArr ) ) ) {
        //                    $lastmodify = ( new \DateTime( $value[ 'lastmodify_time' ] ) )->format( 'Y-m-d H:i:s' );
        //                    if ( $value[ 'status' ] == 'CA' && $value[ 'xml' ] != '' ) {
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

        $regex = '/(<Reservation\s.*?<\/Reservation>)/is';

        while (preg_match($regex, $xmlStr, $match)) {
            $xmlStr = $this->utilFunc->after($match[0], $xmlStr);
            $tempFile = $match[1];

            $refer = $this->utilFunc->parseOneValue('Locator', $tempFile);
            $lastmodify = (new \DateTime($this->utilFunc->parseoneValue('ReservationDate', $tempFile)))->format('Y-m-d H:i:s');
            $status = $this->utilFunc->parseOneValue('Status', $tempFile);

            $propertyReference = $this->siteUser->hotel_id;
            $this->insertXml($refer, $status, $tempFile, $lastmodify, $propertyReference);

            if ($status == 'OK' || $status == 'PAG' || $status == 'PP' || $status == 'RQ' || $status == 'CON') {
                $this->allRes[] = $this->retrieveReservation($tempFile, $refer, $status, $lastmodify, false);
            } else {
                $this->allRes[] = $this->retrieveCancellation($refer, $lastmodify, false);
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
        $res->currency = $this->utilFunc->parseOneValue('Currency', $resultFile);

        if ($forceFlg) {
            $res->force = \TRUE;
        }

        $res->createDate = ( new \DateTime($lastmodify))->format(DATE_ATOM);
        $res->updateDate = ( new \DateTime('2000-01-01 00:00:00'))->format(DATE_ATOM);

        $res->creditCard->token = NULL;

        if (preg_match('/<Pax.*?<\/Pax>/is', $resultFile, $name_match)) {
            $res->guest->email = $this->choice('Email', $name_match[0]);
            $res->guest->phone = $this->choice('PhoneNumber', $name_match[0]);
            $firstName = $this->utilFunc->parseXMLValue('Name', $name_match[0]);
            $lastName = $this->utilFunc->parseXMLValue('Surname', $name_match[0]);
            $res->guest->firstName = substr($firstName, 0, 40);
            $res->guest->lastName = substr($lastName, 0, 40);
            $res->guest->address = $this->choice('Address', $name_match[0]);
            $res->guest->city = $this->choice('City', $name_match[0]);
            $res->guest->zip = $this->choice('PostalCode', $name_match[0]);
        }


        // Create room data
        if (preg_match('/(<HotelItem.*?<\/HotelItem>)/is', $resultFile, $roomsmatch)) {
            $tempFile = $roomsmatch[1];
            $checkIn = (new \DateTime($this->utilFunc->parseoneValue('Start', $resultFile)))->format('Y-m-d');
            $checkOut = (new \DateTime($this->utilFunc->parseoneValue('End', $resultFile)))->format('Y-m-d');
            $totalBuffer = $this->utilFunc->parseoneValue('Nett', $resultFile);

            if (preg_match_all('/(<HotelItem.*?<\/HotelItem>)/is', $resultFile, $roomMatches)) {
                $numofrooms = count($roomMatches);
                foreach ($roomMatches[0] as $roomXML) {


                    if (preg_match('/<Board\s+Code="\d+">.*?<\/Board>/is', $roomXML, $match)) {
                        $temprate = $match[0];
                        $rateNm = $this->utilFunc->parsexmlValue('Name', $temprate);
                        $rate_id = $this->utilFunc->parseOneValue('Board\s+Code', $roomXML);
                    }
                    if (preg_match('/<HotelRoom\s+Code="\d+"\s+Source="1">.*?<\/HotelRoom>/is', $roomXML, $match)) {
                        $temproom = $match[0];
                        $roomName = $this->utilFunc->parsexmlValue('Name', $temproom);
                        $roomId = $this->utilFunc->parseOneValue('HotelRoom\s+Code', $temproom);
                    } elseif (preg_match('/<HotelRoom\s+Code="\d+"\s+ExternalCode="\d+"\s+Source="1">.*?<\/HotelRoom>/is', $roomXML, $match)) {
                        $temproom = $match[0];
                        $roomName = $this->utilFunc->parsexmlValue('Name', $temproom);
                        $roomId = $this->utilFunc->parseOneValue('HotelRoom\s+Code', $temproom);
                    }
                    $room = new PullReservationRoom($roomId . ':' . $rate_id);
                    if($room){
                    $dateArr = $this->utilFunc->getDatesFromRange($checkIn, $checkOut);
                    $countDays = count($dateArr);

                    foreach ($dateArr as $date) {
                        $pricePerDay = $totalBuffer / $countDays;
                        $room->daily[] = new PullReservationDay($date, $pricePerDay, true);
                    }
                    if (preg_match('/<paxes\s(.*?)>/is', $resultFile, $childmatch)) {
                        $room->children = $this->utilFunc->parseOneValue('Children', $childmatch[1]);
                        $room->pax = $this->utilFunc->parseOneValue('Adults', $childmatch[1]) + $this->utilFunc->parseoneValue('Children', $childmatch[1]);
                    }
                    $room->total = round($totalBuffer, 2);
                    $room->taxIncluded = true;
                    $room->totalPaid = NULL;
                    $room->checkIn = $checkIn;
                    $room->checkOut = $checkOut;
                    $room->paidNotes = NULL;
                    //other data
                    $voucherJson = $this->createVoucherV2($res, $room);

                    $voucherJson['ReservationDate'] = $this->utilFunc->parseOneValue('reservationDate', $resultFile);
                    $voucherJson['ModificationDate'] = $this->utilFunc->parseOneValue('ModificationDate', $resultFile);
                    $voucherJson['TimeZone'] = $this->utilFunc->parseOneValue('TimeZone', $resultFile);
                    $voucherJson['HotelInfo Code'] = $this->utilFunc->parseOneValue('HotelInfo Code', $resultFile);
                    $voucherJson['JPCode'] = $this->utilFunc->parseOneValue('JPCode', $resultFile);
                    //get hotel info
                    $hotelinfo = $this->utilFunc->parsexmlValue('HotelInfo', $resultFile);
                    $voucherJson['HotelInfo Name'] = $this->utilFunc->parsexmlValue('Name', $hotelinfo);
                    $voucherJson['HotelCategory'] = $this->utilFunc->parsexmlValue('HotelCategory', $hotelinfo);
                    $voucherJson['Hotel Address'] = $this->utilFunc->parsexmlValue('Address', $hotelinfo);
                    //get child ages
                    $childinfo = $this->utilFunc->parsexmlValue('ChildrenAges', $hotelinfo);
                    $voucherJson['child Ages'] = $this->childage();
                    //hotel board code or name
                    $voucherJson['hotel Board Code'] = $this->utilFunc->parseoneValue('Board Code', $resultFile);
                    $voucherJson['Board Name '] = $this->utilFunc->parsexmlValue('Board', $resultFile);
                    //Supplier
                    if (preg_match('/<Supplier\s(.*?)>(.*?)<\/Supplier>/is', $resultFile, $suppmatch)) {
                        $voucherJson['Supplier Code '] = $this->utilFunc->parseoneValue('Code', $suppmatch[0]);
                        $voucherJson['Supplier IntCode '] = $this->utilFunc->parseoneValue('IntCode', $suppmatch[0]);
                        $voucherJson['Supplier Name '] = $this->utilFunc->parsexmlValue('Name', $suppmatch[0]);
                    }
                    //HotelRoom
                    if (preg_match('/<HotelRoom\s(.*?)>(.*?)<\/HotelRoom>/is', $resultFile, $hmatch)) {
                        $voucherJson['Hotel Code '] = $this->utilFunc->parseoneValue('Code', $hmatch[0]);
                        $voucherJson['Hotel Source '] = $this->utilFunc->parseoneValue('Source', $hmatch[0]);
                        $voucherJson['Hotel Name '] = $this->utilFunc->parsexmlValue('Name', $hmatch[0]);
                        $voucherJson['RoomCategory Type'] = $this->utilFunc->parseoneValue('RoomCategory Type', $hmatch[0]);
                        $voucherJson['RoomCategory'] = $this->utilFunc->parsexmlValue('RoomCategory', $hmatch[0]);
                    }
                    //HotelContracts
                    if (preg_match('/<HotelContracts>(.*?)<\/HotelContracts>/is', $resultFile, $hcmatch)) {
                        $voucherJson['HotelContract Code '] = $this->utilFunc->parseoneValue('Code', $hcmatch[0]);
                        $voucherJson['HotelContract Name '] = $this->utilFunc->parsexmlValue('Name', $hcmatch[0]);
                    }
                    //CancellationPolicy
                    if (preg_match('/<CancellationPolicy>(.*?)<\/CancellationPolicy>/is', $resultFile, $cmatch)) {
                        $voucherJson['Notes'] = $this->utilFunc->parsexmlValue('Text', $cmatch[0]);
                    }
                    $voucherJson['Price'] = $this->priceAmount($resultFile);
                    $voucherJson['taxes'] = $this->Taxes();

                    $room->json = $voucherJson;

                    $res->rooms[] = $room;
                }
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
    public function getReservations() {
        $d1 = new \DateTime();
        $d1->sub(new \DateInterval('P1D'));
        $d2 = new \DateTime();

        $result = '';
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
				<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns="http://www.juniper.es/webservice/2007/">
					<soapenv:Header/>
					<soapenv:Body>
						<ExtranetReadBooking xmlns="http://www.juniper.es/webservice/2007/">
							<ReadBookingRQ Version="1" Language="ES" IntCode="111" TimeStamp="' . date('Y-m-d') . '">
								<Login Password="' . $this->siteUser->sites_pass . '" Email="' . $this->siteUser->sites_user . '"/>
								<SearchItems>
									<ModificationDate From="' . $d1->format('Y-m-d') . '" To= "' . $d2->format('Y-m-d') . '"/>
									<Hotels>
										<Hotel Code="' . $this->siteUser->hotel_id . '"/>
									</Hotels>
								</SearchItems>
							</ReadBookingRQ>
						</ExtranetReadBooking>
					</soapenv:Body>
				</soapenv:Envelope>';

//      $result = $this->utilFunc->submitXmlPost( $this->url, $xml, [] );
        $result = $this->tempXml();
//        $result = $this->Cantempfile();

        $result = preg_replace('/\&lt\;/is', '<', $result);
        $result = preg_replace('/\&gt\;/is', '>', $result);
        $result = preg_replace('/\&quot\;/is', '"', $result);
        $this->insertResaLog($xml . '------' . $result);
        if (preg_match('/<ReadBookingRS.*?<\/ReadBookingRS>/is', $result)) {
            return $result;
        } elseif (preg_match('/<Warning/is', $result)) {
            return 0;
        } elseif (preg_match('/<Error/is', $result)) {
            return 0;
        } elseif (preg_match('/<\/ExtranetReadBookingResponse>/is', $result)) {
            return 0;
        }
    }

    /**
     * @return xml of temp. XML
     */
    public function tempXml() {
        return '<?xml version="1.0" encoding="UTF-8"?>
 <ReadBookingRS>
<Reservation Locator="591LZY" Channel="XmlU" Interface="XML" Status="PAG">
   <Holder>
      <RelPax IdPax="3" />
   </Holder>
   <Paxes Adults="2" Children="0">
      <Pax IdPax="1">
         <Name>MIGUEL ANGEL</Name>
         <Surname>LAGUNA CALVO</Surname>
         <Age>30</Age>
         <Nationality>ES</Nationality>
      </Pax>
      <Pax IdPax="2">
         <Name>PaxName2</Name>
         <Surname>PaxSurname2</Surname>
         <Age>30</Age>
      </Pax>
      <Pax IdPax="3">
         <Name>MIGUEL ANGEL</Name>
         <Surname>LAGUNA CALVO</Surname>
         <Nationality>ES</Nationality>
      </Pax>
   </Paxes>
   <Agency>
      <ExternalLocator>WM72716929</ExternalLocator>
      <Market>PACKAGE</Market>
   </Agency>
   <Items>
      <HotelItem Start="2023-09-24" End="2023-10-01" ItemId="25744148" Status="OK" ReservationDate="2023-07-05T22:20:22.44" ModificationDate="2023-07-05T22:20:23.557" DirectPayment="false" LuckyDipHotel="false" GroupBooking="false" SpecialPriceBooking="false" TimeZone="02:00">
         <HotelInfo Code="787" JPCode="JP280727">
            <Name>Rentalmar Los Peces</Name>
            <HotelCategory Type="Apt">Aptos turisticos</HotelCategory>
            <Address>C/ Navarra, 2</Address>
            <ChildrenAges>
               <ChildrenAgeFrom>0</ChildrenAgeFrom>
               <ChildrenAgeTo>11</ChildrenAgeTo>
            </ChildrenAges>
         </HotelInfo>
         <Board Code="3">
            <Name>Solo Alojamiento</Name>
         </Board>
         <Supplier Code="HBE" IntCode="2278">
            <Name>BRISASOL - APARTAMENTOS LOS PECES - SA</Name>
         </Supplier>
         <HotelRooms>
            <HotelRoom Code="85" Source="1">
               <Name>APARTAMENTO 1 DORMITORIO CON TERRAZA 2 PAX</Name>
               <RoomCategory Type="AT1">APARTAMENTO 1 DORMITORIO CON TERRAZA</RoomCategory>
               <RelPaxes>
                  <RelPax IdPax="1" />
                  <RelPax IdPax="2" />
               </RelPaxes>
            </HotelRoom>
         </HotelRooms>
         <HotelContracts>
            <HotelContract Code="49617">
               <Name>EXT OCTORATE LONG STAY</Name>
            </HotelContract>
         </HotelContracts>
         <CancellationPolicy>
            <Text> Cancelando hasta 5 días antes del viaje: 0 €  Cancelando desde 3 días hasta 4 días antes del viaje: primera noche  Cancelando desde 0 días hasta 2 días antes del viaje  para estancias desde  2 noche(s): 2 noche(s)  Cancelando no show  para estancias desde  2 noche(s): 2 noche(s)</Text>
         </CancellationPolicy>
         <Price Currency="EUR">
            <TotalFixAmounts Gross="224" Nett="224">
               <Service Amount="224" />
               <ServiceTaxes Included="true" Amount="20.36" />
               <Commissions Included="true" Amount="0" />
            </TotalFixAmounts>
            <Breakdown>
               <Concepts>
                  <Concept Type="BAS" Name="Base">
                     <Items>
                        <Item Amount="32" Date="2023-09-24" Quantity="1" Days="7" Source="1" />
                        <Item Amount="0" Date="2023-09-24" Quantity="2" Days="7" PaxType="ADT" Source="1" />
                     </Items>
                  </Concept>
               </Concepts>
               <Taxes>
                  <Tax Name="Soportado al 10%" Value="10" IsFix="false" ByNight="false" Commissionable="true" Included="true">
                     <Total Base="203.64" Amount="20.36" />
                  </Tax>
               </Taxes>
            </Breakdown>
         </Price>
      </HotelItem>
   </Items>
 </Reservation>
 

</ReadBookingRS>';
    }

    /**
     * @return XML of cancel Temp. XML
     */
    public function Cantempfile() {
        return '<?xml version="1.0" encoding="UTF-8"?>
<ReadBookingRS>
<Reservation Locator="XW746M" Channel="XmlU" Interface="XML" Status="CAN">
   <Holder>
      <RelPax IdPax="2" />
   </Holder>
   <Paxes Adults="1" Children="0">
      <Pax IdPax="1">
         <Name>MARGOT</Name>
         <Surname>HINCAPIE DE ECHEVERRI</Surname>
         <Age>30</Age>
      </Pax>
      <Pax IdPax="2">
         <Name>MARGOT</Name>
         <Surname>HINCAPIE DE ECHEVERRI</Surname>
         <Country>ES</Country>
         <Nationality>ES</Nationality>
      </Pax>
   </Paxes>
   <Agency>
      <ExternalLocator>1BF5698</ExternalLocator>
      <Market>PACKAGE</Market>
   </Agency>
   <Items>
      <HotelItem Start="2023-08-07" End="2023-08-27" ItemId="25747229" Status="CA" ReservationDate="2023-07-06T10:38:25.053" ModificationDate="2023-07-06T10:55:26.833" DirectPayment="false" LuckyDipHotel="false" GroupBooking="false" SpecialPriceBooking="false" TimeZone="02:00">
         <HotelInfo Code="2411" JPCode="JP780495">
            <Name>Apartamentos Zahara Rentalmar</Name>
            <HotelCategory Type="Apt">Alojamiento de Uso Turistico</HotelCategory>
            <Address>Carrer De Navarra, 3</Address>
            <ChildrenAges>
               <ChildrenAgeFrom>0</ChildrenAgeFrom>
               <ChildrenAgeTo>11</ChildrenAgeTo>
            </ChildrenAges>
         </HotelInfo>
         <Board Code="3">
            <Name>Solo Alojamiento</Name>
         </Board>
         <Supplier Code="HBE" IntCode="2277">
            <Name>BRISASOL - APARTAMENTOS ZAHARA/AZAHAR - SA</Name>
         </Supplier>
         <HotelRooms>
            <HotelRoom Code="109" ExternalCode="879" Source="1">
               <Name>APARTAMENTO 1 DORMITORIO CON BALCON 2/4/APARTMENT 1 BEDROOM WITH BALCONY 2/4</Name>
               <RoomCategory Type="AQJ">APARTAMENTO 1 DORMITORIO CON BALCON</RoomCategory>
               <RelPaxes>
                  <RelPax IdPax="1" />
               </RelPaxes>
            </HotelRoom>
         </HotelRooms>
         <HotelContracts>
            <HotelContract Code="49522">
               <Name>EXT OCTORATE ZAHARA LONG STAY</Name>
            </HotelContract>
         </HotelContracts>
         <CancellationPolicy>
            <Text> Cancelling up to 5 days (included) before the trip: 0 €  Cancelling up to 3 days to 4 days (included) before the trip: first night  Cancelling up to 0 days to 2 days (included) before the trip  for stays from   2 nights: 2 nights  Cancelling no show  for stays from   2 nights: 2 nights</Text>
         </CancellationPolicy>
         <Price Currency="EUR">
            <TotalFixAmounts Gross="0" Nett="0">
               <Commissions Included="true" Amount="0" />
            </TotalFixAmounts>
            <Breakdown>
               <Concepts>
                  <Concept Type="BAS" Name="Base">
                     <Items>
                        <Item Amount="129" Date="2023-08-07" Quantity="1" Days="4" Source="1" />
                        <Item Amount="142" Date="2023-08-11" Quantity="1" Days="2" Source="1" />
                        <Item Amount="133" Date="2023-08-13" Quantity="1" Days="5" Source="1" />
                        <Item Amount="142" Date="2023-08-18" Quantity="1" Days="2" Source="1" />
                        <Item Amount="109" Date="2023-08-20" Quantity="1" Days="5" Source="1" />
                        <Item Amount="111" Date="2023-08-25" Quantity="1" Days="2" Source="1" />
                        <Item Amount="0" Date="2023-08-07" Quantity="1" Days="4" PaxType="ADT" Source="1" />
                        <Item Amount="0" Date="2023-08-11" Quantity="1" Days="2" PaxType="ADT" Source="1" />
                        <Item Amount="0" Date="2023-08-13" Quantity="1" Days="5" PaxType="ADT" Source="1" />
                        <Item Amount="0" Date="2023-08-18" Quantity="1" Days="2" PaxType="ADT" Source="1" />
                        <Item Amount="0" Date="2023-08-20" Quantity="1" Days="5" PaxType="ADT" Source="1" />
                        <Item Amount="0" Date="2023-08-25" Quantity="1" Days="2" PaxType="ADT" Source="1" />
                     </Items>
                  </Concept>
               </Concepts>
               <Taxes>
                  <Tax Name="Soportado al 10%" Value="10" IsFix="false" ByNight="false" Commissionable="true" Included="true">
                     <Total Base="2287.27" Amount="228.73" />
                  </Tax>
               </Taxes>
            </Breakdown>
         </Price>
      </HotelItem>
   </Items>
</Reservation>
</ReadBookingRS>';
    }

    /**
     * @return value  of choice
     */
    public function choice($lable, $xml) {
        $choice = $this->utilFunc->parsexmlValue($lable, $xml);
        if ($choice) {
            return $choice;
        } else {
            return NULL;
        }
    }

    /**
     * @return table of childAge
     */
    public function priceAmount($tempFile) {
        if (preg_match('/<Price.*?<\/Price>/is', $tempFile, $match)) {

            $rmrate = $match[0];

            $total = 0;
            $details = '<table border="1" width="50%">';
            $details .= '<tr>';
            $details .= '<td align="center">EffectiveDate</td>';
            $details .= '<td align="center">Quantity</td>';
            $details .= '<td align="center">UnitPrice</td>';
            $details .= '</tr>';
            $cloop = 1;
            while (preg_match('/<Item\s+Amount.*?\/>/is', $rmrate, $match1)) {
                $rmrate = $this->utilFunc->after($match1[0], $rmrate);
                $dayFile = $match1[0];
                $EffectiveDate = $this->utilFunc->parseoneValue('Date', $dayFile);
                if ($cloop == 1) {
                    $checkin = $EffectiveDate;
                }
                $daytotal = $this->utilFunc->parseoneValue('Item\s+Amount', $dayFile);
                $quantity = $this->utilFunc->parseoneValue('Quantity', $dayFile);

                $details .= '<tr>';
                $details .= '<td align="center">' . $EffectiveDate . '</td>';
                $details .= '<td align="center">' . $quantity . '</td>';
                $details .= '<td align="center">' . $daytotal . '</td>';
                $details .= '</tr>';
                $total += $daytotal * $quantity;
                $cloop++;
            }
            $details .= '</table>';
        }
        return $details;
    }

    /**
     * @return table of childAge
     */
    public function Taxes() {
        $xmlStr = $this->getReservations();
        preg_match_all('/<Taxes>(.*?)<\/Taxes>/is', $xmlStr, $tmatch);

        $table = '<table border="2px" >';
        $table .= '<tr><th>Name</th><th>Value</th><th>Isfix</th><th>Bynight</th><th>Commissionable</th><th>Included</th></tr>';

        foreach ($tmatch[1] as $taxXML) {

            $name = $this->utilFunc->parseoneValue('Name', $taxXML);
            $Value = $this->utilFunc->parseoneValue('Value', $taxXML);
            $IsFix = $this->utilFunc->parseoneValue('IsFix', $taxXML);
            $ByNight = $this->utilFunc->parseoneValue('ByNight', $taxXML);
            $Commissionable = $this->utilFunc->parseoneValue('Commissionable', $taxXML);
            $Included = $this->utilFunc->parseoneValue('Included', $taxXML);

            $table .= '<tr>';
            $table .= '<td>' . $name . '</td>';
            $table .= '<td>' . $Value . '</td>';
            $table .= '<td>' . $IsFix . '</td>';
            $table .= '<td>' . $ByNight . '</td>';
            $table .= '<td>' . $Commissionable . '</td>';
            $table .= '<td>' . $Included . '</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    /**
     * @return table of childAge
     */
    public function childage() {


        $table = '<table border="2px" >';
        $table .= '<tr><th>ChildrenAgeFrom</th><th>ChildrenAgeTo</th></tr>';
        $xmlStr = $this->getReservations();
        preg_match_all('/<ChildrenAges>(.*?)<\/ChildrenAges>/is', $xmlStr, $childmatch);

        foreach ($childmatch[1] as $childXML) {

            $agefrom = $this->utilFunc->parsexmlValue('ChildrenAgeFrom', $childXML);
            $ageto = $this->utilFunc->parsexmlValue('ChildrenAgeTo', $childXML);

            $table .= '<tr>';
            $table .= '<td>' . $agefrom . '</td>';
            $table .= '<td>' . $ageto . '</td>';

            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

}
?>

