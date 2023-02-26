<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */
if (!defined('PARENT_LOADED')) {
    exit;
}
if (!$_SESSION['isAuthenticated']) {
    exit;
}

require_once 'classes/captcha/class.captcha.php';

$i = count($SIDEBAR);
$SIDEBAR[$i] = "<h1>Functionality</h1>\n";
if ($_SESSION['config'][ENDUSER_ARCHIVE] != 'no') {
    $SIDEBAR[$i] .= '<div>';
    $SIDEBAR[$i] .= '	<img src="./images/icon-checkmark.gif" width="20" height="20" alt="Archives Online" title="Archives Online" style="vertical-align: middle" /> <a href="'.$_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_ARCHIVE_FILENAME]."\" style=\"vertical-align: middle\" target=\"_blank\">Archives Online</a>\n";
    $SIDEBAR[$i] .= "</div>\n";
    $SIDEBAR[$i] .= '<div>';
    $SIDEBAR[$i] .= '	<img src="./images/icon-checkmark.gif" width="20" height="20" alt="RSS Feed Online" title="RSS Feed Online" style="vertical-align: middle" /> <a href="'.$_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_ARCHIVE_FILENAME]."?view=feed\" style=\"vertical-align: middle\" target=\"_blank\">RSS Feed Online</a>\n";
    $SIDEBAR[$i] .= "</div>\n";
} else {
    $SIDEBAR[$i] .= '<div>';
    $SIDEBAR[$i] .= "	<img src=\"./images/icon-cross.gif\" width=\"20\" height=\"20\" alt=\"Archives Offline\" title=\"Archives Offline\" style=\"vertical-align: middle\" /> <span style=\"vertical-align: middle\">Archives Offline</span>\n";
    $SIDEBAR[$i] .= "</div>\n";
    $SIDEBAR[$i] .= '<div>';
    $SIDEBAR[$i] .= "	<img src=\"./images/icon-cross.gif\" width=\"20\" height=\"20\" alt=\"RSS Feed Offline\" title=\"RSS Feed Offline\" style=\"vertical-align: middle\" /> <span style=\"vertical-align: middle\">RSS Feed Offline</span>\n";
    $SIDEBAR[$i] .= "</div>\n";
}

if ($_SESSION['config'][ENDUSER_PROFILE] != 'no') {
    $SIDEBAR[$i] .= '<div>';
    $SIDEBAR[$i] .= '	<img src="./images/icon-checkmark.gif" width="20" height="20" alt="Profiles Online" title="Profiles Online" style="vertical-align: middle" /> <a href="'.$_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_PROFILE_FILENAME]."\" style=\"vertical-align: middle\" target=\"_blank\">Profiles Online</a>\n";
    $SIDEBAR[$i] .= "</div>\n";
} else {
    $SIDEBAR[$i] .= '<div>';
    $SIDEBAR[$i] .= "	<img src=\"./images/icon-cross.gif\" width=\"20\" height=\"20\" alt=\"Profiles Offline\" title=\"Profiles Offline\" style=\"vertical-align: middle\" /> <span style=\"vertical-align: middle\">Profiles Offline</span>\n";
    $SIDEBAR[$i] .= "</div>\n";
}

if ($_SESSION['config'][ENDUSER_FORWARD] != 'no') {
    $SIDEBAR[$i] .= '<div>';
    $SIDEBAR[$i] .= '	<img src="./images/icon-checkmark.gif" width="20" height="20" alt="Forward to Friends Online" title="Forward to Friends Online" style="vertical-align: middle" /> <a href="'.$_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_FORWARD_FILENAME]."\" style=\"vertical-align: middle\" target=\"_blank\">Forwards Online</a>\n";
    $SIDEBAR[$i] .= "</div>\n";
} else {
    $SIDEBAR[$i] .= '<div>';
    $SIDEBAR[$i] .= "	<img src=\"./images/icon-cross.gif\" width=\"20\" height=\"20\" alt=\"Forward to Friends Offline\" title=\"Forward to Friends Offline\" style=\"vertical-align: middle\" /> <span style=\"vertical-align: middle\">Forwards Offline</span>\n";
    $SIDEBAR[$i] .= "</div>\n";
}

