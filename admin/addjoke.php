<?php $_POST["errormessagetype"] = "html" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("../includes/authentication.php") ?>
<?php admin_session() ?>
<?php admin_session_authenticate() ?>
<?php
function addSubmittedJoke($connection)
{
	global $message, $minjokelength, $maxjokelength, $htmldisplayjoke;
	
	if (isset($_POST["joketext"]))
	{
		//Validation
		
		if (empty($_POST["joketext"]))
		{
			$message = "You cannot submit a blank joke.";
			return false;
		}
		
		if (strlen($_POST["joketext"]) < $minjokelength)
		{
			$message = "Your joke is too short.";
			return false;
		}
		
		if (strlen($_POST["joketext"]) > $maxjokelength)
		{
			$message = "Your joke is too long. It cannot exceed " . number_format($maxjokelength) . " characters.";
			return false;
		}
		
		//Store the joke in jokes

		$htmldisplayjoke = "<p>\n" . nl2br(trim(htmlspecialchars($_POST["joketext"], ENT_QUOTES))) . "\n</p>\n";
		$submittedjoke = mysql_real_escape_string($htmldisplayjoke);

		addJoke($connection, $submittedjoke);
		
		$message = "The following joke has been added:";
		return true;
	}
	else
		return false;
}

function addJoke($connection, &$submittedjoke)
{	
	$jokeid = getNextJokeID($connection);
	$removed = 0;
	$predictor = 0;
	$numratings = 0;
	$canberecommended = 0;
	
	$query = "INSERT INTO jokes (jokeid, joketext, removed, predictor, numratings, canberecommended) VALUES ({$jokeid}, '{$submittedjoke}', {$removed}, {$predictor}, {$numratings}, {$canberecommended})";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

function getNextJokeID($connection)
{
	$query = "SELECT jokeid FROM jokes ORDER BY jokeid DESC";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	$row = mysql_fetch_row($result);
	$highestjokeid = $row[0];

	$nextjokeid = $highestjokeid + 1;
	
	return $nextjokeid;
}

function isInteger($num)
{
  return (is_numeric($num) && (intval($num) == floatval($num)));
}
?>
<?php require("../includes/adminheader.php") ?>
<div id="navigation">
<h3>
Add a Joke
</h3>
<?php require_once("../includes/adminnavbar.php") ?>
</div>
<?php
openConnection();

if (isset($_POST["joketext"]))
{
?>
<div id="topnotice">
<?php
$errors = false;

if (isset($_POST["joketext"]) && !addSubmittedJoke($connection))
{
	$errors = true;
	print "<p class=\"error\">Error: ";
}
else
	print "<p>";
?>
<?php print $message ?>
</p>
<?php
if (isset($_POST["joketext"]) && !$errors)
	print "<p>\n<textarea class=\"noedit\" readonly>" . $htmldisplayjoke . "</textarea>\n</p>\n";
?>
</div>
<?php
}
?>
<div id="main">
<p>
Please enter the text of the joke:
</p>
<?php
if (isset($_POST["joketext"]) && $errors)
	$joketext = htmlspecialchars($_POST["joketext"], ENT_QUOTES);
else
	$joketext = "";
?>
<form action="../admin/addjoke.php" method="post" class="inlineform">
<p>
<textarea name="joketext" type="text"><?php print $joketext ?></textarea>
</p>
<p>
<input name="submitadd" type="submit" value="Submit" />
<input name="resetadd" type="reset" value="Reset" onclick="window.location.href = '../admin/addjoke.php'" />
</p>
</form>
</div>
<?php
mysql_close($connection);
?>
<?php require("../includes/footer.php") ?>