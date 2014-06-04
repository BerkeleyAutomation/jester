<?php require_once("../includes/settings.php") ?>
<?php
if (!isset($_POST["errormessagetype"]) || ($_POST["errormessagetype"] != "text"))
{
	$_POST["preinvalidquery"] = $_POST["preinvalidqueryhtml"];
	$_POST["postinvalidquery"] = $_POST["postinvalidqueryhtml"];
	$_POST["prenoconnectdb"] = $_POST["prenoconnectdbhtml"];
	$_POST["postnoconnectdb"] = $_POST["postnoconnectdbhtml"];
	$_POST["prenoselectdb"] = $_POST["prenoselectdbhtml"];
	$_POST["postnoselectdb"] = $_POST["postnoselectdbhtml"];
	$_POST["presystemerror"] = $_POST["presystemerrorhtml"];
	$_POST["postsystemerror"] = $_POST["postsystemerrorhtml"];
}
else
{
	$_POST["preinvalidquery"] = $_POST["preinvalidquerytext"];
	$_POST["postinvalidquery"] = $_POST["postinvalidquerytext"];
	$_POST["prenoconnectdb"] = $_POST["prenoconnectdbtext"];
	$_POST["postnoconnectdb"] = $_POST["postnoconnectdbtext"];
	$_POST["prenoselectdb"] = $_POST["prenoselectdbtext"];
	$_POST["postnoselectdb"] = $_POST["postnoselectdbtext"];
	$_POST["presystemerror"] = $_POST["presystemerrortext"];
	$_POST["postsystemerror"] = $_POST["postsystemerrortext"];
}
?>