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

        $regex = '<bookedbookings>(.*?)<\/bookedbookings>/is';

        preg_match_all($regex,$xmlStr,$match);
        while ($match) {
            
            $xmlStr = $this->utilFunc->after($match[0], $xmlStr);
            $tempFile = $match[1];

            $refer = $this->utilFunc->parseXmlValue('reference', $tempFile);
            $lastmodify = (new \DateTime($this->utilFunc->parseXmlValue('created_at', $tempFile)))->format('Y-m-d H:i:s');
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
    function getFinalStr($str) {
        $qrystr = trim($str);
        $hash = strtoupper($qrystr) . $this->saltKey;
        $hash = md5($hash);
        return $qrystr . '&hash=' . $hash;
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
&mailto:lt;customer_email>laurelodell@hotmail.comundefined</customer_email>undefined<customer_address>
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
?>