<!doctype html>
<html>
<head>
<meta charset="utf-8">
   <meta name="viewport" content="width=device-width,initial-scale=1">
   <title>Jester: The Online Joke Recommender</title>
   <?php include 'imports.php'; ?>

   <!-- Jester imports go here -->
   </head>
   <?php require_once("../includes/settings.php") ?>
	<?php require_once("../includes/autologout.php") ?>
	<?php require_once("../includes/authentication.php") ?>
	<?php user_session() ?>
	<?php user_session_authenticate_accessstring("rerecommend") ?>
   <body>
   <div data-role="page" data-theme="c">
   <div data-role="header" data-position="inline" data-theme="e">
   <h1><img src="jester_notext.gif" style="height:1em">Jester 4.0</h1>
   <div data-role="navbar">
   <?php include 'navbar.php'; ?>
   </div>
   </div>
   <div data-role="content" data-theme="c">

   <h2>Notice</h2> <!-- page title -->
   <!-- body goes here -->

	<!-- <?php require_once("../includes/header.php") ?>
	<div id="navigation">
	<h3>
	Notice
	</h3>
	<?php require_once("../includes/navbar.php") ?>
	</div> -->
	<div id="main">
	<p>
	We have new jokes to recommend to you! Please click below to be presented with the newest recommendations.
	</p>
	<form action="jokes.php" method="post">
	<input name="rerecommend" type="hidden" />
	<p>
	<input name="submit" type="submit" value="Let's Go!" />
	</p>
	</form>
	</div>
	<?php require_once("../includes/footer.php") ?>
   
   </div>
   </div>
   </body>
   </html>

