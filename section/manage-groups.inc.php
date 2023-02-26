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

$ACTION = false;

if (!empty($_GET['action'])) {
    $ACTION = clean_input($_GET['action'], ['alphanumeric']);
} elseif (!empty($_POST['action'])) {
    $ACTION = clean_input($_POST['action'], ['alphanumeric']);
}

switch ($ACTION) {
    case 'add':
        if (!empty($_POST)) {
            if (!empty($_POST['group_name']) && (strlen(trim($_POST['group_name'])) < 1)) {
                ++$ERROR;
                $ERRORSTR[] = 'Please enter a Group Name to continue.';
            } else {
                $query = 'SELECT `groups_id` FROM `'.TABLES_PREFIX.'groups` WHERE `group_name` = '.$db->qstr($_POST['group_name']).' AND `group_parent` = '.$db->qstr($_POST['group_parent']);
                if ($db->GetRow($query)) {
                    ++$ERROR;
                    $ERRORSTR[] = 'The Group Name you have entered already exists in that parent group.';
                }
            }

            if (!$ERROR) {
                $query = 'INSERT INTO `'.TABLES_PREFIX.'groups` (`groups_id`, `group_name`, `group_parent`, `group_private`) VALUES (NULL, '.$db->qstr($_POST['group_name']).', '.$db->qstr($_POST['group_parent']).", '".((!empty($_POST['group_private']) && ($_POST['group_private'] == '1')) ? 'true' : 'false')."')";
                if ($db->Execute($query)) {
                    header('Location: index.php?section=manage-groups');
                    exit;
                } else {
                    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to add group [".checkslashes($_POST['group_name']).'] to database. Database said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                    }

                    ++$ERROR;
                    $ERRORSTR[] = 'Sorry, but we were unable to add the new group. Please check your ListMessenger error_log for more information if you have logging enabled.';
                }
            }
        }
        ?>
		<h1>New Group</h1>
		After adding a new group, you'll be able to either manually add subscribers to the system or provide end-users with a subscribe box to subscribe to this group from your website.
		<br /><br />
		<?php echo ($ERROR > 0) ? display_error($ERRORSTR) : ''; ?>

		<form action="index.php?section=manage-groups&action=add" method="post">
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
					<input type="hidden" name="group_private" value="0" />
					<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=manage-groups'" />
					<input type="submit" value="Add Group" class="button" />
				</td>
			</tr>
		</tfoot>
		<tbody>
			<tr>
				<td><?php echo create_tooltip('Group Name', '<strong>Field Name: <em>Group Name</em></strong><br />This is the name of your new ListMessenger group.<br /><br />A group can also be thought of as a mailing list; it is the container that will hold your subscribers.', true); ?></td>
				<td><input type="text" class="text-box" style="width: 200px" name="group_name" value="<?php echo !empty($_POST['group_name']) ? clean_input($_POST['group_name'], 'stripslashes') : ''; ?>" /></td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Parent Group', '<strong>Field Name: <em>Parent Group</em></strong><br />ListMessenger supports a hierarchical group structure, so you are free to create groups and sub-groups as best fits your website.<br /><br />For example, with ListMessenger you could do something like:<ul><li>General Public</li><ul><li>Website Subscribers</li><li>Telephone Subscribers</li><li>Magazine Subscribers</li></ul><li>Clients</li><ul><li>Tier 1</li><li>Tier 2</li><li>Tier 3</li></ul><li>Departments</li><ul><li>Sales</li><ul><li>Managers</li><li>Other</li></ul><li>Research / Development</li><ul><li>Managers</li><li>Other</li></ul></ul><li>Other</li></ul>', false); ?></td>
				<td>
					<select name="group_parent" style="width: 204px">
					<option value="0">-- Top Level Group --</option>
					<?php echo groups_inselect(0, !empty($_POST['group_parent']) ? (int) $_POST['group_parent'] : 0); ?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td><?php echo create_tooltip('Group Type', '<strong>Field Name: <em>Group Type</em></strong><br />This option allows you to choose whether this group can be subscribed to by the public through the end-user tools.<br /><br />If you make this a <strong>Private Group</strong> then people will not be able to subscribe to this group themselves, you as an administrator will have to add them.', false); ?></td>
				<td>
					<select id="group_private" name="group_private" style="width: 204px">
						<option value="0"<?php echo (!empty($_POST['group_private'])) ? (($_POST['group_private'] != '1') ? ' selected="selected"' : '') : ' selected="selected"'; ?>>Public Group</option>
						<option value="1"<?php echo (!empty($_POST['group_private'])) ? (($_POST['group_private'] == '1') ? ' selected="selected"' : '') : ''; ?>>Private Group</option>
					</select>
				</td>
			</tr>
		</tbody>
		</table>
		</form>
		<?php
    break;
    case 'delete':
        $delete_id = 0;

        if (!empty($_GET['id'])) {
            $delete_id = (int) $_GET['id'];
        }

        if (!$delete_id) {
            ++$ERROR;
            $ERRORSTR[] = 'There was no group ID provided to delete.';

            echo ($ERROR > 0) ? display_error($ERRORSTR) : '';

            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThere was no group ID provided in the URL to delete.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
        } else {
            $info = groups_information([$delete_id]);

            if (!$info[$delete_id]) {
                ++$ERROR;
                $ERRORSTR[] = 'The group ID you have specified does not exist in the database.';

                echo ($ERROR > 0) ? display_error($ERRORSTR) : '';
            } else {
                if (!empty($_POST['confirmed']) && ($_POST['confirmed'] == 'true')) {
                    if (empty($_POST['group_id']) || (int) $_POST['group_id'] != $delete_id) {
                        ++$ERROR;
                        $ERRORSTR[] = 'The group ID in the URL does not match the posted group ID.';
                    }

                    if ($ERROR) {
                        echo display_error($ERRORSTR);
                    } else {
                        $handle_users = false;
                        if (!empty($_POST['handle_users'])) {
                            $handle_users = clean_input($_POST['handle_users'], 'alphanumeric');
                        }

                        if ($handle_users == 'delete') {
                            if (!users_delete($delete_id)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'Unable to delete the subscribers from the users table. Please check the error_log for more information.';

                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete subscribers in the users table. MySQL reported: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }
                            }
                        } elseif ($handle_users == 'move') {
                            if (empty($_POST['users_group_parent']) || !users_move($delete_id, (int) $_POST['users_group_parent'])) {
                                ++$ERROR;
                                $ERRORSTR[] = 'Unable to change the group in which the subscribers reside. Please check the error_log for more information.';

                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to update the group information in the users table. MySQL reported: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }
                            }
                        }

                        $handle_subgroups = false;
                        if (!empty($_POST['handle_subgroups'])) {
                            $handle_subgroups = clean_input($_POST['handle_subgroups'], 'alphanumeric');
                        }
                        if ($handle_subgroups == 'delete') {
                            if (!groups_delete($delete_id, 0, true)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'Unable to delete the groups from the groups table. Please check the error_log for more information.';

                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete groups from the groups table. MySQL reported: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }
                            }
                        } elseif ($handle_subgroups == 'move') {
                            if (empty($_POST['groups_group_parent']) || !groups_move($handle_subgroups, (int) $_POST['groups_group_parent'])) {
                                ++$ERROR;
                                $ERRORSTR[] = 'Unable to change the parent group in which the sub-groups reside. Please check the error_log for more information.';

                                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to change the parent group in which the sub-groups reside. MySQL reported: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }
                            }
                        }

                        $query = 'DELETE FROM `'.TABLES_PREFIX.'groups` WHERE `groups_id` = '.$delete_id;
                        if (!$db->Execute($query)) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Unable to delete the group that you specified. Please check the error_log for more information.';

                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete the group that you specified. MySQL reported: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }
                        }

                        if ($ERROR) {
                            echo display_error($ERRORSTR);
                        } else {
                            header('Location: index.php?section=manage-groups');
                            exit;
                        }
                    }
                } else {
                    ?>
					<h1>Deleting Group</h1>
					<?php echo ($ERROR > 0) ? display_error($ERRORSTR) : ''; ?>
					<form action="index.php?section=manage-groups&action=delete&id=<?php echo (int) $_GET['id']; ?>" method="post">
					<input type="hidden" name="confirmed" value="true" />
					<input type="hidden" name="group_id" value="<?php echo (int) $_GET['id']; ?>" />
					Please confirm that you wish to delete the following group:
					<ol>
						<li><span class="confirm-delete"><?php echo html_encode($info[$_GET['id']]['name']); ?></span></li>
					</ol>
					<br />
					<?php
                    $users = users_count(checkslashes($_GET['id']));
                    if ($users > 0) {
                        $group_select = groups_inselect(0, false, 0, [$_GET['id']]);

                        if ($group_select != '') {
                            $ONLOAD[] = "div_hide(element_type('users_group_parent'))";
                        }

                        echo 'There are '.$users.' user'.(($users != 1) ? 's' : '').' residing under '.html_encode($info[checkslashes($_GET['id'])]['name']).':';
                        echo "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">\n";
                        echo "<tr>\n";
                        echo "	<td style=\"width: 5%; text-align: right\"><input type=\"radio\" name=\"handle_users\" value=\"delete\" onclick=\"div_hide(element_type('users_group_parent'))\" checked=\"checked\" /></td>\n";
                        echo "	<td style=\"width: 95%; text-align: left\"><strong>Delete</strong> all subscribers residing in this group.</td>\n";
                        echo "</tr>\n";
                        if ($group_select != '') {
                            echo "<tr>\n";
                            echo "	<td style=\"width: 5%; text-align: right\"><input type=\"radio\" name=\"handle_users\" value=\"move\" onclick=\"div_show(element_type('users_group_parent'))\" /></td>\n";
                            echo "	<td style=\"width: 95%; text-align: left\"><strong>Move</strong> all subscribers residing in this group.</td>\n";
                            echo "</tr>\n";
                            echo "<tr>\n";
                            echo "	<td style=\"width: 5%; text-align: right\">&nbsp;</td>\n";
                            echo "	<td style=\"width: 95%; height: 25px; text-align: left\">\n";
                            echo "		<div id=\"users_group_parent\">\n";
                            echo "		<select name=\"users_group_parent\" style=\"width: 204px\">\n";
                            echo $group_select;
                            echo "		</select>\n";
                            echo "		</div>\n";
                            echo "	</td>\n";
                            echo "</tr>\n";
                        } else {
                            echo "<tr>\n";
                            echo "	<td style=\"width: 5%; text-align: right\">&nbsp;</td>\n";
                            echo "	<td style=\"width: 95%; text-align: left\"><span class=\"small-grey\">Unable to move subscribers, as there are no other top level groups.</span></td>\n";
                            echo "</tr>\n";
                        }
                        echo "</table>\n";

                        echo "<br />\n";
                    }

                    $sub_groups = groups_count(checkslashes($_GET['id']), $groups);
                    if ($sub_groups > 0) {
                        $ONLOAD[] = "div_hide(element_type('groups_group_parent'))";

                        echo 'There are '.$sub_groups.' sub-group'.(($sub_groups != 1) ? 's' : '').' residing under '.html_encode($info[checkslashes($_GET['id'])]['name']).':';
                        echo "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">\n";
                        echo "<tr>\n";
                        echo "	<td style=\"width: 5%; text-align: right\"><input type=\"radio\" name=\"handle_subgroups\" value=\"delete\" onclick=\"div_hide(element_type('groups_group_parent'))\" checked=\"checked\" /></td>\n";
                        echo "	<td style=\"width: 95%; text-align: left\"><strong>Delete</strong> all sub-groups and any subscribers in those sub-groups.</td>\n";
                        echo "</tr>\n";
                        echo "<tr>\n";
                        echo "	<td style=\"width: 5%; text-align: right\"><input type=\"radio\" name=\"handle_subgroups\" value=\"move\" onclick=\"div_show(element_type('groups_group_parent'))\" /></td>\n";
                        echo "	<td style=\"width: 95%; text-align: left\"><strong>Move</strong> all sub-groups and any subscribers in those sub-groups.</td>\n";
                        echo "</tr>\n";
                        echo "<tr>\n";
                        echo "	<td style=\"width: 5%; text-align: right\">&nbsp;</td>\n";
                        echo "	<td style=\"width: 95%; height: 25px; text-align: left\">\n";
                        echo "		<div id=\"groups_group_parent\">\n";
                        echo "		<select name=\"groups_group_parent\" style=\"width: 204px\">\n";
                        echo "		<option value=\"0\">-- Top Level Group --</option>\n";
                        echo groups_inselect(0, false, 0, [$_GET['id']]);
                        echo "		</select>\n";
                        echo "		</div>\n";
                        echo "	</td>\n";
                        echo "</tr>\n";
                        echo "</table>\n";
                    }
                    ?>
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td style="text-align: right; border-top: 1px #333333 dotted; padding-top: 5px">
							<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=manage-groups'" />
							<input type="submit" value="Confirm" class="button" />
						</td>
					</tr>
					</table>
					</form>
					<?php
                }
            }
        }
        break;
    case 'edit':
        if (strlen($_GET['id']) < 1) {
            ++$ERROR;
            $ERRORSTR[] = 'There was no group ID provided in the URL to edit.';
            echo ($ERROR > 0) ? display_error($ERRORSTR) : '';
            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThere was no group ID provided in the URL to edit.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
        } else {
            $query = 'SELECT * FROM `'.TABLES_PREFIX."groups` WHERE `groups_id`='".checkslashes($_GET['id'])."'";
            $result = $db->GetRow($query);
            if ($result) {
                if ($_POST) {
                    if (strlen(trim($_POST['group_name'])) < 1) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Please enter a Group Name to continue.';
                    } else {
                        if ($_POST['ogroup_parent'] != $_POST['group_parent']) {
                            $query = 'SELECT `groups_id` FROM `'.TABLES_PREFIX."groups` WHERE `group_name`='".checkslashes($_POST['group_name'])."' AND `group_parent`='".checkslashes($_POST['group_parent'])."'";
                            if ($db->GetRow($query)) {
                                ++$ERROR;
                                $ERRORSTR[] = 'The specified Group Name already exists in the groups new desination. Please change the name of your group or move it to a different location.';
                            }
                        }
                    }

                    if ($_GET['id'] == $_POST['group_parent']) {
                        ++$ERROR;
                        $ERRORSTR[] = 'A group cannot be a parent group of itself. Please choose a different parent for this group.';
                    }

                    if (!$ERROR) {
                        $query = 'UPDATE `'.TABLES_PREFIX."groups` SET `group_name`='".checkslashes($_POST['group_name'])."', `group_parent`='".checkslashes($_POST['group_parent'])."', `group_private`='".(($_POST['group_private'] == '1') ? 'true' : 'false')."' WHERE `groups_id`='".checkslashes($_GET['id'])."'";
                        if ($db->Execute($query)) {
                            header('Location: index.php?section=manage-groups');
                            exit;
                        } else {
                            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to add group [".checkslashes($_POST['group_name']).'] to database. Database said: '.$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }

                            ++$ERROR;
                            $ERRORSTR[] = 'Sorry, but we were unable to edit the group. Please check your ListMessenger error_log for more information if you have logging enabled.';
                        }
                    }
                }
                ?>
				<h1>Edit Group</h1>
				After you edit the group information, changes will take effect immediately.
				<br /><br />
				<?php echo ($ERROR > 0) ? display_error($ERRORSTR) : ''; ?>
				<form action="index.php?section=manage-groups&action=edit&id=<?php echo (int) $_GET['id']; ?>" method="post">
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
							<input type="hidden" name="ogroup_parent" value="<?php echo $result['group_parent']; ?>" />
							<input type="button" value="Cancel" class="button" onclick="window.location='index.php?section=manage-groups'" />
							<input type="submit" value="Save" class="button" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<td><?php echo create_tooltip('Group Name', '<strong>Field Name: <em>Group Name</em></strong><br />This is the name of your new ListMessenger group.<br /><br />A group can also be thought of as a mailing list; it is the container that will hold your subscribers.', true); ?></td>
						<td><input type="text" class="text-box" style="width: 200px" name="group_name" value="<?php echo ($_POST) ? checkslashes($_POST['group_name'], 1) : $result['group_name']; ?>" /></td>
					</tr>
					<tr>
						<td><?php echo create_tooltip('Parent Group', '<strong>Field Name: <em>Parent Group</em></strong><br />ListMessenger supports a hierarchical group structure, so you are free to create groups and sub-groups as best fits your website.<br /><br />For example, with ListMessenger you could do something like:<ul><li>General Public</li><ul><li>Website Subscribers</li><li>Telephone Subscribers</li><li>Magazine Subscribers</li></ul><li>Clients</li><ul><li>Tier 1</li><li>Tier 2</li><li>Tier 3</li></ul><li>Departments</li><ul><li>Sales</li><ul><li>Managers</li><li>Other</li></ul><li>Research / Development</li><ul><li>Managers</li><li>Other</li></ul></ul><li>Other</li></ul>', false); ?></td>
						<td>
							<select id="group_parent" name="group_parent" style="width: 204px">
							<option value="0">-- Top Level Group --</option>
							<?php echo groups_inselect(0, [($_POST) ? checkslashes($_POST['group_parent']) : $result['group_parent']], 0, [$_GET['id']]); ?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td><?php echo create_tooltip('Group Type', '<strong>Field Name: <em>Group Type</em></strong><br />This option allows you to choose whether this group can be subscribed to by the public through the end-user tools.<br /><br />If you make this a <strong>Private Group</strong> then people will not be able to subscribe to this group themselves, you as an administrator will have to add them.', false); ?></td>
						<td>
							<select id="group_private" name="group_private" style="width: 204px">
								<option value="0"<?php echo ($_POST) ? (($_POST['group_private'] != '1') ? ' selected="selected"' : '') : (($result['group_private'] != 'true') ? ' selected="selected"' : ''); ?>>Public Group</option>
								<option value="1"<?php echo ($_POST) ? (($_POST['group_private'] == '1') ? ' selected="selected"' : '') : (($result['group_private'] == 'true') ? ' selected="selected"' : ''); ?>>Private Group</option>
							</select>
						</td>
					</tr>
				</tbody>
				</table>
				</form>
				<?php
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'The group ID specified in the URL ['.checkslashes($_GET['id']).'] does not exist in the database.';
                echo ($ERROR > 0) ? display_error($ERRORSTR) : '';
                if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThe group ID specified in the URL [".checkslashes($_GET['id'])."] does not exist in the database.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            }
        }
        break;
    case 'view':
        if (strlen($_GET['id']) < 1) {
            ++$ERROR;
            $ERRORSTR[] = 'There was no group ID provided to view.';
            echo ($ERROR > 0) ? display_error($ERRORSTR) : '';
            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThere was no group ID provided in the URL to view.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
        } else {
        }
        break;
    default:	// List Groups
        $i = count($SIDEBAR);
        $SIDEBAR[$i] = "<br /><div align=\"center\"><input type=\"button\" value=\"New Group\" class=\"button\" onclick=\"window.location='index.php?section=manage-groups&action=add'\" /></div>";

        ?>
		<h1>Manage Groups</h1>
		In the Manage Groups section you are able to manage all aspects of your groups (or lists), including adding a new group, editing, removing groups and viewing statistics about an existing group.
		<br /><br />
		<?php
        $display_groups = groups_intable(0);
        if ($display_groups != '') {
            echo "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\">\n";
            echo "<colgroup>\n";
            echo "	<col style=\"width: 8%\" />\n";
            echo "	<col style=\"width: 47%\" />\n";
            echo "	<col style=\"width: 15%\" />\n";
            echo "	<col style=\"width: 15%\" />\n";
            echo "	<col style=\"width: 15%\" />\n";
            echo "</colgroup>\n";
            echo $display_groups;
            echo "</table>\n";
        } else {
            ?>
			<div class="generic-message">
				There are no Groups available in your ListMessenger database.
				<br /><br />
				To create a new ListMessenger Group click the <strong>New Group</strong> button in the sidebar.
			</div>
			<?php
        }
        break;
}
