<?php

class skin_mod {

function warn_view_header($id, $name, $links) {
global $ibforums;
return <<<EOF
<table cellspacing='0' cellpadding='0' width='100%' border='0'>
<tr>
 <td align='left'><span id='phototitle'>$name</span></td>
 <td align='right'>$links</td>
</tr>
</table>
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['w_v_title']}: <a href='{$ibforums->base_url}showuser=$id'>$name</a></div>
</div>
 <table cellspacing='1' width='100%' cellpadding='6' class='plainborder'>
 <tr>
  <th class='pformstrip' width='30%'>{$ibforums->lang['w_v_warnby']}</th>
  <th class='pformstrip' width='70%'>{$ibforums->lang['w_v_notes']}</th>
 </tr>
EOF;
}


function warn_view_positive_row($date, $content, $puni_name) {
global $ibforums;
return <<<EOF
  <tr>
    <td class='row4' valign='top'><strong>$puni_name</strong></td>
    <td class='row4' valign='top'>{$ibforums->lang['w_v_warned_on']} <strong>$date</strong></td>
  </tr>
  <tr>
    <td class='row1' valign='middle'><span class='warngood'>{$ibforums->lang['w_v_minus']}</span></td>
    <td class='row1' valign='top'><span class='postcolor'>$content</span></td>
  </tr>
EOF;
}

function warn_view_negative_row($date, $content, $puni_name) {
global $ibforums;
return <<<EOF
  <tr>
    <td class='row4' valign='top'><strong>$puni_name</strong></td>
    <td class='row4' valign='top'>{$ibforums->lang['w_v_warned_on']} <strong>$date</strong></td>
  </tr>
  <tr>
    <td class='row1' valign='middle'><span class='warnbad'>{$ibforums->lang['w_v_add']}</span></td>
    <td class='row1' valign='top'><span class='postcolor'>$content</span></td>
  </tr>
EOF;
}


function warn_view_none() {
global $ibforums;
return <<<EOF
  <tr>
    <td class='row1' colspan='2' align='center'><strong>{$ibforums->lang['w_v_none']}</strong></td>
  </tr>
EOF;
}

function warn_view_footer() {
global $ibforums;
return <<<EOF
</table>
EOF;
}


function warn_success() {
global $ibforums;
return <<<EOF
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['w_done_t']}</div>
 <div class='pformstrip'>&nbsp;</div>
 <div class='tablepad'>
  {$ibforums->lang['w_done_te']}
  <ul>
   <li><a href='{$ibforums->base_url}'>{$ibforums->lang['w_done_home']}</a></li>
   <!--IBF.FORUM_TOPIC-->
  </ul>
 </div>
</div>
 	
EOF;
}

function warn_success_forum($fid, $fname, $tid, $tname, $st=0) {
global $ibforums;
return <<<EOF
   <li><a href='{$ibforums->base_url}showforum=$fid'>{$ibforums->lang['w_done_forum']} <strong>$fname</strong></a></li>
   <li><a href='{$ibforums->base_url}showtopic=$tid&amp;st=$st'>{$ibforums->lang['w_done_topic']} <strong>$tname</strong></a></li>
EOF;
}

function warn_errors($data) {
global $ibforums;
return <<<EOF
<div class="tableborder">
  <div class="pformstrip">{$ibforums->lang['errors_found']}</div>
  <div class="tablepad"><span class='postcolor'>$data</span></div>
</div>
<br />
EOF;
}

function warn_header($mid, $name, $cur=0, $min = 0, $max=10, $key, $tid='',$st='', $type) {
global $ibforums;
return <<<EOF
<form method='post' action='{$ibforums->base_url}&amp;act=warn&amp;CODE=dowarn&amp;mid=$mid&amp;t=$tid&amp;st=$st&amp;type={$ibforums->input['type']}'>
<input type='hidden' name='key' value='$key'>
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['w_warnfor']} <a href='{$ibforums->base_url}showuser=$mid'>$name</a> ( $min &lt; $cur &gt; $max )</div>
 <div class='pformstrip'>{$ibforums->lang['w_complete']}</div>
 <table width='100%' cellpadding='0' border='0' cellspacing='0'>
 <tr>
  <td class='pformleftw'><strong>{$ibforums->lang['w_adjust_level']}</strong></td>
  <td class='pformright'>
    <input type='radio' name='level' id='add' class='radiobutton' value='add' {$type['add']} /><label for='add' class='warnbad'><strong>{$ibforums->lang['w_add']}</strong></label>
    <br />
    <input type='radio' name='level' id='minus' class='radiobutton' value='remove' {$type['minus']} /><label for='minus' class='warngood'><strong>{$ibforums->lang['w_remove']}</strong></label>
 </tr>
