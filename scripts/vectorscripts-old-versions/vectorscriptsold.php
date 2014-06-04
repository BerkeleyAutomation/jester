<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("../includes/scriptfunctions.php") ?>
<?php
openConnection();
print("Running Offline Scripts\n\n");
setDownForMaintenance($connection, 1);
correl($connection);
genLayer($connection);
genClusters($connection);
genPredictVec($connection);
calcJokeCorrelation($connection);
enableRecommendation($connection);
#testAlgorithm($connection);
setDownForMaintenance($connection, 0);
print("\nOffline Scripts Complete\n");
mysql_close($connection);
?>
<?php
function correl($connection)
{
	global $covariance, $eigenvecs, $meanvector, $countvector, $covnormalizer, $correlnormx, $correlnormy, $eigenvals;
	global $numjokes, $predictjokes, $numseedjokes, $threshold, $ispredictor, $eigenaxes;
	global $projections, $projectionsforclustering, $useridsforprojections;
	
	$alphaflag = 0;
	$betaflag = 0;
	$numalphas = 0;
	$numbetas = 0;
	$numerrors = 0;
	$numlows = 0;
	$numusers = 0;
	
	//Initialize all the arrays that will be used
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		$meanvector[$i] = 0.0; //The mean rating for each joke $i (from users who qualify)
		$countvector[$i] = 0; //The number of ratings for each joke $i (from users who qualify)
		$histogram[$i] = 0; //$histogram[$i] is the number of users who rated $i jokes
	}
	
	$histogram[$i] = 0; //The histogram is of length ($numjokes + 1)
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($j = 0; $j < count($predictjokes); $j++)
		{
			$covariance[$i][$j] = 0.0;
			$eigenvecs[$i][$j] = 0.0;
			$covnormalizer[$i][$j] = 0;
			$correlnormx[$i][$j] = 0.0;
			$correlnormy[$i][$j] = 0.0;
		}
	}
	
	$query = "SELECT numrated, userid FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$numrated = $row[0];
		$userid = $row[1];
		
		$numusers++;
		
		//Skip user if an error occurs
		
		if ($numrated > $numjokes)
		{
			print($_POST["presystemerror"] . "User " . $userid . " has rated " . $numrated . " jokes." . $_POST["postsystemerror"]);
			$numerrors++;
			continue;
		}
		
		$histogram[$numrated]++;
		
		//Skip user if he/she has not rated enough jokes
		
		if ($numrated <= (count($predictjokes) + $numseedjokes))
		{
			$numlows++;
			continue;
		}
		
		if ($numrated > $threshold)
		{
			$alphaflag = 1;
			$betaflag = 0;
			setAlphaFlag($connection, $userid, $alphaflag);
			setBetaFlag($connection, $userid, $betaflag); //Must set this to prevent an old "true" value from being retained
			$numalphas++;
		}
		else
		{
			$alphaflag = 0;
			$betaflag = 1;
			setAlphaFlag($connection, $userid, $alphaflag); //Must set this to prevent an old "true" value from being retained
			setBetaFlag($connection, $userid, $betaflag);
			$numbetas++;
		}
		
		//Process user ratings
		
		$query = "SELECT jokeid, jokerating FROM ratings WHERE userid={$userid}";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
		while ($row = mysql_fetch_array($resultinner))
		{
			$jokeid = $row[0];
			$jokerating = $row[1];
			$jokeindex = $jokeid - 1;
			
			$meanvector[$jokeindex] += $jokerating;
			$countvector[$jokeindex]++;
		}
	}
	
	//Store the histogram
	
	clearTable($connection, "histogram");
	
	for ($i = 0; $i < ($numjokes + 1); $i++)
	{
		$query = "INSERT INTO histogram (numjokes, numusers) VALUES ({$i}, {$histogram[$i]})";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}

	//Compile the statistics
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		if ($countvector[$i] != 0)
			$meanvector[$i] = $meanvector[$i] / $countvector[$i];
		//else $meanvector[$i] = 0.0 (neutral rating, because no one has rated it yet)
	}
	
	//Store the mean ratings for each joke (from users who qualify)
	//Store the number of ratings for each joke (from users who qualify)
	
	clearTable($connection, "ratingtotals");
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		$jokeid = $i + 1;
		
		$query = "INSERT INTO ratingtotals (jokeid, meanrating, numratings) VALUES ({$jokeid}, {$meanvector[$i]}, {$countvector[$i]})";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
	
	//Store other statistics
	
	clearTable($connection, "statistics");
	
	$query = "INSERT INTO statistics (numalphas, numbetas, numerrors, numlows, numusers) VALUES ({$numalphas}, {$numbetas}, {$numerrors}, {$numlows}, {$numusers})";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	//Calculate covariance
	
	clearTable($connection, "usercovariances");
	clearTable($connection, "pairs");
	clearTable($connection, "covariance");

	calcCovariance($connection, "alphaflag=1"); //Alpha users
	calcCovariance($connection, "betaflag=1"); //Beta users
	
	//Normalize covariance matrix and store it, along with the pairs
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($j = 0; $j < count($predictjokes); $j++)
		{
			if ((sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j])) != 0)
				$covariance[$i][$j] = $covariance[$i][$j] / (sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j]));
			else //One or both of the jokes was not rated, so $correlnormx[$i][$j] and/or $correlnormy[$i][$j] are equal to zero
				$covariance[$i][$j] = 0; //So, if one or both of the jokes was not rated, there is no correlation
			
			//Status = -1: Low
			//Status = 0: Normal
			//Status = 1: High
			
			$status = 0;
			
			if (($covariance[$i][$j] < 0.1) && ($i < $j))
			{	
				$status = -1;
			}
			
			if (($covariance[$i][$j] > 0.2) && ($i < $j))
			{
				$status = 1;
			}
			
			if ($status != 0)
			{
				$query = "INSERT INTO pairs (row, col, covariance, status) VALUES ({$i}, {$j}, {$covariance[$i][$j]}, {$status})";
				$result = mysql_query($query, $connection);
				if (!$result)
					die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			}
			
			$query = "INSERT INTO covariance (row, col, covariance) VALUES ({$i}, {$j}, {$covariance[$i][$j]})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
	
	//Calculate eigenvectors and eigenvalues
	
	jacobi(count($predictjokes));
	eigSrt(count($predictjokes));
	
	//Store eigenvectors and eigenvalues
	
	clearTable($connection, "eigenvalues");
	clearTable($connection, "eigenvectors");
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		$query = "INSERT INTO eigenvalues (eigenvalueindex, eigenvalue) VALUES ({$i}, {$eigenvals[$i]})";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($j = 0; $j < count($predictjokes); $j++)
		{
			$query = "INSERT INTO eigenvectors (row, col, eigenvectorelement) VALUES ({$i}, {$j}, {$eigenvecs[$i][$j]})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
	
	//Calculate and store projections
	
	clearTable($connection, "projection");
	
	$usercount = 0;
	$projections = array();
	$projectionsforclustering = array();
	$useridsforprojections = array();
	calcProjection($connection, "alphaflag=1", $eigenaxes, $usercount); //Alpha users
	calcProjection($connection, "betaflag=1", $eigenaxes, $usercount); //Beta users
	
	print("Completed: Correlation\n");
}

