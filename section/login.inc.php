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

$ONLOAD[] = "$('#username').focus()";
?>
<table style="width: 100%; height: 450px" cellspacing="0" cellpadding="0" border="0">
<tr>
	<td style="width: 40%; text-align: right; vertical-align: middle; padding-right: 15px">
		<img src="images/listmessenger.gif" width="139" height="167" alt="ListMessenger <?php echo html_encode(trim(VERSION_TYPE.' '.VERSION_INFO)); ?>" />
	</td>
	<td style="width: 60%; text-align: left; vertical-align: middle; padding-left: 15px"">
		<?php
        /**
         * Check if the configuration has been successfully loaded in includes/loader.inc.php.
         */
        if ($CONFIG_LOADED) {
            ?>
			<div style="margin-bottom: 15px">
				Welcome to <span class="titlea-positive">List</span><span class="titleb-positive">Messenger</span> <span class="titlea-positive"><?php echo html_encode(trim(VERSION_TYPE)); ?></span>
			</div>
			<?php echo ($SUCCESS) ? display_success($SUCCESSSTR) : ''; ?>
			<?php echo ($ERROR) ? display_error($ERRORSTR) : ''; ?>
			<form action="index.php?section=login" method="post">
			<input type="hidden" name="action" value="login" />
			<input type="hidden" name="goto" value="<?php echo (!empty($_GET['section'])) ? html_encode(trim($_GET['section'])) : ''; ?>" />
			<table style="width: 250px" border="0" cellspacing="1" cellpadding="1">
			<tr>
				<td><label for="username">Username:</label></td>
				<td style="text-align: right"><input type="text" class="text-box" style="width: 150px" id="username" name="username" value="<?php echo (!empty($_POST['username'])) ? html_encode(trim($_POST['username'])) : ''; ?>" /></td>
			</tr>
			<tr>
				<td><label for="password">Password:</label></td>
				<td style="text-align: right"><input type="password" class="pass-box" style="width: 150px" id="password" name="password" value="" /></td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: right; padding-top: 10px">
					<input type="submit" value="Login" class="button" />
				</td>
			</tr>
			<tr>
				<td colspan="2" style="padding-top: 5px">
					<a href="index.php?section=password" style="font-weight: normal; font-size: 11px">Forgot Password</a>
				</td>
			</tr>
			</table>
			</form>
			<?php
        } else {
            echo "<div style=\"margin: 25px\">\n";
            echo ($ERROR) ? display_error($ERRORSTR) : '';
            echo "</div>\n";
        }
?>
	</td>
</tr>
</table>
