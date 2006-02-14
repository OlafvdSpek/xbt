//--------------------------------------------
// Set up our simple tag open values
//--------------------------------------------

var B_open = 0;
var I_open = 0;
var U_open = 0;
var QUOTE_open = 0;
var CODE_open = 0;
var SQL_open = 0;
var HTML_open = 0;

var bbtags   = new Array();

// Set the initial radio button status based on cookies

var allcookies = document.cookie;
var pos = allcookies.indexOf("bbmode=");

prep_mode();

function prep_mode()
{
	if (pos != 1) {
		var cstart = pos + 7;
		var cend   = allcookies.indexOf(";", cstart);
		if (cend == -1) { cend = allcookies.length; }
		cvalue = allcookies.substring(cstart, cend);
		
		if (cvalue == 'ezmode') {
			document.REPLIER.bbmode[0].checked = true;
		} else {
			document.REPLIER.bbmode[1].checked = true;
		}
	} else {
		// default to normal mode.
		document.REPLIER.bbmode[1].checked = true;
	}
}

function setmode(mVal)
{
	document.cookie = "bbmode="+mVal+"; path=/; expires=Wed, 1 Jan 2020 00:00:00 GMT;";
}

function get_easy_mode_state()
{
	// Returns true if we've chosen easy mode
	
	if (document.REPLIER.bbmode[0].checked)
	{
		return true;
	}
	else
	{
		return false;
	}
}

//--------------------------------------------
// Set the help bar status
//--------------------------------------------

function hstat(msg)
{
	document.REPLIER.helpbox.value = eval( "help_" + msg );
}

// Set the number of tags open box

function cstat()
{
	var c = stacksize(bbtags);
	
	if ( (c < 1) || (c == null) )
	{
		c = 0;
	}
	
	if ( ! bbtags[0] )
	{
		c = 0;
	}
	
	document.REPLIER.tagcount.value = c;
}

//--------------------------------------------
// Get stack size
//--------------------------------------------

function stacksize(thearray)
{
	for (i = 0 ; i < thearray.length; i++ )
	{
		if ( (thearray[i] == "") || (thearray[i] == null) || (thearray == 'undefined') )
		{
			return i;
		}
	}
	
	return thearray.length;
}

//--------------------------------------------
// Push stack
//--------------------------------------------

function pushstack(thearray, newval)
{
	arraysize = stacksize(thearray);
	thearray[arraysize] = newval;
}

//--------------------------------------------
// Pop stack
//--------------------------------------------

function popstack(thearray)
{
	arraysize = stacksize(thearray);
	theval = thearray[arraysize - 1];
	delete thearray[arraysize - 1];
	return theval;
}


//--------------------------------------------
// Close all tags
//--------------------------------------------

function closeall()
{
	//var c=bbtags.length;
	
	//alert('Stack: ' + c);
	
	if (bbtags[0])
	{
		while (bbtags[0])
		{
			tagRemove = popstack(bbtags)
			document.REPLIER.Post.value += "[/" + tagRemove + "]";
			
			// Change the button status
			eval("document.REPLIER." + tagRemove + ".value = ' " + tagRemove + " '");
			eval(tagRemove + "_open = 0");
		}
		
	}
	
	// Ensure we got them all
	
	document.REPLIER.tagcount.value = 0;
	bbtags = new Array();
	document.REPLIER.Post.focus();
}

//--------------------------------------------
// EMOTICONS
//--------------------------------------------

function emoticon(theSmilie)
	{
	if (document.REPLIER.Post.caretPos && document.REPLIER.Post.createTextRange)
	{
		var caretPos = document.REPLIER.Post.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? theSmilie + ' ' : theSmilie;
	}
	else
	{
    	document.REPLIER.Post.value += ' ' + theSmilie + ' ';
    }
    
    document.REPLIER.Post.focus();
}

//--------------------------------------------
// ADD CODE
//--------------------------------------------

function add_code(NewCode)
{
    document.REPLIER.Post.value += NewCode;
    document.REPLIER.Post.focus();
    return;
}

