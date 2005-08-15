<?php

class skin_modcp {

function mm_start() {
global $ibforums;
return <<<EOF
<option value='-1'>------------------------------</option>
<option value='-1'>{$ibforums->lang['mm_title']}</option>
<option value='-1'>------------------------------</option>
EOF;
}


function mm_entry($id, $title) {
global $ibforums;
return <<<EOF
<option value='t_{$id}'>--  $title</option>
EOF;
}

function mm_end() {
global $ibforums;
return <<<EOF

EOF;
}


function mod_cp_start() {
global $ibforums;
return <<<EOF
<div class='tableborder'>
 <div align='center' class='pformstrip'>
  <a href='{$ibforums->base_url}act=modcp&amp;CODE=showforums'>{$ibforums->lang['menu_forums']}</a> &middot;
  <a href='{$ibforums->base_url}act=modcp&amp;CODE=members'>{$ibforums->lang['menu_users']}</a> &middot;
  <a href='{$ibforums->base_url}act=modcp&amp;CODE=ip'>{$ibforums->lang['menu_ip']}</a>
 </div>
</div>
<br />
EOF;
}


function modtopicview_start($tid,$forumname, $fid, $title) {
global $ibforums;
return <<<EOF

<form name='ibform' action='{$ibforums->base_url}' method='POST'>
<input type='hidden' name='s' value='{$ibforums->session_id}'>
<input type='hidden' name='act' value='modcp'>
<input type='hidden' name='CODE' value='domodposts'>
<input type='hidden' name='f' value='{$fid}'>
<input type='hidden' name='tid' value='{$tid}'>
<strong>{$ibforums->lang['cp_mod_posts_title2']} $forumname</strong>
<br />$pages


<div class='tableborder'>
  <div class='maintitle'>$title</div>

                
EOF;
}


function modpost_topicstart($forumname, $fid) {
global $ibforums;
return <<<EOF
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['cp_mod_posts_title2']} $forumname</div>
  <table width='100%' cellpadding='4' cellspacing='1'>
  <tr>
	<th class='pformstrip' width='40%' align='left'>{$ibforums->lang['cp_3_title']}</th>
	<th class='pformstrip' width='20%' align='center'>{$ibforums->lang['cp_3_replies']}</th>
	<th class='pformstrip' width='20%' align='center'>{$ibforums->lang['cp_3_approveall']}</th>
	<th class='pformstrip' width='20%' align='center'>{$ibforums->lang['cp_3_viewall']}</th>
  </tr>
	 
EOF;
}

function modpost_topicentry($title, $tid, $replies, $fid) {
global $ibforums;
return <<<EOF

   <tr>
	 <td class='row1' width='40%' align='left'><b><a href='{$ibforums->base_url}act=ST&amp;f=$fid&amp;t=$tid' target='_blank'>$title</a></b></td>
	 <td class='row1' width='20%' align='center'>$replies</td>
	 <td class='row1' width='20%' align='center'><a href='{$ibforums->base_url}act=modcp&amp;f=$fid&amp;tid=$tid&amp;CODE=modtopicapprove'>{$ibforums->lang['cp_3_approveall']}</a></td>
	 <td class='row1' width='20%' align='center'><a href='{$ibforums->base_url}act=modcp&amp;f=$fid&amp;tid=$tid&amp;CODE=modtopicview'>{$ibforums->lang['cp_3_viewall']}</a></td>
   </tr>
	 
EOF;
}

function modpost_topicend() {
global $ibforums;
return <<<EOF

   </table>
</div>
	 
EOF;
}



function modtopics_start($pages,$forumname, $fid) {
global $ibforums;
return <<<EOF

<form name='ibform' action='{$ibforums->base_url}' method='POST'>
<input type='hidden' name='s' value='{$ibforums->session_id}'>
<input type='hidden' name='act' value='modcp'>
<input type='hidden' name='CODE' value='domodtopics'>
<input type='hidden' name='f' value='{$fid}'>
<strong>{$ibforums->lang['cp_mod_topics_title2']} $forumname</strong>
<br />$pages	 
EOF;
}

