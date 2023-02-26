<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */

// Setup PHP and start page setup.
ini_set('include_path', __DIR__.'/includes');
ini_set('allow_url_fopen', 1);
ini_set('session.name', md5(__DIR__));
ini_set('session.use_trans_sid', 0);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_secure', 0);
ini_set('session.referer_check', '');
ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('magic_quotes_runtime', 0);

$HEAD = [];
$ONLOAD = [];
$SIDEBAR = [];

$ERROR = 0;
$ERRORSTR = [];
$NOTICE = 0;
$NOTICESTR = [];
$SUCCESS = 0;
$SUCCESSSTR = [];

$RTE_ENABLED = false;
$TRIP = true;
$SECTION = 'login';

$SUBSCRIBER_SUMMARY = ['', 0];

require_once 'pref_ids.inc.php';
require_once 'config.inc.php';
require_once 'classes/adodb/adodb.inc.php';
require_once 'dbconnection.inc.php';

session_start();

require_once 'functions.inc.php';
require_once 'loader.inc.php';

ob_start('on_complete');

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

// Upgrade detection and execution
if (!$version = $db->GetOne('SELECT `preference_value` FROM `'.TABLES_PREFIX."preferences` WHERE `preference_id` = '".PREF_VERSION."'")) {
    $version = $_SESSION['config'][PREF_VERSION];
}

switch ($version) {
    case '2.0.0':
        minor_version_upgrade('2.0.0');
        break;
    case '2.0.1':
    case '2.0.2':
        minor_version_upgrade('2.0.1');
        break;
    case '2.1.0':
        minor_version_upgrade('2.1.0');
        break;
    case '2.2.0':
        minor_version_upgrade('2.2.0');
        break;
    case '2.2.1':
        minor_version_upgrade('2.2.1');
        break;
    case '2.2.1':
        minor_version_upgrade('2.2.2');
        break;
    default:
        break;
}

// Login
if ((!empty($_POST['action'])) && ($_POST['action'] == 'login')) {
    reload_configuration();

    if ((checkslashes($_POST['username']) == $_SESSION['config'][PREF_ADMUSER_ID]) && (md5(clean_input($_POST['password'], 'trim')) == $_SESSION['config'][PREF_ADMPASS_ID])) {
        /*
         * Added security feature for PHP 4.3.6+ users.
         */
        if (version_compare(phpversion(), '4.3.6', '>')) {
            if ((PREF_DATABASE_SESSIONS == 'yes') && function_exists('adodb_session_regenerate_id')) {
                adodb_session_regenerate_id();
            } elseif (function_exists('session_regenerate_id')) {
                session_regenerate_id();
            }
        }

        $_SESSION['isAuthenticated'] = true;

        header('Location: index.php');
        exit;
    } else {
        ++$ERROR;
        $ERRORSTR[] = 'Your username or password are invalid. Please re-enter your username and password.';

        if ($_SESSION['config'][PREF_ERROR_LOGGING] == 'yes') {
            error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tInvalid username and password tried to log into ListMessenger. IP Address: [".$_SERVER['REMOTE_ADDR']."]\n", 3, $_SESSION['config'][PREF_PRIVATE_PATH].'logs/error_log.txt');
        }
    }
// Logout
} elseif ((!empty($_GET['action'])) && ($_GET['action'] == 'logout')) {
    if (PREF_DATABASE_SESSIONS == 'yes') {
        ADOdb_Session::gc(1);
    }

    $_SESSION['isAuthenticated'] = false;
    $_SESSION = [];
    session_unset();
    session_destroy();
// Reload Preferences
} elseif ((!empty($_GET['action'])) && ($_GET['action'] == 'reload')) {
    reload_configuration();
}

