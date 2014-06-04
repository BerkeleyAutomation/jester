	   <?php if (!userLoggedIn()): ?>
	   <ul>
	   	<li><a href="index.php" data-theme="c" data-ajax=false>Home</a></li>
	   	<li><a href="about.php" data-theme="c">About</a></li>
	   	<li><a href="newuser.php" data-theme="c">Register</a></li>
	   	<li><a href="login.php" data-theme="c">Login</a></li>		
	   </ul>
	<?php endif; ?>

	<?php if (userLoggedIn()): ?>
	<ul>
		<li><a href="index.php" data-theme="c" data-ajax=false>Home</a></li>
		<li><a href="about.php" data-theme="c">About</a></li>
		<li><a href="jokes.php" data-theme="c">Jokes</a></li>
		
		
		
		<?php 
		openConnection();
		$userid = $_SESSION["userid"];
		if (!isRegistered($connection, $userid))
			{ ?>
		<li><a href="newuser.php" data-theme="c">Register</a></li>
		<li><a href="logout.php"data-theme="c">End</a></li>

		<?php
	}
	else
		{ ?>
	<!-- for space considerations, feedback link only shown to registered users. -->
	<li><a href="feedback.php" data-theme="c">Feedback</a></li>
	<li><a href="logout.php" data-theme="c">Logout</a></li>

	<?php
} ?>

</ul>
<?php endif; ?>					      
