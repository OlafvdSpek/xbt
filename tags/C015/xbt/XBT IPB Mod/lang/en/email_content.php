<?php

//----- SET UP CUSTOM HEADERS AND FOOTERS HERE --//

$EMAIL['header'] = "";

$EMAIL['footer'] = <<<EOF

Regards,

The <#BOARD_NAME#> team.
<#BOARD_ADDRESS#>

EOF;

$SUBJECT['new_topic_queue_notify'] = 'New Topic Awaiting Approval';
$EMAIL['new_topic_queue_notify'] = <<<EOF
Hello!

This email has been sent from: <#BOARD_NAME#>.

A new topic has been entered into the moderation queue and is awaiting
approval.

----------------------------------
Topic: <#TOPIC#>
Forum: <#FORUM#>
Author: <#POSTER#>
Time: <#DATE#>
Manage Queue: <#LINK#>
----------------------------------

If you no longer require notification, you can stop these emails by simply
removing your email address from the forum settings options.

<#BOARD_ADDRESS#>


EOF;



$SUBJECT['pm_notify'] = 'You have a new personal message';
$EMAIL['pm_notify'] = <<<EOF
<#NAME#>,

<#POSTER#> has sent you a new personal message entitled "<#TITLE#>".

You can read this personal message by following the link below:

<#BOARD_ADDRESS#><#LINK#>


EOF;



$EMAIL['send_text']	= <<<EOF
I thought you might be interested in reading this web page: <#THE LINK#>

From,

<#USER NAME#>

EOF;


$EMAIL['report_post'] = <<<EOF

<#MOD_NAME#>,

You have been sent this email from <#USERNAME#> via the "Report this post to a moderator" link.

------------------------------------------------
Topic: <#TOPIC#>
------------------------------------------------
Link to post: <#LINK_TO_POST#>
------------------------------------------------
Report:

<#REPORT#>

------------------------------------------------

EOF;



$EMAIL['pm_archive'] = <<<EOF

<#NAME#>,
This email has been sent from <#BOARD_ADDRESS#>.

Your archived messages have been compiled into a single
file and has been attached to this message.

EOF;

$EMAIL['reg_validate'] = <<<EOF

<#NAME#>,
This email has been sent from <#BOARD_ADDRESS#>.

You have received this email because this email address
was used during registration for our forums.
If you did not register at our forums, please disregard this
email. You do not need to unsubscribe or take any further action.

------------------------------------------------
Activation Instructions
------------------------------------------------

Thank you for registering.
We require that you "validate" your registration to ensure that
the email address you entered was correct. This protects against
unwanted spam and malicious abuse.

To activate your account, simply click on the following link:

<#THE_LINK#>

(AOL Email users may need to cut and paste the link into your web
browser).

------------------------------------------------
Not working?
------------------------------------------------

If you could not validate your registration by clicking on the link, please
visit this page:

<#MAN_LINK#>

It will ask you for a user id number, and your validation key. These are shown
below:

User ID: <#ID#>

Validation Key: <#CODE#>

Please cut and paste, or type those numbers into the corresponding fields in the form.

If you still cannot validate your account, it's possible that the account has been removed.
If this is the case, please contact an administrator to rectify the problem.

Thank you for registering and enjoy your stay!

EOF;

$EMAIL['admin_newuser'] = <<<EOF

Hello Mr. Admin Sir!

You have received this email because a new user has registered!

<#MEMBER_NAME#> completed their registration on <#DATE#>

You can turn off user notification in the Admin Control Panel

Have a super day!

EOF;

$EMAIL['lost_pass'] = <<<EOF

<#NAME#>,
This email has been sent from <#BOARD_ADDRESS#>.

You have received this email because a user account password recovery
was instigated by you on <#BOARD_NAME#>.

------------------------------------------------
IMPORTANT!
------------------------------------------------

If you did not request this password change, please IGNORE and DELETE this
email immediately. Only continue if you wish your password to be reset!

------------------------------------------------
Activation Instructions Below
------------------------------------------------

We require that you "validate" your password recovery to ensure that
you instigated this action. This protects against
unwanted spam and malicious abuse.

Simply click on the link below and complete the rest of the form

<#THE_LINK#>

(AOL Email users may need to cut and paste the link into your web
browser).

------------------------------------------------
Not working?
------------------------------------------------

If you could not validate your registration by clicking on the link, please
visit this page:

<#MAN_LINK#>

It will ask you for a user id number, and your validation key. These are shown
below:

User ID: <#ID#>

Validation Key: <#CODE#>

Please cut and paste, or type those numbers into the corresponding fields in the form.