function modtopics_end() {
global $ibforums;
return <<<EOF

<div class='tableborder'>
  <div class='pformstrip' align='center'><input type='submit' value='{$ibforums->lang['cp_1_go']}' class='forminput' /></div>
</div>
</form>
	 
EOF;
}


function mod_topic_title($title, $topic_id) {
global $ibforums;
return <<<EOF

<div class='tableborder'>
  <div class='titlemedium'><select name='TID_$topic_id' class='forminput'><option value='approve'>{$ibforums->lang['cp_1_approve']}</option><option value='remove'>{$ibforums->lang['cp_1_remove']}</option><option value='leave'>{$ibforums->lang['cp_1_leave']}</option></select>&nbsp;&nbsp; $title</div>
                
EOF;
}


function mod_postentry($data) {
global $ibforums;
return <<<EOF
   <table width='100%' cellspacing='1'>	
   <tr>
	   <td valign='top' class='row1' nowrap="nowrap"><span class='normalname'>{$data['member']['name']}</span><br /><br />{$data['member']['avatar']}<span class='postdetails'><br />{$data['member']['MEMBER_GROUP']}<br />{$data['member']['MEMBER_POSTS']}<br />{$data['member']['MEMBER_JOINED']}</span></td>
	   <td valign='top' class='row1' width='100%'>
		   <b>{$ibforums->lang['posted_on']} {$data['msg']['post_date']}</b><br /><br />
		   <span class='postcolor'>
			{$data['msg']['post']}
		   </span>
	   </td>
	</tr>
	</table>		  

EOF;
}

function mod_postentry_checkbox($pid) {
global $ibforums;
return <<<EOF
 <div class='pformstrip' align='right'><select name='PID_$pid' class='forminput'><option value='approve'>{$ibforums->lang['cp_1_approve']}</option><option value='remove'>{$ibforums->lang['cp_1_remove']}</option><option value='leave'>{$ibforums->lang['cp_1_leave']}</option></select>&nbsp;&nbsp;{$ibforums->lang['cp_3_postno']}&nbsp;$pid</div>
EOF;
}


function mod_topic_spacer() {
global $ibforums;
return <<<EOF

</div>
<br />

EOF;
}

function results($text) {
global $ibforums;
return <<<EOF

<tr>
  <td colspan='2'>
    <table cellpadding='2' cellspacing='1' border='0' width='100%' class='fancyborder' align='center'>
     <tr>
       <td><span class='pagetitle'>{$ibforums->lang['cp_results']}</span>
       </td>
     </tr>
	  <tr>
	    <td colspan='2'><b>$text</b></td>
	  </tr>
	 </table>
   </td>
  </tr>

EOF;
}


function prune_confirm($tcount, $count, $link, $link_text, $key) {
global $ibforums;
return <<<EOF
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['mpt_confirm']}</div>
  <div class='pformstrip'>{$ibforums->lang['cp_check_result']}</div>
  <table width='100%' cellspacing='0'>
   <tr>
	<td class='pformleftw'><strong>{$ibforums->lang['cp_total_topics']}</strong></td>
	<td class='pformright'>$tcount</td>
   </tr>
   <tr>
	<td class='pformleftw'><span style='color:red;font-weight:bold;'>{$ibforums->lang['cp_total_match']}</span></td>
	<td class='pformright'><span style='color:red;font-weight:bold;'>$count</span></td>
   </tr>
   </table>
   <form action='{$ibforums->base_url}$link' method='post'>
   <input type='hidden' name='key' value='$key' />
   <div class='pformstrip' align='center'><input type='submit' class='forminput' value='$link_text' /></div>
   </form>
</div>
<br />

EOF;
}

