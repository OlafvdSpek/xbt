<?php

class skin_csite {

function rules_link($url="", $title="")
{
global $ibforums;
return <<<EOF
&nbsp;&middot; <a href="$url">$title</a>
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
&nbsp; &nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/atb_chat.gif" border="0" alt="" />&nbsp;<a href="javascript:chat_pop();">{$ibforums->lang['live_chat']}</a>
EOF;
}


function csite_javascript() {
global $ibforums;
return <<<EOF
<script type="text/javascript">
<!--
function buddy_pop() { window.open('{$ibforums->js_base_url}act=buddy','BrowserBuddy','width=200,height=500,resizable=yes,scrollbars=yes'); }
function chat_pop()  { window.open('{$ibforums->js_base_url}act=chat&pop=1','Chat','width={$ibforums->vars['chat_width']},height={$ibforums->vars['chat_height']},resizable=yes,scrollbars=yes'); }
//-->
</script>
EOF;
}

function csite_css_inline($css="") {
global $ibforums;
return <<<EOF
<style type='text/css'>
{$css}
</style>
EOF;
}

function csite_sep_char() {
global $ibforums;
return <<<EOF
,
EOF;
}

function csite_css_external($css, $img) {
global $ibforums;
return <<<EOF
<style type='text/css' media="all">
@import url({$ibforums->vars['board_url']}/css.php?d={$css}_{$img}.css);
</style>
EOF;
}


function tmpl_links_wrap($link="", $name="") {
global $ibforums;
return <<<EOF
&middot; <a href='$link' style='text-decoration:none'>$name</a><br />
EOF;
}

function tmpl_welcomebox_member($pm_string="",$last_visit="", $name="", $return="") {
global $ibforums;
return <<<EOF
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> {$ibforums->lang['wbox_welcome']}, $name</div>
 <div class='tablepad'>
  <span class='desc'>$last_visit</span>
  <br />&middot; <a href="{$ibforums->base_url}act=Search&amp;CODE=getnew" style='text-decoration:none'>{$ibforums->lang['wbox_getnewposts']}</a>
  <br />&middot; <a href="{$ibforums->base_url}act=UserCP" style='text-decoration:none'>{$ibforums->lang['wbox_mycontrols']}</a>
  <br />&middot; <a href="javascript:buddy_pop();" style='text-decoration:none'>{$ibforums->lang['wbox_myassistant']}</a>
  <br />&middot; <a href="{$ibforums->base_url}act=Login&amp;CODE=03&amp;return=$return" style='text-decoration:none'>{$ibforums->lang['wbox_logout']}</a>
 </div>
</div>
<br />
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> <a href='{$ibforums->base_url}&act=Msg'>{$ibforums->lang['pm_title']}</a></div>
 <div class='tablepad'><span class='desc'>$pm_string</span></div>
</div>
EOF;
}

function tmpl_welcomebox_guest($top_string, $return) {
global $ibforums;
return <<<EOF
<form action="{$ibforums->base_url}act=Login&amp;CODE=01&amp;CookieDate=1&amp;return=$return" method="post">
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> {$ibforums->lang['wbox_welcome']}, {$ibforums->lang['wbox_guest_name']}</div>
 <div class='tablepad'>
 	<span class='desc'>$top_string</span>
 	<br /><br />&middot; <strong><a href="{$ibforums->base_url}act=Search&amp;CODE=getactive" style='text-decoration:none'>{$ibforums->lang['wbox_getnewposts']}</a></strong>
 	<br /><br /><span class='desc'>{$ibforums->lang['wbox_g_username']}</span>
 	<br /><input type='text' class='textinput' size='15' name='UserName' />
 	<br /><span class='desc'>{$ibforums->lang['wbox_g_password']}</span>
 	<br /><input type='password' class='textinput' size='15' name='PassWord' />
 	<br /><input type='submit' class='textinput' value='{$ibforums->lang['wbox_g_login']}' />
 </div>
</div>
</form>
EOF;
}


function tmpl_search() {
global $ibforums;
return <<<EOF
<br />
<form action='{$ibforums->base_url}act=Search&amp;CODE=01&amp;forums=all' method='post' name='search'>
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> {$ibforums->lang['search_title']}</div>
 <div class='tablepad' align='center'>
  <input type='text' name='keywords' value='' size='10' class='textinput' /><input type='submit' value='{$ibforums->lang['search_go']}' />
  <br /><a href='{$ibforums->base_url}act=Search&amp;mode=adv'>{$ibforums->lang['search_advanced']}</a>
 </div>
</div>
</form>
EOF;
}

function tmpl_sitenav($links) {
global $ibforums;
return <<<EOF
<br />
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> {$ibforums->lang['links_title']}</div>
 <div class='tablepad'>
  $links
 </div>
</div>
EOF;
}

function tmpl_affiliates($links) {
global $ibforums;
return <<<EOF
<br />
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> {$ibforums->lang['aff_title']}</div>
 <div class='tablepad'>
  $links
 </div>
</div>
EOF;
}

function tmpl_changeskin($select) {
global $ibforums;
return <<<EOF
<br />
<form action="{$ibforums->vars['dynamiclite']}&amp;s={$ibforums->session_id}&amp;setskin=1" method="post">
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> {$ibforums->lang['cskin_title']}</div>
 <div class='tablepad' align="center">
  <span class='desc'>{$ibforums->lang['cskin_text']}</span>
  <br />
  $select
  <br />
  <input type='submit' value='{$ibforums->lang['cskin_go']}' />
 </div>
</div>
</form>
EOF;
}

function tmpl_onlineusers($breakdown, $split, $names) {
global $ibforums;
return <<<EOF
<br />
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> <a href="{$ibforums->base_url}act=Online">{$ibforums->lang['online_title']}</a></div>
 <div class='tablepad'>
  <span class='desc'>$breakdown<br />$split<br />$names</span>
 </div>
</div>
EOF;
}

function tmpl_poll_header($question,$tid) {
global $ibforums;
return <<<EOF
<br />
<form action="{$ibforums->base_url}act=Poll&amp;t=$tid" method="post">
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> <a href="{$ibforums->base_url}showtopic=$tid">{$ibforums->lang['poll_title']}</a></div>
 <div class='pformstrip'>$question</div>
 <div class='tablepad'>
EOF;
}

function tmpl_poll_result_row($votes, $id, $choice, $percent, $width) {
global $ibforums;
return <<<EOF
  $choice
  <br /><img src='{$ibforums->vars['img_url']}/bar_left.gif' border='0' width='4' height='11' align='middle' alt='' /><img src='{$ibforums->vars['img_url']}/bar.gif' border='0' width='$width' height='11' align='middle' alt='' /><img src='{$ibforums->vars['img_url']}/bar_right.gif' border='0' width='4' height='11' align='middle' alt='' />
  <br />
EOF;
}

function tmpl_poll_choice_row($id, $choice) {
global $ibforums;
return <<<EOF
  <input type='radio' name='poll_vote' value='$id' class='radiobutton' />&nbsp;<strong>$choice</strong>
  <br />
EOF;
}

function tmpl_poll_footer($vote, $total, $tid) {
global $ibforums;
return <<<EOF
  <span class='desc'>
   &middot; <strong>$total</strong>
   <br />&middot; $vote
   <br />&middot; <a href="{$ibforums->base_url}showtopic=$tid">{$ibforums->lang['poll_discuss']}</a>
  </span>
  </div>
</div>
</form>
EOF;
}

function tmpl_poll_vote() {
global $ibforums;
return <<<EOF
<input type='submit' value='{$ibforums->lang['poll_vote']}' class='codebuttons' />
EOF;
}

function tmpl_latestposts($posts) {
global $ibforums;
return <<<EOF
<br />
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> {$ibforums->lang['discuss_title']}</div>
 $posts
</div>
EOF;
}

function tmpl_topic_row($tid, $title, $posts, $views, $mid, $mname, $date) {
global $ibforums;
return <<<EOF
<div class='row2' style='padding:3px'><strong><a href='{$ibforums->base_url}showtopic=$tid' style='text-decoration:none;font-size:10px'>$title</a></strong></div>
<div class='desc' style='padding:3px'>
 <a href='{$ibforums->base_url}showuser=$mid' style='text-decoration:none'>$mname</a> &#064; $date
 <br />{$ibforums->lang['recent_read']}: $views &nbsp; {$ibforums->lang['recent_comments']}: $posts
</div>
EOF;
}

function tmpl_recentarticles($articles) {
global $ibforums;
return <<<EOF
<br />
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}> {$ibforums->lang['recent_title']}</div>
 $articles
</div>
EOF;
}

