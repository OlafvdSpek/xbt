<?php

class skin_online {



function show_row($session) {
global $ibforums;
return <<<EOF
              <!-- Entry for {$session['member_id']} -->
              <tr>
                <td class='row1'>{$session['member_name']}</td>
                <td class='row1'>{$session['where_line']}</td>
                <td class='row1' align='center'>{$session['running_time']}</td>
                <td class='row1' align='center'>{$session['msg_icon']}</td>
              </tr>
              <!-- End of Entry -->
EOF;
}

function Page_end($show_mem, $sort_order, $sort_key, $links) {
global $ibforums;
return <<<EOF
            <!-- End content Table -->
            <tr>
            <td colspan='4' class='darkrow1' align='center' valign='middle'>
             <form method='post' action='{$ibforums->base_url}act=Online&amp;CODE=listall'>
             <b>{$ibforums->lang['s_by']}&nbsp;</b>
             <select class='forminput' name='sort_key'>{$sort_key}</select>
             <select class='forminput' name='show_mem'>&nbsp;{$show_mem}</select>
             <select class='forminput' name='sort_order'>&nbsp;{$sort_order}</select>
             <input type='submit' value='{$ibforums->lang['s_go']}' class='forminput'>
             <form>
            </td>
            </tr>
            </table>
           </div>
          <br />
          <div align='left'>$links</div>
EOF;
}

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
	
    <div align='left'>$links</div>
    <br />
    <div class="tableborder">
      <div class='maintitle'>&nbsp;&nbsp;{$ibforums->lang['page_title']}</div>
	  <table cellpadding='4' cellspacing='1' border='0' width='100%'>
		<tr>
		   <th align='left' width='30%' class='titlemedium'>{$ibforums->lang['member_name']}</th>
		   <th align='left' width='30%' class='titlemedium'>{$ibforums->lang['where']}</th>
		   <th align='center' width='20%' class='titlemedium'>{$ibforums->lang['time']}</th>
		   <th align='left' width='10%' class='titlemedium'>&nbsp;</th>
		</tr>
EOF;
}


}
?>