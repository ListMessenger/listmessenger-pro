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

if (!empty($_COOKIE['display']['attachments']['collapsed'])) {
    $COLLAPSED = explode(',', $_COOKIE['display']['attachments']['collapsed']);
} else {
    $COLLAPSED = [];
}

if (!empty($_FILES) && ($_GET['action'] == 'add')) {
    if ((empty($_FILES['newfile'])) || ($_FILES['newfile'] == '')) {
        ++$ERROR;
        $ERRORSTR[] = 'You did not select a file on your computer to upload. Please select a local file.';
    } else {
        switch ($_FILES['newfile']['error']) {
            case '1':
                // File exceeds upload_max_file size in php.ini.
                $ERROR++;
                $ERRORSTR[] = 'The file that you are trying to upload is larger than the server currently allows.';
                break;
            case '2':
                // File exceeds MAX_FILE_SIZE directive in form.
                $ERROR++;
                $ERRORSTR[] = 'The file that you are trying to upload is larger than the max file size your server is set to.';
                break;
            case '3':
                // File was only partially uploaded.
                $ERROR++;
                $ERRORSTR[] = 'The file that uploaded did not complete the upload process or was interupted. Please try again.';
                break;
            case '4':
                // There was no file uploaded.
                $ERROR++;
                $ERRORSTR[] = 'You did not select a file on your computer to upload. Please select a local file.';
                break;
            default:
                $filename = valid_filename($_FILES['newfile']['name']);
                if (move_uploaded_file($_FILES['newfile']['tmp_name'], $_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename)) {
                    ++$SUCCESS;
                    $SUCCESSSTR[] = 'Successfully uploaded '.$filename.' to your public files directory.';
                } else {
                    ++$ERROR;
                    $ERRORSTR[] = 'Unable to move &quot;'.$filename.'&quot; to the &quot;'.$_SESSION['config'][PREF_PUBLIC_PATH].'files&quot; directory.<br /><br />To resolve this, please make sure that PHP has write permissions to the public files directory.';
                }
                break;
        }
    }
}

if ($ERROR) {
    echo display_error($ERRORSTR);
}
if ($SUCCESS) {
    echo display_success($SUCCESSSTR);
}

