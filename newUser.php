<?php

const SALT_SIZE = 64;
const PASSWORD_VALID_REGEX =  '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'; //at least one lowercase, uppercase, digit, and special character
const USERNAME_VALID_REGEX = '/^([A-Za-z0-9_-])+$/'; //only latin chars and digits.
const EMAIL_VALID_REGEX = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';
const PASSWORD_MIN_SIZE_NO_LIMITS = 12; //determines the minimum length of a password before arbitrary limitations are lifted.
const PASSWORD_MIN = 6; //all passwords must be at least this length long
const USERNAME_MAX = 32;
const SALT_SEP = 8;



echo <<<_EOT
<form method = 'post' action = 'newUser.php' enctype = 'multipart/form-data'>
    <h2>New User</h2>
    Enter email: <input type='text' name ="email" >Must be a valid email<br> 
    Enter username: <input type='text' name ="user" >Must be letters, numbers, or underscores and dashes. No more than 32 in length<br> 
    Enter password: <input type='text' name ="pass" >Must be at least 6 characters. Passwords smaller than 12 must contain at least one uppercase letter, number, and special character<br> 
    <input type='submit' value = 'Register'></form>
_EOT;

require_once "login.php";
$conn = new mysqli($hn, $un, $pw, $db); // establish connection
if($conn->connect_error) die(fatal_error_mesage("DB_CONN"));


if(isset($_POST["email"]) && isset($_POST["pass"]) && isset($_POST["user"]))
{
    $email = mysql_fix_string($conn, $_POST["email"]);
    $pass = mysql_fix_string($conn, $_POST["pass"]);
    $user = mysql_fix_string($conn, $_POST["user"]);
    add_user($conn, $user, $pass, $email);
}
$conn->close();
function add_user($conn, $user, $pass, $email)
{
    //check if username is valid
    //a valid username contains only underscores,dashes, digits, lowercase latin characters, and uppercase latin characters. 
    //Due to database constraints, can only be up to 32 characters long. No minimums.No other limitations.
    if(!preg_match(USERNAME_VALID_REGEX, $user) || strlen($user) > USERNAME_MAX)
    {
        die(fatal_error_mesage("INVALID_USERNAME"));
    }
    
    //check if username exists
    $query = "SELECT * FROM users WHERE username = '$user'";
    $result = $conn->query($query);
    if(mysqli_num_rows($result)>0) die(fatal_error_mesage("USER_EXISTS"));
    
    //check if email is valid
    //I copypasted that regex from emailregex.com. Judging from the name and content of the website, i'm assuming the regex is an industry standard.
    if(!preg_match(EMAIL_VALID_REGEX, $email))
    {
        die(fatal_error_mesage("INVALID_EMAIL"));
    }
    
    //check if email is used
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($query);
    if(mysqli_num_rows($result)>0) die(fatal_error_mesage("USER_EXISTS"));

    //check if password is valid
    //passwords at least 12 characters long do not have arbitrary limits
    //passwords between 6 and 12 characters long need to have at least one: lowercase, uppercase, digit, special character
    if(strlen($pass)<PASSWORD_MIN_SIZE_NO_LIMITS)
    {
        if(strlen($pass)<PASSWORD_MIN)
        {
            die(fatal_error_mesage("PASSWORD_TOO_SHORT"));
        }
        else if (!preg_match(PASSWORD_VALID_REGEX, $pass))
        { die(fatal_error_mesage("PASSWORD_NOT_VALID"));}
    }
    
    //insert user
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
    $salt = hash('ripemd256', $user);
    $saltsep = substr(hexdec($salt), 0, SALT_SEP);
    $seperation = hexdec($saltsep) % SALT_SIZE;


    $salt1 = substr($salt, 0, $seperation);
    $salt2 = substr($salt, $seperation - SALT_SIZE - 1 , SALT_SIZE);
    $pretoken = $salt1 . $pass . $salt2;
    
    $token = hash('ripemd256', $pretoken);
    $query = "INSERT INTO users VALUES('$user', '$email', '$token')";
    $result = $conn->query($query);
    if (!$result) die($conn-fatal_error_mesage("DB_REG"));
    echo "<p>Registered $user as user. Welcome. Please reload main site to log in. </p>";
}

function fatal_error_mesage($msg)
{
    echo <<<_EOT
<p> We're sorry, an error has occured and your request could not be processed.
    Please try again later. If this problem persists, please contact a server administrator.</p>
    <p>Error code: $msg </p>
_EOT;
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
?>