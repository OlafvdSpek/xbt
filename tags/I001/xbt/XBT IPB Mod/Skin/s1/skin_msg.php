<?php

class skin_msg {


function preview($data) {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['pm_preview']}</div>
<p>$data</p>
EOF;
}


function pm_errors($data) {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['err_errors']}</div>
<span class='postcolor'><p>$data<br /><br />{$ibforums->lang['pme_none_sent']}</p></span>
EOF;
}



function pm_popup($text, $mid) {
global $ibforums;
return <<<EOF
<script language='javascript'>
<!--
 function goto_inbox() {
 	opener.document.location.href = '{$ibforums->base_url}act=Msg&amp;CODE=01';
 	window.close();
 }
 
 function goto_this_inbox() {
 	window.resizeTo('700','500');
 	document.location.href = '{$ibforums->base_url}&act=Msg&CODE=01';
 }
 
 function go_read_msg() {
 	window.resizeTo('700','500');
 	document.location.href = '{$ibforums->base_url}&act=Msg&CODE=03&VID=in&MSID=$mid';
 }
 
//-->
</script>

<table cellspacing='1' cellpadding='10' width='100%' height='100%' align='center' class='row1'>
<tr>
   <td id='phototitle' align='center'>{$ibforums->lang['pmp_title']}</td>
</tr>
<tr>
   <td align='center'>$text</td>
</tr>
<tr>
   <td align='center' style='font-size:12px;font-weight:bold'>
   <a href='javascript:go_read_msg();'>{$ibforums->lang['pmp_get_last']}</a>
   <br /><br />
   <a href='javascript:goto_inbox();'>{$ibforums->lang['pmp_go_inbox']}</a> ( <a href='javascript:goto_this_inbox();'>{$ibforums->lang['pmp_thiswindow']}</a> )<br /><br /><a href='javascript:window.close();'>{$ibforums->lang['pmp_ignore']}</a></td>
</tr>
</table>
EOF;
}