EOF;
}

function warn_mod_posts($mod_tick, $mod_array, $mod_extra) {
global $ibforums;
return <<<EOF
 <tr>
  <td class='pformleftw'><strong>{$ibforums->lang['w_modq']}</strong></td>
  <td class='pformright'>
    <input type='checkbox' name='mod_indef' class='forminput' value='1' $mod_tick> {$ibforums->lang['w_modq_i']}
    <br /><strong>{$ibforums->lang['w_orfor']}</strong>
    <input type='input' name='mod_value' class='forminput' value='{$mod_array['timespan']}' size='5'> <select name='mod_unit' class='forminput'><option value='d' {$mod_array['days']}>{$ibforums->lang['w_day']}</option><option value='h' {$mod_array['hours']}>{$ibforums->lang['w_hour']}</option></select>
 	$mod_extra
 </tr>
EOF;
}


function warn_rem_posts($post_tick, $post_array, $post_extra) {
global $ibforums;
return <<<EOF
 <tr>
  <td class='pformleftw'><strong>{$ibforums->lang['w_resposts']}</strong></td>
  <td class='pformright'>
    <input type='checkbox' name='post_indef' class='forminput' value='1' $post_tick> {$ibforums->lang['w_resposts_i']}
    <br /><strong>{$ibforums->lang['w_orfor']}</strong>
    <input type='input' name='post_value' class='forminput' value='{$post_array['timespan']}' size='5'> <select name='post_unit' class='forminput'><option value='d' {$post_array['days']}>{$ibforums->lang['w_day']}</option><option value='h' {$post_array['hours']}>{$ibforums->lang['w_hour']}</option></select>
 	$post_extra
 </tr>
EOF;
}

function warn_suspend($susp_array, $susp_extra) {
global $ibforums;
return <<<EOF
 <tr>
  <td class='pformleftw'><strong>{$ibforums->lang['w_suspend']}</strong></td>
  <td class='pformright'>
     {$ibforums->lang['w_susfor']}  <input type='input' name='susp_value' class='forminput' value='{$susp_array['timespan']}' size='5'> <select name='susp_unit' class='forminput'><option value='d' {$susp_array['days']}>{$ibforums->lang['w_day']}</option><option value='h' {$susp_array['hours']}>{$ibforums->lang['w_hour']}</option></select>
 	  $susp_extra
 </tr>
EOF;
}

function warn_footer() {
global $ibforums;
return <<<EOF
  <tr>
   <td class='pformleftw' valign='top'><strong>{$ibforums->lang['w_reason']}</strong><br />{$ibforums->lang['w_reason2']}</td>
   <td class='pformright'><textarea rows='6' cols='70' class='textinput' name='reason'>{$ibforums->input['reason']}</textarea></td>
  </tr>
  <tr>
  <td class='pformleftw'><strong>{$ibforums->lang['w_c_subj']}</strong></td>
  <td class='pformright'><input type='input' name='subject' class='forminput' value='{$ibforums->input['subject']}' size='30'></td>
 </tr>
  <tr>
   <td class='pformleftw' valign='top'><strong>{$ibforums->lang['w_contact']}</strong><br />{$ibforums->lang['w_contact2']}</td>
   <td class='pformright'>
   	  {$ibforums->lang['w_c']}&nbsp;<select name='contactmethod' class='forminput'><option value='pm'>{$ibforums->lang['w_c_p']}</option><option value='email'>{$ibforums->lang['w_c_e']}</option></select>
     <br /><textarea rows='6' cols='70' class='textinput' name='contact'>{$ibforums->input['contact']}</textarea>
   </td>
  </tr>
  </table>
  <div align='center' class='pformstrip'><input type='submit' class='forminput' value='{$ibforums->lang['w_submit']}' /></div>
</div>
</form>
EOF;
}


