<?php

class skin_emails {

function board_rules( $title="", $body="") {
global $ibforums;
return <<<EOF
<div class="tableborder">
 <div class="pformstrip">$title</div>
 <div class="tablepad">$body</div>
</div>
EOF;
}


function errors($data) {
global $ibforums;
return <<<EOF
<div class="tableborder">
  <div class="pformstrip">{$ibforums->lang['errors_found']}</div>
  <div class="tablepad"><span class='postcolor'>$data</span></div>
</div>
<br />
EOF;
}

function chat_inline($acc_no, $lang, $w, $h, $user="",$pass="") {
global $ibforums;
return <<<EOF
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['chat_title']}</div>
 <div class='tablepad' align='center'>
  <applet
	 codebase="http://client.invisionchat.com/current/"
	 code="Client.class" archive="scclient_$lang.zip"
	 width=$w height=$h
	 style='border: 1px solid #000'>
	 <param name="room" value="$acc_no">
	 <param name="cabbase" value="scclient_$lang.cab">
	 <param name="username" value="$user">
	 <param name="password" value="$pass">
	 <param name="autologin" value="yes">
  </applet>
 </div>
</div>
<br />
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['chat_help']}</div>
 <div class='tablepad'>
   {$ibforums->lang['chat_help_text']}
 </div>
</div>
EOF;
}

function chat_pop($acc_no, $lang, $w, $h, $user="",$pass="") {
global $ibforums;
return <<<EOF
<div align='center'>
 <applet
	codebase="http://client.invisionchat.com/current/"
	code="Client.class" archive="scclient_$lang.zip"
	width=$w height=$h
	style='border: 1px solid <{tbl_border}>'>
	<param name="room" value="$acc_no">
	<param name="cabbase" value="scclient_$lang.cab">
	<param name="username" value="$user">
	<param name="password" value="$pass">
	<param name="autologin" value="yes">
 </applet>
</div>
EOF;
}

function report_form($fid, $tid, $pid, $st, $topic_title) {
global $ibforums;
return <<<EOF
<form action="{$ibforums->base_url}act=report&amp;send=1&amp;f=$fid&amp;t=$tid&amp;p=$pid&amp;st=$st" method="post" name='REPLIER'>
<div class='tableborder'>
  <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['report_title']}</div>
  <div class='pformstrip'>&nbsp;</div>
  <table cellpadding='4' cellspacing='1' border='0' width='100%'>
   <tr>
   <td class='row1' align='left'  width='30%' valign='top'><b>{$ibforums->lang['report_topic']}</b></td>
   <td class='row1' width='80%'><a href='{$ibforums->base_url}showtopic=$tid&amp;st=$st&amp;&#35;entry$pid'>$topic_title</a>
   </td>
   </tr>
   <tr>
   <td class='row1' align='left'  width='30%' valign='top'>{$ibforums->lang['report_message']}</td>
   <td class='row1' width='80%'><textarea cols='60' rows='12' wrap='soft' name='message' class='textinput'></textarea>
   </td>
   </tr>
  </table>
  <div align='center' class='pformstrip'><input type="submit" value="{$ibforums->lang['report_submit']}" class='forminput' /></div>
 </div>
</form>
EOF;
}


function msn_body($msnname) {
global $ibforums;
return <<<EOF
				
			  <object classid='clsid:F3A614DC-ABE0-11d2-A441-00C04F795683' codebase='#Version=2,0,0,83' codetype='application/x-oleobject' id='MsgrObj' name='MsgrApp' width='0' height='0'></object>
			  <object classid='clsid:FB7199AB-79BF-11d2-8D94-0000F875C541' codetype='application/x-oleobject' id='MsgrApp' name='MsgrApp' width='0' height='0'></object>
              <tr>
                <td class='row2' align='left'><b>{$ibforums->lang['msn_name']}</b></td>
                <td class='row2' align='left'><input type='text' name='msnname' value='$msnname' size='40' class='forminput' onMouseOver="this.focus()" onFocus="this.select()"></td>
              </tr>
              <tr>
                <td class='row2' align='center' colspan='2'><a href="javascript:MsgrApp.LaunchIMUI('$msnname');">{$ibforums->lang['msn_send_msg']}</a></td>
              </tr>
              <tr>
                <td class='row2' align='center' colspan='2'><a href="javascript:MsgrApp.LaunchAddContactUI('$msnname');">{$ibforums->lang['msn_add_contact']}</a></td>
              </tr>
              
EOF;
}

