<?php
include('xmlparser.php');

class DepartureData
{
	public $vehicleId = "";
	public $vehicleType = "";
	public $vehicleDestination = "";
	public $vehicleDeparture = "";
	public $detailUrl = "";
}
class WeatherRow
{
	public $weatherWeekday = "";
	public $weatherDate = "";
	public $weatherTime = "";
	public $weatherCloudiness = "";
	public $weatherTemperature = "";
	public $weatherDownfall = "";
	public $weatherWindDesc = "";
	public $weatherWindSpeed = "";
}
class NewsItem {
	public $title;
	public $link;
	public $description;
	public $category;
	function __construct($xmlItem)
	{
		foreach($xmlItem->children as $curChild)
		{
			switch($curChild->tagName)
			{
				case "title":
					$this->title = $curChild->text;
					break;
				case "link":
					$this->link  = $curChild->text;
					break;
				case "description":
					$this->description = $curChild->text;
					break;
				case "category":
					$this->category = $curChild->text;
					break;
			}
		}
	}
}
function can_i_write_to($filepath)
{
	$statinfo = NULL;
	$is_parent_folder = false;
	if(file_exists($filepath))
	{
		$statinfo = stat($filepath);
	} else
	{
		$statinfo = stat(dirname($filepath));
		$is_parent_folder = true;
	}
	if($statinfo)
	{
		$file_owner = $statinfo['uid'];
		$file_group = $statinfo['gid'];
		$need_to_check_other = false;
		if(posix_geteuid() === $file_owner)
		{
			// check for 0200
			if($statinfo['mode'] & 128)
			{
				// check for 0100 if necessary
				if(($is_parent_folder && ($statinfo['mode'] & 64)) || !$is_parent_folder)
				{
					return true;
				}
			}
		} else
		{
			$all_my_gids = posix_getgroups();
			foreach ($all_my_gids as $cur_gid)
			{
				if($cur_gid === $file_group)
				{
					// check for 0020
					if($statinfo['mode'] & 16)
					{
						// check for 0010 if necessary
						if(($is_parent_folder && ($statinfo['mode'] & 16)) || !$is_parent_folder)
						{
							return true;
						}
					}
					break;
				}
			}
			$need_to_check_other = true;
		}
		if($need_to_check_other)
		{
			// check for 0002
			if($statinfo['mode'] & 2)
			{
				// check for 0001 if necessary
				if(($is_parent_folder && ($statinfo['mode'] & 1)) || !$is_parent_folder)
				{
					return true;
				}
			}
		}
	}
	return false;
}

function get_filecache($shortpath_to_file)
{
	global $CACHE_PREFIX;

	$actual_path = $CACHE_PREFIX . $shortpath_to_file;
	if(file_exists($actual_path))
	{
		return file_get_contents($actual_path);
	} else
	{
		note_error(__FUNCTION__, "Cache file \"$actual_path\" does not exist.");
	}
	return '';
}

