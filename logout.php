<?php
 echo <<<_EOT
 <html>
 <head>
  <title>Decrypto.id:The definitive cloud encryption and decryption service.</title>
 </head>
 <body>
_EOT;
destroy_session_and_data();
echo <<<_EOT
You have sucessfully logged out. <a href="index.php">Click here</a> to redirect to the site index.
_EOT;

function destroy_session_and_data() {
		$_SESSION = array();
		setcookie(session_name(), '', time() - 2592000, '/');
		session_destroy();
	}
?>
