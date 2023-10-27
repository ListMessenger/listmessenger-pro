<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */

// Setup PHP and start page setup.
ini_set('include_path', str_replace('\\', '/', dirname(__FILE__)).'/includes');
ini_set('allow_url_fopen', 1);
ini_set('session.name', md5(dirname(__FILE__)));
ini_set('session.use_trans_sid', 0);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_secure', 0);
ini_set('session.referer_check', '');
ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('magic_quotes_runtime', 0);

set_time_limit(300);	// Max run time, 5 minutes.

define('IN_SENDING_ENGINE', true);

require_once 'pref_ids.inc.php';
require_once 'config.inc.php';
require_once 'classes/adodb/adodb.inc.php';
require_once 'dbconnection.inc.php';

session_start();

echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
echo "<body>\n";

if ((empty($_SESSION['isAuthenticated'])) || (!(bool) $_SESSION['isAuthenticated'])) {
    echo "<script type=\"text/javascript\">\n";
    echo "\talert('Failed to authenticate your session identifier.\\n\\nPlease ensure your servers session support is properly configured and you provide an authenticated session identifier to the engine.');\n";
    echo "</script>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit;
}

require_once 'functions.inc.php';
require_once 'loader.inc.php';

// Check the connecting IP address against the blacklisted IP address list.
if ((!empty($_SERVER['REMOTE_ADDR'])) && banned_ip($_SERVER['REMOTE_ADDR'], $_SESSION['config'][ENDUSER_BANIPS])) {
    echo "The IP address you are attempting to connect from is prohibited from accessing this system.\n";
    echo "<br /><br />\n";
    echo 'Please contact the website administrator for further assistance.';

    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tA banned IP address [".$_SERVER['REMOTE_ADDR']."] attempted to connect to ListMessenger but was blocked.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
    }
    exit;
}

if ((empty($_GET['qid'])) || (!(int) trim($_GET['qid']))) {
    echo "<script type=\"text/javascript\">\n";
    echo "\talert('Failed to locate a queue identifier in your request.\\n\\nPlease ensure you provide a valid queue identifier to the engine.');\n";
    echo "</script>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit;
} else {
    $query = 'SELECT * FROM `'.TABLES_PREFIX."queue` WHERE `queue_id` = '".(int) trim($_GET['qid'])."'";
    $result = $db->GetRow($query);
    if (!$result) {
        echo "<script type=\"text/javascript\">\n";
        echo "\talert('Failed to load the queue identifier you have specified in your request.\\n\\nPlease ensure you provide a valid queue identifier to the engine.');\n";
        echo "</script>\n";
        echo "</body>\n";
        echo "</html>\n";
        exit;
    } else {
        $_SESSION['queue_details'] = [];
        $_SESSION['queue_details']['queue_id'] = $result['queue_id'];
        $_SESSION['queue_details']['message_id'] = $result['message_id'];
        $_SESSION['queue_details']['date'] = $result['date'];
        $_SESSION['queue_details']['touch'] = $result['touch'];
        $_SESSION['queue_details']['target'] = unserialize($result['target']);
        $_SESSION['queue_details']['progress'] = $result['progress'];
        $_SESSION['queue_details']['total'] = $result['total'];
        $_SESSION['queue_details']['status'] = $result['status'];
    }
}

$ACTION = (!empty($_GET['action']) ? clean_input($_GET['action'], 'alpha') : false);

