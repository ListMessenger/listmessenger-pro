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
ini_set('include_path', str_replace('\\', '/', dirname(__FILE__)).'/includes');
ini_set('allow_url_fopen', 1);
ini_set('session.name', md5(dirname(__FILE__)));
ini_set('session.use_trans_sid', 0);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_secure', 0);
ini_set('session.referer_check', '');
ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('magic_quotes_runtime', 0);

set_time_limit(0);

$HEAD = [];
$ONLOAD = [];
$SIDEBAR = [];

$ERROR = 0;
$ERRORSTR = [];
$NOTICE = 0;
$NOTICESTR = [];
$SUCCESS = 0;
$SUCCESSSTR = [];

$LMDIRECTORY = str_replace('\\', '/', dirname(__FILE__)).'/';
$PREVIOUS_VERSIONS = ['0.5.0', '0.9.3', '0.9.4', '0.9.5', '1.0.0', '2.0.0', '2.0.1', '2.1.0', '2.2.0', '2.2.1'];

$STEP = (int) ((!empty($_GET['step'])) ? $_GET['step'] : ((!empty($_POST['step'])) ? $_POST['step'] : 1));

define('IN_SETUP', true);

if (!empty($_SESSION)) {
    $_SESSION['isAuthenticated'] = false;
    $_SESSION = [];
    session_unset();
    session_destroy();
}

if ((!empty($_POST['previous_version'])) && in_array($_POST['previous_version'], ['2.0.0', '2.0.1', '2.1.0', '2.2.0', '2.2.1'])) {
    header('Location: ./index.php');
    exit;
}

require_once './setup/databases.inc.php';
require_once './setup/preference_map.inc.php';

require_once './includes/pref_ids.inc.php';
require_once './includes/classes/adodb/adodb.inc.php';

require_once './includes/functions.inc.php';

