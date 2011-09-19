<?

if($argc < 3)
{
	echo "usage: php fetchKey.php <username> <password> <true to write to a file>\n\n";
	exit;
}

$username = urlencode($argv[1]);
$password = urlencode($argv[2]);

//Setup CURL and Execute
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://www.pivotaltracker.com/services/v3/tokens/active");
curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, 0);
$tokenString = curl_exec($curl);

if(trim($tokenString) == "Access denied.")
{
	echo "Access denied.\n\n";
	exit;
}

$xml = new SimpleXMLElement($tokenString);

//test the xml file
//print_r($xml);

//this is what we are after
echo $xml->guid."\n";

//write the token to the pivotal-key.txt file
if($argv[3] == "true")
{
	$fh = fopen("pivotal-key.txt", "w+");
	fwrite($fh, $xml->guid);
	fclose($fh);
}

?>
