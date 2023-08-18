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

class LastminutePull extends AbstractPullStream {

    /**
     * @return array of PullReservation
     */
    public function pullReservations() {
        $this->utilFunc = new UtilFunc();
        $this->allRes = [];
        $this->resaArr = [];
        $this->confirmationReq = Array();
        $this->indexNumber = '';
        $this->ResvalueId = '';

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

        $regex = '/<HotelReservation\s.*?<\/HotelReservation>/is';

        while (preg_match($regex, $xmlStr, $match)) {


            $xmlStr = $this->utilFunc->after($match[0], $xmlStr);

            $tempFile = $match[0];

            $refermatch = $this->utilFunc->parseXMLValue('HotelReservationIDs', $tempFile);

            if ($refermatch) {
                $referBuffer = $this->utilFunc->parseoneValue('ResID_Value', $refermatch);
                $refer = $referBuffer . '_' . $this->utilFunc->parseoneValue('IndexNumber', $tempFile);
            }
            $lastmodify = (new \DateTime($this->utilFunc->parseoneValue('LastModifyDateTime', $tempFile)))->format('Y-m-d H:i:s');
            $status = $this->utilFunc->parseOneValue('ResStatus', $tempFile);

            $propertyReference = $this->siteUser->hotel_id;
            $insertId = $this->insertXml($refer, $status, $tempFile, $lastmodify, $propertyReference);

            if ($status == 'Book') {
                $this->allRes[] = $this->retrieveReservation($tempFile, $refer, $status, $lastmodify, false, $insertId);
            } else {
                $this->allRes[] = $this->retrieveCancellation($refer, $status, $lastmodify, false, $insertId);
            }
        }
        if (!empty($this->confirmationReq)) {
            $this->sendConfirmation();
        }
    }

