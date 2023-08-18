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

class MrandmrssmithPull extends AbstractPullStream {

    /**
     * @return array of PullReservation
     */
    public function pullReservations() {
        $this->utilFunc = new UtilFunc();
        $this->allRes = [];
        $this->resaArr = [];
        $this->saltKey = "dc954716a68bff52bc6aba4f6c59a5e5";

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



        while (preg_match('/<bookedbooking>(.*?)<\/bookedbooking>/is', $xmlStr, $match)) {
            $xmlStr = $this->utilFunc->after($match[0], $xmlStr);
            $tempFile = $match[1];

            $refer = $this->utilFunc->parseXmlValue('reference', $tempFile);
            $lastmodify = date("Y-m-d H:i:s", $this->utilFunc->parseXmlValue('updated_at', $tempFile));

            if ($lastmodify == ' ') {
                $lastmodify = date("Y-m-d H:i:s", $this->utilFunc->parseXmlValue('created_at', $tempFile));
            }
            if ($lastmodify == ' ') {
                $lastmodify = date("Y-m-d H:i:s", $this->utilFunc->parseXmlValue('booked_time', $tempFile));
            }
            if ($lastmodify == ' ') {
                $lastmodify = date("Y-m-d H:i:s", $this->utilFunc->parseXmlValue('date', $tempFile));
            }

            $status = $this->utilFunc->parseXmlValue('status', $tempFile);

            $propertyReference = $this->siteUser->hotel_id;
            $this->insertXml($refer, $status, $tempFile, $lastmodify, $propertyReference);
            if ($status == 'cancelled') {
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
        $res->currency = $this->utilFunc->parseXmlValue('currency', $resultFile);

        $payment = $this->utilFunc->parseXmlValue('payment_type', $resultFile);
        if ($payment) {
            $res->paymentMode = $payment;
        } else {
            $res->paymentMode = NULL;
        }
        if ($forceFlg) {
            $res->force = \TRUE;
        }

        $res->createDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
        $res->updateDate = (new \DateTime('2000-01-01 00:00:00'))->format(DATE_ATOM);

        $res->creditCard->token = NULL;

        $res->guest->email = $this->utilFunc->parseXmlValue('customer_email', $resultFile);

        $res->guest->phone = $this->utilFunc->parseXmlValue('mobile', $resultFile);
        $firstName = '';
        $lastName = '';
        list($firstName, $lastName) = explode(' ', $this->utilFunc->parseXmlValue('customer_name', $resultFile) . ',');

        $res->guest->firstName = substr($firstName, 0, 40);
        $res->guest->lastName = substr($lastName, 0, 40);
        $res->guest->address = $this->utilFunc->parseXmlValue('line_1', $resultFile) . $this->utilFunc->parseXmlValue('line_2', $resultFile);
        $res->guest->city = $this->utilFunc->parseXmlValue('town', $resultFile);
        $res->guest->zip = $this->utilFunc->parseXmlValue('postcode', $resultFile);
        $res->guest->country = $this->utilFunc->parseXmlValue('country', $resultFile);

        // Create room data
        if (preg_match_all('/(<summary>(.*?)<\/summary>)/is', $resultFile, $roomsmatch)) {

            $numberofrooms = count($roomsmatch[0]);
            $checkIn = (new \DateTime($this->utilFunc->parseXmlValue('start_date_fmt', $resultFile)))->format('Y-m-d');
            $checkOut = (new \DateTime($this->utilFunc->parseXmlValue('end_date_fmt', $resultFile)))->format('Y-m-d');

            foreach ($roomsmatch[0] as $roomXML) {

                $room = new PullReservationRoom($this->utilFunc->parseXmlValue('room_type_id', $resultFile) . ':' . $this->utilFunc->parseXmlValue('rate_code', $resultFile));
                if ($room) {
                    $dateArr = $this->utilFunc->getDatesFromRange($checkIn, $checkOut);
                    $countDays = count($dateArr);
                    $totalBuffer = $this->utilFunc->parseXmlValue('total_gbp', $roomXML);

                    foreach ($dateArr as $date) {
                        $pricePerDay = $totalBuffer / $countDays;
                        $room->daily[] = new PullReservationDay($date, $pricePerDay, true);
                    }

                    $room->children = $this->utilFunc->parseXmlValue('children', $resultFile);
                    $room->pax = $this->utilFunc->parseXmlValue('adults', $resultFile) + $this->utilFunc->parseoneValue('children', $resultFile);

                    $room->total = round($totalBuffer, 2);
                    $room->taxIncluded = true;
                    $room->totalPaid = NULL;
                    $room->checkIn = $checkIn;
                    $room->checkOut = $checkOut;
                    $room->paidNotes = NULL;

                    $voucherJson = $this->createVoucherV2($res, $room);
                    $voucherJson['Date'] = $this->utilFunc->parseXmlValue('date', $roomXML);
                    $voucherJson['hotel Id'] = $this->utilFunc->parseXmlValue('hotel_id', $resultFile);

                    $voucherJson['roomTable'] = $this->roomTable($roomXML);
                    $voucherJson['Number of rooms'] = $numberofrooms;
                    $voucherJson['deposit_policy'] = $this->utilFunc->parseXmlValue('deposit_policy', $resultFile);
                    $voucherJson['cancellation_policy'] = $this->utilFunc->parseXmlValue('cancellation_policy', $resultFile);
                    $voucherJson['inclusions'] = $this->utilFunc->parseXmlValue('inclusions', $resultFile);
                    $voucherJson['smith_card_offer'] = $this->utilFunc->parseXmlValue('smith_card_offer', $resultFile);
                    $voucherJson['useful_information'] = $this->utilFunc->parseXmlValue('useful_information', $resultFile);
                    $voucherJson['checkinout_policy'] = $this->utilFunc->parseXmlValue('checkinout_policy', $resultFile);
                    $voucherJson['price_information'] = $this->utilFunc->parseXmlValue('price_information', $resultFile);
                    $voucherJson['arrival_time'] = $this->utilFunc->parseXmlValue('arrival_time', $resultFile);
                    $voucherJson['exchange_rate_provider'] = $this->utilFunc->parseXmlValue('exchange_rate_provider', $resultFile);
                    $voucherJson['exchange_rate_customer'] = $this->utilFunc->parseXmlValue('exchange_rate_customer', $resultFile);
                    $voucherJson['is_markup'] = $this->utilFunc->parseXmlValue('is_markup', $resultFile);
                    $voucherJson['provider_cost_gbp'] = $this->utilFunc->parseXmlValue('provider_cost_gbp', $resultFile);
                    $voucherJson['provider_cost_customer'] = $this->utilFunc->parseXmlValue('provider_cost_customer', $resultFile);
                    $voucherJson['provider_cost_provider'] = $this->utilFunc->parseXmlValue('provider_cost_provider', $resultFile);
                    $voucherJson['commission_rate'] = $this->utilFunc->parseXmlValue('commission_rate', $resultFile);
                    $voucherJson['commission_gbp'] = $this->utilFunc->parseXmlValue('commission_gbp', $resultFile);
                    $voucherJson['commission_customer'] = $this->utilFunc->parseXmlValue('commission_customer', $resultFile);
                    $voucherJson['commission_provider'] = $this->utilFunc->parseXmlValue('commission_provider', $resultFile);
                    $voucherJson['commission_res_gbp'] = $this->utilFunc->parseXmlValue('commission_res_gbp', $resultFile);
                    $voucherJson['total_provider'] = $this->utilFunc->parseXmlValue('total_provider', $resultFile);
                    $voucherJson['availability_check_date'] = $this->utilFunc->parseXmlValue('availability_check_date', $resultFile);
                    $voucherJson['processing_type'] = $this->utilFunc->parseXmlValue('processing_type', $resultFile);
                    $voucherJson['prevent_enett_reason'] = $this->utilFunc->parseXmlValue('prevent_enett_reason', $resultFile);
                    $voucherJson['loyalty_calculation_base_amount'] = $this->utilFunc->parseXmlValue('loyalty_calculation_base_amount', $resultFile);
                    $voucherJson['provider_cost_provider_ex_tax'] = $this->utilFunc->parseXmlValue('provider_cost_provider_ex_tax', $resultFile);
                    $voucherJson['total_provider_ex_tax'] = $this->utilFunc->parseXmlValue('total_provider_ex_tax', $resultFile);
                    $roomname = $this->utilFunc->parseXmlValue('room_name', $resultFile);
                    $voucherJson['room_name'] = $this->parseRoomName($roomname);
                    $voucherJson['hotel_id'] = $this->utilFunc->parseXmlValue('hotel_id', $resultFile);
                    $voucherJson['rate_type_name'] = $this->utilFunc->parseXmlValue('rate_type_name', $resultFile);
                    $voucherJson['customer_rate_code'] = $this->utilFunc->parseXmlValue('customer_rate_code', $resultFile);
                    $voucherJson['nights'] = $this->utilFunc->parseXmlValue('nights', $resultFile);
                    $voucherJson['hotel_region'] = $this->utilFunc->parseXmlValue('hotel_region', $resultFile);
                    $voucherJson['hotel_country_code'] = $this->utilFunc->parseXmlValue('hotel_country_code', $resultFile);
                    $voucherJson['hotel_rate_per_night'] = $this->utilFunc->parseXmlValue('hotel_rate_per_night', $resultFile);
                    $voucherJson['contract_type'] = $this->utilFunc->parseXmlValue('contract_type', $resultFile);
                    $voucherJson['other_fees_gbp'] = $this->utilFunc->parseXmlValue('other_fees_gbp', $resultFile);
                    $voucherJson['other_fees_customer'] = $this->utilFunc->parseXmlValue('other_fees_customer', $resultFile);
                    $voucherJson['other_fees_provider'] = $this->utilFunc->parseXmlValue('other_fees_provider', $resultFile);
                    $voucherJson['mrcp_id'] = $this->utilFunc->parseXmlValue('mrcp_id', $resultFile);
                    $voucherJson['mrdp_id'] = $this->utilFunc->parseXmlValue('mrdp_id', $resultFile);
                    $voucherJson['rate_per_night_provider'] = $this->utilFunc->parseXmlValue('rate_per_night_provider', $resultFile);
                    $voucherJson['property_id'] = $this->utilFunc->parseXmlValue('property_id', $resultFile);

                    $room->json = $voucherJson;
                }
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
        $errmsg = '';
        $result = '';
        $d1 = new \DateTime();
        $d1->sub(new \DateInterval('P1D'));
        $d2 = new \DateTime();

        $qrystr = 'username=' . $this->siteUser->sites_user . '&version=2&hotel_id=' . $this->siteUser->hotel_id . '&dateandtime=' . $d1->format('Y-m-d') . '+00%3A00%3A00&todateandtime=' . $d2->format('Y-m-d') . '+08%3A28%3A05';
        $queryStr = $this->getFinalStr($qrystr);
        $url = 'https://api.mrandmrssmith.com/api-xml/bookings/changed-since-for-hotel?';
        $finalUrl = $url . $queryStr;

        //$result = $this->utilFunc->get( '$finalUrl',[] );
        $result = $this->tempXml();

        $str = "finalUrl=>$finalUrl\nresult=>$result\n";
//        $this->utilFunc->appendFile($this->LogFile, $str);

        if (preg_match('/<status>\s*error\s*<\/status>/is', $result, $match)) {
            if (preg_match('/<error>(.*?)<\/error>/is', $result, $match)) {
                $errmsg = trim($match[1]);
            }
            if ($errmsg == '') {
                $errmsg = 'Reservation Not Found. Some Error Occurs';
                throw new \Exception($errmsg);
            }
        } elseif (preg_match('/<status>\s*(.*?)\s*<\/status>/is', $result)) {

            return $result;
        } else {
            $errmsg = "Authentication failed or server issue.\n";
            throw new \Exception($errmsg);
        }
    }

    /**
     * @return final string//
     */
    public function getFinalStr($str) {
        $qrystr = trim($str);
        $hash = strtoupper($qrystr) . $this->saltKey;
        $hash = md5($hash);
        return $qrystr . '&hash=' . $hash;
    }

    //return rooms name//
    public function parseRoomName($roomName) {
        if (preg_match('/<!\[CDATA\[(.*?)\]\]/', $roomName, $match)) {
            return $match[1];
        } else {
            return $roomName;
        }
    }

    /**
     * @return table of rooms data 
     */
    public function roomTable($resultFile) {

        $details = '<table border="1" width="100%">';
        $details .= '<tr>';
        $details .= '<td align="center">Date</td>';

        $details .= '<td align="center">Price</td>';
        $details .= '<td align="center">commission_rate</td>';
        $details .= '<td align="center">commission_gbp</td>';
        $details .= '<td align="center">commission_customer</td>';
        $details .= '<td align="center">commission_provider</td>';

        $details .= '<td align="center">breakfast</td>';
        $details .= '<td align="center">lunch</td>';
        $details .= '<td align="center">dinner</td>';

        $details .= '<td align="center">rate_or_offer_name</td>';
        



        $details .= '</tr>';
        $total1 = 0;
        //print "resultFile=>$resultFile\n\n";
        while (preg_match('/<summary>(.*?)<\/summary>/is', $resultFile, $match)) {
            //print "test=>\n\n";
            $resultFile = $this->utilFunc->after($match[0], $resultFile);

            $tempFile = $match[1];

            $total = '0';
            $checkin1 = $this->utilFunc->parseXmlValue('date', $tempFile);
            list($y, $m, $d) = explode('-', $checkin1);
            $month = $this->monthValue($m);
            $checkin1 = $y . '-' . $month . '-' . $d;
            //print "checkin=>$checkin\n";



            $commission = $this->utilFunc->parseXmlValue('commission_rate', $tempFile);
            $com_gbp =$this->utilFunc->parseXmlValue('commission_gbp', $tempFile);
            $commission_customer =$this->utilFunc->parseXmlValue('commission_customer', $tempFile);
            $commission_provider =$this->utilFunc->parseXmlValue('commission_provider', $tempFile);

            $total = $this->utilFunc->parseXmlValue('total_gbp', $tempFile);
            $breakfast = $this->utilFunc->parseXmlValue('breakfast', $tempFile);
            $lunch = $this->utilFunc->parseXmlValue('lunch', $tempFile);
            $dinner = $this->utilFunc->parseXmlValue('dinner', $tempFile);


            $ratename = $this->utilFunc->parseXmlValue('rate_or_offer_name', $tempFile);
            

            $details .= '<tr>';
            $details .= '<td align="center">' . $checkin1 . '</td>';
            $details .= '<td align="center">'.$total.'</td>';
            $details .= '<td align="center">' .  $commission . '</td>';
            $details .= '<td align="center">' .  $com_gbp . '</td>';
            $details .= '<td align="center">' .  $commission_customer. '</td>';
            $details .= '<td align="center">' .  $commission_provider . '</td>';

            $details .= '<td align="center">' .$breakfast . '</td>';
            $details .= '<td align="center">' . $lunch . '</td>';
            $details .= '<td align="center">' . $dinner . '</td>';
            $details .= '<td align="center">' . $ratename  . '</td>';

            $details .= '</tr>';
            $total1 += $total;
        }
        $details .= '</table>';
        return $details;
    }

    //return  Months//
    public function monthValue($m) {
        $months = array('Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04', 'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08', 'Sep' => '09', 'Oct' => 10, 'Nov' => 11, 'Dec' => 12);
        foreach ($months as $num => $name) {
            if ($m == $num) {
                $month = $months[$m];
            }
        }
        return $month;
    }

    ///parse date//

    public function parseCreateDate($createDateTime) {
        if (preg_match('/(.*?)\s+/is', $createDateTime, $match)) {
            return $match[1];
        } else {
            $errmsg = "Could not parse reservation date.\n";
            return $errmsg;
        }
    }

    /**
     * @return xml of temp. XML
     */
    public function tempXml() {
        return '<?xml version="1.0" encoding="UTF-8"?>
<bookedbookings>
<bookedbooking>
<deposit_policy>Payment upon check-out can be made with cash in Euros or Moroccan Dirhams.</deposit_policy>
<cancellation_policy>Please be aware that cancellation after 14 Sep 2023 00:00 (BST) will incur a charge in full.
</cancellation_policy>
<inclusions>a:2:{s:11:"14 Oct 2023";a:1:{i:0;s:9:"breakfast";}s:11:"15 Oct 2023";a:1:{i:0;s:9:"breakfast";}}</inclusions>
<smith_card_offer>A bottle of wine on arrival for BlackSmiths and SilverSmiths; GoldSmiths get a handmade scented candle</smith_card_offer>
<useful_information>Please note the property only accepts guests aged six years and above.</useful_information>
<checkinout_policy>Check in is 11am and check out is 2pm. Both can be flexible, subject to availability. 
</checkinout_policy>
<price_information>
	<![CDATA[Tax is included at 20%.<br />Please note the hotel charges an additional local city tax of €3.00 per person per night on check-out]]>
</price_information>
<arrival_time>20:00</arrival_time>
<package></package>
<supplier></supplier>
<reference>OB23071121423205-2315465</reference>
<type>room</type>
<status>confirmed</status>
<status_time>1689108158</status_time>
<status_reason></status_reason>
<created_at>1689108152</created_at>
<updated_at>1689108158</updated_at>
<booked_time>1689108156</booked_time>
<offer_name></offer_name>
<exchange_rate_provider>1.16902</exchange_rate_provider>
<exchange_rate_customer>1</exchange_rate_customer>
<currency>GBP</currency>
<currency_customer>GBP</currency_customer>
<currency_provider>EUR</currency_provider>
<is_markup>0</is_markup>
<provider_cost_gbp>214.89</provider_cost_gbp>
<provider_cost_customer>214.89</provider_cost_customer>
<provider_cost_provider>251.21</provider_cost_provider>
<commission_rate>20</commission_rate>
<commission_gbp>53.72</commission_gbp>
<commission_customer>53.72</commission_customer>
<commission_provider>62.796</commission_provider>
<total_gbp>268.61</total_gbp>
<total_with_tax_gbp>268.61</total_with_tax_gbp>
<commission_res_gbp>53.72</commission_res_gbp>
<total_customer>268.61</total_customer>
<total_provider>314</total_provider>
<deposit_gbp>268.61</deposit_gbp>
<deposit_customer>268.61</deposit_customer>
<deposit_provider>314</deposit_provider>
<tax_rate>20</tax_rate>
<paid_to_provider></paid_to_provider>
<availability_check_date>1689108158</availability_check_date>
<incoming_external_reference></incoming_external_reference>
<incoming_booking_channel></incoming_booking_channel>
<loyalty_calculation_base>0</loyalty_calculation_base>
<processing_type>system</processing_type>
<chained></chained>
<chained_name></chained_name>
<is_nonrefundable></is_nonrefundable>
<sort_order></sort_order>
<security_deposit></security_deposit>
<admin_cancellation_only></admin_cancellation_only>
<is_prepaid>1</is_prepaid>
<amended_at></amended_at>
<prevent_enett></prevent_enett>
<prevent_enett_reason>NULL</prevent_enett_reason>
<loyalty_calculation_base_currency></loyalty_calculation_base_currency>
<loyalty_calculation_base_amount>0</loyalty_calculation_base_amount>
<provider_cost_provider_ex_tax>209.34</provider_cost_provider_ex_tax>
<total_provider_ex_tax>261.67</total_provider_ex_tax>
<summarys>
	<summary>
		<date>2023-Oct-14</date>
		<commission_rate>20</commission_rate>
		<commission_gbp>28.23</commission_gbp>
		<commission_customer>28.23</commission_customer>
		<commission_provider>33</commission_provider>
		<total_gbp>141.14</total_gbp>
		<total_customer>141.14</total_customer>
		<total_provider>165</total_provider>
		<breakfast>1</breakfast>
		<lunch></lunch>
		<dinner></dinner>
		<affiliate_commission_gbp>0</affiliate_commission_gbp>
		<affiliate_commission_customer>0</affiliate_commission_customer>
		<affiliate_commission_provider>0</affiliate_commission_provider>
		<rate_or_offer_name>Bed and breakfast rate</rate_or_offer_name>
		<updated_at>1689108153</updated_at>
		<total_customer_ex_tax>117.62</total_customer_ex_tax>
		<commission_customer_ex_tax>23.52</commission_customer_ex_tax>
		<affiliate_commission_customer_ex_tax>0</affiliate_commission_customer_ex_tax>
		<customer_sale_inc_tax>243.12</customer_sale_inc_tax>
		<customer_sale_ex_tax>202.6</customer_sale_ex_tax>
		<total_provider_ex_tax>137.5</total_provider_ex_tax>
	</summary>
	<summary>
		<date>2023-Oct-15</date>
		<commission_rate>20</commission_rate>
		<commission_gbp>25.49</commission_gbp>
		<commission_customer>25.49</commission_customer>
		<commission_provider>29.796</commission_provider>
		<total_gbp>127.46</total_gbp>
		<total_customer>127.46</total_customer>
		<total_provider>149</total_provider>
		<breakfast>1</breakfast>
		<lunch></lunch>
		<dinner></dinner>
		<affiliate_commission_gbp>0</affiliate_commission_gbp>
		<affiliate_commission_customer>0</affiliate_commission_customer>
		<affiliate_commission_provider>0</affiliate_commission_provider>
		<rate_or_offer_name>Bed and breakfast rate</rate_or_offer_name>
		<updated_at>1689108153</updated_at>
		<total_customer_ex_tax>106.22</total_customer_ex_tax>
		<commission_customer_ex_tax>21.24</commission_customer_ex_tax>
		<affiliate_commission_customer_ex_tax>0</affiliate_commission_customer_ex_tax>
		<customer_sale_inc_tax>240.38</customer_sale_inc_tax>
		<customer_sale_ex_tax>200.31</customer_sale_ex_tax>
		<total_provider_ex_tax>124.17</total_provider_ex_tax>
	</summary>
</summarys>
<extras></extras>
<prepaid>1</prepaid>
<offer_tag_text></offer_tag_text>
<incoming_api_user_name>
	<![CDATA[Mr & Mrs Smith]]>
</incoming_api_user_name>
<cancellation_penalty_charge>0</cancellation_penalty_charge>
<cancellation_penalty_charge_customer>0</cancellation_penalty_charge_customer>
<auto_cancellable></auto_cancellable>
<room_name>Superior Room</room_name>
<property_location>Marrakech</property_location>
<property_country>Morocco</property_country>
<hotel_name>
	<![CDATA[Ksar Kasbah & Spa]]>
</hotel_name>
<hotel_id>7002</hotel_id>
<rate_type>20443</rate_type>
<rate_code>B</rate_code>
<customer_rate_code>B</customer_rate_code>
<reward_night></reward_night>
<rate_type_name>Bed and breakfast rate</rate_type_name>
<nights>2</nights>
<adults>2</adults>
<children>0</children>
<hotel_region>EMEA</hotel_region>
<hotel_country_code>MA</hotel_country_code>
<hotel_rate_per_night>134.3</hotel_rate_per_night>
<adult_extra_beds>0</adult_extra_beds>
<child_extra_beds>0</child_extra_beds>
<cots>0</cots>
<contract_type>invoice</contract_type>
<is_rate_prepaid></is_rate_prepaid>
<other_fees_gbp>10.2650</other_fees_gbp>
<other_fees_customer>10.2650</other_fees_customer>
<other_fees_provider>12.0000</other_fees_provider>
<rate_parity_issue></rate_parity_issue>
<mrcp_id>699227</mrcp_id>
<mrdp_id>0</mrdp_id>
<accommodation_type></accommodation_type>
<rate_per_night_provider>130.835</rate_per_night_provider>
<property_id>6838</property_id>
<check_in_time>11:00:00</check_in_time>
<check_out_time>14:00:00</check_out_time>
<room_type_id>24659</room_type_id>
<offer_tag></offer_tag>
<start_date_fmt>2023-10-14</start_date_fmt>
<end_date_fmt>2023-10-16</end_date_fmt>
<notes></notes>
<child_ages></child_ages>
<hotel_deposit_required></hotel_deposit_required>
<customer_email>laurelodell@hotmail.comundefined</customer_email>undefined<customer_address>
<company></company>
<line_1>182 Fleeming Road</line_1>
<line_2></line_2>
<town>London</town>
<county></county>
<postcode>E17 5EU</postcode>
<country>GB</country>
<telephone>+447921163511</telephone>
<mobile>+447921163511</mobile>undefined</customer_address>undefined<customer_name>Laurel O"Dell</customer_name>undefined<customer_first_name>Laurel</customer_first_name>undefined<customer_last_name>O"Dell</customer_last_name>undefined<customer_gender></customer_gender>undefined<customer_membership_type>BlackSmith</customer_membership_type>undefined<lead_name>Laurel O"Dell</lead_name>undefined<lead_first_name>Laurel</lead_first_name>undefined<lead_last_name>O"Dell</lead_last_name>
</bookedbooking>
</bookedbookings>';
    }

}
