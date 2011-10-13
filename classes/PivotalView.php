<?
class PivotalView
{
	private $token;
	private $baseUrl = "https://www.pivotaltracker.com/services/v3/";
	//public $states = array("started", "unstarted", "unscheduled", "unestimated", "finished", "delivered", "accepted");
	public $states = array("accepted", "started", "finished", "delivered", "unstarted", "unscheduled", "unestimated");
	public $simpleStates = array("complete" => array("accepted"), 
									"in progress" => array("started", "finished", "delivered"),
									"outstanding" => array("unstarted", "unscheduled", "unestimaged"));

	public function __construct($token = '', $useFile = false)
	{
		if($useFile == true)
			$this->token = file_get_contents(realpath(dirname(dirname(__FILE__)))."/pivotal-key.txt");
		else
			$this->token = $token;
	}

	public function fetchToken($username, $password)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://www.pivotaltracker.com/services/v3/tokens/active");
		curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		$tokenString = curl_exec($curl);

		if(trim($tokenString) == "Access denied.")
		{
			//echo "Access denied.\n\n";
			return false;
		}
		
		//echo $tokenString;
		$xml = new SimpleXMLElement($tokenString);
		//echo $xml."<br />";

		//test the xml file
		//print_r($xml);

		//store
		curl_close($curl);
		$this->token = $xml->guid;
		//echo $this->getToken();
		return true;
	}
	
	private function makeRequest($path)
	{
		if($this->token == '')
			return;
		
		$curl = curl_init();
		$URL = $this->baseUrl.$path;
		curl_setopt($curl, CURLOPT_URL, "$URL");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-TrackerToken: ".$this->token));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($curl, CURLOPT_HEADER, 0);
		$string = curl_exec($curl);
		curl_close($curl);

		return $string;
	}
	
	public function getToken()
	{
		return $this->token;
	}

	private function parseXML($xml)
	{
		$xml = new SimpleXMLElement($xml);
		return $xml;
	}


	public function getProjects()
	{
		$projects = $this->makeRequest("projects");
		$projects = $this->parseXML($projects);
		foreach($projects->project AS $item)
		{
			$projectId = substr($item->id, 0);
			$items[$projectId] = $item;
		}
		return $items;

		echo "<pre>";
		print_r($items);
		echo "</pre>";
		echo "<br /><br />";
		foreach($items AS $item)
		{
			echo $item->name."<br />";
		}
	}

	public function getProjectById($id)
	{
		$project = $this->makeRequest("projects/$id");
		$project = $this->parseXML($project);
		return $project;
		
		echo "project: ";
		print_r($project);
		echo "<br />";
		foreach($project->project AS $item)
		{
			print_r($item);
			$items[] = $item;
		}
		return $items;

	}

	public function getStories($projectId)
	{
		$stories = $this->makeRequest("projects/$projectId/stories");
		$stories = $this->parseXML($stories);
		foreach($stories->story AS $item)
		{
			$items[] = $item;
		}
		return $items;
	}

	public function getStoryById($projectId, $storyId)
	{
		$projects = $this->makeRequest("projects/$projectId/stories");
		$projects = $this->parseXML($projects);
		foreach($projects->project AS $item)
		{
			$items[] = $item;
		}
		return $items;
	}
	
	public function getActivityStream($limit=25)
	{
		$activity = $this->makeRequest("activities/?limit=".$limit);
		$activity = $this->parseXML($activity);
		foreach($activity->activity AS $item)
		{
			$items[] = $item;
		}
		return $items;
	}
	
	function getProjectedCompletionWeek($totals, $velocity)
	{
		//print_r($totals);
		$hoursRemaining = $totals[1][1] + $totals[1][2];
		$estimatedWeeks = floor($hoursRemaining / $velocity);
		//echo $hoursRemaining." - ".$velocity." - ".$estimatedWeeks."<br />";
		
		$estimatedDate = strtotime(	$estimatedWeeks." weeks");
		//echo date("m/d/Y", $estimatedDate)."<br />";
		$estimatedDate = strtotime("Last Monday 12:00 AM", $estimatedDate);
		//echo date("m/d/Y", $estimatedDate);
		
		return $estimatedDate;
	}
	
	function findMonday($timestamp)
	{
		$mondayTimestamp = 0;
		$mondayTimestamp = strtotime("Last Monday 12:00 AM", $timestamp);
		return $mondayTimestamp;
	}
	
	function weeklyProgress($stories)
	{
		$weeklyProgress = array();
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
		foreach($stories AS $story)
		{
			$state = substr($story->current_state, 0);
			if($story->accepted_at != '')
			{
				$accepted_at = strtotime(substr($story->accepted_at, 0));
				$estimate = substr($story->estimate, 0);
				if($estimate < 0)
					$estimate = 0;
				//$current_state = substr($story->current_state, 0);
				$mondayTimestamp = $this->findMonday($accepted_at);
				$weeklyProgress[$mondayTimestamp] += $estimate;
			}
		}
		
		ksort($weeklyProgress);
		//print_r($weeklyProgress);
		return $weeklyProgress;
	}
	
	function weeklyProgressChartData($weeklyProgress)
	{
		foreach($weeklyProgress AS $timestamp => $hours)
		{
			$date = date("m/d/Y", $timestamp);
			$labels[] = $date;
			$values[] = $hours;
			$dataTableRows[] = array($date, $hours);
		}
		
		//$data = array($labels, $values);

		$data = "data.addColumn('string', 'Week'); data.addColumn('number', 'Hours Completed'); ";
		$data .= "data.addRows(".json_encode($dataTableRows).");";
		return $data;
	}
	
	public function totalsChartData($totals)
	{
		/*[['Germany', 'USA', 'Brazil', 'Canada', 'France', 'RU'], [700, 300, 400, 500, 600, 800]] */
		//echo json_encode($this->states);
		/*
		foreach($this->states AS $state)
		{
			$hours[] = $totals['hours'][$state];
		}
		$data = array($this->states, $hours);
		*/
		foreach($this->simpleStates AS $simpleState => $states)
		{
			$stateNames[] = $simpleState;
			foreach($states AS $state)
			{
				$hours[$simpleState] += $totals['hours'][$state];
			}
		}
		
		foreach($hours AS $key => $hour)
		{
			$hourNoKey[] = $hour;
		}
		
		$data = array($stateNames, $hourNoKey);
		return $data;
	}
	
	public function getTokenExpire()
	{
		//seconds in a month
		//seconds, minutes, hours, days
		$offset = 60*60*24*30;

		return time()+$offset;
	}
}
?>
