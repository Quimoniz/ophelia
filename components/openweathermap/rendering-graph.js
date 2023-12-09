function init()
{
  //var plotEle = document.getElementById("weather_plot");
  var wrapperEle = document.querySelector(".weather_box");
  removeAllChilds(wrapperEle);

  var now   = new Date();
  var today = new Date( utilGetDateStr(now) );
  for(var i = 0; i < 2; ++i)
  {
    var myPlot = undefined;
    var plotlyConfiguration = new Object();
    var dayEle = document.createElement("div");
    wrapperEle.appendChild(dayEle);
    dayEle.classList.add("weather_day");

    var labelingEle = document.createElement("label");
    labelingEle.classList.add("weather_daylabel");
    dayEle.appendChild(labelingEle);
    labelingEle.appendChild(document.createTextNode(utilGetDayMonthStr(new Date(today.getTime() + 86400000 * i))));

    var plotEle = document.createElement("div");
    dayEle.appendChild(plotEle);
    plotEle.classList.add("weather_day_plot");

    plotlyConfiguration = configurePlotly(plotEle);
    plotlyConfiguration.plotlyData = buildDataSerieses(
      window.jsonWeather,
      plotlyConfiguration,
      today.getTime() + 86400000*i,
      today.getTime() + 86400000*(i+1)
    );
  
    myPlot = Plotly.newPlot(plotEle,
      plotlyConfiguration.plotlyData,
  	plotlyConfiguration.plotlyLayout,
  	plotlyConfiguration.plotlyConfiguration
    );
  }
}
function utilGetDateStr(dateObj) {
    if ( ! (dateObj instanceof Date)) return undefined;
    return dateObj.getFullYear() + "-" + (dateObj.getMonth() < 9 ? "0" : "") + (dateObj.getMonth() + 1) + "-"  + (dateObj.getDate() < 10 ? "0" : "")+ dateObj.getDate()
}
function utilGetDayMonthStr(dateObj) {
    if ( ! (dateObj instanceof Date)) return undefined;
    const monthNames = [
      "Januar",
      "Februar",
      "März",
      "April",
      "Mai",
      "Juni",
      "Juli",
      "August",
      "September",
      "Oktober",
      "November",
      "Dezember"
     ];
    return dateObj.getDate() + ". " + monthNames[dateObj.getMonth()];
}

function configurePlotly(plotEle)
{
  // Thanks to past-me
  // https://github.com/metricq/metricq-webview/blob/75d073de93353ad1e1d040df670829583c1ba6f5/js/MetricQWebView.js#L39
  // see the source file
  //   src/plots/cartesian/layout_attributes.js
  // for a complete set of available options
  var plotlyConfiguration = new Object();
  plotlyConfiguration.plotlyLayout = {
	  xaxis: {
	    type: 'date',
	    showticklabels: true,
	    ticks: "outside",
	    tickangle: 'auto',
	    tickfont: {
	      family: 'Open Sans, Sans, Verdana',
	      size: 14,
	      color: 'black'
	    },
        showgrid: false,
	    exponentformat: 'e',
	    showexponent: 'all',
		fixedrange: true // disable x-zooming
	  },
	  yaxis: {
	    showticklabels: true,
	    tickangle: 'auto',
	    tickmode: "last",
	    tickfont: {
	      family: 'Open Sans, Sans, Verdana',
	      size: 14,
	      color: 'black'
	    },
	    exponentformat: 'e',
	    showexponent: 'all',
	    fixedrange: true, // disable y-zooming
		//gridcolor: "rgba(64, 64, 64, 1)", // pretty black-ish
		gridcolor: "rgba(32, 32, 224, 0.7)"
	  },
	  hovermode: "closest",
	  showlegend: false,
	  annotations: new Array(),
	  images: new Array(),
          //plot_bgcolor: "transparent",
          //paper_bgcolor: "transparent",
          plot_bgcolor: 'rgb(184, 232, 255)',
          paper_bgcolor: 'rgb(184, 232, 255)',
	  margin: {
	    t: 0,
		r: 0,
	    b: 30,
		l: 30
	  },
	  separators: ", "  // german number writing, e.g. "," for decimal fractions, and " " for three-digit separation
		
	  //dragmode: "pan"
  };
  plotlyConfiguration.plotlyOptions = {
	  scrollZoom: true,
	  // see src/components/modebar/buttons.js
	  // on how to modify hover behaviour:
	  // 173 modeBarButtons.hoverClosestCartesian
	  // "zoom2d", "zoomIn2d", "zoomOut2d"
	  modeBarButtonsToRemove: [ "lasso2d", "autoScale2d", "resetScale2d", "select2d", "toggleHover", "toggleSpikelines", "hoverClosestCartesian", "hoverCompareCartesian", "toImage"],
	  displaylogo: false, // don't show the plotly logo
	  toImageButtonOptions: {
	    format: "png", // also available: jpeg, png, webp
	    // something like "openweathermap-forecast_2022-10-29.png"
	    filename: ("openweathermap-forecast_" + ((new Date()).toISOString().substring(0, 10)) + ".png"),
	    height: 500,
	    width: 800,
	    scale: 1
	  },
	  responsive: true, // automatically adjust to window resize
	  displayModeBar: true // icons always visible
	}
	//Plotly.d3.behavior.zoom.scaleBy = function(selection, k) { return k*100; };
  return plotlyConfiguration;
}