$error_store = array();
function note_error($error_source, $error_msg)
{
	global $error_store;
	array_push($error_store, array($error_source, $error_msg));
}
function parseRss($RSS_FILE = "")
{
	$xmlText = get_filecache($RSS_FILE);
	$parsedXml = parseXml($xmlText);
	$items_arr = array();
	if($parsedXml)
	{
		$items_node_arr = $parsedXml->querySelectorAll("rss channel item");
		foreach($items_node_arr as $cur_item_node)
		{
			$items_arr[] = new NewsItem($cur_item_node);
		}
	}
	return $items_arr;
}
function parseVvoReply($VVO_FILE = "")
{
	$arrDepartures = array();
	if (class_exists('DOMDocument'))
	{
		$vvoDom = new DOMDocument();
		libxml_use_internal_errors(true);
		$vvoDom->loadHTMLFile($VVO_FILE);
		foreach ($vvoDom->getElementsByTagName("ul") as $ulItemList)
		{
			if (0 === strpos($ulItemList->getAttribute('class'), 'item-list'))
			{
				foreach ($ulItemList->childNodes as $listElement)
				{
					if($listElement->nodeType == XML_ELEMENT_NODE &&
					   !($listElement->hasAttribute("class") &&
					   "empty-list" === $listElement->getAttribute("class")) )
					{
						$curDepartureData = new DepartureData();
						if ( ($listElement instanceof DOMElement))
						{
							$myEle = getDomNodeByTagAndClass($listElement, 'div', 'list-entry load-details');
							if($myEle)
							{
								$myEle = $myEle[0];
								$curDepartureData->detailUrl = $myEle->getAttribute('data-id');
							}
							$myList=$listElement->getElementsByTagName("img");
							if ( ($myList instanceof DOMNodeList) 
							&& 0 < $myList->length)
							{
								$curDepartureData->vehicleType = $myList->item(0)->getAttribute("alt");
							}
						}
						$myEle = getDomNodeByTagAndClass($listElement, 'div', 'col c4of12 tour');
						if ($myEle) $myEle = $myEle[0];
						if ( $myEle instanceof DOMElement )
						{
							$myList=$myEle->getElementsByTagName('strong');
							if ( ($myList instanceof DOMNodeList) 
							&& 0 < $myList->length)
							{
								$curDepartureData->vehicleId=$myList->item(0)->nodeValue;
							}
							$myList=$myEle->getElementsByTagName('p');
							if ( ($myList instanceof DOMNodeList) 
							&& 1 < $myList->length)
							{
								$curDepartureData->vehicleDestination=$myList->item(1)->nodeValue;
							}
						}
						$myEle = getDomNodeByTagAndClass($listElement, 'div', 'c2of12');
						if ($myEle) $myEle = $myEle[count($myEle) - 1];
						if ( $myEle instanceof DOMElement )
						{
							$myList=$myEle->getElementsByTagName('strong');
							if ( ($myList instanceof DOMNodeList) 
							&& 0 < $myList->length)
							{
								$curDepartureData->vehicleDeparture=str_replace(" Uhr", "", $myList->item(0)->nodeValue);
							}
						}
						$arrDepartures[] = $curDepartureData;
					}
				}

				break;
			}
		}
	} else {
		print ('DOMDocument does not exist<br/>');
	}
	return $arrDepartures;
}
function get_text_between($haystack, $needle_start, $needle_end, $offset = 0)
{
    $start_match = strpos($haystack, $needle_start, $offset);
    $end_match = FALSE;
    if(FALSE !== $start_match)
    {
        $start_match += strlen($needle_start);
        $end_match = strpos($haystack, $needle_end, $start_match);
        if(FALSE !== $end_match)
        {
            return substr($haystack, $start_match, $end_match - $start_match);
        }
    }
    return "";
}
function parse_openweathermap($res_description)//$WEATHER_JSON)
{
	$json_weather = array();
	try {
                $raw_json_data = get_filecache($res_description['file']);
		if(1 > strlen($raw_json_data)) note_error("OpenWeatherMap-Parsing", "The underlying JSON file is empty.");
		$json_weather = json_decode($raw_json_data, true);
	} catch(Exception $e)
	{
		// don't do anything
		//$json_weather = null;
		note_error("OpenWeatherMap-Parsing", "Error when trying to decode weather info as JSON.");
	}
	if( is_null($json_weather) )
	{
		note_error("OpenWeatherMap-Parsing", "\$json_weather is null");
		note_error("OpenWeatherMap-Parsing", "<span title='Could not parse " . $res_description['file'] . "'>&#9888;</span>");
		return;
	}
	$db_handle = new mysqli($res_description['secrets']['DBHOST'],
				$res_description['secrets']['DBUSER'],
				$res_description['secrets']['DBPASS'],
				$res_description['secrets']['DBDB']);
	if ($db_handle && ! ( $db_handle -> connect_errno ) ) {
		//everythin is well
	} else
	{
		note_error(__FUNCTION__, "Couldn't connect to database");
		echo "\n\n\nDB ERROR\n\n\n";
	}

	$json_weather = (array) $json_weather; // we could use 'get_object_vars()' just as well


	if(array_key_exists('hourly', $json_weather))
	{
/*
| openweathermap_forecast | CREATE TABLE `openweathermap_forecast` (
  `dt` bigint(20) DEFAULT NULL,
  `temp` decimal(7,3) DEFAULT NULL,
  `feels_like` decimal(7,3) DEFAULT NULL,
  `pressure` decimal(7,3) DEFAULT NULL,
  `humidity` decimal(7,3) DEFAULT NULL,
  `dew_point` decimal(7,3) DEFAULT NULL,
  `uvi` decimal(7,3) DEFAULT NULL,
  `clouds` decimal(7,3) DEFAULT NULL,
  `visibility` decimal(10,3) DEFAULT NULL,
  `wind_speed` decimal(7,3) DEFAULT NULL,
  `wind_deg` decimal(7,3) DEFAULT NULL,
  `wind_gust` decimal(7,3) DEFAULT NULL,
  `weather_id` int(11) DEFAULT NULL,
  `weather_name` varchar(255) DEFAULT NULL,
  `weather_description` varchar(255) DEFAULT NULL,
  `weather_icon` varchar(255) DEFAULT NULL,
  `pop` decimal(7,3) DEFAULT NULL,
  `rain` decimal(7,3) DEFAULT NULL,
  KEY `dt` (`dt`),
  KEY `dt_2` (`dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 |
*/
//TODO: enter the fields into the database
//       furthermore read database and print out from database
		$hourly_weather = $json_weather['hourly'];
		$combined_insert = 'REPLACE INTO openweathermap_forecast VALUES ';
		$is_first_entry = true;
		for($i = 0; $i < count($hourly_weather); $i++)
		{
			$cur_hour = (array) $hourly_weather[$i];
			if(array_key_exists('dt', $cur_hour))
			{
				if($is_first_entry)
				{
					$is_first_entry = false;
				} else
				{
					$combined_insert .= ', ';
				}
				$cur_description = array('id'          => 0,
							 'main'        => "NULL",
							 'description' => "NULL",
							 'icon'        => "NULL");
				if(array_key_exists('weather', $cur_hour)
				&& is_array($cur_hour['weather']))
				{
//$cur_hour['weather'];
					$cur_weather_desc_temp = (array) ($cur_hour['weather'][0]);
					if(array_key_exists('id', $cur_weather_desc_temp)) $cur_description['id'] = (int) $cur_weather_desc_temp['id'];
					foreach(array('main', 'description', 'icon') as $cur_entry)
					{
						if(array_key_exists($cur_entry, $cur_weather_desc_temp))
						{
							$cur_description[$cur_entry] = (string) $cur_weather_desc_temp[$cur_entry];
							if(0 < strlen($cur_description[$cur_entry]))
							{
								$cur_description[$cur_entry] = "'" . $cur_description[$cur_entry] . "'";
							}
						}
					}
				}
				
				$combined_insert .= sprintf("(%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",
					$cur_hour['dt'],
					array_key_exists('temp', $cur_hour) ? $cur_hour['temp'] : "NULL",
					array_key_exists('feels_like', $cur_hour) ? $cur_hour['feels_like'] : "NULL",
					array_key_exists('pressure', $cur_hour) ? $cur_hour['pressure'] : "NULL",
					array_key_exists('humidity', $cur_hour) ? $cur_hour['humidity'] : "NULL",
					array_key_exists('dew_point', $cur_hour) ? $cur_hour['dew_point'] : "NULL",
					array_key_exists('uvi', $cur_hour) ? $cur_hour['uvi'] : "NULL",
					array_key_exists('clouds', $cur_hour) ? $cur_hour['clouds'] : "NULL",
					array_key_exists('visibility', $cur_hour) ? $cur_hour['visibility'] : "NULL",
					array_key_exists('wind_speed', $cur_hour) ? $cur_hour['wind_speed'] : "NULL",
					array_key_exists('wind_deg', $cur_hour) ? $cur_hour['wind_deg'] : "NULL",
					array_key_exists('wind_gust', $cur_hour) ? $cur_hour['wind_gust'] : "NULL",
					$cur_description['id'],
					$cur_description['main'],
					$cur_description['description'],
					$cur_description['icon'],
					array_key_exists('pop', $cur_hour) ? $cur_hour['pop'] : "NULL",
					array_key_exists('rain', $cur_hour)
					&& array_key_exists('1h', $cur_hour['rain']) ? $cur_hour['rain']['1h'] : "NULL"
				);
			}
		}
		$combined_insert .= ";";
		if($db_handle) $result = $db_handle->query($combined_insert);
		//$db_handle = new mysqli ($VOCAB_DB_HOST, $VOCAB_DB_USER, $VOCAB_DB_PASS, $VOCAB_DB_DB);
/*
group in 3 hour-intervals, show min-avg-max temperature for these intervals:

SELECT FROM_UNIXTIME((FLOOR((dt - 1657576800) / (3600*3)) * (3600 * 3)) + 1657576800) AS 'mytime', MIN(temp), AVG(temp), MAX(temp) FROM openweathermap_forecast WHERE dt >= 1657576800 AND dt <= 1657746000 GROUP BY mytime;
*/
		file_put_contents('lengthy-sql-replace.sql', $combined_insert);
		//echo "<pre>";
		//print_r($combined_insert);
		//echo "</pre>";

		//println('<div style="font-family: Sans, Sans-Serif; background-color: #d0d000; color: #ffffff; text-shadow: 1px 1px 3px rgba(0,0,0,1); border-radius: 20px; float: right; font-size: 90%; padding: 0em 1em 0em 1em;"><h3>OpenWeatherMap</h3>');
	} else
	{
		note_error(__FUNCTION__, "Key 'hourly' doesn't exist in JSON weather data.");
	}
}
function print_openweathermap($res_description)
{
	$db_handle = new mysqli($res_description['secrets']['DBHOST'],
				$res_description['secrets']['DBUSER'],
				$res_description['secrets']['DBPASS'],
				$res_description['secrets']['DBDB']);
	if (!$db_handle || ( $db_handle -> connect_errno ) ) {
		note_error(__FUNCTION__, "Couldn't connect to database");
	} else
	{
		$ts_today = strtotime("today",time());
		$ts_end = $ts_today + 86400*2 - 3600;
		$result = $db_handle->query("SELECT ((FLOOR((dt - $ts_today) / (3600*8)) * (3600 * 8)) + $ts_today) AS 'mytime', MIN(temp) AS 'temp_min', AVG(temp) AS 'temp_avg', MAX(temp) AS 'temp_max', AVG(humidity) AS 'humidity', MAX(wind_speed) AS 'wind_speed', AVG(clouds) AS 'clouds', GROUP_CONCAT(weather_name) AS 'weather_name', GROUP_CONCAT(weather_icon) AS 'weather_icon', GROUP_CONCAT(weather_description) AS 'weather_description' FROM openweathermap_forecast WHERE dt >= $ts_today AND dt <= $ts_end GROUP BY mytime");
		println('<div class="weather_box">');
		$prev_date = "";
                $cur_date = "";
                //for($i = 0; $i < count($hourly_weather); $i+=8)
		//{
		$cur_hour = null;
		$i = 0;
		while($cur_hour = $result->fetch_assoc())
		{
			//$cur_hour = $hourly_weather[$i];
			$prev_date = $cur_date;
// API description here:
//   https://openweathermap.org/current
			$cur_time = $cur_hour['mytime'];
			$cur_temp = array('min' => $cur_hour['temp_min'],
				'avg' => $cur_hour['temp_avg'],
				'max' => $cur_hour['temp_max']
			);
			$cur_humidity = $cur_hour['humidity'];
			$cur_wind_speed = $cur_hour['wind_speed']; // meters per second
			$cur_wind_speed = round($cur_wind_speed * 3600 / 1000);
			$cur_clouds_percent = $cur_hour['clouds'];
			$cur_clouds_name = $cur_hour['weather_name'];
			$cur_clouds_icon = $cur_hour['weather_icon'];
			$cur_clouds_description = $cur_hour['weather_description'];
			$cur_date = date('d.m.', $cur_time);
			$cur_time = date('H:i', $cur_time);
			if(0 !== strcmp($prev_date, $cur_date))
			{
				if(0 !== $i)
				{
					println('<div class="weather_cleaner"></div>');
					println('</div>');
				}
				println('<div class="weather_column">');
				println('<div class="weather_date">' . $cur_date . '</div>');
			}
			//println($cur_time . ", &nbsp; &nbsp; " . $cur_temp . " C, &nbsp; &nbsp;" . $cur_clouds_description . ", &nbsp; &nbsp; Wind: " . $cur_wind_speed . " km/h");
			//println('<br/>');
			$cloudinessClass = 'rainy';
			if($cur_clouds_percent < 30) $cloudinessClass = 'sunny';
			elseif($cur_clouds_percent < 60) $cloudinessClass = 'bright';
			else $cloudinessClass = 'rainy';
			println('    <div class="weather_detail ' .$cloudinessClass . '">'); //one of: sunny, bright, rainy, thunderstorm
			println('      <div class="weather_time">' . $cur_time . '</div>');
			println('      <div class="weather_cloudiness">' . $cur_clouds_name . '</div>');
			println('      <div class="weather_temperature">' . round($cur_temp['min']) . 'C&nbsp;&leq;&nbsp;' . round($cur_temp['avg']) . 'C&nbsp;&leq;&nbsp;' . round($cur_temp['max']) . 'C</div>');
			//println('      <div class="weather_downfall">' . str_replace("Risiko ","", $weather_row->weatherDownfall) . ' Regen</div>');
			println('      <div class="weather_windspeed">' . $cur_wind_speed . ' km/h Wind</div>');
			println('    </div>');
			$i += 1;
		}
		if ($i >= 3)
		{
			println('<div class="weather_cleaner"></div>');
			println('</div>');
		}
		println('</div>');
	//println("</div>");
	}
}
function parseWeatherReply($WEATHER_FILE)
{
	$arrWeather = array();
	$weatherHtml = file_get_contents($WEATHER_FILE);
	$posWeatherColumn = stripos($weatherHtml, "<div class=\"forecast-list list-standard\">");
	while(false !== $posWeatherColumn)
	{
		$pos_cur_row = $posWeatherColumn + 20;
		$nextColumnEnd = stripos($weatherHtml, "<div class=\"forecast-item-day\">", $pos_cur_row);
		if(false !== $nextColumnEnd)
		{
			$has_reached_end = false;
			$cur_date = strip_surrounding_whitespace(get_text_between($weatherHtml,"<div class=\"text-date\">", "</div>", $pos_cur_row));
			$cur_weekday = strip_surrounding_whitespace(get_text_between($weatherHtml, "<div class=\"text-day\">", "</div>", $pos_cur_row));
			while(false === $has_reached_end)
			{
				$nextDetailBox = stripos($weatherHtml,"<div class=\"forecast-column column-1 wt-border-radius-6\">", $pos_cur_row + 46);
				if(false !== $nextDetailBox && $nextDetailBox < $nextColumnEnd)
				{
					$pos_cur_row = $nextDetailBox;
					$cur_weather_row = new WeatherRow();
					$cur_weather_row->weatherDate = $cur_date;
					$cur_weather_row->weatherWeekday = $cur_weekday;
					$cur_weather_row->weatherTime = strip_surrounding_whitespace(get_text_between($weatherHtml,"<div class=\"forecast-column-date\">", "</div>", $pos_cur_row));
					$cur_weather_row->weatherCloudiness = strip_surrounding_whitespace(get_text_between($weatherHtml,"<div class=\"forecast-column-condition\">", "</div>", $pos_cur_row));
					$cur_weather_row->weatherTemperature = strip_surrounding_whitespace(get_text_between($weatherHtml,"<div class=\"forecast-text-temperature wt-font-light\">", "</div>", $pos_cur_row));
					$cur_weather_row->weatherDownfall = strip_surrounding_whitespace(strip_tags(get_text_between($weatherHtml,"<span>Risiko</span> <span class=\"wt-font-semibold\">", "</div>", $pos_cur_row)));
					$pos_wind = stripos($weatherHtml, "<div class=\"forecast-wind-text\">", $pos_cur_row);
					if(false !== $pos_wind)
					{
						$pos_wind += 33;
						$cur_weather_row->weatherWindDesc = strip_surrounding_whitespace(get_text_between($weatherHtml, "<div class=\"forecast-wind-text\">", "<br>", $pos_wind));
						$cur_weather_row->weatherWindSpeed = strip_surrounding_whitespace(get_text_between($weatherHtml, "<span class=\"wt-font-semibold\">", "</span>", $pos_wind));
						
					}
					$arrWeather[] = $cur_weather_row;
				} else
				{
					$has_reached_end = true;
				}
			}
		} else {
			break;
		}
		$posWeatherColumn = stripos($weatherHtml, "<div class=\"forecast-item-day\">", $nextColumnEnd);
	}
	return $arrWeather;
}
function printWeatherBox($arrWeather, $countDays)
{
	$old_date = ""; 
	$cur_date = "";
	$i = 0;
	$j = 0;
	$closing_tags_required = true;
	if(0 < count($arrWeather))
	{
		println("<div class=\"weather_box\">");
	}
	foreach($arrWeather as $curWeatherRow)
	{
		$cur_date = $curWeatherRow->weatherDate;
		if(0 != strcmp($cur_date, $old_date))
		{
			if(0 < $i)
			{
				println("    <div class=\"weather_cleaner\"> &nbsp;</div>\n  </div>");
			}
			if($j < $countDays)
			{
				println("  <div class=\"weather_column\">");
				println('    <div class="weather_date">' . $curWeatherRow->weatherWeekday . ' ' . $curWeatherRow->weatherDate . '</div>');
			} else
			{
				$closing_tags_required = false;
				break;
			}
			$j++;
		}
		$cur_time = (int) str_replace(':', '', $curWeatherRow->weatherTime);
                if(400 < $cur_time)
		{
			printWeatherDetail($curWeatherRow);
		}
		$old_date = $cur_date;
		$i++;
	}
	if(0 < $i)
	{
		if($closing_tags_required)
		{
			println("    </div>\n    <div class=\"weather_cleaner\"> &nbsp;</div>\n");
		}
		println("  <div class=\"cleaner\"> &nbsp;</div>\n");
		println("\n</div>");
	}
}
function println($text) {
	print($text . "\n");	
}
//function getDomNodeByTagAndClass (DOMElement $parentNode, string $tagName, string $className) {
function getDomNodeByTagAndClass ( $parentNode, $tagName,  $className) {
	$resultArray=array();
//DEBUG:
	if('ul' == $tagName) {
		print("function getDomNodeByTagAndClass invoked with arguments:<br/>\n  tagName:" . $tagName . "<br/>\n  className:" . $className . "<br/>\n  parentNode:" . $parentNode);
		var_dump($parentNode);
		println('<br/>');
	}
	if ( $parentNode
	&& ($parentNode instanceof DOMElement
	 || $parendNode instanceof DOMDocument))
	{
		if('ul' == $tagName) println('$parentNode is an instance of DOMElement or an instance of DOMDocument<br/>');
		foreach ($parentNode->getElementsByTagName($tagName) as $desiredElement)
		{
			if ( $desiredElement instanceof DOMElement)
			{
				if ($desiredElement->hasAttribute("class"))
				{
//DEBUG:
					if('ul' == $tagName)
						println('Checking ul-element\'s class. It\'s value:'.$desiredElement->getAttribute("class") . '<br/>');
					if (0 === stripos($desiredElement->getAttribute("class"),$className))
					{
						$resultArray[] = $desiredElement;
					} else
					{
						if ( false === strpos($className, " "))
						{
							foreach (explode(" ", $desiredElement->getAttribute("class")) as $sequenceOfClassName)
							{
								if (is_string( $sequenceOfClassName))
								{
									if ($sequenceOfClassName === $className)
									{
										$resultArray[] = $desiredElement;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	return $resultArray;
/*
	if (0 < count($resultArray))
	{
		return $resultArray;
	} else
	{
		return NULL;
	}
*/
}
function downloadToFile ( $uri, $filename)
{
	global $CACHE_PREFIX;
	$downloadSucceeded = false;

	$filename = $CACHE_PREFIX . $filename;
	
/* CURL does not work here.  No need to bother with it
if ( function_exists('curl_init') 
  && function_exists('curl_setopt_array') 
  && function_exists('curl_exec') 
  && function_exists('curl_close'))
{

	$curlOptions = array(
   	CURLOPT_FILE => $VVO_FILE,
   	CURLOPT_TIMEOUT => 30,
   	CURLOPT_URL => $reqUrlVvo
	);
	$curlHandle = curl_init();
	curl_setopt_array($curlHandle, $curlOptions);
	if (false === curl_exec($curlHandle))
	{
		print ('curl-errorno: ' . curl_errno() . "<br/>\n");
		print ('curl-error: ' . curl_error() . "<br/>\n");
	} else {
		$downloadSucceeded = true;
	}
	curl_close($curlHandle);
}
*/
	if (function_exists('file_put_contents') && function_exists('file_get_contents'))
	{
		if(is_writable($filename) || !file_exists($filename))
		{
			try{
				$response_body = file_get_contents($uri);
				$write_success = file_put_contents($filename, $response_body);
				if (false !== $write_success && 0 < $write_success) {
					$downloadSucceeded = true;
				} else
				{
					note_error(__FUNCTION__, "Wanted to write " . strlen("$response_body") . " Bytes, but no bytes could be written. I/O failure for file \"$filename\"");
				}
				unset($response_body);
			} catch(Exception $e)
			{
				//do nothing
				note_error(__FUNCTION__, "Error during file_get_contents() or file_put_contents() with URL \"$uri\" and filename \"$filename\".");
			}
		} else
		{
			$myid = array();
			exec("id", $myid);
			note_error(__FUNCTION__, "File \"$filename\" not writable. Id information: " . implode("\n", $myid));
		}
	} else {
		print ('file_put_contents and file_get_contents not available<br/>');
	}
	return $downloadSucceeded;
}

function print_vehicle_symbol ($vehicleType, $httpUserAgent) 
{
	if ( false !== stripos($vehicleType, "Zug"))
	{
		//println("🚆 ")
		if (false === stripos($httpUserAgent . '', 'Kindle'))
		{
			println('<img src="img/travel/train_small.jpg" width="60" height="28" alt="Bus" title="' . $vehicleType . '"/>');
		} else {
?>

<pre class="train">   ++++++++   
  ####  ####  
 :####,'####  
 :##+:` :###  
 :    `       
 :   ,##.     
 :##########  
  ,########.  
 :#; #### ##  
 :### ##.###  
 :#,##  ##.#  
 :` ##+##+ ;  
 :#+######+#  
  ##########  
  ,########`  
    #````#    
  ;;      #.                                                                                                                                     ?>
 #`````````.# 
</pre>

<?php
		}
	} else if ( false !== stripos($vehicleType, "bus"))
	{
		if (false === stripos($httpUserAgent . '', 'Kindle'))
		{
		//println("🚌")
			println('<img src="img/travel/bus_small.jpg" width="60" height="27" alt="Bus" title="' . $vehicleType . '"/>');
		} else {
?>

<pre class="bus">  .#################### 
  #####################+
  #,  #   #   #   #   #+
 +#   #   #   #   #   #+
 ##   #   #   #   #   #+
 #.   #   #   #   #   #+
+######################+
+######################+
+######################+
+## ` ###########.` ###+
+# ###:#########'### ##+
 +:### ######### ###;###
  `###           ###`   
   ,#             #:    
</pre>
<?php
		}
	} else if (false !== stripos($vehicleType, "Straßenbahn")
	       || false !== stripos($vehicleType, "Tram"))
        {
		//println("🚊");
		println('<img src="img/travel/tram_small.jpg" width="60" height="32" alt="Tram" title="' . $vehicleType . '"/>');
	}
}

function simplifiedVehicleClass($original_type)
{
	$vehicleClassNamesArr = array("Zug" => "train", "bus" => "bus", "Straßenbahn" => "tram", "Tram" => "tram", "CityBus" => "bus");
	$vehicleClassName = '';
	foreach($vehicleClassNamesArr as $searchString => $classString)
	{
		if(false !== stripos($original_type, $searchString))
		{
			$vehicleClassName .= ' vehicle_' . $classString;
		}
	}
	return $vehicleClassName;
}

function printDepartureAPIBox($res_description)//$heading, $json_departures_file)
{
	$httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
	// use try/catch for JSON parsing errors
	$json_departures = null;

	try {
		$json_departures = json_decode(get_filecache($res_description['file']), true);
	} catch(Exception $e)
	{
		// don't to anything
		$json_departures = null;
	}
	if( is_null($json_departures) )
	{
		println("<span title='Could not parse $json_departures_file'>&#9888;</span>");
		return;
	}
	println('<div class="wrapper_departures">');
	println('<h3 class="departures_heading">' . (array_key_exists('title', $res_description) ? $res_description['title'] : '') . '</h3>');
	// Development
	//println('<pre>');
	//print_r($json_departures);
	//println('</pre>');
	if(array_key_exists('Departures', $json_departures))
	{
		// doesn't help me here :(
		// $local_time_offset = date_offset_get(); // should get offset by seconds
		$offset_for_time = -7200000; //  -3600000 in winter, -7200000 in summer;
		foreach($json_departures['Departures']  as $cur_departure_item)
		{
			println('  <div class="departure_row ' . simplifiedVehicleClass($cur_departure_item['Mot']) . '">');
			println('    <div class="departure_cell-id">');
			println('      ' . $cur_departure_item['LineName']);
			println('    </div>');
			println('    <div class="departure_cell-type">');
			print_vehicle_symbol($cur_departure_item['Mot'], $httpUserAgent);
			println('    </div>');
			println('    <div class="departure_cell-destination">');
			println('      ' . $cur_departure_item['Direction']);
			println('    </div>');
			//$cur_time = $cur_departure_item['RealTime'];
			$matches = array();
			$cur_time = $cur_departure_item['ScheduledTime'];
			if(array_key_exists('RealTime', $cur_departure_item)) $cur_time = $cur_departure_item['RealTime'];
			preg_match('/([0-9]+)/', $cur_time, $matches);
			if(2 <= count($matches))
			{
				$cur_time = doubleval($matches[1]);
				$cur_time = $cur_time + $offset_for_time;
			} else
			{
				$cur_time = time();
			}
			$departure_time = $cur_time / 1000;
			println('    <div class="departure_cell-departure" title="' . $departure_time . ' aus ' . $cur_departure_item['ScheduledTime'] . ' matches ' . implode(',', $matches) .  '" timestamp="' . $cur_time . '">');
			print('        ⌚');
			println(date('H:i', $departure_time));
			println('    </div>');
			println('    <div class="cleaner"> &nbsp; </div>');
			println('  </div>');
		}

		/*
			println('  <div class="departure_row ' . $vehicleClassName . '" onclick="Departures.loadDetails(this, \'' . $departure->detailUrl . '\')">');
			println('    <div class="departure_cell-id">');
			println('      ' . $departure->vehicleId);
			println('    </div>');
			println('    <div class="departure_cell-type">');
			print_vehicle_symbol($departure->vehicleType, $httpUserAgent);
			println('    </div>');
	
			println('    <div class="departure_cell-destination">');
			println('      ' . $varDestination);
			println('    </div>');
			println('    <div class="departure_cell-departure">');
			print('        ⌚');
			println($departure->vehicleDeparture);
			println('    </div>');
			println('    <div class="cleaner"> &nbsp; </div>');
			println('  </div>');
		 */
	}
	println('</div>');
}
function printDepartureBox($httpUserAgent, $arrDepartures, $departureWhiteBlackList, $isWhitelist = true)
{
	println('<div class="wrapper_departures">');
	foreach ($arrDepartures as $departure)
	{
		$varDestination = $departure->vehicleDestination;
		$varDestination = str_replace('Hauptbahnhof','Hbf.', $varDestination);
		$varDestination = str_replace(array('Bahnhof','bahnhof'),array('Bf.','bf.'), $varDestination);
		$varDestination = str_replace('Dresden', 'DD', $varDestination);
		$varDestination = str_replace('Kaditz, Am Vorwerksfeld','Kaditz', $varDestination);
		if ($departureWhiteBlackList)
		{
			foreach($departureWhiteBlackList as $listItem)
			{
				if(true === $isWhitelist)
				{
					if( FALSE === stripos($varDestination, $listItem))
					{
						continue 2;
					}	
				} else
				{
					if( FALSE !== stripos($varDestination, $listItem) )
					{
						continue 2;
					}
				}
			}
		}
		$vehicleClassNamesArr = array("Zug" => "train", "bus" => "bus", "Straßenbahn" => "tram");
		$vehicleClassName = '';
		foreach($vehicleClassNamesArr as $searchString => $classString)
		{
			if(false !== stripos($departure->vehicleType, $searchString))
			{
				$vehicleClassName .= ' vehicle_' . $classString;
			}
		}

		println('  <div class="departure_row ' . $vehicleClassName . '" onclick="Departures.loadDetails(this, \'' . $departure->detailUrl . '\')">');
		println('    <div class="departure_cell-id">');
		println('      ' . $departure->vehicleId);
		println('    </div>');
		println('    <div class="departure_cell-type">');
		print_vehicle_symbol($departure->vehicleType, $httpUserAgent);
		println('    </div>');

		println('    <div class="departure_cell-destination">');
		println('      ' . $varDestination);
		println('    </div>');
		println('    <div class="departure_cell-departure">');
		print('        ⌚');
		println($departure->vehicleDeparture);
		println('    </div>');
		println('    <div class="cleaner"> &nbsp; </div>');
		println('  </div>');
	}
	println('</div>');
}

function get_line_matching_str($haystack, $needle, $offset = 0)
{
	$pos_of_needle = stripos($haystack, $needle, $offset);
	if(FALSE !== $pos_of_needle)
	{
		$last_newline_pos=strrpos($haystack,"\n",(strlen($haystack) - $pos_of_needle - 1) * -1);
		$next_newline_pos=strpos($haystack,"\n",$pos_of_needle + 1);
		if (FALSE !== $next_newline_pos
		&&  FALSE !== $last_newline_pos)
		{
			return substr($haystack, $last_newline_pos + 1, $next_newline_pos - $last_newline_pos - 1);
		}
	}	
	return "";
}

function strip_surrounding_whitespace($origin_str)
{
	$lower_lim = 0;
	$len_of_origin = strlen($origin_str);
	$cur_char = 0;
	for(; $lower_lim < $len_of_origin; $lower_lim++)
	{
		$cur_char = ord($origin_str[$lower_lim]);
		if (($cur_char < 9 || $cur_char > 13) && $cur_char != 32)
			break;
	}
	$upper_lim = $len_of_origin - 1;
	for(; $upper_lim >= 0; $upper_lim--)
	{
		$cur_char = ord($origin_str[$upper_lim]);
		if (($cur_char < 9 || $cur_char > 13) && $cur_char != 32)
			break;
	}
	if ($lower_lim < $upper_lim)
	{
		return substr($origin_str,$lower_lim, $upper_lim - $lower_lim + 1);
	} else
	{
		return "";
	}
}

function printWeatherDetail($weather_row)
{
	$cloudinessClass = '';
	switch($weather_row->weatherCloudiness)
	{
		case 'klar':
		case 'sonnig':
			$cloudinessClass = 'sunny';
			break;
		case 'heiter':
		case 'heiter bis wolkig':
		case 'meist wolkig':
		case 'neblig':
			$cloudinessClass = 'bright';
			break;
		case 'wolkig':
		case 'unbeständig':
		case 'wechselhaft':
		case 'regnerisch':
			$cloudinessClass = 'rainy';
			break;
		case 'gewittrig':
			$cloudinessClass = 'thunderstorm';
			break;
	}
	println('    <div class="weather_detail ' .$cloudinessClass . '">');
	println('      <div class="weather_time">' . $weather_row->weatherTime . '</div>');
	println('      <div class="weather_cloudiness">' . $weather_row->weatherCloudiness . '</div>');
	println('      <div class="weather_temperature">' . $weather_row->weatherTemperature . ' C</div>');
	println('      <div class="weather_downfall">' . str_replace("Risiko ","", $weather_row->weatherDownfall) . ' Regen</div>');
	println('      <div class="weather_windspeed">' . str_replace(array("(", ")", " km/h"), array("", "", " km"), $weather_row->weatherWindSpeed) . ' Wind</div>');
	println('    </div>');
}
