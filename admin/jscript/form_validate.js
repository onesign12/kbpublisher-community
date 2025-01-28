//-------------------------------------------------------------------
// Trim functions
//   Returns string with whitespace trimmed
//-------------------------------------------------------------------
/*
function LTrim(str){
	if (str==null){return null;}
	for(var i=0;str.charAt(i)==" ";i++);
	return str.substring(i,str.length);
	}
function RTrim(str){
	if (str==null){return null;}
	for(var i=str.length-1;str.charAt(i)==" ";i--);
	return str.substring(0,i+1);
	}
function Trim(str){return LTrim(RTrim(str));}
function LTrimAll(str) {
	if (str==null){return str;}
	for (var i=0; str.charAt(i)==" " || str.charAt(i)=="\n" || str.charAt(i)=="\t"; i++);
	return str.substring(i,str.length);
	}
function RTrimAll(str) {
	if (str==null){return str;}
	for (var i=str.length-1; str.charAt(i)==" " || str.charAt(i)=="\n" || str.charAt(i)=="\t"; i--);
	return str.substring(0,i+1);
	}
function TrimAll(str) {
	return LTrimAll(RTrimAll(str));
	}
*/

//-------------------------------------------------------------------
// isBlank(value)
//   Returns true if value only contains spaces
//-------------------------------------------------------------------
function isBlank(val){
	if(val == null){ return true; }
	for(var i=0;i<val.length;i++) {
		if ((val.charAt(i)!=' ')&&(val.charAt(i)!="\t")&&(val.charAt(i)!="\n")&&(val.charAt(i)!="\r")){return false;}
	}
	return true;
}


//-------------------------------------------------------------------
// isBlank(value)
//   Returns true is value between min and max, false otherwise. 
//-------------------------------------------------------------------
function isBetween(value, min, max) {
	
	if(isInteger(value) || isNumeric(value)) { val = parseFloat(value); }
	else                                     { val = value.length; }
	
	if(max && max) { return (val >= min && val <= max); } 
	else if(min)   { return (val >= min); } 
	else if(max)   { return (val <= max); } 
	else           { return false; }
	return true;
}


//-------------------------------------------------------------------
// isInteger(value)
//   Returns true if value contains all digits
//-------------------------------------------------------------------
function isInteger(val){
	if (isBlank(val)){return false;}
	for(var i=0;i<val.length;i++){
		if(!isDigit(val.charAt(i))){return false;}
	}
	return true;
}


//-------------------------------------------------------------------
// isDigit(value)
//   Returns true if value is a 1-character digit
//-------------------------------------------------------------------
function isDigit(num) {
	if (num.length>1){return false;}
	var string="1234567890";
	if (string.indexOf(num)!=-1){return true;}
	return false;
}


//-------------------------------------------------------------------
// isNumeric(value)
//   Returns true if value contains a positive float value
//-------------------------------------------------------------------
function isNumeric(val) {
	return(parseFloat(val,10)==(val*1));
}

//-------------------------------------------------------------------
// isZipCode(value)
//   Returns true is value is valid zip, false otherwise.
//-------------------------------------------------------------------
function isZipCode(val) {
	var re = new RegExp("[0-9]{5}");
	return re.test(val);
}


//-------------------------------------------------------------------
// isValidEmailStrict(value)
//	 Check that an email address has the form something@something.something
//   This is a stricter standard than RFC 821 (?) which allows addresses like postmaster@localhost
//   
//  Returns true if valid email
//-------------------------------------------------------------------
function isValidEmailStrict(address) {
	if (isValidEmail(address) == false) return false;
	var domain = address.substring(address.indexOf('@') + 1);
	if (domain.indexOf('.') == -1) return false;
	if (domain.indexOf('.') == 0 || domain.indexOf('.') == domain.length - 1) return false;
	return true;
}


// Check that an email address is valid based on RFC 821 (?)
function isValidEmail(address) {
	if (address != '' && address.search) {
      if (address.search(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/) != -1) return true;
      else return false;
	}
	
  	// allow empty strings to return true - screen these with either a 'required' test or a 'length' test
   	else return true;
}