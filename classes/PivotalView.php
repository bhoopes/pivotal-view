<?
class PivotalView
{
	private $token;
	private $baseUrl = "https://www.pivotaltracker.com/services/v3/";
	public $states = array("started", "unstarted", "unscheduled", "unestimated", "finished", "delivered", "accepted");

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
			$items[] = $item;
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
		foreach($project->project AS $item)
		{
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
	
	public function totalsChartData($totals)
	{
		/*[['Germany', 'USA', 'Brazil', 'Canada', 'France', 'RU'], [700, 300, 400, 500, 600, 800]] */
		//echo json_encode($this->states);
		foreach($this->states AS $state)
		{
			$hours[] = $totals['hours'][$state];
		}
		$data = array($this->states, $hours);
		return json_encode($data);
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
