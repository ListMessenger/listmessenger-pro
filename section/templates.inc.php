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

if (!empty($_GET['action'])) {
    $ACTION = clean_input($_GET['action'], 'alphanumeric');
} else {
    $ACTION = false;
}

if (($ACTION == 'add') || ($ACTION == 'edit')) {
    $extras = [];
    $extras['Required Variables'] = [];
    $extras['Required Variables'][] = ['variable' => '[message]', 'tooltip' => '<strong>Variable: <em>[message]</em></strong><br /><em>Message</em><br />This is the place holder for the location that your new message will be inserted when you compose a new message and select this template.'];

    /*
     * Add all message variables from defined function.
     */
    add_sidebar_variables($extras);
}

switch ($ACTION) {
    case 'add':
        // Check which type of template we're creating.
        switch ($_GET['type']) {
            case 'html':
                if ($_POST) {
                    $PROCESSED = [];
                    if ($_POST['template_type'] != 'html') {
                        ++$ERROR;
                        $ERRORSTR[] = 'You are attempting to add a new html template; however, the template type does not equal html in the form.';
                    } else {
                        $PROCESSED['template_type'] = 'html';
                    }
                    if (strlen(trim($_POST['template_name'])) < 1) {
                        ++$ERROR;
                        $ERRORSTR[] = 'You must enter a name for this template in order to add it to ListMessenger.';
                    } else {
                        $query = 'SELECT * FROM `'.TABLES_PREFIX."templates` WHERE `template_name`='".checkslashes(trim($_POST['template_name']))."' AND `template_type`='html'";
                        $result = $db->GetRow($query);
                        if ($result) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Your html template name should be unique, and there is already one by this name in your database.';
                        } else {
                            $PROCESSED['template_name'] = trim($_POST['template_name']);
                        }
                    }
                    if (strlen(trim($_POST['template_description'])) > 0) {
                        $PROCESSED['template_description'] = trim($_POST['template_description']);
                    }
                    if (strlen(trim($_POST['template_content'])) < 1) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Your template contents are empty. Please be sure you enter your text template in the template content box.';
                    } else {
                        if (strstr($_POST['template_content'], '[message]') === false) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Your template does not contain the required [message] variable place holder. Please enter [message] in your template where you would like the contents of a newly composed message inserted.';
                        } else {
                            $PROCESSED['template_content'] = trim($_POST['template_content']);
                        }
                    }
                    if (!$ERROR) {
                        $query = 'SELECT * FROM `'.TABLES_PREFIX.'templates` WHERE `template_id`=-1';
                        $fields = $db->Execute($query);

                        $query = $db->GetInsertSQL($fields, $PROCESSED, ini_get('magic_quotes_gpc'));
                        if ($query != '') {
                            if ($db->Execute($query)) {
                                header('Location: index.php?section=templates');
                                exit;
                            } else {
                                ++$ERROR;
                                $ERRORSTR[] = 'ListMessenger was unable to insert your template into the database. Please check your error log for more detailed information.';

                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to insert template data into database. ADODB returned: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }
                            }
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'The automatically generated insert query was empty. ADODB returned: '.$db->ErrorMsg();

                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThe automatically generated insert query was empty. ADODB returned: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }
                        }
                    }
                }
                ?>
				<h1>New HTML Template</h1>
				This section allows you to add a new <strong>HTML</strong> template to ListMessenger, which can be used when you compose a new message. Keep in mind you can use message variables within templates as well!
				<br /><br />
				<?php echo ($ERROR > 0) ? display_error($ERRORSTR) : ''; ?>
				<form action="index.php?section=templates&action=add&type=html" method="post">
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
							<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=templates'" />
							<input type="submit" value="Add Template" class="button" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<td class="form-row-req">Template Type:</td>
						<td>
							<select id="template_type" name="template_type" onkeypress="return handleEnter(this, event)" onchange="window.location='index.php?section=templates&action=add&type=text'">
								<option value="text">Text Template</option>
								<option value="html" selected="selected">HTML Template</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="form-row-req">Template Name:</td>
						<td><input type="text" class="text-box" style="width: 350px" name="template_name" value="<?php echo !empty($_POST['template_name']) ? html_encode($_POST['template_name']) : ''; ?>" onkeypress="return handleEnter(this, event)" /></td>
					</tr>
					<tr>
						<td class="form-row-nreq" style="vertical-align: top">Template Description:</td>
						<td>
							<textarea name="template_description" style="width: 352px; height: 50px"><?php echo !empty($_POST['template_description']) ? html_encode($_POST['template_description']) : ''; ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td class="form-row-req" colspan="2">Template HTML Content: <span class="small-grey">Note: The [message] variable must be present in your template.</span></td>
					</tr>
					<tr>
						<td colspan="2">
							<textarea name="template_content" id="template_content" style="width: 98%; height: 400px"><?php echo !empty($_POST['template_content']) ? $_POST['template_content'] : ''; ?></textarea>
							<?php
                            if ($RTE_ENABLED) {
                                rte_display('template_content', ['fullpage' => true]);
                            }
                ?>
						</td>
					</tr>
				</tbody>
				</table>
				</form>
				<?php
            break;
            default:
                if ($_POST) {
                    $PROCESSED = [];
                    if ($_POST['template_type'] != 'text') {
                        ++$ERROR;
                        $ERRORSTR[] = 'You are attempting to add a new text template; however, the template type does not equal text in the form.';
                    } else {
                        $PROCESSED['template_type'] = 'text';
                    }
                    if (strlen(trim($_POST['template_name'])) < 1) {
                        ++$ERROR;
                        $ERRORSTR[] = 'You must enter a name for this template in order to add it to ListMessenger.';
                    } else {
                        $query = 'SELECT * FROM `'.TABLES_PREFIX."templates` WHERE `template_name`='".checkslashes(trim($_POST['template_name']))."' AND `template_type`='text'";
                        $result = $db->GetRow($query);
                        if ($result) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Your text template name should be unique, and there is already one by this name in your database.';
                        } else {
                            $PROCESSED['template_name'] = trim($_POST['template_name']);
                        }
                    }
                    if (strlen(trim($_POST['template_description'])) > 0) {
                        $PROCESSED['template_description'] = trim($_POST['template_description']);
                    }
                    if (strlen(trim($_POST['template_content'])) < 1) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Your template contents are empty. Please be sure you enter your text template in the template content box.';
                    } else {
                        if (strstr($_POST['template_content'], '[message]') === false) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Your template does not contain the required [message] variable place holder. Please enter [message] in your template where you would like the contents of a newly composed message inserted.';
                        } else {
                            $PROCESSED['template_content'] = trim($_POST['template_content']);
                        }
                    }
                    if (!$ERROR) {
                        $query = 'SELECT * FROM `'.TABLES_PREFIX.'templates` WHERE `template_id`=-1';
                        $fields = $db->Execute($query);

                        $query = $db->GetInsertSQL($fields, $PROCESSED, ini_get('magic_quotes_gpc'));
                        if ($query != '') {
                            if ($db->Execute($query)) {
                                header('Location: index.php?section=templates');
                                exit;
                            } else {
                                ++$ERROR;
                                $ERRORSTR[] = 'ListMessenger was unable to insert your template into the database. Please check your error log for more detailed information.';

                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to insert template data into database. ADODB returned: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }
                            }
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'The automatically generated insert query was empty. ADODB returned: '.$db->ErrorMsg();

                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThe automatically generated insert query was empty. ADODB returned: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }
                        }
                    }
                }
                ?>
				<h1>New Text Template</h1>
				This section allows you to add a new <strong>text</strong> template to ListMessenger, which can be used when you compose a new message. Keep in mind you can use message variables within templates as well!
				<br /><br />
				<?php echo ($ERROR > 0) ? display_error($ERRORSTR) : ''; ?>
				<form action="index.php?section=templates&action=add&type=text" method="post">
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
							<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=templates'" />
							<input type="submit" value="Add Template" class="button" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<td class="form-row-req">Template Type:</td>
						<td>
							<select id="template_type" name="template_type" onkeypress="return handleEnter(this, event)" onchange="window.location='index.php?section=templates&action=add&type=html'">
								<option value="text" selected="selected">Text Template</option>
								<option value="html">HTML Template</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="form-row-req">Template Name:</td>
						<td><input type="text" class="text-box" style="width: 350px" name="template_name" value="<?php echo !empty($_POST['template_name']) ? html_encode($_POST['template_name']) : ''; ?>" onkeypress="return handleEnter(this, event)" /></td>
					</tr>
					<tr>
						<td class="form-row-nreq" style="vertical-align: top">Template Description:</td>
						<td>
							<textarea name="template_description" style="width: 352px; height: 50px"><?php echo !empty($_POST['template_description']) ? html_encode($_POST['template_description']) : ''; ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td class="form-row-req" colspan="2">Template Text Content: <span class="small-grey">Note: The [message] variable must be present in your template.</span></td>
					</tr>
					<tr>
						<td colspan="2">
							<textarea name="template_content" style="width: 98%; height: 400px"><?php echo !empty($_POST['template_content']) ? $_POST['template_content'] : ''; ?></textarea>
						</td>
					</tr>
				</tbody>
				</table>
				</form>
				<?php
            break;
        }
        break;
    case 'delete':
        ?>
		<h1>Template Removal</h1>
		<?php
        if (!empty($_POST['confirmed']) && $_POST['confirmed'] == 'true') {
            if (!empty($_POST['deltemplates']) && is_array($_POST['deltemplates'])) {
                $ONLOAD[] = "setTimeout('window.location=\'index.php?section=templates\'', 5000)";

                foreach ($_POST['deltemplates'] as $template_id) {
                    $query = 'DELETE FROM `'.TABLES_PREFIX."templates` WHERE `template_id`='".(int) $template_id."'";
                    if (!$db->Execute($query)) {
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete template id [".$template_id.']. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                        ++$ERROR;
                        $ERRORSTR[] = 'Unable to delete template id ['.$template_id.'] from the database.';
                    } else {
                        ++$SUCCESS;
                    }
                }
                if ($ERROR) {
                    echo display_error($ERRORSTR);
                } elseif ($SUCCESS) {
                    $SUCCESSSTR[] = 'You have successfully deleted '.$SUCCESS.' template'.(($SUCCESS != 1) ? 's' : '').' from the database.<br /><br />You will be automatically redirected in 5 seconds, or <a href="index.php?section=templates">click here</a> if you prefer not to wait.';
                    echo display_success($SUCCESSSTR);
                }
            } else {
                header('Location: index.php?section=templates');
                exit;
            }
        } else {
            if (is_array($_POST['deltemplates']) && (count($_POST['deltemplates']) > 0)) {
                ?>
				<div style="padding-bottom: 5px">
					Please confirm that you wish to remove the following <?php echo count($_POST['deltemplates']); ?> template<?php echo (count($_POST['deltemplates']) != 1) ? 's' : ''; ?>:
				</div>
				<form action="index.php?section=templates&action=delete" method="post">
				<input type="hidden" name="confirmed" value="true" />
				<table class="tabular" cellspacing="0" cellpadding="1" border="0">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 19%" />
					<col style="width: 30%" />
					<col style="width: 48%" />
				</colgroup>
				<thead>
					<tr>
						<td>&nbsp;</td>
						<td>Template Type</td>
						<td>Template Name</td>
						<td class="close">Template Description</td>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="4" style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
							<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=templates'" />
							<input type="submit" value="Confirm" class="button" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<?php
                    foreach ($_POST['deltemplates'] as $template_id) {
                        if ($template_id = (int) $template_id) {
                            $query = 'SELECT `template_type`, `template_name`, `template_description` FROM `'.TABLES_PREFIX."templates` WHERE `template_id`='".checkslashes($template_id)."'";
                            $result = $db->GetRow($query);
                            if ($result) {
                                echo "<tr onmouseout=\"this.style.backgroundColor='#FFFFFF'\" onmouseover=\"this.style.backgroundColor='#F0FFD1'\">\n";
                                echo '	<td style="white-space: nowrap"><input type="checkbox" name="deltemplates[]" value="'.checkslashes($template_id)."\" checked=\"checked\" /></td>\n";
                                echo '	<td class="cursor">'.ucwords($result['template_type'])." Template</td>\n";
                                echo '	<td class="cursor">'.html_encode(limit_chars($result['template_name'], 38))."</td>\n";
                                echo '	<td class="cursor">'.html_encode(limit_chars($result['template_description'], 48))."</td>\n";
                                echo "</tr>\n";
                            }
                        }
                    }
                ?>
				</tbody>
				</table>
				</form>
				<?php
            } else {
                header('Location: index.php?section=template');
                exit;
            }
        }
    break;
    case 'edit':
        if (!empty($_GET['id']) && (int) trim($_GET['id'])) {
            $query = 'SELECT * FROM `'.TABLES_PREFIX."templates` WHERE `template_id`='".(int) trim($_GET['id'])."'";
            $result = $db->GetRow($query);
            if ($result) {
                if ($_POST) {
                    $PROCESSED = [];
                    switch ($_POST['template_type']) {
                        case 'html':
                            $PROCESSED['template_type'] = 'html';
                            break;
                        default:
                            $PROCESSED['template_type'] = 'text';
                            break;
                    }
                    if (strlen(trim($_POST['template_name'])) < 1) {
                        ++$ERROR;
                        $ERRORSTR[] = 'You must enter a name for this template in order to add it to ListMessenger.';
                    } else {
                        if (trim($_POST['template_name']) != trim($_POST['otemplate_name'])) {
                            $squery = 'SELECT * FROM `'.TABLES_PREFIX.'templates` WHERE `template_name`='.$db->qstr($_POST['template_name']).' AND `template_type`='.$db->qstr($PROCESSED['template_type']);
                            $sresult = $db->GetRow($squery);
                            if ($sresult) {
                                ++$ERROR;
                                $ERRORSTR[] = 'Your '.$PROCESSED['template_type'].' template name should be unique, and there is already one by this name in your database.';
                            } else {
                                $PROCESSED['template_name'] = trim($_POST['template_name']);
                            }
                        }
                    }
                    if (strlen(trim($_POST['template_description'])) > 0) {
                        $PROCESSED['template_description'] = trim($_POST['template_description']);
                    }
                    if (strlen(trim($_POST['template_content'])) < 1) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Your template contents are empty. Please be sure you enter your '.$PROCESSED['template_type'].' template in the template content box.';
                    } else {
                        if (!strpos($_POST['template_content'], '[message]')) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Your template does not contain the required [message] variable place holder. Please enter [message] in your template where you would like the contents of a newly composed message inserted.';
                        } else {
                            $PROCESSED['template_content'] = trim($_POST['template_content']);
                        }
                    }
                    if (!$ERROR) {
                        $query = 'SELECT * FROM `'.TABLES_PREFIX."templates` WHERE `template_id`='".checkslashes(trim($_GET['id']))."'";
                        $fields = $db->Execute($query);

                        $query = $db->GetUpdateSQL($fields, $PROCESSED, false, ini_get('magic_quotes_gpc'));
                        if ($query != '') {
                            if ($db->Execute($query)) {
                                header('Location: index.php?section=templates');
                                exit;
                            } else {
                                ++$ERROR;
                                $ERRORSTR[] = 'ListMessenger was unable to update your template information. Please check your error log for more detailed information.';

                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to update template data in database. ADODB returned: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }
                            }
                        } else {
                            header('Location: index.php?section=templates');
                            exit;
                        }
                    }
                }
                ?>
				<h1>Editing <?php echo ucwords($result['template_type']); ?> Template <small>[<?php echo html_encode(checkslashes($result['template_name'], 1)); ?>]</small></h1>
				<?php echo ($ERROR > 0) ? display_error($ERRORSTR) : ''; ?>
				<form action="index.php?section=templates&action=edit&id=<?php echo (int) $_GET['id']; ?>" method="post">
				<input type="hidden" name="otemplate_name" value="<?php echo html_encode(trim($result['template_name'])); ?>" />
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
							<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=templates'" />
							<input type="submit" value="Save Template" class="button" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<td class="form-row-req" style="vertical-align: top">Template Type:</td>
						<td>
							<select id="template_type" name="template_type">
								<option value="text"<?php echo ($_POST) ? (($_POST['template_type'] == 'text') ? ' selected="selected"' : '') : (($result['template_type'] == 'text') ? ' selected="selected"' : ''); ?>>Text Template</option>
								<option value="html"<?php echo ($_POST) ? (($_POST['template_type'] == 'html') ? ' selected="selected"' : '') : (($result['template_type'] == 'html') ? ' selected="selected"' : ''); ?>>HTML Template</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="form-row-req">Template Name:</td>
						<td><input type="text" class="text-box" style="width: 350px" name="template_name" value="<?php echo ($_POST) ? checkslashes($_POST['template_name'], 1) : checkslashes($result['template_name'], 1); ?>" onkeypress="return handleEnter(this, event)" /></td>
					</tr>
					<tr>
						<td class="form-row-nreq" style="vertical-align: top">Template Description:</td>
						<td >
							<textarea name="template_description" style="width: 352px; height: 50px"><?php echo ($_POST) ? checkslashes($_POST['template_description'], 1) : checkslashes($result['template_description'], 1); ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td class="form-row-req" colspan="2">Template Content: <span class="small-grey">Note: The [message] variable must be present in your template.</span></td>
					</tr>
					<tr>
						<td colspan="2">
							<textarea name="template_content" style="width: 98%; height: 400px"><?php echo ($_POST) ? checkslashes($_POST['template_content'], 1) : checkslashes($result['template_content'], 1); ?></textarea>
							<?php
                            if ((($_POST && ($_POST['template_type'] == 'html')) || ($result['template_type'] == 'html')) && $RTE_ENABLED) {
                                rte_display('template_content', ['fullpage' => true]);
                            }
                ?>
						</td>
					</tr>
				</tbody>
				</table>
				</form>
				<?php
            } else {
            }
        } else {
        }
        break;
    case 'view':
        if ((int) trim($_GET['id'])) {
            $query = 'SELECT * FROM `'.TABLES_PREFIX."templates` WHERE `template_id`='".checkslashes(trim($_GET['id']))."'";
            $result = $db->GetRow($query);
            if ($result) {
                ?>
				<h1>Viewing <?php echo ucwords($result['template_type']); ?> Template <small>[<?php echo html_encode(checkslashes($result['template_name'], 1)); ?>]</small></h1>
				<form>
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
				<tr>
					<td style="width: 30%" class="form-row-req" style="vertical-align: top">Template Type:</td>
					<td style="width: 70%"><?php echo ucwords($result['template_type']); ?> Template</td>
				</tr>
				<tr>
					<td class="form-row-req">Template Name:</td>
					<td><?php echo html_encode($result['template_name']); ?></td>
				</tr>
				<tr>
					<td class="form-row-nreq" style="vertical-align: top">Template Description:</td>
					<td><?php echo html_encode($result['template_description']); ?></td>
				</tr>
				<tr>
					<td class="form-row-req" colspan="2">Template Content:</td>
				</tr>
				<tr>
					<td colspan="2" style="padding-left: 5px">
						<?php
                        switch ($result['template_type']) {
                            case 'html':
                                echo "<div align=\"right\" style=\"padding-bottom: 5px\">\n";
                                echo "	<a href=\"./preview.php\" target=\"_blank\">Open in New Window</a>\n";
                                echo "</div>\n";
                                if (trim(strip_tags($result['template_content'])) != '') {
                                    $_SESSION['html_message'] = urlencode(checkslashes(trim($result['template_content']), 1));
                                } else {
                                    unset($_SESSION['html_message']);
                                }
                                echo "<iframe src=\"./preview.php\" style=\"border: 1px; width: 100%; height: 400px\"></iframe>\n";
                                break;
                            default:
                                echo wordwrap(nl2br(html_encode($result['template_content'])), $_SESSION['config'][PREF_WORDWRAP], '<br />', 1);
                                break;
                        }
                ?>
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
						<input type="button" value="Close" class="button" onclick="window.location='index.php?section=templates'" />
						<input type="button" class="button" value="Edit Template" onclick="window.location='index.php?section=templates&action=edit&id=<?php echo (int) $_GET['id']; ?>'" />
					</td>
				</tr>
				</table>
				</form>
				<?php
            } else {
            }
        } else {
        }
        break;
    default:
        // Setup "Sort By Field" Information
        if (!empty($_GET['sort']) && strlen($_GET['sort']) > 0) {
            $_SESSION['display']['templates']['sort'] = checkslashes($_GET['sort']);
            setcookie('display[templates][sort]', checkslashes($_GET['sort']), PREF_COOKIE_TIMEOUT);
        } elseif ((empty($_SESSION['display']['templates']['sort'])) && (!empty($_COOKIE['display']['templates']['sort']))) {
            $_SESSION['display']['templates']['sort'] = $_COOKIE['display']['templates']['sort'];
        } else {
            if (empty($_SESSION['display']['templates']['sort'])) {
                $_SESSION['display']['templates']['sort'] = 'name';
                setcookie('display[templates][sort]', 'name', PREF_COOKIE_TIMEOUT);
            }
        }

        // Setup "Sort Order" Information
        if (!empty($_GET['order'])) {
            switch ($_GET['order']) {
                case 'asc':
                    $_SESSION['display']['templates']['order'] = 'ASC';
                    break;
                case 'desc':
                    $_SESSION['display']['templates']['order'] = 'DESC';
                    break;
                default:
                    $_SESSION['display']['templates']['order'] = 'ASC';
                    break;
            }
            setcookie('display[templates][order]', $_SESSION['display']['templates']['order'], PREF_COOKIE_TIMEOUT);
        } elseif ((empty($_SESSION['display']['templates']['order'])) && (!empty($_COOKIE['display']['templates']['order']))) {
            $_SESSION['display']['templates']['order'] = $_COOKIE['display']['templates']['order'];
        } else {
            if (empty($_SESSION['display']['templates']['order'])) {
                $_SESSION['display']['templates']['order'] = 'ASC';
                setcookie('display[templates][order]', 'ASC', PREF_COOKIE_TIMEOUT);
            }
        }

        // Set the internal variables used for sorting, ordering and in pagination.
        $sort = $_SESSION['display']['templates']['sort'];
        $order = $_SESSION['display']['templates']['order'];

        // Get the colomn names of the sorted by colomn.
        switch ($sort) {
            case 'name':
                $sortby = '`template_name`';
                break;
            case 'desc':
                $sortby = '`template_description`';
                break;
            default:
                $sortby = '`template_name`';
                break;
        }
        $ONLOAD[] = "\$('#tab-pane-1').tabs()";
        ?>
		<h1>E-Mail Templates</h1>
		<div id="tab-pane-1">
			<ul>
				<li><a href="#fragment-1"><span>Text Templates</span></a></li>
				<li><a href="#fragment-2"><span>HTML Templates</span></a></li>
			</ul>
			<div id="fragment-1">
				<form action="index.php?section=templates&action=delete" method="post" name="text_templates">
				<div align="right">
					<input type="button" class="button" value="New Template" onclick="window.location='index.php?section=templates&action=add&type=text'" />
				</div>
				<br />
				<?php
                $query = 'SELECT `template_id`, `template_name`, `template_description` FROM `'.TABLES_PREFIX."templates` WHERE `template_type`='text' ORDER BY ".$sortby.' '.strtoupper($order);
        $results = $db->GetAll($query);
        if ($results) {
            ?>
					<table class="tabular" cellspacing="0" cellpadding="1" border="0">
					<colgroup>
						<col style="width: 8%" />
						<col style="width: 32%" />
						<col style="width: 50%" />
						<col style="width: 10%" />
					</colgroup>
					<thead>
						<tr>
							<td>&nbsp;</td>
							<td class="<?php echo ($sort == 'name') ? 'on' : 'off'; ?>"><?php echo order_link('name', 'Text Template Name', $order, $sort); ?></td>
							<td class="<?php echo ($sort == 'desc') ? 'on' : 'off'; ?>"><?php echo order_link('desc', 'Description', $order, $sort); ?></td>
							<td class="close">In Use</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td colspan="5" style="border-top: 1px #333333 dotted; padding-top: 5px">
								<input type="checkbox" name="selectall" value="1" onclick="selection(this, 'deltemplates[]')" />&nbsp;
								<input type="submit" value="Delete Selected" class="button" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
                foreach ($results as $result) {
                    echo "<tr onmouseout=\"this.style.backgroundColor='#FFFFFF'\" onmouseover=\"this.style.backgroundColor='#F0FFD1'\">\n";
                    echo '	<td style="white-space: nowrap"><input type="checkbox" name="deltemplates[]" value="'.$result['template_id'].'" />&nbsp;<a href="index.php?section=templates&action=edit&id='.$result['template_id'].'"><img src="./images/icon-edit-users.gif" width="16" height="16" border="0" alt="Edit" title="Edit '.html_encode($result['template_name'])."\" /></a></td>\n";
                    echo "	<td class=\"cursor\" onclick=\"window.location='index.php?section=templates&action=view&id=".$result['template_id']."'\">".html_encode($result['template_name'])."</td>\n";
                    echo "	<td class=\"cursor\" onclick=\"window.location='index.php?section=templates&action=view&id=".$result['template_id']."'\">".limit_chars(html_encode($result['template_description']), 48)."</td>\n";
                    echo '	<td>'.template_count($result['template_id'])."</td>\n";
                    echo "</tr>\n";
                }
            ?>
					</tbody>
					</table>
					<?php
        } else {
            ?>
					<div class="generic-message">
						There are no text message templates in your ListMessenger database.
						<br /><br />
						To add a new text template, click the <strong>New Template</strong> button above.
					</div>
					<?php
        }
        ?>
				</form>
			</div>
			<div id="fragment-2">
				<form action="index.php?section=templates&action=delete" method="post" name="html_templates">
				<div align="right">
					<input type="button" class="button" value="New Template" onclick="window.location='index.php?section=templates&action=add&type=html'" />
				</div>
				<br />
				<?php
        $query = 'SELECT `template_id`, `template_name`, `template_description` FROM `'.TABLES_PREFIX."templates` WHERE `template_type`='html' ORDER BY ".$sortby.' '.strtoupper($order);
        $results = $db->GetAll($query);
        if ($results) {
            ?>
					<table class="tabular" cellspacing="0" cellpadding="1" border="0">
					<colgroup>
						<col style="width: 8%" />
						<col style="width: 32%" />
						<col style="width: 50%" />
						<col style="width: 10%" />
					</colgroup>
					<thead>
						<tr>
							<td>&nbsp;</td>
							<td class="<?php echo ($sort == 'name') ? 'on' : 'off'; ?>"><?php echo order_link('name', 'HTML Template Name', $order, $sort); ?></td>
							<td class="<?php echo ($sort == 'desc') ? 'on' : 'off'; ?>"><?php echo order_link('desc', 'Description', $order, $sort); ?></td>
							<td class="close">In Use</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td colspan="5" style="border-top: 1px #333333 dotted; padding-top: 5px">
								<input type="checkbox" name="selectall" value="1" onclick="selection(this, 'deltemplates[]')" />&nbsp;
								<input type="submit" value="Delete Selected" class="button" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
                foreach ($results as $result) {
                    echo "<tr onmouseout=\"this.style.backgroundColor='#FFFFFF'\" onmouseover=\"this.style.backgroundColor='#F0FFD1'\">\n";
                    echo '	<td style="white-space: nowrap"><input type="checkbox" name="deltemplates[]" value="'.$result['template_id'].'" />&nbsp;<a href="index.php?section=templates&action=edit&id='.$result['template_id'].'"><img src="./images/icon-edit-users.gif" width="16" height="16" border="0" alt="Edit" title="Edit '.html_encode($result['template_name'])."\" /></a></td>\n";
                    echo "	<td class=\"cursor\" onclick=\"window.location='index.php?section=templates&action=view&id=".$result['template_id']."'\">".html_encode($result['template_name'])."</td>\n";
                    echo "	<td class=\"cursor\" onclick=\"window.location='index.php?section=templates&action=view&id=".$result['template_id']."'\">".limit_chars(html_encode($result['template_description']), 48)."</td>\n";
                    echo "	<td class=\"cursor\" onclick=\"window.location='index.php?section=templates&action=view&id=".$result['template_id']."'\">".template_count($result['template_id'])."</td>\n";
                    echo "</tr>\n";
                }
            ?>
					</tbody>
					</table>
					<?php
        } else {
            ?>
					<div class="generic-message">
						There are no HTML message templates in your ListMessenger database.
						<br /><br />
						To add a new HTML template, click the <strong>New Template</strong> button above.
					</div>
					<?php
        }
        ?>
				</form>
			</div>
		</div>
		<?php
    break;
}
