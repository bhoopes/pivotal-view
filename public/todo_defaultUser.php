<?
	require_once('../classes/PivotalView.php');

	$tokenExpire = PivotalView::getTokenExpire();
	
	$displayUser = $_GET['displayUser'];

	setcookie('displayUser', $displayUser, $tokenExpire);
?>
