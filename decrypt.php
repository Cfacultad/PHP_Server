<?php
//handles decryption of input text

const SIMPLE_SUBSITUTION = 1;
const DOUBLE_TRANSPOSITION = 2;
const RC4 = 3;
const ALPHABET = 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z';
const LATIN_ALPHABET_REGEX = "/^([A-Za-z\s]*)$/";
const LATIN_ALPHABET_NO_WS_REGEX = "/^([A-Za-z]*)$/";
const WS_REGEX = "/^(/\s+/)$/";
const DT_COLUMNS = 16;
const DT_MIN = 32;
const SEED = 192791281123123;

//session handling
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
require_once "login.php";
$conn = new mysqli($hn, $un, $pw, $db); // establish connection
if($conn->connect_error) die(fatal_error_mesage("DB_CONN"));
//textbox submission
if (isset($_POST['textboxsubmission']) && isset($_POST['cipher']))
{
    $cipher = $_POST['cipher'];
    $text = $_POST['textboxsubmission'];
    //check if cipher is valid
    if ($cipher == SIMPLE_SUBSITUTION)
    {
        $result = simple_subsitution($text);
        updateDatabase($conn, $username, $text, $cipher);
        echo <<<_EOT
        <p>Submitted cryptotext: </p>
        <p>$text</p>
        <p>plain text:</p>
        <p>$result</p>
        <p><a href = 'menu.php'> Click here </a> to return.</p>"
_EOT;
    }
    else if ($cipher == DOUBLE_TRANSPOSITION)
    {
        $result = double_transposition($text);
        updateDatabase($conn, $username, $text, $cipher);
        echo <<<_EOT
        <p>Submitted cryptotext: </p>
        <p>$text</p>
        <p>plain text:</p>
        <p>$result</p>
        <p><a href = 'menu.php'> Click here </a> to return.</p>"
_EOT;
    }
    else if ($cipher == RC4)
    {
        $result = rc4($text);
        updateDatabase($conn, $username, $text, $cipher);
        echo <<<_EOT
        <p>Submitted cryptotext: </p>
        <p>$text</p>
        <p>plain text:</p>
        <p>$result</p>
        <p><a href = 'menu.php'> Click here </a> to return.</p>"
_EOT;
    }
    else
    {
        die("<p>Cipher input was invalid <a href = 'menu.php'> Click here </a> to return.</p>");
    }

}
//file submission
else if ($_FILES && isset($_POST['cipher']))
{
    $cipher = $_POST['cipher'];
        $name = $_FILES['filename']['name'];
        if ($_FILES['filename']['type'] == 'text/plain') {
            $ext = 'txt';
            
        }
        if ($ext) {
            $n = "upload.$ext";
            move_uploaded_file($_FILES['filename']['tmp_name'], $n);
            echo "Uploaded file as $name";
            if (file_exists($n)) {
                $file = fopen("$n", "r") or die("Upload fail.");
                $s = fread($file, filesize("$n"));
                fclose($file);
                if ($cipher == SIMPLE_SUBSITUTION)
                {
                $result = simple_subsitution($s);
                updateDatabase($conn, $username, $text, $cipher);
        echo <<<_EOT
        <p>Submitted cryptotext: </p>
        <p>$s</p>
        <p>plain text:</p>
        <p>$result</p>
        <p><a href = 'menu.php'> Click here </a> to return.</p>"
_EOT;
            }
            else if ($cipher == DOUBLE_TRANSPOSITION)
            {
                $result = double_transposition($s);
                updateDatabase($conn, $username, $text, $cipher);
                echo <<<_EOT
                <p>Submitted cryptotext: </p>
                <p>$s</p>
                <p>plain text:</p>
                <p>$result</p>
                <p><a href = 'menu.php'> Click here </a> to return.</p>"
_EOT;
            }
            else if ($cipher == RC4)
            {
                $result = rc4($s);
                updateDatabase($conn, $username, $text, $cipher);
                echo <<<_EOT
                <p>Submitted cryptotext: </p>
                <p>$s</p>
                <p>plain text:</p>
                <p>$result</p>
                <p><a href = 'menu.php'> Click here </a> to return.</p>"
_EOT;
            }
            else die("<p>Cipher input was invalid <a href = 'menu.php'> Click here </a> to return.</p>");
        }
            
        }
        else {
            echo "<p>'$name' is not a .txt file</p>";
        }
    }
else
{
    die("<p>There was either an error with the form you submitted, or you have arrived to this page by error. <a href = 'menu.php'> Click here </a> to return.</p>");
}

$conn->close();

