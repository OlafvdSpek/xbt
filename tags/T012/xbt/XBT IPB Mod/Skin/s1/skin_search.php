<?php

class skin_search {


  
function RenderRow($Data) {
global $ibforums;
return <<<EOF
    <!-- Begin Topic Entry {$Data['tid']} -->
    <tr> 
	  <td align='center' class='row4'>{$Data['folder_img']}</td>
      <td align='center' width='3%' class='row2'>{$Data['topic_icon']}</td>
      <td class='row4'>
	  <table width='100%' border='0' cellspacing='0' cellpadding='0'>
		  <tr> 
			<td valign='middle'>{$Data['go_new_post']}</td>
            <td width='100%'>{$Data['prefix']} <a href='{$ibforums->base_url}showtopic={$Data['tid']}&amp;hl={$Data['keywords']}'>{$Data['title']}</a>  {$Data[PAGES]}</td>
          </tr>
        </table>
        <span class='desc'>{$Data['description']}</span></td>
      <td class='row4' width='20%' align='center'><a href="{$ibforums->base_url}showforum={$Data['forum_id']}">{$Data['forum_name']}</a></td>
      <td align='center' class='row2'>{$Data['starter']}</td>
      <td align='center' class='row4'>{$Data['posts']}</td>
      <td align='center' class='row2'>{$Data['views']}</td>
      <td class='row2'>{$Data['last_post']}<br /><a href='{$ibforums->base_url}showtopic={$Data['tid']}&amp;view=getlastpost'>{$Data['last_text']}</a> <b>{$Data['last_poster']}</b></td>
    </tr>
    <!-- End Topic Entry {$Data['tid']} -->
EOF;
}

function RenderPostRow($Data) {
global $ibforums;
return <<<EOF
<br />
<div class="tableborder">
  <div class="maintitle">{$Data['folder_img']}&nbsp;{$Data['prefix']} <a href='{$ibforums->base_url}showtopic={$Data['tid']}&amp;hl={$Data['keywords']}' class='linkthru'>{$Data['title']}</a></span></b>  {$Data[PAGES]}</div>
  <table class="tablebasic" cellpadding="6" cellspacing="1" width='100%'>
  <tr>
	<td width='150' align='left' class='row4'><span class='normalname'>{$Data['author_name']}</span></td>
	<td class='row4' width="100%"><strong>{$ibforums->lang['rp_postedon']} {$Data['post_date']}</strong></td>
  </tr>
  <tr>
	<td class='post1' align='left'>
	  <img src='{$ibforums->vars['img_url']}/spacer.gif' alt='' width='150' height='15' />
	  <br />
	  <span class='postdetails'>{$ibforums->lang['rp_replies']} <b>{$Data['posts']}</b><br />{$ibforums->lang['rp_hits']} <b>{$Data['views']}</b></span>
	</td>
	<td class='post1' align='left' width="100%">{$Data['post']}</td>
  </tr>
   <tr>
	<td class='row4'>&nbsp;</td>
	<td class='row4'>{$ibforums->lang['rp_forum']} <a href="{$ibforums->base_url}showforum={$Data['forum_id']}">{$Data['forum_name']}</a>&nbsp;&nbsp;&middot;&nbsp;&nbsp;{$ibforums->lang['rp_post']} <a href='{$ibforums->base_url}act=ST&amp;f={$Data['forum_id']}&amp;t={$Data['tid']}&amp;hl={$Data['keywords']}&amp;view=findpost&amp;p={$Data['pid']}' class='linkthru'>#{$Data['pid']}</a></td>
  </tr>
  </table>
</div>
EOF;
}

function start_as_post($Data) {
global $ibforums;
return <<<EOF
<div>{$Data[SHOW_PAGES]}</div>

EOF;
}

function end_as_post($Data) {
global $ibforums;
return <<<EOF
<br />
<div>{$Data[SHOW_PAGES]}</div>
<div align='left' class="wrapmini">
	<{B_NEW}>&nbsp;&nbsp;{$ibforums->lang['pm_open_new']}
	<br /><{B_NORM}>&nbsp;&nbsp;{$ibforums->lang['pm_open_no']}
	<br /><{B_HOT}>&nbsp;&nbsp;{$ibforums->lang['pm_hot_new']}
	<br /><{B_HOT_NN}>&nbsp;&nbsp;{$ibforums->lang['pm_hot_no']}
</div>

<div align='left' class="wrapmini">
	<{B_POLL}>&nbsp;&nbsp;{$ibforums->lang['pm_poll']}
	<br /><{B_POLL_NN}>&nbsp;&nbsp;{$ibforums->lang['pm_poll_no']}
	<br /><{B_LOCKED}>&nbsp;&nbsp;{$ibforums->lang['pm_locked']}
	<br /><{B_MOVED}>&nbsp;&nbsp;{$ibforums->lang['pm_moved']}
</div>
EOF;
}


function end($Data) {
global $ibforums;
return <<<EOF
</table>
<div class="titlemedium">&nbsp;</div>
</div>
<br />
<div>{$Data[SHOW_PAGES]}</div>
<br />
<div align='left' class="wrapmini">
	<{B_NEW}>&nbsp;&nbsp;{$ibforums->lang['pm_open_new']}
	<br /><{B_NORM}>&nbsp;&nbsp;{$ibforums->lang['pm_open_no']}
	<br /><{B_HOT}>&nbsp;&nbsp;{$ibforums->lang['pm_hot_new']}
	<br /><{B_HOT_NN}>&nbsp;&nbsp;{$ibforums->lang['pm_hot_no']}
</div>

<div align='left' class="wrapmini">
	<{B_POLL}>&nbsp;&nbsp;{$ibforums->lang['pm_poll']}
	<br /><{B_POLL_NN}>&nbsp;&nbsp;{$ibforums->lang['pm_poll_no']}
	<br /><{B_LOCKED}>&nbsp;&nbsp;{$ibforums->lang['pm_locked']}
	<br /><{B_MOVED}>&nbsp;&nbsp;{$ibforums->lang['pm_moved']}
</div>
<br />
<br clear="all" />
EOF;
}


function boolean_explain_page() {
global $ibforums;
return <<<EOF
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['be_link']}</div>
 <table width='100%' cellpadding='0' cellspacing='1'>
 <tr>
  <th width='30%' class='pformstrip'>{$ibforums->lang['be_use']}</th>
  <th width='70%' class='pformstrip'>{$ibforums->lang['be_means']}</th>
 </tr>
 <tr>
  <td class='pformleft'>{$ibforums->lang['be_u1']}</td>
  <td class='pformleft'>{$ibforums->lang['be_m1']}</td>
 </tr>
 <tr>
  <td class='pformleft'>{$ibforums->lang['be_u2']}</td>
  <td class='pformleft'>{$ibforums->lang['be_m2']}</td>
 </tr>
 <tr>
  <td class='pformleft'>{$ibforums->lang['be_u3']}</td>
  <td class='pformleft'>{$ibforums->lang['be_m3']}</td>
 </tr>
 <tr>
  <td class='pformleft'>{$ibforums->lang['be_u4']}</td>
  <td class='pformleft'>{$ibforums->lang['be_m4']}</td>
 </tr>
 <tr>
  <td class='pformleft'>{$ibforums->lang['be_u5']}</td>
  <td class='pformleft'>{$ibforums->lang['be_m5']}</td>
 </tr>
 </table>
</div> 
EOF;
}

