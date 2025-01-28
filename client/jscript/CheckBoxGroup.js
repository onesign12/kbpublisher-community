// HISTORY
// ------------------------------------------------------------------
// February 29, 2004: Fixed bug caused by LAST update, when control
//    checkbox is the first on checked. I hate when that happens.
// January 28, 2004: Fixed bug that occurred when checkbox was CHECKED
//    by default.
// December 16, 2002: Created
/* 
Original coding done by David Rogers - http://www.MrDaveR.com/

DESCRIPTION: This library lets you quickly and easily make multiple checkboxes
behave as a group by limiting the total number of boxes that can be checked
and/or have a master control checkbox. 

COMPATABILITY: Should work on all Javascript-compliant browsers.

USAGE:
// Create a new CheckBoxGroup object
var myOptions = new CheckBoxGroup();

// Tell the object which checkboxes exist in your group. You may make multiple
// calls to this function, and/or pass multiple arguments. You may specify
// field names exactly, or use a wildcard at the beginning or end of the 
// name.
myOptions.addToGroup("cb1","cb2","optionset*");
myOptions.addToGroup("*checkbox");

// Optionally set a "control" box which will effect all other boxes.
myOptions.setControlBox("masterCheckboxName");

// Specify how your control box will interact with the group. Options are
// either "some" or "all" ("all" is default).
// all: Checking the box will select all the other boxes. Unchecking it will
//      uncheck all the group checkboxes. Selecting all the checkboxes in
//      the group will automatically check the control box.
// some: Checking any checkbox in the group will check the control box. The
//       control box may not be unchecked if any option in the group is 
//       still checked. If no boxes in the group are checked, you may still
//       check the control box.
myOptions.setMasterBehavior("some");

// Optionally set the maximum number of boxes in the group which are allowed
// to be checked. You may pass a second argument which is an alert message to
// be displayed to the user if they exceed this limit.
myOptions.setMaxAllowed(3);
myOptions.setMaxAllowed(3,"You may only select 3 choices!");

// IMPORTANT: After defining the group and behavior, you must insert an action
// into EVERY checkbox's onClick event-handler. Just specify the name of the
// group object that the checkbox belongs to, and pass it 'this' as the argument.
<INPUT TYPE="checkbox" NAME="cb1" onClick="myOptions.check(this)">

// That's it! Your checkboxes will now behave as a group!

*/ 
function CheckBoxGroup() {
	this.controlBox=null;
	this.controlBoxChecked=null;
	this.maxAllowed=null;
	this.maxAllowedMessage=null;
	this.masterBehavior=null;
	this.formRef=null;
	this.checkboxWildcardNames=new Array();
	this.checkboxNames=new Array();
	this.totalBoxes=0;
	this.totalSelected=0;
	
	// my vars
	this.radioButtonBehavior=null ;	
	
	// Public methods
	this.setRadioButtonBehavior=CBG_setRadioButtonBehavior;	
	
	this.setControlBox=CBG_setControlBox;
	this.setMaxAllowed=CBG_setMaxAllowed;
	this.setMasterBehavior=CBG_setMasterBehavior;	// all, some
	this.addToGroup=CBG_addToGroup;
	// Private methods
	this.expandWildcards=CBG_expandWildcards;
	this.addWildcardCheckboxes=CBG_addWildcardCheckboxes;
	this.addArrayCheckboxes=CBG_addArrayCheckboxes;
	this.addSingleCheckbox=CBG_addSingleCheckbox;
	this.check=CBG_check;
	}

// Set the master control checkbox name
function CBG_setControlBox(name) { this.controlBox=name; }

// Set the maximum number of checked boxes in the set, and optionally
// the message to popup when the max is reached.
function CBG_setMaxAllowed(num,msg) {
	this.maxAllowed=num;
	if (msg!=null&&msg!="") { this.maxAllowedMessage=msg; }
	}



// Set the maximum number of checked boxes in the set, and optionally
// the message to popup when the max is reached.
function CBG_setRadioButtonBehavior(num,msg) {
	this.radioButtonBehavior = true;
	num = (num!=null&&num!="") ? num : 1;
	this.setMaxAllowed(num, msg);
	}


// Set the behavior for the checkbox group master checkbox
//	All: all boxes must be checked for the master to be checked
//	Some: one or more of the boxes can be checked for the master to be checked
function CBG_setMasterBehavior(b) { this.masterBehavior = b.toLowerCase(); }

