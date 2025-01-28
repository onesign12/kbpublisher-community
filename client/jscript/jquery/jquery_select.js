/*
 *
 * Copyright (c) 2006 Sam Collett (http://www.texotela.co.uk)
 * Licensed under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 * 
 */
 
 
 
/*
 jQuery.fn.selectOption = function ()
 {
 	jQuery(this).removeOption(0);
 } 
*/
 
 
 
/*
 * Adds (single/multiple) options to a select box (or series of select boxes)
 *
 * @name     addOption
 * @author   Sam Collett (http://www.texotela.co.uk)
 * @example  $("#myselect").addOption("Value", "Text"); // add single value (will be selected)
 *           $("#myselect").addOption("Value 2", "Text 2", false); // add single value (won't be selected)
 *           $("#myselect").addOption({"foo":"bar","bar":"baz"}, false); // add multiple values, but don't select
 *
 */
jQuery.fn.addOption = function()
{
	if(arguments.length == 0) return this;
	// select option when added? default is true
	var selectOption = true;
	// multiple items
	var multiple = false;
	if(typeof arguments[0] == "object")
	{
		multiple = true;
		var items = arguments[0];
	}
	if(arguments.length >= 2)
	{
		if(typeof arguments[1] == "boolean") selectOption = arguments[1];
		else if(typeof arguments[2] == "boolean") selectOption = arguments[2];
		if(!multiple)
		{
			var value = arguments[0];
			var text = arguments[1];
		}
	}
	this.each(
		function()
		{
			if(this.nodeName.toLowerCase() != "select") return;
			if(multiple)
			{
				for(var v in items)
				{
					jQuery(this).addOption(v, items[v], selectOption);
				}
			}
			else
			{
				var option = document.createElement("option");
				option.value = value;
				option.text = text;
				this.options.add(option);
			}
			if(selectOption)
			{
				this.options[this.options.length-1].selected = true;
			}
		}
	)
	return this;
}

/*
 * Removes an option (by value or index) from a select box (or series of select boxes)
 *
 * @name     removeOption
 * @author   Sam Collett (http://www.texotela.co.uk)
 * @example  jQuery("#myselect").removeOption("Value"); // remove by value
 *           jQuery("#myselect").removeOption(0); // remove by index
 *
 */
jQuery.fn.removeOption = function()
{
	if(arguments.length == 0) return this;
	if(typeof arguments[0] == "string") var value = arguments[0];
	else if(typeof arguments[0] == "number") var index = arguments[0];
	else return this;
	this.each(
		function()
		{
			if(this.nodeName.toLowerCase() != "select") return;
			if(value)
			{
				var optionsLength = this.options.length;
				for(var i=optionsLength-1; i>=0; i--)
				{
					if(this.options[i].value == value)
					{
						this.options[i] = null;
					}
				}
			}
			else
			{
				this.remove(index);
			}
		}
	)
	return this;
}

/*
 * Sort options (ascending or descending) in a select box (or series of select boxes)
 *
 * @name     sortOptions
 * @author   Sam Collett (http://www.texotela.co.uk)
 * @param    ascending   Sort ascending (true/undefined), or descending (false)
 * @example  // ascending
 *           jQuery("#myselect").sortOptions(); // or jQuery("#myselect").sortOptions(true);
 *           // descending
 *           jQuery("#myselect").sortOptions(false);
 *
 */
jQuery.fn.sortOptions = function(ascending)
{
	this.each(
		function()
		{
			if(this.nodeName.toLowerCase() != "select") return;
			// default sort is ascending if parameter is undefined
			ascending = typeof ascending == "undefined" ? true : ascending;
			// get number of options
			var optionsLength = this.options.length;
			// create an array for sorting
			var sortArray = [];
			// loop through options, adding to sort array
			for(var i = 0; i<optionsLength; i++)
			{
				sortArray[i] =
				{
					value: this.options[i].value,
					text: this.options[i].text
				};
			}
			// sort items in array
			sortArray.sort(
				function(option1, option2)
				{
					// option text is made lowercase for case insensitive sorting
					option1text = option1.text.toLowerCase();
					option2text = option2.text.toLowerCase();
					// if options are the same, no sorting is needed
					if(option1text == option2text) return 0;
					if(ascending)
					{
						return option1text < option2text ? -1 : 1;
					}
					else
					{
						return option1text > option2text ? -1 : 1;
					}
				}
			);
			// change the options to match the sort array
			for(var i = 0; i<optionsLength; i++)
			{
				this.options[i].text = sortArray[i].text;
				this.options[i].value = sortArray[i].value;
			}
		}
	)
	return this;
}