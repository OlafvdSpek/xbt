<?php

class skin_profile {

function warn_level($mid, $img, $percent) {
global $ibforums;
return <<<EOF
  <tr>
	<td class="row3" valign='top'><b>{$ibforums->lang['warn_level']}</b></td>
	<td align='left' class='row1'><a href="javascript:PopUp('{$ibforums->base_url}act=warn&amp;mid={$mid}&amp;CODE=view','Pager','500','450','0','1','1','1')">{$percent}</a>%: <a href='{$ibforums->base_url}act=warn&amp;type=minus&amp;mid={$mid}' title='{$ibforums->lang['tt_warn_minus']}'><{WARN_MINUS}></a>{$img}<a href='{$ibforums->base_url}act=warn&amp;type=add&amp;mid={$mid}' title='{$ibforums->lang['tt_warn_add']}'><{WARN_ADD}></a>
</td>
  </tr>
EOF;
}

function warn_level_no_mod($mid, $img, $percent) {
global $ibforums;
return <<<EOF
  <tr>
	<td class="row3" valign='top'><b>{$ibforums->lang['warn_level']}</b></td>
	<td align='left' class='row1'><a href="javascript:PopUp('{$ibforums->base_url}act=warn&amp;mid={$mid}&amp;CODE=view','Pager','500','450','0','1','1','1')">{$percent}</a>%: {$img}</td>
  </tr>
EOF;
}

function warn_level_rating($mid, $level,$min=0,$max=10) {
global $ibforums;
return <<<EOF
 <tr>
	<td class="row3" valign='top'><b>{$ibforums->lang['rating_level']}</b></td>
	<td align='left' class='row1'><a href='{$ibforums->base_url}act=warn&amp;type=minus&amp;mid={$mid}' title='{$ibforums->lang['tt_warn_minus']}'><{WARN_MINUS}></a> &lt;&nbsp;$min ( <a href="javascript:PopUp('{$ibforums->base_url}act=warn&amp;mid={$mid}&amp;CODE=view','Pager','500','450','0','1','1','1')">{$level}</a> ) $max&nbsp;&gt; <a href='{$ibforums->base_url}act=warn&amp;type=add&amp;mid={$mid}' title='{$ibforums->lang['tt_warn_add']}'><{WARN_ADD}></a></td>
  </tr>
EOF;
}

function warn_level_rating_no_mod($mid, $level,$min=0,$max=10) {
global $ibforums;
return <<<EOF
 <tr>
	<td class="row3" valign='top'><b>{$ibforums->lang['rating_level']}</b></td>
	<td align='left' class='row1'>&lt;&nbsp;$min ( <a href="javascript:PopUp('{$ibforums->base_url}act=warn&amp;mid={$mid}&amp;CODE=view','Pager','500','450','0','1','1','1')">{$level}</a> ) $max&nbsp;&gt;</td>
  </tr>
EOF;
}

function get_photo($show_photo, $show_width, $show_height) {
global $ibforums;
return <<<EOF
<img src="$show_photo" border="0" alt="User Photo" $show_width $show_height />
EOF;
}

function show_photo($name, $photo) {
global $ibforums;
return <<<EOF
<div id="photowrap">
 <div id="phototitle">$name</div>
 <div id="photoimg">$photo</div>
</div>
EOF;
}

function show_card_download($name, $photo, $info) {
global $ibforums;
return <<<EOF
<html>
 <head>
  <title>$name</title>
  <style type="text/css">
	 form { display:inline; }
	 img  { vertical-align:middle }
	 BODY { font-family: Verdana, Tahoma, Arial, sans-serif; font-size: 11px; color: #000; margin-left:5%;margin-right:5%;margin-top:5px;  }
	 TABLE, TR, TD { font-family: Verdana, Tahoma, Arial, sans-serif; font-size: 11px; color: #000; }
	 a:link, a:visited, a:active { text-decoration: underline; color: #000 }
	 a:hover { color: #465584; text-decoration:underline }
	 #profilename { font-size:28px; font-weight:bold; }
	 #photowrap { padding:6px; }
	 #phototitle { font-size:24px; border-bottom:1px solid black }
	 #photoimg   { text-align:center; margin-top:15px } 
	 .plainborder { border:1px solid #345487;background-color:#F5F9FD }
	 .tableborder { border:1px solid #345487;background-color:#FFF }
	 .tablefill   { border:1px solid #345487;background-color:#F5F9FD;padding:6px }
	 .tablepad    { background-color:#F5F9FD;padding:6px }
	 .tablebasic  { width:100%; padding:0px 0px 0px 0px; margin:0px; border:0px }
	 .row1 { background-color: #F5F9FD }
	 .row2 { background-color: #DFE6EF }
	 .row3 { background-color: #EEF2F7 }
	 .row4 { background-color: #E4EAF2 }
  </style>
  <script language='javascript' type="text/javascript">
  <!--
   function redirect_to(where, closewin)
   {
	  document.location= '$ibforums->base_url' + where;
	  
	  if (closewin == 1)
	  {
		  self.close();
	  }
   }
  //-->
  </script>
 </head>
<body>
<table width="100%" height="100%">
<tr>
 <td valign="middle" align="center" width="400">
	<div id="phototitle">$name</div>
	<br />
	<table class="tablebasic" cellspacing="6">
	<tr>
	 <td valign="middle" class="row1">$photo</td>
	 <td width="100%" class="row1" valign="bottom">
	   <table class="tablebasic" cellpadding="5">
		 <tr>
		   <td nowrap="nowrap">{$ibforums->lang['email']}</td>
		   <td width="100%">{$info['email']}</td>
		 </tr>
		 <tr>
		  <td nowrap="nowrap">{$ibforums->lang['integ_msg']}</td>
		  <td width="100%">{$info['integ_msg']}</td>
	     </tr>
		 <tr>
		   <td nowrap="nowrap">{$ibforums->lang['aim']}</td>
		   <td width="100%">{$info['aim_name']}</td>
		 </tr>
		 <tr>
		   <td nowrap="nowrap">{$ibforums->lang['icq']}</td>
		   <td width="100%">{$info['icq_number']}</td>
		 </tr>
		 <tr>
		   <td nowrap="nowrap">{$ibforums->lang['yahoo']}</td>
		   <td width="100%">{$info['yahoo']}</td>
		 </tr>
		 <tr>
		   <td nowrap="nowrap">{$ibforums->lang['msn']}</td>
		   <td width="100%">{$info['msn_name']}</td>
		 </tr>
		 <tr>
		   <td nowrap="nowrap">{$ibforums->lang['pm']}</b></td>
		   <td><a href='javascript:redirect_to("&act=Msg&;CODE=4&MID={$info['mid']}", 1);'>{$ibforums->lang['click_here']}</a></td>
		 </tr>
		</td>
	   </tr>
	  </table>
	 </td>
	</tr>
	</table>
  </td>
 </tr>
</table>
</body>
</html>
EOF;
}

function show_card($name, $photo, $info) {
global $ibforums;
return <<<EOF
<script language='javascript' type="text/javascript">
<!--
 function redirect_to(where, closewin)
 {
 	opener.location= '$ibforums->base_url' + where;
 	
 	if (closewin == 1)
 	{
 		self.close();
 	}
 }
//-->
</script>
<div id="photowrap">
 <div id="phototitle">$name</div>
 <br />
 <table class="tablebasic" cellspacing="6">
 <tr>
  <td valign="middle" class="row1">$photo</td>
  <td width="100%" class="row1" valign="bottom">
    <table class="tablebasic" cellpadding="5">
      <tr>
        <td nowrap="nowrap">{$ibforums->lang['email']}</td>
		<td width="100%">{$info['email']}</td>
	  </tr>
	  <tr>
		<td nowrap="nowrap">{$ibforums->lang['integ_msg']}</td>
		<td width="100%">{$info['integ_msg']}</td>
	  </tr>
	  <tr>
		<td nowrap="nowrap">{$ibforums->lang['aim']}</td>
		<td width="100%">{$info['aim_name']}</td>
	  </tr>
	  <tr>
		<td nowrap="nowrap">{$ibforums->lang['icq']}</td>
		<td width="100%">{$info['icq_number']}</td>
	  </tr>
	  <tr>
		<td nowrap="nowrap">{$ibforums->lang['yahoo']}</td>
		<td width="100%">{$info['yahoo']}</td>
	  </tr>
	  <tr>
		<td nowrap="nowrap">{$ibforums->lang['msn']}</td>
		<td width="100%">{$info['msn_name']}</td>
	  </tr>
	  <tr>
		<td nowrap="nowrap">{$ibforums->lang['pm']}</b></td>
		<td><a href='javascript:redirect_to("&amp;act=Msg&amp;CODE=4&amp;MID={$info['mid']}", 1);'>{$ibforums->lang['click_here']}</a></td>
	  </tr>
     </td>
    </tr>
   </table>
  </td>
 </tr>
 </table>
</div>
<div align="center">
  <a href="{$ibforums->base_url}act=Profile&amp;CODE=showcard&amp;MID={$info['mid']}&amp;download=1">{$ibforums->lang['ac_download']}</a>
  &middot; <a href="javascript:self.close();">{$ibforums->lang['ac_close']}</a>
</div>
EOF;
}



function user_edit($info) {
global $ibforums;
return <<<EOF
&middot; <a href='{$info['base_url']}act=UserCP&amp;CODE=22'>{$ibforums->lang['edit_my_sig']}</a> &middot;
<a href='{$info['base_url']}act=UserCP&amp;CODE=24'>{$ibforums->lang['edit_avatar']}</a> &middot;
<a href='{$info['base_url']}act=UserCP&amp;CODE=01'>{$ibforums->lang['edit_profile']}</a>
EOF;
}

function show_profile($info) {
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
<table class="tablebasic" cellspacing="0" cellpadding="2">
<tr>
 <td>{$info['photo']}</td>
 <td width="100%" valign="bottom">
   <div id="profilename">{$info['name']}</div>
   <div>
	 <a href='{$info['base_url']}act=Search&amp;CODE=getalluser&amp;mid={$info['mid']}'>{$ibforums->lang['find_posts']}</a> &middot;
	 <a href='{$info['base_url']}act=Msg&amp;CODE=02&amp;MID={$info['mid']}'>{$ibforums->lang['add_to_contact']}</a>
	 <!--MEM OPTIONS-->
   </div>
 </td>
</tr>
</table>
<br />
<table cellpadding='0' align='center' cellspacing='2' border='0' width="100%">
  <tr>
	<td width='50%' valign='top' class="plainborder">
	 <table cellspacing="1" cellpadding='6' width='100%'>
	  <tr>
		<td align='center' colspan='2' class='maintitle'>{$ibforums->lang['active_stats']}</td>
	  </tr>
	  <tr>
		<td class="row3" width='30%' valign='top'><b>{$ibforums->lang['total_posts']}</b></td>
		<td align='left' width='70%' class='row1'><b>{$info['posts']}</b><br />( {$info['total_pct']}% {$ibforums->lang['total_percent']} )</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['posts_per_day']}</b></td>
		<td align='left' class='row1'><b>{$info['posts_day']}</b></td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['joined']}</b></td>
		<td align='left' class='row1'><b>{$info['joined']}</b></td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['fav_forum']}</b></td>
		<td align='left' class='row1'><a href='{$info['base_url']}act=SF&amp;f={$info['fav_id']}'>{$info['fav_forum']}</a><br />{$info['fav_posts']} {$ibforums->lang['fav_posts']}<br />( {$info['percent']}% {$ibforums->lang['fav_percent']} )</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['user_local_time']}</b></td>
		<td align='left' class='row1'>{$info['local_time']}</td>
	  </tr>
	  </table>
	</td>
	
	<!-- Communication -->
	
   <td width='50%' valign='top' class="plainborder">
	 <table cellspacing="1" cellpadding='6' width='100%'>
	  <tr>
		<td align='center' colspan='2' class='maintitle'>{$ibforums->lang['communicate']}</td>
	  </tr>
	  <tr>
		<td class="row3" width='30%' valign='top'><b>{$ibforums->lang['email']}</b></td>
		<td align='left' width='70%' class='row1'>{$info['email']}</td>
	  </tr>
	   <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['integ_msg']}</b></td>
		<td align='left' class='row1'>{$info['integ_msg']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['aim']}</b></td>
		<td align='left' class='row1'>{$info['aim_name']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['icq']}</b></td>
		<td align='left' class='row1'>{$info['icq_number']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['yahoo']}</b></td>
		<td align='left' class='row1'>{$info['yahoo']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['msn']}</b></td>
		<td align='left' class='row1'>{$info['msn_name']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['pm']}</b></td>
		<td align='left' class='row1'><a href='{$info['base_url']}act=Msg&amp;CODE=4&amp;MID={$info['mid']}'>{$ibforums->lang['click_here']}</a></td>
	  </tr>
	  </table>
	</td>
	
	<!-- END CONTENT ROW 1 -->
	<!-- information -->
	
  </tr>
  <tr>
	<td width='50%' valign='top' class="plainborder">
	 <table cellspacing="1" cellpadding='6' width='100%'>
	  <tr>
		<td align='center' colspan='2' class='maintitle'>{$ibforums->lang['info']}</td>
	  </tr>
	  <tr>
		<td class="row3" width='30%' valign='top'><b>{$ibforums->lang['homepage']}</b></td>
		<td align='left' width='70%' class='row1'>{$info['homepage']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['birthday']}</b></td>
		<td align='left' class='row1'>{$info['birthday']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['location']}</b></td>
		<td align='left' class='row1'>{$info['location']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['interests']}</b></td>
		<td align='left' class='row1'>{$info['interests']}</td>
	  </tr>
	  <!--{CUSTOM.FIELDS}-->
	  </table>
	</td>
	
	<!-- Profile -->
	
   <td width='50%' valign='top' class="plainborder">
	 <table cellspacing="1" cellpadding='6' width='100%'>
	  <tr>
		<td align='center' colspan='2' class='maintitle'>{$ibforums->lang['post_detail']}</td>
	  </tr>
	  <tr>
		<td class="row3" width='30%' valign='top'><b>{$ibforums->lang['mgroup']}</b></td>
		<td align='left' width='70%'  class='row1'>{$info['group_title']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['mtitle']}</b></td>
		<td align='left' class='row1'>{$info['member_title']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['avatar']}</b></td>
		<td align='left' class='row1'>{$info['avatar']}</td>
	  </tr>
	  <tr>
		<td class="row3" valign='top'><b>{$ibforums->lang['siggie']}</b></td>
		<td align='left' class='row1'>{$info['signature']}</td>
	  </tr>
	  <!--{WARN_LEVEL}-->
	  </table>
	</td>
	</tr>
</table>
<div class='tableborder'>
 <div class='pformstrip' align='center'>&lt;( <a href='javascript:history.go(-1)'>{$ibforums->lang['back']}</a> )</div>
</div>
	
EOF;
}

function custom_field($title, $value="") {
global $ibforums;
return <<<EOF
			<tr>
              <td class="row3" valign='top'><b>$title</b></td>
              <td align='left' class='row1'>$value</td>
            </tr>
EOF;
}

}
?>