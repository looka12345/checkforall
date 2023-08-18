<?php

/*
 * CtripPull.php
 * Octorate srl. All rights reserved. 2019
 */

namespace com\octorate\stream\pull;

use com\octorate\stream\common\AbstractPullStream;
use com\octorate\stream\common\PullReservation;
use com\octorate\stream\common\PullReservationRoom;
use com\octorate\stream\common\PullReservationDay;
use com\octorate\stream\common\PullReservationExtra;
use com\octorate\stream\utils\UtilFunc;

Class HooperPull extends AbstractPullStream {

    /**
     * Pull reservations from external site without selecting by property id.
     * @return array of PullReservation.
     */
    public function pullGlobal() {

        $this->utilFunc = new UtilFunc();
        $this->allRes = [];
        //echo "hello";
        //echo "\$this->allRes";
        if ($this->checkUnprocessedBooking(2)) {
            //echo "data=>";
           // print_r($this->pendingResaArr);
            foreach ($this->pendingResaArr as $refer => $value) {
                $dataArr = json_decode($value['xml']);
                $dataArrNew = json_decode(json_encode($dataArr), true);
                // print_r($value['xml']);
                //echo "dataArrNew=>";
                //print_r($dataArrNew);
                // exit;
                //$dataArr=array();
                $lastmodify = (new \DateTime($value['lastmodify_time']))->format('Y-m-d H:i:s');
                // echo "data of reser=>";
                //echo "debug1\n";
                //print_r($dataArr);
                //print($dataArrNew['checkIn']);              
                //echo $value['status'];               
                //exit;
                if ($value['status'] == 'cancel') {
                    //echo "inside if\n";
//                    exit;
                     $this->allRes[] = $this->retrieveCancellation($refer, $lastmodify);
                } else {
                   // echo "inside elseif\n";

                    //echo "\$this->retrieveReservation($dataArrNew, $refer, $lastmodify, $value['status'])";
                    // exit;
                    $this->allRes[] = $this->retrieveReservation($dataArrNew, $refer, $lastmodify, $value['status']);
                }
                if (!$this->test) {
//                    $this->markAsProcessedBooking($refer);
                }
            }
        }
        //if (count($this->allRes) > 0) {
        //$this->insertResaLog(json_encode($this->allRes));
        //}
        if ($this->test) {
            echo "allRes=>";
            print_r($this->allRes);
        }
        // return array with all reservations found
        // header('X-Pull-Version: 2');
        return $this->allRes;
    }

    public function retrieveReservation($result, $refer, $lastmodify, $status) {
        
        // success, parse response and create reservations
        $res = new PullReservation();
        $res->refer = $refer;
        //print("\ndebugres\n");
        //print_r($res->refer);

        $res->status = PullReservation::CONFIRMED;
        if ($status == 'CONFIRMED') {
            $res->createDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
            $res->updateDate = (new \DateTime('2000-01-01 00:00:00'))->format(DATE_ATOM);
        } else {
            $res->updateDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
            $res->createDate = NULL;
        }
        $res->creditCard->token = isset($result['paymentDetails']['cardNumber']) ? $result['paymentDetails']['cardNumber'] : NULL;
        //print("debugging10");
        // print($res->creditCard->token);

        foreach ($result['guests'] as $guests) {
            $res->guest->firstName = isset($guests['firstName']) ? substr($guests['firstName'], 0, 40) : NULL;
            $res->guest->lastName = isset($guests['lastName']) ? substr($guests['lastName'], 0, 40) : NULL;
            $res->guest->email = isset($guests['email']) ? $guests['email'] : NULL;
            $res->guest->phone = isset($guests['phone']) ? $guests['phone'] : NULL;
            $res->guest->address = isset($guests['address']) ? $guests['address'] : NULL;
            $res->guest->ageCategory = isset($guests['ageCategory']) ? $guests['ageCategory'] : NULL;
        }
        //echo "helll";
        //print_r($result['guests']);
        $res->guest->city = NULL;
        $res->guest->country = NULL;
        $res->guest->zip = NULL;
        $res->language = NULL;
        $res->currency = $result['reservationTotal']['currency'];
        //print($res);
        // create room data
        $checkIn = (new \DateTime($result['stayRange']['checkIn']))->format('Y-m-d');
        $checkOut = (new \DateTime($result['stayRange']['checkOut']))->format('Y-m-d');
        //$night = $this->utilFunc->dateDiff($checkIn, $checkOut);
        $adults = $result['occupancy']['adults'];
        $children = $result['occupancy']['children'];
        //print_r($adults);
        $total = $result['reservationTotal']['amountAfterTax'];

        foreach ($result['roomRates'] as $roomnew) {
            $roomid = $roomnew['roomTypeId'];
            $roomtype = $roomnew['ratePlanCode'];
            $date = new \DateTime($roomnew['date']);
        }

        $room = new PullReservationRoom($roomid . ':' . $roomtype);
        //print("echo debug");
        //print_r((array) $room);
        $room->checkIn = (new \DateTime($checkIn))->format('Y-m-d');
        $room->checkOut = (new \DateTime($checkOut))->format('Y-m-d');
        $room->notes = NULL;
        $room->paidNotes = NULL;
        $priceArr = $result['roomRates'];
        $d1 = $room->checkIn;
        //$total = 0;
        foreach ($priceArr as $value) {
            $price = $value;
            $room->daily[] = new PullReservationDay($date, round($price['amountAfterTax'], 2), true);
            $d1 = $this->utilFunc->dateAdd($d1, 1);
            // $total += $price;
        }
        $room->adults = $adults;       
        $room->children = $children;
        $pax = $adults + $children;
        $room->pax = $pax;
        $room->total = round($total, 2);
        $room->totalPaid = NULL;

        $voucherJson = $this->utilFunc->createVoucherV2($res, $room);

        $voucherJson['reservationTotal']['amountBeforeTax'] = isset($result['reservationTotal']['amountBeforeTax']) ? $result['reservationTotal']['amountBeforeTax'] : '';
        $voucherJson['paymentDetails']['cardholderName'] = isset($result['paymentDetails']['cardholderName']) ? $result['paymentDetails']['cardholderName'] : '';
        $voucherJson['paymentDetails']['expiryDate'] = isset($result['paymentDetails']['expiryDate']) ? $result['paymentDetails']['expiryDate'] : '';
        $voucherJson['cancellationPolicy']['cancellationPolicyID'] = isset($result['cancellationPolicy']['cancellationPolicyID']) ? $result['cancellationPolicy']['cancellationPolicyID'] : '';
        $voucherJson['cancellationPolicy']['daysBeforeCheckIn'] = isset($result['cancellationPolicy']['daysBeforeCheckIn']) ? $result['cancellationPolicy']['daysBeforeCheckIn'] : '';
        $voucherJson['cancellationPolicy']['hoursBeforeCheckIn'] = isset($result['cancellationPolicy']['hoursBeforeCheckIn']) ? $result['cancellationPolicy']['hoursBeforeCheckIn'] : '';
        $voucherJson['cancellationPolicy']['penalty'] = isset($result['cancellationPolicy']['penalty']) ? $result['cancellationPolicy']['penalty'] : '';

        $room->json = $voucherJson;
        $res->rooms[] = $room;

        return $res;
    }

    public function retrieveCancellation($refer, $lastmodify) {
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CANCELLED;
        //$res->propertyReference = $propertyReference;
        $res->createDate = NULL;
        $res->updateDate = (new \DateTime($lastmodify))->format(DATE_ATOM);
        // return single reservations object
        return $res;
    }

    /**
     * @return xml example for test purpose
     */
    public function tempXml() {
        return '{

   "reservationId":"RES123",

   "hotelId":"WDJR4GB",

   "stayRange":{


      "checkIn":"2022-11-10",

      "checkOut":"2022-11-12"

   },

   "occupancy":{


      "adults":2,

      "children":0,

      "childrenAges":[

         0

      ]

   },

   "reservationTotal":{


      "currency":"USD",

      "amountBeforeTax":240,

      "amountAfterTax":280

   },

   "paymentDetails":{


      "cardNumber":"123456789876654333",

      "cardholderName":"Jane Doe",

      "expiryDate":"05/25"

   },

   "guests":[

      {

         "firstName":"Jane",

         "lastName":"Doe",

         "email":"janedoe@mail.com",

         "phone":"123456789",

         "address":"8752 Durham Road Ridgewood, NJ 07450",

         "ageCategory":"Adult"

      }

   ],

   "roomRates":[

      {

         "date":"2022-11-10",

         "roomTypeId":"DBL",

         "ratePlanCode":"BAR",

         "currency":"USD",

         "amountBeforeTax":120,

         "amountAfterTax":140

      },

      {

         "date":"2022-11-11",

         "roomTypeId":"DBL",

         "ratePlanCode":"BAR",

         "currency":"USD",

         "amountBeforeTax":120,

         "amountAfterTax":140

      }

   ],

   "cancellationPolicy":{


      "cancellationPolicyID":"RP1CP1",

      "hoursBeforeCheckIn":24,

      "daysBeforeCheckIn":1,

      "penalty":"FirstNight"

   }

}';
    }

    public function pullReservations() {
        
    }

    //"localhost/php_stream/push/hooper.php?status=$obr->req&reservationId=$obr->res";
    //"localhost/php_stream/pull.php?gid=34445";
}