$SIDEBAR[$i] .= '<div class="small-grey" style="margin-top: 10px">';
$SIDEBAR[$i] .= "If you would like to enable or disable any of this functionality, please visit the <a href=\"./index.php?section=preferences&type=enduser\" style=\"font-weight: normal; font-size: 11px\">End-User Preferences</a>.\n";
$SIDEBAR[$i] .= "</div>\n";

$ONLOAD[] = "\$('#tab-pane-example-one').tabs()";

$captcha_code = generate_captcha_html();

$examples = [];
$examples['two']['desc'] = 'Basic Subscribe / Unsubscribe without name fields and a static group.';
$examples['two']['html'] = <<<EXAMPLE
    <!-- Start of example two. (copy from here) -->
    <form action="%URL%" method="post">
    <input type="hidden" name="group_ids[]" value="ENTER_GROUP_ID_HERE" />
    <table cellspacing="1" cellpadding="1" border="0">
    <tbody>
    	<tr>
    		<td><label for="email_address">E-mail Address:</label>&nbsp;&nbsp;</td>
    		<td><input type="text" id="email_address" name="email_address" value="" /></td>
    	</tr>
    	%CAPTCHA_CODE%
    	<tr>
    		<td><label for="action_subscribe">Subscribe:</label>&nbsp;<input type="radio" name="action" id="action_subscribe" value="subscribe" checked="checked" /></td>
    		<td><label for="action_unsubscribe">UnSubscribe:</label>&nbsp;<input type="radio" name="action" id="action_unsubscribe" value="unsubscribe" /></td>
    	</tr>
    	<tr>
    		<td colspan="2" style="text-align: right">
    			<input type="submit" name="submit" value="Proceed" />
    		</td>
    	</tr>
    </tbody>
    </table>
    </form>
    <!-- End of example two. (to here) -->
    EXAMPLE;

$examples['three']['desc'] = 'Basic Subscribe / Unsubscribe with the name and select box.';
$examples['three']['html'] = <<<EXAMPLE
    <!-- Start of example three. (copy from here) -->
    <form action="%URL%" method="post">
    <table cellspacing="1" cellpadding="1" border="0">
    <tbody>
    	<tr>
    		<td><label for="email_address">E-mail Address:</label>&nbsp;&nbsp;</td>
    		<td><input type="text" id="email_address" name="email_address" value="" /></td>
    	</tr>
    	<tr>
    		<td><label for="firstname">Firstname:</label>&nbsp;&nbsp;</td>
    		<td><input type="text" id="firstname" name="firstname" value="" /></td>
    	</tr>
    	<tr>
    		<td><label for="lastname">Lastname:</label>&nbsp;&nbsp;</td>
    		<td><input type="text" id="lastname" name="lastname" value="" /></td>
    	</tr>
    	<tr>
    		<td><label for="group_ids">Group:</label>&nbsp;</td>
    		<td>
    			<select id="group_ids" name="group_ids[]">
    			<option value="ENTER_GROUP_ID_HERE">GroupName 1</option>
    			<option value="ENTER_GROUP_ID_HERE">GroupName 2</option>
    			<option value="ENTER_GROUP_ID_HERE">GroupName 3</option>
    			</select>
    		</td>
    	</tr>
    	%CAPTCHA_CODE%
    	<tr>
    		<td>Subscribe:&nbsp;<input type="radio" name="action" value="subscribe" checked="checked" /></td>
    		<td>UnSubscribe:&nbsp;<input type="radio" name="action" value="unsubscribe" /></td>
    	</tr>
    	<tr>
    		<td colspan="2" style="text-align: right">
    			<input type="submit" name="submit" value="Proceed" />
    		</td>
    	</tr>
    </tbody>
    </table>
    </form>
    <!-- End of example three. (to here) -->
    EXAMPLE;

