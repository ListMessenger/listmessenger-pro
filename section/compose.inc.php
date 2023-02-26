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

$STEP = 1;
if (!empty($_GET['step'])) {
    $STEP = (int) $_GET['step'];
}

$msg_attachment = ((!empty($_POST['msg_attachment'])) ? $_POST['msg_attachment'] : []);

if (!empty($_POST['back'])) {
    $STEP -= 2;
}

// Error checking step switch.
switch ($STEP) {
    case '2':
        if (!empty($_POST['online_filename'])) {
            $STEP = 1; // Back to step 1.
            $filename = checkslashes(trim($_POST['online_filename']));
            if (file_exists($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename)) {
                if (!in_array($filename, $msg_attachment)) {
                    $msg_attachment[] = $filename;

                    ++$SUCCESS;
                    $SUCCESSSTR[] = 'Successfully attached '.$filename.' to this message.';
                } else {
                    ++$ERROR;
                    $ERRORSTR[] = 'The file that you have selected to be attached to this message already is attached to this message.';
                }
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'The online file that you have attempted to attach does not exist in your public files directory.';
            }
        } elseif (!empty($_POST['attach_file'])) {
            $STEP = 1; // Back to step 1.
            if (is_writeable($_SESSION['config'][PREF_PUBLIC_PATH].'files')) {
                switch ($_FILES['attachment']['error']) {
                    case 1:
                        $ERROR++;
                        $ERRORSTR[] = 'The file attachment that you are trying to add is larger than the maximum size that your server will allow you to upload.<br /><br />To correct this problem, either upload a smaller file or modify the &quot;<em>upload_max_filesize</em>&quot; directive in your servers php.ini file.';
                        break;
                    case 2:
                        $ERROR++;
                        $ERRORSTR[] = 'The file attachment that you are trying to add is larger than the maximum size that was specified in the HTML form.<br /><br />To correct this problem upload a smaller file attachment.';
                        break;
                    case 3:
                        $ERROR++;
                        $ERRORSTR[] = 'The file attachment that you are trying to add was only partially uploaded or your upload was interrupted.<br /><br />To correct this, please try to upload the file attachment again.';
                        break;
                    case 4:
                        $ERROR++;
                        $ERRORSTR[] = 'You have clicked the &quot;Upload File&quot; button; however, you have not selected a file on your hard drive to upload to the web server.';
                        break;
                    default:
                        $filename = valid_filename($_FILES['attachment']['name']);
                        if (!in_array($filename, $msg_attachment)) {
                            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename)) {
                                $msg_attachment[] = $filename;
                                ++$SUCCESS;
                                $SUCCESSSTR[] = 'Successfully uploaded and attached '.$filename.' to this message.';
                            } else {
                                ++$ERROR;
                                $ERRORSTR[] = 'Unable to move &quot;'.$filename.'&quot; to the &quot;'.$_SESSION['config'][PREF_PUBLIC_PATH].'files&quot; directory.<br /><br />To resolve this, please make sure that PHP has write permissions to the public files directory.';
                            }
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'The file that you have selected to be attached to this message already is attached to this message.';
                        }
                        break;
                }
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'Your public files directory is not writeable by PHP, so unfortunately uploading your file will not be possible.<br /><br />To fix this please chmod the '.$_SESSION['config'][PREF_PUBLIC_PATH].'files/ directory to 777.';
            }
        } elseif (!empty($_POST['remove_attachment'])) {
            $STEP = 1; // Back to step 1.
            if (!empty($_POST['attachments'])) {
                $counter = 0;
                $names = [];
                foreach ($_POST['attachments'] as $id => $value) {
                    if ($value == '1') {
                        ++$counter;
                        $names[] = $msg_attachment[$id];
                        unset($msg_attachment[$id]);
                    }
                }
                ++$SUCCESS;
                $SUCCESSSTR[] = 'Successfully removed '.implode(', ', $names).' from this message.';
            }
        } elseif (!empty($_POST['save_draft'])) {
            $query = 'INSERT INTO `'.TABLES_PREFIX."messages` (`message_id`, `message_date`, `message_title`, `message_subject`, `message_from`, `message_reply`, `message_priority`, `text_message`, `text_template`, `html_message`, `html_template`, `attachments`) VALUES (NULL, '".time()."', '".checkslashes($_POST['title'])."', '".checkslashes($_POST['subject'])."', '".checkslashes($_POST['from'])."', '".checkslashes($_POST['reply'])."', '".checkslashes($_POST['priority'])."', '".checkslashes($_POST['text_message'])."', '".checkslashes($_POST['text_template'])."', '".((trim(strip_tags($_POST['html_message'])) != '') ? checkslashes($_POST['html_message']) : '')."', '".checkslashes($_POST['html_template'])."', '".((is_array($_POST['msg_attachment'])) ? checkslashes(serialize($_POST['msg_attachment'])) : '')."');";
            if ($db->Execute($query)) {
                $id = $db->Insert_ID();
                if ($id) {
                    header('Location: ./index.php?section=message&id='.$id);
                    exit;
                } else {
                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to retrieve the insert ID of the previous query, redirecting to the Message Centre instead.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                    }
                    header('Location: ./index.php?section=message');
                    exit;
                }
            } else {
                $STEP = 1;
                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to save draft message. Database said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
                ++$ERROR;
                $ERRORSTR[] = 'Unable to save your message as a draft because there was an error inserting it into the database. The database server said: '.$db->ErrorMsg();
            }
        } elseif (!empty($_POST['save_proceed'])) {
            if (empty($_POST['from'])) {
                ++$ERROR;
                $ERRORSTR[] = 'Your from address seems to be empty, please make sure you format it correctly!<br /><strong>Example:</strong> &quot;My Name&quot; &lt;email@domain.com&gt;';
            }
            if (empty($_POST['reply'])) {
                ++$ERROR;
                $ERRORSTR[] = 'Your reply-to address seems to be empty, please make sure you format it correctly!<br /><strong>Example:</strong> &quot;My Name&quot; &lt;email@domain.com&gt;';
            }
            if (empty($_POST['title'])) {
                ++$ERROR;
                $ERRORSTR[] = 'Your internal message title seems to be empty, please enter a title for this message that uniquely identifies it in your message centre.';
            }
            if (empty($_POST['subject'])) {
                $_POST['subject'] = '(no subject)';
            }
            if (empty($_POST['priority'])) {
                ++$ERROR;
                $ERRORSTR[] = "Please be sure to select a priority for this message. By default this is set to Normal and for the most part, probably shouldn't change.";
            }
            if (empty($_POST['text_message'])) {
                ++$ERROR;
                $ERRORSTR[] = "It seems that you have not entered a text version of your message. ListMessenger requires a text version of your message because it uses a multi-part alternative message format when sending messages. Because it sends in this format, if a text version of the message isn't present and a subscriber's e-mail client isn't configured for HTML messages, the subscriber will see nothing but a blank e-mail.";
            }
            // If there's an error, go back a step.
            if ($ERROR) {
                $STEP = 1;
            }
        }
        break;
    case '3':
        if (!empty($_POST['save_proceed'])) {
            if (empty($_POST['from'])) {
                ++$ERROR;
                $ERRORSTR[] = 'Your from address seems to be empty, please make sure you format it correctly!<br /><strong>Example:</strong> &quot;My Name&quot; &lt;email@domain.com&gt;';
            }
            if (empty($_POST['reply'])) {
                ++$ERROR;
                $ERRORSTR[] = 'Your reply-to address seems to be empty, please make sure you format it correctly!<br /><strong>Example:</strong> &quot;My Name&quot; &lt;email@domain.com&gt;';
            }
            if (empty($_POST['title'])) {
                ++$ERROR;
                $ERRORSTR[] = 'Your internal message title seems to be empty, please enter a title for this message that uniquely identifies it in your message centre.';
            }
            if (empty($_POST['subject'])) {
                $_POST['subject'] = '(no subject)';
            }
            if (empty($_POST['priority'])) {
                ++$ERROR;
                $ERRORSTR[] = "Please be sure to select a priority for this message. By default this is set to Normal and for the most part, probably shouldn't change.";
            }
            if (empty($_POST['text_message'])) {
                ++$ERROR;
                $ERRORSTR[] = "It seems that you have not entered a text version of your message. ListMessenger requires a text version of your message because it uses a multi-part alternative message format when sending messages. Because it sends in this format, if a text version of the message isn't present and a subscriber's e-mail client isn't configured for HTML messages, the subscriber will see nothing but a blank e-mail.<br /><br />For more information, please visit our <a href=\"https://listmessenger.com/index.php/faq\" target=\"_blank\">Frequently Asked Questions</a>.";
            }

            if (!$ERROR) {
                $message = [
                    'message_date' => time(),
                    'message_title' => $_POST['title'],
                    'message_subject' => $_POST['subject'],
                    'message_from' => $_POST['from'],
                    'message_reply' => $_POST['reply'],
                    'message_priority' => $_POST['priority'],
                    'text_message' => $_POST['text_message'],
                    'text_template' => $_POST['text_template'],
                    'html_message' => ((!empty($_POST['html_message']) && trim(strip_tags($_POST['html_message'])) != '') ? $_POST['html_message'] : ''),
                    'html_template' => $db->qstr($_POST['html_template']),
                    'attachments' => ((!empty($_POST['msg_attachment']) && is_array($_POST['msg_attachment'])) ? serialize($_POST['msg_attachment']) : '')
                ];

                if ($db->AutoExecute(TABLES_PREFIX.'messages', $message)) {
                    $id = $db->Insert_ID();
                    if ($id) {
                        header('Location: ./index.php?section=message&action=view&id='.$id);
                        exit;
                    } else {
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to retrieve the insert ID of the previous query, redirecting to the Message Centre instead.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                        header('Location: ./index.php?section=message');
                        exit;
                    }
                } else {
                    $STEP = 1;
                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to save draft message. Database said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                    }
                    ++$ERROR;
                    $ERRORSTR[] = 'Unable to save your message as a draft because there was an error inserting it into the database. The database server said: '.$db->ErrorMsg();
                }
            } else {
                $STEP = 2;
            }
        }
        break;
    default:
        break;
}