function prune_splash($forum, $forums, $select) {
global $ibforums;
return <<<EOF

<!-- IBF.CONFIRM -->
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['cp_prune']} {$forum['name']}</div>
  <div class='pformstrip'>{$ibforums->lang['mpt_help']}</div>
  <div class='tablepad'>{$ibforums->lang['cp_prune_text']}</div>
  <form name='ibform' action='{$ibforums->base_url}' method='POST'>
  <input type='hidden' name='s' value='{$ibforums->session_id}'>
  <input type='hidden' name='act' value='modcp'>
  <input type='hidden' name='CODE' value='prune'>
  <input type='hidden' name='f' value='{$forum['id']}'>
  <input type='hidden' name='check' value='1'>
  <div class='pformstrip'>{$ibforums->lang['mpt_title']}</div>
   <table width='100%' cellspacing='0'>
   <tr>
	<td class='pformleftw'>{$ibforums->lang['cp_action']}</td>
	<td class='pformright'><select name='df' class='forminput'>$forums</select></td>
   </tr>
   <tr>
	<td class='pformleftw'>{$ibforums->lang['cp_prune_days']}</td>
	<td class='pformright'><input type='text' size='40' name='dateline' value='{$ibforums->input['dateline']}' class='forminput' /></td>
   </tr>
   <tr>
	<td class='pformleftw'>{$ibforums->lang['cp_prune_type']}</td>
	<td class='pformright'>$select &nbsp; <input type='checkbox' id='cbox' name='ignore_pin' value='1' checked='checked' class='checkbox' />&nbsp;<label for='cbox'>{$ibforums->lang['mps_ignorepin']}</label></td>
   </tr>
   <tr>
	<td class='pformleftw'>{$ibforums->lang['cp_prune_replies']}</td>
	<td class='pformright'><input type='text' size='40' name='posts' value='{$ibforums->input['posts']}' class='forminput' /></td>
   </tr>
   <tr>
	<td class='pformleftw'>{$ibforums->lang['cp_prune_member']}</td>
	<td class='pformright'><input type='text' size='40' name='member' value='{$ibforums->input['member']}' class='forminput' /></td>
   </tr>
   </table>
  <div class='pformstrip' align='center'><input type='submit' value='{$ibforums->lang['cp_prune_sub1']}' class='forminput' /></div>
  </form>
</div>

EOF;
}




function edit_user_form($profile) {
global $ibforums;
return <<<EOF

<form name='ibform' action='{$ibforums->base_url}act=modcp&amp;CODE=compedit&amp;memberid={$profile['id']}' method='post'>
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['cp_edit_user']}: {$profile['name']}</div>
  <table class='tablebasic'>
  <tr>
   <td class='pformleft'>{$ibforums->lang['cp_remove_av']}</td>
   <td class='pformright'><select name='avatar' class='forminput'><option value='0'>{$ibforums->lang['no']}</option><option value='1'>{$ibforums->lang['yes']}</option></select></td>
  </tr>
  <tr>
   <td class='pformleft'>{$ibforums->lang['cp_remove_photo']}</td>
   <td class='pformright'><select name='photo' class='forminput'><option value='0'>{$ibforums->lang['no']}</option><option value='1'>{$ibforums->lang['yes']}</option></select></td>
  </tr>
  <tr>
   <td class='pformleft'>{$ibforums->lang['cp_edit_website']}</td>
   <td class='pformright'><input type='text' size='40' name='website' value='{$profile['website']}' class='forminput' /></td>
  </tr>
  <tr>
   <td class='pformleft'>{$ibforums->lang['cp_edit_location']}</td>
   <td class='pformright'><input type='text' size='40' name='location' value='{$profile['location']}' class='forminput' /></td>
  </tr>
  <tr>
   <td class='pformleft'>{$ibforums->lang['cp_edit_interests']}</td>
   <td class='pformright'><textarea cols='50' rows='3' name='interests' class='forminput'>{$profile['interests']}</textarea></td>
  </tr>
   <tr>
   <td class='pformleft'>{$ibforums->lang['cp_edit_signature']}</td>
   <td class='pformright'><textarea cols='50' rows='5' name='signature' class='forminput'>{$profile['signature']}</textarea></td>
  </tr>
  </table>
  <div class='pformstrip' align='center'><input type='submit' value='{$ibforums->lang['cp_find_2_submit']}' class='forminput' /></div>
</div>
</form>

EOF;
}


