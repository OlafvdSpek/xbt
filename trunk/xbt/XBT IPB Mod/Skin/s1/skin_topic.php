<?php

class skin_topic {

function warn_level_warn($id, $percent) {
global $ibforums;
return <<<EOF
{$ibforums->lang['tt_warn']} (<a href="javascript:PopUp('{$ibforums->base_url}act=warn&amp;mid={$id}&amp;CODE=view','Pager','500','450','0','1','1','1')">{$percent}</a>%)
EOF;
}

function warn_level_rating($id, $level,$min=0,$max=10) {
global $ibforums;
return <<<EOF
&lt;&nbsp;$min ( <a href="javascript:PopUp('{$ibforums->base_url}act=warn&amp;mid={$id}&amp;CODE=view','Pager','500','450','0','1','1','1')">{$level}</a> ) $max&nbsp;&gt;
EOF;
}


function report_link($data) {
global $ibforums;
return <<<EOF
<a href='{$ibforums->base_url}act=report&amp;f={$data['forum_id']}&amp;t={$data['topic_id']}&amp;p={$data['pid']}&amp;st={$ibforums->input['st']}'><{P_REPORT}></a>
EOF;
}

function ip_show($data) {
global $ibforums;
return <<<EOF
<span class='desc'>{$ibforums->lang['ip']}: $data</span>
EOF;
}

function golastpost_link($fid, $tid) {
global $ibforums;
return <<<EOF
( <a href='{$ibforums->base_url}act=ST&amp;f=$fid&amp;t=$tid&amp;view=getnewpost'>{$ibforums->lang['go_new_post']}</a> )
EOF;
}

function mm_start($tid) {
global $ibforums;
return <<<EOF
<br />
<form action='{$ibforums->base_url}act=mmod&amp;t=$tid' method='post'>
<input type='hidden' name='check' value='1'>
<select name='mm_id' class='forminput'>
<option value='-1'>{$ibforums->lang['mm_title']}</option>
EOF;
}

function mm_entry($id, $name) {
global $ibforums;
return <<<EOF
<option value='$id'>$name</option>
EOF;
}

function mm_end() {
global $ibforums;
return <<<EOF
</select>&nbsp;<input type='submit' value='{$ibforums->lang['mm_submit']}' class='forminput' /></form>
EOF;
}

function Mod_Panel($data, $fid, $tid, $key="") {
global $ibforums;
return <<<EOF
  <div align='left' style='float:left;width:auto'>
	<form method='POST' style='display:inline' name='modform' action='{$ibforums->base_url}'>
	<input type='hidden' name='t' value='$tid' />
	<input type='hidden' name='f' value='$fid' />
	<input type='hidden' name='st' value='{$ibforums->input['st']}' />
	<input type='hidden' name='auth_key' value='$key' />
	<input type='hidden' name='act' value='Mod' />
	<select name='CODE' class='forminput' style="font-weight:bold;color:red">
	<option value='-1' style='color:black'>{$ibforums->lang['moderation_ops']}</option>
	$data
	</select>&nbsp;<input type='submit' value='{$ibforums->lang['jmp_go']}' class='forminput' /></form>
  </div>

EOF;
}

function mod_wrapper($id="", $text="") {
global $ibforums;
return <<<EOF
<option value='$id'>$text</option>
EOF;
}




function quick_reply_box_open($fid="",$tid="",$show="hide", $key="") {
global $ibforums;
return <<<EOF
	<script type="text/javascript">
	<!--
	function emo_pop()
	{
	  window.open('index.{$ibforums->vars['php_ext']}?act=legends&amp;CODE=emoticons&amp;s={$ibforums->session_id}','Legends','width=250,height=500,resizable=yes,scrollbars=yes');
	}
	//-->
	</script>
	<br />
	<div align='left' id='qr_open' style="display:$show;position:relative;">
	   <form name='REPLIER' action="{$ibforums->base_url}" method='post'>
	   <input type='hidden' name='act' value='Post' />
	   <input type='hidden' name='CODE' value='03' />
	   <input type='hidden' name='f' value='$fid' />
	   <input type='hidden' name='t' value='$tid' />
	   <input type='hidden' name='st' value='{$ibforums->input['st']}' />
	   <input type='hidden' name='enabletrack' value='{$ibforums->member['auto_track']}' />
	   <input type='hidden' name='auth_key' value='$key' />
	   <!-- TITLE DIV -->
	   <div class="tableborder">
	     <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['qr_title']}</div>
		 <div class="tablepad" align="center">
			 <textarea cols='70' rows='8' name='Post' class='textinput' tabindex="1"></textarea>
			 <br /><br />
			 <a href='javascript:emo_pop();'>{$ibforums->lang['show_emo']}</a> &#124;
			 <input type='checkbox' name='enableemo' value='yes' class="checkbox" checked="checked" />&nbsp;{$ibforums->lang['qr_add_smilie']} &#124;
			 <input type='checkbox' name='enablesig' value='yes' class="checkbox" checked="checked" />&nbsp;{$ibforums->lang['qr_add_sig']}
			 <br /><br />
			 <input type='submit' name='submit' value='{$ibforums->lang['qr_submit']}' class='forminput' tabindex="2" accesskey="s" />&nbsp;
			 <input type='submit' name='preview' value='{$ibforums->lang['qr_more_opts']}' class='forminput' />
			 &nbsp;&nbsp; <input type='button' name='qrc' onclick="ShowHide('qr_open','qr_closed');" value='{$ibforums->lang['qr_closeit']}' class='forminput' />
		 </div>
	  </div>
	   </form>
	</div>
EOF;
}

