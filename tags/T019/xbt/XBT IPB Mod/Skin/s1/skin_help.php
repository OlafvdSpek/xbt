<?php

class skin_help {



function row($entry) {
global $ibforums;
return <<<EOF
<li class="helprow"><a href='{$ibforums->base_url}act=Help&amp;CODE=01&amp;HID={$entry['id']}'><b>{$entry['title']}</b></a><br />{$entry['description']}</li>

EOF;
}

function display($text) {
global $ibforums;
return <<<EOF
</div>
<div style='padding:4px'>$text</div>
</div>
EOF;
}

function end() {
global $ibforums;
return <<<EOF
  </ul>
 </div>
</div>
EOF;
}

function no_results() {
global $ibforums;
return <<<EOF
                <tr>
                   <td class='row1' colspan='2'><b>{$ibforums->lang['no_results']}</b></td>
                 </tr>
EOF;
}

function start($one_text, $two_text, $three_text) {
global $ibforums;
return <<<EOF
<div>$two_text</div>
<br />
<form action="{$ibforums->base_url}" method="post">
<input type='hidden' name='act' value='Help' />
<input type='hidden' name='CODE' value='02' />
<div class="tableborder">
  <div class="maintitle">$one_text</div>
  <div class="tablepad">{$ibforums->lang['search_txt']}&nbsp;&nbsp;<input type='text' maxlength='60' size='30' class='forminput' name='search_q' />&nbsp;<input type='submit' value='{$ibforums->lang['submit']}' class='forminput' /></div>
</div>
</form>
<br />
<div class="tableborder">
  <div class="maintitle">$three_text</div>
  <div class="tablepad">
  <ul id="help">
EOF;
}


}
?>