<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

namespace com\octorate\stream\pull;

use com\octorate\stream\common\AbstractPullStream;
use com\octorate\stream\common\PullReservation;
use com\octorate\stream\common\PullReservationRoom;
use com\octorate\stream\common\PullReservationDay;
use com\octorate\stream\common\PullReservationExtra;
use com\octorate\stream\utils\UtilFunc;

class AmadeusPull extends AbstractPullStream {

    private $confirmationReq;

    /**
     * @return array of PullReservation
     */
    public function pullReservations() {
        $this->utilFunc = new UtilFunc();
        $this->allRes = [];
        $this->resaArr = [];
        $this->confirmationReq = Array();

        if ($xmlStr = $this->tempxml()) {
            $this->parseReservations($xmlStr);
        }
        //echo "<pre>". htmlentities($xmlStr) . "</pre>";
        if (!empty($this->confirmationReq)) {
            $this->sendConfirmation($this->confirmationReq);
        }
        print_r($this->test);
        if($this->test){
        print("value of allRes=>");
        print_r($this->allRes);
        exit;
        }
        // return array with all reservations found
        header('X-Pull-Version: 2');
        // return $this->allRes;
    }

    /**
     * @return xml of parse All bookings
     */
    public function parseReservations($xmlStr) {
        $resaArr = [];
        if (preg_match('/<HotelReservation\s.*?<\/HotelReservation>/is', $xmlStr)) {
            while (preg_match('/<HotelReservation\s.*?<\/HotelReservation>/is', $xmlStr, $match)) {
                $xmlStr = $this->utilFunc->after($match[0], $xmlStr);
                $tempFile = $match[0];
                $status = $this->utilFunc->parseOneValue('ResStatus', $tempFile);
                if (preg_match('/<HotelReservationID ResID_Type="14"(.*?)\/>/is', $tempFile, $match)) {
                    $temp = $match[0];
                    $refer = $this->utilFunc->parseOneValue('ResID_Value', $temp);
                }
                $propertyReference = $this->siteUser->hotel_id;
                $modification = $this->utilFunc->parseOneValue('LastModifyDateTime', $tempFile);
                if ($modification == '') {
                    $modification = $this->utilFunc->parseOneValue('CreateDateTime', $tempFile);
                }
                $lastmodify = (new \DateTime($modification))->format('Y-m-d H:i:s');
                $tempFileXml = preg_replace('/<PaymentCard.*?<\/PaymentCard>/is', '<PaymentCard></PaymentCard>', $tempFile);
                $insertId = $this->insertXml($refer, $status, $tempFileXml, $lastmodify, $propertyReference);
                $this->addConfirmationReq($insertId, $refer);
                if ($status == 'cancel') {
                    $this->allRes[] = $this->retrieveCancellation($refer, $lastmodify, false);
                } else {
                    $this->allRes[] = $this->retrieveReservation($tempFile, $refer, $status, $lastmodify, false);
                }
            }
        }
        $resaArr[$refer] = $refer;
        if ($this->checkUnprocessedBooking()) {
            foreach ($this->pendingResaArr as $refer => $value) {
                if (!(array_key_exists($refer, $resaArr))) {
                    if ($value['status'] == 'cancel' && $value['xml'] != '') {
                        $this->allRes[] = $this->retrieveCancellation($refer, $value['lastmodify_time'], true);
                    } elseif ($value['xml'] != '') {
                        $this->allRes[] = $this->retrieveReservation($value['xml'], $refer, $value['status'], $value['lastmodify_time'], true);
                    }
                    $this->markAsProcessedBooking($refer);
                }
            }
        }
    }

