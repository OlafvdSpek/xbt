<?php

class skin_boards {

function whoschatting_show($total, $names, $link, $txt) {
global $ibforums;
return <<<EOF
        <tr>
           <td class='pformstrip' colspan='2'>{$total} {$ibforums->lang['whoschatting_total']} <a href='$link'>{$ibforums->lang['whoschatting_loadchat']}</a></td>
    	</tr>
    	<tr>
          <td width="5%" class='row2'><{F_ACTIVE}></td>
          <td class='row4' width='95%'>
            {$names}<div class='desc' style='margin-top:5px'>$txt</div>
          </td>
        </tr>
EOF;
}

function whoschatting_empty($link) {
global $ibforums;
return <<<EOF
        <tr>
           <td class='pformstrip' colspan='2'>{$ibforums->lang['whoschatting_total']} <a href='$link'>{$ibforums->lang['whoschatting_loadchat']}</a></td>
    	</tr>
    	<tr>
          <td width="5%" class='row2'><{F_ACTIVE}></td>
          <td class='row4' width='95%'>
            <i>{$ibforums->lang['whoschatting_none']}</i>
          </td>
        </tr>
EOF;
}

function whoschatting_inline_link() {
global $ibforums;
return <<<EOF
{$ibforums->base_url}act=chat
EOF;
}

function whoschatting_popup_link() {
global $ibforums;
return <<<EOF
javascript:chat_pop({$ibforums->vars['chat_width']}, {$ibforums->vars['chat_height']});
EOF;
}

function active_list_sep() {
global $ibforums;
return <<<EOF
,
EOF;
}


function stats_header() {
global $ibforums;
return <<<EOF
<!-- Board Stats -->
	<!--IBF.QUICK_LOG_IN-->
    <br />
	<div align='center'>
		<a href='{$ibforums->base_url}act=Stats&amp;CODE=leaders'>{$ibforums->lang['sm_forum_leaders']}</a> |
		<a href='{$ibforums->base_url}act=Search&amp;CODE=getactive'>{$ibforums->lang['sm_todays_posts']}</a> |
		<a href='{$ibforums->base_url}act=Stats'>{$ibforums->lang['sm_today_posters']}</a> |
		<a href='{$ibforums->base_url}act=Members&amp;max_results=10&amp;sort_key=posts&amp;sort_order=desc'>{$ibforums->lang['sm_all_posters']}</a>
	</div>
    <br />
	<div class="tableborder">
		<div class="maintitle">{$ibforums->lang['board_stats']}</div>
		<table cellpadding='4' cellspacing='1' border='0' width='100%'>
EOF;
}

function ActiveUsers($active) {
global $ibforums;
return <<<EOF
        <tr>
           <td class='pformstrip' colspan='2'>$active[TOTAL] {$ibforums->lang['active_users']}</td>
    	</tr>
    	<tr>
          <td width="5%" class='row2'><{F_ACTIVE}></td>
          <td class='row4' width='95%'>
            <b>{$active[GUESTS]}</b> {$ibforums->lang['guests']}, <b>$active[MEMBERS]</b> {$ibforums->lang['public_members']} <b>$active[ANON]</b> {$ibforums->lang['anon_members']}
            <div class='thin'>{$active[NAMES]}</div>
            {$active['links']}
          </td>
        </tr>
        <!--IBF.WHOSCHATTING-->
EOF;
}

function active_user_links() {
global $ibforums;
return <<<EOF
{$ibforums->lang['oul_show_more']} <a href='{$ibforums->base_url}act=Online&amp;CODE=listall&amp;sort_key=click'>{$ibforums->lang['oul_click']}</a>, <a href='{$ibforums->base_url}act=Online&amp;CODE=listall&amp;sort_key=name&amp;sort_order=asc&amp;show_mem=reg'>{$ibforums->lang['oul_name']}</a>
EOF;
}

function ShowStats($text) {
global $ibforums;
return <<<EOF
		   <tr>
		     <td class='pformstrip' colspan='2'>{$ibforums->lang['board_stats']}</td>
		   </tr>
		   <tr>
			 <td class='row2' width='5%' valign='middle'><{F_STATS}></td>
			 <td class='row4' width="95%" align='left'>$text<br />{$ibforums->lang['most_online']}</td>
		   </tr>
EOF;
}

function ShowTrackerStats($text) {
global $ibforums;
return <<<EOF
		   <tr>
		     <td class='pformstrip' colspan='2'>{$ibforums->lang['tracker_stats']}</td>
		   </tr>
		   <tr>
			 <td class='row2' width='5%' valign='middle'><{F_STATS}></td>
			 <td class='row4' width="95%" align='left'>$text<br /></td>
		   </tr>
EOF;
}

function birthdays($birthusers="", $total="", $birth_lang="") {
global $ibforums;
return <<<EOF
        <tr>
           <td class='pformstrip' colspan='2'>{$ibforums->lang['birthday_header']}</td>
    	</tr>
    	<tr>
          <td class='row2' width='5%' valign='middle'><{F_ACTIVE}></td>
          <td class='row4' width='95%'><b>$total</b> $birth_lang<br />$birthusers</td>
        </tr>
EOF;
}



function calendar_events($events = "") {
global $ibforums;
return <<<EOF
        <tr>
           <td class='pformstrip' colspan='2'>{$ibforums->lang['calender_f_title']}</td>
    	</tr>
    	<tr>
          <td class='row2' width='5%' valign='middle'><{F_ACTIVE}></td>
          <td class='row4' width='95%'>$events</td>
        </tr>
EOF;
}

function stats_footer() {
global $ibforums;
return <<<EOF
         </table>
	 </div>
    <!-- Board Stats -->
EOF;
}

function bottom_links() {
global $ibforums;
return <<<EOF
   <br />
   <div align='right'><a href="{$ibforums->base_url}act=Login&amp;CODE=06">{$ibforums->lang['d_delete_cookies']}</a> &middot; <a href="{$ibforums->base_url}act=Login&amp;CODE=05">{$ibforums->lang['d_post_read']}</a></div>
EOF;
}


function CatHeader_Expanded($Data) {
global $ibforums;
return <<<EOF
	<div class="tableborder">
	  <div class='maintitle' align='left'><{CAT_IMG}>&nbsp;<a href="{$ibforums->base_url}c={$Data['id']}">{$Data['name']}</a></div>
      <table width="100%" border="0" cellspacing="1" cellpadding="4">
        <tr>
          <th align="center" width="2%" class='titlemedium'><img src="{$ibforums->vars['img_url']}/spacer.gif" alt="" width="28" height="1" /></th>
          <th align="left" width="59%" class='titlemedium'>{$ibforums->lang['cat_name']}</th>
          <th align="center" width="7%" class='titlemedium'>{$ibforums->lang['topics']}</th>
          <th align="center" width="7%" class='titlemedium'>{$ibforums->lang['replies']}</th>
          <th align="left" width="25%" class='titlemedium'>{$ibforums->lang['last_post_info']}</th>
        </tr>
EOF;
}



function subheader() {
global $ibforums;
return <<<EOF
    <br />
	<div class="tableborder">
	  <table width="100%" border="0" cellspacing="1" cellpadding="4">
	  <tr>
		<td align="center" class='titlemedium'><img src="{$ibforums->vars['img_url']}/spacer.gif" alt="" width="28" height="1" /></td>
		<th align='left' width="59%" class='titlemedium'>{$ibforums->lang['cat_name']}</th>
		<th align="center" width="7%" class='titlemedium'>{$ibforums->lang['topics']}</th>
		<th align="center" width="7%" class='titlemedium'>{$ibforums->lang['replies']}</th>
		<th align='left' width="27%" class='titlemedium'>{$ibforums->lang['last_post_info']}</th>
	  </tr>
EOF;
}

function end_this_cat() {
global $ibforums;
return <<<EOF
         <tr>
          <td class='darkrow2' colspan="5">&nbsp;</td>
        </tr>
      </table>
    </div>
    <br />
EOF;
}

function end_all_cats() {
global $ibforums;
return <<<EOF

EOF;
}

function newslink( $fid="", $title="", $tid="" ) {
global $ibforums;
return <<<EOF
<b>{$ibforums->vars['board_name']} {$ibforums->lang['newslink']} <a href='{$ibforums->base_url}showtopic=$tid'>$title</a></b><br />
EOF;
}

function PageTop($lastvisit) {
global $ibforums;
return <<<EOF
	<div align='left' style='text-align:left;padding-bottom:4px'>
		<!-- IBF.NEWSLINK -->{$ibforums->lang['welcome_back_text']} $lastvisit
	</div>
EOF;
}

function quick_log_in() {
global $ibforums;
return <<<EOF
<form style='display:inline' action="{$ibforums->base_url}act=Login&amp;CODE=01&amp;CookieDate=1" method="post">
<div align='right'><strong>{$ibforums->lang['qli_title']}</strong>
<input type="text" class="forminput" size="10" name="UserName" onfocus="this.value=''" value="{$ibforums->lang['qli_name']}" />
<input type='password' class='forminput' size='10' name='PassWord' onfocus="this.value=''" value='ibfrules' />
<input type='submit' class='forminput' value='{$ibforums->lang['qli_go']}' />
</div>
</form>
EOF;
}

function forum_img_with_link($img, $id) {
global $ibforums;
return <<<EOF
<a href='{$ibforums->base_url}act=Login&amp;CODE=04&amp;f={$id}' title='{$ibforums->lang['bi_markread']}'>{$img}</a>
EOF;
}

function subforum_img_with_link($img, $id) {
global $ibforums;
return <<<EOF
<a href='{$ibforums->base_url}act=Login&amp;CODE=04&amp;f={$id}&amp;i=1' title='{$ibforums->lang['bi_markallread']}'>{$img}</a>
EOF;
}


function ForumRow($info) {
global $ibforums;
return <<<EOF
        <tr>
          <td class="row4" align="center">{$info['img_new_post']}</td>
          <td class="row4"><b><a href="{$ibforums->base_url}showforum={$info['id']}">{$info['name']}</a></b><br /><span class='desc'>{$info['description']}<br />{$info['moderator']}</span></td>
          <td class="row2" align="center">{$info['topics']}</td>
          <td class="row2" align="center">{$info['posts']}</td>
          <td class="row2" nowrap="nowrap">{$info['last_post']}<br />{$ibforums->lang['in']}:&nbsp;{$info['last_unread']}{$info['last_topic']}<br />{$ibforums->lang['by']}: {$info['last_poster']}</td>
        </tr>
EOF;
}

function forum_redirect_row($info) {
global $ibforums;
return <<<EOF
    <!-- Forum {$info['id']} entry -->
        <tr>
          <td class="row4" align="center"><{BR_REDIRECT}></td>
          <td class="row4"><b><a href="{$ibforums->base_url}showforum={$info['id']}" {$info['redirect_target']}>{$info['name']}</a></b><br /><span class='desc'>{$info['description']}</span></td>
          <td class="row2" align="center">-</td>
          <td class="row2" align="center">-</td>
          <td class="row2">{$ibforums->lang['rd_hits']}: {$info['redirect_hits']}</td>
        </tr>
    <!-- End of Forum {$info['id']} entry -->
EOF;
}

function forumrow_lastunread_link($fid, $tid) {
global $ibforums;
return <<<EOF
<a href='{$ibforums->base_url}showtopic=$tid&amp;view=getlastpost' title='{$ibforums->lang['tt_golast']}'><{LAST_POST}></a>
EOF;
}


}
?>