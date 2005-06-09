<?php

class skin_forum {

function show_page_jump($total, $pp, $qe) {
global $ibforums;
return <<<EOF
<a href="javascript:multi_page_jump( $total, $pp, '$qe' )" title="{$ibforums->lang['tpl_jump']}">{$ibforums->lang['multi_page_forum']}</a>
EOF;
}


function Forum_log_in($data) {
global $ibforums;
return <<<EOF
<form action='{$ibforums->base_url};act=SF&amp;f=$data' method='post'>
<input type='hidden' name='act' value='SF'>
<input type='hidden' name='f' value='$data'>
<input type='hidden' name='L' value='1'>
<input type='hidden' name='s' value='{$ibforums->session_id}'>
<div class='tableborder'>
  <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['need_password']}</div>
  <div class='tablepad'>{$ibforums->lang['need_password_txt']}</div>
  <div class='tablepad' style='text-align:center'>
    <strong>{$ibforums->lang['enter_pass']}</strong>
    <br />
    <input type='password' size='20' name='f_password' />
  </div>
  <div class='pformstrip' align='center'><input type='submit' value='{$ibforums->lang['f_pass_submit']}' class='forminput' /></div>
</div>
</form>
EOF;
}


function show_sub_link($fid) {
global $ibforums;
return <<<EOF
		&#0124; <a href='{$ibforums->base_url}act=Track&amp;f=$fid&amp;type=forum'>{$ibforums->lang['ft_title']}</a>
EOF;
}

function show_mod_link($fid) {
global $ibforums;
return <<<EOF
<br />
<strong>{$ibforums->lang['post_modq']} <a href='{$ibforums->base_url}act=modcp'>{$ibforums->lang['post_click']}</a></strong>
EOF;
}

function PageTop($data) {
global $ibforums;
return <<<EOF
<script language='javascript' type="text/javascript">
<!--
	function who_posted(tid)
	{
		window.open("{$ibforums->js_base_url}act=Stats&CODE=who&t="+tid, "WhoPosted", "toolbar=no,scrollbars=yes,resizable=yes,width=230,height=300");
	}
//-->
</script>

<!--IBF.SUBFORUMS-->
<!--IBF.MODLINK-->
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
 <td align='left' width="20%" nowrap="nowrap">{$data['SHOW_PAGES']}</td>
 <td align='right' width="80%"><a href='{$ibforums->base_url}act=Post&amp;CODE=00&amp;f={$data['id']}'><{A_POST}></a>{$data[POLL_BUTTON]}</td>
</tr>
</table>
<br />

	<form action='{$ibforums->base_url}act=SF&amp;f={$data['id']}&amp;st={$ibforums->input['st']}' method='post'>
      <div class="tableborder">
        <div class='maintitle'><{CAT_IMG}>&nbsp;{$data['name']}</div>
		<table width='100%' border='0' cellspacing='1' cellpadding='4'>
		  <tr>
			<td align='center' class='titlemedium'><img src='{$ibforums->vars['img_url']}/spacer.gif' alt='' width='20' height='1' /></td>
			<td align='center' class='titlemedium'><img src='{$ibforums->vars['img_url']}/spacer.gif' alt='' width='20' height='1' /></td>
			<th width='45%' align='left' nowrap="nowrap" class='titlemedium'>{$ibforums->lang['h_topic_title']}</th>
			<th class='titlemedium'>L
			<th class='titlemedium'>S
			<th width='14%' align='center' nowrap="nowrap" class='titlemedium'>{$ibforums->lang['h_topic_starter']}</th>
			<th width='7%' align='center' nowrap="nowrap" class='titlemedium'>{$ibforums->lang['h_replies']}</th>
			<th width='7%' align='center' nowrap="nowrap" class='titlemedium'>{$ibforums->lang['h_hits']}</th>
			<th width='27%' align='left' nowrap="nowrap" class='titlemedium'>{$ibforums->lang['h_last_action']}</th>
		  </tr>
        <!-- Forum page unique top -->
EOF;
}

function TableEnd($data) {
global $ibforums;
return <<<EOF
      </table>

      <!--IBF.FORUM_ACTIVE-->

      <div align='center' class='darkrow2' style='padding:4px'>{$ibforums->lang['showing_text']}{$ibforums->lang['sort_text']}&nbsp;<input type='submit' value='{$ibforums->lang['sort_submit']}' class='forminput' /></div>
	</div>
</form>

<br />

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
 <td align='left' width="20%" nowrap="nowrap">{$data['SHOW_PAGES']}</td>
 <td align='right' width="80%"><a href='{$ibforums->base_url}act=Post&amp;CODE=00&amp;f={$data['id']}'><{A_POST}></a>{$data[POLL_BUTTON]}</td>
</tr>
</table>

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

<div align='right'>
    <form action='{$ibforums->base_url}' method='post' name='search'>
	<input type='hidden' name='forums' value='{$data['id']}' />
	<input type='hidden' name='cat_forum' value='forum' />
	<input type='hidden' name='act' value='Search' />
	<input type='hidden' name='joinname' value='1' />
	<input type='hidden' name='CODE' value='01' />
	{$ibforums->lang['search_forum']}&nbsp;
	<input type='text' size='30' name='keywords' class='forminput' value='{$ibforums->lang['enter_keywords']}' onfocus='this.value = "";' /><input type='submit' value='{$ibforums->lang['search_go']}' class='forminput' />
   </form>
   <br />
   <br />
   {$data[FORUM_JUMP]}
</div>
<br />
<br />
<div align='center'><a href='{$ibforums->base_url}act=Login&amp;CODE=04&amp;f={$data['id']}'>{$ibforums->lang['mark_as_read']}</a> <!--IBF.SUB_FORUM_LINK--></div>
<br clear="all" />


EOF;
}

