<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */
require_once 'classes/lm_mailer.class.php';

if (!defined('PARENT_LOADED')) {
    exit;
}

if (!empty($_SESSION['isAuthenticated']) && (bool) $_SESSION['isAuthenticated']) {
    ++$NOTICE;
    $NOTICESTR[] = 'You are already logged into ListMessenger, so you cannot recover a forgotten password.<br /><br />To change your administrator password you can visit the <a href="./index.php?section=preferences&amp;type=program">Program Preferences</a> page.';

    echo display_notice($NOTICESTR);
} else {
    $STEP = 1;

    if ((!empty($_POST['step'])) && ((int) $_POST['step'])) {
        $STEP = (int) $_POST['step'];
    }

    ?>
	<table style="width: 100%" cellspacing="0" cellpadding="3" border="0" summary="">
	<colgroup>
		<col style="width: 18%" />
		<col style="width: 82%" />
	</colgroup>
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td>
				<?php
                if (!empty($_GET['hash'])) {
                    echo "<h1>ListMessenger Password Reset</h1>\n";

                    if ((!$hash = clean_input($_GET['hash'], 'alphanumeric')) || (strlen($hash) != 32)) {
                        ++$ERROR;
                        $ERRORSTR[] = "<strong>The provided password reset hash code is invalid.</strong><br /><br />Please ensure that you either click the link in the <em>ListMessenger Password Reset Instructions</em> e-mail or copy and paste the entire link into your web-browser's location bar.";
                    } else {
                        $query = 'SELECT * FROM `'.TABLES_PREFIX.'preferences` WHERE `preference_id` = '.$db->qstr(PASSWORD_RESET_HASH).' AND `preference_value` = '.$db->qstr($hash);
                        $result = $db->GetRow($query);
                        if (!$result) {
                            ++$ERROR;
                            $ERRORSTR[] = "<strong>The provided password reset hash code is invalid.</strong><br /><br />Please ensure that you either click the link in the <em>ListMessenger Password Reset Instructions</em> e-mail or copy and paste the entire link into your web-browser's location bar.";
                        }
                    }

                    if ($ERROR) {
                        echo display_error($ERRORSTR);
                    } else {
                        /*
                         * Error Checking
                         */
                        switch ($STEP) {
                            case 2:
                                if ((!empty($_POST['npassword1'])) && trim($_POST['npassword1'])) {
                                    if ((!empty($_POST['npassword2'])) && trim($_POST['npassword2'])) {
                                        if (trim($_POST['npassword1']) == trim($_POST['npassword2'])) {
                                            if (strlen(trim($_POST['npassword1'])) > 5) {
                                                if ($db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => md5(trim($_POST['npassword1']))], 'UPDATE', "preference_id='".PREF_ADMPASS_ID."'")) {
                                                    reload_configuration();

                                                    $db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => ''], 'UPDATE', "preference_id='".PASSWORD_RESET_HASH."'");

                                                    $reset_subject = 'ListMessenger Password Reset Complete';

                                                    $reset_message = "Attention ListMessenger Administrator,\n\n";
                                                    $reset_message .= "Thank-you for completing the password reset procedure. For your convenience this is simply a notification letting you know that your password has been successfully reset, and you can log in at the following location:\n\n";
                                                    $reset_message .= 'Username: '.$_SESSION['config'][PREF_ADMUSER_ID]."\n\n";
                                                    $reset_message .= clean_input($_SESSION['config'][PREF_PROGURL_ID], 'emailheaders')."index.php\n\n";
                                                    $reset_message .= "--\n";
                                                    $reset_message .= "Good Day,\n";
                                                    $reset_message .= "ListMessenger\n\n";

                                                    try {
                                                        $mail = new LM_Mailer($_SESSION['config']);
                                                        $mail->Subject = $reset_subject;
                                                        $mail->Body = $reset_message;

                                                        $mail->ClearAllRecipients();
                                                        $mail->AddAddress($_SESSION['config'][PREF_ADMEMAL_ID], $_SESSION['config'][PREF_FRMNAME_ID]);

                                                        if ($mail->Send()) {
                                                            ++$SUCCESS;
                                                            $SUCCESSSTR[] = 'You have successfully reset your ListMessenger administrator password, and a notice of this action has been sent to the <strong>administrator e-mail address</strong> provided in the ListMessenger control panel.<br /><br />Please continue by logging into ListMessenger: <a href="index.php">click here</a>';
                                                        } else {
                                                            ++$SUCCESS;
                                                            $SUCCESSSTR[] = 'You have successfully reset your ListMessenger administrator password.<br /><br />Please continue by logging into ListMessenger: <a href="index.php">click here</a>';

                                                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send the ListMessenger administrator password reset completion notice [".$_SESSION['config'][PREF_ADMEMAL_ID].']. PHPMailer said: '.$mail->ErrorInfo."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                            }
                                                        }
                                                    } catch (Exception $e) {
                                                        ++$SUCCESS;
                                                        $SUCCESSSTR[] = 'You have successfully reset your ListMessenger administrator password.<br /><br />Please continue by logging into ListMessenger: <a href="index.php">click here</a>';

                                                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\t".$e->getMessage()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                        }
                                                    }
                                                } else {
                                                    ++$ERROR;
                                                    $ERRORSTR[] = 'ListMessenger was unable to save your new password at this time. Please check the ListMessenger error_log file for more details.';

                                                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to save the new administrator password. Database said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                    }
                                                }
                                            } else {
                                                ++$ERROR;
                                                $ERRORSTR[] = 'Your new ListMessenger password must be at least five (6) characters in length.';
                                            }
                                        } else {
                                            ++$ERROR;
                                            $ERRORSTR[] = 'The new passwords that you have entered do not match. Please re-enter your new ListMessenger password.';
                                        }
                                    } else {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'Please ensure that you re-enter your new ListMessenger password in the &quot;Retype New Password&quot; field.';
                                    }
                                } else {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'Please ensure that you enter your new ListMessenger password where requested.';
                                }

                                if ($ERROR) {
                                    $STEP = 1;
                                }
                                break;
                            case 1:
                            default:
                                break;
                        }

                        /*
                         * Display Page Content
                         */
                        switch ($STEP) {
                            case 2:
                                if ($SUCCESS) {
                                    echo display_success($SUCCESSSTR);
                                }
                                break;
                            case 1:
                            default:
                                if ($ERROR) {
                                    echo display_error($ERRORSTR);
                                }
                                ?>
								<form action="index.php?section=password&amp;hash=<?php echo $hash; ?>" method="post">
								<input type="hidden" name="step" value="2" />
								<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
								<colgroup>
									<col style="width: 30%" /> 
									<col style="width: 70%" />
								</colgroup>
								<tfoot>
									<tr>
										<td colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td colspan="2" style="text-align: right; border-top: 1px #333333 dotted; padding-top: 10px">
											<input type="button" class="button" value="Cancel" onclick="window.location='index.php'" />&nbsp;
											<input type="submit" name="save" class="button" value="Save" />
										</td>
									</tr>
								</tfoot>
								<tbody>
									<tr>
										<td><span class="not-required">ListMessenger Username</span></td>
										<td><strong><?php echo html_encode($_SESSION['config'][PREF_ADMUSER_ID]); ?></strong></td>
									</tr>
									<tr>
										<td colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td><?php echo create_tooltip('New ListMessenger Password', '<strong><em>New ListMessenger Password</em></strong><br />If you would like to change the password that you will use to log into the ListMessenger administration interface, you can simply type the new password here.', true); ?></td>
										<td><input type="password" class="text-box" style="width: 150px" name="npassword1" value="" /></td>
									</tr>
									<tr>
										<td><?php echo create_tooltip('Retype New Password', "<strong><em>Retype New Password</em></strong><br />If you are entering a new password, please verify the new password by entering it again in this box.'", true); ?></td>
										<td><input type="password" class="text-box" style="width: 150px" name="npassword2" value="" /></td>
									</tr>
								</tbody>
								</table>
								</form>
								<?php
                            break;
                        }
                    }
                } else {
                    echo "<h1>ListMessenger Password Retrieval</h1>\n";

                    /*
                     * Error Checking
                     */
                    switch ($STEP) {
                        case 2:
                            $hash = md5(uniqid(rand(), 1));

                            if ($db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => $hash], 'UPDATE', "preference_id='".PASSWORD_RESET_HASH."'")) {
                                $reset_subject = 'ListMessenger Password Reset Instructions';

                                $reset_message = "Attention ListMessenger Administrator,\n\n";
                                $reset_message .= "Someone has requested that the administrator password used to log into the ListMessenger administration interface be reset. If this is the case, and you would like to reset your ListMessenger password please visit the following link in your web-browser:\n\n";
                                $reset_message .= clean_input($_SESSION['config'][PREF_PROGURL_ID], 'emailheaders').'index.php?section=password&hash='.$hash."\n\n";
                                $reset_message .= "If you do not wish to have your password reset, it is important that you ignore this e-mail. If requests persist you can log into ListMessenger and ban the following IP address by clicking Control Panel > Preferences > ListMessenger Blacklist:\n\n";
                                $reset_message .= clean_input($_SERVER['REMOTE_ADDR'], 'emailheaders')."\n\n";
                                $reset_message .= "--\n";
                                $reset_message .= "Good Day,\n";
                                $reset_message .= "ListMessenger\n\n";
                                $reset_message .= clean_input($_SESSION['config'][PREF_PROGURL_ID], 'emailheaders')."\n";

                                try {
                                    $mail = new LM_Mailer($_SESSION['config']);
                                    $mail->Subject = $reset_subject;
                                    $mail->Body = $reset_message;

                                    $mail->ClearAllRecipients();
                                    $mail->AddAddress($_SESSION['config'][PREF_ADMEMAL_ID], $_SESSION['config'][PREF_FRMNAME_ID]);

                                    if ($mail->Send()) {
                                        ++$SUCCESS;
                                        $SUCCESSSTR[] = 'Further instructions for resetting your ListMessenger administrator password have been sent to the <strong>administrator e-mail address</strong> provided in the ListMessenger control panel.';
                                    } else {
                                        $db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => ''], 'UPDATE', "preference_id='".PASSWORD_RESET_HASH."'");

                                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send the ListMessenger administrator password reset e-mail [".$_SESSION['config'][PREF_ADMEMAL_ID].']. PHPMailer said: '.$mail->ErrorInfo."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                        }

                                        throw new Exception('The instructions for resetting your ListMessenger administrator password could not be sent. Please check the ListMessenger error_log file for further details.');
                                    }
                                } catch (Exception $e) {
                                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\t".$e->getMessage()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                    }

                                    ++$ERROR;
                                    $ERRORSTR[] = $e->getMessage();
                                }
                            } else {
                                ++$ERROR;
                                $ERRORSTR[] = 'A problem occurred while preparing the password reset procedure. Please see your ListMessenger error_log for more information.';

                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to insert the new password reset hash code into the preferences table. Database said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }
                            }

                            if ($ERROR) {
                                $STEP = 1;
                            }
                            break;
                        case 1:
                        default:
                            break;
                    }

                    /*
                     * Display Page Content
                     */
                    switch ($STEP) {
                        case 2:
                            if ($SUCCESS) {
                                echo display_success($SUCCESSSTR);
                            }
                            break;
                        case 1:
                        default:
                            ?>
							<div class="generic-message">
								If you have forgotten your ListMessenger administrator password you can use this page to begin the password reset process. Simply click the proceed button below and further instructions will be e-mailed to the ListMessenger administrator e-mail address in the system.
							</div>
				
							<?php echo ($ERROR) ? display_error($ERRORSTR) : ''; ?>
							
							<form action="index.php?section=password" method="post">
							<input type="hidden" name="step" value="2" />
							<div style="text-align: right; padding-top: 10px; border-top: 1px #333333 dotted">
								<input type="button" class="button" value="Cancel" onclick="window.location='index.php'" />
								<input type="submit" class="button" value="Proceed" />
							</div>
							</form>
							<?php
                        break;
                    }
                }
    ?>
			</td>
		</tr>
	</tbody>
	</table>
	<?php
}
