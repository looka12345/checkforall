<?php

/*
 * DjocaPull.php
 * Octorate srl. All rights reserved. 2019
 */

namespace com\octorate\stream\pull;

use com\octorate\stream\common\AbstractPullStream;
use com\octorate\stream\common\PullReservation;
use com\octorate\stream\common\PullReservationRoom;
use com\octorate\stream\common\PullReservationDay;
use com\octorate\stream\common\PullReservationExtra;
use com\octorate\stream\utils\UtilFunc;

class DjocaPull extends AbstractPullStream {

    /**
     * Pull reservations from external site without selecting by property id.
     * @return array of PullReservation.
     */
    public function pullGlobal() {
        $this->utilFunc = new UtilFunc();
        $this->allRes = [];
        if ($this->checkUnprocessedBooking(2)) {
            foreach ($this->pendingResaArr as $refer => $value) {
                $lastmodify = (new \DateTime($value['lastmodify_time']))->format('Y-m-d H:i:s');
                if (strtolower($value['status']) == 'ok') {
                    $this->allRes[] = $this->retrieveReservation($value['xml'], $refer, $value['property_id'], $lastmodify, $value['status']);
                } elseif (strtolower($value['status']) == 'cx') {
                    $this->allRes[] = $this->retrieveCancellation($refer, $value['property_id'], $lastmodify);
                }
                if (!$this->test) {
                    $this->markAsProcessedBooking($refer);
                }
            }
        }
        //if (count($this->allRes) > 0) {
            //$this->insertResaLog(json_encode($this->allRes));
        //}

        if ($this->test) {
            echo "allRes=>";
            print_r($this->allRes);
            exit;
        }
        // return array with all reservations found
        header('X-Pull-Version: 2');
        return $this->allRes;
    }

