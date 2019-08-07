// code from https://github.com/metricq/metricq-webview-prototype/
// license GPL
// copyright: TU Dresden
// author: Lars "Quimoniz" Jitschin

function Tabbing(paramParentElement, paramTabSize)
{
  this.parentElement = paramParentElement;
  this.tabSize = paramTabSize;
  this.headArea = undefined;
  this.bodyArea = undefined;
  this.moreTabsEle = undefined;
  this.tabs = new Array();
  this.visibleTabHeadings = new Array();
  this.focusedTabIndex = 0;
  this.visibleTabsCount = 0;
  this.init = function()
  {
    this.headArea = document.createElement("div");
    this.headArea.setAttribute("class", "tabbing_header");
    this.headArea.style.fontSize = "14pt";
    this.headArea.style.height = "40px";
    this.headArea.style.width = this.tabSize[0];
    this.headArea.style.padding = "10px 0px 0px 0px";
    this.bodyArea = document.createElement("div");
    this.bodyArea.setAttribute("class", "tabbing_body");
    this.bodyArea.style.width = this.tabSize[0];
    this.bodyArea.style.height = this.tabSize[1];
    this.headArea = this.parentElement.appendChild(this.headArea);
    this.bodyArea = this.parentElement.appendChild(this.bodyArea);
  };
  this.addTab = function(tabName, predefinedTabBody)
  {
    var newTabDescription = new TabDescription(this.bodyArea, tabName);
    if(((this.tabs.length + 1) * 270) < (this.headArea.offsetWidth - 50))
    {
      this.visibleTabsCount++;
      this.visibleTabHeadings.push(new TabHeading(this, tabName));
      if(0 == this.tabs.length)
      {
        newTabDescription.focus();
      }
    } else
    {
      if(!this.moreTabsEle)
      {
        this.moreTabsEle = document.createElement("div");
        this.moreTabsEle.style.float = "right";
        this.moreTabsEle.style.margin = "0px 10px 0px 0px";
        this.moreTabsEle.style.padding = "5px";
        this.moreTabsEle.addEventListener("mouseover", function(evtObj) { evtObj.target.style.backgroundColor = "#efefef"; });
        this.moreTabsEle.addEventListener("mouseout", function(evtObj) { evtObj.target.style.backgroundColor = "#a0a0a0"; });
        this.moreTabsEle.addEventListener("click", function(tabbingObj) { return function(evtObj) { tabbingObj.showMoreTabs(evtObj); }; }(this));
        this.moreTabsEle.appendChild(document.createTextNode("▼"));
        this.headArea.appendChild(this.moreTabsEle);
      }
    }
    this.tabs.push(newTabDescription);
    if(predefinedTabBody)
    {
      if(predefinedTabBody.parentNode)
      {
        predefinedTabBody.parentNode.removeChild(predefinedTabBody);
      }
      newTabDescription.mainEle.appendChild(predefinedTabBody);
    }
    return newTabDescription.mainEle;
  };
  this.showMoreTabs = function(evtObj)
  {
    var position = this.getActualOffset(this.moreTabsEle);
    position[0] -= 110;
    position[1] -= 10;
    if((position[0] + 220) > window.innerWidth)
    {
      position[0] = window.innerWidth - 220;
    }
    var tabChooserEle = document.createElement("ul");
    tabChooserEle.style.backgroundColor = "#FFFFFF";
    tabChooserEle.style.position = "absolute";
    tabChooserEle.style.listStyle = "none";
    tabChooserEle.style.left = position[0] + "px";
    tabChooserEle.style.top = position[1] + "px";
    tabChooserEle.style.width = "200px";
    tabChooserEle.style.zIndex = 500;
    tabChooserEle.style.padding = "0px";
    tabChooserEle.setAttribute("class", "tab_chooser_overlay");
    for(var i = 0; i < this.tabs.length; ++i)
    {
      var curLi = document.createElement("li");
      curLi.appendChild(document.createTextNode(this.tabs[i].name));
      curLi.style.margin = "8px";
      curLi.style.border = "2px solid #FFFFFF";
      curLi.addEventListener("mouseover", function(evtObj) {evtObj.target.style.border = "2px solid #707070"; });
      curLi.addEventListener("mouseout", function(evtObj) {evtObj.target.style.border = "2px solid #FFFFFF"; });
      curLi.addEventListener("click", function(tabbingObj, curName) { return function(evtObj) {
        var damnVeil = document.querySelector(".veil");
        damnVeil.parentNode.removeChild(damnVeil);
        evtObj.target.parentNode.parentNode.removeChild(evtObj.target.parentNode);
        tabbingObj.focusTab(curName);
      }; }(this, this.tabs[i].name));
      tabChooserEle.appendChild(curLi);
    }
    var veilEle = document.createElement("div");
    veilEle.style.opacity = "0.3";
    veilEle.style.backgroundColor = "#000000";
    veilEle.style.position = "fixed";
    veilEle.style.left = "0px";
    veilEle.style.top = "0px";
    veilEle.style.width = window.innerWidth + "px";
    veilEle.style.height = window.innerHeight + "px";
    veilEle.style.zIndex = 100;
    veilEle.setAttribute("class", "veil");
    veilEle.appendChild(document.createTextNode(" "));
    veilEle.addEventListener("click", function() {
      var curEle = document.querySelector(".veil");
      curEle.parentNode.removeChild(curEle);
      curEle = document.querySelector(".tab_chooser_overlay");
      curEle.parentNode.removeChild(curEle);
    });
    
    document.getElementsByTagName("body")[0].appendChild(veilEle);
    document.getElementsByTagName("body")[0].appendChild(tabChooserEle);
  };
  this.getActualOffset = function(ele)
  {
    var x = 0;
    var y = 0;
    x += ele.offsetLeft;
    y += ele.offsetTop;
    for(var curEle = ele; curEle.parentNode; curEle = curEle.parentNode)
    {
      if(curEle["tagName"] && "BODY" == curEle.tagName)
      {
        x += curEle.scrollLeft;
        y += curEle.scrollTop;
	break;
      } else
      {
        x -= curEle.scrollLeft;
        y -= curEle.scrollTop;
      }
    }
    
    return [x, y];
  };
  this.focusTab = function (tabName)
  {
    var oldMainTab = this.getTabDescription(this.visibleTabHeadings[0].title);
    var i = this.visibleTabsCount - 1;
    for(var j = 0; j < this.visibleTabsCount; ++j)
    {
      if(tabName == this.visibleTabHeadings[j].title)
      {
        i = j;
      }
    }
    for(; i > 0; --i)
    {
      this.visibleTabHeadings[i].setTitle(this.visibleTabHeadings[i - 1].title);
    }
    this.visibleTabHeadings[0].setTitle(tabName);
    oldMainTab.unfocus();
    this.getTabDescription(tabName).focus();
  };
  this.getTabDescription = function(searchName)
  {
    for(var i = 0; this.tabs.length; ++i)
    {
      if(searchName == this.tabs[i].name)
      {
        return this.tabs[i];
      }
    }
    return undefined;
  }

  this.init();
}
function TabDescription(parentElement, tabName)
{
  this.name = tabName;
  this.mainEle = document.createElement("div");
  this.mainEle.style.display = "none";
  this.mainEle.style.width = "100%";
  this.mainEle.style.height = "100%";
  parentElement.appendChild(this.mainEle);
  this.unfocus = function()
  {
    this.mainEle.style.display = "none";
  };
  this.focus = function()
  {
    this.mainEle.style.display = "block";
  };
}
function TabHeading(paramTabbingObj, tabName)
{
  this.title = tabName;
  this.headingEle = document.createElement("div");
  this.headingEle.style.display = "inline-block";
  this.headingEle.style.fontSize = "16pt";
  this.headingEle.style.margin = "0px 10px 0px 5px";
  this.headingEle.style.padding = "5px 0px 0px 5px";
  this.headingEle.style.width = "250px";
  this.headingEle.style.height = "33px";
  this.headingEle.style.cursor = "pointer";
  this.headingEle.style.borderLeft = "2px solid #606060";
  this.headingEle.style.borderTop  = "2px solid #606060";
  this.headingEle.style.borderRight= "2px solid #606060";
  this.headingEle.style.borderTopLeftRadius = "10px";
  this.headingEle.style.borderTopRightRadius = "10px";
  this.headingEle.setAttribute("class", "tab_head tab_head_unfocused")
  this.eventHandler = undefined;

  this.focus = function()
  {
    this.headingEle.setAttribute("class", "tab_head tab_head_focused")
  }
  this.defocus = function()
  {
    this.headingEle.setAttribute("class", "tab_head tab_head_unfocused")
  }
  this.setTitle = function(newTitle)
  {
    this.title = newTitle;
    if(this.headingEle.firstChild)
    {
      this.headingEle.removeChild(this.headingEle.firstChild);
    }
    if(20 < newTitle.length)
    {
      this.headingEle.appendChild(document.createTextNode(newTitle.substring(0, 20)));
    } else
    {
      this.headingEle.appendChild(document.createTextNode(newTitle));
    }
    this.headingEle.setAttribute("title", newTitle);
    if(this.eventHandler)
    {
      this.headingEle.removeEventListener("click", this.eventHandler);
    }
    this.eventHandler = function(tabbingObj, tabName) { return function() { tabbingObj.focusTab(tabName); }; }(paramTabbingObj, newTitle);
    this.headingEle.addEventListener("click", this.eventHandler);
  }

  this.setTitle(tabName); 
  if(0 == paramTabbingObj.tabs.length)
  {
    this.focus();
  }
  paramTabbingObj.headArea.appendChild(this.headingEle);
}
