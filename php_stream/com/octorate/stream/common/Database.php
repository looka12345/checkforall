<?php

/*
 * Database.php
 * Octorate srl. All rights reserved. 2019 
 */
namespace com\octorate\stream\common;

define('DB_DATA_USER', 'root');
define('DB_DATA_NAME', 'octo_db');
define('DB_LOGS_USER', 'root');
define('DB_LOGS_NAME', 'logs');
//print("this is host\n");
//print ($hostname);
$hostname = 'localhost';

switch ($hostname) {
    case 'localhost':
        define('DB_DATA_PASS', '');
        define('DB_DATA_HOST', 'localhost');
        define('DB_RESA_XML_TABLE', 'php_stream_reservation_xml');
        define('DB_LOGS_PASS', '');
        define('DB_LOGS_HOST', 'localhost');
        break;
    default:
        define('DB_DATA_PASS', '');
        define('DB_DATA_HOST', 'localhost');
        define('DB_RESA_XML_TABLE', 'php_stream_reservation_xml');
        define('DB_LOGS_PASS', '');
        define('DB_LOGS_HOST', 'localhost');
        break;
}

class Database {

    private $pdo;   
    private $dbuser="root";
    private $dbname="octo_db";
    private $dbpass="";
    private $dbhost="localhost";

    public static function getData() {
        // echo "\nthis is debug 3";
        return new Database('DATA');
    }

    public static function getLogs() {
        // echo "\nthis is debug 4";
        return new Database('LOGS');
    }

    public static function getOcto() {
        return new Database('OCTO');
    }

    /**
     * @param string $type
     */
    public function __construct($type) {
        // echo "\nthis is debug 2";
        print($this->dbhost);
        $this->dbhost = constant('DB_' . $type . '_HOST'); 
        $this->dbuser = constant('DB_' . $type . '_USER');
        $this->dbname = constant('DB_' . $type . '_NAME');
        $this->dbpass = constant('DB_' . $type . '_PASS');
        // echo "\nthis is debug 6 \n";
    
      
    }

//   private function db_base(){
//  $servername = "localhost";
//  $dbuser = "username";
//  //$dbpass = "";
// $dbname="octo_db";
// try {
//   $conn = new PDO($servername,$dbname, $dbuser, $this->dbpass);
//   // set the PDO error mode to exception
//   $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//   echo "Connected successfully";
// } catch(PDOException $e) {
//   echo "Connection failed: " . $e->getMessage();
// }
//     }
    /**
     * Connect to database.
     */
    private function connect() {
    
        try {
            if (!$this->pdo) {
                $str = 'mysql:host=' . $this->dbhost . ';port=3306;dbname=' . $this->dbname . ';charset=UTF8';
                // echo "str=>$str\n";
                // echo "user=>" .$this->dbuser . "\n";
                // echo "dbpass=>" . $this->dbpass. "\n";
                $this->pdo = new \PDO($str, $this->dbuser, $this->dbpass);
                $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            }
      
            // set the PDO error mode to exception
            //$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
           //echo "Connection established successfully";

          } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            $str = 'mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname . ';charset=UTF8';
            $this->pdo = new \PDO($str, $this->dbuser, $this->dbpass);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
          }
        // if (!$this->pdo) {
        //     $str = 'mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname . ';charset=UTF8';
        //     $this->pdo = new \PDO($this->dbhost, $this->dbname, $this->dbuser, $this->dbpass);
        //     $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // }
    }

    public function checkdbname() {

        return $this->dbname;
    }

    /**
     * Execute a query.
     * @param type $sql
     * @param type $params
     */
    public function executeQuery($sql, $params = []) {

          $this->connect();
        try {
            $stm = $this->pdo->prepare($sql);
            $stm->execute($params);
        } catch (\Throwable $th) {

           $databaseErrors = $stm->errorInfo();

            if (!empty($databaseErrors)) {
                $errorInfo = print_r($databaseErrors, true); # true flag returns val rather than print

                $errorLogMsg = "error info: $errorInfo"; # do what you wish with this var, write to log file etc... 

                if (preg_match('/MySQL server has gone away/', $errorLogMsg)) {

                   // $this->pdo = null;
                   $this->connect();
                    $stm = $this->pdo->prepare($sql);
                    $stm->execute($params);
                }
            }
        }

        return $stm;
    }

    /*
     * Get last insert id.
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function insertExecuteQuery($sql, $params = []) {

        $lastid = '';
          $this->connect();

        try {
            $stm = $this->pdo->prepare($sql);
            $stm->execute($params);
            print("this is debugginggg\n");
            $lastid = $this->pdo->lastInsertId();
        } catch (\Throwable $th) {

            $databaseErrors = $stm->errorInfo();

            if (!empty($databaseErrors)) {
                $errorInfo = print_r($databaseErrors, true); # true flag returns val rather than print

                $errorLogMsg = "error info: $errorInfo"; # do what you wish with this var, write to log file etc... 

                if (preg_match('/MySQL server has gone away/', $errorLogMsg)) {

                  //  $this->pdo = null;
                   // $this->db_base();
                    $this->connect();
                   $stm = $this->pdo->prepare($sql);
                    $stm->execute($params);
                    print("this is debuggingggg\n");
                   $lastid = $this->pdo->lastInsertId();
                }
            }
        }

        return $lastid;
    }
    /**
     * @param int $siteId
     * @param PullReservation $reservation
     * @param boolean $force
     */
    public function savePullReservation($siteId, $reservation, $force = false)
    {
        if (!$force && $reservation->updateDate) {
            // check if updated after db version
            $up = $this->isUpToDate($siteId, $reservation);
            if ($up) {
                // ignore reservation
                return;
            }
        }
        return $this->savePushImport($siteId, $reservation);
    }

    /*
     * Get affected rows
     */

     public function count_rows($stmt) {
         return $stmt->rowCount();
     }

    /*
     * Get affected rows
     */

    public function affected_rows($stmt) {
        return $stmt->fetchObject();
    }

    /**
     * Load site config for input id from hot_sites tables.
     * @param type $gid
     * @return mixed
     */
    public function getSiteConfig($gid)
    {
        return $this->fetchObject('SELECT * FROM hot_sites WHERE sites_id = ?', [$gid]);
    }

    /**
     * Load property info
     * @param $codice the property Id
     * @return mixed the property info
     */
    public function getProperty($codice)
    {
        return $this->fetchObject('SELECT * FROM clienti WHERE codice = ?', [$codice]);
    }

    /**
     * Load site user for input id from hot_sites_user tables.
     * @param type $id
     * @return mixed
     */
    public function getSiteUser($id)
    {
        return $this->fetchObject('SELECT * FROM hot_sites_user WHERE id = ?', [$id]);
    }
    
    
    /**
     * Execute a query.
     * @param type $sql
     * @param type $params
     */
    public function fetchObject($sql, $params = []) {
        $stm = $this->executeQuery($sql, $params);
        return $stm->fetchObject();
    }

    /**
     * Execute a query.
     * @param type $sql
     * @param type $params
     */
    public function fetchAll($sql, $params = []) {
        $stm = $this->executeQuery($sql, $params);
        return $stm->fetchAll(\PDO::FETCH_OBJ);
    }
