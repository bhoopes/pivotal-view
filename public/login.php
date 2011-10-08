<?php

require_once('../classes/PivotalView.php');

$pv = new PivotalView();

if($_REQUEST['submit'] == 'Submit')
{
	$username = filter_var($_REQUEST['username'], FILTER_SANITIZE_ENCODED);
	$pwd = filter_var($_REQUEST['pwd'], FILTER_SANITIZE_ENCODED);
	
	//echo $username." - ".$pwd."<br />";
	if($pv->fetchToken($username, $pwd) == true)
	{
		
		$token = $pv->getToken();
		$tokenExpire = $pv->getTokenExpire();
		setcookie('token', $token, $tokenExpire);
		setcookie('pv_username' , $username, $tokenExpire);
		
		//$_SESSION['token'] = $token;
		//echo "token: ".$_SESSION['token']."<br />";
		header("location: index.php");
	}
	else
	{
		$error = "Username or Password invalid.";
	}
	
}

?>
<html>
	<head>
		<title>Pivotal View - Login</title>
		<link type="text/css" rel="stylesheet" href="style.css" /> 
	</head>
	<body onload="document.form.username.focus();">
		<!-- <h1>Pivotal View Login</h1> -->
		<? include("header.php"); ?>
		<h3><?= $error ?></h3>
		<form name="form" action="login.php" method="POST">
			<span class="inputLabel">Username: </span><input type="text" name="username" tabindex="1"></input>
			<br />
			<span class="inputLabel">Password: </span><input type="password" name="pwd" tabindex="2"></input>
			<br />
			<input type="submit" name="submit" value="Submit" tabindex="3" ></input>
				
		</form>
	</body>
</html>
