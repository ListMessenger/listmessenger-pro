<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */
$db = NewADOConnection(DATABASE_TYPE);
if (DATABASE_PCONNECT == true) {
    if (!$db->PConnect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME)) {
        ++$ERROR;
        $ERRORSTR[] = 'Unable to connect to your database server using persistent connections. Please ensure that the &quot;'.DATABASE_TYPE.'&quot; server on &quot;'.DATABASE_HOST.'&quot; is online and that the username &quot;'.DATABASE_USER.'&quot; is able to connect to it from &quot;'.$_SERVER['SERVER_ADDR'].'&quot;.';
    }
} else {
    if (!$db->Connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME)) {
        ++$ERROR;
        $ERRORSTR[] = 'Unable to connect to your database server. Please ensure that the &quot;'.DATABASE_TYPE.'&quot; server on &quot;'.DATABASE_HOST.'&quot; is online and that the username &quot;'.DATABASE_USER.'&quot; is able to connect to it from &quot;'.$_SERVER['SERVER_ADDR'].'&quot;.';
    }
}
$db->SetFetchMode(ADODB_FETCH_ASSOC);
$db->Execute("SET SESSION `sql_mode`=''");
$db->debug = false;

/*
 * If database driven sessions are enabled, launch them.
 */
if ((defined('PREF_DATABASE_SESSIONS') && PREF_DATABASE_SESSIONS == 'yes') && $db->IsConnected() && is_array($DATABASE_TABLES = $db->MetaTables()) && in_array(TABLES_PREFIX.'sessions', $DATABASE_TABLES)) {
    require_once 'classes/adodb/session/adodb-session2.php';

    ADOdb_Session::config(DATABASE_TYPE, DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME, ['table' => TABLES_PREFIX.'sessions']);
    ADOdb_Session::encryptionKey(DATABASE_PASS);
    ADOdb_Session::optimize(true);
    ADOdb_Session::persist(DATABASE_PCONNECT);
}