function tmpl_comment_link($tid) {
global $ibforums;
return <<<EOF
<a href="{$ibforums->base_url}showtopic=$tid&amp;view=getlastpost">{$ibforums->lang['article_comment']}</a>
EOF;
}

function tmpl_readmore_link($tid) {
global $ibforums;
return <<<EOF
...<a href="{$ibforums->base_url}showtopic=$tid">{$ibforums->lang['article_readmore']}</a>
EOF;
}

function tmpl_articles($articles) {
global $ibforums;
return <<<EOF
$articles
EOF;
}

function tmpl_skin_select_top() {
global $ibforums;
return <<<EOF
<select name="skinid" class="forminput" onchange="window.location='{$ibforums->vars['dynamiclite']}&s={$ibforums->session_id}&setskin=1&skinid=' + this.value">
EOF;
}

function tmpl_skin_select_row($sid, $name, $used) {
global $ibforums;
return <<<EOF
<option value="$sid" $used>$name</option>
EOF;
}

function tmpl_skin_select_bottom() {
global $ibforums;
return <<<EOF
</select>
EOF;
}

function tmpl_articles_row($entry, $bottom_string, $read_more, $top_string) {
global $ibforums;
return <<<EOF
<table cellspacing="0" width="100%" class='tableborder'>
<tr>
 <td class='maintitle' colspan="2"><img src="{$ibforums->vars['img_url']}/cs_page.gif" alt="" border="0" />&nbsp;<a href="{$ibforums->base_url}showtopic={$entry['tid']}">{$entry['title']}</a></td>
</tr>
<tr>
 <td class='row2' colspan="2" style='padding:5px'>$top_string</td>
</tr>
<tr>
 <td class="post1" width="5%" valign="top" style="padding:5px">{$entry['avatar']}</td>
 <td class="post1" width="95%" valign="top" style="padding:5px">{$entry['post']} $read_more</td>
</tr>
<tr>
 <td class='row2' colspan="2" style='padding:5px' align='right'>
   $bottom_string
   &nbsp;&nbsp;<a href="{$ibforums->base_url}act=Print&amp;client=printer&amp;f={$entry['forum_id']}&amp;t={$entry['tid']}"><img src="{$ibforums->vars['img_url']}/cs_print.gif" alt="Print" border="0" /></a>
   <a href="{$ibforums->base_url}act=Forward&amp;f={$entry['forum_id']}&amp;t={$entry['tid']}"><img src="{$ibforums->vars['img_url']}/cs_email.gif" alt="email" border="0" /></a>
 </td>
</tr>
</table>
<br />
EOF;
}