function boolean_explain_link() {
global $ibforums;
return <<<EOF
&#091; <a href='#' title='{$ibforums->lang['be_ttip']}' onclick='win_pop()'>{$ibforums->lang['be_link']}</a> &#093;
EOF;
}

function simple_form($forums) {
global $ibforums;
return <<<EOF
<script type="text/javascript">
<!--
function go_gadget_advanced()
{
	window.location = "{$ibforums->js_base_url}act=Search&mode=adv&f={$ibforums->input['f']}";
}
function win_pop()
{
    window.open('{$ibforums->js_base_url}act=Search&CODE=explain','WIN','width=400,height=300,resizable=yes,scrollbars=yes'); 
}
-->
</script>
<form action="{$ibforums->base_url}act=Search&amp;CODE=simpleresults&amp;mode=simple" method="post" name='sForm'>
$hidden_fields
<div class="tableborder">
  <div class="maintitle"  align='center'>{$ibforums->lang['search_options']}</div>
  <div class="pformstrip" align="center">{$ibforums->lang['key_search']}</div>
  <div class="tablepad" align="center">
    <input type='text' maxlength='100' size='40' id="keywords" name='keywords' class='forminput' />
	<br />
	<label for="keywords">{$ibforums->lang['keysearch_text']}</label> <!--IBF.BOOLEAN_EXPLAIN-->
  </div>
  <div class="pformstrip" align="center">{$ibforums->lang['search_where']}</div>
   <div class="tablepad" align="center">
    $forums
    <br /><br />
    <strong>{$ibforums->lang['sf_show_me']}</strong>
      <input type="radio" name="sortby" value="relevant" id="sortby_one" checked="checked" class="radiobutton" />
      <label for="sortby_one">{$ibforums->lang['sf_most_r_f']}</label>
      &nbsp;
      <input type="radio" name="sortby" value="date" id="sortby_two" class="radiobutton" />
      <label for="sortby_two">{$ibforums->lang['sf_most_date']}</label>
   </div>
  <div class="pformstrip" align="center">
    <input type='submit' value='{$ibforums->lang['do_search']}' class='forminput' />
    &nbsp;
    <input type='button' value='{$ibforums->lang['so_more_opts']}' onclick="go_gadget_advanced()" class='forminput' />
  </div>
</div>
</form>
    
EOF;
}

