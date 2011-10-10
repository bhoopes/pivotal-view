<?php

/* Todo List */

$token = $_COOKIE['token'];
if($token == '')
{
	header("location: login.php");
}

$displayUser = filter_var($_GET['displayUser'], FILTER_SANITIZE_ENCODED);
if($displayUser == '')
{
	$displayUser = $_COOKIE['displayUser'];
}
if($displayUser == '')
	$displayUser = "none";

require_once('../classes/PivotalView.php');

$pv = new PivotalView($token);


//$fullname = filter_var($_REQUEST['fullname'], FILTER_SANITIZE_ENCODED);

/*
	SimpleXMLElement Object
	(
		[id] => 15607667
		[project_id] => 313769
		[story_type] => chore
		[url] => https://www.pivotaltracker.com/story/show/15607667
		[estimate] => -1
		[current_state] => accepted
		[description] => We had a deal date change on Friday around 5:30pm. The expiration date changed from 09/30/11 to 09/10/11. Could we please pull the names and email addresses of anyone that purchased before that time and then send out an email about change.
		[name] => Pull email addresses for Cherry Hill Deal
		[requested_by] => Russell Ahlstrom
		[owned_by] => Russell Ahlstrom
		[created_at] => 2011/07/11 10:53:50 MDT
		[updated_at] => 2011/07/11 11:24:35 MDT
		[accepted_at] => 2011/07/11 11:24:35 MDT
	)
*/

//$fullname = "Brian Hoopes";
$projects = $pv->getProjects();
foreach($projects AS $project)
{
	$stories = $pv->getStories($project->id);
	if(count($stories))
	{
		foreach($stories AS $story)
		{
			$owner = substr($story->owned_by,0);
			if($owner == '0')
				$owner = "none";
			$story->project_name = $project->name;
			$state = substr($story->current_state, 0);
			$todo[$owner][$state][] = $story;
		}
	}
}

//sort the todo list by name
ksort($todo);

function displayStory($story)
{
	//print_r($story);
	$output = '';

	$output .= "
		<div class='story'>";
		$output .= "<div class='storyTitle'>".$story->name." (".$story->project_name.")";
		$output .= "<span class='toggleStories'>&nbsp;&nbsp;<a href='#' onclick='return toggleDetails(".$story->id.")' >show/hide details</a></span>";
		$output .= "</div><!-- storyTitle -->";
		$output .= "<div class='todoStoryDetails' id='todoStoryDetails_".$story->id."'>";
		$output .= "<div class='storyInfo'><span class='storyData'>".$story->current_state."&nbsp;</span><span class='storyLabel'>Status</span></div><!-- storyInfo -->";
		$output .= "<div class='storyInfo'><span class='storyData'>".$story->story_type."&nbsp;</span><span class='storyLabel'>Type</span></div><!-- storyInfo -->";
		$output .= "<div class='storyInfo'><span class='storyData'>".$story->requested_by."&nbsp;</span><span class='storyLabel'>Requestor</span></div><!-- storyInfo -->";
		$output .= "<div class='storyInfo'><span class='storyData'>".$story->owned_by."&nbsp;</span><span class='storyLabel'>Owner</span></div><!-- storyInfo -->";
		$output .= "<br clear=both />";
		$output .= "<div class='storyInfo'><span class='storyData'>".$story->estimate."&nbsp;</span><span class='storyLabel'>Hours Estimate</span></div><!-- storyInfo -->";
		$output .= "<div class='storyInfo'><span class='storyData'>".$story->created_at."&nbsp;</span><span class='storyLabel'>Created</span></div><!-- storyInfo -->";
		$output .= "<div class='storyInfo'><span class='storyData'>".$story->updated_at."&nbsp;</span><span class='storyLabel'>Updated</span></div><!-- storyInfo -->";
		$output .= "<div class='storyInfo'><span class='storyData'>".$story->accepted_at."&nbsp;</span><span class='storyLabel'>Accepted</span></div><!-- storyInfo -->";
		$output .= "<br clear=both />";
		$output .= "<div class='storyDesc'>Description: ";
		$output .= $story->description;
		$output .= "<br /><a href='".$story->url."' target='_blank'>View in PivotalTracker</a>";
		$output .= "</div> <!-- storyDetails_".$story->id." -->";
		$output .= "</div><!-- storyDesc -->";
	$output .= "</div> <!-- story -->
";

	return $output;
}
?>

<html>
	<head>
		<title>Pivotal View - Todo</title>
		<meta name="author" content="Brian Hoopes">
		<link type="text/css" rel="stylesheet" href="style.css" /> 

		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
		
		<script type="text/javascript">
			function toggleDetails(id)
			{
				var divName = "#todoStoryDetails_" + id;
				if($(divName).css("display") == "none")
				{
					$(divName).css("display", "block");
				}
				else
				{
					$(divName).css("display", "none");
				}
				return false;
			}
			
			function toggleList()
			{
				$(".todoList").css('display', 'none');
				
				var displayDiv = '#todo_' + $("#displayUser").val();
				$(displayDiv).css('display', 'block');
			}

			function setDefaultUser()
			{
				var displayUser = $("#displayUser").val();
				var url = "/todo_defaultUser.php?displayUser=" + displayUser;
				//alert(url);
				$.ajax(url);

				return false;
			}
		</script>
	</head>
	<body>
		<? include("header.php"); ?>
		<?
		if($error != '')
			echo "<h3>".$error."</h3>";
		
		if(count($todo))
		{
			echo "<form action='todo.php'>";
				echo "<select id='displayUser' name='displayUser' onChange='toggleList();'>";
				foreach($todo AS $name => $states)
				{
					echo "<option value='".str_replace(' ', '', $name)."'";
					if(str_replace(' ', '', $name) == $displayUser)
						echo " selected ";
					echo ">".$name."</option>";
				}
				echo "</select> <!-- display_user -->";
			echo "&nbsp;&nbsp;<a href='#' onClick='return setDefaultUser()'>set as default</a>";
			echo "</form>";
		}
		
		if(count($todo))
		{
			$definedStates = $pv->states;
			arsort($definedStates);
			foreach($todo AS $name => $states)
			{
				$userDiv = str_replace(' ', '', $name);
				echo "<div class='todoList' style='display: ";
				if($displayUser == $userDiv)
					echo "block";
				else 
					echo "none";
				echo ";' id='todo_".str_replace(' ', '', $name)."'>";
				foreach($definedStates AS $state)
				{
					echo "<h3>".ucwords($state)."</h3>";
					if(count($todo[$name][$state]))
					{
						foreach($todo[$name][$state] AS $item)
						{
							echo displayStory($item);
						}
					}
				}
				echo "</div>";
			}
		}
		?>
	</body>
</html>
