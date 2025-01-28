var OnKeyRequestBuffer =
    {
        bufferText: false,
        bufferTime: 700,
        mobile: false,

		
		getStr : function(str) 
		{
			str = str.replace(/\n/g, " ");
			str = str.replace(/"/g, "");
			str = str.replace(/\\/g, "\\\\");
			
			// rewrites "<script" as "<noscript" and "<script" as "</noscript." 
			//str = str.replace(/(\<\/?)script/g,"$1noscript"); ??

			return str;
		},

        modified : function(strId)
        {
			var strText = OnKeyRequestBuffer.getStr(xajax.$(strId).value);
            setTimeout('OnKeyRequestBuffer.compareBuffer("'+strId+'", "'+strText+'");', this.bufferTime);
        },

        compareBuffer : function(strId, strText)
        {
		   var strText1 = OnKeyRequestBuffer.getStr(xajax.$(strId).value);
		   if (strText == strText1 && strText != this.bufferText)
            {
                this.bufferText = strText;
                OnKeyRequestBuffer.makeRequest(strId);
            }
        },

        makeRequest : function(strId)
        {
            if (OnKeyRequestBuffer.mobile) {
                $.mobile.showPageLoadingMsg();
                xajax_requestBuffer(xajax.$(strId).value); 
            } else {
                xajax_requestBuffer(xajax.$(strId).value, 'spinner_message');    
            }
        }
    }

/*
This class will only execute the code in the OnKeyRequestBuffer.makeRequest function
if the text in the input with the given id has not changed in a half of a second (500 ms).
So now, instead of calling the xajax function directly in the onkeyup event, we call our buffer class:

<input id="myText" type="text" onkeyup="OnKeyRequestBuffer.modified('myText');" />
*/