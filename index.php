<?php
header('Content-Type: text/html; charset=utf-8');
include('includes.php');
if(@file_exists('config.php'))
{
	include('config.php');
} else
{
	echo "<h1>config.php does not exist!</h1>\n";
	echo "<h1>Please rename the config.php.example and adjust it to your needs</h1>\n";
}

$CACHE_PREFIX = 'cache/';

//TODO use set_error_handler()
//  to define a callback, that handles PHP errors
//  so we can display errors straight away, without
//  the need to look at the error.log file on the system


set_time_limit(30);
$min_cache = 600; // affects automatic site reload
$cacheAge=array();
foreach($EXTERNAL_RESSOURCES as $cur_key => $cur_value)
{
	$cacheAge[$cur_key] = 0;
}
$CONFIG_FILE = $CACHE_PREFIX . 'status-config.ini';
if(file_exists($CONFIG_FILE) && can_i_write_to($CONFIG_FILE))
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
	if (array_key_exists($curCacheName, $EXTERNAL_RESSOURCES)
	&& ($curTime - $curCacheAge) > $EXTERNAL_RESSOURCES[$curCacheName]['caching_duration'])
	{
		$downloadingSucceeded = FALSE;
		$downloadingSucceeded = downloadToFile($EXTERNAL_RESSOURCES[$curCacheName]['url'], $EXTERNAL_RESSOURCES[$curCacheName]['file']);
		if(!$downloadingSucceeded)
		{
			note_error(null, "Downloading " . $EXTERNAL_RESSOURCES[$curCacheName]['url'] . " failed :(\n");
		} else
		{
			$cacheAge[$curCacheName] = $curTime;
			if(array_key_exists('parser_func', $EXTERNAL_RESSOURCES[$curCacheName]))
			{
				try {
					$EXTERNAL_RESSOURCES[$curCacheName]['parser_func']($EXTERNAL_RESSOURCES[$curCacheName]);
				}  catch(Exception $exc)
				{
					note_error(null, "Exception upon calling parser_func '". $EXTERNAL_RESSOURCES['parser_func'] . "' for ressource '" . $curCacheName . "'");
				}
			}
			
		}
	}
}
$cacheText = "[Cache]\n";
foreach($cacheAge as $cacheKey => $cacheValue)
{
	$cacheText .= $cacheKey . "=" . $cacheValue . "\n";
}
file_put_contents($CONFIG_FILE, $cacheText);



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
<?php
include('tabbing.js');
include('script.js');
?>
</script>
</head>
<body>
<div class="hovering_clock_wrapper">
<?php
$right_now_timestamp = round(microtime(true) * 1000);
echo '<div class="hovering_clock_time" timestamp_ms="' . $right_now_timestamp . '">';
echo date("H:i");
echo '</div>';
?>
<script type="text/javascript">
var hovering_clock = {
  wrapperEle: undefined,
  descEle: undefined,
  initTime: "",
  initOffset: 0,
  previousTimeString: undefined,
  init: function()
  {
    hovering_clock.wrapperEle = document.querySelector(".hovering_clock_wrapper");
    hovering_clock.descEle = document.querySelector(".hovering_clock_time");
    hovering_clock.initTime = parseInt(hovering_clock.descEle.getAttribute("timestamp_ms"));
    hovering_clock.initOffset = hovering_clock.initTime - (new Date()).getTime();
    hovering_clock.previousTimeString = hovering_clock.descEle.firstChild.nodeValue;
    hovering_clock.descEle.setAttribute("title", "Offset, from Server Time: " + (hovering_clock.initOffset / 1000 * -1) + " Sekunden");
    hovering_clock.wrapperEle.style.left = (window.innerWidth / 2 - hovering_clock.wrapperEle.offsetWidth / 2) + "px";
    hovering_clock.update();
    setInterval(hovering_clock.update, 1000);
  },
  update: function()
  {
    var adjustedTime = new Date((new Date()).getTime() + hovering_clock.initOffset);
    var timeTuples = [
      adjustedTime.getHours(),
      adjustedTime.getMinutes(),
      adjustedTime.getSeconds()
    ];
    var timeAsString = "";
    var firstTuple = true;
    for(var curTuple of timeTuples)
    {
      if(firstTuple) firstTuple = false;
      else           timeAsString += ":";
      if(curTuple < 10) timeAsString += "0";
      timeAsString += curTuple;
    }
    if(hovering_clock.previousTimeString != timeAsString)
    {
      hovering_clock.descEle.firstChild.nodeValue = timeAsString;
    }
  }
};
setTimeout(hovering_clock.init(), 1);
</script>
</div>
<div class="global_options">
<div class="global_last_update">
letzte Aktualisierung:<br/>
<?php
print(date("H:i:s"));
?>
</div>
<div class="global_go_fullscreen">
<img class="fullscreen_image" src="img/view-fullscreen.png" alt="Fullscreen" width="48" height="48" />
</div>
</div>
<?php

// they changed their formatting yet again >.<
// so this does not work anymore :(
// printWeatherBox(parseWeatherReply($EXTERNAL_RESSOURCES['weather']['file']), 2);
//parse_openweathermap($EXTERNAL_RESSOURCES['openweathermap']['file']);

println('<div style="clear:both;"></div>');
//printDepartureAPIBox('Tharandter Stra&szlig;e', $EXTERNAL_RESSOURCES['departures_tha']['file']);
//printDepartureAPIBox('Clara-Viebig-Stra&szlig;e', $EXTERNAL_RESSOURCES['departures_cvi']['file']);
foreach($EXTERNAL_RESSOURCES as $cur_res)
{
	if(array_key_exists('render_func', $cur_res))
	{
		$cur_res['render_func']($cur_res);
	}
	println('<div style="clear:both;"></div>');
}
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
	println("<div>");
function printNewsTab($res_description)
{
	global $NEWS_SHOW_ITEMS_COUNT;
	//foreach(array('tagesschau', 'hackernews') as $curNewsSource)
	//{
		echo "<div class=\"news_wrapper\" title=\"" . $res_description['title'] . "\">\n";
		$i = 0;
		foreach(parseRss($res_description['file']) as $cur_news)
		{
			if(0 == $i)
			{
				echo "<ul class=\"news_list\">\n";
			}
			echo '  <li class="news_listitem"><a href="' . $cur_news->link  . '">' . $cur_news->title . '</a></li>';
			echo "\n";
		
			$i++;
			if($NEWS_SHOW_ITEMS_COUNT == $i)
			{
				break;
			}
		}
		if(0 < $i)
		{
			echo "</ul>\n";
		}
		echo "<div class=\"news_source\">Quelle " . $res_description['source'] . "</div>";
		echo "</div>";
	//}
}
	println("</div>");

//echo "<div class=\"vocab_wrapper\" title=\"Vokabeln\">";
//include('vocab.php');
//echo "</div>";
if(0 < count($error_store))
{
	echo "<div><details><summary>Fehlermeldungen (" . count($error_store) . ")</summary>\n";
	echo "<table>\n";
	foreach($error_store as $key => $value)
	{
		if(is_array($value))
		{
			echo "<tr>\n";
			if(2 <= count($value))
			{
				echo "<td>" . htmlspecialchars($value[0]) . "</td><td>" . htmlspecialchars($value[1]) . "</td>";
			} elseif(1 == count($value))
			{
				echo "<td></td><td>" . htmlspecialchars($value[1]) . "</td>";
			}
			echo "</tr>\n";
		} else
		{
			echo "<tr>\n";
			echo "<td></td><td>" . htmlspecialchars($value) . "</td>";
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
	echo "</details></div>\n";

}

?>
</body>
</html>

