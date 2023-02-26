<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */
if (!defined('IN_SETUP')) {
    exit;
}

$PAGE = (int) ((!empty($_GET['p'])) ? trim($_GET['p']) : 1);

switch ($PAGE) {
    case 2:
        if (empty($_GET['refresh'])) {
            if (trim($_POST['npassword1']) != '') {
                if (trim($_POST['npassword2']) != '') {
                    if (trim($_POST['npassword1']) == trim($_POST['npassword2'])) {
                        if (strlen(trim($_POST['npassword1'])) > 5) {
                            $_POST['preferences'][PREF_ADMPASS_ID] = md5(trim($_POST['npassword1']));
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'Your password must be at least five (5) characters long in order to be used. Please re-enter your password.';
                        }
                    } else {
                        ++$ERROR;
                        $ERRORSTR[] = 'The passwords that you have entered do not match. Please re-enter your password.';
                    }
                } else {
                    ++$ERROR;
                    $ERRORSTR[] = 'Please be sure you re-enter your password in the &quot;Retype Password:&quot; text box.';
                }
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'You did not enter your ListMessenger Password. Please enter a password that you will use to log into ListMessenger.';
            }

            if (!$ERROR) {
                if (!empty($_POST['preferences']) && is_array($_POST['preferences']) && (count($_POST['preferences']) > 0)) {
                    foreach ($_POST['preferences'] as $preference_id => $preference_value) {
                        switch ($preference_id) {
                            case PREF_ADMUSER_ID:
                                if (strlen(trim($preference_value)) < 5) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'Please ensure that the ListMessenger username is longer than five (5) characters in length.';
                                }
                                break;
                            case PREF_ADMPASS_ID:
                                // Already checked above.
                                break;
                            case PREF_FRMNAME_ID:
                                if (strlen(trim($preference_value)) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'Please ensure that you enter the From Name into setup. If you are unsure of what this is, try clicking the field title to display a tooltip!';
                                }
                                break;
                            case PREF_FRMEMAL_ID:
                                if (!valid_address($preference_value)) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'Please ensure that the From E-Mail Address is a valid e-mail address. If you are unsure of what this is, try clicking the field title to display a tooltip!';
                                }
                                break;
                            case PREF_PROPATH_ID:
                                if (strlen(trim($preference_value)) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'Please ensure that you enter the ListMessenger Directory Path into setup. If you are unsure of what this is, try clicking the field title to display a tooltip!';
                                } else {
                                    if (!is_dir($preference_value)) {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'The ListMessenger Directory Path you have entered into setup does not seem to be valid or is not readable by PHP. Please ensure that this directory is accessible and readable by PHP. Maybe try chmodding the ListMessenger directory to 775. If you are unsure of what this is, try clicking the field title to display a tooltip!';
                                    } else {
                                        if (substr($preference_value, -1) != '/') {
                                            $_POST['preferences'][$preference_id] .= '/';
                                        }
                                    }
                                }
                                break;
                            case PREF_PROGURL_ID:
                                if (substr($preference_value, -1) != '/') {
                                    $_POST['preferences'][$preference_id] .= '/';
                                }
                                break;
                            default:
                                $ERROR++;
                                $ERRORSTR[] = 'Unrecognized preference ID ['.$preference_id.'] with a value of ['.$preference_value.'] was passed to the installer.';
                                break;
                        }
                    }

                    if ($ERROR) {
                        $PAGE = 2;
                    } else {
                        if ($LMDATABASE['new'] != '') {
                            $search = [];
                            $replace = [];
                            $lmdb = $LMDATABASE['new'];

                            $_POST['preferences'][PREF_RPYEMAL_ID] = $_POST['preferences'][PREF_FRMEMAL_ID];
                            $_POST['preferences'][PREF_ABUEMAL_ID] = $_POST['preferences'][PREF_FRMEMAL_ID];
                            $_POST['preferences'][PREF_ERREMAL_ID] = $_POST['preferences'][PREF_FRMEMAL_ID];
                            $_POST['preferences'][PREF_ADMEMAL_ID] = $_POST['preferences'][PREF_FRMEMAL_ID];

                            $_POST['preferences'][REG_SERIAL] = '';
                            $_POST['preferences'][REG_DOMAIN] = $_POST['preferences'][PREF_PROGURL_ID];
                            $_POST['preferences'][REG_NAME] = $_POST['preferences'][PREF_FRMNAME_ID];
                            $_POST['preferences'][REG_EMAIL] = $_POST['preferences'][PREF_FRMEMAL_ID];

                            foreach ($_POST['preferences'] as $preference_id => $preference_value) {
                                $id = count($search);
                                $search[$id] = '%preferences['.$preference_id.']%';
                                $replace[$id] = checkslashes(trim($preference_value));
                            }

                            $id = count($search);
                            $search[$id] = '%TABLES_PREFIX%';
                            $replace[$id] = TABLES_PREFIX;

                            $id = count($search);
                            $search[$id] = '%TABLES_ENGINE%';
                            $replace[$id] = (defined('TABLES_ENGINE') ? TABLES_ENGINE : 'MyISAM');

                            $lmdb = str_replace($search, $replace, $lmdb);
                            $lmdb = str_replace("\r", "\n", $lmdb);
                            $lmdb = trim(str_replace("\n\n", "\n", $lmdb));
                            $queries = explode("\n", $lmdb);

                            foreach ($queries as $query) {
                                if ($query != '') {
                                    if (!$db->Execute(trim($query))) {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'Unable to execute query. Database server said: '.$db->ErrorMsg();
                                        $PAGE = 2;
                                    }
                                }
                            }

                            ++$SUCCESS;
                            $SUCCESSSTR[] = 'You have successfully installed ListMessenger. Thank you very much and we hope you enjoy our product!';
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'It appears as though the ListMessenger database embedded into this file is corrupt or non-existent. Please try unpacking the ListMessenger distribution again.';
                            $PAGE = 2;
                        }
                    }
                } else {
                    ++$ERROR;
                    $ERRORSTR[] = 'Your preferences do not appear to have been submitted using a http post. Please restart setup and ensure you follow the page by page instructions.';
                    $PAGE = 2;
                }
            } else {
                $PAGE = 2;
            }
        }
        break;
    case 1:
    default:
        // No error checking required.
        break;
}

