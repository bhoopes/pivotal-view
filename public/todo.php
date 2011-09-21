<?php

/* Todo List */

$token = $_COOKIE['token'];
if($token == '')
{
	header("location: login.php");
}

$fullname_array = array("bhoopes" => "Brian Hoopes", "codazoda" => "Joel Dare");
$username = $_COOKIE['pv_username'];
$fullname = $fullname_array[$username];

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
if($fullname != '')
{
	$projects = $pv->getProjects();
	foreach($projects AS $project)
	{
		$stories = $pv->getStories($project->id);
		if(count($stories))
		{
			foreach($stories AS $story)
			{
				if($story->owned_by == $fullname)
				{
					$story->project_name = $project->name;
					$state = substr($story->current_state, 0);
					$todo[$state][] = $story;
				}
			}
		}
	}
}
else
{
	$error = "Sorry, no name found.<br />";
}

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
		</script>
	</head>
	<body>
		<a href="index.php" alt="The big picture.">Project Overview</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="logout.php" alt="Logout of your current session">Logout</a>
		<h1>Todo</h1>
		<?
		if($error != '')
			echo "<h3>".$error."</h3>";
		
		if(count($todo))
		{
			foreach($pv->states AS $state)
			{
				echo "<h3>".ucwords($state)."</h3>";
				if(count($todo[$state]))
				{
					foreach($todo[$state] AS $item)
					{
						echo displayStory($item);
					}
				}
			}
		}
		?>
	</body>
</html>