function calcJokeCorrelation($connection)
{
	global $numjokes, $meanvector;
	
	$covariance = array();
	$correlnormx = array();
	$correlnormy = array();
	$correlation = array();

	for ($i = 0; $i < $numjokes; $i++)
	{
		for ($j = 0; $j < $numjokes; $j++)
		{
			$covariance[$i][$j] = 0.0;
			$correlnormx[$i][$j] = 0.0;
			$correlnormy[$i][$j] = 0.0;
			$correlation[$i][$j] = 0.0;
		}
	}
	
	$query = "SELECT userid FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
  		$userid = $row[0];
		$ratingvect = array();

		for ($i = 0; $i < $numjokes; $i++)
		{
			$jokeid = $i + 1;
			
			if (isRated($connection, $userid, $jokeid))
			{
				$jokerating = getJokeRating($connection, $userid, $jokeid);
				
				$ratingvect[$i] = $jokerating - $meanvector[$i];
			}
			else
			{
				$ratingvect[$i] = false;
			}
		}
		
		for ($i = 0; $i < $numjokes; $i++)
		{
			for ($j = 0; $j < $numjokes; $j++)
			{
				if (!(($ratingvect[$i] === false) || ($ratingvect[$j] === false)))
				{
					$covij = $ratingvect[$i] * $ratingvect[$j]; //This represents the covariance of joke i and joke j (for this user)
					$correlnormx[$i][$j] += $ratingvect[$i] * $ratingvect[$i];
					$correlnormy[$i][$j] += $ratingvect[$j] * $ratingvect[$j];
				}
				else
				{
					$covij = 0.0;
				}
				
				$covariance[$i][$j] += $covij;
			}
		}
	}
	
	clearTable($connection, "jokecorrelations");
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		for ($j = 0; $j < $numjokes; $j++)
		{	
			if ((sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j])) != 0)
				$correlation[$i][$j] = $covariance[$i][$j] / (sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j]));
			else //One or both of the jokes was not rated, so $correlnormx[$i][$j] and/or $correlnormy[$i][$j] are equal to zero
				$correlation[$i][$j] = 0; //So, if one or both of the jokes was not rated, there is no correlation
				
			$jokex = $i + 1;
			$jokey = $j + 1;
			$correlxy = $correlation[$i][$j];
				
			$query = "INSERT INTO jokecorrelations (jokex, jokey, correlxy) VALUES ({$jokex}, {$jokey}, {$correlxy})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
	
	print("Completed: Joke Correlation\n");
}

