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

if (is_array($ERRORSTR) && count($ERRORSTR)) {
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
    echo "<head>\n";
    echo "	<title>ListMessenger Error</title>\n";
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
    echo display_error($ERRORSTR);
    echo "</body>\n";
    echo "</html>\n";
} else {
    if (trim($TITLE) == '') {
        $TITLE = $LANGUAGE_PACK['default_page_title'];
    }

    if (trim($MESSAGE) == '') {
        $MESSAGE = $LANGUAGE_PACK['default_page_message'];
    }

    /**
     * Replace the variables in the template and display the HTML.
     */
    $template_search = ['[title]', '[rssfeed]', '[message]'];
    $template_replace = [$TITLE, ($config[ENDUSER_ARCHIVE] == 'yes') ? ' <link rel="alternate" type="application/rss+xml" title="Newsletter RSS Archive" href="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME].'?view=feed" />' : '', '<h1>'.$TITLE."</h1>\n".$MESSAGE];

    echo str_ireplace($template_search, $template_replace, $TEMPLATE_CONTENTS);
}
