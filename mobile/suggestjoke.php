<!doctype html>
<html>
<head>
<meta charset="utf-8">
   <meta name="viewport" content="width=device-width,initial-scale=1">
   <title>Jester: The Online Joke Recommender</title>
    <?php include 'imports.php'; ?>

   <!-- Jester imports go here -->

	<?php $_POST["errormessagetype"] = "html" ?>
	<?php require_once("../includes/settings.php") ?>
	<?php require_once("../includes/autologout.php") ?>
	<?php require_once("../includes/constants.php") ?>
	<?php require("../includes/errormessages.php") ?>
	<?php require_once("../includes/authentication.php") ?>
   </head>
   
   <body>
   <div data-role="page" data-theme="c">
   <div data-role="header" data-position="inline" data-theme="e">
   <h1><img src="jester_notext.gif" style="height:1em">Jester 4.0</h1>
   <div data-role="navbar">
   <?php include 'navbar.php'; ?>
   </div>
   </div>
   <div data-role="content" data-theme="c">

   <h2>Suggest A Joke</h2> <!-- page title -->
   <!-- body goes here -->

	<?php user_session() ?>
	<?php user_session_authenticate() ?>
	<?php
	function addSubmittedSuggestedJoke($connection)
	{
	global $message, $minjokelength, $maxjokelength, $textdisplayjoke;		
	
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
	
	//Store the joke in suggestedjokes
	
	$textdisplayjoke = trim(htmlspecialchars($_POST["joketext"], ENT_QUOTES));
	$submittedjoke = mysql_real_escape_string($textdisplayjoke);
	
	$query = "SELECT jokeid FROM suggestedjokes ORDER BY jokeid DESC";
	$result = mysql_query($query, $connection);
	if (!$result)
	die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	$row = mysql_fetch_row($result);
	$highestjokeid = $row[0];
	
	$jokeid = $highestjokeid + 1;
	
	$userid = mysql_real_escape_string(htmlspecialchars($_SESSION["userid"], ENT_QUOTES));
	
	$query = "INSERT INTO suggestedjokes (jokeid, joketext, userid) VALUES ({$jokeid}, '{$submittedjoke}', {$userid})";
	$result = mysql_query($query, $connection);
	if (!$result)
	die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	$message = "Your joke suggestion has been submitted:";
	return true;
	}
	?>

	<!--
	<?php require_once("../includes/header.php") ?>
	<div id="navigation">
	<h3>
	Suggest a Joke
	</h3>
	<?php require_once("../includes/navbar.php") ?>
	</div>
-->
	<?php
	openConnection();
	
	if (isset($_POST["joketext"]))
	{
	?>
	<div id="topnotice">
	<?php
	$errors = false;
	
	if (!addSubmittedSuggestedJoke($connection))
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
	if (!$errors)
	print "<p>\n<textarea class=\"noedit\" readonly>" . $textdisplayjoke . "</textarea>\n</p>\n";
	?>
	</div>
	<?php
	}
	?>
	<div id="main">
	<?php
	$submitstring = "Submit";
	
	if (!isset($_POST["joketext"]) || $errors)
	{
	?>
	<p>
	You may use the form below to send us as many jokes as you like; however, Jester is a research project, so please understand that we are not able to attend to your suggestions on a regular basis.
	</p>
	<p>
	<span class="important">Note:</span> Jokes must be at least <?php print number_format($minjokelength) ?> characters and at most <?php print number_format($maxjokelength) ?> characters long. We will throw out jokes that do not have proper grammar and/or spelling. Please also refrain from using any HTML.
	</p>
	<?php
	}
	else
	{
	$submitstring = "Submit Another Joke Suggestion";
	?>
	<p>
	Feel free to suggest another joke using the form below:
	</p>
	<?php
	}
	?>
	<?php
	if (isset($_POST["joketext"]) && $errors)
	$joketext = htmlspecialchars($_POST["joketext"], ENT_QUOTES);
	else
	$joketext = "";
	?>
	<form action="suggestjoke.php" method="post">
	<p>
	<textarea name="joketext" type="text"><?php print $joketext ?></textarea>
	</p>
	<p>
	<input name="submit" type="submit" value="<?php print $submitstring ?>" />
	</p>
	</form>
	</div>
	<?php require_once("../includes/footer.php") ?>
   
   </div>
   </div>
   </body>
   </html>