    /**
     * @return array of retrieveReservation
     */
    public function retrieveReservation($resultFile, $refer, $status, $lastmodify, $forceFlg) {
        // success, parse response and create reservations
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CONFIRMED;
        if ($forceFlg) {
            $res->force = \TRUE;
        }
        if (strtolower($status) == 'book') {
            $res->createDate = (new \DateTime($this->utilFunc->parseOneValue('CreateDateTime', $resultFile)))->format(DATE_ATOM);
            $res->updateDate = (new \DateTime('2000-01-01 00:00:00'))->format(DATE_ATOM);
        } else {
            $res->updateDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
            $res->createDate = NULL;
        }
        //$ccs_token = $this->utilFunc->parseOneValue('ccs_token', $resultFile);
        $res->creditCard->token = $this->utilFunc->parseOneValue('CardNumber', $resultFile);
        $res->guest->email = NULL;
        $res->guest->phone = NULL;
        $res->guest->firstName = substr($this->utilFunc->parseXmlValue('GivenName', $resultFile), 0, 40);
        $res->guest->lastName = substr($this->utilFunc->parseXmlValue('Surname', $resultFile), 0, 40);
        $res->guest->address = NULL;
        $res->guest->city = NULL;
        $res->guest->country = strtoupper($this->utilFunc->parseOneValue('CountryName', $resultFile));
        $res->guest->zip = NULL;
        $res->currency = strtoupper($this->utilFunc->parseOneValue('CurrencyCode', $resultFile));
        $res->language = strtoupper($this->utilFunc->parseOneValue('Language', $resultFile));
        $res->propertyReference = $this->siteUser->hotel_id;

        // create room data
        if (preg_match("/(<RoomStay.*?<\/RoomStay>)/is", $resultFile, $match)) {
            $tempFile = $resultFile;
            $checkIn = (new \DateTime($this->utilFunc->parseOneValue('Start', $tempFile)))->format('Y-m-d');
            $checkOut = (new \DateTime($this->utilFunc->parseOneValue('End', $tempFile)))->format('Y-m-d');
            while (preg_match("/(<RoomStay.*?<\/RoomStay>)/is", $tempFile, $match)) {
                $tempFile = $this->utilFunc->after($match[0], $tempFile);
                $roomXml = $match[1];
                $dayXml = $roomXml;
                $rateXml = $roomXml;
                $room = new PullReservationRoom($this->utilFunc->parseOneValue('RoomTypeCode', $roomXml) . ':' . $this->utilFunc->parseOneValue('RatePlanCode', $roomXml));
                $adultCount = 0;
                $childCount = 0;
                while (preg_match('/<GuestCount\s+(.*?)\/>/is', $dayXml, $match)) {
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
                while (preg_match("/<RoomRate(.*?)<\/RoomRate>/is", $rateXml, $match)) {
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

    /**
     * @return array of retrieveCancellation
     */
    public function retrieveCancellation($refer, $lastmodify, $forceFlg) {
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CANCELLED;
        $res->updateDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
        $res->createDate = NULL;
        if ($forceFlg) {
            $res->force = \TRUE;
        }
        // return single reservations object
        return $res;
    }

    /**
     * @return xml of All bookings
     */
    public function getReservations() {
        $result = '';
        $xml = '<OTA_ReadRQ EchoToken="' . time() . '" PrimaryLangID="en-us" Target="Production" TimeStamp="' . date('Y-m-d') . 'T' . date('H:i:s') . '" Version="0.001" xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05
                    OTA_ReadRQ.xsd"
	xmlns="http://www.opentravel.org/OTA/2003/05"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<ReadRequests>
		<HotelReadRequest ChainCode="CG" HotelCode="' . $this->siteUser->hotel_id . '" HotelName="' . $this->siteUser->hotel_id . '">
			<SelectionCriteria SelectionType="Undelivered"/>
		</HotelReadRequest>
	</ReadRequests>
</OTA_ReadRQ>';
        $header = Array('Connection: Keep-Alive', 'Content-Type: application/soap+xml', 'charset=utf-8');
        //$result = $this->utilFunc->submitXmlPost($this->url, $xml, $headers);
        $result = $this->tempxml();
        if (preg_match('/Success/is', $result)) {
            return $result;
        } elseif (preg_match('/<Warnings>/is', $result)) {
            throw new \Exception("Something went wrong, warning pulling reservations!");
        } elseif (preg_match('/<Errors>/is', $result)) {
            throw new \Exception("Something went wrong, error pulling reservations!");
        }
    }

    /**
     * @return array
     */
    public function addConfirmationReq($insertId, $refer) {
        $resaUnconfArray = array();
        if ($insertId > 0) {
            ;
        } else {
            return 0; //no need to confirm resa until insertId not exists.
        }
        if (array_key_exists($refer, $resaUnconfArray)) {
            ;
        } else {
            $resaUnconfArray[$refer] = '';
            $this->confirmationReq[$insertId] .= '<HotelReservation LastModifierID="AmadeusPMS">
	<UniqueID ID="' . uniqid() . '" Type="14"/>
	<ResGlobalInfo>
		<HotelReservationIDs>
			<HotelReservationID ResID_Source="HTNG" ResID_Type="14"
                                                        ResID_Value="' . $refer . '"/>
			<HotelReservationID ResID_Source="AmadeusPMS" ResID_Type="10"
                                                        ResID_Value="' . $insertId . '"/>
			<HotelReservationID ResID_Source="HTNG" ResID_Type="18"
                                                        ResID_Value="378182398"/>
		</HotelReservationIDs>
	</ResGlobalInfo>
</HotelReservation>';
        }
    }

    /**
     * @return array
     */
    public function sendConfirmation($confirmationReq) {
        $xml = '';
        $xml .= '<OTA_NotifReportRQ EchoToken="' . time() . '" PrimaryLangID="en-us" Target="Production"
                        TimeStamp="' . date('Y-m-d') . 'T' . date('H:i:s') . '" Version="0.001" xsi:schemaLocation="http://www
                        .opentravel.org/OTA/2003/05 OTA_NotifReportRQ.xsd"
	xmlns="http://www.opentravel.org/OTA/2003/05"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<Success/>
	<NotifDetails>
		<HotelNotifReport>
			<HotelReservations>' . "\n";
        foreach ($confirmationReq as $insertId => $request) {
            $xml .= $request;
        }
        $xml .= '</HotelReservations>
                      </HotelNotifReport>
                    </NotifDetails>
                  </OTA_NotifReportRQ>';
        $final_xml = '<soap:Envelope
	xmlns:wsa="http://schemas.xmlsoap.org/ws/2004/08/addressing"
	xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wsswssecurityutility-1.0.xsd"
	xmlns:xsd="http://www.w3.org/2001/XMLSchema"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecuritysecext-1.0.xsd"
	xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">&
                        
	<soap:Header>&
                            
		<wsse:Security soap:mustUnderstand="1">
			<wsse:UsernameToken wsu:Id="">
				<wsse:Username>' . $this->siteUser->sites_user . '</wsse:Username>
				<wsse:Password>"' . $this->siteUser->sites_pass . '"</wsse:Password>
				<wsse:PartnerID>' . $this->siteUser->user_org_id . '</wsse:PartnerID>
			</wsse:UsernameToken>
		</wsse:Security>
		<wsa:To>https://hotelplatform.services.amadeus.com/dri</wsa:To>
		<wsa:Action>https://hotelplatform.services.amadeus.com/dri/htng</wsa:Action>
		<wsa:From>
			<wsa:Address>http://schemas.xmlsoap.org/ws/2004/08/addressing/role/anonymous</wsa:Address>
			<wsa:Reference ChainCode="WL" BrandCode="AB" HotelCode="' . $this->siteUser->hotel_id . '"/>
		</wsa:From>&
                        
	</soap:Header>&                
	<soap:Body>&' . $xml;    
       // echo "Confirm xml => $final_xml\n";
        //$resultFile = $this->utilFunc->submitXmlPost($url, $final_xml, $header);
        //$this->insertConfirmationXml($insertId, $final_xml, $resultFile);
        //$this->insertResaLog($xml . "------" . $result);
    }

    public function tempxml() {

        return '<SOAP-ENV:Body
	xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
	<OTA_ResRetrieveRS EchoToken="Search_retrieve" TimeStamp="2009-12-02T15:30:26" Version="6.000"
                         xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05 OTA_ResRetrieveRS.xsd"
		xmlns="http://www.opentravel.org/OTA/2003/05"
		xmlns:xsi="http://www.w3.org/2001/XMLSchemainstance">
		<Success/>
		<ReservationsList>
			<HotelReservation CreateDateTime="2009-12-02T15:29:10" CreatorID="MUC1A0701" ResStatus="Book">
				<POS>
					<Source>
						<RequestorID ID="1A" Type="5"/>
						<BookingChannel Primary="true" Type="5">
							<CompanyName Code="1A"/>
						</BookingChannel>
					</Source>
				</POS>
				<UniqueID ID="16989086" ID_Context="CRS" Type="14"/>
				<RoomStays>
					<RoomStay IndexNumber="1">
						<RoomRates>
							<RoomRate NumberOfUnits="1" RatePlanCode="RA1" RoomTypeCode="DBL">
								<Rates>
									<Rate
                                    EffectiveDate="2009-12-12" ExpireDate="2009-12-14" RateTimeUnit="Day" UnitMultiplier="1">
										<Base AmountAfterTax="788"
                                    CurrencyCode="ZAR"/>
									</Rate>
								</Rates>
							</RoomRate>
						</RoomRates>
						<GuestCounts IsPerRoom="1">
							<GuestCount AgeQualifyingCode="10" Count="1"/>
						</GuestCounts>
						<TimeSpan End="2009-12-14" Start="2009-12-12"/>
						<DepositPayments>
							<GuaranteePayment>
								<AcceptedPayments>
									<AcceptedPayment
                                PaymentTransactionTypeCode="reserve">
										<PaymentCard CardCode="VI" ExpireDate="1219">
											<CardNumber>
												<PlainText>4444333322221111</PlainText>
											</CardNumber>
											<ThreeDomainSecurity>
												<Gateway ECI="2"/>
												<Results CAVV="AAABB1QlBAIBkyaDGCUEEEp5p2Qq" PAResStatus="Y"
                                    XID="02010000754b66d72af14d8fbdd503c75e640bcb"></Results>
											</ThreeDomainSecurity>
										</PaymentCard>
									</AcceptedPayment>
								</AcceptedPayments>
								<AmountPercent Amount="788" CurrencyCode="ZAR"/>
							</GuaranteePayment>
						</DepositPayments>
						<Total AmountAfterTax="1576" CurrencyCode="ZAR"/>
						<BasicPropertyInfo ChainCode="CG" HotelCode="CGCL0001"/>
						<ResGuestRPHs>1</ResGuestRPHs>
					</RoomStay>
				</RoomStays>
				<ResGuests>
					<ResGuest PrimaryIndicator="1" ResGuestRPH="1">
						<Profiles>
							<ProfileInfo>
								<Profile
                                ProfileType="1">
									<Customer>
										<PersonName>
											<GivenName>YYYYY</GivenName>
											<Surname>XXXX</Surname>
										</PersonName>
									</Customer>
								</Profile>
							</ProfileInfo>
							<ProfileInfo>
								<UniqueID ID="12345675"
                                ID_Context="IATA" Type="5"/>
								<Profile ProfileType="4">
									<CompanyInfo>
										<CompanyName Code="12345675"
                                CodeContext="IATA">TRAVEL AGENCY
                                12345675</CompanyName>
									</CompanyInfo>
								</Profile>
							</ProfileInfo>
						</Profiles>
					</ResGuest>
				</ResGuests>
				<ResGlobalInfo>
					<HotelReservationIDs>
						<HotelReservationID ResID_Type="14" ResID_Value="16989086"/>
						<HotelReservationID ResID_Type="18" ResID_Value="378183072"/>
					</HotelReservationIDs>
				</ResGlobalInfo>
			</HotelReservation>
			<HotelReservation CreateDateTime="2009-12-02T15:29:10" CreatorID="MUC1A0701" ResStatus="Book">
				<POS>
					<Source>
						<RequestorID ID="1A" Type="5"/>
						<BookingChannel Primary="true" Type="5">
							<CompanyName Code="1A"/>
						</BookingChannel>
					</Source>
				</POS>
				<UniqueID ID="16989085" ID_Context="CRS" Type="14"/>
				<RoomStays>
					<RoomStay IndexNumber="1">
						<RoomRates>
							<RoomRate NumberOfUnits="1" RatePlanCode="RA1" RoomTypeCode="DBL">
								<Rates>
									<Rate EffectiveDate="2009-12-12" ExpireDate="2009-12-14" RateTimeUnit="Day"
                                UnitMultiplier="1">
										<Base AmountAfterTax="788"
                                CurrencyCode="ZAR"/>
									</Rate>
								</Rates>
							</RoomRate>
						</RoomRates>
						<GuestCounts IsPerRoom="1">
							<GuestCount AgeQualifyingCode="10" Count="1"/>
						</GuestCounts>
						<TimeSpan End="2009-12-14" Start="2009-12-12"/>
						<DepositPayments>
							<GuaranteePayment>
								<AcceptedPayments>
									<AcceptedPayment
            PaymentTransactionTypeCode="reserve">
										<PaymentCard CardCode="VI" ExpireDate="1219">
											<CardNumber>
												<PlainText>4444333322221111</PlainText>
											</CardNumber>
											<ThreeDomainSecurity>
												<Gateway ECI="2"/>
												<Results CAVV="AAABB1QlBAIBkyaDGCUEEEp5p2Qq" PAResStatus="Y"
                                    XID="02010000754b66d72af14d8fbdd503c75e640bcb"></Results>
											</ThreeDomainSecurity>
										</PaymentCard>
									</AcceptedPayment>
								</AcceptedPayments>
								<AmountPercent Amount="788"
                                CurrencyCode="ZAR"/>
							</GuaranteePayment>
						</DepositPayments>
						<Total AmountAfterTax="1576" CurrencyCode="ZAR"/>
						<BasicPropertyInfo ChainCode="CG" HotelCode="CGCL0001"/>
						<ResGuestRPHs>1</ResGuestRPHs>
					</RoomStay>
				</RoomStays>
				<ResGuests>
					<ResGuest PrimaryIndicator="1" ResGuestRPH="1">
						<Profiles>
							<ProfileInfo>
								<Profile
                            ProfileType="1">
									<Customer>
										<PersonName>
											<GivenName>YYYYY</GivenName>
											<Surname>XXXX</Surname>
										</PersonName>
									</Customer>
								</Profile>
							</ProfileInfo>
							<ProfileInfo>
								<UniqueID ID="12345675"
                            ID_Context="IATA" Type="5"/>
								<Profile ProfileType="4">
									<CompanyInfo>
										<CompanyName Code="12345675"
                            CodeContext="IATA">TRAVEL AGENCY
                            12345675</CompanyName>
									</CompanyInfo>
								</Profile>
							</ProfileInfo>
						</Profiles>
					</ResGuest>
				</ResGuests>
				<ResGlobalInfo>
					<HotelReservationIDs>
						<HotelReservationID ResID_Type="14" ResID_Value="16989085"/>
						<HotelReservationID ResID_Type="18" ResID_Value="378183063"/>
					</HotelReservationIDs>
				</ResGlobalInfo>
			</HotelReservation>
			<HotelReservation CreateDateTime="2009-12-02T15:29:10" CreatorID="MUC1A0701" ResStatus="Book">
				<POS>
					<Source>
						<RequestorID ID="1A" Type="5"/>
						<BookingChannel Primary="true"
                            Type="5">
							<CompanyName Code="1A"/>
						</BookingChannel>
					</Source>
				</POS>
				<UniqueID ID="16989084" ID_Context="CRS" Type="14"/>
				<RoomStays>
					<RoomStay IndexNumber="1">
						<RoomRates>
							<RoomRate NumberOfUnits="1" RatePlanCode="RA1" RoomTypeCode="DBL">
								<Rates>
									<Rate
                                EffectiveDate="2009-12-12" ExpireDate="2009-12-14" RateTimeUnit="Day"
                                UnitMultiplier="1">
										<Base AmountAfterTax="788"
                                CurrencyCode="ZAR"/>
									</Rate>
								</Rates>
							</RoomRate>
						</RoomRates>
						<GuestCounts IsPerRoom="1">
							<GuestCount AgeQualifyingCode="10" Count="1"/>
						</GuestCounts>
						<TimeSpan End="2009-12-14" Start="2009-12-12"/>
						<DepositPayments>
							<GuaranteePayment>
								<AcceptedPayments>
									<AcceptedPayment
                                PaymentTransactionTypeCode="reserve">
										<PaymentCard CardCode="VI" ExpireDate="1219">
											<CardNumber>
												<PlainText>4444333322221111</PlainText>
											</CardNumber>
											<ThreeDomainSecurity>
												<Gateway ECI="2"/>
												<Results CAVV="AAABB1QlBAIBkyaDGCUEEEp5p2Qq" PAResStatus="Y"
                                XID="02010000754b66d72af14d8fbdd503c75e640bcb"></Results>
											</ThreeDomainSecurity>
										</PaymentCard>
									</AcceptedPayment>
								</AcceptedPayments>
								<AmountPercent Amount="788"
                                CurrencyCode="ZAR"/>
							</GuaranteePayment>
						</DepositPayments>
						<Total AmountAfterTax="1576" CurrencyCode="ZAR"/>
						<BasicPropertyInfo ChainCode="CG" HotelCode="CGCL0001"/>
						<ResGuestRPHs>1</ResGuestRPHs>
					</RoomStay>
				</RoomStays>
				<ResGuests>
					<ResGuest PrimaryIndicator="1" ResGuestRPH="1">
						<Profiles>
							<ProfileInfo>
								<Profile
                            ProfileType="1">
									<Customer>
										<PersonName>
											<GivenName>YYYYY</GivenName>
											<Surname>XXXX</Surname>
										</PersonName>
									</Customer>
								</Profile>
							</ProfileInfo>
							<ProfileInfo>
								<UniqueID ID="12345675"
                            ID_Context="IATA" Type="5"/>
								<Profile ProfileType="4">
									<CompanyInfo>
										<CompanyName Code="12345675"
                            CodeContext="IATA">TRAVEL AGENCY
                            12345675</CompanyName>
									</CompanyInfo>
								</Profile>
							</ProfileInfo>
						</Profiles>
					</ResGuest>
				</ResGuests>
				<ResGlobalInfo>
					<HotelReservationIDs>
						<HotelReservationID ResID_Type="14" ResID_Value="16989084"/>
						<HotelReservationID ResID_Type="18" ResID_Value="378183054"/>
					</HotelReservationIDs>
				</ResGlobalInfo>
			</HotelReservation>
		</ReservationsList>
	</OTA_ResRetrieveRS>
</SOAP-ENV:Body>';
    }

}