function yahoo_body($yahoo) {
global $ibforums;
return <<<EOF
				
			  <tr>
                <td class='row2' align='left'><b>{$ibforums->lang['yahoo_name']}</b></td>
                <td class='row2' align='left'><input type='text' name='msnname' value='$yahoo' size='40' class='forminput' onMouseOver="this.focus()" onFocus="this.select()"></td>
              </tr>
               <tr>
                <td class='row2' align='left'><b>{$ibforums->lang['yahoo_status']}</b></td>
                <td class='row2' align='left'><img border=0 src="http://opi.yahoo.com/online?u=$yahoo&amp;m=g&amp;t=2"></td>
              </tr>
              <tr>
                <td class='row2' align='center' colspan='2'><a href="http://edit.yahoo.com/config/send_webmesg?.target=$yahoo&amp;.src=pg">{$ibforums->lang['yahoo_send_msg']}</a></td>
              </tr>
              <tr>
                <td class='row2' align='center' colspan='2'><a href="http://members.yahoo.com/interests?.oc=t&amp;.kw=$yahoo&amp;.sb=1">{$ibforums->lang['yahoo_view_profile']}</a></td>
              </tr>
              
EOF;
}


function icq_body($data) {
global $ibforums;
return <<<EOF
              <form action="http://msg.mirabilis.com/scripts/WWPMsg.dll" METHOD="POST" name="frmPager">
			      <INPUT TYPE="hidden" NAME="subject" VALUE="From WebPager Panel">
              <input type="hidden" name="to" value="{$data[UIN]}">
              <tr>
                <td class='row2' align='left'><b>{$ibforums->lang['name']}</b></td>
                <td class='row2' align='left'><input type='text' name='from' value='{$ibforums->member['name']}' size='40' class='forminput' onMouseOver="this.focus()" onFocus="this.select()"></td>
              </tr>
              <tr>
                <td class='row2' align='left'><b>{$ibforums->lang['email']}</b></td>
                <td class='row2' align='left'><input type='text' name='fromemail' value='{$ibforums->member['email']}' size='40' class='forminput' onMouseOver="this.focus()" onFocus="this.select()"></td>
              </tr>
              <tr>
                <td class='row2' align='left' valign='top'><b>{$ibforums->lang['msg']}</b></td>
                <td class='row2' align='left'><textarea wrap='virtual' cols='50' rows='12' wrap='soft' name='body' class='textinput' onMouseOver="this.focus()" onFocus="this.select()"></textarea></td>
              </tr>
              <tr>
                <td class='row2' align='center' colspan='2'><input type='submit' value='{$ibforums->lang['submit']}' class='forminput'></td>
              </tr>
              </form>
EOF;
}

function end_table() {
global $ibforums;
return <<<EOF
            <!-- End content Table -->
            </table>
            </td>
            </tr>
            <tr>
            <td class='darkrow1' colspan='2'>&nbsp;</td>
            </tr>
            </table>
EOF;
}

function pager_header($data) {
global $ibforums;
return <<<EOF
       <table cellpadding='0' cellspacing='0' border='0' width='100%' bgcolor='<{tbl_border}>' align='center'>
        <tr>
            <td>
              <table cellpadding='4' cellspacing='0' border='0' width='100%'>
                <tr>
                   <td colspan='2' align='center' class='titlemedium'>{$data[TITLE]}</td>
EOF;
}

function aol_body($data) {
global $ibforums;
return <<<EOF
<!-- Begin AIM Remote -->
<table width='140' align='center'>
<tr align='right'><td><a href="http://www.aol.co.uk/aim/index.html"><img src="http://www.aol.co.uk/aim/remote/gr/aimver_man.gif" width=44 height=55 border=0 alt="Download AIM"></a><img src="http://www.aol.co.uk/aim/remote/gr/aimver_topsm.gif" width=73 height=55 border=0 alt="AIM Remote"><br /><a href="aim:goim?screenname={$data['AOLNAME']}&amp;message=Hi.+Are+you+there?"><img src="http://www.aol.co.uk/aim/remote/gr/aimver_im.gif" width=117 height=39 border=0 alt="Send me an Instant Message"></a><br /><a href="aim:addbuddy?screenname={$data['AOLNAME']}"><img src="http://www.aol.co.uk/aim/remote/gr/aimver_bud.gif" width=117 height=39 border=0 alt="Add me to Your Buddy List"></a><br /><a href="http://www.aol.co.uk/aim/remote.html"><img src="http://www.aol.co.uk/aim/remote/gr/aimver_botadd.gif" width=117 height=23 border=0 alt="Add Remote to Your Page"></a><br /><a href="http://www.aol.co.uk/aim/index.html"><img src="http://www.aol.co.uk/aim/remote/gr/aimver_botdow.gif" width=117 height=29 border=0 alt="Download AOL Instant Messenger"></a><br /><br /></td></tr></table>
<!-- End AIM Remote -->
EOF;
}

