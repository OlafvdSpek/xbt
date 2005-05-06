<?php

class skin_calendar {

function table_top($data) {
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

	function changeDiv(id, method)
	{
		var itm = null;
		if (document.getElementById) {
			itm = document.getElementById(id);
		} else if (document.all)     {
			itm = document.all[id];
		} else if (document.layers)   {
			itm = document.layers[id];
		}
		
		if (itm.style)
		{
			if ( method == 'show' )
			{
				itm.style.display = "";
			}
			else
			{
				itm.style.display = "none";
			}
		}
		else
		{
			itm.visibility = "show";
		}
	}
	
	function selecttype()
	{
		
		if ( document.REPLIER.eventtype[1].checked == true )
		{
			// Ranged date...
			
			changeDiv( 'rangeshow', 'show' );
			changeDiv( 'rangehide', 'none' );
			changeDiv( 'recurshow', 'none' );
			changeDiv( 'recurhide', 'show' );
		}
		else if ( document.REPLIER.eventtype[2].checked == true )
		{
			// Repeating event
			
			changeDiv( 'rangeshow', 'none' );
			changeDiv( 'rangehide', 'show' );
			changeDiv( 'recurshow', 'show' );
			changeDiv( 'recurhide', 'none' );
		}
		else
		{
			changeDiv( 'rangeshow', 'none' );
			changeDiv( 'rangehide', 'show' );
			changeDiv( 'recurshow', 'none' );
			changeDiv( 'recurhide', 'show' );
		}
	}
	
	function changeColour(BOXid, val)
	{
		document.all.BOXid.style.backgroundColor = val;
	}
	//-->
</script>

<table class='tableborder' cellpadding="0" cellspacing="0" width="100%">
<tr>
 <td class='maintitle' colspan="2">&nbsp;&nbsp;$data</td>
</tr>
      
EOF;
}

function calendar_end_form($data) {
global $ibforums;
return <<<EOF
 <tr>
  <td class='pformstrip' align='center' style='text-align:center' colspan="2"><input type="submit" name="submit" value="$data" tabindex='4' class='forminput'></td>
</tr>
</table>
</form>
<br />
<br clear="all" />
EOF;
}

function calendar_start_form() {
global $ibforums;
return <<<EOF
<form action='{$ibforums->base_url}act=calendar&amp;code=addnewevent' method='post' name='REPLIER' onSubmit='return ValidateForm()'>
EOF;
}


function calendar_start_edit_form($eventid) {
global $ibforums;
return <<<EOF
<form action='{$ibforums->base_url}act=calendar&amp;code=doedit&amp;eventid=$eventid' method='post' name='REPLIER' onsubmit='return ValidateForm()'>
EOF;
}


function calendar_event_title($data="") {
global $ibforums;
return <<<EOF
  
<tr> 
          <td class="pformleft"><strong>{$ibforums->lang['calendar_title']}</strong></td>
          <td class="pformright"><input type='text' size='40' maxlength='40' name='event_title' class='forminput' value='$data' /></td>
        </tr>
EOF;
}


function calendar_delete_box() {
global $ibforums;
return <<<EOF
<tr> 
          <td class="row1" width="100%" colspan='2' style='height:40px;border:1px solid black'><input type='checkbox' name='event_delete' value='1'>&nbsp;{$ibforums->lang['calendar_delete_box']}</td>
        </tr>
EOF;
}


function calendar_choose_date($days, $months, $years, $end_days, $end_months, $end_years, $recur_days, $recur_unit, $div, $checked, $cols, $recend) {
global $ibforums;
return <<<EOF
<tr> 
          <td class="pformleft" valign='top'><strong>{$ibforums->lang['calendar_event_date']}</strong></td>
          <td class="pformright">
          	<table cellpadding='3'>
          	<tr>
          	 <td><input type='radio' name='eventtype' id='single' onclick='selecttype();' class='radiobutton' value='single' {$checked['normal']}><strong><label for='single'>{$ibforums->lang['fv_single']}</label></strong></td>
          	 <td><select name='e_day' class='forminput'>$days</select>&nbsp;&nbsp;<select name='e_month' class='forminput'>$months</select>&nbsp;&nbsp;<select name='e_year' class='forminput'>$years</select></td>
          	</tr>
          	<tr>
          	 <td><input type='radio' name='eventtype' id='range' onclick='selecttype();' class='radiobutton' value='range'{$checked['range']}><strong><label for='range'>{$ibforums->lang['fv_range']}</label></strong></td>
          	 <td>
          	    <div id='rangeshow' style='display:{$div['range_on']}'>
          	      <table cellpadding='3'>
          	       <tr>
          	        <td><strong>{$ibforums->lang['fv_range_ends']}</strong></td>
          	        <td><select name='end_day' class='forminput'>$end_days</select>&nbsp;&nbsp;<select name='end_month' class='forminput'>$end_months</select>&nbsp;&nbsp;<select name='end_year' class='forminput'>$end_years</select></td>
          	       </tr>
          	       <tr>
          	       	<td><strong>{$ibforums->lang['fv_bgcolor']}</strong></td>
          	       	<td>{$cols['bg']} <input type='text' name='style' size='5' readonly='readonly' id='stylebg' style='border:2px inset black;' /></td>
          	       </tr>
          	       <tr>
          	       	<td><strong>{$ibforums->lang['fv_font']}</strong></td>
          	       	<td>{$cols['ft']} <input type='text' name='style2' size='5' readonly='readonly' id='styleft' style='border:2px inset black;' /></td>
          	       </tr>
          	      </table>
          	    </div>
          	    <div id='rangehide' style='display:{$div['range_off']}'>{$ibforums->lang['fv_hidden']}</div>
          	 </td>
          	</tr>
          	<tr>
          	 <td><input type='radio' name='eventtype' id='recur'  onclick='selecttype();' class='radiobutton' value='recur' {$checked['recur']}><strong><label for='recur'>{$ibforums->lang['fv_recur']}</label></strong></td>
          	 <td>
          	     <div id='recurshow' style='display:{$div['recur_on']}'>
          	     	<strong>{$ibforums->lang['fv_recur_every']}</strong> <select name='recur_unit' class='forminput'>$recur_unit</select>
          	     	<strong>{$ibforums->lang['fv_until']}</strong> <select name='recend_day' class='forminput'>{$recend['d']}</select><select name='recend_month' class='forminput'>{$recend['m']}</select>&nbsp;&nbsp;<select name='recend_year' class='forminput'>{$recend['y']}</select>
          	     </div>
          	     <div id='recurhide' style='display:{$div['recur_off']}'>{$ibforums->lang['fv_hidden']}</div>
          	 </td>
			</tr>
			</table>
          </td>
        </tr>
EOF;
}


function calendar_event_type($pub_select="", $priv_select="") {
global $ibforums;
return <<<EOF
<tr> 
          <td class="pformleft"><strong>{$ibforums->lang['calendar_event_type']}</strong></td>
          <td class="pformright"><select name='e_type' class='forminput'><option value='public'$pub_select>{$ibforums->lang['calendar_type_public']}</option><option value='private'$priv_select>{$ibforums->lang['calendar_type_private']}</option></select></td>
        </tr>
EOF;
}


function calendar_admin_group_box($groups) {
global $ibforums;
return <<<EOF
<tr> 
          <td class="pformleft">{$ibforums->lang['calendar_group_filter']}</td>
          <td class="pformright"><select name='e_groups[]' class='forminput' size='5' multiple>$groups</select></td>
        </tr>
EOF;
}



function cal_edit_del_button($id) {
global $ibforums;

return <<<HTML
	<div align='right'><a href='javascript:deleteEvent($id)'><{P_DELETE}></a>&nbsp;<a href='{$ibforums->base_url}act=calendar&amp;code=edit&amp;eventid=$id'><{P_EDIT}></a></div>
HTML;
}


function cal_show_event($event, $member, $event_type, $edit_button="", $type, $date_ends="") {
global $ibforums;

return <<<HTML
  <div class='pformstrip'>{$event['title']} ($event_type)</div>
  <table width="100%" border="0" cellspacing="1" cellpadding="3">
  <tr>
   <td class='row4' width='1%' valign='middle' nowrap='nowrap'><span class='normalname'><a href='{$ibforums->base_url}showuser={$member['id']}'>{$member['name']}</a></span></td>
   <td class='row4' width='99%'><div align='left' style='float:left;width:auto;padding-top:4px;padding-bottom:4px;'><strong>{$ibforums->lang['event_date']} {$event['mday']} {$event['month_text']} {$event['year']}</strong> $date_ends ($type)</div>$edit_button</td>
  </tr>
  <tr>
   <td valign='top' class='row1' nowrap='nowrap'>{$member['avatar']}<span class='postdetails'><br />{$ibforums->lang['group']} {$member['g_title']}<br />{$ibforums->lang['posts']} {$member['posts']}<br />{$ibforums->lang['joined']} {$member['joined']}</span></td>
   <td valign='top' class='row1'><span class='postcolor'>{$event['event_text']}</span></td>
  </tr>
  </table>
HTML;
}

function cal_page_events_start() {
global $ibforums;

return <<<HTML
<script type='text/javascript'>
	function deleteEvent(id) {
		if (confirm('{$ibforums->lang['js_del_1']}')) {
			 window.location.href = "{$ibforums->base_url}act=calendar&code=delete&e=" + id;
		 }
		 else {
			alert ('{$ibforums->lang['js_del_2']}');
		} 
	}
</script>
<div class='tableborder'>
  <div class='maintitle'>{$ibforums->lang['cal_title_events']}</div>
HTML;
}

function cal_page_events_end() {
global $ibforums;

return <<<HTML
  <div align='center' class='pformstrip'>&lt;&lt; <a href='{$ibforums->base_url}act=calendar&amp;d={$ibforums->input['d']}&amp;m={$ibforums->input['m']}&amp;y={$ibforums->input['y']}'>{$ibforums->lang['back']}</a></div>
</div>
HTML;
}

function cal_birthday_start() {
global $ibforums;

return <<<HTML
  <div class='pformstrip'>{$ibforums->lang['cal_birthdays']}</div>
  <div class='tablepad'>
   <ul>	
HTML;
}

function cal_birthday_entry($uid, $uname, $age="") {
global $ibforums;

return <<<HTML
		<li><a href='{$ibforums->base_url}showuser=$uid'>$uname</a> ($age)</li>
HTML;
}


function cal_birthday_end() {
global $ibforums;

return <<<HTML
   </ul>
  </div>
HTML;
}



function cal_main_content($month, $year, $prev, $next) {
global $ibforums;

return <<<HTML

<form action='{$ibforums->base_url}act=calendar' method='post'>
<div align='left' id='calendarname'><a href='{$ibforums->base_url}act=calendar&amp;code=newevent' title='{$ibforums->lang['post_new_event']}'><{CAL_NEWEVENT}></a> <span style='position:relative;top:3px'>$month $year</span></div>
<br />
<div class='tableborder'>
  <div class='maintitle'>
    <div align='center'>
      &lt; <a href='{$ibforums->base_url}act=calendar&amp;m={$prev['month_id']}&amp;y={$prev['year_id']}'>{$prev['month_name']} {$prev['year_id']}</a>
      &middot; {$ibforums->lang['table_title']} &middot;
	   <a href='{$ibforums->base_url}act=calendar&amp;m={$next['month_id']}&amp;y={$next['year_id']}'>{$next['month_name']} {$next['year_id']}</a> &gt;
	</div>
  </div>
  <table width="100%" border="0" cellspacing="1" cellpadding="0">
   <tr>
	 <!--IBF.DAYS_TITLE_ROW-->
   
	 <!--IBF.DAYS_CONTENT-->
	</tr>
  </table>
</div>
<br />
<div class='tableborder'>
  <div class='pformstrip'>
     <div align='left' style='float:left;width:40%'><a href='{$ibforums->base_url}act=calendar&amp;code=newevent'>{$ibforums->lang['post_new_event']}</a></div>
  	 <div align='right'><strong>{$ibforums->lang['month_jump']}</strong>&nbsp;<select name='m' class='forminput'><!--IBF.MONTH_BOX--></select>&nbsp;<select name='y' class='forminput'><!--IBF.YEAR_BOX--></select>&nbsp;&nbsp;<input type='submit' value='{$ibforums->lang['form_submit_show']}' class='forminput' /></div>
  </div>
</div>
</form>
HTML;
}

function cal_day_bit($day) {
global $ibforums;

return <<<HTML

	<td width='14%' class='pformstrip'>$day</td>
HTML;
}


function cal_new_row() {
global $ibforums;

return <<<HTML

	</tr>
	<!-- NEW ROW-->
	<tr>
	
HTML;
}

function cal_blank_cell() {
global $ibforums;

return <<<HTML

	<td style='height:100px' class='darkrow1'><br /></td>
	
HTML;
}


function cal_date_cell($month_day, $events="") {
global $ibforums;

return <<<HTML

	<td style='height:100px' valign='top' class='row3'>
	<div class='caldate'>$month_day</div>$events
	</td>
	
HTML;
}

function cal_date_cell_today($month_day, $events="") {
global $ibforums;

return <<<HTML

	<td style='height:100px;border:2px;border-style:outset' valign='top' class='row1'>
	<div class='caldate'>$month_day</div>
	$events
	</td>
	
HTML;
}


function cal_events_start() {
global $ibforums;

return <<<HTML
<div style='padding:2px'>
	
HTML;
}

function cal_events_wrap($link, $text) {
global $ibforums;

return <<<HTML
&middot;<strong><a href='{$ibforums->base_url}act=calendar&amp;$link'>$text</a></strong><br />
HTML;
}

function cal_events_wrap_range($link, $text, $ft="", $bg="") {
global $ibforums;

return <<<HTML
<div style='background-color:$bg;color:$ft;padding:3px;border-top:3px outset $bg;border-bottom:3px outset $bg;'><a href='{$ibforums->base_url}act=calendar&amp;$link' style='color:$ft'>$text</a></div>
HTML;
}

function cal_events_wrap_recurring($link, $text) {
global $ibforums;

return <<<HTML
&middot; <a href='{$ibforums->base_url}act=calendar&amp;$link' title='{$ibforums->lang['tbt_recur']}'>$text</a><br />
HTML;
}

function cal_events_end() {
global $ibforums;

return <<<HTML
</div>
HTML;
}

}
?>