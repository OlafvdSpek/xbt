<?php

class skin_subscriptions {

function do_nochex_upgrade_screen($ccode, $member_id, $new_sub_id, $to_pay, $nochex_email, $sub_title, $cur_tran_id, $desc_string="") {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['sc_complete']}</div>
<p>
{$ibforums->lang['sc_upgrade_explain']}
<br /><br />$desc_string

<br /><br /><b>{$ibforums->lang['gw_nochex']}</b>
</p>
<form action='https://www.nochex.com/nochex.dll/checkout' method='post'>
<input type="hidden" name="email" value="$nochex_email" />
<input type="hidden" name="amount" value="$to_pay" />
<input type="hidden" name="ordernumber" value="{$new_sub_id}x{$member_id}x$cur_tran_id" />
<input type="hidden" name="returnurl" value="{$ibforums->base_url}act=module&module=subscription&CODE=paydone&type=nochex">
<br /><br />
<center>
<input type="image" src="http://www.nochex.com/web/images/payme4.gif" border="0" name="submit" alt="Pay with NOCHEX now"></center>
</form>
EOF;
}

function do_nochex_normal_screen($ccode, $member_id, $new_sub_id, $to_pay, $nochex_email, $sub_title, $desc_string="") {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['sc_complete']}</div>
<p>
{$ibforums->lang['sc_upgrade_explain']}
<br /><br />$desc_string
<br /><br /><b>{$ibforums->lang['gw_nochex']}</b>
</p>
<form action='https://www.nochex.com/nochex.dll/checkout' method='post'>
<input type="hidden" name="email" value="$nochex_email" />
<input type="hidden" name="amount" value="$to_pay" />
<input type="hidden" name="ordernumber" value="{$new_sub_id}x{$member_id}x0" />
<input type="hidden" name="returnurl" value="{$ibforums->base_url}act=module&module=subscription&CODE=paydone&type=nochex">
<br /><br />
<center>
<input type="image" src="http://www.nochex.com/web/images/payme4.gif" border="0" name="submit" alt="Pay with NOCHEX now"></center>
</form>
EOF;
}

function show_ticket($sub, $id, $paid, $extra="") {
global $ibforums;
return <<<EOF
<h2>{$ibforums->lang['man_title']}</h2>
<br />
<table cellpadding='0' cellspacing='1' border='0' width='245' align='center' bgcolor='#000000'>
<tr>
 <td>
  <table cellpadding='4'  cellspacing='1' border='0' width='100%' align='center'>
  <tr>
   <td width='50%' bgcolor='#FFFFFF'>{$ibforums->lang['man_mem']}</td>
   <td width='50%' bgcolor='#FFFFFF'>{$ibforums->member['name']}</td>
  </tr>
  <tr>
   <td width='50%' bgcolor='#FFFFFF'>{$ibforums->lang['man_mem_id']}</td>
   <td width='50%' bgcolor='#FFFFFF'>{$ibforums->member['id']}</td>
  </tr>
  <tr>
   <td width='50%' bgcolor='#FFFFFF'>{$ibforums->lang['man_email']}</td>
   <td width='50%' bgcolor='#FFFFFF'>{$ibforums->member['email']}</td>
  </tr>
  <tr>
   <td width='50%' bgcolor='#FFFFFF'>{$ibforums->lang['man_trans_id']}</td>
   <td width='50%' bgcolor='#FFFFFF'>{$id}</td>
  </tr>
  <tr>
   <td width='50%' bgcolor='#FFFFFF'>{$ibforums->lang['man_sub']}</td>
   <td width='50%' bgcolor='#FFFFFF'>{$sub['sub_title']} $extra</td>
  </tr>
  <tr>
   <td width='50%' bgcolor='#FFFFFFF'>{$ibforums->lang['man_pay']}</td>
   <td width='50%' bgcolor='#FFFFFF'>{$paid}</td>
  </tr>
 </table>
</td>
</tr>
</table>
EOF;
}



