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

include 'D:\xampp\htdocs\xampp\Netbeansprojects\php_stream\com\octorate\stream\common\AbstractPullStream.php';
include 'D:\xampp\htdocs\xampp\Netbeansprojects\php_stream\com\octorate\stream\utils\UtilFunc.php';


class NewonePull{

    public $url='';
    
   public function getReservations() {
        $d1 = new \DateTime();
        $d1->sub( new \DateInterval( 'P1D' ) );
        $d2 = new \DateTime();
        $result = '';
        $username='';
        $password='';
        
        $xml ='<?xml version="1.0" encoding="UTF-8"?>
            <HotelSearchRQ version="1">
            <credentials>
            <username>'.$username.'</username>
            <password>'.$password.'</password>
            </credentials>
        </HotelSearchRQ> ';
                
        $curlHeaders = [
            'Content-type: text/xml',
            'Content-length: '.strlen($xml1),
            'Connection: close' ];

        $result = $this->utilFunc->submitXmlPost( $this->url, $xml,$curlHeaders);
        $result = preg_replace( '/\&lt\;/is', '<', $result );
        $result = preg_replace( '/\&gt\;/is', '>', $result );
        $result = preg_replace( '/\&quot\;/is', '"', $result );
        $this->insertResaLog( $xml . '------' . $result );

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
    }

//Ping (PingRQ)//
      
function handlePingRequest()
{
    
    $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <PingRQ>
            <Message>Ping</Message>
            </PingRQ>';
    
    $xml = simplexml_load_string($requestXml);

    // Get the message from the request
    $message = (string) $xml->Message;

   $responseXml=$this->utilFunc->submitXmlPost( $this->url, $xml,[]);
    


    // Check if the message is "Ping"
      if ($message === "Ping") {
        // If the message is "Ping", respond with "Pong"
      $responseXml=preg_replace( '/<Response>Ping<\/Response>/is','<Response>Pong</Response>',$responseXml);

  
    } else {
        // If the message is not "Ping", respond with the same message
     $responseXml=preg_replace( '/<Response>Pong<\/Response>/is', '<Response>'.$message.'</Response>', $responseXml );

    }

 

    return  $responseXml;
}
//Getting Hotels (OTA_HotelSearchRQ)///////

public function gethotels(){
 $username='';
 $password='';
$requestXML = '<?xml version="1.0" encoding="UTF-8" ?>
        <HotelSearchRQ version="1">
            <credentials>
                <username>'.$usrname.'</username>
                <password>'.$password.'</password>
            </credentials>
        </HotelSearchRQ>';
    
    // Send the request to the API  endpoint
   $response=$this->utilFunc->submitXmlPost( $this->url, $requestXML ,[]);

    
    // Parse the response XML
    $hotels = [];
    $xml = simplexml_load_string($response);
    
    foreach ($xml->Properties->Property as $property) {
        $hotel = [
            'hotelCode' => (string)$property['hotelCode'],
            'name' => (string)$property->name,
            'stars' => (int)$property->stars,
            'address' => [
                'city' => (string)$property->address['city'],
                'addressLine' => (string)$property->address['AddressLine'],
                'cp' => (string)$property->address['cp'],
                'latitude' => (float)$property->address['latitude'],
                'longitude' => (float)$property->address['longitude']
            ]
        ];
        
        $hotels[] = $hotel;
    }
    
    // Return the list of hotels
    return $hotels;
}
 
//Getting hotel products (OTA_HotelProductRQ)

public function GethotelProducts(){
    $usernam='';
    $password='';
    $hotelcode='';
    // Construct the request XML
    $requestXML = '<?xml version="1.0" encoding="UTF-8"?>
        <HotelProductRQ>
            <credentials>
                <username>'.$usernam.'</username>
                <password>'.$password.'</password>
            </credentials>
            <HotelProducts>
                <HotelProduct hotelCode="' . $hotelCode . '">
                    <roomTypes />
                    <mealPlans />
                    <ratePlans />
                </HotelProduct>
            </HotelProducts>
        </HotelProductRQ>';
    
    // Send the request to the API  endpoint
    $response = $this->utilFunc->submitXmlPost( $this->url, $requestXML ,[]);
    
    // Parse the response XML
    $hotelProduct = [];
    $xml = simplexml_load_string($response);
    
    $hotelProduct = [
        'hotelCode' => (string)$xml->HotelProduct['hotelCode'],
        'mealPlans' => [],
        'ratePlans' => [],
        'roomTypes' => []
    ];
    
    // Parse meal plans
    foreach ($xml->HotelProduct->mealPlans->mealPlan as $mealPlan) {
        $mealPlanData = [
            'id' => (string)$mealPlan['id'],
            'name' => (string)$mealPlan['name']
        ];
        
        $hotelProduct['mealPlans'][] = $mealPlanData;
    }
    
    // Parse rate plans
    foreach ($xml->HotelProduct->ratePlans->ratePlan as $ratePlan) {
        $ratePlanData = [
            'id' => (string)$ratePlan['id'],
            'name' => (string)$ratePlan['name']
        ];
        
        $hotelProduct['ratePlans'][] = $ratePlanData;
    }
    
    // Parse room types
    foreach ($xml->HotelProduct->roomTypes->roomType as $roomType) {
        $roomTypeData = [
            'id' => (string)$roomType['id'],
            'name' => (string)$roomType['name'],
            'type' => (int)$roomType['type'],
            'adults' => (int)$roomType['adults'],
            'children' => (int)$roomType['children'],
            'juniors' => (int)$roomType['juniors'],
            'minPersons' => (int)$roomType['minPersons'],
            'maxPersons' => (int)$roomType['maxPersons']
        ];
        
        $hotelProduct['roomTypes'][] = $roomTypeData;
    }
    
    // Return the hotel products
    return $hotelProduct;    
}


//Getting availability (OTA_HotelRoomListAvailabilityRQ)
 

public function GetRoomListAvailability() {
    $username='';
    $password ='';
    // Construct the request XML
    $requestXML = '<HotelRoomListAvailabilityRQ>
	<credentials>
    	        <username>'.$username.'</username>
            	       <password>'.$password.'</password>
	</credentials>
	<AvailRequestSegments>
    	          <AvailRequestSegment hotelCode="192" ratePlan="37" roomCode="1" >
        	                  <dateRange start="2016-01-01" end="2016-01-01" />
    	          </AvailRequestSegment>
	</AvailRequestSegments>
</HotelRoomListAvailabilityRQ> ';
    
    // Send the request to the API  endpoint
      $response = $this->utilFunc->submitXmlPost( $this->url, $requestXML ,[]);
      
    // Parse the response XML
//    $roomAvailability = [];
    $xml = simplexml_load_string($response);
    
    $roomsList = [
        'hotelCode' => (string)$xml->roomsList['hotelCode'],
        'ratePlanCode' => (string)$xml->roomsList['ratePlanCode'],
        'rooms' => []
    ];
    
    // Parse room availability
    foreach ($xml->roomsList->rooms->room as $room) {
        $roomData = [
         'roomCode' => (string)$room['roomCode'],
            'noCheckin' => (string)$room['noCheckIn'],
            'noCheckout' => (string)$room['noCheckOut'],
            'available' => (int)$room['available'],
            'closed' => (int)$room['closed'],
            'mealPlanCode' => (string)$room['mealPlanCode'],
            'date' => (string)$room['date']
       ];
        
        $roomsList['rooms'][] = $roomData;
    }
    
    // Return the room availabilityn  
    return $roomsList;
}
function GetRoomListPrices() {
    $username='';
    $password='';
    // Construct the request XML
    $requestXML = '<?xml version="1.0" encoding="UTF-8"?>
<HotelRoomListPricesRQ>
	<credentials>
    	        <username>'.$username.'</username>
            	       <password>'.$password.'</password>
	</credentials>
	<AvailRequestSegments>
    	          <AvailRequestSegment hotelCode="192" ratePlan="37" roomCode="3A2C1">
        	                  <dateRange start="2016-01-01" end="2016-01-01" />
    	          </AvailRequestSegment>
	</AvailRequestSegments>
</HotelRoomListPricesRQ> 
';
    
    // Send the request to the API endpoint
    $response = $this->utilFunc->submitXmlPost( $this->url, $requestXML ,[]);
      
    
    // Parse the response XML
//    $roomPrices = [];
    $xml = simplexml_load_string($response);
    
    $pricesList = [
        'hotelCode' => (string)$xml->pricesList['hotelCode'],
        'ratePlanCode' => (string)$xml->pricesList['ratePlanCode'],
        'prices' => []
    ];
    
    // Parse room prices
    foreach ($xml->pricesList->prices as $prices) {
        $mealPlanCode = (string)$prices['mealPlanCode'];
        
        foreach ($prices->price as $price) {
            $priceData = [
                'price' => (float)$price['price'],
                'date' => (string)$price['date'],
                'roomCode' => (string)$price['roomCode'],
                'type' => (string)$price['type'],
                'adults' => (int)$price['adults'],
                'children' => (int)$price['children'],
                'juniors' => (int)$price['juniors'],
                'minNights' => (int)$price['minNights'],
                'daysNotice' => (int)$price['daysNotice'],
                'occupation' => (int)$price['occupation']
            ];
            
            $pricesList['prices'][$mealPlanCode][] = $priceData;
        }
    }
    
    // Return the room prices
    return $pricesList;
}
//Hotel Availability Notify Request (OTA_HotelAvailNotifRQ)

//XML (1 - Update availability using roomCode)

function updateAvailabilityByRoomCode() {
    // Construct the request XML
    $requestXML = '<?xml version="1.0" encoding="UTF-8"?>
<HotelAvailNotifRQ>
    <credentials>
   	 <username>test</username>
   	 <password>test</password>
    </credentials>
    <AvailStatusMessages>
   	 <AvailStatusMessage hotelCode="192">
   		 <availStatus BookingLimit="100" roomCode="2A2C0" start="2015-12-22"  end="2015-12-22" ratePlan="20" noCheckIn="1"  noCheckOut="1" closed=”0” mealPlanCode="2"/>
   	 </AvailStatusMessage>
    </AvailStatusMessages>
</HotelAvailNotifRQ>
';
    
    // Send the request to the API endpoint
    $response =$this->utilFunc->submitXmlPost( $this->url, $requestXML ,[]);

    
    // Parse the response XML
    $xml = simplexml_load_string($response);
    
    // Check if the update was successful
    $success = isset($xml->success);
    
    // Return the success status
    
    if ($success) {
    echo "Availability updated successfully using roomCode.\n";
    return $success;
} else {
    echo "Failed to update availability using roomCode.\n";
}

}

//XML  (2 - Update availability by occupation)

function updateAvailabilityByOccupation() {
    // Construct the request XML
    $requestXML = '<?xml version="1.0" encoding="UTF-8"?>
<HotelAvailNotifRQ>
    <credentials>
   	 <username>test</username>
   	 <password>test</password>
    </credentials>
    <AvailStatusMessages>
   	 <AvailStatusMessage hotelCode="192">
   		 <availStatus BookingLimit="100" roomType="2" roomAdults="2"  start="2015-12-22"  end="2015-12-22" ratePlan="20" noCheckIn="1"  noCheckOut="1" closed=”0” mealPlanCode="2"/>
   	 </AvailStatusMessage>
    </AvailStatusMessages>
</HotelAvailNotifRQ>
';
    
    // Send the request to the APi endpoint
    $response = $this->utilFunc->submitXmlPost( $this->url, $requestXML ,[]);

    
    // Parse the response XML
    $xml = simplexml_load_string($response);
    
    // Check if the update was successful
    $success = isset($xml->success);
    
    // Return the success status
    if ($success) {
    echo "Availability updated successfully by occupation.\n";
    return $success;
} else {
    echo "Failed to update availability by occupation.\n";
}
    
}
//Hotel Availability Price Notify Request (OTA_HotelAvailPriceNotifRQ)
//XML (1 - Update price using roomCode)
function updatePriceByRoomCode() {
    // Construct the request XML
    $requestXML = '<?xml version="1.0" encoding="UTF-8"?>
<HotelAvailPriceNotifRQ>
    <credentials>
   	 <username>test</username>
   	 <password>test</password>
    </credentials>
    <rules mealPlanCode="2" ratePlanCode="29" hotelCode="192">
   	 <rule roomCode="10A2C0" price="99.00" minNights="1" maxNights="0" daysNotice="1" start="2016-10-24" end="2016-10-25" occupation="2"/>
    </rules>
</HotelAvailPriceNotifRQ>
';
    
    // Send the request to the API endpoint
    $response = $this->utilFunc->submitXmlPost( $this->url, $requestXML ,[]);

    
    // Parse the response XML
    $xml = simplexml_load_string($response);
    
    // Check if the update was successful
    $success = isset($xml->success);
    
    // Return the success status
      if($success){
    echo 'Price updated successfully using roomCode.\n';    
    return $success;
    }
    else{
    echo 'Failed to update price using roomCode.\n' ;   
    }
}
//XML Example (Update price by occupation)

function updatePriceByOccupation() {
    // Construct the request XML
    $requestXML = '<?xml version="1.0" encoding="UTF-8"?>
<HotelAvailPriceNotifRQ>
    <credentials>
   	 <username>test</username>
   	 <password>test</password>
    </credentials>
    <rules mealPlanCode="2" ratePlanCode="29" hotelCode="192">
   	 <rule roomCode="10A2C0" roomType="10" roomAdults="2" price="99.00" minNights="1"   maxNights="0" daysNotice="1" start="2016-10-24" end="2016-10-25" occupation="2"/>
    </rules>
</HotelAvailPriceNotifRQ>
';
    
    // Send the request to the API endpoint
    $response = $this->utilFunc->submitXmlPost( $this->url, $requestXML ,[]);

    
    // Parse the response XML
    $xml = simplexml_load_string($response);
    
    // Check if the update was successful
    $success = isset($xml->success);
    
    // Return the success status
    if($success){
    echo 'Price updated successfully using occupation.\n';    
    return $success;
    }
    else{
    echo 'Failed to update price using occupation.\n' ;   
    }
}

public function BookingDownloadRequest(){
    
}
public function BookingDownloadNotifRequest(){
    
}

}
//$nn = new NewonePull();
//$mm =$nn->Ping_PingRQ();
//print_r($mm);