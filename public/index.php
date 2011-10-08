<?
	$token = $_COOKIE['token'];
	if($token == '')
	{
		//echo "No token, redirecting to login page.<br />";
		//exit;
		header("location: login.php");
	}
	
	date_default_timezone_set('America/Denver');
	
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
	
	function projectTotals($totals, $story, &$totalHours)
	{
		$startDate = 0;
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
		
		$totalHours += $estimate;
		
		return $totals;
	}
	
	function zeroTotals($totals, $pv)
	{
		foreach($pv->states AS $state)
		{
			if($totals['counts'][$state] == '')
				$totals['counts'][$state] = 0;
			if($totals['hours'][$state] == '')
				$totals['hours'][$state] = 0;
		}
		
		return $totals;
	}
	
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
			$output .= "<br clear=both />";
			$output .= "<div class='storyDesc'>Description: ";
			$output .= $story->description;
			$output .= "<br /><a href='".$story->url."' target='_blank'>View in PivotalTracker</a>";
			$output .= "</div><!-- storyDesc -->";
		$output .= "</div> <!-- story -->
";

		return $output;
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

<!-- Add Google Chart functionality-->
<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">
	google.load('visualization', '1', {packages:["corechart"]});   // Don't need to specify chart libraries!
</script>

</head>
<body>
<? include("header.php"); ?>
<div class="projects">
	<!-- <h3>Projects</h3> -->
	<?
		$projects = $pv->getProjects();
		foreach($projects AS $project)
		{
			$totalHours = 0;
			$stories = $pv->getStories($project->id);
			$weeklyProgress = $pv->weeklyProgress($stories);
			$totals = array('hours' => array(), 'counts' => array());
			if(count($stories))
			{
				foreach($stories AS $story)
				{
					$totals = projectTotals($totals, $story, $totalHours);
				}
			}
			//print_r($totals);
			$totals = zeroTotals($totals, $pv);
			$simpleTotals = $pv->totalsChartData($totals);
			$estimatedCompletionDate = $pv->getProjectedCompletionWeek($simpleTotals, $project->current_velocity);
			?>			
			<div class='project'>
				<div class='projectTitle'>
					<?= $project->name ?>
					<br /><span class='toggleStories'>(<a href='#' onclick='return toggleStories(<?= $project->id ?>)' >show/hide stories</a>)</span>
				</div>
				<div class="projectInfoRight">
					<div class="projectVelocity">
						<div class='storyInfo'><span class='storyData'><?= $project->current_velocity ?>&nbsp;</span><span class='storyLabel'>Current Velocity</span></div><!-- storyInfo -->
					</div>
					<div class="projectEstimatedCompletion">
						<div class='storyInfo'><span class='storyData'><?= date("m/d/Y", $estimatedCompletionDate); ?>&nbsp;</span><span class='storyLabel'>Estimated Completion Week</span></div><!-- storyInfo -->
					</div>
				</div>

				<!-- show the weekly progress graph -->
				<script type="text/javascript">
					google.setOnLoadCallback(drawBarChart_<?= $project->id ?>);

					function drawBarChart_<?= $project->id ?>() {
						var wrapper = new google.visualization.ChartWrapper({
							chartType: 'BarChart',
							dataTable: <?= json_encode($simpleTotals) ?>,
							//'title': '<?= $project->name ?> Hours',
							options: { 'isStacked':'true', 'legend':'bottom', colors:['#4b80c4','#61b847', '#f27926'], 'hAxis':{'maxValue':'1', 'viewWindow':{'max':'<?= $totalHours ?>'}}},
							containerId: 'projectBarChart_<?= $project->id ?>'
						});
						wrapper.draw();
					}
				</script>
				<div class="projectChart" id="projectBarChart_<?= $project->id ?>" style="" ></div>
				<br clear=both />
				<!-- end show the weekly progress graph -->
				
				<!-- projectStats -->
				<div class='projectStats'>
					<? /*
					 * This is a bar chart that is currently unused
					 * But I didn't want to delete it because it might be useful for others
					<script type="text/javascript">
						google.setOnLoadCallback(drawChart_<?= $project->id ?>);

						function drawChart_<?= $project->id ?>() {
							var wrapper = new google.visualization.ChartWrapper({
								chartType: 'ColumnChart',
								dataTable: <?= $pv->totalsChartData($totals) ?>,
								options: {'title': '<?= $project->name ?> Hours'},
								containerId: 'projectChart_<?= $project->id ?>'
							});
							wrapper.draw();
						}
					</script>
					 <div class="projectChart" id="projectChart_<?= $project->id ?>" style="" ></div>
					 */
					?>
					
					<?
						/* Display the state totals for the project */
						foreach($pv->states AS $state)
						{
							?>
							<div class='storyInfo'><span class='storyData'><?= $totals['hours'][$state] ?> hours&nbsp;<br />(<?= $totals['counts'][$state] ?> stories)</span><span class='storyLabel <?= $state ?>'><?= ucwords($state) ?></span></div><!-- storyInfo -->
							<?
						}
						/* End display the state totals for the project */
					?>
				</div>
				<?
				/*
				<!-- draw the chart -->
				<script type="text/javascript">
					google.setOnLoadCallback(drawWeeklyProgressBarChart_<?= $project->id ?>);

					function drawWeeklyProgressBarChart_<?= $project->id ?>() {
						var data = new google.visualization.DataTable();
						<?= $pv->weeklyProgressChartData($weeklyProgress) ?>

						var chart = new google.visualization.ColumnChart(
							document.getElementById('projectWeeklyProgressBarChart_<?= $project->id ?>') 
						);
						chart.draw(data, {'legend':'none', 'vAxis': {'title':'Hours completed by week'}, 'hAxis':{'textPosition':'none', 'slantedText': true, 'showTextEvery':'2', 'slantedTextAngle':'90'}});
					}
				</script>
				<div class="progressChart" id="projectWeeklyProgressBarChart_<?= $project->id ?>" style="" ></div>
				<br clear=both />
				<!-- end of chart -->
				*/
				?>
				<?
				/* create stories hidden div */
				if(count($stories))
				{
				?>
					<div class='stories' id='stories_<?= $project->id ?>'>
					<?
					foreach($stories AS $story)
					{
						echo displayStory($story);
					}
					?>
					</div> <!-- stories -->
				<?
				}
				/* End create stories hidden div */
				?>
			</div>  <!-- project -->

		<?
		}
	?> <!-- projects -->
</div> <!-- projects -->

<div class="activityStream">
	<h3>Activity Stream</h3>
	<?
		$activites = $pv->getActivityStream();
		if(count($activites))
		{
			foreach($activites AS $activity)
			{
				?>
				<div class="activityStreamItem">
					<span class="activityStreamTitle"><?= $activity->description ?></span><!-- activityStreamTitle -->
					<br />
					<span class="activityStreamDetails">
						(<?= $activity->occurred_at ?>)
						<a href='https://www.pivotaltracker.com/story/show/<?= $activity->stories->story->id ?>' alt='Really? Show me.' target='_blank' >View in Pivotal Tracker</a>
					</span>
				</div> <!-- activityStreamItem -->
				<?
			}
		}
	?>
</div>
</body>
</html>
