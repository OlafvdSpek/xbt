<?php

class skin_mlist {



function Page_header($links) {
global $ibforums;
return <<<EOF
<script language='Javascript' type='text/javascript'>
<!--
function PopUp(url, name, width,height,center,resize,scroll,posleft,postop) {
	if (posleft != 0) { x = posleft }
	if (postop  != 0) { y = postop  }

	if (!scroll) { scroll = 1 }
	if (!resize) { resize = 1 }

	if ((parseInt (navigator.appVersion) >= 4 ) && (center)) {
	  X = (screen.width  - width ) / 2;
	  Y = (screen.height - height) / 2;
	}
	if (scroll != 0) { scroll = 1 }

	var Win = window.open( url, name, 'width='+width+',height='+height+',top='+Y+',left='+X+',resizable='+resize+',scrollbars='+scroll+',location=no,directories=no,status=no,menubar=no,toolbar=no');
 }
//-->
</script>
<form action='{$ibforums->base_url}' method='post'>
<input type='hidden' name='act' value='Members' />
<input type='hidden' name='s'   value='{$ibforums->session_id}' />
<div align="left">{$links[SHOW_PAGES]}</div>
<br />
<div class='tableborder'>
 <div class="maintitle">{$ibforums->lang['page_title']}</div>
 <table width="100%" border="0" cellspacing="1" cellpadding="4">
  <tr>
	<th class='pformstrip' width="30%">{$ibforums->lang['member_name']}</th>
	<th class='pformstrip' align="center" width="10%">{$ibforums->lang['member_level']}</th>
	<th class='pformstrip' align="center" width="10%">{$ibforums->lang['member_group']}</th>
	<th class='pformstrip' align="center" width="20%">{$ibforums->lang['member_joined']}</th>
	<th class='pformstrip' align="center" width="10%">{$ibforums->lang['member_posts']}</th>
	<th class='pformstrip' align="center">{$ibforums->lang['member_email']}</th>
	<th class='pformstrip' align="center">{$ibforums->lang['member_aol']}</th>
	<th class='pformstrip' align="center">{$ibforums->lang['member_icq']}</th>
	<th class='pformstrip' width="5%" align="center">{$ibforums->lang['member_photo']}</th>
	
  </tr>
EOF;
}

function end($links) {
global $ibforums;
return <<<EOF
<br />
<div align="left">{$links[SHOW_PAGES]}</div>
EOF;
}

function no_results() {
global $ibforums;
return <<<EOF
No results
EOF;
}

function start() {
global $ibforums;
return <<<EOF
<!-- nothing here -->
EOF;
}

function Page_end($checked="") {
global $ibforums;
return <<<EOF
  <!-- End content Table -->
  <tr> 
    <td class='row3' colspan="9" align='center' valign='middle'>
      <strong>{$ibforums->lang['photo_only']}&nbsp;<input type="checkbox" value="1" name="photoonly" class="forminput" $checked /></strong>
    </td>
  </tr>
  <tr> 
    <td class='pformstrip' colspan="9" align='center' valign='middle'>
      <select class='forminput' name='name_box'>
	 <option value='begins'>{$ibforums->lang['ch_begins']}</option>
	 <option value='contains'>{$ibforums->lang['ch_contains']}</option>
	 <option value='all' selected="selected">{$ibforums->lang['ch_all']}</option>
	 </select>&nbsp;&nbsp;<input class='forminput' type='text' size='25' name='name' value='{$ibforums->input['name']}' />
    </td>
  </tr>
  <tr>
   <td class='darkrow1' colspan="9" align='center' valign='middle'>
     {$ibforums->lang['sorting_text']}&nbsp;<input type='submit' value='{$ibforums->lang['sort_submit']}' class='forminput' />
   </td>
 </tr>
</table>
</div>
</form>
EOF;
}

function show_row($member) {
global $ibforums;
return <<<EOF
  <!-- Entry for {$member[MEMBER_NAME]} -->
  <tr>
	 <td class='row4'><strong><a href="{$ibforums->base_url}showuser={$member['id']}">{$member['name']}</a></strong></td>
	 <td class='row4'>{$member['pips']}</td>
	 <td class='row2' align="center" width="20%">{$member['group']}</td>
	 <td class='row4' align="center" width="20%">{$member['joined']}</td>
	 <td class='row4' align="center" width="10%">{$member['posts']}</td>
	 <td class='row2' align="center">{$member['member_email']}</td>
	 <td class='row2' align="center">{$member['aim_name']}</td>
	 <td class='row2' align="center">{$member['icq_number']}</td>
	 <td class='row2' align="center">{$member['camera']}</td>
  </tr>
  <!-- End of Entry -->
EOF;
}


}
?>