if ((empty($_SESSION['isAuthenticated'])) || (!(bool) $_SESSION['isAuthenticated'])) {
    if ((!empty($_GET['section'])) && (clean_input($_GET['section'], 'section') == 'password')) {
        $SECTION = 'password';
    } else {
        $SECTION = 'login';
    }
} else {
    if ((!empty($_GET['section'])) && ($tmp_section = clean_input($_GET['section'], 'section'))) {
        $SECTION = $tmp_section;
    } else {
        $SECTION = 'subscribers';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="<?php echo (!empty($_SESSION['config']) && array_key_exists($_SESSION['config'][PREF_DEFAULT_CHARSET], $CHARACTER_SETS)) ? $_SESSION['config'][PREF_DEFAULT_CHARSET] : 'ISO-8859-1'; ?>" />
	<title>ListMessenger <?php echo VERSION_TYPE.(((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) ? ' '.VERSION_INFO : ''); ?></title>

	<link rel="shortcut icon" href="./images/listmessenger.ico" />
	<link rel="stylesheet" href="./css/common.css" media="all" />
	<link rel="stylesheet" href="./javascript/jquery/jquery-ui.min.css" media="all" />
	<link rel="stylesheet" href="./css/cluetip.css" media="all" />

	<script src="./javascript/common.js"></script>
	<script src="./javascript/jquery/jquery.min.js"></script>
	<script src="./javascript/jquery/jquery-ui.min.js"></script>

	<script src="./javascript/jquery/jquery.bgiframe.js"></script>
	<script src="./javascript/jquery/jquery.ajaxqueue.js"></script>
	<script src="./javascript/jquery/jquery.textarearesizer.js"></script>
	<script src="./javascript/jquery/jquery.hoverintent.js"></script>
	<script src="./javascript/jquery/jquery.cluetip.js"></script>
	
	<script>
		$(document).ready(function() {
			$('textarea.resizable:not(.processed)').TextAreaResizer();
			$('a.tooltip').cluetip({activation: 'click', titleAttribute: 'rel', splitTitle: '|-|', sticky: true, closePosition: 'title', arrows: true, fx: {open: 'fadeIn'}, dropShadow: false});
			<?php if ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) { ?>
			$("#aboutDialog").dialog({bgiframe: true, width: 545, height: 505, modal: true, autoOpen: false});
			<?php } ?>
		});
	</script>

	%HEAD%
</head>
<body>
<div align="center">
	<div id="shadow-container" style="width: 85%; min-width: 762px">
		<div class="shadow1">
			<div class="shadow2">
				<div class="shadow3">
					<div class="container">
						<table class="listmessenger-window" cellspacing="0" cellpadding="0" border="0">
						<tr>
							<td>
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
								<?php if ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) { ?>
									<td class="nav<?php echo ($SECTION == 'subscribers') ? ' active' : ''; ?>"><a href="index.php">Subscribers</a></td>
									<td class="nav<?php echo ($SECTION == 'compose') ? ' active' : ''; ?>"><a href="index.php?section=compose">Compose Message</a></td>
									<td class="nav<?php echo ($SECTION == 'message') ? ' active' : ''; ?>"><a href="index.php?section=message">Message Centre</a></td>
									<td class="nav<?php echo ($SECTION == 'queue') ? ' active' : ''; ?>"><a href="index.php?section=queue">Queue Manager</a></td>
									<td class="nav logo" colspan="2" onclick="openAbout()"><span class="titlea">List</span><span class="titleb">Messenger</span> <span class="titlea"><?php echo VERSION_INFO; ?></span></td>
								<?php } else { ?>
									<td class="nav" style="width: 72%">
										<img src="./images/pixel.gif" width="500" height="20" alt="" title="" />
									</td>
									<td class="nav logo" style="width: 28%">
										<span class="titlea">List</span><span class="titleb">Messenger</span> <span class="titlea">Login</span>
									</td>
								<?php } ?>
								</tr>
								<?php if ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) { ?>
								<tr>
									<td style="width: 72%; height: 20px; padding-left: 8px; border-bottom: 1px #CCCCCC dotted; border-right: 1px #848284 solid; text-align: left" colspan="4">%USERCOUNT%</td>
									<td class="nav<?php echo ($SECTION == 'control') ? ' active' : ''; ?>" style="width: 14%"><a href="index.php?section=control">Control Panel</a></td>
									<td class="nav logout" style="width: 14%"><a href="index.php?action=logout">Logout</a></td>
								</tr>
								<?php } ?>
								<tr>
									<td style="width: 100%; height: <?php echo ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) ? '512' : '530'; ?>px; vertical-align: top" colspan="<?php echo ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) ? '6' : '2'; ?>">
										<table style="width: 100%; height: <?php echo ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) ? '512' : '530'; ?>px" cellspacing="0" cellpadding="3" border="0">
										<colgroup>
											<?php if ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) { ?>
											<col style="width: 18%" />
											<col style="width: 82%" />
											<?php } else { ?>
											<col style="width: 100%" />
											<?php } ?>
										</colgroup>
										<tr>
											<?php if ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) { ?>
											<td style="vertical-align: top; padding: 5px">
												<img src="./images/pixel.gif" width="125" height="1" alt="" title="" />
												<div id="lm-sidebar-tag">
													%SIDEBAR%
												</div>
											</td>
											<?php } ?>
											<td style="vertical-align: top; padding: 5px">
												<img src="./images/pixel.gif" width="595" height="1" alt="" title="" />
												<div id="lm-body-tag">
												<?php
                                                define('PARENT_LOADED', true);

