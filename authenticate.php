<?php
/*
 * This page is dedicated to authenticating the user.
 */

const SALT_SEP = 8;
const SALT_SIZE = 64;

require_once 'login.php';
$conn = new mysqli($hn, $un, $pw, $db);

auth_user($conn);



function auth_user($conn)
{
     if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
     {
        $un_temp = mysql_entities_fix_string($conn, $_SERVER['PHP_AUTH_USER']);
		$pw_temp = mysql_entities_fix_string($conn, $_SERVER['PHP_AUTH_PW']);

         
         /*
          * Token generation.
          * To genererate an authorization token, I've taken the first 64 characters of a ripemd256 hash of the username, called 'salt'.
          * I dissect the salt in two and sandwich the plaintext password somewhere in between to create the pre-hash token.
          * The point of seperation is determined by taking a 64 modulo of salt.
          * The final token is then generated by taking a ripemd256 hash of this pre-hash token. 
          * 
          * My rationale for this is that salts are dynamically generated and instead of being stored alongside the user.
          * In case of a database breach, the attackers may not necessarily know how a salt/token is generated unless they are also
          * able to compromise the actual server scripts as well. 
          */
         
          $salt = hash('ripemd256', $un_temp);
          $saltsep = substr(hexdec($salt), 0, SALT_SEP);
          $seperation = hexdec($saltsep) % SALT_SIZE;
      
      
          $salt1 = substr($salt, 0, $seperation);
          $salt2 = substr($salt, $seperation - SALT_SIZE - 1 , SALT_SIZE);
        $pretoken = $salt1 . $pw_temp . $salt2;
        
        $token = hash('ripemd256', $pretoken);

        $query = "SELECT * FROM users WHERE username = '$un_temp'";
        $result = $conn->query($query);
        $rows = $result->num_rows;
        for ($j = 0 ; $j < $rows ; ++$j)
        {
            $result->data_seek($j);
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $un = $row['username'];
            $token1 = $row['token'];
        }
        $token1 = $row['token'];
        echo "$token<br>$token<br>";
            if ($token == $token1) {
                

                session_start();
                $_SESSION['username'] = $un_temp;
                $_SESSION['check'] = hash('ripemd256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']); // SUPA XTRA SECURITY!!!!!!!

				echo "You are now logged in as $un";
				die ("<p><a href=menu.php>Click here to continue</a></p>");
             
         }
             else die("Invalid username / password combination");
        }
    
     else
     {
         header('WWW-Authenticate: Basic realm="Restricted Section"');
         header('HTTP/1.0 401 Unauthorized');
         die ("Please enter your username and password");
     }
    $conn->close(); 
}
function mysql_entities_fix_string($connection, $string)
	{
		return htmlentities(mysql_fix_string($connection, $string));
	}
	function mysql_fix_string($connection, $string)
	{
		if (get_magic_quotes_gpc()) $string = stripslashes($string);
		return $connection->real_escape_string($string);
	}
function fatal_error_mesage($msg)
{
    echo <<<_EOT
<p> We're sorry, an error has occured and your request could not be processed.
    Please try again later. If this problem persists, please contact a server administrator.</p>
    <p>Error code: $msg </p>
_EOT;
}

?>