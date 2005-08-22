<?php

class skin_global {

function pop_up_window($title, $css, $text) {
global $ibforums;
return <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml"> 
 <head> 
  <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" /> 
  <title>$title</title>
  $css
 </head>
 <script language='JavaScript' type="text/javascript">
 <!--
 function buddy_pop() { window.open('index.{$ibforums->vars['php_ext']}?act=buddy&s={$ibforums->session_id}','BrowserBuddy','width=250,height=500,resizable=yes,scrollbars=yes'); }
 function chat_pop(cw,ch)  { window.open('index.{$ibforums->vars['php_ext']}?s={$ibforums->session_id}&act=chat&pop=1','Chat','width='+cw+',height='+ch+',resizable=yes,scrollbars=yes'); }
 function multi_page_jump( url_bit, total_posts, per_page )
 {
 pages = 1; cur_st = parseInt("{$ibforums->input['st']}"); cur_page  = 1;
 if ( total_posts % per_page == 0 ) { pages = total_posts / per_page; }
  else { pages = Math.ceil( total_posts / per_page ); }
 msg = "{$ibforums->lang['tpl_q1']}" + " " + pages;
 if ( cur_st > 0 ) { cur_page = cur_st / per_page; cur_page = cur_page -1; }
 show_page = 1;
 if ( cur_page < pages )  { show_page = cur_page + 1; }
 if ( cur_page >= pages ) { show_page = cur_page - 1; }
  else { show_page = cur_page + 1; }
 userPage = prompt( msg, show_page );
 if ( userPage > 0  ) {
	 if ( userPage < 1 )     {    userPage = 1;  }
	 if ( userPage > pages ) { userPage = pages; }
	 if ( userPage == 1 )    {     start = 0;    }
	 else { start = (userPage - 1) * per_page; }
	 window.location = url_bit + "&st=" + start;
 }
 }
 //-->
 </script>
 <body>
 <div style='text-align:left'>
 $text
 </div>
 </body>
</html>
EOF;
}


function make_page_jump($tp="", $pp="", $ub="" ) {
global $ibforums;
return <<<EOF
<a title="{$ibforums->lang['tpl_jump']}" href="javascript:multi_page_jump('$ub',$tp,$pp);">{$ibforums->lang['tpl_pages']}</a>
EOF;
}

function signature_separator($sig="") {
global $ibforums;
return <<<EOF
<br /><br />--------------------<br />
<div class='signature'>$sig</div>
EOF;
}

function forum_show_rules_full($rules) {
global $ibforums;
return <<<EOF
    <!-- Show FAQ/Forum Rules -->
    <div align='left'><{F_RULES}>&nbsp;<b>{$rules['title']}</b><br /><br />{$rules['body']}</div>
	<br />
   <!-- End FAQ/Forum Rules -->
EOF;
}

function forum_show_rules_link($rules) {
global $ibforums;
return <<<EOF
	<!-- Show FAQ/Forum Rules -->
	
    <div align='left'><{F_RULES}>&nbsp;<b><a href='{$ibforums->base_url}act=SR&amp;f={$rules['fid']}'>{$rules['title']}</a></b></div>
	
    <!-- End FAQ/Forum Rules -->
EOF;
}

function css_inline($css="") {
global $ibforums;
return <<<EOF
<style type='text/css'>
{$css}
</style>
EOF;
}

function css_external($css, $img) {
global $ibforums;
return <<<EOF
<style type='text/css' media="all">
@import url(css.php?d={$css}_{$img}.css);
</style>
EOF;
}

function Member_bar($msg, $ad_link, $mod_link, $val_link) {
global $ibforums;
return <<<EOF
  <table width="100%" id="userlinks" cellspacing="6">
   <tr>
    <td><strong>{$ibforums->lang['logged_in_as']} <a href='{$ibforums->base_url}showuser={$ibforums->member['id']}'>{$ibforums->member['name']}</a></strong> ( <a href='{$ibforums->base_url}act=Login&amp;CODE=03'>{$ibforums->lang['log_out']}</a>$ad_link $mod_link $val_link )</td>
    <td align='right'>
      <b><a href='{$ibforums->base_url}act=UserCP&amp;CODE=00' title='{$ibforums->lang['cp_tool_tip']}'>{$ibforums->lang['your_cp']}</a></b> &middot; <a href='{$ibforums->base_url}act=Msg&amp;CODE=01'>{$msg[TEXT]}</a>
      &middot; <a href='{$ibforums->base_url}act=Search&amp;CODE=getnew'>{$ibforums->lang['view_new_posts']}</a> &middot; <a href='javascript:buddy_pop();' title='{$ibforums->lang['bb_tool_tip']}'>{$ibforums->lang['l_qb']}</a></td>
   </tr>
  </table>
EOF;
}

function Guest_bar() {
global $ibforums;
return <<<EOF
  <table width="100%" id="userlinks" cellspacing="6">
   <tr>
    <td>{$ibforums->lang['guest_stuff']} ( <a href='{$ibforums->base_url}act=Login&amp;CODE=00'>{$ibforums->lang['log_in']}</a> | <a href='{$ibforums->base_url}act=Reg&amp;CODE=00'>{$ibforums->lang['register']}</a> )</td>
    <td align='right'><a href='{$ibforums->base_url}act=Reg&amp;CODE=reval'>{$ibforums->lang['ml_revalidate']}</a></td>
   </tr>
  </table>
EOF;
}

function member_bar_disabled() {
global $ibforums;
return <<<EOF
  <table width="100%" id="userlinks" cellspacing="6">
   <tr>
    <td><strong>{$ibforums->lang['mb_disabled']} </strong></td>
   </tr>
  </table>
EOF;
}

function Member_no_usepm_bar($ad_link, $mod_link, $val_link) {
global $ibforums;
return <<<EOF
  <table width="100%" id="userlinks" cellspacing="6">
   <tr>
    <td><b>{$ibforums->lang['logged_in_as']} {$ibforums->member['name']}</b> ( <a href='{$ibforums->base_url}act=Login&amp;CODE=03'>{$ibforums->lang['log_out']}</a>$ad_link $mod_link $val_link )</td>
    <td align='right'>
   <b><a href='{$ibforums->base_url}act=UserCP&amp;CODE=00' title='{$ibforums->lang['cp_tool_tip']}'>{$ibforums->lang['your_cp']}</a></b>
    &middot;  <a href='{$ibforums->base_url}act=Search&amp;CODE=getnew'>{$ibforums->lang['view_new_posts']}</a>  &middot;  <a href='javascript:buddy_pop();' title='{$ibforums->lang['bb_tool_tip']}'>{$ibforums->lang['l_qb']}</a></td>
   </tr>
  </table>
EOF;
}


function BoardHeader($time="") {
global $ibforums;
return <<<EOF
<script language='JavaScript' type="text/javascript">
<!--
function buddy_pop() { window.open('index.{$ibforums->vars['php_ext']}?act=buddy&s={$ibforums->session_id}','BrowserBuddy','width=250,height=500,resizable=yes,scrollbars=yes'); }
function chat_pop(cw,ch)  { window.open('index.{$ibforums->vars['php_ext']}?s={$ibforums->session_id}&act=chat&pop=1','Chat','width='+cw+',height='+ch+',resizable=yes,scrollbars=yes'); }
function multi_page_jump( url_bit, total_posts, per_page )
{
pages = 1; cur_st = parseInt("{$ibforums->input['st']}"); cur_page  = 1;
if ( total_posts % per_page == 0 ) { pages = total_posts / per_page; }
 else { pages = Math.ceil( total_posts / per_page ); }
msg = "{$ibforums->lang['tpl_q1']}" + " " + pages;
if ( cur_st > 0 ) { cur_page = cur_st / per_page; cur_page = cur_page -1; }
show_page = 1;
if ( cur_page < pages )  { show_page = cur_page + 1; }
if ( cur_page >= pages ) { show_page = cur_page - 1; }
 else { show_page = cur_page + 1; }
userPage = prompt( msg, show_page );
if ( userPage > 0  ) {
	if ( userPage < 1 )     {    userPage = 1;  }
	if ( userPage > pages ) { userPage = pages; }
	if ( userPage == 1 )    {     start = 0;    }
	else { start = (userPage - 1) * per_page; }
	window.location = url_bit + "&st=" + start;
}
}
//-->
</script>
 
<!--IBF.BANNER-->
<div id='logostrip'>
  <a href='{$ibforums->base_url}' title='Board Home'><img src='{$ibforums->vars['img_url']}/logo4.gif' alt='Powered by Invision Power Board' border="0" /></a>
</div>

<!-- IE6/Win TABLE FIX -->
<table  width="100%" cellspacing="6" id="submenu">
<tr>
 <td><a href='{$ibforums->vars['home_url']}'>{$ibforums->vars['home_name']}</a><!--IBF.RULES--></td>
 <td align="right">
   <img src="{$ibforums->vars['img_url']}/atb_help.gif" border="0" alt="" />&nbsp;<a href='{$ibforums->base_url}act=Help'>{$ibforums->lang['tb_help']}</a>
   &nbsp; &nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/atb_search.gif" border="0" alt="" />&nbsp;<a href='{$ibforums->base_url}act=Search&amp;f={$ibforums->input['f']}'>{$ibforums->lang['tb_search']}</a>
   &nbsp; &nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/atb_members.gif" border="0" alt="" />&nbsp;<a href='{$ibforums->base_url}act=Members'>{$ibforums->lang['tb_mlist']}</a>
   &nbsp; &nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/atb_calendar.gif" border="0" alt="" />&nbsp;<a href='{$ibforums->base_url}act=calendar'>{$ibforums->lang['tb_calendar']}</a>
   <!--IBF.CHATLINK-->
   <!--IBF.TSLLINK-->
 </td>
</tr>
</table>
<% MEMBER BAR %>
<br />

EOF;
}

function rules_link($url="", $title="")
{
global $ibforums;
return <<<EOF
&nbsp;&middot; <a href="$url">$title</a>
EOF;
}

function show_tsl_link_inline()
{
global $ibforums;
return <<<EOF
&nbsp; &nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/atb_toplist.gif" border="0" alt="" />&nbsp;<a href='{$ibforums->base_url}act=module&amp;module=toplist'>{$ibforums->lang['tb_toplist']}</a>
EOF;
}

function show_chat_link_inline()
{
global $ibforums;
return <<<EOF
&nbsp; &nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/atb_chat.gif" border="0" alt="" />&nbsp;<a href='{$ibforums->base_url}act=chat'>{$ibforums->lang['live_chat']}</a>
EOF;
}

function show_chat_link_popup()
{
global $ibforums;
return <<<EOF
&nbsp; &nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/atb_chat.gif" border="0" alt="" />&nbsp;<a href="javascript:chat_pop({$ibforums->vars['chat_width']}, {$ibforums->vars['chat_height']});">{$ibforums->lang['live_chat']}</a>
EOF;
}


function start_nav() {
global $ibforums;
return <<<EOF
<div id='navstrip' align='left'><{F_NAV}>&nbsp;
EOF;
}

function end_nav() {
global $ibforums;
return <<<EOF
</div>
<br />
EOF;
}





function Redirect($Text, $Url, $css) {
global $ibforums;
return <<<EOF
<html>
<head>
<title>{$ibforums->lang['stand_by']}</title>
<meta http-equiv='refresh' content='2; url=$Url' />
<script type="text/javascript"> </script>
$css
</head>
<body>
<table width='100%' height='85%' align='center'>
<tr>
  <td valign='middle'>
	  <table align='center' cellpadding="4" class="tablefill">
	  <tr> 
		<td width="100%" align="center">
		  {$ibforums->lang['thanks']}, 
		  $Text<br /><br />
		  {$ibforums->lang['transfer_you']}<br /><br />
	      (<a href='$Url'>{$ibforums->lang['dont_wait']}</a>)
	    </td>
	  </tr>
	</table>
  </td>
</tr>
</table>
</body>
</html>
EOF;
}

function PM_popup() {
global $ibforums;
return <<<EOF
     <script language='JavaScript' type="text/javascript">
     <!--
       window.open('index.{$ibforums->vars['php_ext']}?act=Msg&CODE=99&s={$ibforums->session_id}','NewPM','width=500,height=250,resizable=yes,scrollbars=yes'); 
     //-->
     </script>
EOF;
}

function admin_link() {
global $ibforums;
return <<<EOF
&nbsp;&middot; <b><a href='{$ibforums->vars['board_url']}/admin.{$ibforums->vars['php_ext']}' target='_blank'>{$ibforums->lang['admin_cp']}</a></b>
EOF;
}

function mod_link() {
global $ibforums;
return <<<EOF
&middot; <b><a href='{$ibforums->base_url}act=modcp&amp;forum={$ibforums->input['f']}'>{$ibforums->lang['mod_cp']}</a></b>
EOF;
}

function validating_link() {
global $ibforums;
return <<<EOF
&nbsp;&middot; <a href='{$ibforums->base_url}act=Reg&amp;CODE=reval'>{$ibforums->lang['ml_revalidate']}</a>
EOF;
}

function error_log_in($q_string) {
global $ibforums;
return <<<EOF
<form action='{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}' method='post'>
<input type='hidden' name='act' value='Login' />
<input type='hidden' name='CODE' value='01' />
<input type='hidden' name='s' value='{$ibforums->session_id}' />
<input type='hidden' name='referer' value='$q_string' />
<input type='hidden' name='CookieDate' value='1' />
<div class="tableborder">
  <div class="titlemedium">{$ibforums->lang['er_log_in_title']}</div>
  <table>
   <tr>
	<td class="pformleft">{$ibforums->lang['erl_enter_name']}</td>
	<td class="pformright"><input type='text' size='20' maxlength='64' name='UserName' class='forminput' /></td>
   </tr>
   <tr>
	<td class="pformleft">{$ibforums->lang['erl_enter_pass']}</td>
	<td class="pformright"><input type='password' size='20' name='PassWord' class='forminput' /></td>
   </tr>
  </table>
  <div class="pformstrip" align="center"><input type='submit' name='submit' value='{$ibforums->lang['erl_log_in_submit']}' class='forminput' /></div>
</div>
</form>
EOF;
}

function board_offline($message = "") {
global $ibforums;
return <<<EOF
<form action='{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}' method='post'>
<input type='hidden' name='act' value='Login' />
<input type='hidden' name='CODE' value='01' />
<input type='hidden' name='s' value='{$ibforums->session_id}' />
<input type='hidden' name='referer' value='' />
<input type='hidden' name='CookieDate' value='1' />
<div class='tableborder'>
  <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['offline_title']}</div>
  <div class='tablepad'>$message</div>
  <table width='100%' cellpadding='0' cellspacing='0'>
  <tr>
   <td class='pformleftw'>{$ibforums->lang['erl_enter_name']}</td>
   <td class='pformright'><input type='text' size='20' maxlength='64' name='UserName' class='forminput' /></td>
  </tr>
  <tr>
   <td class='pformleftw'>{$ibforums->lang['erl_enter_pass']}</td>
   <td class='pformright'><input type='password' size='20' name='PassWord' class='forminput' /></td>
  </tr>
  </table>
  <div class='pformstrip' align='center'><input type='submit' name='submit' value='{$ibforums->lang['erl_log_in_submit']}' class='forminput' /></div>
</div>
</form>
EOF;
}

