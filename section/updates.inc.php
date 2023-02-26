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

?>
<h1>Program Updates</h1>
<?php
if (function_exists('fsockopen')) {
    require_once 'classes/talkback/class.talkback.php';

    $talk = [];
    $talk['current_version'] = VERSION_INFO;
    $talk['version_type'] = VERSION_TYPE;
    $talk['host_name'] = $_SERVER['HTTP_HOST'];
    $talk['email_addr'] = $_SESSION['config'][REG_EMAIL];
    $talk['domain_name'] = $_SESSION['config'][REG_DOMAIN];
    $talk['serial_number'] = $_SESSION['config'][REG_SERIAL];
    $talk['timestamp'] = time();
    $talkback = new TalkBack('version', $talk);
    $result = $talkback->post();

    if ($result) {
        $version = get_data('version', $result);
        $features = get_data('features', $result);
        $update_info = get_data('update_info', $result);

        if ($version[0] > VERSION_INFO.'.'.VERSION_BUILD) {
            echo "<table width=\"400\" cellspacing=\"1\" cellpadding=\"2\" border=\"0\">\n";
            echo "<tr>\n";
            echo "	<td><strong>Newest Version:</strong>&nbsp;&nbsp;</td>\n";
            echo '	<td><strong><big>'.$version[0].'</big></strong></td>';
            echo "</tr>\n";
            echo "<tr>\n";
            echo "	<td>Your Version:&nbsp;&nbsp;</td>\n";
            echo '	<td>'.VERSION_INFO.'</td>';
            echo "</tr>\n";
            echo "<tr>\n";
            echo "	<td>Release Type:&nbsp;&nbsp;</td>\n";
            echo '	<td>'.VERSION_TYPE.'</td>';
            echo "</tr>\n";
            echo "<tr>\n";
            echo "	<td colspan=\"2\">\n";
            echo "		<br />\n";
            echo "		<div align=\"justify\">\n";
            echo $update_info[0];
            echo "		</div>\n";
            echo "	</td>\n";
            echo "</tr>\n";
            if ((count($features) > 0) && ($features[0] != '')) {
                echo "<tr>\n";
                echo "	<td colspan=\"2\">\n";
                echo "		<br />\n";
                echo "		Here are some features and fixes from the new version:\n";
                echo "		<ul>\n";
                foreach ($features as $value) {
                    echo '<li>'.$value.'</li>';
                }
                echo "		</ul>\n";
                echo "	</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
        } else {
            echo 'No new versions of ListMessenger are available at this time.';
        }
    } else {
        ++$ERROR;
        $ERRORSTR[] = 'ListMessenger was unable to check for updates at this time. Please try again later.';

        echo display_error($ERRORSTR);
        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tListMessenger was unable to check for updates. You may have allow_fopen_url disabled, your Internet connection may be off or the ListMessenger server may not be responding at this time. Please try your call again later. This is a recording.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
        }
    }
} else {
    ++$ERROR;
    $ERRORSTR[] = 'ListMessenger was unable to check for updates at this time because your hosting provider or server administrator has disabled access to the <tt>fsockopen()</tt> PHP function, which ListMessenger uses to check for updates.<br /><br />To check for updates manually, please visit our website: <a href="https://listmessenger.com" target="_blank">https://listmessenger.com</a>';

    echo display_error($ERRORSTR);
    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tListMessenger was unable to check for updates. fsockopen() is disabled.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
    }
}
