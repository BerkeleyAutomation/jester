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
	<?php require_once("../includes/dbfunctions.php") ?>
	<?php require_once("../includes/jokefunctions.php") ?>
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

   <h2>Joke Viewer</h2>

   <?php
openConnection();

if (isset($_GET["jokeid1"]))
{
	//(int) needed to protect against injection
	$jokeid1 = (int) mysql_real_escape_string(htmlspecialchars($_GET["jokeid1"], ENT_QUOTES));
	
	if ($jokeid1 >= 1 && $jokeid1 <= $numjokes)
	{
		$query = "SELECT joketext FROM jokes WHERE jokeid={$jokeid1}";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		$row = mysql_fetch_row($result);
		$joketext1 = $row[0];
	}
	else
	{
		$jokeid1 = "";
		$joketext1 = "<p>\nSlot 1: Invalid joke ID.\n</p>\n";
	}
}
else
{
	$jokeid1 = "";
	$joketext1 = "<p>\nSlot 1: No joke ID was entered.\n</p>\n";
}

if (isset($_GET["jokeid2"]))
{
	//(int) needed to protect against injection
	$jokeid2 = (int) mysql_real_escape_string(htmlspecialchars($_GET["jokeid2"], ENT_QUOTES));
	
	if ($jokeid2 >= 1 && $jokeid2 <= $numjokes)
	{
		$query = "SELECT joketext FROM jokes WHERE jokeid={$jokeid2}";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		$row = mysql_fetch_row($result);
		$joketext2 = $row[0];
	}
	else
	{
		$jokeid2 = "";
		$joketext2 = "<p>\nSlot 2: Invalid joke ID.\n</p>\n";
	}
}
else
{
	$jokeid2 = "";
	$joketext2 = "<p>\nSlot 2: No joke ID was entered.\n</p>\n";
}


mysql_close($connection);
?>
<!-- <?php require_once("../includes/header.php") ?> -->

<!--
<style type="text/css">
#main
{
	
}

.jokeview1, .jokeview2
{
	clear: both;
	width: 600px;
	padding: 0px 10px 0px 10px;
	margin: -1px 0 0 0;
	border: 1px solid #9A6850;
	background-color: #FBFAC5;
	height: 140px;
	overflow: auto;
}

.slot1, .slot2
{
	clear: both;
	width: 600px;
	padding: 0px 10px 0px 10px;
	margin: -1px 0 0 0;
	border: 1px solid #9A6850;
	background-color: #FFFFFF;
	overflow: auto;
}

p.slimmer
{
	margin: 5px 0 5px 0;
	padding: 0 0 0 0;
}

.separator
{
	color: #888888;
	font-size: 10pt;
}

</style> 
-->
<!-- <div id="navigation">
<h3>
Joke Viewer
</h3>
</div> -->
<div id="main">
<form action="viewjokes.php" method="get">
<p>
Joke ID #1: <input name="jokeid1" type="text" maxlength="3" size="3" value="<?php print $jokeid1 ?>" /> <span class="separator"></span>
Joke ID #2: <input name="jokeid2" type="text" maxlength="3" size="3" value="<?php print $jokeid2 ?>" /> <span class="separator"></span> <input name="submit" type="submit" value="View" /><input name="reset" type="button" value="Reset" onclick="window.location.href = 'viewjokes.php'" />
</p>
</form>
</div>

<!--
<?php if (isset($_GET["jokeid1"]) || isset($_GET["jokeid2"]))
{
?>
<div class="slot1">
<p class="slimmer"><?php print "Joke ID #1: $jokeid1" ?></p>
</div>
<div class="jokeview1">
<?php print $joketext1 ?>
</div>
<div class="slot2">
<p class="slimmer"><?php print "Joke ID #2: $jokeid2" ?></p>
</div>
<div class="jokeview2">
<?php print $joketext2 ?>
</div>
<?php
}
?>
-->

<?php if (isset($_GET["jokeid1"]) || isset($_GET["jokeid2"]))
{
?>
<h3> Joke #1, ID #: <?php print "$jokeid1" ?> </h3>
<?php print $joketext1 ?>
<h3> Joke #2, ID #: <?php print "$jokeid2" ?> </h3>
<?php print $joketext2 ?>
<?php
}
?>

<?php require_once("../includes/footer.php") ?>

   </div>
   </div>
   </body>
   </html>

