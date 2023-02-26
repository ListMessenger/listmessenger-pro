<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * Profile update logic written by James Collins.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */

// Change the $LM_PATH variable in the public_config.inc.php file in this directory.
require_once './public_config.inc.php';

if (!file_exists($LM_PATH.'includes/config.inc.php')) {
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
    echo "<head>\n";
    echo "	<title>ListMessenger Path Error</title>\n";
    echo "	<style type=\"text/css\">\n";
    echo "	div.error-message {\n";
    echo "		background-color:	#FFD9D0;\n";
    echo "		border:				1px #CC0000 solid;\n";
    echo "		padding:			8px;\n";
    echo "		color:				#333333;\n";
    echo "		font-family:		Verdana, Arial, Helvetica, sans-serif;\n";
    echo "		font-size:			12px;\n";
    echo "		margin-bottom:		10px;\n";
    echo "	}\n";
    echo "	</style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<div class=\"error-message\">\n";
    echo '	The path to the ListMessenger directory that you have provided [<strong>'.$LM_PATH.'</strong>] appears to be invalid or PHP does not have permission to read files from this directory. Please ensure that you provide the full path from root to your ListMessenger program directory in the $LM_PATH variable within this file [<strong>'.__FILE__.'</strong>].';
    echo "</div>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit;
} else {
    ini_set('include_path', str_replace('\\', '/', $LM_PATH.'/includes'));
    ini_set('allow_url_fopen', 1);
    ini_set('error_reporting', E_ALL ^ E_NOTICE);
    ini_set('magic_quotes_runtime', 0);

    define('TOOLS_LOADED', true);
    require_once 'eu_header.inc.php';

    /*
     * If the addr value is set, and the email_address value is not,
     * assign email_address to addr.
     */
    if ((!empty($LM_REQUEST['addr'])) && (empty($LM_REQUEST['email_address']))) {
        $LM_REQUEST['email_address'] = $LM_REQUEST['addr'];
    }

    if (empty($LM_REQUEST['action'])) {
        $LM_REQUEST['action'] = false;
    }

    if ($config[ENDUSER_PROFILE] == 'yes') {
        $TITLE = $LANGUAGE_PACK['page_profile_opened_title'];

        if (empty($LM_REQUEST['code']) || empty($LM_REQUEST['id'])) {
            /*
             * Assume we are doing Step 1 if the code or ID field is not present or blank.
             */
            if ((($config[ENDUSER_CAPTCHA] == 'no') || ((!empty($LM_REQUEST['captcha_code'])) && PhpCaptcha::Validate($LM_REQUEST['captcha_code']))) && (!empty($LM_REQUEST['email_address'])) && ($LM_REQUEST['email_address'] = clean_input($LM_REQUEST['email_address'], ['nows', 'lowercase', 'emailheaders'])) && valid_address($LM_REQUEST['email_address'])) {
                $query = 'SELECT COUNT(*) AS num FROM `'.TABLES_PREFIX.'users` WHERE `email_address` = '.$db->qstr($LM_REQUEST['email_address']);
                $result = $db->GetRow($query);
                if ($result && ((int) $result['num'])) {
                    $update_id = 0;

                    $insert_array = [];
                    $insert_array['date'] = time();
                    $insert_array['email_address'] = checkslashes(trim($LM_REQUEST['email_address']));
                    $insert_array['completed'] = 0;
                    $insert_array['hash'] = md5(uniqid(rand(), 1));

                    $result = $db->AutoExecute(TABLES_PREFIX.'user_updates', $insert_array, 'INSERT');
                    if ($result && $db->Affected_Rows()) {
                        $update_id = $db->Insert_Id();
                    } else {
                        $attempt = 0;
                        while (((!$result) || (!$db->Affected_Rows())) && ($attempt < 10)) {
                            ++$attempt;
                            $insert_array['hash'] = md5(uniqid(rand(), 1));
                            $result = $db->AutoExecute(TABLES_PREFIX.'user_updates', $insert_array, 'INSERT');
                        }

                        if ($result && $db->Affected_Rows()) {
                            $update_id = $db->Insert_Id();
                        }
                    }

                    if ($update_id) {
                        $query = 'SELECT `firstname`, `lastname` FROM `'.TABLES_PREFIX.'users` WHERE `email_address` = '.$db->qstr($LM_REQUEST['email_address']);
                        $result = $db->GetRow($query);
                        if ($result) {
                            $firstname = clean_input($result['firstname'], 'emailheaders');
                            $lastname = clean_input($result['lastname'], 'emailheaders');

                            try {
                                $mail = new LM_Mailer($config);
                                $mail->Subject = $LANGUAGE_PACK['update_profile_confirmation_subject'];
                                $mail->Body = str_replace(['[name]', '[url]', '[abuse_address]', '[from]'], [$firstname, $config[PREF_PUBLIC_URL].$config[ENDUSER_PROFILE_FILENAME].'?id='.$update_id.'&code='.$insert_array['hash'], $config[PREF_ABUEMAL_ID], $config[PREF_FRMNAME_ID]], $LANGUAGE_PACK['update_profile_confirmation_message']);

                                if (strlen($firstname.$lastname) > 1) {
                                    $senders_name = checkslashes(trim($firstname).' '.trim($lastname), 1);
                                } else {
                                    $senders_name = $LM_REQUEST['email_address'];
                                }

                                $mail->ClearAllRecipients();
                                $mail->AddAddress($LM_REQUEST['email_address'], $senders_name);

                                if ((!$mail->IsError()) && $mail->Send()) {
                                    $MESSAGE = $LANGUAGE_PACK['page_profile_step1_complete'];
                                } else {
                                    /*
                                     * Error sending the email, delete the recent row from the user_updates table.
                                     */
                                    if (!$db->Execute('DELETE FROM `'.TABLES_PREFIX.'user_updates` WHERE `updates_id` = '.$db->qstr((int) $update_id))) {
                                        if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete the failed update profile request from the user_updates table. Database server said: ".$db->ErrorMsg()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                        }
                                    }

                                    if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send profile update e-mail. LM_Mailer responded: ".$mail->ErrorInfo."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                    }

                                    throw new Exception($LANGUAGE_PACK['error_update_profile']);
                                }
                            } catch (Exception $e) {
                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\t".$e->getMessage()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }

                                ++$ERROR;
                                $TITLE = $LANGUAGE_PACK['error_default_title'];
                                $MESSAGE = $e->getMessage();
                            }
                        } else {
                            ++$ERROR;
                            $TITLE = $LANGUAGE_PACK['error_default_title'];
                            $MESSAGE = $LANGUAGE_PACK['error_update_profile'];
                        }
                    } else {
                        ++$ERROR;
                        $TITLE = $LANGUAGE_PACK['error_default_title'];
                        $MESSAGE = $LANGUAGE_PACK['error_update_profile'];
                    }
                } else {
                    ++$ERROR;
                    $TITLE = $LANGUAGE_PACK['error_default_title'];
                    $MESSAGE = $LANGUAGE_PACK['error_unsubscribe_email_not_found'];
                }
            } else {
                $MESSAGE = '';

                /*
                 * If CAPTCHA support is enabled do this validation first.
                 */
                if ($config[ENDUSER_CAPTCHA] == 'yes') {
                    if ((empty($LM_REQUEST['captcha_code'])) || (!PhpCaptcha::Validate($LM_REQUEST['captcha_code']))) {
                        ++$ERROR;
                        $ERRORMSG[] = $LANGUAGE_PACK['page_captcha_invalid'];
                    }
                }

                $MESSAGE .= '<form action="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_PROFILE_FILENAME]."\" method=\"post\">\n";
                $MESSAGE .= "<table style=\"width: 75%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">\n";
                $MESSAGE .= "<colgroup>\n";
                $MESSAGE .= "	<col style=\"width: 35%\" />\n";
                $MESSAGE .= "	<col style=\"width: 65%\" />\n";
                $MESSAGE .= "</colgroup>\n";

                if (($config[ENDUSER_CAPTCHA] == 'yes') && (!empty($LM_REQUEST['captcha_code']))) {
                    $MESSAGE .= "<thead>\n";
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= '		<td colspan="2">'.display_error($ERRORMSG)."</td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= "</thead>\n";
                }

                $MESSAGE .= "<tfoot>\n";
                $MESSAGE .= "	<tr>\n";
                $MESSAGE .= "		<td colspan=\"2\" style=\"text-align: right; padding-top: 5px\">\n";
                $MESSAGE .= '			<input type="button" value="'.$LANGUAGE_PACK['page_profile_cancel_button']."\" onclick=\"window.location='".$config[PREF_PUBLIC_URL].$config[ENDUSER_HELP_FILENAME]."'\" />\n";
                $MESSAGE .= '			<input type="submit" value="'.$LANGUAGE_PACK['page_profile_submit_button']."\" />\n";
                $MESSAGE .= "		</td>\n";
                $MESSAGE .= "	</tr>\n";
                $MESSAGE .= "</tfoot>\n";
                $MESSAGE .= "<tbody>\n";
                $MESSAGE .= "	<tr>\n";
                $MESSAGE .= "		<td colspan=\"2\">\n";
                $MESSAGE .= '			'.$LANGUAGE_PACK['page_profile_instructions']."<br /><br />\n";
                $MESSAGE .= "		</td>\n";
                $MESSAGE .= "	</tr>\n";
                $MESSAGE .= "	<tr>\n";
                $MESSAGE .= '		<td><label for="email_address" class="required">'.$LANGUAGE_PACK['page_profile_email_address']."</label></td>\n";
                $MESSAGE .= '		<td><input type="text" id="email_address" name="email_address" value="'.((!empty($LM_REQUEST['email_address'])) ? public_html_encode($LM_REQUEST['email_address']) : '')."\" maxlength=\"128\" /></td>\n";
                $MESSAGE .= "	</tr>\n";

                if ($config[ENDUSER_CAPTCHA] == 'yes') {
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= "		<td colspan=\"2\" style=\"padding-top: 15px\">\n";
                    $MESSAGE .= '			<h2>'.$LANGUAGE_PACK['page_captcha_title']."</h2>\n";
                    $MESSAGE .= '			'.$LANGUAGE_PACK['page_captcha_message_sentence']."<br />\n";
                    $MESSAGE .= "		</td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= generate_captcha_html($config[PREF_PUBLIC_URL].$config[ENDUSER_FILENAME], $LANGUAGE_PACK['page_captcha_label']);
                }

                $MESSAGE .= "</tbody>\n";
                $MESSAGE .= "</table>\n";
                $MESSAGE .= "</form>\n";
            }
        } else {
            if (($LM_REQUEST['id'] = (int) trim($LM_REQUEST['id'])) && (strlen($LM_REQUEST['code']) == 32)) {
                $query = 'SELECT * FROM `'.TABLES_PREFIX.'user_updates` WHERE `updates_id` = '.$db->qstr($LM_REQUEST['id']).' AND `hash` = '.$db->qstr(trim($LM_REQUEST['code'])).' LIMIT 1';
                $result = $db->GetRow($query);
                if ($result) {
                    if ($result['date'] < strtotime('+'.(((int) $config[PREF_EXPIRE_CONFIRM]) ? (int) $config[PREF_EXPIRE_CONFIRM] : 7).' days')) {
                        if (!$result['completed']) {
                            $USERS_IDS = [];

                            $query = 'SELECT * FROM `'.TABLES_PREFIX.'users` WHERE `email_address` = '.$db->qstr($result['email_address']);
                            $sresults = $db->GetAll($query);
                            if ($sresults) {
                                if (is_array($sresults[0]) && count($sresults[0])) {
                                    foreach ($sresults[0] as $key => $value) {
                                        if (empty($LM_REQUEST[$key])) {
                                            $LM_REQUEST[$key] = $value;
                                        }
                                    }
                                }

                                foreach ($sresults as $sresult) {
                                    $USERS_IDS[] = (int) $sresult['users_id'];
                                }
                            }

                            if (is_array($USERS_IDS) && count($USERS_IDS)) {
                                $TITLE = $LANGUAGE_PACK['page_profile_opened_title'];

                                if ($LM_REQUEST['action'] == 'save') {
                                    /*
                                     * Error check: Validation and error checking on the firstname field.
                                     */
                                    if ((!empty($LM_REQUEST['firstname'])) && ($tmp_input = public_html_encode(clean_input($LM_REQUEST['firstname'], ['emailheaders', 'notags', 'trim'])))) {
                                        $LM_REQUEST['firstname'] = $tmp_input;
                                    } else {
                                        $LM_REQUEST['firstname'] = '';
                                    }

                                    if (check_required('firstname') && ($LM_REQUEST['firstname'] == '')) {
                                        ++$ERROR;
                                        $ERRORMSG[] = str_replace('[cfield_name]', $LANGUAGE_PACK['page_confirm_firstname'], $LANGUAGE_PACK['error_subscribe_required_cfield']);
                                    }

                                    /*
                                     * Error check: Validation and error checking on the lastname field.
                                     */
                                    if ((!empty($LM_REQUEST['lastname'])) && ($tmp_input = public_html_encode(clean_input($LM_REQUEST['lastname'], ['emailheaders', 'notags', 'trim'])))) {
                                        $LM_REQUEST['lastname'] = $tmp_input;
                                    } else {
                                        $LM_REQUEST['lastname'] = '';
                                    }

                                    if (check_required('lastname') && ($LM_REQUEST['lastname'] == '')) {
                                        ++$ERROR;
                                        $ERRORMSG[] = str_replace('[cfield_name]', $LANGUAGE_PACK['page_confirm_lastname'], $LANGUAGE_PACK['error_subscribe_required_cfield']);
                                    }

                                    /**
                                     * Error check: Custom field data.
                                     */
                                    $query = 'SELECT `field_sname`, `field_lname` FROM `'.TABLES_PREFIX."cfields` WHERE `field_req`='1' ORDER BY `field_order` ASC";
                                    $results = $db->GetAll($query);
                                    if ($results) {
                                        foreach ($results as $result) {
                                            if (empty($LM_REQUEST[$result['field_sname']]) || (!$LM_REQUEST[$result['field_sname']]) || !custom_data_field_value($LM_REQUEST[$result['field_sname']])) {
                                                ++$ERROR;
                                                $ERRORMSG[] = str_replace('[cfield_name]', public_html_encode($result['field_lname']), $LANGUAGE_PACK['error_subscribe_required_cfield']);
                                            }
                                        }
                                    }
                                }

                                if ($ERROR || ($LM_REQUEST['action'] != 'save')) {
                                    $MESSAGE = '';

                                    $query = 'SELECT * FROM `'.TABLES_PREFIX.'cfields` ORDER BY `field_order` ASC';
                                    $custom_fields = $db->GetAll($query);

                                    $firstname_required = check_required('firstname');
                                    $lastname_required = check_required('lastname');

                                    $MESSAGE .= '<form action="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_PROFILE_FILENAME]."\" method=\"post\">\n";
                                    $MESSAGE .= "<input type=\"hidden\" name=\"action\" value=\"save\" />\n";
                                    $MESSAGE .= '<input type="hidden" name="id" value="'.public_html_encode($LM_REQUEST['id'])."\" />\n";
                                    $MESSAGE .= '<input type="hidden" name="code" value="'.public_html_encode($LM_REQUEST['code'])."\" />\n";
                                    /*
                                     * Attach any hidden custom fields to the new request.
                                     */
                                    foreach ($custom_fields as $custom_field) {
                                        if ($custom_field['field_type'] == 'hidden') {
                                            $MESSAGE .= '<input type="hidden" name="'.public_html_encode($custom_field['field_sname']).'" value="'.public_html_encode($result['field_options'])."\" />\n";
                                        }
                                    }

                                    $MESSAGE .= "<table style=\"width: 75%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">\n";
                                    $MESSAGE .= "<colgroup>\n";
                                    $MESSAGE .= "	<col style=\"width: 35%\" />\n";
                                    $MESSAGE .= "	<col style=\"width: 65%\" />\n";
                                    $MESSAGE .= "</colgroup>\n";

                                    if ($ERROR) {
                                        $MESSAGE .= "<thead>\n";
                                        $MESSAGE .= "	<tr>\n";
                                        $MESSAGE .= '		<td colspan="2">'.display_error($ERRORMSG)."</td>\n";
                                        $MESSAGE .= "	</tr>\n";
                                        $MESSAGE .= "</thead>\n";
                                    }

                                    $MESSAGE .= "<tfoot>\n";
                                    $MESSAGE .= "	<tr>\n";
                                    $MESSAGE .= "		<td colspan=\"2\" style=\"text-align: right; padding-top: 5px\">\n";
                                    $MESSAGE .= '			<input type="button" value="'.((!empty($SUCCESS)) ? $LANGUAGE_PACK['page_profile_close_button'] : $LANGUAGE_PACK['page_profile_cancel_button'])."\" onclick=\"window.location='".$config[PREF_PUBLIC_URL].$config[ENDUSER_HELP_FILENAME]."'\" />\n";
                                    $MESSAGE .= '			<input type="submit" value="'.$LANGUAGE_PACK['page_profile_update_button']."\" />\n";
                                    $MESSAGE .= "		</td>\n";
                                    $MESSAGE .= "	</tr>\n";
                                    $MESSAGE .= "</tfoot>\n";
                                    $MESSAGE .= "<tbody>\n";
                                    $MESSAGE .= "	<tr>\n";
                                    $MESSAGE .= '		<td colspan="2">'.$LANGUAGE_PACK['page_profile_step2_instructions']."<br /><br /></td>\n";
                                    $MESSAGE .= "	</tr>\n";
                                    $MESSAGE .= "	<tr>\n";
                                    $MESSAGE .= '		<td><label for="email_address" class="required">'.$LANGUAGE_PACK['page_confirm_email_address']."</label></td>\n";
                                    $MESSAGE .= '		<td><input type="text" id="email_address" name="email_address" value="'.((!empty($LM_REQUEST['email_address'])) ? public_html_encode($LM_REQUEST['email_address']) : '')."\" maxlength=\"128\" /></td>\n";
                                    $MESSAGE .= "	</tr>\n";
                                    $MESSAGE .= "	<tr>\n";
                                    $MESSAGE .= '		<td><label for="firstname"'.(($firstname_required) ? ' class="required"' : '').'>'.$LANGUAGE_PACK['page_confirm_firstname']."</label></td>\n";
                                    $MESSAGE .= '		<td><input type="text" id="firstname" name="firstname" value="'.((!empty($LM_REQUEST['firstname'])) ? public_html_encode($LM_REQUEST['firstname']) : '')."\" maxlength=\"32\" /></td>\n";
                                    $MESSAGE .= "	</tr>\n";
                                    $MESSAGE .= "	<tr>\n";
                                    $MESSAGE .= '		<td><label for="lastname"'.(($lastname_required) ? ' class="required"' : '').'>'.$LANGUAGE_PACK['page_confirm_lastname']."</label></td>\n";
                                    $MESSAGE .= '		<td><input type="text" id="lastname" name="lastname" value="'.((!empty($LM_REQUEST['lastname'])) ? public_html_encode($LM_REQUEST['lastname']) : '')."\" maxlength=\"32\" /></td>\n";
                                    $MESSAGE .= "	</tr>\n";

                                    /*
                                     * Provide all available custom field details.
                                     */
                                    foreach ($custom_fields as $custom_field) {
                                        $query = 'SELECT * FROM `'.TABLES_PREFIX.'cdata` WHERE `user_id` IN ('.implode(', ', $USERS_IDS).') AND `cfield_id` = '.$db->qstr($custom_field['cfields_id']);
                                        $custom_data = $db->GetRow($query);

                                        if ((empty($LM_REQUEST[$custom_field['field_sname']])) && (!empty($custom_data['value']))) {
                                            $LM_REQUEST[$custom_field['field_sname']] = $custom_data['value'];
                                        }

                                        if ($custom_field['field_type'] == 'linebreak') {
                                            $MESSAGE .= "<tr>\n";
                                            $MESSAGE .= "	<td colspan=\"2\">&nbsp;</td>\n";
                                            $MESSAGE .= "</tr>\n";
                                        } else {
                                            $MESSAGE .= "<tr>\n";
                                            $MESSAGE .= '	<td style="vertical-align: top"><label for="'.public_html_encode($custom_field['field_sname']).'"'.(($custom_field['field_req'] == 1) ? ' class="required"' : '').'>'.public_html_encode($custom_field['field_lname'])."</label></td>\n";
                                            $MESSAGE .= "	<td>\n";
                                            switch ($custom_field['field_type']) {
                                                case 'textbox':
                                                    $MESSAGE .= '<input type="text" id="'.public_html_encode($custom_field['field_sname']).'" name="'.public_html_encode($custom_field['field_sname']).'" value="'.((!empty($LM_REQUEST[$custom_field['field_sname']])) ? public_html_encode($LM_REQUEST[$custom_field['field_sname']]) : '').'"'.(((int) $custom_field['field_length']) ? ' maxlength="'.(int) $custom_field['field_length'].'"' : '')." />\n";
                                                    break;
                                                case 'textarea':
                                                    $MESSAGE .= "\t\t\t<textarea id=\"".public_html_encode($custom_field['field_sname']).'" name="'.public_html_encode($custom_field['field_sname']).'" rows="4" cols="30">'.((!empty($LM_REQUEST[$custom_field['field_sname']])) ? public_html_encode($LM_REQUEST[$custom_field['field_sname']]) : '')."</textarea>\n";
                                                    break;
                                                case 'select':
                                                    if ($custom_field['field_options'] != '') {
                                                        $options = explode("\n", $custom_field['field_options']);

                                                        if (is_array($options) && count($options)) {
                                                            $MESSAGE .= '<select id="'.public_html_encode($custom_field['field_sname']).'" name="'.public_html_encode($custom_field['field_sname'])."\">\n";

                                                            foreach ($options as $option) {
                                                                $pieces = explode('=', $option);
                                                                $MESSAGE .= '<option value="'.public_html_encode($pieces[0]).'"'.((!empty($LM_REQUEST[$custom_field['field_sname']])) ? ((is_array($LM_REQUEST[$custom_field['field_sname']])) ? ((in_array($pieces[0], $LM_REQUEST[$custom_field['field_sname']])) ? ' selected="selected"' : '') : (($LM_REQUEST[$custom_field['field_sname']] == $pieces[0]) ? ' selected="selected"' : '')) : '').'>'.public_html_encode($pieces[1])."</option>\n";
                                                            }

                                                            $MESSAGE .= "</select>\n";
                                                        }
                                                    }
                                                    break;
                                                case 'checkbox':
                                                    if ($custom_field['field_options'] != '') {
                                                        $options = explode("\n", $custom_field['field_options']);

                                                        if (is_scalar($LM_REQUEST[$custom_field['field_sname']])) {
                                                            $LM_REQUEST[$custom_field['field_sname']] = explode(', ', $LM_REQUEST[$custom_field['field_sname']]);
                                                        }

                                                        if (is_array($options) && count($options)) {
                                                            foreach ($options as $key => $option) {
                                                                $pieces = explode('=', $option);
                                                                $MESSAGE .= '<input type="checkbox" id="'.public_html_encode($custom_field['field_sname']).'_'.$key.'" name="'.public_html_encode($custom_field['field_sname']).'[]" value="'.public_html_encode($pieces[0]).'"'.(((!empty($LM_REQUEST[$custom_field['field_sname']])) && is_array($LM_REQUEST[$custom_field['field_sname']]) && in_array($pieces[0], $LM_REQUEST[$custom_field['field_sname']])) ? ' checked="checked"' : '').'> <label for="'.public_html_encode($custom_field['field_sname']).'_'.$key.'">'.public_html_encode($pieces[1])."</label><br />\n";
                                                            }
                                                        }
                                                    }
                                                    break;
                                                case 'radio':
                                                    if ($custom_field['field_options'] != '') {
                                                        $options = explode("\n", $custom_field['field_options']);

                                                        if (is_array($options) && count($options)) {
                                                            foreach ($options as $key => $option) {
                                                                $pieces = explode('=', $option);
                                                                $MESSAGE .= '<input type="radio" id="'.public_html_encode($custom_field['field_sname']).'_'.$key.'" name="'.public_html_encode($custom_field['field_sname']).'" value="'.public_html_encode($pieces[0]).'"'.(((!empty($LM_REQUEST[$custom_field['field_sname']])) && ($LM_REQUEST[$custom_field['field_sname']] == $pieces[0])) ? ' checked="checked"' : '').'> <label for="'.public_html_encode($custom_field['field_sname']).'_'.$key.'">'.public_html_encode($pieces[1])."</label><br />\n";
                                                            }
                                                        }
                                                    }
                                                    break;
                                                default:
                                                    $MESSAGE .= '&nbsp;';
                                                    break;
                                            }
                                            $MESSAGE .= "	</td>\n";
                                            $MESSAGE .= "</tr>\n";
                                        }
                                    }
                                    $MESSAGE .= "</tbody>\n";
                                    $MESSAGE .= "</table>\n";
                                    $MESSAGE .= "</form>\n";
                                } else {
                                    $query = 'UPDATE `'.TABLES_PREFIX.'users` SET `firstname` = '.$db->qstr($LM_REQUEST['firstname']).', `lastname` = '.$db->qstr($LM_REQUEST['lastname']).' WHERE `users_id` IN ('.implode(', ', $USERS_IDS).')';
                                    if ($db->Execute($query)) {
                                        /*
                                         * Stores each users_ids custom data.
                                         */
                                        foreach ($USERS_IDS as $user_id) {
                                            custom_data_store($user_id, $LM_REQUEST, $config);
                                        }

                                        $query = 'UPDATE `'.TABLES_PREFIX."user_updates` SET `completed` = '1' WHERE `updates_id` = ".(int) trim($LM_REQUEST['id']);
                                        if (!$db->Execute($query)) {
                                            if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to set the completed flag in the user_updates table. Database server said: ".$db->ErrorMsg()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                            }
                                        } else {
                                            $TITLE = $LANGUAGE_PACK['page_profile_opened_title'];
                                            $MESSAGE = $LANGUAGE_PACK['page_profile_step2_complete'];
                                        }
                                    } else {
                                        ++$ERROR;
                                        $ERRORSTR[] = $LANGUAGE_PACK['error_confirm_unable_request'];

                                        if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to update subscriber data in group ".$group_name.'. Database server said: '.$db->ErrorMsg()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                        }
                                    }
                                }
                            } else {
                                $TITLE = $LANGUAGE_PACK['error_default_title'];
                                $MESSAGE = $LANGUAGE_PACK['error_confirm_invalid_request'];
                            }
                        } else {
                            $TITLE = $LANGUAGE_PACK['error_default_title'];
                            $MESSAGE = $LANGUAGE_PACK['error_confirm_completed'];
                        }
                    } else {
                        $TITLE = $LANGUAGE_PACK['error_default_title'];
                        $MESSAGE = $LANGUAGE_PACK['error_expired_code'];
                    }
                } else {
                    $TITLE = $LANGUAGE_PACK['error_default_title'];
                    $MESSAGE = $LANGUAGE_PACK['error_confirm_invalid_request'];
                }
            } else {
                $TITLE = $LANGUAGE_PACK['error_default_title'];
                $MESSAGE = $LANGUAGE_PACK['error_confirm_invalid_request'];
            }
        }
    } else {
        $abuse = encode_address($config[PREF_ABUEMAL_ID]);
        $TITLE = $LANGUAGE_PACK['page_profile_closed_title'];
        $MESSAGE = $LANGUAGE_PACK['page_profile_closed_message_sentence'];
        $MESSAGE = str_replace('[abuse_address]', '<a href="mailto:'.$abuse['address'].'" style="font-weight: strong">'.$abuse['text'].'</a>', $MESSAGE);
    }

    require_once 'eu_footer.inc.php';
}