switch ($ACTION) {
    case 'cancel':
        $query = 'DELETE FROM `'.TABLES_PREFIX."sending` WHERE `queue_id` = '".(int) trim($_SESSION['queue_details']['queue_id'])."'";
        if ($db->Execute($query)) {
            $query = 'OPTIMIZE TABLE `'.TABLES_PREFIX.'sending`';
            if (!$db->Execute($query)) {
                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to optimize the sending table. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            }
            $query = 'UPDATE `'.TABLES_PREFIX."queue` SET `status`='Cancelled' WHERE `queue_id` = '".(int) trim($_SESSION['queue_details']['queue_id'])."'";
            if (!$db->Execute($query)) {
                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to update queue status in the queue table. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            }
        } else {
            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete queue data in the sending table. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
        }

        echo "<script type=\"text/javascript\">\n";
        echo "\tparent.document.getElementById('buttonHTML').innerHTML	= '';\n";
        echo "\tparent.document.getElementById('progressText').innerHTML = 'Successfully cancelled; returning to the message centre in 5 seconds.';\n";
        echo "\tsetTimeout('parent.window.location=\'index.php?section=message&action=view&id=".(int) trim($_SESSION['message_details']['message_id'])."\'', 5000);";
        echo "</script>\n";

        unset($_SESSION['queue_details'], $_SESSION['message_details']);
        break;
    case 'pause':
        if ($_SESSION['queue_details']['status'] != 'Paused') {
            $query = 'UPDATE `'.TABLES_PREFIX."queue` SET `status`='Paused', `touch`='".time()."' WHERE `queue_id`='".(int) trim($_SESSION['queue_details']['queue_id'])."'";
            $db->Execute($query);
        }
        echo "<script type=\"text/javascript\">\n";
        echo "\tparent.document.getElementById('buttonHTML').innerHTML	= '<form><input type=\"button\" class=\"button\" value=\"Resume\" onclick=\"document.getElementById(\'workerFrame\').src = \'sender.php?qid=".(int) $_SESSION['queue_details']['queue_id']."&action=resume\'\" />&nbsp;<input type=\"button\" class=\"button\" value=\"Cancel\" onclick=\"document.getElementById(\'workerFrame\').src = \'sender.php?qid=".(int) $_SESSION['queue_details']['queue_id']."&action=cancel\'\" /></form>';\n";
        echo "\tparent.document.getElementById('progressText').innerHTML	= 'This send has been paused.';\n";
        echo "</script>\n";
        ob_flush();
        break;
    case 'resume':
        if ($_SESSION['queue_details']['status'] != 'Resuming') {
            $query = 'UPDATE `'.TABLES_PREFIX."queue` SET `status`='Resuming', `touch`='".time()."' WHERE `queue_id`='".(int) trim($_SESSION['queue_details']['queue_id'])."'";
            $db->Execute($query);
        }
        echo "<script type=\"text/javascript\">\n";
        echo "\tparent.document.getElementById('buttonHTML').innerHTML	= '';\n";
        echo "\tparent.document.getElementById('progressText').innerHTML	= 'Resuming the send. Please wait...';\n";
        echo "\twindow.location='sender.php?qid=".(int) $_SESSION['queue_details']['queue_id']."&action=send';";
        echo "</script>\n";
        ob_flush();
        break;
    case 'send':
        if (!empty($_SESSION['message_details'])) {
            require_once 'classes/lm_mailer.class.php';

            try {
                $mail = new LM_Mailer($_SESSION['config']);
                $mail->Priority = $_SESSION['message_details']['message_priority'];

                if ($_SESSION['queue_details']['status'] != 'Sending') {
                    $query = 'UPDATE `'.TABLES_PREFIX."queue` SET `status`='Sending', `touch`='".time()."' WHERE `queue_id`='".checkslashes($_SESSION['queue_details']['queue_id'])."'";
                    $db->Execute($query);
                }
                echo "<script type=\"text/javascript\">\n";
                echo "\tparent.document.getElementById('progressBar').style.display = ''\n";
                echo "\tparent.document.getElementById('progressText').innerHTML = 'Sending messages. Please wait...';\n";
                echo "\tparent.document.getElementById('buttonHTML').innerHTML	= '<form><input type=\"button\" class=\"button\" value=\"Pause\" onclick=\"document.getElementById(\'workerFrame\').src = \'sender.php?qid=".(int) $_SESSION['queue_details']['queue_id']."&action=pause\'\" />&nbsp;<input type=\"button\" class=\"button\" value=\"Cancel\" onclick=\"document.getElementById(\'workerFrame\').src = \'sender.php?qid=".(int) $_SESSION['queue_details']['queue_id']."&action=cancel\'\" /></form>';\n";
                echo "</script>\n";
                ob_flush();

                $from_pieces = explode('" <', $_SESSION['message_details']['message_from']);
                $mail->From = substr($from_pieces[1], 0, strlen($from_pieces[1]) - 1);
                $mail->FromName = substr($from_pieces[0], 1, strlen($from_pieces[0]));

                $reply_pieces = explode('" <', $_SESSION['message_details']['message_reply']);
                $mail->AddReplyTo(substr($reply_pieces[1], 0, strlen($reply_pieces[1]) - 1), substr($reply_pieces[0], 1, strlen($reply_pieces[0])));

                $date = time();
                $subject = $_SESSION['message_details']['message_subject'];

                $html_template = $_SESSION['message_details']['html_template'];
                $html_message = $_SESSION['message_details']['html_message'];

                $text_template = $_SESSION['message_details']['text_template'];
                $text_message = $_SESSION['message_details']['text_message'];

                // Look for attachments on this message, if they're there and valid, attach them.
                if ($_SESSION['message_details']['attachments'] != '') {
                    $attachments = unserialize($_SESSION['message_details']['attachments']);
                    if (is_array($attachments) && (count($attachments) > 0)) {
                        foreach ($attachments as $filename) {
                            if (file_exists($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename)) {
                                $mail->AddAttachment($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename);
                            }
                        }
                    }
                }

                $progress = $_SESSION['queue_details']['progress'];
                $errors = 0;

                $query = 'SELECT * FROM `'.TABLES_PREFIX."sending` WHERE `queue_id` = '".(int) trim($_GET['qid'])."' ORDER BY `sending_id` ASC LIMIT ".(int) trim($progress).', '.(int) $_SESSION['config'][PREF_MSG_PER_REFRESH];
                $results = $db->GetAll($query);
                if ($results) {
                    foreach ($results as $result) {
                        ++$progress;
                        if ($result['sent'] == '0') {
                            if ($user_data = get_custom_data($result['users_id'], ['messageid' => (int) $_SESSION['message_details']['message_id'], 'queueid' => (int) trim($_GET['qid'])], $_SESSION['config'])) {
                                if (is_array($user_data) && valid_address($user_data['email'])) {
                                    $mail->AddCustomHeader('List-Help: <'.$_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_HELP_FILENAME].'>');
                                    $mail->AddCustomHeader('List-Owner: <mailto:'.$mail->From.'> ('.$mail->FromName.')');
                                    $mail->AddCustomHeader('List-Unsubscribe: <'.$_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_UNSUB_FILENAME].'?addr='.$user_data['email'].'>');
                                    $mail->AddCustomHeader('List-Archive: <'.$_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_ARCHIVE_FILENAME].'>');
                                    $mail->AddCustomHeader('List-Post: NO');

                                    $mail->Subject = custom_data($user_data, $subject);

                                    if (strlen(trim($html_message)) > 0) {
                                        $mail->Body = custom_data($user_data, unsubscribe_message(insert_template('html', $html_template, $html_message), 'html', $_SESSION['config']));
                                        $mail->AltBody = custom_data($user_data, unsubscribe_message(insert_template('text', $text_template, $text_message), 'text', $_SESSION['config']));
                                    } else {
                                        $mail->Body = custom_data($user_data, unsubscribe_message(insert_template('text', $text_template, $text_message), 'text', $_SESSION['config']));
                                    }

                                    $mail->ClearAllRecipients();
                                    $mail->AddAddress($user_data['email'], $user_data['name']);

                                    if ($mail->Send()) {
                                        $query = 'UPDATE `'.TABLES_PREFIX."sending` SET `sent`='1' WHERE `sending_id` = '".(int) $result['sending_id']."'";
                                        $result = $db->Execute($query);
                                        if ($result) {
                                            $percentage = ceil(($progress / $_SESSION['queue_details']['total']) * 100);
                                            echo "<script type=\"text/javascript\">\n";
                                            echo "\tparent.document.getElementById('progressStatus').style.width = '".$percentage."%';\n";
                                            if ($percentage > 3) {
                                                echo "\tparent.document.getElementById('progressStatus').innerHTML = '".$percentage."%';\n";
                                            } else {
                                                echo "\tparent.document.getElementById('progressStatus').innerHTML = '';\n";
                                            }
                                            echo "</script>\n";
                                        } else {
                                            ++$errors;
                                            echo "<script type=\"text/javascript\">\n";
                                            echo "\tparent.document.getElementById('progressStatus').innerHTML = '';\n";
                                            echo "\tparent.document.getElementById('errorText').style.display = '';\n";
                                            echo "\tparent.document.getElementById('errorText').value += 'Failed to update sending record for ".$user_data['email'].", check error log for details.\\n';\n";
                                            echo "\tparent.document.getElementById('errorText').scrollTop = parent.document.getElementById('errorText').scrollHeight;\n";
                                            echo "</script>\n";

                                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to update sending record for ".$user_data['email'].'. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                            }
                                        }
                                    } else {
                                        ++$errors;
                                        echo "<script type=\"text/javascript\">\n";
                                        echo "\tparent.document.getElementById('progressStatus').innerHTML = '';\n";
                                        echo "\tparent.document.getElementById('errorText').style.display = '';\n";
                                        echo "\tparent.document.getElementById('errorText').value += 'Failed sending to ".$user_data['email'].", check error log for details.\\n';\n";
                                        echo "\tparent.document.getElementById('errorText').scrollTop = parent.document.getElementById('errorText').scrollHeight;\n";
                                        echo "</script>\n";

                                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send message to ".$user_data['email'].'. LM_Mailer responded: '.$mail->ErrorInfo."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                        }
                                    }

                                    $mail->ClearCustomHeaders();
                                    ob_flush();
                                }
                            }
                        }
                    }

                    $query = 'UPDATE `'.TABLES_PREFIX."queue` SET `progress` = '".(int) trim($progress)."', `touch` = '".time()."' WHERE `queue_id` = '".(int) trim($_SESSION['queue_details']['queue_id'])."'";
                    if ($db->Execute($query)) {
                        $total_batches = ceil($_SESSION['queue_details']['total'] / $_SESSION['config'][PREF_MSG_PER_REFRESH]);
                        $sent_batch = ($total_batches - ceil(($_SESSION['queue_details']['total'] - $progress) / $_SESSION['config'][PREF_MSG_PER_REFRESH]));

                        if ($sent_batch != $total_batches) {
                            echo "<script type=\"text/javascript\">\n";
                            echo "\tparent.document.getElementById('progressText').innerHTML = 'Sent batch ".$sent_batch.' of '.$total_batches.'; pausing for '.$_SESSION['config'][PREF_PAUSE_BETWEEN].' second'.(($_SESSION['config'][PREF_PAUSE_BETWEEN] != 1) ? 's' : '').".';\n";
                            echo "\tsetTimeout('window.location=\'sender.php?qid=".(int) $_SESSION['queue_details']['queue_id']."&action=send\'', ".($_SESSION['config'][PREF_PAUSE_BETWEEN] * 1000).');';
                            echo "</script>\n";
                        } else {
                            echo "<script type=\"text/javascript\">\n";
                            echo "\tparent.document.getElementById('buttonHTML').innerHTML	= '';\n";
                            echo "\tparent.document.getElementById('progressText').innerHTML = 'Sent batch ".$sent_batch.' of '.$total_batches.".';\n";
                            echo "\twindow.location = 'sender.php?qid=".(int) $_SESSION['queue_details']['queue_id']."&action=send';";
                            echo "</script>\n";
                        }
                    } else {
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to update queue information. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    }
                } else {
                    $query = 'DELETE FROM `'.TABLES_PREFIX."sending` WHERE `queue_id`='".checkslashes($_SESSION['queue_details']['queue_id'])."'";
                    if ($db->Execute($query)) {
                        $query = 'OPTIMIZE TABLE `'.TABLES_PREFIX.'sending`';
                        if (!$db->Execute($query)) {
                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to optimize the sending table. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }
                        }

                        $query = 'UPDATE `'.TABLES_PREFIX."queue` SET `status`='Complete' WHERE `queue_id`='".checkslashes($_SESSION['queue_details']['queue_id'])."'";
                        if (!$db->Execute($query)) {
                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to update queue status in the queue table. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }
                        }
                    } else {
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete queue data in the sending table. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    }

                    echo "<script type=\"text/javascript\">\n";
                    echo "\tparent.document.getElementById('progressText').innerHTML = 'Completed sending your message to ".$_SESSION['queue_details']['total'].' subscriber'.(($_SESSION['queue_details']['total'] != '1') ? 's' : '').". Click finish to continue.';\n";
                    echo "\tparent.document.getElementById('buttonHTML').innerHTML	= '<form><input type=\"button\" class=\"button\" value=\"Finish\" onclick=\"parent.window.location=\'index.php?section=message&action=view&id=".(int) $_SESSION['message_details']['message_id']."\'\" /></form>';\n";
                    echo "</script>\n";

                    unset($_SESSION['unsubscribe_message'], $_SESSION['queue_details'], $_SESSION['message_details']);
                }
            } catch (Exception $e) {
                echo "<script type=\"text/javascript\">\n";
                echo "\tparent.document.getElementById('progressText').innerHTML = '".$e->getMessage()."';\n";
                echo "\talert('".$e->getMessage()."');\n";
                echo "</script>\n";
                echo "</body>\n";
                echo "</html>\n";

                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\t".$e->getMessage()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
                exit;
            }
        } else {
            echo "<script type=\"text/javascript\">\n";
            echo "\tparent.document.getElementById('progressText').innerHTML = 'The required session details were not available, please try again.';\n";
            echo "\talert('The required session details were not available, please try again.\\n\\nIf this problem persists, please set PREF_DATABASE_SESSIONS to no in includes/pref_ids.inc.php.');\n";
            echo "</script>\n";
            echo "</body>\n";
            echo "</html>\n";

            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tLost the message_details session data or it was not properly set to begin with.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
            exit;
        }
        break;
    default: // Queue Messages.
        $query = 'UPDATE `'.TABLES_PREFIX."queue` SET `status` = 'Preparing' WHERE `queue_id` = '".(int) trim($_SESSION['queue_details']['queue_id'])."'";
        $db->Execute($query);

        echo "<script type=\"text/javascript\">parent.document.getElementById('progressText').innerHTML = 'Preparing data to generate the queue. Please wait...';</script>\n";
        ob_flush();

        if (is_array($_SESSION['queue_details']['target']) && (count($_SESSION['queue_details']['target']) > 0)) {
            echo "<script type=\"text/javascript\">parent.document.getElementById('progressText').innerHTML = 'Inserting queue data into the database. Please wait...';</script>\n";
            ob_flush();

            $query = 'INSERT INTO `'.TABLES_PREFIX."sending` (`email_address`, `users_id`, `queue_id`) SELECT `email_address`, `users_id`, '".checkslashes($_SESSION['queue_details']['queue_id'])."' FROM `".TABLES_PREFIX."users` WHERE `group_id` IN ('".implode("', '", $_SESSION['queue_details']['target'])."') GROUP BY `email_address`";
            if (!$db->Execute($query)) {
                echo "<script type=\"text/javascript\">\n";
                echo "\tparent.document.getElementById('progressText').innerHTML = 'No subscribers to load into queue.';\n";
                echo "\talert('There were no subscribers in any of the groups you selected to load into the sending queue.\\n\\nPlease choose a group to send to which contains subscribers.');\n";
                echo "</script>\n";
                echo "</body>\n";
                echo "</html>\n";

                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tInsert Select statement returned false, maybe you have no subscribers to send to or maybe there was a database error. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
                exit;
            } else {
                $query = 'SELECT COUNT(*) AS `total` FROM `'.TABLES_PREFIX."sending` WHERE `queue_id`='".checkslashes($_SESSION['queue_details']['queue_id'])."'";
                $result = $db->GetRow($query);
                $num = 0;

                if ($result && ((int) $result['total'])) {
                    $num = (int) $result['total'];
                } else {
                    echo "<script type=\"text/javascript\">\n";
                    echo "\tparent.document.getElementById('progressText').innerHTML = 'No subscribers to load into queue.';\n";
                    echo "\talert('There were no subscribers in any of the groups you selected to load into the sending queue.\\n\\nPlease choose a group to send to which contains subscribers.');\n";
                    echo "</script>\n";
                    echo "</body>\n";
                    echo "</html>\n";

                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tdUnable to find any subscribers for this queue_id (".checkslashes($_SESSION['queue_details']['queue_id']).') in the sending table. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                    }
                    exit;
                }
                echo "<script type=\"text/javascript\">\n";
                echo "function readyToGo() {\n";
                echo "\tvar is_confirmed = confirm('Your message has been successfully queued and is ready to be sent to ".$num.' unique e-mail address'.(($num != 1) ? 'es' : '').".\\n\\nIf you would like to proceed to send this message press OK, otherwise click Cancel to cancel the queue and return to the message centre.');\n";
                echo "\tif (is_confirmed == true) {\n";
                echo "\t\twindow.location='sender.php?qid=".(int) $_SESSION['queue_details']['queue_id']."&action=send';\n";
                echo "\t\treturn;\n";
                echo "\t} else {\n";
                echo "\t\twindow.location='sender.php?qid=".(int) $_SESSION['queue_details']['queue_id']."&action=cancel';\n";
                echo "\t\treturn;\n";
                echo "\t}\n";
                echo "}\n\n";
                echo "parent.document.getElementById('progressText').innerHTML = 'Successfully inserted ".$num.' address'.(($num != 1) ? 'es' : '')." into the sending queue.';\n";
                echo "readyToGo();\n";
                echo "</script>\n";
                ob_flush();
            }
        } else {
            echo "<script type=\"text/javascript\">\n";
            echo "\tparent.document.getElementById('progressText').innerHTML = 'Invalid group selected to send to.';\n";
            echo "\talert('Sending engine was provided with invalid groups of users.');\n";
            echo "</script>\n";
            echo "</body>\n";
            echo "</html>\n";

            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThe sending engine was provided with a group to send to which was not an array. Request made by: ".$_SERVER['REMOTE_ADDR']."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
            exit;
        }
        break;
}
echo "</body>\n";
echo "</html>\n";
