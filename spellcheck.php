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
    ORIGINAL LICENCE INFORMAION:
    Pungo Spell Copyright (c) 2003 Billy Cook, Barry Johnson

    Permission is hereby granted, free of charge, to any person obtaining a copy of
    this software and associated documentation files (the "Software"), to deal in
    the Software without restriction, including without limitation the rights to
    use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
    the Software, and to permit persons to whom the Software is furnished to do so,
    subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
    FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
    COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
    IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
    CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// Setup PHP and start page setup.
ini_set('include_path', str_replace('\\', '/', dirname(__FILE__)).'/includes');
ini_set('allow_url_fopen', 1);
ini_set('session.name', md5(dirname(__FILE__)));
ini_set('session.use_trans_sid', 0);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_secure', 0);
ini_set('session.referer_check', '');
ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('magic_quotes_runtime', 0);

ob_start();

require_once 'pref_ids.inc.php';
require_once 'config.inc.php';
require_once 'classes/adodb/adodb.inc.php';
require_once 'dbconnection.inc.php';

session_start();

if ((empty($_SESSION['isAuthenticated'])) || (!(bool) $_SESSION['isAuthenticated'])) {
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
    echo "<body>\n";
    echo "<script language=\"JavaScript\" type=\"text/javascript\">\n";
    echo "alert('It appears as though you are either not currently logged into ListMessenger or your session has expired. You will now be taken to the ListMessenger login page; please re-login.');\n";
    echo "if(window.opener) {\n";
    echo "	window.opener.location = './index.php?action=logout';\n";
    echo "	top.window.close();\n";
    echo "} else {\n";
    echo "	window.location = './index.php?action=logout';\n";
    echo "}\n";
    echo "</script>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit;
}

$ERROR = 0;
$ERRORSTR = [];
$NOTICE = 0;
$NOTICESTR = [];
$SUCCESS = 0;
$SUCCESSSTR = [];

require_once 'functions.inc.php';
require_once 'loader.inc.php';

// Check the connecting IP address against the blacklisted IP address list.
if ((!empty($_SERVER['REMOTE_ADDR'])) && banned_ip($_SERVER['REMOTE_ADDR'], $_SESSION['config'][ENDUSER_BANIPS])) {
    echo "The IP address you are attempting to connect from is prohibited from accessing this system.\n";
    echo "<br /><br />\n";
    echo 'Please contact the website administrator for further assistance.';

    if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
        error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tA banned IP address [".$_SERVER['REMOTE_ADDR']."] attempted to connect to ListMessenger but was blocked.\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
    }
    exit;
}

