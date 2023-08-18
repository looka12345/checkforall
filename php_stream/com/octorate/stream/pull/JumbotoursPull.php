<?php

/*
* JumbotoursPull.php
* Octorate srl. All rights reserved. 2019
*/




namespace com\octorate\stream\pull;
use com\octorate\stream\common\AbstractPullStream;
use com\octorate\stream\common\PullReservation;
use com\octorate\stream\common\PullReservationRoom;
use com\octorate\stream\common\PullReservationDay;
use com\octorate\stream\common\PullReservationExtra;
use com\octorate\stream\utils\UtilFunc;

//include 'D:\xampp\htdocs\xampp\Netbeansprojects\php_stream\com\octorate\stream\common\AbstractPullStream.php';
//include 'D:\xampp\htdocs\xampp\Netbeansprojects\php_stream\com\octorate\stream\utils\UtilFunc.php';



class  JumbotoursPull extends AbstractPullStream {

    /**
    * @return array of PullReservation
    */
 
    public function pullReservations() {
        $this->utilFunc = new UtilFunc();
        $this->allRes = [];
        $this->resaArr = [];
//        $this->url = 'https://wscontract.xtravelsystem.com/ws-contracts/ContractInsertionService';
      
        

        if ( $xmlStr = $this->tempXml() ) {
            print_r($xmlStr);
            $this->parseReservations( $xmlStr );
        }
  

//        if ( $this->checkUnprocessedBooking() ) {
//            foreach ( $this->pendingResaArr as $refer => $value ) {
//                if ( !( array_key_exists( $refer, $this->resaArr ) ) ) {
//                    $lastmodify = ( new \DateTime( $value[ 'lastmodify_time' ] ) )->format( 'Y-m-d H:i:s' );
//                    if ( $value[ 'status' ] == 'CANCELLED' && $value[ 'xml' ] != '' ) {
//                        $this->allRes[] = $this->retrieveCancellation( $refer, $lastmodify, true );
//                    } elseif ( $value[ 'status' ] == 'CLOSED' && $value[ 'xml' ] != '' ) {
//                        $this->allRes[] = $this->retrieveReservation( $value[ 'xml' ], $refer, $value[ 'status' ], $lastmodify, true );
//                    }
//                    if ( !( $this->test == true ) ) {
//                        $this->markAsProcessedBooking( $refer );
//                    }
//                }
//            }
//        }
      
            echo 'allRes=>';
            echo "<pre>";
            print_r( $this->allRes );
            echo "</pre>";
            exit;
        
        // return array with all reservations found
//        header( 'X-Pull-Version: 2' );
       
    }


    /**
    * @return xml of parse All bookings
    */

    public function parseReservations( $xmlStr ) {
        $filteredResa = array();
        while ( preg_match( '/(<ns2:service>.*?<\/ns2:service>)/is', $xmlStr, $match ) ) {
            //while ( preg_match( '/(<ns2:serviceV3>.*?<\/ns2:serviceV3>)/is', $xmlStr, $match ) ) {
            $xmlStr = $this->utilFunc->after( $match[ 0 ], $xmlStr );
            $tempFile = $match[ 1 ];
            $refer = $this->utilFunc->parseXmlValue( 'bookingLocator', $tempFile );
            $lastmodify = ( new \DateTime( $this->utilFunc->parseXmlValue( 'formalizationDate', $tempFile ) ) )->format( 'Y-m-d H:i:s' );
            if ( !array_key_exists( $refer, $filteredResa ) ) {
                $filteredResa[ $refer ] = $tempFile . '@@##' . $lastmodify;
            } else {
                list( $tmpFile, $lm ) = explode( '@@##', $filteredResa[ $refer ] );
                if ( strtotime( $lastmodify ) > strtotime( $lm ) ) {
                    $filteredResa[ $refer ] = $tempFile . '@@##' . $lastmodify;
                }
            }
        }
        if ( !empty( $filteredResa ) ) {
            foreach ( $filteredResa as $refer_id => $tmpf_lmd ) {
                list( $tmpf, $lmd ) = explode( '@@##', $tmpf_lmd );
                $status = $this->utilFunc->parseXmlValue( 'status', $tmpf );
              
                $propertyReference = $this->siteUser->hotel_id;
                $this->insertXml( $refer_id, $status, $tmpf, $lmd, $propertyReference );
                $this->resaArr[ $refer_id ] = $refer_id;
//                if ( $this->checkResaExistence( $refer_id ) ) {
//                    if ( $this->test == true ) {
//                        //echo "resa $refer_id already exists in last 1 day, continue\n";
//                    }
//                    continue;
//                }
                
                
                if ( $status == 'CANCELLED' ) {
                    $this->allRes[] = $this->retrieveCancellation( $refer_id, $lmd, false );
                } elseif ( $status == 'CLOSED' || 'CONFIRMED' ) {
                    
                    $this->allRes[] = $this->retrieveReservation( $tmpf, $refer_id, $status, $lmd, false );
                    
                }
            }
        }
    }