function find_two($select) {
global $ibforums;
return <<<EOF
<form name='ibform' action='{$ibforums->base_url}act=modcp&amp;CODE=doedituser' method='post'>
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['cp_edit_user']}</div>
  <table class='tablebasic' cellspacing="1" cellpadding="3">
  <tr>
   <td width='40%' class='row1'>{$ibforums->lang['cp_find_2_user']}</td>
   <td class='row1'>$select</td>
  </tr>
  </table>
  <div class='pformstrip' align='center'><input type='submit' value='{$ibforums->lang['cp_find_2_submit']}' class='forminput' /></div>
</div>
</form>
EOF;
}


function find_user() {
global $ibforums;
return <<<EOF

<form name='ibform' action='{$ibforums->base_url}act=modcp&amp;CODE=dofinduser' method='post'>
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['cp_edit_user']}</div>
  <table class='tablebasic' cellspacing="1" cellpadding="3">
  <tr>
   <td width='40%' class='row1'>{$ibforums->lang['cp_find_user']}</td>
   <td class='row1'><input type='text' size='40' name='name' value='' class='forminput' /></td>
  </tr>
  </table>
  <div class='pformstrip' align='center'><input type='submit' value='{$ibforums->lang['cp_find_submit']}' class='forminput' /></div>
</div>
</form>

EOF;
}

function ip_start_form($ip="") {
global $ibforums;
return <<<EOF

<form name='ibform' action='{$ibforums->base_url}' method='post'>
<input type='hidden' name='s' value='{$ibforums->session_id}'>
<input type='hidden' name='act' value='modcp'>
<input type='hidden' name='CODE' value='doip'>
<div class='tableborder'>
 <div class='maintitle'>{$ibforums->lang['menu_ip']}</div>
 <table class='tablebasic'>
 <tr>
   <td width='40%' class='row1'>{$ibforums->lang['ip_enter']}</td>
   <td class='row1'>
	 <input type='text' size='20' maxlength='24' name='ip' value='{$ip}' class='forminput' />
	 <select name='iptool' class='forminput'>
		 <option value='resolve'>{$ibforums->lang['ip_resolve']}</option>
		 <option value='posts'>{$ibforums->lang['ip_posts']}</option>
		 <option value='members'>{$ibforums->lang['ip_members']}</option>
	 </select>
   </td>
  </tr>
  </table>
  <div class='pformstrip' align='center'><input type='submit' value='{$ibforums->lang['ip_submit']}' class='forminput' /></div>
</div>
</form>
<br />
<div class='tableborder'>
 <div class='maintitle'>{$ibforums->lang['iph_title']}</div>
 <div class='tablepad' style='line-height:150%'>{$ibforums->lang['ip_desc_text']}<br /><br />{$ibforums->lang['ip_warn_text']}</div>
</div>
EOF;
}

function ip_member_start($pages) {
global $ibforums;
return <<<EOF

<div align='left'>$pages</div>
<br />
<div class='tableborder'>
 <div class='maintitle'>{$ibforums->lang['ipm_title']}</div>
 <table cellpadding='6' class='tablebasic'>
 <tr>
  <th class='pformstrip' width='20%'>{$ibforums->lang['ipm_name']}</th>
  <th class='pformstrip' width='20%'>{$ibforums->lang['ipm_ip']}</th>
  <th class='pformstrip' width='10%'>{$ibforums->lang['ipm_posts']}</th>
  <th class='pformstrip' width='20%'>{$ibforums->lang['ipm_reg']}</th>
  <th class='pformstrip' width='30%'>{$ibforums->lang['ipm_options']}</th>
 </tr>

EOF;
}

function ip_member_row($row) {
global $ibforums;
return <<<EOF

	 <tr>
	  <td class='row2'>{$row['name']}</td>
	  <td class='row2'>{$row['ip_address']}</td>
	  <td class='row2'>{$row['posts']}</td>
	  <td class='row2'>{$row['joined']}</td>
	  <td class='row2' align='center'><a href='{$ibforums->base_url}showuser={$row['id']}' target='_blank'>{$ibforums->lang['ipm_view']}</a>
	  | <a href='{$ibforums->base_url}act=modcp&amp;CODE=doedituser&amp;memberid={$row['id']}'>{$ibforums->lang['ipm_edit']}</a></td>
	 </tr>

EOF;
}

