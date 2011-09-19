<?
	$token = $_COOKIE['token'];
	if($token == '')
	{
		//echo "No token, redirecting to login page.<br />";
		//exit;
		header("location: login.php");
	}
	
	require_once('../classes/PivotalView.php');

	$pv = new PivotalView($token);

	//echo "Token: ".$pv->getToken()."<br /><br />";

	
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
	function displayStory($story)
	{
		//print_r($story);
		$output = '';

		$output .= "
			<div class='story'>";
			$output .= "<div class='storyTitle'>".$story->name."</div><!-- storyTitle -->";
			$output .= "<div class='storyInfo'><span class='storyData'>".$story->current_state."&nbsp;</span><span class='storyLabel'>Status</span></div><!-- storyInfo -->";
			$output .= "<div class='storyInfo'><span class='storyData'>".$story->story_type."&nbsp;</span><span class='storyLabel'>Type</span></div><!-- storyInfo -->";
			$output .= "<div class='storyInfo'><span class='storyData'>".$story->requested_by."&nbsp;</span><span class='storyLabel'>Requestor</span></div><!-- storyInfo -->";
			$output .= "<div class='storyInfo'><span class='storyData'>".$story->owned_by."&nbsp;</span><span class='storyLabel'>Owner</span></div><!-- storyInfo -->";
			$output .= "<br clear=both />";
			$output .= "<div class='storyInfo'><span class='storyData'>".$story->estimate."&nbsp;</span><span class='storyLabel'>Hours Estimate</span></div><!-- storyInfo -->";
			$output .= "<div class='storyInfo'><span class='storyData'>".$story->created_at."&nbsp;</span><span class='storyLabel'>Created</span></div><!-- storyInfo -->";
			$output .= "<div class='storyInfo'><span class='storyData'>".$story->updated_at."&nbsp;</span><span class='storyLabel'>Updated</span></div><!-- storyInfo -->";
			$output .= "<div class='storyInfo'><span class='storyData'>".$story->accepted_at."&nbsp;</span><span class='storyLabel'>Accepted</span></div><!-- storyInfo -->";
		$output .= "</div> <!-- story -->
";

		return $output;
	}
	
	function projectTotals($totals, $story)
	{
		//this week
		$thisWeekStart = strtotime("last monday");
		$thisWeekEnd = strtotime("next sunday");
		
		if($story->estimate < 0)
		{
			$totals['counts']['unestimated']++;
			return $totals;
		}
		
		$state = substr($story->current_state, 0);
		$estimate = substr($story->estimate, 0);
		
		$totals['hours'][$state] = $totals['hours'][$state] + $estimate;
		$totals['counts'][$state]++;
		
		return $totals;
	}
	
	function zeroTotals($totals)
	{
		$states = array("accepted", "finished", "started", "unstarted", "unscheduled", "unestimated");
		foreach($states AS $state)
		{
			if($totals['counts'][$state] == '')
				$totals['counts'][$state] = 0;
			if($totals['hours'][$state] == '')
				$totals['hours'][$state] = 0;
		}
		
		return $totals;
	}
?>
<html>
<head>
<title>Pivotal View</title>
<meta name="author" content="Brian Hoopes">
<link type="text/css" rel="stylesheet" href="style.css" /> 

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
<script type="text/javascript">
	function toggleStories(id)
	{
		var divName = "#stories_" + id;
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
<h1>Pivotal View</h1>
<!-- <h3>Projects</h3> -->
<?
	$projects = $pv->getProjects();
	foreach($projects AS $project)
	{
	?>
		<div class='project'>
		<div class='projectTitle'>
			<?= $project->name ?>
			<span class='toggleStories'>(<a href='#' onclick='return toggleStories(<?= $project->id ?>)' >show/hide stories</a>)</span>
		</div>
		<?
		$stories = $pv->getStories($project->id);
		$totals = array('hours' => array(), 'counts' => array());
		if(count($stories))
		{
		?>
			<div class='stories' id='stories_<?= $project->id ?>'>
			<?
			foreach($stories AS $story)
			{
				echo displayStory($story);
				$totals = projectTotals($totals, $story);
			}
			?>
			</div> <!-- stories -->
		<?
		}
		//print_r($totals);
		$totals = zeroTotals($totals);
		?>
		<!-- projectStats -->
		<div class='projectStats'>
			<div class='storyInfo'><span class='storyData'><?= $project->current_velocity ?>&nbsp;</span><span class='storyLabel'>Current Velocity</span></div><!-- storyInfo -->
			<br clear=both />
			<div class='storyInfo'><span class='storyData'><?= $totals['hours']['accepted'] ?> hours&nbsp; (<?= $totals['counts']['accepted'] ?> stories)</span><span class='storyLabel'>Accepted</span></div><!-- storyInfo -->
			<div class='storyInfo'><span class='storyData'><?= $totals['hours']['finished'] ?> hours&nbsp; (<?= $totals['counts']['finished'] ?> stories)</span><span class='storyLabel'>Finished</span></div><!-- storyInfo -->
			<div class='storyInfo'><span class='storyData'><?= $totals['hours']['started'] ?> hours&nbsp; (<?= $totals['counts']['started'] ?> stories)</span><span class='storyLabel'>Started</span></div><!-- storyInfo -->
			<div class='storyInfo'><span class='storyData'><?= $totals['hours']['unstarted'] ?> hours&nbsp; (<?= $totals['counts']['unstarted'] ?> stories)</span><span class='storyLabel'>Unstarted</span></div><!-- storyInfo -->
			<div class='storyInfo'><span class='storyData'><?= $totals['hours']['unscheduled'] ?> hours&nbsp; (<?= $totals['counts']['unscheduled'] ?> stories)</span><span class='storyLabel'>Unscheduled</span></div><!-- storyInfo -->
			<div class='storyInfo'><span class='storyData'>(<?= $totals['counts']['unestimated'] ?> stories)&nbsp;</span><span class='storyLabel'>Un-estimated</span></div><!-- storyInfo -->
		</div>
		</div>  <!-- project -->
		<br clear=all>
	<?
	}
?>
</body>
</html>