if ((!empty($_GET['ifrm'])) && ($_GET['ifrm'] == 'true')) {
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
    echo "<head>\n";
    echo '<meta http-equiv="Content-Type" content="text/html; charset='.$_SESSION['config'][PREF_DEFAULT_CHARSET]."\" />\n";
    echo "<title>ListMessenger Spell Checking</title>\n";
    echo '<link rel="stylesheet" type="text/css" href="'.$_SESSION['config'][PREF_PROGURL_ID]."css/common.css\" title=\"ListMessenger Style\" />\n";
    echo "<script>\n";
    echo "function assignSelf() {\n";
    echo "   window.parent.iFrameBody = window.document.getElementById('pageBody');\n";
    echo "}\n";
    echo "</script>\n";
    echo "</head>\n";
    echo "<body onLoad=\"assignSelf(); window.parent.startsp();\" id=\"pageBody\">\n";
    echo "Data failed to spell check.\n";
    echo "</body>\n";
    echo "</html>\n";
} else {
    if (!$pspell_link = pspell_new('en', '', '', '', PSPELL_FAST | PSPELL_RUN_TOGETHER)) {
        $js = "alert('ASpell failed to load your dictionary files. Please make sure your system has English dictionary files available.');\n";
    } else {
        $string = checkslashes($_POST['spellstring'], 1);
        $string = str_replace(["\r", "\n"], ['', '_|_'], trim($string));

        preg_match_all("/[[:alpha:]']+|<[^>]+>|&[^;\ ]+;/", $string, $alphas, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);

        /*
        ORIGINAL AUTHORS NOTE
        This has to be done after the matching or it messes up the indexing.
        I have not figured out exactly why this happens but I know this fixes it.
        */
        $string = str_replace('"', '\\"', $string);
        $js = 'var mispstr	= "'.$string."\";\n";
        $js .= 'var misps	= Array(';
        $curindex = 0;

        for ($i = 0; $i < sizeof($alphas[0]); ++$i) {
            // If the word is an html tag or entity then skip it
            if (preg_match("/<[^>]+>|&[^;\ ]+;/", $alphas[0][$i][0])) {
                continue;
            }
            if (!pspell_check($pspell_link, $alphas[0][$i][0])) {
                $js .= "new misp('".str_replace("'", "\\'", $alphas[0][$i][0])."',".$alphas[0][$i][1].','.(strlen($alphas[0][$i][0]) + ($alphas[0][$i][1] - 1)).',[';
                $suggestions = pspell_suggest($pspell_link, $alphas[0][$i][0]);
                foreach ($suggestions as $suggestion) {
                    $sugs[] = "'".str_replace("'", "\\'", $suggestion)."'";
                }
                if (sizeof($sugs)) {
                    $js .= join(',', $sugs);
                }
                unset($sugs);

                $js .= "]),\n";
                $sugs_found = 1;
            }
        }
        if ($sugs_found) {
            $js = substr($js, 0, -2);
        }
        $js .= ');';
    }
    ?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $_SESSION['config'][PREF_DEFAULT_CHARSET]; ?>" />

			<title>ListMessenger Spell Checking</title>

			<link rel="stylesheet" type="text/css" href="<?php echo $_SESSION['config'][PREF_PROGURL_ID]; ?>css/common.css" title="ListMessenger Style" />
			<style type="text/css">
			body {
				margin:				0px;
				background-color:	#EEEEEE
			}

			.editorWindow {
				border-style:	outset;
				padding:		5px;
				width:			640px;
				height:			200px;
				overflow:		auto
			}
			</style>

			<script langauge="JavaScript" type="text/javascript">
			var iFrameBody;
			var spell_formname		= '<?php echo html_encode($_POST['spell_formname']); ?>';
			var spell_fieldname		= '<?php echo html_encode($_POST['spell_fieldname']); ?>';
			</script>
			<script type="text/javascript" src="<?php echo $_SESSION['config'][PREF_PROGURL_ID]; ?>javascript/spellcheck/spellcheck.js"></script>
			<script language="JavaScript" type="text/javascript">
			<?php echo $js; ?>
			</script>
		</head>

		<body>

		<form name="fm1" onSubmit="return false;">
		<iframe style="width: 100%; height: 267px; margin: 0px; padding: 0px; border-left: 0px; border-right: 0px; border-top: 0px; border-bottom: 1px #666666 solid" src="./spellcheck.php?ifrm=true"></iframe>
		<br />
		<table style="width: 100%; margin-top: 3px" cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td style="vertical-align: top; text-align: left; padding-left: 5px">
				Change to:<br />
				<input type="text" class="text-box"name="changeto" style="width: 135px; background-color: #FFFFFF; margin-top: 4px" />
			</td>
			<td style="vertical-align: top; text-align: left">
				Suggestions:<br />
				<select name="suggestions" style="width: 225px; margin-top: 4px" size="5" onclick="this.form.changeto.value = this.options[ this.selectedIndex ].text"></select>
			</td>
			<td style="vertical-align: top; text-align: right; padding-right: 5px">
				<table width="100%" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td style="height: 25px">
						<input type="button" class="button" name="change" value="Change" onclick="replaceWord()" />
						<input type="button" class="button" name="changeall" value="Change All" onclick="replaceAll()" />
					</td>
				</tr>
				<tr>
					<td style="height: 25px">
						<input type="button" class="button" name="ignore" value="Ignore" onclick="nextWord(false)" />
						<input type="button" class="button" name="ignoreall" value="Ignore All" onclick="nextWord(true)" />
					</td>
				</tr>
				<tr>
					<td style="height: 25px; text-align: right">
						<input type="button" class="button" name="cancel" value="Cancel" onclick="parent.window.focus();top.window.close()" />
					</td>
				</tr>
				</table>
			</td>
		</tr>
		</table>
		</form>

		</body>
		</html>
		<?php
}
?>