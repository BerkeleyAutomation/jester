<!doctype html>
<html>
<head>
<meta charset="utf-8">
   <meta name="viewport" content="width=device-width,initial-scale=1">
   <title>Jester: The Online Joke Recommender</title>
      <?php include 'imports.php'; ?>

   <!-- Jester imports go here -->
   <?php require_once("../includes/settings.php") ?>
   <?php require_once("../includes/autologout.php") ?>
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

   <h2>Logout</h2>

   <?php user_session() ?>
   <?php
   if (isset($_SESSION["message"]))
   {
   $message = $_SESSION["message"];
   unset($_SESSION["message"]);
   }
   else if (!isset($_SESSION["userid"]))
   {
   $message = "You are logged out.";
   }
   else
   $message = "Thanks for using Jester!</p><p>Check out <a href=\"http://dd.berkeley.edu\">Donation Dashboard</a>, which uses Eigentaste to recommend a donation portfolio to you.</p>";
   
   user_session_erase();
   ?>
   <!-- </p><a href=\"http://dd.berkeley.edu\" target=\"_blank\"><img src=\"../images/dd_banner_square.jpg\" style=\"border: 1px solid #9A6850;\" /></a>";-->

   <!-- <?php require_once("../includes/header.php") ?>
   <div id="navigation">
   <h3>
   Logout
   </h3>
   <?php require_once("../includes/navbar.php") ?> 
   </div> -->
   <div id="main">
   <p>
   <?php print $message ?>
   </p>
   </div>
   <?php require_once("../includes/footer.php") ?>

   </div>
   </div>
   </body>
   </html>