function do_manual_normal_screen($new_sub_id, $desc_string="") {
global $ibforums;
return <<<EOF
<script type="text/javascript">
<!--
function pop_ticket()
{
	window.open('{$ibforums->js_base_url}act=module&module=subscription&type=manual&CODE=custom&mode=ticket&tickid=0&upgrade=0&sid=$new_sub_id','TICKET','width=250,height=350,resizable=yes,scrollbars=yes');
}

//-->
</script>
<div class="pformstrip">{$ibforums->lang['sc_complete']}</div>
<p>
$desc_string
<br /><br />
{$ibforums->lang['post_manual']}
<br /><br />
{$ibforums->lang['post_manual_more']}
</p>
<br /><br />
<center>
<input type="button" value="{$ibforums->lang['gw_manual']}" onclick="pop_ticket()" />
</center>
EOF;
}

function do_manual_upgrade_screen($new_sub_id, $cur_id, $desc_string="") {
global $ibforums;
return <<<EOF
<script type="text/javascript">
<!--
function pop_ticket()
{
	window.open('{$ibforums->js_base_url}act=module&module=subscription&type=manual&CODE=custom&mode=ticket&tickid={$cur_id}&upgrade=1&sid=$new_sub_id','TICKET','width=250,height=350,resizable=yes,scrollbars=yes');
}

//-->
</script>
<div class="pformstrip">{$ibforums->lang['sc_upgrade_title']}</div>
<p>
$desc_string
<br /><br />
{$ibforums->lang['post_manual']}
<br /><br />
{$ibforums->lang['post_manual_more']}
</p>
<br /><br />
<center>
<input type="button" value="{$ibforums->lang['gw_manual']}" onclick="pop_ticket()" />
</center>
EOF;
}

function do_paypal_normal_recurring_screen($ccode, $member_id, $new_sub_id, $unit, $length, $cost, $paypal_email, $sub_title, $desc_string="") {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['sc_complete']}</div>
<p>
{$ibforums->lang['sc_upgrade_explain']}
<br /><br />$desc_string
<br /><br /><b>{$ibforums->lang['gw_paypal']}</b>
</p>
<form action='https://www.paypal.com/cgi-bin/webscr' method='post'>
<input type="hidden" name="cmd" value="_xclick-subscriptions" />
<input type="hidden" name="business" value="$paypal_email" />
<input type="hidden" name="item_name" value="$sub_title" />
<input type="hidden" name="item_number" value="$new_sub_id" />
<input type="hidden" name="no_shipping" value="1" />
<input type="hidden" name="src" value="1" />
<input type="hidden" name="a3" value="$cost" />
<input type="hidden" name="p3" value="$length" />
<input type="hidden" name="t3" value="$unit" />
<input type="hidden" name="custom" value="$member_id" />
<input type="hidden" name="notify_url" value="{$ibforums->base_url}act=module&module=subscription&CODE=incoming&type=paypal" />
<input type="hidden" name="currency_code" value="$ccode">
<input type="hidden" name="return" value="{$ibforums->base_url}act=module&module=subscription&CODE=paydone&type=paypal">
<input type="hidden" name="cancel_return" value="{$ibforums->base_url}act=module&module=subscription&CODE=paydone&type=paypal">
<br /><br />
<center>
<input type="image" src="https://www.paypal.com/images/x-click-but6.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!"></center>
</form>
EOF;
}

function do_paypal_upgrade_recurring_screen($ccode, $member_id, $new_sub_id, $to_pay, $time_left_to_run, $time_left_unit, $unit, $length, $cost, $paypal_email, $sub_title, $cur_tran_id, $desc_string="") {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['sc_complete']}</div>
<p>
{$ibforums->lang['sc_upgrade_explain']}
<br /><br />$desc_string
<br /><br /><b>{$ibforums->lang['gw_paypal']}</b>
</p>
<form action='https://www.paypal.com/cgi-bin/webscr' method='post'>
<input type="hidden" name="cmd" value="_xclick-subscriptions" />
<input type="hidden" name="business" value="$paypal_email" />
<input type="hidden" name="item_name" value="$sub_title" />
<input type="hidden" name="item_number" value="$new_sub_id" />
<input type="hidden" name="no_shipping" value="1" />
<input type="hidden" name="src" value="1" />
<input type="hidden" name="a1" value="$to_pay" />
<input type="hidden" name="p1" value="$time_left_to_run" />
<input type="hidden" name="t1" value="$time_left_unit" />
<input type="hidden" name="a3" value="$cost" />
<input type="hidden" name="p3" value="$length" />
<input type="hidden" name="t3" value="$unit" />
<input type="hidden" name="invoice" value="{$cur_tran_id}x{$new_sub_id}x{$member_id}" />
<input type="hidden" name="custom" value="$member_id" />
<input type="hidden" name="memo" value="upgrade" />
<input type="hidden" name="notify_url" value="{$ibforums->base_url}act=module&module=subscription&CODE=incoming&type=paypal" />
<input type="hidden" name="currency_code" value="$ccode">
<input type="hidden" name="return" value="{$ibforums->base_url}act=module&module=subscription&CODE=paydone&type=paypal">
<input type="hidden" name="cancel_return" value="{$ibforums->base_url}act=module&module=subscription&CODE=paydone&type=paypal">
<br /><br />
<center>
<input type="image" src="https://www.paypal.com/images/x-click-but6.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!"></center>
</form>
EOF;
}

