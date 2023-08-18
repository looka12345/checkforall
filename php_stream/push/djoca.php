<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
 * djoca.com - All rights reserved
 */

// include utility function
include '../com/octorate/setup.php';

// include reservation classes
use com\octorate\stream\common\PullReservation;
use com\octorate\stream\common\PullReservationRoom;
use com\octorate\stream\common\PullReservationDay;
use com\octorate\stream\common\PullReservationExtra;
use com\octorate\stream\utils\UtilFunc;

// create object of djoca push class
$djocaObj = new DjocaPush();

try {
    // receive xml from djoca .
    $reqXml = file_get_contents('php://input');
    //$reqXml = $djocaObj->tempXml(); //for testing
    $djocaObj->utilFunc->appendFile($djocaObj->logFile, date("Y-m-d H:i:s") . ":\nNotification:\n" . $reqXml . "\n\n\n");     
    if (!empty($reqXml)) {
        $djocaObj->saveReservation($reqXml);
    } else {
        throw new \Exception("Something went wrong, error receiving reservation xml!");
    }
} catch (Exception $ex) {
    // error in request
    echo $ex->getMessage();
}
exit;

class DjocaPush {

    public $utilFunc;
    protected $errors;
    protected $confXml;

    /**
     * create object of UtilFunc and database
     */
    public function __construct() {
        $this->utilFunc = new UtilFunc();
        $this->utilFunc->logs = \com\octorate\stream\common\Database::getLogs();
        $this->utilFunc->database = \com\octorate\stream\common\Database::getData();
        $this->utilFunc->sites_id = 310;
        @$myLogPath = $this->utilFunc->getLogPath();
        $this->logFile = @$myLogPath . 'Djoca_Push_LogFile_' . date('Y-m-d') . '.log';
    }

    /**
     * save reservation into database
     */
    public function saveReservation($reqXml) {
        if (empty($this->utilFunc->parseOneValue('accom_code', $reqXml))) {
            $errors = "<Error code=602>Accommodation not found</Error>";
        }
        if (empty($this->utilFunc->parseOneValue('hosting_type_code', $reqXml))) {
            $errors .= "<Error code=507>Hosting type not found</Error>";
        }
        if (empty($this->utilFunc->parseOneValue('rate_plan_code', $reqXml))) {
            $errors .= "<Error code=508>Rate plan type not found</Error>";
        }
        $refer = $this->utilFunc->parseOneValue('customer_bookingref', $reqXml);
        $propertyReference = $this->utilFunc->parseOneValue('accom_code', $reqXml);
        $lastmodify = (new \DateTime($this->utilFunc->parseOneValue('timestamp', $reqXml)))->format('Y-m-d H:i:s');
        $status = $this->utilFunc->parseOneValue('booking_status', $reqXml);
        $checkOut = (new \DateTime($this->utilFunc->parseOneValue('checkout', $reqXml)))->format('Y-m-d');
        // insert reservation xml in db
        $this->utilFunc->insertXml($refer, $status, $reqXml, $lastmodify, $propertyReference, $this->utilFunc->sites_id, $processed = 0, $checkOut);

        if (!empty($errors)) {
            $finalmsg = '<Errors>' . $errors . '</Errors>';
        } elseif (!empty($refer)) {
            $finalmsg = '<Success/>
			<RemoteReference>' . $refer . '</RemoteReference>';
        } else {
            $finalmsg = '<Errors><Error code=602>Failure for unknown reason</Error></Errors>';
        }
        echo $response = '<PushBookingResponse xmlns="http://webservice.adapter.channelmanager.othyssia.koedia.com/document/v0.1/schemas">' . $finalmsg . '</PushBookingResponse>';
    }

    /**
     * @return xml example for test purpose
     */
    public function tempXml() {
        return '<PushBooking timestamp="20210108153900" action="create" lang="fre" xmlns="http://webservice.adapter.channelmanager.othyssia.koedia.com/document/v0.1/schemas">
                    <Authentication channel_manager_code="OCTR4" keystore="DJOCA1" login="octoratedjoca754" password="NrnNF8452"/>
                    <Booking othyssia_bookingref="8481573827" customer_bookingref="1DSN0GUGZXC9" customer_agent_id="DJOCA_Oksana ESTEGASSY_icare" booking_status="OK" creation_date="2021-01-08" currency="EUR" payment_mode="CC" guarantee_mode="CC">
                      <Accommodation accom_code="BEAULIEUCF">Hôtel Beaulieu</Accommodation>
                      <Price>
                        <TotalPrice amount_after_tax="65.09" amount_before_tax="65.09"/>
                      </Price>
                      <HostingPlanBooked>
                        <HostingUnit checkin="2021-03-03" checkout="2021-03-04" hosting_type_code="CD2P">
                          <HostingLabel>CHAMBRE DOUBLE 2 PERSONNES</HostingLabel>
                          <Rates amount_after_tax="65.09" amount_before_tax="65.09" meal_plan_included="DJO_BB" rate_plan_code="E3">
                            <DailyRates>
                              <DailyRate amount_after_tax="65.088" amount_before_tax="65.088" date="2021-03-03"/>
                            </DailyRates>
                            <MealPlanName>Petit-déjeuner</MealPlanName>
                            <Taxes>
                              <Tax code="ALL" amount="0.0"/>
                            </Taxes>
                          </Rates>
                          <PaxList nb_cot="0">
                            <Pax title="Mr" is_roomleader="true" type_of_pax="adult" ota_age_qualifying_code="10">
                              <Lastname>Test</Lastname>
                              <Firstname>Djoca</Firstname>
                            </Pax>
                            <Pax title="Mr" is_roomleader="false" type_of_pax="adult" ota_age_qualifying_code="10">
                              <Lastname>Test</Lastname>
                              <Firstname>Djoca un</Firstname>
                            </Pax>
                          </PaxList>
                        </HostingUnit>
                      </HostingPlanBooked>
                      <CustomerInformation>
                        <Contact>
                          <Name>Djoca Test</Name>
                        </Contact>
                        <CreditCardDetails for_payment="true" for_guarantee="true">
                          <CreditCard type="" number="" expiryyear="" expirymonth="" passcode="413"/>
                          <CardHolder><\/CardHolder>
                        </CreditCardDetails>
                      </CustomerInformation>
                    </Booking>
                  </PushBooking>';
    }

}
