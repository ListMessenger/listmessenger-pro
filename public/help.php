<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */

// Change the $LM_PATH variable in the public_config.inc.php file in this directory.
require_once './public_config.inc.php';

if (!file_exists($LM_PATH.'includes/config.inc.php')) {
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
    echo "<head>\n";
    echo "	<title>ListMessenger Path Error</title>\n";
    echo "	<style type=\"text/css\">\n";
    echo "	div.error-message {\n";
    echo "		background-color:	#FFD9D0;\n";
    echo "		border:				1px #CC0000 solid;\n";
    echo "		padding:			8px;\n";
    echo "		color:				#333333;\n";
    echo "		font-family:		Verdana, Arial, Helvetica, sans-serif;\n";
    echo "		font-size:			12px;\n";
    echo "		margin-bottom:		10px;\n";
    echo "	}\n";
    echo "	</style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<div class=\"error-message\">\n";
    echo '	The path to the ListMessenger directory that you have provided [<strong>'.$LM_PATH.'</strong>] appears to be invalid or PHP does not have permission to read files from this directory. Please ensure that you provide the full path from root to your ListMessenger program directory in the $LM_PATH variable within this file [<strong>'.__FILE__.'</strong>].';
    echo "</div>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit;
} else {
    ini_set('include_path', str_replace('\\', '/', $LM_PATH.'/includes'));
    ini_set('allow_url_fopen', 1);
    ini_set('error_reporting', E_ALL ^ E_NOTICE);
    ini_set('magic_quotes_runtime', 0);

    define('TOOLS_LOADED', true);
    require_once 'eu_header.inc.php';

    $abuse = encode_address($config[PREF_ABUEMAL_ID]);

    $TITLE = $LANGUAGE_PACK['page_help_title'];

    $MESSAGE = '';
    $MESSAGE .= $LANGUAGE_PACK['page_help_message_sentence'];
    $MESSAGE .= "<br /><br />\n";
    $MESSAGE .= $LANGUAGE_PACK['page_help_subtitle']."\n";
    $MESSAGE .= "<ol>\n";
    $MESSAGE .= "	<li style=\"padding-bottom: 10px\">\n";
    $MESSAGE .= '		<div style="font-weight: bold">'.$LANGUAGE_PACK['page_help_question_1'].'</div>';
    $MESSAGE .= '		'.(($config[ENDUSER_SUBCON] == 'yes') ? $LANGUAGE_PACK['page_help_answer_1_optin'] : $LANGUAGE_PACK['page_help_answer_1_no_optin']);
    $MESSAGE .= "	</li>\n";
    $MESSAGE .= "	<li style=\"padding-bottom: 10px\">\n";
    $MESSAGE .= '		<div style="font-weight: bold">'.$LANGUAGE_PACK['page_help_question_2'].'</div>';
    $MESSAGE .= '		'.(($config[ENDUSER_UNSUBCON] == 'yes') ? $LANGUAGE_PACK['page_help_answer_2_optout'] : $LANGUAGE_PACK['page_help_answer_1_no_optout'])."<br /><br />\n";
    $MESSAGE .= '		<form action="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_UNSUB_FILENAME]."\" method=\"get\">\n";
    $MESSAGE .= '		'.$LANGUAGE_PACK['page_confirm_email_address'].' <input type="text" name="addr" value="" /> <input type="submit" value="'.$LANGUAGE_PACK['page_unsubscribe_submit_button']."\" />\n";
    $MESSAGE .= "		</form>\n";
    $MESSAGE .= "	</li>\n";
    $MESSAGE .= "	<li style=\"padding-bottom: 10px\">\n";
    $MESSAGE .= '		<div style="font-weight: bold">'.$LANGUAGE_PACK['page_help_question_3'].'</div>';
    $MESSAGE .= '		'.$LANGUAGE_PACK['page_help_answer_3'];
    $MESSAGE .= "	</li>\n";

    if ($config[ENDUSER_PROFILE] == 'yes') {
        $MESSAGE .= "	<li style=\"padding-bottom: 10px\">\n";
        $MESSAGE .= '		<div style="font-weight: bold">'.$LANGUAGE_PACK['page_help_question_4'].'</div>';
        $MESSAGE .= '		'.str_replace('[URL]', $config[PREF_PUBLIC_URL].$config[ENDUSER_PROFILE_FILENAME], $LANGUAGE_PACK['page_help_answer_4']);
        $MESSAGE .= "	</li>\n";
    }
    $MESSAGE .= "</ol>\n";

    $MESSAGE = str_replace('[abuse_address]', '<a href="mailto:'.$abuse['address'].'" style="font-weight: strong">'.$abuse['text'].'</a>', $MESSAGE);

    require_once 'eu_footer.inc.php';
}
