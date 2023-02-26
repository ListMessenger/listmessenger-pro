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

    $ERRORMSG = [];
    $NOTICE = 0;
    $NOTICEMSG = [];
    $MESSAGE_DETAILS = [];
    $MESSAGE_ID = 0;
    $GROUP_ID = 0;

    if ($config[ENDUSER_FORWARD] == 'yes') {
        /*
         * Check if there is a message_id and group_id in the request.
         */
        if ((!empty($LM_REQUEST['id'])) && ($LM_REQUEST['id'] = clean_input($LM_REQUEST['id'], 'nows'))) {
            if (is_array($pieces = explode(':', $LM_REQUEST['id']))) {
                if ((!empty($pieces[0])) && ($tmp_input = clean_input($pieces[0], ['int']))) {
                    $MESSAGE_ID = $tmp_input;
                }
                if ((!empty($pieces[1])) && ($tmp_input = clean_input($pieces[1], ['int']))) {
                    $GROUP_ID = $tmp_input;
                }
            }
        }

        if ($MESSAGE_ID) {
            $query = '
						SELECT a.*, b.`target`
						FROM `'.TABLES_PREFIX.'messages` AS a
						LEFT JOIN `'.TABLES_PREFIX.'queue` AS b
						ON b.`message_id` = a.`message_id`
						WHERE a.`message_id` = '.$db->qstr($MESSAGE_ID)."
						AND b.`status` = 'Complete'";
            $results = $db->GetAll($query);
            if ($results) {
                $allow_forward = false;

                foreach ($results as $result) {
                    if (($target = unserialize($result['target'])) && is_array($target)) {
                        if (array_key_exists('subscriber', $target)) {
                            $allow_forward = true;
                        } else {
                            $target_groups = groups_information($target);

                            if (is_array($target_groups) && ($total_groups = count($target_groups))) {
                                $private_groups = 0;

                                foreach ($target_groups as $group) {
                                    if ($group['private'] == 'true') {
                                        ++$private_groups;
                                    }
                                }

                                if ($private_groups < $total_groups) {
                                    $MESSAGE_DETAILS = $result;
                                    $allow_forward = true;

                                    break;
                                }
                            }
                        }
                    }
                }

                /*
                 * If this message was sent to only private groups,
                 * do not display the message.
                 */
                if (!$allow_forward) {
                    ++$ERROR;
                    $TITLE = $LANGUAGE_PACK['error_default_title'];
                    $MESSAGE = $LANGUAGE_PACK['page_forward_error_private'];
                }
            } else {
                ++$ERROR;
                $TITLE = $LANGUAGE_PACK['error_default_title'];
                $MESSAGE = $LANGUAGE_PACK['page_forward_error_no_message'];
            }
        } else {
            ++$ERROR;
            $TITLE = $LANGUAGE_PACK['error_default_title'];
            $MESSAGE = $LANGUAGE_PACK['page_forward_error_no_message'];
        }

        if (!$ERROR) {
            if (empty($LM_REQUEST['action'])) {
                $LM_REQUEST['action'] = '';
            }

            /*
             * Error Checking
             */
            switch ($LM_REQUEST['action']) {
                case 'process':
                    $PROCESSED = [];

                    /*
                     * Not Required: If there is a group_id attached, attach it.
                     */
                    if ($GROUP_ID) {
                        $query = 'SELECT * FROM `'.TABLES_PREFIX.'groups` WHERE `groups_id` = '.$db->qstr($GROUP_ID)." AND `group_private` = 'false'";
                        $result = $db->GetRow($query);
                        if ($result) {
                            $PROCESSED['group_id'] = $GROUP_ID;
                        } else {
                            $PROCESSED['group_id'] = 0;
                        }
                    } else {
                        $PROCESSED['group_id'] = 0;
                    }

                    /*
                     * Required: Your Name
                     */
                    if ((!empty($LM_REQUEST['name'])) && ($tmp_input = substr(clean_input($LM_REQUEST['name'], ['trim', 'notags', 'specialchars', 'emailcontent', 'emailheaders']), 0, 64))) {
                        $PROCESSED['name'] = $tmp_input;
                    } else {
                        ++$NOTICE;
                        $NOTICEMSG[] = $LANGUAGE_PACK['page_forward_error_from_name'];
                    }

                    /*
                     * Required: Your E-Mail Address
                     */
                    if ((!empty($LM_REQUEST['addr'])) && valid_address($tmp_input = substr(clean_input($LM_REQUEST['addr'], ['trim', 'notags', 'specialchars', 'emailcontent', 'emailheaders']), 0, 128))) {
                        $address_pieces = explode('@', $tmp_input);

                        /*
                         * Check for the "from" e-mail address in the Banned e-mail addresses and banned domain names
                         */
                        if (is_array($config[ENDUSER_BANEMAIL]) && banned_address($tmp_input, $config[ENDUSER_BANEMAIL])) {
                            ++$NOTICE;
                            $NOTICEMSG[] = $LANGUAGE_PACK['error_subscribe_banned_email'];
                        } elseif (is_array($config[ENDUSER_BANIPS]) && (!empty($_SERVER['REMOTE_ADDR'])) && banned_ip($_SERVER['REMOTE_ADDR'], $config[ENDUSER_BANIPS])) {
                            ++$NOTICE;
                            $NOTICEMSG[] = $LANGUAGE_PACK['error_subscribe_banned_ip'];
                        } elseif (($config[ENDUSER_MXRECORD] == 'yes') && (getmxrr($address_pieces[1].'.', $mxhosts) == false) && (gethostbyname($address_pieces[1].'.') == $address_pieces[1].'.')) {
                            ++$NOTICE;
                            $NOTICEMSG[] = $LANGUAGE_PACK['error_subscribe_invalid_domain'];
                        } else {
                            /*
                             * The from e-mail address is good to go.
                             */
                            $PROCESSED['addr'] = $tmp_input;
                        }
                    } else {
                        ++$NOTICE;
                        $NOTICEMSG[] = $LANGUAGE_PACK['page_forward_error_from_email'];
                    }

                    /*
                     * Required: Friend's Name
                     */
                    if ((!empty($LM_REQUEST['friend_name'])) && ($tmp_input = substr(clean_input($LM_REQUEST['friend_name'], ['trim', 'notags', 'specialchars', 'emailcontent', 'emailheaders']), 0, 64))) {
                        $PROCESSED['friend_name'] = $tmp_input;
                    } else {
                        ++$NOTICE;
                        $NOTICEMSG[] = $LANGUAGE_PACK['page_forward_error_friend_name'];
                    }

                    /*
                     * Required: Friend's E-Mail Address
                     */
                    if ((!empty($LM_REQUEST['friend_addr'])) && valid_address($tmp_input = substr(clean_input($LM_REQUEST['friend_addr'], ['trim', 'notags', 'specialchars', 'emailcontent', 'emailheaders']), 0, 128))) {
                        $address_pieces = explode('@', $tmp_input);

                        /*
                         * Check for the "friends" e-mail address in the Banned e-mail addresses and banned domain names
                         */
                        if (is_array($config[ENDUSER_BANEMAIL]) && banned_address($tmp_input, $config[ENDUSER_BANEMAIL])) {
                            ++$NOTICE;
                            $NOTICEMSG[] = $LANGUAGE_PACK['error_subscribe_banned_email'];
                        } elseif (is_array($config[ENDUSER_BANIPS]) && (!empty($_SERVER['REMOTE_ADDR'])) && banned_ip($_SERVER['REMOTE_ADDR'], $config[ENDUSER_BANIPS])) {
                            ++$NOTICE;
                            $NOTICEMSG[] = $LANGUAGE_PACK['error_subscribe_banned_ip'];
                        } elseif (($config[ENDUSER_MXRECORD] == 'yes') && (getmxrr($address_pieces[1].'.', $mxhosts) == false) && (gethostbyname($address_pieces[1].'.') == $address_pieces[1].'.')) {
                            ++$NOTICE;
                            $NOTICEMSG[] = $LANGUAGE_PACK['error_subscribe_invalid_domain'];
                        } else {
                            /*
                             * The friends e-mail address is good to go.
                             */
                            $PROCESSED['friend_addr'] = $tmp_input;
                        }
                    } else {
                        ++$NOTICE;
                        $NOTICEMSG[] = $LANGUAGE_PACK['page_forward_error_friend_email'];
                    }

                    /*
                     * Not Required: Optional Message
                     */
                    if ((!empty($LM_REQUEST['message'])) && ($tmp_input = substr(clean_input($LM_REQUEST['message'], ['trim', 'notags', 'specialchars', 'emailcontent']), 0, 400))) {
                        $PROCESSED['message'] = $tmp_input;
                    } else {
                        $PROCESSED['message'] = '';
                    }

                    /*
                     * Error check: CAPTCHA Code / captcha_code
                     */
                    if ($config[ENDUSER_CAPTCHA] == 'yes') {
                        if ((empty($LM_REQUEST['captcha_code'])) || (!PhpCaptcha::Validate($LM_REQUEST['captcha_code']))) {
                            ++$NOTICE;
                            $NOTICEMSG[] = $LANGUAGE_PACK['page_captcha_invalid'];
                        }
                    }

                    if (!$NOTICE) {
                        $text_message_prefix = str_ireplace(['[from_name]', '[optional_message]'], [$PROCESSED['name'], ($PROCESSED['message'] != '') ? "\n".substr($PROCESSED['message'], 0, 255)."\n" : ''], $LANGUAGE_PACK['page_forward_text_message_prefix']);
                        $html_message_prefix = str_ireplace(['[from_name]', '[optional_message]'], [$PROCESSED['name'], ($PROCESSED['message'] != '') ? '<br /><em>'.nl2br(substr($PROCESSED['message'], 0, 255)).'</em><br />' : ''], $LANGUAGE_PACK['page_forward_html_message_prefix']);

                        if ($GROUP_ID) {
                            $subscribe_url = $config[PREF_PUBLIC_URL].$config[ENDUSER_FILENAME].'?action=subscribe&group_ids='.$GROUP_ID.'&addr='.rawurlencode($PROCESSED['friend_addr']);

                            $text_subscribe_paragraph = str_ireplace('[subscribe_url]', $subscribe_url, $LANGUAGE_PACK['page_forward_text_subscribe_paragraph']);
                            $html_subscribe_paragraph = str_ireplace('[subscribe_url]', $subscribe_url, $LANGUAGE_PACK['page_forward_html_subscribe_paragraph']);

                            $text_message_prefix = str_ireplace('[subscribe_paragraph]', $text_subscribe_paragraph, $text_message_prefix);
                            $html_message_prefix = str_ireplace('[subscribe_paragraph]', '<br />'.$html_subscribe_paragraph, $html_message_prefix);
                        } else {
                            $text_message_prefix = str_ireplace('[subscribe_paragraph]', '', $text_message_prefix);
                            $html_message_prefix = str_ireplace('[subscribe_paragraph]', '', $html_message_prefix);
                        }

                        try {
                            $mail = new LM_Mailer($config);
                            $mail->Priority = $MESSAGE_DETAILS['message_priority'];
                            $mail->From = $PROCESSED['addr'];
                            $mail->FromName = $PROCESSED['name'];
                            $mail->AddReplyTo($PROCESSED['addr'], $PROCESSED['name']);

                            $date = time();
                            $subject = $LANGUAGE_PACK['page_forward_subject_prefix'].$MESSAGE_DETAILS['message_subject'].$LANGUAGE_PACK['page_forward_subject_suffix'];

                            $text_template = $MESSAGE_DETAILS['text_template'];
                            $text_message = $text_message_prefix."\n\n".$MESSAGE_DETAILS['text_message']."\n\n".$LANGUAGE_PACK['page_forward_text_message_suffix'];

                            $html_template = ($MESSAGE_DETAILS['html_template'] ?: '');
                            $html_message = ($MESSAGE_DETAILS['html_message'] ?: '');

                            /*
                             * If there is an HTML message prefix or suffix it needs
                             * to be added.
                             */
                            if (!empty($html_message) && ($html_message_prefix != '' || $LANGUAGE_PACK['page_forward_html_message_suffix'] != '')) {
                                /*
                                 * Search for the <body> tag and add the prefix and
                                 * suffix in the correct place.
                                 */
                                if (preg_match('/(<body(.*)>)/Usi', $html_message, $matches) && is_array($matches) && ($body_tag = trim($matches[0]))) {
                                    $html_message = str_ireplace([$body_tag, '</body>'], [$body_tag.$html_message_prefix.'<br /><br />', '<br /><br />'.$LANGUAGE_PACK['page_forward_html_message_suffix'].'</body>'], $MESSAGE_DETAILS['html_message']);
                                } else {
                                    $html_message = $html_message_prefix.'<br /><br />'.$MESSAGE_DETAILS['html_message'].'<br /><br />'.$LANGUAGE_PACK['page_forward_html_message_suffix'];
                                }
                            }

                            /*
                             * Look for attachments on this message, if they're there and valid, attach them.
                             */
                            if (!empty($MESSAGE_DETAILS['attachments'])) {
                                $attachments = unserialize($MESSAGE_DETAILS['attachments']);
                                if (is_array($attachments)) {
                                    foreach ($attachments as $filename) {
                                        if (file_exists($config[PREF_PUBLIC_PATH].'files/'.$filename)) {
                                            $mail->AddAttachment($config[PREF_PUBLIC_PATH].'files/'.$filename);
                                        }
                                    }
                                }
                            }

                            $user_data = [];
                            $user_data['name'] = $PROCESSED['friend_name'];
                            $user_data['firstname'] = $PROCESSED['friend_name'];	// @todo We should try to at least guess the firstname.
                            $user_data['lastname'] = ''; 							// @todo We should try to at least guess the lastname.
                            $user_data['email'] = $PROCESSED['friend_addr'];
                            $user_data['email_address'] = $PROCESSED['friend_addr'];
                            $user_data['date'] = display_date($config[PREF_DATEFORMAT], time());
                            $user_data['groupname'] = (($GROUP_ID) ? groups_information($GROUP_ID, true, true) : '');
                            $user_data['groupid'] = $GROUP_ID;
                            $user_data['userid'] = 0;
                            $user_data['messageid'] = $MESSAGE_ID;
                            $user_data['signupdate'] = $user_data['date'];
                            $user_data['archiveurl'] = $config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME].'?id='.$user_data['messageid'];
                            $user_data['profileurl'] = $config[PREF_PUBLIC_URL].$config[ENDUSER_PROFILE_FILENAME].'?addr='.rawurlencode($user_data['email_address']);
                            $user_data['forwardurl'] = $config[PREF_PUBLIC_URL].$config[ENDUSER_FORWARD_FILENAME].'?id='.$user_data['messageid'].':'.$user_data['groupid'].'&addr='.rawurlencode($user_data['email_address']);

                            $mail->AddCustomHeader('List-Help: <'.$config[PREF_PUBLIC_URL].$config[ENDUSER_HELP_FILENAME].'>');
                            $mail->AddCustomHeader('List-Owner: <mailto:'.$mail->From.'> ('.$mail->FromName.')');
                            $mail->AddCustomHeader('List-Unsubscribe: <'.$config[PREF_PUBLIC_URL].$config[ENDUSER_UNSUB_FILENAME].'?addr='.$user_data['email'].'>');
                            $mail->AddCustomHeader('List-Archive: <'.$config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME].'>');
                            $mail->AddCustomHeader('List-Post: NO');

                            $mail->Subject = custom_data($user_data, $subject);

                            if (!empty($html_message) && strlen(trim($html_message)) > 0) {
                                $mail->Body = custom_data($user_data, unsubscribe_message(insert_template('html', $html_template, $html_message), 'html', $config));
                                $mail->AltBody = custom_data($user_data, unsubscribe_message(insert_template('text', $text_template, $text_message), 'text', $config));
                            } else {
                                $mail->Body = custom_data($user_data, unsubscribe_message(insert_template('text', $text_template, $text_message), 'text', $config));
                            }

                            $mail->ClearAllRecipients();
                            $mail->AddAddress($user_data['email'], $user_data['name']);

                            if ($mail->IsError() || (!$mail->Send())) {
                                throw new Exception($LANGUAGE_PACK['page_forward_error_failed_send']);
                            }
                        } catch (Exception $e) {
                            ++$NOTICE;
                            $NOTICEMSG[] = $e->getMessage();

                            if (!empty($config[PREF_ERROR_LOGGING]) && ($config[PREF_ERROR_LOGGING] == 'yes')) {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\t".$e->getMessage()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }
                        }
                    }

                    if ($NOTICE) {
                        $LM_REQUEST['action'] = 'form';
                    }

                    break;
                case 'form':
                default:
                    break;
            }

            /*
             * Display Content
             */
            switch ($LM_REQUEST['action']) {
                case 'process':
                    $TITLE = $LANGUAGE_PACK['page_forward_title'];
                    $MESSAGE = str_ireplace('[email_address]', $PROCESSED['friend_addr'], $LANGUAGE_PACK['page_forward_successful_send']);
                    break;
                case 'form':
                default:
                    $TITLE = $LANGUAGE_PACK['page_forward_title'];
                    $MESSAGE = '';

                    if ($NOTICE) {
                        $MESSAGE .= display_error($NOTICEMSG);
                    }

                    $MESSAGE .= '<form action="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_FORWARD_FILENAME]."\" method=\"post\">\n";
                    $MESSAGE .= "<input type=\"hidden\" name=\"action\" value=\"process\" />\n";
                    $MESSAGE .= '<input type="hidden" name="id" value="'.public_html_encode($LM_REQUEST['id'])."\" />\n";
                    $MESSAGE .= "<table style=\"width: 75%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">\n";
                    $MESSAGE .= "<colgroup>\n";
                    $MESSAGE .= "	<col style=\"width: 35%\" />\n";
                    $MESSAGE .= "	<col style=\"width: 65%\" />\n";
                    $MESSAGE .= "</colgroup>\n";
                    $MESSAGE .= "<thead>\n";
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= '		<td colspan="2">'.$LANGUAGE_PACK['page_forward_message_sentence']."</td>\n";
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
                    $MESSAGE .= '		<td colspan="2"><h2>'.$LANGUAGE_PACK['page_forward_from_header']."</h2></td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= '		<td><label for="name" class="required">'.$LANGUAGE_PACK['page_forward_from_name']."</label></td>\n";
                    $MESSAGE .= '		<td><input type="text" id="name" name="name" value="'.((!empty($LM_REQUEST['name'])) ? clean_input($LM_REQUEST['name'], ['trim', 'notags', 'specialchars']) : '')."\" maxlength=\"64\" /></td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= '		<td><label for="addr" class="required">'.$LANGUAGE_PACK['page_forward_from_email']."</label></td>\n";
                    $MESSAGE .= '		<td><input type="text" id="addr" name="addr" value="'.(((!empty($LM_REQUEST['addr'])) && valid_address($LM_REQUEST['addr'])) ? clean_input($LM_REQUEST['addr'], ['trim', 'notags', 'specialchars']) : '')."\" maxlength=\"128\" /></td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= '		<td colspan="2"><h2>'.$LANGUAGE_PACK['page_forward_friend_header']."</h2></td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= '		<td><label for="friend_name" class="required">'.$LANGUAGE_PACK['page_forward_friend_name']."</label></td>\n";
                    $MESSAGE .= '		<td><input type="text" id="friend_name" name="friend_name" value="'.((!empty($LM_REQUEST['friend_name'])) ? clean_input($LM_REQUEST['friend_name'], ['trim', 'notags', 'specialchars']) : '')."\" maxlength=\"64\" /></td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= '		<td><label for="friend_addr" class="required">'.$LANGUAGE_PACK['page_forward_friend_email']."</label></td>\n";
                    $MESSAGE .= '		<td><input type="text" id="friend_addr" name="friend_addr" value="'.(((!empty($LM_REQUEST['friend_addr'])) && valid_address($LM_REQUEST['friend_addr'])) ? clean_input($LM_REQUEST['friend_addr'], ['trim', 'notags', 'specialchars']) : '')."\" maxlength=\"128\" /></td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= "		<td colspan=\"2\">&nbsp;</td>\n";
                    $MESSAGE .= "	</tr>\n";
                    $MESSAGE .= "	<tr>\n";
                    $MESSAGE .= '		<td style="vertical-align: top"><label for="message">'.$LANGUAGE_PACK['page_forward_optional_message']."</label></td>\n";
                    $MESSAGE .= "		<td><textarea id=\"message\" name=\"message\" rows=\"5\" cols=\"35\" style=\"width: 99%; height: 75px\" onkeyup=\"(this.value.length > 400) ? this.value = this.value.substring(0, 400) : ''\">".((!empty($LM_REQUEST['message'])) ? clean_input($LM_REQUEST['message'], ['trim', 'notags', 'emailcontent']) : '')."</textarea></td>\n";
                    $MESSAGE .= "	</tr>\n";

                    if ($config[ENDUSER_CAPTCHA] == 'yes') {
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
                    break;
            }
        }
    } else {
        $abuse = encode_address($config[PREF_ABUEMAL_ID]);
        $TITLE = $LANGUAGE_PACK['page_forward_closed_title'];
        $MESSAGE = $LANGUAGE_PACK['page_forward_closed_message_sentence'];
        $MESSAGE = str_replace('[abuse_address]', '<a href="mailto:'.$abuse['address'].'" style="font-weight: strong">'.$abuse['text'].'</a>', $MESSAGE);
    }

    require_once 'eu_footer.inc.php';
}
