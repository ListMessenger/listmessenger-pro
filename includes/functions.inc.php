<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */

/*
 * If a table engine isn't defined in config.inc.php then define it here.
 */
if (!defined('TABLES_ENGINE')) {
    define('TABLES_ENGINE', 'MyISAM');
}

/*
 * If this is before PHP 4.3, then I suppose we'll have to make our own file_get_contents function.
 */
if (!function_exists('file_get_contents')) {
    function file_get_contents($filename, $use_include_path = 0)
    {
        $data = '';
        $handle = fopen($filename, 'rb', $use_include_path);
        if ($handle) {
            while (!feof($handle)) {
                $data .= fread($handle, 1024);
            }
            fclose($handle);
        }

        return $data;
    }
}

/*
 * If this is before PHP 5, then I suppose we'll have to make our own file_put_contents function.
 */
if (!function_exists('file_put_contents') && !defined('FILE_APPEND')) {
    define('FILE_APPEND', 1);

    function file_put_contents($n, $d, $flag = false)
    {
        $mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND') ? 'a' : 'w';
        $f = fopen($n, $mode);
        if ($f === false) {
            return 0;
        } else {
            if (is_array($d)) {
                $d = implode($d);
            }
            $bytes_written = fwrite($f, $d);
            fclose($f);

            return $bytes_written;
        }
    }
}

/*
 * This function is for PHP 4.1 users who do not have the ob_flush() function.
 */
if (!function_exists('ob_flush')) {
    function ob_flush()
    {
        return;
    }
}

/*
 * This function is for PHP4 users, where str_ireplace() does not exist.
 */
if (!function_exists('str_ireplace')) {
    function str_ireplace($search, $replace, $subject)
    {
        return str_replace($search, $replace, $subject);
    }
}

if (!function_exists('json_encode')) {
    function json_encode($a = false)
    {
        if (is_null($a)) {
            return 'null';
        }

        if ($a === false) {
            return 'false';
        }

        if ($a === true) {
            return 'true';
        }

        if (is_scalar($a)) {
            if (is_float($a)) {
                // Always use "." for floats.
                return floatval(str_replace(',', '.', strval($a)));
            }

            if (is_scalar($a)) {
                static $jsonReplaces = [['\\', '/', "\n", "\t", "\r", "\b", "\f", '"'], ['\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"']];

                return '"'.str_replace($jsonReplaces[0], $jsonReplaces[1], $a).'"';
            } else {
                return $a;
            }
        }

        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
            if (key($a) !== $i) {
                $isList = false;
                break;
            }
        }

        $result = [];

        if ($isList) {
            foreach ($a as $v) {
                $result[] = json_encode($v);
            }

            return '['.join(',', $result).']';
        } else {
            foreach ($a as $k => $v) {
                $result[] = json_encode($k).':'.json_encode($v);
            }

            return '{'.join(',', $result).'}';
        }
    }
}

/**
 * Function loads PHPSniff and checks to see if a Rich Text Editor can be loaded.
 */
function rte_loader()
{
    global $RTE_ENABLED;

    if ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) {
        if ((!empty($_SESSION['config'][PREF_USERTE])) && ($_SESSION['config'][PREF_USERTE] != 'disabled')) {
            $RTE_ENABLED = true;
        }
    }
}

/**
 * This function loads the code necessary to load the specified rich text editor.
 */
function rte_display($id = 'html_message', $options = [])
{
    global $HEAD, $ONLOAD;

    if (!$id) {
        $id = 'html_message';
    }

    $path_details = pathinfo(html_encode($_SERVER['PHP_SELF']));

    $_SESSION['config'][PREF_USERTE] = 'ckeditor';

    switch ($_SESSION['config'][PREF_USERTE]) {
        case 'disabled':
        case 'no':
            break;
        case 'ckeditor':
        default:
            if (!array_key_exists('ckeditor', $HEAD)) {
                $HEAD['ckeditor'] = "<script src=\"./javascript/wysiwyg/ckeditor/ckeditor.js\"></script>\n";
            }
            echo "<script>\n";
            echo "CKEDITOR.replace('".html_encode($id)."', {\n";
            echo "	docType : '<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">',\n";
            echo "	filebrowserBrowseUrl : 'javascript/filemanager/dialog.php?type=2&editor=ckeditor&fldr=',\n";
            echo "	filebrowserUploadUrl : 'javascript/filemanager/dialog.php?type=2&editor=ckeditor&fldr=',\n";
            echo "	filebrowserImageBrowseUrl : 'javascript/filemanager/dialog.php?type=1&editor=ckeditor&fldr=',\n";
            echo "});\n";
            echo "</script>\n";

            break;

            break;
    }
}

/**
 * Function is called by the output buffer upon completion.
 *
 * @param string $buffer
 *
 * @return string
 */
function on_complete($buffer)
{
    $output = check_onload($buffer);
    $output = check_sidebar($output);
    $output = check_head($output);
    $output = check_count($output);

    return $output;
}

/**
 * Function is called by on_complete function. It can add any onLoad events to the <body> tag.
 *
 * @param string $output
 *
 * @return string
 */
function check_onload($output)
{
    global $ONLOAD;

    if (!empty($ONLOAD) && is_array($ONLOAD) && !empty($ONLOAD)) {
        return str_replace('<body>', '<body onload="'.implode(', ', $ONLOAD).'">', $output);
    } else {
        return $output;
    }
}

/**
 * Function is called by on_complete. It can modify the sidebar with any specified content.
 *
 * @param string $output
 *
 * @return string
 */
function check_sidebar($output)
{
    global $SIDEBAR;

    if (!empty($SIDEBAR) && is_array($SIDEBAR) && !empty($SIDEBAR)) {
        $html = implode("\n", $SIDEBAR);
    } else {
        $html = '';
    }

    return str_replace('%SIDEBAR%', $html, $output);
}

/**
 * Function is called by on_complete. It can add contents to the <head> tags by replacing %HEAD%.
 *
 * @param string $output
 *
 * @return string
 */
function check_head($output)
{
    global $HEAD;

    if (!empty($HEAD) && is_array($HEAD) && !empty($HEAD)) {
        $html = implode("\n", $HEAD);
    } else {
        $html = '';
    }

    return str_replace('%HEAD%', $html, $output);
}

/**
 *  Function is called by on_complete. It's used to replace the total number of users on the page.
 *
 * @param string $output
 *
 * @return string
 */
function check_count($output)
{
    global $SUBSCRIBER_SUMMARY;

    if (empty($SUBSCRIBER_SUMMARY) || empty($SUBSCRIBER_SUMMARY[0])) {
        $SUBSCRIBER_SUMMARY = ['', 0];
    }

    return str_replace('%USERCOUNT%', $SUBSCRIBER_SUMMARY[0], $output);
}

/**
 * Function checks to ensure that the ListMessenger Licence key exists.
 *
 * @return bool
 */
function check_licence()
{
    return true;
}

/**
 * Function that checks to see if magic_quotes_gpc is enabled or not.
 *
 * Legacy: This is here for legacy purposes. It serves no function.
 *
 * @param string $value
 * @param int    $display
 *
 * @return string
 */
function checkslashes($value = '', $display = 0)
{
    return $value;
}

/**
 * This is a work around to allow the administrator to indicate that the
 * first and last name of the subscriber are required fields.
 *
 * @param string $field_type
 *
 * @return bool
 */
function check_required($field_type = 'firstname')
{
    global $db;

    switch ($field_type) {
        case 'lastname':
            $query = 'SELECT * FROM `'.TABLES_PREFIX.'preferences` WHERE `preference_id` = '.$db->qstr((int) ENDUSER_REQUIRE_LASTNAME);
            $result = $db->GetRow($query);
            if ($result && ($result['preference_value'] == 'yes')) {
                return true;
            }
            break;
        case 'firstname':
        default:
            $query = 'SELECT * FROM `'.TABLES_PREFIX.'preferences` WHERE `preference_id` = '.$db->qstr((int) ENDUSER_REQUIRE_FIRSTNAME);
            $result = $db->GetRow($query);
            if ($result && ($result['preference_value'] == 'yes')) {
                return true;
            }
            break;
    }

    return false;
}

/**
 * Function checks to ensure the e-mail address is valid.
 *
 * @param string $address
 *
 * @return bool
 */
