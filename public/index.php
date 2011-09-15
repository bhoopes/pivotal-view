<html>
<head>
</head>
<body>
<h1>Pivotal View</h1>

<?
	require_once('../classes/PivotalView.php');

	$pv = new PivotalView();

	echo "Token: x".$pv->getToken()."x<br />";
?>
</body>
</html>
