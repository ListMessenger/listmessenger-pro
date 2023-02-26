<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */
if (!defined('TOOLS_LOADED')) {
    exit;
}

$ERROR = 0;
$ERRORSTR = [];
$ERRORMSG = [];

$TITLE = '';
$MESSAGE = '';

$notice_custom_data = [];

require_once 'pref_ids.inc.php';
require_once 'config.inc.php';
require_once 'classes/adodb/adodb.inc.php';
require_once 'dbconnection.inc.php';
require_once 'functions.inc.php';
require_once 'classes/lm_mailer.class.php';
require_once 'classes/captcha/class.captcha.php';

$config = enduser_configuration();
$LM_REQUEST = [];

if ($config) {
    if ((!empty($_GET['action'])) && ($_GET['action'] == 'captcha') && ($config[ENDUSER_CAPTCHA] == 'yes')) {
        $fonts = [];
        $fonts[] = $config[PREF_PROPATH_ID].'includes/fonts/vera.ttf';
        $fonts[] = $config[PREF_PROPATH_ID].'includes/fonts/verabd.ttf';
        $captcha = new PhpCaptcha($fonts, 172, 40);
        $captcha->UseColour(false);
        $captcha->Create();
        exit;
    } elseif ((!empty($_GET['action'])) && ($_GET['action'] == 'audiocaptcha') && ($config[ENDUSER_AUDIO_CAPTCHA] == 'yes') && is_executable($config[PREF_FLITE_PATH])) {
        $captcha = new AudioPhpCaptcha(PREF_FLITE_PATH, $config[PREF_PRIVATE_PATH].'tmp/');
        $captcha->Create();
        exit;
    } else {
        /**
         * Determine whether to use $_GET or $_POST data in this request.
         */
        if (!empty($_GET) && is_array($_GET)) {
            $LM_REQUEST = $_GET;

            /*
             * Adds better support for e-mail address with + signs.
             */
            if (!empty($LM_REQUEST['addr']) && $LM_REQUEST['addr']) {
                $LM_REQUEST['addr'] = rawurldecode(urlencode($LM_REQUEST['addr']));
            }
            if (!empty($LM_REQUEST['email_address']) && $LM_REQUEST['email_address']) {
                $LM_REQUEST['email_address'] = rawurldecode(urlencode($LM_REQUEST['email_address']));
            }
        } elseif (!empty($_POST) && is_array($_POST)) {
            $LM_REQUEST = $_POST;
        } else {
            $LM_REQUEST = [];
        }
        unset($_GET, $_POST);

        if ((!empty($LM_REQUEST['template'])) || (!empty($_COOKIE['lm_template']))) {
            $template_file = clean_input((!empty($LM_REQUEST['template'])) ? $LM_REQUEST['template'] : $_COOKIE['lm_template'], ['trim', 'lowercase', 'filename']);
        } else {
            $template_file = '';
        }

        if (!$TEMPLATE_CONTENTS = get_template($template_file)) {
            ++$ERROR;
            $ERRORSTR[] = 'Unable to retrieve the template file contents. Please contact the website administrator and make them aware that their ListMessenger template file is unreachable.';

            if ($config[PREF_ERROR_LOGGING] == 'yes') {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tUnable to retrieve the template file.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
            }
        } else {
            if (stristr($TEMPLATE_CONTENTS, '[title]') === false) {
                ++$ERROR;
                $ERRORSTR[] = 'The retrieved template file does not contain a &quot;[title]&quot; variable. Please ensure your template has the string &quot;[title]&quot; somewhere in the file, usually between your &lt;title&gt;&lt/title&gt; tags.';

                if ($config[PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tTemplate file does not have [title] variable within the file.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            }
            if (stristr($TEMPLATE_CONTENTS, '[message]') === false) {
                ++$ERROR;
                $ERRORSTR[] = 'The retrieved template file does not contain a &quot;[message]&quot; variable. Please ensure your template has the string &quot;[message]&quot; somewhere in the file where you would like status messages displayed.';

                if ($config[PREF_ERROR_LOGGING] == 'yes') {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tTemplate file does not have [message] variable within the file.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                }
            }
        }

        if ((!empty($LM_REQUEST['language'])) || (!empty($_COOKIE['lm_language']))) {
            $language_file = clean_input((!empty($LM_REQUEST['language'])) ? $LM_REQUEST['language'] : $_COOKIE['lm_language'], ['trim', 'lowercase', 'filename']);
        } else {
            $language_file = '';
        }

        if ((!empty($LM_REQUEST['language'])) && ($language_file != '') && file_exists($config[PREF_PUBLIC_PATH].'languages/'.$language_file.'.lang.php') && is_readable($config[PREF_PUBLIC_PATH].'languages/'.$language_file.'.lang.php')) {
            setcookie('lm_language', $language_file, PREF_COOKIE_TIMEOUT);
            require_once $config[PREF_PUBLIC_PATH].'languages/'.$language_file.'.lang.php';
        } elseif ((!empty($_COOKIE['lm_language'])) && ($language_file != '') && file_exists($config[PREF_PUBLIC_PATH].'languages/'.$language_file.'.lang.php') && is_readable($config[PREF_PUBLIC_PATH].'languages/'.$language_file.'.lang.php')) {
            require_once $config[PREF_PUBLIC_PATH].'languages/'.$language_file.'.lang.php';
        } elseif (file_exists($config[PREF_PUBLIC_PATH].'languages/'.$config[ENDUSER_LANG_ID].'.lang.php') && is_readable($config[PREF_PUBLIC_PATH].'languages/'.$config[ENDUSER_LANG_ID].'.lang.php')) {
            require_once $config[PREF_PUBLIC_PATH].'languages/'.$config[ENDUSER_LANG_ID].'.lang.php';
        } elseif (file_exists($config[PREF_PUBLIC_PATH].'languages/english.lang.php') && is_readable($config[PREF_PUBLIC_PATH].'languages/english.lang.php')) {
            require_once $config[PREF_PUBLIC_PATH].'languages/english.lang.php';
        } else {
            ++$ERROR;
            $ERRORSTR[] = 'The public language directory does not contain the proposed language file, or the English language file. Please notify the website administrator that their ListMessenger language files need to be examined.';
        }
    }
} else {
    ++$ERROR;
    $ERRORSTR[] = "Unable to load ListMessenger's configuration data. Please information the website administrator of this error so it can be resolved.";
}