ob_start('on_complete');
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
		<title>ListMessenger <?php echo VERSION_TYPE.' '.VERSION_INFO; ?> Setup</title>

		<meta name="MSSmartTagsPreventParsing" content="true" />
		<meta http-equiv="imagetoolbar" content="no" />

		<link rel="shortcut icon" href="./images/listmessenger.ico" />
		<link rel="stylesheet" type="text/css" href="./css/common.css" media="all" />
		<link rel="stylesheet" type="text/css" href="./css/cluetip.css" media="all" />

		<script type="text/javascript" src="./javascript/common.js"></script>
		<script type="text/javascript" src="./javascript/jquery/jquery.min.js"></script>
		<script type="text/javascript" src="./javascript/jquery/jquery.textarearesizer.js"></script>
		<script type="text/javascript" src="./javascript/jquery/jquery.hoverintent.js"></script>
		<script type="text/javascript" src="./javascript/jquery/jquery.cluetip.js"></script>

		<script type="text/javascript">
		$(function() {
			$('textarea.resizable:not(.processed)').TextAreaResizer();
			$('a.tooltip').cluetip({activation: 'click', titleAttribute: 'rel', splitTitle: '|-|', sticky: true, closePosition: 'title', arrows: true, fx: {open: 'fadeIn'}, dropShadow: false});
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
										<td style="width: 72%; height: 20px; background-color: #CCCCCC; border-bottom: 1px #848284 solid; border-right: 1px #848284 solid">
											<img src="./images/pixel.gif" width="500" height="20" alt="" title="" />
										</td>
										<td style="width: 28%; height: 20px; background-color: #999999; border-bottom: 1px #848284 solid; text-align: center">
											<span class="titlea">List</span><span class="titleb">Messenger</span> <span class="titlea">Setup</span>
										</td>
									</tr>
									<tr>
										<td style="width: 100%; height: 530px; vertical-align: top" colspan="2">
											<table style="width: 100%; height: 530px" cellspacing="0" cellpadding="3" border="0">
											<colgroup>
												<col style="width: 18%" />
												<col style="width: 82%" />
											</colgroup>
											<tr>
												<td style="vertical-align: top; padding: 5px">
													<img src="./images/pixel.gif" width="125" height="1" alt="" title="" />
													%SIDEBAR%
												</td>
												<td style="vertical-align: top; padding: 5px">
													<img src="./images/pixel.gif" width="595" height="1" alt="" title="" />
													<h1>ListMessenger <?php echo VERSION_TYPE.' '.VERSION_INFO; ?> Setup</h1>
													<?php
                                                /**
                                                 * Error Checking.
                                                 */
                                                switch ($STEP) {
                                                    case 5:
                                                        if (file_exists(dirname(__FILE__).'/includes/config.inc.php')) {
                                                            require_once dirname(__FILE__).'/includes/config.inc.php';

                                                            if (!empty($_GET['type'])) {
                                                                $SETUP_TYPE = trim($_GET['type']);
                                                            } elseif (!empty($_POST['type'])) {
                                                                $SETUP_TYPE = trim($_POST['type']);
                                                            } else {
                                                                $SETUP_TYPE = false;
                                                            }
                                                        } else {
                                                            $STEP = 2;
                                                        }
                                                        break;
                                                    case 4:
                                                    case 3:
                                                        $PROCESSED = [];

                                                        if (!empty($_POST['database_type']) && in_array(trim($_POST['database_type']), ['mysqli'])) {
                                                            $PROCESSED['database_type'] = trim($_POST['database_type']);
                                                        } else {
                                                            ++$ERROR;
                                                            $ERRORSTR[] = 'You must specify a <strong>Database Adapter</strong> for ListMessenger to use.';
                                                        }

                                                        if (!empty($_POST['database_host']) && ($tmp_input = trim($_POST['database_host']))) {
                                                            $PROCESSED['database_host'] = $tmp_input;
                                                        } else {
                                                            ++$ERROR;
                                                            $ERRORSTR[] = 'You must provide a <strong>MySQL Hostname</strong> for ListMessenger to connect to.';
                                                        }

                                                        if (!empty($_POST['database_name']) && ($tmp_input = trim($_POST['database_name']))) {
                                                            $PROCESSED['database_name'] = $tmp_input;
                                                        } else {
                                                            ++$ERROR;
                                                            $ERRORSTR[] = 'You must provide the <strong>MySQL Database</strong> name Listmessenger will use.';
                                                        }

                                                        if (!empty($_POST['database_user']) && ($tmp_input = trim($_POST['database_user']))) {
                                                            $PROCESSED['database_user'] = $tmp_input;
                                                        } else {
                                                            ++$ERROR;
                                                            $ERRORSTR[] = 'You must provide the <strong>MySQL Username</strong> ListMessenger will connect using.';
                                                        }

                                                        if (!empty($_POST['database_pass']) && ($tmp_input = trim($_POST['database_pass']))) {
                                                            $PROCESSED['database_pass'] = $tmp_input;
                                                        } else {
                                                            ++$ERROR;
                                                            $ERRORSTR[] = 'You must provide the <strong>MySQL Password</strong> for the provided username.';
                                                        }

                                                        if (!empty($_POST['tables_prefix']) && ($tmp_input = trim($_POST['tables_prefix']))) {
                                                            $PROCESSED['tables_prefix'] = $tmp_input;
                                                        } else {
                                                            $PROCESSED['tables_prefix'] = '';
                                                        }

                                                        /*
                                                         * This really isn't an optin during setup. It can be tuned later if desired.
                                                         */
                                                        $PROCESSED['database_pconnect'] = false;

                                                        if (!$ERROR) {
                                                            $db = NewADOConnection($PROCESSED['database_type']);
                                                            if (!$db->Connect($PROCESSED['database_host'], $PROCESSED['database_user'], $PROCESSED['database_pass'], $PROCESSED['database_name']) || !$db->isConnected()) {
                                                                ++$ERROR;
                                                                $ERRORSTR[] = 'ListMessenger was unable to connect to the database server using the provided database connection information.<br /><br /><strong>Database Server Response</strong><div style="font-family: monospace">'.$db->ErrorMsg()."</div>\n";
                                                            }
                                                        }

                                                        // Proceed with step 4 error checking (yes, even on step 3).
                                                        if (!$ERROR) {
                                                            if (file_exists(dirname(__FILE__).'/includes/config.inc.php')) {
                                                                require_once dirname(__FILE__).'/includes/config.inc.php';

                                                                if (defined('DATABASE_TYPE') && in_array(DATABASE_TYPE, ['mysqli']) &&
                                                                    defined('DATABASE_HOST') && DATABASE_HOST &&
                                                                    defined('DATABASE_NAME') && DATABASE_NAME &&
                                                                    defined('DATABASE_USER') && DATABASE_USER &&
                                                                    defined('DATABASE_PASS') && DATABASE_PASS &&
                                                                    defined('TABLES_PREFIX') &&
                                                                    $PROCESSED['database_type'] == DATABASE_TYPE &&
                                                                    $PROCESSED['database_host'] == DATABASE_HOST &&
                                                                    $PROCESSED['database_name'] == DATABASE_NAME &&
                                                                    $PROCESSED['database_user'] == DATABASE_USER &&
                                                                    $PROCESSED['database_pass'] == DATABASE_PASS &&
                                                                    $PROCESSED['tables_prefix'] == TABLES_PREFIX) {
                                                                    /**
                                                                     * Connection has been tested, and the data is identical
                                                                     * so it doesn't need to be written, skip to the next step.
                                                                     */
                                                                    $STEP = 4;
                                                                } else {
                                                                    if ($STEP == 4) {
                                                                        ++$ERROR;
                                                                        $ERRORSTR[] = 'The <span style="font-family: monospace; font-weight: 700">/includes/config.inc.php</span> file in your ListMessenger directory does not contain the sames values as the textarea below.<br /><br />Please ensure that you copy and paste the contents of the textarea below into that file on your server before you continue.';
                                                                        $STEP = 3;
                                                                    }
                                                                }
                                                            } else {
                                                                if ($STEP == 4) {
                                                                    ++$ERROR;
                                                                    $ERRORSTR[] = "Oops the <span style=\"font-family: monospace; font-weight: 700\">/includes/config.inc.php</span> file doesn't appear to exist in your ListMessenger directory.<br /><br />Please ensure that you copy and paste the contents of the textarea below into that file on your server before you continue.";
                                                                    $STEP = 3;
                                                                }
                                                            }
                                                        } else {
                                                            $STEP = 2;
                                                        }
                                                        break;
                                                    case 2:
                                                    case 1:
                                                    default:
                                                        break;
                                                }

                                                /*
                                                 * Display Step
                                                 */
                                                switch ($STEP) {
                                                    case 5:
                                                        if (file_exists(dirname(__FILE__).'/includes/dbconnection.inc.php')) {
                                                            require_once dirname(__FILE__).'/includes/dbconnection.inc.php';

                                                            switch ($SETUP_TYPE) {
                                                                case 'new':
                                                                    if (file_exists(dirname(__FILE__).'/setup/new.inc.php')) {
                                                                        require_once dirname(__FILE__).'/setup/new.inc.php';
                                                                    } else {
                                                                        ++$ERROR;
                                                                        $ERRORSTR[] = 'You are missing some installer files. Please re-upload the contents of the setup directory from your ListMessenger distribution file.';
                                                                        echo display_error($ERRORSTR);
                                                                    }
                                                                    break;
                                                                case 'installed':
                                                                    header('Location: index.php');
                                                                    exit;
                                                                    break;
                                                                default:
                                                                    $ERROR++;
                                                                    $ERRORSTR[] = 'You have submitted in invalid installation type to this installer. Please re-start the installer and follow the on-page instructions. For more details, please view the installation log file.';
                                                                    echo display_error($ERRORSTR);
                                                                    break;
                                                            }
                                                        } else {
                                                            ++$ERROR;
                                                            $ERRORSTR[] = 'You are missing the database connection file in your includes directory. Please re-upload the contents of the includes directory from your ListMessenger distribution file and re-edit your /includes/config.inc.php file.';
                                                            echo display_error($ERRORSTR);
                                                        }
                                                        break;
                                                    case 4:
                                                        $install_type = 'new';
                                                        $version = '';
                                                        $db_tables = $db->MetaTables('TABLES');

                                                        if ($db_tables && is_array($db_tables) && in_array($PROCESSED['tables_prefix'].'preferences', $db_tables)) {
                                                            $query = 'SELECT `preference_value` FROM `'.$PROCESSED['tables_prefix']."preferences` WHERE `preference_id`='".PREF_VERSION."'";
                                                            $result = $db->GetRow($query);
                                                            if ($result) {
                                                                $version = $result['preference_value'];
                                                                if (version_compare($version, VERSION_INFO, '<') == 1) {
                                                                    $install_type = 'upgrade';
                                                                } else {
                                                                    $install_type = 'installed';
                                                                }
                                                            } else {
                                                                $install_type = 'upgrade';
                                                                if (in_array($PROCESSED['tables_prefix'].'sent_messages', $db_tables) && in_array($PROCESSED['tables_prefix'].'sent_templates', $db_tables)) {
                                                                    $version = '0.5.0';
                                                                } elseif (in_array($PROCESSED['tables_prefix'].'email_messages', $db_tables) && in_array($PROCESSED['tables_prefix'].'email_queues', $db_tables) && in_array($PROCESSED['tables_prefix'].'email_sending', $db_tables)) {
                                                                    $version = '0.9.3';
                                                                }
                                                            }
                                                        }
                                                        ?>
															<form action="./setup.php" method="post">
															<input type="hidden" name="step" value="5" />
															<?php
                                                        foreach ($PROCESSED as $key => $value) {
                                                            echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value)."\" />\n";
                                                        }
                                                        ?>
															<input type="hidden" name="type" value="<?php echo $install_type; ?>" />
															<h2>Installation Type</h2>
															<table style="width: 100%" cellspacing="0" cellpadding="1" border="0">
															<?php
                                                        // New ListMessenger Installation
                                                        if ($install_type == 'new') {
                                                            ?>
																<tr>
																	<td style="width: 5%; text-align: center"><img src="./images/section-show.gif" width="9" height="9" alt="" title="" /></td>
																	<td style="width: 95%; font-weight: bold; color: #000000">ListMessenger First Time Installation</td>
																</tr>
																<tr>
																	<td></td>
																	<td style="padding-bottom: 10px">
																		ListMessenger will create the following database tables in the <span style="border-bottom: 1px #669900 dotted"><?php echo $PROCESSED['database_name']; ?></span> database:
																		<ol style="margin-bottom: 0px">
																			<li><?php echo $PROCESSED['tables_prefix']; ?>cdata</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>cfields</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>confirmation</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>groups</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>messages</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>preferences</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>queue</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>sending</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>sessions</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>templates</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>users</li>
																			<li><?php echo $PROCESSED['tables_prefix']; ?>user_updates</li>
																		</ol>
																	</td>
																</tr>
																<?php
                                                            // Not A New Installation
                                                        } else {
                                                            ?>
																<tr>
																	<td style="width: 5%; text-align: center"><img src="./images/section-hide.gif" width="9" height="9" alt="" title="" /></td>
																	<td style="width: 95%; color: #666666">ListMessenger First Time Installation</td>
																</tr>
																<?php
                                                        }

                                                        // Upgrading ListMessenger
                                                        if ($install_type == 'upgrade') {
                                                            ?>
																<tr>
																	<td style="text-align: center"><img src="./images/section-show.gif" alt="" title="" /></td>
																	<td style="font-weight: bold; color: #000000">Upgrade ListMessenger Database</td>
																</tr>
																<tr>
																	<td></td>
																	<td style="padding-bottom: 10px">
																		<table style="width: 100%" cellspacing="0" cellpadding="1" border="0">
																		<tr>
																			<td style="white-space: nowrap">Please verify your current version of ListMessenger:</td>
																			<td>
																				<select class="select" name="previous_version">
																				<?php
                                                                            foreach ($PREVIOUS_VERSIONS as $value) {
                                                                                echo '<option value="'.$value.'"'.(($value == $version) ? ' SELECTED' : '').'>ListMessenger '.$value."</option>\n";
                                                                            }
                                                            ?>
																				</select>
																			</td>
																		</tr>
																		</table>
																	</td>
																</tr>
																<?php
                                                            // Not Upgrading ListMessenger
                                                        } else {
                                                            ?>
																<tr>
																	<td style="text-align: center"><img src="./images/section-hide.gif" width="9" height="9" alt="" title="" /></td>
																	<td style="color: #666666">Upgrade ListMessenger Database</td>
																</tr>
																<?php
                                                        }

                                                        // Already Installed
                                                        if ($install_type == 'installed') {
                                                            ?>
																<tr>
																	<td style="text-align: center"><img src="./images/section-show.gif" width="9" height="9" alt="" title="" /></td>
																	<td style="font-weight: bold; color: #000000">Completed ListMessenger <?php echo VERSION_INFO; ?> Database</td>
																</tr>
																<tr>
																	<td></td>
																	<td style="padding-bottom: 10px">
																		It appears that you have ListMessenger <?php echo VERSION_INFO; ?> already installed and there is no need to run the ListMessenger setup program at this time.
																	</td>
																</tr>
																<?php
                                                            // Not Already Installed
                                                        } else {
                                                            ?>
																<tr>
																	<td style="text-align: center"><img src="./images/section-hide.gif" width="9" height="9" alt="" title="" /></td>
																	<td style="color: #666666">Completed ListMessenger <?php echo VERSION_INFO; ?> Database</td>
																</tr>
																<?php
                                                        }
                                                        ?>
															<tr>
																<td colspan="2">&nbsp;</td>
															</tr>
															<tr>
																<td style="text-align: right; border-top: 2px #CCC solid; padding-top: 5px" colspan="2">
																	<?php
                                                                if ($install_type == 'installed') {
                                                                    echo "<input type=\"button\" value=\"Continue\" onclick=\"window.location = 'index.php'\" />\n";
                                                                } else {
                                                                    echo "<input type=\"submit\" value=\"Continue\" />\n";
                                                                }
                                                        ?>
																</td>
															</tr>
															</table>
															</form>
															<?php
                                                        break;
                                                    case 3:
                                                        $PASSED = false;
                                                        $config_path = dirname(__FILE__).'/includes';
                                                        $config_filename = 'config.inc.php';
                                                        $config_file = $config_path.'/'.$config_filename;

                                                        $can_write_config = false;

                                                        if ((file_exists($config_file) && is_writable($config_file)) || (file_exists($config_path) && is_writable($config_path))) {
                                                            $can_write_config = true;
                                                        }

                                                        $config_string = "<?php\n";
                                                        $config_string .= "define('DATABASE_TYPE', '".$PROCESSED['database_type']."');\n";
                                                        $config_string .= "define('DATABASE_HOST', '".$PROCESSED['database_host']."');\n";
                                                        $config_string .= "define('DATABASE_NAME', '".$PROCESSED['database_name']."');\n";
                                                        $config_string .= "define('DATABASE_USER', '".$PROCESSED['database_user']."');\n";
                                                        $config_string .= "define('DATABASE_PASS', '".$PROCESSED['database_pass']."');\n";
                                                        $config_string .= "define('TABLES_PREFIX', '".$PROCESSED['tables_prefix']."');\n";
                                                        $config_string .= "define('DATABASE_PCONNECT', false);\n";

                                                        if ($can_write_config) {
                                                            if (file_put_contents($config_file, $config_string) !== false) {
                                                                $PASSED = true;
                                                            }
                                                        }

                                                        ?>
															<h2>Write Configuration</h2>
															<form action="./setup.php" method="post">
															<input type="hidden" name="step" value="4" />
															<?php
                                                        foreach ($PROCESSED as $key => $value) {
                                                            echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value)."\" />\n";
                                                        }

                                                        if ($PASSED) {
                                                            echo display_success(['ListMessenger successfully wrote your configuration data to the /includes/config.inc.php file.']);
                                                        } else {
                                                            ?>
																<div class="generic-message">
																	ListMessenger was unable to automatically create you a config.inc.php file on your server because the setup tool did not have enough permissions to write the file. Please copy and paste the contents below into your <span style="font-family: monospace">/includes/config.inc.php</span> file.
																</div>
																<?php
                                                            if ($ERROR) {
                                                                echo display_error($ERRORSTR);
                                                            }
                                                            if ($NOTICE) {
                                                                echo display_notice($NOTICESTR);
                                                            }
                                                            ?>
																<ol class="setup">
																	<li>Copy the contents of the following text area.
																		<br /><br />
																		<textarea id="config_string" name="config_string" style="width: 80%; height: 125px; font-size: 11px; font-family: monospace" onclick="this.select()" readonly="readonly"><?php echo (!empty($config_string) && $config_string) ? $config_string : ''; ?></textarea>
																	</li>
																	<li>Paste this into a new file called <span style="font-family: monospace">config.inc.php</span> in your <span style="font-family: monospace">/includes</span> directory.</li>
																	<li>Click the Continue button below.</li>
																</ol>
																<?php
                                                        }
                                                        ?>
															<table style="width: 100%; margin-top: 15px" cellspacing="0" cellpadding="1" border="0">
															<tr>
																<td style="text-align: right; border-top: 2px #CCC solid; padding-top: 5px" colspan="2">
																	<input type="submit" value="Continue" />
																</td>
															</tr>
															</table>
															</form>
															<?php
                                                        break;
                                                    case 2:
                                                        $PASSED = true;

                                                        $register_globals = (bool) ini_get('register_globals');
                                                        $php_version = phpversion();
                                                        $php_verison_required = '7.1.3';
                                                        ?>
															Welcome to the ListMessenger <?php echo VERSION_TYPE.' '.VERSION_INFO; ?> setup program that will help you install ListMessenger for the first time or upgrade your current ListMessenger database to this new version.
															<h2>Requirements Check</h2>
															<form action="./setup.php" method="post">
															<input type="hidden" name="step" value="3" />
															<ul class="setup">
															<?php
                                                        if (version_compare($php_version, $php_verison_required, '<')) {
                                                            $PASSED = false;
                                                            echo "<li class=\"error-message\">\n";
                                                            echo '	PHP Version: PHP '.$php_version." is <strong style=\"color: #CC0000\">unsupported</strong>.\n";
                                                            echo '	<div class="setup-error-text">Your server is currently running PHP '.$php_version.' which must be upgraded to at least PHP '.$php_verison_required.' in order to properly run ListMessenger. Please speak with your hosting provider or your server administrator about upgrading to a newer version of PHP so that you can install and run ListMessenger.</div>';
                                                            echo "</li>\n";
                                                        } else {
                                                            echo "<li class=\"success-message\">\n";
                                                            echo '	PHP Version: PHP '.$php_version." is <strong style=\"color: #669900\">supported</strong>.\n";
                                                            echo "</li>\n";
                                                        }

                                                        if (!function_exists('ini_get')) {
                                                            $PASSED = false;
                                                            echo "<li class=\"error-message\">\n";
                                                            echo "	PHP Function: ini_get() is <strong style=\"color: #CC0000\">disabled</strong>.\n";
                                                            echo '	<div class="setup-error-text">It appears as though your hosting provider has disabled the ini_get() function in PHP. ListMessenger requires that this function be enabled so it is able to read information about how your environment is setup.</div>';
                                                            echo "</li>\n";
                                                        } else {
                                                            echo "<li class=\"success-message\">\n";
                                                            echo "	PHP Function: ini_get() is <strong style=\"color: #669900\">enabled</strong>.\n";
                                                            echo "</li>\n";
                                                        }

                                                        if (!function_exists('ini_set')) {
                                                            $PASSED = false;
                                                            echo "<li class=\"error-message\">\n";
                                                            echo "	PHP Function: ini_set() is <strong style=\"color: #CC0000\">disabled</strong>.\n";
                                                            echo "	<div class=\"setup-error-text\">It appears as though your hosting provider has disabled the ini_set() function in PHP. ListMessenger requires that this function be enabled so it is able to set up your environment to run the application.</div>\n";
                                                            echo "</li>\n";
                                                        } else {
                                                            echo "<li class=\"success-message\">\n";
                                                            echo "	PHP Function: ini_set() is <strong style=\"color: #669900\">enabled</strong>.\n";
                                                            echo "</li>\n";
                                                        }

                                                        if ($PASSED) {
                                                            if (!function_exists('file_exists')) {
                                                                $PASSED = false;
                                                                echo "<li class=\"error-message\">\n";
                                                                echo "	PHP Function: file_exists() is <strong style=\"color: #CC0000\">disabled</strong>.\n";
                                                                echo '	<div class="setup-error-text">It appears as though your hosting provider has disabled the file_exists() function in PHP. ListMessenger uses the file_exists() function, so it needs to be re-enabled in PHP.</div>';
                                                                echo "</li>\n";
                                                            }

                                                            if (!function_exists('fopen')) {
                                                                $PASSED = false;
                                                                echo "<li class=\"error-message\">\n";
                                                                echo "	PHP Function: fopen() is <strong style=\"color: #CC0000\">disabled</strong>.\n";
                                                                echo '	<div class="setup-error-text">It appears as though your hosting provider has disabled the fopen() function in PHP. ListMessenger uses the fopen() function, so it needs to be re-enabled in PHP.</div>';
                                                                echo "</li>\n";
                                                            }

                                                            if (!function_exists('fwrite')) {
                                                                $PASSED = false;
                                                                echo "<li class=\"error-message\">\n";
                                                                echo "	PHP Function: fwrite() is <strong style=\"color: #CC0000\">disabled</strong>.\n";
                                                                echo '	<div class="setup-error-text">It appears as though your hosting provider has disabled the fwrite() function in PHP. ListMessenger uses the fwrite() function, so it needs to be re-enabled in PHP.</div>';
                                                                echo "</li>\n";
                                                            }

                                                            if (!function_exists('fsockopen')) {
                                                                echo "<li class=\"notice-message\">\n";
                                                                echo "	PHP Function: fsockopen() is <strong style=\"color: #FF9900\">disabled</strong>.\n";
                                                                echo "	<div class=\"setup-error-text\">It appears as though your hosting provider has disabled the fsockopen() function in PHP. You can still run ListMessenger but some features (i.e. Program Update, PHP Support in Template Files, Sending messages by SMTP) will not function.</div>\n";
                                                                echo "</li>\n";
                                                            }

                                                            if (!function_exists('pspell_new')) {
                                                                echo "<li class=\"notice-message\">\n";
                                                                echo "	PHP Extension: pSpell support is <strong style=\"color: #FF9900\">not available</strong>.\n";
                                                                echo "	<div class=\"setup-error-text\">You do not appear to have <a href=\"http://php.net/pspell\" target=\"_blank\" style=\"font-weight: normal\">pSpell / aSpell support</a> compiled with your PHP installation. This means that while you can continue to use ListMessenger without issue, you will not be able to use the built in spell checking.</div>\n";
                                                                echo "</li>\n";
                                                            } else {
                                                                echo "<li class=\"success-message\">\n";
                                                                echo "	PHP Extension: pSpell support is <strong style=\"color: #669900\">available</strong>.\n";
                                                                echo "</li>\n";
                                                            }
                                                        }
                                                        ?>
															</ul>

															<?php
                                                        if ($PASSED) {
                                                            ?>
																<h2 style="margin-top: 25px">Database Setup</h2>
																ListMessenger requires a connection to a MySQL server to operate. Please provide your MySQL connection details below. If you are unsure of this information please contact your <strong>web-hosting provider</strong> before you continue.
																<table style="width: 100%" cellspacing="0" cellpadding="1" border="0">
																	<colgroup>
																		<col width="20%" />
																		<col width="80%" />
																	</colgroup>
																	<tbody>
																		<tr>
																			<td colspan="2">
																			<?php
                                                                        /**
                                                                         * Check for config file or get database connection details.
                                                                         */
                                                                        if ((empty($PROCESSED) || empty($PROCESSED)) && file_exists(dirname(__FILE__).'/includes/config.inc.php')) {
                                                                            require_once dirname(__FILE__).'/includes/config.inc.php';

                                                                            if (!defined('DATABASE_TYPE') || !in_array(DATABASE_TYPE, ['mysqli']) ||
                                                                                !defined('DATABASE_HOST') || !DATABASE_HOST ||
                                                                                !defined('DATABASE_NAME') || !DATABASE_NAME ||
                                                                                !defined('DATABASE_USER') || !DATABASE_USER ||
                                                                                !defined('DATABASE_PASS') || !DATABASE_PASS) {
                                                                                $PASSED = false;
                                                                            } else {
                                                                                $PROCESSED = [
                                                                                    'database_type' => DATABASE_TYPE,
                                                                                    'database_host' => DATABASE_HOST,
                                                                                    'database_name' => DATABASE_NAME,
                                                                                    'database_user' => DATABASE_USER,
                                                                                    'database_pass' => DATABASE_PASS,
                                                                                    'tables_prefix' => ((defined('TABLES_PREFIX') && (TABLES_PREFIX != '')) ? TABLES_PREFIX : ''),
                                                                                    'database_pconnect' => ((defined('DATABASE_PCONNECT') && DATABASE_PCONNECT === true) ? true : false),
                                                                                ];

                                                                                $db = NewADOConnection($PROCESSED['database_type']);
                                                                                if ($PROCESSED['database_pconnect']) {
                                                                                    if ($db->PConnect($PROCESSED['database_host'], $PROCESSED['database_user'], $PROCESSED['database_pass'], $PROCESSED['database_name']) && $db->isConnected()) {
                                                                                        $PASSED = true;
                                                                                    } else {
                                                                                        $PASSED = false;
                                                                                    }
                                                                                } else {
                                                                                    if ($db->Connect($PROCESSED['database_host'], $PROCESSED['database_user'], $PROCESSED['database_pass'], $PROCESSED['database_name']) && $db->isConnected()) {
                                                                                        $PASSED = true;
                                                                                    } else {
                                                                                        $PASSED = false;
                                                                                    }
                                                                                }

                                                                                if ($PASSED) {
                                                                                    foreach ($PROCESSED as $key => $value) {
                                                                                        echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value)."\" />\n";
                                                                                    }
                                                                                    ?>
																						<ul class="setup">
																							<li class="success-message">The <strong>existing</strong> database connection information in your config.inc.php file is <strong style="color: #669900">valid</strong>.</li>
																						</ul>
																						<?php
                                                                                } else {
                                                                                    ?>
																						<ul class="setup">
																							<li class="error-message">
																								We were unable to connect to the database server using the <strong>existing</strong> database connection information in your config.inc.php file.
																								<br /><br />
																								<strong>Database Server Response</strong>
																								<div style="font-family: monospace">
																									<?php echo $db->ErrorMsg(); ?>
																								</div>
																							</li>
																						</ul>
																						<?php
                                                                                }
                                                                            }
                                                                        } else {
                                                                            if ($ERROR) {
                                                                                echo '<br /><br />'.display_error($ERRORSTR);
                                                                            }

                                                                            $PASSED = false;
                                                                        }
                                                            ?>
																			</td>
																		</tr>
																		<?php
                                                                        /**
                                                                         * Request database connection information from user.
                                                                         */
                                                                        if (!$PASSED) {
                                                                            $PASSED = true;
                                                                            ?>
																			<tr>
																				<td colspan="2">&nbsp;</td>
																			</tr>
																			<tr>
																				<td>
																					<?php echo create_tooltip('Database Adapter', "<strong><em>Database Adapter</em></strong><br />ListMessenger connects to either MariaDB or MySQL using PHP's <a href=\"http://php.net/mysqli\" target=\"_blank\">MySQLi</a> extension.", true); ?>
																				</td>
																				<td>
																					<select id="database_type" name="database_type" style="width: 205px">
																						<option value="mysqli"<?php echo (empty($PROCESSED['database_type']) || ($PROCESSED['database_type'] == 'mysqli')) ? ' selected="selected"' : ''; ?>>MySQLi</option>
																					</select>
																				</td>
																			</tr>
																			<tr>
																				<td>
																					<?php echo create_tooltip('MySQL Hostname', '<strong><em>MySQL Hostname</em></strong><br />The hostname of the MySQL server you would like ListMessenger to connect to.<br /><br />For many web-hosting providers this option can simply be left as &quot;localhost&quot;.<br /><br />Please contact your <strong>web-hosting</strong> provider to obtain this information if you are unsure what to provide here.', true); ?>
																				</td>
																				<td>
																					<input type="text" id="database_host" name="database_host" value="<?php echo !empty($PROCESSED['database_host']) && $PROCESSED['database_host'] ? $PROCESSED['database_host'] : 'localhost'; ?>" style="width: 200px" />
																				</td>
																			</tr>
																			<tr>
																				<td colspan="2">&nbsp;</td>
																			</tr>
																			<tr>
																				<td>
																					<?php echo create_tooltip('MySQL Database', '<strong><em>MySQL Database</em></strong><br />The name of the MySQL database you would like ListMessenger to use on your mysql server.<br /><br />Please contact your <strong>web-hosting</strong> provider to obtain this information if you are unsure what to provide here.', true); ?>
																				</td>
																				<td>
																					<input type="text" id="database_name" name="database_name" value="<?php echo !empty($PROCESSED['database_name']) && $PROCESSED['database_name'] ? $PROCESSED['database_name'] : ''; ?>" style="width: 200px" />
																				</td>
																			</tr>
																			<tr>
																				<td colspan="2">&nbsp;</td>
																			</tr>
																			<tr>
																				<td>
																					<?php echo create_tooltip('MySQL Username', '<strong><em>MySQL Username</em></strong><br />The MySQL username you would like ListMessenger to use to connect to your MySQL server.<br /><br />Please contact your <strong>web-hosting</strong> provider to obtain this information if you are unsure what to provide here.', true); ?>
																				</td>
																				<td>
																					<input type="text" id="database_user" name="database_user" value="<?php echo !empty($PROCESSED['database_user']) && $PROCESSED['database_user'] ? $PROCESSED['database_user'] : ''; ?>" style="width: 200px" />
																				</td>
																			</tr>
																			<tr>
																				<td>
																					<?php echo create_tooltip('MySQL Password', '<strong><em>MySQL Password</em></strong><br />The password for the MySQL username listed above.<br /><br />Please contact your <strong>web-hosting</strong> provider to obtain this information if you are unsure what to provide here.', true); ?>
																				</td>
																				<td>
																					<input type="password" id="database_pass" name="database_pass" value="<?php echo !empty($PROCESSED['database_pass']) && $PROCESSED['database_pass'] ? $PROCESSED['database_pass'] : ''; ?>" style="width: 200px" />
																				</td>
																			</tr>
																			<tr>
																				<td colspan="2">&nbsp;</td>
																			</tr>
																			<tr>
																				<td>
																					<?php echo create_tooltip('Table Prefix', '<strong><em>Database Table Prefix</em></strong><br />Providing a database table prefix allows you to install multiple versions of ListMessenger in the same database, and avoid table collisions with other appications.<br /><br />It is recommended that you provide a table prefix if this is your first time installing ListMessenger.', false); ?>
																				</td>
																				<td>
																					<input type="text" id="tables_prefix" name="tables_prefix" value="<?php echo !empty($PROCESSED['tables_prefix']) && $PROCESSED['tables_prefix'] ? $PROCESSED['tables_prefix'] : (!defined('TABLES_PREFIX') ? 'lm_' : ''); ?>" style="width: 200px" />
																				</td>
																			</tr>
																			<?php
                                                                        }
                                                            ?>
																	</tbody>
																</table>
																<?php
                                                        }
                                                        ?>
															<br /><br />
															<table style="width: 100%; margin-top: 5px" cellspacing="0" cellpadding="1" border="0">
															<tr>
																<td style="text-align: right; border-top: 2px #CCC solid; padding-top: 5px" colspan="2">
																	<?php
                                                                if ($PASSED) {
                                                                    echo '<input type="submit" value="Continue" />';
                                                                } else {
                                                                    echo '<input type="button" value="Refresh" onclick="window.location.href = window.location" />';
                                                                }
                                                        ?>
																</td>
															</tr>
															</table>
															</form>
															<?php
                                                        break;
                                                    case 1:
                                                    default:
                                                        ?>
															<h2>Licence Agreement</h2>
															<?php
                                                        if ((!file_exists('./licence.html')) || (!is_readable('./licence.html'))) {
                                                            ++$NOTICE;
                                                            $NOTICESTR[] = 'The ListMessenger licence agreement (<a href="licence.html" target="_blank">licence.html</a>) does not appear to exist in your ListMessenger directory. Please upload the licence.html file from the ListMessenger distribution archive into your ListMessenger directory before running the setup program.';

                                                            echo display_notice($NOTICESTR);
                                                            ?>
																<div style="text-align: right; border-top: 2px #CCC solid; margin-top: 5px; padding-top: 5px">
																	<input type="button" value="Refresh" onclick="window.location.href = window.location" />
																</div>
																<?php
                                                        } else {
                                                            ?>
																Before we begin you must <strong>agree</strong> to the following licence agreement:
																<iframe style="width: 99%; height: 350px; border:0; margin:0; padding:0" src="licence.html"></iframe>
																<div style="text-align: right; border-top: 2px #CCC solid; margin-top: 5px; padding-top: 5px">
																	<form action="./setup.php" method="post">
																	<input type="hidden" name="step" value="2" />
																	<input type="button" value="Do Not Agree" onclick="window.location='https://listmessenger.com'" />
																	<input type="submit" value="I Agree" />
																	</form>
																</div>
																<?php
                                                        }
                                                        break;
                                                }
?>
													<br />
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
	</body>
	</html>