if (!empty($_GET['action']) && $_GET['action'] == 'delete') {
    if (!empty($_POST['remove']) && is_array($_POST['remove']) && (count($_POST['remove']) > 0)) {
        if ((empty($_POST['confirmed'])) || ($_POST['confirmed'] != '1')) {
            $filecount = 0;
            $totalsize = 0;
            ?>
			<h1>Removing Attachments</h1>
			Please confirm that you wish to permenantly remove the following attachments from the filesystem.
			<br /><br />
			<form action="./index.php?section=attachments&action=delete" method="post">
			<input type="hidden" name="confirmed" value="1" />
			<table style="width: 100%; text-align: left" cellspacing="0" cellpadding="1" border="0">
			<tr>
				<td style="width: 3%; height: 15px; padding-left: 2px; border-top: 1px #9D9D9D solid; border-left: 1px #9D9D9D solid; border-bottom: 1px #9D9D9D solid; background-image: url('./images/table-head-off.gif'); white-space: nowrap">&nbsp;</td>
				<td style="width: 80%; height: 15px; padding-left: 2px; border-top: 1px #9D9D9D solid; border-left: 1px #9D9D9D solid; border-bottom: 1px #9D9D9D solid; background-image: url('./images/table-head-on.gif'); white-space: nowrap"><span class="theading-off">Filename</span></td>
				<td style="width: 17%; height: 15px; padding-left: 2px; border: 1px #9D9D9D solid; background-image: url('./images/table-head-off.gif'); white-space: nowrap"><span class="theading-off">Filesize</span></td>
			</tr>
			<?php
            foreach ($_POST['remove'] as $filename) {
                if (file_exists($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename)) {
                    ++$filecount;
                    $filesize = filesize($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename);
                    $totalsize += $filesize;
                    echo "<tr>\n";
                    echo '	<td><input type="checkbox" name="remove[]" value="'.$filename."\" checked=\"checked\" /></td>\n";
                    echo '	<td style="padding-left: 5px"><a href="'.$_SESSION['config'][PREF_PUBLIC_URL].'files/'.$filename.'">'.$filename."</a></td>\n";
                    echo '	<td style="padding-left: 5px">'.readable_size($filesize)."</td>\n";
                    echo "</tr>\n";
                }
            }
            echo "<tr>\n";
            echo "	<td colspan=\"3\">&nbsp;</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
            echo "	<td colspan=\"3\">\n";
            if ($filecount > 0) {
                echo 'I confirm that I wish to delete the following <strong>'.$filecount.'</strong> file'.(($filecount != 1) ? 's' : '').' <em>('.readable_size($totalsize).')</em> from the public files directory.<br /><br />';
                echo "<input type=\"button\" class=\"button\" value=\"Cancel\" onclick=\"window.location='index.php?section=attachments'\" />&nbsp;";
                echo "<input type=\"submit\" class=\"button\" value=\"Confirmed\" />\n";
            }
            echo "	</td>\n";
            echo "</tr>\n";
            echo "</table>\n";
            echo "</form>\n";
        } else {
            foreach ($_POST['remove'] as $filename) {
                $filename = clean_input($filename, 'file');
                unlink($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename);
            }
            header('Location: index.php?section=attachments');
            exit;
        }
    } else {
        ++$ERROR;
        $ERRORSTR[] = 'You did not select any attachments to remove from the file system. If you would like to remove an attachment, check the checkbox beside the filename then click the Delete Selected button.';

        echo display_error($ERRORSTR);
    }
} else {
    // Setup "Sort By Field" Information
    if (!empty($_GET['sort']) && strlen($_GET['sort']) > 0) {
        $_SESSION['display']['attachments']['sort'] = checkslashes($_GET['sort']);
        setcookie('display[attachments][sort]', checkslashes($_GET['sort']), PREF_COOKIE_TIMEOUT);
    } elseif ((empty($_SESSION['display']['attachments']['sort'])) && (!empty($_COOKIE['display']['attachments']['sort']))) {
        $_SESSION['display']['attachments']['sort'] = $_COOKIE['display']['attachments']['sort'];
    } else {
        if (empty($_SESSION['display']['attachments']['sort'])) {
            $_SESSION['display']['attachments']['sort'] = 'name';
            setcookie('display[attachments][sort]', 'name', PREF_COOKIE_TIMEOUT);
        }
    }

    // Setup "Sort Order" Information
    if (!empty($_GET['order'])) {
        switch ($_GET['order']) {
            case 'asc':
                $_SESSION['display']['attachments']['order'] = 'ASC';
                break;
            case 'desc':
                $_SESSION['display']['attachments']['order'] = 'DESC';
                break;
            default:
                $_SESSION['display']['attachments']['order'] = 'ASC';
                break;
        }
        setcookie('display[attachments][order]', $_SESSION['display']['attachments']['order'], PREF_COOKIE_TIMEOUT);
    } elseif ((empty($_SESSION['display']['attachments']['order'])) && (!empty($_COOKIE['display']['attachments']['order']))) {
        $_SESSION['display']['attachments']['order'] = $_COOKIE['display']['attachments']['order'];
    } else {
        if (empty($_SESSION['display']['attachments']['order'])) {
            $_SESSION['display']['attachments']['order'] = 'ASC';
            setcookie('display[attachments][order]', 'ASC', PREF_COOKIE_TIMEOUT);
        }
    }

    // Set the internal variables used for sorting, ordering and in pagination.
    $sort = $_SESSION['display']['attachments']['sort'];
    $order = $_SESSION['display']['attachments']['order'];
    $filecount = 0;

    if (is_dir($_SESSION['config'][PREF_PUBLIC_PATH].'files/')) {
        if (is_writable($_SESSION['config'][PREF_PUBLIC_PATH].'files/')) {
            ?>
			<div style="display: <?php echo in_array('upload', $COLLAPSED) ? 'none' : 'inline'; ?>" id="opened_upload">
				<table style="width: 100%; border: 1px #CCCCCC solid" cellspacing="0" cellpadding="1">
				<tr>
					<td class="cursor" style="height: 15px; background-image: url('./images/table-head-on.gif'); background-color: #EEEEEE; border-bottom: 1px #CCCCCC solid" onclick="toggle_section('upload', 1, '<?php echo javascript_cookie(); ?>', 'attachments')">
						<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
						<tr>
							<td style="width: 95%; text-align: left"><span class="search-on">Attachment Upload</span></td>
							<td style="width: 5%; text-align: right"><a href="javascript: toggle_section('upload', 1, '<?php echo javascript_cookie(); ?>', 'attachments')"><img src="./images/section-hide.gif" width="9" height="9" alt="Hide" title="Hide Attachment Upload" border="0" /></a></td>
						</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<form action="index.php?section=attachments&action=add" method="post" enctype="multipart/form-data" />
						<table style="width: 100%; height: 25px" cellspacing="1" cellpadding="1" border="0">
						<tr>
							<td style="width: 25%; vertical-align: middle" class="search-heading">Upload Attachment:</td>
							<td style="width: 50%; vertical-align: middle"><input type="file" name="newfile" class="file" size="30" /></td>
							<td style="width: 25%; vertical-align: middle; text-align: right"><input type="submit" class="button" value="Upload File" /></td>
						</tr>
						</table>
						</form>
					</td>
				</tr>
				</table>
			</div>
			<div style="display: <?php echo !in_array('upload', $COLLAPSED) ? 'none' : 'inline'; ?>" id="closed_upload">
				<table style="width: 100%; border: 1px #CCCCCC solid" cellspacing="0" cellpadding="1">
				<tr>
					<td class="cursor" style="height: 15px; background-image: url('./images/table-head-off.gif'); background-color: #EEEEEE" onclick="toggle_section('upload', 0, '<?php echo javascript_cookie(); ?>', 'attachments')">
						<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
						<tr>
							<td style="width: 95%; text-align: left"><span class="search-off">Attachment Upload</span></td>
							<td style="width: 5%; text-align: right"><a href="javascript: toggle_section('upload', 0, '<?php echo javascript_cookie(); ?>', 'attachments')"><img src="./images/section-show.gif" width="9" height="9" alt="Show" title="Show Attachment Upload" border="0" /></a></td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</div>
			<?php
        } else {
            ++$ERROR;
            $ERRORSTR[] = 'Your documents directory is currently not writable by PHP, please chmod it to 777 so you are able to upload files to this directory.';

            echo display_error($ERRORSTR);
        }
        ?>
		<h1>Manage Attachments</h1>
		<?php
        if ($handle = opendir($_SESSION['config'][PREF_PUBLIC_PATH].'files/')) {
            $filenames = [];
            $filesizes = [];
            $totalsize = 0;

            while (($filename = readdir($handle)) !== false) {
                if (($filename != '.') && ($filename != '..') && (substr($filename, 1) != '.')) {
                    $filesize = filesize($_SESSION['config'][PREF_PUBLIC_PATH].'files/'.$filename);

                    $i = count($filenames);
                    $filenames[$i] = $filename;
                    $filesizes[$i] = $filesize;

                    $totalsize += $filesize;
                }
            }
            closedir($handle);

            $filecount = count($filenames);

            if ($filecount > 0) {
                ?>
				<form action="./index.php?section=attachments&action=delete" method="post">
				<table class="tabular" cellspacing="0" cellpadding="1" border="0">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 80%" />
					<col style="width: 17%" />
				</colgroup>
				<thead>
					<tr>
						<td>&nbsp;</td>
						<td class="<?php echo ($sort == 'name') ? 'on' : 'off'; ?>"><?php echo order_link('name', 'Filename', $order, $sort); ?></td>
						<td class="close <?php echo ($sort == 'size') ? 'on' : 'off'; ?>"><?php echo order_link('size', 'Filesize', $order, $sort); ?></td>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="3" style="border-top: 1px #333333 dotted; padding-top: 5px">
							<input type="checkbox" name="selectall" value="1" onclick="selection(this, 'remove[]')" />
							<input type="submit" value="Delete Selected" class="button" />
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<h2>Public Files Directory Statistics:</h2>
							There <?php echo ($filecount != 1) ? 'are' : 'is'; ?> currently <strong><?php echo $filecount; ?></strong> file<?php echo ($filecount != 1) ? 's' : ''; ?> in your public files directory.
							<br />
							Your public files directory constains a total of <strong><?php echo readable_size($totalsize); ?></strong> worth of files.
						</td>
					</tr>
				</tfoot>
				<tbody>
					<?php
                    if ($sort == 'name') {
                        if ($order == 'ASC') {
                            asort($filenames);
                        } else {
                            arsort($filenames);
                        }
                        foreach ($filenames as $key => $filename) {
                            echo "<tr>\n";
                            echo '	<td><input type="checkbox" name="remove[]" value="'.$filename."\" /></td>\n";
                            echo '	<td style="padding-left: 5px"><a href="'.$_SESSION['config'][PREF_PUBLIC_URL].'files/'.$filename.'">'.$filename."</a></td>\n";
                            echo '	<td style="padding-left: 5px">'.readable_size($filesizes[$key])."</td>\n";
                            echo "</tr>\n";
                        }
                    } else {
                        if ($order == 'ASC') {
                            asort($filesizes);
                        } else {
                            arsort($filesizes);
                        }
                        foreach ($filesizes as $key => $filesize) {
                            echo "<tr>\n";
                            echo '	<td><input type="checkbox" name="remove[]" value="'.$filenames[$key]."\" /></td>\n";
                            echo '	<td style="padding-left: 5px"><a href="'.$_SESSION['config'][PREF_PUBLIC_URL].'files/'.$filenames[$key].'">'.$filenames[$key]."</a></td>\n";
                            echo '	<td style="padding-left: 5px">'.readable_size($filesize)."</td>\n";
                            echo "</tr>\n";
                        }
                    }
                ?>
				</tbody>
				</table>
				</form>
				<?php
            } else {
                ?>
				<div class="generic-message">
					There are no file attachments in your ListMessenger public files directory.
					<br /><br />
					To upload a file to this directory use the <strong>Upload Attachment</strong> menu at the top of this page.
				</div>
				<?php
            }
        }
    } else {
        ++$ERROR;
        $ERRORSTR[] = 'Your public files directory does not appear to exist or PHP is not able to read the directory. Please go into the <a href="index.php?section=preferences&type=program">ListMessenger Program Preferences</a> and update your public folder directory path.';

        echo display_error($ERRORSTR);
    }
}
