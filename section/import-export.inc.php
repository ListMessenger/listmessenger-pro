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

ini_set('auto_detect_line_endings', 1);
ini_set('magic_quotes_runtime', 0);
ini_set('memory_limit', '128M');

set_time_limit(0);

if (!empty($_GET['action'])) {
    $ACTION = clean_input($_GET['action'], 'alphanumeric');
} elseif (!empty($_POST['action'])) {
    $ACTION = clean_input($_POST['action'], 'alphanumeric');
} else {
    $ACTION = 'default';
}

if (!empty($_GET['step']) && (int) trim($_GET['step'])) {
    $STEP = (int) trim($_GET['step']);
} elseif (!empty($_POST['step']) && (int) trim($_POST['step'])) {
    $STEP = (int) trim($_POST['step']);
} else {
    $STEP = 1;
}

switch ($ACTION) {
    case 'export':
        /*
         * ERROR CHECKING
         */
        switch ($STEP) {
            case '2':
                $HASH = '';
                $EXPORT_FILENAME = '';

                if (!is_dir($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/')) {
                    ++$ERROR;
                    $ERRORSTR[] = 'Your private <strong>tmp</strong> directory does not appear to exist or PHP is not able to read the directory. Please go into the <a href="index.php?section=preferences&type=program">ListMessenger Program Preferences</a> and update your private folder directory path and ensure that the &quot;<strong>tmp</strong>&quot; folder exists in that directory.';
                } else {
                    if (!is_writable($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/')) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Your private <strong>tmp</strong> directory is currently not writable by PHP, please chmod it to 777 so you are able to upload and create new backup files in this directory.';
                    }
                }

                if (empty($_POST['export']['standard']) || !is_array($_POST['export']['standard'])) {
                    ++$ERROR;
                    $ERRORSTR[] = 'You must select at least one standard ListMessenger field to export to a CSV file.';
                    $STEP = 1;
                }

                if (empty($_POST['group_ids']) || !is_array($_POST['group_ids'])) {
                    ++$ERROR;
                    $ERRORSTR[] = 'You must select at least one group to export subscribers from using the group selection box on this page.';
                    $STEP = 1;
                } else {
                    $group_ids = [];

                    foreach ($_POST['group_ids'] as $group_id) {
                        if ($group_id = (int) trim($group_id)) {
                            $group_ids[] = $group_id;
                        }
                    }

                    $standard_fields = [];
                    foreach ($_POST['export']['standard'] as $field) {
                        if ($field = clean_input($field, 'section')) {
                            $standard_fields[] = $field;
                        }
                    }

                    $custom_fields = [];
                    foreach ($_POST['export']['custom'] as $field) {
                        if ($field = clean_input($field, 'section')) {
                            $custom_fields[] = $field;
                        }
                    }

                    if (count($group_ids)) {
                        $query = '
									SELECT `'.implode('`, `', $standard_fields).'`'.((!empty($custom_fields)) ? ", '' AS `".implode("`, '' AS `", $custom_fields).'`' : '').'
									FROM `'.TABLES_PREFIX.'users`
									LEFT JOIN `'.TABLES_PREFIX.'groups`
									ON `'.TABLES_PREFIX.'groups`.`groups_id` = `'.TABLES_PREFIX.'users`.`group_id`
									WHERE `'.TABLES_PREFIX.'users`.`group_id` IN ('.implode(', ', $group_ids).')';
                        $results = $db->GetAll($query);
                        if ($results) {
                            $HASH = md5(uniqid(rand(), 1)).'-'.time();
                            $EXPORT_FILENAME = 'lmexport-'.$HASH;
                            if ($handle = fopen($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$EXPORT_FILENAME, 'w')) {
                                fwrite($handle, csv_record(array_keys($results[0]), $_POST['csv']['fields_enclosed'], $_POST['csv']['fields_delimited'])."\n");

                                foreach ($results as $result) {
                                    if (!empty($custom_fields)) {
                                        $squery = '
													SELECT `'.TABLES_PREFIX.'cfields`.`field_sname`, `'.TABLES_PREFIX.'cdata`.`value`
													FROM `'.TABLES_PREFIX.'cfields`
													LEFT JOIN `'.TABLES_PREFIX.'cdata`
													ON `'.TABLES_PREFIX.'cdata`.`cfield_id` = `'.TABLES_PREFIX.'cfields`.`cfields_id`
													AND `'.TABLES_PREFIX."cdata`.`user_id` = '".(int) $result['users_id']."'
													WHERE `".TABLES_PREFIX."cfields`.`field_sname` IN ('".implode("', '", $custom_fields)."')";
                                        $sresults = $db->GetAll($squery);
                                        if ($sresults) {
                                            foreach ($sresults as $sresult) {
                                                $result[$sresult['field_sname']] = $sresult['value'];
                                            }
                                        }
                                    }

                                    fwrite($handle, csv_record($result, $_POST['csv']['fields_enclosed'], $_POST['csv']['fields_delimited'])."\n");
                                }
                                fclose($handle);
                            } else {
                                ++$ERROR;
                                $ERRORSTR[] = 'Unable to create a new temporary file in your private tmp directory. Please make sure that PHP has permission to read and write to your private tmp directory.';
                                $STEP = 1;
                            }
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'There were no rows to export in the groups that you selected, please select a group or groups that contain at least one subscriber.';
                            $STEP = 1;
                        }
                    } else {
                        ++$ERROR;
                        $ERRORSTR[] = 'You have not selected any valid groups to export subscribers from, please select at least one valid group.';
                        $STEP = 1;
                    }
                }
                break;
            default:
                // No error checking for step 1.
                break;
        }

        // PAGE DISPLAY
        switch ($STEP) {
            case '2':
                $HEAD[] = '<meta http-equiv="refresh" content="0; url='.$_SESSION['config'][PREF_PROGURL_ID].'export.php?hash='.$HASH."\" />\n";
                $ONLOAD[] = "setTimeout('window.location=\'index.php?section=import-export\'', 10000)";

                ++$SUCCESS;
                $SUCCESSSTR[] = 'Your Comma Separated Values (.csv) export file should begin downloading automatically; however, if it does not please <a href="'.$_SESSION['config'][PREF_PROGURL_ID].'export.php?hash='.$HASH.'" target="_blank">click here</a> to download it.';
                ?>
				<h1>Export Mailing List</h1>
				<?php
                if ($SUCCESS) {
                    echo display_success($SUCCESSSTR);
                }
                ?>
				<br /><br />
				Please be advised that this export file is only valid for one download after that, it is automatically removed. If you require it downloaded again, you will need to create a new export.
				<?php
            break;
            default:
                ?>
				<h1>Export Mailing List</h1>
				<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2"  style="vertical-align: middle" alt="" title="" /> <a href="index.php?section=import-export">Import &amp; Export</a>&nbsp;
				<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2"  style="vertical-align: middle" alt="" title="" /> Export Mailing List
				<br /><br />
				Please select which fields you would like to be included with this export using the form below:
				<br /><br />
				<?php echo ($ERROR) ? display_error($ERRORSTR) : ''; ?>
				<?php echo ($NOTICE) ? display_notice($NOTICESTR) : ''; ?>

				<h2>Standard Exportable Fields</h2>
				<form action="index.php?section=import-export&action=export&step=2" method="post" id="exportData">
				<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td style="width: 3%">1.</td>
					<td style="width: 3%"><input type="checkbox" name="export[standard][]" value="users_id" onkeypress="return handleEnter(this, event)" onclick="this.checked = true; alert('The subscriber id must be present in all exports, you cannot de-select this field.')" checked="checked" /></td>
					<td style="width: 24%; padding-left: 10px"><strong>subscriber_id</strong></td>
					<td style="width: 70%">ID of the subscriber in the database.</td>
				</tr>
				<tr>
					<td style="width: 3%">2.</td>
					<td style="width: 3%"><input type="checkbox" name="export[standard][]" value="group_id" onkeypress="return handleEnter(this, event)"<?php echo (!$_POST || in_array('group_id', $_POST['export']['standard'])) ? ' checked="checked"' : ''; ?> /></td>
					<td style="width: 24%; padding-left: 10px"><strong>group_id</strong></td>
					<td style="width: 70%">ID of the group in the database.</td>
				</tr>
				<tr>
					<td style="width: 3%">3.</td>
					<td style="width: 3%"><input type="checkbox" name="export[standard][]" value="group_name" onkeypress="return handleEnter(this, event)"<?php echo (!$_POST || in_array('group_name', $_POST['export']['standard'])) ? ' checked="checked"' : ''; ?> /></td>
					<td style="width: 24%; padding-left: 10px"><strong>group_name</strong></td>
					<td style="width: 70%">Name of the group.</td>
				</tr>
				<tr>
					<td style="width: 3%">4.</td>
					<td style="width: 3%"><input type="checkbox" name="export[standard][]" value="signup_date" onkeypress="return handleEnter(this, event)"<?php echo (!$_POST || in_array('signup_date', $_POST['export']['standard'])) ? ' checked="checked"' : ''; ?> /></td>
					<td style="width: 24%; padding-left: 10px"><strong>signup_date</strong></td>
					<td style="width: 70%">Date the subscriber joined your database.</td>
				</tr>
				<tr>
					<td style="width: 3%">5.</td>
					<td style="width: 3%"><input type="checkbox" name="export[standard][]" value="email_address" onkeypress="return handleEnter(this, event)"<?php echo (!$_POST || in_array('email_address', $_POST['export']['standard'])) ? ' checked="checked"' : ''; ?> /></td>
					<td style="width: 24%; padding-left: 10px"><strong>email_address</strong></td>
					<td style="width: 70%">E-mail address of the subscriber.</td>
				</tr>
				<tr>
					<td style="width: 3%">6.</td>
					<td style="width: 3%"><input type="checkbox" name="export[standard][]" value="firstname" onkeypress="return handleEnter(this, event)"<?php echo (!$_POST || in_array('firstname', $_POST['export']['standard'])) ? ' checked="checked"' : ''; ?> /></td>
					<td style="width: 24%; padding-left: 10px"><strong>firstname</strong></td>
					<td style="width: 70%">Firstname of the subscriber.</td>
				</tr>
				<tr>
					<td style="width: 3%">7.</td>
					<td style="width: 3%"><input type="checkbox" name="export[standard][]" value="lastname" onkeypress="return handleEnter(this, event)"<?php echo (!$_POST || in_array('lastname', $_POST['export']['standard'])) ? ' checked="checked"' : ''; ?> /></td>
					<td style="width: 24%; padding-left: 10px"><strong>lastname</strong></td>
					<td style="width: 70%">Lastname of the subscriber.</td>
				</tr>
				<?php
                $query = 'SELECT `cfields_id`, `field_sname`, `field_lname` FROM `'.TABLES_PREFIX.'cfields` ORDER BY `field_order` ASC';
                $results = $db->GetAll($query);
                if ($results) {
                    echo "<tr>\n";
                    echo "	<td colspan=\"4\">\n";
                    echo "		<h2>Custom Exportable Fields</h2>\n";
                    echo "	</td>\n";
                    echo "</tr>\n";

                    foreach ($results as $i => $result) {
                        echo "<tr>\n";
                        echo '	<td style="width: 3%">'.($i + 8).'.</td>';
                        echo '	<td style="width: 3%"><input type="checkbox" name="export[custom]['.$result['cfields_id'].']" value="'.html_encode($result['field_sname']).'" onkeypress="return handleEnter(this, event)"'.((!$_POST || in_array($result['field_sname'], $_POST['export']['custom'])) ? ' checked="checked"' : '').' /></td>';
                        echo '	<td style="width: 24%; padding-left: 10px"><strong>'.html_encode($result['field_sname'])."</strong></td>\n";
                        echo '	<td style="width: 70%">'.html_encode($result['field_lname'])."</td>\n";
                        echo "</tr>\n";
                    }
                }
                ?>
				<tr>
					<td colspan="4">
						<h2>CSV Export Options</h2>
					</td>
				</tr>
				<tr>
					<td></td>
					<td style="padding: 2px" class="form-row-req" colspan="2">Fields Enclosed By:</td>
					<td style="padding: 2px"><input type="text" class="text-box" name="csv[fields_enclosed]" value="<?php echo (!empty($_POST['type']) && $_POST['type'] == 'csv' && !empty($_POST['csv']['fields_enclosed'])) ? html_encode($_POST['csv']['fields_enclosed']) : '&quot;'; ?>" style="width: 15px" onkeypress="return handleEnter(this, event)" /></td>
				</tr>
				<tr>
					<td></td>
					<td style="padding: 2px" class="form-row-req" colspan="2">Fields Delimited By:</td>
					<td style="padding: 2px"><input type="text" class="text-box" name="csv[fields_delimited]" value="<?php echo (!empty($_POST['type']) && $_POST['type'] == 'csv' && !empty($_POST['csv']['fields_delimited'])) ? html_encode($_POST['csv']['fields_delimited']) : ','; ?>" style="width: 15px" onkeypress="return handleEnter(this, event)" /></td>
				</tr>

				<tr>
					<td colspan="4">
						<h2>Export Groups</h2>
					</td>
				</tr>
				<tr>
					<td></td>
					<td colspan="3">
						Please select the group or groups that you would like to export subscribers from:
						<select name="group_ids[]" style="margin-top: 5px; width: 97%" multiple="multiple" size="7" onkeypress="return handleEnter(this, event)">
						<?php echo groups_inselect(0, !empty($_POST['group_ids']) ? $_POST['group_ids'] : []); ?>
						</select>
						<br />
						<span class="small-grey"><strong>Notice:</strong> If a subscriber resides in multiple groups they will be included multiple times.</span>
					</td>
				</tr>
				</table>
				<br /><br />
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
				<tr>
					<td colspan="2" style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
						<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=control'" />
						<input type="submit" value="Export List" class="button" />
					</td>
				</tr>
				</table>

				<?php
            break;
        }
        break;
    case 'import':
        if ($_SESSION['config'][ENDUSER_BANEMAIL]) {
            $BANNED_ADDRESSES = explode(';', $_SESSION['config'][ENDUSER_BANEMAIL]);
        } else {
            $BANNED_ADDRESSES = [];
        }

        require_once 'classes/lm_mailer.class.php';

        $IMPORT_FILENAME = '';
        $IMPORT_TYPE = (!empty($_POST['type']) ? clean_input($_POST['type'], 'alphanumeric') : false);

        // ERROR CHECKING
        switch ($STEP) {
            case '2':
                $IMPORT_FIELDS = [];
                $IMPORT_SAMPLE = [];

                if (!is_dir($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/')) {
                    ++$ERROR;
                    $ERRORSTR[] = 'Your private <strong>tmp</strong> directory does not appear to exist or PHP is not able to read the directory. Please go into the <a href="index.php?section=preferences&type=program">ListMessenger Program Preferences</a> and update your private folder directory path and ensure that the &quot;<strong>tmp</strong>&quot; folder exists in that directory.';
                } else {
                    if (!is_writable($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/')) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Your private <strong>tmp</strong> directory is currently not writable by PHP, please chmod it to 777 so you are able to upload and create new backup files in this directory.';
                    }
                }

                if (empty($_POST['group_ids']) || !is_array($_POST['group_ids'])) {
                    ++$ERROR;
                    $ERRORSTR[] = 'You must select at least one group to import these subscribers into under &quot;Imported Data Destination&quot;.';
                }

                if (!$ERROR) {
                    switch ($IMPORT_TYPE) {
                        case 'csv' :
                            if (empty($_FILES['csvfile'])) {
                                ++$ERROR;
                                $ERRORSTR[] = 'You did not select a Comma Separated Values (.csv) file from your computer to upload and import.';
                            } else {
                                switch ($_FILES['csvfile']['error']) {
                                    case '0':
                                        // File was uploaded successfully.
                                        break;
                                    case '1':
                                        // File exceeds upload_max_file size in php.ini.
                                        $ERROR++;
                                        $ERRORSTR[] = 'The CSV file that you are trying to upload is a larger filesize than your server currently allows. Please either modify the &quot;upload_max_file&quot; in your php.ini file or add the appropriate directives to your web-server configuration file.';
                                        break;
                                    case '2':
                                        // File exceeds MAX_FILE_SIZE directive in form.
                                        $ERROR++;
                                        $ERRORSTR[] = 'The CSV file that you are trying to upload is a larger filesize than your server currently allows.';
                                        break;
                                    case '3':
                                        // File was only partially uploaded.
                                        $ERROR++;
                                        $ERRORSTR[] = 'The CSV file that was uploaded did not complete the upload process or was interrupted. Please try again.';
                                        break;
                                    case '4':
                                        // There was no file uploaded.
                                        $ERROR++;
                                        $ERRORSTR[] = 'You did not select a CSV file from your computer to upload and import.';
                                        break;
                                    default:
                                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tA CSV file was uploaded to import and PHP returned an unrecognized file upload error [".$_FILES['csvfile']['error']."].\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                        }
                                        break;
                                }

                                if (empty($_POST['csv']['fields_delimited'])) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'You must enter the character that your fields are delimited by. This is commonly a comma or semi-colon in a CSV file or use \\t if you are importing a tab separated values file.';
                                }

                                if (!$ERROR) {
                                    $IMPORT_FILENAME = valid_filename($_FILES['csvfile']['name']);

                                    if (!$IMPORT_FILENAME) {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'The filename of your CSV file cannot be handled in ListMessenger at this time, please rename your local file and re-upload it.';
                                    } else {
                                        if (!move_uploaded_file($_FILES['csvfile']['tmp_name'], $_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME)) {
                                            ++$ERROR;
                                            $ERRORSTR[] = 'ListMessenger was unable to move the CSV file from your servers temporary storage directory to your private tmp directory at &quot;'.$_SESSION['config'][PREF_PRIVATE_PATH].'tmp/&quot;.';
                                        } else {
                                            if (file_exists($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME)) {
                                                if (!$handle = fopen($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME, 'rb')) {
                                                    ++$ERROR;
                                                    $ERRORSTR[] = 'ListMessenger was unable to open your CSV file, please make sure that PHP has read permissions on your private tmp directory.';
                                                } else {
                                                    $IMPORT_FILESIZE = filesize($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME);

                                                    $csvdata = [];
                                                    $row_count = 0;
                                                    $col_count = 0;
                                                    while (($row = fgetcsv($handle, $IMPORT_FILESIZE, $_POST['csv']['fields_delimited'], $_POST['csv']['fields_enclosed'])) !== false) {
                                                        if ($row_count >= 6) {
                                                            break;
                                                        } else {
                                                            if ($col_count < count($row)) {
                                                                $col_count = count($row);
                                                            }

                                                            $csvdata['row'][] = $row;
                                                            ++$row_count;
                                                        }
                                                    }
                                                    $csvdata['max_row'] = count($csvdata['row']);
                                                    $csvdata['max_col'] = $col_count;
                                                    fclose($handle);

                                                    if (is_array($csvdata) && (!empty($csvdata['max_row'])) && (!empty($csvdata['max_col']))) {
                                                        $start_row = (($_POST['options']['firstrowfields'] == '1') ? 1 : 0);

                                                        foreach ($csvdata['row'][0] as $column => $value) {
                                                            $IMPORT_FIELDS[$column] = (($_POST['options']['firstrowfields'] == '1') ? $value : '');
                                                        }

                                                        $sample_rows = ($start_row + 4);
                                                        if ($sample_rows > ($csvdata['max_row'] - 1)) {
                                                            $sample_rows = ($csvdata['max_row'] - 1);
                                                        }

                                                        for ($row = $start_row; $row <= $sample_rows; ++$row) {
                                                            $IMPORT_SAMPLE[$row] = $csvdata['row'][$row];
                                                        }
                                                    } else {
                                                        ++$ERROR;
                                                        $ERRORSTR[] = 'There were no rows found in the CSV file you are attempting to import. Please choose a new file and try again.';
                                                    }
                                                }
                                            } else {
                                                ++$ERROR;
                                                $ERRORSTR[] = 'The CSV file that you are trying to import ['.$IMPORT_FILENAME.'] no longer exists in your private tmp directory.';
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                        case 'text' :
                            if (empty($_POST['text']['data']) || trim($_POST['text']['data']) == '') {
                                ++$ERROR;
                                $ERRORSTR[] = 'You did not provide any CSV data in the provided textarea. Please enter some CSV text or use another method to import your list.';
                            } else {
                                if (empty($_POST['csv']['fields_delimited'])) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'You must enter the character that your fields are delimited by. This is commonly a comma or semi-colon in a CSV file or use \\t if you are importing a tab separated values file.';
                                } else {
                                    $IMPORT_FILENAME = valid_filename('csvtext_'.time().'.csv');

                                    if ($handle = fopen($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME, 'w')) {
                                        if (fwrite($handle, trim($_POST['text']['data'])) === false) {
                                            ++$ERROR;
                                            $ERRORSTR[] = 'Unable to write the CSV data to the temporary CSV file ['.$IMPORT_FILENAME.']. Please make sure that PHP has write permissions on files in that directory.';
                                        }
                                        fclose($handle);
                                    } else {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'Unable to create the temporary CSV file ['.$IMPORT_FILENAME.'] to insert the CSV data into. Please make sure that PHP has read and write permissions on your private tmp directory.';
                                    }
                                }
                                if (!$ERROR) {
                                    if (file_exists($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME)) {
                                        if (!$handle = fopen($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME, 'rb')) {
                                            ++$ERROR;
                                            $ERRORSTR[] = 'ListMessenger was unable to open your temporary CSV file, please make sure that PHP has read permissions on your private tmp directory.';
                                        } else {
                                            $IMPORT_FILESIZE = filesize($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME);

                                            $csvdata = [];
                                            $row_count = 0;
                                            $col_count = 0;
                                            while (($row = fgetcsv($handle, $IMPORT_FILESIZE, $_POST['text']['fields_delimited'], $_POST['text']['fields_enclosed'])) !== false) {
                                                if ($row_count >= 6) {
                                                    break;
                                                } else {
                                                    if ($col_count < count($row)) {
                                                        $col_count = count($row);
                                                    }

                                                    $csvdata['row'][] = $row;
                                                    ++$row_count;
                                                }
                                            }
                                            $csvdata['max_row'] = count($csvdata['row']);
                                            $csvdata['max_col'] = $col_count;
                                            fclose($handle);

                                            if (is_array($csvdata) && (!empty($csvdata['max_row'])) && $csvdata['max_col']) {
                                                $start_row = ((!empty($_POST['options']['firstrowfields']) && $_POST['options']['firstrowfields'] == '1') ? 1 : 0);

                                                foreach ($csvdata['row'][0] as $column => $value) {
                                                    $IMPORT_FIELDS[$column] = ((!empty($_POST['options']['firstrowfields']) && $_POST['options']['firstrowfields'] == '1') ? $value : '');
                                                }

                                                $sample_rows = ($start_row + 4);
                                                if ($sample_rows > ($csvdata['max_row'] - 1)) {
                                                    $sample_rows = ($csvdata['max_row'] - 1);
                                                }

                                                for ($row = $start_row; $row <= $sample_rows; ++$row) {
                                                    $IMPORT_SAMPLE[$row] = $csvdata['row'][$row];
                                                }
                                            } else {
                                                ++$ERROR;
                                                $ERRORSTR[] = 'There were no rows found in the CSV file you are attempting to import. Please enter some CSV data into the textarea and try again.';
                                            }
                                        }
                                    } else {
                                        ++$ERROR;
                                        $ERRORSTR[] = 'The CSV file that you are trying to import ['.$IMPORT_FILENAME.'] no longer exists in your private tmp directory.';
                                    }
                                }
                            }
                            break;
                        default:
                            $ERROR++;
                            $ERRORSTR[] = 'The selected data source is not a valid &quot;Imported Data Source&quot; type. Please choose from either CSV File or Textarea.';
                            break;
                    }
                }

                if (empty($IMPORT_FILENAME)) {
                    ++$ERROR;
                    $ERRORSTR[] = 'There is no file in the filesystem to import, please try again.';
                }

                if ($ERROR) {
                    $STEP = 1;
                }
                break;
            case '3':
                if ((empty($_POST['import_filename'])) || (trim($_POST['import_filename']) == '')) {
                    ++$ERROR;
                    $ERRORSTR[] = 'There is no filename provided to this step to import. Please go back to the first step and go through the process again.';
                } else {
                    $IMPORT_FILENAME = valid_filename(trim($_POST['import_filename']));
                    $IMPORT_FILENAME = str_replace(['..', '/', '\\'], '', $IMPORT_FILENAME);

                    if (!file_exists($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME)) {
                        ++$ERROR;
                        $ERRORSTR[] = 'The filename that was provided to step three to import does not exist in your private tmp directory. Please go back to the first step and go through the process again.';
                    }
                }

                if (empty($_POST['fields']) || !is_array($_POST['fields'])) {
                    ++$ERROR;
                    $ERRORSTR[] = 'You did not select any fields to import from your CSV file, please try again but select the proper fields.';
                } else {
                    if (empty($_POST['fields']['email_address']) && $_POST['fields']['email_address'] !== '0') {
                        ++$ERROR;
                        $ERRORSTR[] = 'You did not select a column to import that contains the subscribers e-mail address. Please select this column and try again.';
                    }
                }

                if (empty($_POST['group_ids']) || !is_array($_POST['group_ids'])) {
                    ++$ERROR;
                    $ERRORSTR[] = 'Unable to locate any groups to import these subscribers into, please try again.';
                }

                if (!$ERROR) {
                    /*
                     * If confirmation is requested, setup PHPMailer.
                     */

                    if ((!empty($_POST['options']['confirmation'])) && ($_POST['options']['confirmation'] == '1')) {
                        if (file_exists($_SESSION['config'][PREF_PUBLIC_PATH].'languages/'.$_SESSION['config'][ENDUSER_LANG_ID].'.lang.php')) {
                            require_once $_SESSION['config'][PREF_PUBLIC_PATH].'languages/'.$_SESSION['config'][ENDUSER_LANG_ID].'.lang.php';
                        } elseif (file_exists($_SESSION['config'][PREF_PUBLIC_PATH].'languages/english.lang.php')) {
                            require_once $_SESSION['config'][PREF_PUBLIC_PATH].'languages/english.lang.php';

                            ++$NOTICE;
                            $NOTICESTR[] = 'Your selected language file does not exist in the public languages directory, so the English default file is being used.';
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'Your public language directory does not contain your selected language file, or the English language file. Please ensure that you have the proper language files in your public languages directory.';
                        }

                        try {
                            $mail = new LM_Mailer($_SESSION['config']);
                            $mail->AddReplyTo($_SESSION['config'][PREF_RPYEMAL_ID], $_SESSION['config'][PREF_FRMNAME_ID]);
                            $mail->Subject = $LANGUAGE_PACK['subscribe_confirmation_subject'];
                        } catch (Exception $e) {
                            ++$ERROR;
                            $ERRORSTR[] = $e->getMessage();
                        }
                    }

                    switch ($IMPORT_TYPE) {
                        case 'csv' :
                            if (file_exists($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME)) {
                                if (!$handle = fopen($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME, 'rb')) {
                                    ++$ERROR;
                                    $ERRORSTR[] = 'ListMessenger was unable to open your Comma Separated Values (.csv) file, please make sure that PHP has read permissions on your private tmp directory.';
                                } else {
                                    $csvdata = [];
                                    $row_count = 0;
                                    while (($row = fgetcsv($handle, filesize($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME), checkslashes($_POST['csv']['fields_delimited'], 1), checkslashes($_POST['csv']['fields_enclosed'], 1))) !== false) {
                                        $csvdata['row'][] = $row;
                                        if (empty($csvdata['max_col'])) {
                                            $csvdata['max_col'] = count($row);
                                        }
                                    }
                                    $csvdata['max_row'] = count($csvdata['row']);

                                    fclose($handle);
                                }
                            }

                            if ($ERROR) {
                                $STEP = 1;
                            } else {
                                if (is_array($csvdata) && (!empty($csvdata['max_row'])) && (!empty($csvdata['max_col']))) {
                                    $start_row = ((!empty($_POST['options']['firstrowfields']) && $_POST['options']['firstrowfields'] == '1') ? 1 : 0);
                                    for ($row = $start_row; $row <= ($csvdata['max_row'] - 1); ++$row) {
                                        $skip_row = false;

                                        $email_address = '';
                                        $firstname = '';
                                        $lastname = '';
                                        $groups = [];
                                        $cdata = [];

                                        foreach ($_POST['fields'] as $field_name => $column) {
                                            if (trim($column) != '') {
                                                $result = trim($csvdata['row'][$row][$column]);

                                                if (in_array($field_name, $_POST['required_fields']) && ($result == '')) {
                                                    ++$ERROR;
                                                    $ERRORSTR[] = 'Row '.($row + (1 - $start_row)).': Required field ['.$field_name.'] is missing from this row.';
                                                    $skip_row = true;
                                                }

                                                switch ($field_name) {
                                                    case 'email_address' :
                                                        $email_address = $result;
                                                        break;
                                                    case 'firstname' :
                                                        $firstname = $result;
                                                        break;
                                                    case 'lastname' :
                                                        $lastname = $result;
                                                        break;
                                                    default:
                                                        $cdata[$field_name] = $result;
                                                        break;
                                                }
                                            }
                                        }

                                        if ($email_address != '') {
                                            if (!valid_address($email_address)) {
                                                ++$ERROR;
                                                $ERRORSTR[] = 'Row '.($row + (1 - $start_row)).': E-mail address ['.$email_address.'] does not appear to be valid.';
                                                $skip_row = true;
                                            } elseif (banned_address($email_address, $BANNED_ADDRESSES)) {
                                                ++$NOTICE;
                                                $NOTICESTR[] = 'Row '.($row + (1 - $start_row)).': E-mail address ['.$email_address.'] is banned from subscribing and was skipped.';
                                                $skip_row = true;
                                            } else {
                                                if ((!empty($_POST['group_ids'])) && is_array($_POST['group_ids'])) {
                                                    $groups = $_POST['group_ids'];

                                                    /*
                                                     * Check if this e-mail address already exists in this group before adding it.
                                                     */
                                                    if ((!empty($_POST['options']['dupecheck'])) && ($_POST['options']['dupecheck'] == '1')) {
                                                        foreach ($groups as $key => $group_id) {
                                                            if ($group_id = (int) $group_id) {
                                                                $query = 'SELECT `users_id` FROM `'.TABLES_PREFIX."users` WHERE `group_id` = '".checkslashes($group_id)."' AND `email_address` = ".$db->qstr($email_address);
                                                                $result = $db->GetRow($query);
                                                                if ($result) {
                                                                    unset($groups[$key]);

                                                                    ++$NOTICE;
                                                                    $NOTICESTR[] = 'Row '.($row + (1 - $start_row)).': E-mail address ['.$email_address.'] already exists in '.groups_information([$group_id], true).'.';
                                                                }
                                                            }
                                                        }
                                                    }

                                                    /*
                                                     * Check if this subscriber has previously unsubscribed from any of the specified
                                                     * groups before adding it.
                                                     */
                                                    if ((!empty($_POST['options']['noresubscribe'])) && ($_POST['options']['noresubscribe'] == '1')) {
                                                        foreach ($groups as $key => $group_id) {
                                                            if ($group_id = (int) $group_id) {
                                                                $query = 'SELECT `date`, `group_ids` FROM `'.TABLES_PREFIX."confirmation` WHERE `action` = 'usr-unsubscribe' AND `email_address` = ".$db->qstr($email_address)." AND `confirmed` = '1'";
                                                                $results = $db->GetAll($query);
                                                                if ($results) {
                                                                    foreach ($results as $result) {
                                                                        if (trim($result['group_ids']) != '') {
                                                                            $tmp_group_ids = unserialize(trim($result['group_ids']));
                                                                            if (is_array($tmp_group_ids) && in_array($group_id, $tmp_group_ids)) {
                                                                                unset($groups[$key]);

                                                                                ++$NOTICE;
                                                                                $NOTICESTR[] = 'Row '.($row + (1 - $start_row)).': E-mail address ['.$email_address.'] previously unsubscribed from '.groups_information([$group_id], true).' on '.display_date($_SESSION['config'][PREF_DATEFORMAT], $result['date']).'.';
                                                                                break;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        if (!count($groups)) {
                                            $skip_row = true;
                                        }

                                        if (!$skip_row) {
                                            if ((!empty($_POST['options']['confirmation'])) && ($_POST['options']['confirmation'] == '1')) {
                                                $result = users_queue($email_address, $firstname, $lastname, $groups, $cdata, 'adm-import');
                                                if ($result) {
                                                    $mail->Body = str_replace(['[name]', '[url]', '[abuse_address]', '[from]'], [$firstname, $_SESSION['config'][PREF_PUBLIC_URL].'confirm.php?id='.$result['confirm_id'].'&code='.$result['hash'], $_SESSION['config'][PREF_ABUEMAL_ID], $_SESSION['config'][PREF_FRMNAME_ID]], $LANGUAGE_PACK['subscribe_confirmation_message']);

                                                    if ($firstname != '') {
                                                        $senders_name = $firstname.(($lastname != '') ? ' '.$lastname : '');
                                                    } else {
                                                        $senders_name = $email_address;
                                                    }

                                                    $mail->ClearAllRecipients();
                                                    $mail->AddAddress($email_address, $senders_name);

                                                    if ($mail->Send()) {
                                                        ++$SUCCESS;
                                                        $SUCCESSSTR[] = 'Row '.($row + (1 - $start_row)).': An opt-in request e-mail address was sent to ['.$email_address.'].';
                                                    } else {
                                                        ++$ERROR;
                                                        $ERRORSTR[] = 'Row '.($row + (1 - $start_row)).': An opt-in request could not be sent. Please check your error log for more details.';

                                                        $query = 'DELETE FROM `'.TABLES_PREFIX."confirmation` WHERE `confirm_id`='".$result['confirm_id']."';";
                                                        if (!$db->Execute($query)) {
                                                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tRow ".($row + (1 - $start_row)).': Unable to delete the failed confirmation queue request from the confirmation table. Database server said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                            }
                                                        }
                                                        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tRow ".($row + (1 - $start_row)).': Unable to send opt-in request to '.$email_address.'. PHPMailer said: '.$mail->ErrorInfo."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                        }
                                                    }
                                                } else {
                                                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tRow ".($row + (1 - $start_row)).': Unable to add a new subscriber ['.$email_address."] to the confirmation queue. The subscriber is already present in all groups.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                    }
                                                }
                                            } else {
                                                $result = users_add($email_address, $firstname, $lastname, $groups, $cdata, $_SESSION['config']);
                                                if ($result) {
                                                    $query = 'INSERT INTO `'.TABLES_PREFIX."confirmation` VALUES (NULL, '".time()."', 'adm-import', '".addslashes($_SERVER['REMOTE_ADDR'])."', '".addslashes($_SERVER['HTTP_REFERER'])."', '".addslashes($_SERVER['HTTP_USER_AGENT'])."', '".$email_address."', '".addslashes($firstname)."', '".addslashes($lastname)."', '".addslashes(serialize($groups))."', '".addslashes(serialize($cdata))."', '', '0');";
                                                    $db->Execute($query);

                                                    if ($result['failed'] > 0) {
                                                        ++$ERROR;
                                                        $ERRORSTR[] = 'Row '.($row + (1 - $start_row)).': Inserting ['.$email_address.'] failed for '.$result['failed'].' group'.((count($groups) != 1) ? 's' : '').'.';
                                                    }
                                                    if ($result['semi'] > 0) {
                                                        ++$NOTICE;
                                                        $NOTICESTR[] = 'Row '.($row + (1 - $start_row)).': Inserting custom data for ['.$email_address.'] failed for '.$result['semi'].' field'.(($result['semi'] != 1) ? 's' : '').'.';
                                                    }
                                                    if ($result['success'] > 0) {
                                                        ++$SUCCESS;
                                                        $SUCCESSSTR[] = 'Row '.($row + (1 - $start_row)).': Successfully inserted ['.$email_address.'] into '.$result['success'].' group'.((count($groups) != 1) ? 's' : '').'.';
                                                    }
                                                } else {
                                                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tRow ".($row + (1 - $start_row)).': Unable to add a new subscriber ['.$email_address."] to the database. The subscriber is already present in all groups.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                                    }
                                                }
                                            }
                                        } else {
                                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tRow ".($row + (1 - $start_row)).": Row was skipped during import.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                            }
                                        }
                                    }
                                } else {
                                    ++$ERROR;
                                    $ERRORSTR[] = "There has been a problem importing your Comma Separated Values (CSV) document. Either the file contains no rows or column's or there is no data found. Please select a different file.";
                                }
                            }
                            break;
                        case 'text' :
                            break;
                        default:
                            $ERROR++;
                            $ERRORSTR[] = 'The selected data source is not a valid &quot;Imported Data Source&quot; type. Please choose from either CSV File or Textarea.';
                            break;
                    }
                }
                break;
            default:
                // No error checking for Step 1.
                break;
        }

        // PAGE DISPLAY
        switch ($STEP) {
            case '2':
                $field_list = [];
                $field_list[] = ['name' => 'email_address', 'required' => 1];
                $field_list[] = ['name' => 'firstname', 'required' => 0];
                $field_list[] = ['name' => 'lastname', 'required' => 0];

                $query = 'SELECT `field_sname`, `field_req` FROM `'.TABLES_PREFIX.'cfields` ORDER BY `field_order` ASC';
                $results = $db->GetAll($query);
                if ($results) {
                    foreach ($results as $result) {
                        $field_list[] = ['name' => $result['field_sname'], 'required' => $result['field_req']];
                    }
                }
                ?>
				<script language="JavaScript" type="text/javascript">
				var sample_number = 1;

				function showSample(field_name) {
					if((!sample_number) || (sample_number == 'undefined') || (sample_number == '') || (sample_number < 1) || (sample_number > <?php echo count($IMPORT_SAMPLE); ?>)) {
						sample_number = 1;
					}

					var sample_data = new Array();
					<?php
                    if (count($IMPORT_SAMPLE) > 0) {
                        foreach ($IMPORT_SAMPLE as $example => $sample_data) {
                            if (is_array($sample_data) && count($sample_data)) {
                                $example = (($_POST['options']['firstrowfields'] == '1') ? $example : ($example + 1));
                                echo 'sample_data['.$example."] = new Array();\n";
                                foreach ($sample_data as $column => $data) {
                                    echo 'sample_data['.$example.']['.$column."] = '".(($data != '') ? addslashes(limit_chars($data, 35)) : '-- empty --')."';\n";
                                }
                                echo "\n";
                            }
                        }
                    }
                ?>
					if (field_name != '') {
						if (($('#' + field_name).val() != "") && (typeof(sample_data[sample_number]) != 'undefined')) {
							$('#sample_'+field_name).html(sample_data[sample_number][$('#' + field_name).val()]);
						} else {
							$('#sample_'+field_name).html('N/A');
						}
					}
				}

				function navigateSamples(number) {
					sample_number = number;
					<?php
                foreach ($field_list as $field) {
                    echo "showSample('".$field['name']."');\n";
                }
                ?>
				}
				</script>
				<h1>Import Mailing List</h1>
				<?php
                if ($ERROR) {
                    echo display_error($ERRORSTR);
                }

                if ($NOTICE) {
                    echo display_notice($NOTICESTR);
                }
                ?>
				Please choose the matching column information using the table below. The column on the left hand side is a list of all fields that currently reside within ListMessenger, including any custom fields you have created. The list of columns in the select boxes are a list fields that ListMessenger is able to import based on the data that you are trying to import. You need to match the columns that your importing to the appropriate column on the left.
				<br /><br />
				<form action="index.php?section=import-export&action=import&step=3" method="post">
				<input type="hidden" name="import_filename" value="<?php echo html_encode($IMPORT_FILENAME); ?>" />
				<?php
                switch ($IMPORT_TYPE) {
                    case 'csv':
                        echo "<input type=\"hidden\" name=\"type\" value=\"csv\" />\n";
                        echo '<input type="hidden" name="csv[fields_enclosed]" value="'.html_encode(checkslashes($_POST['csv']['fields_enclosed'], 1))."\" />\n";
                        echo '<input type="hidden" name="csv[fields_delimited]" value="'.html_encode(checkslashes($_POST['csv']['fields_delimited'], 1))."\" />\n";
                        break;
                    case 'text':
                        echo "<input type=\"hidden\" name=\"type\" value=\"csv\" />\n";
                        echo '<input type="hidden" name="csv[fields_enclosed]" value="'.html_encode(checkslashes($_POST['text']['fields_enclosed'], 1))."\" />\n";
                        echo '<input type="hidden" name="csv[fields_delimited]" value="'.html_encode(checkslashes($_POST['text']['fields_delimited'], 1))."\" />\n";
                        break;
                }

                if (!empty($_POST['group_ids']) && is_array($_POST['group_ids'])) {
                    foreach ($_POST['group_ids'] as $group_id) {
                        echo '<input type="hidden" name="group_ids[]" value="'.$group_id."\" />\n";
                    }
                }

                if (is_array($field_list)) {
                    foreach ($field_list as $field) {
                        if ($field['required'] == '1') {
                            echo '<input type="hidden" name="required_fields[]" value="'.$field['name']."\" />\n";
                        }
                    }
                }
                ?>
				<input type="hidden" name="options[confirmation]" value="<?php echo !empty($_POST['options']['confirmation']) ? html_encode($_POST['options']['confirmation']) : ''; ?>" />
				<input type="hidden" name="options[firstrowfields]" value="<?php echo !empty($_POST['options']['firstrowfields']) ? html_encode($_POST['options']['firstrowfields']) : ''; ?>" />
				<input type="hidden" name="options[dupecheck]" value="<?php echo !empty($_POST['options']['dupecheck']) ? html_encode($_POST['options']['dupecheck']) : ''; ?>" />
				<input type="hidden" name="options[noresubscribe]" value="<?php echo !empty($_POST['options']['noresubscribe']) ? html_encode($_POST['options']['noresubscribe']) : ''; ?>" />
				<table style="width: 100%" cellspacing="1" cellpadding="3" border="0">
				<colgroup>
					<col style="width: 33%" />
					<col style="width: 1%" />
					<col style="width: 33%" />
					<col style="width: 33%" />
				</colgroup>
				<thead>
					<tr>
						<td><h2>ListMessenger Field Names</h2></td>
						<td>&nbsp;</td>
						<td><h2>Imported Column Names</h2></td>
						<td><h2>Sample Output</h2></td>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="4">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="4" style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
							<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=import-export&action=import'" />
							<input type="submit" value="Import List" class="button" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<?php
                    if (is_array($field_list)) {
                        foreach ($field_list as $field) {
                            echo "<tr>\n";
                            echo '	<td class="form-row-'.(($field['required'] != 1) ? 'n' : '').'req">'.$field['name']."</td>\n";
                            echo "	<td>=</td>\n";
                            echo "	<td >\n";
                            echo '		<select name="fields['.$field['name'].']" onkeypress="return handleEnter(this, event)" id="'.$field['name']."\" onchange=\"showSample('".$field['name']."')\">\n";
                            echo "		<option value=\"\">-- Do not import --</option>\n";
                            foreach ($IMPORT_FIELDS as $column_number => $column_name) {
                                echo '<option value="'.$column_number.'"'.(($field['name'] == variable_name($column_name)) ? ' selected="selected"' : '').'>Column '.($column_number + 1).' '.(($column_name != '') ? '['.$column_name.']' : '')."</option>\n";
                            }
                            echo "		</select>\n";
                            echo "	</td>\n";
                            echo '	<td class="small-grey"><div id="sample_'.$field['name']."\">&nbsp;</div></td>\n";
                            echo "</tr>\n";
                        }
                    }
                ?>
					<tr>
						<td colspan="4">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
						<td style="text-align: right">Show sample output using:</td>
						<td>
							<select onkeypress="return handleEnter(this, event)" onchange="navigateSamples(this.options[selectedIndex].value)">
							<?php
                        for ($i = 1; $i <= count($IMPORT_SAMPLE); ++$i) {
                            echo '<option value="'.$i.'">Row '.$i."</option>\n";
                        }
                ?>
							</select>
						</td>
					</tr>
				</tbody>
				</table>
				<?php
                $ONLOAD[] = "navigateSamples('1')";
                break;
            case '3':
                if (!unlink($_SESSION['config'][PREF_PRIVATE_PATH].'tmp/'.$IMPORT_FILENAME)) {
                    ++$NOTICE;
                    $NOTICESTR[] = 'ListMessenger was unable to remove the temporary file ['.$IMPORT_FILENAME.'] it used to import your subscribers. Please remove this file manually from your private tmp directory.';
                }
                ?>
				<h1>Import Mailing List</h1>
				<?php echo ($ERROR) ? display_error($ERRORSTR) : ''; ?>
				<?php echo ($NOTICE) ? display_notice($NOTICESTR) : ''; ?>
				<?php echo ($SUCCESS) ? display_success($SUCCESSSTR) : ''; ?>
				<br /><br />
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
				<tr>
					<td colspan="2" style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
						<input type="button" value="Finished" class="button" onclick="window.location='index.php?section=import-export&action=import'" />
					</td>
				</tr>
				</table>
				<?php
            break;
            default:
                $ONLOAD[] = "setImportType('".(((!empty($_POST['type'])) && is_array($_POST['type']) && in_array($_POST['type'], ['csv', 'text'])) ? $_POST['type'] : 'csv')."')";
                ?>
				<h1>Import Mailing List</h1>
				<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2"  style="vertical-align: middle" alt="" title="" /> <a href="index.php?section=import-export">Import &amp; Export</a>&nbsp;
				<img src="images/record-next-on.gif" width="9" height="9" hspace="2" vspace="2"  style="vertical-align: middle" alt="" title="" /> Import Mailing List
				<br /><br />
				<?php echo ($ERROR) ? display_error($ERRORSTR) : ''; ?>
				<?php echo ($NOTICE) ? display_notice($NOTICESTR) : ''; ?>
				<h2>Imported Data Source</h2>
				<form action="index.php?section=import-export&action=import&step=2" method="post" enctype="multipart/form-data">
				<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo return_bytes(ini_get('post_max_size')); ?>" />
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 97%" />
				</colgroup>
				<thead>
					<tr>
						<td><input type="radio" id="type_csv" name="type" value="csv" onclick="setImportType('csv')" onkeypress="return handleEnter(this, event)"<?php echo (empty($_POST['type']) || (!empty($_POST['type']) && $_POST['type'] == 'csv')) ? ' checked="checked"' : ''; ?> /></td>
						<td><label for="type_csv">I would like to upload and import a Comma Separated Values (CSV) file.</label></td>
					</tr>
					<tr>
						<td><input type="radio" id="type_text" name="type" value="text" onclick="setImportType('text')" onkeypress="return handleEnter(this, event)"<?php echo (!empty($_POST['type']) && $_POST['type'] == 'text') ? ' checked="checked"' : ''; ?> /></td>
						<td><label for="type_text">I would like to paste CSV data into a textarea to import.</label></td>
					</tr>
				</thead>
				<tbody id="csv" style="display: none">
					<tr>
						<td>&nbsp;</td>
						<td>
							<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
							<colgroup>
								<col style="width: 35%" />
								<col style="width: 65%" />
							</colgroup>
							<tbody>
								<tr>
									<td><?php echo create_tooltip('Select Your Local CSV File', '<strong>Select Your Local CSV File</strong><br />Please select the Comma Separated Values (.csv) file from your computer that you would like to import into ListMessenger.<br /><br />Please note that if your field delimiter is not a comma, then you need to specify the delimiter below (i.e. \\t for tab).', true); ?></td>
									<td><input type="file" name="csvfile" class="file" size="30" onkeypress="return handleEnter(this, event)" /></td>
								</tr>
								<tr>
									<td colspan="2" class="small-grey">
										<strong>Size Notice:</strong> Please be aware that your server's maximum upload file size is set to <?php echo readable_size(return_bytes(ini_get('upload_max_filesize'))); ?>, and we suggest making the file you wish to import fewer than 1,000 rows at a time to prevent server timeouts.
										<br /><br />
									</td>
								</tr>
								<tr>
									<td><?php echo create_tooltip('Fields Enclosed By', '<strong>Fields Enclosed By</strong><br />This is generally a double quote character; however, if you your fields are enclosed by a different character please specify it here.'); ?></td>
									<td><input type="text" class="text-box" name="csv[fields_enclosed]" value="<?php echo (!empty($_POST['type']) && $_POST['type'] == 'csv') ? html_encode($_POST['csv']['fields_enclosed']) : '&quot;'; ?>" style="width: 15px" onkeypress="return handleEnter(this, event)" /></td>
								</tr>
								<tr>
									<td><?php echo create_tooltip('Fields Delimited By', '<strong>Fields Delimited By</strong><br />This is generally a comma; however, if you your fields are delimited by a different character please specify it here (i.e. a semi-colon [;], or tab [\\t]).'); ?></td>
									<td><input type="text" class="text-box" name="csv[fields_delimited]" value="<?php echo (!empty($_POST['type']) && $_POST['type'] == 'csv') ? html_encode($_POST['csv']['fields_delimited']) : ','; ?>" style="width: 15px" onkeypress="return handleEnter(this, event)" /></td>
								</tr>
							</tbody>
							</table>
						</td>
					</tr>
				</tbody>
				<tbody id="text" style="display: none">
					<tr>
						<td>&nbsp;</td>
						<td>
							<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
							<colgroup>
								<col style="width: 35%" />
								<col style="width: 65%" />
							</colgroup>
							<tbody>
								<tr>
									<td colspan="2"><?php echo create_tooltip('Paste CSV Data Below', '<strong>Paste CSV Data Below</strong><br />If you would like to copy and paste your CSV data instead of uploading a file from your computer, please paste it in the textarea below.', true); ?></td>
								</tr>
								<tr>
									<td colspan="2" style="padding-left: 5px">
										<textarea name="text[data]" style="width: 97%; height: 125px"><?php echo (!empty($_POST['type']) && $_POST['type'] == 'text') ? checkslashes($_POST['text']['data'], 1) : ''; ?></textarea>
									</td>
								</tr>
								<tr>
									<td><?php echo create_tooltip('Fields Enclosed By', '<strong>Fields Enclosed By</strong><br />This is generally a double quote character; however, if you your fields are enclosed by a different character please specify it here.'); ?></td>
									<td><input type="text" class="text-box" name="text[fields_enclosed]" value="<?php echo (!empty($_POST['type']) && $_POST['type'] == 'text') ? html_encode($_POST['text']['fields_enclosed']) : '&quot;'; ?>" style="width: 15px" onkeypress="return handleEnter(this, event)" /></td>
								</tr>
								<tr>
									<td><?php echo create_tooltip('Fields Delimited By', '<strong>Fields Delimited By</strong><br />This is generally a comma; however, if you your fields are delimited by a different character please specify it here (i.e. a semi-colon [;], or tab [\\t]).'); ?></td>
									<td><input type="text" class="text-box" name="text[fields_delimited]" value="<?php echo (!empty($_POST['type']) && $_POST['type'] == 'text') ? html_encode($_POST['text']['fields_delimited']) : ','; ?>" style="width: 15px" onkeypress="return handleEnter(this, event)" /></td>
								</tr>
							</tbody>
							</table>
						</td>
					</tr>
				</tbody>
				<tr>
					<td colspan="2">
						<h2>Imported Data Destination</h2>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						Please select the group or groups that you would like these importees subscribed to:
						<select name="group_ids[]" style="margin-top: 5px; width: 97%" multiple="multiple" size="7" onkeypress="return handleEnter(this, event)">
						<?php echo groups_inselect(0, !empty($_POST['group_ids']) ? $_POST['group_ids'] : []); ?>
						</select>
						<br />
						<span class="small-grey"><strong>Notice:</strong> The importee will only receive one opt-in message for all groups you select here.</span>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<h2>Import Options</h2>
					</td>
				</tr>
				<tr>
					<td><input type="checkbox" id="options_confirmation" name="options[confirmation]" value="1" onkeypress="return handleEnter(this, event)"<?php echo (!empty($_POST['options']['confirmation']) && $_POST['options']['confirmation'] == '1') ? ' checked="checked"' : ''; ?> /></td>
					<td><label for="options_confirmation">Please send all importees opt-in notices prior to adding them to the selected groups.</label></td>
				</tr>
				<tr>
					<td><input type="checkbox" id="options_firstrowfields" name="options[firstrowfields]" value="1" onkeypress="return handleEnter(this, event)"<?php echo (empty($_POST) || (!empty($_POST['options']['firstrowfields']) && $_POST['options']['firstrowfields'] == '1')) ? ' checked="checked"' : ''; ?> /></td>
					<td><label for="options_firstrowfields">The first row in the file/data contains the field names; try to match those.</label></td>
				</tr>
				<tr>
					<td><input type="checkbox" id="options_dupecheck" name="options[dupecheck]" value="1" onkeypress="return handleEnter(this, event)"<?php echo (empty($_POST) || (!empty($_POST['options']['dupecheck']) && $_POST['options']['dupecheck'] == '1')) ? ' checked="checked"' : ''; ?> /></td>
					<td><label for="options_dupecheck">Check for duplicates to ensure only one e-mail address is in each group.</label></td>
				</tr>
				<tr>
					<td><input type="checkbox" id="options_noresubscribe" name="options[noresubscribe]" value="1" onkeypress="return handleEnter(this, event)"<?php echo (empty($_POST) || (!empty($_POST['options']['noresubscribe']) && $_POST['options']['noresubscribe'] == '1')) ? ' checked="checked"' : ''; ?> /></td>
					<td><label for="options_noresubscribe">Do not import subscribers that have previously unsubscribed from the selected groups.</label></td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
						<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=control'" />
						<input type="submit" value="Next Step" class="button" />
					</td>
				</tr>
				</table>
				</form>
				<?php
            break;
        }
        break;
    default:
        ?>
		<h1>Import &amp; Export</h1>
		Welcome to the import and export wizard for ListMessenger Pro. This wizard will allow you to import Comma Separated Values (CSV) data into ListMessenger Pro, as well as export to a Comma Separated Values (CSV) format so you are able to manage your mailing list externally.
		<div style="height: 25px">&nbsp;</div>
		<table style="width: 100%" cellspacing="0" cellpadding="5" border="0">
		<tr>
			<td style="width: 10%; text-align: center"><a href="./index.php?section=import-export&action=import"><img src="./images/icon-import.gif" width="48" height="48" alt="Import Mailing List" title="Import Mailing List" border="0" /></a></td>
			<td style="width: 90%; text-align: left">
				<h2>Import Mailing List</h2>
				Please <a href="./index.php?section=import-export&action=import">click here</a> if you wish to import your mailing list into ListMessenger.
			</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td style="width: 10%; text-align: center"><a href="./index.php?section=import-export&action=export"><img src="./images/icon-export.gif" width="48" height="48" alt="Export Mailing List" title="Export Mailing List" border="0" /></a></td>
			<td style="width: 90%; text-align: left">
				<h2>Export Mailing List</h2>
				Please <a href="./index.php?section=import-export&action=export">click here</a> if you wish to export your mailing list from ListMessenger.
			</td>
		</tr>
		</table>
		<?php
    break;
}
