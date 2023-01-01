
var Departures = {
  BODY: undefined,
  vvoList: undefined,
  departureRows: new Array(),
  MAX_DEPARTURES_TO_SHOW: 8,
  //offsetForTime: -3600000,
  offsetForTime: 0,
  init: function()
  {
    Departures.BODY = document.getElementsByTagName("body")[0];
    Departures.vvoList = document.getElementsByClassName("wrapper_departures")[0];
    Departures.parseDepartureRows();
    Departures.updateDepartures();
    setInterval(Departures.updateDepartures, 60000);
  },
  parseDepartureRows: function()
  {
    for(var i = 0, curRow, curCell, curBlob; i < Departures.vvoList.childNodes.length; ++i)
    {
      curRow = Departures.vvoList.childNodes[i];
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
              curBlob.departure = new Date(parseInt(curCell.getAttribute("timestamp")) + Departures.offsetForTime);
            }
	  }
        }
        Departures.departureRows.push(curBlob);
        if( i >= Departures.MAX_DEPARTURES_TO_SHOW)
        {
          curBlob.setVisibility(curBlob, false);
        }
      }
    }
  },
  updateDepartures: function()
  {
    var killTime = (new Date()).getTime() + 120000;
    for( var i = 0, curRow; i < Departures.departureRows.length; ++i)
    {
      curRow = Departures.departureRows[i];
      if(curRow.departure.getTime() < killTime)
      {
        Departures.vvoList.removeChild(curRow.element);
        Departures.departureRows.splice(i, 1);
        --i;
        curRow.element = undefined;
      } else if(curRow.element)
      {
        curRow.setVisibility(curRow, i < Departures.MAX_DEPARTURES_TO_SHOW);

        var departureEle = curRow.element.querySelector(".departure_cell-departure");
        if(departureEle)
        {
          departureEle.removeChild(departureEle.firstChild);
		//TODO: put this text generation in the DepartureBlob class/function
		//      because the class itself should know about how to
		//      represent it's own state/departure time
          departureEle.appendChild(document.createTextNode(Math.floor((curRow.departure.getTime() - (new Date()).getTime()) / 60000) + " Min"));
        } else console.log("couldnt find departure element");
      }
    }
  },
  getHtmlMatches: function(sourceHtml, precedingText)
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
  },
  htmlDecode: function(input) // Courtesy of Wladimir Palant https://stackoverflow.com/a/34064434
  {
    var doc = new DOMParser().parseFromString(input, "text/html");
    return doc.documentElement.textContent;
  },
  appendDetail: function(rowEle, stopsArr)
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
      curListItem.appendChild(document.createTextNode(Departures.htmlDecode(stopsArr[i]) + " "));
      stopsList.appendChild(curListItem);
    }
    detailBox.appendChild(stopsList);
    rowEle.style.height = "auto";
    rowEle.appendChild(detailBox);
  },
//TODO: this needs to be brought up to date
//        I suppose it doesn't work anymore presently
  loadDetails: function(eventSourceEle, detailUrl)
  {
    var req = new XMLHttpRequest();
    req.open("GET", "vvo_detail.php?url=" + encodeURIComponent(detailUrl));
    req.onload = function()
    {
        Departures.req = req; //DEBUG

        var matches = Departures.getHtmlMatches(req.responseText, " tour\">\r\n                <p><strong>");

        Departures.appendDetail(eventSourceEle, matches);
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
function DepartureBlob()
{
  this.element = undefined;
  this.id = 0;
  this.type = "";
  this.destination = "";
  this.departure = 0;
  this.parseDeparture = function(selfReference, rawStr)
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
  this.setVisibility = function(selfReference, shouldBeVisible)
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
}


document.addEventListener("DOMContentLoaded", Global.init);
document.addEventListener("DOMContentLoaded", Departures.init);
