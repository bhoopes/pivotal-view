<?php

//allow user to create a group name
//ordering of groups
//save to file

$token = $_COOKIE['token'];
if($token == '')
{
	//echo "No token, redirecting to login page.<br />";
	//exit;
	header("location: login.php");
}

date_default_timezone_set('America/Denver');

require_once('../classes/PivotalView.php');
require_once('../classes/DeveloperGroups.php');
$pv = new PivotalView($token);
$groupClass = new DeveloperGroups($pv);


if($_REQUEST['submit'] == "Go")
{
	$groups = trim($_REQUEST['groupOrder']);
	$groups = explode("~", $groups);
	
	//check to see if there is a new group
	if($_REQUEST['newGroup'] != '')
	{
		$newGroup = $_REQUEST['newGroup'];
		$groups[] = $newGroup;
	}
	
	$groupClass->writeGroups($groups);
	
	//print_r($groups);
			
}

$groupData = $groupClass->getGroups();

//$projects = $pv->getProjects();

?>
<html>
	<head>
		<title>Pivotal View Groups</title>
		<link type="text/css" rel="stylesheet" href="style.css" /> 

		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
		
		<script type="text/javascript">
				$(function() {
					$(".groupList").sortable();
					//$(".groupProjects").sortable();
					//$(".groupList").disableSelection();
					//$(".projectList li").draggable({ connectToSortable: '.groupProjects' });
					/*
					$(".group").droppable({
						drop: function() { projectDrop(this); }
					});
					*/
				});
				
				function projectDrop(div)
				{
					alert($(div).text());
				}
				
				function setOrder()
				{
					var order = '';
					$(".groupList li").each(function() {
						if(order != '')
							order = order + "~" + $(this).children(".name").text();
						else
							order = $(this).children(".name").text();
					});
					//alert(order);
					$("#groupOrder").val(order);
				}
				
				function removeGroup(div)
				{	
					$(div).parent().parent().remove();
					
					return false;
				}
		</script>
	</head>
	<body>
		<? include("header.php"); ?>
		<div class="groupsBody">
			<div class="groups">
				<form name="form" method="POST">
					<ul class="groupList">
						<?
							foreach($groupData AS $groups)
							{
								echo "<li class='group'>";
									echo "<span class='name'>".$groups."</span>";
									echo "&nbsp;&nbsp;<span class='removeLink'><a href='#' onClick='return removeGroup(this);'>remove</a></span>";
								echo "</li>";
							}
						?>
					</ul>
					<input name="newGroup" id="newGroup" type="text" />
					<input type="hidden" id="groupOrder" name="groupOrder" value="" /><br />
					<input type="submit" onClick="setOrder();" name="submit" value="Go" />
				</form>
			</div>
		</div><!-- end body -->
	</body>
</html>