function ip_member_end($pages) {
global $ibforums;
return <<<EOF

	 </table>
</div>
<br />
<div align='left'>$pages</div>
EOF;
}

function splash($tcount, $pcount, $forum) {
global $ibforums;
return <<<EOF

 <tr>
  <td class='pagetitle'>{$ibforums->lang['cp_welcome']}</td>
 </tr>
 <tr>
  <td>{$ibforums->lang['cp_welcome_text']}</td>
 </tr>
 <tr>
  <td>
    <table cellpadding='2' cellspacing='1' border='0' width='75%' class='fancyborder' align='center'>
	  <tr>
	    <td><b>{$ibforums->lang['cp_mod_in']}</b></td>
	    <td>$forum</td>
	  </tr>
	  <tr>
	    <td><b>{$ibforums->lang['cp_topics_wait']}</b></td>
	    <td>$tcount</td>
	  </tr>
	  <tr>
	    <td><b>{$ibforums->lang['cp_posts_wait']}</b></td>
	    <td>$pcount</td>
	  </tr>
	 </table>
   </td>
  </tr>

EOF;
}







function mod_exp($words) {
global $ibforums;
return <<<EOF



                <tr>
                <td class='row1' colspan='2'>$words</td>
                </tr>


EOF;
}

function end_form($action) {
global $ibforums;
return <<<EOF


                <tr>
                <td class='row2' align='center' colspan='2'>
                <input type="submit" name="submit" value="$action" class='forminput'>
                </td></tr></table>
                </td></tr></table>
                </form>


EOF;
}

function forum_row($info) {
global $ibforums;
return <<<EOF
<!-- Forum {$info['id']} entry -->
  <tr>
	<td class='row4' align='center' width='5%'>{$info['folder_icon']}</td>
	<td class="row4" colspan=2><b><a href="{$ibforums->base_url}act=modcp&amp;CODE=showtopics&amp;f={$info['id']}">{$info['name']}</a></b><br /><span class='desc'>{$info['description']}</span><br />{$info['moderator']}</td>
	<td class="row2" align="center">{$info['q_topics']}</td>
	<td class="row2" align="center">{$info['q_posts']}</td>
	<td class="row2">{$info['last_post']}<br />{$ibforums->lang['in']}: {$info['last_topic']}<br />{$ibforums->lang['by']}: {$info['last_poster']}</td>
	<td class="row2" align="center">{$info['select_button']}</td>        
  </tr>
<!-- End of Forum {$info['id']} entry -->
EOF;
}


function subforum_row($info) {
global $ibforums;
return <<<EOF
<!-- Forum {$info['id']} entry -->
  <tr>
	<td class='row4' align='center' width='5%'>&nbsp;</td>
	<td class='row4' align='center' width='5%'>{$info['folder_icon']}</td>
	<td class="row2"><b><a href="{$ibforums->base_url}act=modcp&amp;CODE=showtopics&amp;f={$info['id']}">{$info['name']}</a></b><br /><span class='desc'>{$info['description']}</span><br />{$info['moderator']}</td>
	<td class="row2" align="center">{$info['q_topics']}</td>
	<td class="row2" align="center">{$info['q_posts']}</td>
	<td class="row2">{$info['last_post']}<br />{$ibforums->lang['in']}: {$info['last_topic']}<br />{$ibforums->lang['by']}: {$info['last_poster']}</td>
	<td class="row2" align="center"><input type='radio' name='f' value='{$info['id']}' /></td>        
  </tr>
<!-- End of Forum {$info['id']} entry -->
EOF;
}

function forum_page_start() {
global $ibforums;
return <<<EOF
<form action='{$ibforums->base_url}act=modcp&amp;CODE=fchoice' method='post'>
<div class='tableborder'>
  <table class='tablebasic' cellspacing="1" cellpadding="3">
EOF;
}


