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
$error_store = array();
function note_error($error_source, $error_msg)
{
	global $error_store;
	array_push($error_store, array($error_source, $error_msg));
}
function parseRss($RSS_FILE = "")
{
	$xmlText = file_get_contents($RSS_FILE);
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
function parse_openweathermap($WEATHER_JSON)
{
	if(file_exists($WEATHER_JSON))
	{
		try {
			$json_weather = json_decode(file_get_contents($WEATHER_JSON), true);
		} catch(Exception $e)
		{
			// don't to anything
			$json_weather = null;
		}
	}
	if( is_null($json_weather) )
	{
		note_error("OpenWeatherMap-Parsing", "<span title='Could not parse $WEATHER_JSON'>&#9888;</span>");
		return;
	}
	if(array_key_exists('hourly', $json_weather))
	{
//TODO: enter the fields into the database
//       furthermore read database and print out from database
		$hourly_weather = $json_weather['hourly'];
		//println('<div style="font-family: Sans, Sans-Serif; background-color: #d0d000; color: #ffffff; text-shadow: 1px 1px 3px rgba(0,0,0,1); border-radius: 20px; float: right; font-size: 90%; padding: 0em 1em 0em 1em;"><h3>OpenWeatherMap</h3>');
		println('<div class="weather_box">');
		$prev_date = "";
                $cur_date = "";
                for($i = 0; $i < count($hourly_weather); $i+=8)
		{
			$cur_hour = $hourly_weather[$i];
			$prev_date = $cur_date;
// API description here:
//   https://openweathermap.org/current
			$cur_time = $cur_hour['dt'];
			$cur_temp = $cur_hour['temp'];
			$cur_humidity = $cur_hour['humidity'];
			$cur_wind_speed = $cur_hour['wind_speed']; // meters per second
			$cur_wind_speed = round($cur_wind_speed * 3600 / 1000);
			$cur_clouds_percent = $cur_hour['clouds'];
			$cur_clouds_name = $cur_hour['weather'][0]['main'];
			$cur_clouds_icon = $cur_hour['weather'][0]['icon'];
			$cur_clouds_description = $cur_hour['weather'][0]['description'];
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
			println('      <div class="weather_temperature">' . round($cur_temp) . ' C</div>');
			//println('      <div class="weather_downfall">' . str_replace("Risiko ","", $weather_row->weatherDownfall) . ' Regen</div>');
			println('      <div class="weather_windspeed">' . $cur_wind_speed . ' km/h Wind</div>');
			println('    </div>');
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

function printDepartureAPIBox($heading, $json_departures_file)
{
	$httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
	// use try/catch for JSON parsing errors
	$json_departures = null;

	if(file_exists($json_departures_file))
	{
		try {
			$json_departures = json_decode(file_get_contents($json_departures_file), true);
		} catch(Exception $e)
		{
			// don't to anything
			$json_departures = null;
		}
	}
	if( is_null($json_departures) )
	{
		println("<span title='Could not parse $json_departures_file'>&#9888;</span>");
		return;
	}
	println('<div class="wrapper_departures">');
	println('<h3 class="departures_heading">' . $heading . '</h3>');
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
