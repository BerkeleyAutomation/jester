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
function addRemoveSuggestedJokes($connection)
{
	global $messagearray, $minjokelength, $maxjokelength, $displayaddedarray;

	if (isset($_POST["addsuggestedjoke"]) || isset($_POST["removesuggestedjoke"]))
	{
		$suggestedjokearray = $_POST["joketext"];
		$suggestedjokeidarray = $_POST["jokeid"];
		
		if (isset($_POST["addsuggestedjoke"]))
			$suggestedjokeidstoadd = $_POST["addsuggestedjoke"];
			
		if (isset($_POST["removesuggestedjoke"]))
			$suggestedjokeidstoremove = $_POST["removesuggestedjoke"];

		for ($i = 0; $i < count($suggestedjokearray); $i++)
		{
			$suggestedjokeid = mysql_real_escape_string(htmlspecialchars($suggestedjokeidarray[$i], ENT_QUOTES));
			
			if (isset($_POST["addsuggestedjoke"]) && in_array($suggestedjokeid, $suggestedjokeidstoadd))
			{
				//Validation

				if (empty($suggestedjokearray[$i]))
				{
					$messagearray[$suggestedjokeid] = "You cannot submit a blank joke.";
					continue;
				}

				if (strlen($suggestedjokearray[$i]) < $minjokelength)
				{
					$messagearray[$suggestedjokeid] = "Joke is too short.";
					continue;
				}

				if (strlen($suggestedjokearray[$i]) > $maxjokelength)
				{
					$messagearray[$suggestedjokeid] = "Joke is too long. It cannot exceed " . number_format($maxjokelength) . " characters.";
					continue;
				}
				
				$htmldisplayjoke = "<p>\n" . nl2br(trim(htmlspecialchars($suggestedjokearray[$i], ENT_QUOTES))) . "\n</p>\n";
				$displayaddedarray[] = $htmldisplayjoke;
				$submittedjoke = mysql_real_escape_string($htmldisplayjoke);
				addJoke($connection, $submittedjoke);
				removeSuggestedJoke($connection, $suggestedjokeid);
			}
			else if (isset($_POST["removesuggestedjoke"]) && in_array($suggestedjokeid, $suggestedjokeidstoremove))
			{
				$htmldisplayjoke = "<p>\n" . nl2br(trim(htmlspecialchars($suggestedjokearray[$i], ENT_QUOTES))) . "\n</p>\n";
				$displayremovedarray[] = $htmldisplayjoke;
				removeSuggestedJoke($connection, $suggestedjokeid);
			}
		}
	}
}

function removeSuggestedJoke($connection, $suggestedjokeid)
{
	$query = "DELETE FROM suggestedjokes WHERE jokeid={$suggestedjokeid}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
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

function areSuggestedJokes($connection)
{
	$query = "SELECT * FROM suggestedjokes";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	if (mysql_num_rows($result) == 0)
		return false;
		
	return true;
}

function viewSuggestedJokes($connection, &$messagearray)
{
	$query = "SELECT jokeid, joketext FROM suggestedjokes ORDER BY jokeid";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	$count = 0;

	while ($row = mysql_fetch_array($result))
	{
		$jokeid = $row[0];
		$joketext = $row[1];
		
		if (($count % 2) == 0)
			$class = "suggestedjokesa";
		else
			$class = "suggestedjokesb";
			
		if (isset($_POST["joketext"]))
		{
			if ($jokeindex = array_search($jokeid, $_POST["joketext"]))
			{
				$joketext = htmlspecialchars($_POST["joketext"][$jokeindex], ENT_QUOTES);
			}
		}

		if (!empty($messagearray[$jokeid]))
		{
			print "<tr class=\"incrementalnotice\">\n";
			print "<td colspan=\"3\">\n";
			print "<p class=\"error\">\nError: ";
			print $messagearray[$jokeid];
			print "\m</p>\n";
		}
?>
</td>
</tr>
<tr class="<?php print $class ?>">
<td class="addsuggestedjoke">
<div class="checkboxa">
<input type="checkbox" name="addsuggestedjoke[]" value="<?php print $jokeid ?>" />
</div>
</td>
<td class="removesuggestedjoke">
<div class="checkboxb">
<input type="checkbox" name="removesuggestedjoke[]" value="<?php print $jokeid ?>" />
</div>
</td>
<td class="joketext">
<input type="hidden" name="jokeid[]" value="<?php print $jokeid ?>" />
<textarea name="joketext[]" type="text"><?php print $joketext ?></textarea>
</td>
</tr>
<?php
		$count++;
	}
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
?>
<?php require("../includes/adminheader.php") ?>
<script language="JavaScript" type="text/javascript">
<!--
function toggleChecked(oElement, elementName) 
{ 
  oForm = oElement.form; 
  oElement = oForm.elements[elementName]; 
  if(oElement.length) 
  { 
    bChecked = oElement[0].checked; 
    for(i = 1; i < oElement.length; i++) 
      oElement[i].checked = bChecked; 
  } 
}

function toggleController(oElement)
{oForm=oElement.form;oElement=oForm.elements[oElement.name];if(oElement.length)
{bChecked=true;nChecked=0;for(i=1;i<oElement.length;i++)
if(oElement[i].checked)
nChecked++;if(nChecked<oElement.length-1)
bChecked=false;oElement[0].checked=bChecked;}}

-->
</script>
<div id="navigation">
<h3>
Approve Jokes
</h3>
<?php require_once("../includes/adminnavbar.php") ?>
</div>
<?php
openConnection();

if (isset($_POST["joketext"]))
{
	addRemoveSuggestedJokes($connection);
	
	$begin = true;
	
	for ($i = 0; $i < count($displayaddedarray); $i++)
	{
		if ($begin == true)
		{
			print "<div id=\"topnotice\">\n";
			print "<p>\nThe following jokes have been added:\n</p>\n";
			$begin = false;
		}
		
		print "<p>\n<textarea class=\"noedit\" readonly>" . $displayaddedarray[$i] . "</textarea>\n</p>\n";
	}
	
	if ($begin == false)
		print "</div>\n";
}

if (areSuggestedJokes($connection))
{
?>
<div id="suggestedjokescontainer">
<table id="suggestedjokes">
<form action="../admin/approvejokes.php" id="addsuggestedjoke" method="post">
<tr>
<th class="addsuggestedjoke">
<div class="checkboxa">
<input type="checkbox" name="addallsuggestedjokes" onclick="toggleChecked(this, 'addsuggestedjoke[]')" />
</div>
</th>
<th class="removesuggestedjoke">
<div class="checkboxb">
<input type="checkbox" name="removeallsuggestedjokes" onclick="toggleChecked(this, 'removesuggestedjoke[]')" />
</div>
</th>
<th class="joketext">
<h4>
Suggested Jokes
</h4>
</th>
</tr>
<?php viewSuggestedJokes($connection, $messagearray) ?>
</table>
</div>
<?php
mysql_close($connection);
?>
<div id="main">
<p>
<input name="submitapprove" type="submit" value="Approve and Update" />
<input name="resetapprove" type="reset" value="Reset" />
</p>
</form>
</div>
<?php
}
else
{
?>
<div id="main">
<p>
There are no suggested jokes in the database.
</p>
</div>
<?php	
}
?>
<?php require("../includes/footer.php") ?>