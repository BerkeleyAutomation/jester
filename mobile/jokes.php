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
   <?php require_once("../includes/generalfunctions.php") ?>
   <?php require_once("../includes/authentication.php") ?>
   <?php user_session() ?>
   <?php user_session_authenticate() ?>
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

         <?php
         function storeRating($connection, $userid)
         {  
            global $minjokerating, $maxjokerating;

   if (isset($_POST["slidernew"])) //If the rating meter was clicked
   {  
      //Make sure that the rating meter was not clicked by a different session user

      /* This situation could occur the following way:

         User 1 logs in, and rates jokes 1, 2, and 3
         User 2 loads browser, logs out, registers for new account and rates joke 1
         User 2 switches to User 1's screen and rates joke 4 -> This rating will count as User 2's rating of joke 4!
      */

         if ($_SESSION["sessionhash"] != $_POST["sessionhash"])
         {
            $_SESSION["message"] = "Jester cannot be open in two windows on the same machine (at the same time).";
            header("Location: logout.php");
            exit;
         }

      //Find the joke rating

         $meter = $_POST["slidernew"];
         $jokerating = (($meter * ($maxjokerating - $minjokerating))/640) - $maxjokerating;

      //Truncate joke rating

         if ($jokerating > $maxjokerating)
            $jokerating = $maxjokerating;

         if ($jokerating < $minjokerating)
            $jokerating = $minjokerating;

         $jokeid = $_POST["previousjokeid"];

      //Make sure that users do not rate jokes that they've already rated (ie. by pressing the back button or 
      //clicking too fast)

      if (!isRated($connection, $userid, $jokeid) && isRemoved($connection, $jokeid)) //Joke is unrated and was removed, and somehow there was an attempt to rate it: This is a system error
      die($_POST["presystemerror"] . "There has been an attempt to rate a joke that has been removed. Please try again later." . $_POST["postsystemerror"]);
      else if (isRated($connection, $userid, $jokeid)) //Joke has already been rated, and whether it was removed afterwards or not, the user can be directed to the next joke
      {
         $_SESSION["accessstring"] = "reratewarning";
         header("Location: reratewarning.php");
         exit;
      }
      
      //Store the number of jokes the user has now rated, the number of ratings the joke now has, and the rating

      incrementNumRated($connection, $userid);
      incrementNumRatings($connection, $jokeid);
      setJokeRating($connection, $userid, $jokeid, $jokerating);
      
      if (isUsingJester5($connection, $userid) && !isUsingJester4($connection, $userid)) //The latter part is necessary to protect against old users logging in (who are registered as both usingjester4 and usingjester5, but will experience Jester 4.0)
      setJokeClusterRating($connection, $userid, $jokeid, $jokerating);

      //Clear the ID of the last displayed joke, because now that the rating is stored, the joke should not be 
      //displayed again (i.e. if the user logs out and back in, or presses Back and "Continue")
      
      clearLastJokeID($connection, $userid);
   }
}