    /**
     * @return array of Retrieve Reservation
     */
    public function retrieveReservation($resultFile, $refer, $status, $lastmodify, $forceFlg, $insertId) {
        // Success, parse response and create reservations
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CONFIRMED;
        $languageLen = strlen($this->utilFunc->parseOneValue('Language', $resultFile));
        if ($languageLen != 2) {
            $language = $this->utilFunc->parseOneValue('Language', $resultFile);
            $lang = strtoupper(preg_replace('/-.*/is', ' ', $language));
            $res->language = $lang;
        } else {
            $res->language = $this->utilFunc->parseOneValue('Language', $resultFile);
        }
        $res->currency = $this->utilFunc->parseOneValue('CurrencyCode', $resultFile);

        if ($forceFlg) {
            $res->force = \TRUE;
        }

        $res->createDate = ( new \DateTime($lastmodify))->format(DATE_ATOM);
        $res->updateDate = ( new \DateTime('2000-01-01 00:00:00'))->format(DATE_ATOM);
        $res->creditCard->token = NULL;
        //email match //
        $email = $this->utilFunc->parseXMLValue('Email', $resultFile);
        if ($email) {
            $res->guest->email = $email;
        } else {
            $res->guest->email = NULL;
        }
        //phone match
        $phonematch = $this->utilFunc->parseXMLValue('Telephone', $resultFile);
        if ($phonematch) {
            $res->guest->phone = $this->parseTelephone($phonematch);
        } else {
            $res->guest->phone = NULL;
        }
        //guest Name match//
        $guestname = $this->utilFunc->parseXMLValue('PersonName', $resultFile);

        if ($guestname) {
            $firstName = $this->utilFunc->parseXMLValue('GivenName', $guestname);
            $lastName = $this->utilFunc->parseXMLValue('Surname', $guestname);
            $res->guest->firstName = substr($firstName, 0, 40);
            $res->guest->lastName = substr($lastName, 0, 40);
        }
        $res->guest->address = NULL;
        $res->guest->city = NULL;
        $res->guest->zip = NULL;

        // Create room data
        if (preg_match('/<RoomStays>(.*?)<\/RoomStays>/is', $resultFile, $roomsmatch)) {
            $tempFile = $roomsmatch[1];
            $checkIn = (new \DateTime($this->utilFunc->parseoneValue('Start', $resultFile)))->format('Y-m-d');
            $checkOut = (new \DateTime($this->utilFunc->parseoneValue('End', $resultFile)))->format('Y-m-d');
            //total amount
            if (preg_match('/.*<Total(.*?)>/is', $resultFile, $totalmatch)) {
                $totalBuffer = $this->utilFunc->parseoneValue('AmountAfterTax', $totalmatch[1]);
            }

            if (preg_match_all('/<RoomStay\s.*?<\/RoomStay>/is', $resultFile, $roomMatches)) {
                $numofrooms = count($roomMatches);

                foreach ($roomMatches[0] as $roomXML) {
//                    room type code//
                    $RoomTypeCode = $this->utilFunc->parseoneValue('RoomTypeCode', $tempFile);
//                    rate plan code//
                    $RatePlanType = $this->utilFunc->parseoneValue('RatePlanCode', $tempFile);
                    if ($RoomTypeCode && $RatePlanType) {
                        $room = new PullReservationRoom($RoomTypeCode . ':' . $RatePlanType);
                    }
                    $this->indexNumber = $this->utilFunc->parseoneValue('IndexNumber', $resultFile);
                    $this->ResvalueId = $this->utilFunc->parseoneValue('ResID_Value', $resultFile);

                    if (!empty($insertId)) {
                        $this->addConfirmationReq($insertId, $this->indexNumber, $this->ResvalueId);
                    }


                    if ($room) {
                        $dateArr = $this->utilFunc->getDatesFromRange($checkIn, $checkOut);
                        $countDays = count($dateArr);
                        foreach ($dateArr as $date) {
                            $pricePerDay = $totalBuffer / $countDays;
                            $room->daily[] = new PullReservationDay($date, $pricePerDay, true);
                        }

                        $room->children = NULL;
                        $room->pax = $this->parsePax($roomXML);
                        $room->total = round($totalBuffer, 2);
                        $room->taxIncluded = true;
                        $room->totalPaid = NULL;
                        $room->checkIn = $checkIn;
                        $room->checkOut = $checkOut;
                        $room->paidNotes = NULL;

                        //other data
                        $voucherJson = $this->createVoucherV2($res, $room);
                        //booking compant name//
                        if (preg_match('/<BookingChannel\s(.*?)>(.*?)<\/BookingChannel>/is', $resultFile, $booking)) {
                            $voucherJson['BookingChannel Type'] = $this->utilFunc->parseOneValue('Type', $booking[0]);
                            $voucherJson['CompanyName '] = $this->utilFunc->parseOneValue('CompanyShortName', $booking[0]);
                        }
                        $voucherJson['RoomStay IndexNumber '] = $this->indexNumber;
                        $voucherJson['RoomType NumberOfUnits '] = $this->utilFunc->parseOneValue('NumberOfUnits', $resultFile);
                        $voucherJson['RoomDescription Name '] = $this->utilFunc->parseOneValue('RoomDescription Name', $resultFile);
//                       rate plans//
                        if (preg_match('/<RatePlans>(.*?)<\/RatePlans>/is', $resultFile, $rateplans)) {

                            $voucherJson['RatePlanType'] = $this->utilFunc->parseOneValue('RatePlanType', $rateplans[0]);
                            $voucherJson['PriceViewableInd'] = $this->utilFunc->parseOneValue('PriceViewableInd', $rateplans[0]);
                            $voucherJson['PrepaidIndicator'] = $this->utilFunc->parseOneValue('PrepaidIndicator', $rateplans[0]);
                        }
                        //room rates//
                        if (preg_match('/<RoomRates>(.*?)<\/RoomRates>/is', $resultFile, $roomrate)) {
                            $voucherJson['RoomRate InvBlockCode'] = $this->utilFunc->parseOneValue('InvBlockCode', $roomrate[0]);
                            $voucherJson['Rate EffectiveDate'] = $this->utilFunc->parseOneValue('Rate EffectiveDate', $roomrate[0]);
                            $voucherJson['ExpireDateExclusiveIndicator'] = $this->utilFunc->parseOneValue('ExpireDateExclusiveIndicator', $roomrate[0]);
                            $voucherJson['RoomPricingType'] = $this->utilFunc->parseOneValue('RoomPricingType', $roomrate[0]);
                            $voucherJson['Base AmountBeforeTax'] = $this->utilFunc->parseOneValue('AmountBeforeTax', $roomrate[0]);
                            $voucherJson['Total AmountBeforeTax'] = $this->utilFunc->parseOneValue('Total AmountBeforeTax', $roomrate[0]);
                            $voucherJson['AmountAfterTax'] = $this->utilFunc->parseOneValue('AmountAfterTax', $roomrate[0]);
                        }
                        //CancelPenalties//
                        if (preg_match('/<CancelPenalties\s(.*?)>(.*?)<\/CancelPenalties>/is', $resultFile, $can)) {
                            $voucherJson['CancelPolicyIndicator'] = $this->utilFunc->parseOneValue('CancelPolicyIndicator', $can[0]);
                            $voucherJson['Deadline OffsetTimeUnit'] = $this->utilFunc->parseOneValue('Deadline OffsetTimeUnit', $can[0]);
                            $voucherJson['OffsetUnitMultiplier'] = $this->utilFunc->parseOneValue('OffsetUnitMultiplier', $can[0]);
                            $voucherJson['AmountPercent NmbrOfNights'] = $this->utilFunc->parseOneValue('AmountPercent NmbrOfNights', $can[0]);
                        }
                        //guest data//
                        if (preg_match('/<ResGuests>(.*?)<\/ResGuests>/is', $resultFile, $guest)) {
                            $voucherJson['ResGuest ResGuestRPH'] = $this->utilFunc->parseOneValue('ResGuest ResGuestRPH', $guest[0]);
                            $voucherJson['AgeQualifyingCode'] = $this->utilFunc->parseOneValue('AgeQualifyingCode', $guest[0]);
                            $voucherJson['guest ProfileType'] = $this->utilFunc->parseOneValue('ProfileType', $guest[0]);
                        }
                        //TimeSpan //
                        $voucherJson['TimeSpan Duration'] = $this->utilFunc->parseOneValue('Duration', $resultFile);
                        $voucherJson['PaymentTransactionTypeCode'] = $this->utilFunc->parseOneValue('PaymentTransactionTypeCode', $resultFile);
                        $voucherJson['NonSmoking'] = $this->utilFunc->parseOneValue('NonSmoking', $resultFile);
                        $voucherJson['Rate Table'] = $this->rateTable($resultFile);

                        $voucherJson['HotelReservationIDs'] = $this->HotelReservationIDs($resultFile);
                        if (preg_match_all('/<Profiles>(.*?)<\/Profiles>/is', $resultFile, $rFile)) {

                            $voucherJson['ProfileInfo UniqueID Type'] = $this->utilFunc->parseOneValue('Type', $rFile[0][1]);
                            $voucherJson['ProfileInfo UniqueID ID'] = $this->utilFunc->parseOneValue('ID', $rFile[0][1]);
                            $voucherJson[' ProfileInfo UniqueID  ID_Context'] = $this->utilFunc->parseOneValue('ID_Context', $rFile[0][1]);
                            $voucherJson['CompanyInfo CompanyShortName'] = $this->utilFunc->parseOneValue('CompanyShortName', $rFile[0][1]);
                        }
                        $voucherJson['Number of rooms '] = $numofrooms;

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
    public function retrieveCancellation($refer, $sts, $lastmodify, $forceFlg, $insertId) {
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CANCELLED;
        if ($forceFlg) {
            $res->force = \TRUE;
        }
        $res->updateDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
        $res->createDate = NULL;

        if (!empty($insertId)) {
            $this->addConfirmationReq($insertId, $refer, $sts);
        }

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
        $echoToken = "SUPPLIER" . $this->siteUser->hotel_id . time();

        $result = '';
        $xml = '<SOAP-ENV:Envelope xmlns:SOAP-ENV = "http://schemas.xmlsoap.org/soap/envelope/" xmlns:eb = "http://www.ebxml.org/namespaces/messageHeader" xmlns:xlink = "http://www.w3.org/1999/xlink" xmlns:xsd = "http://www.w3.org/1999/XMLSchema">
					<SOAP-ENV:Header></SOAP-ENV:Header>
					<SOAP-ENV:Body>
						<HotelResRetrieveRQ xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://webservices.lastminute.com/lmnXML/2003/05"  EchoToken="' . $echoToken . '" TimeStamp="2005-02-17T10:00:01.104-06:00" Target="Production" Version="1.000" PrimaryLangID="en-us" UserName="' . $this->siteUser->sites_user . '">
						   <RetrieveCriteria>
							  <HotelProperties>
									<HotelProperty HotelCode="' . $this->siteUser->hotel_id . '" HotelCodeContext="Global Hotels"/>
							  </HotelProperties>
							  <BookingDate Start="' . $d1->format('Y-m-d') . '" End="' . $d2->format('Y-m-d') . '"/>
						   </RetrieveCriteria>
						</HotelResRetrieveRQ>
					</SOAP-ENV:Body>
				</SOAP-ENV:Envelope>';

//      $result = $this->utilFunc->submitXmlPost( 'http://hotelsupplierapi.lastminute.com/retrieve/', $xml, [] );
        $result = $this->tempXml();
        if (preg_match('/<PaymentCard/', $result)) {

            $result = preg_replace('/<CardHolderName.*?<\/CardHolderName>/is', '<CardHolderName></CardHolderName>', $result);
            $result = preg_replace('/<PaymentCard.*?<\/PaymentCard>/is', '<PaymentCard></PaymentCard>', $result);
        }




        if (preg_match('/shorttext\W+No\s*bookings\s*found\s*for\s*Properties/', $result)) {
            return 0;
        } else {
            $result = preg_replace('/<blockquote>/is', '', $result);
            $result = preg_replace('/<\/blockquote>/is', '', $result);

            return $result;
        }
    }

    /**
     * @return Confirmation XML of get All Bookings
     */
    public function sendConfirmation() {
        $echoToken = "SUPPLIER" . $this->siteUser->hotel_id . time();

        $xml = '';
        $xml .= '<SOAP-ENV:Envelope xmlns:SOAP-ENV = "http://schemas.xmlsoap.org/soap/envelope/" xmlns:eb = "http://www.ebxml.org/namespaces/messageHeader" xmlns:xlink = "http://www.w3.org/1999/xlink" xmlns:xsd = "http://www.w3.org/1999/XMLSchema">
	            <SOAP-ENV:Header></SOAP-ENV:Header>
		      <SOAP-ENV:Body>
		 <HotelConfirmationNotifRQ xmlns = "http://webservices.lastminute.com/lmnXML/2003/05" EchoToken = "' . $echoToken . '" TimeStamp = "2005-02-17T10:00:01Z" Target = "Production" Version = "1.000" PrimaryLangID = "en-us">
		  <HotelReservations>' . "\n";

        foreach ($this->confirmationReq as $insertId => $request) {
            $xml .= $request;
        }

        $xml .= '</HotelReservations>
		 </HotelConfirmationNotifRQ>
		 </SOAP-ENV:Body>
		</SOAP-ENV:Envelope>';
        $finalxml = $xml;

      
        if ($this->test) {
            $response = '';
        } else {
            $response = $this->utilFunc->submitXmlPost('http://hotelsupplierapi.lastminute.com/confirmation', $finalxml, array());

            $this->insertConfirmationXml($insertId, $finalxml, $response);
        }
        if ($this->test) {
            echo "ConfirmXML=>$finalxml\nConfirmResp =>$response\n";
        }
    }

    /**
     * @return array
     */
    public function addConfirmationReq($insertId, $IndexNumber, $ResID_Value) {

        if (array_key_exists($insertId, $this->confirmationReq)) {


            $this->confirmationReq[$insertId] .= '<HotelReservation RoomStayReservation = "true">
										<RoomStays>
												<RoomStay RoomStayStatus = "Cancel" IndexNumber = "' . $IndexNumber . '">
													   <Reference Type = "15" ID = "' . $IndexNumber . '"/>
												</RoomStay>
										</RoomStays>
										<ResGlobalInfo>
												<HotelReservationIDs>
														<HotelReservationID ResID_Type = "14" ResID_Value = "' . $ResID_Value . '" ResID_Source = "GH"/>
												</HotelReservationIDs>
										</ResGlobalInfo>
								</HotelReservation>' . "\n";
        } else {

            $this->confirmationReq[$insertId] = ' <HotelReservation RoomStayReservation = "true">
										<RoomStays>
												<RoomStay RoomStayStatus = "Book" IndexNumber = "' . $IndexNumber . '">
													   <Reference Type = "40" ID = "' . $IndexNumber . '"/>
												</RoomStay>
						
												</RoomStays>
										<ResGlobalInfo>
												<HotelReservationIDs>
														<HotelReservationID ResID_Type = "14" ResID_Value = "' . $ResID_Value . '" ResID_Source = "GH"/>
												</HotelReservationIDs>
										</ResGlobalInfo>
								</HotelReservation>' . "\n";
        }


//        }
    }

    /**
     * @return pax count number
     */
    public function parsePax($resultFile) {
        $totalCount = 0;
        $flag1 = 0;
        while (preg_match('/<GuestCount\s.*?\/>/is', $resultFile, $match)) {

            $tempFile = $match[0];
            $flag1 = 1;
            $resultFile = $this->utilFunc->after($match[0], $resultFile);
            $Count = $this->utilFunc->parseoneValue('Count', $tempFile);
            $totalCount += $Count;
        }
        if ($flag1 == '0') {
            while (preg_match('/<GuestCount(.*?)<\/GuestCount>/is', $resultFile, $match)) {
                $tempFile = $match[1];
                $resultFile = $this->utilFunc->after($match[0], $resultFile);
                $Count = $this->utilFunc->parseoneValue('Count', $tempFile);
                $totalCount += $Count;
            }
        }
        return $totalCount;
    }

    /**
     * @return phone number
     */
    function parseTelephone($tempfile) {
        $name = NULL;
        if ($tempfile != '' && $tempfile != NULL) {
            $CountryAccessCode = $this->utilFunc->parseoneValue('CountryAccessCode', $tempfile);
            $AreaCityCode = $this->utilFunc->parseoneValue('AreaCityCode', $tempfile);
            $PhoneNumber = $this->utilFunc->parseoneValue('PhoneNumber', $tempfile);

            $name = '';
            if ($CountryAccessCode) {
                $name .= $CountryAccessCode . ' ';
            }
            if ($AreaCityCode) {
                $name .= $AreaCityCode . ' ';
            }
            if ($PhoneNumber) {
                $name .= $PhoneNumber . ' ';
            }
            $name = preg_replace('/\s+$/is', '', $name);
        }
        return $name;
    }

    public function rateTable($tempFile) {

        $details = '<table border="1" width="50%">';
        $details .= '<tr>';
        $details .= '<td align="center">Start</td>';
        $details .= '<td align="center">End</td>';
        $details .= '<td align="center">Price</td>';
        $details .= '<td align="center">Currency</td>';
        $details .= '<td align="center">RoomPricingType</td>';
        $details .= '</tr>';

        while (preg_match('/<Rate\s.*?\/Rate>/is', $tempFile, $match1)) {

            $dayFile = $match1[0];
            $tempFile = $this->utilFunc->after($match1[0], $tempFile);

            $EffectiveDate = $this->utilFunc->parseoneValue('EffectiveDate', $dayFile);
            $ExpireDate = $this->utilFunc->parseoneValue('ExpireDate', $dayFile);
            $RoomPricingType = $this->utilFunc->parseoneValue('RoomPricingType', $dayFile);
            $CurrencyCode = $this->utilFunc->parseoneValue('CurrencyCode', $dayFile);
            $daytotal = $this->parseTotalValue('Total', $dayFile);

            $details .= '<tr>';
            $details .= '<td align="center">' . $EffectiveDate . '</td>';
            $details .= '<td align="center">' . $ExpireDate . '</td>';

            $details .= '<td align="center">' . $daytotal . '</td>';
            $details .= '<td align="center">' . $CurrencyCode . '</td>';
            $details .= '<td align="center">' . $RoomPricingType . '</td>';
            $details .= '</tr>';
        }
        $details .= '</table>';
        return $details;
    }

    public function parseTotalValue($label, $resultFile) {
        $total = '';

        if (preg_match('/.*<Total(.*?)>/is', $resultFile, $match1)) {

            $total = $this->utilFunc->parseoneValue('AmountAfterTax', $match1[1]);
        }
        return $total;
    }

    public function HotelReservationIDs($tempFile) {



        $table = '<table border="1" width="50%">';
        $table .= '<tr><th>ResID_Source</th><th>ResID_Type</th><th>ResID_Value</th></tr>';

        while (preg_match('/<HotelReservationIDs>(.*?)<\/HotelReservationIDs>/is', $tempFile, $match1)) {
            $File = $match1[0];
            $tempFile = $this->utilFunc->after($match1[0], $tempFile);

            $idS = $this->utilFunc->parseOneValue('ResID_Source', $File);
            $idT = $this->utilFunc->parseOneValue('ResID_Type', $File);
            $idV = $this->utilFunc->parseOneValue('ResID_Value', $File);

            $table .= '<tr>';
            $table .= '<td>' . $idS . '</td>';
            $table .= '<td>' . $idT . '</td>';
            $table .= '<td>' . $idV . '</td>';

            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    /**
     * @return xml of temp. XML
     */
    public function tempXml() {
        return '<?xml version="1.0" encoding="UTF-8"?>
</HotelReservation>
<HotelReservation RoomStayReservation="true" CreateDateTime="2023-07-08T12:47:14Z" LastModifyDateTime="2023-07-08T12:47:14Z" ResStatus="Book">
   <POS>
      <Source>
         <BookingChannel Type="288">
            <CompanyName CompanyShortName="lastminute.com" />
         </BookingChannel>
      </Source>
   </POS>
   <RoomStays>
      <RoomStay RoomStayStatus="Book" IndexNumber="21766780">
         <RoomTypes>
            <RoomType NumberOfUnits="1" RoomTypeCode="SUPSGL" NonSmoking="true">
               <RoomDescription Name="Superior Single Room. Room Only." Language="en-us" />
            </RoomType>
         </RoomTypes>
         <RatePlans>
            <RatePlan RatePlanCode="PRONR" RatePlanType="11" PriceViewableInd="false" PrepaidIndicator="true" />
         </RatePlans>
         <RoomRates>
            <RoomRate InvBlockCode="SUPSGLPRONR">
               <Rates>
                  <Rate EffectiveDate="2023-07-24" ExpireDate="" ExpireDateExclusiveIndicator="true" RoomPricingType="Per night">
                     <Base AmountBeforeTax="80.82" CurrencyCode="EUR" />
                     <Total AmountBeforeTax="80.82" AmountAfterTax="91.18" CurrencyCode="EUR" />
                  </Rate>
                  <Rate EffectiveDate="2023-07-25" ExpireDate="" ExpireDateExclusiveIndicator="true" RoomPricingType="Per night">
                     <Base AmountBeforeTax="80.82" CurrencyCode="EUR" />
                     <Total AmountBeforeTax="80.82" AmountAfterTax="91.18" CurrencyCode="EUR" />
                  </Rate>
               </Rates>
            </RoomRate>
         </RoomRates>
         <GuestCounts>
            <GuestCount AgeQualifyingCode="10" Count="1" ResGuestRPH="1" />
         </GuestCounts>
         <CancelPenalties CancelPolicyIndicator="true">
            <CancelPenalty>
               <Deadline OffsetTimeUnit="Day" OffsetUnitMultiplier="550" />
               <AmountPercent NmbrOfNights="2" />
            </CancelPenalty>
         </CancelPenalties>
         <Total AmountBeforeTax="161.64" AmountAfterTax="182.36" CurrencyCode="EUR">
            <Taxes>
               <Tax Amount="20.72" CurrencyCode="EUR" />
            </Taxes>
         </Total>
         <TPA_Extensions />
      </RoomStay>
   </RoomStays>
   <ResGuests>
      <ResGuest ResGuestRPH="1" AgeQualifyingCode="10">
         <Profiles>
            <ProfileInfo>
               <Profile ProfileType="1">
                  <Customer>
                     <PersonName>
                        <GivenName>Ajit</GivenName>
                        <Surname>Amin</Surname>
                     </PersonName>
                  </Customer>
                  <CompanyInfo />
               </Profile>
            </ProfileInfo>
         </Profiles>
      </ResGuest>
   </ResGuests>
   <ResGlobalInfo>
      <TimeSpan Start="2023-07-24" End="2023-07-26" Duration="P2D" />
      <Guarantee>
         <GuaranteesAccepted>
            <GuaranteeAccepted PaymentTransactionTypeCode="reserve">
               <PaymentCard />
            </GuaranteeAccepted>
         </GuaranteesAccepted>
      </Guarantee>
      <Total AmountBeforeTax="161.64" AmountAfterTax="182.36" CurrencyCode="EUR">
         <Taxes>
            <Tax Amount="20.72" CurrencyCode="EUR" />
         </Taxes>
      </Total>
      <HotelReservationIDs>
         <HotelReservationID ResID_Source="GH" ResID_Type="14" ResID_Value="10342527097" />
         <HotelReservationID ResID_Source="??10521644_en-us??" ResID_Type="34" ResID_Value="2413614038" />
      </HotelReservationIDs>
      <Profiles>
         <ProfileInfo>
            <UniqueID Type="10" ID="1100001918" ID_Context="HGC" />
            <Profile>
               <CompanyInfo>
                  <CompanyName CompanyShortName="Hotel Patria" />
               </CompanyInfo>
            </Profile>
         </ProfileInfo>
      </Profiles>
   </ResGlobalInfo>
</HotelReservation>
<HotelReservation RoomStayReservation="true" CreateDateTime="2023-07-08T12:47:14Z" LastModifyDateTime="2023-07-08T12:47:14Z" ResStatus="Book">
   <POS>
      <Source>
         <BookingChannel Type="288">
            <CompanyName CompanyShortName="lastminute.com" />
         </BookingChannel>
      </Source>
   </POS>
   <RoomStays>
      <RoomStay RoomStayStatus="Book" IndexNumber="21766780">
         <RoomTypes>
            <RoomType NumberOfUnits="1" RoomTypeCode="SUPSGL" NonSmoking="true">
               <RoomDescription Name="Superior Single Room. Room Only." Language="en-us" />
            </RoomType>
         </RoomTypes>
         <RatePlans>
            <RatePlan RatePlanCode="PRONR" RatePlanType="11" PriceViewableInd="false" PrepaidIndicator="true" />
         </RatePlans>
         <RoomRates>
            <RoomRate InvBlockCode="SUPSGLPRONR">
               <Rates>
                  <Rate EffectiveDate="2023-07-24" ExpireDate="" ExpireDateExclusiveIndicator="true" RoomPricingType="Per night">
                     <Base AmountBeforeTax="80.82" CurrencyCode="EUR" />
                     <Total AmountBeforeTax="80.82" AmountAfterTax="91.18" CurrencyCode="EUR" />
                  </Rate>
                  <Rate EffectiveDate="2023-07-25" ExpireDate="" ExpireDateExclusiveIndicator="true" RoomPricingType="Per night">
                     <Base AmountBeforeTax="80.82" CurrencyCode="EUR" />
                     <Total AmountBeforeTax="80.82" AmountAfterTax="91.18" CurrencyCode="EUR" />
                  </Rate>
               </Rates>
            </RoomRate>
         </RoomRates>
         <GuestCounts>
            <GuestCount AgeQualifyingCode="10" Count="1" ResGuestRPH="1" />
         </GuestCounts>
         <CancelPenalties CancelPolicyIndicator="true">
            <CancelPenalty>
               <Deadline OffsetTimeUnit="Day" OffsetUnitMultiplier="550" />
               <AmountPercent NmbrOfNights="2" />
            </CancelPenalty>
         </CancelPenalties>
         <Total AmountBeforeTax="161.64" AmountAfterTax="182.36" CurrencyCode="EUR">
            <Taxes>
               <Tax Amount="20.72" CurrencyCode="EUR" />
            </Taxes>
         </Total>
         <TPA_Extensions />
      </RoomStay>
   </RoomStays>
   <ResGuests>
      <ResGuest ResGuestRPH="1" AgeQualifyingCode="10">
         <Profiles>
            <ProfileInfo>
               <Profile ProfileType="1">
                  <Customer>
                     <PersonName>
                        <GivenName>Ajit</GivenName>
                        <Surname>Amin</Surname>
                     </PersonName>
                  </Customer>
                  <CompanyInfo />
               </Profile>
            </ProfileInfo>
         </Profiles>
      </ResGuest>
   </ResGuests>
   <ResGlobalInfo>
      <TimeSpan Start="2023-07-24" End="2023-07-26" Duration="P2D" />
      <Guarantee>
         <GuaranteesAccepted>
            <GuaranteeAccepted PaymentTransactionTypeCode="reserve">
               <PaymentCard />
            </GuaranteeAccepted>
         </GuaranteesAccepted>
      </Guarantee>
      <Total AmountBeforeTax="161.64" AmountAfterTax="182.36" CurrencyCode="EUR">
         <Taxes>
            <Tax Amount="20.72" CurrencyCode="EUR" />
         </Taxes>
      </Total>
      <HotelReservationIDs>
         <HotelReservationID ResID_Source="yu" ResID_Type="14" ResID_Value="10342" />
         <HotelReservationID ResID_Source="??10521644_en-us??" ResID_Type="34" ResID_Value="2413614038" />
      </HotelReservationIDs>
      <Profiles>
         <ProfileInfo>
            <UniqueID Type="10" ID="1100001918" ID_Context="HGC" />
            <Profile>
               <CompanyInfo>
                  <CompanyName CompanyShortName="Hotel Patria" />
               </CompanyInfo>
            </Profile>
         </ProfileInfo>
      </Profiles>
   </ResGlobalInfo>
</HotelReservation>

</HotelReservations>';
    }

}
