<?php

class skin_buddy {


function buddy_js() {
global $ibforums;
return <<<EOF
<script language='javascript' type="text/javascript">
<!--
 function redirect_to(where, closewin)
 {
 	opener.location= '$ibforums->base_url' + where;
 	
 	if (closewin == 1)
 	{
 		self.close();
 	}
 }
 
 function check_form(helpform)
 {
 	opener.name = "ibfmain";
 
 	if (helpform == 1) {
 		document.theForm2.target = 'ibfmain';
 	} else {
 		document.theForm.target = 'ibfmain';
 	}
 	
 	return true;
 }
 
 function shrink()
 {
 	window.resizeTo('200','75');
 }
 
 function expand()
 {
 	window.resizeTo('200','450');
 }
 
 
 //-->
 </script>
 
EOF;
}


function build_away_msg() {
global $ibforums;
return <<<EOF

	{$ibforums->lang['new_posts']}
	<br />
	{$ibforums->lang['my_replies']}
 
EOF;
}

function append_view($url="") {
global $ibforums;
return <<<EOF
	( <b><a href='javascript:redirect_to("$url", 0)'>{$ibforums->lang['view_link']}</a></b> )
EOF;
}


function main($away_text="") {
global $ibforums;
return <<<EOF
<div id="ucpcontent">
 <div class="titlemedium" align="center">{$ibforums->lang['page_title']}&nbsp;&nbsp;&nbsp;[ <a href='javascript:shrink()' style='text-decoration:none'>-</a>&nbsp;&nbsp;<a href='javascript:expand()' style='text-decoration:none'>+</a> ]</div>
 <div class="pformstrip" align="center">{$ibforums->lang['while_away']}</div>
 <p>{$away_text}</p>
 <div class="pformstrip" align="center">{$ibforums->lang['show_me']}</div>
 <p>
	&middot;&nbsp;<a href='javascript:redirect_to("&amp;act=Stats&amp;CODE=leaders",0)'>{$ibforums->lang['sm_forum_leaders']}</a>
	<br />&middot;&nbsp;<a href='javascript:redirect_to("&amp;act=Search&amp;CODE=getactive",0)'>{$ibforums->lang['sm_todays_posts']}</a>
	<br />&middot;&nbsp;<a href='javascript:redirect_to("&amp;act=Stats",0)'>{$ibforums->lang['sm_today_posters']}</a>
	<br />&middot;&nbsp;<a href='javascript:redirect_to("&amp;act=Members&amp;max_results=10&amp;sort_key=posts&amp;sort_order=desc",0)'>{$ibforums->lang['sm_all_posters']}</a>
	<br />&middot;&nbsp;<a href='javascript:redirect_to("&amp;act=Search&amp;CODE=lastten",0)'>{$ibforums->lang['sm_my_last_posts']}</a>
 </p>
 <div class="pformstrip" align="center">{$ibforums->lang['search_forums']}</div>
 <div align="center">
 <p>
 <form action="{$ibforums->base_url}act=Search&amp;CODE=01&amp;forums=all&amp;cat_forum=forum&amp;joinname=1&amp;search_in=posts&amp;result_type=topics" method="post" name='theForm' onsubmit='return check_form();'>
  <input type='text' size='17' name='keywords' class='forminput' />&nbsp;<input type='submit' value='{$ibforums->lang['go']}' />
 </form>
 </p>
 </div>
 
 <div class="pformstrip" align="center">{$ibforums->lang['search_help']}</div>
 <div align="center">
 <p>
 <form action="{$ibforums->base_url}act=Help&amp;CODE=02" method="post" name='theForm2' onsubmit='return check_form(1);'>
  <input type='text' size='17' name='search_q' class='forminput' />&nbsp;<input type='submit' value='{$ibforums->lang['go']}' />
 </form>
 </p>
 </div>
</div>
<div align="center"><!--CLOSE.LINK--></div>
EOF;
}



function login() {
global $ibforums;
return <<<EOF

<form action="{$ibforums->base_url}act=Login&amp;CODE=01&amp;CookieDate=1&amp;buddy=1" method="post" name='theForm' onSubmit='return check_form();'>
<div class='tableborder'>
 <div class='maintitle'>{$ibforums->lang['page_title']}</div>
 <div class='pformstrip'>{$ibforums->lang['log_in_needed']}</div>
 <div class='tablepad'>
  {$ibforums->lang['no_guests']}
  <br /><br />
  <center>
  <b>{$ibforums->lang['log_in']}</b>
  <br /><br />
  {$ibforums->lang['lin_name']}<br /><input type='text' name='UserName' class='forminput'>
  <br />
  {$ibforums->lang['lin_pass']}<br /><input type='password' name='PassWord' class='forminput'>
  <br />
  <input type='submit' value='{$ibforums->lang['log_in']}' class='forminput'>
  </center>
  <br /><br />
  {$ibforums->lang['reg_text']}
  <br /><br />
  <center><a href='javascript:redirect_to("&amp;act=Reg", 1);'>{$ibforums->lang['reg_link']}</a></center>
 </div>
</div>
<!--CLOSE.LINK-->
</form>

EOF;
}


function closelink() {
global $ibforums;
return <<<EOF

<div align="center">
 [ <a href="javascript:window.location=window.location;">{$ibforums->lang['refresh']}</a> ] | [ <a href='javascript:self.close();'>{$ibforums->lang['close_win']}</a> ]
</div>
EOF;
}

}
?>