function cat_row($cat_name) {
global $ibforums;
return <<<EOF
  <tr>
	<td colspan='7' class='maintitle'>$cat_name</td>
  </tr>
  <tr> 
	<th class='titlemedium' align='left' width='5%'>&nbsp;</th>
	<th width="35%" class='titlemedium' colspan='2'>{$ibforums->lang['cat_name']}</th>
	<th width="15%" class='titlemedium'>{$ibforums->lang['f_q_topics']}</th>
	<th width="15%" class='titlemedium'>{$ibforums->lang['f_q_posts']}</th>
	<th width="25%" class='titlemedium'>{$ibforums->lang['last_post_info']}</th>
	<th width="5%"  class='titlemedium'>{$ibforums->lang['f_select']}</th>
  </tr>
EOF;
}

function forum_page_end() {
global $ibforums;
return <<<EOF
  <tr>
   <td colspan='7' class='row2' align='right'><b>{$ibforums->lang['f_w_selected']}</b>
   <select class='forminput' name='fact'>
   <option value='mod_topic'>{$ibforums->lang['cp_mod_topics']}</option>
   <option value='mod_post'>{$ibforums->lang['cp_mod_posts']}</option>
   <option value='prune_move'>{$ibforums->lang['cp_prune_posts']}</option>
   </select>&nbsp;<input type='submit' value='{$ibforums->lang['f_go']}' class='forminput' />
   </td>
  </tr>
  </table>
</div>
</form>
EOF;
}

function mod_simple_page($title="",$msg="") {
global $ibforums;
return <<<EOF
<div class='tableborder'>
  <div class='maintitle'>$title</div>
  <div class='tablepad'>$msg</div>
</div>

EOF;
}

function ip_post_results($uid="",$count="") {
global $ibforums;
return <<<EOF
{$ibforums->lang['ipp_found']} $count
<br />
<br />
<a target='_blank' href='{$ibforums->base_url}act=Search&amp;CODE=show&amp;searchid=$uid&amp;search_in=posts&amp;result_type=posts'>{$ibforums->lang['ipp_click']}</a>

EOF;
}

function start_topics($pages,$info) {
global $ibforums;
return <<<EOF

<script language='javascript'>
<!--
 function checkdelete() {
 
   isDelete = document.topic.tact.options[document.topic.tact.selectedIndex].value;
   
   msg = '';
   
   if (isDelete == 'delete')
   {
	   msg = "{$ibforums->lang['cp_js_delete']}";
	   
	   formCheck = confirm(msg);
	   
	   if (formCheck == true)
	   {
		   return true;
	   }
	   else
	   {
		   return false;
	   }
   }
 }
//-->
</script>
<form action='{$ibforums->base_url}act=modcp&amp;f={$info['id']}&amp;CODE=topicchoice' method='post' name='topic' onsubmit='return checkdelete();'>
<div class='pagelinks'>$pages</div>
<div align='right'>
  <a href='{$ibforums->base_url}act=modcp&amp;fact=mod_topic&amp;CODE=fchoice&amp;f={$info['id']}'>{$ibforums->lang['cp_mod_topics']}</a> &middot;
  <a href='{$ibforums->base_url}act=modcp&amp;fact=mod_post&amp;CODE=fchoice&amp;f={$info['id']}'>{$ibforums->lang['cp_mod_posts']}</a> &middot;
  <a href='{$ibforums->base_url}act=modcp&amp;fact=prune_move&amp;CODE=fchoice&amp;f={$info['id']}'>{$ibforums->lang['cp_prune_posts']}</a>
</div>
<br />
<div class='tableborder'>
  <div class='maintitle'>{$info['name']} [ <a target='_blank' href='{$ibforums->base_url}showforum={$info['id']}'>{$ibforums->lang['new_show_forum']}</a> ]</div>
  <table width='100%' border='0' cellspacing='1' cellpadding='4'>
  <tr> 
	<td class='titlemedium' style='width:5px'>&nbsp;</td>
	<td class='titlemedium' style='width:5px'>&nbsp;</td>
	<td width='40%' class='titlemedium'>{$ibforums->lang['h_topic_title']}</td>
	<td width='15%' align='center' nowrap="nowrap" class='titlemedium'>{$ibforums->lang['h_topic_starter']}</td>
	<td width='7%' align='center' nowrap="nowrap" class='titlemedium'>{$ibforums->lang['h_replies']}</td>
	<td width='8%' align='center' nowrap="nowrap" class='titlemedium'>{$ibforums->lang['h_hits']}</td>
	<td width='25%' nowrap="nowrap" class='titlemedium'>{$ibforums->lang['h_last_action']}</td>
	<td width='5%' nowrap="nowrap" class='titlemedium'>{$ibforums->lang['f_select']}</td>
  </tr>

EOF;
}

