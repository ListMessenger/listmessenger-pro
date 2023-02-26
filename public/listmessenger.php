<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
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

    $GROUP_IDS = [];
    $SUBSCRIBER_IDS = [];

    /*
     * If the addr value is set, and the email_address value is not,
     * assign email_address to addr.
     */
    if ((!empty($LM_REQUEST['addr'])) && (empty($LM_REQUEST['email_address']))) {
        $LM_REQUEST['email_address'] = $LM_REQUEST['addr'];
    }

    /*
     * If CAPTCHA support is enabled do this validation first.
     */
    if ($config[ENDUSER_CAPTCHA] == 'yes') {
        if ((empty($LM_REQUEST['captcha_code'])) || (!PhpCaptcha::Validate($LM_REQUEST['captcha_code']))) {
            ++$ERROR;
            $ERRORMSG[] = $LANGUAGE_PACK['page_captcha_invalid'];

            $TITLE = $LANGUAGE_PACK['page_captcha_title'];
            $MESSAGE = '';

            $MESSAGE .= '<form action="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_FILENAME]."\" method=\"post\">\n";

            /**
             * All current $LM_REQUEST values including arrays (depth of 2 max).
             */
            foreach ($LM_REQUEST as $key => $value) {
                if ($key != 'captcha_code') {
                    if (is_array($value)) {
                        foreach ($value as $skey => $svalue) {
                            if (is_scalar($svalue)) {
                                $MESSAGE .= '<input type="hidden" name="'.public_html_encode($key.'['.$skey.']').'" value="'.public_html_encode($svalue)."\" />\n";
                            }
                        }
                    } elseif (is_scalar($value)) {
                        $MESSAGE .= '<input type="hidden" name="'.public_html_encode($key).'" value="'.public_html_encode($value)."\" />\n";
                    }
                }
            }

            $MESSAGE .= "<table style=\"width: 75%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">\n";
            $MESSAGE .= "<colgroup>\n";
            $MESSAGE .= "	<col style=\"width: 35%\" />\n";
            $MESSAGE .= "	<col style=\"width: 65%\" />\n";
            $MESSAGE .= "</colgroup>\n";

            if (!empty($LM_REQUEST['captcha_code'])) {
                $MESSAGE .= "<thead>\n";
                $MESSAGE .= "	<tr>\n";
                $MESSAGE .= '		<td colspan="2">'.display_error($ERRORMSG)."</td>\n";
                $MESSAGE .= "	</tr>\n";
                $MESSAGE .= "</thead>\n";
            }

            $MESSAGE .= "<tfoot>\n";
            $MESSAGE .= "	<tr>\n";
            $MESSAGE .= "		<td colspan=\"2\" style=\"text-align: right; padding-top: 5px\">\n";
            $MESSAGE .= '			<input type="button" value="'.$LANGUAGE_PACK['page_forward_cancel_button']."\" onclick=\"window.location='".$config[PREF_PUBLIC_URL].$config[ENDUSER_HELP_FILENAME]."'\" />\n";
            $MESSAGE .= '			<input type="submit" value="'.$LANGUAGE_PACK['page_forward_submit_button']."\" />\n";
            $MESSAGE .= "		</td>\n";
            $MESSAGE .= "	</tr>\n";
            $MESSAGE .= "</tfoot>\n";
            $MESSAGE .= "<tbody>\n";
            $MESSAGE .= "	<tr>\n";
            $MESSAGE .= "		<td colspan=\"2\">\n";
            $MESSAGE .= '			'.$LANGUAGE_PACK['page_captcha_message_sentence']."<br />\n";
            $MESSAGE .= "		</td>\n";
            $MESSAGE .= "	</tr>\n";
            $MESSAGE .= generate_captcha_html($config[PREF_PUBLIC_URL].$config[ENDUSER_FILENAME], $LANGUAGE_PACK['page_captcha_label']);
            $MESSAGE .= "</tbody>\n";
            $MESSAGE .= "</table>\n";
            $MESSAGE .= "</form>\n";
        }
    }

    if (!$ERROR) {
        /*
         * Recognized actions for this file are "subscribe" or "unsubscribe".
         */
        switch ($LM_REQUEST['action']) {
            case 'subscribe':
                /*
                 * Ensure there are no errors before actually performing the
                 * action. An example would be an invalid CAPTCHA code.
                 */
                if (!$ERROR) {
                    /*
                     * Error check: Group Information / group_ids
                     */
                    if (is_scalar($LM_REQUEST['group_ids'])) {
                        if ((int) trim($LM_REQUEST['group_ids'])) {
                            $LM_REQUEST['group_ids'] = [(int) trim($LM_REQUEST['group_ids'])];
                        } else {
                            $LM_REQUEST['group_ids'] = [];
                        }
                    }

                    if (is_array($LM_REQUEST['group_ids']) && count($LM_REQUEST['group_ids'])) {
                        foreach ($LM_REQUEST['group_ids'] as $group_id) {
                            if ($group_id = (int) trim($group_id)) {
                                $query = 'SELECT `group_name` FROM `'.TABLES_PREFIX.'groups` WHERE `groups_id` = '.$db->qstr($group_id)." AND `group_private` = 'false'";
                                $result = $db->GetRow($query);
                                if ($result) {
                                    $query = 'SELECT `users_id` FROM `'.TABLES_PREFIX.'users` WHERE `group_id` = '.$db->qstr($group_id).' AND `email_address` = '.$db->qstr($LM_REQUEST['email_address']);
                                    $result = $db->GetRow($query);
                                    if ((!$result) && (!in_array($group_id, $GROUP_IDS))) {
                                        $GROUP_IDS[] = $group_id;
                                    }
                                }
                            }
                        }
                    }

                    if (!count($GROUP_IDS)) {
                        ++$ERROR;
                        $TITLE = $LANGUAGE_PACK['error_default_title'];
                        $MESSAGE = $LANGUAGE_PACK['error_subscribe_email_exists'];
                    }

                    if (!$ERROR) {
                        /*
                         * Error check: E-Mail Address / email_addres
                         */
                        if ((!empty($LM_REQUEST['email_address'])) && ($LM_REQUEST['email_address'] = clean_input($LM_REQUEST['email_address'], ['nows', 'lowercase', 'emailheaders']))) {
                            if (valid_address($LM_REQUEST['email_address'])) {
                                $address_pieces = explode('@', $LM_REQUEST['email_address']);

                                if (is_array($config[ENDUSER_BANEMAIL]) && banned_address($LM_REQUEST['email_address'], $config[ENDUSER_BANEMAIL])) {
                                    ++$ERROR;
                                    $ERRORMSG[] = $LANGUAGE_PACK['error_subscribe_banned_email'];
                                } elseif (is_array($config[ENDUSER_BANIPS]) && (!empty($_SERVER['REMOTE_ADDR'])) && banned_ip($_SERVER['REMOTE_ADDR'], $config[ENDUSER_BANIPS])) {
                                    ++$ERROR;
                                    $ERRORMSG[] = $LANGUAGE_PACK['error_subscribe_banned_ip'];
                                } elseif (($config[ENDUSER_MXRECORD] == 'yes') && (getmxrr($address_pieces[1].'.', $mxhosts) == false) && (gethostbyname($address_pieces[1].'.') == $address_pieces[1].'.')) {
                                    ++$ERROR;
                                    $ERRORMSG[] = $LANGUAGE_PACK['error_subscribe_invalid_domain'];
                                }
                            } else {
                                ++$ERROR;
                                $ERRORMSG[] = $LANGUAGE_PACK['error_subscribe_invalid_email'];
                            }
                        } else {
                            ++$ERROR;
                            $ERRORMSG[] = $LANGUAGE_PACK['error_subscribe_no_email'];
                        }

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
                                if (empty($LM_REQUEST[$result['field_sname']]) || !custom_data_field_value($LM_REQUEST[$result['field_sname']])) {
                                    ++$ERROR;
                                    $ERRORMSG[] = str_replace('[cfield_name]', public_html_encode($result['field_lname']), $LANGUAGE_PACK['error_subscribe_required_cfield']);
                                }
                            }
                        }

                        if ($ERROR) {
                            $query = 'SELECT * FROM `'.TABLES_PREFIX.'cfields` ORDER BY `field_order` ASC';
                            $custom_fields = $db->GetAll($query);

                            $firstname_required = check_required('firstname');
                            $lastname_required = check_required('lastname');

                            $TITLE = $LANGUAGE_PACK['error_default_title'];
                            $MESSAGE = '';

                            $MESSAGE .= '<form action="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_FILENAME]."\" method=\"post\">\n";
                            $MESSAGE .= "<input type=\"hidden\" name=\"action\" value=\"subscribe\" />\n";

                            /*
                             * Attach all valid group_ids to the new request.
                             */
                            foreach ($GROUP_IDS as $group_id) {
                                $MESSAGE .= '<input type="hidden" name="group_ids[]" value="'.(int) $group_id."\" />\n";
                            }

                            /*
                             * Attach the CAPTCHA code so the subscriber does not have to enter it again.
                             */
                            if ($config[ENDUSER_CAPTCHA] == 'yes') {
                                $fonts = [];
                                $fonts[] = $config[PREF_PROPATH_ID].'includes/fonts/vera.ttf';
                                $fonts[] = $config[PREF_PROPATH_ID].'includes/fonts/verabd.ttf';
                                $captcha = new PhpCaptcha($fonts, 172, 40);
                                $captcha->GenerateCode();

                                if (!empty($_SESSION['php_captcha'])) {
                                    $MESSAGE .= '<input type="hidden" name="captcha_code" value="'.public_html_encode($_SESSION['php_captcha'])."\" />\n";
                                }
                            }

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
                            $MESSAGE .= "<thead>\n";
                            $MESSAGE .= "	<tr>\n";
                            $MESSAGE .= '		<td colspan="2">'.display_error($ERRORMSG)."</td>\n";
                            $MESSAGE .= "	</tr>\n";
                            $MESSAGE .= "</thead>\n";
                            $MESSAGE .= "<tfoot>\n";
                            $MESSAGE .= "	<tr>\n";
                            $MESSAGE .= "		<td colspan=\"2\" style=\"text-align: right; padding-top: 5px\">\n";
                            $MESSAGE .= '			<input type="button" value="'.$LANGUAGE_PACK['page_forward_cancel_button']."\" onclick=\"window.location='".$config[PREF_PUBLIC_URL].$config[ENDUSER_HELP_FILENAME]."'\" />\n";
                            $MESSAGE .= '			<input type="submit" value="'.$LANGUAGE_PACK['page_forward_submit_button']."\" />\n";
                            $MESSAGE .= "		</td>\n";
                            $MESSAGE .= "	</tr>\n";
                            $MESSAGE .= "</tfoot>\n";
                            $MESSAGE .= "<tbody>\n";
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

                            /*
                             * It seems the session variable was not set for some reason, so we should
                             * display the CAPTCHA input again. The subscriber is not going to be happy.
                             */
                            if (($config[ENDUSER_CAPTCHA] == 'yes') && (empty($_SESSION['php_captcha']))) {
                                $MESSAGE .= "	<tr>\n";
                                $MESSAGE .= "		<td colspan=\"2\">\n";
                                $MESSAGE .= '			<h2>'.$LANGUAGE_PACK['page_captcha_title']."</h2>\n";
                                $MESSAGE .= '			'.$LANGUAGE_PACK['page_captcha_message_sentence']."<br />\n";
                                $MESSAGE .= "		</td>\n";
                                $MESSAGE .= "	</tr>\n";
                                $MESSAGE .= generate_captcha_html($config[PREF_PUBLIC_URL].$config[ENDUSER_FILENAME], $LANGUAGE_PACK['page_captcha_label']);
                            }
                            $MESSAGE .= "</tbody>\n";
                            $MESSAGE .= "</table>\n";
                            $MESSAGE .= "</form>\n";
                        } else {
                            if ($config[ENDUSER_SUBCON] == 'yes') {
                                $result = users_queue($LM_REQUEST['email_address'], $LM_REQUEST['firstname'], $LM_REQUEST['lastname'], $GROUP_IDS, $LM_REQUEST, 'usr-subscribe');
                                if ($result) {
                                    try {
                                        $mail = new LM_Mailer($config);
                                        $mail->Subject = $LANGUAGE_PACK['subscribe_confirmation_subject'];
                                        $mail->Body = str_replace(['[name]', '[url]', '[abuse_address]', '[from]'], [clean_input($LM_REQUEST['firstname'], 'slashtestremove'), $config[PREF_PUBLIC_URL].$config[ENDUSER_CONFIRM_FILENAME].'?id='.$result['confirm_id'].'&code='.$result['hash'], $config[PREF_ABUEMAL_ID], $config[PREF_FRMNAME_ID]], $LANGUAGE_PACK['subscribe_confirmation_message']);

                                        if ($LM_REQUEST['firstname'] != '') {
                                            $senders_name = clean_input($LM_REQUEST['firstname'], 'slashtestremove').(($LM_REQUEST['lastname'] != '') ? ' '.clean_input($LM_REQUEST['lastname'], 'slashtestremove') : '');
                                        } else {
                                            $senders_name = $LM_REQUEST['email_address'];
                                        }

                                        $mail->ClearAllRecipients();
                                        $mail->AddAddress($LM_REQUEST['email_address'], $senders_name);

                                        if ((!$mail->IsError()) && $mail->Send()) {
                                            $TITLE = $LANGUAGE_PACK['success_subscribe_optin_title'];
                                            $MESSAGE = $LANGUAGE_PACK['success_subscribe_optin_message'];
                                        } else {
                                            $query = 'DELETE FROM `'.TABLES_PREFIX.'confirmation` WHERE `confirm_id` = '.$db->qstr($result['confirm_id']);
                                            if (!$db->Execute($query)) {
                                                if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete the failed confirmation queue request from the confirmation table. Database server said: ".$db->ErrorMsg()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                }
                                            }

                                            if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to confirmation message to ".$email_address.'. LM_Mailer responded: '.$mail->ErrorInfo."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                            }

                                            throw new Exception($LANGUAGE_PACK['error_subscribe_failed_optin']);
                                        }
                                    } catch (Exception $e) {
                                        ++$ERROR;
                                        $TITLE = $LANGUAGE_PACK['error_default_title'];
                                        $MESSAGE = $e->getMessage();
                                    }
                                } else {
                                    ++$ERROR;
                                    $TITLE = $LANGUAGE_PACK['error_default_title'];
                                    $MESSAGE = $LANGUAGE_PACK['error_subscribe_email_exists'];

                                    if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to add a new subscriber to the confirmation queue. The subscriber is already present in all groups.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                    }
                                }
                            } else {
                                $result = users_add($LM_REQUEST['email_address'], $LM_REQUEST['firstname'], $LM_REQUEST['lastname'], $GROUP_IDS, $LM_REQUEST, $config);
                                if ($result) {
                                    $query = 'INSERT INTO `'.TABLES_PREFIX."confirmation` VALUES (NULL, '".time()."', 'usr-subscribe', ".$db->qstr($_SERVER['REMOTE_ADDR']).', '.$db->qstr($_SERVER['HTTP_REFERER']).', '.$db->qstr($_SERVER['HTTP_USER_AGENT']).', '.$db->qstr($LM_REQUEST['email_address']).', '.$db->qstr($LM_REQUEST['firstname']).', '.$db->qstr($LM_REQUEST['lastname']).', '.$db->qstr(serialize($GROUP_IDS)).', '.$db->qstr(serialize($LM_REQUEST)).", '', '0')";
                                    $db->Execute($query);

                                    if ($result['failed'] > 0) {
                                        if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tFailed to add some subscriber data to the database during subscription.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                        }
                                    }

                                    if ($result['success'] > 0) {
                                        if ($config[ENDUSER_NEWSUBNOTICE] == 'yes') {
                                            $query = 'SELECT `users_id` FROM `'.TABLES_PREFIX.'users` WHERE `group_id` = '.$db->qstr((int) $GROUP_IDS[0]).' AND `email_address` = '.$db->qstr($LM_REQUEST['email_address']);
                                            $result = $db->GetRow($query);
                                            if ($result) {
                                                $notice_custom_data = get_custom_data($result['users_id'], [], $config);

                                                if (!send_notice('subscribe', $GROUP_IDS, $notice_custom_data, $config)) {
                                                    if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send new subscriber notice to administrator.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                    }
                                                }
                                            } else {
                                                if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send new subscriber notice to administrator because users_id could not be found.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                }
                                            }
                                        }

                                        $TITLE = $LANGUAGE_PACK['success_subscribe_title'];
                                        $MESSAGE = $LANGUAGE_PACK['success_subscribe_message'];
                                    }
                                } else {
                                    ++$ERROR;
                                    $TITLE = $LANGUAGE_PACK['error_default_title'];
                                    $MESSAGE = $LANGUAGE_PACK['error_subscribe_email_exists'];

                                    if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to add a new subscriber to the database. The subscriber is already present in all groups.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case 'unsubscribe':
                /*
                 * Ensure there are no errors before actually performing the
                 * action. An example would be an invalid CAPTCHA code.
                 */
                if (!$ERROR) {
                    /*
                     * Error check: E-Mail Address / email_addres
                     */
                    if ((!empty($LM_REQUEST['email_address'])) && ($LM_REQUEST['email_address'] = clean_input($LM_REQUEST['email_address'], ['nows', 'lowercase', 'emailheaders']))) {
                        if (valid_address(trim($LM_REQUEST['email_address']))) {
                            /*
                             * Error check: Group Information / group_ids
                             */
                            if (is_scalar($LM_REQUEST['group_ids'])) {
                                if ((int) trim($LM_REQUEST['group_ids'])) {
                                    $LM_REQUEST['group_ids'] = [(int) trim($LM_REQUEST['group_ids'])];
                                } else {
                                    $LM_REQUEST['group_ids'] = [];
                                }
                            }

                            if (is_array($LM_REQUEST['group_ids']) && count($LM_REQUEST['group_ids'])) {
                                foreach ($LM_REQUEST['group_ids'] as $group_id) {
                                    if ($group_id = (int) trim($group_id)) {
                                        $query = 'SELECT `group_name` FROM `'.TABLES_PREFIX.'groups` WHERE `groups_id` = '.$db->qstr($group_id);
                                        $result = $db->GetRow($query);
                                        if ($result) {
                                            $query = 'SELECT `users_id` FROM `'.TABLES_PREFIX.'users` WHERE `group_id` = '.$db->qstr($group_id).' AND `email_address` = '.$db->qstr($LM_REQUEST['email_address']);
                                            $result = $db->GetRow($query);
                                            if ($result) {
                                                if (!in_array($group_id, $GROUP_IDS)) {
                                                    $GROUP_IDS[] = $group_id;
                                                }
                                                if (!in_array($result['users_id'], $SUBSCRIBER_IDS)) {
                                                    $SUBSCRIBER_IDS[] = $result['users_id'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if ((!count($SUBSCRIBER_IDS)) || (!count($GROUP_IDS))) {
                                ++$ERROR;
                                $TITLE = $LANGUAGE_PACK['error_default_title'];
                                $MESSAGE = $LANGUAGE_PACK['error_unsubscribe_email_not_exists'];
                            }

                            if (!$ERROR) {
                                if ($config[ENDUSER_UNSUBCON] == 'yes') {
                                    $result = users_queue($LM_REQUEST['email_address'], '', '', $GROUP_IDS, [], 'usr-unsubscribe');
                                    if ($result) {
                                        try {
                                            $mail = new LM_Mailer($config);
                                            $mail->Subject = $LANGUAGE_PACK['unsubscribe_confirmation_subject'];
                                            $mail->Body = str_replace(['[url]', '[abuse_address]', '[from]'], [$config[PREF_PUBLIC_URL].$config[ENDUSER_CONFIRM_FILENAME].'?id='.$result['confirm_id'].'&code='.$result['hash'], $config[PREF_ABUEMAL_ID], $config[PREF_FRMNAME_ID]], $LANGUAGE_PACK['unsubscribe_confirmation_message']);

                                            $mail->ClearAllRecipients();
                                            $mail->AddAddress($LM_REQUEST['email_address'], $LM_REQUEST['email_address']);

                                            if ((!$mail->IsError()) && $mail->Send()) {
                                                $TITLE = $LANGUAGE_PACK['success_unsubscribe_optout_title'];
                                                $MESSAGE = $LANGUAGE_PACK['success_unsubscribe_optout_message'];
                                            } else {
                                                $query = 'DELETE FROM `'.TABLES_PREFIX.'confirmation` WHERE `confirm_id` = '.$db->qstr((int) $result['confirm_id']);
                                                if (!$db->Execute($query)) {
                                                    if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete the failed confirmation queue request from the confirmation table. Database server said: ".$db->ErrorMsg()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                    }
                                                }

                                                if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send confirmation message to ".$email_address.'. LM_Mailer responded: '.$mail->ErrorInfo."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                }

                                                throw new Exception($LANGUAGE_PACK['error_unsubscribe_failed_optout']);
                                            }
                                        } catch (Exception $e) {
                                            ++$ERROR;
                                            $TITLE = $LANGUAGE_PACK['error_default_title'];
                                            $MESSAGE = $e->getMessage();
                                        }
                                    } else {
                                        ++$ERROR;
                                        $TITLE = $LANGUAGE_PACK['error_default_title'];
                                        $MESSAGE = $LANGUAGE_PACK['error_unsubscribe_email_not_exists'];

                                        if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to add a new subscriber to the confirmation queue. The subscriber is already present in all groups.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                        }
                                    }
                                } else {
                                    if ($config[ENDUSER_UNSUBNOTICE] == 'yes') {
                                        $notice_custom_data = get_custom_data($SUBSCRIBER_IDS[0], [], $config);
                                    }

                                    $result = subscriber_remove($SUBSCRIBER_IDS, $config);
                                    if ($result) {
                                        $query = 'INSERT INTO `'.TABLES_PREFIX."confirmation` VALUES (NULL, '".time()."', 'usr-unsubscribe', ".$db->qstr($_SERVER['REMOTE_ADDR']).', '.$db->qstr($_SERVER['HTTP_REFERER']).', '.$db->qstr($_SERVER['HTTP_USER_AGENT']).', '.$db->qstr($LM_REQUEST['email_address']).", '', '', ".$db->qstr(serialize($GROUP_IDS)).", '', '', '0')";
                                        $db->Execute($query);

                                        if ($config[ENDUSER_UNSUBNOTICE] == 'yes') {
                                            if (!send_notice('unsubscribe', $GROUP_IDS, $notice_custom_data, $config)) {
                                                if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send unsubscribe notice to administrator.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                }
                                            }
                                        }

                                        $TITLE = $LANGUAGE_PACK['success_unsubscribe_title'];
                                        $MESSAGE = $LANGUAGE_PACK['success_unsubscribe_message'];
                                    } else {
                                        ++$ERROR;
                                        $TITLE = $LANGUAGE_PACK['error_default_title'];
                                        $MESSAGE = $LANGUAGE_PACK['error_unsubscribe_email_not_exists'];

                                        if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to remove subscriber from the database. They do not appear to be subscribed to the system.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                        }
                                    }
                                }
                            }
                        } else {
                            ++$ERROR;
                            $TITLE = $LANGUAGE_PACK['error_default_title'];
                            $MESSAGE = $LANGUAGE_PACK['error_unsubscribe_invalid_email'];
                        }
                    } else {
                        ++$ERROR;
                        $TITLE = $LANGUAGE_PACK['error_default_title'];
                        $MESSAGE = $LANGUAGE_PACK['error_unsubscribe_no_email'];
                    }
                }
                break;
            default:
                $ERROR++;
                $TITLE = $LANGUAGE_PACK['error_default_title'];
                $MESSAGE = $LANGUAGE_PACK['error_invalid_action'];
                break;
        }
    }

    require_once 'eu_footer.inc.php';
}