function quick_reply_box_closed() {
global $ibforums;
return <<<EOF
	<!-- DEFAULT DIV -->
	<a href="javascript:ShowHide('qr_open','qr_closed');" title="{$ibforums->lang['qr_open']}" accesskey="f"><{T_QREPLY}></a>
EOF;
}

function start_poll_link($fid, $tid) {
global $ibforums;
return <<<EOF
	<a href="{$ibforums->base_url}act=Post&amp;CODE=14&amp;f=$fid&amp;t=$tid">{$ibforums->lang['new_poll_link']}</a> &#124;&nbsp;
EOF;
}



function PageTop($data) {
global $ibforums;
return <<<EOF
    <script language='javascript' type='text/javascript'>
    <!--

    function link_to_post(pid)
    {
    	temp = prompt( "{$ibforums->lang['tt_prompt']}", "{$ibforums->base_url}showtopic={$ibforums->input['t']}&view=findpost&p=" + pid );
    	return false;
    }

    function delete_post(theURL) {
       if (confirm('{$ibforums->lang['js_del_1']}')) {
          window.location.href=theURL;
       }
       else {
          alert ('{$ibforums->lang['js_del_2']}');
       }
    }

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

	function ShowHide(id1, id2) {
	  if (id1 != '') expMenu(id1);
	  if (id2 != '') expMenu(id2);
	}

	function expMenu(id) {
	  var itm = null;
	  if (document.getElementById) {
		itm = document.getElementById(id);
	  } else if (document.all){
		itm = document.all[id];
	  } else if (document.layers){
		itm = document.layers[id];
	  }

	  if (!itm) {
	   // do nothing
	  }
	  else if (itm.style) {
		if (itm.style.display == "none") { itm.style.display = ""; }
		else { itm.style.display = "none"; }
	  }
	  else { itm.visibility = "show"; }
	}
    //-->
    </script>

<a name='top'></a>
<!--IBF.FORUM_RULES-->

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
 <td align='left' width="20%" nowrap="nowrap">{$data['TOPIC']['SHOW_PAGES']}&nbsp;{$data['TOPIC']['go_new']}</td>
 <td align='right' width="80%">{$data[TOPIC][REPLY_BUTTON]}<a href='{$ibforums->base_url}act=Post&amp;CODE=00&amp;f={$data[FORUM]['id']}' title='{$ibforums->lang['start_new_topic']}'><{A_POST}></a>{$data[TOPIC][POLL_BUTTON]}</td>
</tr>
</table>
<br />
<div class="tableborder">
    <div class='maintitle'><{CAT_IMG}>&nbsp;<b>{$data['TOPIC']['title']}</b>{$data['TOPIC']['description']}</div>
	<!--{IBF.POLL}-->
	<div align='right' class='postlinksbar'>
	  <strong><!--{IBF.START_NEW_POLL}--><a href='{$ibforums->base_url}act=Track&amp;f={$data['FORUM']['id']}&amp;t={$data['TOPIC']['tid']}'>{$ibforums->lang['track_topic']}</a> |
	  <a href='{$ibforums->base_url}act=Forward&amp;f={$data['FORUM']['id']}&amp;t={$data['TOPIC']['tid']}'>{$ibforums->lang['forward']}</a> |
	  <a href='{$ibforums->base_url}act=Print&amp;client=printer&amp;f={$data['FORUM']['id']}&amp;t={$data['TOPIC']['tid']}'>{$ibforums->lang['print']}</a></strong>
	</div>

EOF;
}


