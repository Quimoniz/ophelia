
class DeparturesDisplayHandler {
  static initialize()
  {
      let allDepartureListings = document.getElementsByClassName("wrapper_departures");
      if(0 == allDepartureListings)
      {
          console.error("Did not find any class='wrapper_departures' elements to initialize the javascript for it ")
      } else {
          for(var curDepartureWrapper of allDepartureListings)
          {
              new DeparturesDisplayHandler(curDepartureWrapper)
          }
      }
  }
  constructor(parentEle)
  {
console.log(this);
    this.BODY = document.getElementsByTagName("body")[0];
    this.vvoList = parentEle;
    this.departureRows = new Array();
    this.MAX_DEPARTURES_TO_SHOW = 8;
  //offsetForTime: -3600000,
    this.offsetForTime = 0;
    this.parseDepartureRows();
    this.updateDepartures();
    var selfReference = this;
    setInterval(() => { selfReference.updateDepartures() }, 60000);
  }
  parseDepartureRows()
  {
    for(var i = 0, curRow, curCell, curBlob; i < this.vvoList.childNodes.length; ++i)
    {
      curRow = this.vvoList.childNodes[i];
      if(curRow && curRow.nodeType && 1 == curRow.nodeType
      && -1 < curRow.className.indexOf("departure_row"))
      {
        curBlob = new DepartureBlob();
        curBlob.element = curRow;
	for(var j = 0; j < curRow.childNodes.length; ++j)
        {
          curCell = curRow.childNodes[j];
          if(curCell && 1 == curCell.nodeType)
	  {
            if(0 == curCell.className.indexOf("departure_cell-id"))
            {
              curBlob.id = parseInt(curCell.firstChild.nodeValue);
            } else if(0 == curCell.className.indexOf("departure_cell-type"))
            {
              if(1 < curCell.childNodes.length && 1 == curCell.childNodes[1].nodeType)
              {
                curBlob.type = curCell.childNodes[1].getAttribute("alt");
              }
            } else if(0 == curCell.className.indexOf("departure_cell-destination"))
            {
              curBlob.destination = Util.stripWhitespace(curCell.firstChild.nodeValue);
            } else if(0 == curCell.className.indexOf("departure_cell-departure"))
            {
              //curBlob.parseDeparture(curBlob, Util.stripWhitespace(curCell.firstChild.nodeValue));
              curBlob.departure = new Date(parseInt(curCell.getAttribute("timestamp")) + this.offsetForTime);
            }
	  }
        }
        this.departureRows.push(curBlob);
        if( i >= this.MAX_DEPARTURES_TO_SHOW)
        {
          curBlob.setVisibility(curBlob, false);
        }
      }
    }
  }
  updateDepartures()
  {
    var killTime = (new Date()).getTime() + 120000;
    for( var i = 0, curRow; i < this.departureRows.length; ++i)
    {
      curRow = this.departureRows[i];
      if(curRow.departure.getTime() < killTime)
      {
        this.vvoList.removeChild(curRow.element);
        this.departureRows.splice(i, 1);
        --i;
        curRow.element = undefined;
      } else if(curRow.element)
      {
        curRow.setVisibility(curRow, i < this.MAX_DEPARTURES_TO_SHOW);

        var departureEle = curRow.element.querySelector(".departure_cell-departure");
        if(departureEle)
        {
          departureEle.removeChild(departureEle.firstChild);
		//TODO: put this text generation in the DepartureBlob class/function
		//      because the class itself should know about how to
		//      represent it's own state/departure time
          departureEle.appendChild(document.createTextNode(`${curRow.getDepartureMinutes()} Min (${curRow.getDepartureHHMM()})`))
          curRow.blinkDepartureTime();
        } else console.log("couldnt find departure element");
      }
    }
  }
  getHtmlMatches(sourceHtml, precedingText)
  {
    var curOffset = 0;
    var searchIndex = undefined;
    var endlineIndex = undefined;
    var curLine = "";
    var resultArr = new Array();
    while(-1 < (searchIndex = sourceHtml.indexOf(precedingText, curOffset)))
    {
      curOffset = searchIndex + precedingText.length;
      endlineIndex = sourceHtml.indexOf("\n", curOffset);
      if(-1 == endlineIndex)
      {
        endlineIndex = sourceHtml.length;
      }
      curLine = sourceHtml.substring(curOffset, endlineIndex);
      curLine = curLine.replace(/<[^>]+>/g, "");
      resultArr.push(curLine);
      curOffset = endlineIndex;
    }
    return resultArr;
  }
  htmlDecode(input) // Courtesy of Wladimir Palant https://stackoverflow.com/a/34064434
  {
    var doc = new DOMParser().parseFromString(input, "text/html");
    return doc.documentElement.textContent;
  }
  appendDetail(rowEle, stopsArr)
  {
    var detailBox = undefined;
    detailBox = rowEle.querySelector(".departure_details");
    if(detailBox)
    {
      detailBox.parentNode.removeChild(detailBox);
    }
    detailBox = document.createElement("div");
    var stopsList = document.createElement("ul");
    var curListItem = undefined;
    for(var i = 0; i < stopsArr.length; ++i)
    {
      curListItem = document.createElement("li");
      curListItem.appendChild(document.createTextNode(this.htmlDecode(stopsArr[i]) + " "));
      stopsList.appendChild(curListItem);
    }
    detailBox.appendChild(stopsList);
    rowEle.style.height = "auto";
    rowEle.appendChild(detailBox);
  }
//TODO: this needs to be brought up to date
//        I suppose it doesn't work anymore presently
  loadDetails(eventSourceEle, detailUrl)
  {
    var req = new XMLHttpRequest();
    req.open("GET", "vvo_detail.php?url=" + encodeURIComponent(detailUrl));
    req.onload = function()
    {
        this.req = req; //DEBUG

        var matches = this.getHtmlMatches(req.responseText, " tour\">\r\n                <p><strong>");

        this.appendDetail(eventSourceEle, matches);
    }
    req.send();
/*
<div class="col c4of12 tour">

                <p><strong>


    var divDetail = document.createElement("div");
    divDetail.appendChild(document.createTextNode(detailUrl));
    eventSourceEle.style.height = "auto";
    eventSourceEle.appendChild(divDetail);
*/
  }
};
var Global = {
  BODY: undefined,
  fullscreenActivated: false,
  tabs: undefined,
  init: function()
  {
    Global.BODY = document.getElementsByTagName("body")[0];
    var fullscreenEle = document.querySelector(".fullscreen_image");
    fullscreenEle.addEventListener("click", Global.fullscreenToggle);
    Global.initializeTabs();
  },
  fullscreenToggle: function()
  {
    if(Global.fullscreenActivated)
    {
      if(document.exitFullscreen)
      {
        document.exitFullscreen();
      } else if (document.mozCancelFullScreen)
      {
        document.mozCancelFullScreen();
      } else if (document.webkitExitFullscreen)
      {
        document.webkitExitFullscreen();
      }
      Global.fullscreenActivated = false;
    } else
    {
      if(Global.BODY.requestFullscreen)
      {
        Global.BODY.requestFullscreen();
      } else if(Global.BODY.mozRequestFullScreen)
      {
        Global.BODY.mozRequestFullScreen();
      } else if(Global.BODY.webkitRequestFullScreen)
      {
        Global.BODY.webkitRequestFullScreen();
      }
      Global.fullscreenActivated = true;
    }
  },
  initializeTabs: function()
  {
    var newsEles = document.querySelectorAll(".news_wrapper");
    Global.tabs = new Tabbing(Global.BODY, ["calc(100% - 4px)", "15em"]);
    for(var i = 0; i < newsEles.length; ++i)
    {
      Global.tabs.addTab(newsEles[i].getAttribute("title"), newsEles[i]);
    }
    //var vocabEle = document.querySelector(".vocab_wrapper");
    //Global.tabs.addTab(vocabEle.getAttribute("title"), vocabEle);
  }
};
var Util = {
  loadTime: undefined,
  init: function()
  {
    Util.loadTime = new Date();
  },
  stripWhitespace: function(origStr)
  {
    var filteredStr = origStr.replace(/^[ \t\n]*/, "").replace(/[ \t\n]*$/, "");
    return filteredStr;
  },
  hhMmToInt: function(timeStr)
  {
    var splitted = (timeStr + "").split(":");
    if(1 < splitted.length)
    {
      return parseInt(splitted[0]) * 100 + parseInt(splitted[1]);
    } else
    {
      return 0;
    }
  }
};
Util.init();
class DepartureBlob
{
  constructor() {
    this.element = undefined;
    this.id = 0;
    this.type = "";
    this.destination = "";
    this.departure = 0;
  }
  parseDeparture(selfReference, rawStr)
  {
    var parseMinutesDecimal = Util.hhMmToInt(rawStr.substring(1));
    var loadHour = Util.loadTime.getHours(),
        loadMinutes = Util.loadTime.getMinutes();
    var loadMinutesDecimal = loadHour * 100 + loadMinutes;
    var diff = parseMinutesDecimal - loadMinutesDecimal;
    var nextDay = -1200 > diff
    var parsedDate = new Date(Util.loadTime.getTime() + (nextDay ? 86400000: 0));
    parsedDate.setHours(Math.floor(parseMinutesDecimal / 100));
    parsedDate.setMinutes(parseMinutesDecimal % 100);
    parsedDate.setSeconds(0);
    parsedDate.setMilliseconds(0);
    selfReference.departure = parsedDate;
  }
  setVisibility(selfReference, shouldBeVisible)
  {
    if(selfReference.element)
    {
      if(shouldBeVisible)
      {
	selfReference.element.style.display = "block";
      } else
      {
	selfReference.element.style.display = "none";
      }
    }
  }
  getDepartureMinutes()
  {
    const nowTime = (new Date()).getTime();
    return Math.floor((this.departure.getTime() - nowTime) / 60000);
  }
  getDepartureHHMM()
  {
    var outStr = "";
    if(  this.departure.getHours() < 10) outStr +="0";
    outStr += this.departure.getHours()
    outStr += ":";
    if(this.departure.getMinutes() < 10) outStr +="0";
    outStr += this.departure.getMinutes()

    return outStr;
  }
  blinkDepartureTime()
  {
    const departureEle = this.element.querySelector(".departure_cell-departure");
    departureEle.animate(
     [ {
         "backgroundColor": "#ffffff"
       }, {
         "backgroundColor": "#7090ff"
       }, {
         "backgroundColor": "#ffffff"
       }
     ], 1000);
  }
}

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


document.addEventListener("DOMContentLoaded", Global.init);
document.addEventListener("DOMContentLoaded", DeparturesDisplayHandler.initialize);
