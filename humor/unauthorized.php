<?php require_once("../includes/settings.php") ?><?php require_once("../includes/autologout.php") ?><?php require_once("../includes/authentication.php") ?><?php user_session() ?><?php user_session_authenticate_accessstring("unauthorized") ?><?php$message = "";if (isset($_SESSION["message"])){	$message .= $_SESSION["message"];	unset($_SESSION["message"]);}?><?php require_once("../includes/header.php") ?><div id="navigation"><h3>Unauthorized</h3><?php require_once("../includes/navbar.php") ?></div><div id="main"><p><?php print $message ?></p></div><?php require_once("../includes/footer.php") ?>