function warn_restricition_in_place() {
global $ibforums;
return <<<EOF
<br /><strong>{$ibforums->lang['w_restricted']}</strong>
EOF;
}

function mod_exp($words) {
global $ibforums;
return <<<EOF
<div class='pformstrip'>$words</div>
EOF;
}

function end_form($action) {
global $ibforums;
return <<<EOF
  <div class='pformstrip' align='center'><input type="submit" name="submit" value="$action" class='forminput' /></div>
</div>
</form>
EOF;
}

function move_form($jhtml, $forum_name) {
global $ibforums;
return <<<EOF
  <table width='100%' cellspacing='1'>
  <tr>
  <td class='pformleftw'><strong>{$ibforums->lang[move_from]} <b>$forum_name</b> {$ibforums->lang[to]}</strong></td>
  <td class='pformright'><select name='move_id' class='forminput'>$jhtml</select></td>
  </tr>
  <tr>
  <td class='pformleftw'><strong>{$ibforums->lang['leave_link']}</strong></td>
  <td class='pformright'><select name='leave' class='forminput'><option value='y' selected="selected">{$ibforums->lang['yes']}</option><option value='n'>{$ibforums->lang['no']}</option></select></td>
  </tr>
  </table>
EOF;
}

function delete_js() {
global $ibforums;
return <<<EOF

  <script language='JavaScript' type='text/javascript'>
  <!--
  function ValidateForm() {
	 document.REPLIER.submit.disabled = true;
	 return true;
  }
  //-->
  </script>
          
EOF;
}

function topictitle_fields($title, $desc) {
global $ibforums;
return <<<EOF
  <table width='100%' cellspacing='1'>
  <tr>
  <td class='pformleftw'><strong>{$ibforums->lang[edit_f_title]}</strong></td>
  <td class='pformright'><input type='text' size='40' maxlength='50' name='TopicTitle' value='$title' /></td>
  </tr>
  <tr>
  <td class='pformleftw'><strong>{$ibforums->lang[edit_f_desc]}</strong></td>
  <td class='pformright'><input type='text' size='40' maxlength='40' name='TopicDesc' value='$desc' /></td>
  </tr>
  </table>
EOF;
}

function poll_edit_top() {
global $ibforums;

return <<<EOF
<table width='100%' cellpadding='6' border='0' cellspacing='0'>
EOF;

}


function poll_entry($id, $entry) {
global $ibforums;

return <<<EOF

				<tr>
				<td class='row1'><b>{$ibforums->lang['pe_option']} $id</b></td>
                <td class='row1'><input type='text' size='60' maxlength='250' name='POLL_$id' value='$entry'></td>
                </tr>
                
EOF;

}

function poll_edit_new_entry($id) {
global $ibforums;

return <<<EOF

				<tr>
				<td class='row1'><b>{$ibforums->lang['pe_option']} $id</b> <em>( {$ibforums->lang['pe_unused']} )</em></td>
                <td class='row1'><input type='text' size='60' maxlength='250' name='POLL_$id' value=''></td>
                </tr>
                
EOF;

}

function poll_select_form($poll_question="") {
global $ibforums;

return <<<EOF
	   <tr>
	   <td class='row1'><b>{$ibforums->lang['pe_question']}</b></td>
	   <td class='row1'><input type='text' size='60' maxlength='250' name='poll_question' value='$poll_question'></td>
	   </tr>
	   <tr>
	   <td class='row1'><b>{$ibforums->lang['pe_pollonly']}</b></td>
	   <td class='row1'><select name='pollonly' class='forminput'><option value='0'>{$ibforums->lang['pe_no']}</option><option value='1'>{$ibforums->lang['pe_yes']}</option></select></td>
	   </tr>
	   </table>
                
EOF;

}


function table_top($posting_title) {
global $ibforums;
return <<<EOF

<div class='tableborder'>
 <div class='maintitle'>$posting_title</div>

EOF;
}


