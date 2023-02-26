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

    if ((empty($LM_REQUEST['code'])) || (empty($LM_REQUEST['id'])) || (!$LM_REQUEST['id'] = (int) trim($LM_REQUEST['id'])) || (strlen($LM_REQUEST['code']) != 32)) {
        ++$ERROR;
        $TITLE = $LANGUAGE_PACK['error_default_title'];
        $MESSAGE = $LANGUAGE_PACK['error_confirm_invalid_request'];
    } else {
        $query = 'SELECT * FROM `'.TABLES_PREFIX.'confirmation` WHERE `confirm_id` = '.$db->qstr($LM_REQUEST['id']).' AND `hash` = '.$db->qstr(trim($LM_REQUEST['code']));
        $confirm = $db->GetRow($query);
        if ($confirm) {
            if ($confirm['confirmed'] != '0') {
                ++$ERROR;
                $TITLE = $LANGUAGE_PACK['error_default_title'];
                $MESSAGE = $LANGUAGE_PACK['error_confirm_completed'];
            }
        } else {
            ++$ERROR;
            $TITLE = $LANGUAGE_PACK['error_default_title'];
            $MESSAGE = $LANGUAGE_PACK['error_confirm_invalid_request'];
        }
    }

    if (!$ERROR) {
        if (empty($LM_REQUEST['action'])) {
            $LM_REQUEST['action'] = '';
        }

        switch ($LM_REQUEST['action']) {
            case 'confirm':
                $query = 'UPDATE `'.TABLES_PREFIX."confirmation` SET `confirmed`='1' WHERE `confirm_id` = ".$db->qstr($LM_REQUEST['id']);
                if ($db->Execute($query) && $db->Affected_Rows()) {
                    switch ($confirm['action']) {
                        case 'adm-import':
                        case 'adm-subscribe':
                        case 'usr-subscribe':
                            $group_ids = unserialize($confirm['group_ids']);
                            $cdata = unserialize($confirm['cdata']);
                            $result = users_add($confirm['email_address'], $confirm['firstname'], $confirm['lastname'], $group_ids, $cdata, $config);
                            if ($result) {
                                if ($result['failed'] > 0) {
                                    if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tFailed to add some subscriber data to the database during subscription.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                    }
                                }

                                if ($result['success'] > 0) {
                                    if ($config[ENDUSER_NEWSUBNOTICE] == 'yes') {
                                        $query = 'SELECT `users_id` FROM `'.TABLES_PREFIX.'users` WHERE `group_id` = '.$db->qstr((int) $group_ids[0]).' AND `email_address` = '.$db->qstr($confirm['email_address']);
                                        $result = $db->GetRow($query);
                                        if ($result) {
                                            $notice_custom_data = get_custom_data($result['users_id'], [], $config);

                                            if (!send_notice('subscribe', $group_ids, $notice_custom_data, $config)) {
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
                            break;
                        case 'adm-unsubscribe':
                        case 'usr-unsubscribe':
                            $subscriber_ids = [];
                            $group_ids = unserialize($confirm['group_ids']);

                            if (is_array($group_ids)) {
                                foreach ($group_ids as $group_id) {
                                    $query = 'SELECT `users_id` FROM `'.TABLES_PREFIX.'users` WHERE `group_id` = '.$db->qstr($group_id).' AND `email_address` = '.$db->qstr(strtolower($confirm['email_address']));
                                    $result = $db->GetRow($query);
                                    if ($result && (!in_array($result['users_id'], $subscriber_ids))) {
                                        $subscriber_ids[] = $result['users_id'];
                                    }
                                }

                                if (!count($subscriber_ids)) {
                                    ++$ERROR;
                                    $TITLE = $LANGUAGE_PACK['error_default_title'];
                                    $MESSAGE = $LANGUAGE_PACK['error_unsubscribe_email_not_exists'];
                                }
                            } else {
                                ++$ERROR;
                                $TITLE = $LANGUAGE_PACK['error_default_title'];
                                $MESSAGE = $LANGUAGE_PACK['error_confirm_unable_request'];
                            }

                            if (!$ERROR) {
                                if ($config[ENDUSER_UNSUBNOTICE] == 'yes') {
                                    $notice_custom_data = get_custom_data($subscriber_ids[0], [], $config);
                                }

                                $result = subscriber_remove($subscriber_ids, $config);
                                if ($result) {
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
                            $ERROR++;
                            $TITLE = $LANGUAGE_PACK['error_default_title'];
                            $MESSAGE = $LANGUAGE_PACK['error_confirm_unable_request'];
                            break;
                    }
                } else {
                    ++$ERROR;
                    $TITLE = $LANGUAGE_PACK['error_default_title'];
                    $MESSAGE = $LANGUAGE_PACK['error_confirm_unable_request'];
                }
                break;
            default:
                $groups_info = groups_information(unserialize($confirm['group_ids']));

                if (is_array($groups_info) && count($groups_info)) {
                    $TITLE = $LANGUAGE_PACK['page_confirm_title'];

                    $MESSAGE = '';
                    $MESSAGE .= $LANGUAGE_PACK['page_confirm_message_sentence'];
                    $MESSAGE .= "<div style=\"height: 5px\">&nbsp;</div>\n";
                    $MESSAGE .= '<form action="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_CONFIRM_FILENAME]."\" method=\"get\">\n";
                    $MESSAGE .= "<input type=\"hidden\" name=\"action\" value=\"confirm\" />\n";
                    $MESSAGE .= '<input type="hidden" name="id" value="'.(int) trim($LM_REQUEST['id'])."\" />\n";
                    $MESSAGE .= '<input type="hidden" name="code" value="'.public_html_encode(trim($LM_REQUEST['code']))."\" />\n";
                    $MESSAGE .= "<table cellspacing=\"0\" cellpadding=\"2\" border=\"0\">\n";
                    $MESSAGE .= "<tfoot>\n";
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= "		<td colspan=\"2\" style=\"text-align: right; padding-top: 5px\">\n";
                    $MESSAGE .= '			<input type="button" value="'.$LANGUAGE_PACK['page_confirm_cancel_button']."\" onclick=\"window.location='".$config[PREF_PUBLIC_URL].$config[ENDUSER_HELP_FILENAME]."'\" />\n";
                    $MESSAGE .= '			<input type="submit" value="'.$LANGUAGE_PACK['page_confirm_submit_button']."\" />\n";
                    $MESSAGE .= "		</td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= "</tfoot>\n";
                    $MESSAGE .= "<tbody>\n";
                    if ($confirm['firstname'] != '') {
                        $MESSAGE .= "<tr>\n";
                        $MESSAGE .= '	<td style="white-space: nowrap">'.$LANGUAGE_PACK['page_confirm_firstname']."</td>\n";
                        $MESSAGE .= '	<td style="padding-left: 5px; font-weight: bold">'.public_html_encode($confirm['firstname'])."</td>\n";
                        $MESSAGE .= "</tr>\n";
                    }
                    if ($confirm['lastname'] != '') {
                        $MESSAGE .= "<tr>\n";
                        $MESSAGE .= '	<td style="white-space: nowrap">'.$LANGUAGE_PACK['page_confirm_lastname']."</td>\n";
                        $MESSAGE .= '	<td style="padding-left: 5px; font-weight: bold">'.public_html_encode($confirm['lastname'])."</td>\n";
                        $MESSAGE .= "</tr>\n";
                    }
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= '		<td style="white-space: nowrap">'.$LANGUAGE_PACK['page_confirm_email_address']."</td>\n";
                    $MESSAGE .= '		<td style="padding-left: 5px; font-weight: bold">'.public_html_encode($confirm['email_address'])."</td>\n";
                    $MESSAGE .= "	</tr>\n";

                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= '		<td style="vertical-align: top; white-space: nowrap">'.$LANGUAGE_PACK['page_confirm_group_info']."</td>\n";
                    $MESSAGE .= "		<td style=\"padding-left: 5px; font-weight: bold\">\n";
                    foreach ($groups_info as $group_info) {
                        $MESSAGE .= '&rarr; '.public_html_encode($group_info['name'])."<br />\n";
                    }
                    $MESSAGE .= "		</td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= "</tbody>\n";
                    $MESSAGE .= "</table>\n";
                    $MESSAGE .= "</form>\n";
                } else {
                    ++$ERROR;
                    $TITLE = $LANGUAGE_PACK['error_default_title'];
                    $MESSAGE = $LANGUAGE_PACK['error_confirm_unable_find_info'];
                }
                break;
        }
    }

    require_once 'eu_footer.inc.php';
}