//--------------------------------------------
// ALTER FONT
//--------------------------------------------

function alterfont(theval, thetag)
{
    if (theval == 0)
    {
    	return;
    }
    else
    {
    	document.REPLIER.Post.value += "[" + thetag + "=" + theval + "] [/" + thetag + "]";
    }
    
    document.REPLIER.ffont.selectedIndex  = 0;
    document.REPLIER.fsize.selectedIndex  = 0;
    document.REPLIER.fcolor.selectedIndex = 0;
    
    document.REPLIER.Post.focus();
}


//--------------------------------------------
// SIMPLE TAGS (such as B, I U, etc)
//--------------------------------------------

function simpletag(thetag)
{

	var tagOpen = eval(thetag + "_open");
	
	if ( get_easy_mode_state() )
	{
		inserttext = prompt(prompt_start + "\n[" + thetag + "]xxx[/" + thetag + "]");
		if ( (inserttext != null) && (inserttext != "") )
		{
			document.REPLIER.Post.value += "[" + thetag + "]" + inserttext + "[/" + thetag + "] ";
		}
	}
	else
	{
	
		if (tagOpen == 0)
		{
			document.REPLIER.Post.value += "[" + thetag + "]";
			
			eval(thetag + "_open = 1");
			
			// Change the button status
			
			eval("document.REPLIER." + thetag + ".value += '*'");
			
			pushstack(bbtags, thetag);
			cstat();
			hstat('click_close');
		}
		else
		{
			// Find the last occurance of the opened tag
			
			lastindex = 0;
			
			for (i = 0 ; i < bbtags.length; i++ )
			{
				if ( bbtags[i] == thetag )
				{
					lastindex = i;
				}
			}
			
			// Close all tags opened up to that tag was opened
			
			while (bbtags[lastindex])
			{
				tagRemove = popstack(bbtags);
				document.REPLIER.Post.value += "[/" + tagRemove + "]";
				
				// Change the button status
				eval("document.REPLIER." + tagRemove + ".value = ' " + tagRemove + " '");
				eval(tagRemove + "_open = 0");
			}
			
			cstat();
			
		}
	}
	
	document.REPLIER.Post.focus();
}


function tag_list()
{
	listvalue = "init";
	
	thelist = "[LIST]\n";
	
	while ( (listvalue != "") && (listvalue != null) )
	{
		listvalue = prompt(list_prompt, "");
		
		if ( (listvalue != "") && (listvalue != null) )
		{
			thelist = thelist+"[*]"+listvalue+"\n";
		}
	}
	document.REPLIER.Post.value += thelist + "[/LIST]\n";
	document.REPLIER.Post.focus();
}

function tag_url()
{
    var FoundErrors = '';
    var enterURL   = prompt(text_enter_url, "http://");
    var enterTITLE = prompt(text_enter_url_name, "My Webpage");
    if (!enterURL)
    {
        FoundErrors += " " + error_no_url;
    }
    if (!enterTITLE)
    {
        FoundErrors += " " + error_no_title;
    }
    if (FoundErrors)
    {
        alert("Error!"+FoundErrors);
        return;
    }
    var ToAdd = "[URL="+enterURL+"]"+enterTITLE+"[/URL]";
    document.REPLIER.Post.value+=ToAdd;
	document.REPLIER.Post.focus();
}

function tag_image()
{
    var FoundErrors = '';
    var enterURL   = prompt(text_enter_image, "http://");
    if (!enterURL) {
        FoundErrors += " " + error_no_url;
    }
    if (FoundErrors) {
        alert("Error!"+FoundErrors);
        return;
    }
    var ToAdd = "[IMG]"+enterURL+"[/IMG]";
    document.REPLIER.Post.value+=ToAdd;
	document.REPLIER.Post.focus();
}

function tag_email()
{
    var emailAddress = prompt(text_enter_email,"");
    if (!emailAddress) { alert(error_no_email); return; }
    var ToAdd = "[EMAIL]"+emailAddress+"[/EMAIL]";
    document.REPLIER.Post.value+=ToAdd;
	document.REPLIER.Post.focus();
}