<?php
/*
 * Run this file to initialize the MySQL database for the server. You will need to manually create a user in MySQL in order to do this.
 * The default value for the user is defined in login.php as "finaluser".
 */

//table initialization
const USERS_TABLE = "CREATE TABLE IF NOT EXISTS
    users (username VARCHAR(32) NOT NULL UNIQUE, email VARCHAR(64) NOT NULL UNIQUE, token BINARY(64) NOT NULL);";
const INPUT_TABLE = "CREATE TABLE IF NOT EXISTS
    inputtext ( username VARCHAR(32) NOT NULL, input VARCHAR(8000) NOT NULL, cipher TINYINT UNSIGNED NOT NULL)";



require_once 'login.php';

$conn = new mysqli($hn, $un, $pw); // establish connection
if($conn->connect_error) die(fatal_error_mesage("DB_CONN"));

// check if the database has been created, create if not
$query = "CREATE DATABASE IF NOT EXISTS $db";
$result = $conn->query($query);
if (!$result) die(fatal_error_mesage("DB_CRE"));

//connect to the db
$result = $conn->query("USE $db");
if (!$result) die(fatal_error_mesage("DB_OPE"));

//check if tables are created, create if not.
$query = USERS_TABLE;
$result = $conn->query($query);
echo $conn->error;
if (!$result) die(fatal_error_mesage("DB_TAB_CRE"));

$query = INPUT_TABLE;
$result = $conn->query($query);
echo $conn->error;
if (!$result) die(fatal_error_mesage("DB_TAB_CRE"));


function fatal_error_mesage($msg)
{
    echo <<<_EOT
<p> We're sorry, an error has occured and your request could not be processed.
    Please try again later. If this problem persists, please contact a server administrator.</p>
    <p>Error code: $msg </p>
_EOT;
}
?>