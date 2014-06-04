<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/autologout.php") ?>
<?php require_once("../includes/authentication.php") ?>
<?php user_session() ?>
<?php user_session_authenticate_accessstring("rerecommend") ?>
<?php require_once("../includes/header.php") ?>
<div id="navigation">
<h3>
Notice
</h3>
<?php require_once("../includes/navbar.php") ?>
</div>
<div id="main">
<p>
We have new jokes to recommend to you! Please click below to be presented with the newest recommendations.
</p>
<form action="../user/jokes.php" method="post">
<input name="rerecommend" type="hidden" />
<p>
<input name="submit" type="submit" value="Let's Go!" />
</p>
</form>
</div>
<?php require_once("../includes/footer.php") ?>