<?php
function abort($error_code, $error_title, $error_description)
{
	header('HTTP/1.0 ' . $error_code . ' ' . $error_title);
	echo "<!DOCTYPE html>\n<html><head><meta charset=\"utf8\" /><title>" . $error_code . " " . htmlspecialchars($error_title) . "</title></head>";
	echo "<body>\n<h1>" . $error_code . " " . htmlspecialchars($error_title) . "</h1>\n";
	echo "<p>" . $error_description . "</p>\n";
        echo "</body></html>";
        exit();
}
if(isset($_GET['url']))
{
	if(0 === stripos($_GET['url'], '/de/fahrplan/aktuelle-abfahrten-ankuenfte/details?id='))
	{
		/* TODO: rate limit */
		$request_url = 'http://www.vvo-online.de' . $_GET['url'];
		echo file_get_contents($request_url);
	} else
	{
    		abort(400, "Wrong url format", "The provided url did not satisfy the expected criteria.");
	}
} else
{
	abort(400, "Insufficient Parameters", "The provided GET/POST parameters were not sufficient to fullfill the request.");
}
?>