function do_paypal_normal_screen($ccode, $member_id, $new_sub_id, $to_pay, $paypal_email, $sub_title, $desc_string="") {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['sc_complete']}</div>
<p>
{$ibforums->lang['sc_upgrade_explain']}
<br /><br />$desc_string
<br /><br /><b>{$ibforums->lang['gw_paypal']}</b>
</p>
<form action='https://www.paypal.com/cgi-bin/webscr' method='post'>
<input type="hidden" name="cmd" value="_xclick" />
<input type="hidden" name="business" value="$paypal_email" />
<input type="hidden" name="amount" value="$to_pay">
<input type="hidden" name="item_name" value="$sub_title" />
<input type="hidden" name="item_number" value="$new_sub_id" />
<input type="hidden" name="no_shipping" value="1" />
<input type="hidden" name="custom" value="$member_id" />
<input type="hidden" name="notify_url" value="{$ibforums->base_url}act=module&module=subscription&CODE=incoming&type=paypal" />
<input type="hidden" name="currency_code" value="$ccode">
<input type="hidden" name="return" value="{$ibforums->base_url}act=module&module=subscription&CODE=paydone&type=paypal">
<input type="hidden" name="cancel_return" value="{$ibforums->base_url}act=module&module=subscription&CODE=paydone&type=paypal">
<br /><br />
<center>
<input type="image" src="https://www.paypal.com/images/x-click-but6.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!"></center>
</form>
EOF;
}

function do_paypal_upgrade_screen($ccode, $member_id, $new_sub_id, $to_pay, $paypal_email, $sub_title, $cur_tran_id, $desc_string="") {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['sc_complete']}</div>
<p>
{$ibforums->lang['sc_upgrade_explain']}
<br /><br />$desc_string
<br /><br /><b>{$ibforums->lang['gw_paypal']}</b>
</p>
<form action='https://www.paypal.com/cgi-bin/webscr' method='post'>
<input type="hidden" name="cmd" value="_xclick" />
<input type="hidden" name="business" value="$paypal_email" />
<input type="hidden" name="amount" value="$to_pay">
<input type="hidden" name="item_name" value="$sub_title" />
<input type="hidden" name="item_number" value="$new_sub_id" />
<input type="hidden" name="no_shipping" value="1" />
<input type="hidden" name="invoice" value="{$cur_tran_id}x{$new_sub_id}x{$member_id}" />
<input type="hidden" name="custom" value="$member_id" />
<input type="hidden" name="memo" value="upgrade" />
<input type="hidden" name="notify_url" value="{$ibforums->base_url}act=module&module=subscription&CODE=incoming&type=paypal" />
<input type="hidden" name="currency_code" value="$ccode">
<input type="hidden" name="return" value="{$ibforums->base_url}act=module&module=subscription&CODE=paydone&type=paypal">
<input type="hidden" name="cancel_return" value="{$ibforums->base_url}act=module&module=subscription&CODE=paydone&type=paypal">
<br /><br />
<center>
<input type="image" src="https://www.paypal.com/images/x-click-but6.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!"></center>
</form>
EOF;
}







function sub_two_upgrade_summary() {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['sc_upgrade_title']}</div>
<p>{$ibforums->lang['sc_upgrade_explain']}
<br /><br />{$ibforums->lang['sc_upgrade_string']}
</p>             
EOF;
}

function sub_two_normal_summary() {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['sc_normal_title']}</div>
<p>{$ibforums->lang['sc_upgrade_explain']}
<br /><br />{$ibforums->lang['sc_normal_string']}
</p>             
EOF;
}