switch ($PAGE) {
    case 2:
        $PASSED = true;
        ?>
		<?php echo ($ERROR) ? display_error($ERRORSTR) : ''; ?>
		<?php echo ($NOTICE) ? display_notice($NOTICESTR) : ''; ?>
		<?php echo ($SUCCESS) ? display_success($SUCCESSSTR) : ''; ?>
		<h2>Installation Successful</h2>
		You have successfully installed ListMessenger <?php echo VERSION_TYPE.' '.VERSION_INFO; ?> on your website. One final step is setting up a few directory permissions so as ListMessenger is able to read and write some important data such as backups, restores, imports, exports, etc.
		<br /><br />
		<h2>Directory Permissions</h2>
		<ul class="setup">
		<?php
        if (!is_writable($LMDIRECTORY.'private/backups/')) {
            $PASSED = false;
            echo "<li class=\"error-message\">\n";
            echo "	ListMessenger Directory: private/backups is <strong style=\"color: #CC0000\">not writable</strong>.\n";
            echo '	<div class="setup-error-text" style="font-family: monospace; font-size: 10px; margin-top: 5px">chmod 777 '.$LMDIRECTORY.'private/backups/</div>';
            echo "</li>\n";
        } else {
            echo "<li class=\"success-message\">\n";
            echo "	ListMessenger Directory: private/backups is <strong style=\"color: #669900\">writable</strong>.\n";
            echo "</li>\n";
        }

        if (!is_writable($LMDIRECTORY.'private/logs/')) {
            $PASSED = false;
            echo "<li class=\"error-message\">\n";
            echo "	ListMessenger Directory: private/logs is <strong style=\"color: #CC0000\">not writable</strong>.\n";
            echo '	<div class="setup-error-text" style="font-family: monospace; font-size: 10px; margin-top: 5px">chmod 777 '.$LMDIRECTORY.'private/logs/</div>';
            echo "</li>\n";
        } else {
            echo "<li class=\"success-message\">\n";
            echo "	ListMessenger Directory: private/logs is <strong style=\"color: #669900\">writable</strong>.\n";
            echo "</li>\n";
        }

        if (!is_writable($LMDIRECTORY.'private/tmp/')) {
            $PASSED = false;
            echo "<li class=\"error-message\">\n";
            echo "	ListMessenger Directory: private/tmp is <strong style=\"color: #CC0000\">not writable</strong>.\n";
            echo '	<div class="setup-error-text" style="font-family: monospace; font-size: 10px; margin-top: 5px">chmod 777 '.$LMDIRECTORY.'private/tmp/</div>';
            echo "</li>\n";
        } else {
            echo "<li class=\"success-message\">\n";
            echo "	ListMessenger Directory: private/tmp is <strong style=\"color: #669900\">writable</strong>.\n";
            echo "</li>\n";
        }

        if (!is_writable($LMDIRECTORY.'public/files/')) {
            $PASSED = false;
            echo "<li class=\"error-message\">\n";
            echo "	ListMessenger Directory: public/files is <strong style=\"color: #CC0000\">not writable</strong>.\n";
            echo '	<div class="setup-error-text" style="font-family: monospace; font-size: 10px; margin-top: 5px">chmod 777 '.$LMDIRECTORY.'public/files/</div>';
            echo "</li>\n";
        } else {
            echo "<li class=\"success-message\">\n";
            echo "	ListMessenger Directory: public/files is <strong style=\"color: #669900\">writable</strong>.\n";
            echo "</li>\n";
        }

        if (!is_writable($LMDIRECTORY.'public/images/')) {
            $PASSED = false;
            echo "<li class=\"error-message\">\n";
            echo "	ListMessenger Directory: public/images is <strong style=\"color: #CC0000\">not writable</strong>.\n";
            echo '	<div class="setup-error-text" style="font-family: monospace; font-size: 10px; margin-top: 5px">chmod 777 '.$LMDIRECTORY.'public/images/</div>';
            echo "</li>\n";
        } else {
            echo "<li class=\"success-message\">\n";
            echo "	ListMessenger Directory: public/images is <strong style=\"color: #669900\">writable</strong>.\n";
            echo "</li>\n";
        }
        ?>
		</ul>

		<form action="./setup.php?step=5&type=new&p=3&refresh" method="get">
		<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
		<tr>
			<td colspan="2" style="text-align: right; border-top: 2px #CCC solid; padding-top: 5px">
				<?php
                if ($PASSED) {
                    echo "<input type=\"button\" value=\"Completed\" class=\"button\" onclick=\"window.location='./index.php'\" />\n";
                } else {
                    echo "<input type=\"button\" value=\"Refresh\" class=\"button\" onclick=\"window.location='./setup.php?step=5&type=new&p=3&refresh'\" />\n";
                    echo "<input type=\"button\" value=\"Skip\" class=\"button\" onclick=\"window.location='./index.php'\" />\n";
                }
        ?>
			</td>
		</tr>
		</table>
		</form>
		<?php
    break;
    case 1:
    default:
        ?>
		<?php echo ($ERROR) ? display_error($ERRORSTR) : ''; ?>
		<?php echo ($NOTICE) ? display_notice($NOTICESTR) : ''; ?>
		<?php echo ($SUCCESS) ? display_success($SUCCESSSTR) : ''; ?>
		<h2>Preferences and Registration</h2>
		<div class="generic-message">
			The following basic preferences are required to be set during the installation. The default name and e-mail address were retrieved from your licence key, and you are free to change any of these preferences later by logging into ListMessenger and clicking Control Panel &gt; Preferences &gt; Program Preferences.
		</div>

		<form action="setup.php?step=5&type=new&p=2" method="post">
		<fieldset>
			<legend class="page-subheading">Login Information</legend>
			<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
			<colgroup>
				<col style="width: 40%" /> 
				<col style="width: 60%" />
			</colgroup>
			<tbody>
				<tr>
					<td><?php echo create_tooltip('ListMessenger Username', '<strong><em>ListMessenger Username</em></strong><br />This username is what you will enter on the ListMessenger login page to access the ListMessenger interface.<br /><br /><strong>Important:</strong><br />If you forget this username, it can be retrieved using PHPMyAdmin or any other database management application and look in the preferences table.', true); ?></td>
					<td><input type="text" style="width: 50%" name="preferences[<?php echo PREF_ADMUSER_ID; ?>]" value="<?php echo (!empty($_POST['preferences'][PREF_ADMUSER_ID])) ? checkslashes(trim($_POST['preferences'][PREF_ADMUSER_ID]), 1) : ''; ?>" autocomplete="off" /></td>
				</tr>
				<tr>
					<td><?php echo create_tooltip('ListMessenger Password', '<strong><em>ListMessenger Password</em></strong><br />This is the password that you will use to log into the ListMessenger interface.<br /><br /><strong>Important:</strong><br />If you forget this password, it can be retrieved using PHPMyAdmin or any other database management application and look in the preferences table.', true); ?></td>
					<td><input type="password" style="width: 50%" name="npassword1" value="" autocomplete="off" /></td>
				</tr>
				<tr>
					<td><?php echo create_tooltip('Retype Password', '<strong><em>ListMessenger Password</em></strong><br />This is the password that you will use to log into the ListMessenger interface.<br /><br /><strong>Important:</strong><br />If you forget this password, it can be retrieved using PHPMyAdmin or any other database management application and look in the preferences table.', true); ?></td>
					<td><input type="password" style="width: 50%" name="npassword2" value="" autocomplete="off" /></td>
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
					<td><?php echo create_tooltip('Your Name', '<strong><em>Your Name</em></strong><br />This is the default name that will show up in from and reply field of any e-mail client when a subscriber receives a newsletter. This would generally be your full name, company name or website title.', true); ?></td>
					<td><input type="text" style="width: 50%" name="preferences[<?php echo PREF_FRMNAME_ID; ?>]" value="<?php echo (!empty($_POST['preferences'][PREF_FRMNAME_ID])) ? $_POST['preferences'][PREF_FRMNAME_ID] : ''; ?>" autocomplete="off" /></td>
				</tr>
				<tr>
					<td><?php echo create_tooltip('Your Email Address', '<strong><em>Your Email Address</em></strong><br />This is the default email address that will show up in the from field of any e-mail client when a subscriber receives a newsletter.', true); ?></td>
					<td><input type="text" style="width: 50%" name="preferences[<?php echo PREF_FRMEMAL_ID; ?>]" value="<?php echo (!empty($_POST['preferences'][PREF_FRMEMAL_ID])) ? $_POST['preferences'][PREF_FRMEMAL_ID] : ''; ?>" autocomplete="off" /></td>
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
					<td><?php echo create_tooltip('ListMessenger Directory Path', "<strong><em>ListMessenger Directory Path</em></strong><br />This is the full directory path from root to your ListMessenger program directory. This field is <strong>not</strong> a URL, but <strong>is</strong> a directory path.<br /><br /><strong>Example:</strong><br />/home/domain.com/listmessenger/ or D:/domain.com/listmessenger/.<br /><br /><strong>Important:</strong><br />Windows users, please ensure you use forward slashes [/] to input your directory, <strong>not</strong> back slashes [\&#92;].", true); ?></td>
					<td><input type="text" style="width: 80%" name="preferences[<?php echo PREF_PROPATH_ID; ?>]" value="<?php echo (!empty($_POST['preferences'][PREF_PROPATH_ID])) ? $_POST['preferences'][PREF_PROPATH_ID] : $LMDIRECTORY; ?>" autocomplete="off" /></td>
				</tr>
				<tr>
					<td><?php echo create_tooltip('ListMessenger Program URL', '<strong><em>ListMessenger Program URL</em></strong><br />This is the full URL address to your ListMessenger directory on your web-server.<br /><br /><strong>Example:</strong><br />http://domain.com/listmessenger/', true); ?></td>
					<td><input type="text" style="width: 80%" name="preferences[<?php echo PREF_PROGURL_ID; ?>]" value="<?php echo (!empty($_POST['preferences'][PREF_PROGURL_ID])) ? $_POST['preferences'][PREF_PROGURL_ID] : ((!empty($_SERVER['HTTPS'])) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].str_replace('setup.php', '', $_SERVER['PHP_SELF']); ?>" autocomplete="off" /></td>
				</tr>
			</tbody>
			</table>
		</fieldset>

		<br />
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
		<tr>
			<td style="text-align: right; border-top: 2px #CCC solid; padding-top: 5px">
				<input type="submit" name="save" class="button" value="Proceed" />
			</td>
		</tr>
		</table>
		</form>
		<?php
    break;
}
