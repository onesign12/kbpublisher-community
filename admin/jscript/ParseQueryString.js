/* ParseQueryString v1.0.2

   Changes since v1.0.1
     - corrected grammar and spelling in documentation
     - cosmetic changes to code

   Changes since v1.0.0
     - bugfix for Netscape v4.79 browsers

   Copyright (c) 2004-2005, Jeff Mott. All rights reserved.
   This is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License.
   <http://www.gnu.org/licenses/gpl.txt> */

Object.prototype.clone = function()
{
    var o = {};
    var property;

    for (property in this) {
        if (typeof this[property] == "object")
            o[property] = this[property].clone();
        else
            o[property] = this[property];
    }

    return o;
}

Array.prototype.clone = function()
{
    var a = [];
    var i;

    for (i = 0; i < this.length; i++) {
        if (typeof this[i] == "object")
            a[i] = this[i].clone();
        else
            a[i] = this[i];
    }

    return a;
}

String.prototype.decodeURL = function() {
    /* unescape has been deprecated in JavaScript 1.5
       this line may need to be changed in a future version */
    return unescape(this.replace(/\+/g, " "));
}

function ParseQueryString()
{
    var parameters = {};
    var parameterNames = [];
	
	this.location = location;

    /* defined for convience and readability */
    function defined(expr)
    {
        return expr != undefined;
    }

    function init()
    {
        var i;
        var pair, pairs;
        var name, value;

        if (this.location.search.length < 2)
            return;

        pairs = this.location.search.substr(1).split(/[&;]/);

        for (i = 0; i < pairs.length; i++)
        {
            pair = pairs[i].split(/=/);

            name = pair[0].decodeURL();
            if (defined(pair[1]))
                value = pair[1].decodeURL();
            else
                value = undefined;

            if (!defined(parameters[name])) {
                parameterNames.push(name);
                if (defined(value))
                    parameters[name] = [value];
                else
                    parameters[name] = [];
            }
            else if (defined(value))
                parameters[name].push(value);
        }
    }
    init();
    
    var scalarParamIndex = 0;
    
    this.param = function(name)
    {
        if (arguments.length) {
            if (defined(parameters[name]))
                return parameters[name][0];
            else
                return undefined;
        }
        else {
            if (scalarParamIndex < parameterNames.length)
                return parameterNames[scalarParamIndex++];
            else {
                scalarParamIndex = 0;
                return undefined;
            }
        }
    }
    
    this.params = function(name)
    {
        if (arguments.length) {
            if (defined(parameters[name]))
                return parameters[name].clone();
            else
                return null;
        }
        else
            return parameterNames.clone();
    }
}
