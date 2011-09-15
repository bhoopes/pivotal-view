<?
class PivotalView
{
	private $token;

	public function __construct()
	{
		$this->token = file_get_contents(realpath(dirname(dirname(__FILE__)))."/pivotal-key.txt");
	}

	public function getToken()
	{
		return $this->token;
	}

}
?>