function RenderRow($post, $author) {
global $ibforums;
return <<<EOF

	<!--Begin Msg Number {$post['pid']}-->
    <table width='100%' border='0' cellspacing='1' cellpadding='3'>
    <tr>
      <td valign='middle' class='row4' width="1%"><a name='entry{$post['pid']}'></a><span class='{$post['name_css']}'>{$author['name']}</span></td>
        <td class='row4' valign='top' width="99%">

        <!-- POSTED DATE DIV -->

        <div align='left' class='row4' style='float:left;padding-top:4px;padding-bottom:4px'>
        {$post['post_icon']}<span class='postdetails'><b><a title="{$ibforums->lang['tt_link']}" href="#" onclick="link_to_post({$post['pid']}); return false;" style="text-decoration:underline">{$ibforums->lang['posted_on']}</a></b> {$post['post_date']}</span>
        </div>

        <!-- REPORT / DELETE / EDIT / QUOTE DIV -->

        <div align='right'>
        {$post['report_link']}{$post['delete_button']}{$post['edit_button']}<a href='{$ibforums->base_url}act=Post&amp;CODE=06&amp;f={$ibforums->input[f]}&amp;t={$ibforums->input[t]}&amp;p={$post['pid']}'><{P_QUOTE}></a>
      </div>

      </td>
    </tr>
    <tr>
      <td valign='top' class='{$post['post_css']}'>
        <span class='postdetails'>{$author['avatar']}<br /><br />
        {$author['title']}<br />
        {$author['member_rank_img']}<br /><br />
        {$author['member_group']}<br />
        {$author['member_posts']}<br />
        {$author['member_number']}<br />
        {$author['member_joined']}<br /><br />
        {$author['warn_text']} {$author['warn_minus']}{$author['warn_img']}{$author['warn_add']}</span><br />
        <!--$ author[field_1]-->
        <img src='{$ibforums->vars['img_url']}/spacer.gif' alt='' width='160' height='1' /><br />
      </td>
      <td width='100%' valign='top' class='{$post['post_css']}'>
        <!-- THE POST {$post['pid']} -->
        <div class='postcolor'>{$post['post']} {$post['attachment']}</div>
        {$post['signature']}
        <!-- THE POST -->
      </td>
    </tr>
    <tr>
      <td class='darkrow3' align='left'><b>{$post['ip_address']}</b></td>
      <td class='darkrow3' nowrap="nowrap" align='left'>

        <!-- PM / EMAIL / WWW / MSGR -->

        <div align='left' class='darkrow3' style='float:left;width:auto'>
        {$author['addresscard']}{$author['message_icon']}{$author['email_icon']}{$author['website_icon']}{$author['integ_icon']}{$author['icq_icon']}{$author['aol_icon']}{$author['yahoo_icon']}{$author['msn_icon']}
        </div>

        <!-- REPORT / UP -->

        <div align='right'>
         <a href='javascript:scroll(0,0);'><img src='{$ibforums->vars['img_url']}/p_up.gif' alt='Top' border='0' /></a>
        </div>
      </td>
    </tr>
    </table>
    <div class='darkrow1' style='height:5px'><!-- --></div>

EOF;
}

function TableFooter($data) {
global $ibforums;
return <<<EOF

      <!--IBF.TOPIC_ACTIVE-->
      <div class="activeuserstrip" align="center">&laquo; <a href='{$ibforums->base_url}showtopic={$data[TOPIC]['tid']}&amp;view=old'>{$ibforums->lang['t_old']}</a> &#0124; <strong><a href='{$ibforums->base_url}showforum={$data['FORUM']['id']}'>{$data['FORUM']['name']}</a></strong> &#0124; <a href='{$ibforums->base_url}showtopic={$data[TOPIC]['tid']}&amp;view=new'>{$ibforums->lang['t_new']}</a> &raquo;</div>
</div>

<br />
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
 <td align='left' width="20%" nowrap="nowrap"><!--IBF.TOPIC_OPTIONS_CLOSED-->{$data[TOPIC][SHOW_PAGES]}</td>
 <td align='right' width="80%">{$data[TOPIC][REPLY_BUTTON]}<!--IBF.QUICK_REPLY_CLOSED--><a href='{$ibforums->base_url}act=Post&amp;CODE=00&amp;f={$data[FORUM]['id']}' title='{$ibforums->lang['start_new_topic']}'><{A_POST}></a>{$data[TOPIC][POLL_BUTTON]}</td>
</tr>
</table>

<!--IBF.QUICK_REPLY_OPEN-->
<!--IBF.TOPIC_OPTIONS_OPEN-->

<br />
<!--IBF.MOD_PANEL-->
<div align='right'>{$data[FORUM]['JUMP']}</div>
<!--IBF.MULTIMOD-->
<br />
EOF;
}

