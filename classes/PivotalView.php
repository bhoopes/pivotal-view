<?
class PivotalView
{
	private $token;
	private $baseUrl = "https://www.pivotaltracker.com/services/v3/";

	public function __construct()
	{
		$this->token = file_get_contents(realpath(dirname(dirname(__FILE__)))."/pivotal-key.txt");
	}

	private function makeRequest($path)
	{
		$headerArray = array("X-TrackerToken: ".$this->token);

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
}
?>
