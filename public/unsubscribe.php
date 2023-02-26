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

    // Copy smaller variable names to standard naming convention.
    $LM_REQUEST['group_ids'] = (!empty($LM_REQUEST['g']) ? $LM_REQUEST['g'] : []);
    $LM_REQUEST['email_address'] = (!empty($LM_REQUEST['addr']) ? $LM_REQUEST['addr'] : '');
    unset($LM_REQUEST['g'], $LM_REQUEST['addr']);

    if (empty($LM_REQUEST['action'])) {
        $LM_REQUEST['action'] = false;
    }

    // Error checking for e-mail address information.
    if ((!empty($LM_REQUEST['email_address'])) && ($LM_REQUEST['email_address'] = clean_input($LM_REQUEST['email_address'], ['nows', 'lowercase', 'emailheaders']))) {
        if (valid_address($LM_REQUEST['email_address'])) {
            $group_ids = [];
            $subscriber_ids = [];
            $groups_requested = false;

            if ((!empty($LM_REQUEST['group_ids'])) && (!empty($LM_REQUEST['group_ids']))) {
                if (is_scalar($LM_REQUEST['group_ids'])) {
                    if ((int) trim($LM_REQUEST['group_ids'])) {
                        $LM_REQUEST['group_ids'] = [(int) trim($LM_REQUEST['group_ids'])];
                    } else {
                        $LM_REQUEST['group_ids'] = [];
                    }
                }

                if (is_array($LM_REQUEST['group_ids']) && count($LM_REQUEST['group_ids'])) {
                    $groups_requested = true;

                    foreach ($LM_REQUEST['group_ids'] as $group_id) {
                        if ($group_id = (int) trim($group_id)) {
                            $query = 'SELECT `group_name` FROM `'.TABLES_PREFIX.'groups` WHERE `groups_id` = '.$db->qstr($group_id);
                            $result = $db->GetRow($query);
                            if ($result) {
                                $query = 'SELECT `users_id` FROM `'.TABLES_PREFIX.'users` WHERE `group_id` = '.$db->qstr($group_id).' AND `email_address` = '.$db->qstr($LM_REQUEST['email_address']);
                                $result = $db->GetRow($query);
                                if ($result) {
                                    if (!in_array($group_id, $group_ids)) {
                                        $group_ids[] = $group_id;
                                    }

                                    if (!in_array($result['users_id'], $subscriber_ids)) {
                                        $subscriber_ids[] = $result['users_id'];
                                    }
                                }
                            }
                        }
                    }

                    if ((!count($subscriber_ids)) || (!count($group_ids))) {
                        ++$ERROR;
                        $TITLE = $LANGUAGE_PACK['error_default_title'];
                        $MESSAGE = $LANGUAGE_PACK['error_unsubscribe_email_not_exists'];
                    }
                }
            } elseif (!empty($LM_REQUEST['action']) && $LM_REQUEST['action'] != 'confirm') {
                $query = 'SELECT `users_id`, `group_id` FROM `'.TABLES_PREFIX.'users` WHERE `email_address` = '.$db->qstr($LM_REQUEST['email_address']);
                $results = $db->GetAll($query);
                if ($results) {
                    foreach ($results as $result) {
                        if (!in_array($result['group_id'], $group_ids)) {
                            $group_ids[] = $result['group_id'];
                        }

                        if (!in_array($result['users_id'], $subscriber_ids)) {
                            $subscriber_ids[] = $result['users_id'];
                        }
                    }
                }

                if ((!count($subscriber_ids)) || (!count($group_ids))) {
                    ++$ERROR;
                    $TITLE = $LANGUAGE_PACK['error_default_title'];
                    $MESSAGE = $LANGUAGE_PACK['error_unsubscribe_email_not_found'];
                }
            }

            if (!$ERROR) {
                switch ($LM_REQUEST['action']) {
                    case 'confirm' :
                        if ($config[ENDUSER_UNSUBCON] == 'yes') {
                            $result = users_queue($LM_REQUEST['email_address'], '', '', $group_ids, [], 'usr-unsubscribe');
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
                                        $query = 'DELETE FROM `'.TABLES_PREFIX.'confirmation` WHERE `confirm_id` = '.$db->qstr($result['confirm_id']);
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

                                    $mail->ClearCustomHeaders();
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
                                $MESSAGE = $LANGUAGE_PACK['error_unsubscribe_email_not_exists'];

                                if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to add a new subscriber to the confirmation queue. The subscriber is already present in all groups.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }
                            }
                        } else {
                            if ($config[ENDUSER_UNSUBNOTICE] == 'yes') {
                                $notice_custom_data = get_custom_data($subscriber_ids[0], [], $config);
                            }

                            $result = subscriber_remove($subscriber_ids, $config);
                            if ($result) {
                                $query = 'INSERT INTO `'.TABLES_PREFIX."confirmation` VALUES (NULL, '".time()."', 'usr-unsubscribe', ".$db->qstr($_SERVER['REMOTE_ADDR']).', '.$db->qstr($_SERVER['HTTP_REFERER']).', '.$db->qstr($_SERVER['HTTP_USER_AGENT']).', '.$db->qstr($LM_REQUEST['email_address']).", '', '', ".$db->qstr(serialize($group_ids)).", '', '', '0')";
                                $db->Execute($query);

                                if ($config[ENDUSER_UNSUBNOTICE] == 'yes') {
                                    if (!send_notice('unsubscribe', $group_ids, $notice_custom_data, $config)) {
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
                        break;
                    default:
                        $groups_info = groups_information($group_ids);

                        $TITLE = $LANGUAGE_PACK['page_unsubscribe_title'];

                        $MESSAGE = "<br />\n";
                        $MESSAGE .= $LANGUAGE_PACK['page_unsubscribe_message_sentence'];
                        $MESSAGE .= '<form action="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_UNSUB_FILENAME]."\" method=\"get\">\n";
                        $MESSAGE .= "<input type=\"hidden\" name=\"action\" value=\"confirm\" />\n";
                        $MESSAGE .= '<input type="hidden" name="addr" value="'.public_html_encode($LM_REQUEST['email_address'])."\" />\n";
                        $MESSAGE .= "<table style=\"width: auto; margin-top: 15px\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">\n";
                        $MESSAGE .= "<tfoot>\n";
                        $MESSAGE .= "	<tr>\n";
                        $MESSAGE .= "		<td colspan=\"2\" style=\"text-align: right; padding-top: 15px\">\n";
                        $MESSAGE .= '			<input type="button" value="'.$LANGUAGE_PACK['page_unsubscribe_cancel_button']."\" onclick=\"window.location='".$config[PREF_PUBLIC_URL].$config[ENDUSER_HELP_FILENAME]."'\" />\n";
                        $MESSAGE .= '			<input type="submit" value="'.$LANGUAGE_PACK['page_unsubscribe_submit_button']."\" />\n";
                        $MESSAGE .= "		</td>\n";
                        $MESSAGE .= "	</tr>\n";
                        $MESSAGE .= "</tfoot>\n";
                        $MESSAGE .= "<tbody>\n";

                        foreach ($groups_info as $group_id => $group_info) {
                            $MESSAGE .= "<tr>\n";
                            $MESSAGE .= '	<td><input type="checkbox" id="lm_group_'.$group_id.'" name="g[]" value="'.$group_id.'"'.(($groups_requested) ? ' checked="checked"' : '').' /></td>';
                            $MESSAGE .= '	<td style="padding-left: 5px"><label for="lm_group_'.$group_id.'">'.str_replace(['[email]', '[groupname]'], [trim($LM_REQUEST['email_address']), $group_info['name']], $LANGUAGE_PACK['page_unsubscribe_list_groups']).'</label></td>';
                            $MESSAGE .= "</tr>\n";
                        }

                        $MESSAGE .= "</tbody>\n";
                        $MESSAGE .= "</table>\n";
                        $MESSAGE .= "</form>\n";
                        break;
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

    require_once 'eu_footer.inc.php';
}
