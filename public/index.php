<html>
<head>
</head>
<body>
<h1>Pivotal View</h1>
<?
//phpinfo();
	require_once('../classes/PivotalView.php');

	$pv = new PivotalView();

	//echo "Token: ".$pv->getToken()."<br /><br />";
	
?>
<h3>Projects</h3>
<?
	$projects = $pv->getProjects();
	foreach($projects AS $project)
	{
		echo $project->name."<br />";
		$stories = $pv->getStories($project->id);
		if(count($stories))
		{
			echo "<ul>";
			foreach($stories AS $story)
			{
				echo "<li>".$story->name."(".$story->current_state.")</li>";
			}
			echo "</ul>";
		}
	}
?>
</body>
</html>
