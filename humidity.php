<?php

include('config.php');

$environ_handle = new mysqli($ENVIRON_DB_HOST, $ENVIRON_DB_USER, $ENVIRON_DB_PASS, $ENVIRON_DB_DB);
if(isset($_GET['brief_humidity']))
{
	$environ_result = $environ_handle->query('SELECT time, ort, temperature, pressure, humidity FROM sensors WHERE ort=\'vorratskammer\' ORDER BY time DESC LIMIT 1');
	if($environ_result && 0 < $environ_result->num_rows)
	{
		$cur_row = $environ_result->fetch_assoc();
		echo $cur_row['humidity'] . '% (' . $cur_row['temperature'] . 'C)';
	}
} else {
function js_escape($text) {
	return str_replace(array('"', '\\'), array('\\"', '\\\\'), $text);
}
?>
<!DOCTYPE html5>
<head>
<meta charset="utf-8" />
<title>Sensordaten</title>
<meta http-equiv="refresh" content="60" />
<script type="text/javascript">
<?php
	$cur_timestamp = time();
	$max_age = $cur_timestamp - 43200;
	$environ_result = $environ_handle->query('SELECT time, ort, temperature, pressure, humidity FROM sensors WHERE time>=' . $max_age . ' ORDER BY ort ASC, time ASC');
	if($environ_result && 0 < $environ_result->num_rows)
	{
		$last_ort = NULL;
		while($cur_row = $environ_result->fetch_assoc())
                {
			if($cur_row['ort'] != $last_ort)
			{
				if(NULL == $last_ort)
				{
					echo "var sensors = {\n";
				} else {
					echo "],\n";
				}
				echo '"' . js_escape($cur_row['ort']) . '": [';
				$last_ort = $cur_row['ort'];
			} else {
				echo ",\n";
			}
			echo '{ "time": ' . $cur_row['time'] . ', "humidity": ' . $cur_row['humidity'] . ', "temperature": ' . $cur_row['temperature'] . ', "pressure": ' . $cur_row['pressure'] . '}';
		}
		echo "]};\n";
	}
?>
function Graticule(ctx, offsetDimension, minTime, maxTime, minValue, maxValue)
{
  this.ctx = ctx;
  this.graticuleDimensions = offsetDimension;
  this.timeConstraints = [minTime, maxTime];
  this.valueConstraints = [minValue, maxValue];
  this.widthPerSecond = this.graticuleDimensions[2] / (this.timeConstraints[1] - this.timeConstraints[0]);
  this.valueSubtract = this.valueConstraints[0];
  this.valueMultiply = this.graticuleDimensions[3] / (this.valueConstraints[1] - this.valueConstraints[0]);
  this.drawDataPoint = function(time, value)
  {
    var y = Math.round(this.graticuleDimensions[1] + this.graticuleDimensions[3] - ((value - this.valueSubtract) * this.valueMultiply));
    ctx.fillRect( 
      Math.floor(this.graticuleDimensions[0] + (time - this.timeConstraints[0]) * this.widthPerSecond),
      y,
      1,
      this.graticuleDimensions[3] - y);
  };
  this.drawMinMax = function(drawMin, drawMax)
  {
    ctx.fillStyle = "#20f020";
    ctx.font = "20px Sans";
    if(drawMax)
    {
      ctx.fillRect(this.graticuleDimensions[0], this.graticuleDimensions[1], this.graticuleDimensions[2], 2);
      ctx.fillText("" + this.valueConstraints[1], this.graticuleDimensions[0] + this.graticuleDimensions[2] / 2 - 40, this.graticuleDimensions[1] + 18);
    }
    if(drawMin)
    {
      ctx.fillRect(this.graticuleDimensions[0], this.graticuleDimensions[1] + this.graticuleDimensions[3] - 2, this.graticuleDimensions[2], 2);
      ctx.fillText("" + this.valueConstraints[0], this.graticuleDimensions[0] + this.graticuleDimensions[2] / 2 - 40, this.graticuleDimensions[1] + this.graticuleDimensions[3] - 4);
    }
  };
  this.drawGrid = function(pixelsLeft, pixelsBottom)
  {
    ctx.fillStyle = "#2020f0";
    ctx.font = "14px Sans";
    var everyThatManyPixels = 40;
    var timeStretch = this.timeConstraints[1] - this.timeConstraints[0];
    var minMaxTimeInterval = [this.graticuleDimensions[2] / everyThatManyPixels * 0.8, this.graticuleDimensions[2] / everyThatManyPixels * 1.6];
    for(var i = 0; i < minMaxTimeInterval.length; ++i)
    {
      minMaxTimeInterval[i] = timeStretch / minMaxTimeInterval[i];
    }
    var possibleTimeIntervals = [1, 15, 30, 60, 300, 900, 1800, 3600, 7200, 10800, 43200, 86400];
    var chosenTimeInterval = 0;
    for(var i = 1; i < possibleTimeIntervals.length; ++i)
    {
      if(possibleTimeIntervals[i] < minMaxTimeInterval[0])
      {
        continue;
      } else
      {
        chosenTimeInterval = i;
        break;
      }
      
    }
    chosenTimeInterval = possibleTimeIntervals[chosenTimeInterval];
    for(var i = this.timeConstraints[0] - (this.timeConstraints[0] % chosenTimeInterval); i < this.timeConstraints[1]; i += chosenTimeInterval)
    {
      if(i >= this.timeConstraints[0])
      {
        var x = this.graticuleDimensions[0] + (i - this.timeConstraints[0]) * this.widthPerSecond;
        ctx.fillRect( x, this.graticuleDimensions[1], 2, this.graticuleDimensions[3] + pixelsBottom / 4 );
        ctx.fillText(dateToHHMMStr(new Date(i * 1000)), x - 20, this.graticuleDimensions[1] + this.graticuleDimensions[3] + pixelsBottom / 2 );
      }
    }

    ctx.fillStyle = "#f02020";
    for(var i = 0; i < this.graticuleDimensions[3]; i += everyThatManyPixels)
    {
      ctx.fillRect(this.graticuleDimensions[0] - pixelsLeft, i, this.graticuleDimensions[2] + pixelsLeft, 2);
      ctx.fillText(Math.round((this.valueSubtract + ((this.graticuleDimensions[3] - i) / this.valueMultiply)) * 100) / 100, this.graticuleDimensions[0] - pixelsLeft, i + 14);
    }
  }
}
function dateToHHMMStr(curDate)
{
  return hhmmToStr(curDate.getHours(), curDate.getMinutes());
}
function hhmmToStr(minutes, seconds)
{
  var outStr = "";
  if(minutes < 10)
  {
    outStr += "0";
  }
  outStr += minutes;
  outStr += ":";
  if(seconds < 10)
  {
    outStr += "0";
  }
  outStr += seconds;
  return outStr; 
}
function generateSingleGraph(fieldname, measurepoints, masterWrapper)
{
  if(measurepoints && "object" == (typeof measurepoints) && measurepoints.length && 0 < measurepoints.length)
  {
    var wrapperEle = document.createElement("div");
    wrapperEle.style.float = "left";
    wrapperEle.style.marginRight = 10;
    var canvasSize = [500, 250];
    var pixelsLeft = 60, pixelsBottom = 30;
    wrapperEle.style.width = canvasSize[0] + pixelsLeft;
    wrapperEle.style.height = canvasSize[1] + 80;
    var headingEle = document.createElement("div");
    headingEle.appendChild(document.createTextNode(fieldname));
    headingEle.style.textAlign = "center";
    headingEle.style.fontFamily = "Sans";
    headingEle.style.fontSize = "20px";
    var canvasEle = document.createElement("canvas");
    canvasEle.width = canvasSize[0] + pixelsLeft;
    canvasEle.height = canvasSize[1] + pixelsBottom;
    ctx = canvasEle.getContext("2d");
    var minValue = measurepoints[0][fieldname];
    var maxValue = measurepoints[0][fieldname];
    for(var i = 1; i < measurepoints.length; ++i)
    {
      if(measurepoints[i][fieldname] < minValue)
      {
        minValue = measurepoints[i][fieldname];
      }
      if(measurepoints[i][fieldname] > maxValue)
      {
        maxValue = measurepoints[i][fieldname];
      }
    }
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, canvasSize[0], canvasSize[1]);
    var coordinates = new Graticule(ctx, [pixelsLeft, 0, canvasSize[0], canvasSize[1]], measurepoints[0].time, measurepoints[measurepoints.length - 1].time, minValue, maxValue);
    coordinates.drawGrid(pixelsLeft, pixelsBottom);

    ctx.fillStyle = "#000000";
    for(var i = 0; i < measurepoints.length; ++i) {
      coordinates.drawDataPoint(measurepoints[i].time, measurepoints[i][fieldname]);
    }
    coordinates.drawMinMax(true, false);

    wrapperEle.appendChild(headingEle);
    wrapperEle.appendChild(canvasEle);
    masterWrapper.appendChild(wrapperEle);
  }
}
function generateMultipleGraphs(ort, measurements)
{
  if(measurements && "object" == (typeof measurements) && measurements.length && 0 < measurements.length)
  {
    var ortsWrapper = document.createElement("div");
    ortsWrapper.style.fontFamily = "Sans";
    ortsWrapper.style.fontSize = "30px";
    var starttime = new Date(measurements[0].time * 1000);
    var endtime = new Date(measurements[measurements.length - 1].time * 1000);
    ortsWrapper.appendChild(document.createTextNode("Messwerte von " + ort + " von " + dateToHHMMStr(starttime)  + " Uhr bis " + dateToHHMMStr(endtime) + " Uhr"));
    var cleaner = document.createElement("div");
    cleaner.style.clear = "both";
    cleaner.style.width = 0;
    cleaner.style.height = 0;
    ortsWrapper.appendChild(cleaner);

    var firstObject = measurements[0];
    for(var field in firstObject)
    {
      if("time" != field)
      {
        generateSingleGraph(field, measurements, ortsWrapper);
      }
    }
    var cleaner = document.createElement("div");
    cleaner.style.clear = "both";
    cleaner.style.width = 0;
    cleaner.style.height = 0;
    ortsWrapper.appendChild(cleaner);
    document.getElementsByTagName("body")[0].appendChild(ortsWrapper);
  } else {
    console.log("error");
    console.log(measurements);
  }
}
function init()
{
  for(var field in sensors)
  {
    generateMultipleGraphs(field, sensors[field]);
  }
}
document.addEventListener("DOMContentLoaded", init);
</script>
</head>
<body>
</body>
</html>
<?php
}
if($environ_handle)
{
	$environ_handle->close();
}
?>