$examples['four']['desc'] = 'Subscribe / Unsubscribe with name and multiple select-box.';
$examples['four']['html'] = <<<EXAMPLE
    <!-- Start of example four. (copy from here) -->
    <form action="%URL%" method="post">
    <table cellspacing="1" cellpadding="1" border="0">
    <tbody>
    	<tr>
    		<td><label for="firstname">Firstname:</label>&nbsp;&nbsp;</td>
    		<td><input type="text" id="firstname" name="firstname" value="" /></td>
    	</tr>
    	<tr>
    		<td><label for="lastname">Lastname:</label>&nbsp;&nbsp;</td>
    		<td><input type="text" id="lastname" name="lastname" value="" /></td>
    	</tr>
    	<tr>
    		<td><label for="email_address">E-mail Address:</label>&nbsp;&nbsp;</td>
    		<td><input type="text" id="email_address" name="email_address" value="" /></td>
    	</tr>
    	<tr>
    		<td style="vertical-align: top"><label for="group_ids">Select Groups:</label><br /><small>(CTRL + click for multiple)</small>&nbsp;</td>
    		<td>
    			<select id="group_ids" name="group_ids[]" multiple="multiple" size="5">
    			<option value="ENTER_GROUP_ID_HERE">GroupName 1</option>
    			<option value="ENTER_GROUP_ID_HERE">GroupName 2</option>
    			<option value="ENTER_GROUP_ID_HERE">GroupName 3</option>
    			</select>
    		</td>
    	</tr>
    	%CAPTCHA_CODE%
    	<tr>
    		<td><label for="action">Action:</label>&nbsp;</td>
    		<td>
    			<select id="action" name="action">
    			<option value="subscribe">Subscribe</option>
    			<option value="unsubscribe">Unsubscribe</option>
    			</select>
    		</td>
    	</tr>
    	<tr>
    		<td colspan="2" style="text-align: right">
    			<input type="submit" name="submit" value="Proceed" />
    		</td>
    	</tr>
    </tbody>
    </table>
    </form>
    <!-- End of example four. (To here) -->
    EXAMPLE;

$examples['five']['desc'] = 'Subscribe / Unsubscribe with name and check-boxes.';
$examples['five']['html'] = <<<EXAMPLE
    <!-- Start of example five. (copy from here) -->
    <form action="%URL%" method="post">
    <table cellspacing="1" cellpadding="1" border="0">
    <tbody>
    	<tr>
    		<td><label for="email_address">E-mail Address:</label>&nbsp;&nbsp;</td>
    		<td><input type="text" id="email_address" name="email_address" value="" /></td>
    	</tr>
    	<tr>
    		<td><label for="firstname">Firstname:</label>&nbsp;&nbsp;</td>
    		<td><input type="text" id="firstname" name="firstname" value="" /></td>
    	</tr>
    	<tr>
    		<td><label for="lastname">Lastname:</label>&nbsp;&nbsp;</td>
    		<td><input type="text" id="lastname" name="lastname" value="" /></td>
    	</tr>
    	<tr>
    		<td style="vertical-align: top">Select Groups:</td>
    		<td>
    			<input type="checkbox" id="group_ids_1" name="group_ids[]" value="ENTER_GROUP_ID_HERE"> <label for="group_ids_1">GroupName 1</label><br />
    			<input type="checkbox" id="group_ids_2" name="group_ids[]" value="ENTER_GROUP_ID_HERE"> <label for="group_ids_2">GroupName 2</label><br />
    			<input type="checkbox" id="group_ids_3" name="group_ids[]" value="ENTER_GROUP_ID_HERE"> <label for="group_ids_3">GroupName 3</label><br />
    		</td>
    	</tr>
    	%CAPTCHA_CODE%
    	<tr>
    		<td><label for="action_subscribe">Subscribe:</label>&nbsp;<input type="radio" id="action_subscribe" name="action" value="subscribe" checked="checked" /></td>
    		<td><label for="action_unsubscribe">UnSubscribe:</label>&nbsp;<input type="radio" id="action_unsubscribe" name="action" value="unsubscribe" /></td>
    	</tr>
    	<tr>
    		<td colspan="2" style="text-align: right">
    			<input type="submit" name="submit" value="Proceed" />
    		</td>
    	</tr>
    </tbody>
    </table>
    </form>
    <!-- End of example five. (to here) -->
    EXAMPLE;

