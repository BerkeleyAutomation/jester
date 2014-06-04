<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php
//Project on eigenplane
function project($connection, &$projection, &$vectors, $userid, $numaxes)
{
	global $predictjokes;
	
	$mean = 0;
	$count = 0;
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		$jokeid = $predictjokes[$i];
		
		if (isRated($connection, $userid, $jokeid))
		{
			$jokerating = getJokeRating($connection, $userid, $jokeid);
	
			$mean += $jokerating;
			$count++;
			
			$ratingvect[$i] = $jokerating;
		}
		else
			$ratingvect[$i] = false;
	}
	
	//Create the projection array for this user
	
	$mean = $mean / $count;
	//print "mean: $mean\n";
	
	for ($axis = 0; $axis < $numaxes; $axis++)
		$projection[$axis] = 0.0;
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		if ($ratingvect[$i] === false)
			$ratingvect[$i] = $mean;
		
		for ($axis = 0; $axis < $numaxes; $axis++)
		{
			$projection[$axis] += $ratingvect[$i] * $vectors[$i][$axis];
			//print "projection[$axis] += " . $ratingvect[$i] . " (ratingvect[$i]) * " . $vectors[$i][$axis] . " (vectors[$i][$axis])\n";
		}
	}
}

function getClusterName(&$layer, &$coordinate)
{
	global $clusterlevels;
	
	$cluster = "";
	$level = 0;
	$col = 0;
	$i = 0;
	
	while ($level < $clusterlevels)
	{	
		/*
		Since $layer is from the database, it has lost some accuracy. Even if $layer[$i] and $coordinate[$col] have
		the same value, the truncation from the database will make the values slightly different. Truncating them
		both using sprintf will result in a proper comparison. A MySQL float is 7 characters wide (with the decimal),
		so truncating both to 7 characters (including the decimal) results in both elements having the same accuracy,
		which will be as accurate or less accurate (i.e. negatives, because of the "-") than MySQL float accuracy.
		*/
		
		if (sprintf("%.7s", $layer[$i]) < sprintf("%.7s", $coordinate[$col]))
		{
			$i = 2 * ($i + 1);
			$col = flip($col);
			$cluster .= "1";
		}
		else
		{
			$i = (2 * ($i + 1)) - 1;
			$col = flip($col);
			$cluster .= "0";
		}
		
		$level++;
	}
	
	return $cluster;
}

function flip($num)
{
	if ($num == 0)
		return 1;
	else
		return 0;
}
?>