//Calculate the global covariance matrix and normalization matrices
function calcCovariance($connection, $condition)
{
	global $predictjokes, $meanvector, $covariance, $covnormalizer, $correlnormx, $correlnormy;
	
	$query = "SELECT userid FROM users WHERE {$condition}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
  		$userid = $row[0];
		
		for ($i = 0; $i < count($predictjokes); $i++)
		{
			$jokeid = $predictjokes[$i];
			
			if (isRated($connection, $userid, $jokeid)) //The user may not have necessarily rated every prediction joke 
			{											//as new ones may have been added
				$jokerating = getJokeRating($connection, $userid, $jokeid);
				
				$ratingvect[$i] = $jokerating - $meanvector[$jokeid - 1];
			}
			else
				$ratingvect[$i] = false;
		}
		
		//Calculate covariance matrix for this user
		
		for ($i = 0; $i < count($predictjokes); $i++)
		{
			for ($j = 0; $j < count($predictjokes); $j++)
			{
				if (!(($ratingvect[$i] === false) || ($ratingvect[$j] === false)))
				{
					$covij = $ratingvect[$i] * $ratingvect[$j]; //This represents the covariance of joke i and joke j (for this user)
					$covnormalizer[$i][$j]++; //Keep track of rated contributions to the global covariance matrix
					$correlnormx[$i][$j] += $ratingvect[$i] * $ratingvect[$i];
					$correlnormy[$i][$j] += $ratingvect[$j] * $ratingvect[$j];
				}
				else
				{
					$covij = 0.0; //If the user did not rate one or two of these prediction jokes, the covariance is zero (rather than positive or negative)
				}
				
				$query = "INSERT INTO usercovariances (userid, row, col, covariance) VALUES ({$userid}, {$i}, {$j}, {$covij})";
				$resultinner = mysql_query($query, $connection);
				if (!$resultinner)
					die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
				
				$covariance[$i][$j] += $covij; //Add the covariance to the global covariance matrix
			}
		}
	}
}