if (!defined('PHP_VERSION') || version_compare(PHP_VERSION, '5.0.0', '<')) {
    echo display_error(['<strong>You are running an unsupported version of PHP.</strong><br /><br />PHP '.PHP_VERSION.' is no longer supported by ListMessenger, and you must upgrade to PHP 5.1 or higher to continue.']);
}

if ($SECTION && (!empty($_SESSION['config']) && file_exists($_SESSION['config'][PREF_PROPATH_ID].'section/'.$SECTION.'.inc.php'))) {
    if ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) {
        $setup_file = false;
        $setup_dir = false;

        if ((!defined('DEVELOPMENT_MODE')) || (!DEVELOPMENT_MODE)) {
            if (file_exists('./setup.php')) {
                $setup_file = true;
            }
            if (file_exists('./setup')) {
                $setup_dir = true;
            }

            if ($setup_file || $setup_dir) {
                echo display_notice(['Now that you have successfully setup ListMessenger, please delete the:<ol>'.(($setup_file) ? '<li>setup.php <strong>file</strong></li>' : '').(($setup_dir) ? '<li>setup <strong>directory</strong></li>' : '').'</ol>from the ListMessenger application directory for application security.']);
            }
        }

        if ((!file_exists($_SESSION['config'][PREF_PROPATH_ID].'licence.html')) || (!is_readable($_SESSION['config'][PREF_PROPATH_ID].'licence.html'))) {
            echo display_notice(['The <a href="licence.html" target="_blank">licence.html</a> file does not exist in your ListMessenger directory ['.html_encode($_SESSION['config'][PREF_PROPATH_ID]).']. Please place the licence.html file from the ListMessenger distribution archive into your ListMessenger directory.']);
        }
    }

    require_once $_SESSION['config'][PREF_PROPATH_ID].'section/'.$SECTION.'.inc.php';
} else {
    if (!empty($_SESSION['config']) && file_exists($_SESSION['config'][PREF_PROPATH_ID].'section/error.inc.php')) {
        require_once $_SESSION['config'][PREF_PROPATH_ID].'section/error.inc.php';
    } else {
        /*
        * This action will reload the preferences from the database
        * which will hopefully resolve the stale session problems when
        * directories have changed.
        */
        if ((empty($_GET['action'])) || ($_GET['action'] != 'reload')) {
            header('Location: index.php?action=reload');
            exit;
        }

        ++$ERROR;
        $ERRORSTR[0] = "The path which was provided to ListMessenger is currently not accessible and needs to be corrected prior to login.\n";
        $ERRORSTR[0] .= "<br /><br />\n";
        $ERRORSTR[0] .= "ListMessenger is trying to load files out of the following directory:<br />\n";
        $ERRORSTR[0] .= '<em>'.$_SESSION['config'][PREF_PROPATH_ID]."</em><br /><br />\n";
        if (file_exists(str_replace('\\', '/', dirname(__FILE__)).'/index.php')) {
            $ERRORSTR[0] .= "It looks as though your path might actually be:<br />\n";
            $ERRORSTR[0] .= '<em>'.str_replace('\\', '/', dirname(__FILE__))."/</em><br /><br />\n";
        }
        $ERRORSTR[0] .= 'Please correct this problem in the '.TABLES_PREFIX."preferences database table and try again.<br /><br />\n";
        $ERRORSTR[0] .= 'If you require assistance, please consult the <a href="https://listmessenger.com/index.php/faq" target="_blank">Frequently Asked Questions</a>.';
        echo "<blockquote>\n";
        echo display_error($ERRORSTR);
        echo "</blockquote>\n";
    }
}
?>
												</div>
											</td>
										</tr>
										</table>
									</td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if ((!empty($_SESSION['isAuthenticated'])) && ((bool) $_SESSION['isAuthenticated'])) { ?>
	<div id="aboutDialog" title="About ListMessenger">
		<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
		<colgroup>
			<col style="width: 30%" />
			<col style="width: 70%" />
		</colgroup>
		<tbody>
			<tr>
				<td>
					<img src="./images/listmessenger.gif" width="139" height="167" alt="ListMessenger" title="ListMessenger" />
				</td>
				<td>
					<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
					<colgroup>
						<col style="width: 25%" />
						<col style="width: 75%" />
					</colgroup>
					<tbody>
						<tr>
							<td style="padding-bottom: 10px" colspan="2"><span class="titlea-positive">List</span><span class="titleb-positive">Messenger</span> <span class="titlea-positive"><?php echo VERSION_TYPE.' '.VERSION_INFO.((VERSION_BUILD != '') ? '.'.VERSION_BUILD : ''); ?></span></td>
						</tr>
						<tr>
							<td>Website:</td>
							<td><a href="https://listmessenger.com" target="_blank">https://listmessenger.com</a></td>
						</tr>
						<tr>
							<td>Author:</td>
							<td><a href="mailto:msimpson@listmessenger.com">Matt Simpson</a></td>
						</tr>
						<tr>
							<td>Copyright:</td>
							<td>Copyright &copy; <?php echo gmdate('Y', time() + ($_SESSION['config'][PREF_TIMEZONE] * 3600)); ?> <a href="http://www.silentweb.ca" target="_blank">Silentweb</a></td>
						</tr>
						<tr>
							<td style="padding-top: 10px" colspan="2">
								ListMessenger exceeds expectations as a well designed, easy to use and extremely robust electronic mailing list management solution for your website.
							</td>
						</tr>
					</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="padding-top: 10px">
					<div id="aboutDialogTabs">
						<ul>
							<li><a href="#fragment-1"><span>Credits</span></a></li>
							<li><a href="#fragment-2"><span>Licence</span></a></li>
							<li><a href="#fragment-3"><span>Registration</span></a></li>
						</ul>
						<div id="fragment-1">
							<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
							<colgroup>
								<col style="width: 30%" />
								<col style="width: 70%" />
							</colgroup>
							<tbody>
								<tr>
									<td>&nbsp;<img src="./images/record-next-on.gif" width="9" height="9" alt="" title="" />&nbsp;<a href="http://www.karlawithak.ca" target="_blank">Karla Simpson</a>&nbsp;&nbsp;</td>
									<td>For all her love and support.</td>
								</tr>
								<tr>
									<td>&nbsp;<img src="./images/record-next-on.gif" width="9" height="9" alt="" title="" />&nbsp;<a href="http://listmessenger.net" target="_blank">Erik Geurts</a>&nbsp;&nbsp;</td>
									<td>For all his help on the forums and the User Guide.</td>
								</tr>
								<tr>
									<td>&nbsp;<img src="./images/record-next-on.gif" width="9" height="9" alt="" title="" />&nbsp;<a href="http://www.33sticks.com" target="_blank">Nathaniel Murray</a>&nbsp;&nbsp;</td>
									<td>For designing the ListMessenger logo.</td>
								</tr>
								<tr>
									<td>&nbsp;<img src="./images/record-next-on.gif" width="9" height="9" alt="" title="" />&nbsp;<a href="http://www.pixelpoint.com" target="_blank">PixelPoint</a>&nbsp;&nbsp;</td>
									<td>(Nina Vecchi) for initial project sponsorship.</td>
								</tr>
								<tr>
									<td>&nbsp;<img src="./images/record-next-on.gif" width="9" height="9" alt="" title="" />&nbsp;<a href="http://www.hotscripts.com/?RID=9600" target="_blank">HotScripts</a>&nbsp;&nbsp;</td>
									<td>For providing a dependable listing service.</td>
								</tr>
								<tr>
									<td>&nbsp;<img src="./images/record-next-on.gif" width="9" height="9" alt="" title="" />&nbsp;<a href="http://phpmailer.sourceforge.net" target="_blank">Brent Matzelle</a>&nbsp;&nbsp;</td>
									<td>For creating the PHPMailer class.</td>
								</tr>
								<tr>
									<td>&nbsp;<img src="./images/record-next-on.gif" width="9" height="9" alt="" title="" />&nbsp;<a href="http://adodb.sourceforge.net" target="_blank">John Lim</a>&nbsp;&nbsp;</td>
									<td>For creating the ADOdb database library.</td>
								</tr>
								<tr>
									<td colspan="2" style="padding-top: 5px">
										<a href="http://www.hotscripts.com/?RID=9600" target="_blank"><img src="./images/logo-hotscripts.png" style="float: left" width="173" height="56" alt="HotScripts - Application Resources" title="HotScripts - Application Resources" border="0" /></a>
										<a href="http://www.jdrf.ca" target="_blank"><img src="./images/logo-jdrf.jpg" style="float: right" width="223" height="56" alt="Juvenile Diabetes Research Foundation of Canada" title="Juvenile Diabetes Research Foundation of Canada" border="0" /></a>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
						<div id="fragment-2">
							<iframe style="width: 100%; height:185px; border: 0px; margin: 0px; padding: 0px" src="licence.html"></iframe>
						</div>
						<div id="fragment-3">
							<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
							<colgroup>
								<col style="width: 30%" />
								<col style="width: 70%" />
							</colgroup>
							<tbody>
								<tr>
									<td><strong>Program Name:</strong>&nbsp;</td>
									<td>ListMessenger</td>
								</tr>
								<tr>
									<td><strong>Program Version:</strong>&nbsp;</td>
									<td><?php echo VERSION_INFO.((VERSION_BUILD != '') ? '.'.VERSION_BUILD : ''); ?></td>
								</tr>
								<tr>
									<td colspan="2">&nbsp;</td>
								</tr>
								<tr>
									<td><strong>Registered To:</strong>&nbsp;</td>
									<td><?php echo html_encode($_SESSION['config'][REG_NAME]); ?></td>
								</tr>
								<tr>
									<td><strong>E-Mail Address:</strong>&nbsp;</td>
									<td><?php echo html_encode($_SESSION['config'][REG_EMAIL]); ?></td>
								</tr>
								<tr>
									<td><strong>Registered Domain:</strong>&nbsp;</td>
									<td><?php echo html_encode($_SESSION['config'][REG_DOMAIN]); ?></td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>
					<script>
					$('#aboutDialogTabs').tabs();
					</script>
				</td>
			</tr>
		</tbody>
		</table>
	</div>
<?php } ?>
</body>
</html>
<?php
/**
 * Get the total number of subscribers available before going into the
 * on_complete() callback function where all objects are dead and gone.
 */
$SUBSCRIBER_SUMMARY = total_subscribers();