function result_simple_header($data) {
global $ibforums;
return <<<EOF
<div class="plainborder">
  <div class="maintitle"><{CAT_IMG}>&nbsp;{$ibforums->lang['search_results']}</div>
  <div class="pformstrip">
	<div class="pagelinks">{$ibforums->lang['g_searched_for']} <strong>{$data['keyword']}</strong></div>
	<div align="right">
	   {$ibforums->lang['g_results']} <strong>{$data['start']} - {$data['end']}</strong> {$ibforums->lang['g_of_about']} <strong>{$data['matches']}</strong>.
	   {$ibforums->lang['g_search_took']} <strong>{$data['ex_time']}</strong> {$ibforums->lang['g_seconds']}
	</div>
  </div>
  <br />
EOF;
}

function result_simple_footer($data) {
global $ibforums;
return <<<EOF
  <div class="pformstrip" align="left">{$ibforums->lang['search_pages']} &nbsp;  &nbsp; &nbsp;<span class="googlepagelinks">{$data['links']}</span></div>
</div>
EOF;
}

function result_simple_entry($data) {
global $ibforums;
return <<<EOF
  <div class="{$data['css_class']}">
  <span class="googlish"><a href='{$ibforums->base_url}act=ST&amp;t={$data['tid']}&amp;f={$data['id']}&amp;view=findpost&amp;p={$data['pid']}'>{$data['title']}</span></a>
  <br />
  {$data['post']}
  <br />
  <span class='googlesmall'>
  {$ibforums->lang['location_g']}: <a href='{$ibforums->base_url}act=idx'>{$ibforums->lang['g_b_home']}</a>
  &gt; <a href='{$ibforums->base_url}act=SC&amp;c={$data['cat_id']}'>{$data['cat_name']}</a>
  &gt; <a href='{$ibforums->base_url}act=SF&amp;f={$data['id']}'>{$data['name']}</a>
  </span>
  <br />
  <span class="googlebottom"><strong>{$ibforums->lang['g_relevance']}: {$data['relevance']}% &middot; Author: {$data['author_name']} &middot; Posted on: {$data['post_date']}</strong></span>
  <span class="googlesmall"> - <a href='{$ibforums->base_url}act=ST&amp;t={$data['tid']}&amp;f={$data['id']}&amp;view=findpost&amp;p={$data['pid']}' target='_blank'>{$ibforums->lang['g_new_window']}</a></span>
  </div>
  <br />
EOF;
}