public function isUpToDate($siteId, $reservation)
    {
        if ($reservation->force || !$reservation->updateDate) {
            return false;
        }
        // convert to server timezone
        $gmtTimezone = new \DateTimeZone('Europe/Rome');
        $d = new \DateTime($reservation->updateDate);
        $d->setTimezone($gmtTimezone);
        $ud = $d->format('Y-m-d H:i:s');
        // search in db
        $sql = 'SELECT MAX(a.resvModificationDateTime) FROM conferme a';
        $sql .= ' JOIN hot_sites c ON c.sites_id = ?';
        $sql .= ' AND a.sito = c.sites_name';
        $sql .= ' JOIN hot_sites_user b ON b.sites_id = c.sites_id AND b.';
        $par = [$siteId];
        if ($reservation->connectionId) {
            $sql .= 'id';
            $par[] = $reservation->connectionId;
        } else {
            $sql .= 'hotel_id';
            $par[] = $reservation->propertyReference;
        }
        $par[] = $reservation->refer;
        $par[] = $reservation->refer;
        $par[] = $ud;
        $sql .= ' = ? WHERE a.codice = b.sites_asso_id';
        $sql .= ' AND (a.refer = ? OR a.refer_disp = ?)';
        $sql .= ' HAVING MAX(a.resvModificationDateTime) >= ?';
        $query = $this->executeQuery($sql, $par);
        $val = $query->fetchColumn();
        if ($val) {
            // db version is up-to-date
            return true;
        }
        // not found or older than input one
        return false;
    }
}
// TO POST THE DATA IN URL
//test=true&id=6664&data={%22externalRoomId%22:750329,%22dateIntervals%22:[{%22startDate%22:%222021-03-05%22,%22endDate%22:%222021-03-05%22,%22dows%22:[5]}],%22processIds%22:[243906336],%22changeMask%22:255,%22availability%22:0,%22price%22:62,%22minstay%22:3,%22maxstay%22:90,%22stopSells%22:false,%22closeToArrival%22:false,%22closeToDeparture%22:false,%22cutOffDays%22:1}