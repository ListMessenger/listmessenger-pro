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
 * Loads all configuration settings into $_SESSION["config"] session array.
 */
if (!load_configuration()) {
    if (file_exists('./setup.php')) {
        header('Location: setup.php');
        exit;
    } else {
        ++$ERROR;
        $ERRORSTR[] = 'Unable to load your ListMessenger application settings.<br /><br />The most common cause of this error is that you have not yet run the <a href="setup.php" style="font-weight: bold">ListMessenger Setup Program</a>. Please do this now.<br /><br />If you have previously run the setup program, please check to see if changes have been made to your '.DATABASE_TYPE.' database server connection or database name.';
    }
    $CONFIG_LOADED = false;
} else {
    $CONFIG_LOADED = true;
}

/*
 * Check to see if the rich text editor should be loaded.
 */
rte_loader();

/*
 * Ensure script is not being loaded from the sending engine.
 */
if (!defined('IN_SENDING_ENGINE')) {
    /*
     * Perform routine maintenance once per session.
     */
    perform_maintenance(((!empty($_SESSION['doneMaintenance'])) && ((bool) $_SESSION['doneMaintenance'])) ? true : false);
}

/*
 * This loads the constants based on the E-Mail Configuration preferences.
 */
if (!empty($_SESSION['config'][PREF_MAILER_BY_ID])) {
    switch (trim($_SESSION['config'][PREF_MAILER_BY_ID])) {
        case 'sendmail':
            define('MAIL_BY', 'sendmail');

            if ((!empty($_SESSION['config'][PREF_MAILER_BY_VALUE])) && (trim($_SESSION['config'][PREF_MAILER_BY_VALUE]) != '')) {
                define('SENDMAIL_PATH', trim($_SESSION['config'][PREF_MAILER_BY_VALUE]));
            } else {
                define('SENDMAIL_PATH', '/usr/bin/sendmail');
            }
            break;
        case 'smtp':
            define('MAIL_BY', 'smtp');

            /*
             * Configure and set SMTP hosts.
             */
            if ((!empty($_SESSION['config'][PREF_MAILER_BY_VALUE])) && (trim($_SESSION['config'][PREF_MAILER_BY_VALUE]) != '')) {
                define('SMTP_HOSTS', trim($_SESSION['config'][PREF_MAILER_BY_VALUE]));
            } else {
                define('SMTP_HOSTS', 'localhost');
            }

            /*
             * Determine and set SMTP keep alive option.
             */
            if (!empty($_SESSION['config'][PREF_MAILER_SMTP_KALIVE])) {
                if (in_array(trim($_SESSION['config'][PREF_MAILER_SMTP_KALIVE]), ['yes', 'true'])) {
                    define('SMTP_KEEP_ALIVE', true);
                } else {
                    define('SMTP_KEEP_ALIVE', false);
                }
            } else {
                define('SMTP_KEEP_ALIVE', true);
            }

            /*
             * Determine and set SMTP authentication options.
             */
            if (!empty($_SESSION['config'][PREF_MAILER_AUTH_ID])) {
                if (in_array(trim($_SESSION['config'][PREF_MAILER_AUTH_ID]), ['yes', 'true'])) {
                    define('SMTP_AUTH', true);

                    if ((!empty($_SESSION['config'][PREF_MAILER_AUTHUSER_ID])) && (trim($_SESSION['config'][PREF_MAILER_AUTHUSER_ID]) != '')) {
                        define('SMTP_AUTH_USER', trim($_SESSION['config'][PREF_MAILER_AUTHUSER_ID]));

                        if ((!empty($_SESSION['config'][PREF_MAILER_AUTHPASS_ID])) && (trim($_SESSION['config'][PREF_MAILER_AUTHPASS_ID]) != '')) {
                            define('SMTP_AUTH_PASS', trim($_SESSION['config'][PREF_MAILER_AUTHPASS_ID]));
                        } else {
                            define('SMTP_AUTH_PASS', '');
                        }
                    } else {
                        define('SMTP_AUTH_USER', '');
                        define('SMTP_AUTH_PASS', '');
                    }
                } else {
                    define('SMTP_AUTH', false);
                    define('SMTP_AUTH_USER', '');
                    define('SMTP_AUTH_PASS', '');
                }
            } else {
                define('SMTP_AUTH', false);
                define('SMTP_AUTH_USER', '');
                define('SMTP_AUTH_PASS', '');
            }
            break;
        case 'mailadvanced':
            define('MAIL_BY', 'mailadvanced');
            break;
        case 'mail':
        default:
            define('MAIL_BY', 'mail');
            break;
    }
} else {
    define('MAIL_BY', 'mail');
}

/**
 * Listing of all preferences that contain paths or URL's. This is used
 * in the backup & restore process.
 */
$PREFERENCES_SKIP = [];
$PREFERENCES_SKIP[] = PREF_PROPATH_ID;
$PREFERENCES_SKIP[] = PREF_PROGURL_ID;
$PREFERENCES_SKIP[] = PREF_PUBLIC_PATH;
$PREFERENCES_SKIP[] = PREF_PUBLIC_URL;
$PREFERENCES_SKIP[] = PREF_PRIVATE_PATH;

/**
 * Listing of all available character sets supported by PHP.
 */
$CHARACTER_SETS = [];
$CHARACTER_SETS['ISO-8859-1'] = 'Western European, Latin-1 (default).';
$CHARACTER_SETS['ISO-8859-15'] = 'Western European, Latin-9.';
$CHARACTER_SETS['UTF-8'] = 'ASCII compatible multi-byte 8-bit Unicode.';
$CHARACTER_SETS['cp1252'] = 'Windows-1252: Windows specific charset for Western European.';
$CHARACTER_SETS['BIG5'] = 'Traditional Chinese, mainly used in Taiwan.';
$CHARACTER_SETS['GB2312'] = 'Simplified Chinese, national standard character set.';
$CHARACTER_SETS['BIG5-HKSCS'] = 'Big5 with Hong Kong extensions, Traditional Chinese.';
$CHARACTER_SETS['Shift_JIS'] = 'Japanese.';
$CHARACTER_SETS['EUC-JP'] = 'Japanese.';
$CHARACTER_SETS['cp866'] = 'DOS-specific Cyrillic charset.';
$CHARACTER_SETS['cp1251'] = 'Windows-1251: Windows-specific Cyrillic charset.';
$CHARACTER_SETS['KOI8-R'] = 'Russian.';
