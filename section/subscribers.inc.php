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

$i = count($SIDEBAR);
$SIDEBAR[$i] = "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\">\n";
$SIDEBAR[$i] .= "<tr>\n";
$SIDEBAR[$i] .= "	<td><img src=\"./images/icon-man-users.gif\" width=\"16\" height=\"16\" alt=\"Add Subscriber\" title=\"Add Subscriber\" /></td>\n";
$SIDEBAR[$i] .= "	<td><a href=\"index.php?section=subscribers&action=add\">Add Subscriber</a></td>\n";
$SIDEBAR[$i] .= "</tr>\n";
$SIDEBAR[$i] .= "<tr>\n";
$SIDEBAR[$i] .= "	<td><img src=\"./images/icon-del-users.gif\" width=\"16\" height=\"16\" alt=\"Bulk Removal Tool\" title=\"Bulk Removal Tool\" /></td>\n";
$SIDEBAR[$i] .= "	<td><a href=\"index.php?section=subscribers&action=bulkremoval\" style=\"white-space: nowrap\">Bulk Removal Tool</a></td>\n";
$SIDEBAR[$i] .= "</tr>\n";
$SIDEBAR[$i] .= "<tr>\n";
$SIDEBAR[$i] .= "	<td><img src=\"./images/icon-man-groups.gif\" width=\"16\" height=\"16\" alt=\"Manage Groups\" title=\"Manage Groups\" /></td>\n";
$SIDEBAR[$i] .= "	<td><a href=\"index.php?section=manage-groups\">Manage Groups</a></td>\n";
$SIDEBAR[$i] .= "</tr>\n";
$SIDEBAR[$i] .= "<tr>\n";
$SIDEBAR[$i] .= "	<td><img src=\"./images/icon-man-fields.gif\" width=\"16\" height=\"16\" alt=\"Manage Fields\" title=\"Manage Fields\" /></td>\n";
$SIDEBAR[$i] .= "	<td><a href=\"index.php?section=manage-fields\">Manage Fields</a></td>\n";
$SIDEBAR[$i] .= "</tr>\n";
$SIDEBAR[$i] .= "<tr>\n";
$SIDEBAR[$i] .= "	<td><img src=\"./images/icon-stats.gif\" width=\"16\" height=\"16\" alt=\"Basic Subscriber Stats\" title=\"Basic Subscriber Stats\" /></td>\n";
$SIDEBAR[$i] .= "	<td><a href=\"index.php?section=statistics\">Subscriber Stats</a></td>\n";
$SIDEBAR[$i] .= "</tr>\n";
$SIDEBAR[$i] .= "</table>\n";

if ((!empty($_GET['action'])) && (trim($_GET['action']) != '')) {
    $ACTION = checkslashes($_GET['action']);
} elseif ((!empty($_POST['action'])) && (trim($_POST['action']) != '')) {
    $ACTION = checkslashes($_POST['action']);
} else {
    $ACTION = '';
}

if ((!empty($_GET['step'])) && ((int) trim($_GET['step']))) {
    $STEP = (int) trim($_GET['step']);
} elseif ((!empty($_POST['step'])) && ((int) trim($_POST['step']))) {
    $STEP = (int) trim($_POST['step']);
} else {
    $STEP = 1;
}

