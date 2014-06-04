<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("Mail.php") ?>
<?php
openConnection();

$email = "Email";
$numrated = "Jokes Rated";
$heardabout = "Referred From";
$registertime = "Time (PST)";

$query = "SELECT email, numrated, heardabout, DATE_FORMAT(firsttime, '%r') AS registertime, DATE(firsttime) AS registerdate, usingjester4, usingjester5 FROM users HAVING registerdate = CURDATE()";		
$result = mysql_query($query, $connection);
if (!$result)
	die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

$emailheader = "Jester 4.0 Referral List\n" . date('l, F jS, Y');
$tableheader = $registertime . ": " . $email . ", " . $numrated . ", [ " . $heardabout . " ]";

$tablerows = array();

$totaljokesrated = 0;
$numusers = 0;

while ($row = mysql_fetch_array($result))
{
	$registerdate = $row[4];
	
	$email = $row[0];
	if ($email == null)
		$email = "(unregistered)";
	
	$numrated = $row[1];
	
	if (is_null($row[2]))
		$heardabout = "";
	else
		$heardabout = ", [ " . $row[2] . " ]";
		
	$registertime = $row[3];
	$usingjester4 = $row[5];
	$usingjester5 = $row[6];
	
	$algversionstring = "";
	if ($usingjester4 == 1)
		$algversionstring = "E4";
	else
		$algversionstring = "E5";
	
	$tablerows[] = "[" . $algversionstring . "] " . $registertime . ": " . $email . ", " . $numrated . $heardabout;
	
	$totaljokesrated += $numrated;
	$numusers++;
}

if ($numusers == 0)
	$averagejokesrated = 0;
else
	$averagejokesrated = $totaljokesrated / $numusers;

$tablecontents = "";
for ($i = 0; $i < count($tablerows); $i++)
{
	$tablecontents .= $tablerows[$i] . "\n";
}

//Find the jokes with the highest variances

$variancearray = array();
$meanarray = array();

for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
{
	$jokeid = $jokeindex + 1;	
	$variancearray[$jokeindex] = getRatingVariance($connection, $jokeid);
	$meanarray[$jokeindex] = getMeanRating($connection, $jokeid);
}

arsort($variancearray);
arsort($meanarray);

$top15variancejokes = "\n\nTop 15 Variance Jokes:\n";
$top15variancecount = 0;

foreach ($variancearray as $jokeindex => $variance)
{
	$jokeid = $jokeindex + 1;

	if (in_array($jokeid, $predictjokes))
		$predicttext = " (Predictor)";
	else
		$predicttext = "";
		
	$top15variancejokes .= "\n" . ($top15variancecount + 1) . ". Variance for Joke {$jokeid}" . $predicttext . ": " . sprintf("%.2f", $variance) .
							" [ http://eigentaste.berkeley.edu/user/viewjoke.php?jokeid={$jokeid} ]";
	
	$top15variancecount++;
	if ($top15variancecount == 15)
		break;
}

$top15favoritejokes = "\n\nTop 15 Favorite Jokes:\n";
$top15favoritecount = 0;

foreach ($meanarray as $jokeindex => $meanrating)
{
	$jokeid = $jokeindex + 1;

	if (in_array($jokeid, $predictjokes))
		$predicttext = " (Predictor)";
	else
		$predicttext = "";
		
	$top15favoritejokes .= "\n" . ($top15favoritecount + 1) . ". Mean Rating for Joke {$jokeid}" . $predicttext . ": " . sprintf("%.2f", $meanrating) .
							" [ http://eigentaste.berkeley.edu/user/viewjoke.php?jokeid={$jokeid} ]";
	
	$top15favoritecount++;
	if ($top15favoritecount == 15)
		break;
}

$reportbody = $emailheader . "\n\n" . $tableheader . "\n\n" . $tablecontents . "\n" .
				"Unique Users: " . $numusers . "\n" .
				"Total Jokes Rated: " . $totaljokesrated . "\n" .
				"Average Jokes Rated: " . sprintf("%.2f", $averagejokesrated) . $top15variancejokes . $top15favoritejokes;

mysql_close($connection);

//Send email

//$recipients = "patel24jay@gmail.com," . "sanjaykrishn@gmail.com," . $goldberg;
$recipients = "patel24jay@gmail.com";

//$webmaster . "," . $goldberg . "," . "faridani@gmail.com" . "," .  "tavi.nathanson@gmail.com" . "," . "david_wong@berkeley.edu";

$headers["From"] = "Jester <" . $fromemail . ">";
$headers["To"] = $recipients;
$headers["Subject"] = "Jester 4.0 Referral List for " . date('l, F jS, Y');

$body = $reportbody;

//Create the mail object using the Mail::factory method
$mail_object =& Mail::factory('sendmail');

$mail_object->send($recipients, $headers, $body);
?>
