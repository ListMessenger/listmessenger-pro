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

if (!empty($_GET['type'])) {
    $TYPE = clean_input($_GET['type'], 'alphanumeric');
} else {
    $TYPE = false;
}

switch ($TYPE) {
    case 'program' :
        if ((!empty($_POST['npassword1'])) || (!empty($_POST['npassword2'])) || ((!empty($_POST['preferences'])) && is_array($_POST['preferences']) && count($_POST['preferences']))) {
            if (trim($_POST['npassword1']) != '') {
                if (trim($_POST['npassword2']) != '') {
                    if (trim($_POST['npassword1']) == trim($_POST['npassword2'])) {
                        if (strlen(trim($_POST['npassword1'])) > 5) {
                            $_POST['preferences'][PREF_ADMPASS_ID] = md5(trim($_POST['npassword1']));
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'Your new password must be at least five (6) characters long in order to be used. Please re-enter your new password.';
                        }
                    } else {
                        ++$ERROR;
                        $ERRORSTR[] = 'The new passwords that you have entered do not match. Please re-enter your new password.';
                    }
                } else {
                    ++$ERROR;
                    $ERRORSTR[] = 'If you are trying to enter a new password, please re-enter your new password in the &quot;Retype New Password&quot; text box.';
                }
            }

            if (!$ERROR) {
                foreach ($_POST['preferences'] as $preference_id => $preference_value) {
                    $preference_value = trim($preference_value);
                    $skip_query = false;

                    switch ($preference_id) {
                        case PREF_ADMUSER_ID:
                            if (strlen($preference_value) < 5) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The ListMessenger Username that you have entered did not exceed 5 characters. Please enter a username that exceeds 5 characters and try again.';
                                $skip_query = true;
                            }
                            break;
                        case PREF_FRMNAME_ID:
                            if (strlen($preference_value) < 1) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The Default From Name is a required setting, please enter the name you would like your messages to be sent from to update this preference.';
                                $skip_query = true;
                            }
                            break;
                        case PREF_FRMEMAL_ID:
                            if (!valid_address($preference_value)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The Default From E-Mail Address you have entered does not appear to be valid. Please enter a valid From E-Mail Address to update this preference.';
                                $skip_query = true;
                            }
                            break;
                        case PREF_RPYEMAL_ID:
                            if (!valid_address($preference_value)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The Default Reply E-Mail Address you have entered does not appear to be valid. Please enter a valid Reply E-Mail Address to update this preference.';
                                $skip_query = true;
                            }
                            break;
                        case PREF_ABUEMAL_ID:
                            if (!valid_address($preference_value)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The Abuse E-Mail Address you have entered does not appear to be valid. Please enter a valid Abuse E-Mail Address to update this preference.';
                                $skip_query = true;
                            }
                            break;
                        case PREF_ERREMAL_ID:
                            if (!valid_address($preference_value)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The Bounce-To E-Mail Address you have entered does not appear to be valid. Please enter a valid Bounces Sent To E-Mail Address to update this preference.';
                                $skip_query = true;
                            }
                            break;
                        case PREF_ADMEMAL_ID:
                            if (!valid_address($preference_value)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The Administrator E-Mail Address you have entered does not appear to be valid. Please enter a valid Notices Sent To E-Mail Address to update this preference.';
                                $skip_query = true;
                            }
                            break;
                        case PREF_PROPATH_ID:
                            if (!is_dir($preference_value)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The ListMessenger Directory Path you have entered does not exist or is unreadable by PHP. Please enter the full directory path from root, to your ListMessenger directory to update this preference.';
                                $skip_query = true;
                            }
                            if (substr($preference_value, -1) != '/') {
                                $preference_value .= '/';
                            }
                            break;
                        case PREF_PUBLIC_PATH:
                            if (!is_dir($preference_value)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The Public Folder Directory Path you have entered does not exist or is unreadable by PHP. Please enter the full directory path from root, to the public folder directory to update this preference.';
                                $skip_query = true;
                            }
                            if (substr($preference_value, -1) != '/') {
                                $preference_value .= '/';
                            }
                            break;
                        case PREF_PRIVATE_PATH:
                            if (!is_dir($preference_value)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The Private Folder Directory Path you have entered does not exist or is unreadable by PHP. Please enter the full directory path from root, to the private directory to update this preference.';
                                $skip_query = true;
                            }
                            if (substr($preference_value, -1) != '/') {
                                $preference_value .= '/';
                            }
                            break;
                        case PREF_PROGURL_ID:
                            if (strlen($preference_value) < 1) {
                                ++$ERROR;
                                $ERRORSTR[] = 'You have failed to enter the ListMessenger Program URL. Please enter the valid URL to your ListMessenger directory to update this preference.';
                                $skip_query = true;
                            }
                            if (substr($preference_value, -1) != '/') {
                                $preference_value .= '/';
                            }
                            break;
                        case PREF_PUBLIC_URL:
                            if (strlen($preference_value) < 1) {
                                ++$ERROR;
                                $ERRORSTR[] = 'You have failed to enter the ListMessenger Public Folder URL. Please enter the valid URL to your Public Folder directory to update this preference.';
                                $skip_query = true;
                            }
                            if (substr($preference_value, -1) != '/') {
                                $preference_value .= '/';
                            }
                            break;
                        case PREF_DEFAULT_CHARSET:
                            if (strlen($preference_value) < 1) {
                                ++$ERROR;
                                $ERRORSTR[] = 'You must select a Character Encoding in order for ListMessenger to function properly. The default encoding type is ISO-8859-1.';
                                $skip_query = true;
                            } else {
                                if ($preference_value != $_SESSION['config'][PREF_DEFAULT_CHARSET]) {
                                    ++$NOTICE;
                                    $NOTICESTR[] = "You have changed ListMessenger's default character set, please do not forget that you must manually change the character set in your public/template file as well or the text from your language file may not be displayed correctly when viewing the template in your web-browser.";
                                }
                            }
                            break;
                        case PREF_ENCODING_STYLE:
                            if ($preference_value != 'htmlspecialchars') {
                                $preference_value = 'htmlentities';
                            }
                            break;
                        case PREF_DATEFORMAT:
                            if (strlen($preference_value) < 1) {
                                ++$ERROR;
                                $ERRORSTR[] = "You must enter a valid date format that you would like ListMessenger to use to display the date and time. If you would like to change the default date format &quot;M jS Y g:ia&quot; to something else, please make sure you read up on <a href=\"http://www.php.net/date\" target=\"_blank\">PHP's date() function</a>.";
                                $skip_query = true;
                            }
                            break;
                        case PREF_PERPAGE_ID:
                            if (!(int) $preference_value) {
                                ++$ERROR;
                                $ERRORSTR[] = 'Please enter a valid integer for &quot;Display Rows Per Page&quot; to update this setting.';
                                $skip_query = true;
                            }
                            break;
                        case PREF_USERTE:
                            switch ($preference_value) {
                                case 'disabled':
                                case 'no':
                                    $preference_value = 'disabled';
                                    break;
                                case 'ckeditor':
                                default:
                                    $preference_value = 'ckeditor';
                                    break;
                            }

                            if ($preference_value != 'disabled') {
                                if (!is_dir($_SESSION['config'][PREF_PROPATH_ID].'javascript/wysiwyg/ckeditor')) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'The &quot;Rich Text Editor&quot; that you have selected (<strong>'.$preference_value.'</strong>) does not appear to exist in <em>'.$_SESSION['config'][PREF_PROPATH_ID].'javascript/wysiwyg</em>. Please make sure that this editor directory exists in th <em>wysiwyg</em> directory, and try again.<br /><br />Alternative rich text (WYSIWYG) editors can be downloaded from the <a href="https://listmessenger.com" target="_blank">ListMessenger website</a>.';
                                    $skip_query = true;
                                }
                            }
                            break;
                        case PREF_ERROR_LOGGING:
                            if ($preference_value != 'yes') {
                                $preference_value = 'no';
                            }
                            break;
                        case PREF_TIMEZONE:
                            if (($preference_value < -12) || ($preference_value > 12)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'Please choose a timezone offset between -12 and 12 hours from GMT.';
                                $skip_query = true;
                            }
                            break;
                        case PREF_DAYLIGHT_SAVINGS:
                            if ($preference_value != 'yes') {
                                $preference_value = 'no';
                            }
                            break;
                        case PREF_ADMPASS_ID:
                            // Already done this error checking above.
                            break;
                        default:
                            $ERROR++;
                            $ERRORSTR[] = 'Unrecognized preference ID ['.$preference_id.'] with a value of ['.$preference_value.'] was passed to preferences updater.';
                            $skip_query = true;
                            break;
                    }

                    // Only change modified preferences.
                    if ($_SESSION['config'][$preference_id] == $preference_value) {
                        $skip_query = true;
                    }

                    if (!$skip_query) {
                        $query = 'UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='".checkslashes($preference_value)."' WHERE `preference_id`='".$preference_id."'";
                        if (!$db->Execute($query)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Unable to update preference ID '.$preference_id.'. Please check your error log for more information.';
                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tPreference ID ".$preference_id.' was not updated. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }
                        }
                    }
                }

                if (!reload_configuration()) {
                    ++$ERROR;
                    $ERRORSTR[] = "Unable to reload your configuration into your session. Please check your error log for more information, but you'll have to close and then re-open your web-browser to load the changed settings.";

                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to reload the settings from the database. The load_settings() function returned false.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                    }
                } else {
                    ++$SUCCESS;
                    $SUCCESSSTR[] = 'You have successfully reloaded the ListMessenger configuration information.';
                }
            }
        }

        if (!is_dir($_SESSION['config'][PREF_PROPATH_ID])) {
            ++$NOTICE;
            $NOTICESTR[] = 'Your ListMessenger Directory Path does not exist at '.$_SESSION['config'][PREF_PROPATH_ID].'. Please enter the proper path to your ListMessenger directory.';
        }

        if (!is_dir($_SESSION['config'][PREF_PUBLIC_PATH])) {
            ++$NOTICE;
            $NOTICESTR[] = 'Your Public Folder Directory Path does not exist at '.$_SESSION['config'][PREF_PUBLIC_PATH].' or is not readable by PHP. Please enter the proper path to your Public folder directory.';
        }

        if (!is_dir($_SESSION['config'][PREF_PRIVATE_PATH])) {
            ++$NOTICE;
            $NOTICESTR[] = 'Your Private Folder Directory Path does not exist at '.$_SESSION['config'][PREF_PRIVATE_PATH].' or is not readable by PHP. Please enter the proper path to your Private folder directory.';
        }
        ?>
		<h1>Program Preferences</h1>
		<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /> <a href="index.php?section=preferences">Preferences and Configuration</a>&nbsp;
		<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /> Program Preferences
		<br /><br />
		<?php echo ($ERRORSTR > 0) ? display_error($ERRORSTR) : ''; ?>
		<?php echo ($NOTICE) ? display_notice($NOTICESTR) : ''; ?>
		<?php echo ($SUCCESS) ? display_success($SUCCESSSTR) : ''; ?>
		<form action="index.php?section=preferences&type=program" method="post">
		<fieldset>
		<legend class="page-subheading">Login Information</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('ListMessenger Username', '<strong><em>ListMessenger Username</em></strong><br />This username is what you will enter on the ListMessenger login page to access the ListMessenger interface.<br /><br /><strong>Important:</strong><br />If you forget this password, it can be retrieved using PHPMyAdmin or any other database management application and look in the preferences table.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo PREF_ADMUSER_ID; ?>]" value="<?php echo $_SESSION['config'][PREF_ADMUSER_ID]; ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('New ListMessenger Password', '<strong><em>New ListMessenger Password</em></strong><br />If you would like to change the password that you will use to log into the ListMessenger administration interface, you can simply type the new password here.', false); ?></td>
				<td><input type="password" class="text-box" style="width: 150px" name="npassword1" value="" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Retype New Password', "<strong><em>Retype New Password</em></strong><br />If you are entering a new password, please verify the new password by entering it again in this box.'", false); ?></td>
				<td><input type="password" class="text-box" style="width: 150px" name="npassword2" value="" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<br />
		<fieldset>
		<legend class="page-subheading">Contact Information</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('Default From Name', '<strong><em>Default From Name</em></strong><br />This is the default name that will show up in from and reply field of any e-mail client when a subscriber receives a newsletter. This would generally be your full name, company name or website title.', true); ?></td>
				<td style="width: 60%"><input type="text" class="text-box" style="width: 60%" name="preferences[<?php echo PREF_FRMNAME_ID; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_FRMNAME_ID]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Default From E-Mail Address', '<strong><em>Default From E-Mail Address</em></strong><br />This is the default e-mail address that will show up in the from field of any e-mail client when a subscriber receives a newsletter.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 60%" name="preferences[<?php echo PREF_FRMEMAL_ID; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_FRMEMAL_ID]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Default Reply E-Mail Address', '<strong><em>Default Reply E-Mail Address</em></strong><br />This is the default e-mail address that will show up in the reply field of any e-mail client when a subscriber receives a newsletter.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 60%" name="preferences[<?php echo PREF_RPYEMAL_ID; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_RPYEMAL_ID]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Administrator E-Mail Address', '<strong><em>Administrator E-Mail Address</em></strong><br />This e-mail address will receive administrative notifications such as when a user subscribes or unsubscribes, as well as system password reset e-mails.<br /><br />This is an administrative e-mail account that is only used by ListMessenger to send information to the program owner and will never been seen by subscribers.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 60%" name="preferences[<?php echo PREF_ADMEMAL_ID; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_ADMEMAL_ID]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Abuse E-Mail Address', '<strong><em>Abuse E-Mail Address</em></strong><br />This important e-mail address will provide subscribers with an address that enables them to contact you if they feel there is an instance of abuse.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 60%" name="preferences[<?php echo PREF_ABUEMAL_ID; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_ABUEMAL_ID]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Bounce-To E-Mail Address', '<strong><em>Bounce-To E-Mail Address</em></strong><br />This e-mail address (also known as the Return-Path) will instruct remote mail servers where to send bounce messages if the subscribers e-mail address no longer exists, is full or if there is any other problems with delivery of your newsletter.<br /><br /><strong>Important:</strong><br />In most cases you should set this e-mail address to be the same as your Default From E-Mail Address. If this address differs from your &quot;from&quot; address some spam filters might consider your newsletter as spam.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 60%" name="preferences[<?php echo PREF_ERREMAL_ID; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_ERREMAL_ID]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<br />
		<fieldset>
		<legend class="page-subheading">Directory Paths and URLs</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('ListMessenger Program URL', '<strong><em>ListMessenger Program URL</em></strong><br />This is the full URL address to your ListMessenger directory on your web-server.<br /><br /><strong>Example:</strong><br />http://domain.com/listmessenger/', true); ?></td>
				<td><input type="text" class="text-box" style="width: 100%" name="preferences[<?php echo PREF_PROGURL_ID; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_PROGURL_ID]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Public Folder URL', '<strong><em>Public Folder URL</em></strong><br />This is the full URL address to your ListMessenger &quot;public&quot; directory on your web-server.<br /><br /><strong>Example:</strong><br />http://domain.com/lmpublic/', true); ?></td>
				<td><input type="text" class="text-box" style="width: 100%" name="preferences[<?php echo PREF_PUBLIC_URL; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_PUBLIC_URL]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('ListMessenger Directory Path', "<strong><em>ListMessenger Directory Path</em></strong><br />This is the full directory path from root to your ListMessenger program directory. This field is <strong>not</strong> a URL, but <strong>is</strong> a directory path.<br /><br /><strong>Example:</strong><br />/home/domain.com/listmessenger/ or D:/domain.com/listmessenger/.<br /><br /><strong>Important:</strong><br />Windows users, please ensure you use forward slashes [/] to input your directory, <strong>not</strong> back slashes [\&#92;].", true); ?></td>
				<td><input type="text" class="text-box" style="width: 100%" name="preferences[<?php echo PREF_PROPATH_ID; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_PROPATH_ID]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Public Folder Directory Path', "<strong><em>Public Folder Directory Path</em></strong><br />This is the full directory path from root to your ListMessenger &quot;public&quot; directory. This field is <strong>not</strong> a URL, but <strong>is</strong> a directory path.<br /><br /><strong>Example:</strong><br />/home/domain.com/lmpublic/ or D:/domain.com/lmpublic/.<br /><br /><strong>Important:</strong><br />Windows users, please ensure you use forward slashes [/] to input your directory, <strong>not</strong> back slashes [\&#92;].", true); ?></td>
				<td><input type="text" class="text-box" style="width: 100%" name="preferences[<?php echo PREF_PUBLIC_PATH; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_PUBLIC_PATH]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Private Folder Directory Path', "<strong><em>Private Folder Directory Path</em></strong><br />This is the full directory path from root to your ListMessenger &quot;private&quot; directory. This field is <strong>not</strong> a URL, but <strong>is</strong> a directory path.<br /><br /><strong>Example:</strong><br />/home/lmprivate/ or D:/lmprivate/.<br /><br /><strong>Important:</strong><br />Windows users, please ensure you use forward slashes [/] to input your directory, <strong>not</strong> back slashes [\&#92;].<br /><br /><strong>Security Notice:</strong><br />This directory should <strong>not</strong> be web accessible, meaning that you should <strong>not</strong> be able to access this folder with your web-browser. This is especially true if you are not using the Apache web-server or if your web-server does not read .htaccess files.", true); ?></td>
				<td><input type="text" class="text-box" style="width: 100%" name="preferences[<?php echo PREF_PRIVATE_PATH; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_PRIVATE_PATH]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<?php
            if (substr($_SESSION['config'][PREF_PRIVATE_PATH], 0, strlen($_SESSION['config'][PREF_PROPATH_ID])) == $_SESSION['config'][PREF_PROPATH_ID]) {
                if (!file_exists($_SESSION['config'][PREF_PRIVATE_PATH].'ignore.txt')) {
                    echo "<tr>\n";
                    echo "	<td>&nbsp;</td>\n";
                    echo "	<td style=\"padding-top: 5px\">\n";
                    echo display_notice(['It appears as though your ListMessenger &quot;private&quot; folder may reside in a web-accessible directory.<br /><br />While ListMessenger will function normally like this, we generally recommend if possible to move your private folder outside of your web-root as an added security precaution.<br /><br />To make this warning go away, either move your private folder outside of your ListMessenger directory or place a blank file called ignore.txt in the folder.']);
                    echo "	</td>\n";
                    echo "</tr>\n";
                }
            }
        ?>
		</tbody>
		</table>
		</fieldset>

		<br />
		<fieldset>
		<legend class="page-subheading">Date and Time</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('PHP Date Format', '<strong><em>PHP Date Format</em></strong><br />This is a <a href="http://www.php.net/date" target="_blank">PHP compatible date format</a> that will be used in most cases to display the current date and time throughout the ListMessenger interface as well as in newsletters that contain the [date] variable.<br /><br /><strong>Important:</strong><br />If you change this, make sure that the date format you enter is valid or your dates will not be displayed properly.', true); ?></td>
				<td>
					<input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo PREF_DATEFORMAT; ?>]" value="<?php echo $_SESSION['config'][PREF_DATEFORMAT]; ?>" onkeypress="return handleEnter(this, event)" />
				</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Timezone Offset', '<strong><em>Timezone Offset</em></strong><br />This option allows you to specify the number of hours difference between the timezone you are located in and Greenwich Mean Time (GMT).<br /><br /><strong>Example:</strong><br />GMT -5:00 hours is Eastern Standard Time (EST)', true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_TIMEZONE; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="-12"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-12') ? ' selected="selected"' : ''; ?>>GMT - 12:00 hours</option>
					<option value="-11"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-11') ? ' selected="selected"' : ''; ?>>GMT - 11:00 hours</option>
					<option value="-10"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-10') ? ' selected="selected"' : ''; ?>>GMT - 10:00 hours</option>
					<option value="-9"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-9') ? ' selected="selected"' : ''; ?>>GMT - 9:00 hours</option>
					<option value="-8"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-8') ? ' selected="selected"' : ''; ?>>GMT - 8:00 hours</option>
					<option value="-7"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-7') ? ' selected="selected"' : ''; ?>>GMT - 7:00 hours</option>
					<option value="-6"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-6') ? ' selected="selected"' : ''; ?>>GMT - 6:00 hours</option>
					<option value="-5"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-5') ? ' selected="selected"' : ''; ?>>GMT - 5:00 hours</option>
					<option value="-4"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-4') ? ' selected="selected"' : ''; ?>>GMT - 4:00 hours</option>
					<option value="-3.5"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-3.5') ? ' selected="selected"' : ''; ?>>GMT - 3:30 hours</option>
					<option value="-3"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-3') ? ' selected="selected"' : ''; ?>>GMT - 3:00 hours</option>
					<option value="-2"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-2') ? ' selected="selected"' : ''; ?>>GMT - 2:00 hours</option>
					<option value="-1"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '-1') ? ' selected="selected"' : ''; ?>>GMT - 1:00 hour</option>
					<option value="0"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '0') ? ' selected="selected"' : ''; ?>>GMT</option>
					<option value="1"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '1') ? ' selected="selected"' : ''; ?>>GMT + 1:00 hour</option>
					<option value="2"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '2') ? ' selected="selected"' : ''; ?>>GMT + 2:00 hours</option>
					<option value="3"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '3') ? ' selected="selected"' : ''; ?>>GMT + 3:00 hours</option>
					<option value="3.5"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '3.5') ? ' selected="selected"' : ''; ?>>GMT + 3:30 hours</option>
					<option value="4"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '4') ? ' selected="selected"' : ''; ?>>GMT + 4:00 hours</option>
					<option value="4.5"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '4.5') ? ' selected="selected"' : ''; ?>>GMT + 4:30 hours</option>
					<option value="5"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '5') ? ' selected="selected"' : ''; ?>>GMT + 5:00 hours</option>
					<option value="5.5"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '5.5') ? ' selected="selected"' : ''; ?>>GMT + 5:30 hours</option>
					<option value="6"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '6') ? ' selected="selected"' : ''; ?>>GMT + 6:00 hours</option>
					<option value="7"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '7') ? ' selected="selected"' : ''; ?>>GMT + 7:00 hours</option>
					<option value="8"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '8') ? ' selected="selected"' : ''; ?>>GMT + 8:00 hours</option>
					<option value="9"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '9') ? ' selected="selected"' : ''; ?>>GMT + 9:00 hours</option>
					<option value="9.5"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '9.5') ? ' selected="selected"' : ''; ?>>GMT + 9:30 hours</option>
					<option value="10"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '10') ? ' selected="selected"' : ''; ?>>GMT + 10:00 hours</option>
					<option value="11"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '11') ? ' selected="selected"' : ''; ?>>GMT + 11:00 hours</option>
					<option value="12"<?php echo ($_SESSION['config'][PREF_TIMEZONE] == '12') ? ' selected="selected"' : ''; ?>>GMT + 12:00 hours</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Adjust Daylight Savings Time', "<strong><em>Adjust Daylight Savings Time</em></strong><br />This option allows you to specify whether or not you would like ListMessenger to try to automatically adjust for daylight savings time.<br /><br /><strong>Note:</strong><br />There are many countries on Earth who do not use Daylight Savings Time, and many countries who\'s daylight savings time starts on different hours or different days.", true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_DAYLIGHT_SAVINGS; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][PREF_DAYLIGHT_SAVINGS] == 'yes') ? ' selected="selected"' : ''; ?>>Yes</option>
					<option value="no"<?php echo ($_SESSION['config'][PREF_DAYLIGHT_SAVINGS] == 'no') ? ' selected="selected"' : ''; ?>>No</option>
					</select>
					<span class="small-grey"><strong>Output:</strong> <?php echo display_date($_SESSION['config'][PREF_DATEFORMAT], time()); ?></span>
				</td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<br />
		<fieldset>
		<legend class="page-subheading">General Options</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('Use Rich Text Editor', '<strong><em>Use Rich Text Editor</em></strong><br />This option allows you to specify which, if any, rich text editor (WYSIWYG editor) you would like to use when you are composing and editing a message within ListMessenger.<br /><br /><strong>Please Note:</strong><br />There is a functionality difference between TinyMCE Basic and TinyMCE Advanced modes. The TinyMCE Basic mode will produce plain HTML without any inline styles (CSS), whereas TinyMCE Advanced mode will produce valid XHTML and will generate inline styles (CSS).<br /><br />While creating any HTML document using CSS is generally recommended, using CSS in HTML newsletters will unfortunately produce a less compatible HTML newsletter, mainly due to inadequacies in Outlook 2007.', true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_USERTE; ?>]" onkeypress="return handleEnter(this, event)">
    					<option value="ckeditor"<?php echo !in_array($_SESSION['config'][PREF_USERTE], ['disabled', 'no']) ? ' selected="selected"' : ''; ?>>CKEditor 4</option>
	    				<option value="disabled"<?php echo (($_SESSION['config'][PREF_USERTE] == 'disabled') || ($_SESSION['config'][PREF_USERTE] == 'no')) ? ' selected="selected"' : ''; ?>>Disabled</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Character Encoding', '<strong><em>Character Encoding</em></strong><br />Some languages other than English will require you to change the character encoding so that multi-byte characters can be properly displayed.<br /><br /><strong>Important:</strong><br />Most users will not be required to change this; however, if you do change it, make sure you select the proper encoding type.<br /><br />Also note that this encoding type will be what is used not only throughout the ListMessenger interface, but also in all e-mail messages that you send out.', true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_DEFAULT_CHARSET; ?>]" onkeypress="return handleEnter(this, event)">
					<?php
                ksort($CHARACTER_SETS);
        foreach ($CHARACTER_SETS as $charset => $description) {
            echo '<option value="'.$charset.'"'.((strtolower($_SESSION['config'][PREF_DEFAULT_CHARSET]) == strtolower($charset)) ? ' selected="selected"' : '').'>'.clean_input($charset, ['upper', 'encode']).': '.html_encode($description)."</option>\n";
        }
        ?>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('HTML Encoding Function', "<strong><em>HTML Encoding Function</em></strong><br />ListMessenger helps to prevent cross-site scripting vulnerabilities by encoding all data displayed on your screen from the database.<br /><br />By default ListMessenger uses PHP's <a href=\"http://www.php.net/htmlentities\">htmlentities()</a> function to do this; however, this can in some cases cause problems displaying multi-byte characters.<br /><br />If you are having trouble displaying multi-byte characters in ListMessenger, you can change this option to <a href=\"http://www.php.net/htmlspecialchars\">htmlspecialchars()</a>, which will encode fewer characters.", true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_ENCODING_STYLE; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="htmlentities"<?php echo ($_SESSION['config'][PREF_ENCODING_STYLE] == 'htmlentities') ? ' selected="selected"' : ''; ?>>Use htmlentities()</option>
					<option value="htmlspecialchars"<?php echo ($_SESSION['config'][PREF_ENCODING_STYLE] == 'htmlspecialchars') ? ' selected="selected"' : ''; ?>>Use htmlspecialchars()</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Display Rows Per Page', '<strong><em>Display Rows Per Page</em></strong><br />This setting allows you to adjust the number of rows are displayed per page throughout the ListMessenger interface. The default is 25 rows per page.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo PREF_PERPAGE_ID; ?>]" value="<?php echo (int) $_SESSION['config'][PREF_PERPAGE_ID]; ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Log Error Messages', '<strong><em>Log Error Messages</em></strong><br />This option allows you to specify whether or not you should log error messages to your private/log directory.<br /><br /><strong>Important:</strong><br />Logging can be very beneficial to see what exactly the problem is if something goes wrong; however, you may wish to disable to save a bit of disk space or if ListMessenger is working fine for you.', true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_ERROR_LOGGING; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') ? ' selected="selected"' : ''; ?>>Yes</option>
					<option value="no"<?php echo ($_SESSION['config'][PREF_ERROR_LOGGING] == 'no') ? ' selected="selected"' : ''; ?>>No</option>
					</select>
				</td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<div style="text-align: right; border-top: 1px #333333 dotted; margin: 15px 0px 15px 0px; padding-top: 10px">
			<input type="button" class="button" value="Close" onclick="window.location='index.php?section=preferences'" />&nbsp;
			<input type="submit" name="save" class="button" value="Save" />
		</div>
		</form>
		<?php
    break;
    case 'blacklist' :
        if ((!empty($_POST['preferences'])) && is_array($_POST['preferences']) && count($_POST['preferences'])) {
            foreach ($_POST['preferences'] as $preference_id => $preference_value) {
                $preference_value = trim($preference_value);
                $skip_query = false;

                switch ($preference_id) {
                    case ENDUSER_BANEMAIL:
                        if ($preference_value != '') {
                            $value = str_replace("\r", "\n", $preference_value);
                            $banned = str_replace("\n\n", "\n", $value);
                            $preference_value = str_replace("\n", ';', $banned);
                        } else {
                            $preference_value = '';
                        }
                        break;
                    case ENDUSER_BANIPS:
                        if ($preference_value != '') {
                            $value = str_replace("\r", "\n", $preference_value);
                            $banned = str_replace("\n\n", "\n", $value);
                            $preference_value = str_replace("\n", ';', $banned);
                        } else {
                            $preference_value = '';
                        }
                        break;
                    default:
                        $ERROR++;
                        $ERRORSTR[] = 'Unrecognized preference ID ['.$preference_id.'] with a value of ['.$preference_value.'] was passed to preferences updater.';
                        $skip_query = true;
                        break;
                }

                // Only change modified preferences.
                if ($_SESSION['config'][$preference_id] == $preference_value) {
                    $skip_query = true;
                }

                if (!$skip_query) {
                    $query = 'UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='".checkslashes($preference_value)."' WHERE `preference_id`='".$preference_id."'";
                    if (!$db->Execute($query)) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Unable to update preference ID '.$preference_id.'. Please check your error log for more information.';
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tPreference ID ".$preference_id.' was not updated. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    }
                }
            }

            if (!reload_configuration()) {
                ++$ERROR;
                $ERRORSTR[] = "Unable to reload your configuration into your session. Please check your error log for more information, but you'll have to close and then re-open your web-browser to load the changed settings.";

                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to reload the settings from the database. The load_settings() function returned false.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            } else {
                ++$SUCCESS;
                $SUCCESSSTR[] = 'You have successfully updated and reloaded the ListMessenger Blacklist settings.';
            }
        }

        ?>
		<h1>ListMessenger Blacklist</h1>
		<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /> <a href="index.php?section=preferences">Preferences and Configuration</a>&nbsp;
		<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /> ListMessenger Blacklist
		<br /><br />
		<?php echo ($ERROR) ? display_error($ERRORSTR) : ''; ?>
		<?php echo ($NOTICE) ? display_notice($NOTICESTR) : ''; ?>
		<?php echo ($SUCCESS) ? display_success($SUCCESSSTR) : ''; ?>
		<form action="index.php?section=preferences&type=blacklist" method="post">
		<fieldset>
		<legend class="page-subheading">Blacklisted Content</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td style="vertical-align: top">
					<?php echo create_tooltip('Banned E-Mail Addresses', '<strong><em>Banned E-Mail Addresses</em></strong><br />If you would like to ban a specific e-mail address or domain name from being able to subscribe to your mailing lists, simply enter the address or domain, one per line in this textarea.<br /><br />Please note that you can use the * as a wild card to ban multiple e-mail addresses with one entry.<br /><br /><strong>Example Usage:</strong><ul><li>user@domain.com</li><li>*@domain.com</li><li>user@*.com</li><li>*.com</li></ul>', false); ?>
					<div class="small-grey" style="font-size: 11px; margin-top: 10px">
						<strong>Example Usage:</strong>
						<ul style="margin-top: 3px">
							<li>user@domain.com</li>
							<li>*@domain.com</li>
							<li>user@*.com</li>
							<li>*.com</li>
						</ul>
					</div>
				</td>
				<td>
					<textarea style="width: 100%; height: 200px" name="preferences[<?php echo ENDUSER_BANEMAIL; ?>]"><?php echo str_replace(';', "\n", html_encode($_SESSION['config'][ENDUSER_BANEMAIL])); ?></textarea>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td style="vertical-align: top">
					<?php echo create_tooltip('Banned IP Addresses', '<strong><em>Banned IP Addresses</em></strong><br />If you would like to ban specific IP addresses from being able to connect to the ListMessenger interface or subscribe to your mailing lists you can do so by entering them, one per line in this text area.<br /><br />Please note that you can use the * as a wild card to ban entire sub-nets with one entry.<br /><br /><strong>Example Usage:</strong><ul><li>192.168.1.50</li><li>198.168.1.*</li><li>192.168.*.*</li></ul>', false); ?>
					<div class="small-grey" style="font-size: 11px; margin-top: 10px">
						<strong>Example Usage:</strong>
						<ul style="margin-top: 3px">
							<li>192.168.1.50</li>
							<li>192.168.1.*</li>
							<li>192.168.*.*</li>
						</ul>
					</div>
				</td>
				<td>
					<textarea style="width: 100%; height: 200px" name="preferences[<?php echo ENDUSER_BANIPS; ?>]"><?php echo str_replace(';', "\n", html_encode($_SESSION['config'][ENDUSER_BANIPS])); ?></textarea>
				</td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<div style="text-align: right; border-top: 1px #333333 dotted; margin: 15px 0px 15px 0px; padding-top: 10px">
			<input type="button" class="button" value="Close" onclick="window.location='index.php?section=preferences'" />&nbsp;
			<input type="submit" name="save" class="button" value="Save" />
		</div>
		</form>
		<?php
    break;
    case 'enduser' :
        if ((!empty($_POST['preferences'])) && is_array($_POST['preferences']) && count($_POST['preferences'])) {
            foreach ($_POST['preferences'] as $preference_id => $preference_value) {
                $preference_value = trim($preference_value);
                $skip_query = false;
                switch ($preference_id) {
                    case ENDUSER_CAPTCHA:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case ENDUSER_UNSUBCON:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case ENDUSER_SUBCON:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case PREF_EXPIRE_CONFIRM:
                        if (!(int) $preference_value) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Please select a valid number of days that you would like confirmation notices to expire.';
                            $skip_query = true;
                        }
                        break;
                    case PREF_POSTSUBSCRIBE_MSG:
                        $preference_value = (int) $preference_value;
                        break;
                    case PREF_POSTUNSUBSCRIBE_MSG:
                        $preference_value = (int) $preference_value;
                        break;
                    case ENDUSER_NEWSUBNOTICE:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case ENDUSER_UNSUBNOTICE:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case PREF_FOPEN_URL:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case ENDUSER_MXRECORD:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case ENDUSER_ARCHIVE:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case ENDUSER_FORWARD:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case ENDUSER_PROFILE:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case ENDUSER_LANG_ID:
                        if (!file_exists($_SESSION['config'][PREF_PUBLIC_PATH].'languages/'.$preference_value.'.lang.php')) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The language file ['.$preference_value.'.lang.php] that you have selected does not exist in '.$_SESSION['config'][PREF_PUBLIC_PATH].'languages/.';
                            $skip_query = true;
                        }
                        break;
                    case ENDUSER_ARCHIVE_FILENAME:
                        if (!file_exists($_SESSION['config'][PREF_PUBLIC_PATH].$preference_value)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The Archive Script Filename ['.$preference_value.'] that you have specified does not exist in '.$_SESSION['config'][PREF_PUBLIC_PATH].'.';
                            $skip_query = true;
                        }
                        break;
                    case ENDUSER_PROFILE_FILENAME:
                        if (!file_exists($_SESSION['config'][PREF_PUBLIC_PATH].$preference_value)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The Profile Script Filename ['.$preference_value.'] that you have specified does not exist in '.$_SESSION['config'][PREF_PUBLIC_PATH].'.';
                            $skip_query = true;
                        }
                        break;
                    case ENDUSER_CONFIRM_FILENAME:
                        if (!file_exists($_SESSION['config'][PREF_PUBLIC_PATH].$preference_value)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The Confirmation Script Filename ['.$preference_value.'] that you have specified does not exist in '.$_SESSION['config'][PREF_PUBLIC_PATH].'.';
                            $skip_query = true;
                        }
                        break;
                    case ENDUSER_HELP_FILENAME:
                        if (!file_exists($_SESSION['config'][PREF_PUBLIC_PATH].$preference_value)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The Help Page Filename ['.$preference_value.'] that you have specified does not exist in '.$_SESSION['config'][PREF_PUBLIC_PATH].'.';
                            $skip_query = true;
                        }
                        break;
                    case ENDUSER_FORWARD_FILENAME:
                        if (!file_exists($_SESSION['config'][PREF_PUBLIC_PATH].$preference_value)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The Forward Script Filename ['.$preference_value.'] that you have specified does not exist in '.$_SESSION['config'][PREF_PUBLIC_PATH].'.';
                            $skip_query = true;
                        }
                        break;
                    case ENDUSER_FILENAME:
                        if (!file_exists($_SESSION['config'][PREF_PUBLIC_PATH].$preference_value)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The End-User Script Filename ['.$preference_value.'] that you have specified does not exist in '.$_SESSION['config'][PREF_PUBLIC_PATH].'.';
                            $skip_query = true;
                        }
                        break;
                    case ENDUSER_TEMPLATE:
                        if (!file_exists($_SESSION['config'][PREF_PUBLIC_PATH].$preference_value)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The End-User Template Filename ['.$preference_value.'] that you have specified does not exist in '.$_SESSION['config'][PREF_PUBLIC_PATH].'.';
                            $skip_query = true;
                        }
                        break;
                    case ENDUSER_UNSUB_FILENAME:
                        if (!file_exists($_SESSION['config'][PREF_PUBLIC_PATH].$preference_value)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The Unsubscribe Script ['.$preference_value.'] that you have specified does not exist in '.$_SESSION['config'][PREF_PUBLIC_PATH].'.';
                            $skip_query = true;
                        }
                        break;
                    default:
                        $ERROR++;
                        $ERRORSTR[] = 'Unrecognized preference ID ['.$preference_id.'] with a value of ['.$preference_value.'] was passed to preferences updater.';
                        $skip_query = true;
                        break;
                }

                // Only change modified preferences.
                if ($_SESSION['config'][$preference_id] == $preference_value) {
                    $skip_query = true;
                }

                if (!$skip_query) {
                    $query = 'UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='".checkslashes($preference_value)."' WHERE `preference_id`='".$preference_id."'";
                    if (!$db->Execute($query)) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Unable to update preference ID '.$preference_id.'. Please check your error log for more information.';
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tPreference ID ".$preference_id.' was not updated. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    }
                }
            }

            if (!reload_configuration()) {
                ++$ERROR;
                $ERRORSTR[] = "Unable to reload your configuration into your session. Please check your error log for more information, but you'll have to close and then re-open your web-browser to load the changed settings.";

                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to reload the settings from the database. The load_settings() function returned false.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            } else {
                ++$SUCCESS;
                $SUCCESSSTR[] = 'You have successfully updated and reloaded the ListMessenger End-User Preferences.';
            }
        }

        if (!is_dir($_SESSION['config'][PREF_PUBLIC_PATH])) {
            ++$NOTICE;
            $NOTICESTR[] = 'Your Public Folder Directory Path does not exist at '.$_SESSION['config'][PREF_PUBLIC_PATH].' or is not readable by PHP. Please log into the ListMessenger Program Preferences and update the Public Folder Directory Path.';
        }
        ?>
		<h1>End-User Preferences</h1>
		<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /> <a href="index.php?section=preferences">Preferences and Configuration</a>&nbsp;
		<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /> End-User Preferences
		<br /><br />
		<?php echo ($ERROR) ? display_error($ERRORSTR) : ''; ?>
		<?php echo ($NOTICE) ? display_notice($NOTICESTR) : ''; ?>
		<?php echo ($SUCCESS) ? display_success($SUCCESSSTR) : ''; ?>
		<form action="index.php?section=preferences&type=enduser" method="post">
		<fieldset>
		<legend class="page-subheading">Confirmation Settings</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('Require CAPTCHA Image Confirmation', '<strong><em>Require CAPTCHA Image Confirmation</em></strong><br />Require subscribers to enter the text in a <acronym title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</acronym> image code before being able to subscribe to your list.<br /><br />This functionality requires GD Image Library and Freetype support to be compiled with PHP, which your installation <strong>does'.(((!function_exists('gd_info')) || (!function_exists('imagettftext'))) ? ' not' : '').'</strong> appear to have.', true); ?></td>
				<td>
					<?php
                    if (((!function_exists('gd_info')) || (!function_exists('imagettftext'))) && ($_SESSION['config'][ENDUSER_CAPTCHA] == 'no')) {
                        echo '<input type="hidden" name="preferences['.ENDUSER_CAPTCHA."]\" value=\"no\" />\n";
                        $allow_captcha = false;
                    } else {
                        $allow_captcha = true;
                    }
        ?>
					<select style="width: 175px" name="preferences[<?php echo ENDUSER_CAPTCHA; ?>]" onkeypress="return handleEnter(this, event)"<?php echo (!$allow_captcha) ? ' disabled="disabled"' : ''; ?>>
						<option value="yes"<?php echo ($allow_captcha && ($_SESSION['config'][ENDUSER_CAPTCHA] == 'yes')) ? ' selected="selected"' : ''; ?>>Yes</option>
						<option value="no"<?php echo ((!$allow_captcha) || ($_SESSION['config'][ENDUSER_CAPTCHA] == 'no')) ? ' selected="selected"' : ''; ?>>No</option>
					</select>
					<?php
        if ((!$allow_captcha) && ($_SESSION['config'][ENDUSER_CAPTCHA] == 'no')) {
            echo '<span class="small-grey">(Disabled because <strong>GD</strong> is unavailable.)</span>';
        }
        ?>
				</td>
			</tr>
			<?php
            if (((!function_exists('gd_info')) || (!function_exists('imagettftext'))) && ($_SESSION['config'][ENDUSER_CAPTCHA] == 'yes')) {
                echo "<tr>\n";
                echo "	<td>&nbsp;</td>\n";
                echo "	<td style=\"padding-top: 5px\">\n";
                echo display_notice(['You have setup ListMessenger to require CAPTCHA image confirmation; however, GD Image Library or Freetype is no longer available with PHP on your server.<br /><br />Please disable Require CAPTCHA Image Confirmation, otherwise your subscribers will be unable to subscribe using the end-user tools.<br /><br />If you require this functionality to be enabled (recommended) you must ensure that GD Image Library and Freetype support is available with PHP.</span>']);
                echo "	</td>\n";
                echo "</tr>\n";
            }
        ?>
			<tr>
				<td><?php echo create_tooltip('Require MX Record Validation', '<strong><em>Require MX Record Validation</em></strong><br />Set this to yes if you want the subscribers e-mail address domain name to be validated as they are subscribing.<br /><br /><strong>Important:</strong><br />This option is not available if ListMessenger is installed on a Windows server due to restrictions with PHP and the Windows operating system.', true); ?></td>
				<td>
					<?php
                $PHP_OS = PHP_OS;

        if ((strtoupper(substr($PHP_OS, 0, 3)) == 'WIN') && ($_SESSION['config'][ENDUSER_MXRECORD] == 'no')) {
            echo '<input type="hidden" name="preferences['.ENDUSER_MXRECORD."]\" value=\"no\" />\n";
            $allow_mxrecord = false;
        } else {
            $allow_mxrecord = true;
        }
        ?>
					<select style="width: 175px" name="preferences[<?php echo ENDUSER_MXRECORD; ?>]" onkeypress="return handleEnter(this, event)"<?php echo (!$allow_mxrecord) ? ' disabled="disabled"' : ''; ?>>
						<option value="yes"<?php echo ($allow_mxrecord && ($_SESSION['config'][ENDUSER_MXRECORD] == 'yes')) ? ' selected="selected"' : ''; ?>>Yes</option>
						<option value="no"<?php echo ((!$allow_mxrecord) || ($_SESSION['config'][ENDUSER_MXRECORD] == 'no')) ? ' selected="selected"' : ''; ?>>No</option>
					</select>
					<?php
        if ((!$allow_mxrecord) && ($_SESSION['config'][ENDUSER_MXRECORD] == 'no')) {
            echo '<span class="small-grey">(Disabled on <strong>Windows</strong> operating system.)</span>';
        }
        ?>
				</td>
			</tr>
			<?php
            if ((strtoupper(substr($PHP_OS, 0, 3)) == 'WIN') && ($_SESSION['config'][ENDUSER_MXRECORD] == 'yes')) {
                echo "<tr>\n";
                echo "	<td>&nbsp;</td>\n";
                echo "	<td style=\"padding-top: 5px\">\n";
                echo display_notice(['You have setup ListMessenger to validate MX records of subscribers as they subscribe; however, you appear to be hosting ListMessenger on a Windows server and Windows does not allow this sort of functionality.<br /><br />Please disable MX Record Lookup, otherwise your subscribers will be unable to subscribe using the end-user tools.<br /><br />If you require this sort of functionality you must switch your web-hosting to a Unix / Linux based operating system.']);
                echo "	</td>\n";
                echo "</tr>\n";
            }
        ?>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Send User Opt-In Confirmation', '<strong><em>Send User Opt-In Confirmation</em></strong><br />Send a confirmation e-mail prior to potential subscribers prior to adding them to your mailing list.<br /><br /><strong>Important:</strong><br />This option should almost always be set to <strong>yes</strong>. If you do not require subscribers to confirm their subscription then you run a risk of being a spammer.', true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo ENDUSER_SUBCON; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][ENDUSER_SUBCON] == 'yes') ? ' selected="selected"' : ''; ?>>Yes</option>
					<option value="no"<?php echo ($_SESSION['config'][ENDUSER_SUBCON] == 'no') ? ' selected="selected"' : ''; ?>>No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Send User Opt-Out Confirmation', '<strong><em>Send User Opt-Out Confirmation</em></strong><br />Send a confirmation e-mail prior to removing subscribers from your mailing list.', true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo ENDUSER_UNSUBCON; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][ENDUSER_UNSUBCON] == 'yes') ? ' selected="selected"' : ''; ?>>Yes</option>
					<option value="no"<?php echo ($_SESSION['config'][ENDUSER_UNSUBCON] == 'no') ? ' selected="selected"' : ''; ?>>No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Confirmation Expiry', '<strong><em>Confirmation Expiry</em></strong><br />This is the number of days that the Opt-In and Opt-Out Confirmation links are valid for. After the expiry date, confirmations are no longer valid and will be removed.<br /><br /><strong>Notice:</strong><br />This option is only valid if Send Opt-In or Opt-Out Confirmation is set to yes.', false); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_EXPIRE_CONFIRM; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="1"<?php echo ($_SESSION['config'][PREF_EXPIRE_CONFIRM] == '1') ? ' selected="selected"' : ''; ?>>1 Day</option>
					<option value="3"<?php echo ($_SESSION['config'][PREF_EXPIRE_CONFIRM] == '3') ? ' selected="selected"' : ''; ?>>3 Days</option>
					<option value="5"<?php echo ($_SESSION['config'][PREF_EXPIRE_CONFIRM] == '5') ? ' selected="selected"' : ''; ?>>5 Days</option>
					<option value="7"<?php echo ($_SESSION['config'][PREF_EXPIRE_CONFIRM] == '7') ? ' selected="selected"' : ''; ?>>7 Days</option>
					<option value="14"<?php echo ($_SESSION['config'][PREF_EXPIRE_CONFIRM] == '14') ? ' selected="selected"' : ''; ?>>14 Days</option>
					<option value="31"<?php echo ($_SESSION['config'][PREF_EXPIRE_CONFIRM] == '31') ? ' selected="selected"' : ''; ?>>31 Days</option>
					</select>
				</td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<br />
		<fieldset>
		<legend class="page-subheading">Notification and Message Options</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('Send Admin New Subscriber Notices', '<strong><em>Send Admin New Subscriber Notices</em></strong><br />Enable this if you want to be notified every time a new subscriber subscribes to a list in ListMessenger.', true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo ENDUSER_NEWSUBNOTICE; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][ENDUSER_NEWSUBNOTICE] == 'yes') ? ' selected="selected"' : ''; ?>>Enabled</option>
					<option value="no"<?php echo ($_SESSION['config'][ENDUSER_NEWSUBNOTICE] == 'no') ? ' selected="selected"' : ''; ?>>Disabled</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Send Admin Unsubscribe Notices', '<strong><em>Send Admin Unsubscribe Notices</em></strong><br />Enable this if you want to be notified every time a subscriber unsubscribes from a list in ListMessenger.', true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo ENDUSER_UNSUBNOTICE; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][ENDUSER_UNSUBNOTICE] == 'yes') ? ' selected="selected"' : ''; ?>>Enabled</option>
					<option value="no"<?php echo ($_SESSION['config'][ENDUSER_UNSUBNOTICE] == 'no') ? ' selected="selected"' : ''; ?>>Disabled</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Send User Post Subscribe Message', '<strong><em>Send User Post Subscribe Message</em></strong><br />You can enable this option if you would like your subscribers to receive an e-mail which resides in your Message Centre automatically after they subscribe to your mailing list.<br /><br /><strong>Tip:</strong><br />You can use this to send your subscribers a post subscription welcome message!', false); ?></td>
				<td>
					<select style="width: 100%" name="preferences[<?php echo PREF_POSTSUBSCRIBE_MSG; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="0"<?php echo ($_SESSION['config'][PREF_POSTSUBSCRIBE_MSG] == '0') ? ' selected="selected"' : ''; ?>>-- No Post Subscribe Message Sent --</option>
					<?php
            $query = 'SELECT `message_id`, `message_title` FROM `'.TABLES_PREFIX.'messages` ORDER BY `message_date` DESC';
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                echo '<option value="'.$result['message_id'].'"'.(($_SESSION['config'][PREF_POSTSUBSCRIBE_MSG] == $result['message_id']) ? ' selected="selected"' : '').'>'.html_encode($result['message_title'])."</option>\n";
            }
        }
        ?>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Send User Post Unsubscribe Message', '<strong><em>Send User Post Unsubscribe Message</em></strong><br />You can enable this option if you would like your subscribers a message which resides in your Message Centre automatically after remove themselves from your mailing list.<br /><br /><strong>Tip:</strong><br />You can use this to send your subscribers a sorry-to-see-you-go, message or something to that effect.', false); ?></td>
				<td>
					<select style="width: 100%" name="preferences[<?php echo PREF_POSTUNSUBSCRIBE_MSG; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="0"<?php echo ($_SESSION['config'][PREF_POSTUNSUBSCRIBE_MSG] == '0') ? ' selected="selected"' : ''; ?>>-- No Post Unsubscribe Message Sent --</option>
					<?php
            $query = 'SELECT `message_id`, `message_title` FROM `'.TABLES_PREFIX.'messages` ORDER BY `message_date` DESC';
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                echo '<option value="'.$result['message_id'].'"'.(($_SESSION['config'][PREF_POSTUNSUBSCRIBE_MSG] == $result['message_id']) ? ' selected="selected"' : '').'>'.html_encode($result['message_title'])."</option>\n";
            }
        }
        ?>
					</select>
				</td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<br />
		<fieldset>
		<legend class="page-subheading">Template Options</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('Allow URL Fopening', "<strong><em>Allow URL Fopening</em></strong><br />Set this to yes if you want ListMessenger to open your template file using using the full URL instead of the path. This is useful if your template file is a PHP file that needs to be parsed by PHP.<br /><br /><strong>Important:</strong><br />You must have the <strong>allow_url_fopen</strong> directive enabled in your servers' <strong>php.ini</strong> file to use this functionality.<br /><br />Also your domain name must properly resolve this server, and if you have a load-balancer in front of your website it must be configured to allow this.", true); ?></td>
				<td>
					<?php
        if (((!ini_get('allow_url_fopen')) || (ini_get('allow_url_fopen') == 'Off')) && ($_SESSION['config'][PREF_FOPEN_URL] == 'no')) {
            echo '<input type="hidden" name="preferences['.PREF_FOPEN_URL."]\" value=\"no\" />\n";
            $allow_fopening = false;
        } else {
            $allow_fopening = true;
        }
        ?>
					<select style="width: 175px" name="preferences[<?php echo PREF_FOPEN_URL; ?>]" onkeypress="return handleEnter(this, event)"<?php echo (!$allow_fopening) ? ' disabled="disabled"' : ''; ?>>
						<option value="yes"<?php echo ($allow_fopening && ($_SESSION['config'][PREF_FOPEN_URL] == 'yes')) ? ' selected="selected"' : ''; ?>>Yes</option>
						<option value="no"<?php echo ((!$allow_fopening) || ($_SESSION['config'][PREF_FOPEN_URL] == 'no')) ? ' selected="selected"' : ''; ?>>No</option>
					</select>
					<?php
        if ((!$allow_fopening) && ($_SESSION['config'][PREF_FOPEN_URL] == 'no')) {
            echo '<span class="small-grey">(<strong>allow_url_fopen</strong> is disabled.)</span>';
        }
        ?>
				</td>
			</tr>
			<?php
            if (((!ini_get('allow_url_fopen')) || (ini_get('allow_url_fopen') == 'Off')) && ($_SESSION['config'][PREF_FOPEN_URL] == 'yes')) {
                echo "<tr>\n";
                echo "	<td>&nbsp;</td>\n";
                echo "	<td style=\"padding-top: 5px\">\n";
                echo display_notice(["You have setup ListMessenger to allow URL fopening; however, your servers' PHP configuration does not allow this.<br /><br />Please disable Allow URL Fopening, otherwise your subscribers will be unable to subscribe using the end-user tools.<br /><br />If you require this functionality to be enabled (i.e. you have PHP template files) then you must enable the <strong>allow_url_fopen</strong> directive in your servers <strong>php.ini</strong> file.</span>"]);
                echo "	</td>\n";
                echo "</tr>\n";
            }
            $language_directory = $_SESSION['config'][PREF_PUBLIC_PATH].'languages/';
        if (is_dir($language_directory)) {
            $handle = opendir($language_directory);
            if ($handle) {
                ?>
                        <tr>
                            <td><?php echo create_tooltip('Default End-User Language File', '<strong><em>Default End-User Language File</em></strong><br />This list shows all of the language files that currently reside in the languages folder which is located in the Public directory.', true); ?></td>
                            <td>
                            <?php
                    echo '<select style="width: 175px" name="preferences['.ENDUSER_LANG_ID."]\" onkeypress=\"return handleEnter(this, event)\">\n";
                while (false !== ($file = readdir($handle))) {
                    if (!is_dir($file)) {
                        $pieces = explode('.', strtolower($file));
                        if (($pieces[1] == 'lang') && (count($pieces) == 3) && (filesize($language_directory.$file) > 0)) {
                            echo '<option value="'.$pieces[0].'"'.(($_SESSION['config'][ENDUSER_LANG_ID] == $pieces[0]) ? ' selected="selected"' : '').'>'.ucwords($pieces[0])."</option>\n";
                        }
                    }
                }
                echo "</select>\n";
                ?>
                            </td>
                        </tr>
                        <?php
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'Unable to read any language files in your languages directory. Please ensure that there are valid language files in your public/languages directory.';
                echo "<tr>\n";
                echo "	<td colspan=\"2\">\n";
                echo display_error($ERRORSTR);
                echo "	</td>\n";
                echo "</tr>\n";
            }
        } else {
            ++$ERROR;
            $ERRORSTR[] = 'Unable to read find your languages directory at '.$language_directory.'. Please ensure that the language directory exists and contains valid ListMessenger language files.';
            echo "<tr>\n";
            echo "	<td colspan=\"2\">\n";
            echo display_error($ERRORSTR);
            echo "	</td>\n";
            echo "</tr>\n";
        }
        ?>
		</tbody>
		</table>
		</fieldset>

		<br />
		<fieldset>
		<legend class="page-subheading">End-User Extra Features</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('Enable Public Archive Access', '<strong><em>Enable Public Archive Access</em></strong><br />If you would like to enable people to view a list of all messages you have sent out in the past through their web-browser, you can set this to yes.', false); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo ENDUSER_ARCHIVE; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][ENDUSER_ARCHIVE] == 'yes') ? ' selected="selected"' : ''; ?>>Yes</option>
					<option value="no"<?php echo ($_SESSION['config'][ENDUSER_ARCHIVE] == 'no') ? ' selected="selected"' : ''; ?>>No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Enable Subscriber Profile Updates', '<strong><em>Enable Subscriber Profile Updates</em></strong><br />If you would like to allow subscribers to update their subscriber profile, you can set this to yes.', false); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo ENDUSER_PROFILE; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][ENDUSER_PROFILE] == 'yes') ? ' selected="selected"' : ''; ?>>Yes</option>
					<option value="no"<?php echo ($_SESSION['config'][ENDUSER_PROFILE] == 'no') ? ' selected="selected"' : ''; ?>>No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Enable Forward to a Friend', '<strong><em>Enable Forward to a Friend</em></strong><br />If you would like to allow your subscribers to use the <strong>Forward Message to a Friend</strong> feature built into ListMessenger, you can set this to yes.<br /><br />When this feature is enabled subscribers can click a link in the e-mail they receive from you <em>(i.e. [forwardtofriend] variable)</em>, enter their friends contact information into a form on your website, and the message will be sent to their friend.', false); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo ENDUSER_FORWARD; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][ENDUSER_FORWARD] == 'yes') ? ' selected="selected"' : ''; ?>>Yes</option>
					<option value="no"<?php echo ($_SESSION['config'][ENDUSER_FORWARD] == 'no') ? ' selected="selected"' : ''; ?>>No</option>
					</select>
				</td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<br />
		<fieldset>
		<legend class="page-subheading">End-User Files</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('Archive Script Filename', '<strong><em>Archive Script Filename</em></strong><br />This file is called archive.php by default and is responsible for displaying the public archive of messages sent.<br /><br /><strong>Tip:</strong><br />This script can be enabled or disabled in the Display Options category.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo ENDUSER_ARCHIVE_FILENAME; ?>]" value="<?php echo html_encode($_SESSION['config'][ENDUSER_ARCHIVE_FILENAME]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Confirmation Script Filename', '<strong><em>Confirm Script Filename</em></strong><br />This file is called confirm.php by default and is responsible for handling all confirmations for both unsubscribing and subscribing.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo ENDUSER_CONFIRM_FILENAME; ?>]" value="<?php echo html_encode($_SESSION['config'][ENDUSER_CONFIRM_FILENAME]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('End-User Script Filename', '<strong><em>End-User Script Filename</em></strong><br />This file is called listmessenger.php by default and acts as a sort of controller file more most end-user transactions.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo ENDUSER_FILENAME; ?>]" value="<?php echo html_encode($_SESSION['config'][ENDUSER_FILENAME]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Forward Script Filename', '<strong><em>Forward Script Filename</em></strong><br />This file is called forward.php by default and is used by the <strong>Forward to Friend</strong> feature.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo ENDUSER_FORWARD_FILENAME; ?>]" value="<?php echo html_encode($_SESSION['config'][ENDUSER_FORWARD_FILENAME]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Help Page Filename', '<strong><em>Help Page Filename</em></strong><br />This file is called help.php by default and displays basic help information for subscribers who wish to know more about a list they are on.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo ENDUSER_HELP_FILENAME; ?>]" value="<?php echo html_encode($_SESSION['config'][ENDUSER_HELP_FILENAME]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Profile Script Filename', '<strong><em>Profile Script Filename</em></strong><br />This file is called profile.php by default and allows subscribers to update their profile on the mailing list.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo ENDUSER_PROFILE_FILENAME; ?>]" value="<?php echo html_encode($_SESSION['config'][ENDUSER_PROFILE_FILENAME]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('End-User Template Filename', '<strong><em>End-User Template Filename</em></strong><br />This file is called template.html by default and is wrapped around the end-user output. This file <strong>must</strong> include a [title] variable, and [message] variable to function properly.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo ENDUSER_TEMPLATE; ?>]" value="<?php echo html_encode($_SESSION['config'][ENDUSER_TEMPLATE]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Unsubscribe Script Filename', '<strong><em>Unsubscribe Script Filename</em></strong><br />This file is called unsubscribe.php by default and generally takes care of all unsubscribe actions.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo ENDUSER_UNSUB_FILENAME; ?>]" value="<?php echo html_encode($_SESSION['config'][ENDUSER_UNSUB_FILENAME]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td colspan="2">
					<br />
					<div style="background-color: #FFFFCC; border: 1px #FFCC00 solid; padding: 5px">These files <strong>must</strong> be present in your Public Folder directory.</div>
				</td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<div style="text-align: right; border-top: 1px #333333 dotted; margin: 15px 0px 15px 0px; padding-top: 10px">
			<input type="button" class="button" value="Close" onclick="window.location='index.php?section=preferences'" />&nbsp;
			<input type="submit" name="save" class="button" value="Save" />
		</div>
		</form>
		<?php
    break;
    case 'email' :
        if ((!empty($_POST['preferences'])) && is_array($_POST['preferences']) && count($_POST['preferences'])) {
            foreach ($_POST['preferences'] as $preference_id => $preference_value) {
                $preference_value = trim($preference_value);
                $skip_query = false;
                switch ($preference_id) {
                    case PREF_WORDWRAP:
                        if (!(int) $preference_value) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Please enter a valid number of characters you would like ListMessenger to wrap your outgoing messages to.';
                            $skip_query = true;
                        }
                        break;
                    case PREF_ADD_UNSUB_LINK:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case PREF_ADD_UNSUB_GROUP:
                        if ($preference_value != 'yes') {
                            $preference_value = 'no';
                        }
                        break;
                    case PREF_MSG_PER_REFRESH:
                        if (!(int) $preference_value) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Please enter a valid number of messages you would like ListMessenger to send per refresh.';
                            $skip_query = true;
                        }
                        break;
                    case PREF_PAUSE_BETWEEN:
                        if (!(int) $preference_value) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Please enter a valid number of seconds you would like ListMessenger pause between refreshes.';
                            $skip_query = true;
                        }
                        break;
                    case PREF_QUEUE_TIMEOUT:
                        if (!(int) $preference_value) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Please enter a valid number of seconds that ListMessenger will consider your sending process stalled after.';
                            $skip_query = true;
                        }
                        break;
                    case PREF_MAILER_LE:
                        if (!in_array($preference_value, ['n', 'rn', 'r'])) {
                            $preference_value = 'n';
                        }
                        break;
                    case PREF_MAILER_INC_NAME:
                        if ($preference_value != 'no') {
                            $preference_value = 'yes';
                        }
                        break;
                    case PREF_MAILER_BY_ID:
                        $skip_query = true;
                        switch ($preference_value) {
                            case 'mail':
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='mail' WHERE `preference_id`='".$preference_id."'");
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='' WHERE `preference_id`='".PREF_MAILER_BY_VALUE."'");
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='yes' WHERE `preference_id`='".PREF_MAILER_SMTP_KALIVE."'");
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='false' WHERE `preference_id`='".PREF_MAILER_AUTH_ID."'");
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='' WHERE `preference_id`='".PREF_MAILER_AUTHUSER_ID."'");
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='' WHERE `preference_id`='".PREF_MAILER_AUTHPASS_ID."'");
                                break;
                            case 'mailadvanced':
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='mailadvanced' WHERE `preference_id`='".$preference_id."'");
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='' WHERE `preference_id`='".PREF_MAILER_BY_VALUE."'");
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='yes' WHERE `preference_id`='".PREF_MAILER_SMTP_KALIVE."'");
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='false' WHERE `preference_id`='".PREF_MAILER_AUTH_ID."'");
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='' WHERE `preference_id`='".PREF_MAILER_AUTHUSER_ID."'");
                                $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='' WHERE `preference_id`='".PREF_MAILER_AUTHPASS_ID."'");
                                break;
                            case 'smtp':
                                if (strlen(trim($_POST['smtp_servers'])) > 1) {
                                    $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='smtp' WHERE `preference_id`='".$preference_id."'");
                                    $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='".checkslashes(trim($_POST['smtp_servers']))."' WHERE `preference_id`='".PREF_MAILER_BY_VALUE."'");
                                    $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='".(((!empty($_POST['smtp_keep_alive'])) && (trim($_POST['smtp_keep_alive']) != 'no')) ? 'yes' : 'no')."' WHERE `preference_id`='".PREF_MAILER_SMTP_KALIVE."'");
                                    if ($_POST['smtp_authentication'] == 'true') {
                                        $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='true' WHERE `preference_id`='".PREF_MAILER_AUTH_ID."'");
                                        $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='".checkslashes(trim($_POST['auth_username']))."' WHERE `preference_id`='".PREF_MAILER_AUTHUSER_ID."'");
                                        $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='".checkslashes(trim($_POST['auth_password']))."' WHERE `preference_id`='".PREF_MAILER_AUTHPASS_ID."'");
                                    } else {
                                        $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='false' WHERE `preference_id`='".PREF_MAILER_AUTH_ID."'");
                                        $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='' WHERE `preference_id`='".PREF_MAILER_AUTHUSER_ID."'");
                                        $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='' WHERE `preference_id`='".PREF_MAILER_AUTHPASS_ID."'");
                                    }
                                } else {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'If you select to send mail using SMTP you must provide a valid SMTP server to send mail through.';
                                }
                                break;
                            case 'sendmail':
                                if (strlen(trim($_POST['sendmail_path'])) > 1) {
                                    $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='sendmail' WHERE `preference_id`='".$preference_id."'");
                                    $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='yes' WHERE `preference_id`='".PREF_MAILER_SMTP_KALIVE."'");
                                    $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='false' WHERE `preference_id`='".PREF_MAILER_AUTH_ID."'");
                                    $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='' WHERE `preference_id`='".PREF_MAILER_AUTHUSER_ID."'");
                                    $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='' WHERE `preference_id`='".PREF_MAILER_AUTHPASS_ID."'");
                                    $db->Execute('UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='".checkslashes(trim($_POST['sendmail_path']))."' WHERE `preference_id`='".PREF_MAILER_BY_VALUE."'");
                                } else {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'If you select to send mail using Sendmail you must provide a valid path to the Sendmail executable.';
                                }
                                break;
                        }
                        break;
                    case PREF_MAILER_BY_VALUE:
                        $skip_query = true;
                        break;
                    case PREF_MAILER_AUTH_ID:
                        $skip_query = true;
                        break;
                    case PREF_MAILER_AUTHUSER_ID:
                        $skip_query = true;
                        break;
                    case PREF_MAILER_AUTHPASS_ID:
                        $skip_query = true;
                        break;
                    default:
                        $ERROR++;
                        $ERRORSTR[] = 'Unrecognized preference ID ['.$preference_id.'] with a value of ['.$preference_value.'] was passed to preferences updater.';
                        $skip_query = true;
                        break;
                }

                if (!$skip_query) {
                    $query = 'UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='".checkslashes($preference_value)."' WHERE `preference_id`='".$preference_id."'";
                    if (!$db->Execute($query)) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Unable to update preference ID '.$preference_id.'. Please check your error log for more information.';
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tPreference ID ".$preference_id.' was not updated. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    }
                }
            }

            if (!reload_configuration()) {
                ++$ERROR;
                $ERRORSTR[] = "Unable to reload your configuration into your session. Please check your error log for more information, but you'll have to close and then re-open your web-browser to load the changed settings.";

                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to reload the settings from the database. The load_settings() function returned false.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            } else {
                if (!$ERROR) {
                    ++$SUCCESS;
                    $SUCCESSSTR[] = 'You have successfully updated and reloaded the ListMessenger E-Mail Configuration.';
                }
            }
        }

        if (!is_dir($_SESSION['config'][PREF_PUBLIC_PATH])) {
            ++$NOTICE;
            $NOTICESTR[] = 'Your Public Folder Directory Path does not exist at '.$_SESSION['config'][PREF_PUBLIC_PATH].' or is not readable by PHP. Please log into the ListMessenger Program Preferences and update the Public Folder Directory Path.';
        }
        ?>
		<h1>E-Mail Configuration</h1>
		<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /> <a href="index.php?section=preferences">Preferences and Configuration</a>&nbsp;
		<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /> E-Mail Configuration
		<br /><br />
		<?php echo ($ERROR) ? display_error($ERRORSTR) : ''; ?>
		<?php echo ($NOTICE) ? display_notice($NOTICESTR) : ''; ?>
		<?php echo ($SUCCESS) ? display_success($SUCCESSSTR) : ''; ?>
		<form action="index.php?section=preferences&type=email" method="post">
		<fieldset>
		<legend class="page-subheading">Message Options</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('Character Wordwrap', '<strong><em>Character Wordwrap</em></strong><br />This is the maximum number of characters per line that will exist when you send out your message.<br /><br /><strong>Important:</strong><br />There are a lot of spam filters that will increase the spam score if lines are longer than 76 characters; therefore, it is suggested to not increase this number higher then 76.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 150px" name="preferences[<?php echo PREF_WORDWRAP; ?>]" value="<?php echo html_encode($_SESSION['config'][PREF_WORDWRAP]); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Auto-Add Unsubscribe Link', '<strong><em>Auto-Add Unsubscribe Link</em></strong><br />Select whether or not you wish to have ListMessenger automatically add an unsubscribe link to every newsletter that you send out.', false); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_ADD_UNSUB_LINK; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][PREF_ADD_UNSUB_LINK] == 'yes') ? ' selected="selected"' : ''; ?>>Yes</option>
					<option value="no"<?php echo ($_SESSION['config'][PREF_ADD_UNSUB_LINK] == 'no') ? ' selected="selected"' : ''; ?>>No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Use Group ID in Unsubscribe Link', '<strong><em>Use Group ID in Unsubscribe Link</em></strong><br />Choose whether or not you wish to include the group ID variable in your unsubscribe links.<br /><br />If you do include the group ID in your unsubscribe link, then the specified group will already be selected as they go to unsubscribe.<br /><br />If you do not include the group ID, your subscribers will be able to choose which group or groups they wish to be removed from as they go to unsubscribe.', false); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_ADD_UNSUB_GROUP; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][PREF_ADD_UNSUB_GROUP] == 'yes') ? ' selected="selected"' : ''; ?>>Yes</option>
					<option value="no"<?php echo ($_SESSION['config'][PREF_ADD_UNSUB_GROUP] == 'no') ? ' selected="selected"' : ''; ?>>No</option>
					</select>
				</td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<br />
		<fieldset>
		<legend class="page-subheading">Sending Options</legend>
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 40%" /> 
			<col style="width: 60%" />
		</colgroup>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('Messages Per Refresh', '<strong><em>Messages Per Refresh</em></strong><br />This is the number of messages that ListMessenger will send before it pauses and then refreshes. The default setting is 50 messages and this number should be increased or decreased based on the load of your server.<br /><br /><strong>Important:</strong><br />ListMessenger works by delivering X number of messages, then pausing X number of seconds. This process is called a cycle and is used to help you prevent your mail server from being overwhelmed and to prevent PHP timeouts.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 50px" name="preferences[<?php echo PREF_MSG_PER_REFRESH; ?>]" value="<?php echo (int) $_SESSION['config'][PREF_MSG_PER_REFRESH]; ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Pause Between Refreshes', '<strong><em>Pause Between Refreshes</em></strong><br />This is the number of seconds that ListMessenger will pause between refreshes. The default setting is 1 second; however, you are free to increase or decrease this based on the load of your server.<br /><br /><strong>Important:</strong><br />ListMessenger has the ability to pause between refreshes to allow you to prevent your mail server from being overwhelmed and to prevent PHP timeouts.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 50px" name="preferences[<?php echo PREF_PAUSE_BETWEEN; ?>]" value="<?php echo (int) $_SESSION['config'][PREF_PAUSE_BETWEEN]; ?>" onkeypress="return handleEnter(this, event)" /> <span class="small-grey">second(s)</span></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Queue Timeout', '<strong><em>Queue Timeout</em></strong><br />This is the number of seconds that ListMessenger will consider your sending queue still active. After this number, ListMessenger will assume that your sending process has stalled at it will allow you to resume the stalled send.<br /><br /><strong>Important:</strong><br />This number <em>must</em> be higher than your &quot;Pause Between Refreshes&quot; number or ListMessenger will consider your active sending queue stalled when it is not.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 50px" name="preferences[<?php echo PREF_QUEUE_TIMEOUT; ?>]" value="<?php echo (int) $_SESSION['config'][PREF_QUEUE_TIMEOUT]; ?>" onkeypress="return handleEnter(this, event)" /> <span class="small-grey">second(s)</span></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Message Line Endings', '<strong><em>Message Line Endings</em></strong><br />Different SMTP servers and even different delivery methods can require different <a href="http://www.wikipedia.org/wiki/Newline">new line</a> characters in order to accept messages for delivery.<br /><br />This can be a bit frustrating to figure out for your particular environment; however, most delivery methods seem to accept the default LF option.<br /><br />If you are unable to send e-mail using ListMessenger you can try the different options to see if you can find one that works for you.', false); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_MAILER_LE; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="n"<?php echo ($_SESSION['config'][PREF_MAILER_LE] == 'n') ? ' selected="selected"' : ''; ?>>LF (\n)</option>
					<option value="rn"<?php echo ($_SESSION['config'][PREF_MAILER_LE] == 'rn') ? ' selected="selected"' : ''; ?>>CR+LF (\r\n)</option>
					<option value="r"<?php echo ($_SESSION['config'][PREF_MAILER_LE] == 'r') ? ' selected="selected"' : ''; ?>>CR (\r)</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('E-Mail Address Format', '<strong><em>E-Mail Address Format</em></strong><br />Different operating systems and even different delivery methods can require e-mail addresses be formatted in different ways when sending messages, especially <strong>Windows Server and IIS</strong>.<br /><br />ListMessenger allows you to choose whether or not to include the recipients name in the e-mail address formatting when sending messages.<br /><br /><strong>Important:</strong> In most cases we strongly advise keeping the default setting of &quot;Fullname &lt;email@address.org&gt;&quot; as this is the RFC 2822 standard format; however, on some servers this will just not work so you will need to the more basic format.', false); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_MAILER_INC_NAME; ?>]" onkeypress="return handleEnter(this, event)">
					<option value="yes"<?php echo ($_SESSION['config'][PREF_MAILER_INC_NAME] == 'yes') ? ' selected="selected"' : ''; ?>>&quot;Fullname&quot; &lt;email@address.org&gt;</option>
					<option value="no"<?php echo ($_SESSION['config'][PREF_MAILER_INC_NAME] == 'no') ? ' selected="selected"' : ''; ?>>email@address.org</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Message Delivery Method', "<strong><em>Message Delivery Method</em></strong><br />ListMessenger provides you with the option of choosing what method you use to deliver your messages to your subscribers.<br /><br /><strong>PHP mail() [basic]</strong> (default) <br />Uses PHP's build in mail() function, and it does so <em>without</em> setting the Return-Path (5th parameter) for greatest compatibility.<br /><br /><strong>PHP mail() [advanced]</strong><br />Uses PHP's built in mail() function and attemptes to set the Return-Path (5th parameter).<br /><br /><strong>SMTP Server</strong><br />Uses the SMTP server you specify to send messages.<br /><br /><strong>Sendmail</strong><br />Uses Unix / Linux Sendmail wrapper to send messages.<br /><br />", true); ?></td>
				<td>
					<select style="width: 175px" name="preferences[<?php echo PREF_MAILER_BY_ID; ?>]" onchange="sending_delivery_options(this.options[this.selectedIndex].value);" onkeypress="return handleEnter(this, event);">
					<option value="mail"<?php echo ($_SESSION['config'][PREF_MAILER_BY_ID] == 'mail') ? ' selected="selected"' : ''; ?>>PHP mail() [basic]</option>
					<option value="mailadvanced"<?php echo ($_SESSION['config'][PREF_MAILER_BY_ID] == 'mailadvanced') ? ' selected="selected"' : ''; ?>>PHP mail() [advanced]</option>
					<option value="smtp"<?php echo ($_SESSION['config'][PREF_MAILER_BY_ID] == 'smtp') ? ' selected="selected"' : ''; ?>>SMTP Server</option>
					<option value="sendmail"<?php echo ($_SESSION['config'][PREF_MAILER_BY_ID] == 'sendmail') ? ' selected="selected"' : ''; ?>>Sendmail</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="padding: 10px">
					<div id="toggle-smtp_options" style="display: none">
						<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
						<colgroup>
							<col style="width: 40%" /> 
							<col style="width: 60%" />
						</colgroup>
						<tbody>
							<tr>
								<td><?php echo create_tooltip('SMTP Server Address', '<strong><em>SMTP Server Address</em></strong><br />Please specify the SMTP server, or servers that you wish to send mail through. You can specify more than one mail server by seperating them with a semi-colon.<br /><br />You can also specify an SMTP server port by following your server name with a colon and the port number:<br /><br /><strong>SMTP Server Examples:</strong><ol><li><tt>localhost</tt></li><li><tt>mail.yourdomain.com:25</tt></li><li><tt>ssl://mail.yourdomain.com:465</tt></li></ol>', true); ?></td>
								<td>
									<input type="text" class="text-box" style="width: 100%" id="smtp_servers" name="smtp_servers" value="<?php echo ($_SESSION['config'][PREF_MAILER_BY_ID] == 'smtp') ? html_encode($_SESSION['config'][PREF_MAILER_BY_VALUE]) : ''; ?>" onkeypress="return handleEnter(this, event)" />
								</td>
							</tr>
							<tr>
								<td><?php echo create_tooltip('SMTP Keep Alive', '<strong><em>SMTP Keep Alive</em></strong><br />If you would like ListMessenger to connect then disconnect to your SMTP server for every e-mail that is sent you can disable SMTP Keep Alive here.<br /><br />Please be advised that disabling this setting is not recommended unless you have a specific reason for doing so.', false); ?></td>
								<td>
									<select style="width: 175px" id="preferences[<?php echo PREF_MAILER_SMTP_KALIVE; ?>]" name="smtp_keep_alive" onkeypress="return handleEnter(this, event)">
										<option value="yes"<?php echo ($_SESSION['config'][PREF_MAILER_SMTP_KALIVE] == 'yes') ? ' selected="selected"' : ''; ?>>Enabled</option>
										<option value="no"<?php echo ($_SESSION['config'][PREF_MAILER_SMTP_KALIVE] == 'no') ? ' selected="selected"' : ''; ?>>Disabled</option>
									</select>
								</td>
							</tr>
							<tr>
								<td><?php echo create_tooltip('Enable SMTP Authentication', '<strong><em>Enable SMTP Authentication</em></strong><br />Chances are you will be required to authenticate as a user on the remote mail server. If you are required to do this, set this value to yes.', false); ?></td>
								<td>
									<select style="width: 175px" id="preferences[<?php echo PREF_MAILER_AUTH_ID; ?>]" name="smtp_authentication" onchange="sending_delivery_options('smtp', this.options[this.selectedIndex].value);" onkeypress="return handleEnter(this, event);">
										<option value="true"<?php echo ($_SESSION['config'][PREF_MAILER_AUTH_ID] == 'true') ? ' selected="selected"' : ''; ?>>Yes</option>
										<option value="false"<?php echo ($_SESSION['config'][PREF_MAILER_AUTH_ID] == 'false') ? ' selected="selected"' : ''; ?>>No</option>
									</select>
								</td>
							</tr>
							<tr id="toggle-smtp_username" style="display: none">
								<td><?php echo create_tooltip('SMTP Username', '<strong><em>SMTP Username</em></strong><br />This is the username that ListMessenger will use to authenticate you at the SMTP server you provided.', true); ?></td>
								<td><input type="text" class="text-box" style="width: 150px" name="auth_username" value="<?php echo html_encode($_SESSION['config'][PREF_MAILER_AUTHUSER_ID]); ?>" onkeypress="return handleEnter(this, event)" /></td>
							</tr>
							<tr id="toggle-smtp_password" style="display: none">
								<td><?php echo create_tooltip('SMTP Password', '<strong><em>SMTP Password</em></strong><br />This is the password that ListMessenger will use to authenticate you at the SMTP server you provided.', true); ?></td>
								<td><input type="password" class="text-box" style="width: 150px" name="auth_password" value="<?php echo html_encode($_SESSION['config'][PREF_MAILER_AUTHPASS_ID]); ?>" onkeypress="return handleEnter(this, event)" /></td>
							</tr>
						</tbody>
						</table>
					</div>
					<div id="toggle-sendmail_options" style="display: none">
						<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
						<colgroup>
							<col style="width: 40%" /> 
							<col style="width: 60%" />
						</colgroup>
						<tbody>						
							<tr>
								<td><?php echo create_tooltip('Path to Sendmail', '<strong><em>Path to Sendmail</em></strong><br />Please specify the full path to your Sendmail executable as well as any switches you would like ListMessenger to pass to sendmail.<br /><br /><strong>Example:</strong><br />/usr/sbin/sendmail', true); ?></td>
								<td><input type="text" class="text-box" style="width: 100%" id="sendmail_path" name="sendmail_path" value="<?php echo ($_SESSION['config'][PREF_MAILER_BY_ID] == 'sendmail') ? $_SESSION['config'][PREF_MAILER_BY_VALUE] : ''; ?>" onkeypress="return handleEnter(this, event)" /></td>
							</tr>
						</tbody>
						</table>
					</div>
				</td>
			</tr>
		</tbody>
		</table>
		</fieldset>

		<div style="text-align: right; border-top: 1px #333333 dotted; margin: 15px 0px 15px 0px; padding-top: 10px">
			<input type="button" class="button" value="Close" onclick="window.location='index.php?section=preferences'" />&nbsp;
			<input type="submit" name="save" class="button" value="Save" />
		</div>
		</form>
		<?php
        $ONLOAD[] = "sending_delivery_options('".$_SESSION['config'][PREF_MAILER_BY_ID]."', '".$_SESSION['config'][PREF_MAILER_AUTH_ID]."')";
        break;
    default:
        ?>
		<h1>Preferences and Configuration</h1>
		<table style="width: 100%" cellspacing="3" cellpadding="1" border="0">
		<tr>
			<td style="text-align: center; vertical-align: top"><a href="index.php?section=preferences&type=program"><img src="images/icon-preferences-sm.gif" width="36" height="36" alt="Program Preferences Icon" title="ListMessenger Program Preferences" border="0"></a></td>
			<td>
				<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /><a href="index.php?section=preferences&type=program" class="preferences-title">Program Preferences</a><br />
				These are the main program preferences that allow you set things like the ListMessenger administrator username and password, as well as directory paths, urls, date formats, etc.
			</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>

		<tr>
			<td style="text-align: center; vertical-align: top"><a href="index.php?section=preferences&type=enduser"><img src="images/icon-preferences-sm.gif" width="36" height="36" alt="End-User Preferences Icon" title="ListMessenger End-User Preferences" border="0"></a></td>
			<td>
				<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /><a href="index.php?section=preferences&type=enduser" class="preferences-title">End-User Preferences</a><br />
				The end-user preferences allow you to modify settings that are directed towards your subscribers experience in the system; such as the default display language, CAPTCHA settings, subscriber notifications, etc.
			</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>

		<tr>
			<td style="text-align: center; vertical-align: top"><a href="index.php?section=preferences&type=blacklist"><img src="images/icon-preferences-sm.gif" width="36" height="36" alt="ListMessenger Blacklist" title="ListMessenger Blacklist" border="0"></a></td>
			<td>
				<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /><a href="index.php?section=preferences&type=blacklist" class="preferences-title">ListMessenger Blacklist</a><br />
				The ListMessenger blacklist allows you to control e-mail accounts that are not allowed to subscribe to your mailing lists, and IP addresses that cannot connect to this system.
			</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>

		<tr>
			<td style="text-align: center; vertical-align: top"><a href="index.php?section=preferences&type=email"><img src="images/icon-preferences-sm.gif" width="36" height="36" alt="E-Mail Configuration Icon" title="ListMessenger E-Mail Configuration" border="0"></a></td>
			<td>
				<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2" style="vertical-align: middle" alt="" title="" /><a href="index.php?section=preferences&type=email" class="preferences-title">E-Mail Configuration</a><br />
				The e-mail configuration settings allow you to change how ListMessenger delivers e-mail to your subscribers. You can change the delivery method, refresh speed, pause duration, etc.
			</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		</table>
		<?php
    break;
}
