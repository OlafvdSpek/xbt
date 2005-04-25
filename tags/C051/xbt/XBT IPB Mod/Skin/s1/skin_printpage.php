<?php

class skin_printpage {

function choose_form($fid, $tid, $title) {
global $ibforums;
return <<<EOF
<div class='tableborder'>
 <div class='maintitle'><{CAT_IMG}>&nbsp;{$ibforums->lang['tvo_title']}&nbsp;$title</div>
 <div class='tablepad'>
  <b><a href='{$ibforums->base_url}act=Print&amp;client=printer&amp;f=$fid&amp;t=$tid'>{$ibforums->lang['o_print_title']}</a></b>
  <br />
  {$ibforums->lang['o_print_desc']}
  <br /><br />
  <b><a href='{$ibforums->base_url}act=Print&amp;client=html&amp;f=$fid&amp;t=$tid'>{$ibforums->lang['o_html_title']}</a></b>
  <br />
  {$ibforums->lang['o_html_desc']}
  <br /><br />
  <b><a href='{$ibforums->base_url}act=Print&amp;client=wordr&amp;f=$fid&amp;t=$tid'>{$ibforums->lang['o_word_title']}</a></b>
  <br />
  {$ibforums->lang['o_word_desc']}
 </div>
 <div align='center' class='pformstrip'>&lt;&lt;<a href='{$ibforums->base_url}showtopic=$tid'>{$ibforums->lang['back_topic']}</a></div>
</div>
<br />
EOF;
}



function pp_postentry($poster, $entry ) {
global $ibforums;
return <<<EOF
	<table width='90%' align='center' cellpadding='6' border='1'>
	<tr>
	 <td bgcolor='#EEEEEE'><font face='arial' size='2' color='#000000'><b>{$ibforums->lang['by']}: {$entry['author_name']}</b> {$ibforums->lang['on']} {$entry['post_date']}</b></font></td>
	</tr>
	<tr>
	 <td><font face='arial' size='3' color='#000000'>{$entry['post']}</font></td>
	</tr>
	</table>
	<br />
EOF;
}

function pp_end() {
global $ibforums;
return <<<EOF
    <center><font face='arial' size='1' color='#000000'>Powered by Invision Power Board (http://www.invisionboard.com)<br />&copy; Invision Power Services (http://www.invisionpower.com)</font></center>
EOF;
}

function pp_header($forum_name, $topic_title, $topic_starter,$fid, $tid) {
global $ibforums;
return <<<EOF
    <html>
    <head>
      <title>{$ibforums->vars['board_name']} [Powered by Invision Power Board]</title>
    </head>
    <body bgcolor='#FFFFFF' alink='#000000' vlink='#000000' link='#000000'>
     <table width='90%' border='0' align='center' cellpadding='6'>
      <tr>
       <td><b><font face='arial' size='5' color='#4C77B6'><b>{$ibforums->lang['title']}</font></b>
       	   <br /><font face='arial' size='2' color='#000000'><b><a href='{$ibforums->base_url}act=ST&amp;f=$fid&amp;t=$tid'>{$ibforums->lang['topic_here']}</a></b></font>
       </td>
      </tr>
      <tr>
       <td><font face='arial' size='2' color='#000000'><b>{$ibforums->vars['board_name']} &gt; $forum_name &gt; <font color='red'>$topic_title</font></b></font></td>
      </tr>
     </table>
     <br />
     <br />
EOF;
}


}
?>