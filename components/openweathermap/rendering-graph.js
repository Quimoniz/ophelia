var myPlot = undefined;
var plotlyConfiguration = new Object();
function init()
{
  //var plotEle = document.getElementById("weather_plot");
  var plotEle = document.querySelector(".weather_box");
  removeAllChilds(plotEle);
  configurePlotly(plotEle);
  plotlyConfiguration.plotlyData = buildDataSerieses(window.jsonWeather);

  myPlot = Plotly.newPlot(plotEle,
    plotlyConfiguration.plotlyData,
	plotlyConfiguration.plotlyLayout,
	plotlyConfiguration.plotlyConfiguration
  );
}
function configurePlotly(plotEle)
{
  // Thanks to past-me
  // https://github.com/metricq/metricq-webview/blob/75d073de93353ad1e1d040df670829583c1ba6f5/js/MetricQWebView.js#L39
  // see the source file
  //   src/plots/cartesian/layout_attributes.js
  // for a complete set of available options
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
}

function buildDataSerieses(owmArr)
{
  // determine timestamp of the start of the current day
  //   TODO: not used anymore, remove it
  var startTime = new Date();
  console.log(startTime);
  var timeString = startTime.getFullYear() + "-" +
                   ((startTime.getMonth() + 1) < 10 ? ("0" + (startTime.getMonth() + 1)) : (startTime.getMonth() + 1)) + "-" +
				   (startTime.getDate()  < 10 ? ("0" + startTime.getDate() ) : startTime.getDate());
  console.log(timeString);
  startTime = new Date(timeString);
  console.log(startTime);

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
    // add now:
    {
      x: [
        now.getTime() - (1800 * 1000),
        now.getTime() + (1800 * 1000)
      ],
      y: [
        20,
        20
      ],
      fill: 'tozeroy',
      type: 'scatter',
      mode: 'none',
      fillcolor: 'rgba(240, 128, 128, 0.4)'
    }
   ];


  var minTime = owmArr[0].timestamp * 1000;
  var maxTime = owmArr[owmArr.length - 1].timestamp * 1000;
  var minTemps = new Array( undefined, undefined);
  var maxTemps = new Array( undefined, undefined);
  for(var i = 0; i < owmArr.length; ++i)
  {
    let curTime = owmArr[i].timestamp * 1000;
    let curTemp = Math.round(owmArr[i].temp * 10)/10;
    dataSerieses[0]["x"].push(curTime);
    dataSerieses[0]["y"].push(curTemp);
    let dayOffset = Math.floor(i / 24);
    if(undefined === minTemps[dayOffset] || minTemps[dayOffset]["y"] > curTemp)
    {
      minTemps[dayOffset] = {
        "x": curTime,
    	"y": curTemp
      };
    }
    if(undefined === maxTemps[dayOffset] || maxTemps[dayOffset]["y"] < curTemp)
    {
      maxTemps[dayOffset] = {
        "x": curTime,
    	"y": curTemp
      };
    }
  }

  // determine all time min max
  var allMinMax = [minTemps[0]["y"], maxTemps[0]["y"]];
  if(minTemps[1]["y"] < allMinMax[0]) allMinMax[0] = minTemps[1]["y"];
  if(maxTemps[1]["y"] > allMinMax[1]) allMinMax[1] = maxTemps[1]["y"];
  var deltaMinMax = allMinMax[1] - allMinMax[0];

  //   potential TODO: draw two horizontal lines into the graph, annotating them with the overall min and max temperatures

  // Generate Annotations for
  //   minimum and maximum temperature on each day
  for(var i = 0; i < 2; ++i)
  {
    for(var curPos of [{x: minTemps[i]["x"],
	                y: minTemps[i]["y"],
			prefix: "Minimal",
                        color: "#0000d0"
	 	       },{
		        x: maxTemps[i]["x"],
			y: maxTemps[i]["y"],
			prefix: "Maximal",
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
        ay: 40,  // the annotation's offset, apparently in pixels
        text: (curPos["prefix"] + " Temperatur " + curPos["y"] + " ℃"), // U+2103
        font: {
          size: 14,
          color: curPos.color
        },
        bgcolor: "rgba(255, 255, 255, 0.6)",
        arrowcolor: "#b0b0b0"
      };
      if("Minimal" == curPos.prefix) curAnnotation.ay = -40;
      if(curAnnotation.x < (minTime + 3600 * 2 * 1000))
      {
        curAnnotation.x += 3600 * 2 * 1000;
        curAnnotation.ay *= 1.5;
      }
      plotlyConfiguration.plotlyLayout.annotations.push(curAnnotation);
     }
  }

  // TODO: think about some mergin algorithm for the clouds of each 4 hour period
  for(var i = 0; i < owmArr.length; i+=4)
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
	  sizex:  (1000 * 3600 * 3.5),
	  sizey:  0.5, //20,
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
        curImage.source += "wolke_regen.small.png";
        break;
      case '10':   // "rain"
        curImage.source += "wolke_regen_2.small.png";
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

  dataSerieses[1]["y"][0] = allMinMax[1];
  dataSerieses[1]["y"][1] = allMinMax[1];
  return dataSerieses;
}

document.addEventListener("DOMContentLoaded", init);
