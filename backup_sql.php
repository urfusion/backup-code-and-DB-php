<?php

#####################
//CONFIGURATIONS
#####################
// Define the name of the backup directory
define('BACKUP_DIR', './');
// Define  Database Credentials
define('HOST', 'localhost');
define('USER', 'user');
define('PASSWORD', 'pass');
define('DB_NAME', 'db');
/*
  Define the filename for the Archive
  If you plan to upload the  file to Amazon's S3 service , use only lower-case letters .
  Watever follows the "&" character should be kept as is , it designates a timestamp , which will be used by the script .
 */
$archiveName = 'mysql_backup_' . date("d-m-Y") . '.sql';
// Set execution time limit
if (function_exists('max_execution_time')) {
    if (ini_get('max_execution_time') > 0)
        set_time_limit(0);
}

//END  OF  CONFIGURATIONS



if (createNewArchive($archiveName)) {
// Create a new Archive
    echo 'Back up Created<br />';
    $date = new DateTime();
    $date->sub(new DateInterval('P1D'));
    $oldFile = 'mysql_backup_' . $date->format('d-m-Y') . '.sql';
    if (file_exists($oldFile)) {
        unlink($oldFile);
    }
} else {
    echo 'Sorry the latest Archive is not older than 24Hours , try a few hours later ';
}

function createNewArchive($archiveName) {
    $mysqli = new mysqli(HOST, USER, PASSWORD, DB_NAME);
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s", mysqli_connect_error());
        exit();
    }
    // Introduction information

    $return = "--\n";
    $return .= "-- A Mysql Backup System \n";
    $return .= "--\n";
    $return .= '-- Export created: ' . date("Y/m/d") . ' on ' . date("h:i") . "\n\n\n";
    $return .= "--\n";
    $return .= "-- Database : " . DB_NAME . "\n";
    $return .= "--\n";
    $return .= "-- --------------------------------------------------\n";
    $return .= "-- ---------------------------------------------------\n";
    $return .= 'SET AUTOCOMMIT = 0 ;' . "\n";
    $return .= 'SET FOREIGN_KEY_CHECKS=0 ;' . "\n";
    $tables = array();
// Exploring what tables this database has
    $result = $mysqli->query('SHOW TABLES');
// Cycle through "$result" and put content into an array
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
// Cycle through each  table
    foreach ($tables as $table) {
// Get content of each table
        $result = $mysqli->query('SELECT * FROM ' . $table);
// Get number of fields (columns) of each table
        $num_fields = $mysqli->field_count;
// Add table information
        $return .= "--\n";
        $return .= '-- Tabel structure for table `' . $table . '`' . "\n";
        $return .= "--\n";
        $return .= 'DROP TABLE  IF EXISTS `' . $table . '`;' . "\n";
// Get the table-shema
        $shema = $mysqli->query('SHOW CREATE TABLE ' . $table);
// Extract table shema
        $tableshema = $shema->fetch_row();
// Append table-shema into code
        $return .= $tableshema[1] . ";" . "\n\n";
// Cycle through each table-row
        while ($rowdata = $result->fetch_row()) {
// Prepare code that will insert data into table
            $return .= 'INSERT INTO `' . $table . '`  VALUES ( ';
// Extract data of each row
            for ($i = 0; $i < $num_fields; $i++) {
                $return .= '"' . $rowdata[$i] . "\",";
            }
            // Let's remove the last comma
            $return = substr("$return", 0, -1);
            $return .= ");" . "\n";
        }
        $return .= "\n\n";
    }
// Close the connection
    $mysqli->close();
    $return .= 'SET FOREIGN_KEY_CHECKS = 1 ; ' . "\n";
    $return .= 'COMMIT ; ' . "\n";
    $return .= 'SET AUTOCOMMIT = 1 ; ' . "\n";
//$file = file_put_contents($archiveName , $return) ;
    file_put_contents($archiveName, $return);
    return true;
}
