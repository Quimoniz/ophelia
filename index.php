<?php
header('Content-Type: text/html; charset=utf-8');
include('includes.php');
include('config.php');


set_time_limit(30);
$downloadSucceeded=false;
$min_cache = 600; // affects automatic site reload
$cacheAge=array('vvo'     => 0,
		 'weather' => 0);
$maxCacheAge=array('vvo'     => (600  - 5),
		   'weather' => (3600 - 5));
$CONFIG_FILE='status-config.ini';
if(file_exists($CONFIG_FILE))
{
	$readOutConfig=parse_ini_file($CONFIG_FILE, true);
	if(array_key_exists('Cache', $readOutConfig))
	{
		if(array_key_exists('vvo', $readOutConfig['Cache']))
		{
			$cacheAge['vvo']=(int) $readOutConfig['Cache']['vvo'];
		}
		if(array_key_exists('weather', $readOutConfig['Cache']))
		{
			$cacheAge['weather']= (int) $readOutConfig['Cache']['weather'];
		}
	}
}


$downloadSucceeded=true;

if ( (time() - $cacheAge['weather']) > $maxCacheAge['weather'])
{
	downloadToFile($reqUrlWeather, $WEATHER_FILE);
	$cacheAge['weather'] = time();
}
if ( (time() - $cacheAge['vvo']) > $maxCacheAge['vvo'])
{
	$downloadSucceeded=downloadToFile($reqUrlVvo, $VVO_FILE);
	$cacheAge['vvo'] = time();
}
file_put_contents($CONFIG_FILE, "[Cache]\nvvo=" . $cacheAge['vvo'] . "\nweather=" . $cacheAge['weather'] . "\n");



$arrDepartures = array();
if(true === $downloadSucceeded)
{
    $arrDepartures = parseVvoReply($VVO_FILE);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Status-Seite</title>
<?php
$refresh_seconds = $min_cache;
if(isset($_GET["refresh"]))
{
  $refresh_seconds = (int) $_GET["refresh"];
  if(5 > $refresh_seconds)
  {
    $refresh_seconds = 5;
  }
}
print("<meta http-equiv=\"refresh\" content=\"" . $refresh_seconds . "\" />");
include('styles.css');
?>
<script type="text/javascript">
<?php include('script.js'); ?>
</script>
</head>
<body>
<div class="global_options">
letzte Aktualisierung:<br/>
<?php
print(date("H:i:s"));
?>
<br/>
<img class="fullscreen_image" src="img/view-fullscreen.png" alt="Fullscreen" width="48" height="48" />
</div>
<?php

printWeatherBox(parseWeatherReply($WEATHER_FILE), 2);
println("<div class=\"link_row\">");
println("  <a href=\"https://www.wetteronline.de/regenradar/sachsen\">Regen-Radar</a>");
println("  |");
println("  <a href=\"https://www.wetter.de/deutschland/wetter-dresden-18232486.html\">Wetter Auskunft</a>");
println("  |");
println('Luftfeuchtigkeit/Vorratskammer: <a href="humidity.php" id="humidity">wird geladen...</span></a>');
?>
<script type="text/javascript">
document.addEventListener("DOMContentLoaded", loadHumidity);
function loadHumidity()
{
  var req = new XMLHttpRequest();
  req.open("GET", "humidity.php?brief_humidity", true);
  req.onreadystatechange = function(ajax) { return function(evt) { if(4 == evt.target.readyState) { var ele = document.getElementById("humidity"); removeAllChilds(ele); ele.appendChild(document.createTextNode(evt.target.responseText)); } } }(req);
  req.send();

  setTimeout(loadHumidity, 15000);
}
function removeAllChilds(parentNode)
{
  for(var i = parentNode.childNodes.length - 1; i >= 0; i--)
  {
    parentNode.removeChild(parentNode.childNodes[i]);
  }
}
</script>
<?php
println("</div><div class=\"cleaner\"> &nbsp; </div>");



if ( 0 < count ($arrDepartures))
{
	if(isset($_GET['px-guides']))
	{
		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			println('User-Agent: '. $_SERVER['HTTP_USER_AGENT'] . '<br/>');
		} else {
			println('No User-Agent sent!<br/>');
		}
		println('<div title="Breite  10px Hoehe: 10px" style="background-color: #000000; width:  10px; height: 10px;"> &nbsp; </div>');
		println('<div title="Breite  50px Hoehe: 10px" style="background-color: #707070; width:  50px; height: 10px;"> &nbsp; </div>');
		println('<div title="Breite 100px Hoehe: 10px" style="background-color: #000000; width: 100px; height: 10px;"> &nbsp; </div>');
		println('<div title="Breite 500px Hoehe: 10px" style="background-color: #707070; width: 500px; height: 10px;"> &nbsp; </div>');
	}
	printDepartureBox($_SERVER['HTTP_USER_AGENT'], $arrDepartures, array('Striesen', 'Laubegast', 'Comeniusplatz'), false);
}

printTabBox();

?>

</body>
</html>