function Form($forums) {
global $ibforums;
return <<<EOF
<script type="text/javascript">
<!--
function go_gadget_simple()
{
	window.location = "{$ibforums->js_base_url}act=Search&mode=simple&f={$ibforums->input['f']}";
}
function win_pop()
{
    window.open('{$ibforums->js_base_url}act=Search&CODE=explain','WIN','width=400,height=300,resizable=yes,scrollbars=yes'); 
}
-->
</script>
<form action="{$ibforums->base_url}act=Search&amp;CODE=01" method="post" name='sForm'>
$hidden_fields
<div class="tableborder">
<table cellpadding='4' cellspacing='0' border='0' width='100%'>
<tr>
	<td colspan='2' class="maintitle"  align='center'>{$ibforums->lang['keywords_title']}</td>
</tr>
<tr>
	<td class='pformstrip' width='50%'>{$ibforums->lang['key_search']}</td>
	<td class='pformstrip' width='50%'>{$ibforums->lang['mem_search']}</td>
</tr>
<tr>
	<td class='row1' valign='top'>
	  <input type='text' maxlength='100' size='40' name='keywords' id="keywords" class='forminput' />
	  <br /><br />
	  <label for="keywords">{$ibforums->lang['keysearch_text']}</label> <!--IBF.BOOLEAN_EXPLAIN-->
	</td>
	<td class='row1' valign='top'>
	<table width='100%' cellpadding='4' cellspacing='0' border='0' align='center'>
	<tr>
	 <td><input type='text' maxlength='100' size='50' name='namesearch' class='forminput' /></td>
	</tr>
	<tr>
	<td width='40%'><input type='checkbox' name='exactname' id='matchexact' value='1' class="checkbox" /><label for="matchexact">{$ibforums->lang['match_name_ex']}</label></td>
   </tr>
</table>
</td>
</tr>
</table>
</div>
<br />
<div class="tableborder">
<table cellpadding='4' cellspacing='0' border='0' width='100%'>         
<tr>
	<td colspan='2' class="maintitle"  align='center'>{$ibforums->lang['search_options']}</td>
</tr>

<tr>
	<td class='pformstrip' width='50%' valign='middle'>{$ibforums->lang['search_where']}</td>
	<td class='pformstrip' width='50%' valign='middle'>{$ibforums->lang['search_refine']}</td>
</tr>

<tr>
	<td class='row1' valign='middle'>
	  $forums
	  <br />
	  <input type='checkbox' name='searchsubs' value='1' id="searchsubs" checked="checked" />&nbsp;<label for="searchsubs">{$ibforums->lang['search_in_subs']}</label>
	</td>
	<td class='row1' valign='top'>
		<table cellspacing='4' cellpadding='0' width='100%' align='center' border='0'>
		<tr>
		 <td valign='top'>
		   <fieldset class="search">
		     <legend><strong>{$ibforums->lang['search_from']}</strong></legend>
			 <select name='prune' class='forminput'>
			 <option value='1'>{$ibforums->lang['today']}</option>
			 <option value='7'>{$ibforums->lang['this_week']}</option>
			 <option value='30' selected="selected">{$ibforums->lang['this_month']}</option>
			 <option value='60'>{$ibforums->lang['this_60']}</option>
			 <option value='90'>{$ibforums->lang['this_90']}</option>
			 <option value='180'>{$ibforums->lang['this_180']}</option>
			 <option value='365'>{$ibforums->lang['this_year']}</option>
			 <option value='0'>{$ibforums->lang['ever']}</option>
			 </select>
			 <br />
			 <input type='radio' name='prune_type' id="prune_older" value='older' class='radiobutton' />&nbsp;<label for="prune_older">{$ibforums->lang['older']}</label>
			 <br />
			 <input type='radio' name='prune_type' id="prune_newer" value='newer' class='radiobutton' checked="checked" />&nbsp;<label for="prune_newer">{$ibforums->lang['newer']}</label>
		  </fieldset>
		</td>
		<td valign='top'>
		  <fieldset class="search">
		     <legend><strong>{$ibforums->lang['sort_results']}</strong></legend>
			 <select name='sort_key' class='forminput'>
			 <option value='last_post'>{$ibforums->lang['last_date']}</option>
			 <option value='posts'>{$ibforums->lang['number_topics']}</option>
			 <option value='starter_name'>{$ibforums->lang['poster_name']}</option>
			 <option value='forum_id'>{$ibforums->lang['forum_name']}</option>
			 </select>
			 <br /><input type='radio' name='sort_order' id="sort_desc" class="radiobutton" value='desc' checked="checked" /><label for="sort_desc">{$ibforums->lang['descending']}</label>
			 <br /><input type='radio' name='sort_order' id="sort_asc" class="radiobutton" value='asc' /><label for="sort_asc">{$ibforums->lang['ascending']}</label>
		  </fieldset>
		</td>
		</tr>
		<tr>
		 <td nowrap="nowrap">
		   <fieldset class="search">
		     <legend><strong>{$ibforums->lang['search_where']}</strong></legend>
			 <input type='radio' name='search_in' class="radiobutton" id="search_in_posts" value='posts' checked="checked" /><label for="search_in_posts">{$ibforums->lang['in_posts']}</label>
			 <br />
			 <input type='radio' name='search_in' class="radiobutton" id="search_in_titles" value='titles' /><label for="search_in_titles">{$ibforums->lang['in_topics']}</label>
		   </fieldset>
		 </td>
		 <td>
		    <fieldset class="search">
		     <legend><strong>{$ibforums->lang['result_type']}</strong></legend>
		     <input type='radio' name='result_type' class="radiobutton" value='topics' id="result_topics" checked="checked" /><label for="result_topics">{$ibforums->lang['results_topics']}</label>
		     <br />
		     <input type='radio' name='result_type' class="radiobutton" value='posts' id="result_posts" /><label for="result_posts">{$ibforums->lang['results_post']}</label>
		   </fieldset>
		 </td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td class='pformstrip' colspan='2' align='center'><input type='submit' value='{$ibforums->lang['do_search']}' class='forminput' /><!--IBF.SIMPLE_BUTTON--></td>
</tr>
</table>
</div>
</form>
    
EOF;
}
function form_simple_button() {
global $ibforums;
return <<<EOF
&nbsp;<input type='button' value='{$ibforums->lang['so_less_opts']}' onclick="go_gadget_simple()" class='forminput' />
EOF;
}