function show_rules($rules) {
global $ibforums;
return <<<EOF
<div class='tableborder'>
  <div class='maintitle'>{$rules['title']}</div>
  <div class='tablepad'>{$rules['body']}</div>
  <div class='pformstrip' align='center'>&gt;&gt;<a href='{$ibforums->base_url}act=SF&amp;f={$rules['fid']}'>{$ibforums->lang['back_to_forum']}</a></div>
</div>
EOF;
}

function page_title($title="", $pages="") {
global $ibforums;
return <<<EOF
<div><span class='pagetitle'>$title</span>$pages</div>
EOF;
}




function forum_active_users($active=array()) {
global $ibforums;
return <<<EOF
	  <div class='darkrow2' style='padding:6px'>{$ibforums->lang['active_users_title']} ({$ibforums->lang['active_users_detail']})</div>
	  <div class='row2' style='padding:6px'>{$ibforums->lang['active_users_members']} {$active['names']}</div>
EOF;
}



function show_no_matches() {
global $ibforums;
return <<<EOF
				<tr>
					<td class='row4' colspan='7' align='center'>
						<br />
                         <b>{$ibforums->lang['no_topics']}</b>
						<br /><br />
					</td>
        </tr>
EOF;
}


function who_link($tid, $posts) {
global $ibforums;
return <<<EOF
    <a href='javascript:who_posted($tid);'>$posts</a>
EOF;
}

function RenderRow($data) {
global $ibforums;
return <<<EOF
    <!-- Begin Topic Entry {$data['tid']} -->
    <tr>
	  <td align='center' class='row4'>{$data['folder_img']}</td>
      <td align='center' class='row2'>{$data['topic_icon']}</td>
      <td class='row4'>
        {$data['go_new_post']}{$data['prefix']} <a href="{$ibforums->base_url}showtopic={$data['tid']}" title="{$ibforums->lang['topic_started_on']} {$data['start_date']}">{$data['title']}</a>  {$data[PAGES]}
        <br /><span class='desc'>{$data['description']}</span></td>
      <td class='row4' align=right>{$data['leechers']}
      <td class='row4' align=right>{$data['seeders']}
      <td align='center' class='row2'>{$data['starter']}</td>
      <td align='center' class='row4'>{$data['posts']}</td>
      <td align='center' class='row2'>{$data['views']}</td>
      <td class='row2'><span class='desc'>{$data['last_post']}<br /><a href='{$ibforums->base_url}showtopic={$data['tid']}&amp;view=getlastpost'>{$data['last_text']}</a> <b>{$data['last_poster']}</b></span></td>
    </tr>
    <!-- End Topic Entry {$data['tid']} -->
EOF;
}

function render_pinned_start() {
global $ibforums;
return <<<EOF
    <!-- START PINNED -->
    <tr>
      <td align='center' class='darkrow1'>&nbsp;</td>
      <td align='center' class='darkrow1'>&nbsp;</td>
	  <td align='left' class='darkrow1' colspan='5' style='padding:6px'><b>{$ibforums->lang['pinned_start']}</b></td>
    </tr>
EOF;
}

function render_pinned_end() {
global $ibforums;
return <<<EOF
    <!-- END PINNED -->
    <tr>
      <td align='center' class='darkrow1'>&nbsp;</td>
      <td align='center' class='darkrow1'>&nbsp;</td>
	  <td align='left' class='darkrow1' colspan='5' style='padding:6px'><b>{$ibforums->lang['regular_topics']}</b></td>
    </tr>
EOF;
}


function render_pinned_row($data) {
global $ibforums;
return <<<EOF
    <!-- Begin Pinned Topic Entry {$data['tid']} -->
    <tr>
	  <td align='center' class='row4'>{$data['folder_img']}</td>
      <td align='center' class='row2'>{$data['topic_icon']}</td>
      <td class='row4'>
       {$data['go_new_post']}<b>{$data['prefix']} <a href='{$ibforums->base_url}showtopic={$data['tid']}' class='linkthru' title='{$ibforums->lang['topic_started_on']} {$data['start_date']}'>{$data['title']}</a></b>  {$data[PAGES]}
        <br /><span class='desc'>{$data['description']}</span></td>
      <td class='row4'>
      <td class='row4'>
      <td align='center' class='row4'>{$data['starter']}</td>
      <td align='center' class='row4'>{$data['posts']}</td>
      <td align='center' class='row4'>{$data['views']}</td>
      <td class='row4'><span class='desc'>{$data['last_post']}<br /><a href='{$ibforums->base_url}showtopic={$data['tid']}&amp;view=getlastpost'>{$data['last_text']}</a> <b>{$data['last_poster']}</b></span></td>
    </tr>
    <!-- End Pinned Topic Entry {$data['tid']} -->
EOF;
}


}
?>