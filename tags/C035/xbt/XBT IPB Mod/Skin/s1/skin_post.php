<?php

class skin_post {



function nameField_unreg($data) {
global $ibforums;
return <<<EOF
<tr>
 <td colspan="2" class='pformstrip'>{$ibforums->lang['unreg_namestuff']}</td>
</tr>
<tr>
  <td class='pformleft'>{$ibforums->lang['guest_name']}</td>
  <td class='pformright'><input type='text' size='40' maxlength='40' name='UserName' value='$data' class='textinput' /></td>
</tr>

EOF;
}


function poll_box($data, $extra="") {
global $ibforums;
return <<<EOF
<tr>
  <td colspan="2" class='pformstrip'>{$ibforums->lang['tt_poll_settings']}</td>
</tr>
<tr>
  <td class='pformleft'><strong>{$ibforums->lang['poll_question']}</strong></td>
  <td class='pformright'><input type='text' size='40' maxlength='250' name='pollq' value='{$ibforums->input['pollq']}' class='textinput' /></td>
</tr>
<tr>
  <td class='pformleft'>{$ibforums->lang['poll_choices']}<br /><br />$extra</td>
  <td class='pformright'><textarea cols='60' rows='12' name='PollAnswers' class='textinput'>$data</textarea><!--IBF.POLL_OPTIONS--></td>
</tr>

EOF;
}

function poll_options() {
global $ibforums;
return <<<EOF
<br /><input type='checkbox' size='40' value='1' name='allow_disc' class='forminput' />&nbsp;{$ibforums->lang['poll_only']}
EOF;
}

function poll_end_form($data) {
global $ibforums;
return <<<EOF
 <tr>
  <td class='pformstrip' align='center' style='text-align:center' colspan="2">
	<input type="submit" name="submit" value="$data" tabindex='4' class='forminput' accesskey='s' />&nbsp;
  </td>
</tr>
</table>
</form>
<br />
<br clear="all" />
EOF;
}


function pm_postbox_buttons($data) {
global $ibforums;
return <<<EOF
 <tr>
 <td class='pformstrip' colspan="2">{$ibforums->lang['ib_code_buttons']}</td>
 </tr>
 <tr>
   <td class='pformleft'>
	   <input type='radio' name='bbmode' class='radiobutton' value='ezmode' onclick='setmode(this.value)' />&nbsp;<b>{$ibforums->lang['bbcode_guided']}</b><br />
	   <input type='radio' name='bbmode' class='radiobutton' value='normal' onclick='setmode(this.value)' checked="checked" />&nbsp;<b>{$ibforums->lang['bbcode_normal']}</b>
	   <script type='text/javascript' src='html/ibfcode.js'></script>
   </td>
   <td class='pformright'>
	   <input type='button' accesskey='b' value=' B '       onclick='simpletag("B")' class='codebuttons' name='B' style="font-weight:bold" onmouseover="hstat('bold')" />
	   <input type='button' accesskey='i' value=' I '       onclick='simpletag("I")' class='codebuttons' name='I' style="font-style:italic" onmouseover="hstat('italic')" />
	   <input type='button' accesskey='u' value=' U '       onclick='simpletag("U")' class='codebuttons' name='U' style="text-decoration:underline" onmouseover="hstat('under')" />
	   
	   <select name='ffont' class='codebuttons' onchange="alterfont(this.options[this.selectedIndex].value, 'FONT')"  onmouseover="hstat('font')">
	   <option value='0'>{$ibforums->lang['ct_font']}</option>
	   <option value='Arial' style='font-family:Arial'>{$ibforums->lang['ct_arial']}</option>
	   <option value='Times' style='font-family:Times'>{$ibforums->lang['ct_times']}</option>
	   <option value='Courier' style='font-family:Courier'>{$ibforums->lang['ct_courier']}</option>
	   <option value='Impact' style='font-family:Impact'>{$ibforums->lang['ct_impact']}</option>
	   <option value='Geneva' style='font-family:Geneva'>{$ibforums->lang['ct_geneva']}</option>
	   <option value='Optima' style='font-family:Optima'>Optima</option>
	   </select><select name='fsize' class='codebuttons' onchange="alterfont(this.options[this.selectedIndex].value, 'SIZE')" onmouseover="hstat('size')">
	   <option value='0'>{$ibforums->lang['ct_size']}</option>
	   <option value='1'>{$ibforums->lang['ct_sml']}</option>
	   <option value='7'>{$ibforums->lang['ct_lrg']}</option>
	   <option value='14'>{$ibforums->lang['ct_lest']}</option>
	   </select><select name='fcolor' class='codebuttons' onchange="alterfont(this.options[this.selectedIndex].value, 'COLOR')" onmouseover="hstat('color')">
	   <option value='0'>{$ibforums->lang['ct_color']}</option>
	   <option value='blue' style='color:blue'>{$ibforums->lang['ct_blue']}</option>
	   <option value='red' style='color:red'>{$ibforums->lang['ct_red']}</option>
	   <option value='purple' style='color:purple'>{$ibforums->lang['ct_purple']}</option>
	   <option value='orange' style='color:orange'>{$ibforums->lang['ct_orange']}</option>
	   <option value='yellow' style='color:yellow'>{$ibforums->lang['ct_yellow']}</option>
	   <option value='gray' style='color:gray'>{$ibforums->lang['ct_grey']}</option>
	   <option value='green' style='color:green'>{$ibforums->lang['ct_green']}</option>
	   </select>
	   &nbsp; <a href='javascript:closeall();' onmouseover="hstat('close')">{$ibforums->lang['js_close_all_tags']}</a>
	   <br />
	   <input type='button' accesskey='h' value=' http:// ' onclick='tag_url()'            class='codebuttons' name='url' onmouseover="hstat('url')" />
	   <input type='button' accesskey='g' value=' IMG '     onclick='tag_image()'          class='codebuttons' name='img' onmouseover="hstat('img')" />
	   <input type='button' accesskey='e' value='  @  '     onclick='tag_email()'          class='codebuttons' name='email' onmouseover="hstat('email')" />
	   <input type='button' accesskey='q' value=' QUOTE '   onclick='simpletag("QUOTE")'   class='codebuttons' name='QUOTE' onmouseover="hstat('quote')" />
	   <input type='button' accesskey='p' value=' CODE '    onclick='simpletag("CODE")'    class='codebuttons' name='CODE' onmouseover="hstat('code')" />
	   <input type='button' accesskey='l' value=' LIST '     onclick='tag_list()'          class='codebuttons' name="LIST" onmouseover="hstat('list')" />
	   <!--<input type='button' accesskey='l' value=' SQL '     onclick='simpletag("SQL")'     class='codebuttons' name='SQL'>
	   <input type='button' accesskey='t' value=' HTML '    onclick='simpletag("HTML")'    class='codebuttons' name='HTML'>-->
	   <br />
	   {$ibforums->lang['hb_open_tags']}:&nbsp;<input type='text' name='tagcount' size='3' maxlength='3' style='font-size:10px;font-family:verdana,arial;border:0px;font-weight:bold;' readonly="readonly" class='row1' value="0" />
	   &nbsp;<input type='text' name='helpbox' size='50' maxlength='120' style='width:auto;font-size:10px;font-family:verdana,arial;border:0px' readonly="readonly" class='row1' value="{$ibforums->lang['hb_start']}" />
	</td>
   </tr>
   <tr>
     <td colspan="2" class='pformstrip'>{$ibforums->lang['post']}</td>
   </tr>
   <tr>
     <td class='pformleft'>
	   <!--SMILIE TABLE-->
	   <br />
	   (<a href='javascript:CheckLength()'>{$ibforums->lang['check_length']}</a>)
     </td>
     <td class="pformright" valign='top'><textarea cols='70' rows='15' name='Post' tabindex='3' class='textinput'>$data</textarea></td>
   </tr>
EOF;
}


function postbox_buttons($data) {
global $ibforums;
return <<<EOF
 <tr>
   <td class='pformstrip' colspan="2">{$ibforums->lang['ib_code_buttons']}</td>
 </tr>
 <tr>
   <td class='pformleft'>
	   <input type='radio' class='radiobutton' name='bbmode' value='ezmode' onclick='setmode(this.value)' />&nbsp;<b>{$ibforums->lang['bbcode_guided']}</b><br />
	   <input type='radio' class='radiobutton' name='bbmode' value='normal' onclick='setmode(this.value)' checked="checked" />&nbsp;<b>{$ibforums->lang['bbcode_normal']}</b>
	   <script type='text/javascript' src='html/ibfcode.js'></script>
   </td>
   <td class='pformright'>
	   <input type='button' accesskey='b' value=' B '       onclick='simpletag("B")' class='codebuttons' name='B' style="font-weight:bold" onmouseover="hstat('bold')" />
	   <input type='button' accesskey='i' value=' I '       onclick='simpletag("I")' class='codebuttons' name='I' style="font-style:italic" onmouseover="hstat('italic')" />
	   <input type='button' accesskey='u' value=' U '       onclick='simpletag("U")' class='codebuttons' name='U' style="text-decoration:underline" onmouseover="hstat('under')" />
	   
	   <select name='ffont' class='codebuttons' onchange="alterfont(this.options[this.selectedIndex].value, 'FONT')"  onmouseover="hstat('font')">
	   <option value='0'>{$ibforums->lang['ct_font']}</option>
	   <option value='Arial' style='font-family:Arial'>{$ibforums->lang['ct_arial']}</option>
	   <option value='Times' style='font-family:Times'>{$ibforums->lang['ct_times']}</option>
	   <option value='Courier' style='font-family:Courier'>{$ibforums->lang['ct_courier']}</option>
	   <option value='Impact' style='font-family:Impact'>{$ibforums->lang['ct_impact']}</option>
	   <option value='Geneva' style='font-family:Geneva'>{$ibforums->lang['ct_geneva']}</option>
	   <option value='Optima' style='font-family:Optima'>Optima</option>
	   </select><select name='fsize' class='codebuttons' onchange="alterfont(this.options[this.selectedIndex].value, 'SIZE')" onmouseover="hstat('size')">
	   <option value='0'>{$ibforums->lang['ct_size']}</option>
	   <option value='1'>{$ibforums->lang['ct_sml']}</option>
	   <option value='7'>{$ibforums->lang['ct_lrg']}</option>
	   <option value='14'>{$ibforums->lang['ct_lest']}</option>
	   </select><select name='fcolor' class='codebuttons' onchange="alterfont(this.options[this.selectedIndex].value, 'COLOR')" onmouseover="hstat('color')">
	   <option value='0'>{$ibforums->lang['ct_color']}</option>
	   <option value='blue' style='color:blue'>{$ibforums->lang['ct_blue']}</option>
	   <option value='red' style='color:red'>{$ibforums->lang['ct_red']}</option>
	   <option value='purple' style='color:purple'>{$ibforums->lang['ct_purple']}</option>
	   <option value='orange' style='color:orange'>{$ibforums->lang['ct_orange']}</option>
	   <option value='yellow' style='color:yellow'>{$ibforums->lang['ct_yellow']}</option>
	   <option value='gray' style='color:gray'>{$ibforums->lang['ct_grey']}</option>
	   <option value='green' style='color:green'>{$ibforums->lang['ct_green']}</option>
	   </select>
	   &nbsp; <a href='javascript:closeall();' onmouseover="hstat('close')">{$ibforums->lang['js_close_all_tags']}</a>
	   <br />
	   <input type='button' accesskey='h' value=' http:// ' onclick='tag_url()'            class='codebuttons' name='url' onmouseover="hstat('url')" />
	   <input type='button' accesskey='g' value=' IMG '     onclick='tag_image()'          class='codebuttons' name='img' onmouseover="hstat('img')" />
	   <input type='button' accesskey='e' value='  @  '     onclick='tag_email()'          class='codebuttons' name='email' onmouseover="hstat('email')" />
	   <input type='button' accesskey='q' value=' QUOTE '   onclick='simpletag("QUOTE")'   class='codebuttons' name='QUOTE' onmouseover="hstat('quote')" />
	   <input type='button' accesskey='p' value=' CODE '    onclick='simpletag("CODE")'    class='codebuttons' name='CODE' onmouseover="hstat('code')" />
	   <input type='button' accesskey='l' value=' LIST '     onclick='tag_list()'          class='codebuttons' name="LIST" onmouseover="hstat('list')" />
	   <!--<input type='button' accesskey='l' value=' SQL '     onclick='simpletag("SQL")'     class='codebuttons' name='SQL'>
	   <input type='button' accesskey='t' value=' HTML '    onclick='simpletag("HTML")'    class='codebuttons' name='HTML'>-->
	   <br />
	   {$ibforums->lang['hb_open_tags']}:&nbsp;<input type='text' name='tagcount' size='3' maxlength='3' style='font-size:10px;font-family:verdana,arial;border:0px;font-weight:bold;' readonly="readonly" class='row1' value="0" />
	   &nbsp;<input type='text' name='helpbox' size='50' maxlength='120' style='width:auto;font-size:10px;font-family:verdana,arial;border:0px' readonly="readonly" class='row1' value="{$ibforums->lang['hb_start']}" />
	</td>
   </tr>
   <tr>
     <td colspan="2" class='pformstrip'>{$ibforums->lang['post']}</td>
   </tr>
   <tr>
     <td class='pformleft' align='center'>
	   <!--SMILIE TABLE-->
	   <br /><div class='desc'><strong><a href='javascript:CheckLength()'>{$ibforums->lang['check_length']}</a> &middot; <a href='javascript:bbc_pop()'>{$ibforums->lang['bbc_help']}</a></strong></div>
     </td>
     <td class="pformright" valign='top'>
     	<textarea cols='80' rows='20' name='Post' tabindex='3' class='textinput'>$data</textarea></td>
   </tr>
   <tr>
	<td class='pformleft'><b>{$ibforums->lang['po_options']}</b></td>
	<td class='pformright'>
	 <!--IBF.EMO-->
	 <!--IBF.SIG-->
	 <!--IBF.TRACK-->
	</td>
   </tr>
EOF;
}


function get_box_enableemo($checked) {
global $ibforums;
return <<<EOF
<input type='checkbox' name='enableemo' class='checkbox' value='yes' $checked />&nbsp;{$ibforums->lang['enable_emo']}
EOF;
}

function get_box_enablesig($checked) {
global $ibforums;
return <<<EOF
<br /><input type='checkbox' name='enablesig' class='checkbox' value='yes' $checked />&nbsp;{$ibforums->lang['enable_sig']}
EOF;
}

function get_box_enabletrack($checked) {
global $ibforums;
return <<<EOF
<br /><input type='checkbox' name='enabletrack' class='checkbox' value='1' $checked />&nbsp;{$ibforums->lang['enable_track']}
EOF;
}

function get_box_alreadytrack() {
global $ibforums;
return <<<EOF
<br />{$ibforums->lang['already_sub']}
EOF;
}


function get_javascript() {
global $ibforums;
return <<<EOF
<script language="javascript1.2" type="text/javascript">
<!--
var MessageMax  = "{$ibforums->lang['the_max_length']}";
var Override    = "{$ibforums->lang['override']}";
MessageMax      = parseInt(MessageMax);

if ( MessageMax < 0 )
{
	MessageMax = 0;
}
	
function emo_pop()
{
  window.open('index.{$ibforums->vars['php_ext']}?act=legends&CODE=emoticons&s={$ibforums->session_id}','Legends','width=250,height=500,resizable=yes,scrollbars=yes'); 
}
function bbc_pop()
{
  window.open('index.{$ibforums->vars['php_ext']}?act=legends&CODE=bbcode&s={$ibforums->session_id}','Legends','width=700,height=500,resizable=yes,scrollbars=yes'); 
}	
function CheckLength() {
	MessageLength  = document.REPLIER.Post.value.length;
	message  = "";
		if (MessageMax > 0) {
			message = "{$ibforums->lang['js_post']}: {$ibforums->lang['js_max_length']} " + MessageMax + " {$ibforums->lang['js_characters']}.";
		} else {
			message = "";
		}
		alert(message + "      {$ibforums->lang['js_used']} " + MessageLength + " {$ibforums->lang['js_characters']}.");
}
	
	function ValidateForm(isMsg) {
		MessageLength  = document.REPLIER.Post.value.length;
		errors = "";
		
		if (isMsg == 1)
		{
			if (document.REPLIER.msg_title.value.length < 2)
			{
				errors = "{$ibforums->lang['msg_no_title']}";
			}
		}
	
		if (MessageLength < 2) {
			 errors = "{$ibforums->lang['js_no_message']}";
		}
		if (MessageMax !=0) {
			if (MessageLength > MessageMax) {
				errors = "{$ibforums->lang['js_max_length']} " + MessageMax + " {$ibforums->lang['js_characters']}. {$ibforums->lang['js_current']}: " + MessageLength;
			}
		}
		if (errors != "" && Override == "") {
			alert(errors);
			return false;
		} else {
			document.REPLIER.submit.disabled = true;
			return true;
		}
	}
	
	// IBC Code stuff
	var text_enter_url      = "{$ibforums->lang['jscode_text_enter_url']}";
	var text_enter_url_name = "{$ibforums->lang['jscode_text_enter_url_name']}";
	var text_enter_image    = "{$ibforums->lang['jscode_text_enter_image']}";
	var text_enter_email    = "{$ibforums->lang['jscode_text_enter_email']}";
	var text_enter_flash    = "{$ibforums->lang['jscode_text_enter_flash']}";
	var text_code           = "{$ibforums->lang['jscode_text_code']}";
	var text_quote          = "{$ibforums->lang['jscode_text_quote']}";
	var error_no_url        = "{$ibforums->lang['jscode_error_no_url']}";
	var error_no_title      = "{$ibforums->lang['jscode_error_no_title']}";
	var error_no_email      = "{$ibforums->lang['jscode_error_no_email']}";
	var error_no_width      = "{$ibforums->lang['jscode_error_no_width']}";
	var error_no_height     = "{$ibforums->lang['jscode_error_no_height']}";
	var prompt_start        = "{$ibforums->lang['js_text_to_format']}";
	
	var help_bold           = "{$ibforums->lang['hb_bold']}";
	var help_italic         = "{$ibforums->lang['hb_italic']}";
	var help_under          = "{$ibforums->lang['hb_under']}";
	var help_font           = "{$ibforums->lang['hb_font']}";
	var help_size           = "{$ibforums->lang['hb_size']}";
	var help_color          = "{$ibforums->lang['hb_color']}";
	var help_close          = "{$ibforums->lang['hb_close']}";
	var help_url            = "{$ibforums->lang['hb_url']}";
	var help_img            = "{$ibforums->lang['hb_img']}";
	var help_email          = "{$ibforums->lang['hb_email']}";
	var help_quote          = "{$ibforums->lang['hb_quote']}";
	var help_list           = "{$ibforums->lang['hb_list']}";
	var help_code           = "{$ibforums->lang['hb_code']}";
	var help_click_close    = "{$ibforums->lang['hb_click_close']}";
	var list_prompt         = "{$ibforums->lang['js_tag_list']}";
	
	
	//-->
</script>


EOF;
}


function nameField_reg() {
global $ibforums;
return <<<EOF
<!-- REG NAME -->
EOF;
}


function mod_options($jump) {
global $ibforums;
return <<<EOF
  <tr>
   <td class='pformstrip' colspan="2">{$ibforums->lang['tt_options']}</td>
  </tr>
  <tr>
    <td class='pformleft'>{$ibforums->lang['mod_options']}</td>
    <td class='pformright'>$jump</select></td>
  </tr>

EOF;
}


function quote_box($data) {
global $ibforums;
return <<<EOF
<tr>
  <td colspan="2" class='pformstrip'>{$ibforums->lang['post_to_quote']}</td>
</tr>
<tr>
  <td class='pformleft'>{$ibforums->lang['post_to_quote_txt']}</td>
  <td class='pformright'><textarea cols='60' rows='12' wrap='soft' name='QPost' class='textinput'>{$data['post']}</textarea><input type='hidden' name='QAuthor' value='{$data['author_id']}' /><input type='hidden' name='QAuthorN' value='{$data['author_name']}' /><input type='hidden' name='QDate'   value='{$data['post_date']}' /></td>
</tr>

EOF;
}


function TopicSummary_top() {
global $ibforums;
return <<<EOF
<br />
<div class="tableborder">
  <div class="pformstrip">{$ibforums->lang['last_posts']}</div>
  <table cellpadding='6' cellspacing='1' border='0' width='100%'>
EOF;
}

function TopicSummary_body($data) {
global $ibforums;
return <<<EOF
  <tr>
    <td class='row4' valign='top' width='20%'><b>{$data['author']}</b></td>
    <td class='row4' valign='top' width='80%'>{$ibforums->lang['posted_on']} {$data['date']}</td>
  </tr>
  <tr>
    <td class='row1' valign='top' width='20%'>&nbsp;</td>
    <td class='row1' valign='top' width='80%'><span class='postcolor'>{$data['post']}</span></td>
  </tr>
EOF;
}


function TopicSummary_bottom() {
global $ibforums;
return <<<EOF

  </table>
  <div class="pformstrip"><a href="javascript:PopUp('index.{$ibforums->vars['php_ext']}?act=ST&amp;f={$ibforums->input['f']}&amp;t={$ibforums->input['t']}','TopicSummary',700,450,1,1)">{$ibforums->lang['review_topic']}</a></div>
</div>

EOF;
}



function preview($data) {
global $ibforums;
return <<<EOF
<div class="tableborder">
  <div class="pformstrip">{$ibforums->lang['post_preview']}</div>
  <div class="row1" style="padding:6px"><div class='postcolor'>$data</div></div>
</div>
<br />
EOF;
}





function edit_upload_field($data, $file_name="") {
global $ibforums;
return <<<EOF
<tr> 
          <td class="pformstrip" colspan="2">{$ibforums->lang['upload_title']}</td>
        </tr>
        <tr> 
          <td class='pformleft'>{$ibforums->lang['upload_text']} $data</td>
          <td class='pformright' width="100%">
           <table cellpadding='4' cellspacing='0' width='100%' border='0'>
            <tr>
             <td><input type='radio' name='editupload' value='keep' checked></td>
             <td width='100%'><b>{$ibforums->lang['eu_keep']}</b> ( $file_name )</td>
            </tr>
            <tr>
             <td><input type='radio' name='editupload' value='delete'></td>
             <td width='100%'><b>{$ibforums->lang['eu_delete']}</b></td>
            </tr>
            <tr>
             <td valign='middle'><input type='radio' name='editupload' value='new'></td>
             <td><b>{$ibforums->lang['eu_new']}</b><br /><input class='textinput' type='file' size='30' name='FILE_UPLOAD' onclick='document.REPLIER.editupload[2].checked=true;' /></td>
            </tr>
           </table>
          </td>
        </tr>
EOF;
}


function Upload_field($data) {
global $ibforums;
return <<<EOF
  <tr>
    <td colspan="2" class='pformstrip'>{$ibforums->lang['upload_title']}</td>
  </tr>
  <tr>
    <td class='pformleft'>{$ibforums->lang['upload_text']} $data</td>
    <td class='pformright'><input class='textinput' type='file' size='30' name='FILE_UPLOAD' /></td>
  </tr>
  
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




function EndForm($data) {
global $ibforums;
return <<<EOF
 <tr>
  <td class='pformstrip' align='center' style='text-align:center' colspan="2">
	<input type="submit" name="submit" value="$data" tabindex='4' class='forminput' accesskey='s' />&nbsp;
	<input type="submit" name="preview" value="{$ibforums->lang['button_preview']}" tabindex='5' class='forminput' />
  </td>
</tr>
</table>
</form>
<br />
<br clear="all" />
EOF;
}


function smilie_table() {
global $ibforums;
return <<<EOF
<table class='tablefill' cellpadding='4' align='center'>
<tr>
<td align="center" colspan="{$ibforums->vars['emo_per_row']}"><b>{$ibforums->lang['click_smilie']}</b></td>
</tr>
<!--THE SMILIES-->
<tr>
<td align="center" colspan="{$ibforums->vars['emo_per_row']}"><b><a href='javascript:emo_pop()'>{$ibforums->lang['all_emoticons']}</a></b></td>
</tr>
</table>
EOF;
}




function PostIcons() {
global $ibforums;
return <<<EOF
 <tr>
  <td class='pformleft'>{$ibforums->lang['post_icon']}</td>
  <td class='pformright'>
	<input type="radio" class="radiobutton" name="iconid" value="1" />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon1.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="2"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon2.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="3"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon3.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="4"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon4.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="5"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon5.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="6"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon6.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="7"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon7.gif"  align='middle' alt='' /><br />
	<input type="radio" class="radiobutton" name="iconid" value="8" />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon8.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="9"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon9.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="10"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon10.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="11"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon11.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="12"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon12.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="13"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon13.gif"  align='middle' alt='' />&nbsp;&nbsp;&nbsp;<input type="radio" class="radiobutton" name="iconid" value="14"  />&nbsp;&nbsp;<img src="{$ibforums->vars['img_url']}/icon14.gif"  align='middle' alt='' /><br />
    <input type="radio" class="radiobutton" name="iconid" value="0" checked="checked" />&nbsp;&nbsp;[ Use None ]
  </td>
 </tr>
EOF;
}


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
	//-->
</script>

<table class='tableborder' cellpadding="0" cellspacing="0" width="100%">
<tr>
 <td class='maintitle' colspan="2">&nbsp;&nbsp;$data</td>
</tr>
      
EOF;
}




function table_structure() {
global $ibforums;
return <<<EOF
<!--FORUM RULES--><br />
<!--START TABLE-->
<!--NAME FIELDS-->
<!--TOPIC TITLE-->
<!--POLL BOX-->
<!--POST BOX-->
<!--QUOTE BOX-->
<!--POST ICONS-->
<!--UPLOAD FIELD-->
<!--MOD OPTIONS-->
<!--END TABLE-->
EOF;
}


function add_edit_box($checked="") {
global $ibforums;
return <<<EOF
<tr>
  <td class='pformleft'><b>{$ibforums->lang['edit_ops']}</b></td>
  <td class='pformright'><input type='checkbox' name='add_edit' value='1' $checked class='forminput' />&nbsp;{$ibforums->lang['append_edit']}</td>
</tr>
EOF;
}


function topictitle_fields($data) {
global $ibforums;
return <<<EOF
<tr>
 <td colspan="2" class='pformstrip'>{$ibforums->lang['tt_topic_settings']}</td>
</tr>
<tr>
  <td class='pformleft'>{$ibforums->lang['topic_title']}</td>
  <td class='pformright'><input type='text' size='40' maxlength='50' name='TopicTitle' value='{$data[TITLE]}' tabindex='1' class='forminput' /></td>
</tr>
<tr>
   <td class='pformleft'>{$ibforums->lang['topic_desc']}</td>
   <td class='pformright'><input type='text' size='40' maxlength='40' name='TopicDesc' value='{$data[DESC]}' tabindex='2' class='forminput' /></td>
</tr>
EOF;
}


}
?>