// Add checkbox wildcards to the checkboxes array
function CBG_addToGroup() {
	if (arguments.length>0) {
		for (var i=0;i<arguments.length;i++) {
			this.checkboxWildcardNames[this.checkboxWildcardNames.length]=arguments[i];
			}
		}
	}

// Expand the wildcard checkbox names given in the addToGroup method
function CBG_expandWildcards() {
	if (this.formRef==null) {alert("ERROR: No form element has been passed.  Cannot extract form name!"); return false; }
	for (var i=0; i<this.checkboxWildcardNames.length;i++) {
		var n = this.checkboxWildcardNames[i];
		var el = this.formRef[n];
		if (n.indexOf("*")!=-1) { this.addWildcardCheckboxes(n); }
		else if(CBG_nameIsArray(el)) { this.addArrayCheckboxes(n); }
		else { this.addSingleCheckbox(el); }
		}
	}


// Add checkboxes to the group which match a pattern
function CBG_addWildcardCheckboxes(name) {
	var i=name.indexOf("*");
	if ((i==0) || (i==name.length-1)) {
		searchString= (i)?name.substring(0,name.length-1):name.substring(1,name.length);
		for (var j=0;j<this.formRef.length;j++) {
			currentElement = this.formRef.elements[j];
			currentElementName=currentElement.name;
			var partialName = (i)?currentElementName.substring(0,searchString.length) : currentElementName.substring(currentElementName.length-searchString.length,currentElementName.length);
			if (partialName==searchString) {
				if(CBG_nameIsArray(currentElement)) this.addArrayCheckboxes(currentElement);
				else this.addSingleCheckbox(currentElement);
				}
			}
		}
	}

// Add checkboxes to the group which all have the same name
function CBG_addArrayCheckboxes(name) {
	if((CBG_nameIsArray(this.formRef[name])) && (this.formRef[name].length>0)) {
		for (var i=0; i<this.formRef[name].length; i++) { this.addSingleCheckbox(this.formRef[name][i]); }
		}
	}

function CBG_addSingleCheckbox(obj) {
	if (obj != this.formRef[this.controlBox]) {
		this.checkboxNames[this.checkboxNames.length]=obj;
		this.totalBoxes++;
		if (obj.checked) {
			this.totalSelected++;
			}
		}
	}

// Runs whenever a checkbox in the group is clicked
function CBG_check(obj) {
	var checked=obj.checked;
	if (this.formRef==null) {
		this.formRef=obj.form;
		this.expandWildcards();
		if (this.controlBox==null || obj.name!=this.controlBox) {
			this.totalSelected += (checked)?-1:1;
			}
		}
	if (this.controlBox!=null&&obj.name==this.controlBox) {
		if (this.masterBehavior=="all") {
			for (i=0;i<this.checkboxNames.length;i++) { this.checkboxNames[i].checked=checked; }
			this.totalSelected=(checked)?this.checkboxNames.length:0;
			}
		else {
			if (!checked) {
				obj.checked = (this.totalSelected>0)?true:false;
				obj.blur();
				}
			}
		}
	else {
		if (this.masterBehavior=="all") {
			if (!checked) {
				this.formRef[this.controlBox].checked=false;
				this.totalSelected--;
				}
			else { this.totalSelected++; }
			if (this.controlBox!=null) {
				this.formRef[this.controlBox].checked=(this.totalSelected==this.totalBoxes)?true:false;
				}
			}
		else {
			if (!obj.checked) { this.totalSelected--; }	
			else { this.totalSelected++; }
			if (this.controlBox!=null) {
				this.formRef[this.controlBox].checked=(this.totalSelected>0)?true:false;
				}
			if (this.maxAllowed!=null && this.radioButtonBehavior == null) {
				if (this.totalSelected>this.maxAllowed) {
					obj.checked=false;
					this.totalSelected--;
					if (this.maxAllowedMessage!=null) { alert(this.maxAllowedMessage); }
					return false;
					}
				}
			}
		//}
		
			// my vars
			if(this.radioButtonBehavior != null) {
				if (this.totalSelected>this.maxAllowed) {
					for (var i=0;i<this.checkboxNames.length;i++) { 
						if(this.checkboxNames[i].checked == true && this.checkboxNames[i] != obj) {
							this.checkboxNames[i].checked = false
							this.totalSelected--;
							return;		
						}
					}
				}
			}
		}
	}

function CBG_nameIsArray(obj) {
	return ((typeof obj.type!="string")&&(obj.length>0)&&(obj[0]!=null)&&(obj[0].type=="checkbox"));
	}