function show_no_topics() {
global $ibforums;
return <<<EOF
  <tr> 
	<td class='row4' colspan='8' align='center'>
		<br />
		 <b>{$ibforums->lang['fv_no_topics']}</b>
		<br /><br />
	</td>
  </tr>
EOF;
}

function topic_row($data) {
global $ibforums;
return <<<EOF
    <!-- Begin Topic Entry {$data['tid']} -->
    <tr> 
	  <td align='center' class='row4'>{$data['folder_img']}</td>
      <td align='center' class='row2'>{$data['topic_icon']}</td>
      <td class='row4'>{$data['prefix']} <a target='_blank' href='{$ibforums->base_url}showtopic={$data['tid']}' title='{$ibforums->lang['topic_started_on']} {$data['start_date']}'>{$data['title']}</a><br /><span class='desc'>{$data['description']}</span></td>
      <td align='center' class='row2'>{$data['starter']}</td>
      <td align='center' class='row4'>{$data['posts']}</td>
      <td align='center' class='row2'>{$data['views']}</td>
      <td class='row2'>{$data['last_post']}<br />{$data['last_text']} <b>{$data['last_poster']}</b></td>
      <td align='center' class='row2'><input type='checkbox' name='TID_{$data['real_tid']}' value='1' /></td>
    </tr>
    <!-- End Topic Entry {$data['tid']} -->
EOF;
}

function topics_end($data) {
global $ibforums;
return <<<EOF
  </table>
  <div class='pformstrip' align='center'>
     {$ibforums->lang['t_w_selected']}
	 <select class='forminput' name='tact'>
	 <option value='close'>{$ibforums->lang['cpt_close']}</option>
	 <option value='open'>{$ibforums->lang['cpt_open']}</option>
	 <option value='pin'>{$ibforums->lang['cpt_pin']}</option>
	 <option value='unpin'>{$ibforums->lang['cpt_unpin']}</option>
	 <option value='move'>{$ibforums->lang['cpt_move']}</option>
	 <option value='delete'>{$ibforums->lang['cpt_delete']}</option>
	 <!--IBF.MMOD-->
	 </select> &nbsp;<input type='submit' value='{$ibforums->lang['f_go']}' class='forminput' />
  </div>
</div>
</form>

EOF;
}




function move_checked_form_start($forum_name, $fid) {
global $ibforums;
return <<<EOF
<form action='{$ibforums->base_url}act=modcp&amp;CODE=topicchoice&amp;tact=domove&amp;f=$fid' method='post'>
<div class='tableborder'>
 <div class='maintitle'>{$ibforums->lang['cp_tmove_start']} $forum_name</div>
 <table class='tablebasic'>
EOF;
}

function move_checked_form_entry($tid, $title) {
global $ibforums;
return <<<EOF
  <tr>
   <td class='row1' width='10%' align='center'><input type='checkbox' name='TID_$tid' value='1' checked="checked" /></td>
   <td class='row1' width='90%' align='left'><strong>$title</strong></td>
  </tr>
EOF;
}

function move_checked_form_end($jump_html) {
global $ibforums;
return <<<EOF

   </table>
   <div align='center' class='tablepad'>{$ibforums->lang['cp_tmove_to']}&nbsp;&nbsp;<select class='forminput' name='df'>$jump_html</select></div>
   <div align='center' class='pformstrip'><input type='submit' value='{$ibforums->lang['cp_tmove_end']}' class='forminput' /></div>
 </div>
</form>
EOF;
}

}

?>