function archive_html_header() {
global $ibforums;
return <<<EOF
<html>
 <head>
  <title>Private Message Archive</title>
 </head>
 <style type='text/css'>
	 BODY { font-family: Verdana, Tahoma, Arial, sans-serif;
			font-size: 11px;
			color: #000;
			margin:0px;
			padding:0px;
			background-color:#FFF;
			text-align:center
		   }
		   
	#ipbwrapper { text-align:left; width:95%; margin-left:auto;margin-right:auto }
	
	html { overflow-x: auto; } 
	
	a:link, a:visited, a:active { text-decoration: underline; color: #000 }
	a:hover { color: #465584; text-decoration:underline }
	img        { vertical-align:middle; border:0px }
	
	.post1 { background-color: #F5F9FD }
	.post2 { background-color: #EEF2F7 }
	
	/* Common elements */
	.row1 { background-color: #F5F9FD }
	.row2 { background-color: #DFE6EF }
	.row3 { background-color: #EEF2F7 }
	.row4 { background-color: #E4EAF2 }
	
	/* tableborders gives the white column / row lines effect */
	.plainborder { border:1px solid #345487;background-color:#F5F9FD }
	.tableborder { border:1px solid #345487;background-color:#FFF; padding:0; margin:0 }
	.tablefill   { border:1px solid #345487;background-color:#F5F9FD;padding:6px;  }
	.tablepad    { background-color:#F5F9FD;padding:6px }
	.tablebasic  { width:100%; padding:0px 0px 0px 0px; margin:0px; border:0px }
	
	.pformstrip { background-color: #D1DCEB; color:#3A4F6C;font-weight:bold;padding:7px;margin-top:1px }
	#QUOTE { font-family: Verdana, Arial; font-size: 11px; color: #465584; background-color: #FAFCFE; border: 1px solid #000; padding-top: 2px; padding-right: 2px; padding-bottom: 2px; padding-left: 2px }
	#CODE  { font-family: Courier, Courier New, Verdana, Arial;  font-size: 11px; color: #465584; background-color: #FAFCFE; border: 1px solid #000; padding-top: 2px; padding-right: 2px; padding-bottom: 2px; padding-left: 2px }

	
	/* Main table top (dark blue gradient by default) */
	.maintitle { vertical-align:middle;font-weight:bold; color:#FFF; background-color:D1DCEB;padding:8px 0px 8px 5px; background-image: url({$ibforums->vars['board_url']}/style_images/<#IMG_DIR#>/tile_back.gif) }
	.maintitle a:link, .maintitle  a:visited, .maintitle  a:active { text-decoration: none; color: #FFF }
	.maintitle a:hover { text-decoration: underline }
    
    /* Topic View elements */
	.signature   { font-size: 10px; color: #339; line-height:150% }
	.postdetails { font-size: 10px }
	.postcolor   { font-size: 12px; line-height: 160% }
 </style>
 <body>
 <div id='ipbwrapper'>
EOF;
}

function archive_html_entry($info) {
global $ibforums;
return <<<EOF
<div class='tableborder'>
 <div class='maintitle'><img src="{$ibforums->vars['board_url']}/style_images/<#IMG_DIR#>/f_norm.gif" alt='PM' />&nbsp;PM: {$info['msg_title']}</div>
 <div class='tablefill'><div class='postcolor'>{$info['msg_content']}</div></div>
 <div class='pformstrip'>Sent by <b>{$info['msg_sender']}</b> on {$info['msg_date']}</div>
</div>
<br />
EOF;
}

function archive_html_entry_sent($info) {
global $ibforums;
return <<<EOF
<div class='tableborder'>
 <div class='maintitle'><img src="{$ibforums->vars['board_url']}/style_images/<#IMG_DIR#>/f_moved.gif" alt='PM' />&nbsp;PM: {$info['msg_title']}</div>
 <div class='tablefill'><div class='postcolor'>{$info['msg_content']}</div></div>
 <div class='pformstrip'>Sent to <b>{$info['msg_sender']}</b> on {$info['msg_date']}</div>
</div>
<br />
EOF;
}

function archive_html_footer() {
global $ibforums;
return <<<EOF
  </div>
 </body>
</html>
EOF;
}

function archive_complete() {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['arc_comp_title']}</div>
<p>{$ibforums->lang['arc_complete']}</p>
EOF;
}

function archive_form($jump_html="") {
global $ibforums;
return <<<EOF
<form action="{$ibforums->base_url}" method="post">
<input type='hidden' name='act' value='Msg' />
<input type='hidden' name='CODE' value='15' />
<div class="pformstrip">{$ibforums->lang['archive_title']}</div>
<p>{$ibforums->lang['archive_text']}</p>
<table width="100%" style="padding:6px">
<tr>
   <td><b>{$ibforums->lang['arc_folders']}</b></td>
   <td>$jump_html</td>
</tr>
<tr>
   <td><b>{$ibforums->lang['arc_dateline']}</b></td>
   <td valign='middle'><select name='dateline' class='forminput'>
	 <option value='1'>1</option>
	 <option value='7'>7</option>
	 <option value='30' selected='selected'>30</option>
	 <option value='90'>90</option>
	 <option value='365'>365</option>
	 <option value='all'>{$ibforums->lang['arc_alldays']}</option>
	 </select>&nbsp;&nbsp;{$ibforums->lang['arc_days']}
	 <select name='oldnew' class='forminput'>
	  <option value='newer' selected='selected'>{$ibforums->lang['arch_new']}</option>
	  <option value='older'>{$ibforums->lang['arch_old']}</option>
	 </select>
   </td>
</tr>
<tr>
   <td><b>{$ibforums->lang['arc_max']}</b></td>
   <td valign='middle'><select name='number' class='forminput'><option value='5'>5</option><option value='10'>10</option><option value='20' selected>20</option><option value='30'>30</option><option value='40'>40</option><option value='50'>50</option></select></td>
</tr>
<tr>
   <td><b>{$ibforums->lang['arc_delete']}</b></td>
   <td valign='middle'><select name='delete' class='forminput'><option value='yes'>{$ibforums->lang['arc_yes']}</option><option value='no' selected='selected'>{$ibforums->lang['arc_no']}</option></select></td>
</tr>
<tr>
   <td><b>{$ibforums->lang['arc_type']}</b></td>
   <td valign='middle'><select name='type' class='forminput'><option value='xls' selected>{$ibforums->lang['arc_xls']}</option><option value='html'>{$ibforums->lang['arc_html']}</option></select></td>
</tr>
</table>
<div class="pformstrip" align="center"><input type="submit" value="{$ibforums->lang['arc_submit']}" class='forminput' /></div>
</form>
EOF;
}

function No_msg_inbox() {
global $ibforums;
return <<<EOF
      <tr>
      <td class='row1' colspan='5' align='center'><b>{$ibforums->lang['inbox_no_msg']}</b></td>                
      </tr>
EOF;
}

function empty_folder_header() {
global $ibforums;
return <<<EOF
<form action="{$ibforums->base_url}" method="post">
<input type='hidden' name='act' value='Msg' />
<input type='hidden' name='CODE' value='dofolderdelete' />
<div class="pformstrip">{$ibforums->lang['mi_prune_msg']}</div>
<p>{$ibforums->lang['fd_text']}</p>
<div class="tableborder">
<table cellpadding='4' cellspacing='1' width="100%">
<tr>
  <th class="titlemedium">{$ibforums->lang['fd_name']}</th>
  <th class="titlemedium">{$ibforums->lang['fd_count']}</th>
  <th class="titlemedium">{$ibforums->lang['fd_empty']}</th>
</tr>
EOF;
}

function empty_folder_row($real, $id, $cnt) {
global $ibforums;
return <<<EOF
<tr>
  <td class="row1"><strong>$real</strong></td>
  <td class="row1" align="center">$cnt</td>
  <td class="row1" align="center"><input type="checkbox" class="checkbox" name="its_$id" value="1" /></td>
</tr>
EOF;
}

function empty_folder_save_unread() {
global $ibforums;
return <<<EOF
<tr>
  <td class="row2" colspan='3' align='center'><input type="checkbox" class="checkbox" name="save_unread" value="1" checked="checked"/> <strong>{$ibforums->lang['fd_save_unread']}</strong></td>
</tr>
EOF;
}

function empty_folder_footer() {
global $ibforums;
return <<<EOF
</table>
<div class="pformstrip" align="center"><input type='submit' value='{$ibforums->lang['fd_continue']}' class='forminput'></div>
</div>
</form>
EOF;
}

function prefs_header() {
global $ibforums;
return <<<EOF
<form action="{$ibforums->base_url}" method="post">
<input type='hidden' name='act' value='Msg' />
<input type='hidden' name='CODE' value='08' />
<div class="pformstrip">{$ibforums->lang['prefs_current']}</div>
<p>{$ibforums->lang['prefs_text_a']}</p>
EOF;
}


function prefs_row($data) {
global $ibforums;
return <<<EOF
<p><input type='text' name='{$data[ID]}' value='{$data[REAL]}' class='forminput' />{$data[EXTRA]}</p>
EOF;
}

function prefs_add_dirs() {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['prefs_new']}</div>
<p>{$ibforums->lang['prefs_text_b']}</p>

EOF;
}

function prefs_footer() {
global $ibforums;
return <<<EOF
<div class="pformstrip" align="center"><input type='submit' value='{$ibforums->lang['prefs_submit']}' class='forminput'></div>
</form>
EOF;
}



function Address_header() {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['address_current']}</div>
EOF;
}

function Address_table_header() {
global $ibforums;
return <<<EOF
<br />
<div class="tableborder">
<table cellpadding='4' cellspacing='1' width="100%">
<tr>
  <td class="titlemedium"><b>{$ibforums->lang['member_name']}</b></td>
  <td width="60%" class="titlemedium"><b>{$ibforums->lang['enter_block']}</b></td>
</tr>
EOF;
}


function render_address_row($entry) {
global $ibforums;
return <<<EOF
<tr>
  <td class='row1 align='left' valign='middle'><a href='{$ibforums->base_url}act=Profile&amp;CODE=03&amp;MID={$entry['contact_id']}'><b>{$entry['contact_name']}</b></a> &nbsp; &nbsp;[ {$entry['contact_desc']} ]</td>
  <td class='row1 align='left' valign='middle'>
	   [ <a href='{$ibforums->base_url}act=Msg&amp;CODE=11&amp;MID={$entry['contact_id']}'>{$ibforums->lang['edit']}</a> ] :: [ <a href='{$ibforums->base_url}act=Msg&amp;CODE=10&amp;MID={$entry['contact_id']}'>{$ibforums->lang['delete']}</a> ]
		&nbsp;&nbsp;( {$entry['text']} )
  </td>
</tr>
EOF;
}


function Address_none() {
global $ibforums;
return <<<EOF
<p style="text-align:center">{$ibforums->lang['address_none']}</p>
EOF;
}

function end_address_table() {
global $ibforums;
return <<<EOF
</table>
</div>
<br />
EOF;
}

function address_edit($data) {
global $ibforums;
return <<<EOF
<form action="{$ibforums->base_url}" method="post">
<input type='hidden' name='act' value='Msg'>
<input type='hidden' name='CODE' value='12'>
<input type='hidden' name='MID' value='{$data[MEMBER]['contact_id']}'>
<div class="pformstrip">{$ibforums->lang['member_edit']}</div>
<table width="100%">
<tr>
 <td valign='middle' align='left'><b>{$data[MEMBER]['contact_name']}</b></td>
 <td valign='middle' align='left'>{$ibforums->lang['enter_desc']}<br /><input type='text' name='mem_desc' size='30' maxlength='60' value='{$data[MEMBER]['contact_desc']}' class='forminput' /></td>
 <td valign='middle' align='left'>{$ibforums->lang['allow_msg']}<br />{$data[SELECT]}</td>
</tr>
</table>
<div class="pformstrip" align="center"><input type="submit" value="{$ibforums->lang['submit_address_edit']}" class='forminput' /></div>
</form>
EOF;
}



function address_add($mem_to_add) {
global $ibforums;
return <<<EOF
<form action="{$ibforums->base_url}" method="post">
<input type='hidden' name='act' value='Msg' />
<input type='hidden' name='CODE' value='09' />
<div class="pformstrip">{$ibforums->lang['member_add']}</div>
<table style="padding:6px">
<tr>
 <td valign='middle'>{$ibforums->lang['enter_a_name']}<br /><input type='text' name='mem_name' size='20' maxlength='40' value='$mem_to_add' class='forminput' /></td>
 <td valign='middle'>{$ibforums->lang['enter_desc']}<br /><input type='text' name='mem_desc' size='30' maxlength='60' value='' class='forminput' /></td>
 <td valign='middle'>{$ibforums->lang['allow_msg']}<br /><select name='allow_msg' class='forminput'><option value='yes' selected="selected">{$ibforums->lang['yes']}<option value='no'>{$ibforums->lang['no']}</select></td>
</tr>
</table>
<div class="pformstrip" align="center"><input type="submit" value="{$ibforums->lang['submit_address']}" class='forminput' /></div>
</form>
EOF;
}





function Render_msg($data) {
global $ibforums;
return <<<EOF
<script language='javascript' type="text/javascript">
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
<div class="pformstrip">{$data['msg']['title']}</div>
<div align="right" style="padding:6px;font-weight:bold">
  [ <a href='{$ibforums->base_url}CODE=04&amp;act=Msg&amp;MSID={$data['msg']['msg_id']}&amp;MID={$data['member']['id']}&amp;fwd=1'>{$ibforums->lang['vm_forward_pm']}</a> | <a href='{$ibforums->base_url}CODE=04&amp;act=Msg&amp;MID={$data['member']['id']}&amp;MSID={$data['msg']['msg_id']}'>{$ibforums->lang['pm_reply_link']}</a> ]
</div>
<div class="tableborder">
 <div class="titlemedium">&nbsp;&nbsp;{$ibforums->lang['m_pmessage']}</div>
 <table width='100%' cellpadding='3' cellspacing='1'>
  <tr>
      <td valign='middle' class='row4'><span class='normalname'><a href="{$ibforums->base_url}showuser={$data['member']['id']}">{$data['member']['name']}</a></span></td>
        <td class='row4' valign='top'>
        
        <!-- POSTED DATE DIV -->
        
        <div align='left' class='row4' style='float:left;padding-top:4px;padding-bottom:4px'>
        {$data[POST]['post_icon']}<span class='postdetails'><b>{$data['msg']['title']}</b>, {$data['msg']['msg_date']}</span>
        </div>
        
        <!-- DELETE  DIV -->
        
        <div align='right'>
          <a href='{$ibforums->base_url}CODE=05&amp;act=Msg&amp;MSID={$data['msg']['msg_id']}&amp;VID={$data['member']['VID']}'><{P_DELETE}></a>
          &nbsp;<a href='{$ibforums->base_url}CODE=04&amp;act=Msg&amp;MID={$data['member']['id']}&amp;MSID={$data['msg']['msg_id']}'><{P_QUOTE}></a>
        </div>
      
      </td>
    </tr>
    <tr>
      <td valign='top' class='post1'>
        <span class='postdetails'>
        {$data['member']['avatar']}
        <br />{$data['member']['title']}
        <br />{$data['member']['member_rank_img']}<br />
        <br />{$data['member']['member_group']}
        <br />{$data['member']['member_posts']}
        <br />{$data['member']['member_joined']}
        <br />
        </span>
        <img src='{$ibforums->vars['img_url']}/spacer.gif' alt='' width='160' height='1' /><br /> 
      </td>
      <td width='100%' valign='top' class='post1'><span class='postcolor'>{$data['msg']['message']}</span><span class="signature">{$data['member']['signature']}</span></td>
    </tr>
    <tr>
      <td class='darkrow3' align='left'>[ <a href='{$ibforums->base_url}CODE=02&amp;act=Msg&amp;MID={$data['member']['id']}'>{$ibforums->lang['add_to_book']}</a> ]</td>
      <td class='darkrow3' nowrap="nowrap" align='left'>
      
        <!-- EMAIL / WWW / MSGR -->
      
        <div align='left' class='darkrow3' style='float:left;'>
        {$data['member']['addresscard']}{$data['member']['message_icon']}{$data['member']['email_icon']}{$data['member']['website_icon']}{$data['member']['integ_icon']}{$data['member']['icq_icon']}{$data['member']['aol_icon']}{$data['member']['yahoo_icon']}{$data['member']['msn_icon']}
      </div>
        
        <!-- UP -->
         
        <div align='right'><a href='javascript:scroll(0,0);'><img src='{$ibforums->vars['img_url']}/p_up.gif' alt='Top' border='0' /></a></div>
      </td>
    </tr>
</table>
</div>
<div style="float:left;width:auto;padding:6px">
<form action="{$ibforums->base_url}" name='jump' method="post">
<input type='hidden' name='act' value='Msg'>
<input type='hidden' name='CODE' value='01'>
{$ibforums->lang[goto_folder]}:</b>&nbsp; {$data['jump']}
<input type='submit' name='submit' value='{$ibforums->lang[goto_submit]}' class='forminput' />
</form>
</div>
<div align="right" style="padding:6px;font-weight:bold">
  [ <a href='{$ibforums->base_url}CODE=04&amp;act=Msg&amp;MSID={$data['msg']['msg_id']}&amp;MID={$data['member']['id']}&amp;fwd=1'>{$ibforums->lang['vm_forward_pm']}</a> | <a href='{$ibforums->base_url}CODE=04&amp;act=Msg&amp;MID={$data['member']['id']}&amp;MSID={$data['msg']['msg_id']}'>{$ibforums->lang['pm_reply_link']}</a> ]
</div>

EOF;
}

function trackread_table_header() {
global $ibforums;
return <<<EOF
<script language='JavaScript' type="text/javascript">
<!--
function CheckAll(fmobj) {
	for (var i=0;i<fmobj.elements.length;i++) {
		var e = fmobj.elements[i];
		if ((e.name != 'allbox') && (e.type=='checkbox') && (!e.disabled)) {
			e.checked = fmobj.allbox.checked;
		}
	}
}
function CheckCheckAll(fmobj) {	
	var TotalBoxes = 0;
	var TotalOn = 0;
	for (var i=0;i<fmobj.elements.length;i++) {
		var e = fmobj.elements[i];
		if ((e.name != 'allbox') && (e.type=='checkbox')) {
			TotalBoxes++;
			if (e.checked) {
				TotalOn++;
			}
		}
	}
	if (TotalBoxes==TotalOn) {fmobj.allbox.checked=true;}
	else {fmobj.allbox.checked=false;}
}
//-->
</script>
<form action="{$ibforums->base_url}CODE=31&amp;act=Msg" name='trackread' method="post">
<div class='pformstrip'>{$ibforums->lang['tk_read_messages']}</div>
<p>{$ibforums->lang['tk_read_desc']}</p>
<div class="tableborder">
<table cellpadding='4' cellspacing='1' align='center' width='100%'>
<tr>
  <th align='left' width='5%' class='titlemedium'>&nbsp;</th>
  <th align='left' width='30%' class='titlemedium'><b>{$ibforums->lang['message_title']}</b></th>
  <th align='left' width='30%' class='titlemedium'><b>{$ibforums->lang['pms_message_to']}</b></th>
  <th align='left' width='20%' class='titlemedium'><b>{$ibforums->lang['tk_read_date']}</b></th>
  <th align='left' width='5%' class='titlemedium'><input name="allbox" type="checkbox" value="Check All" onclick="CheckAll(document.trackread);" /></th>
</tr>
EOF;
}

function trackread_row($data) {
global $ibforums;
return <<<EOF
<tr>
  <td class='row2' align='left' valign='middle'>{$data['icon']}</td>
  <td class='row2' align='left'>{$data['title']}</td>
  <td class='row2' align='left'><a href='{$ibforums->base_url}showuser={$data['memid']}'>{$data['to_name']}</a></td>
  <td class='row2' align='left'>{$data['date']}</td>
  <td class='row2' align='left'><input type='checkbox' name='msgid_{$data['msg_id']}' value='yes' class='forminput' onclick="CheckCheckAll(document.trackread);" /></td>
</tr>
EOF;
}

function trackread_end() {
global $ibforums;
return <<<EOF
<tr>
 <td align='right' class='titlemedium' colspan='5'><input type='submit' name='endtrack' value='{$ibforums->lang['tk_untrack_button']}' class='forminput' /> {$ibforums->lang['selected_msg']}</td>
</tr>
</table>
</div>
</form>
<br />
EOF;
}







function trackUNread_table_header() {
global $ibforums;
return <<<EOF
<form action="{$ibforums->base_url}CODE=32&amp;act=Msg" name='trackunread' method="post">
<div class='pformstrip'>{$ibforums->lang['tk_unread_messages']}</div>
<p>{$ibforums->lang['tk_unread_desc']}</p>
<div class="tableborder">
<table cellpadding='4' cellspacing='1' align='center' width='100%'>
<tr>
  <th align='left' width='5%' class='titlemedium'>&nbsp;</td>
  <th align='left' width='30%' class='titlemedium'><b>{$ibforums->lang['message_title']}</b></th>
  <th align='left' width='30%' class='titlemedium'><b>{$ibforums->lang['pms_message_to']}</b></th>
  <th align='left' width='20%' class='titlemedium'><b>{$ibforums->lang['tk_unread_date']}</b></th>
  <th align='left' width='5%' class='titlemedium'><input name="allbox" type="checkbox" value="Check All" onclick="CheckAll(document.trackunread);" /></th>
</tr>
EOF;
}

function trackUNread_row($data) {
global $ibforums;
return <<<EOF
<tr>
  <td class='row2' align='left' valign='middle'>{$data['icon']}</td>
  <td class='row2' align='left'>{$data['title']}</td>
  <td class='row2' align='left'><a href='{$ibforums->base_url}showuser={$data['memid']}'>{$data['to_name']}</a></td>
  <td class='row2' align='left'>{$data['date']}</td>
  <td class='row2' align='left'><input type='checkbox' name='msgid_{$data['msg_id']}' value='yes' class='forminput' onclick="CheckCheckAll(document.trackunread);" /></td>
</tr>
EOF;
}

function trackUNread_end() {
global $ibforums;
return <<<EOF
<tr>
 <td align='right' class='titlemedium' colspan='5'><input type='submit' name='delete' value='{$ibforums->lang['delete_button']}' class='forminput' /> {$ibforums->lang['selected_msg']}</td>
</tr>
</table>
</div>
</form>
                  
EOF;
}




function unsent_table_header() {
global $ibforums;
return <<<EOF
<script language='JavaScript' type="text/javascript">
<!--
function CheckAll(cb) {
	var fmobj = document.mutliact;
	for (var i=0;i<fmobj.elements.length;i++) {
		var e = fmobj.elements[i];
		if ((e.name != 'allbox') && (e.type=='checkbox') && (!e.disabled)) {
			e.checked = fmobj.allbox.checked;
		}
	}
}
function CheckCheckAll(cb) {	
	var fmobj = document.mutliact;
	var TotalBoxes = 0;
	var TotalOn = 0;
	for (var i=0;i<fmobj.elements.length;i++) {
		var e = fmobj.elements[i];
		if ((e.name != 'allbox') && (e.type=='checkbox')) {
			TotalBoxes++;
			if (e.checked) {
				TotalOn++;
			}
		}
	}
	if (TotalBoxes==TotalOn) {fmobj.allbox.checked=true;}
	else {fmobj.allbox.checked=false;}
}
//-->
</script>

<form action="{$ibforums->base_url}CODE=06&amp;act=Msg&amp;saved=1" name='mutliact' method="post">
<div class="pformstrip">{$ibforums->lang['pms_saved_title']}</div>
<br />
<div class="tableborder">
<table cellpadding='4' cellspacing='1' align='center' width='100%'>
<tr>
  <th align='left' width='5%' class='titlemedium'>&nbsp;</td>
  <th align='left' width='30%' class='titlemedium'><b>{$ibforums->lang['message_title']}</b></th>
  <th align='left' width='30%' class='titlemedium'><b>{$ibforums->lang['pms_message_to']}</b></th>
  <th align='left' width='20%' class='titlemedium'><b>{$ibforums->lang['pms_saved_date']}</b></th>
  <th align='left' width='10%' class='titlemedium'><b>{$ibforums->lang['pms_cc_users']}</b></th>
  <th align='left' width='5%' class='titlemedium'><input name="allbox" type="checkbox" value="Check All" onclick="CheckAll();" /></th>
</tr>
EOF;
}


function unsent_row($data) {
global $ibforums;
return <<<EOF
<tr>
  <td class='row2' align='left' valign='middle'>{$data['msg']['icon']}</td>
  <td class='row2' align='left'><a href='{$ibforums->base_url}act=Msg&amp;CODE=21&amp;MSID={$data['msg']['msg_id']}'>{$data['msg']['title']}</a></td>
  <td class='row2' align='left'><a href='{$ibforums->base_url}showuser={$data['msg']['recipient_id']}'>{$data['msg']['to_name']}</a></td>
  <td class='row2' align='left'>{$data['msg']['date']}</td>
  <td class='row2' align='center'>{$data['msg']['cc_users']}</td>
  <td class='row2' align='left'><input type='checkbox' name='msgid_{$data['msg']['msg_id']}' value='yes' class='forminput' /></td>
</tr>
EOF;
}

function unsent_end() {
global $ibforums;
return <<<EOF
<tr>
 <td align='center' nowrap class='titlemedium' colspan='6'><input type='submit' name='delete' value='{$ibforums->lang['delete_button']}' class='forminput' /> {$ibforums->lang['selected_msg']}</td>
</tr>
</table>
</div>
</form>
                  
EOF;
}

function inbox_table_header($dirname, $info, $vdi_html="", $pages="") {
global $ibforums;
return <<<EOF
<!-- inbox folder -->
<script language='JavaScript' type="text/javascript">
<!--

var ie  = document.all  ? 1 : 0;
//var ns4 = document.layers ? 1 : 0;

function hl(cb)
{
   if (ie)
   {
	   while (cb.tagName != "TR")
	   {
		   cb = cb.parentElement;
	   }
   }
   else
   {
	   while (cb.tagName != "TR")
	   {
		   cb = cb.parentNode;
	   }
   }
   cb.className = 'hlight';
}

function dl(cb) {
   if (ie)
   {
	   while (cb.tagName != "TR")
	   {
		   cb = cb.parentElement;
	   }
   }
   else
   {
	   while (cb.tagName != "TR")
	   {
		   cb = cb.parentNode;
	   }
   }
   cb.className = 'dlight';
}

function cca(cb) {
   if (cb.checked)
   {
	   hl(cb);
   }
   else
   {
	   dl(cb);
   }
}
	   
function CheckAll(cb) {
	var fmobj = document.mutliact;
	for (var i=0;i<fmobj.elements.length;i++) {
		var e = fmobj.elements[i];
		if ((e.name != 'allbox') && (e.type=='checkbox') && (!e.disabled)) {
			e.checked = fmobj.allbox.checked;
			if (fmobj.allbox.checked)
			{
			   hl(e);
			}
			else
			{
			   dl(e);
			}
		}
	}
}
function CheckCheckAll(cb) {	
	var fmobj = document.mutliact;
	var TotalBoxes = 0;
	var TotalOn = 0;
	for (var i=0;i<fmobj.elements.length;i++) {
		var e = fmobj.elements[i];
		if ((e.name != 'allbox') && (e.type=='checkbox')) {
			TotalBoxes++;
			if (e.checked) {
				TotalOn++;
			}
		}
	}
	if (TotalBoxes==TotalOn) {fmobj.allbox.checked=true;}
	else {fmobj.allbox.checked=false;}
}

function select_read() {	
	var fmobj = document.mutliact;
	for (var i=0;i<fmobj.elements.length;i++) {
		var e = fmobj.elements[i];
		if ((e.type=='hidden') && (e.value == 1) && (! isNaN(e.name) ))
		{
			eval("fmobj.msgid_" + e.name + ".checked=true;");
			hl(e);
		}
	}
}

function unselect_all() {	
	var fmobj = document.mutliact;
	for (var i=0;i<fmobj.elements.length;i++) {
		var e = fmobj.elements[i];
		if (e.type=='checkbox') {
			e.checked=false;
			dl(e);
		}
	}
}

//-->
</script>
<div class="pformstrip">$dirname</div>
<table width="100%" style="padding:6px">
<tr>
 <td valign="middle">
  <!-- LEFT -->
  <table style="width:250px" cellspacing="1" class="tableborder">
   <tr>
	<td class='row1' align='left' colspan='3'>{$info['full_messenger']}</td>
   </tr>
   <tr>
	<td align='left' valign='middle' class='row2' colspan='3'><img src='{$ibforums->vars['img_url']}/bar_left.gif' border='0' width='4' height='11' align='middle' alt='' /><img src='{$ibforums->vars['img_url']}/bar.gif' border='0' width='{$info['img_width']}' height='11' align='middle' alt='' /><img src='{$ibforums->vars['img_url']}/bar_right.gif' border='0' width='4' height='11' align='middle' alt='' /></td>
   </tr>
   <tr>
	 <td class='row1' width='33%' align='left' valign='middle'>0%</td>
	 <td class='row1' width='33%' align='center' valign='middle'>50%</td>
	 <td class='row1' width='33%' align='right' valign='middle'>100%</td>
   </tr>
  </table>
 </td>
 <!-- RIGHT -->
 <td align="right" valign="bottom" style="line-height:100%;">
  $pages<br /><br />
  <a href='javascript:select_read()'>{$ibforums->lang['pmpc_mark_read']}</a> :: <a href='javascript:unselect_all()'>{$ibforums->lang['pmpc_unmark_all']}</a><br /><br />
  <form action="{$ibforums->base_url}CODE=01&amp;act=Msg" name='jump' method="post">
  <b>{$ibforums->lang['goto_folder']}: </b>&nbsp; $vdi_html <input type='submit' name='submit' value='{$ibforums->lang['goto_submit']}' class='forminput' />
  </form>
 </td>
</tr>
</table>

<!-- INBOX TABLE -->
  
<form action="{$ibforums->base_url}CODE=06&amp;act=Msg" name='mutliact' method="post">
<div class="tableborder">
  <table cellpadding='4' cellspacing='1' width="100%">
  <tr>
	<th width='5%'  class='titlemedium'>&nbsp;</th>
	<th width='35%' class='titlemedium'><a href='{$ibforums->base_url}act=Msg&amp;CODE=01&amp;VID={$info['vid']}&amp;sort=title&amp;st={$ibforums->input['st']}'><b>{$ibforums->lang['message_title']}</b></a></th>
	<th width='30%' class='titlemedium'><a href='{$ibforums->base_url}act=Msg&amp;CODE=01&amp;VID={$info['vid']}&amp;sort=name&amp;st={$ibforums->input['st']}'><b>{$ibforums->lang['message_from']}</b></a></th>
	<th width='25%' class='titlemedium'><a href='{$ibforums->base_url}act=Msg&amp;CODE=01&amp;VID={$info['vid']}&amp;sort={$info['date_order']}&amp;st={$ibforums->input['st']}'><b>{$ibforums->lang['message_date']}</b></a></th>
	<th width='5%'  class='titlemedium'><input name="allbox" type="checkbox" value="Check All" onclick="CheckAll();" /></th>
  </tr>
EOF;
}

function inbox_row($data) {
global $ibforums;
return <<<EOF
  <tr class="dlight">
	<td align="center" valign='middle'>{$data['msg']['icon']}</td>
	<td><a href='{$ibforums->base_url}act=Msg&amp;CODE=03&amp;VID={$data['stat']['current_id']}&amp;MSID={$data['msg']['msg_id']}'>{$data['msg']['title']}</a></td>
	<td><a href='{$ibforums->base_url}showuser={$data['msg']['from_id']}'>{$data['msg']['from_name']}</a> {$data['msg']['add_to_contacts']}</td>
	<td>{$data['msg']['date']}</td>
	<td align="center"><input type='hidden' name='{$data['msg']['msg_id']}' value='{$data['msg']['read_state']}' /><input type='checkbox' name='msgid_{$data['msg']['msg_id']}' value='yes' class='forminput' onclick="cca(this);" /></td>
  </tr>
EOF;
}

function end_inbox($vdi_html, $amount_info="", $pages="") {
global $ibforums;
return <<<EOF
  <tr>
   <td align='right' class='titlemedium' colspan='5'>
	 <input type='submit' name='move' value='{$ibforums->lang['move_button']}' class='forminput' /> $vdi_html {$ibforums->lang['move_or_delete']} <input type='submit' name='delete' value='{$ibforums->lang['delete_button']}' class='forminput' /> {$ibforums->lang['selected_msg']}
  </td>
</tr>
</table>
</div>
</form>
<div class="wrapmini" style="padding:6px"><{M_READ}>&nbsp;{$ibforums->lang['icon_read']}<br /><{M_UNREAD}>&nbsp;{$ibforums->lang['icon_unread']}</div>
<div align="right" style="padding:6px">$pages<br /><i>$amount_info</i></div>
EOF;
}

function send_form_footer() {
global $ibforums;
return <<<EOF
<tr>
 <td colspan='2' class='pformstrip'>{$ibforums->lang['msg_options']}</td>
</tr>
<tr>
 <td class="pformleft">&nbsp;</td>
 <td class="pformright">
	<input type='checkbox' name='add_sent' value='yes' checked="checked" />&nbsp;<b>{$ibforums->lang['auto_sent_add']}</b>
	<br /><input type='checkbox' name='add_tracking' value='1' />&nbsp;<b>{$ibforums->lang['vm_track_msg']}</b>
 </td>
</tr>
<tr>
 <td class='pformstrip' align='center' colspan='2'>
  <input type="submit" value="{$ibforums->lang['submit_send']}" tabindex="4" accesskey="s" class='forminput' name='submit' />
  <input type="submit" value="{$ibforums->lang['pm_pre_button']}" tabindex="5" class='forminput' name='preview' />
  <input type="submit" value="{$ibforums->lang['pms_send_later']}" tabindex="6" class='forminput' name='save' />
 </td>
</tr>
</table>
</form>
EOF;
}

function Send_form($data) {
global $ibforums;
return <<<EOF

<script language='javascript' type="text/javascript">
<!--
function find_users()
{
  url = "index.{$ibforums->vars['php_ext']}?act=legends&CODE=finduser_one&s={$ibforums->session_id}&entry=textarea&name=carbon_copy&sep=line";
  window.open(url,'FindUsers','width=400,height=250,resizable=yes,scrollbars=yes'); 
}
//-->
</script>
<form action="{$ibforums->base_url}" method="post" name='REPLIER' onsubmit='return ValidateForm(1)'>
<input type='hidden' name='act' value='Msg' />
<input type='hidden' name='CODE' value='04' />
<input type='hidden' name='MODE' value='01' />
<input type='hidden' name='OID'  value='{$data['OID']}' />
<table width="100%" cellspacing="0">
<tr>
  <td colspan='2' class='pformstrip'>{$ibforums->lang['to_whom']}</td>
</tr>
<tr>
  <td class="pformleft">{$ibforums->lang['address_list']}</td>
  <td class="pformright">{$data[CONTACTS]}&nbsp;</td>
</tr>  
<tr>
  <td class="pformleft">{$ibforums->lang['enter_name']}</td>
  <td class="pformright"><input type='text' name='entered_name' size='50' value='{$data[N_ENTER]}' tabindex="1" class='forminput' /></td>
</tr>
<!--IBF.MASS_PM_BOX-->
<tr>
  <td colspan='2' class='pformstrip'>{$ibforums->lang['enter_message']}</td>
</tr>
<tr>
  <td class="pformleft">{$ibforums->lang['msg_title']}</td>
  <td class="pformright"><input type='text' name='msg_title' size='40' tabindex="2" maxlength='40' value='{$data[O_TITLE]}' class='forminput' /></td>
</tr>
EOF;
}



function mass_pm_box($names="") {
global $ibforums;
return <<<EOF
     
<tr>
<td colspan='2' class='pformstrip'>{$ibforums->lang['carbon_copy_title']}</td>
</tr>
<tr>
<td class="pformleft">{$ibforums->lang['carbon_copy_desc']}</td>
<td class='pformright'>
 <textarea name='carbon_copy' rows='5' cols='40'>$names</textarea><br />
 <input type='button' class='forminput' name='findusers' onclick='find_users()' value='{$ibforums->lang['find_user_names']}' />
</td>
</tr>
EOF;
}






}
?>