    /**
    * @return array of retrieveReservation
    */

    public function retrieveReservation( $resultFile, $refer, $status, $lastmodify, $forceFlg ) {
        // success, parse response and create reservations
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CONFIRMED;
        $res->language = NULL;
        if ( $forceFlg ) {
            $res->force = \TRUE;
        }
        if ( $status == 'CLOSED' || 'CONFIRMED' ) {
            $res->createDate = ( new \DateTime( $lastmodify ) )->format( DATE_ATOM );
            $res->updateDate = ( new \DateTime( '2000-01-01 00:00:00' ) )->format( DATE_ATOM );
        } else {
            $res->updateDate = ( new \DateTime( $lastmodify ) )->format( DATE_ATOM );
            $res->createDate = NULL;
        }
        $res->creditCard->token = NULL;
        $res->guest->email = NULL;
        $res->guest->phone = NULL;
        $firstName = '';
        $lastName = '';
        list( $firstName, $lastName ) = explode( ',', $this->utilFunc->parseXmlValue( 'paxName', $resultFile ) . ',' );
        if ( empty( $firstName ) && empty( $lastName ) ) {
            list( $firstName, $lastName ) = explode( ',', $this->utilFunc->parseXmlValue( 'leadPaxName', $resultFile ) . ',' );
        }
        $res->guest->firstName = substr( $firstName, 0, 40 );
        $res->guest->lastName = substr( $lastName, 0, 40 );
        $res->guest->address = NULL;
        $res->guest->city = $this->utilFunc->parseXmlValue( 'cityName', $resultFile );
        //$res->guest->country = strtoupper( $this->utilFunc->parseXmlValue( 'countryName', $resultFile ) );
        //not in valid format
        $res->guest->zip = NULL;
        $res->currency = $this->utilFunc->parseXmlValue( 'currencyCode', $resultFile );

        // create room data
        if ( preg_match( '/(<occupancies>.*?<\/occupancies>)/is', $resultFile, $match ) ) {
            $tempFile = $resultFile;
            $checkIn = ( new \DateTime( $this->utilFunc->parseXmlValue( 'from', $tempFile ) ) )->format( 'Y-m-d' );
            $checkOut = ( new \DateTime( $this->utilFunc->parseXmlValue( 'to', $tempFile ) ) )->format( 'Y-m-d' );
            $totalBuffer = $this->utilFunc->parseXmlValue( 'purchasePrice', $tempFile );
            $total = $totalBuffer;

            $numofRoom = 1;

            preg_match_all( '/<occupancies>/is', $resultFile, $m3 );
            $numofRoom = count( $m3[ 0 ] );
            if ( $numofRoom > 1 && $numofRoom < 100 ) {
                $total = $totalBuffer / $numofRoom;
            }
            while ( preg_match( '/(<occupancies>.*?<\/occupancies>)/is', $tempFile, $match ) ) {
                $tempFile = $this->utilFunc->after( $match[ 0 ], $tempFile );
                $roomXml = $match[ 1 ];
                $room = new PullReservationRoom( $this->utilFunc->parseXmlValue( 'roomTypeCode', $roomXml ) . ':' . $this->utilFunc->parseXmlValue( 'boardTypeCode', $roomXml ) );
                
                $dateArr = $this->utilFunc->getDatesFromRange( $checkIn, $checkOut );
                $dayNum = count( $dateArr );
                foreach ( $dateArr as $date ) {
                    $pricePerDay = round( ( $total / $dayNum ), 2 );
                    $room->daily[] = new PullReservationDay( $date, $pricePerDay, true );
                }
                $room->children = $this->utilFunc->parseXmlValue( 'children', $resultFile );
                $room->pax = $this->utilFunc->parseXmlValue( 'adults', $resultFile ) + $this->utilFunc->parseXmlValue( 'children', $resultFile );
                $room->total = round( $total, 2 );
                $room->taxIncluded = true;
                $room->totalPaid = NULL;
                $room->checkIn = $checkIn;
                $room->checkOut = $checkOut;
                $room->notes = $this->utilFunc->parseXmlValue( 'text', $this->utilFunc->parseXmlValue( 'erratas', $resultFile ) );
                $room->paidNotes = NULL;

                $voucherJson = $this->createVoucherV2( $res, $room );
                $voucherJson[ 'Comments' ] = $this->parseDetails( $resultFile, 'comments' );
                //$voucherJson[ 'Erratas' ] = $this->parseDetails( $resultFile, 'erratas' );
                $voucherJson[ 'Establishment_Id' ] = $this->utilFunc->parseXmlValue( 'id', $this->utilFunc->parseXmlValue( 'establishment', $resultFile ) );
                $voucherJson[ 'Establishment_Name' ] = $this->utilFunc->parseXmlValue( 'name', $this->utilFunc->parseXmlValue( 'establishment', $resultFile ) );
                $voucherJson[ 'HandlerId' ] = $this->utilFunc->parseXmlValue( 'handlerId', $resultFile );
                $voucherJson[ 'HandlerName' ] = $this->utilFunc->parseXmlValue( 'handlerName', $resultFile );
                $voucherJson[ 'BoardTypeName' ] = $this->utilFunc->parseXmlValue( 'boardTypeName', $resultFile );
                $voucherJson[ 'NumberOfRooms' ] = $this->utilFunc->parseXmlValue( 'numberOfRooms', $resultFile );
                $voucherJson[ 'RoomTypeName' ] = $this->utilFunc->parseXmlValue( 'roomTypeName', $resultFile );
                $voucherJson[ 'serviceDescription' ] = $this->utilFunc->parseXmlValue( 'serviceDescription', $resultFile );
                $voucherJson[ 'serviceId' ] = $this->utilFunc->parseXmlValue( 'serviceId', $resultFile );
                $voucherJson[ 'serviceType' ] = $this->utilFunc->parseXmlValue( 'serviceType', $resultFile );

                $room->json = $voucherJson;
                $res->rooms[] = $room;
            }
        } else {
            throw new \Exception( 'Something went wrong, error pulling reservations!' );
        }

        //print_r( $res );
        return $res;
    }

