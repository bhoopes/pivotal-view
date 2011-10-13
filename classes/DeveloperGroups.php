<?php

class DeveloperGroups
{
	private $groupFile = "../public/groups.txt";
	private $projectGroupFile = "../public/projectGroup.txt";
	private $groupNames;
	private $pv;
	
	public function __construct($pv)
	{
		$this->pv = $pv;
	}
	
	function getGroups()
	{
		$groupData = file_get_contents($this->groupFile);
		$groupData = json_decode($groupData);
		
		return $groupData;
	}
	
	function writeGroups($groups)
	{	
		$fh = fopen($this->groupFile, "w+");
		$groupsEncoded = json_encode($groups);
		fwrite($fh, $groupsEncoded, strlen($groupsEncoded));
		fclose($fh);
	}
	
	function getProjectGroup()
	{
		$projectGroup = file_get_contents($this->projectGroupFile);
		
		$projectGroup = json_decode($projectGroup, true);
		
		return $projectGroup;
	}
	
	function setProjectGroup($projectId, $projectName, $group)
	{
		$projectGroup = $this->getProjectGroup();
		
		$projectGroup[$projectId] = array("projectName" => $projectName, "groupName" => $group);
		
		$projectGroup = json_encode($projectGroup);
		
		$fh = fopen($this->projectGroupFile, "w+");
		fwrite($fh, $projectGroup, strlen($projectGroup));
		fclose($fh);
	}
	
	function getProjectGroupByGroupName()
	{
		$projectGroup = $this->getProjectGroup();
		$groupsIncluded = array();
		
		foreach($projectGroup AS $id => $group)
		{
			$groupName = $group['groupName'];
			$groupProject[$groupName][] = $id;
			$groupsIncluded[$id] = true;
		}
		
		//find all the projects that are not set to a group
		$projects = $this->pv->getProjects();
		foreach($projects AS $id => $project)
		{
			if($groupsIncluded[$id] == true)
				continue;
			
			$groupProject[''][] = $id;
		}
		return $groupProject;
	}
}
?>
