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
   <?php require_once("Mail.php") ?>
   <?php require_once("Validate.php") ?>
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

         <h2>Register</h2>

         <?php
         function registrationValid($connection, $email, $password, $passwordagain)
         {
            global $errorstr;

            $alreadyregistered = false;
            
            if (empty($email) || empty($password))
            {
               $errorstr = "You must complete all fields to register.";
               return false;
            }
            
            $validate = new Validate();
            if (($email != mysql_real_escape_string(htmlspecialchars($email, ENT_QUOTES))) || !$validate->email($email, true))
            {
               $errorstr = "That email address is not valid.";
               return false;
            }
            
            if (strlen($password) < 4)
            {
               $errorstr = "Your password must be at least four characters.";
               return false;
            }
            
            if ($password != $passwordagain)
            {
               $errorstr = "The passwords you entered do not match.";
               return false;
            }
            
            if (!(strpos($password, ' ') === false))
            {
               $errorstr = "You cannot have any spaces in your password.";
               return false;
            }
            
            $query = "SELECT * FROM users WHERE email='{$email}'";
            $result = mysql_query($query, $connection);
            if (!$result)
               die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
            
            if (mysql_num_rows($result) != 0)
               $alreadyregistered = true;
            
            if ($alreadyregistered)
            {
               $errorstr = "That email address is already registered.";
               return false;
            }

            return true;
         }

         function register($connection, $email, $password, $heardabout)
         {  
            $status = 0;
            
   if ($email != null) //User has registered
   {
      $passwordhash = mysql_real_escape_string(md5(trim($password)));
      
      $emailstring = "'{$email}'";
      $passwordhashstring = "'{$passwordhash}'";
      
      $selectemailstring = "='{$email}'";
      $selectpasswordhashstring = "='{$passwordhash}'";
   }
   else //User has not registered
   {
      $emailstring = "NULL";
      $passwordhashstring = "NULL";
      
      $selectemailstring = " IS NULL";
      $selectpasswordhashstring = " IS NULL";
   }
   
   $envirn = $_SERVER["REMOTE_ADDR"];
   $times = 1;
   $numrated = 0;
   $lastjokeid = 0;
   $lastjokeratingid = 0;
   $usingjester4 = 0;
   $usingjester5 = 0;
   
   $query = "SELECT COUNT(*) FROM jokes WHERE canberecommended=1";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
   
   $row = mysql_fetch_row($result);
   $lastnumenabledjokes = $row[0];
   
   if (empty($heardabout))
   {
      $heardaboutstring = "NULL";
      $selectheardaboutstring = " IS NULL";
   }
   else
   {
      $heardaboutstring = "'{$heardabout}'";
      $selectheardaboutstring = "='{$heardabout}'";
   }
   
   $query = "INSERT INTO users (email, status, password, envirn, times, numrated, lastjokeid, lastnumenabledjokes, heardabout, lastjokeratingid, usingjester4, usingjester5) " . 
   "VALUES (" . $emailstring . ", {$status}, " . $passwordhashstring . ", '{$envirn}', {$times}, {$numrated}, {$lastjokeid}, {$lastnumenabledjokes}, " . $heardaboutstring . ", {$lastjokeratingid}, {$usingjester4}, {$usingjester5})";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   //Get userid
   
   //If the user has not registered, get the latest userid who properly matches all the variables
   //If, due to concurrency issues, this is technically a different userid from the one created, that is okay--as long as all the variables match, there is no difference yet (as there are no ratings yet)
   
   $query = "SELECT userid FROM users WHERE email" . $selectemailstring  . " AND status={$status} AND password" . $selectpasswordhashstring . 
   " AND envirn='{$envirn}' AND times={$times} AND numrated={$numrated} AND lastjokeid={$lastjokeid} AND lastnumenabledjokes={$lastnumenabledjokes} AND heardabout" . $selectheardaboutstring . 
   " AND lastjokeratingid={$lastjokeratingid} AND usingjester4={$usingjester4} AND usingjester5={$usingjester5} ORDER BY userid DESC LIMIT 1";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
   
   $row = mysql_fetch_row($result);
   $userid = $row[0];

   //Get current timestamp (automatically placed in lasttime)
   
   $query = "SELECT lasttime FROM users WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
   
   //Give firsttime that timestamp, since this is both the firsttime and lasttime
   
   $row = mysql_fetch_row($result);
   $firsttime = $row[0];

   $query = "UPDATE users SET firsttime='{$firsttime}' WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   //Set usingjester4 or usingjester5, randomly
   
   $usingjester4 = rand(0, 1);
   
   if ($usingjester4 == 1)
      $usingjester5 = 0;
   else
      $usingjester5 = 1;
   
   $query = "UPDATE users SET usingjester4='{$usingjester4}' WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   $query = "UPDATE users SET usingjester5='{$usingjester5}' WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   //Initialize table names

   if (initializeTableNames($connection, $userid) === false)
      die($_POST["presystemerror"] . "User is not using either Jester version." . $_POST["postsystemerror"]);

   //Send confirmation email

   if ($email != null) //User has registered
   sendConfirmationEmail($connection, $email);
   
   return $userid;
}

