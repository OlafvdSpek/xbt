<?php

class skin_stats {


function who_header($fid, $tid, $title) {
global $ibforums;
return <<<EOF

<script language='javascript'>
<!--
 function bog_off()
 {
 	var tid = '$tid';
 	var fid = '$fid';
 	
 	opener.location= '$ibforums->base_url' + 'showtopic=' + tid;
 	self.close();
 }
 //-->
 </script>
<div class="tableborder">
  <div class="titlemedium" align="center">{$ibforums->lang['who_farted']} $title</div>
  <table cellpadding='5' cellspacing='1' border='0' width='100%'>
  <tr>
	 <td width='70%' align='left'   class='pformstrip'   valign='middle'>{$ibforums->lang['who_poster']}</td>
	 <td width='30%' align='center' class='pformstrip' valign='middle'>{$ibforums->lang['who_posts']}</td>
  </tr>
EOF;
}


function who_row($row) {
global $ibforums;
return <<<EOF
   <tr>
	  <td align='left' class='row1' valign='middle'>{$row['author_name']}</td>
	  <td align='center' class='row1' valign='middle'>{$row['pcount']}</td>
   </tr>
EOF;
}

function who_name_link($id, $name) {
global $ibforums;
return <<<EOF
<a href='{$ibforums->base_url}showuser=$id' target='_blank'>$name</a>
EOF;
}


function who_end() {
global $ibforums;
return <<<EOF
  </table>
  <div class='titlemedium' align='center'><a href='javascript:bog_off();'>{$ibforums->lang['who_go']}</a></div>
</div>
EOF;
}



function page_title($title) {
global $ibforums;
return <<<EOF
    <table cellpadding='0' cellspacing='0' border='0' width='<{tbl_width}>' align='center'>
      <tr>
         <td valign='middle' align='left'><span class='pagetitle'>{$title}</td>
      </tr>
     </table>
EOF;
}

function group_strip( $group ) {
global $ibforums;
return <<<EOF
<div class="tableborder">
 <div class="maintitle"><{CAT_IMG}>&nbsp;$group</div>
 <table cellpadding='4' cellspacing='1' width="100%">
  <tr>
	 <td width='30%' align='left'   class='pformstrip' valign='middle'>{$ibforums->lang['leader_name']}</td>
	 <td width='40%' align='center' class='pformstrip' valign='middle'>{$ibforums->lang['leader_forums']}</td>
	 <td align='center' width='25%' class='pformstrip' valign='middle'>{$ibforums->lang['leader_location']}</td>
	 <td align='center' width='5%'  class='pformstrip' valign='middle'>&nbsp;</td>
  </tr>
EOF;
}

function leader_row($info, $forums) {
global $ibforums;
return <<<EOF
  <tr>
	 <td align='left'   class='row1' valign='middle'><a href='{$ibforums->base_url}showuser={$info['id']}'>{$info['name']}</a></td>
	 <td align='center' class='row1' valign='middle'>$forums</td>
	 <td align='center' class='row1' valign='middle'>{$info['location']}</td>
	 <td align='center' class='row1' valign='middle'>{$info['msg_icon']}</td>
  </tr>
EOF;
}

function leader_row_forum_start($id, $count_string) {
global $ibforums;
return <<<EOF
<form method="post" onsubmit="if(document.jmenu$id.f.value == -1){return false;}" action="{$ibforums->base_url}act=SF" name="jmenu$id">
<select class='forminput' name="f" onchange="if(this.options[this.selectedIndex].value != -1){ document.jmenu$id.submit() }" style='width:95%'>
<option value="-1">$count_string</option>
<option value="-1">--------------------------------------------------------</option>
EOF;
}

function leader_row_forum_entry($id, $name) {
global $ibforums;
return <<<EOF
<option value='$id'>$name</option>
EOF;
}

function leader_row_forum_end() {
global $ibforums;
return <<<EOF
</select></form>
EOF;
}

function close_strip() {
global $ibforums;
return <<<EOF
  </table>
</div>
<br />
EOF;
}

function top_poster_header() {
global $ibforums;
return <<<EOF
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['todays_posters']}</div>
 <table cellpadding='5' cellspacing='1' border='0' width='100%'>
 <tr>
	<th width='30%' align='left' class='pformstrip'   valign='middle'>{$ibforums->lang['member']}</th>
	<th width='20%' align='center' class='pformstrip' valign='middle'>{$ibforums->lang['member_joined']}</th>
	<th align='center' width='15%' class='pformstrip' valign='middle'>{$ibforums->lang['member_posts']}</th>
	<th align='center' width='15%' class='pformstrip' valign='middle'>{$ibforums->lang['member_today']}</th>
	<th align='center' width='20%' class='pformstrip' valign='middle'>{$ibforums->lang['member_percent']}</th>
 </tr>
EOF;
}

function top_poster_row($info) {
global $ibforums;
return <<<EOF
   <tr>
	  <td align='left' class='row1' valign='middle'><a href='{$ibforums->base_url}showuser={$info['id']}'>{$info['name']}</a></td>
	  <td align='center' class='row1' valign='middle'>{$info['joined']}</td>
	  <td align='center' class='row1' valign='middle'>{$info['posts']}</td>
	  <td align='center' class='row1' valign='middle'>{$info['tpost']}</td>
	  <td align='center' class='row1' valign='middle'>{$info['today_pct']}%</td>
   </tr>
EOF;
}

function top_poster_footer($info) {
global $ibforums;
return <<<EOF
  </table>
  <div class='pformstrip' align='center'>{$ibforums->lang['total_today']} $info</div>
</div>
EOF;
}

function top_poster_no_info() {
global $ibforums;
return <<<EOF
   <tr>
	  <td colspan='5' align='center' class='row1' valign='middle'>{$ibforums->lang['no_info']}</td>
   </tr>
EOF;
}


}
?>