function sub_two_methods_top($sub_chosen, $upgrade="", $curid="", $currency="") {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['sc_available_methods']}</div>
<form action='{$ibforums->base_url}act=module&amp;module=subscription&amp;upgrade=$upgrade&amp;CODE=paymentscreen&amp;curid=$curid&amp;sub=$sub_chosen&amp;currency=$currency' method='post'>             
<div class="tableborder">
  <table cellpadding='4' cellspacing='1' width="100%">
  <tr>
	<th width='5%'  class='titlemedium' align="center">&nbsp;</th>
	<th width='95%' class='titlemedium'>{$ibforums->lang['sc_method']}</th>
  </tr>
EOF;
}

function sub_two_methods_row($mid, $name, $desc="") {
global $ibforums;
return <<<EOF
<tr>
 <td class="row3" align="center"><input type='radio' name='methodid' value="$mid" /></td>
 <td class="row3"><b>$name</b><br /><i>$desc</i></td>
</tr>
EOF;
}

function sub_two_methods_bottom() {
global $ibforums;
return <<<EOF
</table>
</div>
EOF;
}

function sub_two_methods_continue_button() {
global $ibforums;
return <<<EOF
<br />
<div class='tableborder'>
 <div class='pformstrip' align="center"><input type='submit' value='{$ibforums->lang['s_continue_button2']}' class='forminput' /></div>
</div>
</form>
EOF;
}

function sub_choose_current_top() {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['s_current_subs']}</div>
<p>{$ibforums->lang['s_current_explain']}</p>             
<div class="tableborder">
  <table cellpadding='4' cellspacing='1' width="100%">
  <tr>
	<th width='5%'  class='titlemedium' align="center">{$ibforums->lang['s_transid']}</th>
	<th width='35%' class='titlemedium'>{$ibforums->lang['s_detail']}</th>
	<th width='20%' class='titlemedium' align="center">{$ibforums->lang['s_started']}</th>
	<th width='20%' class='titlemedium' align="center">{$ibforums->lang['s_end']}</th>
	<th width='10%' class='titlemedium' align="center">{$ibforums->lang['s_paid']}</th>
	<th width='10%' class='titlemedium' align="center">{$ibforums->lang['s_status']}</th>
   </tr>
EOF;
}

function sub_choose_current_row($id, $name, $start, $end, $paid, $state ) {
global $ibforums;
return <<<EOF
<tr>
 <td class="row3" align="center">$id</td>
 <td class="row3"><b>$name</b></td>
 <td class="row3" align="center">$start</td>
 <td class="row3" align="center">$end</td>
 <td class="row3" align="center">{$paid}</td>
 <td class="row3" align="center">$state</td>
</tr>
EOF;
}

function sub_choose_current_bottom() {
global $ibforums;
return <<<EOF
</table>
</div>
EOF;
}

function sub_page_bottom() {
global $ibforums;
return <<<EOF
<p>{$ibforums->lang['s_current_bottom']}</p>
EOF;
}


function sub_choose_dead_top() {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['s_dead_subs']}</div>
<p>{$ibforums->lang['s_dead_explain']}</p>             
<div class="tableborder">
  <table cellpadding='4' cellspacing='1' width="100%">
  <tr>
	<th width='5%'  class='titlemedium' align="center">{$ibforums->lang['s_transid']}</th>
	<th width='35%' class='titlemedium'>{$ibforums->lang['s_detail']}</th>
	<th width='20%' class='titlemedium' align="center">{$ibforums->lang['s_started']}</th>
	<th width='20%' class='titlemedium' align="center">{$ibforums->lang['s_end']}</th>
	<th width='10%' class='titlemedium' align="center">{$ibforums->lang['s_paid']}</th>
	<th width='10%' class='titlemedium' align="center">{$ibforums->lang['s_status']}</th>
   </tr>
EOF;
}

function sub_choose_dead_row($id, $name, $start, $end, $paid, $state ) {
global $ibforums;
return <<<EOF
<tr>
 <td class="row3" align="center">$id</td>
 <td class="row3"><b>$name</b></td>
 <td class="row3" align="center">$start</td>
 <td class="row3" align="center">$end</td>
 <td class="row3" align="center">{$paid}</td>
 <td class="row3" align="center">$state</td>
</tr>
EOF;
}