switch ($ACTION) {
    case 'add':
        if ($_POST) {
            if (strlen(trim($_POST['email_address'])) < 1) {
                ++$ERROR;
                $ERRORSTR[] = 'Please enter the subscribers e-mail address to continue.';
            } else {
                if (!valid_address(trim($_POST['email_address']))) {
                    ++$ERROR;
                    $ERRORSTR[] = 'The e-mail address you have entered appears to be invalid. Please check the address and try again.';
                } else {
                    if (empty($_POST['group_ids'])) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Please select a group or groups that you wish this subscriber to be located in.';
                    } else {
                        $groups = [];
                        foreach ($_POST['group_ids'] as $group_id) {
                            $query = 'SELECT `users_id` FROM `'.TABLES_PREFIX."users` WHERE `group_id`='".checkslashes($group_id)."' AND `email_address`='".checkslashes(trim($_POST['email_address']))."'";
                            $result = $db->GetRow($query);
                            if (!$result) {
                                $groups[] = $group_id;
                            }
                        }
                        if (count($groups) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The email address you have entered already exists in the group'.((count($_POST['group_ids']) != 1) ? 's' : '').' you have selected.';
                        }
                    }
                }

                /* Error check: Validation and error checking on the firstname field.
                 */
                if ((!empty($_POST['firstname'])) && ($tmp_input = clean_input($_POST['firstname'], ['emailheaders', 'notags', 'encode', 'trim']))) {
                    $_POST['firstname'] = $tmp_input;
                } else {
                    $_POST['firstname'] = '';
                }

                if (check_required('firstname') && ($_POST['firstname'] == '')) {
                    ++$ERROR;
                    $ERRORSTR[] = 'You have indicated that the <strong>firstname</strong> field is required in the <a href="index.php?section=manage-fields">Manage Fields</a> section.';
                }

                /*
                 * Error check: Validation and error checking on the lastname field.
                 */
                if ((!empty($_POST['lastname'])) && ($tmp_input = clean_input($_POST['lastname'], ['emailheaders', 'notags', 'encode', 'trim']))) {
                    $_POST['lastname'] = $tmp_input;
                } else {
                    $_POST['lastname'] = '';
                }

                if (check_required('lastname') && ($_POST['lastname'] == '')) {
                    ++$ERROR;
                    $ERRORSTR[] = 'You have indicated that the <strong>lastname</strong> field is required in the <a href="index.php?section=manage-fields">Manage Fields</a> section.';
                }

                $query = 'SELECT `field_sname`, `field_lname` FROM `'.TABLES_PREFIX."cfields` WHERE `field_req`='1' ORDER BY `field_order` ASC";
                $results = $db->GetAll($query);
                if ($results) {
                    foreach ($results as $result) {
                        if (empty($_POST['cdata'][$result['field_sname']]) || (!$_POST['cdata'][$result['field_sname']]) || !custom_data_field_value($_POST['cdata'][$result['field_sname']])) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The custom field <strong>'.html_encode($result['field_lname']).'</strong> is a required field.';
                        }
                    }
                }
            }

            if (!$ERROR) {
                require_once 'classes/lm_mailer.class.php';

                if ((!empty($_POST['confirmation'])) && ($_POST['confirmation'] == '1')) {
                    $result = users_queue($_POST['email_address'], $_POST['firstname'], $_POST['lastname'], $groups, $_POST['cdata'], 'adm-subscribe');
                    if ($result) {
                        if (file_exists($_SESSION['config'][PREF_PUBLIC_PATH].'languages/'.$_SESSION['config'][ENDUSER_LANG_ID].'.lang.php')) {
                            require_once $_SESSION['config'][PREF_PUBLIC_PATH].'languages/'.$_SESSION['config'][ENDUSER_LANG_ID].'.lang.php';
                        } elseif (file_exists($_SESSION['config'][PREF_PUBLIC_PATH].'languages/english.lang.php')) {
                            require_once $_SESSION['config'][PREF_PUBLIC_PATH].'languages/english.lang.php';

                            ++$NOTICE;
                            $NOTICESTR[] = 'Your selected language file does not exist in the public languages directory, so the English default file is being used.';
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'Your public language directory does not contain your selected language file, or the English language file. Please ensure that you have the proper language files in your public languages directory.';
                        }

                        try {
                            $mail = new LM_Mailer($_SESSION['config']);
                            $mail->AddReplyTo($_SESSION['config'][PREF_RPYEMAL_ID], $_SESSION['config'][PREF_FRMNAME_ID]);

                            $mail->Subject = $LANGUAGE_PACK['subscribe_confirmation_subject'];
                            $mail->Body = str_replace(['[name]', '[url]', '[abuse_address]', '[from]'], [checkslashes(trim($_POST['firstname']), 1), $_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_CONFIRM_FILENAME].'?id='.$result['confirm_id'].'&code='.$result['hash'], $_SESSION['config'][PREF_ABUEMAL_ID], $_SESSION['config'][PREF_FRMNAME_ID]], $LANGUAGE_PACK['subscribe_confirmation_message']);

                            if (strlen(trim($_POST['firstname'])) > 1) {
                                $senders_name = checkslashes(trim($_POST['firstname']), 1).((strlen(trim($_POST['lastname'])) > 1) ? ' '.checkslashes(trim($_POST['lastname']), 1) : '');
                            } else {
                                $senders_name = trim($_POST['email_address']);
                            }

                            $mail->ClearAllRecipients();
                            $mail->AddAddress(trim($_POST['email_address']), $senders_name);

                            if ($mail->Send()) {
                                ++$SUCCESS;
                                $SUCCESSSTR[] = 'A confirmation e-mail was successfully sent to this potential subscriber. They will be automatically added the group'.((count($groups) != 1) ? 's' : '').' if they process the confirmation within '.$_SESSION['config'][PREF_EXPIRE_CONFIRM].' days; otherwise the request will be removed from the confirmation queue.';

                                unset($_POST);
                            } else {
                                $query = 'DELETE FROM `'.TABLES_PREFIX."confirmation` WHERE `confirm_id`='".$result['confirm_id']."';";
                                if (!$db->Execute($query)) {
                                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete the failed confirmation queue request from the confirmation table. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                    }
                                }

                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send confirmation message to ".trim($_POST['email_address']).'. PHPMailer said: '.$mail->ErrorInfo."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }

                                throw new Exception('The confirmation message could not be sent, the sending engine responded with:<br /><div style="margin: 2px; padding: 3px; border: 1px #666666 solid; background-color: #EEEEEE; font-size: 12px">'.$mail->ErrorInfo."</div><br />You may want try changing the method which ListMessenger uses to send e-mail, if this way isn't working for you. You can do this in the Control Panel > Preferences > E-Mail Settings; <a href=\"index.php?section=message&action=view&id=".$_SESSION['message_details']['message_id'].'">Click here</a> to return to your message details.');
                            }
                        } catch (Exception $e) {
                            ++$ERROR;
                            $ERRORSTR[] = $e->getMessage();
                        }
                    } else {
                        ++$ERROR;
                        $ERRORSTR[] = 'The subscriber you are trying to insert into confirmation queue already exists in all of the groups you trying to insert them into.';
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to add a new subscriber to the confirmation queue. The subscriber is already present in all groups.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    }
                } else {
                    $result = users_add($_POST['email_address'], $_POST['firstname'], $_POST['lastname'], $groups, $_POST['cdata'], $_SESSION['config']);
                    if ($result) {
                        $query = 'INSERT INTO `'.TABLES_PREFIX."confirmation` VALUES (NULL, '".time()."', 'adm-subscribe', '".addslashes($_SERVER['REMOTE_ADDR'])."', '".addslashes($_SERVER['HTTP_REFERER'])."', '".addslashes($_SERVER['HTTP_USER_AGENT'])."', '".trim($_POST['email_address'])."', '".trim($_POST['firstname'])."', '".trim($_POST['lastname'])."', '".addslashes(serialize($groups))."', '".addslashes(serialize($_POST['cdata']))."', '', '0');";
                        $db->Execute($query);

                        if ($result['failed'] > 0) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Inserting the new subscriber failed for '.$result['failed'].' group'.((count($groups) != 1) ? 's' : '').'. Please check your error log for more detailed information.';
                        }
                        if ($result['semi'] > 0) {
                            ++$NOTICE;
                            $NOTICESTR[] = 'Inserting custom data for the new subscriber failed for '.$result['semi'].' field'.(($result['semi'] != 1) ? 's' : '').'. Please check your error log for more detailed information.';
                        }
                        if ($result['success'] > 0) {
                            ++$SUCCESS;
                            $SUCCESSSTR[] = 'You have successfully inserted this subscriber into '.$result['success'].' group'.((count($groups) != 1) ? 's' : '').'.';
                        }
                        unset($_POST);
                    } else {
                        ++$ERROR;
                        $ERRORSTR[] = 'The subscriber you are trying to add to the database already exists in all of the groups you trying to insert them into.';
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to add a new subscriber to the database. The subscriber is already present in all groups.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    }
                }
            }
        }
        ?>
		<h1>Add Subscriber</h1>
		<?php
        $groups = 0;
        if (groups_count(0, $groups) < 1) {
            ++$NOTICE;
            $NOTICESTR[] = 'You have not yet created any groups to place this new subscriber in. To create a new group click &quot;<a href="index.php?section=manage-groups">Manage Groups</a>&quot; then click the &quot;New Group&quot; button.';

            echo display_notice($NOTICESTR);
        } else {
            if ($ERROR) {
                echo display_error($ERRORSTR);
            }

            if ($NOTICE) {
                echo display_notice($NOTICESTR);
            }

            if ($SUCCESS) {
                echo display_success($SUCCESSSTR);
            }
            ?>
			<form action="index.php?section=subscribers&action=add" method="post">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
			<colgroup>
				<col style="width: 25%" />
				<col style="width: 75%" />
			</colgroup>
			<tfoot>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
						<input type="button" value="<?php echo ($SUCCESS) ? 'Close' : 'Cancel'; ?>" class="button" onclick="window.location='index.php'" />
						<input type="submit" value="Add Subscriber" class="button" />
					</td>
				</tr>
			</tfoot>
			<tbody>
				<tr>
					<td><?php echo create_tooltip('E-Mail Address', '<strong>Field Name: <em>E-Mail Address</em></strong><br />This is the e-mail address of the new subscriber you are adding.', true); ?></td>
					<td><input type="text" id="email_address" name="email_address" class="text-box" style="width: 200px" value="<?php echo !empty($_POST['email_address']) ? html_encode($_POST['email_address']) : ''; ?>" onkeypress="return handleEnter(this, event)" /></td>
				</tr>
				<tr>
					<td><?php echo create_tooltip('Firstname', '<strong>Field Name: <em>Firstname</em></strong><br />This is the firstname of the new subscriber you are adding.', check_required('firstname')); ?></td>
					<td><input type="text" id="firstname" name="firstname" class="text-box" style="width: 200px" value="<?php echo !empty($_POST['firstname']) ? html_encode($_POST['firstname']) : ''; ?>" onkeypress="return handleEnter(this, event)" /></td>
				</tr>
				<tr>
					<td><?php echo create_tooltip('Lastname', '<strong>Field Name: <em>Lastname</em></strong><br />This is the lastname of the new subscriber you are adding.', check_required('lastname')); ?></td>
					<td><input type="text" id="lastname" name="lastname" class="text-box" style="width: 200px" value="<?php echo !empty($_POST['lastname']) ? html_encode($_POST['lastname']) : ''; ?>" onkeypress="return handleEnter(this, event)" /></td>
				</tr>
				<tr>
					<td style="vertical-align: top"><?php echo create_tooltip('Subscriber Group', '<strong>Field Name: <em>Subscriber Group</em></strong><br />Select the group or groups you would like this new subscriber to be placed into.<br /><br />Please note you can select multiple groups by holding the CTRL button (Win) or Command button (Mac) down and clicking multiple groups.', true); ?></td>
					<td>
						<select id="group_ids" name="group_ids[]" style="width: 99%" multiple="multiple" size="7" onkeypress="return handleEnter(this, event)">
						<?php echo groups_inselect(0, (!empty($_POST['group_ids'])) ? $_POST['group_ids'] : ''); ?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<?php
                $query = 'SELECT * FROM `'.TABLES_PREFIX.'cfields` ORDER BY `field_order` ASC';
            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $result) {
                    echo "<tr>\n";
                    echo '	<td style="vertical-align: top">'.($result['field_lname'] ? create_tooltip($result['field_lname'], '<strong>Field Name: <em>'.html_encode($result['field_lname']).'</em></strong><br />Administrator created custom field, so no additional documentation exists.', (bool) $result['field_req']) : '&nbsp;')."</td>\n";
                    echo "	<td>\n";
                    switch ($result['field_type']) {
                        case 'checkbox':
                            if ($result['field_options']) {
                                $options = explode("\n", $result['field_options']);
                                foreach ($options as $key => $option) {
                                    $pieces = explode('=', $option);

                                    $checked = '';
                                    if (!empty($_POST['cdata'][$result['field_sname']])) {
                                        if (in_array($pieces[0], $_POST['cdata'][$result['field_sname']])) {
                                            $checked = ' checked="checked"';
                                        }
                                    }

                                    echo '<input type="checkbox" name="cdata['.$result['field_sname'].'][]" id="cdata_'.$result['field_sname'].'_'.$key.'" value="'.html_encode($pieces[0]).'"'.$checked.' onkeypress="return handleEnter(this, event)" /> <label for="cdata_'.$result['field_sname'].'_'.$key.'">'.html_encode($pieces[1])."</label><br />\n";
                                }
                            } else {
                                echo '<span class="small-grey">(No options in configuration to check)</span>';
                            }
                            break;
                        case 'radio':
                            if ($result['field_options']) {
                                $options = explode("\n", $result['field_options']);
                                foreach ($options as $key => $option) {
                                    $pieces = explode('=', $option);

                                    $checked = '';
                                    if (!empty($_POST['cdata'][$result['field_sname']])) {
                                        if ($pieces[0] == $_POST['cdata'][$result['field_sname']]) {
                                            $checked = ' checked="checked"';
                                        }
                                    }

                                    echo '<input type="radio" name="cdata['.$result['field_sname'].']" id="cdata_'.$result['field_sname'].'_'.$key.'" value="'.html_encode($pieces[0]).'"'.$checked.' onkeypress="return handleEnter(this, event)" /> <label for="cdata_'.$result['field_sname'].'_'.$key.'">'.html_encode($pieces[1])."</label><br />\n";
                                }
                            } else {
                                echo '<span class="small-grey">(No options in configuration to select)</span>';
                            }
                            break;
                        case 'select':
                            if ($result['field_options']) {
                                $options = explode("\n", $result['field_options']);
                                echo '<select name="cdata['.$result['field_sname'].']" id="cdata_'.$result['field_sname']."\" onkeypress=\"return handleEnter(this, event)\">\n";
                                foreach ($options as $option) {
                                    $pieces = explode('=', $option);
                                    echo '<option value="'.html_encode(trim($pieces[0])).'"'.((!empty($_POST['cdata'][$result['field_sname']]) && $_POST['cdata'][$result['field_sname']] == $pieces[0]) ? ' selected="selected"' : '').'>'.html_encode(trim($pieces[1]))."</option>\n";
                                }
                                echo "</select>\n";
                            } else {
                                echo '<span class="small-grey">(No options in configuration to select)</span>';
                            }
                            break;
                        case 'textarea':
                            echo '<textarea name="cdata['.$result['field_sname'].']" id="cdata_'.$result['field_sname'].'" style="width: 98%; height: 75px">'.(!empty($_POST['cdata'][$result['field_sname']]) ? html_encode($_POST['cdata'][$result['field_sname']]) : '')."</textarea>\n";
                            break;
                        case 'textbox':
                            echo '<input type="text" name="cdata['.$result['field_sname'].']" id="cdata_'.$result['field_sname'].'" value="'.(!empty($_POST['cdata'][$result['field_sname']]) ? html_encode($_POST['cdata'][$result['field_sname']]) : '').'"'.((strlen($result['field_length']) > 0) ? ' maxlength="'.$result['field_length'].'"' : '')." onkeypress=\"return handleEnter(this, event)\" />\n";
                            break;
                        default:
                            echo '&nbsp;';
                            break;
                    }
                    echo "	</td>\n";
                    echo "</tr>\n";
                }
            }
            ?>
				<tr>
					<td>&nbsp;</td>
					<td style="padding-top: 10px"><input type="checkbox" id="confirmation" name="confirmation" value="1"<?php echo ((!empty($_POST['confirmation'])) && ($_POST['confirmation'] == '1')) ? ' checked="checked"' : ''; ?> style="vertical-align: middle" /> <label for="confirmation" class="form-row-nreq">Send an opt-in confirmation e-mail prior to adding subscriber.</label></td>
				</tr>
			</tbody>
			</table>
			</form>
			<?php
        }
        break;	// End Add
    case 'bulkremoval':
        switch ($STEP) {
            case '2':
                $email_addresses = [];
                $group_ids = [];
                if (!empty($_POST['email_addresses'])) {
                    $tmp_addresses = str_replace("\r", "\n", trim($_POST['email_addresses']));
                    $tmp_addresses = str_replace("\n\n", "\n", trim($tmp_addresses));
                    $email_addresses = explode("\n", $tmp_addresses);
                    unset($tmp_addresses);

                    if (is_array($email_addresses) && count($email_addresses)) {
                        $email_addresses = array_unique($email_addresses);

                        foreach ($email_addresses as $key => $email_address) {
                            if (!valid_address($email_address)) {
                                unset($email_addresses[$key]);
                            }
                        }
                    }

                    if ((!empty($_POST['group_ids'])) && is_array($_POST['group_ids']) && count($_POST['group_ids'])) {
                        foreach ($_POST['group_ids'] as $group_id) {
                            if ((int) trim($group_id)) {
                                $group_ids[] = (int) trim($group_id);
                            }
                        }
                    }

                    if (is_array($email_addresses) && count($email_addresses)) {
                        if (is_array($group_ids) && count($group_ids)) {
                            $group_ids = array_unique($group_ids);

                            $query = 'DELETE FROM `'.TABLES_PREFIX."users` WHERE `email_address` IN ('".implode("', '", $email_addresses)."') AND `group_id` IN ('".implode("', '", $group_ids)."')";
                            if ((!$db->Execute($query)) || (!$deleted_users = $db->Affected_Rows())) {
                                ++$ERROR;
                                $ERRORSTR[] = 'There were no e-mail addresses removed from the system during this removal. It is possible that provided e-mail addresses do not exist in any of the selected groups.';

                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tNo e-mail addresses removed during bulk removal. Database said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }
                            }
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'You must select at least one group that you want to remove these e-mail addresses from. You can select multiple groups by pressing the CTRL (PC) / Command (Mac) while you select the group names in the list.';
                        }
                    } else {
                        ++$ERROR;
                        $ERRORSTR[] = 'There were no valid e-mail addresses provided to the removal tool. Please check your list and try again.';
                    }
                } else {
                    ++$ERROR;
                    $ERRORSTR[] = 'There were e-mail addresses provided to the removal tool. Please enter one e-mail address per line.';
                }

                if ($ERROR) {
                    echo display_error($ERRORSTR);
                } else {
                    $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

                    ++$SUCCESS;
                    $SUCCESSSTR[] = 'You have successfully removed '.$deleted_users.' subscriber'.(($deleted_users != 1) ? 's' : '').' from the system and will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

                    echo display_success($SUCCESSSTR);
                }
                break;
            case '1':
            default:
                ?>
				<h1>Bulk Removal Tool</h1>
				This tool allows you to remove e-mail addresses from the system in bulk by specifying one e-mail address per line. You can also choose to remove the provided e-mail addresses from one or more groups within the system.
				<br /><br />
				<form action="index.php?section=subscribers&action=bulkremoval&step=2" method="post">
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
				<colgroup>
					<col style="width: 25%" />
					<col style="width: 75%" />
				</colgroup>
				<tfoot>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="2" style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
							<input type="button" value="<?php echo ($SUCCESS) ? 'Close' : 'Cancel'; ?>" class="button" onclick="window.location='index.php'" />
							<input type="submit" value="Run Tool" class="button" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<td style="vertical-align: top"><label for="email_addresses" class="form-row-req">Addresses To Remove:</label><div class="small-grey" style="padding: 5px"><strong>Notice:</strong> Provide one e-mail address per line to remove.</div></td>
						<td><textarea id="email_addresses" name="email_addresses" cols="55" rows="12" style="width: 98%; height: 120px"><?php echo !empty($_POST['email_addresses']) ? html_encode($_POST['email_addresses']) : ''; ?></textarea></td>
					</tr>
					<tr>
						<td style="vertical-align: top"><label for="group_ids" class="form-row-req">Remove From:</label><div class="small-grey" style="padding: 5px"><strong>Tip:</strong> Hold the CTRL (PC) / Command (Mac) button to select multiple or all groups.</div></td>
						<td>
							<select id="group_ids" name="group_ids[]" style="width: 98%" multiple="multiple" size="7" onkeypress="return handleEnter(this, event)">
							<?php echo groups_inselect(0, (!empty($_POST['group_ids'])) ? $_POST['group_ids'] : ''); ?>
							</select>
						</td>
					</tr>
				</tbody>
				</table>
				</form>
				<?php
            break;
        }
        break;	// End Bulk Removal
    case 'copy':
        if ((!empty($_POST['subscribers'])) && is_array($_POST['subscribers']) && count($_POST['subscribers'])) {
            $subscribers = [];
            foreach ($_POST['subscribers'] as $subscriber) {
                if ($subscriber = (int) trim($subscriber)) {
                    $subscribers[] = $subscriber;
                }
            }
            if (count($subscribers)) {
                $subscribers = array_unique($subscribers);

                switch ($STEP) {
                    case '2':
                        if ((empty($_POST['group_ids'])) || (!(int) trim($_POST['group_ids']))) {
                            ++$ERROR;
                            $ERRORSTR[] = 'You must select a Copy Destination group to copy the selected subscriber'.((count($subscribers) != 1) ? 's' : '').' into.';

                            echo display_error($ERRORSTR);
                        } else {
                            $query = 'SELECT * FROM `'.TABLES_PREFIX.'groups` WHERE `groups_id` = '.$db->qstr((int) trim($_POST['group_ids']));
                            $result = $db->GetRow($query);
                            if ($result) {
                                $copied = 0;
                                $skipped = 0;
                                $exists = 0;
                                foreach ($subscribers as $users_id) {
                                    $query = 'SELECT * FROM `'.TABLES_PREFIX.'users` WHERE `users_id` = '.$db->qstr((int) $users_id);
                                    if ($result = $db->GetRow($query)) {
                                        $query = 'SELECT * FROM `'.TABLES_PREFIX.'users` WHERE `email_address` = '.$db->qstr($result['email_address']).' AND `group_id` = '.$db->qstr((int) trim($_POST['group_ids']));
                                        if (!$sresult = $db->GetRow($query)) {
                                            $query = '
													INSERT INTO `'.TABLES_PREFIX."users` (`group_id`, `signup_date`, `firstname`, `lastname`, `email_address`)
													VALUES ('".(int) trim($_POST['group_ids'])."', '".(((!empty($_POST['reset_signup'])) && ($_POST['reset_signup'] == '1')) ? time() : (int) $result['signup_date'])."', ".$db->qstr($result['firstname']).', '.$db->qstr($result['lastname']).', '.$db->qstr($result['email_address']).')';
                                            if ($db->Execute($query) && ($new_users_id = $db->Insert_Id())) {
                                                ++$copied;
                                                $query = '
														INSERT INTO `'.TABLES_PREFIX."cdata` (`user_id`, `cfield_id`, `value`)
														SELECT '".(int) $new_users_id."', `cfield_id`, `value`
														FROM `".TABLES_PREFIX.'cdata`
														WHERE `user_id` = '.$db->qstr((int) $users_id);
                                                if (!$db->Execute($query)) {
                                                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tInsert Select statement failed. Database said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                    }
                                                }
                                            } else {
                                                ++$skipped;
                                            }
                                        } else {
                                            ++$exists;
                                        }
                                    } else {
                                        ++$skipped;
                                    }
                                }

                                $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

                                if (!$copied) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'There were no subscribers that were succesfully copied. The subscribers you are trying copy may already exist in the copy destination.<br /><br />You will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

                                    echo display_error($ERRORSTR);
                                } else {
                                    ++$SUCCESS;
                                    $SUCCESSSTR[] = 'You have successfully copied '.$copied.' subscriber'.(($copied != 1) ? 's' : '').' into <strong>'.groups_information((int) trim($_POST['group_ids']), true).'</strong>.<br /><br />You will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

                                    echo display_success($SUCCESSSTR);
                                }
                            } else {
                                ++$ERROR;
                                $ERRORSTR[] = 'The Copy Destination group to selected does not exist in the database. Please ensure you select a valid ListMessenger Group.';

                                echo display_error($ERRORSTR);
                            }
                        }
                        break;
                    case '1':
                    default:
                        ?>
						<h1>Copy Subscriber<?php echo (count($_POST['subscribers']) != 1) ? 's' : ''; ?></h1>
						To complete the subscriber copy please choose a destination ListMessenger Group to copy the selected subscribers into. As a matter of convenience subscribers will not be copied if the e-mail address already exists in the destination group.
						<br /><br />
						<form action="index.php?section=subscribers&action=copy&step=2" method="post">
						<table style="width: 100%; text-align: left" cellspacing="0" cellpadding="1" border="0">
						<colgroup>
							<col style="width: 20%" />
							<col style="width: 80%" />
						</colgroup>
						<tfoot>
							<tr>
								<td colspan="2" style="border-top: 1px #333333 dotted; padding-top: 5px; text-align: right">
									<input type="button" class="button" value="Cancel" onclick="window.location='index.php'" />
									<input type="submit" class="button" value="Copy" />
								</td>
							</tr>
						</tfoot>
						<tbody>
							<tr>
								<td style="vertical-align: top"><label for="group_ids" class="form-row-req">Copy Destination:</label></td>
								<td>
									<select id="group_ids" name="group_ids" style="width: 300px" onkeypress="return handleEnter(this, event)">
									<?php echo groups_inselect(0, (!empty($_POST['group_ids'])) ? (int) $_POST['group_ids'] : ''); ?>
									</select>
								</td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td style="padding-top: 5px"><input type="checkbox" id="reset_signup" name="reset_signup" value="1" style="vertical-align: middle" /> <label for="reset_signup" class="form-row-nreq">Reset the copied subscribers sign-up date to today, once copied.</label></td>
							</tr>
							<tr>
								<td colspan="2">&nbsp;</td>
							</tr>
							<tr>
								<td style="vertical-align: top"><span class="form-row-nreq">Subscribers:</span></td>
								<td style="padding-bottom: 10px">
									<table class="tabular" cellspacing="0" cellpadding="1" border="0">
									<colgroup>
										<col style="width: 3%" />
										<col style="width: 30%" />
										<col style="width: 43%" />
										<col style="width: 24%" />
									</colgroup>
									<thead>
										<tr>
											<td>&nbsp;</td>
											<td>Name Field</td>
											<td>E-Mail Address</td>
											<td class="close">Group</td>
										</tr>
									</thead>
									<tbody>
									<?php
                                    $query = "
											SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `name_field`, b.`group_name`
											FROM `".TABLES_PREFIX.'users` AS a
											LEFT JOIN `'.TABLES_PREFIX."groups` AS b
											ON b.`groups_id` = a.`group_id`
											WHERE a.`users_id` IN ('".implode("', '", $subscribers)."')";
                        if ($results = $db->GetAll($query)) {
                            foreach ($results as $result) {
                                echo "<tr>\n";
                                echo '	<td style="white-space: nowrap"><input type="checkbox" name="subscribers[]" value="'.(int) $result['users_id']."\" checked=\"checked\" /></td>\n";
                                echo '	<td>'.(($result['name_field'] != ', ') ? html_encode(limit_chars((substr($result['name_field'], -2) == ', ') ? substr($result['name_field'], 0, strlen($result['name_field']) - 2) : $result['name_field'], 20)) : '&nbsp;')."</td>\n";
                                echo '	<td>'.html_encode(limit_chars($result['email_address'], 28))."</td>\n";
                                echo '	<td>'.html_encode(limit_chars($result['group_name'], 25))."</td>\n";
                                echo "</tr>\n";
                            }
                        }
                        ?>
									</tbody>
									</table>
								</td>
							</tr>
						</tbody>
						</table>
						</form>
						<?php
                    break;
                }
            } else {
                $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

                ++$ERROR;
                $ERRORSTR[] = 'You did not select any subscribers you wish to copy. To copy subscribers, check the checkbox beside their name and try again. You will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

                echo display_error($ERRORSTR);
            }
        } else {
            $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

            ++$ERROR;
            $ERRORSTR[] = 'You did not select any subscribers you wish to copy. To copy subscribers, check the checkbox beside their name and try again. You will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

            echo display_error($ERRORSTR);
        }
        break;	// End Copy
    case 'delete':
        $users_ids = [];

        if (!empty($_POST['subscribers']) && is_array($_POST['subscribers'])) {
            foreach ($_POST['subscribers'] as $id) {
                if ((int) $id) {
                    $users_ids[] = $id;
                }
            }

            $total_user_ids = count($users_ids);
            if ((empty($_POST['confirmed'])) || (!(int) $_POST['confirmed'])) {
                ?>
				<h1>Deleting Subscriber<?php echo ($total_user_ids != 1) ? 's' : ''; ?></h1>
				
				<?php
                echo display_notice(['Please confirm that you wish to delete the following <strong>'.$total_user_ids.' subscriber'.($total_user_ids != 1 ? 's' : '').'</strong> from ListMessenger.']);
                ?>
				<form action="index.php?section=subscribers&action=delete" method="post">
				<input type="hidden" name="confirmed" value="1" />
				<table class="tabular" cellspacing="0" cellpadding="1" border="0">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 24%" />
					<col style="width: 32%" />
					<col style="width: 21%" />
					<col style="width: 20%" />
				</colgroup>
				<thead>
					<tr>
						<td>&nbsp;</td>
						<td>Name Field</td>
						<td>E-Mail Address</td>
						<td>Signup Date</td>
						<td class="close">Group</td>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="5" style="border-top: 1px #333333 dotted; padding-top: 5px; text-align: right">
							<input type="button" value="Cancel" class="button" onclick="window.location='index.php'" />
							<input type="submit" value="Confirmed" class="button" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<?php
                    foreach ($users_ids as $users_id) {
                        $query = "SELECT `users_id`, `group_id`, `signup_date`, CONCAT_WS(', ', `lastname`, `firstname`) AS `name_field`, `email_address`, `groups_id`, `group_name`
									FROM `".TABLES_PREFIX.'users`
									LEFT JOIN `'.TABLES_PREFIX.'groups`
									ON `'.TABLES_PREFIX.'users`.`group_id`=`'.TABLES_PREFIX.'groups`.`groups_id`
									WHERE `users_id` = '.$db->qstr($users_id);
                        $result = $db->GetRow($query);
                        if ($result) {
                            echo "<tr>\n";
                            echo '	<td style="white-space: nowrap"><input type="checkbox" name="subscribers[]" value="'.$users_id."\" checked=\"checked\" /></td>\n";
                            echo '	<td>'.(($result['name_field'] != ', ') ? html_encode(limit_chars((substr($result['name_field'], -2) == ', ') ? substr($result['name_field'], 0, strlen($result['name_field']) - 2) : $result['name_field'], 20)) : '&nbsp;')."</td>\n";
                            echo '	<td>'.html_encode(limit_chars($result['email_address'], 28))."</td>\n";
                            echo '	<td>'.display_date($_SESSION['config'][PREF_DATEFORMAT], $result['signup_date'])."</td>\n";
                            echo '	<td>'.html_encode(limit_chars($result['group_name'], 25))."</td>\n";
                            echo "</tr>\n";
                        }
                    }
                ?>
				</tbody>
				</table>
				</form>
				<?php
            } else {
                if ($deleted_users = users_delete_list($_POST['subscribers'])) {
                    $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

                    ++$SUCCESS;
                    $SUCCESSSTR[] = 'You have successfully removed '.$deleted_users.' subscriber'.(($deleted_users != 1) ? 's' : '').' from the database and will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

                    echo display_success($SUCCESSSTR);
                } else {
                    header('Location: index.php');
                    exit;
                }
            }
        } else {
            $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

            ++$ERROR;
            $ERRORSTR[] = 'You did not select any subscribers you wish to delete from the database. To delete a subscriber, click the checkbox beside their name and try again. You will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

            echo display_error($ERRORSTR);
        }
        break;	// End Delete
    case 'edit':
        if ((empty($_GET['id'])) || (!(int) trim($_GET['id']))) {
            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tAttempted to edit subscriber information without providing a subscriber ID [".$_GET['id']."].\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
            header('Location: index.php');
            exit;
        }

        if ($_POST) {
            if (empty($_POST['email_address'])) {
                ++$ERROR;
                $ERRORSTR[] = 'Please enter the subscribers e-mail address to continue.';
            } else {
                if (!valid_address(trim($_POST['email_address']))) {
                    ++$ERROR;
                    $ERRORSTR[] = 'The e-mail address you have entered appears to be invalid. Please check the address and try again.';
                }

                $query = 'SELECT `field_sname`, `field_lname` FROM `'.TABLES_PREFIX."cfields` WHERE `field_req`='1' ORDER BY `field_order` ASC";
                $results = $db->GetAll($query);
                if ($results) {
                    foreach ($results as $result) {
                        if (empty($_POST['cdata'][$result['field_sname']]) || (!$_POST['cdata'][$result['field_sname']]) || !custom_data_field_value($_POST['cdata'][$result['field_sname']])) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The custom field <strong>'.html_encode($result['field_lname']).'</strong> is a required field.';
                        }
                    }
                }
            }

            if ((empty($_POST['group_id'])) || (!(int) $_POST['group_id'])) {
                ++$ERROR;
                $ERRORSTR[] = 'Please select a group to place this subscriber into or update them from.';
            }

            if (!$ERROR) {
                if (!empty($_POST['updateall']) && ($_POST['updateall'] == '1')) {
                    $query = 'SELECT `users_id`, `group_id` FROM `'.TABLES_PREFIX."users` WHERE `email_address`='".trim($_POST['oemail_address'])."'";
                    $results = $db->GetAll($query);
                    if ($results) {
                        foreach ($results as $result) {
                            $users_id = $result['users_id'];
                            $group_id = $result['group_id'];

                            if (trim($_POST['email_address']) != trim($_POST['oemail_address'])) {
                                $squery = 'SELECT `group_id` FROM `'.TABLES_PREFIX."users` WHERE `email_address`='".checkslashes(trim($_POST['email_address']))."' AND `group_id`='".checkslashes($_POST['group_id'])."'";
                                $sresult = $db->GetRow($squery);
                                if ($sresult) {
                                    ++$NOTICE;
                                    $NOTICESTR[] = 'The updated e-mail address you have entered already exists in the &quot;'.groups_information([$sresult['group_id']], true).'&quot; subscriber group. <strong>Skipping</strong>';
                                }
                            }

                            if (!$ERROR) {
                                $squery = 'UPDATE `'.TABLES_PREFIX."users` SET `firstname`='".checkslashes(trim($_POST['firstname']))."', `lastname`='".checkslashes(trim($_POST['lastname']))."', `email_address`='".checkslashes(trim($_POST['email_address']))."' WHERE `users_id`='".$users_id."';";
                                if ($db->Execute($squery)) {
                                    /*
                                     * Stores users custom field data.
                                     */
                                    custom_data_store($users_id, $_POST['cdata'] ?? [], $_SESSION['config']);

                                    ++$SUCCESS;
                                    $SUCCESSSTR[] = 'Successfully updated the account information in group '.groups_information([$group_id], true).'.';
                                } else {
                                    $group_name = groups_information([$group_id], true);

                                    ++$ERROR;
                                    $ERRORSTR[] = 'Unable to update subscriber information at this time in group '.$group_name.'. Check your error_log for more detailed information.';

                                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to update subscriber data in group ".$group_name.'. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                    }
                                }
                            }
                        }
                    } else {
                        ++$ERROR;
                        $ERRORSTR[] = 'Unable to locate the subscriber in the database that you wish to edit. Please check your error log for more details.';

                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to select the subscriber from the database. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    }
                } else {
                    if ((trim($_POST['email_address']) != trim($_POST['oemail_address'])) || (trim($_POST['group_id']) != trim($_POST['ogroup_id']))) {
                        $query = 'SELECT `group_id` FROM `'.TABLES_PREFIX."users` WHERE `email_address`='".checkslashes(trim($_POST['email_address']))."' AND `group_id`='".(int) trim($_POST['group_id'])."'";
                        $result = $db->GetRow($query);
                        if ($result) {
                            ++$ERROR;
                            $ERRORSTR[] = 'The updated e-mail address you have entered already exists in the &quot;'.groups_information($result['group_id'], true).'&quot; subscriber group.';
                        }
                    }
                    if (!$ERROR) {
                        $query = 'UPDATE `'.TABLES_PREFIX."users` SET `group_id`='".(int) trim($_POST['group_id'])."', `firstname`='".checkslashes(trim($_POST['firstname']))."', `lastname`='".checkslashes(trim($_POST['lastname']))."', `email_address`='".checkslashes(trim($_POST['email_address']))."' WHERE `users_id`='".(int) trim($_GET['id'])."';";
                        if ($db->Execute($query)) {
                            /*
                             * Stores users custom field data.
                             */
                            custom_data_store((int) trim($_GET['id']), $_POST['cdata'] ?? [], $_SESSION['config']);

                            ++$SUCCESS;
                            $SUCCESSSTR[] = 'You have successfully updated the account information for this subscriber.';
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'Unable to update subscriber information at this time. Check your error_log for more detailed information.';

                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to update subscriber data. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }
                        }
                    }
                }
            }
        }

        $query = 'SELECT * FROM `'.TABLES_PREFIX."users` WHERE `users_id`='".(int) trim($_GET['id'])."'";
        $result = $db->GetRow($query);
        if ($result) {
            ?>
			<h1>Editing Subscriber</h1>
			<?php
            if ($ERROR) {
                echo display_error($ERRORSTR);
            }
            if ($NOTICE) {
                echo display_notice($NOTICESTR);
            }
            if ($SUCCESS) {
                echo display_success($SUCCESSSTR);
            }
            ?>
			<form action="index.php?section=subscribers&action=edit&id=<?php echo html_encode(trim($_GET['id'])); ?>" method="post">
			<input type="hidden" name="oemail_address" value="<?php echo html_encode($result['email_address']); ?>" />
			<input type="hidden" name="ogroup_id" value="<?php echo html_encode($result['group_id']); ?>" />
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
			<colgroup>
				<col style="width: 25%" />
				<col style="width: 75%" />
			</colgroup>
			<tfoot>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
						<input type="button" value="<?php echo ($SUCCESS) ? 'Close' : 'Cancel'; ?>" class="button" onclick="window.location='index.php'" />
						<input type="submit" value="Save" class="button" />
					</td>
				</tr>
			</tfoot>
			<tbody>
				<tr>
					<td><?php echo create_tooltip('E-Mail Address', '<strong>Field Name: <em>E-Mail Address</em></strong><br />This is the e-mail address of the new subscriber you are adding.', true); ?></td>
					<td><input type="text" id="email_address" name="email_address" class="text-box" style="width: 200px" value="<?php echo html_encode(($_POST) ? checkslashes($_POST['email_address'], 1) : $result['email_address']); ?>" /></td>
				</tr>
				<tr>
					<td><?php echo create_tooltip('Firstname', '<strong>Field Name: <em>Firstname</em></strong><br />This is the firstname of the new subscriber you are adding.', check_required('firstname')); ?></td>
					<td><input type="text" id="firstname" name="firstname" class="text-box" style="width: 200px" value="<?php echo html_encode(($_POST) ? checkslashes($_POST['firstname'], 1) : $result['firstname']); ?>" /></td>
				</tr>
				<tr>
					<td><?php echo create_tooltip('Lastname', '<strong>Field Name: <em>Lastname</em></strong><br />This is the lastname of the new subscriber you are adding.', check_required('lastname')); ?></td>
					<td><input type="text" id="lastname" name="lastname" class="text-box" style="width: 200px" value="<?php echo html_encode(($_POST) ? checkslashes($_POST['lastname'], 1) : $result['lastname']); ?>" /></td>
				</tr>
				<tr>
					<td style="vertical-align: top"><?php echo create_tooltip('Subscriber Group', '<strong>Field Name: <em>Subscriber Group</em></strong><br />Select the group or groups you would like this new subscriber to be placed into.<br /><br />Please note you can select multiple groups by holding the CTRL button (Win) or Command button (Mac) down and clicking multiple groups.', true); ?></td>
					<td>
						<select id="group_id" name="group_id" style="width: 304px;">
						<?php echo groups_inselect(0, [($_POST) ? $_POST['group_id'] : $result['group_id']]); ?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<?php
                $query = 'SELECT * FROM `'.TABLES_PREFIX.'cfields` ORDER BY `field_order` ASC';
            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $result) {
                    $squery = 'SELECT * FROM `'.TABLES_PREFIX."cdata` WHERE `user_id`='".trim(checkslashes($_GET['id']))."' AND `cfield_id`='".$result['cfields_id']."'";
                    $cdata = $db->GetRow($squery);

                    echo "<tr>\n";
                    echo '	<td style="vertical-align: top">'.($result['field_lname'] ? create_tooltip($result['field_lname'], '<strong>Field Name: <em>'.html_encode($result['field_lname']).'</em></strong><br />Administrator created custom field, so no additional documentation exists.', (bool) $result['field_req']) : '&nbsp;')."</td>\n";
                    echo "	<td>\n";
                    switch ($result['field_type']) {
                        case 'checkbox':
                            if ($result['field_options']) {
                                $options = explode("\n", $result['field_options']);
                                $values = (!empty($cdata) ? explode(', ', $cdata['value']) : []);

                                foreach ($options as $option) {
                                    $pieces = explode('=', $option);
                                    echo '<input type="checkbox" name="cdata['.$result['field_sname'].'][]" value="'.html_encode($pieces[0]).'"'.(in_array($pieces[0], !empty($_POST) ? (!empty($_POST['cdata'][$result['field_sname']]) ? $_POST['cdata'][$result['field_sname']] : []) : $values) ? ' checked="checked"' : '').'> '.html_encode($pieces[1])."<br />\n";
                                }
                            } else {
                                echo '<span class="small-grey">(No options in configuration to check)</span>';
                            }
                            break;
                        case 'radio':
                            if ($result['field_options']) {
                                $options = explode("\n", $result['field_options']);
                                foreach ($options as $option) {
                                    $pieces = explode('=', $option);
                                    echo '<input type="radio" name="cdata['.$result['field_sname'].']" value="'.html_encode($pieces[0]).'"'.(((!empty($_POST['cdata'][$result['field_sname']]) ? $_POST['cdata'][$result['field_sname']] : $cdata['value']) == $pieces[0]) ? ' checked="checked"' : '').'> '.html_encode($pieces[1])."<br />\n";
                                }
                            } else {
                                echo '<span class="small-grey">(No options in configuration to select)</span>';
                            }
                            break;
                        case 'select':
                            if (!empty($result['field_options'])) {
                                $options = explode("\n", $result['field_options']);
                                echo '<select name="cdata['.$result['field_sname']."]\">\n";
                                foreach ($options as $option) {
                                    $pieces = explode('=', $option);
                                    echo '<option value="'.html_encode(trim($pieces[0])).'"'.(((!empty($_POST['cdata'][$result['field_sname']]) ? $_POST['cdata'][$result['field_sname']] : $cdata['value']) == $pieces[0]) ? ' selected="selected"' : '').'>'.html_encode(trim($pieces[1]))."</option>\n";
                                }
                                echo "</select>\n";
                            } else {
                                echo '<span class="small-grey">(No options in configuration to select)</span>';
                            }
                            break;
                        case 'textarea':
                            if (!empty($_POST['cdata'][$result['field_sname']])) {
                                $value = trim($_POST['cdata'][$result['field_sname']]);
                            } elseif (!empty($cdata['value'])) {
                                $value = trim($cdata['value']);
                            } else {
                                $value = '';
                            }

                            echo '<textarea name="cdata['.$result['field_sname'].']" style="width: 98%; height: 75px">'.html_encode($value)."</textarea>\n";
                            break;
                        case 'textbox':
                            if (!empty($_POST['cdata'][$result['field_sname']])) {
                                $value = trim($_POST['cdata'][$result['field_sname']]);
                            } elseif (!empty($cdata['value'])) {
                                $value = trim($cdata['value']);
                            } else {
                                $value = '';
                            }

                            echo '<input type="text" name="cdata['.$result['field_sname'].']" value="'.html_encode($value).'"'.((strlen($result['field_length']) > 0) ? ' maxlength="'.$result['field_length'].'"' : '')." />\n";
                            break;
                        default:
                            echo '&nbsp;';
                            break;
                    }
                    echo "	</td>\n";
                    echo "</tr>\n";
                }
            }
            ?>
				<tr>
					<td>&nbsp;</td>
					<td style="padding-top: 10px"><input type="checkbox" id="updateall" name="updateall" value="1" style="vertical-align: middle" /> <label for="updateall" class="form-row-nreq">Update contact information for this subscriber in all groups.</label></td>
				</tr>
			</tbody>
			</table>
			</form>
			<?php
        } else {
            $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

            ++$ERROR;
            $ERRORSTR[] = 'The subscriber that you are trying to edit was not found. Please ensure you click the edit button beside the subscriber you wish to edit.<br /><br />You will be automatically redirected to the Subscriber Directory in 5 seconds, or <a href="index.php">click here</a> if you prefer not to wait.';

            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThe subscriber id provided [".$_GET['id'].'] was not found in the database. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }

            echo display_error($ERRORSTR);
        }
        break;	// End Edit
    case 'move':
        if ((!empty($_POST['subscribers'])) && is_array($_POST['subscribers']) && count($_POST['subscribers'])) {
            $subscribers = [];
            foreach ($_POST['subscribers'] as $subscriber) {
                if ($subscriber = (int) trim($subscriber)) {
                    $subscribers[] = $subscriber;
                }
            }
            if (count($subscribers)) {
                $subscribers = array_unique($subscribers);

                switch ($STEP) {
                    case '2':
                        if ((empty($_POST['group_ids'])) || (!(int) trim($_POST['group_ids']))) {
                            ++$ERROR;
                            $ERRORSTR[] = 'You must select a Move Destination group to move the selected subscriber'.((count($subscribers) != 1) ? 's' : '').' into.';

                            echo display_error($ERRORSTR);
                        } else {
                            $query = 'SELECT * FROM `'.TABLES_PREFIX.'groups` WHERE `groups_id` = '.$db->qstr((int) trim($_POST['group_ids']));
                            $result = $db->GetRow($query);
                            if ($result) {
                                foreach ($subscribers as $key => $users_id) {
                                    $query = 'SELECT * FROM `'.TABLES_PREFIX.'users` WHERE `users_id` = '.$db->qstr((int) $users_id);
                                    if ($result = $db->GetRow($query)) {
                                        $query = 'SELECT * FROM `'.TABLES_PREFIX.'users` WHERE `email_address` = '.$db->qstr($result['email_address']).' AND `group_id` = '.$db->qstr((int) trim($_POST['group_ids']));
                                        if ($sresult = $db->GetRow($query)) {
                                            unset($subscribers[$key]);
                                        }
                                    }
                                }
                                if (count($subscribers)) {
                                    $query = 'UPDATE `'.TABLES_PREFIX.'users` SET `group_id` = '.$db->qstr((int) trim($_POST['group_ids'])).(((!empty($_POST['reset_signup'])) && ($_POST['reset_signup'] == '1')) ? ', `signup_date` = '.$db->qstr(time()) : '')." WHERE `users_id` IN ('".implode("', '", $subscribers)."')";
                                    if ($db->Execute($query)) {
                                        $moved = $db->Affected_Rows();
                                    } else {
                                        $moved = 0;
                                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tInsert Select statement failed. Database said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                        }
                                    }
                                }

                                $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

                                if (!$moved) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'There were no subscribers that were succesfully moved. The subscribers you are trying move may already exist in the move destination.<br /><br />You will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

                                    echo display_error($ERRORSTR);
                                } else {
                                    ++$SUCCESS;
                                    $SUCCESSSTR[] = 'You have successfully moved '.$moved.' subscriber'.(($moved != 1) ? 's' : '').' into <strong>'.groups_information((int) trim($_POST['group_ids']), true).'</strong>.<br /><br />You will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

                                    echo display_success($SUCCESSSTR);
                                }
                            } else {
                                ++$ERROR;
                                $ERRORSTR[] = 'The Move Destination group to selected does not exist in the database. Please ensure you select a valid ListMessenger Group.';

                                echo display_error($ERRORSTR);
                            }
                        }
                        break;
                    case '1':
                    default:
                        ?>
						<h1>Move Subscriber<?php echo (count($_POST['subscribers']) != 1) ? 's' : ''; ?></h1>
						To complete the subscriber move please choose a destination ListMessenger Group to move the selected subscribers into. As a matter of convenience subscribers will not be moved if the e-mail address already exists in the destination group.
						<br /><br />
						<form action="index.php?section=subscribers&action=move&step=2" method="post">
						<table style="width: 100%; text-align: left" cellspacing="0" cellpadding="1" border="0">
						<colgroup>
							<col style="width: 20%" />
							<col style="width: 80%" />
						</colgroup>
						<tfoot>
							<tr>
								<td colspan="2" style="border-top: 1px #333333 dotted; padding-top: 5px; text-align: right">
									<input type="button" class="button" value="Cancel" onclick="window.location='index.php'" />
									<input type="submit" class="button" value="Move" />
								</td>
							</tr>
						</tfoot>
						<tbody>
							<tr>
								<td style="vertical-align: top"><label for="group_ids" class="form-row-req">Move Destination:</label></td>
								<td>
									<select id="group_ids" name="group_ids" style="width: 300px" onkeypress="return handleEnter(this, event)">
									<?php echo groups_inselect(0, (!empty($_POST['group_ids'])) ? (int) $_POST['group_ids'] : ''); ?>
									</select>
								</td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td style="padding-top: 5px"><input type="checkbox" id="reset_signup" name="reset_signup" value="1" style="vertical-align: middle" /> <label for="reset_signup" class="form-row-nreq">Reset the subscribers sign-up date to today, once moved.</label></td>
							</tr>
							<tr>
								<td colspan="2">&nbsp;</td>
							</tr>
							<tr>
								<td style="vertical-align: top"><span class="form-row-nreq">Subscribers:</span></td>
								<td style="padding-bottom: 10px">
									<table class="tabular" cellspacing="0" cellpadding="1" border="0">
									<colgroup>
										<col style="width: 3%" />
										<col style="width: 30%" />
										<col style="width: 43%" />
										<col style="width: 24%" />
									</colgroup>
									<thead>
										<tr>
											<td>&nbsp;</td>
											<td>Name Field</td>
											<td>E-Mail Address</td>
											<td class="close">Group</td>
										</tr>
									</thead>
									<tbody>
									<?php
                                    $query = "
											SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `name_field`, b.`group_name`
											FROM `".TABLES_PREFIX.'users` AS a
											LEFT JOIN `'.TABLES_PREFIX."groups` AS b
											ON b.`groups_id` = a.`group_id`
											WHERE a.`users_id` IN ('".implode("', '", $subscribers)."')";
                        if ($results = $db->GetAll($query)) {
                            foreach ($results as $result) {
                                echo "<tr>\n";
                                echo '	<td style="white-space: nowrap"><input type="checkbox" name="subscribers[]" value="'.(int) $result['users_id']."\" checked=\"checked\" /></td>\n";
                                echo '	<td>'.(($result['name_field'] != ', ') ? html_encode(limit_chars((substr($result['name_field'], -2) == ', ') ? substr($result['name_field'], 0, strlen($result['name_field']) - 2) : $result['name_field'], 20)) : '&nbsp;')."</td>\n";
                                echo '	<td>'.html_encode(limit_chars($result['email_address'], 28))."</td>\n";
                                echo '	<td>'.html_encode(limit_chars($result['group_name'], 25))."</td>\n";
                                echo "</tr>\n";
                            }
                        }
                        ?>
									</tbody>
									</table>
								</td>
							</tr>
						</tbody>
						</table>
						</form>
						<?php
                    break;
                }
            } else {
                $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

                ++$ERROR;
                $ERRORSTR[] = 'You did not select any subscribers you wish to move. To move subscribers, check the checkbox beside their name and try again. You will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

                echo display_error($ERRORSTR);
            }
        } else {
            $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

            ++$ERROR;
            $ERRORSTR[] = 'You did not select any subscribers you wish to move. To move subscribers, check the checkbox beside their name and try again. You will be automatically returned to the subscriber directory in 5 seconds. <a href="index.php">Click here</a> if you prefer not to wait.';

            echo display_error($ERRORSTR);
        }
        break;	// End Move
    case 'view':
        $COLLAPSED = (!empty($_COOKIE['display']['subscribers']['collapsed']) ? explode(',', $_COOKIE['display']['subscribers']['collapsed']) : []);

        if ((empty($_GET['id'])) || (!(int) trim($_GET['id']))) {
            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tAttempted to edit subscriber information without providing a subscriber ID [".$_GET['id']."].\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
            header('Location: index.php');
            exit;
        }

        $query = 'SELECT * FROM `'.TABLES_PREFIX."users` WHERE `users_id`='".checkslashes(trim($_GET['id']))."'";
        $result = $db->GetRow($query);
        if ($result) {
            $email_address = html_encode($result['email_address']);
            $firstname = html_encode($result['firstname']);
            $lastname = html_encode($result['lastname']);
            $groupname = groups_information($result['group_id'], true);

            if (is_array($groupname)) {
                if (!empty($groupname)) {
                    $groupname = $groupname[0];
                } else {
                    $groupname = '';
                }
            }

            ?>
			<h1>Subscriber Details <small>(<?php echo $email_address; ?>)</small></h1>
			<div style="text-align: right">
				<form>
				<input type="button" value="Edit" class="button" onclick="window.location='index.php?section=subscribers&action=edit&id=<?php echo (int) trim($_GET['id']); ?>'" />
				<input type="button" value="Close" class="button" onclick="window.location='index.php<?php echo ($_SESSION['display']['subscribers']['lastpage'] && ((int) $_SESSION['display']['subscribers']['lastpage'])) ? '?'.replace_query(['action' => false, 'id' => false, 'vp' => $_SESSION['display']['subscribers']['lastpage']]) : ''; ?>'" />
				</form>
			</div>
			<br />
			<?php echo ($ERROR > 0) ? display_error($ERRORSTR) : ''; ?>
			<?php echo ($NOTICE > 0) ? display_notice($NOTICESTR) : ''; ?>
			<?php echo ($SUCCESS > 0) ? display_success($SUCCESSSTR) : ''; ?>
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
			<tr>
				<td class="form-row-req" style="width: 35%">E-Mail Address:</td>
				<td style="width: 65%"><?php echo $email_address; ?></td>
			</tr>
			<tr>
				<td class="form-row-nreq">Firstname:</td>
				<td><?php echo $firstname; ?></td>
			</tr>
			<tr>
				<td class="form-row-nreq">Lastname:</td>
				<td><?php echo $lastname; ?></td>
			</tr>
			<tr>
				<td class="form-row-req" style="vertical-align: top">This Subscriber Group:</td>
				<td><?php echo html_encode($groupname); ?></td>
			</tr>
			<?php
            $squery = '
                        SELECT a.`users_id`, a.`group_id`, b.`group_name`
                        FROM `'.TABLES_PREFIX.'users` AS a
                        LEFT JOIN `'.TABLES_PREFIX.'groups` AS b
                        ON b.`groups_id` = a.`group_id`
                        WHERE `email_address` = '.$db->qstr($result['email_address']).'
                        AND `group_id` <> '.(int) $result['group_id'];
            $sresults = $db->GetAll($squery);
            if ($sresults) {
                echo "<tr>\n";
                echo "\t<td class=\"form-row-nreq\" style=\"vertical-align: top\">Also Subscribed To:</td>\n";
                echo "\t<td>\n";
                echo "\t\t<ol style=\"margin-top: 0px; margin-bottom: 5px; padding-left: 20px\">\n";
                foreach ($sresults as $sresult) {
                    echo "\t\t\t<li>".html_encode($sresult['group_name']).' (<a href="index.php?section=subscribers&action=view&id='.$sresult['users_id']."\">Select Group</a>)</li>\n";
                }
                echo "\t\t</ol>\n";
                echo "\t</td>\n";
                echo "</tr>\n";
            }
            ?>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<?php
            $query = 'SELECT * FROM `'.TABLES_PREFIX.'cfields` ORDER BY `field_order` ASC';
            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $result) {
                    $squery = 'SELECT * FROM `'.TABLES_PREFIX.'cdata` WHERE `user_id`='.(int) $_GET['id'].' AND `cfield_id`='.(int) $result['cfields_id'];
                    $cdata = $db->GetRow($squery);

                    echo "<tr>\n";
                    echo '	<td style="vertical-align: top" class="'.(($result['field_req'] == 1) ? 'form-row-req' : 'form-row-nreq').'">'.(($result['field_lname']) ? checkslashes($result['field_lname'], 1) : '&nbsp;')."</td>\n";
                    echo "	<td>\n";
                    switch ($result['field_type']) {
                        case 'checkbox':
                            if ($result['field_options']) {
                                $checked = [];
                                $options = explode("\n", $result['field_options']);
                                $values = (($cdata) ? explode(', ', $cdata['value']) : []);

                                foreach ($options as $option) {
                                    $pieces = explode('=', $option);
                                    if (in_array($pieces[0], $values)) {
                                        $checked[] = trim($pieces[1]);
                                    }
                                }

                                if (count($checked)) {
                                    echo "<ol style=\"margin-top: 0px; margin-bottom: 5px; padding-left: 20px\">\n";
                                    foreach ($checked as $value) {
                                        echo "\t<li>".html_encode($value)."</li>\n";
                                    }
                                    echo "</ol>\n";
                                } else {
                                    echo '<span class="small-grey">(None checked)</span>';
                                }
                            } else {
                                echo '<span class="small-grey">(No options in configuration to check)</span>';
                            }
                            break;
                        case 'radio':
                            if ($result['field_options'] != '') {
                                $options = explode("\n", $result['field_options']);
                                $selected = '';

                                foreach ($options as $option) {
                                    $pieces = explode('=', $option);
                                    if ($cdata['value'] == $pieces[0]) {
                                        $selected = trim($pieces[1]);
                                    }
                                }

                                if ($selected) {
                                    echo $selected."\n";
                                } else {
                                    echo '<span class="small-grey">(None selected)</span>';
                                }
                            }
                            break;
                        case 'select':
                            if ($result['field_options'] != '') {
                                $options = explode("\n", $result['field_options']);
                                $selected = '';

                                foreach ($options as $option) {
                                    $pieces = explode('=', $option);
                                    if ($cdata['value'] == $pieces[0]) {
                                        $selected = trim($pieces[1]);
                                    }
                                }

                                if ($selected) {
                                    echo $selected."\n";
                                } else {
                                    echo '<span class="small-grey">(None selected)</span>';
                                }
                            }
                            break;
                        case 'textarea':
                            if (!empty($cdata['value']) && $text = trim(nl2br($cdata['value']))) {
                                echo html_encode($text)."\n";
                            } else {
                                echo '<span class="small-grey">(Blank textarea)</span>';
                            }
                            break;
                        case 'textbox':
                            if (!empty($cdata['value']) && $text = trim($cdata['value'])) {
                                echo html_encode($text)."\n";
                            } else {
                                echo '<span class="small-grey">(Blank textbox)</span>';
                            }
                            break;
                        default:
                            echo '&nbsp;';
                            break;
                    }
                    echo "	</td>\n";
                    echo "</tr>\n";
                }
            }
            ?>
			</table>
			<br />
			<div style="display: <?php echo in_array('history', $COLLAPSED) ? 'none' : 'inline'; ?>" id="opened_history">
				<table style="width: 100%; border: 1px #CCCCCC solid" cellspacing="0" cellpadding="1">
				<tr>
					<td class="cursor" style="height: 15px; background-image: url('./images/table-head-on.gif'); background-color: #EEEEEE; border-bottom: 1px #CCCCCC solid" onclick="toggle_section('history', 1, '<?php echo javascript_cookie(); ?>', 'subscribers')">
						<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
						<tr>
							<td style="width: 95%; text-align: left"><span class="search-on">Subscriber History</span></td>
							<td style="width: 5%; text-align: right"><a href="javascript: toggle_section('history', 1, '<?php echo javascript_cookie(); ?>', 'subscribers')"><img src="./images/section-hide.gif" width="9" height="9" alt="Hide" title="Hide History" border="0" /></a></td>
						</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td style="padding: 10px">
						<?php
                        $squery = 'SELECT * FROM `'.TABLES_PREFIX."confirmation` WHERE `email_address`='".$email_address."' ORDER BY `date` DESC";
            $sresults = $db->GetAll($squery);
            if ($sresults) {
                require_once 'classes/phpsniff/phpsniff.class.php';
                ?>
                            <table class="tabular" cellspacing="0" cellpadding="1" border="0">
                            <colgroup>
                                <col style="width: 3%" />
                                <col style="width: 22%" />
                                <col style="width: 35%" />
                                <col style="width: 25%" />
                                <col style="width: 15%" />
                            </colgroup>
                            <thead>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td class="on">Date</td>
                                    <td>Action</td>
                                    <td>IP Address</td>
                                    <td class="close">Confirmed</td>
                                </tr>
                            </thead>
                            <?php
                foreach ($sresults as $sresult) {
                    unset($client);
                    $group_ids = unserialize($sresult['group_ids']);
                    $client = new phpSniff($sresult['user_agent']);

                    echo '<tbody id="opened_dataId['.$sresult['confirm_id']."]\">\n";
                    echo "	<tr onmouseout=\"this.style.backgroundColor='#FFFFFF'\" onmouseover=\"this.style.backgroundColor='#F0FFD1'\" onclick=\"toggle_row('dataId[".$sresult['confirm_id']."]', 1)\">\n";
                    echo "		<td class=\"cursor\"><img src=\"./images/section-show.gif\" width=\"9\" height=\"9\" alt=\"Show\" title=\"Show Details\" border=\"0\" /></td>\n";
                    echo '		<td class="cursor">'.display_date($_SESSION['config'][PREF_DATEFORMAT], $sresult['date'])."</td>\n";
                    echo '		<td class="cursor">'.display_action($sresult['action'])."</td>\n";
                    echo '		<td class="cursor">'.html_encode($sresult['remote_ip'])."</td>\n";
                    echo '		<td class="cursor">'.(($sresult['confirmed']) ? '<span style="color: #009900; font-weight: bold">Yes</span>' : '<span style="color: #CC0000; font-weight: bold">No</span>')."</td>\n";
                    echo "	</tr>\n";
                    echo "</tbody>\n";

                    echo '<tbody id="closed_dataId['.$sresult['confirm_id']."]\" style=\"background-color: #F0FFD1; display: none\">\n";
                    echo "	<tr onclick=\"toggle_row('dataId[".$sresult['confirm_id']."]', 0)\">\n";
                    echo "		<td class=\"cursor\"><img src=\"./images/section-hide.gif\" width=\"9\" height=\"9\" alt=\"Hide\" title=\"Hide Details\" border=\"0\" /></td>\n";
                    echo '		<td class="cursor">'.display_date($_SESSION['config'][PREF_DATEFORMAT], $sresult['date'])."</td>\n";
                    echo '		<td class="cursor">'.display_action($sresult['action'])."</td>\n";
                    echo '		<td class="cursor">'.html_encode($sresult['remote_ip'])."</td>\n";
                    echo '		<td class="cursor">'.(($sresult['confirmed']) ? '<span style="color: #009900; font-weight: bold">Yes</span>' : '<span style="color: #CC0000; font-weight: bold">No</span>')."</td>\n";
                    echo "	</tr>\n";
                    echo "	<tr>\n";
                    echo "		<td>&nbsp;</td>\n";
                    echo "		<td>Hash Code:</td>\n";
                    echo '		<td colspan="3">'.(($sresult['hash']) ? html_encode($sresult['hash']) : '<span class="small-grey">(Not present)</span>')."</td>\n";
                    echo "	</tr>\n";
                    echo "	<tr>\n";
                    echo "		<td>&nbsp;</td>\n";
                    echo "		<td style=\"vertical-align: top\">Groups:</td>\n";
                    echo '		<td colspan="3">';
                    if (is_array($group_ids) && count($group_ids)) {
                        echo "<ol style=\"margin-top: 0px; margin-bottom: 0px; padding-left: 20px\">\n";
                        foreach ($group_ids as $group_id) {
                            echo "\t<li>".html_encode(groups_information($group_id, true))."</li>\n";
                        }
                        echo "</ol>\n";
                    } else {
                        echo '<span class="small-grey">(Not present)</span>';
                    }
                    echo "		</td>\n";
                    echo "	</tr>\n";
                    echo "	<tr>\n";
                    echo "		<td>&nbsp;</td>\n";
                    echo "		<td>Referrer:</td>\n";
                    echo '		<td colspan="3"><div style="width: 98%; overflow: hidden; white-space: nowrap">'.(($sresult['referrer']) ? '<a href="'.html_encode($sresult['referrer']).'" title="'.html_encode($sresult['referrer']).'" style="text-decoration: none">'.html_encode($sresult['referrer']).'</a>' : '<span class="small-grey">(Not present)</span>')."</div></td>\n";
                    echo "	</tr>\n";
                    echo "	<tr>\n";
                    echo "		<td>&nbsp;</td>\n";
                    echo "		<td>Operating System:</td>\n";
                    echo '		<td colspan="3">'.ucwords($client->property('platform')).' '.strtoupper($client->property('os'))."</td>\n";
                    echo "	</tr>\n";
                    echo "	<tr>\n";
                    echo "		<td>&nbsp;</td>\n";
                    echo "		<td>Web Browser:</td>\n";
                    echo '		<td colspan="3">'.ucwords($client->property('long_name')).' '.$client->property('version')."</td>\n";
                    echo "	</tr>\n";
                    echo "	<tr>\n";
                    echo "		<td colspan=\"5\" style=\"height: 5px; border-bottom: 1px #336633 dotted\"><img src=\"./images/pixel.gif\" width=\"1\" height=\"5\" alt=\"\" title=\"\" /></td>\n";
                    echo "	</tr>\n";
                    echo '</tbody>';
                }
                ?>
                            </table>
                            <?php
            } else {
                echo 'No subscription history details have been recorded for this subscriber.';
            }
            ?>
					</td>
				</tr>
				</table>
			</div>
			<div style="display: <?php echo !in_array('history', $COLLAPSED) ? 'none' : 'inline'; ?>" id="closed_history">
				<table style="width: 100%; border: 1px #CCCCCC solid" cellspacing="0" cellpadding="1">
				<tr>
					<td class="cursor" style="height: 15px; background-image: url('./images/table-head-off.gif'); background-color: #EEEEEE" onclick="toggle_section('history', 0, '<?php echo javascript_cookie(); ?>', 'subscribers')">
						<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
						<tr>
							<td style="width: 95%; text-align: left"><span class="search-off">Subscriber History</span></td>
							<td style="width: 5%; text-align: right"><a href="javascript: toggle_section('history', 0, '<?php echo javascript_cookie(); ?>', 'subscribers')"><img src="./images/section-show.gif" width="9" height="9" alt="Show" title="Show History" border="0" /></a></td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</div>
			<br />
			<?php
        } else {
            $ONLOAD[] = "setTimeout('window.location=\'index.php\'', 5000)";

            ++$ERROR;
            $ERRORSTR[] = 'The subscriber that you are trying to edit was not found. Please ensure you click the edit button beside the subscriber you wish to edit.<br /><br />You will be automatically redirected to the Subscriber Directory in 5 seconds, or <a href="index.php">click here</a> if you prefer not to wait.';

            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThe subscriber id provided [".$_GET['id'].'] was not found in the database. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }

            echo display_error($ERRORSTR);
        }
        break;	// End View
    default:
        $COLLAPSED = (!empty($_COOKIE['display']['subscribers']['collapsed']) ? explode(',', $_COOKIE['display']['subscribers']['collapsed']) : []);
        $totalrows = 0;
        $search_query = '';

        // Setup "Sort By Field" Information
        if (!empty($_GET['sort'])) {
            $_SESSION['display']['subscribers']['sort'] = checkslashes($_GET['sort']);
            setcookie('display[subscribers][sort]', checkslashes($_GET['sort']), PREF_COOKIE_TIMEOUT);
        } elseif ((empty($_SESSION['display']['subscribers']['sort'])) && (!empty($_COOKIE['display']['subscribers']['sort']))) {
            $_SESSION['display']['subscribers']['sort'] = $_COOKIE['display']['subscribers']['sort'];
        } else {
            if (empty($_SESSION['display']['subscribers']['sort'])) {
                $_SESSION['display']['subscribers']['sort'] = 'name';
                setcookie('display[subscribers][sort]', 'name', PREF_COOKIE_TIMEOUT);
            }
        }

        // Setup "Sort Order" Information
        if (!empty($_GET['order'])) {
            switch ($_GET['order']) {
                case 'asc':
                    $_SESSION['display']['subscribers']['order'] = 'ASC';
                    break;
                case 'desc':
                    $_SESSION['display']['subscribers']['order'] = 'DESC';
                    break;
                default:
                    $_SESSION['display']['subscribers']['order'] = 'ASC';
                    break;
            }
            setcookie('display[subscribers][order]', $_SESSION['display']['subscribers']['order'], PREF_COOKIE_TIMEOUT);
        } elseif ((empty($_SESSION['display']['subscribers']['order'])) && (!empty($_COOKIE['display']['subscribers']['order']))) {
            $_SESSION['display']['subscribers']['order'] = $_COOKIE['display']['subscribers']['order'];
        } else {
            if (empty($_SESSION['display']['subscribers']['order'])) {
                $_SESSION['display']['subscribers']['order'] = 'ASC';
                setcookie('display[subscribers][order]', 'ASC', PREF_COOKIE_TIMEOUT);
            }
        }

        // Set the internal variables used for sorting, ordering and in pagination.
        $sort = $_SESSION['display']['subscribers']['sort'];
        $order = $_SESSION['display']['subscribers']['order'];

        if (!empty($_GET['pp']) && ((int) $_GET['pp'] > 0) && ((int) $_GET['pp'] <= 1000)) {
            $perpage = (int) $_GET['pp'];
        } elseif (!empty($_SESSION['config'][PREF_PERPAGE_ID]) && ((int) $_SESSION['config'][PREF_PERPAGE_ID] > 0) && ((int) $_SESSION['config'][PREF_PERPAGE_ID] <= 1000)) {
            $perpage = (int) $_SESSION['config'][PREF_PERPAGE_ID];
        } else {
            $perpage = 25;
        }

        // Begin Query String
        if (!empty($_GET['q']) && !empty($_GET['t']) && !empty($_GET['f'])) {
            if (!empty($_GET['f'])) {
                if (substr($_GET['f'], 0, 7) == 'cfield_') {
                    if ($cfield_id = (int) str_replace('cfield_', '', $_GET['f'])) {
                        $search_query .= '
										LEFT JOIN `'.TABLES_PREFIX.'cdata` AS c
										ON c.`user_id` = a.`users_id`
										WHERE c.`cfield_id` = '.$db->qstr($cfield_id).'
										AND c.`value`';
                    }
                } else {
                    switch ($_GET['f']) {
                        case 'firstname':
                            $search_query .= ' WHERE a.`firstname`';
                            break;
                        case 'lastname':
                            $search_query .= ' WHERE a.`lastname`';
                            break;
                        case 'email':
                            $search_query .= ' WHERE a.`email_address`';
                            break;
                        case 'fullname':
                        default:
                            $search_query .= " WHERE CONCAT_WS(', ', a.`lastname`, a.`firstname`)";
                            break;
                    }
                }
            }

            if (!empty($_GET['t'])) {
                switch ($_GET['t']) {
                    case 'equals':
                        $search_query .= ' = '.$db->qstr(trim($_GET['q']));
                        break;
                    case 'contains':
                    default:
                        $search_query .= ' LIKE '.$db->qstr('%'.trim($_GET['q']).'%');
                        break;
                }
            }
        }

        if (!empty($_GET['g']) && (int) trim($_GET['g'])) {
            $search_group_id = (int) trim($_GET['g']);
        } else {
            $search_group_id = 0;
        }

        if ($search_group_id) {
            if ($search_query == '') {
                $search_query .= ' WHERE';
            } else {
                $search_query .= ' AND';
            }

            $groups = [$search_group_id];
            groups_inarray($search_group_id, $groups);

            $search_query .= " `group_id` IN ('".implode("', '", $groups)."')";
        }

        $query = 'SELECT COUNT(*) AS `total_groups` FROM `'.TABLES_PREFIX.'groups`';
        $result = $db->GetRow($query);
        if ($result && (int) $result['total_groups']) {
            $total_groups = (int) $result['total_groups'];
        } else {
            $total_groups = 0;
        }

        $query = 'SELECT COUNT(*) AS `totalrows` FROM `'.TABLES_PREFIX.'users` AS a'.$search_query;
        $result = $db->GetRow($query);
        $totalrows = $result['totalrows'];

        // Get the total number of pages that we need to display.
        if ($totalrows <= $perpage) {
            $totalpages = 1;
        } elseif (($totalrows % $perpage) == 0) {
            $totalpages = (int) ($totalrows / $perpage);
        } else {
            $totalpages = (int) ($totalrows / $perpage) + 1;
        }

        // Check to see what page to output.
        if (!empty($_GET['vp']) && ((int) $_GET['vp'] >= 1) && ((int) $_GET['vp'] <= $totalpages)) {
            $page = (int) $_GET['vp'];
        } else {
            $page = 1;
        }

        $prev_page = $page - 1;
        $next_page = $page + 1;
        $page_start = ($perpage * $page) - $perpage;

        // Get the colomn names of the sorted by colomn.
        switch ($sort) {
            case 'date':
                $sortby = '`signup_date`';
                break;
            case 'name':
                $sortby = '`name_field`';
                break;
            case 'email':
                $sortby = '`email_address`';
                break;
            case 'group':
                $sortby = '`group_name`';
                break;
            default:
                $sortby = '`name_field`';
                break;
        }
        ?>
		<div style="display: <?php echo in_array('search', $COLLAPSED) ? 'none' : 'inline'; ?>" id="opened_search">
			<form id="search_form" action="index.php" method="get">
			<input type="hidden" name="pp" value="<?php echo (!empty($_GET['pp'])) ? (int) $_GET['pp'] : ''; ?>" />
			<table style="width: 100%; border: 1px #CCCCCC solid" cellspacing="0" cellpadding="1">
			<tr>
				<td class="cursor" style="height: 15px; background-image: url('./images/table-head-on.gif'); background-color: #EEEEEE; border-bottom: 1px #CCCCCC solid" onclick="toggle_section('search', 1, '<?php echo javascript_cookie(); ?>', 'subscribers')">
					<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td style="width: 95%; text-align: left"><span class="search-on">Subscriber Search</span></td>
						<td style="width: 5%; text-align: right"><a href="javascript: toggle_section('search', 1, '<?php echo javascript_cookie(); ?>', 'subscribers')"><img src="./images/section-hide.gif" width="9" height="9" alt="Hide" title="Hide Search" border="0" /></a></td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
					<tr>
						<td>
							<label for="f" style="vertical-align: middle">Search:</label>
						</td>
						<td>
							<select id="f" name="f" style="vertical-align: middle; width: 150px">
								<?php
                                echo '<option value="fullname"'.(((!empty($_GET['f'])) && ($_GET['f'] == 'fullname')) ? ' selected="selected"' : '').">Full name</option>\n";
        echo '<option value="firstname"'.(((!empty($_GET['f'])) && ($_GET['f'] == 'firstname')) ? ' selected="selected"' : '').">Firstname</option>\n";
        echo '<option value="lastname"'.(((!empty($_GET['f'])) && ($_GET['f'] == 'lastname')) ? ' selected="selected"' : '').">Lastname</option>\n";
        echo '<option value="email"'.(((!empty($_GET['f'])) && ($_GET['f'] == 'email')) ? ' selected="selected"' : '').">E-Mail Address</option>\n";

        $query = 'SELECT `cfields_id`, `field_sname`, `field_lname` FROM `'.TABLES_PREFIX."cfields` WHERE `field_type` <> 'linebreak'";
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                echo '<option value="cfield_'.$result['cfields_id'].'"'.(((!empty($_GET['f'])) && ($_GET['f'] == 'cfield_'.$result['cfields_id'])) ? ' selected="selected"' : '').'>'.html_encode(($result['field_lname'] != '') ? $result['field_lname'] : $result['field_sname'])."</option>\n";
            }
        }
        ?>
							</select>
							<select name="t" style="vertical-align: middle; width: 85px">
								<option value="contains"<?php echo ((!empty($_GET['t'])) && ($_GET['t'] == 'contains')) ? ' selected="selected"' : ''; ?>>Contains</option>
								<option value="equals"<?php echo ((!empty($_GET['t'])) && ($_GET['t'] == 'equals')) ? ' selected="selected"' : ''; ?>>Equals</option>
							</select>
							<input type="text" class="text-box" style="vertical-align: middle; width: 200px" name="q" value="<?php echo (!empty($_GET['q'])) ? html_encode($_GET['q']) : ''; ?>" />
						</td>
						<td style="text-align: right">
							<input type="submit" value="Search" class="button" />
						</td>
					</tr>
					<tr>
						<td>
							<label for="g" style="vertical-align: middle">Show Group:</label>
						</td>
						<td>
							<select id="g" name="g" style="width: 225px" onchange="$('#search_form').submit();">
								<option value="">-- All Groups --</option>
								<?php echo groups_inselect(0, [$search_group_id]); ?>
							</select>
						</td>
						<td style="text-align: right">
							<?php if (!empty($_GET['q']) || !empty($_GET['t']) || !empty($_GET['f']) || !empty($_GET['g'])) { ?>
							<input type="button" value="Clear" class="button" onclick="window.location='index.php'" />
							<?php } ?>
						</td>
					</tr>
					</table>
				</td>
			</tr>
			</table>
			</form>
		</div>
		<div style="display: <?php echo !in_array('search', $COLLAPSED) ? 'none' : 'inline'; ?>" id="closed_search">
			<table style="width: 100%; border: 1px #CCCCCC solid" cellspacing="0" cellpadding="1">
			<tr>
				<td class="cursor" style="height: 15px; background-image: url('./images/table-head-off.gif'); background-color: #EEEEEE" onclick="toggle_section('search', 0, '<?php echo javascript_cookie(); ?>', 'subscribers')">
					<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td style="width: 95%; text-align: left"><span class="search-off">Subscriber Search</span></td>
						<td style="width: 5%; text-align: right"><a href="javascript: toggle_section('search', 0, '<?php echo javascript_cookie(); ?>', 'subscribers')"><img src="./images/section-show.gif" width="9" height="9" alt="Show" title="Show Search" border="0" /></a></td>
					</tr>
					</table>
				</td>
			</tr>
			</table>
		</div>
		<h1>Subscriber Directory</h1>
		<?php
        if (!empty($_GET['g']) && $totalrows) {
            echo '<div style="padding: 4px; text-align: right" class="small-grey">'.$totalrows.' Subscriber'.(($totalrows != 1) ? 's' : '').' in '.groups_information($search_group_id, true).((is_array($groups) && ($total_groups == (count($groups) - 1))) ? ' (including '.$total_groups.' sub-group'.(($total_groups != 1) ? 's' : '').')' : '').".</div>\n";
        }

        $query = "	SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `name_field`, b.`group_name`
					FROM `".TABLES_PREFIX.'users` AS a
					LEFT JOIN `'.TABLES_PREFIX.'groups` AS b
					ON b.`groups_id` = a.`group_id`
					'.$search_query.'
					ORDER BY '.$sortby.' '.strtoupper($order).'
					LIMIT '.$page_start.', '.$perpage;
        $results = $db->GetAll($query);
        if ($results) {
            ?>
			<table style="width: 100%" cellspacing="0" cellpadding="1" border="0">
			<tr>
				<td style="width: 50%; text-align: left">
					<form action="index.php" method="get">
					<?php
                    echo '<input type="hidden" name="pp" value="'.((!empty($_GET['pp'])) ? (int) $_GET['pp'] : '')."\" />\n";
            echo '<input type="hidden" name="q" value="'.((!empty($_GET['q'])) ? html_encode($_GET['q']) : '')."\" />\n";
            echo '<input type="hidden" name="t" value="'.((!empty($_GET['t'])) ? html_encode($_GET['t']) : '')."\" />\n";
            echo '<input type="hidden" name="f" value="'.((!empty($_GET['f'])) ? html_encode($_GET['f']) : '')."\" />\n";
            echo '<input type="hidden" name="g" value="'.((!empty($_GET['g'])) ? html_encode($_GET['g']) : '').'" />';
            ?>
					<table cellspacing="1" cellpadding="1" border="0">
					<tr>
						<td>Showing Page</td>
						<td><input type="text" name="vp" value="<?php echo html_encode($page); ?>" class="text-box" style="width: 25px" /></td>
						<td>of <?php echo html_encode($totalpages); ?>.</td>
					</tr>
					</table>
					</form>
				</td>
				<td style="width: 50%">
					<div style="float: right">
					<?php
            if ($totalpages > 1) {
                ?>
						<table cellspacing="1" cellpadding="1" border="0">
						<tr>
							<td style="white-space: nowrap; width: 22px; text-align: left">
								<?php
                        if ($prev_page) {
                            echo '<a href="index.php?'.replace_query(['vp' => 1]).'"><img src="./images/record-first-on.gif" border="0" width="9" height="9" alt="First Page" title="Back to first page." /></a>';
                            echo '<a href="index.php?'.replace_query(['vp' => $prev_page]).'"><img src="./images/record-back-on.gif" border="0" width="9" height="9" alt="Page '.$prev_page.'." title="Back to page '.$prev_page.'." /></a>';
                        } else {
                            echo '<img src="./images/record-first-off.gif" border="0" width="9" height="9" alt="" />';
                            echo '<img src="./images/record-back-off.gif" border="0" width="9" height="9" alt="" />';
                        }
                ?>
							</td>
							<td style="text-align: center">
								<?php
                echo '<form action="index.php?'.replace_query()."\" id=\"changepage\" method=\"GET\">\n";
                echo '	<input type="hidden" name="pp" value="'.((!empty($_GET['pp'])) ? (int) $_GET['pp'] : '')."\" />\n";
                echo '	<input type="hidden" name="q" value="'.((!empty($_GET['q'])) ? html_encode($_GET['q']) : '')."\" />\n";
                echo '	<input type="hidden" name="t" value="'.((!empty($_GET['t'])) ? html_encode($_GET['t']) : '')."\" />\n";
                echo '	<input type="hidden" name="f" value="'.((!empty($_GET['f'])) ? html_encode($_GET['f']) : '')."\" />\n";
                echo '	<input type="hidden" name="g" value="'.((!empty($_GET['g'])) ? html_encode($_GET['g']) : '').'" />';
                echo '	<select id="pagination-select" name="vp"'.(($totalpages <= 1) ? ' disabled="disabled"' : '')." onchange=\"$('#changepage').submit();\">\n";
                if (!$totalpages) {
                    echo "<option value=\"\" selected=\"selected\">Page 1</option>\n";
                } else {
                    for ($i = 1; $i <= $totalpages; ++$i) {
                        if ($i == $page) {
                            echo '<option value="'.$i.'" selected="selected">Viewing Page '.$i."</option>\n";
                        } else {
                            echo '<option value="'.$i.'">Page '.$i."</option>\n";
                        }
                    }
                    if (($totalrows % $perpage) != 0) {
                        if ($i == $page) {
                            echo '<option value="'.$i.'" selected="selected">Viewing Page '.$i."</option>\n";
                        } else {
                            echo '<option value="'.$i.'">Page '.$i."</option>\n";
                        }
                    }
                }
                echo "	</select>\n";
                echo "</form>\n";
                ?>
							</td>
							<td style="white-space: nowrap; width: 22px; text-align: right">
								<?php
                if ($page < $totalpages) {
                    echo '<a href="index.php?'.replace_query(['vp' => $next_page]).'"><img src="./images/record-next-on.gif" border="0" width="9" height="9" alt="Page '.$next_page.'." title="Forward to page '.$next_page.'." /></a>';
                    echo '<a href="index.php?'.replace_query(['vp' => $totalpages]).'"><img src="./images/record-last-on.gif" border="0" width="9" height="9" alt="Last Page" title="Forward to last page." /></a>';
                } else {
                    echo '<img src="./images/record-next-off.gif" border="0" width="9" height="9" alt="" />';
                    echo '<img src="./images/record-last-off.gif" border="0" width="9" height="9" alt="" />';
                }
                ?>
							</td>
						</tr>
						</table>
						<?php
            }
            ?>
					</div>
				</td>
			</tr>
			</table>
			<form action="index.php?section=subscribers" method="post">
			<table class="tabular" cellspacing="0" cellpadding="1" border="0">
			<colgroup>
				<col style="width: 7%" />
				<col style="width: 22%" />
				<col style="width: 30%" />
				<col style="width: 21%" />
				<col style="width: 20%" />
			</colgroup>
			<thead>
				<tr>
					<td>&nbsp;</td>
					<td class="<?php echo ($sort == 'name') ? 'on' : 'off'; ?>"><?php echo order_link('name', 'Name Field', $order, $sort); ?></td>
					<td class="<?php echo ($sort == 'email') ? 'on' : 'off'; ?>"><?php echo order_link('email', 'E-Mail Address', $order, $sort); ?></td>
					<td class="<?php echo ($sort == 'date') ? 'on' : 'off'; ?>"><?php echo order_link('date', 'Signup Date', $order, $sort); ?></td>
					<td class="close <?php echo ($sort == 'group') ? 'on' : 'off'; ?>"><?php echo order_link('group', 'Group', $order, $sort); ?></td>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="5" style="border-top: 1px #333333 dotted; padding-top: 5px">
						<input type="checkbox" name="selectall" value="1" onclick="selection(this, 'subscribers[]')" style="vertical-align: middle" />&nbsp;
						<select name="action" style="width: 125px; vertical-align: middle">
							<option value="delete">Delete Selected</option>
							<?php if ($total_groups > 1) { ?>
							<option value="move">Move Selected</option>
							<option value="copy">Copy Selected</option>
							<?php } ?>
						</select>

						<input type="submit" value="Proceed" class="button" style="vertical-align: middle" />
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
                foreach ($results as $result) {
                    echo "<tr onmouseout=\"this.style.backgroundColor='#FFFFFF'\" onmouseover=\"this.style.backgroundColor='#F0FFD1'\">\n";
                    echo '	<td style="white-space: nowrap"><input type="checkbox" name="subscribers[]" value="'.$result['users_id'].'" />&nbsp;<a href="index.php?section=subscribers&action=edit&id='.$result['users_id'].'"><img src="./images/icon-edit-users.gif" width="16" height="16" border="0" alt="Edit" title="Edit '.$result['email_address']."\" /></a></td>\n";
                    echo "	<td class=\"cursor\" onclick=\"window.location='index.php?section=subscribers&action=view&id=".$result['users_id']."'\">".(($result['name_field'] != ', ') ? html_encode(limit_chars($result['name_field'], 20)) : '&nbsp;')."</td>\n";
                    echo "	<td class=\"cursor\" onclick=\"window.location='index.php?section=subscribers&action=view&id=".$result['users_id']."'\">".html_encode(limit_chars($result['email_address'], 28))."</td>\n";
                    echo "	<td class=\"cursor\" onclick=\"window.location='index.php?section=subscribers&action=view&id=".$result['users_id']."'\">".display_date($_SESSION['config'][PREF_DATEFORMAT], $result['signup_date'])."</td>\n";
                    echo "	<td class=\"cursor\" onclick=\"window.location='index.php?section=subscribers&action=view&id=".$result['users_id']."'\">".html_encode(limit_chars($result['group_name'], 25))."</td>\n";
                    echo "</tr>\n";
                }
            ?>
			</tbody>
			</table>
			</form>
			<?php
            $_SESSION['display']['subscribers']['lastpage'] = $page;
        } else {
            if (!empty($_GET['q']) || !empty($_GET['t']) || !empty($_GET['f']) || !empty($_GET['g'])) {
                ?>
				<h2>No Results Found</h2>
				<div class="generic-message">
					There were no subscribers returned based on the search criteria you have provided.
					<br /><br />
					Please modify your search or <a href="index.php?section=subscribers">click here</a> to display everything.
				</div>
				<?php
            } else {
                ?>
				<div class="generic-message">
					There are no Subscribers present in your ListMessenger database.
				</div>
				<h2>Welcome to ListMessenger <?php echo html_encode(VERSION_TYPE.' '.VERSION_INFO); ?></h2>
				<?php
                if ($total_groups) {
                    echo 'Now that you have '.(($total_groups != 1) ? 'some ListMessenger Groups' : 'a ListMessenger Group').' created you might want to put an End-User subscriber form on your website so your visitors can subscribe to your group'.(($total_groups != 1) ? 's' : '').". Examples of End-User subscriber forms that you can use are provided in the <a href=\"index.php?section=end-user\">End-User Tools</a> section within the Control Panel.\n";
                    echo "<br /><br />\n";
                    echo 'If you would like to manually add subscribers to your group'.(($total_groups != 1) ? 's' : '')." you can click the <a href=\"index.php?section=subscribers&amp;action=add\">Add Subscriber</a> link in the sidebar or use ListMessengers <a href=\"index.php?section=import-export&amp;action=import\">Import Mailing List</a> tools which are also available in the Control Panel.\n";
                } else {
                    echo "The first thing that you need to do is create a group or groups which will be used to store your new subscribers in. To create or manage your Groups, click the <a href=\"index.php?section=manage-groups\">Manage Groups</a> link in the sidebar, then click the <strong>New Group</strong> button.\n";
                }
            }
        }
        break;
}