function simple_subsitution($text)
{
  /* Simple subsitution
  * only works on 26 character latin alphabet.
  * If regex matches anything besides the latin alphabet, server dies with error.
  */
  //Check if text contains any characters besides those found in 26 char latin alphabet and whitespace
 if (!preg_match(LATIN_ALPHABET_REGEX, $text))
 {
     die("<p> There was an error with your text input: characters found that were neither latin alphabet nor whitespace");
 }
 $text = strtoupper($text);

 $alphabet1 = explode(",", ALPHABET);
 $alphabet2 = explode(",", ALPHABET);
 $seed = SEED;
 $text = str_split($text);
$shuffled = fisherYatesShuffle($alphabet1, $seed);
$key = array();
$plain = array();
for ($i = 0; $i < count($alphabet1); $i++)
{
    $key[$shuffled[$i]] = $alphabet2[$i];
}

for ($i = 0; $i < count($text); $i++)
{
    if(preg_match(LATIN_ALPHABET_NO_WS_REGEX, $text[$i]))
    {
        
        $plain[$i] = $key[$text[$i]];
    }
    else
    {
        $plain[$i] = $text[$i]; //preserve whitespace
    }
}
$plain = implode("", $plain);
return $plain;

}

function double_transposition($text)
{
/* double transposition
  * only works on 26 character latin alphabet.
  * strips white space to avoid decrpytion through context clues
  * If regex matches anything besides the latin alphabet, server dies with error.
  * Limitation: non-white space characters total to at least 32, to make this cipher effective (and i dont want to deal with coming up with row/column configs for small strings)
  * Seed for randomizing character matches is dependent on ripemd256 hash of content of text SORTED BY ALPHABETICAL ORDER (so i can write a 2-way encypter/decrypter based on content alone)
  * number of columns is hardcoded to 16.
  * number of rows is based on what will fill the 16 columns (example: 100 characters fits in 7 rows of 16)
*/
  if (!preg_match(LATIN_ALPHABET_REGEX, $text))
  {
      die("<p> There was an error with your text input: characters found that were neither latin alphabet nor whitespace. <a href = 'menu.php'> Click here </a> to return.</p>");
  }
//strip whitespace
$text = preg_replace(WS_REGEX, '', $text);

//check if text is now >= 32 characters
if (str_len($text)<DT_MIN)
{
    die("<p> There was an error with your text input: non-whitespace chars < 32. <a href = 'menu.php'> Click here </a> to return.</p> <p>");
}

//create seed based on entire text sorted alphabetically (to easier to encrypt/decrypt based on content alone).
$strarray = str_split($text);
$seed = SEED;

//calculate number of rows/columns
$numrows = ceil(str_len($text) / DT_COLUMNS);
$rows = range($numrows);
$columns = range(DT_COLUMNS);

//assemble the plaintext into a 2d array
$plainarray = array();
$count = 0;
for($i = DT_COLUMNS; $i > 0; $i--)
{
    $newrow = array();
    for($j = $numrows; $j > 0; $j--)
    {
        if($count>=count($strarray))
        {
            break;
        }
        $newrow[$j] = $strarray[$count];
        $count++;
    }
    $plainarray[$i] = $newrow;
}

//permutate rows/columns based on seed
$permrows = fisherYatesShuffle($rows, $seed); 
$permcolumns = fisherYatesShuffle($columns, $seed);

//copy plaintext array and implement permutations
//first dimension
$crypto = array();
for($i = DT_COLUMNS; $i > 0; $i--)
{
    $crypto[$i]= $plainarray[$permcolumns[$i]];
}
//second dimension
foreach($crypto as &$c)
{
    for($i = $numrows; $i > 0; $i--)
    {
        $c[$i]= $plainarray[$permcolumns[$permrows[$i]]];
    }
}
}
  
function fisherYatesShuffle($items, $seed)
{
    @mt_srand($seed);
    for ($i = count($items) - 1; $i > 0; $i--)
    {
        $j = @mt_rand(0, $i);
        $tmp = $items[$i];
        $items[$i] = $items[$j];
        $items[$j] = $tmp;
    }
    return $items;
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
    function updateDatabase($conn, $username, $text, $cipher)
{
    $cleantext = mysql_entities_fix_string($conn, $text);
    $cleanus = mysql_entities_fix_string($conn, $username);
    $cleancipher = mysql_entities_fix_string($conn, $cipher); //overkill i know but whatever
    $query = "INSERT INTO inputtext VALUES('$cleanus', '$cleantext', '$cleancipher')";
    $result = $conn->query($query);
    if (!$result) die($conn->error);
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