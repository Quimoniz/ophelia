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


//TODO use set_error_handler()
//  to define a callback, that handles PHP errors
//  so we can display errors straight away, without
//  the need to look at the error.log file on the system


set_time_limit(30);

// Include all the component's includes
foreach($EXTERNAL_RESSOURCES as $cur_key => $cur_value)
{
	if(array_key_exists('include', $cur_value))
	{
		if(file_exists($cur_value['include']))
		{
			// the promised 'once' benefit is not present, we
			//   have to take care about that ourselves....
			include_once($cur_value['include']);
		}
	}
}

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

// let's use the request's timestamp, the real 'beginning'
//$curTime = time();
$curTime = $_SERVER['REQUEST_TIME'];
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
</div>
<script>hovering_clock.init()</script>
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
        if(array_key_exists('css_clear_afterwards', $cur_res)
        && false !== $cur_res['css_clear_afterwards'])
        {
	        println('<div style="clear:both;"></div>');
        }
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
//include('abfahrten-160.php');
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
	$ERROR_LOG_FILE = 'error.log';
	try {
		if(!file_exists($ERROR_LOG_FILE))
		{
			if(!touch($ERROR_LOG_FILE))
			{
				echo "Due to that an error log file \"" . $ERROR_LOG_FILE . "\" does not yet exist, ";
				echo "I tried to create it. But creating it failed unfortunately.";
			}
		}
		if(file_exists($ERROR_LOG_FILE))
		{
			$fh = fopen($ERROR_LOG_FILE, "a");
			// RFC 3339 date, e.g.: 2022-10-15T13:40:49+02:00
			$time_str = date(DATE_RFC3339, $curTime);
			if(false !== $fh)
			{
				$write_error_count = 0;
				$total_line_count = 0;
				foreach($error_store as $key => $value)
				{
					$total_line_count += 1;
					$to_write = $time_str . ',';

					if(is_array($value))
					{
						if(2 <= count($value))
						{
							$to_write .= $value[0] . ',' . $value[1];
						} elseif(1 == count($value))
						{
							$to_write .= $value[0];
						}
					} else
					{
						$to_write .= $value;
					}

					$to_write .= "\n";
					if(false === fwrite($fh, $to_write))
					{
						$write_error_count += 1;
					}
					$total_line_count += 1;
				}
				if(0 < $write_error_count)
				{
					echo "Writing error log file \"" . $ERROR_LOG_FILE . "\", failed " . $write_error_count . " times out of ";
					echo $total_line_count . " lines to write.";
				}
				if(false === fflush($fh)
				&& false === fclose($fh))
				{
					echo "Writing out ('flushing') and closing of file handle, ";
					echo " of error log file \"" . $ERROR_LOG_FILE . "\", failed.";
				}
			} else {
				echo "Could not open file handle for error log file \"" . $ERROR_LOG_FILE . "\", ";
				echo "even 'though it should exist.";
			}
		} else {
			echo "Couldn't write errors to error log file, it does not exist and I could not create it.";
		}
	} catch(Exception $error_handle)
	{
		echo "Unknown error during writing error log file \"" . $ERROR_LOG_FILE . "\".";
	}

}

?>
</body>
</html>

