//Now for the fun overriding part. 
//We'll override the standard xajax.call() function to show our little spinner and then carry on as usual.

//keep around the old call function
xajax.realCall = xajax.call;

//override the call function to bend to our wicked ways
xajax.call = function(sFunction, aArgs, sRequestType)
{
    //show the spinner
	this.spinner_id = (aArgs) ? aArgs[aArgs.length-1] : 'thereisnotspinner1234567';
	
 	if(document.getElementById(this.spinner_id)) {
	    this.$(this.spinner_id).style.display = 'inline';
	}

    //call the old call function
    return this.realCall(sFunction, aArgs, sRequestType);
}


//So now we've overridden the xajax.call() function so that it shows our spinner when 
//we make an xajax call, we just have to override the xajax.processResponse() function to hide the spinner.

//save the old processResponse function for later
xajax.realProcessResponse = xajax.processResponse;

//override the processResponse function
xajax.processResponse = function(xml)
{
    //hide the spinner
 	if(document.getElementById(this.spinner_id)) {
	    this.$(this.spinner_id).style.display = 'none';	
	}
    
	//call the real processResponse function
    return this.realProcessResponse(xml);
}