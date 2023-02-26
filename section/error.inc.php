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

if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tRequested file was not found: ".$SECTION."\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
}
?>
<h1>Document Not Found</h1>
The requested document was not found. Please return the to main page.