function valid_address($address = '', $mode = 0)
{
    switch ((int) $mode) {
        case 2:	// Strict
            $regex = "/^([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i";
            break;
        case 1:	// Promiscuous
            $regex = "/^([*+!.&#$|\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i";
            break;
        default:	// Recommended
            $regex = "/^([*+!.&#$|0-9a-z^_=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i";
            break;
    }

    if (preg_match($regex, trim($address))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Function tests to ensure the e-mail address is not in the provided banned list.
 * Cudos to the phpBB developers for the concept behind included preg_match().
 *
 * @param string $address
 * @param array  $banned_emails
 *
 * @return bool
 */
function banned_address($address = '', $banned_emails = [])
{
    if (is_scalar($banned_emails) && (strlen($banned_emails) > 3)) {
        $banned_emails = explode(';', $banned_emails);
    }

    if ($address && is_array($banned_emails)) {
        foreach ($banned_emails as $ban) {
            if (preg_match('#^'.str_replace("\*", '.*?', preg_quote($ban, '#')).'$#i', $address)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Function tests to ensure the ip address is not in the provided banned list.
 * Cudos to the phpBB developers for the concept behind included preg_match().
 *
 * @param string $address
 * @param array  $banned_ips
 *
 * @return bool
 */
function banned_ip($address = '', $banned_ips = [])
{
    if (is_scalar($banned_ips)) {
        $banned_ips = explode(';', $banned_ips);
    }

    if ($address && is_array($banned_ips)) {
        foreach ($banned_ips as $ban) {
            if (preg_match('#^'.str_replace("\*", '.*?', preg_quote($ban, '#')).'$#i', $address)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Removes default linebreaks.
 *
 * @param string $string
 *
 * @return string
 */
function remove_linebreaks($string = '')
{
    return str_replace(["\n", "\r"], '', $string);
}

/**
 * Shows the total number of subscribers in all groups.
 *
 * @return array
 */
function total_subscribers()
{
    global $db;

    $message = 'No Subscribers Present';
    $count = 0;

    if (!empty($db) && $db->IsConnected()) {
        $query = 'SELECT COUNT(*) AS `total` FROM `'.TABLES_PREFIX.'users`';
        $result = $db->GetRow($query);
        if ($result && ($subscribers = (int) $result['total'])) {
            $message = $subscribers.' Subscriber'.(($subscribers != 1) ? 's' : '');
            $count = $subscribers;
        }
    }

    return ['<span class="small-grey">'.$message.'</span>', $count];
}

/**
 * Function Loads / Reloads configuration into session array.
 *
 * @param bool $reload
 *
 * @return bool
 */
function load_configuration($reload = false)
{
    global $db;

    if ($reload) {
        unset($_SESSION['config']);
    }

    if (empty($_SESSION['config'])) {
        $query = 'SELECT * FROM `'.TABLES_PREFIX.'preferences`';
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $_SESSION['config'][$result['preference_id']] = $result['preference_value'];
            }

            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}

/**
 * Function will reload the session configuration.
 *
 * @return bool
 */
function reload_configuration()
{
    return load_configuration(true);
}

/**
 * Function Loads the end-user tools configuration into an array.
 *
 * @return array
 */
function enduser_configuration()
{
    global $db;

    $config = [];
    $query = 'SELECT * FROM `'.TABLES_PREFIX.'preferences`';
    $results = $db->GetAll($query);
    if ($results) {
        foreach ($results as $result) {
            switch ($result['preference_id']) {
                case PREF_FRMNAME_ID:
                case PREF_FRMEMAL_ID:
                case PREF_RPYEMAL_ID:
                case PREF_ABUEMAL_ID:
                case PREF_ADMEMAL_ID:
                case PREF_ERREMAL_ID:
                case PREF_PROPATH_ID:
                case PREF_PRIVATE_PATH:
                case PREF_PUBLIC_URL:
                case PREF_PUBLIC_PATH:
                case PREF_DEFAULT_CHARSET:
                case PREF_ENCODING_STYLE:
                case PREF_DATEFORMAT:
                case PREF_ERROR_LOGGING:
                case PREF_TIMEZONE:
                case PREF_DAYLIGHT_SAVINGS:
                case PREF_POSTSUBSCRIBE_MSG:
                case PREF_POSTUNSUBSCRIBE_MSG:
                case PREF_PERPAGE_ID:
                case ENDUSER_UNSUBCON:
                case ENDUSER_SUBCON:
                case ENDUSER_NEWSUBNOTICE:
                case ENDUSER_UNSUBNOTICE:
                case ENDUSER_FORWARD:
                case PREF_FOPEN_URL:
                case ENDUSER_MXRECORD:
                case ENDUSER_ARCHIVE:
                case ENDUSER_PROFILE:
                case ENDUSER_LANG_ID:
                case PREF_EXPIRE_CONFIRM:
                case ENDUSER_ARCHIVE_FILENAME:
                case ENDUSER_PROFILE_FILENAME:
                case ENDUSER_CONFIRM_FILENAME:
                case ENDUSER_HELP_FILENAME:
                case ENDUSER_FILENAME:
                case ENDUSER_TEMPLATE:
                case ENDUSER_UNSUB_FILENAME:
                case ENDUSER_FORWARD_FILENAME:
                case ENDUSER_CAPTCHA:
                case ENDUSER_AUDIO_CAPTCHA:
                case ENDUSER_FLITE_PATH:
                case PREF_WORDWRAP:
                case PREF_MAILER_BY_ID:
                case PREF_MAILER_BY_VALUE:
                case PREF_MAILER_AUTH_ID:
                case PREF_MAILER_AUTHUSER_ID:
                case PREF_MAILER_AUTHPASS_ID:
                case PREF_ADD_UNSUB_LINK:
                case PREF_ADD_UNSUB_GROUP:
                case REG_SERIAL:
                case PREF_MAILER_SMTP_KALIVE:
                case PREF_MAILER_LE:
                case PREF_MAILER_INC_NAME:
                    $config[$result['preference_id']] = $result['preference_value'];
                    break;
                case ENDUSER_BANEMAIL:
                case PREF_POSTSUBSCRIBE_MSG:
                case PREF_POSTUNSUBSCRIBE_MSG:
                case ENDUSER_BANIPS:
                    if ($tmp_preference_value = clean_input($result['preference_value'], 'nows')) {
                        $config[$result['preference_id']] = explode(';', $tmp_preference_value);
                    } else {
                        $config[$result['preference_id']] = [];
                    }
                    break;
            }
        }

        return $config;
    } else {
        return false;
    }
}

/**
 * Function will retrieve the template file and return the HTML as a string if it's found, false if not.
 *
 * @return template file contents
 */
function get_template($requested_template = '')
{
    global $config;

    /**
     * Default template file in ListMessenger.
     */
    $template_filename = (($config[PREF_FOPEN_URL] == 'yes') ? $config[PREF_PUBLIC_URL] : $config[PREF_PUBLIC_PATH]).$config[ENDUSER_TEMPLATE];

    /*
     * Check if a specific template is being requested, if it is then construct
     * the filename based on the default template filename. For example, if the
     * default template file is set to template.html, and you pass this function
     * "newdesign" i.e. get_template("newdesign"), then you need to have a file
     * named: template-newdesign.html in your public directory.
     *
     * Another example:
     * If your default template is set to custom.php, you would need a
     * custom-newdesign.php file in your public directory.
     */
    if (($requested_template != '') && ($requested_template = clean_input($requested_template, ['trim', 'lowercase', 'filename']))) {
        $template_filename_parts = pathinfo($template_filename);

        if (is_array($template_filename_parts)) {
            if (version_compare(phpversion(), '5.2.0', '<')) {
                if ($template_filename_parts['extension']) {
                    $template_filename_parts['filename'] = substr($template_filename_parts['basename'], 0, strlen($template_filename_parts['basename']) - strlen($template_filename_parts['extension']) - 1);
                }
            }

            if (is_array($template_filename_parts) && $template_filename_parts['dirname'] && $template_filename_parts['filename'] && $template_filename_parts['extension']) {
                $template_filename = $template_filename_parts['dirname'].'/'.$template_filename_parts['filename'].'-'.$requested_template.'.'.$template_filename_parts['extension'];
            }
        }
    }

    return file_get_contents($template_filename);
}

/**
 * Function to properly format the error messages for consistency.
 *
 * @param array $errorstr
 *
 * @return string
 */
function display_error($errorstr = [])
{
    $output = '';

    if (is_array($errorstr)) {
        if ($errors = count($errorstr)) {
            $output .= "<div class=\"error-message\">\n";
            $output .= "	<ul>\n";
            foreach ($errorstr as $message) {
                $output .= '	<li>'.$message."</li>\n";
            }
            $output .= "	</ul>\n";
            $output .= "</div>\n";
        }
    }

    return $output;
}

/**
 * Function to properly format the notice messages for consistency.
 *
 * @param array $noticestr
 *
 * @return string
 */
function display_notice($noticestr = [])
{
    $output = '';

    if (is_array($noticestr)) {
        if ($notices = count($noticestr)) {
            $output .= "<div class=\"notice-message\">\n";
            $output .= "	<ul>\n";
            foreach ($noticestr as $message) {
                $output .= '	<li>'.$message."</li>\n";
            }
            $output .= "	</ul>\n";
            $output .= "</div>\n";
        }
    }

    return $output;
}

/**
 * Function to properly format the success messages for consistency.
 *
 * @param array $successstr
 *
 * @return string
 */
function display_success($successstr = [])
{
    $output = '';

    if (is_array($successstr)) {
        if ($success = count($successstr)) {
            $output .= "<div class=\"success-message\">\n";
            $output .= "	<ul>\n";
            foreach ($successstr as $message) {
                $output .= '	<li>'.$message."</li>\n";
            }
            $output .= "	</ul>\n";
            $output .= "</div>\n";
        }
    }

    return $output;
}

/**
 * Handy function that takes the QUERY_STRING and adds / modifies / removes elements from it
 * based on the $modify array that is provided.
 *
 * @param array $modify
 *
 * @return string
 *
 * @example echo "index.php?".replace_query(array("action" => "add", "step" => 2));
 */
function replace_query($modify = [], $html_encode_output = false)
{
    $process = [];
    $tmp_string = [];
    $new_query = '';

    // Checks to make sure there is something to modify, else just returns the string.
    if (count($modify) > 0) {
        $original = explode('&', $_SERVER['QUERY_STRING']);
        if (count($original) > 0) {
            foreach ($original as $value) {
                $pieces = explode('=', $value);
                // Gets rid of any unset variables for the URL.
                if (!empty($pieces[0]) && !empty($pieces[1])) {
                    $process[$pieces[0]] = $pieces[1];
                }
            }
        }

        foreach ($modify as $key => $value) {
            // If the variable already exists, replace it, else add it.
            if (array_key_exists($key, $process)) {
                if (($value === 0) || ($value && ($value != ''))) {
                    $process[$key] = $value;
                } else {
                    unset($process[$key]);
                }
            } else {
                if (($value === 0) || ($value && ($value != ''))) {
                    $process[$key] = $value;
                }
            }
        }
        if (count($process) > 0) {
            foreach ($process as $var => $value) {
                $tmp_string[] = $var.'='.$value;
            }
            $new_query = implode('&', $tmp_string);
        } else {
            $new_query = '';
        }
    } else {
        $new_query = $_SERVER['QUERY_STRING'];
    }

    return ((bool) $html_encode_output) ? html_encode($new_query) : $new_query;
}

// Function to generate the heading links for the User Directory.
function order_link($field, $name, $order, $sort, $islink = true, $att_window = false)
{
    if ($islink) {
        if (strtolower($sort) == strtolower($field)) {
            if (strtolower($order) == 'desc') {
                return '&nbsp;<img src="./images/sort-asc.gif" width="9" height="9" alt="Ordered By '.$name.'">&nbsp;<a href="'.(($att_window) ? 'attachments.php' : 'index.php').'?'.replace_query(['sort' => $field, 'order' => 'asc']).'" class="theading-on" title="Order by '.$name.' &amp; Sort Ascending">'.$name."</a>\n";
            } else {
                return '&nbsp;<img src="./images/sort-desc.gif" width="9" height="9" alt="Ordered By '.$name.'">&nbsp;<a href="'.(($att_window) ? 'attachments.php' : 'index.php').'?'.replace_query(['sort' => $field, 'order' => 'desc']).'" class="theading-on" title="Order by '.$name.' &amp; Sort Decending">'.$name."</a>\n";
            }
        } else {
            return '<a href="'.(($att_window) ? 'attachments.php' : 'index.php').'?'.replace_query(['sort' => $field]).'" class="theading-off" title="Order by '.$name.'">'.$name."</a>&nbsp;<img src=\"./images/pixel.gif\" width=\"9\" height=\"9\" alt=\"\">\n";
        }
    } else {
        return '<span class="theading-off">'.$name."</span>\n";
    }
}

// Function to chop off a string at the given maximum character length.
function limit_chars($string, $chars)
{
    $length = strlen($string);
    if ($length <= $chars) {
        return $string;
    } else {
        return substr(str_pad($string, $chars), 0, $chars - 3).'...';
    }
}

// Function that will set the date & time format accordingly for a JavaScript-Set cookie.
function javascript_cookie()
{
    return gmdate('D, j M Y H:i T', PREF_COOKIE_TIMEOUT);
}

// Function will return all groups below the specified parent_id, as option elements of an input select.
function groups_inselect($parent_id, $current_selected = [], $indent = 0, $exclude = [])
{
    global $db;

    if ($indent > 99) {
        exit('Preventing infinite loop');
    }

    $query = 'SELECT `groups_id`, `group_name`, `group_parent` FROM `'.TABLES_PREFIX.'groups` WHERE `group_parent` = '.$db->qstr((int) $parent_id);
    $results = $db->GetAll($query);

    $output = '';
    foreach ($results as $result) {
        if ((!in_array($result['groups_id'], $exclude)) && (!in_array($parent_id, $exclude))) {
            $output .= '<option value="'.$result['groups_id'].'"'.((is_array($current_selected)) ? ((in_array($result['groups_id'], $current_selected)) ? ' selected="selected"' : '') : '').'>'.str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $indent).(($indent > 0) ? '&rarr;&nbsp;' : '').$result['group_name']."</option>\n";
        } else {
            $exclude[] = $result['groups_id'];
        }
        $output .= groups_inselect($result['groups_id'], $current_selected, $indent + 1, $exclude);
    }

    return $output;
}

// Function will return all groups below the specified parent_id, as table rows of a table.
function groups_intable($parent_id, $indent = 0)
{
    global $db;

    if ($indent > 99) {
        exit('Preventing infinite loop');
    }

    $query = 'SELECT * FROM `'.TABLES_PREFIX.'groups` WHERE `group_parent` = '.$db->qstr($parent_id);
    $results = $db->GetAll($query);
    $output = '';

    foreach ($results as $result) {
        $subscribers = users_count($result['groups_id']);

        $output .= "<tr onmouseout=\"this.style.backgroundColor='#FFFFFF'\" onmouseover=\"this.style.backgroundColor='#F0FFD1'\">\n";
        $output .= '	<td style="width: 40px"><a href="./index.php?section=manage-groups&action=edit&id='.$result['groups_id'].'"><img src="./images/icon-edit-groups.gif" width="16" height="16" border="0" alt="Edit" title="Edit '.$result['group_name'].'" /></a>&nbsp;<a href="./index.php?section=manage-groups&action=delete&id='.$result['groups_id'].'"><img src="./images/icon-del-groups.gif" width="16" height="16" border="0" alt="Delete" title="Delete '.$result['group_name']."\" /></a></td>\n";
        $output .= '	<td style="overflow: hidden">'.str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $indent).'<img src="./images/record-next-off.gif" width="9" height="9" border="0" alt="View" />&nbsp;<a href="./index.php?section=subscribers&g='.$result['groups_id'].'">'.$result['group_name']."</a></td>\n";
        $output .= '	<td class="small-grey">Group ID: '.$result['groups_id']."</td>\n";
        $output .= '	<td class="small-grey">'.$subscribers.' Subscriber'.(($subscribers != 1) ? 's' : '')."</td>\n";
        $output .= '	<td class="small-grey">'.(($result['group_private'] == 'true') ? 'Private Group' : 'Public Group')."</td>\n";
        $output .= "</tr>\n";
        $output .= groups_intable($result['groups_id'], $indent + 1);
    }

    return $output;
}

// Function will return all groups below the specified parent_id, as nested list items of a list.
function groups_inlist($parent_id, $indent = 0)
{
    global $db;

    if ($indent > 99) {
        exit('Preventing infinite loop');
    }

    $query = 'SELECT `groups_id`, `group_name`, `group_parent` FROM `'.TABLES_PREFIX."groups` WHERE `group_parent`='".$parent_id."'";
    $results = $db->GetAll($query);

    $output = '<ul>';
    foreach ($results as $result) {
        $output .= '<li>'.$result['group_name'].'</li>';
        $output .= groups_inlist($result['groups_id'], $indent + 1);
    }

    return $output.'</ul>';
}

// Function will return all group ID's below the specified parent_id, as an array.
function groups_inarray(int $parent_id, &$groups, $level = 0)
{
    global $db;

    if ($level > 99) {
        exit('Preventing infinite loop');
    }

    $query = 'SELECT * FROM `'.TABLES_PREFIX.'groups` WHERE `group_parent` = '.$db->qstr((int) $parent_id);
    $results = $db->GetAll($query);
    if ($results) {
        foreach ($results as $result) {
            $groups[] = $result['groups_id'];

            groups_inarray($result['groups_id'], $groups, $level + 1);
        }
    }

    return $groups;
}

// Function will return the number of sub-groups under the ID you specify.
function groups_count(int $parent_id, &$groups, $level = 0)
{
    global $db;

    if ($level > 99) {
        exit('Preventing infinite loop');
    }

    $query = 'SELECT `groups_id` FROM `'.TABLES_PREFIX."groups` WHERE `group_parent`='".$parent_id."'";
    $results = $db->GetAll($query);
    if ($results) {
        foreach ($results as $result) {
            ++$groups;
            groups_count($result['groups_id'], $groups, $level + 1);
        }
    }

    return $groups;
}

// Function will return name, parent ID, and login status of the id(s) you specify.
function groups_information($group_ids = [], $name_only = false, $output_string = true)
{
    global $db;

    $output = [];

    if (!is_array($group_ids)) {
        $group_ids = [$group_ids];
    }

    if (count($group_ids)) {
        if (!$name_only) {
            foreach ($group_ids as $group_id) {
                if ((int) $group_id) {
                    $query = 'SELECT * FROM `'.TABLES_PREFIX."groups` WHERE `groups_id`='".$group_id."'";
                    $result = $db->GetRow($query);
                    if ($result) {
                        $output[$result['groups_id']] = [
                                                    'name' => $result['group_name'],
                                                    'parent' => $result['group_parent'],
                                                    'private' => $result['group_private'],
                                                    ];
                    } else {
                        $output[$group_id] = false;
                    }
                }
            }
        } else {
            foreach ($group_ids as $group_id) {
                if ((int) $group_id) {
                    $query = 'SELECT `group_name` FROM `'.TABLES_PREFIX.'groups` WHERE `groups_id`='.$db->qstr((int) $group_id);
                    $result = $db->GetRow($query);
                    if ($result) {
                        $output[] = $result['group_name'];
                    }
                }
            }

            if ($output_string) {
                if (empty($output)) {
                    return '';
                } elseif (count($output) == 1) {
                    return $output[0];
                }
            }
        }
    }

    return $output;
}

// Function will delete all groups below the specified parent_id. It will also call users_remove to remove all users in the groups it deletes.
function groups_delete($parent_id = 0, $level = 0, $del_users = false)
{
    global $db;

    if ($level > 99) {
        exit('Preventing infinite loop');
    }

    $query = 'SELECT `groups_id` FROM `'.TABLES_PREFIX.'groups` WHERE `group_parent` = '.$db->qstr($parent_id);
    $results = $db->GetAll($query);

    foreach ($results as $result) {
        $query = 'DELETE FROM `'.TABLES_PREFIX.'groups` WHERE `groups_id` = '.$db->qstr($parent_id);
        $db->Execute($query);
        if ($del_users) {
            users_delete($result['groups_id']);
        }

        groups_delete($result['groups_id'], $level + 1, $del_users);
    }

    return true;
}

/**
 * Function will move the groups with the $from_id, to the $to_id.
 *
 * @param int $from_id
 * @param int $to_id
 *
 * @return bool
 */
function groups_move($from_id = 0, $to_id = 0)
{
    global $db;

    if (($from_id = (int) $from_id) && ($to_id = (int) $to_id)) {
        $query = 'UPDATE `'.TABLES_PREFIX.'groups` SET `group_parent` = '.$db->qstr($to_id).' WHERE `group_parent` = '.$db->qstr($from_id);
        if ($db->Execute($query)) {
            return true;
        }
    }

    return false;
}

/**
 * Will return either an array or a formatted string of the provide $group_id's
 * hierarchy.
 *
 * @example Grandparent > Parent > Child
 *
 * @param int $group_id
 *
 * @return array or string
 */
function groups_hierarchy($group_id = 0, $format_output = false)
{
    $groups_ouput = [];
    $groups = [];

    groups_inarray($group_id, $groups);

    if (is_array($groups) && (count($groups) > 1)) {
        $groups = array_reverse($groups);
    } else {
        $groups = [$group_id];
    }

    $groups_output = groups_information($groups, true, false);

    return ($format_output) ? implode(' &gt; ', $groups_output) : $groups_output;
}

// Function will add the user including custom data to the groups provided.
function users_add($email_address = '', $firstname = '', $lastname = '', $groups_array = [], $custom_data = [], &$config = [])
{
    global $db;

    if (is_array($groups_array) && !empty($groups_array)) {
        $success = 0;
        $failed = 0;
        $semi = 0;

        foreach ($groups_array as $group_id) {
            $query = 'SELECT * FROM `'.TABLES_PREFIX.'users` WHERE `group_id` = '.$db->qstr((int) $group_id).' AND `email_address` = '.$db->qstr(checkslashes($email_address));
            $result = $db->GetRow($query);
            if (!$result) {
                $query = 'INSERT INTO `'.TABLES_PREFIX."users` VALUES (NULL, '".(int) $group_id."', '".time()."', ".$db->qstr($firstname).', '.$db->qstr($lastname).', '.$db->qstr($email_address).');';
                if ($db->Execute($query) && ($user_id = $db->Insert_Id())) {
                    ++$success;

                    /*
                     * Stores users custom field data.
                     */
                    custom_data_store($user_id, $custom_data, $config);
                } else {
                    ++$failed;
                    if ($config[PREF_ERROR_LOGGING] == 'yes') {
                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to insert user data into subscriber table. Database server said: ".$db->ErrorMsg()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                    }
                }
            }
        }

        if ($success && $config[PREF_POSTSUBSCRIBE_MSG]) {
            if (!send_post_action_message('subscribe', [$user_id], $config)) {
                if ($config[PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send new subscriber the post-subscribe message.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            }
        }

        return ['success' => $success, 'semi' => $semi, 'failed' => $failed];
    } else {
        if ($config[PREF_ERROR_LOGGING] == 'yes') {
            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThere were no groups provided to the users_add function.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
        }

        return false;
    }
}

function send_post_action_message($action = 'subscribe', $subscriber_ids = [], &$config = [])
{
    global $db;

    if (is_scalar($subscriber_ids)) {
        $subscriber_ids = [$subscriber_ids];
    }

    if (is_array($subscriber_ids) && !empty($subscriber_ids)) {
        $message_id = (int) (($action == 'subscribe') ? $config[PREF_POSTSUBSCRIBE_MSG] : $config[PREF_POSTUNSUBSCRIBE_MSG]);
        if ($message_id) {
            $query = 'SELECT * FROM `'.TABLES_PREFIX.'messages` WHERE `message_id`='.$db->qstr($message_id);
            $result = $db->GetRow($query);
            if ($result) {
                try {
                    $mail = new LM_Mailer($config);
                    $mail->Priority = $result['message_priority'];

                    $from_pieces = explode('" <', $result['message_from']);
                    $mail->From = substr($from_pieces[1], 0, strlen($from_pieces[1]) - 1);
                    $mail->FromName = substr($from_pieces[0], 1, strlen($from_pieces[0]));

                    $reply_pieces = explode('" <', $result['message_reply']);
                    $mail->AddReplyTo(substr($reply_pieces[1], 0, strlen($reply_pieces[1]) - 1), substr($reply_pieces[0], 1, strlen($reply_pieces[0])));

                    $subject = $result['message_subject'];

                    $html_template = $result['html_template'];
                    $html_message = $result['html_message'];

                    $text_template = $result['text_template'];
                    $text_message = $result['text_message'];

                    // Look for attachments on this message, if they're there and valid, attach them.
                    if ($result['attachments'] != '') {
                        $attachments = unserialize($result['attachments']);
                        if (is_array($attachments) && (count($attachments) > 0)) {
                            foreach ($attachments as $filename) {
                                if (file_exists($config[PREF_PUBLIC_PATH].'files/'.str_replace(['..', '/', '\\'], '', $filename))) {
                                    $mail->AddAttachment($config[PREF_PUBLIC_PATH].'files/'.str_replace(['..', '/', '\\'], '', $filename));
                                }
                            }
                        }
                    }

                    foreach ($subscriber_ids as $subscriber_id) {
                        $user_data = get_custom_data($subscriber_id, ['messageid' => $message_id], $config);

                        if (is_array($user_data) && (count($user_data) > 0) && valid_address($user_data['email'])) {
                            $mail->ClearCustomHeaders();
                            $mail->AddCustomHeader('List-Help: <'.$config[PREF_PUBLIC_URL].$config[ENDUSER_HELP_FILENAME].'>');
                            $mail->AddCustomHeader('List-Owner: <mailto:'.$mail->From.'> ('.$mail->FromName.')');
                            $mail->AddCustomHeader('List-Unsubscribe: <'.$config[PREF_PUBLIC_URL].$config[ENDUSER_UNSUB_FILENAME].'?addr='.$user_data['email'].'>');
                            $mail->AddCustomHeader('List-Archive: <'.$config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME].'>');
                            $mail->AddCustomHeader('List-Post: NO');

                            $mail->Subject = custom_data($user_data, $subject);

                            if (strlen(trim($html_message)) > 0) {
                                $mail->Body = custom_data($user_data, unsubscribe_message(insert_template('html', $html_template, $html_message), 'html', $config));
                                $mail->AltBody = custom_data($user_data, unsubscribe_message(insert_template('text', $text_template, $text_message), 'text', $config));
                            } else {
                                $mail->Body = custom_data($user_data, unsubscribe_message(insert_template('text', $text_template, $text_message), 'text', $config));
                            }

                            $mail->ClearAllRecipients();
                            $mail->AddAddress(trim($user_data['email']), $user_data['name']);

                            if ((!$mail->IsError()) && $mail->Send()) {
                                $sent_msg = true;
                            } else {
                                if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send post-".$action.' message to '.$user_data['email'].'. LM_Mailer responded: '.$mail->ErrorInfo."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                                }

                                throw new Exception('Unable to send post-'.$action.' message to '.$user_data['email'].'. LM_Mailer responded: '.$mail->ErrorInfo);
                            }
                        }
                    }
                } catch (Exception $e) {
                    $sent_msg = false;
                }

                return ($sent_msg) ? true : false;
            }
        }
    }

    return false;
}

// Function will return the number of users is the specified group.
function users_count($id)
{
    global $db;

    if ($id) {
        $query = 'SELECT COUNT(*) AS `total` FROM `'.TABLES_PREFIX."users` WHERE `group_id`='".$id."'";
        $result = $db->GetRow($query);
        if ($result) {
            return $result['total'];
        } else {
            return false;
        }
    } else {
        return false;
    }
}

// Function will remove all users in the specified group as well as their custom field data.
function users_delete($group_id)
{
    global $db;

    if ((int) $group_id) {
        $query = 'SELECT GROUP_CONCAT(DISTINCT `users_id`) AS `user_ids` FROM `'.TABLES_PREFIX.'users` WHERE `group_id`='.$db->qstr($group_id);
        $result = $db->GetRow($query);

        if ($result && !empty($result['user_ids'])) {
            $db->Execute('DELETE FROM `'.TABLES_PREFIX.'users` WHERE `users_id` IN ('.$result['user_ids'].')');
            $db->Execute('DELETE FROM `'.TABLES_PREFIX.'cdata` WHERE `user_id` IN ('.$result['user_ids'].')');
        }

        return true;
    } else {
        return false;
    }
}

// Function will remove all users in the users_array as well as their custom field data.
function users_delete_list($users_array)
{
    global $db;

    if (is_array($users_array) && (count($users_array) > 0)) {
        $total_rows = 0;
        foreach ($users_array as $users_id) {
            ++$total_rows;
            $db->Execute('DELETE FROM `'.TABLES_PREFIX.'users` WHERE `users_id`='.$db->qstr($users_id));
            $db->Execute('DELETE FROM `'.TABLES_PREFIX.'cdata` WHERE `user_id`='.$db->qstr($users_id));
        }
        $db->Execute('OPTIMIZE TABLE `'.TABLES_PREFIX.'users`, `'.TABLES_PREFIX.'cdata`');

        return $total_rows;
    } else {
        return false;
    }
}

// Function will move all users in $from_id, to $to_id.
function users_move($from_id, $to_id)
{
    global $db;

    if (((int) $from_id) && ((int) $to_id)) {
        $query = 'SELECT `users_id`, `email_address` FROM `'.TABLES_PREFIX."users` WHERE `group_id`='".checkslashes($from_id)."'";
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $user_id = $result['users_id'];

                $squery = 'SELECT `users_id` FROM `'.TABLES_PREFIX."users` WHERE `group_id`='".checkslashes($to_id)."' AND `email_address`='".$result['email_address']."'";
                $sresult = $db->GetRow($squery);
                if ($sresult && ((int) $user_id)) {
                    $db->Execute('DELETE FROM `'.TABLES_PREFIX."users` WHERE `users_id`='".$user_id."'");
                    $db->Execute('DELETE FROM `'.TABLES_PREFIX."cdata` WHERE `user_id`='".$user_id."'");
                }
            }
        }

        $query = 'UPDATE `'.TABLES_PREFIX."users` SET `group_id`='".$to_id."' WHERE `group_id`='".$from_id."'";

        return ($db->Execute($query)) ? true : false;
    } else {
        return false;
    }
}

// Function will queue the subscribe / unsubscribe confirmation queue including custom data to the groups provided.
function users_queue($email_address, $firstname, $lastname, $groups_array, $custom_data = [], $queue_type = 'adm-subscribe')
{
    global $db;

    if (count($groups_array) > 0) {
        $hash = md5(uniqid(rand(), 1));
        $query = 'INSERT INTO `'.TABLES_PREFIX."confirmation` VALUES (NULL, '".time()."', '".addslashes($queue_type)."', '".addslashes($_SERVER['REMOTE_ADDR'])."', '".addslashes($_SERVER['HTTP_REFERER'])."', '".addslashes($_SERVER['HTTP_USER_AGENT'])."', ".$db->qstr($email_address).', '.$db->qstr($firstname).', '.$db->qstr($lastname).", '".addslashes(serialize($groups_array))."', '".addslashes(serialize($custom_data))."', '".$hash."', '0');";
        if ($db->Execute($query)) {
            return ['confirm_id' => $db->Insert_Id(), 'hash' => $hash];
        } else {
            if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to insert subscriber into the confirmation queue. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
            }

            return false;
        }
    } else {
        if (!empty($_SESSION['config'][PREF_ERROR_LOGGING]) && $_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThere were no groups provided to the users_queue function.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
        }

        return false;
    }
}

// Function will remove the subscriber including custom data from the groups provided.
function subscriber_remove($subscriber_ids = [], &$config = [])
{
    global $db;

    if (is_scalar($subscriber_ids)) {
        $subscriber_ids = [$subscriber_ids];
    }

    if (is_array($subscriber_ids) && !empty($subscriber_ids)) {
        // Send Post Unsubscribe Messages first because if you send them after, you won't have any of the information ;)
        if ($config[PREF_POSTUNSUBSCRIBE_MSG]) {
            if (!send_post_action_message('unsubscribe', $subscriber_ids, $config)) {
                if ($config[PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to send new subscriber the post-subscribe message.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            }
        }

        foreach ($subscriber_ids as $subscriber_id) {
            if ($subscriber_id = (int) $subscriber_id) {
                $query = 'DELETE FROM `'.TABLES_PREFIX.'users` WHERE `users_id`='.$db->qstr($subscriber_id);
                if ($db->Execute($query)) {
                    $query = 'DELETE FROM `'.TABLES_PREFIX.'cdata` WHERE `user_id`='.$db->qstr($subscriber_id);
                    if (!$db->Execute($query)) {
                        if ($config[PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete custom field data for subscriber id [".$subscriber_id.']. Database server said: '.$db->ErrorMsg()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    }
                } else {
                    if ($config[PREF_ERROR_LOGGING] == 'yes') {
                        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to delete subscriber id [".$subscriber_id.'] from the users table. Database server said: '.$db->ErrorMsg()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                    }
                }
            }
        }

        return true;
    } else {
        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tThere were no groups provided to the users_add function.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
        }
    }

    return false;
}

/**
 * This is the default HTML used to generate a CAPTCHA image.
 * If you want to modify this go ahead, or you can just modify the output.
 *
 * @param string $url
 *
 * @return string
 */
function generate_captcha_html($url = '', $form_label = 'Security Code')
{
    $html = "<tr>\n";
    $html .= "	<td style=\"padding-top: 15px\">&nbsp;</td>\n";
    $html .= "	<td style=\"padding-top: 15px\">\n";
    $html .= '		<img src="'.(($url != '') ? $url : '%URL%')."?action=captcha\" width=\"172\" height=\"45\" alt=\"CAPTCHA Image\" title=\"CAPTCHA Image\" />\n";
    $html .= "	</td>\n";
    $html .= "</tr>\n";
    $html .= "<tr>\n";
    $html .= "	<td style=\"padding-bottom: 15px\">\n";
    $html .= '		<label for="captcha_code" class="required">'.html_encode($form_label)."</label>\n";
    $html .= "	</td>\n";
    $html .= "	<td style=\"padding-bottom: 15px\">\n";
    $html .= "		<input type=\"text\" id=\"captcha_code\" name=\"captcha_code\" value=\"\" autocomplete=\"off\" style=\"width: 170px\" />\n";
    $html .= "	</td>\n";
    $html .= "</tr>\n";

    return $html;
}

// Function will generate the proper HTML to display custom form field. If you want it to output the HTML, then set $output to html.
function generate_cfields($action = '', $output = 'display', $cfields_id = 0)
{
    global $db;

    $html = '';

    if ((int) $cfields_id) {
        $query = 'SELECT * FROM `'.TABLES_PREFIX.'cfields` WHERE `cfields_id` = '.$db->qstr((int) $cfields_id).' ORDER BY `field_order` ASC';
    } else {
        $query = 'SELECT * FROM `'.TABLES_PREFIX.'cfields` ORDER BY `field_order` ASC';
    }
    $results = $db->GetAll($query);

    $html .= '<form'.(($action != '') ? ' action="'.$action.'"' : '')." method=\"post\">\n";
    $html .= "<input type=\"hidden\" name=\"group_ids[]\" value=\"ENTER_GROUP_ID_HERE\" />\n";
    $html .= "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\" summary=\"Subscriber Form\">\n";
    $html .= "<tbody>\n";
    if (!(int) $cfields_id) {
        $firstname_required = check_required('firstname');
        $lastname_required = check_required('lastname');

        $html .= "\t<tr>\n";
        $html .= "\t\t<td><label for=\"email_address\" style=\"color: #CC0000\">E-Mail Address</label></td>\n";
        $html .= "\t\t<td><input type=\"text\" id=\"email_address\" name=\"email_address\" value=\"\" maxlength=\"128\" /></td>\n";
        $html .= "\t</tr>\n";
        $html .= "\t<tr>\n";
        $html .= "\t\t<td><label for=\"firstname\"".(($firstname_required) ? ' style="color: #CC0000"' : '').">Firstname</label></td>\n";
        $html .= "\t\t<td><input type=\"text\" id=\"firstname\" name=\"firstname\" value=\"\" maxlength=\"32\" /></td>\n";
        $html .= "\t</tr>\n";
        $html .= "\t<tr>\n";
        $html .= "\t\t<td><label for=\"lastname\"".(($lastname_required) ? ' style="color: #CC0000"' : '').">Lastname</label></td>\n";
        $html .= "\t\t<td><input type=\"text\" id=\"lastname\" name=\"lastname\" value=\"\" maxlength=\"32\" /></td>\n";
        $html .= "\t</tr>\n";
    }
    if ($results) {
        foreach ($results as $result) {
            if ($result['field_type'] == 'linebreak') {
                $html .= "\t<tr>\n";
                $html .= "\t\t<td colspan=\"2\">&nbsp;</td>\n";
                $html .= "\t</tr>\n";
            } else {
                $html .= "\t<tr>\n";
                $html .= "\t\t<td style=\"vertical-align: top".(($result['field_req'] == 1) ? '; color: #CC0000' : '').'">'.html_encode($result['field_lname'])."</td>\n";
                $html .= "\t\t<td>\n";
                switch ($result['field_type']) {
                    case 'textbox':
                        $html .= "\t\t\t<input type=\"text\" id=\"".html_encode($result['field_sname']).'" name="'.html_encode($result['field_sname']).'" value=""'.(((int) $result['field_length']) ? ' maxlength="'.$result['field_length'].'"' : '')." />\n";
                        break;
                    case 'textarea':
                        $html .= "\t\t\t<textarea id=\"".html_encode($result['field_sname']).'" name="'.html_encode($result['field_sname'])."\" rows=\"4\" cols=\"30\"></textarea>\n";
                        break;
                    case 'select':
                        if ($result['field_options'] != '') {
                            $options = explode("\n", $result['field_options']);
                            $html .= "\t\t\t<select id=\"".html_encode($result['field_sname']).'" name="'.html_encode($result['field_sname'])."\">\n";
                            foreach ($options as $option) {
                                $pieces = explode('=', $option);
                                $html .= "\t\t\t<option value=\"".html_encode($pieces[0]).'">'.html_encode($pieces[1])."</option>\n";
                            }
                            $html .= "\t\t\t</select>\n";
                        }
                        break;
                    case 'hidden':
                        $html .= "\t\t\t<input type=\"hidden\" name=\"".html_encode($result['field_sname']).'" value="'.html_encode($result['field_options'])."\" />\n";
                        break;
                    case 'checkbox':
                        if ($result['field_options'] != '') {
                            $options = explode("\n", $result['field_options']);
                            foreach ($options as $key => $option) {
                                $pieces = explode('=', $option);
                                $html .= "\t\t\t<input type=\"checkbox\" id=\"".html_encode($result['field_sname']).'_'.$key.'" name="'.html_encode($result['field_sname']).'[]" value="'.html_encode($pieces[0]).'"> <label for="'.html_encode($result['field_sname']).'_'.$key.'">'.html_encode($pieces[1])."</label><br />\n";
                            }
                        }
                        break;
                    case 'radio':
                        if ($result['field_options'] != '') {
                            $options = explode("\n", $result['field_options']);
                            foreach ($options as $key => $option) {
                                $pieces = explode('=', $option);
                                $html .= "\t\t\t<input type=\"radio\" id=\"".html_encode($result['field_sname']).'_'.$key.'" name="'.html_encode($result['field_sname']).'" value="'.html_encode($pieces[0]).'"> <label for="'.html_encode($result['field_sname']).'_'.$key.'">'.html_encode($pieces[1])."</label><br />\n";
                            }
                        }
                        break;
                    default:
                        $html .= '&nbsp;';
                        break;
                }
                $html .= "\t\t</td>\n";
                $html .= "\t</tr>\n";
            }
        }
    }
    if (!(int) $cfields_id) {
        if ($_SESSION['config'][ENDUSER_CAPTCHA] == 'yes') {
            $html .= generate_captcha_html($_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_FILENAME]);
        }
        $html .= "\t<tr>\n";
        $html .= "\t\t<td><label for=\"action\" style=\"color: #CC0000\">Subscriber Action</label></td>\n";
        $html .= "\t\t<td>\n";
        $html .= "\t\t\t<select id=\"action\" name=\"action\">\n";
        $html .= "\t\t\t<option value=\"subscribe\">Subscribe</option>\n";
        $html .= "\t\t\t<option value=\"unsubscribe\">Unsubscribe</option>\n";
        $html .= "\t\t\t</select>\n";
        $html .= "\t\t</td>\n";
        $html .= "\t</tr>\n";
        $html .= "\t<tr>\n";
        $html .= "\t\t<td colspan=\"2\" style=\"text-align: right\">\n";
        $html .= "\t\t\t<input type=\"submit\" value=\"Submit\" />\n";
        $html .= "\t\t</td>\n";
        $html .= "\t</tr>\n";
    }
    $html .= "</tbody>\n";
    $html .= "</table>\n";
    $html .= "</form>\n";

    return ($output == 'html') ? html_encode($html) : $html;
}

// Function will return a human readable friendly filesize.
function readable_size($bytes)
{
    $kb = 1024;			// Kilobyte
    $mb = 1048576;			// Megabyte
    $gb = 1073741824;		// Gigabyte
    $tb = 1099511627776;	// Terabyte

    if ($bytes < $kb) {
        return $bytes.' b';
    } elseif ($bytes < $mb) {
        return round($bytes / $kb, 2).' KB';
    } elseif ($bytes < $gb) {
        return round($bytes / $mb, 2).' MB';
    } elseif ($bytes < $tb) {
        return round($bytes / $gb, 2).' GB';
    } else {
        return round($bytes / $tb, 2).' TB';
    }
}

// Function will return in bytes the values used in php.ini
function return_bytes($string)
{
    $string = trim($string);
    $last = strtolower(substr(trim($string), -1));
    $string = clean_input($string, 'numeric');

    switch ($last) {
        case 'g':
            $string *= 1024;
            // no break
        case 'm':
            $string *= 1024;
            // no break
        case 'k':
            $string *= 1024;
            break;
    }

    return $string;
}

// Function will return an unicode character into an HTML special character.
function uc2html($character)
{
    $output = '';
    for ($i = 0; $i < (strlen($character) / 2); ++$i) {
        $charcode = (ord($character[$i * 2]) + 256 * ord($character[$i * 2 + 1]));
        $output .= '&#'.$charcode;
    }

    return $output;
}

// Function will process the data and get what you need from the string.
function get_data($tag_name, $contents)
{
    unset($num, $s, $e, $exp, $data);
    $num = strlen($tag_name);
    $s = ($num + 2);
    $e = ($num - (($num * 2) + 3));
    $exp = "/\[".$tag_name."\](.*)\[\/".$tag_name."\]/si";
    $data = preg_match($exp, $contents, $matches);
    $data = substr($matches[0], $s, $e);
    $data = trim($data);
    $data = explode("\n", $data);

    return $data;
}

// Function will add the variable wrapper to the variable.
function variable_wrapper(&$string)
{
    $string = '['.$string.']';
}

// Function will add the variable wrapper to the variable.
function example_wrapper(&$string)
{
    $string = '(Sample'.(($string != '') ? ': '.$string : '').')';
}

// Function will return a formated unsubscribe link for the user.
function unsubscribe_link($type, $email_address = '')
{
    return $email_address;
}

// Function will wrap the template code around the string and return the output.
function insert_template($type, $template_id, $string)
{
    global $db;

    if ($template_id = (int) $template_id) {
        $query = 'SELECT `template_content` from `'.TABLES_PREFIX."templates` WHERE `template_type`='".checkslashes($type)."' AND `template_id`='".checkslashes($template_id)."'";
        $result = $db->GetRow($query);
        if ($result) {
            return str_replace('[message]', $string, $result['template_content']);
        }
    }

    return $string;
}

/**
 * Function will return the custom field ID of the custom field short name.
 *
 * @param string $field_sname
 *
 * @return int
 */
function get_field_id($field_sname = '')
{
    global $db;

    if (trim($field_sname)) {
        $query = 'SELECT `cfields_id` FROM `'.TABLES_PREFIX."cfields` WHERE `field_sname` = '".checkslashes(trim($field_sname))."'";
        $result = $db->GetRow($query);
        if ($result) {
            return (int) $result['cfields_id'];
        }
    }

    return 0;
}

/**
 * Function will retreive customized data for a specific e-mail address and return an array.
 *
 * @param int    $users_id
 * @param array  $external_custom_data
 * @param string $called_from
 *
 * @return array
 */
function get_custom_data($users_id = 0, $external_custom_data = [], $config = [])
{
    global $db;

    if ($users_id = (int) $users_id) {
        $subscriber = [];
        $query = "
					SELECT *, CONCAT_WS(' ', `firstname`, `lastname`) as `fullname`
					FROM `".TABLES_PREFIX.'users`
					WHERE `users_id` = '.$db->qstr($users_id);
        $result = $db->GetRow($query);
        if ($result) {
            $subscriber['name'] = $result['fullname'];
            $subscriber['firstname'] = $result['firstname'];
            $subscriber['lastname'] = $result['lastname'];
            $subscriber['email'] = $result['email_address'];
            $subscriber['date'] = display_date($config[PREF_DATEFORMAT], (is_array($external_custom_data) && (!empty($external_custom_data['date']))) ? $external_custom_data['date'] : time());
            $subscriber['groupname'] = groups_information([$result['group_id']], true);
            $subscriber['groupid'] = $result['group_id'];
            $subscriber['messageid'] = ((is_array($external_custom_data) && (!empty($external_custom_data['messageid']))) ? $external_custom_data['messageid'] : 0);
            $subscriber['userid'] = $result['users_id'];
            $subscriber['signupdate'] = display_date($config[PREF_DATEFORMAT], $result['signup_date']);
            $subscriber['archiveurl'] = $config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME].'?id='.$subscriber['messageid'].((is_array($external_custom_data) && (!empty($external_custom_data['queueid']))) ? ':'.$external_custom_data['queueid'] : '');
            $subscriber['profileurl'] = $config[PREF_PUBLIC_URL].$config[ENDUSER_PROFILE_FILENAME].'?addr='.rawurlencode($result['email_address']);
            $subscriber['forwardurl'] = $config[PREF_PUBLIC_URL].$config[ENDUSER_FORWARD_FILENAME].'?id='.$subscriber['messageid'].':'.$subscriber['groupid'].(((!empty($subscriber['name'])) && ($subscriber['name'] != '')) ? '&name='.rawurlencode($subscriber['name']) : '').'&addr='.rawurlencode($result['email_address']);

            $squery = '
						SELECT `field_sname`, `value`
						FROM `'.TABLES_PREFIX.'cdata`
						LEFT JOIN `'.TABLES_PREFIX.'cfields`
							ON `'.TABLES_PREFIX.'cdata`.`cfield_id` = `'.TABLES_PREFIX.'cfields`.`cfields_id`
						WHERE `user_id` = '.$db->qstr((int) $result['users_id']).'
						ORDER BY `field_order` ASC';
            $sresults = $db->GetAll($squery);
            if ($sresults) {
                foreach ($sresults as $sresult) {
                    /*
                     * Special custom field variables.
                     */
                    if (trim($sresult['value'])) {
                        switch ($sresult['field_sname']) {
                            case 'firstname_suffix':
                                $subscriber['firstname'] .= ' '.trim($sresult['value']);
                                $subscriber['name'] = $subscriber['firstname'].' '.$subscriber['lastname'];
                                break;
                            case 'firstname_prefix':
                                $subscriber['firstname'] = trim($sresult['value']).' '.$subscriber['firstname'];
                                $subscriber['name'] = $subscriber['firstname'].' '.$subscriber['lastname'];
                                break;
                            case 'lastname_suffix':
                                $subscriber['lastname'] .= ' '.trim($sresult['value']);
                                $subscriber['name'] .= ' '.trim($sresult['value']);
                                break;
                            case 'lastname_prefix':
                                $subscriber['lastname'] = trim($sresult['value']).' '.$subscriber['lastname'];
                                $subscriber['name'] = $subscriber['firstname'].' '.$subscriber['lastname'];
                                break;
                            default:
                                break;
                        }
                    }

                    $subscriber[$sresult['field_sname']] = $sresult['value'];
                }
            }

            return $subscriber;
        }
    }

    return false;
}

/**
 * Return the cleaned value of the custom field in a usable format.
 *
 * @param mixed $value
 *
 * @return mixed
 */
function custom_data_field_value($value = '')
{
    if (is_array($value)) {
        $tmp_values = [];

        foreach ($value as $arr_element) {
            $arr_element = clean_input($arr_element, ['notags', 'trim']);
            if ($arr_element) {
                $tmp_values[] = $arr_element;
            }
        }

        if (!empty($tmp_values)) {
            $tmp_values = array_unique($tmp_values);
            $value = implode(', ', $tmp_values);
        } else {
            $value = '';
        }
    } else {
        $value = clean_input($value, ['notags', 'trim']);
    }

    return $value;
}

/**
 * Function will update a users custom field data.
 *
 * @global object $db
 *
 * @param int   $users_id
 * @param array $cdata
 *
 * @return bool
 */
function custom_data_store($users_id = 0, $cdata = [], &$config = [])
{
    global $db;

    if ($users_id = (int) $users_id) {
        /*
         * Remove existing custom field data since it will be reinserted here.
         */
        $query = 'DELETE FROM `'.TABLES_PREFIX.'cdata` WHERE `user_id` = '.$db->qstr($users_id);
        $db->Execute($query);

        $query = 'SELECT * FROM `'.TABLES_PREFIX.'cfields` ORDER BY `field_order` ASC';
        $cfields = $db->GetAll($query);
        if ($cfields) {
            foreach ($cfields as $cfield) {
                if (!empty($cdata[$cfield['field_sname']])) {
                    $value = custom_data_field_value($cdata[$cfield['field_sname']]);
                    if (!empty($value)) {
                        $query = 'INSERT INTO `'.TABLES_PREFIX.'cdata` VALUES (NULL, '.$db->qstr($users_id).', '.$db->qstr($cfield['cfields_id']).', '.$db->qstr($value).');';
                        if (!$db->Execute($query)) {
                            if ($config[PREF_ERROR_LOGGING] == 'yes') {
                                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to insert custom field data in the custom field table during this update. Database server said: ".$db->ErrorMsg()."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
                            }
                        }
                    }
                }
            }
        }
    }

    return true;
}

// Function will replace the string with customized data to the user.
function custom_data($user_data = [], $string = '', $details = [])
{
    global $db, $RESERVED_VARIABLES;

    $values = [];
    $extras_key = [];
    $extras_val = [];
    $arranged_search = [];
    $arranged_replace = [];

    if (((!is_array($user_data)) || (!count($user_data))) && valid_address($details['email_address'])) {
        $values['name'] = 'Jane Doe';
        $values['firstname'] = 'Jane';
        $values['lastname'] = 'Doe';
        $values['email'] = $details['email_address'];
        $values['email_address'] = $details['email_address'];
        $values['date'] = display_date($_SESSION['config'][PREF_DATEFORMAT], time());
        $values['groupname'] = 'Newsletter 101';
        $values['groupid'] = '6';
        $values['userid'] = '789';
        $values['messageid'] = (((!empty($_SESSION['message_details']['message_id'])) && ((int) $_SESSION['message_details']['message_id'])) ? $_SESSION['message_details']['message_id'] : 0);
        $values['signupdate'] = display_date($_SESSION['config'][PREF_DATEFORMAT], time() - 172800);
        $values['archiveurl'] = $_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_ARCHIVE_FILENAME].'?id='.$values['messageid'].((!empty($user_data['queueid'])) ? ':'.$user_data['queueid'] : '');
        $values['profileurl'] = $_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_PROFILE_FILENAME].'?addr='.rawurlencode($details['email_address']);
        $values['forwardurl'] = $_SESSION['config'][PREF_PUBLIC_URL].$_SESSION['config'][ENDUSER_FORWARD_FILENAME].'?id='.$values['messageid'].':'.$values['groupid'].'&addr='.rawurlencode($values['email_address']);
    } elseif (is_array($user_data)) {
        $values['name'] = $user_data['name'];
        $values['firstname'] = $user_data['firstname'];
        $values['lastname'] = $user_data['lastname'];
        $values['email'] = $user_data['email'];
        $values['email_address'] = $user_data['email'];
        $values['date'] = $user_data['date'];
        $values['groupname'] = $user_data['groupname'];
        $values['groupid'] = $user_data['groupid'];
        $values['userid'] = $user_data['userid'];
        $values['messageid'] = $user_data['messageid'];
        $values['signupdate'] = $user_data['signupdate'];
        $values['archiveurl'] = $user_data['archiveurl'];
        $values['profileurl'] = $user_data['profileurl'];
        $values['forwardurl'] = $user_data['forwardurl'];
    }

    $query = 'SELECT `field_sname` FROM `'.TABLES_PREFIX."cfields` WHERE `field_sname` <> '' ORDER BY `field_order` ASC";
    $results = $db->GetAll($query);
    if ($results) {
        for ($i = 0; $i < count($results); ++$i) {
            $extras_key[$i] = $results[$i]['field_sname'];
            $extras_val[$results[$i]['field_sname']] = (!empty($user_data[$results[$i]['field_sname']]) ? $user_data[$results[$i]['field_sname']] : '');
        }
    }

    $search = array_merge($RESERVED_VARIABLES, $extras_key);
    $replace = array_merge($values, $extras_val);

    if (!$user_data) {
        array_walk($replace, 'example_wrapper');
    }

    for ($i = 0; $i < count($search); ++$i) {
        $arranged_search[$i] = $search[$i];
        $arranged_replace[$i] = (!empty($replace[$search[$i]]) ? $replace[$search[$i]] : '');
    }
    array_walk($arranged_search, 'variable_wrapper');

    return str_replace($arranged_search, $arranged_replace, $string);
}

// Function will return the properly formatted, ready-to-go Automated Unsubscribe Message.
function unsubscribe_message($message, $type = 'text', &$config = [])
{
    global $LANGUAGE_PACK;

    if (($config[PREF_ADD_UNSUB_LINK] == 'yes') || (strpos($message, '[unsubscribe]') !== false)) {
        if ((!is_array($LANGUAGE_PACK)) || (is_array($LANGUAGE_PACK) && (!empty($LANGUAGE_PACK)))) {
            if (file_exists($config[PREF_PUBLIC_PATH].'languages/'.$config[ENDUSER_LANG_ID].'.lang.php')) {
                require_once $config[PREF_PUBLIC_PATH].'languages/'.$config[ENDUSER_LANG_ID].'.lang.php';
            } elseif (file_exists($config[PREF_PUBLIC_PATH].'languages/english.lang.php')) {
                require_once $config[PREF_PUBLIC_PATH].'languages/english.lang.php';
            }
        }

        if ((!empty($LANGUAGE_PACK['unsubscribe_message'])) && ($LANGUAGE_PACK['unsubscribe_message'] != '')) {
            $unsubscribe_message = $LANGUAGE_PACK['unsubscribe_message'];
        } else {
            $unsubscribe_message = 'Please click the following link to unsubscribe yourself from this mailing list: [unsubscribeurl]';
        }

        if ($type == 'html') {
            $unsubscribe_message = '<div class="unsubscribe-message">'.str_replace('[unsubscribeurl]', '<a href="[unsubscribeurl]">[unsubscribeurl]</a>', nl2br($unsubscribe_message)).'</div>';
        }

        if (strpos($message, '[unsubscribe]') !== false) {
            $message = str_replace('[unsubscribe]', $unsubscribe_message, $message);
        } else {
            $message = $message.(($type == 'html') ? '<br /><br />' : "\n\n").$unsubscribe_message;
        }
    }

    if (strpos($message, '[unsubscribeurl]') !== false) {
        $unsubscribe_url = $config[PREF_PUBLIC_URL].$config[ENDUSER_UNSUB_FILENAME].'?'.(($config[PREF_ADD_UNSUB_GROUP] == 'yes') ? 'g=[groupid]&' : '').'addr=[email]';

        $message = str_replace('[unsubscribeurl]', $unsubscribe_url, $message);
    }

    return $message;
}

/**
 * Function will return a properly formatted variable name.
 *
 * @param string $var_name
 *
 * @return string
 */
function variable_name($var_name = '')
{
    $output = '';

    if ($var_name = trim($var_name)) {
        $output = preg_replace("/[^a-z0-9_\-]/i", '_', strtolower($var_name));
    }

    return $output;
}

/**
 * Function will check to ensure that the variable name used is unique and will
 * use the variable_name function to properly format it.
 *
 * @param string $variable_name
 * @param bool   $is_edit
 *
 * @return array
 */
function check_variable($variable_name = '', $is_edit = false)
{
    global $db, $RESERVED_VARIABLES;

    $output = [false, 'There was no variable name provided to check, please try again.'];

    if ($variable_name = variable_name($variable_name)) {
        if (!in_array($variable_name, $RESERVED_VARIABLES)) {
            if (!$is_edit) {
                $query = 'SELECT `field_sname` FROM `'.TABLES_PREFIX."cfields` WHERE `field_sname`='".checkslashes($variable_name)."'";
                $result = $db->GetRow($query);
                if ($result) {
                    $output = [false, 'The &quot;Short Variable Name&quot; that you have used is already in use by another field. Please choose a unique name for this variable.'];
                }
            }

            $output = [true, $variable_name];
        } else {
            $output = [false, 'The &quot;Short Variable Name&quot; that you have used is a reserved word, please use a different field short name.'];
        }
    }

    return $output;
}

/**
 * Function will return a nice English formatted action for the subscriber history.
 *
 * @param string $action
 *
 * @return string
 */
function display_action($action)
{
    switch ($action) {
        case 'adm-import':
            return 'Administrator Imported';
            break;
        case 'adm-subscribe':
            return 'Administrator Subscribed';
            break;
        case 'adm-unsubscribe':
            return 'Administrator Unsubscribed';
            break;
        case 'usr-subscribe':
            return 'Self Subscribed';
            break;
        case 'usr-unsubscribe':
            return 'Self Unsubscribed';
            break;
    }
}

/**
 * Function will return a properly formatted filename.
 *
 * @param string $filename
 *
 * @return string
 */
function valid_filename($filename)
{
    return clean_input($filename, ['lowercase', 'filename']);
}

/**
 * Function will return the number of times the provided template is in use.
 *
 * @param int  $template_id
 * @param bool $return_list
 *
 * @return string
 */
function template_count($template_id, $return_list = true)
{
    global $db;

    $output = '';
    $query = 'SELECT `message_id`, `message_title` FROM `'.TABLES_PREFIX."messages` WHERE `text_template`='".$template_id."' OR `html_template`='".$template_id."' ORDER BY `message_title` ASC";
    $results = $db->GetAll($query);
    if ($results) {
        foreach ($results as $result) {
            $output .= '<li><a href="index.php?section=message&action=view&id='.$result['message_id'].'" style="font-weight: normal">'.$result['message_title'].'</a></li>';
        }

        return create_tooltip(count($results), 'This template is being used in:<ul>'.$output.'</ul>', false);
    } else {
        return '0';
    }
}

/**
 * Function will return the name of the provided template id.
 *
 * @param int $template_id
 *
 * @return string
 */
function template_name($template_id)
{
    global $db;

    $output = '';
    $query = 'SELECT `template_name` FROM `'.TABLES_PREFIX."templates` WHERE `template_id`='".checkslashes($template_id)."'";
    $result = $db->GetRow($query);
    if ($result) {
        return $result['template_name'];
    } else {
        return '-Unknown Template-';
    }
}

/**
 * Function will display the date in the timezone provided by the user.
 *
 * @param string $format
 * @param bool   $session_available
 *
 * @return string
 */
function display_date($format, int $timestamp, $session_available = true)
{
    global $config;

    if (empty($config)) {
        $config = [];
    }

    $timestamp = (int) $timestamp;

    if ($timestamp) {
        $timezone = (($session_available && !empty($_SESSION['config'][PREF_TIMEZONE])) ? $_SESSION['config'][PREF_TIMEZONE] : $config[PREF_TIMEZONE]);
        $daylight = (($session_available && !empty($_SESSION['config'][PREF_DAYLIGHT_SAVINGS])) ? $_SESSION['config'][PREF_DAYLIGHT_SAVINGS] : $config[PREF_DAYLIGHT_SAVINGS]);
        $timestamp = ($timestamp + ($timezone * 3600));

        if ((int) $timestamp) {
            return gmdate($format, (($daylight == 'yes') && date('I', $timestamp)) ? ($timestamp + 3600) : $timestamp);
        }
    }

    return false;
}

/**
 * Function will set the starting element of the XML data.
 *
 * @param string $parser
 * @param string $name
 * @param string $attrs
 */
function backup_stag($parser, $name, $attrs)
{
    global $backup;

    $tag = ['name' => strtolower($name), 'attributes' => $attrs];
    array_push($backup, $tag);
}

/**
 * Function will set the result set from the XML data.
 *
 * @param string $parser
 * @param string $cdata
 */
function backup_data($parser, $cdata)
{
    global $backup;

    if (trim($cdata) != '') {
        if (!empty($backup[count($backup) - 1]['result'])) {
            $backup[count($backup) - 1]['result'] .= trim($cdata);
        } else {
            $backup[count($backup) - 1]['result'] = trim($cdata);
        }
    }
}

/**
 * Function will set the ending element of the XML data.
 *
 * @param string $parser
 * @param string $name
 */
function backup_etag($parser, $name)
{
    global $backup;

    $backup[count($backup) - 2]['tables'][] = $backup[count($backup) - 1];
    array_pop($backup);
}

/**
 * Function will take an array and create a CSV row out of it.
 *
 * @param string $fields
 * @param string $enclosed
 * @param string $delimited
 *
 * @return string
 */
function csv_record($fields, $enclosed = '"', $delimited = ',')
{
    $row = [];
    if (is_array($fields)) {
        foreach ($fields as $field) {
            $enclose = false;
            if (stristr($field, $enclosed)) {
                $enclose = true;
                $field = str_replace($enclosed, $enclosed.$enclosed, $field);
            }
            if (stristr($field, $delimited)) {
                $enclose = true;
            }
            if ($enclose) {
                $field = $enclosed.$field.$enclosed;
            }
            $row[] = $field;
        }
    }

    return implode($delimited, $row);
}

/**
 * Function is responsible for sending notifications to administrator.
 *
 * @param string $notice_type
 * @param array  $notice_custom_data
 *
 * @return bool
 */
function send_notice($notice_type = '', $groups_array = [], $notice_custom_data = [], &$config = [])
{
    global $db, $LANGUAGE_PACK, $LM_PATH;

    if (is_array($groups_array) && count($groups_array)) {
        $group_list = [];
        foreach ($groups_array as $group_id) {
            if ($group_id = (int) $group_id) {
                $group_list[] = groups_information($group_id, true);
            }
        }

        try {
            $mail = new LM_Mailer($config);

            $mail->ClearAllRecipients();
            $mail->AddAddress($config[PREF_ADMEMAL_ID], $config[PREF_FRMNAME_ID]);

            switch ($notice_type) {
                case 'subscribe':
                    $mail->Subject = custom_data($notice_custom_data, $LANGUAGE_PACK['subscribe_notification_subject']);
                    $mail->Body = str_replace('[group_ids]', "\t- ".implode("\n\t- ", $group_list), custom_data($notice_custom_data, $LANGUAGE_PACK['subscribe_notification_message']));
                    break;
                case 'unsubscribe':
                    $mail->Subject = custom_data($notice_custom_data, $LANGUAGE_PACK['unsubscribe_notification_subject']);
                    $mail->Body = str_replace('[group_ids]', "\t- ".implode("\n\t- ", $group_list), custom_data($notice_custom_data, $LANGUAGE_PACK['unsubscribe_notification_message']));
                    break;
                default:
                    throw new Exception('Unable to determine notice type ['.$notice_type.'] to send to administrator.');
                    break;
            }

            if ((!$mail->IsError()) && $mail->Send()) {
                return true;
            } else {
                throw new Exception('Unable to send notice to administrator. LM_Mailer responded: '.$mail->ErrorInfo);
            }
        } catch (Exception $e) {
            if ($config[PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\t".$e->getMessage()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
            }

            return false;
        }
    } else {
        if ($config[PREF_ERROR_LOGGING] == 'yes') {
            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tEither the e-mail address or the group information was not set to be able to send a notice to the administrator.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
        }

        return false;
    }
}

/**
 * Function will hex encode an e-mail address and optionally text. Credits to Monte Ohrt and Jason Sweat.
 *
 * @param string $address
 * @param string $text
 *
 * @return string
 */
function encode_address($address, $text = '')
{
    $address_encode = '';
    $text_encode = '';
    $text = ((trim($text) == '') ? str_replace('@', ' at ', $address) : $text);

    preg_match("!^(.*)(\?.*)$!", $address, $match);
    if (!empty($match[2])) {
        return ['address' => $address, 'text' => $text];
    }
    for ($x = 0; $x < strlen($address); ++$x) {
        if (preg_match("!\w!", $address[$x])) {
            $address_encode .= '%'.bin2hex($address[$x]);
        } else {
            $address_encode .= $address[$x];
        }
    }

    for ($x = 0; $x < strlen($text); ++$x) {
        $text_encode .= '&#x'.bin2hex($text[$x]).';';
    }

    return ['address' => $address_encode, 'text' => $text_encode];
}

/**
 * Function checks to see if the trip has been activiated.
 *
 * @return true
 */
function check_trip()
{
    global $TRIP;

    if ($TRIP) {
        require_once 'classes/talkback/class.talkback.php';

        $talk = [];
        $talk['current_version'] = VERSION_INFO;
        $talk['version_type'] = VERSION_TYPE;
        $talk['host_name'] = $_SERVER['HTTP_HOST'];
        $talk['directory_path'] = dirname(__FILE__);
        $talk['directory_url'] = $_SERVER['PHP_SELF'];
        $talk['email_addr'] = $_SESSION['config'][REG_EMAIL];
        $talk['domain_name'] = $_SESSION['config'][REG_DOMAIN];
        $talk['serial_number'] = $_SESSION['config'][REG_SERIAL];
        $talk['timestamp'] = time();

        $talkback = new TalkBack('trip', $talk);
        $send = $talkback->post();

        return true;
    }
}

/**
 * Function takes care of general maintenance within ListMessenger once per session.
 *
 * @param bool $skip
 *
 * @return bool
 */
function perform_maintenance($skip = false)
{
    global $TRIP, $db;

    if ($skip) {
        return true;
    } else {
        if ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) {
            if ($_SESSION['config'][ENDUSER_SUBCON] == 'yes') {
                if ($_SESSION['config'][MAINTENANCE_PERFORMED] < (time() - 86400)) {
                    $expiration = (int) (time() - ($_SESSION['config'][PREF_EXPIRE_CONFIRM] * 86400));

                    $query = 'DELETE FROM `'.TABLES_PREFIX."confirmation` WHERE (`action`='usr-subscribe' OR `action`='usr-unsubscribe') AND `confirmed`='0' AND `date`<'".$expiration."'";
                    $db->Execute($query);

                    $query = 'DELETE FROM `'.TABLES_PREFIX."user_updates` WHERE `date`<'".$expiration."'";
                    $db->Execute($query);

                    $query = 'OPTIMIZE TABLE `'.TABLES_PREFIX.'confirmation`, `'.TABLES_PREFIX.'user_updates`, `'.TABLES_PREFIX.'sending`';
                    $db->Execute($query);

                    $timestamp = time();
                    $query = 'UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='".$timestamp."' WHERE `preference_id`='".MAINTENANCE_PERFORMED."'";
                    $result = $db->Execute($query);
                    if ((!$result) || (!$db->Affected_Rows())) {
                        if ($config[PREF_ERROR_LOGGING] == 'yes') {
                            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to update last maintenance run preference. Database server said: ".$db->ErrorMsg()."\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                        }
                    }

                    if (function_exists('fsockopen')) {
                        check_trip();
                    }

                    $_SESSION['config'][MAINTENANCE_PERFORMED] = $timestamp;
                    $_SESSION['doneMaintenance'] = true;

                    return true;
                }
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
}

/**
 * This is a wrapper function for html_encode so I didn't need to add an
 * additional attribute.
 *
 * @param string $string
 *
 * @return string
 */
function public_html_encode($string = '')
{
    return html_encode($string, 'public');
}

/**
 * Function will encode html with it's special character representation.
 *
 * @param string $string
 *
 * @return html encoded string
 */
function html_encode($string = '', $called_from = 'private')
{
    global $config;

    $encoding_style = 'htmlentities';
    $character_set = 'ISO-8859-1';

    if ($called_from == 'public') {
        if ((!empty($config[PREF_ENCODING_STYLE])) && ($config[PREF_ENCODING_STYLE] == 'htmlspecialchars')) {
            $encoding_style = 'htmlspecialchars';
        }

        if (!empty($config[PREF_DEFAULT_CHARSET])) {
            $character_set = $config[PREF_DEFAULT_CHARSET];
        }
    } elseif (!empty($_SESSION['config'])) {
        if ((!empty($_SESSION['config'][PREF_ENCODING_STYLE])) && ($_SESSION['config'][PREF_ENCODING_STYLE] == 'htmlspecialchars')) {
            $encoding_style = 'htmlspecialchars';
        }

        if (!empty($_SESSION['config'][PREF_DEFAULT_CHARSET])) {
            $character_set = $_SESSION['config'][PREF_DEFAULT_CHARSET];
        }
    }

    if ($string = trim($string)) {
        if ($encoding_style == 'htmlspecialchars') {
            return htmlspecialchars($string, ENT_QUOTES, $character_set);
        } else {
            return htmlentities($string, ENT_QUOTES, $character_set);
        }
    }

    return '';
}

/**
 * Function will decode the encoded HTML special characters.
 *
 * @param string $string
 *
 * @return html decoded string
 */
function html_decode($string = '')
{
    if ($string = trim($string)) {
        if (version_compare(phpversion(), '4.3.0', '>=')) {
            return html_entity_decode($string, ENT_QUOTES, ($_SESSION['config'][PREF_DEFAULT_CHARSET] != '') ? $_SESSION['config'][PREF_DEFAULT_CHARSET] : 'ISO-8859-1');
        } else {
            /**
             * You really need to upgrade PHP ;).
             */
            $trans_tbl = get_html_translation_table(HTML_ENTITIES);
            $trans_tbl = array_flip($trans_tbl);
            $string = strtr($string, $trans_tbl);
            $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
            $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);

            return $string;
        }
    }

    return '';
}

/**
 * Function will perform 2.0+ version upgrades.
 *
 * @param string $old_version
 */
function minor_version_upgrade($old_version)
{
    global $db, $ERROR, $ERRORSTR, $SUCCESS, $SUCCESSSTR;

    /*
     * Do not proceed with any database upgrades for older versions of PHP>
     */
    if (!defined('PHP_VERSION') || version_compare(PHP_VERSION, '5.0.0', '<')) {
        return false;
    }

    switch ($old_version) {
        case '2.0.0':
            $query = 'DROP TABLE `'.TABLES_PREFIX.'sending`';
            if ($db->Execute($query)) {
                $query = 'CREATE TABLE `'.TABLES_PREFIX."sending` (`sending_id` int(12) NOT NULL auto_increment, `email_address` varchar(128) NOT NULL default '', `users_id` int(12) NOT NULL default '0', `queue_id` int(12) NOT NULL default '0', `sent` tinyint(1) NOT NULL default '0', PRIMARY KEY  (`sending_id`)) ENGINE=".TABLES_ENGINE.' AUTO_INCREMENT=1;';
                if ($db->Execute($query)) {
                    $query = 'UPDATE `'.TABLES_PREFIX."preferences` SET `preference_value`='2.0.1' WHERE `preference_id`='".PREF_VERSION."';";
                    if ($db->Execute($query)) {
                        if (!reload_configuration()) {
                            ++$ERROR;
                            $ERRORSTR[] = 'Unable to reload your configuration into your session. Please restart your web-browser to reload session data.';
                        } else {
                            minor_version_upgrade('2.0.1');
                        }
                    } else {
                        ++$ERROR;
                        $ERRORSTR[] = 'Unable to set the ListMessenger version number to '.VERSION_INFO.' in the ListMessenger database.';
                    }
                } else {
                    ++$ERROR;
                    $ERRORSTR[] = 'Unable to create the new ListMessenger sending table. Please restore the &quot;sending&quot; table from a backup.';
                }
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'Unable to &quot;DROP&quot; the ListMessenger 2.0.0 sending table; therefore, the installer is unable to apply the required changes to the sending table. Does your MySQL user have drop permissions?';
            }
            break;
        case '2.0.1':
        case '2.0.2':
            $query = 'CREATE TABLE `'.TABLES_PREFIX."user_updates` (`updates_id` int(12) NOT NULL auto_increment, `hash` varchar(32) NOT NULL default '', `date` bigint(64) NOT NULL default '0', `email_address` varchar(128) NOT NULL default '0', `completed` int(1) NOT NULL default '0', PRIMARY KEY (`updates_id`), UNIQUE KEY `hash` (`hash`)) ENGINE=".TABLES_ENGINE.' AUTO_INCREMENT=1;';
            if ($db->Execute($query)) {
                $query = 'CREATE TABLE `'.TABLES_PREFIX."sessions` (`sesskey` VARCHAR( 64 ) NOT NULL DEFAULT '', `expiry` TIMESTAMP NOT NULL, `expireref` VARCHAR( 250 ) DEFAULT '', `created` TIMESTAMP NOT NULL, `modified` TIMESTAMP NOT NULL, `sessdata` LONGTEXT, PRIMARY KEY (`sesskey`), INDEX sess2_expiry(`expiry`), INDEX sess2_expireref(`expireref`)) ENGINE=".TABLES_ENGINE.';';
                if ($db->Execute($query)) {
                    $query = 'INSERT INTO `'.TABLES_PREFIX."preferences` (`preference_id`, `preference_value`) VALUES (54, '0'), (55, '0'), (56, 'no'), (57, 'profile.php'), (58, 'no'), (59, 'no'), (60, ''), (61, 'yes')";
                    if ($db->Execute($query)) {
                        if ($db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => '2.1.0'], 'UPDATE', "preference_id='".PREF_VERSION."'")) {
                            if (!reload_configuration()) {
                                ++$ERROR;
                                $ERRORSTR[] = 'Unable to reload your configuration into your session. Please restart your web-browser to reload session data.';
                            } else {
                                $index = [];
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'users` ADD INDEX (`group_id`);';
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'users` ADD INDEX (`signup_date`);';
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'users` ADD INDEX (`email_address`);';
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'templates` ADD INDEX (`template_type`);';
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'cdata` ADD INDEX (`user_id`);';
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'cdata` ADD INDEX (`cfield_id`);';
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'cfields` ADD INDEX (`field_sname`);';
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'cfields` ADD INDEX (`field_lname`);';
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'cfields` ADD INDEX (`field_type`);';
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'cfields` ADD INDEX (`field_req`);';
                                $index[] = 'ALTER TABLE `'.TABLES_PREFIX.'cfields` ADD INDEX (`field_order`);';

                                foreach ($index as $query) {
                                    $db->Execute(trim($query));
                                }

                                minor_version_upgrade('2.1.0');
                            }
                        } else {
                            ++$ERROR;
                            $ERRORSTR[] = 'Unable to set the ListMessenger version number to '.VERSION_INFO.' in the ListMessenger database.';
                        }
                    } else {
                        ++$ERROR;
                        $ERRORSTR[] = 'Unable to insert the new system preferences into the ListMessenger database. Please execute the following query:<blockquote style="font-family: monospace">'.$query.'</blockquote>';
                    }
                } else {
                    ++$ERROR;
                    $ERRORSTR[] = 'Unable to create the new sessions table in your ListMessenger database, please make sure that the database user has permission to create and alter tables in your ListMessenger database.';
                }
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'Unable to create the new user_updates table in your ListMessenger database, please make sure that the database user has permission to create and alter tables in your ListMessenger database.';
            }
            break;
        case '2.1.0':
            $query = 'INSERT INTO `'.TABLES_PREFIX."preferences` (`preference_id`, `preference_value`) VALUES (62, 'no'), (63, 'no'), (64, 'forward.php'), (65, 'no'), (66, 'htmlentities'), (67, 'yes'), (68, '')";
            if ($db->Execute($query)) {
                $query = 'ALTER TABLE `'.TABLES_PREFIX."groups` CHANGE `permit_login` `group_private` ENUM( 'true', 'false' ) NOT NULL DEFAULT 'false'";
                if ($db->Execute($query)) {
                    /*
                     * Change RTE to TinyMCE.
                     */
                    $db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => 'tiny_mce'], 'UPDATE', "preference_id='".PREF_USERTE."'");

                    /*
                     * MD5 hash the password and save it.
                     */
                    $db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => md5($_SESSION['config'][PREF_ADMPASS_ID])], 'UPDATE', "preference_id='".PREF_ADMPASS_ID."'");

                    /**
                     * Move any old banned domains to the banned e-mail addresses section.
                     */
                    $query = 'SELECT * FROM `'.TABLES_PREFIX.'preferences` WHERE `preference_id` IN ('.$db->qstr(ENDUSER_BANEMAIL).', '.$db->qstr(ENDUSER_BANIPS).") AND `preference_value` <> ''";
                    $results = $db->GetAll($query);
                    if ($results) {
                        $banned_list = [];

                        foreach ($results as $result) {
                            if ($preference_value = clean_input($result['preference_value'], 'nows')) {
                                $banned_entry = explode(';', $preference_value);

                                foreach ($banned_entry as $value) {
                                    if ($result['preference_id'] == ENDUSER_BANIPS) {
                                        $banned_list[] = '*@'.$value;
                                    } else {
                                        $banned_list[] = $value;
                                    }
                                }
                            }
                        }

                        if (count($banned_list)) {
                            $db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => ''], 'UPDATE', "preference_id='".ENDUSER_BANIPS."'");
                            $db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => implode(';', $banned_list)], 'UPDATE', "preference_id='".ENDUSER_BANEMAIL."'");
                        }
                    }

                    minor_version_upgrade('2.2.0');
                } else {
                    ++$ERROR;
                    $ERRORSTR[] = 'Unable to alter the groups table in the ListMessenger database. Please execute the following query:<blockquote style="font-family: monospace">'.$query.'</blockquote>';
                }
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'Unable to insert the new system preferences into the ListMessenger database. Please execute the following query:<blockquote style="font-family: monospace">'.$query.'</blockquote>';
            }
            break;
        case '2.2.0':
            // Insert the new line break option.
            $query = 'INSERT INTO `'.TABLES_PREFIX."preferences` (`preference_id`, `preference_value`) VALUES (69, 'n'), (70, 'yes')";
            if ($db->Execute($query)) {
                /*
                 * Update the ListMesenger version number.
                 */
                if ($db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => '2.2.1'], 'UPDATE', "preference_id='".PREF_VERSION."'")) {
                    if (!reload_configuration()) {
                        ++$ERROR;
                        $ERRORSTR[] = 'Unable to reload your configuration into your session. Please restart your web-browser to reload session data.';
                    } else {
                        ++$SUCCESS;
                        $SUCCESSSTR[] = 'Congratulations, you have successfully upgraded to ListMessenger '.VERSION_INFO.' ('.VERSION_BUILD.').';
                    }
                } else {
                    ++$ERROR;
                    $ERRORSTR[] = 'Unable to set the ListMessenger version number to '.VERSION_INFO.' in the ListMessenger database.';
                }
            }
            break;
        case '2.2.1':
            /*
             * Update the ListMesenger version number.
             */
            if ($db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => '2.2.2'], 'UPDATE', "preference_id='".PREF_VERSION."'")) {
                if (!reload_configuration()) {
                    ++$ERROR;
                    $ERRORSTR[] = 'Unable to reload your configuration into your session. Please restart your web-browser to reload session data.';
                } else {
                    ++$SUCCESS;
                    $SUCCESSSTR[] = 'Congratulations, you have successfully upgraded to ListMessenger '.VERSION_INFO.' ('.VERSION_BUILD.').';
                }
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'Unable to set the ListMessenger version number to '.VERSION_INFO.' in the ListMessenger database.';
            }
            break;
        case '2.2.2':
            /*
             * Update the ListMesenger version number.
             */
            if ($db->AutoExecute(TABLES_PREFIX.'preferences', ['preference_value' => '2.2.3'], 'UPDATE', "preference_id='".PREF_VERSION."'")) {
                if (!reload_configuration()) {
                    ++$ERROR;
                    $ERRORSTR[] = 'Unable to reload your configuration into your session. Please restart your web-browser to reload session data.';
                } else {
                    ++$SUCCESS;
                    $SUCCESSSTR[] = 'Congratulations, you have successfully upgraded to ListMessenger '.VERSION_INFO.' ('.VERSION_BUILD.').';
                }
            } else {
                ++$ERROR;
                $ERRORSTR[] = 'Unable to set the ListMessenger version number to '.VERSION_INFO.' in the ListMessenger database.';
            }
            break;
        default:
            break;
    }

    return true;
}