function topic_opts_open($fid, $tid) {
global $ibforums;
return <<<EOF
<div id='topic_open' style='display:none;z-index:2;'>
    <div class="tableborder">
	  <div class='maintitle'><{CAT_IMG}>&nbsp;<a href="javascript:ShowHide('topic_open','topic_closed')">{$ibforums->lang['to_close']}</a></div>
	  <div class='tablepad'>
	   <b><a href='{$ibforums->base_url}act=Track&amp;f={$fid}&amp;t={$tid}'>{$ibforums->lang['tt_title']}</a></b>
	   <br />
	   <span class='desc'>{$ibforums->lang['tt_desc']}</span>
	   <br /><br />
	   <b><a href='{$ibforums->base_url}act=Track&amp;f={$fid}&amp;type=forum'>{$ibforums->lang['ft_title']}</a></b>
	   <br />
	   <span class='desc'>{$ibforums->lang['ft_desc']}</span>
	   <br /><br />
	   <b><a href='{$ibforums->base_url}act=Print&amp;client=choose&amp;f={$fid}&amp;t={$tid}'>{$ibforums->lang['av_title']}</a></b>
	   <br />
	   <span class='desc'>{$ibforums->lang['av_desc']}</span>
	 </div>
   </div>
</div>
EOF;
}

function topic_opts_closed() {
global $ibforums;
return <<<EOF
<a href="javascript:ShowHide('topic_open','topic_closed')" title="{$ibforums->lang['to_open']}"><{T_OPTS}></a>
EOF;
}


function topic_active_users($active=array()) {
global $ibforums;
return <<<EOF
	  <div class="activeuserstrip">{$ibforums->lang['active_users_title']} ({$ibforums->lang['active_users_detail']})</div>
	  <div class='row2' style='padding:6px'>{$ibforums->lang['active_users_members']} {$active['names']}</div>
EOF;
}

function Show_attachments_img($file_name) {
global $ibforums;
return <<<EOF
<br />
<br />
<strong><span class='edit'>{$ibforums->lang['pic_attach']}</span></strong>
<br />
<img src='{$ibforums->vars['upload_url']}/$file_name' class='attach' alt='{$ibforums->lang['pic_attach']}' />
EOF;
}

function Show_attachments_img_thumb($file_name, $width, $height, $aid) {
global $ibforums;
return <<<EOF
<br />
<br />
<strong><span class='edit'>{$ibforums->lang['pic_attach_thumb']}</span></strong>
<br />
<a href='{$ibforums->base_url}act=Attach&amp;type=post&amp;id=$aid' title='{$ibforums->lang['pic_attach_thumb']}' target='_blank'><img src='{$ibforums->vars['upload_url']}/$file_name' width='$width' height='$height' class='attach' alt='{$ibforums->lang['pic_attach']}' /></a>
EOF;
}

function Show_attachments($data) {
global $ibforums;
if ($data['leechers'])
	$data['leechers'] = sprintf(", leechers: %d", $data['leechers']);
else
	unset($data['leechers']);
if ($data['seeders'])
	$data['seeders'] = sprintf(", seeders: %d", $data['seeders']);
else
	unset($data['seeders']);
return <<<EOF
<br />
<br />
<strong><span class='edit'>{$ibforums->lang['attached_file']} ( {$ibforums->lang['attach_hits']}: {$data['hits']}{$data['leechers']}{$data['seeders']})</span></strong>
<br />
<a href='{$ibforums->base_url}act=Attach&amp;type=post&amp;id={$data['pid']}' title='{$ibforums->lang['attach_dl']}' target='_blank'><img src='{$ibforums->vars['mime_img']}/{$data['image']}' border='0' alt='{$ibforums->lang['attached_file']}' /></a>
&nbsp;<a href='{$ibforums->base_url}act=Attach&amp;type=post&amp;id={$data['pid']}' title='{$ibforums->lang['attach_dl']}' target='_blank'>{$data['name']}</a>
EOF;
}


}
?>