function active_start($data) {
global $ibforums;
return <<<EOF
<script language='Javascript' type="text/javascript">
<!--
function checkvalues() {
   f = document.dateline;
   if (f.st_day.value < f.end_day.value) {
	   alert("{$ibforums->lang['active_js_error']}");
	   return false;
   }
   if (f.st_day.value == f.end_day.value) {
	   alert("{$ibforums->lang['active_js_error']}");
	   return false;
   }
}
-->
</script>
<br />
<form action='{$ibforums->base_url}act=Search&amp;CODE=getactive' method='post' name='dateline' onsubmit='return checkvalues();'>
<div class="pagelinks">{$data['SHOW_PAGES']}</div>
<div align="right" style="width:35%;text-align:center;margin-right:0;margin-left:auto">
 <fieldset class="search">
   <legend><strong>{$ibforums->lang['active_st_text']}</strong></legend>
   <label for='st_day'>{$ibforums->lang['active_mid_text']}</label>&nbsp;
   <select name='st_day' id='st_day' class='forminput'>
	<option value='s1'>{$ibforums->lang['active_yesterday']}</option>
	<option value='s2'>2 {$ibforums->lang['active_days']}</option>
	<option value='s3'>3 {$ibforums->lang['active_days']}</option>
	<option value='s4'>4 {$ibforums->lang['active_days']}</option>
	<option value='s5'>5 {$ibforums->lang['active_days']}</option>
	<option value='s6'>6 {$ibforums->lang['active_days']}</option>
	<option value='s7'>{$ibforums->lang['active_week']}</option>
	<option value='s30'>{$ibforums->lang['active_month']}</option>
   </select>
   &nbsp;
   <label for='end_day'>{$ibforums->lang['active_end_text']}</label>&nbsp;
   <select name='end_day' id='end_day' class='forminput'>
	<option value='e0'>{$ibforums->lang['active_today']}</option>
	<option value='e1'>{$ibforums->lang['active_yesterday']}</option>
	<option value='e2'>2 {$ibforums->lang['active_days']}</option>
	<option value='e3'>3 {$ibforums->lang['active_days']}</option>
	<option value='e4'>4 {$ibforums->lang['active_days']}</option>
	<option value='e5'>5 {$ibforums->lang['active_days']}</option>
	<option value='e6'>6 {$ibforums->lang['active_days']}</option>
	<option value='e7'>{$ibforums->lang['active_week']}</option>
   </select>
   &nbsp;
   <input type='submit' value='&gt;&gt;' title="{$ibforums->lang['active_label']}" class='forminput'>
 </fieldset>
</div>
</form>
<br />
<div class="tableborder">
  <div class="maintitle"><{CAT_IMG}>{$ibforums->lang['active_topics']}</div>
  <table class="tablebasic" cellspacing="1" cellpadding="4">
	<tr>
	   <td class='titlemedium' colspan='2' >&nbsp;</td>
	   <th align='left' class='titlemedium'>{$ibforums->lang['h_topic_title']}</th>
	   <th align='center' class='titlemedium'>{$ibforums->lang['h_forum_name']}</th>
	   <th align='center' class='titlemedium'>{$ibforums->lang['h_topic_starter']}</th>
	   <th align='center' class='titlemedium'>{$ibforums->lang['h_replies']}</th>
	   <th align='center' class='titlemedium'>{$ibforums->lang['h_hits']}</th>
	   <th class='titlemedium'>{$ibforums->lang['h_last_action']}</th>
	</tr>
EOF;
}