    /**
     * @return array of retrieveReservation
     */
    public function retrieveReservation($resultFile, $refer, $propertyReference, $lastmodify, $status) {
        // success, parse response and create reservations
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CONFIRMED;
        $res->force = \TRUE;
        if (strtolower($status) == 'ok') {
            $res->createDate = (new \DateTime($this->utilFunc->parseOneValue('creation_date', $resultFile)))->format(DATE_ATOM);
            $res->updateDate = (new \DateTime('2000-01-01 00:00:00'))->format(DATE_ATOM);
        } else {
            $res->updateDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
            $res->createDate = NULL;
        }
        $res->creditCard->token = $this->utilFunc->parseOneValue('ccs_token', $resultFile);
        $res->propertyReference = $propertyReference;
        $res->guest->email = $this->utilFunc->parseXmlValue('Email', $resultFile);
        $res->guest->phone = $this->utilFunc->parseXmlValue('Phone', $resultFile);
        list($firstname, $surname) = explode(' ', $this->utilFunc->parseXmlValue('Name', $resultFile));
        $res->guest->firstName = substr($firstname, 0, 40);
        $res->guest->lastName = substr($surname, 0, 40);
        $res->guest->address = $this->utilFunc->parseXmlValue('AddressLine1', $resultFile);
        $res->guest->city = $this->utilFunc->parseXmlValue('CityName', $resultFile);
        $res->guest->country = $this->utilFunc->parseXmlValue('CountryName', $resultFile);
        $res->guest->zip = $this->utilFunc->parseXmlValue('Zip', $resultFile);
        $res->language = strtoupper($this->utilFunc->parseOneValue('lang', $resultFile));
        $res->currency = $this->utilFunc->parseOneValue('currency', $resultFile);
        if ($res->creditCard->token) {
            $res->paymentMode = PullReservation::PAYMENT_CREDITCARD;
        }

        // create room data
        if (preg_match("/<HostingUnit.*?<\/HostingUnit>/is", $resultFile, $match)) {
            $tempFile = $resultFile;
            $checkIn = (new \DateTime($this->utilFunc->parseOneValue('checkin', $tempFile)))->format('Y-m-d');
            $checkOut = (new \DateTime($this->utilFunc->parseOneValue('checkout', $tempFile)))->format('Y-m-d');
            while (preg_match("/<HostingUnit(.*?)<\/HostingUnit>/is", $tempFile, $match)) {
                $tempFile = $this->utilFunc->after($match[0], $tempFile);
                $roomXml = $match[1];
                $dayXml = $roomXml;
                $paxXml = $roomXml;
                $room = new PullReservationRoom($this->utilFunc->parseOneValue('hosting_type_code', $roomXml) . ':' . $this->utilFunc->parseOneValue('rate_plan_code', $roomXml));
                while (preg_match("/<DailyRate(.*?)\/>/is", $dayXml, $match)) {
                    $dayData = $match[0];
                    $dayXml = $this->utilFunc->after($match[0], $dayXml);
                    $price = $this->utilFunc->parseOneValue('amount_after_tax', $match[1]);
                    $date = new \DateTime($this->utilFunc->parseOneValue('date', $match[1]));
                    $room->daily[] = new PullReservationDay($date->format('Y-m-d'), round($price, 2), true);
                }
                list($paxTable, $pax) = explode('@@', $this->parsePax($resultFile));
                $room->children = NULL;
                $room->pax = (!empty($pax) ? $pax : NULL);
                $room->total = round($this->utilFunc->parseOneValue('TotalPrice amount_after_tax', $resultFile), 2);
                $room->taxIncluded = TRUE;
                $room->totalPaid = NULL;
                $room->checkIn = $checkIn;
                $room->checkOut = $checkOut;
                $room->notes = NULL;
                $room->paidNotes = NULL;

                $voucherJson = $this->utilFunc->createVoucherV2($res, $room);
                $voucherJson['Taxes'] = $this->parseTaxes($resultFile);
                $voucherJson['Pax'] = $paxTable;
                $voucherJson['Othyssia_Bookingref'] = $this->utilFunc->parseOneValue('othyssia_bookingref', $resultFile);
                $voucherJson['Customer_Agent_Id'] = $this->utilFunc->parseOneValue('customer_agent_id', $resultFile);
                $voucherJson['Payment_Mode'] = $this->utilFunc->parseOneValue('payment_mode', $resultFile);
                $voucherJson['Guarantee_Mode'] = $this->utilFunc->parseOneValue('guarantee_mode', $resultFile);
                $voucherJson['Accommodation'] = $this->utilFunc->parseXmlValue('Accommodation', $resultFile);
                $voucherJson['Accommodation_Code'] = $this->utilFunc->parseOneValue('Accommodation accom_code', $resultFile);
                $voucherJson['Hosting_Type_Code'] = $this->utilFunc->parseOneValue('hosting_type_code', $resultFile);
                $voucherJson['HostingLabel'] = $this->utilFunc->parseXmlValue('HostingLabel', $resultFile);
                $voucherJson['Meal_Plan_Included'] = $this->utilFunc->parseOneValue('meal_plan_included', $resultFile);
                $voucherJson['MealPlanName'] = $this->utilFunc->parseXmlValue('MealPlanName', $resultFile);
                $voucherJson['For_Payment'] = $this->utilFunc->parseOneValue('for_payment', $resultFile);
                $voucherJson['For_Guarantee'] = $this->utilFunc->parseOneValue('for_guarantee', $resultFile);

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
    public function retrieveCancellation($refer, $propertyReference, $lastmodify) {
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CANCELLED;
        $res->propertyReference = $propertyReference;
        $res->createDate = NULL;
        $res->updateDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
        // return single reservations object
        return $res;
    }

    /**
     * @return table of Taxes
     */
    public function parseTaxes($res) {
        $table = '';
        if (preg_match('/<Taxes.*?<\/Taxes>/is', $res, $match)) {
            $taxes = $res;
            $table = '<table border="1" width="50%">';
            $table .= '<tr>';
            $table .= '<td align="center">Amount</td>';
            $table .= '<td align="center">Code</td>';
            $table .= '</tr>';
            while (preg_match('/<Tax(.*?)\/>/is', $taxes, $m)) {
                $taxes = $this->utilFunc->after($m[0], $taxes);
                $taxXml = $m[1];
                $amount = trim($this->utilFunc->parseOneValue('amount', $taxXml));
                $code = trim($this->utilFunc->parseOneValue('code', $taxXml));
                $table .= '<tr>';
                $table .= '<td align="center">' . $amount . '</td>';
                $table .= '<td align="center">' . $code . '</td>';
                $table .= '</tr>';
            }
        }
        $table .= '</table>';
        return $table;
    }

    /**
     * @return table of Pax
     */
    public function parsePax($res) {
        $table = '';
        $pax = 0;
        if (preg_match('/<\s*PaxList(.*?)<\s*\/PaxList>/is', $res, $match)) {
            $paxList = $match[1];
            $table = '<table border="1" width="50%">';
            $table .= '<tr>';
            $table .= '<td align="center">Title</td>';
            $table .= '<td align="center">Is Roomleader</td>';
            $table .= '<td align="center">Type Of Pax</td>';
            $table .= '<td align="center">Ota Age Qualifying Code</td>';
            $table .= '<td align="center">Firstname</td>';
            $table .= '<td align="center">Lastname</td>';
            $table .= '</tr>';
            while (preg_match('/<Pax(.*?)<\/Pax>/is', $paxList, $m)) {
                $paxList = $this->utilFunc->after($m[0], $paxList);
                $paxXml = $m[1];
                $title = trim($this->utilFunc->parseOneValue('title', $paxXml));
                $is_roomleader = trim($this->utilFunc->parseOneValue('is_roomleader', $paxXml));
                $type_of_pax = trim($this->utilFunc->parseOneValue('type_of_pax', $paxXml));
                $ota_age_qualifying_code = trim($this->utilFunc->parseOneValue('ota_age_qualifying_code', $paxXml));
                $firstname = trim($this->utilFunc->parseXmlValue('Firstname', $paxXml));
                $lastname = trim($this->utilFunc->parseXmlValue('Lastname', $paxXml));
                $table .= '<tr>';
                $table .= '<td align="center">' . $title . '</td>';
                $table .= '<td align="center">' . $is_roomleader . '</td>';
                $table .= '<td align="center">' . $type_of_pax . '</td>';
                $table .= '<td align="center">' . $ota_age_qualifying_code . '</td>';
                $table .= '<td align="center">' . $firstname . '</td>';
                $table .= '<td align="center">' . $lastname . '</td>';
                $table .= '</tr>';
                $pax++;
            }
        }
        $table .= '</table>';
        return $table . '@@' . $pax;
    }

    public function pullReservations(): array {
        return [];
    }

}
