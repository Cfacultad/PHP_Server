
<?php
/*
 * CS 174 FINAL
 * PROJECT: DECRYPTOID
 * DATE OF SUBMISSION 12/16/2019
 * NAME: CHRISTIAN FACULTAD
 * STUDENT ID: 009147827
 * LICENSE: GNU GPLv3 
 */

 echo <<<_EOT
 <html>
 <head>
  <title>Decrypto.id:The definitive cloud encryption and decryption service.</title>
 </head>
 <body>
_EOT;

function fatal_error_mesage($msg)
{
    echo <<<_EOT
<p> We're sorry, an error has occured and your request could not be processed.
    Please try again later. If this problem persists, please contact a server administrator.</p>
    <p>Error code: $msg </p>
_EOT;
}
    
function get_post($conn, $var)
{
    return $conn->real_escape_string($_POST[$var]);
}

require_once 'login.php';

$conn = new mysqli($hn, $un, $pw); // establish connection
if($conn->connect_error) die(fatal_error_mesage("DB_CONN"));

//menu for entering the site when not logged in.
echo <<<_END
<h1> Welcome to Decrypto.id</h1>
<p> The definitive cloud encryption and decryption service. </p>
<h2> Login </h2>
    <p> Click this button to login </p>
    <form method = 'post' action = 'authenticate.php' enctype = 'multipart/form-data'>
    <input type='submit' value = 'Login'></form>
<form method = 'post' action = 'newUser.php' enctype = 'multipart/form-data'>
    <p> Click the button below to register a new user.</p>
    <input type='submit' value = 'Register'></form>

_END;
?>