?>
<a name="top"></a>
<h1>End-User Tools Index</h1>
<ol>
	<li><a href="#introduction">Introduction</a></li>
	<li><a href="#examples">Subscribe / Unsubscribe Examples</a></li>
	<li>
		<a href="#filelist">Directory and File Listing</a>
		<ul>
			<li><a href="#dir-files">files</a></li>
			<li><a href="#dir-images">images</a></li>
			<li><a href="#dir-languages">languages</a></li>
			<li><a href="#file-archive">archive.php</a></li>
			<li><a href="#file-confirm">confirm.php</a></li>
			<li><a href="#file-help">help.php</a></li>
			<li><a href="#file-listmessenger">listmessenger.php</a></li>
			<li><a href="#file-profile">profile.php</a></li>
			<li><a href="#file-public_config">public_config.inc.php</a></li>
			<li><a href="#file-template">template.html</a></li>
			<li><a href="#file-unsubscribe">unsubscribe.php</a></li>
		</ul>
	</li>
</ol>
<h1>End-User Tools Information</h1>
<ol>
	<li style="padding-bottom: 10px">
		<h2><a name="introduction"></a>Introduction</h2>
		The ListMessenger end-user tools refer to the &quot;public&quot; scripts or the scripts that are accessed by the general public for either subscribing / unsubscribing to your mailing lists, confirming actions and accessing the mailing list archive of messages. By default all end-user scripts are placed in a directory called &quot;public&quot; which resides in the ListMessenger directory root. As of ListMessenger 2.0, this folder can easily be moved out of the ListMessenger directory, just make sure that you update your preferences accordingly.
	</li>
	<li style="padding-bottom: 10px">
		<h2><a name="examples"></a>Subscribe / Unsubscribe Examples</h2>
		Here are several examples of subscription forms that you can use to add subscribers to your mailing list from your website. Please keep in mind that these are examples and will require you to enter the corresponding group ID that you would like the subscribers added to. If you go to the Manage Groups sections within ListMessenger you will see that each group has Group ID displayed to the right of the group name.
		<br /><br />
		<?php
        ++$NOTICE;
$NOTICESTR[] = 'Please remember to replace <em>ENTER_GROUP_ID_HERE</em> with the actual &quot;Group ID&quot; of the group or groups you wish to allow your subscribers to subscribe to.';

echo display_notice($NOTICESTR);
?>
		<div style="border-bottom: 1px #CCCCCC solid">Example <strong>One</strong>: All custom fields included.</div>
		<br />
		<div id="tab-pane-example-one">
			<ul>
				<li><a href="#fragment-one-1"><span>Preview</span></a></li>
				<li><a href="#fragment-one-2"><span>Source Code</span></a></li>
			</ul>
			<div id="fragment-one-1">
				<?php echo generate_cfields($_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_FILENAME]); ?>
			</div>
			<div id="fragment-one-2">
				<form>
				<textarea name="html" style="width: 98%; height: 275px" wrap="off"><!-- Start of example one. (copy from here) -->
<?php echo generate_cfields($_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_FILENAME], 'html'); ?>
<!-- End of example one. (To here) -->
				</textarea>
				</form>
			</div>
		</div>
		<div style="text-align: right"><a href="#top" style="text-decoration: underline">top</a></div>
		<?php
