<?php
header('Content-Type: text/html; charset=utf-8');
include('includes.php');
include('config.php');


set_time_limit(30);
$min_cache = 600; // affects automatic site reload
$cacheAge=array( 'weather' => 0,
                 'tagesschau' => 0,
		 'hackernews' => 0);
$maxCacheAge=array( 'weather' => (3600 - 5),
                    'tagesschau' => (1800 - 5),
                    'hackernews' => (1800 - 5));
$CONFIG_FILE='status-config.ini';
if(file_exists($CONFIG_FILE))
{
	$readOutConfig=parse_ini_file($CONFIG_FILE, true);
	if(array_key_exists('Cache', $readOutConfig))
	{
		foreach($readOutConfig['Cache'] as $curKey => $curValue)
		{
			$cacheAge[$curKey] = (int) $curValue;
		}
	}
}


$curTime = time();
foreach($cacheAge as $curCacheName => $curCacheAge)
{
	if ( ($curTime - $curCacheAge) > $maxCacheAge[$curCacheName])
	{
		if('weather' == $curCacheName)
		{
			downloadToFile($reqUrlWeather, $WEATHER_FILE);
		} elseif('tagesschau' == $curCacheName)
                {
			downloadToFile($reqUrlTagesschau, $TAGESSCHAU_FILE);
		} elseif('hackernews' == $curCacheName)
                {
			downloadToFile($reqUrlHackernews, $HACKERNEWS_FILE);
                }
		$cacheAge[$curCacheName] = $curTime;
	}
}

file_put_contents($CONFIG_FILE, "[Cache]\ntagesschau=" . $cacheAge['tagesschau'] . "\nweather=" . $cacheAge['weather'] . "\n");



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
//println("  |");
//println('Luftfeuchtigkeit/Vorratskammer: <a href="humidity.php" id="humidity">wird geladen...</span></a>');
?>
<script type="text/javascript">
//document.addEventListener("DOMContentLoaded", loadHumidity);
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
/*
$tagesschauContents = file_get_contents($TAGESSCHAU_FILE);
$cur_offset = 0;
$cur_pos = FALSE;
$cur_index = 0;
while( FALSE !== ($cur_pos = strpos($tagesschauContents, '<title>', $cur_offset)))
{
	$line_end = strpos($tagesschauContents, "\n", $cur_pos);
	if(FALSE === $line_end)
	{
		break;
	}
	if(0 < $cur_index)
	{
		$curTitle = substr($tagesschauContents, $cur_pos, $line_end - $cur_pos);
		$curTitle = substr($curTitle, 7, -8);
		echo '<li>' . $curTitle . '</li>';
	} else
	{
		echo '<div style="float: left; margin-top: 10px;">Quelle tagesschau.de<ul style="margin-top: 0px">';
	}

	$cur_offset = $line_end;
	$cur_index++;
	if(6 < $cur_index)
	{
		break;
	}
}
if(0 < $cur_index)
{
  echo '</ul></div>';
}*/
$i = 0;
echo "<div class=\"news_wrapper\">\n";
foreach(parseRss($TAGESSCHAU_FILE) as $cur_news)
{
	if(0 == $i)
	{
		echo "<ul class=\"news_list\">\n";
	}
	echo '  <li class="news_listitem"><a href="' . $cur_news->link  . '">' . $cur_news->title . '</a></li>';
	echo "\n";

	$i++;
	if(7 == $i)
	{
		break;
	}
}
if(0 < $i)
{
	echo "</ul>\n";
}
echo "<div class=\"news_source\">Quelle tagesschau.de</div>";
echo "<div class=\"cleaner\"></div>";
$i = 0;
foreach(parseRss($HACKERNEWS_FILE) as $cur_news)
{
	if(0 == $i)
	{
		echo "<ul class=\"news_list\">\n";
	}
	echo '  <li class="news_listitem"><a href="' . $cur_news->link  . '">' . $cur_news->title . '</a></li>';
	echo "\n";

	$i++;
	if(7 == $i)
	{
		break;
	}
}
if(0 < $i)
{
	echo "</ul>\n";
}
echo "<div class=\"news_source\">Quelle Hacker News</div>";
echo "</div>\n";

printTabBox();
?>

</body>
</html>