------------------------------------------------
Is this not working?
------------------------------------------------

If you cannot re-activate your account, it's possible that the account has been removed or you
are in the process of another activation, such as registering or changing your registered email address.
If this is the case, then please complete the previous activation.
If the error persists, please contact an administrator to rectify the problem.

IP address of sender: <#IP_ADDRESS#>


EOF;

$EMAIL['newemail'] = <<<EOF

<#NAME#>,
This email has been sent from <#BOARD_ADDRESS#>.

You have received this email because you requested an
email address change.

------------------------------------------------
Activation Instructions Below
------------------------------------------------

We require that you "validate" your email address change to ensure that
you instigated this action. This protects against
unwanted spam and malicious abuse.

To activate your account, simply click on the following link:

<#THE_LINK#>

(AOL Email users may need to cut and paste the link into your web
browser).

------------------------------------------------
Not working?
------------------------------------------------

If you could not validate your registration by clicking on the link, please
visit this page:

<#MAN_LINK#>

It will ask you for a user id number, and your validation key. These are shown
below:

User ID: <#ID#>

Validation Key: <#CODE#>

Please cut and paste, or type those numbers into the corresponding fields in the form.

Once the activation is complete, you may need to log back in to update your member group
permissions.

------------------------------------------------
Help! I get an error!
------------------------------------------------

If you cannot re-activate your account, it's possible that the account has been removed or you
are in the process of another activation, such as registering or changing your registered email address.
If this is the case, then please complete the previous activation.
If the error persists, please contact an administrator to rectify the problem.


EOF;

$EMAIL['forward_page'] = <<<EOF

<#TO_NAME#>


<#THE_MESSAGE#>

---------------------------------------------------
Please note that <#BOARD_NAME#> has no control over the
contents of this message.
---------------------------------------------------

EOF;

$SUBJECT['subs_with_post'] = 'Topic Subscription Reply Notification';

$EMAIL['subs_with_post'] = <<<EOF
<#NAME#>,

<#POSTER#> has just posted a reply to a topic that you have subscribed to titled "<#TITLE#>".

----------------------------------------------------------------------
<#POST#>
----------------------------------------------------------------------

The topic can be found here:
<#BOARD_ADDRESS#>?showtopic=<#TOPIC_ID#>&view=getnewpost



There may be more replies to this topic, but only 1 email is sent per board visit for each subscribed topic. This is
to limit the amount of mail that is sent to your inbox.

Unsubscribing:
--------------

You can unsubscribe at any time by logging into your control panel and clicking on the "View Subscriptions" link.

EOF;

$SUBJECT['subs_new_topic'] = 'Forum Subscription New Topic Notification';
$EMAIL['subs_new_topic'] = <<<EOF
<#NAME#>,

<#POSTER#> has just posted a new topic entitled "<#TITLE#>" in forum "<#FORUM#>".

The topic can be found here:
<#BOARD_ADDRESS#>?showtopic=<#TOPIC_ID#>

Please note that if you wish to get email notification of any replies to this topic, you will have to click on the
"Track this Topic" link shown on the topic page, or by visiting the link below:
<#BOARD_ADDRESS#>?act=Track&f=<#FORUM_ID#>&t=<#TOPIC_ID#>


Unsubscribing:
--------------

You can unsubscribe at any time by logging into your control panel and clicking on the "View Subscriptions" link.

EOF;

$SUBJECT['subs_no_post'] = 'Topic Subscription Reply Notification';
$EMAIL['subs_no_post'] = <<<EOF
<#NAME#>,

<#POSTER#> has just posted a reply to a topic that you have subscribed to titled "<#TITLE#>".

The topic can be found here:
<#BOARD_ADDRESS#>?showtopic=<#TOPIC_ID#>&view=getnewpost

There may be more replies to this topic, but only 1 email is sent per board visit for each subscribed topic. This is
to limit the amount of mail that is sent to your inbox.

Unsubscribing:
--------------

You can unsubscribe at any time by logging into your control panel and clicking on the "View Subscriptions" link.

EOF;



$EMAIL['email_member'] = <<<EOF
<#MEMBER_NAME#>,

<#FROM_NAME#> has sent you this email from <#BOARD_ADDRESS#>.


<#MESSAGE#>

---------------------------------------------------
Please note that <#BOARD_NAME#> has no control over the
contents of this message.
---------------------------------------------------


EOF;

$EMAIL['complete_reg'] = <<<EOF

Success!

An administrator has accepted your registration request or email address change at <#BOARD_NAME#>. You may now log in with
your chosen details and access your full user account at <#BOARD_ADDRESS#>

EOF;


?>