/**
 * This function cleans a string with any valid rules that have been provided in the $rules array.
 * Note that $rules can also be a string if you only want to apply a single rule.
 * If no rules are provided, then the string will simply be trimmed using the trim() function.
 *
 * @param string $string
 * @param array  $rules
 *
 * @return string
 *
 * @example $variable = clean_input(" 1235\t\t", array("nows", "int")); // $variable will equal an integer value of 1235.
 */
function clean_input($string, $rules = [])
{
    if (is_scalar($rules)) {
        if (trim($rules) != '') {
            $rules = [$rules];
        } else {
            $rules = [];
        }
    }

    if (is_array($rules) && count($rules)) {
        foreach ($rules as $rule) {
            switch ($rule) {
                case 'url':
                case 'file':
                case 'dir':			// Removes unwanted charachters and space from url's, files and directory names.
                    $string = str_replace([' ', "\t", "\n", "\r", "\0", "\x0B", '..', '://'], '', $string);
                    break;
                case 'filename':		// Modify filename to be a little more friendly.
                    $string = preg_replace("/[^a-z0-9_\-\.]/i", '_', $string);
                    break;
                case 'section':		// Validate the local section request.
                    $string = preg_replace('/[^a-z0-9_-]+/i', '', $string);
                    break;
                case 'int':			// Change string to an integer.
                    $string = (int) $string;
                    break;
                case 'float':			// Change string to a float.
                    $string = (float) $string;
                    break;
                case 'bool':			// Change string to a boolean.
                    $string = (bool) $string;
                    break;
                case 'nows':			// Trim all whitespace anywhere in the string.
                    $string = str_replace([' ', "\t", "\n", "\r", "\0", "\x0B", '&nbsp;'], '', $string);
                    break;
                case 'trim':			// Trim whitespace from ends of string.
                    $string = trim($string);
                    break;
                case 'lower':			// Change string to all lower case.
                case 'lowercase':
                    $string = strtolower($string);
                    break;
                case 'upper':			// Change string to all upper case.
                case 'uppercase':
                    $string = strtoupper($string);
                    break;
                case 'ucwords':		// Change string to correct word case.
                    $string = ucwords(strtolower($string));
                    break;
                case 'notags':			// Strips tags from the string.
                    $string = strip_tags($string);
                    break;
                case 'boolops':		// Removed recognized boolean operators.
                    $string = str_replace(['"', '+', '-', 'AND', 'OR', 'NOT', '(', ')', ',', '-'], '', $string);
                    break;
                case 'quotemeta':		// Quote's meta characters
                    $string = quotemeta($string);
                    break;
                case 'decode':			// Returns the output of the html_decode() function.
                    $string = html_decode($string);
                    break;
                case 'encode':			// Returns the output of the html_encode() function.
                    $string = html_encode($string);
                    break;
                case 'slashtestremove':
                    $string = checkslashes($string, 1);
                    break;
                case 'slashtestadd':
                    $string = checkslashes($string);
                    break;
                case 'specialchars':	// Returns the output of the htmlspecialchars() function.
                    $string = htmlspecialchars($string);
                    break;
                case 'trimds':		// Removes double spaces.
                    $string = str_replace([' ', "\t", "\n", "\r", "\0", "\x0B", '&nbsp;', "\x7f", "\xff", "\x0", "\x1f"], ' ', $string);
                    $string = html_decode(str_replace('&nbsp;', '', html_encode($string)));
                    break;
                case 'credentials':	// Acceptable characters for login credentials.
                    $string = preg_replace("/[^a-z0-9_\-\.]/i", '', $string);
                    break;
                case 'numeric':
                    $string = preg_replace('/[^0-9]+/i', '', $string);
                    break;
                case 'alphanumeric':
                    $string = preg_replace('/[^a-z0-9]+/i', '', $string);
                    break;
                case 'emailheaders':
                    $string = preg_replace("/(%0A|%0D|\n+|\r+)(content-type:|to:|cc:|bcc:)/i", '', $string);
                    break;
                case 'emailcontent':	// Check for evil tags that could be used to spam.
                    $string = str_ireplace(['content-type:', 'bcc:', 'to:', 'cc:'], '', $string);
                    break;
                default:
                    break;
            }
        }

        return $string;
    } else {
        return trim($string);
    }
}

