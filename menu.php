<?php

const SIMPLE_SUBSITUTION = 1;
const DOUBLE_TRANSPOSITION = 2;
const RC4 = 3;

//Main page for logged in standard users
echo <<<_END
<h1> Welcome to decrpyto.id</h1>
<p> The definitive cloud encryption and decryption service. </p>
_END;


//begin session
session_start();
$username = "";
if (isset($_SESSION['username']) && isset($_SESSION['check']))
	{
        $username = $_SESSION['username'];
        if ($_SESSION['check'] != hash('ripemd256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'])) 
        {

            echo <<<_END
            We could not log you in. Please <a href="logout.php">click here</a> to safely log out and log back in.
_END;
        }
        else
        {
            if (!isset($_SESSION['initiated'])) 
	            {
		            session_regenerate_id();
		            $_SESSION['initiated'] = 1;
	            }
	        if (!isset($_SESSION['count'])) $_SESSION['count'] = 0;
	        else ++$_SESSION['count'];
            $username = $_SESSION['username'];
            echo <<<_END
            <p>You are logged in as $username. <a href="logout.php">Click here</a> to log out.</p>
_END;
        }
    }
else echo "You must be logged in to use site functionality. Please <a href='authenticate.php'>click here</a> to log in.";
$ss = SIMPLE_SUBSITUTION;
$dt = DOUBLE_TRANSPOSITION;
$rc4 = RC4;
//encrypt text
echo <<<_END
<h2> Encrypt Text </h2>
<p> You can upload text in two ways: a .txt file upload or a textbox submission. <p>
<h3>File upload</h3>
<form method = 'post' action = 'encrypt.php' enctype = 'multipart/form-data'>
        Select a file: <input type='file' name ='filename' size ='10'><br>
        Select encryption type:<br>
        <input type="radio" name="cipher" value="$ss">Simple Subsitution<br>
        <input type="radio" name="cipher" value="$dt">Double Transposition<br>
        <input type="radio" name="cipher" value="$rc4">RC4<br>
    <input type='submit' value = 'Upload'></form>
<h3>Textbox submssion</h3>
<form method = 'post' action = 'encrypt.php' enctype = 'multipart/form-data'>
    Plaintext: <input type="text" value = "enter plaintext here." name="textboxsubmission"><br>
    Select encryption type:<br>
    <input type="radio" name="cipher" value="$ss">Simple Subsitution<br>
    <input type="radio" name="cipher" value="$dt">Double Transposition<br>
    <input type="radio" name="cipher" value="$rc4">RC4<br>
    <input type='submit' value = 'Encrypt'></form>
    </pre></form>
_END;

//decrpyt text
echo <<<_END
<h2> Decrypt Text </h2>
<p> You can upload text in two ways: a .txt file upload or a textbox submission. <p>
<h3>File upload</h3>
<form method = 'post' action = 'encrypt.php' enctype = 'multipart/form-data'>
        Select a file: <input type='file' name ='filename' size ='10'><br>
        Select decryption type:<br>
        <input type="radio" name="cipher" value="$ss">Simple Subsitution<br>
        <input type="radio" name="cipher" value="$dt">Double Transposition<br>
        <input type="radio" name="cipher" value="$rc4">RC4<br>
    <input type='submit' value = 'Upload'></form>
<h3>Textbox submssion</h3>
<form method = 'post' action = 'decrypt.php' enctype = 'multipart/form-data'>
    Plaintext: <input type="text" value = "enter plaintext here." name="textboxsubmission"><br>
    Select encryption type:<br>
    <input type="radio" name="cipher" value="$ss">Simple Subsitution<br>
    <input type="radio" name="cipher" value="$dt">Double Transposition<br>
    <input type="radio" name="cipher" value="$rc4">RC4<br>
    <input type='submit' value = 'Decrypt'></form>
    </pre></form>
_END;

function fatal_error_mesage($msg)
{
    echo <<<_EOT
<p> We're sorry, an error has occured and your request could not be processed.
    Please try again later. If this problem persists, please contact a server administrator.</p>
    <p>Error code: $msg </p>
_EOT;
}
?>