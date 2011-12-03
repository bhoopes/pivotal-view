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
	require_once('../classes/DeveloperGroups.php');
	$pv = new PivotalView($token);
	
	//will return false if the token is invalid which would cause the page to crash
	if($pv->checkToken() == false)
			header("location: login.php");
	
	$groupClass = new DeveloperGroups($pv);
	
	$groups = $groupClass->getGroups();
	$projectGroup = $groupClass->getProjectGroup();
	$projectsByGroup = $groupClass->getProjectGroupByGroupName();
	
	//print_r($projectsByGroup);
	
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
			$output .= htmlentities($story->description);
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
	
	function changeDeveloperGroup(id, name)
	{
		//alert($('#developerGroupSelect_'+id).val() + " - " + name);
		var displayGroup = $('#developerGroupSelect_'+id).val(); 
		var url = "/developerGroupSelect.php?displayGroup=" + displayGroup + "&projectId=" + id + "&projectName=" + name;
		//alert(url);
		$.ajax(url);

		return false;
	}
	
	function hideDiv(id)
	{
		$("#group_"+id).css("display", "none");
		return false;
	}
	
	function toggleHideableDetails() {
		var currentStatus = $(".hidableDetails").css("display");
		
		if(currentStatus == "none")
			$(".hidableDetails").css("display", "block");
		else
			$(".hidableDetails").css("display", "none");
		
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
	<a href="#" onClick="return toggleHideableDetails();">Show Details</a><br />
	<?
		$groupCount = 0;
		foreach($projectsByGroup AS $groupName => $projects)
		{
			$projectCount = 0;
			?>
			<div class='groupContainer' id='group_<?= $groupCount++ ?>' >
				<?
				if($groupName == '')
					echo "<h1 class='projectGroupTitle'>no development group assigned</h1>";
				else
					echo "<h1 class='projectGroupTitle'>".$groupName."</h1>";
				foreach($projects AS $projectId)
				{
					//check to see if the project exists, if not skip it
					//this was introduced with the introduction of groups
					$project = $pv->getProjectById($projectId);
					if($project == "Resource not found")
					{
						continue;
					}
					$projectCount++;
				
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
					else
						$totals = array();
					//print_r($totals);
					$totals = zeroTotals($totals, $pv);
					$simpleTotals = $pv->totalsChartData($totals);
					$estimatedCompletionDate = $pv->getProjectedCompletionWeek($simpleTotals, $project->current_velocity);
				?>			
					<div class='project'>
						<div class='projectTitle'>
							<div class="projectInfoLeft">
								<div style="float: left;">
									<?= $project->name ?>
									<br /><span class='toggleStories hidableDetails'>(<a href='#' onclick='return toggleStories(<?= $project->id ?>)' >show/hide stories</a>)</span>
								</div>
								<div style="float: left;" class="hidableDetails">
								<?
									if($_COOKIE['pv_username'] == "bhoopes" || $_COOKIE['pv_username'] == "stolman")
									{
										?>
										<form name='developerGroup'>
											<select id='developerGroupSelect_<?= $project->id ?>' onChange='return changeDeveloperGroup(<?= $project->id ?>, "<?= urlencode($project->name) ?>")' name='developerGroupSelect'>
												<option value=''></option>
												<?
												foreach($groups AS $group)
													{
														echo "<option ";
														if($projectGroup[$projectId]['groupName'] == $group)
															echo "selected ";
														echo "value='".$group."'>".$group."</option>";
													}
												?>
											</select>
										</form>
										<?
									}

								?>
								</div> <!-- project Details -->
							</div> <!-- projectInfoLeft -->

							<!-- show the weekly progress graph -->
							<div class="projectTitleProgressChart hidableDetails">
								<!-- draw the chart -->
								<script type="text/javascript">
									google.setOnLoadCallback(drawWeeklyProgressBarChart_<?= $project->id ?>);
									
									function drawWeeklyProgressBarChart_<?= $project->id ?>() {
										var data = new google.visualization.DataTable();
										<?= $pv->weeklyProgressChartData($weeklyProgress) ?>

										var chart = new google.visualization.ColumnChart(
											document.getElementById('projectWeeklyProgressBarChart_<?= $project->id ?>') 
										);
										//chart.draw(data, {'legend':'none', 'chartArea':{'left':'0', 'width':'200'}, 'width':'200', 'colors':['#4b80c4'], 'axisTitlesPosition':'none', 'vAxis': {'baselineColor':'#FFF', 'gridlineColor':'#FFF','textPosition':'none' }, 'hAxis':{'textPosition':'none', 'slantedText': true, 'showTextEvery':'2', 'slantedTextAngle':'90'}});
										chart.draw(data, {'legend':'none', 'chartArea':{'left':'0', 'width':'150'}, 'width':'150', 'colors':['#4b80c4'], 'axisTitlesPosition':'none', 'vAxis': {'baselineColor':'#FFF', 'gridlineColor':'#FFF','textPosition':'none' }, 'hAxis':{'textPosition':'none', 'slantedText': true, 'showTextEvery':'2', 'slantedTextAngle':'90'}});
										//chart.draw(data, {'legend':'none', 'chartArea':{'left':'0'}, 'colors':['#4b80c4'], 'axisTitlesPosition':'none', 'vAxis': {'baselineColor':'#FFF', 'gridlineColor':'#FFF','textPosition':'none' }, 'hAxis':{'textPosition':'none', 'slantedText': true, 'showTextEvery':'2', 'slantedTextAngle':'90'}});
									}
								</script>
								<div class="progressChart" id="projectWeeklyProgressBarChart_<?= $project->id ?>" style="" ></div>
								<br style="line-height: 1px;" clear="both" />
								<div class="progressChartLabel" >hours completed by week</div>
								<!-- end of chart -->
							</div> <!-- projectTitleProgressChart -->
						</div> <!-- projectTitle -->
						<div class="projectInfoRight">
							<div class="projectVelocity">
								<div class='storyInfo' >
									<span class='storyData' ><?= $project->current_velocity ?>&nbsp;</span>
									<span class='storyLabel' >current velocity</span>
								</div><!-- storyInfo -->
							</div> <!-- projectVelocity -->
							<br clear="both" />
							<div class="projectEstimatedCompletion">
								<div class='storyInfo' >
									<span class='storyData' ><?= date("m/d/Y", $estimatedCompletionDate); ?>&nbsp;</span>
									<span class='storyLabel' >estimated completion</span>
								</div><!-- storyInfo -->
							</div> <!-- projectEstimatedCompletion -->
							<br clear="both" />
							<div class="projectTotalHours">
								<div class='storyInfo' >
									<span class='storyData' ><?= $totalHours ?>&nbsp;</span>
									<span class='storyLabel' >total hours</span>
								</div><!-- storyInfo -->
							</div> <!-- projectTotalHours -->
						</div> <!-- projectInfoRight -->

						<div class="projectHourChart">
							<!-- show total hour bar chart -->
							<script type="text/javascript">
								google.setOnLoadCallback(drawBarChart_<?= $project->id ?>);

								function drawBarChart_<?= $project->id ?>() {
									var wrapper = new google.visualization.ChartWrapper({
										chartType: 'BarChart',
										dataTable: <?= json_encode($simpleTotals) ?>,
										//'title': '<?= $project->name ?> Hours',
										options: { 'isStacked':'true', 'legend':'bottom', 'chartArea':{'left':'0', 'width':'675'}, colors:['#4b80c4','#61b847', '#f27926'], 'hAxis':{'maxValue':'1', 'viewWindow':{'max':'<?= $totalHours ?>'}}},
										containerId: 'projectBarChart_<?= $project->id ?>'
									});
									wrapper.draw();
								}
							</script>
							<div class="projectChart" id="projectBarChart_<?= $project->id ?>" style="" ></div>
						</div>  <!-- projectHourChart -->
						<br clear=both />
						<!-- projectStats -->
						<div class='projectStats hidableDetails'>
							<?
								/* Display the state totals for the project */
								foreach($pv->states AS $state)
								{
									?>
									<div class='storyInfo'><span class='storyData'><?= $totals['hours'][$state] ?> hours&nbsp;<br />(<?= $totals['counts'][$state] ?> stories)</span><span class='storyLabel <?= $state ?>'><?= $state ?></span></div><!-- storyInfo -->
									<?
								}
								/* End display the state totals for the project */
							?>
						</div> <!-- projectStats -->

					<?
					/* create stories hidden div */
					if(count($stories))
					{
					?>
						<div class='stories' id='stories_<?= $project->id ?>'>
							<?
							if(count($stories))
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
				if($projectCount == 0)
				{
					echo "<script type='text/javascript'> hideDiv(".($groupCount-1)."); </script>";
					//echo "empty<br />";
				}
			?>
			</div> <!-- groupContainer -->
		<?
		}
	?>
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
	</div> <!-- activityStream -->
	<? include("footer.php"); ?>
</body>
</html>