function Error($message, $ad_email_one="", $ad_email_two="") {
global $ibforums;
return <<<EOF
<script language='JavaScript' type="text/javascript">
<!--
function contact_admin() {

  // Very basic spam bot stopper
	  
  admin_email_one = '$ad_email_one';
  admin_email_two = '$ad_email_two';
  
  window.location = 'mailto:'+admin_email_one+'@'+admin_email_two+'?subject=Error on the forums';
  
}

//-->
</script>
<div class="tableborder">
 <div class="maintitle"><img src='{$ibforums->vars['img_url']}/nav_m.gif' alt='' width='8' height='8' />&nbsp;{$ibforums->lang['error_title']}</div>
</div>
<div class="tablefill">
  {$ibforums->lang['exp_text']}<br /><br />
  <b>{$ibforums->lang['msg_head']}</b>
  <br /><br />
  <span class='postcolor' style='padding:10px'>$message</span>
  <br /><br />
  <!--IBF.LOG_IN_TABLE-->
  <!--IBF.POST_TEXTAREA-->
  <br /><br />
  <b>{$ibforums->lang['er_links']}</b>
  <br /><br />
  &middot; <a href='{$ibforums->base_url}act=Reg&amp;CODE=10'>{$ibforums->lang['er_lost_pass']}</a><br />
  &middot; <a href='{$ibforums->base_url}act=Reg&amp;CODE=00'>{$ibforums->lang['er_register']}</a><br />
  &middot; <a href='{$ibforums->base_url}act=Help&amp;CODE=00'>{$ibforums->lang['er_help_files']}</a><br />
  &middot; <a href='javascript:contact_admin();'>{$ibforums->lang['er_contact_admin']}</a>
</div>
<div class="tableborder">
 <div class="pformstrip" align="center">&lt; <a href='javascript:history.go(-1)'>{$ibforums->lang['error_back']}</a></div>
</div>
EOF;
}

function error_post_textarea($post="") {
global $ibforums;
return <<<EOF
<br />
<div>
<strong>{$ibforums->lang['err_title']}</strong>
<br /><br />
{$ibforums->lang['err_expl']}
</div>
<br />
<br />
<div align='center'>
<input type='button' tabindex='1' value='{$ibforums->lang['err_select']}' onclick='document.mehform.saved.select()'><br />
<form name='mehform'>
<textarea cols='70' rows='5' name='saved' tabindex='2'>$post</textarea>
</form>
</div>
EOF;
}


}
?>