function sub_choose_dead_bottom() {
global $ibforums;
return <<<EOF
</table>
</div>
<br />
EOF;
}


function sub_choose_upgrade_top($cur_id="", $currency="") {
global $ibforums;
return <<<EOF
<br />
<div class="pformstrip">{$ibforums->lang['s_upgrade_subs']}</div>
<p>{$ibforums->lang['s_upgrade_explain']}</p>
<br /> 
<form action='{$ibforums->base_url}act=module&amp;module=subscription&amp;upgrade=1&amp;CODE=paymentmethod&amp;curid=$cur_id&amp;currency=$currency' method='post'>             
<div class="tableborder">
  <table cellpadding='4' cellspacing='1' width="100%">
  <tr>
	<th width='5%'  class='titlemedium'>&nbsp;</th>
	<th width='55%' class='titlemedium'>{$ibforums->lang['s_detail']}</th>
	<th width='20%' class='titlemedium' align="center">{$ibforums->lang['s_end']}</th>
	<th width='20%' class='titlemedium' align="center">{$ibforums->lang['s_balance_cost']}</th>
   </tr>
EOF;
}


function sub_choose_upgrade_row($id, $name, $desc, $cost, $duration ) {
global $ibforums;
return <<<EOF
<tr>
 <td class="row3" align="center"><input type='radio' name='sub' value="$id" /></td>
 <td class="row3"><b>$name</b><br /><i>$desc</i></td>
 <td class="row3" align="center">$duration</td>
 <td class="row3" align="center">{$cost}</td>
</tr>
EOF;
}

function sub_choose_upgrade_bottom() {
global $ibforums;
return <<<EOF
</table>
</div>
<div class='tableborder'>
 <div class='pformstrip' align="center"><input type='submit' value='{$ibforums->lang['s_continue_button']}' class='forminput' /></div>
</div>
</form>
EOF;
}



function sub_choose_new_top($currency) {
global $ibforums;
return <<<EOF
<div class="pformstrip">{$ibforums->lang['s_available_subs']}</div>
<p>{$ibforums->lang['s_explain']}</p>
<br />
<form action='{$ibforums->base_url}act=module&amp;module=subscription&amp;CODE=paymentmethod&amp;currency=$currency' method='post'>             
<div class="tableborder">
  <table cellpadding='4' cellspacing='1' width="100%">
  <tr>
	<th width='5%'  class='titlemedium'>&nbsp;</th>
	<th width='55%' class='titlemedium'>{$ibforums->lang['s_detail']}</th>
	<th width='20%' class='titlemedium' align="center">{$ibforums->lang['s_duration']}</th>
	<th width='20%' class='titlemedium' align="center">{$ibforums->lang['s_cost']}</th>
   </tr>
EOF;
}


function sub_choose_new_row($id, $name, $desc, $cost, $duration ) {
global $ibforums;
return <<<EOF
<tr>
 <td class="row3" align="center"><input type='radio' name='sub' value="$id" /></td>
 <td class="row3"><b>$name</b><br /><i>$desc</i></td>
 <td class="row3" align="center">$duration</td>
 <td class="row3" align="center">{$cost}</td>
</tr>
EOF;
}

function sub_choose_new_bottom() {
global $ibforums;
return <<<EOF
</table>
</div>
<div class='tableborder'>
 <div class='pformstrip' align="center"><input type='submit' value='{$ibforums->lang['s_continue_button']}' class='forminput' /></div>
</div>
</form>
EOF;
}


function sub_currency_change_form($select, $url) {
global $ibforums;
return <<<EOF
<div align='right'><form action="$url" method="post">{$ibforums->lang['cc_currency_in']} $select <input type='submit' value='{$ibforums->lang['cc_change']}' class='forminput' /></form></div>
EOF;
}

function sub_currency_change_top() {
global $ibforums;
return <<<EOF
<select name='currency' class='forminput'>
EOF;
}

function sub_currency_change_row($id, $name, $default="") {
global $ibforums;
return <<<EOF
<option value='$id' $default>$id $name</option>
EOF;
}

function sub_currency_change_bottom() {
global $ibforums;
return <<<EOF
</select>
EOF;
}



}
?>