function registerInSession($connection, $userid, $email, $password)
{
   $passwordhash = mysql_real_escape_string(md5(trim($password)));
   
   $query = "UPDATE users SET email='{$email}', password='{$passwordhash}' WHERE userid={$userid}";
   $result = mysql_query($query, $connection);
   if (!$result)
      die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

   sendConfirmationEmail($connection, $email);
}

function sendConfirmationEmail($connection, $email)
{
   global $fromemail, $confirmationbody;
   
   $recipients = $email;

   $headers["From"] = "Jester <" . $fromemail . ">";
   $headers["To"] = $email;
   $headers["Subject"] = "Jester Registration Confirmation";

   $body = $confirmationbody;

   //Create the mail object using the Mail::factory method
   $mail_object =& Mail::factory('sendmail');

   $mail_object->send($recipients, $headers, $body);
}

function confirmRegistration($connection, $userid)
{
   $sessionhash = createSessionHash($connection);
   
   $_SESSION["userid"] = $userid;
   $_SESSION["ip"] = $_SERVER["REMOTE_ADDR"];
   $_SESSION["recommendready"] = false;
   $_SESSION["randomready"] = false;
   $_SESSION["rerecommendready"] = false;
   $_SESSION["recommendcount"] = 0;
   $_SESSION["seedcount"] = 0;
   $_SESSION["sessionhash"] = $sessionhash;
   
   $_SESSION["accessstring"] = "registerconfirm";
   header("Location: registerconfirm.php");
   //echo '<META HTTP-EQUIV="Refresh" Content="0; URL=registerconfirm.php data-ajax="false"">'; 
   exit;
}

function enterSystem($connection, $userid)
{
   $sessionhash = createSessionHash($connection);
   
   $_SESSION["userid"] = $userid;
   $_SESSION["ip"] = $_SERVER["REMOTE_ADDR"];
   $_SESSION["recommendready"] = false;
   $_SESSION["randomready"] = false;
   $_SESSION["rerecommendready"] = false;
   $_SESSION["recommendcount"] = 0;
   $_SESSION["seedcount"] = 0;
   $_SESSION["sessionhash"] = $sessionhash;
   
   $_SESSION["accessstring"] = "";
   header("Location: jokes.php");
   //echo '<META HTTP-EQUIV="Refresh" Content="0; URL=jokes.php">'; 
   exit;
}
?>
<?php user_session() ?>
<?php
$htmlerror = "";
$htmlerrorflag = false;

if (!userLoggedIn())
{
   if (isset($_POST["email"])) //User is registering (out of session)
   {
      openConnection();

      $email = htmlspecialchars(strtolower($_POST["email"]), ENT_QUOTES);
      $password = $_POST["password"];
      $passwordagain = $_POST["passwordagain"];
      $heardabout = mysql_real_escape_string(htmlspecialchars($_POST["heardabout"], ENT_QUOTES));

      if (registrationValid($connection, $email, $password, $passwordagain))
      {
         $userid = register($connection, $email, $password, $heardabout);
         confirmRegistration($connection, $userid);
      }
      else
      {
         $htmlerrorflag = true;
         $htmlerror .= "<p class=\"error\">Error: ";
         $htmlerror .= $errorstr;
         $htmlerror .= "</p>";
      }

      mysql_close($connection);
   }
   else if (isset($_POST["heardabout"])) //User is entering session (without registering)
   {
      openConnection();

      $heardabout = mysql_real_escape_string(htmlspecialchars($_POST["heardabout"], ENT_QUOTES));

      $userid = register($connection, null, null, $heardabout);
      enterSystem($connection, $userid);

      mysql_close($connection);
   }
}
else
{
   if (isset($_POST["email"])) //User is registering (in session)
   {
      openConnection();
      $userid = $_SESSION["userid"];

      $email = htmlspecialchars(strtolower($_POST["email"]), ENT_QUOTES);
      $password = $_POST["password"];
      $passwordagain = $_POST["passwordagain"];

      if (registrationValid($connection, $email, $password, $passwordagain))
      {
         registerInSession($connection, $userid, $email, $password);
         confirmRegistration($connection, $userid);
      }
      else
      {

         $htmlerrorflag = true;
         $htmlerror .= "<div class=\"alert alert-error\">";
         $htmlerror .= $errorstr;
         $htmlerror .= "</div>";
      }

      mysql_close($connection);
   }
}
?>
<!-- <?php require_once("../includes/header.php") ?>
<div id="navigation">
<h3>
Register
</h3>
<?php require_once("../includes/navbar.php") ?>
</div> -->

