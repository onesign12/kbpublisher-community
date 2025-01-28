//var NS4 = (navigator.appName == "Netscape" && parseInt(navigator.appVersion) < 5);

function addOption(theSel, theText, theValue) {
	var newOpt = new Option(theText, theValue);
	var selLength = theSel.length;
	theSel.options[selLength] = newOpt;
}

function deleteOption(theSel, theIndex) { 
	var selLength = theSel.length;
	if(selLength>0) {
		theSel.options[theIndex] = null;
	}
}

function disableOption(theSel, theIndex) { 
	var selLength = theSel.length;
	if(selLength>0) {
		theSel.options[theIndex].disabled = true;
	}
}


function moveOptions(theSelFrom, theSelTo, theSelFromBehaviour) {
  
	var selLength = theSelFrom.length;
	var selectedText = new Array();
	var selectedValues = new Array();
	var selectedCount = 0;
	var i;
	
	// Find the selected Options in reverse order
	// and delete them from the 'from' Select.
	for(i=selLength-1; i>=0; i--) {
		if(theSelFrom.options[i].selected) {
			selectedText[selectedCount] = theSelFrom.options[i].text;
			selectedValues[selectedCount] = theSelFrom.options[i].value;
			
			if(theSelFromBehaviour == 'move' || theSelFromBehaviour == 'delete') {
				deleteOption(theSelFrom, i);
			} //else if(theSelFromBehaviour == 'disable') {
				//disableOption(theSelFrom, i);
			//}
			
			selectedCount++;
		}
	}
	
	// Add the selected text/values in reverse order.
	// This will add the Options to the 'to' Select
	// in the same order as they were in the 'from' Select.
	if(theSelFromBehaviour == 'move' || theSelFromBehaviour == 'disable') {
		for(i=selectedCount-1; i>=0; i--) {
			addOption(theSelTo, selectedText[i], selectedValues[i]);
		}	
	}

	
	//if(NS4) history.go(0);
}


function moveUp(sList) {
  
	var sText = new Array();
	var sValues = new Array();
	var sSelected = new Array();
	var j = 0;
	
	for (var i=0; i < sList.options.length; i++) {
		if(sList.options[i].selected) {
			sText[j] = sList.options[i].text;
			sValues[j] = sList.options[i].value;
			sSelected[j] = true;
			j++;
		}
	}
	
	for (var i=0; i < sList.options.length; i++) {
		if(!sList.options[i].selected) {
			sText[j] = sList.options[i].text;
			sValues[j] = sList.options[i].value;
			sSelected[j] = false;
			j++;
		}
	}	
	
	sList.options.length = 0;
	for(var i=0; i < sText.length; i++) {
		addOption(sList, sText[i], sValues[i]);
		
		if(sSelected[i]) {
			sList.options[i].selected = true;
		}
	}
}