<?php

	$token = $_COOKIE['token'];
	if($token == '')
	{
		return;
		//echo "No token, redirecting to login page.<br />";
		//exit;
	}

	require_once('../classes/PivotalView.php');
	require_once('../classes/DeveloperGroups.php');
	$pv = new PivotalView($token);
	$groupClass = new DeveloperGroups($pv);
	
	//Array ( [displayGroup] => DNews [projectId] => 313769 [projectName] => Deal Mall [displayUser] => BrianHoopes [token] => 19677e1be7702cb50733e8fea124c619 [pv_username] => bhoopes )
	$group = $_REQUEST['displayGroup'];
	$projectId = $_REQUEST['projectId'];
	$projectName = $_REQUEST['projectName'];
	
	$groupClass->setProjectGroup($projectId, $projectName, $group);
	
	//print_r($_REQUEST);

	

?>