    /**
    * @return array of retrieveCancellation
    */

    public function retrieveCancellation( $refer, $lastmodify, $forceFlg ) {
        $res = new PullReservation();
        $res->refer = $refer;
        $res->status = PullReservation::CANCELLED;
        if ( $forceFlg ) {
            $res->force = \TRUE;
        }
        $res->updateDate = ( new \DateTime( $lastmodify ) )->format( DATE_ATOM );
        $res->createDate = NULL;
        // return single reservations object
        return $res;
    }

    /**
    * @return xml of get All Bookings
    */

    
    

    
    
    
    
    public function getReservations() {
        $d1 = new \DateTime();
        $d1->sub( new \DateInterval( 'P1D' ) );
        $d2 = new \DateTime();
        $result = '';
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:con="http://contracts.jumbotours.com/">
                   <soapenv:Header/>
                   <soapenv:Body>
                          <con:searchServices>
                                 <serviceSearchBean>
                                        <modifiedDateFrom>' . $d1->format( 'Y-m-d' ) . ' 00:00:01</modifiedDateFrom>
                                        <modifiedDateTo>' . $d2->format( 'Y-m-d' ) . ' 23:59:59</modifiedDateTo>
                                        <firstRow>0</firstRow>
                                        <numRows>100</numRows>
                                        <userLogin>' . $this->siteUser->sites_user . '</userLogin>
                                        <userPassword>' . $this->siteUser->sites_pass . '</userPassword>
                                 </serviceSearchBean>
                          </con:searchServices>
                   </soapenv:Body>
                </soapenv:Envelope>';
        $result = $this->utilFunc->submitXmlPost( $this->url, $xml, [] );
        $result = preg_replace( '/\&lt\;/is', '<', $result );
        $result = preg_replace( '/\&gt\;/is', '>', $result );
        $result = preg_replace( '/\&quot\;/is', '"', $result );
        $this->insertResaLog( $xml . '------' . $result );
        //echo 'url=>'.$this->url."\nxml=>$xml\nresult=>$result\n";
        //$result = $this->tempXml();

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

    /**
    * @return table of Rates
    */

    public function parseDetails( $res, $tag ){
        $details = $res;
        $table = '<table border="1" width="50%">';
        $table .= '<tr>';
        $table .= '<td align="center">Text</td>';
        $table .= '<td align="center">Type</td>';
        $table .= '</tr>';
        $regex = "/<$tag>(.*?)<\/$tag>/is";
        while ( preg_match( $regex, $details, $m ) ) {
            $details = $this->utilFunc->after( $m[ 0 ], $details );
            $xml = $m[ 1 ];
            $text = trim( $this->utilFunc->parseXmlValue( 'text', $xml ) );
            $type = trim( $this->utilFunc->parseXmlValue( 'type', $xml ) );
            $table .= '<tr>';
            $table .= '<td align="center">' . $text . '</td>';
            $table .= '<td align="center">' . $type . '</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }

    /**
    * @return xml example for test purpose
    */
    
        public function setSiteUser($sc) {
        $this->siteUser = $sc;
    }

    /**
     * @param object $sc
     */
    public function setSiteConfig($sc) {
        $this->siteConfig = $sc;
    }

    /**
     * @param Database $db
     */
    public function setDatabase($db) {
        $this->database = $db;
    }

    /**
     * @param boolean $test
     */
    public function setTest($test) {
        $this->test = $test;
    }

//       public function checkUnprocessedBooking($flag = 1) {
//        //return 0;//due to server issue not storing
//        $this->pendingResaArr = [];
//        if ($flag == '2') {
//            $q = 'SELECT `refer`,`xml`,`status`,`lastmodify_time`,`property_id` FROM ' . DB_RESA_XML_TABLE . ' WHERE `hot_sites_id` = ? AND `processed` = ? limit 1';
//            $p = [$this->siteConfig->sites_id, 0];
//            //print "q=>$q";print_r($p);
//        } elseif ($this->siteConfig->sites_id == '337' || $this->siteConfig->sites_id == '332') {
//            $q = 'SELECT `refer`,`xml`,`status`,`lastmodify_time`,`property_id` FROM ' . DB_RESA_XML_TABLE . ' WHERE `hot_sites_id` = ? AND `processed` = ? AND property_id = ? limit 300';
//            $p = [$this->siteConfig->sites_id, 0, $this->siteUser->sites_user];
//            //print "q=>$q";print_r($p);
//        } else {
//            $q = 'SELECT `refer`,`xml`,`status`,`lastmodify_time`,`property_id` FROM ' . DB_RESA_XML_TABLE . ' WHERE `hot_sites_id` = ? AND `processed` = ? AND property_id = ? limit 300';
//            $p = [$this->siteConfig->sites_id, 0, $this->siteUser->hotel_id];
//        }
//        $rows = $this->getLogs()->fetchAll($q, $p);
//        //$rows = $this->database->fetchAll($q, $p);
//        foreach ($rows as $row) {
//           // echo "gzuncompress xml=>".gzuncompress($row->xml)."\n";
//            $this->pendingResaArr[$row->refer]['xml'] = gzuncompress($row->xml);
//            $this->pendingResaArr[$row->refer]['status'] = $row->status;
//            $this->pendingResaArr[$row->refer]['lastmodify_time'] = $row->lastmodify_time;
//            $this->pendingResaArr[$row->refer]['property_id'] = $row->property_id;
//        }
//        //print("this is xml\n");
//        //print($this->pendingResaArr[$row->refer]['xml']);
//        if (count($this->pendingResaArr) > 0) {
//            return 1;
//        }
//    } 
    
    
    
    
    

    public function tempXml() {
        return '<ns2:service>
                    <bookingLocator>8513209214</bookingLocator>
                    <comments>
                        <text>100.0%</text>
                        <type>NO SHOW</type>
                    </comments>
                    <comments>
                        <text>true</text>
                        <type>PVP / Retail Price</type>
                    </comments>
                    <comments>
                        <text>112.0</text>
                        <type>PVP / Binding retail price amount</type>
                    </comments>
                    <comments>
                        <text>78203241</text>
                        <type>IATACode</type>
                    </comments>
                    <commision>0.0</commision>
                    <erratas>
                        <text>Info reserva - COVID-19: Debido a la situaci&amp;oacute;n relacionada al COVID-19 pueden existir restricciones sobre viajes que pueden cambiar en el tiempo (ej. visados, pcr test, documentaci&amp;oacute;n, cuarentena, etc.), recomendamos a los clientes de informarse sobre las posibles medidas y/o normas de su destino con los organismos oficiales antes de viajar. Es posible que no todas las instalaciones y servicios del establecimiento y/o de otros proveedores est&amp;eacute;n disponibles y/o funcionando de manera habitual, pedimos disculpas por anticipado y debemos informarles de que no se aceptar&amp;aacute;n reclamaciones debido a esas circunstancias excepcionales, ni tampoco se alterar&amp;aacute;n las condiciones de cancelaci&amp;oacute;n previstas en cada reserva. Os recordamos de cumplir con todas las medidas sanitarias del COVID19 durante el viaje, tanto fuera como dentro del establecimiento reservado.</text>
                        <type>ERRATA</type>
                    </erratas>
                    <erratas>
                        <text>POL&amp;Iacute;TICA PARA GRUPOS: Los grupos son siempre bajo petici&amp;oacute;n.</text>
                        <type>ERRATA</type>
                    </erratas>
                    <establishment>
                        <cityName>GANDIA</cityName>
                        <countryName>ESPAÑA</countryName>
                        <id>1766206</id>
                        <name>HUGO BEACH HOTEL</name>
                    </establishment>
                    <formalizationDate>2021-02-16 21:30:46</formalizationDate>
                    <from>2021-02-18 12:00:00</from>
                    <handlerId>14492</handlerId>
                    <handlerName>JTE-VLC-VALENCIA</handlerName>
                    <leadPaxName>JORDI, Mayquez</leadPaxName>
                    <occupancies>
                        <adults>1</adults>
                        <boardTypeCode>RO</boardTypeCode>
                        <boardTypeName>ROOM ONLY</boardTypeName>
                        <children>0</children>
                        <numberOfRooms>1</numberOfRooms>
                        <roomTypeCode>7NT</roomTypeCode>
                        <roomTypeName>DOUBLE SINGLE USE EXTERIOR</roomTypeName>
                    </occupancies>
                    <paxList>
                        <paxName></paxName>
                        <roomType>7NT</roomType>
                    </paxList>
                    <serviceDescription>HUGO BEACH HOTEL</serviceDescription>
                    <serviceId>11913728</serviceId>
                    <serviceType>HT</serviceType>
                    <status>CONFIRMED</status>
                    <to>2021-02-22 12:00:00</to>
                    <total>
                        <currencyCode>EUR</currencyCode>
                        <purchasePrice>95.2</purchasePrice>
                    </total>
            </ns2:service>';
    }

}




