<?php

class skin_poll {

function poll_javascript($tid,$fid) {
global $ibforums;
return <<<EOF
<script type="text/javascript">
function go_gadget_show()
{
	window.location = "{$ibforums->base_url}&act=ST&f=$fid&t=$tid&mode=show&st={$ibforums->input['start']}";
}
function go_gadget_vote()
{
	window.location = "{$ibforums->base_url}&act=ST&f=$fid&t=$tid&st={$ibforums->input['start']}";
}
</script>
EOF;
}

function button_vote() {
global $ibforums;
return <<<EOF
<input type='submit' name='submit' value='{$ibforums->lang['poll_add_vote']}' title="{$ibforums->lang['tt_poll_vote']}" class='forminput' />
EOF;
}

function button_null_vote() {
global $ibforums;
return <<<EOF
<input type='submit' name='nullvote' value='{$ibforums->lang['poll_null_vote']}' title="{$ibforums->lang['tt_poll_null']}" class='forminput' />
EOF;
}

function button_show_results() {
global $ibforums;
return <<<EOF
<input type='button' value='{$ibforums->lang['pl_show_results']}' class='forminput' title="{$ibforums->lang['tt_poll_show']}" onclick='go_gadget_show()' />
EOF;
}

function button_show_voteable() {
global $ibforums;
return <<<EOF
<input type='button' name='viewresult' value='{$ibforums->lang['pl_show_vote']}'  title="{$ibforums->lang['tt_poll_svote']}" class='forminput' onclick='go_gadget_vote()' />
EOF;
}

function edit_link($tid, $fid, $key="") {
global $ibforums;
return <<<EOF
[ <a href="{$ibforums->base_url}act=Mod&amp;CODE=20&amp;f=$fid&amp;t=$tid&amp;auth_key=$key">{$ibforums->lang['ba_edit']}</a> ]
EOF;
}

function delete_link($tid, $fid, $key="") {
global $ibforums;
return <<<EOF
[ <a href="{$ibforums->base_url}act=Mod&amp;CODE=22&amp;f=$fid&amp;t=$tid&amp;auth_key=$key">{$ibforums->lang['ba_delete']}</a> ]
EOF;
}

function Render_row_form($votes, $id, $answer) {
global $ibforums;
return <<<EOF
    <tr>
    <td class='row1' colspan='3'><input type="radio" name="poll_vote" value="$id" class="radiobutton" />&nbsp;<strong>$answer</strong></td>
    </tr>
EOF;
}


function poll_header($tid, $poll_q, $edit, $delete) {
global $ibforums;
return <<<EOF
<!--IBF.POLL_JS-->
<form action="{$ibforums->base_url}act=Poll&amp;t=$tid" method="post">
<div align="right" class="pformstrip">$edit &nbsp; $delete</div>
<div class="tablepad" align="center">
<table cellpadding="5" align="center">
<tr>
 <td colspan='3' align='center'><b>$poll_q</b></td>
</tr>
EOF;
}

function ShowPoll_footer() {
global $ibforums;
return <<<EOF
</table>
</div>
<div align="center" class="pformstrip"><!--IBF.VOTE-->&nbsp;<!--IBF.SHOW--></div>
</form>
                
EOF;
}

function Render_row_results($votes, $id, $answer, $percentage, $width) {
global $ibforums;
return <<<EOF
    <tr>
    <td class='row1'>$answer</td>
    <td class='row1'> [ <b>$votes</b> ] </td>
    <td class='row1' align='left'><img src='{$ibforums->vars['img_url']}/bar_left.gif' border='0' width='4' height='11' align='middle' alt='' /><img src='{$ibforums->vars['img_url']}/bar.gif' border='0' width='$width' height='11' align='middle' alt='' /><img src='{$ibforums->vars['img_url']}/bar_right.gif' border='0' width='4' height='11' align='middle' alt='' />&nbsp;[$percentage%]</td>
    </tr>
EOF;
}

function show_total_votes($total_votes) {
global $ibforums;
return <<<EOF
    <tr>
    <td class='row1' colspan='3' align='center'><strong>{$ibforums->lang['pv_total_votes']}: $total_votes</strong></td>
    </tr>
EOF;
}


}
?>