function updateStatus($connection, $userid)
{
   global $predictjokes, $numseedjokes;
   
   $numrated = getNumRated($connection, $userid);     
   $status = getStatus($connection, $userid);
   
   //Status = 0: User is rating the initial set of jokes
   //Status = 1: User is rating the recommended jokes
   //Status = 2: User is rating random jokes
   
   //There are no more initial set jokes to rate
   if (($status == 0) && ($numrated >= (count($predictjokes) + $numseedjokes)))
   {
      //If recommend is set, that means that the recommend.php page has been displayed, and the user can move on
      //The recommendready variable protects against a user loading recommend.php himself or pressing Back to get to it (pressing Back is unprotected by accessstring), causing the status to change
      //The recommendready variable overlaps with accessstring in terms of preventing a status change by manually going to recommend.php, but reliance on accesstring is not good
      if (isset($_POST["recommend"]) && $_SESSION["recommendready"])
      {
         $status = 1;
         setStatus($connection, $userid, $status);
         $_SESSION["recommendready"] = false;
         
         //For Jester 5, initialize the joke cluster ratings with the ratings so far (from the prediction set jokes and seed jokes) and cluster means
         
         if (isUsingJester5($connection, $userid) && !isUsingJester4($connection, $userid)) //The latter part is necessary to protect against old users logging in (who are registered as both usingjester4 and usingjester5, but will experience Jester 4.0)
         initializeJokeClusterRatings($connection, $userid);
      }
      //If the page has not been displayed, or it hasn't been displayed at the right time (which is actually impossible when using accessstring), display it
      else
      {
         $_SESSION["recommendready"] = true;
         $_SESSION["accessstring"] = "recommend";
         header("Location: recommend.php");
         exit;
      }
   }
   //If status is 1 and the user is done rating recommended jokes, set status to 2
   else if ($status == 1)
   {
      //If random is set, that means that the randomjokes.php page has been displayed, and the user can move on
      //The randomready variable protects against a user loading randomjokes.php himself or pressing Back to get to it (pressing Back is unprotected by accessstring), causing the status to change
      //The randomready variable overlaps with accessstring in terms of preventing a status change by manually going to randomjokes.php, but reliance on accesstring is not good
      if (isset($_POST["random"]) && $_SESSION["randomready"])
      {
         $status = 2;
         setStatus($connection, $userid, $status);
         $_SESSION["randomready"] = false;
      }
      //No else needed, because it is determined elsewhere whether randomjokes.php needs to be displayed
   }
   //If the count of jokes that can be recommended is different than the user's last count, this means that new
   //jokes have been added to circulation, and the system should recommend these jokes to the user
   else if (($status == 2) &&
     (getLastNumEnabledJokes($connection, $userid) < getNumEnabledJokes($connection, $userid)))
   {
      //If rerecommend is set, that means that the rerecommend.php page has been displayed, and the user can move on
      //The rerecommendready variable protects against a user loading rerecommend.php himself or pressing Back to get to it (pressing Back is unprotected by accessstring), causing the status to change
      //The rerecommendready variable overlaps with accessstring in terms of preventing a status change by manually going to rerecommend.php, but reliance on accesstring is not good
      if (isset($_POST["rerecommend"]) && $_SESSION["rerecommendready"])
      {
         $status = 1;
         setStatus($connection, $userid, $status);
         $_SESSION["rerecommendready"] = false;
      }
      //If the page has not been displayed, or it hasn't been displayed at the right time (which is actually impossible when using accessstring), display it
      else
      {
         $_SESSION["rerecommendready"] = true;
         $_SESSION["accessstring"] = "rerecommend";
         header("Location: rerecommend.php");
         exit;
      }
   }
   /*
   The status can never change from 2 to something greater at this point, because a lack of any more jokes would have
   been discovered in getThisJokeID on the previous page. That directs the user to nomorejokes.php, and this function 
   is not on that page.
   */
}

function getNumEnabledJokes($connection, $userid)
{
   $query = "SELECT COUNT(*) FROM jokes WHERE canberecommended=1";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   $row = mysql_fetch_row($result);
   $numenabledjokes = $row[0];

   return $numenabledjokes;
}

function getLastNumEnabledJokes($connection, $userid)
{
   $query = "SELECT lastnumenabledjokes FROM users WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
   
   $row = mysql_fetch_row($result);
   $lastnumenabledjokes = $row[0];

   return $lastnumenabledjokes;
}