function forward_form($title, $text, $lang) {
global $ibforums;
return <<<EOF
<form action="{$ibforums->base_url}" method="post" name='REPLIER'>
<input type='hidden' name='act'  value='Forward'>
<input type='hidden' name='CODE' value='01'>
<input type='hidden' name='s'    value='{$ibforums->session_id}'>
<input type='hidden' name='st'   value='{$ibforums->input['st']}'>
<input type='hidden' name='f'    value='{$ibforums->input['f']}'>
<input type='hidden' name='t'    value='{$ibforums->input['t']}'>
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['title']}</div>
 <table cellpadding='4' cellspacing='0' border='0' width='100%'>
   <tr>
   <td class='row1' align='left'  width='30%' valign='top'><b>{$ibforums->lang['send_lang']}</b></td>
   <td class='row1' width='80%'>$lang</td>
   </tr>
   <tr>
   <td class='row1' align='left'  width='30%' valign='top'><b>{$ibforums->lang['to_name']}</b></td>
   <td class='row1' width='80%'><input type='text' class='forminput' name='to_name' value='' size='30' maxlength='100'></td>
   </tr>
   <tr>
   <td class='row1' align='left'  width='30%' valign='top'><b>{$ibforums->lang['to_email']}</b></td>
   <td class='row1' width='80%'><input type='text' class='forminput' name='to_email' value='' size='30' maxlength='100'></td>
   </tr>
   <tr>
   <td class='row1' align='left'  width='30%' valign='top'><b>{$ibforums->lang['subject']}</b></td>
   <td class='row1' width='80%'><input type='text' class='forminput' name='subject' value='{$title}' size='30' maxlength='120'></td>
   </tr>
   <tr>
   <td class='row1' align='left'  width='30%' valign='top'><b>{$ibforums->lang['message']}</b></td>
   <td class='row1' width='80%'><textarea cols='60' rows='12' wrap='soft' name='message' class='textinput'>{$text}</textarea>
   </td>
   </tr>
  </table>
  <div align='center' class='pformstrip'><input type="submit" value="{$ibforums->lang['submit_send']}" class='forminput' /></div>
</div>
</form>
EOF;
}

function show_address($data) {
global $ibforums;
return <<<EOF
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['send_email_to']} {$data[NAME]}</div>
  <div class='tablepad'>{$ibforums->lang['show_address_text']}
  <br />
  &gt;&gt;<b><a href="mailto:{$data[ADDRESS]}" class='misc'>{$ibforums->lang['send_email_to']} {$data[NAME]}</a></b>
 </div>
</div>
EOF;
}

function send_form($data) {
global $ibforums;
return <<<EOF
<form action="{$ibforums->base_url}" method="post" name='REPLIER'>
<input type='hidden' name='act' value='Mail'>
<input type='hidden' name='CODE' value='01'>
<input type='hidden' name='to' value='{$data['TO']}'>
<div><strong>{$ibforums->lang['imp_text']}</strong></div>
<br />
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['send_title']}</div>
  <div class='pformstrip'>{$ibforums->lang['send_email_to']} {$data['NAME']}</div>
  <table width='100%' cellspacing='1'>
	<tr>
	  <td class='pformleftw' valign='top'><b>{$ibforums->lang['subject']}</b></td>
	  <td class='pformright'><input type='text' name='subject' value='{$data['subject']}' size='50' maxlength='50' class='forminput' /></td>
	</tr>
	<tr>
	  <td class='pformleftw' valign='top'><b>{$ibforums->lang['message']}</b><br /><br />{$ibforums->lang['msg_txt']}</td>
	  <td class='pformright'><textarea cols='60' rows='12' wrap='soft' name='message' class='textinput'>{$data['content']}</textarea></td>
	</tr>
   </table>
   <div class='pformstrip' align='center'><input type="submit" value="{$ibforums->lang['submit_send']}" class='forminput' /></div>
</div>
</form>
EOF;
}

function sent_screen($member_name) {
global $ibforums;
return <<<EOF
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['email_sent']}</div>
  <div class='tablepad'>{$ibforums->lang['email_sent_txt']} $member_name</div>
</div>

EOF;
}

function forum_jump($data) {
global $ibforums;
return <<<EOF
      <table cellpadding='0' cellspacing='1' border='0' width='<{tbl_width}>' align='center'>
        <tr>
            <td align='right'>$data</td>
        </tr>
       </table>
EOF;
}


}
?>