function topic_history($data) {
global $ibforums;
return <<<EOF
<div class='tableborder'>
 <div class='maintitle'>{$ibforums->lang['th_title']}</div>
   <table cellspacing='1' width='100%'>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['th_topic']}</b></td>
	<td class='pformright'>{$data['th_topic']}</td>
   </tr>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['th_desc']}</b></td>
	<td class='pformright'>{$data['th_desc']}&nbsp;</td>
   </tr>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['th_start_date']}</b></td>
	<td class='pformright'>{$data['th_start_date']}</td>
   </tr>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['th_start_name']}</b></td>
	<td class='pformright'>{$data['th_start_name']}</td>
   </tr>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['th_last_date']}</b></td>
	<td class='pformright'>{$data['th_last_date']}</td>
   </tr>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['th_last_name']}</b></td>
	<td class='pformright'>{$data['th_last_name']}</td>
   </tr>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['th_avg_post']}</b></td>
	<td class='pformright'>{$data['th_avg_post']}</td>
   </tr>
   </table>
</div>
           
EOF;
}

function mod_log_start() {
global $ibforums;
return <<<EOF
<br />
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['ml_title']}</div>
  <table cellspacing='1' width='100%'>
  <tr>
   <td class='pformstrip' width='30%'><b>{$ibforums->lang['ml_name']}</b></td>
   <td class='pformstrip' width='50%'><b>{$ibforums->lang['ml_desc']}</b></td>
   <td class='pformstrip' width='20%'><b>{$ibforums->lang['ml_date']}</b></td>
  </tr>

EOF;

}

function mod_log_none() {
global $ibforums;
return <<<EOF
   <tr>
	<td class='pformright' colspan='3' align='center'><i>{$ibforums->lang['ml_none']}</i></td>
   </tr>

EOF;

}

function mod_log_row($data) {
global $ibforums;
return <<<EOF
   <tr>
	<td class='pformright'>{$data['member']}</td>
	<td class='pformright'>{$data['action']}</td>
	<td class='pformright'>{$data['date']}</td>
   </tr>

EOF;

}

function mod_log_end() {
global $ibforums;
return <<<EOF
	 </table>
</div>
EOF;

}

function forum_jump($data, $menu_extra="") {

global $ibforums;
return <<<EOF

<br />
<div align='right'>{$data}</div>
<br />
EOF;

}



function split_body($jump="") {
global $ibforums;
return <<<EOF

  <table cellspacing='1' width='100%'>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['mt_new_title']}</b></td>
	<td class='pformright'><input type='text' size='40'  class='forminput' maxlength='100' name='title' value='' /></td>
   </tr>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['mt_new_desc']}</b></td>
	<td class='pformright'><input type='text' size='40'  class='forminput' maxlength='100' name='desc' value='' /></td>
   </tr>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['st_forum']}</b></td>
	<td class='pformright'><select name='fid' class='forminput'>$jump</select></td>
   </tr>
  </table>
</div>
<br />
<div class='tableborder'>
 <div class='maintitle'>{$ibforums->lang['st_post']}</div>

EOF;
}

function split_row($row) {
global $ibforums;
return <<<EOF
  <div class='pformstrip'>{$row['st_top_bit']}</div>
  <div class='tablepad'>
   {$row['post']}
   <div align='right'><b>{$ibforums->lang['st_split']}</b>&nbsp;&nbsp;<input type='checkbox' name='post_{$row['pid']}' value='1' /></div>
 </div>
EOF;
}


function split_end_form($action) {
global $ibforums;
return <<<EOF
<div class='pformstrip' align='center'> <input type="submit" name="submit" value="$action" class='forminput' /></div>
</div>
</form>

EOF;
}

function merge_body($title="", $desc="") {
global $ibforums;
return <<<EOF
  <table cellspacing='1' width='100%'>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['mt_new_title']}</b></td>
	<td class='pformright'><input type='text' size='40' maxlength='50' name='title' value='$title' /></td>
   </tr>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['mt_new_desc']}</b></td>
	<td class='pformright'><input type='text' size='40' maxlength='40' name='desc' value='$desc' /></td>
   </tr>
   <tr>
	<td class='pformleftw'><b>{$ibforums->lang['mt_tid']}</b></td>
	<td class='pformright'><input type='text' size='50' name='topic_url' value='' /></td>
   </tr>
  </table>

EOF;
}


	} // end class

?>