//Project on eigenplane and store projections
function calcProjection($connection, $condition, $numaxes, &$usercount)
{
	global $eigenvecs, $projections, $projectionsforclustering, $useridsforprojections;
	
	$query = "SELECT userid FROM users WHERE " . $condition;		
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	while ($row = mysql_fetch_array($result))
	{
		$userid = $row[0];
		
		$projection = array();
		project($connection, $projection, $eigenvecs, $userid, $numaxes);
		
		$useridsforprojections[$usercount] = $userid;
		
		for ($axis = 0; $axis < $numaxes; $axis++)
		{
			$projections[$usercount][$axis] = $projection[$axis]; //This will get changed by genLayer
			$projectionsforclustering[$usercount][$axis] = $projection[$axis]; //A version needs to be retained until genClusters
			
			$query = "INSERT INTO projection (userid, axis, projectionvalue) VALUES ({$userid}, {$axis}, {$projection[$axis]})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
		
		$usercount++;
	}
}

//Calculate the eigenvectors and eigenvalues
function jacobi($n)
{
	global $covariance, $eigenvecs, $eigenvals;
	
	for ($ip = 0; $ip < $n; $ip++)
	{
		for ($iq = 0; $iq < $n; $iq++)
			$eigenvecs[$ip][$iq] = 0.0;
		$eigenvecs[$ip][$ip] = 1.0;
	}
	
	for ($ip = 0; $ip < $n; $ip++)
	{
		$b[$ip] = $covariance[$ip][$ip];
		$eigenvals[$ip] = $covariance[$ip][$ip];
		$z[$ip] = 0.0;
	}
	
	for ($i = 1; $i <= 100; $i++)
	{
		$sm = 0.0;
		
		for ($ip = 0; $ip < ($n - 1); $ip++)
		{
			for ($iq = ($ip + 1); $iq < $n; $iq++)
				$sm += abs($covariance[$ip][$iq]);
		}
		
		if ($sm == 0.0)
		{
			return;
		}
		
		if ($i < 4)
			$tresh = 0.2 * ($sm / ($n * $n));
		else
			$tresh = 0.0;
			
		for ($ip = 0; $ip < ($n - 1); $ip++)
		{
			for ($iq = ($ip + 1); $iq < $n; $iq++)
			{
				$g = 100.0 * abs($covariance[$ip][$iq]);
				
				if (($i > 4) && 
					((abs($eigenvals[$ip]) + $g) == abs($eigenvals[$ip])) && 
					((abs($eigenvals[$iq]) + $g) == abs($eigenvals[$iq])))
				{
					$covariance[$ip][$iq] = 0.0;
				}
				else if (abs($covariance[$ip][$iq]) > $tresh)
				{
					$h = $eigenvals[$iq] - $eigenvals[$ip];
					
					if ((abs($h) + $g) == abs($h))
					{
						if ($h != 0.0)
							$t = ($covariance[$ip][$iq]) / $h;
						else
							$t = 0.0;
					}
					else
					{
						if ($covariance[$ip][$iq] != 0.0)
							$theta = 0.5 * ($h / $covariance[$ip][$iq]);
						else
							$theta = 0.0;
							
						$t = 1.0 / (abs($theta) + sqrt(1.0 + ($theta * $theta)));
						
						if ($theta < 0.0)
							$t = -$t;
					}
					
					$c = 1.0 / sqrt(1 + ($t * $t));
					$s = $t * $c;
					$tau = $s / (1.0 + $c);
					$h = $t * $covariance[$ip][$iq];
					$z[$ip] -= $h;
					$z[$iq] += $h;
					$eigenvals[$ip] -= $h;
					$eigenvals[$iq] += $h;
					$covariance[$ip][$iq] = 0.0;
					
					for ($j = 0; $j < ($ip - 1); $j++)
					{
						rotate($covariance, $j, $ip, $j, $iq, $s, $tau);
					}
					
					for ($j = ($ip + 1); $j < ($iq - 1); $j++)
					{
						rotate($covariance, $ip, $j, $j, $iq, $s, $tau);
					}
					
					for ($j = ($iq + 1); $j < $n; $j++)
					{
						rotate($covariance, $ip, $j, $iq, $j, $s, $tau);
					}
					
					for ($j = 0; $j < $n; $j++)
					{
						rotate($eigenvecs, $j, $ip, $j, $iq, $s, $tau);
					}
				}
			}
		}
		
		for ($ip = 0; $ip < $n; $ip++)
		{
			$b[$ip] = $b[$ip] + $z[$ip];
			$eigenvals[$ip] = $b[$ip];
			$z[$ip] = 0.0;
		}
	}
}

function rotate(&$array, $i, $j, $k, $l, $s, $tau)
{	
	$g = $array[$i][$j];
	$h = $array[$k][$l];
	$array[$i][$j] = $g - ($s * ($h + ($g * $tau)));
	$array[$k][$l] = $h + ($s * ($g - ($h * $tau)));
}

//Sort eigenvectors by rank of eigenvalues
function eigSrt($n)
{
	global $eigenvecs, $eigenvals;
	
	for ($i = 0; $i < $n - 1; $i++)
	{
		$p = $eigenvals[$k = $i];
		
		for ($j = $i + 1; $j < $n; $j++)
		{
			if ($eigenvals[$j] >= $p)
				$p = $eigenvals[$k = $j];
		}
		
		if ($k != $i)
		{
			$eigenvals[$k] = $eigenvals[$i];
			$eigenvals[$i] = $p;
			
			for ($j = 0; $j < $n; $j++)
			{
				$p = $eigenvecs[$j][$i];
				$eigenvecs[$j][$i] = $eigenvecs[$j][$k];
				$eigenvecs[$j][$k] = $p;
			}
		}
	}
}

function setAlphaFlag($connection, $userid, $alphaflag)
{
	$query = "UPDATE users SET alphaflag={$alphaflag} WHERE userid={$userid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

function setBetaFlag($connection, $userid, $betaflag)
{
	$query = "UPDATE users SET betaflag={$betaflag} WHERE userid={$userid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

function genLayer($connection)
{	
	global $projections, $layer, $layersize, $eigenaxes;

	$layer = array();
	
	//Generate layer
	
	recurseMedian(0, 0, 0, 0, (count($projections) - 1));
	
	//Store layer
	
	clearTable($connection, "layer");
	
	for ($i = 0; $i < $layersize; $i++)
	{
		$query = "INSERT INTO layer (layerindex, layervalue) VALUES ({$i}, {$layer[$i]})";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
	
	print("Completed: Layer Generation\n");
}

//Sorts the array between the indices $i and $j ($col picks which column of the array should be sorted)
function quickSort(&$arr, $i, $j, $col)
{
	if ($i < $j)
	{
		$k = partition($arr, $i, $j, $col);
		quickSort($arr, $i, $k, $col);
		quickSort($arr, ($k + 1), $j, $col);
	}
}

//Picks a pivot and sorts the array $arr into elements greater than the pivot or elements less than the pivot ($col picks which column of the array should be sorted)
function partition(&$arr, $left, $right, $col)
{
	$pivot = $arr[(int) (($left + $right) / 2)][$col]; //The index needs to be cast to an integer, as an index cannot be a float
	$left--;
	$right++;
	
	while (1)
	{
		do
			$right--;
		while ($arr[$right][$col] > $pivot);
		
		do
			$left++;
		while ($arr[$left][$col] < $pivot);
		
		if ($left < $right)
		{
			$temp = $arr[$left];
			$arr[$left] = $arr[$right];
			$arr[$right] = $temp;
		}
		else
			return $right;
	}
}

//Determines the median of the values $left and $right for the column $col.
function detMedians(&$array, $left, $right, $col)
{
	$above = 0;
	
	$medInd = (int) (($left + $right) / 2); //Needs to be cast to an integer, as an index cannot be a float
	$medValue = $array[$medInd][$col];
	
	while ($array[$medInd + $above][$col] == $medValue)
	{
		$above++;
		
		if (($medInd + $above) >= count($array))
		{
			break;
		}
	}
	
	return ($medInd + $above);
}

//Recursively determines the layer
function recurseMedian($level, $index, $col, $left, $right)
{
	global $projections, $layer, $clusterlevels;
	
	if ($level == $clusterlevels)
		return;
	
	//Left child
	
	quickSort($projections, $left, $right, $col);
	$midInd = detMedians($projections, $left, $right, $col);
	
	if ($midInd >= count($projections))
		$layer[$index] = 0.0;
	else
		$layer[$index] = $projections[$midInd][$col];
	
	recurseMedian(($level + 1), ((2 * ($index + 1)) - 1), flip($col), $left, ($midInd - 1));

	//Right child
	
	recurseMedian(($level + 1), (2 * ($index + 1)), flip($col), $midInd, $right);
}

function genClusters($connection)
{	
	global $eigenaxes, $layer, $projectionsforclustering, $useridsforprojections, $clusters, $useridsforclusters;
	
	clearTable($connection, "clusters");
	
	$usercount = 0;
	$clustercount = 0;
	$clusterindex = 0;

	$clusters = array();
	$useridsforclusters = array();
	
	while ($usercount < count($projectionsforclustering))
	{	
		$cluster = getClusterName($layer, $projectionsforclustering[$usercount]);
		$userid = $useridsforprojections[$usercount];
		
		//This code makes sure that the cluster index is only incremented when a new cluster is found, and that
		//it is temporarily reverted back to the older cluster index if it was already seen

		if (!empty($clusters))
		{
			if (!in_array($cluster, $clusters))
			{
				$clustercount++;
				$clusterindex = $clustercount;
			}
			else
			{
				$clusterindex = array_search($cluster, $clusters);
			}
		}
		//else $clusterindex = 0
		
		$clusters[$clusterindex] = $cluster;
		$useridsforclusters[$clusterindex][] = $userid;
		
		$query = "INSERT INTO clusters (userid, cluster) VALUES ({$userid}, '{$cluster}')";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
		$usercount++;
	}
	
	print("Completed: Cluster Generation\n");
}

function genPredictVec($connection)
{
	global $numjokes, $predictjokes, $ispredictor, $clusters, $useridsforclusters;
	global $maxjokerating, $minjokerating;
	
	$clusterindex = 0;
	
	$oldclustermeans = array();
			
	while ($clusterindex < count($clusters))
	{
		$cluster = $clusters[$clusterindex];

		//Get old cluster means for use when calculating the mean error
		//Reason: Error is based on what the system was like when the user used it, which is before this script was run
		$query = "SELECT meanrating, jokeid FROM clustermeans WHERE cluster='{$cluster}'";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($result))
		{
			$jokeid = $row[1];
			$jokeindex = $jokeid - 1;
			
			$oldclustermeans[$clusterindex][$jokeindex] = $row[0];
		}
		
		$clusterindex++;
	}
	
	clearTable($connection, "clustermeans");
	clearTable($connection, "predictvectors");
		
	$clusterindex = 0;
	$maesumtotal = 0;
	$usercount = 0;
	
	while ($clusterindex < count($clusters))
	{
		$maesumcluster = 0;
		
		$cluster = $clusters[$clusterindex];
		
		for ($i = 0; $i < $numjokes; $i++)
		{
			$meanclustervector[$i] = 0.0;
			$countclustervector[$i] = 0;
			$jokeindexvector[$i] = $i;
		}

		for ($i = 0; $i < count($useridsforclusters[$clusterindex]); $i++)
		{
			$maesumuser = 0;
				
			$userid = $useridsforclusters[$clusterindex][$i];
			$numrated = getNumRated($connection, $userid);
			
			if ($numrated > $numjokes)
			{
				print($_POST["presystemerror"] . "User " . $userid . " has rated " . $numrated . " jokes." . $_POST["postsystemerror"]);
				$numerrors++;
				continue;
			}
			
			for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
			{
				$jokeid = $jokeindex + 1;
				
				if (isRated($connection, $userid, $jokeid))
				{
					$predictedrating = $oldclustermeans[$clusterindex][$jokeindex]; //The predicted rating (for use in calculating the error)
					$jokerating = getJokeRating($connection, $userid, $jokeid); //The actual rating
					
					$maesumuser += abs($jokerating - $predictedrating); //The sum used to calculate mean absolute error for this user

					$meanclustervector[$jokeindex] += $jokerating;
					$countclustervector[$jokeindex]++;
				}
			}
			
			$maeuser = $maesumuser / $numrated;
			$nmaeuser = $maeuser / ($maxjokerating - $minjokerating);
						
			$maesumcluster += $maeuser;
			$maesumtotal += $maeuser;
			
			$usercount++;
		}
		
		$maecluster = $maesumcluster / count($useridsforclusters[$clusterindex]);
		$nmaecluster = $maecluster / ($maxjokerating - $minjokerating);
		
		for ($i = 0; $i < $numjokes; $i++)
		{
			if ($countclustervector[$i] != 0)
				$meanclustervector[$i] = ($meanclustervector[$i] / $countclustervector[$i]);
			else
				$meanclustervector[$i] = 0.0;
		}
		
		//Sort the mean ratings and joke indices for this cluster in order of mean rating
		for ($i = 0; $i < ($numjokes - 1); $i++)
		{
			for ($j = ($i + 1); $j < $numjokes; $j++)
			{
				if ($meanclustervector[$j] > $meanclustervector[$i]) //$j is rated higher
				{
					$temp = $meanclustervector[$j];
					$meanclustervector[$j] = $meanclustervector[$i];
					$meanclustervector[$i] = $temp;
					
					$temp = $jokeindexvector[$j];
					$jokeindexvector[$j] = $jokeindexvector[$i];
					$jokeindexvector[$i] = $temp;
				}
			}
		}
		
		//Store mean ratings and the prediction vector (joke indices sorted by rank) for this cluster
		
		$rank = 0;

		for ($i = 0; $i < $numjokes; $i++)
		{
			$meanrating = $meanclustervector[$i];
			$jokeid = $jokeindexvector[$i] + 1;
			
			$query = "INSERT INTO clustermeans (cluster, jokeid, meanrating) VALUES ('{$cluster}', {$jokeid}, {$meanrating})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
			if (!$ispredictor[$jokeindexvector[$i]])
			{	
				$query = "INSERT INTO predictvectors (cluster, rank, jokeid) VALUES ('{$cluster}', {$rank}, {$jokeid})";
				$resultinner = mysql_query($query, $connection);
				if (!$resultinner)
					die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
				
				$rank++;
			}
		}
		
		$clusterindex++;
	}
	
	$maetotal = $maesumtotal / $usercount;
	$nmaetotal = $maetotal / ($maxjokerating - $minjokerating);
	
	print("MAE: " . sprintf("%.2f", $maetotal) . "\n");
	print("NMAE: " . sprintf("%.2f", $nmaetotal) . "\n");
	
	print("Completed: Prediction Vector Generation and Error Calculation\n");
}

function enableRecommendation($connection)
{
	$canberecommended = 1;
	
	$query = "UPDATE jokes SET canberecommended={$canberecommended}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	print("Completed: Recommendation Enabling\n");
}

function setDownForMaintenance($connection, $downformaintenance)
{
	$query = "UPDATE maintenance SET downformaintenance={$downformaintenance}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

function getJokeCorrel($connection, $jokex, $jokey)
{
	$query = "SELECT correlxy FROM jokecorrelations WHERE jokex={$jokex} AND jokey={$jokey}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	$row = mysql_fetch_row($result);
	$correlxy = $row[0];
	
	return $correlxy;
}

function testAlgorithm($connection)
{
	global $numjokes;
	
	$testusers = array(320);
	
	$query = "SELECT userid FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$userid = $row[0];
		$numrated = 0;
		
		$usermeans = array();
		
		for ($i = 0; $i < $numjokes; $i++)
		{
			$usermeans[$i] = 0;
		}
		
		if (in_array($userid, $testusers))
		{	
			$query = "SELECT jokeid, jokerating FROM ratings WHERE userid={$userid}";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
			while ($row = mysql_fetch_array($resultinner))
			{
				//User rates a joke
			
				$jokeid = $row[0];
				$jokerating = $row[1];
				$jokeindex = $jokeid - 1;
			
				$numrated++;
				
				if ($jokeid == 7)
				print "\nrating for $jokeid = " . $jokerating . "\n";
			
				//Find the rated joke's correlation with all other jokes
				
				for ($otherjokeindex = 0; $otherjokeindex < $numjokes; $otherjokeindex++)
				{
					$otherjokeid = $otherjokeindex + 1;
					
					$correl = getJokeCorrel($connection, $jokeid, $otherjokeid);
					
					if ($jokeid == 7)
					print "correl of $jokeid and $otherjokeid = " . $correl . "\n";
					
					//The mean array for each otherjoke, based on the correlations with them and the rated joke
				
					if ($jokeid == 7)
						print "usermeans[$otherjokeindex] = " . "((" . $usermeans[$otherjokeindex] . " * " . ($numrated - 1) . ") + ($correl * $jokerating)) / $numrated" . " = " . (($usermeans[$otherjokeindex] * ($numrated - 1)) + ($correl * $jokerating)) / $numrated . "\n";
						
					$usermeans[$otherjokeindex] = (($usermeans[$otherjokeindex] * ($numrated - 1)) + ($correl * $jokerating)) / $numrated;
				}
				
			}
			
			print "User $userid:\n\n";

			print "User Means:\n\n";		
				
			arsort($usermeans);
			
			foreach ($usermeans as $jokeid => $usermean)
			{
				print "User Mean for $jokeid = " . $usermean . "\n";
			}
			
			$query = "SELECT cluster FROM clusters WHERE userid={$userid}";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

			$row = mysql_fetch_row($resultinner);
			$cluster = $row[0];
		
			$clustermeans = array();
		
			print "\nCluster Means\n\n";
		
			$query = "SELECT meanrating, jokeid FROM clustermeans WHERE cluster='{$cluster}' ORDER BY meanrating DESC";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

			while ($row = mysql_fetch_array($resultinner))
			{
				$meanrating = $row[0];
				$jokeid = $row[1];
				$jokeindex = $jokeid - 1;
			
				print "Cluster Mean for $jokeid = " . $meanrating . "\n";
			}
		}
	}
}
?>