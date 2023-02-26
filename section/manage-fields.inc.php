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

$ACTION = '';
$ACTIONS_AVAILABLE = ['add', 'edit', 'delete', 'view', 'update'];
$PROCESSED = [];
$CFIELDS_ID = 0;

if ((!empty($_GET['action'])) && in_array(trim($_GET['action']), $ACTIONS_AVAILABLE)) {
    $ACTION = clean_input($_GET['action']);
} elseif ((!empty($_POST['action'])) && in_array(trim($_POST['action']), $ACTIONS_AVAILABLE)) {
    $ACTION = clean_input($_POST['action']);
}

switch ($ACTION) {
    case 'add':
        $ONLOAD[] = "custom_field_options($('#field_type').val())";

        if ($_POST) {
            if ((!empty($_POST['field_type'])) && (trim($_POST['field_type']) == '')) {
                ++$ERROR;
                $ERRORSTR[] = 'You did not select what type of field this new field will be.';
            } else {
                switch ($_POST['field_type']) {
                    case 'checkbox':
                        if (strlen(trim($_POST['field_sname'])) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a checkbox. This will be the HTML name attribute in the form, so make this lowercase and no special characters/spaces.';
                        } else {
                            $varcheck = check_variable($_POST['field_sname']);
                            if (!$varcheck[0]) {
                                ++$ERROR;
                                $ERRORSTR[] = $varcheck[1];
                            } else {
                                if ($_POST['field_sname'] != $varcheck[1]) {
                                    $_POST['field_sname'] = $varcheck[1];
                                }
                            }
                        }
                        if (strlen(trim($_POST['field_options'])) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = '&quot;Field Options&quot; are required when the field type is a checkbox. This is how the program generates the checkboxs. Use the following as an example:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                        } else {
                            $fix_lf = str_replace("\r", "\n", trim($_POST['field_options']));
                            $fix_lf = str_replace("\n\n", "\n", $fix_lf);
                            $options = explode("\n", $fix_lf);

                            if (count($options) < 1) {
                                ++$ERROR;
                                $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this checkbox. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                            } else {
                                foreach ($options as $option) {
                                    $pieces = explode('=', $option);
                                    if (count($pieces) < 1) {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this checkbox. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                    } else {
                                        if (strlen(trim($pieces[0])) < 1) {
                                            ++$ERROR;
                                            $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this checkbox. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                        } else {
                                            if (strlen(trim($pieces[1])) < 1) {
                                                ++$ERROR;
                                                $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this checkbox. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />;black=Black Ball';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    case 'hidden':
                        if (strlen(trim($_POST['field_sname'])) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a hidden field. This will be the HTML name attribute in the form.';
                        } else {
                            $varcheck = check_variable($_POST['field_sname']);
                            if (!$varcheck[0]) {
                                ++$ERROR;
                                $ERRORSTR[] = $varcheck[1];
                            } else {
                                if ($_POST['field_sname'] != $varcheck[1]) {
                                    $_POST['field_sname'] = $varcheck[1];
                                }
                            }
                        }
                        if (strlen(trim($_POST['field_options'])) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = '&quot;Field Options&quot; are required when the field type is a hidden field. This will be the hidden fields HTML value attribute in the form.';
                        }
                        break;
                    case 'linebreak':
                        $_POST['field_sname'] = substr(md5(uniqid(mt_rand(), true)), 0, 16);
                        $_POST['field_lname'] = null;
                        $_POST['field_length'] = 0;
                        $_POST['field_req'] = 0;
                        break;
                    case 'radio':
                        if (strlen(trim($_POST['field_sname'])) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a radio box. This will be the HTML name attribute in the form, so make this lowercase and no special characters/spaces.';
                        } else {
                            $varcheck = check_variable($_POST['field_sname']);
                            if (!$varcheck[0]) {
                                ++$ERROR;
                                $ERRORSTR[] = $varcheck[1];
                            } else {
                                if ($_POST['field_sname'] != $varcheck[1]) {
                                    $_POST['field_sname'] = $varcheck[1];
                                }
                            }
                        }
                        if (strlen(trim($_POST['field_options'])) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = '&quot;Field Options&quot; are required when the field type is a radio box. This is how the program generates the radio boxes. Use the following as an example:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                        } else {
                            $fix_lf = str_replace("\r", "\n", trim($_POST['field_options']));
                            $fix_lf = str_replace("\n\n", "\n", $fix_lf);
                            $options = explode("\n", $fix_lf);
                            if (count($options) < 1) {
                                ++$ERROR;
                                $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this radio box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                            } else {
                                foreach ($options as $option) {
                                    $pieces = explode('=', $option);
                                    if (count($pieces) < 1) {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this radio box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                    } else {
                                        if (strlen(trim($pieces[0])) < 1) {
                                            ++$ERROR;
                                            $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this radio box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                        } else {
                                            if (strlen(trim($pieces[1])) < 1) {
                                                ++$ERROR;
                                                $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this radio box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    case 'select':
                        if (strlen(trim($_POST['field_sname'])) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a select box. This will be the HTML name attribute in the form, so make this lowercase and no special characters/spaces.';
                        } else {
                            $varcheck = check_variable($_POST['field_sname']);
                            if (!$varcheck[0]) {
                                ++$ERROR;
                                $ERRORSTR[] = $varcheck[1];
                            } else {
                                if ($_POST['field_sname'] != $varcheck[1]) {
                                    $_POST['field_sname'] = $varcheck[1];
                                }
                            }
                        }
                        if (strlen(trim($_POST['field_options'])) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = '&quot;Field Options&quot; are required when the field type is a select box. This is how the program generates the HTML select options. Use the following as an example:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                        } else {
                            $fix_lf = str_replace("\r", "\n", trim($_POST['field_options']));
                            $fix_lf = str_replace("\n\n", "\n", $fix_lf);
                            $options = explode("\n", $fix_lf);
                            if (count($options) < 1) {
                                ++$ERROR;
                                $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this select box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                            } else {
                                foreach ($options as $option) {
                                    $pieces = explode('=', $option);
                                    if (count($pieces) < 1) {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this select box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                    } else {
                                        if (strlen(trim($pieces[0])) < 1) {
                                            ++$ERROR;
                                            $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this select box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                        } else {
                                            if (strlen(trim($pieces[1])) < 1) {
                                                ++$ERROR;
                                                $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this select box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    case 'textarea':
                        if (strlen(trim($_POST['field_sname'])) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a textarea. This will be the HTML name attribute in the form, so make this lowercase and no special characters/spaces.';
                        } else {
                            $varcheck = check_variable($_POST['field_sname']);
                            if (!$varcheck[0]) {
                                ++$ERROR;
                                $ERRORSTR[] = $varcheck[1];
                            } else {
                                if ($_POST['field_sname'] != $varcheck[1]) {
                                    $_POST['field_sname'] = $varcheck[1];
                                }
                            }
                        }
                        break;
                    case 'textbox':
                        if (strlen(trim($_POST['field_sname'])) < 1) {
                            ++$ERROR;
                            $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a text box. This will be the HTML name attribute in the form, so make this lowercase and no special characters/spaces.';
                        } else {
                            $varcheck = check_variable($_POST['field_sname']);
                            if (!$varcheck[0]) {
                                ++$ERROR;
                                $ERRORSTR[] = $varcheck[1];
                            } else {
                                if ($_POST['field_sname'] != $varcheck[1]) {
                                    $_POST['field_sname'] = $varcheck[1];
                                }
                            }
                        }
                        break;
                    default:
                        $ERROR++;
                        $ERRORSTR[] = 'Unrecognized custom field type. Please reselect your custom field type and try again.';
                        break;
                }

                if (!$ERROR) {
                    $query = 'SELECT MAX(`field_order`) AS `max` FROM `'.TABLES_PREFIX.'cfields`';
                    $result = $db->GetRow($query);
                    if ($result) {
                        $max = ($result['max'] + 1);
                    } else {
                        $max = 1;
                    }

                    $query = 'INSERT INTO `'.TABLES_PREFIX."cfields` (`cfields_id`, `field_type`, `field_options`, `field_sname`, `field_lname`, `field_length`, `field_req`, `field_order`) VALUES (NULL, '".checkslashes($_POST['field_type'])."', '".checkslashes(trim($_POST['field_options']))."', '".checkslashes($_POST['field_sname'])."', '".checkslashes($_POST['field_lname'])."', '".checkslashes($_POST['field_length'])."', '".checkslashes($_POST['field_req'])."', '".$max."');";
                    if ($db->Execute($query)) {
                        header('Location: index.php?section=manage-fields');
                        exit;
                    } else {
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to add field to database. Database said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }

                        ++$ERROR;
                        $ERRORSTR[] = 'Sorry, but we were unable to add the new field due to a database error. Please check your ListMessenger error_log for more information if you have logging enabled.';
                    }
                }
            }
        }
        ?>
		<h1>New Custom Field</h1>
		<?php echo display_notice(['After this custom field has been added to ListMessenger, do not forget to update the HTML form on your website so subscribers can successfully complete it.']); ?>
		<?php echo ($ERROR > 0) ? display_error($ERRORSTR) : ''; ?>
		<form action="index.php?section=manage-fields&action=add" method="post">
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
					<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=manage-fields'" />
					<input type="submit" class="button" value="Add Field" />
				</td>
			</tr>
		</tfoot>		
		<tbody>
			<tr>
				<td>
					<?php echo create_tooltip('Field Type', '<strong>Field Name: <em>Field Type</em></strong><br />This is the type of custom field that you are looking at adding. Currently you can select a number of different HTML field types as well as a linebreak.<br /><br /><strong>Example:</strong><br />Radio Buttons', true); ?>
				</td>
				<td>
					<select id="field_type" name="field_type" style="width: 180px" onchange="custom_field_options(this.options[this.selectedIndex].value)">
					<option value="checkbox"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'checkbox')) ? ' selected="selected"' : ''; ?>>Checkbox</option>
					<option value="hidden"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'hidden')) ? ' selected="selected"' : ''; ?>>Hidden Field</option>
					<option value="linebreak"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'linebreak')) ? ' selected="selected"' : ''; ?>>Linebreak</option>
					<option value="radio"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'radio')) ? ' selected="selected"' : ''; ?>>Radio Buttons</option>
					<option value="select"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'select')) ? ' selected="selected"' : ''; ?>>Select Box</option>
					<option value="textarea"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'textarea')) ? ' selected="selected"' : ''; ?>>Textarea</option>
					<option value="textbox"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'textbox')) ? ' selected="selected"' : ''; ?>>Textbox</option>
					</select>
				</td>
			</tr>
		</tbody>
		<tbody id="toggle-field_sname" style="display: none">
			<tr>
				<td>
					<?php echo create_tooltip('Short Variable Name', "<strong>Field Name: <em>Short Variable Name</em></strong><br />The short variable name is the unique key for this custom field. You will use the variable name in your messages to reference the subscribers' field value.<br /><br /><strong>Example:</strong><br />Short Variable Name: favcolor<br /><br /><strong>Usage:</strong><br />Your favourite colour is: [favcolor]", true); ?>
				</td>
				<td><input type="text" class="text-box" style="width: 175px" name="field_sname" value="<?php echo (!empty($_POST['field_sname'])) ? checkslashes($_POST['field_sname'], 1) : ''; ?>" maxlength="16" /></td>
			</tr>
		</tbody>
		<tbody id="toggle-field_lname" style="display: none">
			<tr>
				<td>
					<?php echo create_tooltip('Display Name', '<strong>Field Name: <em>Display Name</em></strong><br />This is the title or longer name for your custom field. This could also be a question.<br /><br /><strong>Example:</strong><br />What is your favourite colour?', true); ?>
				</td>
				<td><input type="text" class="text-box" style="width: 360px" name="field_lname" value="<?php echo (!empty($_POST['field_lname'])) ? checkslashes($_POST['field_lname'], 1) : ''; ?>" maxlength="64"/></td>
			</tr>
		</tbody>
		<tbody id="toggle-field_options" style="display: none">
			<tr>
				<td style="vertical-align: top">
					<?php echo create_tooltip('Field Options / Values', '<strong>Field Name: <em>Field Options</em></strong><br />This is the options or defined value(s) of your custom field. If you are using a checkbox, radio button or select box you will want to specify your options here.<br /><br /><strong>Example:</strong><br />blue=Blue<br />red=Red<br />green=Green', true); ?>
				</td>
				<td><textarea name="field_options" style="width: 360px; height: 125px"><?php echo (!empty($_POST['field_options'])) ? checkslashes($_POST['field_options'], 1) : ''; ?></textarea></td>
			</tr>
		</tbody>
		<tbody id="toggle-field_length" style="display: none">
			<tr>
				<td>
					<?php echo create_tooltip('Maxlength', '<strong>Field Name: <em>Maxlength</em></strong><br />This is only used if your Field Type is a textbox. This will limit the subscriber to typing X number of characters in your textbox.<br /><br /><strong>Example:</strong><br />64', true); ?>
				</td>
				<td><input type="text" class="text-box" style="width: 50px" name="field_length" value="<?php echo (!empty($_POST['field_length'])) ? checkslashes($_POST['field_length'], 1) : 32; ?>" maxlength="4" /></td>
			</tr>
		</tbody>
		<tbody id="toggle-field_req" style="display: none">
			<tr>
				<td>
					<?php echo create_tooltip('Is this a required field?', '<strong>Field Name: <em>Required Field</em></strong><br />Choose whether or not you wish you force your subscribers to provide a response to this field or not.', true); ?>
				</td>
				<td>
					<select name="field_req" style="width: 54px">
					<option value="0"<?php echo ((!empty($_POST['field_req'])) && ($_POST['field_req'] == '0')) ? ' selected="selected"' : ''; ?>>No</option>
					<option value="1"<?php echo ((!empty($_POST['field_req'])) && ($_POST['field_req'] == '1')) ? ' selected="selected"' : ''; ?>>Yes</option>
					</select>
				</td>
			</tr>
		</tbody>
		</table>
		</form>
		<?php
    break;
    case 'delete':
        if (strlen($_GET['id']) < 1) {
            ++$ERROR;
            $ERRORSTR[] = 'There was no custom field ID provided to delete.';
            echo ($ERROR > 0) ? display_error($ERRORSTR) : '';
            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThere was no custom field ID provided to delete.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
        } else {
            if (!empty($_POST['confirmed']) && $_POST['confirmed'] == 'true') {
                if ($_POST['cfields_id'] != $_GET['id']) {
                    ++$ERROR;
                    $ERRORSTR[] = 'The field ID in the URL does not match the posted field ID.';
                }

                if ($ERROR) {
                    echo display_error($ERRORSTR);
                } else {
                    $query = 'DELETE FROM `'.TABLES_PREFIX."cfields` WHERE `cfields_id`='".checkslashes($_POST['cfields_id'])."'";
                    if (!$db->Execute($query)) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Unable to delete the custom field from the custom fields table. Please check the error_log for more information.';
                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete the custom field from the custom fields table. MySQL reported: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    } else {
                        $query = 'DELETE FROM `'.TABLES_PREFIX."cdata` WHERE `cfield_id`='".checkslashes($_POST['cfields_id'])."'";
                        if (!$db->Execute($query)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Unable to delete the custom field data from the users table. Please check the error_log for more information.';
                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete the custom field data from the users table. MySQL reported: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }
                        }
                    }

                    if ($ERROR) {
                        echo display_error($ERRORSTR);
                    } else {
                        header('Location: index.php?section=manage-fields');
                        exit;
                    }
                }
            } else {
                ?>
				<h1>Deleting Custom Field</h1>
				<?php echo ($ERROR > 0) ? display_error($ERRORSTR) : ''; ?>
				Please confirm that you wish to delete the following custom field. Please note that if you delete this custom field, it will remove all pertaining custom user data as well.
				<br /><br />
				<div style="border-bottom: 1px #CC0000 dotted; padding: 3px; margin: 3px">
				<?php echo generate_cfields('', 'display', checkslashes($_GET['id'])); ?>
				</div>
				<span class="small-grey" style="color: #CC0000">*</span> <span class="small-grey">If you are deleting a <strong>linebreak</strong> or <strong>hidden field</strong>, you will not see anything above.</span>
				<br /><br />
				<form action="index.php?section=manage-fields&action=delete&id=<?php echo checkslashes($_GET['id']); ?>" method="post">
				<input type="hidden" name="confirmed" value="true" />
				<input type="hidden" name="cfields_id" value="<?php echo checkslashes($_GET['id']); ?>" />
				<table style="width: 100%" cellspacing="0" cellpadding="3" border="0">
				<tr>
					<td style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
						<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=manage-fields'" />
						<input type="submit" value="Confirm" class="button" />
					</td>
				</tr>
				</table>
				</form>
				<?php
            }
        }
        break;
    case 'edit':
        if ((!empty($_GET['id'])) && (!$CFIELDS_ID = clean_input($_GET['id'], ['trim', 'int']))) {
            ++$ERROR;
            $ERRORSTR[] = 'There was no custom field ID provided to edit, please select one from the &quot;Manage Fields&quot; section.';

            if ($ERROR) {
                echo display_error($ERRORSTR);
            }

            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThere was no custom field ID provided to edit.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
        } else {
            $ONLOAD[] = "custom_field_options($('#field_type').val())";

            $query = 'SELECT * FROM `'.TABLES_PREFIX.'cfields` WHERE `cfields_id` = '.$db->qstr($CFIELDS_ID);
            $result = $db->GetRow($query);
            if ($result) {
                if ($_POST) {
                    if ((!empty($_POST['field_type'])) && (trim($_POST['field_type']) == '')) {
                        ++$ERROR;
                        $ERRORSTR[] = 'You did not select what type of field this new field will be.';
                    } else {
                        switch ($_POST['field_type']) {
                            case 'checkbox':
                                if (strlen(trim($_POST['field_sname'])) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a checkbox. This will be the HTML name attribute in the form, so make this lowercase and no special characters/spaces.';
                                } else {
                                    $varcheck = check_variable(checkslashes($_POST['field_sname']), true);
                                    if (!$varcheck[0]) {
                                        ++$ERROR;
                                        $ERRORSTR[] = $varcheck[1];
                                    } else {
                                        if ($_POST['field_sname'] != $varcheck[1]) {
                                            $_POST['field_sname'] = $varcheck[1];
                                        }
                                    }
                                }

                                if (strlen(trim($_POST['field_options'])) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = '&quot;Field Options&quot; are required when the field type is a checkbox. This is how the program generates the checkboxs. Use the following as an example:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                } else {
                                    $fix_lf = str_replace("\r", "\n", trim($_POST['field_options']));
                                    $fix_lf = str_replace("\n\n", "\n", $fix_lf);
                                    $options = explode("\n", $fix_lf);
                                    if (count($options) < 1) {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this checkbox. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                    } else {
                                        foreach ($options as $option) {
                                            $pieces = explode('=', $option);
                                            if (count($pieces) < 1) {
                                                ++$ERROR;
                                                $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this checkbox. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                            } else {
                                                if (strlen(trim($pieces[0])) < 1) {
                                                    ++$ERROR;
                                                    $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this checkbox. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                                } else {
                                                    if (strlen(trim($pieces[1])) < 1) {
                                                        ++$ERROR;
                                                        $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this checkbox. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'hidden':
                                if (strlen(trim($_POST['field_sname'])) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a hidden field. This will be the HTML name attribute in the form.';
                                } else {
                                    $varcheck = check_variable($_POST['field_sname'], true);
                                    if (!$varcheck[0]) {
                                        ++$ERROR;
                                        $ERRORSTR[] = $varcheck[1];
                                    } else {
                                        if ($_POST['field_sname'] != $varcheck[1]) {
                                            $_POST['field_sname'] = $varcheck[1];
                                        }
                                    }
                                }
                                if (strlen(trim($_POST['field_options'])) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = '&quot;Field Options&quot; are required when the field type is a hidden field. This will be the hidden fields HTML value attribute in the form.';
                                }
                                break;
                            case 'linebreak':
                                break;
                            case 'radio':
                                if (strlen(trim($_POST['field_sname'])) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a radio box. This will be the HTML name attribute in the form, so make this lowercase and no special characters/spaces.';
                                } else {
                                    $varcheck = check_variable($_POST['field_sname'], true);
                                    if (!$varcheck[0]) {
                                        ++$ERROR;
                                        $ERRORSTR[] = $varcheck[1];
                                    } else {
                                        if ($_POST['field_sname'] != $varcheck[1]) {
                                            $_POST['field_sname'] = $varcheck[1];
                                        }
                                    }
                                }
                                if (strlen(trim($_POST['field_options'])) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = '&quot;Field Options&quot; are required when the field type is a radio box. This is how the program generates the radio boxes. Use the following as an example:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                } else {
                                    $fix_lf = str_replace("\r", "\n", trim($_POST['field_options']));
                                    $fix_lf = str_replace("\n\n", "\n", $fix_lf);
                                    $options = explode("\n", $fix_lf);
                                    if (count($options) < 1) {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this radio box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                    } else {
                                        foreach ($options as $option) {
                                            $pieces = explode('=', $option);
                                            if (count($pieces) < 1) {
                                                ++$ERROR;
                                                $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this radio box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                            } else {
                                                if (strlen(trim($pieces[0])) < 1) {
                                                    ++$ERROR;
                                                    $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this radio box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                                } else {
                                                    if (strlen(trim($pieces[1])) < 1) {
                                                        ++$ERROR;
                                                        $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this radio box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'select':
                                if (strlen(trim($_POST['field_sname'])) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a select box. This will be the HTML name attribute in the form, so make this lowercase and no special characters/spaces.';
                                } else {
                                    $varcheck = check_variable($_POST['field_sname'], true);
                                    if (!$varcheck[0]) {
                                        ++$ERROR;
                                        $ERRORSTR[] = $varcheck[1];
                                    } else {
                                        if ($_POST['field_sname'] != $varcheck[1]) {
                                            $_POST['field_sname'] = $varcheck[1];
                                        }
                                    }
                                }
                                if (strlen(trim($_POST['field_options'])) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = '&quot;Field Options&quot; are required when the field type is a select box. This is how the program generates the HTML select options. Use the following as an example:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                } else {
                                    $fix_lf = str_replace("\r", "\n", trim($_POST['field_options']));
                                    $fix_lf = str_replace("\n\n", "\n", $fix_lf);
                                    $options = explode("\n", $fix_lf);

                                    if (count($options) < 1) {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this select box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                    } else {
                                        foreach ($options as $option) {
                                            $pieces = explode('=', $option);
                                            if (count($pieces) < 1) {
                                                ++$ERROR;
                                                $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this select box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                            } else {
                                                if (strlen(trim($pieces[0])) < 1) {
                                                    ++$ERROR;
                                                    $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this select box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                                } else {
                                                    if (strlen(trim($pieces[1])) < 1) {
                                                        ++$ERROR;
                                                        $ERRORSTR[] = 'It looks as though you have formatted &quot;Field Options&quot; incorrectly for this select box. Use the following as an example of proper usage:<br />blue=Blue Ball<br />red=Red Ball<br />yellow=Yellow Ball<br />black=Black Ball';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'textarea':
                                if (strlen(trim($_POST['field_sname'])) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a textarea. This will be the HTML name attribute in the form, so make this lowercase and no special characters/spaces.';
                                } else {
                                    $varcheck = check_variable($_POST['field_sname'], true);
                                    if (!$varcheck[0]) {
                                        ++$ERROR;
                                        $ERRORSTR[] = $varcheck[1];
                                    } else {
                                        if ($_POST['field_sname'] != $varcheck[1]) {
                                            $_POST['field_sname'] = $varcheck[1];
                                        }
                                    }
                                }
                                break;
                            case 'textbox':
                                if (strlen(trim($_POST['field_sname'])) < 1) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'A &quot;Short Variable Name&quot; is required when the field type is a text box. This will be the HTML name attribute in the form, so make this lowercase and no special characters/spaces.';
                                } else {
                                    $varcheck = check_variable($_POST['field_sname'], true);
                                    if (!$varcheck[0]) {
                                        ++$ERROR;
                                        $ERRORSTR[] = $varcheck[1];
                                    } else {
                                        if ($_POST['field_sname'] != $varcheck[1]) {
                                            $_POST['field_sname'] = $varcheck[1];
                                        }
                                    }
                                }
                                break;
                            default:
                                $ERROR++;
                                $ERRORSTR[] = 'Unrecognized custom field type. Please reselect your custom field type and try again.';
                                break;
                        }

                        if (!$ERROR) {
                            $query = 'UPDATE `'.TABLES_PREFIX."cfields` SET `field_type`='".checkslashes(trim($_POST['field_type']))."', `field_options`='".checkslashes(trim($_POST['field_options']))."', `field_sname`='".checkslashes(trim($_POST['field_sname']))."', `field_lname`='".checkslashes(trim($_POST['field_lname']))."', `field_length`='".checkslashes(trim($_POST['field_length']))."', `field_req`='".checkslashes(trim($_POST['field_req']))."' WHERE `cfields_id` = ".$CFIELDS_ID;
                            if ($db->Execute($query)) {
                                header('Location: index.php?section=manage-fields');
                                exit;
                            } else {
                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to edit custom field in database. Database said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }

                                ++$ERROR;
                                $ERRORSTR[] = 'We were unable to edit the custom field due to a database error. Please check your ListMessenger error_log for more information if you have logging enabled.';
                            }
                        }
                    }
                }
                ?>
				<h1>Edit Custom Field</h1>
				<?php echo display_notice(['After this custom field has been modified, please do not forget to update the HTML form on your website so subscribers can successfully complete it.']); ?>
				<?php echo ($ERROR > 0) ? display_error($ERRORSTR) : ''; ?>
				<form action="index.php?section=manage-fields&amp;action=edit&amp;id=<?php echo $CFIELDS_ID; ?>" method="post">
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
							<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=manage-fields'" />
							<input type="submit" class="button" value="Save" />
						</td>
					</tr>
				</tfoot>		
				<tbody>
					<tr>
						<td>
							<?php echo create_tooltip('Field Type', '<strong>Field Name: <em>Field Type</em></strong><br />This is the type of custom field that you are looking at adding. Currently you can select a number of different HTML field types as well as a linebreak.<br /><br /><strong>Example:</strong><br />Radio Buttons', true); ?>
						</td>
						<td>
							<select id="field_type" name="field_type" style="width: 180px" onchange="custom_field_options(this.options[this.selectedIndex].value)">
							<option value="checkbox"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'checkbox')) ? ' selected="selected"' : (($result['field_type'] == 'checkbox') ? ' selected="selected"' : ''); ?>>Checkbox</option>
							<option value="hidden"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'hidden')) ? ' selected="selected"' : (($result['field_type'] == 'hidden') ? ' selected="selected"' : ''); ?>>Hidden Field</option>
							<option value="linebreak"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'linebreak')) ? ' selected="selected"' : (($result['field_type'] == 'linebreak') ? ' selected="selected"' : ''); ?>>Linebreak</option>
							<option value="radio"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'radio')) ? ' selected="selected"' : (($result['field_type'] == 'radio') ? ' selected="selected"' : ''); ?>>Radio Buttons</option>
							<option value="select"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'select')) ? ' selected="selected"' : (($result['field_type'] == 'select') ? ' selected="selected"' : ''); ?>>Select Box</option>
							<option value="textarea"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'textarea')) ? ' selected="selected"' : (($result['field_type'] == 'textarea') ? ' selected="selected"' : ''); ?>>Textarea</option>
							<option value="textbox"<?php echo ((!empty($_POST['field_type'])) && ($_POST['field_type'] == 'textbox')) ? ' selected="selected"' : (($result['field_type'] == 'textbox') ? ' selected="selected"' : ''); ?>>Textbox</option>
							</select>
						</td>
					</tr>
				</tbody>
				<tbody id="toggle-field_sname" style="display: none">
					<tr>
						<td>
							<?php echo create_tooltip('Short Variable Name', "<strong>Field Name: <em>Short Variable Name</em></strong><br />The short variable name is the unique key for this custom field. You will use the variable name in your messages to reference the subscribers' field value.<br /><br /><strong>Example:</strong><br />Short Variable Name: favcolor<br /><br /><strong>Usage:</strong><br />Your favourite colour is: [favcolor]", true); ?>
						</td>
						<td><input type="text" class="text-box" style="width: 175px" name="field_sname" value="<?php echo html_encode((!empty($_POST['field_sname'])) ? checkslashes($_POST['field_sname'], 1) : $result['field_sname']); ?>" maxlength="16" /></td>
					</tr>
				</tbody>
				<tbody id="toggle-field_lname" style="display: none">
					<tr>
						<td>
							<?php echo create_tooltip('Display Name', '<strong>Field Name: <em>Display Name</em></strong><br />This is the title or longer name for your custom field. This could also be a question.<br /><br /><strong>Example:</strong><br />What is your favourite colour?', true); ?>
						</td>
						<td><input type="text" class="text-box" style="width: 360px" name="field_lname" value="<?php echo html_encode((!empty($_POST['field_lname'])) ? checkslashes($_POST['field_lname'], 1) : $result['field_lname']); ?>" maxlength="64"/></td>
					</tr>
				</tbody>
				<tbody id="toggle-field_options" style="display: none">
					<tr>
						<td style="vertical-align: top">
							<?php echo create_tooltip('Field Options / Values', '<strong>Field Name: <em>Field Options</em></strong><br />This is the options or defined value(s) of your custom field. If you are using a checkbox, radio button or select box you will want to specify your options here.<br /><br /><strong>Example:</strong><br />blue=Blue<br />red=Red<br />green=Green', true); ?>
						</td>
						<td><textarea name="field_options" style="width: 360px; height: 125px"><?php echo html_encode((!empty($_POST['field_options'])) ? checkslashes($_POST['field_options'], 1) : $result['field_options']); ?></textarea></td>
					</tr>
				</tbody>
				<tbody id="toggle-field_length" style="display: none">
					<tr>
						<td>
							<?php echo create_tooltip('Maxlength', '<strong>Field Name: <em>Maxlength</em></strong><br />This is only used if your Field Type is a textbox. This will limit the subscriber to typing X number of characters in your textbox.<br /><br /><strong>Example:</strong><br />64', true); ?>
						</td>
						<td><input type="text" class="text-box" style="width: 50px" name="field_length" value="<?php echo html_encode((!empty($_POST['field_length'])) ? checkslashes($_POST['field_length'], 1) : $result['field_length']); ?>" maxlength="4" /></td>
					</tr>
				</tbody>
				<tbody id="toggle-field_req" style="display: none">
					<tr>
						<td>
							<?php echo create_tooltip('Is this a required field?', '<strong>Field Name: <em>Required Field</em></strong><br />Choose whether or not you wish you force your subscribers to provide a response to this field or not.', true); ?>
						</td>
						<td>
							<select name="field_req" style="width: 54px">
							<option value="0"<?php echo ((!empty($_POST['field_req'])) && ($_POST['field_req'] == '0')) ? ' selected="selected"' : (($result['field_req'] == '0') ? ' selected="selected"' : ''); ?>>No</option>
							<option value="1"<?php echo ((!empty($_POST['field_req'])) && ($_POST['field_req'] == '1')) ? ' selected="selected"' : (($result['field_req'] == '1') ? ' selected="selected"' : ''); ?>>Yes</option>
							</select>
						</td>
					</tr>
				</tbody>
				</table>
				</form>
				<?php
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'The provided custom field ID does not exist in the database. Please select the field you wish to edit from the Manage Fields section.';

                if ($ERROR) {
                    echo display_error($ERRORSTR);
                }

                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThe custom field ID specified in the URL [".checkslashes($_GET['id'])."] does not exist in the database.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            }
        }
        break;
    case 'view':
        $mode = '';
        if (!empty($_GET['mode'])) {
            $mode = clean_input($_GET['mode'], 'alphanumeric');
        }

        switch ($mode) {
            case 'html':
                ?>
				<h1>Manage Fields: <small>Get HTML Code</small></h1>
				This page shows the HTML code segment that can be placed directly in your website. Please keep in mind this is just one of examples you can use on your website. For additional examples go to the <a href="index.php?section=end-user">Control Panel &gt; End-User Tools</a> section.
				<br /><br />
				<?php
                echo display_notice(['Please do not forget to replace the &quot;ENTER_GROUP_ID_HERE&quot; string in the form below with the actual ID of the group you would like your subscriber inserted to. To see the actual &quot;Group ID&quot; click <a href="index.php?section=manage-groups">Manage Groups</a>.']);

                echo "<a href=\"index.php?section=manage-fields\">Form Designer</a>&nbsp;|&nbsp;<a href=\"index.php?section=manage-fields&action=view\">Preview Form</a>&nbsp;|&nbsp;<a href=\"index.php?section=manage-fields&action=view&mode=html\" style=\"color: #663333\">Get HTML Code</a><br />\n";
                echo "<div style=\"width: 95%; overflow: auto; border: 1px #666666 solid; padding: 10px; margin: 0px\">\n";
                echo '<pre style="font-size: 11px">'.generate_cfields($_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_FILENAME], 'html')."</pre>\n";
                echo "</div>\n";
                break;
            default:
                ?>
				<h1>Manage Fields: <small>Preview Form</small></h1>
				This page shows what your subscriber form might look like to your subscribers. Please keep in mind this is just one of examples you can use on your website. For additional examples go to the <a href="index.php?section=end-user">Control Panel &gt; End-User Tools</a> section.
				<br /><br />
				<?php
                echo "<a href=\"index.php?section=manage-fields\">Form Designer</a>&nbsp;|&nbsp;<a href=\"index.php?section=manage-fields&action=view\" style=\"color: #663333\">Preview Form</a>&nbsp;|&nbsp;<a href=\"index.php?section=manage-fields&action=view&mode=html\">Get HTML Code</a><br />\n";
                echo "<div style=\"width: 95%; overflow: auto; border: 1px #666666 solid; padding: 10px; margin: 0px\">\n";
                echo generate_cfields($_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_FILENAME]);
                echo "</div>\n";
                break;
        }
        break;
    case 'update':
    default:
        $i = count($SIDEBAR);
        $SIDEBAR[$i] = "<br /><div align=\"center\"><input type=\"button\" value=\"New Field\" class=\"button\" onclick=\"window.location='index.php?section=manage-fields&action=add'\" /></div>";

        if ($ACTION == 'update') {
            /**
             * Make everything not required for a second.
             */
            $query = 'UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value` = 'no' WHERE `preference_id` IN ('".ENDUSER_REQUIRE_FIRSTNAME."', '".ENDUSER_REQUIRE_LASTNAME."')";
            $db->Execute($query);

            /**
             * Make everything not required for a second.
             */
            $query = 'UPDATE `'.TABLES_PREFIX."cfields` SET `field_req` = '0'";
            $db->Execute($query);

            if ((!empty($_POST['required'])) && is_array($_POST['required']) && count($_POST['required'])) {
                /*
                 * Update the static first and lastname preferences.
                 */
                if ((!empty($_POST['required']['static'])) && is_array($_POST['required']['static']) && count($_POST['required']['static'])) {
                    /*
                     * Now go through and make the checked options required.
                     */
                    foreach ($_POST['required']['static'] as $preference_id => $required) {
                        if ($preference_id = (int) $preference_id) {
                            $query = 'UPDATE `'.TABLES_PREFIX.'preferences` SET `preference_value` = '.$db->qstr(($required == 'yes') ? 'yes' : 'no').' WHERE `preference_id` = '.$db->qstr($preference_id).' LIMIT 1';
                            $db->Execute($query);
                        }
                    }
                }

                if ((!empty($_POST['required']['cfield'])) && is_array($_POST['required']['cfield']) && count($_POST['required']['cfield'])) {
                    /*
                     * Now go through and make the checked options required.
                     */
                    foreach ($_POST['required']['cfield'] as $cfields_id => $required) {
                        if ($cfields_id = (int) $cfields_id) {
                            $query = 'UPDATE `'.TABLES_PREFIX.'cfields` SET `field_req` = '.$db->qstr(($required == 'yes') ? '1' : '0').' WHERE `cfields_id` = '.$db->qstr($cfields_id).' LIMIT 1';
                            $db->Execute($query);
                        }
                    }
                }
            }

            /*
             * Go ahead and update the order of each custom field.
             */
            if ((!empty($_POST['order'])) && is_array($_POST['order']) && count($_POST['order'])) {
                foreach ($_POST['order'] as $cfields_id => $field_order) {
                    if (($cfields_id = (int) $cfields_id) && ($field_order = (int) $field_order)) {
                        $query = 'UPDATE `'.TABLES_PREFIX.'cfields` SET `field_order` = '.$db->qstr($field_order).' WHERE `cfields_id` = '.$db->qstr($cfields_id);
                        $db->Execute($query);
                    }
                }
            }
        }
        ?>
		<h1>Manage Fields: <small>Form Designer</small></h1>
		This page allows you to create, modify or remove the custom fields that ListMessenger is able to handle. A custom field is an additional piece of information that ListMessenger can collect from your subscribers (i.e. their address, city, province, country, postal code, etc.). By default ListMessenger collects the subscribers' e-mail address, first and last name.
		<br /><br />
		<?php
        $query = 'SELECT MAX(`field_order`) AS `max` FROM `'.TABLES_PREFIX.'cfields`';
        $result = $db->GetRow($query);
        if ($result) {
            $max = ($result['max'] + 1);
        }

        $query = 'SELECT * FROM `'.TABLES_PREFIX.'cfields` ORDER BY `field_order` ASC';
        $results = $db->GetAll($query);
        if (!$results) {
            echo "<div class=\"generic-message\">\n";
            echo "	There are no custom fields your Listmessenger database.\n";
            echo "	<br /><br />\n";
            echo '	To create a custom field, click <strong>New Field</strong> button to the left.</span>';
            echo "</div>\n";
        }
        ?>
		<a href="index.php?section=manage-fields" style="color: #663333">Form Designer</a>&nbsp;|&nbsp;<a href="index.php?section=manage-fields&action=view">Preview Form</a>&nbsp;|&nbsp;<a href="index.php?section=manage-fields&action=view&mode=html">Get HTML Code</a><br />
		<div style="width: 95%; overflow: auto; border: 1px #666666 solid; padding: 10px; margin: 0px">
			<form action="index.php?section=manage-fields&amp;action=update" method="post">
			<table class="manage-fields" style="width: 100%" cellspacing="0" cellpadding="1" border="0">
			<colgroup>
				<col style="width: 8%" />
				<col style="width: 35%" />
				<col style="width: 37%" />
				<col style="width: 10%" />
				<col style="width: 10%" />
			</colgroup>
			<tfoot>
				<tr>
					<td style="text-align: right; padding-top: 10px" colspan="5">
						<input type="submit" class="button" value="Update" />
					</td>
				</tr>
			</tfoot>			
			<tbody>
				<tr>
					<td colspan="3">
						<span class="small-grey">&lt;form action=&quot;listmessenger.php&quot; method=&quot;post&quot;&gt;</span>
					</td>
					<td class="small-grey" style="font-weight: bold">Required</td>
					<td class="small-grey" style="font-weight: bold">Order</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="4">
						<span class="small-grey">&lt;input type=&quot;hidden&quot; name=&quot;group_ids[]&quot; value=&quot;ENTER_GROUP_ID_HERE&quot;&gt;</span>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><span class="form-row-req">E-Mail Address</span></td>
					<td><input type="text" name="email_address" value="" maxlength="128" /></td>
					<td><input type="checkbox" id="email_address_required" name="email_address_required" value="yes" checked="checked" onclick="this.checked = true" disabled="disabled" /></td>
					<td>&nbsp;</td>
				</tr>
				<?php
                $firstname_required = check_required('firstname');
        ?>
				<tr>
					<td>&nbsp;</td>
					<td><span class="form-row-<?php echo (!$firstname_required) ? 'n' : ''; ?>req">First Name</span></td>
					<td><input type="text" name="firstname" value="" maxlength="32" /></td>
					<td><input type="checkbox" id="required_firstname" name="required[static][<?php echo ENDUSER_REQUIRE_FIRSTNAME; ?>]" value="yes"<?php echo ($firstname_required) ? ' checked="checked"' : ''; ?> /></td>
					<td>&nbsp;</td>
				</tr>
				<?php
        $lastname_required = check_required('lastname');
        ?>
				<tr>
					<td>&nbsp;</td>
					<td><span class="form-row-<?php echo (!$lastname_required) ? 'n' : ''; ?>req">Last Name</span></td>
					<td><input type="text" name="lastname" value="" maxlength="32" /></td>
					<td><input type="checkbox" id="required_lastname" name="required[static][<?php echo ENDUSER_REQUIRE_LASTNAME; ?>]" value="yes"<?php echo ($lastname_required) ? ' checked="checked"' : ''; ?> /></td>
					<td>&nbsp;</td>
				</tr>
				<?php
        if ($results) {
            foreach ($results as $result) {
                echo "<tr>\n";
                echo '	<td><a href="./index.php?section=manage-fields&action=edit&id='.$result['cfields_id'].'"><img src="./images/icon-edit-fields.gif" width="16" height="16" border="0" alt="Edit" title="Edit '.$result['field_lname'].'" /></a>&nbsp;<a href="./index.php?section=manage-fields&action=delete&id='.$result['cfields_id'].'"><img src="./images/icon-del-fields.gif" width="16" height="16" border="0" alt="Delete" title="Delete '.$result['field_lname']."\" /></a></td>\n";
                echo '	<td><span class="'.(($result['field_req'] == 1) ? 'form-row-req' : 'form-row-nreq').'">'.(($result['field_lname']) ? checkslashes($result['field_lname'], 1) : '&nbsp;')."</span></td>\n";
                echo "	<td>\n";
                switch ($result['field_type']) {
                    case 'textbox':
                        echo '<input type="text" id="'.html_encode($result['field_sname']).'" name="'.html_encode($result['field_sname']).'" value=""'.(((int) $result['field_length']) ? ' maxlength="'.$result['field_length'].'"' : '')." />\n";
                        break;
                    case 'textarea':
                        echo '<textarea id="'.html_encode($result['field_sname']).'" name="'.html_encode($result['field_sname'])."\" rows=\"4\" cols=\"30\"></textarea>\n";
                        break;
                    case 'select':
                        if ($result['field_options'] != '') {
                            $options = explode("\n", $result['field_options']);
                            echo '<select id="'.html_encode($result['field_sname']).'" name="'.html_encode($result['field_sname'])."\">\n";
                            foreach ($options as $option) {
                                $pieces = explode('=', $option);
                                echo '<option value="'.html_encode($pieces[0]).'">'.html_encode($pieces[1])."</option>\n";
                            }
                            echo "</select>\n";
                        }
                        break;
                    case 'hidden':
                        echo "<span class=\"small-grey\">\n";
                        echo "	<strong>-- HIDDEN FIELD --</strong><br />\n";
                        echo '	&lt;input type=&quot;hidden&quot; name=&quot;'.html_encode($result['field_sname']).'&quot; value=&quot;'.html_encode($result['field_options'])."&quot;&gt;\n";
                        echo "</span>\n";
                        break;
                    case 'checkbox':
                        if ($result['field_options'] != '') {
                            $options = explode("\n", $result['field_options']);
                            foreach ($options as $key => $option) {
                                $pieces = explode('=', $option);
                                echo '<input type="checkbox" id="'.html_encode($result['field_sname']).'_'.$key.'" name="'.html_encode($result['field_sname']).'" value="'.html_encode($pieces[0]).'"> <label for="'.html_encode($result['field_sname']).'_'.$key.'">'.html_encode($pieces[1])."</label><br />\n";
                            }
                        }
                        break;
                    case 'radio':
                        if ($result['field_options'] != '') {
                            $options = explode("\n", $result['field_options']);
                            foreach ($options as $key => $option) {
                                $pieces = explode('=', $option);
                                echo '<input type="radio" id="'.html_encode($result['field_sname']).'_'.$key.'" name="'.html_encode($result['field_sname']).'" value="'.html_encode($pieces[0]).'"> <label for="'.html_encode($result['field_sname']).'_'.$key.'">'.html_encode($pieces[1])."</label><br />\n";
                            }
                        }
                        break;
                    case 'linebreak':
                        echo "<span class=\"small-grey\">-- LINE BREAK --</span>\n";
                        break;
                    default:
                        echo '&nbsp;';
                        break;
                }
                echo "	</td>\n";
                echo '	<td>'.(($result['field_type'] != 'linebreak') ? '<input type="checkbox" id="required_cfield_'.$result['cfields_id'].'" name="required[cfield]['.$result['cfields_id'].']" value="yes"'.(($result['field_req'] == 1) ? ' checked="checked"' : '').' />' : '&nbsp;')."</td>\n";
                echo "	<td>\n";
                echo '		<select name="order['.$result['cfields_id']."]\">\n";
                for ($i = 1; $i <= (($max) ? $max : (count($results) + 1)); ++$i) {
                    echo '	<option value="'.$i.'"'.(($i == $result['field_order']) ? ' selected="selected"' : '').'>'.$i."</option>\n";
                }
                echo "		</select>\n";
                echo "	</td>\n";
                echo "</tr>\n";
            }
        }
        ?>
				<tr>
					<td>&nbsp;</td>
					<td><span class="form-row-req">Subscriber Action</span></td>
					<td>
						<select name="action">
							<option value="subscribe">Subscribe</option>
							<option value="unsubscribe">Unsubscribe</option>
						</select>
					</td>
					<td><input type="checkbox" id="action_required" name="action_required" checked="checked" onclick="this.checked = true" disabled="disabled" /></td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td colspan="5">
						<span class="small-grey">&lt;/form&gt;</span>
					</td>
				</tr>
			</tbody>
			</table>
			</form>
		</div>
		<?php
    break;
}