foreach ($examples as $number => $example) {
    $ONLOAD[] = "\$('#tab-pane-example-".$number."').tabs()";

    echo "<br /><br />\n";
    echo '<div style="border-bottom: 1px #CCCCCC solid">Example <strong>'.ucwords($number).'</strong>: '.$example['desc']."</div>\n";
    echo "<br />\n";
    echo '<div id="tab-pane-example-'.$number."\">\n";
    echo "	<ul>\n";
    echo '		<li><a href="#fragment-'.$number."-1\"><span>Preview</span></a></li>\n";
    echo '		<li><a href="#fragment-'.$number."-2\"><span>Source Code</span></a></li>\n";
    echo "	</ul>\n";
    echo '	<div id="fragment-'.$number."-1\">\n";
    echo str_replace('%URL%', $_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_FILENAME], str_replace('%CAPTCHA_CODE%', ($_SESSION['config'][ENDUSER_CAPTCHA] == 'yes') ? $captcha_code : '', trim($example['html'])));
    echo "	</div>\n";
    echo '	<div id="fragment-'.$number."-2\">\n";
    echo "		<form>\n";
    echo "		<textarea name=\"html\" style=\"width: 98%; height: 200px\" wrap=\"off\">\n";
    echo html_encode(str_replace('%URL%', $_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_FILENAME], str_replace('%CAPTCHA_CODE%', ($_SESSION['config'][ENDUSER_CAPTCHA] == 'yes') ? $captcha_code : '', trim($example['html']))));
    echo "		</textarea>\n";
    echo "		</form>\n";
    echo "	</div>\n";
    echo "</div>\n";
    echo "<div style=\"text-align: right\"><a href=\"#top\" style=\"text-decoration: underline\">top</a></div>\n";
}
?>
	</li>
	<li style="padding-bottom: 10px">
		<h2><a name="filelist"></a>Directory and File Listing</h2>
		The following is a list of the directories and files that reside in your &quot;public&quot; directory. A brief description is also provided for your reference:
		<ul>
			<li style="padding-bottom: 5px">
				<a name="dir-files"></a><strong>files</strong><br />
				This directory is the location where all file attachments are stored. These attachments can be managed by clicking the <a href="index.php?section=attachments">Attachments</a> link within the Control Panel. Please note that this directory does need to be writable by your webserver so as PHP can move uploaded files to this directory.
			</li>
			<li style="padding-bottom: 5px">
				<a name="dir-images"></a><strong>images</strong><br />
				This directory is the location where all images are uploaded to through the rich text editor if you have it enabled and are using a supported web-browser. If you are using the rich text editor to upload images, this directory also must be writable by your webserver.
			</li>
			<li style="padding-bottom: 5px">
				<a name="dir-languages"></a><strong>languages</strong><br />
				This directory contains all of the language files for the end-user tools. If you would like to translate the end-user tools, simply copy the default english.lang.php file to yourlanguage.lang.php and make the changes within. When you are finished, place your new language file in this directory and select it from the drop down in the End-User Preferences within the Control Panel.
			</li>
			<li style="padding-bottom: 5px">
				<a name="file-archive"></a><strong>archive.php</strong><br />
				The Message Archive can be enabled by the administrator in the End-User Preferences within the Control Panel. The Message Archives allows visitors to view the messages that have successfully been sent using ListMessenger.
			</li>
			<li style="padding-bottom: 5px">
				<a name="file-confirm"></a><strong>confirm.php</strong><br />
				This file is used when a subscriber clicks the confirmation link in either the subscribe or unsubscribe confirmation e-mails.
			</li>
			<li style="padding-bottom: 5px">
				<a name="file-help"></a><strong>help.php</strong><br />
				This file is required as part of RFC2369 compliancy. It gives your subscribers some basic information about your mailing list, how they got on, how they can get off of it, etc.
			</li>
			<li style="padding-bottom: 5px">
				<a name="file-profile"></a><strong>profile.php</strong><br />
				This file is used by your subscribers to update their personal profile (name, e-mail address, etc) in your mailing list if you have enabled this option in the Control Panel.
			</li>
			<li style="padding-bottom: 5px">
				<a name="file-listmessenger"></a><strong>listmessenger.php</strong><br />
				This is the file that your subscription forms will submit to using either a GET or POST method. When submitting a form, it is important to not use both a GET and a POST, use either or. More information will be available on using the listmessenger.php file in the Webmasters Guide on our website.
			</li>
			<li style="padding-bottom: 5px">
				<a name="file-public_config"></a><strong>public_config.inc.php</strong><br />
				This is a simple configuration file which must be changed only if you move the &quot;public&quot; folder out of the ListMessenger program directory. Simply modify the $LM_PATH variable to the full directory path with the trailing slash, to your ListMessenger program directory.
			</li>
			<li style="padding-bottom: 5px">
				<a name="file-template"></a><strong>template.html</strong><br />
				This is the template file that provides the look and feel of every end-user page. Please feel free to modify the default HTML of this file and making it fit into your own websites design. The only requirement is that you enter &quot;[title]&quot; between the &lt;title&gt;&lt;/title&gt; tags, and put the &quot;[message]&quot; tag in your HTML body. This file, by default, is opened via URL so you can change it to a PHP file if you would like and PHP will parse it. This is only possible if you have allow_fopen_url enabled and your website is not behind a load balancer or misconfigured DNS server.
			</li>
			<li style="padding-bottom: 5px">
				<a name="file-unsubscribe"></a><strong>unsubscribe.php</strong><br />
				This is the file that subscribers can use to unsubscribe themselves from your mailing list or lists. It requires that variables are passed using either a GET or a POST method. More information will be available in the Webmasters Guide on our website.
			</li>
		</ul>
	</li>
</ol>