function tmpl_wrap_avatar($avatar) {
global $ibforums;
return <<<EOF
$avatar
EOF;
}

function tmpl_debug($queries, $time) {
global $ibforums;
return <<<EOF
  <div class='desc'>[ DB Queries: $queries ] [ Execution Time: $time ]</div>
EOF;
}

function csite_skeleton_template() {
global $ibforums;
return <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<meta name="generator" content="IPDynamic Lite">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="no-cache">
	<meta http-equiv="Cache-Control" content="no-cache">
	<title><!--CS.TEMPLATE.TITLE--></title>
	<!--CS.TEMPLATE.JAVASCRIPT-->
	<!--CS.TEMPLATE.CSS-->
</head>
<body>
<div id='ipbwrapper'>
<!--Header-->
<div id='logostrip'>
  <a href='{$ibforums->base_url}' title='Board Home'><img src='{$ibforums->vars['img_url']}/logo4.gif' alt='Powered by Invision Power Board' border="0" /></a>
</div>
<table width="100%" cellspacing="6" id="submenu">
<tr>
 <td><a href='{$ibforums->vars['home_url']}'>{$ibforums->vars['home_name']}</a><!--IBF.RULES--></td>
 <td align="right">
   <img src="{$ibforums->vars['img_url']}/atb_help.gif" border="0" alt="" />&nbsp;<a href='{$ibforums->base_url}act=Help'>{$ibforums->lang['tb_help']}</a>
   &nbsp; &nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/atb_search.gif" border="0" alt="" />&nbsp;<a href='{$ibforums->base_url}act=Search&amp;f={$ibforums->input['f']}'>{$ibforums->lang['tb_search']}</a>
   &nbsp; &nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/atb_members.gif" border="0" alt="" />&nbsp;<a href='{$ibforums->base_url}act=Members'>{$ibforums->lang['tb_mlist']}</a>
   &nbsp; &nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/atb_calendar.gif" border="0" alt="" />&nbsp;<a href='{$ibforums->base_url}act=calendar'>{$ibforums->lang['tb_calendar']}</a>
   <!--IBF.CHATLINK-->
 </td>
</tr>
</table>
<!--CS.TEMPLATE.LINKS-->
<!--End Header-->
<!--Main Content-->
<table width="100%" class='tableborder' cellspacing="0" cellpadding="6">
<tr>
 <td width="200" class='row3' valign="top">
   <!-- LEFT -->
    <!--CS.TEMPLATE.WELCOMEBOX-->
    <!--CS.TEMPLATE.SEARCH-->
    <!--CS.TEMPLATE.SITENAV-->
    <!--CS.TEMPLATE.CHANGESKIN-->
    <!--CS.TEMPLATE.ONLINEUSERS-->
    <!--CS.TEMPLATE.POLL-->
    <!--CS.TEMPLATE.LATESTPOSTS-->
    <!--CS.TEMPLATE.RECENTARTICLES-->
    <!--CS.TEMPLATE.AFFILIATES-->
   <!--END LEFT-->
   <br /><img src='{$ibforums->vars['img_url']}/spacer.gif' alt='' width='200' height='1' />
 </td>
 <!--SPACER-->
 <td width="5" class='row3'>&nbsp;</td>
 <td width="90%" class='row3' valign="top">
   <!--MAIN-->
    
    <!--CS.TEMPLATE.ARTICLES-->
   
   <!--END MAIN-->
 </td>
</tr>
<!--End Main Content-->
<tr>
 <td colspan='3'  class='row3' align='center'>
 <!--CS.TEMPLATE.COPYRIGHT-->
 <!--CS.TEMPLATE.DEBUG-->
 </td>
</tr>
</table>
<div class='titlemedium'>&nbsp;</div>
</div>
</body>
</html>
EOF;
}


}
?>