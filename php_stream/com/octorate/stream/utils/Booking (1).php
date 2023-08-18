
<?php
   $xml_booking = '<?xml version="1.0"?>
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
               $xml = simplexml_load_string($xml_booking);
               $response = json_decode( json_encode( $xml ), TRUE );
               echo "<pre>";
              // print_r($response);
               echo "</pre>";
                        
    $bookings = $response[ 'bookings' ][ 'booking' ];


    echo '<pre>';
    //print_r( $bookings );
    

    $i=0;
    $booking = $bookings[ '@attributes'];

    foreach ( $booking as $bookingkey => $bookingvalue ) {
    $bookingData[$bookingkey] = $bookingvalue;
    $i++;
    }
        
    $mealPlan = [];
    $booking = $bookings[ 'mealPlans']['mealPlan'];

    foreach ( $booking['@attributes'] as $key => $value ) {

    $bookingData[$key] = $value;
    $i++;
    }
    print_r( $bookingData );

    $rooms = [];
    $booking = $bookings[ 'rooms'];

    foreach ( $booking['@attributes'] as $key => $value ) {

    $rooms[$key] = $value;
    }
      
    $k=0;
    $roomData= [];
      
    $booking = $bookings[ 'rooms']['room'];
    foreach ($booking as $key1 => $value1) {
    $roomData[$k]=$value1;
    $k++;
    }
            
    $room1 = [];
    $room2 = [];
    $room3 = [];
    $roomData1 = $roomData[0];
    foreach ($roomData1['@attributes'] as $key => $value){
        $room1 [$key] = $value;
    }

    $n=0;
    foreach ($roomData1['childs']['child'] as $key => $value){
        $room2[$n] = $value;
        $n++;
    }

    $room2Data1 = $room2[0];
    $child1Age=[];
    foreach ($room2Data1['@attributes'] as $key => $value){

        $child1Age[$key] = $value;
    }

    $room2Data2 = $room2[1];
    $child2Age=[];
    foreach ($room2Data2['@attributes'] as $key => $value){
        $child2Age[$key] = $value;
     }
    foreach ($roomData1['dailyPrices']['dayPrice'] as $key => $value){
       // $room [$key] = $value;
        foreach ($value as $key => $value) {
            $room3 [$key] = $value;
        }
    }
    print_r( $room1);
    print_r( $child1Age);
    print_r( $child2Age);
    print_r( $room3);

    $room4 = [];
    $room5 = [];
    $roomData1 = $roomData[1];
    foreach ($roomData1['@attributes'] as $key => $value){
        $room4 [$key] = $value;
    }
    foreach ($roomData1['dailyPrices']['dayPrice'] as $key => $value){
       // $room [$key] = $value;
        foreach ($value as $key => $value) {
            $room5 [$key] = $value;
        }
    }
    print_r( $room4);
    print_r( $room5);

    $j=0;
    $name = [];
    $names = [];
    $booking = $bookings['rooms'][ 'occupancy'];
    //print_r( $booking);
    foreach ($booking['person'] as $k => $v) {
    foreach ($v['@attributes'] as $key => $value) {
        $names[$j] = $value;
        $j++;
    }
    }
    $name["name"] = $names;
    print_r($name);

    $remark = [];
    $booking = $bookings[ 'remarks'];
    print_r($booking);
   
 ?>