/**
 * This is currently on a wrapper function; however, I expect that I will
 * do something a bit different with the tooltips so making it this way
 * now should save work down the road.
 *
 * @param string $message
 *
 * @return string
 */
function create_tooltip($title = '', $message = '', $required_field = false, $options = [])
{
    return '<a class="tooltip '.((!(bool) $required_field) ? 'n' : '').'req" title="ListMessenger Help: '.html_encode($title).'" rel="ListMessenger Help|-|'.html_encode($message).'" id="tooltip-'.md5(uniqid(rand(), 1)).'">'.html_encode($title).'</a>';
}

/**
 * Adds the correct message variables to the sidebar when requested.
 *
 * @param array $extra_variables
 *
 * @return true
 */
function add_sidebar_variables($extra_variables = [])
{
    global $db, $SIDEBAR;

    $i = count($SIDEBAR);
    $SIDEBAR[$i] = '<h1>Variables</h1>';
    $SIDEBAR[$i] .= "<div class=\"email-variables\">\n";

    if (is_array($extra_variables) && count($extra_variables)) {
        foreach ($extra_variables as $title => $entries) {
            if (is_array($extra_variables) && count($extra_variables)) {
                $SIDEBAR[$i] .= '	'.html_encode($title)."\n";
                $SIDEBAR[$i] .= "	<ul>\n";
                foreach ($entries as $entry) {
                    $SIDEBAR[$i] .= '		<li>'.create_tooltip($entry['variable'], $entry['tooltip'], true).'</li>';
                }
                $SIDEBAR[$i] .= "	</ul>\n";
            }
        }
    }

    $SIDEBAR[$i] .= "	Message Variables\n";
    $SIDEBAR[$i] .= "	<ul>\n";
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[name]', '<strong>Variable: <em>[name]</em></strong><br /><em>Full Name</em><br />This will input the users full name. Example: Tim Bobbins').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[firstname]', '<strong>Variable: <em>[firstname]</em></strong><br /><em>First Name</em><br />This will input the users first name only. Example: Tim').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[lastname]', '<strong>Variable: <em>[lastname]</em></strong><br /><em>Last Name</em><br />This will input the users last name only. Example: Bobbins').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[email]', '<strong>Variable: <em>[email]</em></strong><br /><em>E-Mail Address</em><br />This will input the users e-mail address. Example: email@domain.com').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[date]', '<strong>Variable: <em>[date]</em></strong><br /><em>Date</em><br />This will input the date that this message was sent. Example: '.display_date($_SESSION['config'][PREF_DATEFORMAT], time())).'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[groupname]', '<strong>Variable: <em>[groupname]</em></strong><br /><em>Group Name</em><br />This will input the name of the group that user belongs to. Example: Default').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[groupid]', '<strong>Variable: <em>[groupid]</em></strong><br /><em>Group ID</em><br />This will input the ID of the group that the user belongs to. Example: 4').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[userid]', '<strong>Variable: <em>[userid]</em></strong><br /><em>User ID</em><br />This will input the database ID of the user. Example: 354').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[messageid]', '<strong>Variable: <em>[messageid]</em></strong><br /><em>Message ID</em><br />This will input the database ID of this message. Example: 25').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[signupdate]', '<strong>Variable: <em>[signupdate]</em></strong><br /><em>Signup Date</em><br />This will input the date that the user signed up to the list. Example: '.display_date($_SESSION['config'][PREF_DATEFORMAT], time() - 172800)).'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[unsubscribe]', '<strong>Variable: <em>[unsubscribe]</em></strong><br /><em>Unsubscribe Segment</em><br />If you provide the [unsubscribe] variable in the body of your newsletter then ListMessenger will substitute the unsubscribe text from your language file in this location.<br /><br />If you do not provide this variable in your newsletter and you have the "Auto-Add Unsubscribe Link" enabled in Control Panel > Preferences > E-Mail Configuration, then ListMessenger will simply add the unsubscribe text to the bottom of the newsletter.<br /><br /><strong>Example:</strong><br />This e-mail was sent to [email] because you are subscribed to at least one of our mailing lists. If at any time you would like to remove yourself from our mailing list, please feel free to do so by visiting: [unsubscribeurl].').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[archiveurl]', '<strong>Variable: <em>[archiveurl]</em></strong><br /><em>Archive URL</em><br />This will input a properly formatted URL of this specific message in the public message archive so that your subscriber is able to click the link and see this message in their web-browser instead of their e-mail client.<br /><br /><strong>Notice:</strong><br />In order for this link to work, you must have the "Public Archive Access" extra feature enabled in <a href="./index.php?section=preferences&type=enduser">End-User Preferences</a>.').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[profileurl]', '<strong>Variable: <em>[profileurl]</em></strong><br /><em>Profile URL</em><br />This will input the URL of the profile update script so your subscriber is able to click the link and update their contact information.<br /><br /><strong>Notice:</strong><br />In order for this link to work, you must have the "Subscriber Profile Updates" extra feature enabled in <a href="./index.php?section=preferences&type=enduser">End-User Preferences</a>.').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[forwardurl]', '<strong>Variable: <em>[forwardurl]</em></strong><br /><em>Forward To Friend URL</em><br />This will input a properly formatted URL (address) of the forward to friend script so that your subscriber is able to click the link and forward the message to someone they think may be interested in the message you are sending.<br /><br /><strong>Notice:</strong><br />In order for this link to work, you must have the "Forward to a Friend" extra feature enabled in <a href="./index.php?section=preferences&type=enduser">End-User Preferences</a>.').'</li>';
    $SIDEBAR[$i] .= '		<li>'.create_tooltip('[unsubscribeurl]', '<strong>Variable: <em>[unsubscribeurl]</em></strong><br /><em>Unsubscribe URL</em><br />This will input a properly formatted URL to your public <em>'.$_SESSION['config'][ENDUSER_UNSUB_FILENAME].'</em> file <em>'.(($_SESSION['config'][PREF_ADD_UNSUB_GROUP] == 'no') ? 'without' : 'with')."</em> the group's identifier in the URL.<br /><br /><strong>Notice:</strong><br />If you would like to change whether or not the group's identifier appears in the URL change the &quot;Use Group ID in Unsubscribe Link&quot; option in the <a href=\"./index.php?section=preferences&amp;type=email\">E-Mail Configuration</a> section.").'</li>';
    $SIDEBAR[$i] .= "	</ul>\n";

    $query = 'SELECT `field_sname`,`field_lname` FROM `'.TABLES_PREFIX."cfields` WHERE `field_type` <> 'linebreak'";
    $results = $db->GetAll($query);
    if ($results) {
        $SIDEBAR[$i] .= "	Custom Variables\n";
        $SIDEBAR[$i] .= "	<ul>\n";
        foreach ($results as $sresult) {
            $SIDEBAR[$i] .= '<li>'.create_tooltip('['.$sresult['field_sname'].']', '<strong>Variable: <em>['.$sresult['field_sname'].']</em></strong><br />'.(($sresult['field_lname']) ? '<em>'.addslashes($sresult['field_lname']).'</em><br />' : '').'This is a custom variable that can be used in any template, message body or message subject.').'</li>';
        }
        $SIDEBAR[$i] .= "	</ul>\n";
    }
    $SIDEBAR[$i] .= "</div>\n";

    return true;
}

/**
 * This function generates a javascript array.
 *
 * @param unknown_type $values
 *
 * @return unknown
 */
function generate_statistics_values($values = [])
{
    $output = [];

    if (is_array($values)) {
        foreach ($values as $key => $value) {
            $output[] = '['.$key.', '.(int) $value.']';
        }
    }

    return implode(', ', $output);
}