function start($Data) {
global $ibforums;
return <<<EOF
<div>{$Data[SHOW_PAGES]}</div>
<br />
<div class="tableborder">
<div class="maintitle"><{CAT_IMG}>&nbsp;{$ibforums->lang['your_results']} &middot; <a style="text-decoration:underline" href="{$ibforums->base_url}act=Login&amp;CODE=05">{$ibforums->lang['mark_search_as_read']}</a></div>
<table class="tablebasic" cellpadding="2" cellspacing="1" width='100%'>
  <tr>
	 <td class='titlemedium' colspan='2'>&nbsp;</td>
	 <td align='left' class='titlemedium'>{$ibforums->lang['h_topic_title']}</td>
	 <td align='center' class='titlemedium'>{$ibforums->lang['h_forum_name']}</td>
	 <td align='center' class='titlemedium'>{$ibforums->lang['h_topic_starter']}</td>
	 <td align='center' class='titlemedium'>{$ibforums->lang['h_replies']}</td>
	 <td align='center' class='titlemedium'>{$ibforums->lang['h_hits']}</td>
	 <td class='titlemedium'>{$ibforums->lang['h_last_action']}</td>
  </tr>
EOF;
}

function active_none() {
global $ibforums;
return <<<EOF
<tr><td colspan='8' class='row1' align='center'><strong>{$ibforums->lang['active_no_topics']}</strong></td></tr>
EOF;
}


}
?>