function buildDataSerieses(owmArr, plotlyConfiguration, startTime, endTime)
{
  // determine timestamp of the start of the current day
  //   TODO: not used anymore, remove it
  console.log(startTime);
  var startTimeObj = new Date(startTime);
  console.log(startTimeObj);
  var timeString = utilGetDateStr(startTimeObj);
  console.log(timeString);
  startTime = new Date(timeString);
  console.log(startTimeObj);

  var now = new Date();
  var dataSerieses = 
   [{
      x: new Array(),
      y: new Array(),
      //text: new Array(),
      mode: "lines+markers",
      line: {
        color: "#000000",
        width: 4
      },
      marker: {
        // complete list here: https://plotly.com/python/marker-style/
        //   (note a few of them can't be used, as that documentation
        //      actually refers to the Python-Plotly, not the Javascript Plotly)
        symbol: "circle",
        color: "#e0e020",
        size: 13
      }
    },
   ];
  // Some text to aid the user in understanding
  //    that the shape is to signify the current time
  // MAYOR BRAIN MOVE:  JUST PRINT THE TIME
  var nowText = "<span style=\"font-size: 90%\">"
              + ((new Date()).toString().substring(16,21))
              + "</span>  "
              + "►"
  plotlyConfiguration.plotlyLayout.annotations.push(
    {
      x: now.getTime(),
      xref: 'x',
      y: 0.4,
      yref: 'paper',
      text: nowText,
      //text: "&#0231A;",//"\u0231A",
      mode: "text",
      showarrow: false,
      //bgcolor: "rgba(255, 255, 255, 0.3)",
      font: {
        size: 32,
        //family: 'Courier New, monospace',
        color: '#ffffff'
      },
      textangle: '-90',
    });
  plotlyConfiguration.plotlyLayout["shapes"] = [{
    // add now:
      x0: now.getTime() - (1800 * 1000),
      x1: now.getTime() + (1800 * 1000),
      xref: 'x',
      y0: 0,
      y1: 1,
      yref: 'paper',
      //fill: 'tozeroy',
      //type: 'scatter',
      type: 'rect',
      mode: 'none',
      fillcolor: 'rgba(255, 240, 128, 0.6)',
      //fillcolor: 'rgba(240, 128, 128, 0.4)', // light red
      line: {
          width: 0
      }
  }];



  var minTime = startTime.getTime();
  var maxTime = endTime;
  var dayTemps = new Array();
  var allMinMax = [ undefined, undefined ];
  for(var i = 0, j=0; i < owmArr.length; ++i)
  {
    let curTime = owmArr[i].timestamp * 1000;
    let curTemp = Math.round(owmArr[i].temp * 10)/10;
    if(curTime < startTime
    || curTime > endTime)
    {
      continue;
    } else
    {
      dataSerieses[0]["x"].push(curTime);
      dataSerieses[0]["y"].push(curTemp);
      let dayOffset = Math.floor(j / 24);
      if(dayOffset == dayTemps.length)
      {
        dayTemps.push({
          startTime:   curTime,
          endTime:     curTime,
          minTemp:     curTemp,
          minTempTime: curTime,
          maxTemp:     curTemp,
          maxTempTime: curTime
        });
      } else
      {
        dayTemps[dayOffset].endTime       = curTime;
        if(dayTemps[dayOffset].minTemp > curTemp)
        {
          dayTemps[dayOffset].minTemp     = curTemp;
          dayTemps[dayOffset].minTempTime = curTime;
        }
        if(dayTemps[dayOffset].maxTemp < curTemp)
        {
          dayTemps[dayOffset].maxTemp     = curTemp;
          dayTemps[dayOffset].maxTempTime = curTime;
        }
      }
      if(undefined === allMinMax[0])
      {
        allMinMax[0] = curTemp;
        allMinMax[1] = curTemp;
      }
      // determine all time min max
      if(allMinMax[0] > curTemp) allMinMax[0] = curTemp;
      if(allMinMax[1] < curTemp) allMinMax[1] = curTemp;
      j += 1;
    }
  }

  var deltaMinMax = allMinMax[1] - allMinMax[0];

  //   potential TODO: draw two horizontal lines into the graph, annotating them with the overall min and max temperatures

  // Generate Annotations for
  //   minimum and maximum temperature on each day
  for(var i = 0; i < dayTemps.length; ++i)
  {
    for(var curPos of [{x: dayTemps[i].minTempTime,
	                y: dayTemps[i].minTemp,
			prefix: "",
			type: "min",
                        color: "#0000d0"
	 	       },{
		        x: dayTemps[i].maxTempTime,
			y: dayTemps[i].maxTemp,
			prefix: "",
			type: "max",
                        color: "#f00000"
		       }])
    {
      // documentation:
      // https://plotly.com/javascript/text-and-annotations/#styling-and-coloring-annotations
      let curAnnotation = {
        xref:  "x",
        yref:  "y",
        x:     curPos["x"],
        y:     curPos["y"],
        ax: 0,
        ay: 20,  // the annotation's offset, apparently in pixels
        text: (curPos["prefix"] + curPos["y"] + " ℃"), // U+2103
        font: {
          size: 18,
          color: curPos.color
        },
        bgcolor: "rgba(255, 255, 255, 0.5)",
        arrowcolor: "#b0b0b0",
        opacity: 0.6,
      };
      if("min" == curPos.type) curAnnotation.ay *= -1;
      if(curAnnotation.x < (minTime + 3600 * 1 * 1000))
      {
        curAnnotation.x += 3600 * 1 * 1000;
        curAnnotation.ay *= 1.5;
      } else if(curAnnotation.x > (maxTime - 3600 * 1 * 1000))
      {
        curAnnotation.x -= 3600 * 1 * 1000;
        curAnnotation.ay *= 1.5;
      }
      plotlyConfiguration.plotlyLayout.annotations.push(curAnnotation);
     }
  }

  // TODO: think about some merging algorithm for the clouds of each 4 hour period
  for(var i = 0; i < owmArr.length; i+=2)
  {
    let curImage = {
          source:  "img/weather/",
	  xref:    "x",  // sets the coordinate system
	  yref:    "paper",  
          //TODO:
	  x:       owmArr[i].timestamp * 1000,//startTime.getTime() + (1000 * 3600 * i),
	  y:       0.25,  // hardcoded y-offset; with 'yref' being paper, 0.5 would refer to half of full height (full height = 1)
	  xanchor: "center",
	  yanchor: "middle",
	  //x: i, y: 20, xref: "paper", yref: "paper",
	  sizex:  (1000 * 3600 * 2.1),
//     0.5 = half the graph's height (since 'yref' above refers to 'paper'),
// plot: https://www.wolframalpha.com/input?i=f%28x%29%3D0.9%2F%28x%2F600%29%3B+x+from+500+to+1100
          sizey:  (0.9 / (window.innerHeight / 600)),
	  sizing:  "stretch",
	  opacity: 0.5,
	  layer:   "below"
	};
        // if current line drawn (i.e. temperature) is below average,
        //   then switch position of the image 'below', to be drawn at the top of the graph
        if(owmArr[i].temp < (allMinMax[0] + deltaMinMax / 2))
        {
          curImage.y = 0.75;
        }
/*  all the weather icons we got
img/weather/achtung.small.png
img/weather/blitz.small.png
img/weather/regentropfen_2.small.png
img/weather/regentropfen.small.png
img/weather/schneeflocken.small.png
img/weather/schneeflocke.small.png
img/weather/sonne.small.png
img/weather/typ_eiswaffel.small.png
img/weather/typ_gross.small.png
img/weather/typ_kaffee.small.png
img/weather/wolke_ansammlung.small.png
img/weather/wolke_klein_2.small.png
img/weather/wolke_klein.small.png
img/weather/wolke_regen_2.small.png
img/weather/wolke_regen_3.small.png
img/weather/wolke_regen.small.png
img/weather/wolke_schnee.small.png
img/weather/wolke_sonne_2.small.png
img/weather/wolke_sonne.small.png
img/weather/wolke_wind_2.small.png
img/weather/wolke_wind.small.png
*/
    // see this reference: https://openweathermap.org/weather-conditions
    switch(("" + owmArr[i].weather_icon).substring(0,2))
    {
      case '01':   // "clear sky"
        curImage.source += "sonne.small.png";
        break;
      case '02':   // "few clouds"
        curImage.source += "typ_eiswaffel.small.png";
        break;
      case '03':   // "scattered clouds"
        curImage.source += "wolke_sonne_2.small.png";
        break;
      case '04':   // "broken clouds"
        curImage.source += "wolke_sonne.small.png";
        break;
      case '09':   // "shower rain"
        curImage.source += "wolke_regen_2.small.png";
        break;
      case '10':   // "rain"
        curImage.source += "wolke_regen.small.png";
        break;
      case '11':   // "thunderstorm"
        curImage.source += "blitz.small.png";
        break;
      case '13':   // "snow"
        curImage.source += "schneeflocken.small.png";
        break;
      case '50':   // "mist"
        curImage.source += "typ_kaffee.small.png";
        break;
     //+ (Math.random() < 0.5 ? "sonne.small.png" : "wolke_regen_2.small.png") ),
    }
    plotlyConfiguration.plotlyLayout.images.push(curImage);
	
  }
  
  //  set the graph's y axis to only contain min to max temperature, not more
  if (deltaMinMax < Math.pow(10, -300)) deltaMinMax = Math.pow(10, -300);
  plotlyConfiguration.plotlyLayout.yaxis.range = [
    allMinMax[0] - (deltaMinMax * 0.1),
    allMinMax[1] + (deltaMinMax * 0.1)
  ];
  plotlyConfiguration.plotlyLayout.xaxis.range = [
    minTime - (1800 * 1000),
    maxTime + (1800 * 1000)
  ];

// setting now...
//  dataSerieses[1]["y"][0] = allMinMax[1];
//  dataSerieses[1]["y"][1] = allMinMax[1];
  return dataSerieses;
}

document.addEventListener("DOMContentLoaded", init);
