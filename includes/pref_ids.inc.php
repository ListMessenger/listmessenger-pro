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
 * Program Preferences
 *
 */
define('PREF_ADMUSER_ID', 8);
define('PREF_ADMPASS_ID', 1);
define('PREF_FRMNAME_ID', 2);
define('PREF_FRMEMAL_ID', 3);
define('PREF_RPYEMAL_ID', 4);
define('PREF_ABUEMAL_ID', 35);
define('PREF_ERREMAL_ID', 19);
define('PREF_ADMEMAL_ID', 43);
define('PREF_PROPATH_ID', 14);
define('PREF_PROGURL_ID', 15);
define('PREF_PUBLIC_PATH', 16);
define('PREF_PUBLIC_URL', 47);
define('PREF_PRIVATE_PATH', 48);
define('PREF_DEFAULT_CHARSET', 49);
define('PREF_ENCODING_STYLE', 66);
define('PREF_DATEFORMAT', 23);
define('PREF_USERTE', 30);
define('PREF_PERPAGE_ID', 9);
define('PREF_ERROR_LOGGING', 51);
define('PREF_TIMEZONE', 52);
define('PREF_DAYLIGHT_SAVINGS', 45);

/*
 * End-User Preferences
 *
 */
define('ENDUSER_UNSUBCON', 36);
define('ENDUSER_SUBCON', 41);
define('PREF_EXPIRE_CONFIRM', 50);
define('ENDUSER_NEWSUBNOTICE', 7);
define('ENDUSER_UNSUBNOTICE', 31);
define('PREF_FOPEN_URL', 32);
define('ENDUSER_MXRECORD', 37);
define('ENDUSER_ARCHIVE', 44);
define('ENDUSER_PROFILE', 56);
define('ENDUSER_FORWARD', 65);
define('ENDUSER_LANG_ID', 40);
define('ENDUSER_ARCHIVE_FILENAME', 10);
define('ENDUSER_CONFIRM_FILENAME', 11);
define('ENDUSER_HELP_FILENAME', 12);
define('ENDUSER_PROFILE_FILENAME', 57);
define('ENDUSER_FILENAME', 33);
define('ENDUSER_TEMPLATE', 34);
define('ENDUSER_UNSUB_FILENAME', 13);
define('ENDUSER_FORWARD_FILENAME', 64);
define('ENDUSER_BANEMAIL', 38);
define('ENDUSER_BANIPS', 39);
define('ENDUSER_CAPTCHA', 58);
define('ENDUSER_AUDIO_CAPTCHA', 59);
define('ENDUSER_FLITE_PATH', 60);
define('PREF_POSTSUBSCRIBE_MSG', 54);
define('PREF_POSTUNSUBSCRIBE_MSG', 55);

/*
 * E-Mail Configuration
 *
 */
define('PREF_WORDWRAP', 17);
define('PREF_ADD_UNSUB_LINK', 46);
define('PREF_ADD_UNSUB_GROUP', 67);
define('PREF_MSG_PER_REFRESH', 18);
define('PREF_PAUSE_BETWEEN', 24);
define('PREF_QUEUE_TIMEOUT', 42);
define('PREF_MAILER_BY_ID', 25);
define('PREF_MAILER_BY_VALUE', 26);
define('PREF_MAILER_SMTP_KALIVE', 61);
define('PREF_MAILER_AUTH_ID', 27);
define('PREF_MAILER_AUTHUSER_ID', 28);
define('PREF_MAILER_AUTHPASS_ID', 29);
define('PREF_MAILER_LE', 69);
define('PREF_MAILER_INC_NAME', 70);

/*
 * About Information
 *
 */
define('VERSION_TYPE', 'Pro');
define('VERSION_INFO', '2.2.3');
define('VERSION_BUILD', '0');
define('REG_DOMAIN', 6);
define('REG_NAME', 20);
define('REG_EMAIL', 21);
define('REG_SERIAL', 22);
define('PREF_SERIAL_ID', 22);
define('PREF_VERSION', 5);

/*
 * Miscellaneous Settings
 *
 */
define('PASSWORD_RESET_HASH', 68);
define('MAXIMUM_MESSAGE_SIZE', 256000);
define('MAINTENANCE_PERFORMED', 53);
define('PREF_COOKIE_TIMEOUT', time() + 604800);

/*
 * Database Session Support ( yes | no )
 * If you would like database session support enabled, switch this
 * value to yes.
 *
 */
define('PREF_DATABASE_SESSIONS', 'no');

/*
 * Whether or not ListMessenger is in development mode or not. You really
 * do not need to enable this; it is just for us developers so we see a
 * bit more of what's going on within the ListMessenger.
 *
 */
define('DEVELOPMENT_MODE', ((bool) getenv('DEVELOPMENT_MODE')) ? true : false);

/*
 * An easy work around to allow admins to require the first and last
 * name of the subscriber if they choose.
 *
 */
define('ENDUSER_REQUIRE_FIRSTNAME', 62);
define('ENDUSER_REQUIRE_LASTNAME', 63);

/**
 * All reserved variables that by default will be searched and replaced.
 */
$RESERVED_VARIABLES = [];
$RESERVED_VARIABLES[] = 'name';
$RESERVED_VARIABLES[] = 'firstname';
$RESERVED_VARIABLES[] = 'lastname';
$RESERVED_VARIABLES[] = 'email';
$RESERVED_VARIABLES[] = 'email_address';
$RESERVED_VARIABLES[] = 'date';
$RESERVED_VARIABLES[] = 'groupname';
$RESERVED_VARIABLES[] = 'groupid';
$RESERVED_VARIABLES[] = 'userid';
$RESERVED_VARIABLES[] = 'messageid';
$RESERVED_VARIABLES[] = 'password';
$RESERVED_VARIABLES[] = 'signupdate';
$RESERVED_VARIABLES[] = 'archiveurl';
$RESERVED_VARIABLES[] = 'profileurl';
$RESERVED_VARIABLES[] = 'forwardurl';
$RESERVED_VARIABLES[] = 'unsubscribe';
$RESERVED_VARIABLES[] = 'unsubscribeurl';
$RESERVED_VARIABLES[] = 'message';
$RESERVED_VARIABLES[] = 'title';
$RESERVED_VARIABLES[] = 'language';
$RESERVED_VARIABLES[] = 'template';
$RESERVED_VARIABLES[] = 'action';
$RESERVED_VARIABLES[] = 'g';
$RESERVED_VARIABLES[] = 'addr';