function updateLastNumEnabledJokes($connection, $userid)
{
   $query = "SELECT COUNT(*) FROM jokes WHERE canberecommended=1";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
   
   $row = mysql_fetch_row($result);
   $lastnumenabledjokes = $row[0];
   
   $query = "UPDATE users SET lastnumenabledjokes={$lastnumenabledjokes} WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

function getStatus($connection, $userid)
{
   $query = "SELECT status FROM users WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
   
   $row = mysql_fetch_row($result);
   $status = $row[0];

   return $status;
}

function setStatus($connection, $userid, $status)
{
   $query = "UPDATE users SET status={$status} WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

//Returns the ID of the last displayed joke
function getLastJokeID($connection, $userid)
{
   $query = "SELECT lastjokeid FROM users WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
   
   $row = mysql_fetch_row($result); 
   $lastjokeid = $row[0];
   
   return $lastjokeid;
}

function setLastJokeID($connection, $userid, $lastjokeid)
{
   $query = "UPDATE users SET lastjokeid={$lastjokeid} WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

function clearLastJokeID($connection, $userid)
{
   setLastJokeID($connection, $userid, 0);
}

//Returns the ID of the next joke
function getThisJokeID($connection, $userid)
{
   global $predictjokes;
   
   //Construct details string
   $_SESSION["detailsstring"] = "";
   //End construct details string
   
   $lastjokeid = getLastJokeID($connection, $userid);
   
   //If there was a displayed joke that was never rated, display that exact joke again
   //This is useful for random jokes, as it displays the same random joke if the user logs out and back in, or presses back and continue
   if (($lastjokeid != 0) && !isRated($connection, $userid, $lastjokeid) && !isRemoved($connection, $lastjokeid)) //!isRated and !isRemoved are needed to protect against jokes being removed between the time a user exits and comes back again, and against failures to set the user's lastjokeid properly
   {
      //Construct details string
      $_SESSION["detailsstring"] = "<p>Resuming from previous state...</p>";
      //End construct details string
      
      $thisjokeid = $lastjokeid;
      return $thisjokeid;
   }

   $status = getStatus($connection, $userid);

   $seeding = true;
   
   //The user has not completed rating the initial set
   if ($status == 0)
   {
      for ($predictjokecount = 0; $predictjokecount < count($predictjokes); $predictjokecount++)
      {
         //This prediction set joke has not yet been rated
         if (!isRated($connection, $userid, $predictjokes[$predictjokecount]))
         {
            //This prediction set joke is marked as removed
            if (isRemoved($connection, $predictjokes[$predictjokecount]))
            {
               //This is an obsolete error message, because the predictjokes array is cleared of all removed jokes before it is created
               die($_POST["presystemerror"] . "Prediction set joke marked as removed. Please try again later." . $_POST["postsystemerror"]);
            }
            else
            {
               //Construct details string
               $_SESSION["detailsstring"] = "<p>Displaying prediction set joke...</p>";
               //End construct details string
               
               $thisjokeid = $predictjokes[$predictjokecount];
               $seeding = false;
               break;   
            }
         }
         //Else this prediction set joke has been rated
      }
      
      //All prediction set jokes have been rated, so the next initial set jokes are for seeding
      if ($seeding == true)
      {
         //Construct details string
         $_SESSION["detailsstring"] = "<p>Displaying seed joke...</p>";
         //End construct details string
         
         $thisjokeid = getSeedJokeID($connection, $userid);
      }
   }
   //The initial set is rated, and jokes can now be recommended
   else if ($status == 1)
   {
      $thisjokeid = recommendJoke($connection, $userid);
   }
   else if ($status == 2)
   {
      //Construct details string
      $_SESSION["detailsstring"] = "<p>Displaying random joke...</p>";
      //End construct details string
      
      $thisjokeid = getRandomUnratedJokeID($connection, $userid);
   }
   
   //No joke was found
   if ($thisjokeid == 0)
   {
      //There are not enough jokes for seeding (a certain amount of jokes for seeding is specified in constants.php)
      if ($status == 0)
         die($_POST["presystemerror"] . "Not enough jokes are available for seeding. Please try again later." . $_POST["postsystemerror"]);
      //There are no more recommended jokes
      else if ($status == 1)
      {
         $_SESSION["randomready"] = true;
         $_SESSION["accessstring"] = "randomjokes";
         header("Location: randomjokes.php");
         exit;
      }
      //There are no more random jokes
      else //if ($status == 2)
      {
         $_SESSION["accessstring"] = "nomorejokes";
         header("Location: nomorejokes.php");
         exit;
      }
   }
   
   return $thisjokeid;
}

//Stores the ID of the joke being displayed (this is cleared later, when the joke is rated) 
function storeLastJokeID($connection, $thisjokeid, $userid)
{
   $query = "UPDATE users SET lastjokeid={$thisjokeid} WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

function getThisJokeText($connection, $thisjokeid)
{
   $query = "SELECT joketext FROM jokes WHERE jokeid={$thisjokeid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   $row = mysql_fetch_row($result);
   $thisjoketext = $row[0];
   
   return $thisjoketext;
}

function setJokeRating($connection, $userid, $jokeid, $jokerating)
{
   $query = "SELECT lastjokeratingid FROM users WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   $row = mysql_fetch_row($result);
   $lastjokeratingid = $row[0];
   
   $jokeratingid = $lastjokeratingid + 1;
   
   $query = "INSERT INTO ratings (userid, jokeid, jokeratingid, jokerating) VALUES ({$userid}, {$jokeid}, {$jokeratingid}, {$jokerating})";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   $query = "UPDATE users SET lastjokeratingid={$jokeratingid} WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

//Increments the number of times the joke has been rated
function incrementNumRatings($connection, $jokeid)
{
   $query = "SELECT numratings FROM jokes WHERE jokeid={$jokeid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   $row = mysql_fetch_row($result);
   $numratings = $row[0];
   
   $numratings++;
   
   $query = "UPDATE jokes SET numratings={$numratings} WHERE jokeid={$jokeid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

//Gets the joke ID of a random unrated joke
function getRandomUnratedJokeID($connection, $userid)
{
   global $numjokes;
   
   if (!moreUnratedJokes($connection, $userid))
      return 0;
   
   $bound = 1;
   
   do
   {
      $jokeadder = rand(0, ($numjokes - $bound)); //$jokeadder is what you need to add to $bound to get the random joke ID
      $jokeid = $bound + $jokeadder;
   } while (isRated($connection, $userid, $jokeid) || isRemoved($connection, $jokeid));
   
   return $jokeid;
}

//Returns true if there are any more unrated jokes for the user
function moreUnratedJokes($connection, $userid)
{
   global $numjokes, $removedjokes;
   
   $numrated = getNumRated($connection, $userid);
   
   if ($numrated == ($numjokes - count($removedjokes)))
      return false;
   
   return true;
}

//Gets the joke ID of an unrated joke with the least amount of ratings
function getSeedJokeID($connection, $userid)
{  
   $query = "SELECT jokeid FROM jokes ORDER BY numratings, jokeid"; //Also sorted by joke ID, so that the earliest jokes added are the earliest to accumulate ratings
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   while ($row = mysql_fetch_array($result))
   {
      $jokeid = $row[0];
      
      if (!isRated($connection, $userid, $jokeid) && !isRemoved($connection, $jokeid))
         return $jokeid;
   }
   
   return 0;
}

//Increments the number of jokes the user has rated
function incrementNumRated($connection, $userid)
{
   $numrated = getNumRated($connection, $userid);
   
   $numrated++;
   
   $query = "UPDATE users SET numrated={$numrated} WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

?>
<?php
openConnection();
$navmessage = "";

$userid = $_SESSION["userid"];
storeRating($connection, $userid);
updateStatus($connection, $userid);
updateLastNumEnabledJokes($connection, $userid);
$status = getStatus($connection, $userid);

if (!isset($_SESSION["detailsstring"]))
   $_SESSION["detailsstring"] = "";

$jokeid = getThisJokeID($connection, $userid);
storeLastJokeID($connection, $jokeid, $userid);
$joketext = getThisJokeText($connection, $jokeid);
$numrated = getNumRated($connection, $userid);

$navmessageidstring = "navmessage";
if ($status == 0)
   $navmessage = "<p id=\"" . $navmessageidstring . "\">\nDisplaying Initial Jokes (" . ($numrated + 1) . "/" . (count($predictjokes) + $numseedjokes) . ")\n</p>\n";
else if ($status == 1)
   $navmessage = "<p id=\"" . $navmessageidstring . "\">\nRecommending Jokes\n</p>\n";
else if ($status == 2)
   $navmessage = "<p id=\"" . $navmessageidstring . "\">\nDisplaying Random Jokes\n</p>\n";

mysql_close($connection);

if ($numrated < 2)
   $ratinginstructionson = true;
else
   $ratinginstructionson = false;
?>

<h2>Jokes</h2>

<?php if($status == 0) : ?>
  <h3>Displaying Initial Jokes (<?php print ($numrated + 1) ?>/<?php print (count($predictjokes) + $numseedjokes) ?>)</h3>
<?php elseif($status == 1) : ?>
  <h3>Recommending Jokes </h3>
<?php else : ?>
  <h3>Displaying Random Jokes</h3>
<?php endif; ?>


<!-- TODO JP: do we want instruction strings? -->


<!-- <?php require_once("../includes/header.php") ?>
<script src="../user/sliderscript.js"></script>
<div id="navigation">
<h3>
Jokes
</h3>
<?php require_once("../includes/navbar.php") ?>
<?php print $navmessage ?>
</div> -->
<div id="jokebody">
   <?php print $joketext ?>
</div>

<?php
if (true === false) //Not for the public
{
   ?>
   <div id="details">
      <?php print $_SESSION["detailsstring"] ?>


   </div>
   <?php
}
?>
<?php
if ($ratinginstructionson)
{
   ?>
   <div class="alert"><b> Instructions:</b> Tap or drag the rating bar.<br><br>The closer you click to "More Funny," the better your rating of the joke, and the closer you click to "Less Funny," the worse your rating of the joke. </div> 

   <?php
}
?>

<style type="text/css">
#joke-rating-form .ui-slider-input {
  display:none !important;
  width:0px !important;
  height:0px !important;
}
#joke-rating-form .ui-slider-track {
  width:100% !important;
  float:left;
  margin: 0 !important;

  background-image: -ms-linear-gradient(left, #F3EA4D 0%, #E65C39 100%);

  /* Mozilla Firefox */ 
  background-image: -moz-linear-gradient(left, #F3EA4D 0%, #E65C39 100%);

  /* Opera */ 
  background-image: -o-linear-gradient(left, #F3EA4D 0%, #E65C39 100%);

  /* Webkit (Safari/Chrome 10) */ 
  background-image: -webkit-gradient(linear, left top, right top, color-stop(0, #F3EA4D), color-stop(1, #E65C39));

  /* Webkit (Chrome 11+) */ 
  background-image: -webkit-linear-gradient(left, #F3EA4D 0%, #E65C39 100%);

  /* W3C Markup, IE10 Release Preview */ 
  background-image: linear-gradient(to right, #F3EA4D 0%, #E65C39 100%);

}
</style>


<div id = "joke-rating-form" data-role="content">
   <form action="jokes.php" method="post">

      <div id="textbox">
         <div class="alignleft">&larr; <b><i> Less Funny </i></b> </div>
         <div class="alignright"> <b> <i> More Funny </i> </b> &rarr;</div>
      </div>

      <input type="range" name="slidernew" id="slidernew" value="319" min="0" max="638" />

      <input type="hidden" name="sessionhash" value="<?php print $_SESSION["sessionhash"] ?>" />
      <input type="hidden" name="previousjokeid" value="<?php print $jokeid ?>" />

   </br>

   <div style="clear: both;"></div> 
   <input type='submit' name='submit-button' value='Rate Joke'>
</form>
</div>


<div id="ratingsdummy">
</div>
<?php require_once("../includes/footer.php") ?>

</div>
</div>
</body>
</html>