<?php
if (isset($_POST["email"]) && ($htmlerrorflag == true))
{
   ?>
   <div id="topnotice">
      <?php print $htmlerror ?>
   </div>
   <?php
}
?>
<div id="main">
   <?php
   $registermessage = "<p>If you would like to be able to log back in at any time and resume where you left off, please complete the following form. Your email will be treated as your login. Please choose your password appropriately, and you will receive an email confirming your registration.</p>" . 
   "<p>We will not share your email with anyone.</p>" . 
   "<p>Please note that the <span class=\"required\">highlighted</span> fields are required, and that <span class=\"important\">your password must be at least four characters.</span></p>";

   if (!userLoggedIn())
   {
      print $registermessage;
      
      if (isset($_POST["email"]))
         $email = htmlspecialchars($_POST["email"], ENT_QUOTES);
      else
         $email = "";
      
      if (isset($_POST["heardabout"]))
         $heardabout = htmlspecialchars($_POST["heardabout"], ENT_QUOTES);
      else
         $heardabout = "";
      
      ?>
      <form action="newuser.php" method="post">
         <div id="registrationinput">
            <p>
               <span class="columnleft"><span class="required">Email:</span></span>
               <span class="columnright"><input name="email" type="text" maxlength="60" value="<?php print $email ?>" /></span>
            </p>
            <p>
               <span class="columnleft"><span class="required">Password:</span></span>
               <span class="columnright"><input name="password" type="password" maxlength="30" /></span>
            </p>
            <p>
               <span class="columnleft"><span class="required">Confirm Password:</span></span>
               <span class="columnright"><input name="passwordagain" type="password" maxlength="30" /></span>
            </p>
            <p>
               <span class="columnleft">Where did you hear about Jester?</span>
               <span class="columnright"><input name="heardabout" type="text" maxlength="255" value="<?php print $heardabout ?>" /></span>
            </p>
         </div>
         <p>
            <input name="submit" type="submit" value="Register" />
            <input name="reset" type="button" value="Reset" onclick="window.location.href = 'newuser.php'" />
         </p>
      </form>
      <?php
   }
   else
   {
      openConnection();
      $userid = $_SESSION["userid"];
      
      if (isRegistered($connection, $userid))
      {
         print "\n<p>\nYou are already <span class=\"important\">registered</span>.\n</p>\n";
      }
      else
      {
         print $registermessage;
         
         if (isset($_POST["email"]))
            $email = htmlspecialchars($_POST["email"], ENT_QUOTES);
         else
            $email = "";
         ?>
         <form action="newuser.php" method="post">
            <div id="registrationinput">
               <p>
                  <span class="columnleft"><span class="required">Email:</span></span>
                  <span class="columnright"><input name="email" type="text" maxlength="60" value="<?php print $email ?>" /></span>
               </p>
               <p>
                  <span class="columnleft"><span class="required">Password:</span></span>
                  <span class="columnright"><input name="password" type="password" maxlength="30" /></span>
               </p>
               <p>
                  <span class="columnleft"><span class="required">Confirm Password:</span></span>
                  <span class="columnright"><input name="passwordagain" type="password" maxlength="30" /></span>
               </p>
            </div>
            <p>
               <input name="submit" type="submit" value="Register" />
               <input name="reset" type="button" value="Reset" onclick="window.location.href = 'newuser.php'" />
            </p>
         </form>
         <?php
      }
      
      mysql_close($connection);
   }
   ?>
   <p>
      For questions regarding logins and passwords, contact the <a href="mailto:<?php print $webmaster ?>">webmaster</a>.
   </p>
</div>
<?php require_once("../includes/footer.php") ?>

</div>
</div>
</body>
</html>