// Body content step switch.
switch ($STEP) {
    case '2':
        $COLLAPSED = [];
        if (!empty($_COOKIE['display']['compose']['collapsed'])) {
            $COLLAPSED = explode(',', $_COOKIE['display']['compose']['collapsed']);
        }

        $ONLOAD[] = "\$('#tab-pane-1').tabs()";

        // Turn the HTML message into a session so we can pass it to the preview script.
        if (trim(strip_tags($_POST['html_message'])) != '') {
            if (!empty($_POST['html_template']) && (int) $_POST['html_template']) {
                $_SESSION['html_message'] = urlencode(checkslashes(trim(insert_template('html', $_POST['html_template'], $_POST['html_message'])), 1));
            } else {
                $_SESSION['html_message'] = urlencode(checkslashes(trim($_POST['html_message']), 1));
            }
        } else {
            unset($_SESSION['html_message']);
        }
        ?>
		<h1>Compose Message</h1>
		<?php
        if ($ERROR) {
            echo display_error($ERRORSTR);
        }
        ?>
		Please confirm the contents of your message by reviewing it below. You can toggle back and forth between Text Version and HTML Version using the tabs.
		<br /><br />
		<table style="width: 100%; margin: 3px" cellspacing="0" cellpadding="1" border="0">
		<tr>
			<td class="form-row-nreq" style="width: 25%">From:&nbsp;</td>
			<td style="width: 75%"><?php echo html_encode(checkslashes($_POST['from'], 1)); ?></td>
		</tr>
		<tr>
			<td class="form-row-nreq" style="width: 25%">Reply-to:&nbsp;</td>
			<td style="width: 75%"><?php echo html_encode(checkslashes($_POST['reply'], 1)); ?></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td class="form-row-nreq" style="width: 25%">Internal Title:&nbsp;</td>
			<td style="width: 75%"><?php echo html_encode(checkslashes($_POST['title'], 1)); ?></td>
		</tr>
		<tr>
			<td class="form-row-nreq" style="width: 25%">Message Subject:&nbsp;</td>
			<td style="width: 75%"><?php echo html_encode(checkslashes($_POST['subject'], 1)); ?></td>
		</tr>
		<tr>
			<td class="form-row-nreq" style="width: 25%">Priority:&nbsp;</td>
			<td style="width: 75%">
				<?php
                if (!empty($_POST['priority'])) {
                    switch ($_POST['priority']) {
                        case '1':
                            echo 'Highest';
                            break;
                        case '3':
                            echo 'Normal';
                            break;
                        default:
                            echo 'Lowest';
                            break;
                    }
                }
        ?>
			</td>
		</tr>
		<?php
        if (count($msg_attachment) > 0) {
            echo "<tr>\n";
            echo "	<td colspan=\"2\">&nbsp;</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
            echo "	<td class=\"form-row-nreq\" style=\"vertical-align: top\">File Attachments:&nbsp;</td>\n";
            echo "	<td>\n";
            echo "		<table style=\"width: 100%; text-align: left\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\">\n";
            echo "		<tr>\n";
            echo "			<td style=\"width: 80%; height: 15px; padding-left: 2px; border-top: 1px #9D9D9D solid; border-left: 1px #9D9D9D solid; border-bottom: 1px #9D9D9D solid; background-image: url('./images/table-head-on.gif'); white-space: nowrap\">File Name</td>\n";
            echo "			<td style=\"width: 20%; height: 15px; padding-left: 2px; border: 1px #9D9D9D solid; background-image: url('./images/table-head-off.gif'); white-space: nowrap\">File Size</td>\n";
            echo "		</tr>\n";
            natsort($msg_attachment);
            foreach ($msg_attachment as $id => $filename) {
                echo "<tr>\n";
                echo '	<td><a href="'.$_SESSION['config'][PREF_PUBLIC_URL].'files/'.$filename.'" target="_blank">'.$filename."</a></td>\n";
                echo '	<td>'.readable_size(filesize($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename))."</td>\n";
                echo "</tr>\n";
            }
            echo "		</table>\n";
            echo "	</td>\n";
            echo "</tr>\n";
        }
        ?>
		</table>
		<br />

		<div id="tab-pane-1">
			<ul>
				<li><a href="#fragment-1"><span>Text Version</span></a></li>
				<li><a href="#fragment-2"><span>HTML Version</span></a></li>
			</ul>
			<div id="fragment-1">
				<?php
                if (!empty($_POST['text_template']) && (int) $_POST['text_template']) {
                    echo checkslashes(wordwrap(nl2br(html_encode(trim(insert_template('text', $_POST['text_template'], $_POST['text_message'])))), $_SESSION['config'][PREF_WORDWRAP], '<br />', 1), 1);
                } else {
                    echo checkslashes(wordwrap(nl2br(html_encode(trim($_POST['text_message']))), $_SESSION['config'][PREF_WORDWRAP], '<br />', 1), 1);
                }
        ?>
			</div>
			<div id="fragment-2">
				<?php
        if ((!empty($_SESSION['html_message'])) && $_SESSION['html_message']) {
            ?>
					<iframe src="./preview.php" width="100%" height="400" style="border:0; margin:0; padding:0"></iframe>
					<div style="padding-top: 10px; text-align: right">
						<button id="new_window">New Window</button>
					</div>
					<div style="display: none;" id="preview_window" title="HTML Message Preview"><iframe src="./preview.php" width="100%" height="100%" style="border:0; margin:0; padding:0"></iframe></div>
					<script type="text/javascript">
					$(document).ready(function(){
						$('#preview_window').dialog({
							title: 'HTML Message Preview',
							modal: true,
							autoOpen: false,
							height: ($(window).height() - 100),
							width: ($(window).width() - 100),
							resizable: true,
							draggable: false
						});

						$('#new_window').click(function() {
							$('#preview_window').dialog('open');
							return false;
						});
					});
					</script>
					<?php
        } else {
            echo display_notice(['There is currently no HTML version of this message present, which is fine; your subscribers will simply receive your message as plain text. If you would like to add an HTML version, simply click the back button; otherwise, you can ignore this.']);
        }
        ?>
			</div>
		</div>
		<br />
		<table style="width: 100%; margin: 3px" cellspacing="0" cellpadding="1" border="0">
		<tr>
			<td style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px" colspan="2">
				<form action="index.php?section=compose&step=3" method="post">
				<input type="hidden" name="from" value="<?php echo !empty($_POST['from']) ? html_encode($_POST['from']) : ''; ?>" />
				<input type="hidden" name="reply" value="<?php echo !empty($_POST['reply']) ? html_encode($_POST['reply']) : ''; ?>" />
				<input type="hidden" name="title" value="<?php echo !empty($_POST['title']) ? html_encode($_POST['title']) : ''; ?>" />
				<input type="hidden" name="subject" value="<?php echo !empty($_POST['subject']) ? html_encode($_POST['subject']) : ''; ?>" />
				<input type="hidden" name="priority" value="<?php echo !empty($_POST['priority']) ? html_encode($_POST['priority']) : ''; ?>" />
				<input type="hidden" name="text_template" value="<?php echo !empty($_POST['text_template']) ? html_encode($_POST['text_template']) : ''; ?>" />
				<input type="hidden" name="text_message" value="<?php echo !empty($_POST['text_message']) ? html_encode($_POST['text_message']) : ''; ?>" />
				<input type="hidden" name="html_template" value="<?php echo !empty($_POST['html_template']) ? html_encode($_POST['html_template']) : ''; ?>" />
				<input type="hidden" name="html_message" value="<?php echo !empty($_POST['html_message']) ? html_encode($_POST['html_message']) : ''; ?>" />
				<?php
        if (count($msg_attachment) > 0) {
            foreach ($msg_attachment as $id => $filename) {
                echo '<input type="hidden" name="msg_attachment['.$id.']" value="'.$filename."\" />\n";
            }
        }
        ?>
				<input type="submit" name="back" class="button" value="Back" />&nbsp;
				<input type="submit" name="save_proceed" class="button" value="Proceed" />
				</form>
			</td>
		</tr>
		</table>
		<?php
    break;
    default:
        if (function_exists('pspell_new')) {
            $HEAD[] = "<script type=\"text/javascript\" language=\"javascript\" src=\"./javascript/spellcheck/spellcheck.js\"></script>\n";
        }

        /*
         * Add all message variables from defined function.
         */
        add_sidebar_variables();

        ?>
		<h1>Compose Message</h1>
		<?php
        if ($ERROR) {
            echo display_error($ERRORSTR);
        } elseif ($SUCCESS) {
            echo display_success($SUCCESSSTR);
        }
        ?>
		<form action="index.php?section=compose&step=2" method="post" enctype="multipart/form-data" id="compose_message">
		<input type="hidden" id="online_filename" name="online_filename" value="" />
		<?php
        if (count($msg_attachment) > 0) {
            foreach ($msg_attachment as $id => $filename) {
                echo '<input type="hidden" name="msg_attachment['.$id.']" value="'.$filename."\" />\n";
            }
        }
        ?>
		<table style="width: 100%; margin: 3px" cellspacing="0" cellpadding="1" border="0">
		<colgroup>
			<col style="width: 25%" />
			<col style="width: 75%" />
		</colgroup>
		<tfoot>
			<tr>
				<td colspan="2" style="border-bottom: 1px #666666 dotted;">&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<div style="width: 98%; padding-top: 5px; text-align: right">
						<input type="submit" name="save_draft" class="button" value="Save as Draft" />&nbsp;
						<input type="submit" name="save_proceed" class="button" value="Proceed" />
					</div>
				</td>
			</tr>
		</tfoot>		
		<tbody>
			<tr>
				<td>
					<?php echo create_tooltip('From', '<strong>Field Name: <em>From</em></strong><br />This is the from name and e-mail address that the end user will see when viewing your message.<br /><br /><strong>Tip:</strong><br />Make sure you keep the formatting of the from address the same.', true); ?>
				</td>
				<td><input type="text" class="text-box" style="width: 350px" name="from" value="<?php echo !empty($_POST['from']) ? html_encode($_POST['from']) : html_encode('"'.$_SESSION['config'][PREF_FRMNAME_ID].'" <'.$_SESSION['config'][PREF_FRMEMAL_ID].'>'); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td>
					<?php echo create_tooltip('Reply-to', '<strong>Field Name: <em>Reply-to</em></strong><br />This is the reply-to name and e-mail address that the end user will see when replying to your message.<br /><br /><strong>Tip:</strong><br />Make sure you keep the formatting of the reply-to address the same.', true); ?>
				</td>
				<td><input type="text" class="text-box" style="width: 350px" name="reply" value="<?php echo !empty($_POST['reply']) ? html_encode($_POST['reply']) : html_encode('"'.$_SESSION['config'][PREF_FRMNAME_ID].'" <'.$_SESSION['config'][PREF_RPYEMAL_ID].'>'); ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td>
					<?php echo create_tooltip('Internal Message Title', '<strong>Field Name: <em>Internal Message Title</em></strong><br />This is an internal identifier for you, the administrator, so you can easily identify this message in the Message Centre. This field will never been seen by an end-user, it is available to the administrator.', true); ?>
				</td>
				<td><input type="text" class="text-box" style="width: 350px" id="title" name="title" value="<?php echo !empty($_POST['title']) ? html_encode($_POST['title']) : ''; ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td>
					<?php echo create_tooltip('Message Subject', '<strong>Field Name: <em>Message Subject</em></strong><br />This is the subject of the message that you are composing.<br /><br /><strong>Tip:</strong><br />Keep in mind, you can use e-mail variables in the subject as well as the body for personalization.'); ?>
				</td>
				<td><input type="text" class="text-box" style="width: 350px" name="subject" value="<?php echo !empty($_POST['subject']) ? html_encode($_POST['subject']) : ''; ?>" onkeypress="return handleEnter(this, event)" /></td>
			</tr>
			<tr>
				<td>
					<?php echo create_tooltip('Message Priority', '<strong>Field Name: <em>Message Priority</em></strong><br />This is the level of priority of the message. Please note, that you will almost always want this set to Normal because if you set it to High, you will have a greater chance of spam filters considering your message as spam.'); ?>
				</td>
				<td>
					<select name="priority" onkeypress="return handleEnter(this, event)">
					<option value="1"<?php echo !empty($_POST['priority']) && $_POST['priority'] == '1' ? ' selected="selected"' : ''; ?>>Highest</option>
					<option value="3"<?php echo empty($_POST['priority']) || $_POST['priority'] == '3' ? ' selected="selected"' : ''; ?>>Normal</option>
					<option value="5"<?php echo !empty($_POST['priority']) && $_POST['priority'] == '5' ? ' selected="selected"' : ''; ?>>Lowest</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" style="padding: 0px; margin: 0px">
					<table style="width: 98%" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td>
							<?php echo create_tooltip('Text Version', '<strong>Field Name: <em>Text Version</em></strong><br />This is the plain text version of your message and it is a required field. If you plan on sending an HTML version, you must include a text version containing either the text of the HTML message or an explanation as to where the user can view the HTML version with their web-browser.', true); ?>
							<span id="text_template_message" class="message-notice" style="display:none">The <strong>selected template</strong> will be applied on the following page.</span>
						</td>
						<td style="text-align: right">
							<?php
                            $query = 'SELECT `template_id`,`template_name` FROM `'.TABLES_PREFIX."templates` WHERE `template_type`='text' ORDER BY `template_name` ASC";
        $results = $db->GetAll($query);
        if ($results) {
            ?>
								Text Template:
								<select id="text_template" name="text_template" style="width: 200px">
									<option value="0">-- Optional Text Template --</option>
									<?php
                foreach ($results as $result) {
                    echo '<option value="'.$result['template_id'].'"'.((!empty($_POST['text_template']) && $_POST['text_template'] == $result['template_id']) ? ' selected="selected"' : '').'>'.$result['template_name']."</option>\n";
                }
            ?>
								</select>
								<script type="text/javascript">
								$('#text_template').change(function() {
									if ($('#text_template').val() > 0) {
										$('#text_template_message').fadeIn();
									} else {
										$('#text_template_message').fadeOut();
									}
								});
								</script>
								<?php
        } else {
            echo '<span style="color: #666666">There are no <strong>text</strong> templates: <a href="index.php?section=templates&action=add&type=text">Click here</a> to add one.</span>';
        }
        ?>
						</td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="padding-right: 3px">
					<div class="msg_container">
						<textarea id="text_message" name="text_message" rows="10" cols="80" autocomplete="off" class="resizable"><?php echo !empty($_POST['text_message']) ? html_encode($_POST['text_message']) : ''; ?></textarea>
						<?php if (function_exists('pspell_new')) { ?>
						<div style="margin-top: 5px">
							<input type="button" class="button" value="Spell Check" onclick="spellCheck('compose_message', 'text_message')" />
						</div>
						<?php } ?>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" style="padding: 0px; margin: 0px">
					<table style="width: 98%" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td>
							<?php echo create_tooltip('HTML Version', '<strong>Field Name: <em>HTML Version</em></strong><br />This is the optional HTML version of your message. '.((($_SESSION['config'][PREF_USERTE] != 'disabled') && $RTE_ENABLED) ? 'You have the Rich Text Editor enabled, so you can just type your message, changing the font size and colour as you would with any text editor.' : 'You have the Rich Text Editor disabled, so your message must be provided to this box in pre formatted HTML.')); ?>
							<span id="html_template_message" class="message-notice" style="display:none">The <strong>selected template</strong> will be applied on the following page.</span>
						</td>
						<td style="text-align: right">
							<?php
        $query = 'SELECT `template_id`,`template_name` FROM `'.TABLES_PREFIX."templates` WHERE `template_type`='html' ORDER BY `template_name` ASC";
        $results = $db->GetAll($query);
        if ($results) {
            ?>
								HTML Template:
								<select id="html_template" name="html_template" style="width: 200px">
									<option value="0">-- Optional HTML Template --</option>
									<?php
                foreach ($results as $result) {
                    echo '<option value="'.$result['template_id'].'"'.((!empty($_POST['html_template']) && $_POST['html_template'] == $result['template_id']) ? ' selected="selected"' : '').'>'.$result['template_name']."</option>\n";
                }
            ?>
								</select>
								<script type="text/javascript">
								$('#html_template').change(function() {
									if ($('#html_template').val() > 0) {
										$('#html_template_message').fadeIn();
									} else {
										$('#html_template_message').fadeOut();
									}
								});
								</script>
								<?php
        } else {
            echo '<span style="color: #666666">There are no <strong>html</strong> templates: <a href="index.php?section=templates&action=add&type=html">Click here</a> to add one.</span>';
        }
        ?>
						</td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div class="msg_container">
						<textarea id="html_message" name="html_message"><?php echo (!empty($_POST['html_message'])) ? clean_input($_POST['html_message'], ['trim', 'encode', 'slashtestremove']) : ''; ?></textarea>
						<?php
                        if ($RTE_ENABLED) {
                            rte_display('html_message');
                        }
        ?>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td>
					<?php echo create_tooltip('File Attachments', '<strong>Field Name: <em>File Attachments</em></strong><br />You can add attachments to your message by clicking the "Browse" button, selecting the file from your computer and clicking "Upload File".<br /><br />If you would like to attach a file that has been previously uploaded, simply click the "Online Files" button and then select from one of your existing files.<br /><br /><strong>Important Notice:</strong><br />Please keep in mind that sending file attachments will dramatically increase the load on mail server when sending out this message.<br /><br /><strong>The maximum recommended size of any and all combined attachments is 250KB.</strong>'); ?>
				</td>
				<td>
					<div class="msg_container">
						<div style="float: left">
							<input type="file" name="attachment" size="16" />
						</div>
						<div style="float: right">
							<input type="submit" name="attach_file" class="button" value="Upload File" />&nbsp;
							<input type="button" name="online_file" class="button" value="Online Files" onclick="openAttachements()" />
						</div>
					</div>
				</td>
			</tr>
			<?php
            if (count($msg_attachment) > 0) {
                echo "<tr>\n";
                echo "	<td>&nbsp;</td>\n";
                echo "	<td style=\"padding-top: 5px\">\n";
                echo "		<div class=\"msg_container\">\n";
                echo "			<table class=\"file-attachments\" style=\"width: 100%; text-align: left\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\">\n";
                echo "			<thead>\n";
                echo "				<tr>\n";
                echo "					<td style=\"width: 3%; height: 15px; padding-left: 2px; border-top: 1px #9D9D9D solid; border-left: 1px #9D9D9D solid; border-bottom: 1px #9D9D9D solid; background-image: url('./images/table-head-off.gif'); white-space: nowrap\">&nbsp;</td>\n";
                echo "					<td style=\"width: 77%; height: 15px; padding-left: 2px; border-top: 1px #9D9D9D solid; border-left: 1px #9D9D9D solid; border-bottom: 1px #9D9D9D solid; background-image: url('./images/table-head-on.gif'); white-space: nowrap\">File Name</td>\n";
                echo "					<td style=\"width: 20%; height: 15px; padding-left: 2px; border: 1px #9D9D9D solid; background-image: url('./images/table-head-off.gif'); white-space: nowrap\">File Size</td>\n";
                echo "				</tr>\n";
                echo "			</thead>\n";
                echo "			<tbody>\n";
                natsort($msg_attachment);
                $total_size = 0;

                foreach ($msg_attachment as $id => $filename) {
                    $file_exists = false;
                    $file_size = 0;

                    if (file_exists($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename) && is_readable($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename)) {
                        $file_exists = true;
                        $file_size = filesize($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename);
                        $total_size += $file_size;
                    }

                    echo '<tr'.((!$file_exists) ? ' class="na"' : '').">\n";
                    echo '	<td><input type="checkbox" name="attachments['.$id.']" value="1" style="vertical-align: middle"'.((!$file_exists) ? ' checked="checked"' : '')." /></td>\n";
                    echo '	<td>'.((!$file_exists) ? '<img src="./images/note-error.gif" width="11" height="11" alt="File Not Found" title="File Not Found" style="vertical-align: middle; margin-right: 10px" />' : '').'<a href="'.$_SESSION['config'][PREF_PUBLIC_URL].'files/'.$filename.'" target="_blank" style="vertical-align: middle">'.$filename.'</a>'.((!$file_exists) ? ' (File does not exist)' : '')."</td>\n";
                    echo '	<td>'.readable_size($file_size)."</td>\n";
                    echo "</tr>\n";
                }
                echo "			</tbody>\n";
                echo "			</table>\n";
                echo "		</div>\n";
                echo "		<div class=\"msg_container\" style=\"margin: 10px 0px 10px 0px; text-align: right\">\n";
                echo "			<input type=\"submit\" name=\"remove_attachment\" class=\"button\" value=\"Remove\" />\n";
                echo "		</div>\n";

                /*
                 * If the total size of the attachments is greater than the recommended size
                 * throw out an notice message.
                 */
                if ($total_size > MAXIMUM_MESSAGE_SIZE) {
                    echo display_notice(['The combined size of your attachments is greater than the recommended maximum <strong>'.readable_size(MAXIMUM_MESSAGE_SIZE).'</strong> message size.<br /><br />While your message may still infact be sent, it will cause excess load on your web and mail servers. You should consider linking to these files in your e-mail rather than attaching them to the message itself.']);
                }
                echo "	</td>\n";
                echo "</tr>\n";
            }
        ?>
		</tbody>
		</table>
		</form>

		<form action="./spellcheck.php" method="post" name="spell_form" id="spell_form" target="spellDialogBox">
		<input type="hidden" name="spell_formname"	value="" />
		<input type="hidden" name="spell_fieldname"	value="" />
		<input type="hidden" name="spellstring"		value="" />
		</form>
		<?php
    break;
}
