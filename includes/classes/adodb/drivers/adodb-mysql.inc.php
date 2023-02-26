<?php
/**
 * MySQL driver.
 *
 * @deprecated
 *
 * This driver only supports the original non-transactional MySQL driver,
 * which was deprecated in PHP version 5.5 and removed in PHP version 7.
 * It is deprecated as of ADOdb version 5.20.0, use the mysqli driver
 * instead, which supports both transactional and non-transactional updates.
 *
 * This file is part of ADOdb, a Database Abstraction Layer library for PHP.
 * @see https://adodb.org Project's web site and documentation
 * @see https://github.com/ADOdb/ADOdb Source code and issue tracker
 *
 * The ADOdb Library is dual-licensed, released under both the BSD 3-Clause
 * and the GNU Lesser General Public Licence (LGPL) v2.1 or, at your option,
 * any later version. This means you can use it in proprietary products.
 * See the LICENSE.md file distributed with this source code for details.
 *
 * @license BSD-3-Clause
 * @license LGPL-2.1-or-later
 * @copyright 2000-2013 John Lim
 * @copyright 2014 Damien Regad, Mark Newnham and the ADOdb community
 */

// security - hide paths
if (!defined('ADODB_DIR')) {
    exit;
}

if (!defined('_ADODB_MYSQL_LAYER')) {
    define('_ADODB_MYSQL_LAYER', 1);

    class ADODB_mysql extends ADOConnection
    {
        public $databaseType = 'mysql';
        public $dataProvider = 'mysql';
        public $hasInsertID = true;
        public $hasAffectedRows = true;
        public $metaTablesSQL = "SELECT
			TABLE_NAME,
			CASE WHEN TABLE_TYPE = 'VIEW' THEN 'V' ELSE 'T' END
		FROM INFORMATION_SCHEMA.TABLES
		WHERE TABLE_SCHEMA=";
        public $metaColumnsSQL = 'SHOW COLUMNS FROM `%s`';
        public $fmtTimeStamp = "'Y-m-d H:i:s'";
        public $hasLimit = true;
        public $hasMoveFirst = true;
        public $hasGenID = true;
        public $isoDates = true; // accepts dates in ISO format
        public $sysDate = 'CURDATE()';
        public $sysTimeStamp = 'NOW()';
        public $hasTransactions = false;
        public $forceNewConnect = false;
        public $poorAffectedRows = true;
        public $clientFlags = 0;
        public $charSet = '';
        public $substr = 'substring';
        public $nameQuote = '`';		// / string to use to quote identifiers and names
        public $compat323 = false; 		// true if compat with mysql 3.23

        /**
         * ADODB_mysql constructor.
         */
        public function __construct()
        {
            if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
                $this->outp_throw(
                    'mysql extension is not supported since PHP 7.0.0, use mysqli instead',
                    __METHOD__
                );
                exit(1); // Stop execution even if not using Exceptions
            } elseif (version_compare(PHP_VERSION, '5.5.0', '>=')) {
                // If mysql extension is available just print a warning,
                // otherwise die with an error message
                if (function_exists('mysql_connect')) {
                    $this->outp('mysql extension is deprecated since PHP 5.5.0, consider using mysqli');
                } else {
                    $this->outp_throw(
                        'mysql extension is not available, use mysqli instead',
                        __METHOD__
                    );
                    exit(1); // Stop execution even if not using Exceptions
                }
            }
        }

        public function setCharSet($charset)
        {
            if (!function_exists('mysql_set_charset')) {
                return false;
            }

            if ($this->charSet !== $charset) {
                $ok = @mysql_set_charset($charset, $this->_connectionID);
                if ($ok) {
                    $this->charSet = $charset;

                    return true;
                }

                return false;
            }

            return true;
        }

        public function serverInfo()
        {
            $arr['description'] = ADOConnection::GetOne('select version()');
            $arr['version'] = ADOConnection::_findvers($arr['description']);

            return $arr;
        }

        public function ifNull($field, $ifNull)
        {
            return " IFNULL($field, $ifNull) "; // if MySQL
        }

        public function metaProcedures($NamePattern = false, $catalog = null, $schemaPattern = null)
        {
            // save old fetch mode
            global $ADODB_FETCH_MODE;

            $false = false;
            $save = $ADODB_FETCH_MODE;
            $ADODB_FETCH_MODE = ADODB_FETCH_NUM;

            if ($this->fetchMode !== false) {
                $savem = $this->SetFetchMode(false);
            }

            $procedures = [];

            // get index details

            $likepattern = '';
            if ($NamePattern) {
                $likepattern = " LIKE '".$NamePattern."'";
            }
            $rs = $this->Execute('SHOW PROCEDURE STATUS'.$likepattern);
            if (is_object($rs)) {
                // parse index data into array
                while ($row = $rs->FetchRow()) {
                    $procedures[$row[1]] = [
                        'type' => 'PROCEDURE',
                        'catalog' => '',
                        'schema' => '',
                        'remarks' => $row[7],
                    ];
                }
            }

            $rs = $this->Execute('SHOW FUNCTION STATUS'.$likepattern);
            if (is_object($rs)) {
                // parse index data into array
                while ($row = $rs->FetchRow()) {
                    $procedures[$row[1]] = [
                        'type' => 'FUNCTION',
                        'catalog' => '',
                        'schema' => '',
                        'remarks' => $row[7],
                    ];
                }
            }

            // restore fetchmode
            if (isset($savem)) {
                $this->SetFetchMode($savem);
            }
            $ADODB_FETCH_MODE = $save;

            return $procedures;
        }

        /**
         * Retrieves a list of tables based on given criteria.
         *
         * @param string $ttype      Table type = 'TABLE', 'VIEW' or false=both (default)
         * @param string $showSchema schema name, false = current schema (default)
         * @param string $mask       filters the table by name
         *
         * @return array list of tables
         */
        public function metaTables($ttype = false, $showSchema = false, $mask = false)
        {
            $save = $this->metaTablesSQL;
            if ($showSchema && is_string($showSchema)) {
                $this->metaTablesSQL .= $this->qstr($showSchema);
            } else {
                $this->metaTablesSQL .= 'schema()';
            }

            if ($mask) {
                $mask = $this->qstr($mask);
                $this->metaTablesSQL .= " AND table_name LIKE $mask";
            }
            $ret = ADOConnection::MetaTables($ttype, $showSchema);

            $this->metaTablesSQL = $save;

            return $ret;
        }

        public function metaIndexes($table, $primary = false, $owner = false)
        {
            // save old fetch mode
            global $ADODB_FETCH_MODE;

            $false = false;
            $save = $ADODB_FETCH_MODE;
            $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
            if ($this->fetchMode !== false) {
                $savem = $this->SetFetchMode(false);
            }

            // get index details
            $rs = $this->Execute(sprintf('SHOW INDEX FROM %s', $table));

            // restore fetchmode
            if (isset($savem)) {
                $this->SetFetchMode($savem);
            }
            $ADODB_FETCH_MODE = $save;

            if (!is_object($rs)) {
                return $false;
            }

            $indexes = [];

            // parse index data into array
            while ($row = $rs->FetchRow()) {
                if ($primary == false and $row[2] == 'PRIMARY') {
                    continue;
                }

                if (!isset($indexes[$row[2]])) {
                    $indexes[$row[2]] = [
                        'unique' => ($row[1] == 0),
                        'columns' => [],
                    ];
                }

                $indexes[$row[2]]['columns'][$row[3] - 1] = $row[4];
            }

            // sort columns by order in the index
            foreach (array_keys($indexes) as $index) {
                ksort($indexes[$index]['columns']);
            }

            return $indexes;
        }

        /**
         * Appropriately quotes strings with ' characters for insertion into the database.
         *
         * Relies on mysql_real_escape_string()
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:qstr
         *
         * @param string $s            The string to quote
         * @param bool   $magic_quotes This param is not used since 5.21.0.
         *                             It remains for backwards compatibility.
         *
         * @return string Quoted string
         */
        public function qStr($s, $magic_quotes = false)
        {
            if (is_null($s)) {
                return 'NULL';
            }

            if (is_resource($this->_connectionID)) {
                return "'".mysql_real_escape_string($s, $this->_connectionID)."'";
            }

            if ($this->replaceQuote[0] == '\\') {
                $s = str_replace(['\\', "\0"], ['\\\\', "\\\0"], $s);
            }

            return "'".str_replace("'", $this->replaceQuote, $s)."'";
        }

        protected function _insertID($table = '', $column = '')
        {
            return ADOConnection::GetOne('SELECT LAST_INSERT_ID()');
            // return mysql_insert_id($this->_connectionID);
        }

        public function getOne($sql, $inputarr = false)
        {
            global $ADODB_GETONE_EOF;
            if ($this->compat323 == false && strncasecmp($sql, 'sele', 4) == 0) {
                $rs = $this->SelectLimit($sql, 1, -1, $inputarr);
                if ($rs) {
                    $rs->Close();
                    if ($rs->EOF) {
                        return $ADODB_GETONE_EOF;
                    }

                    return reset($rs->fields);
                }
            } else {
                return ADOConnection::GetOne($sql, $inputarr);
            }

            return false;
        }

        public function beginTrans()
        {
            if ($this->debug) {
                ADOConnection::outp("Transactions not supported in 'mysql' driver. Use 'mysqlt' or 'mysqli' driver");
            }
        }

        public function _affectedrows()
        {
            return mysql_affected_rows($this->_connectionID);
        }

         // See http://www.mysql.com/doc/M/i/Miscellaneous_functions.html
        // Reference on Last_Insert_ID on the recommended way to simulate sequences
        public $_genIDSQL = 'update %s set id=LAST_INSERT_ID(id+1);';
        public $_genSeqSQL = 'create table if not exists %s (id int not null)';
        public $_genSeqCountSQL = 'select count(*) from %s';
        public $_genSeq2SQL = 'insert into %s values (%s)';
        public $_dropSeqSQL = 'drop table if exists %s';

        public function createSequence($seqname = 'adodbseq', $startID = 1)
        {
            if (empty($this->_genSeqSQL)) {
                return false;
            }
            $u = strtoupper($seqname);

            $ok = $this->Execute(sprintf($this->_genSeqSQL, $seqname));
            if (!$ok) {
                return false;
            }

            return $this->Execute(sprintf($this->_genSeq2SQL, $seqname, $startID - 1));
        }

        public function genID($seqname = 'adodbseq', $startID = 1)
        {
            // post-nuke sets hasGenID to false
            if (!$this->hasGenID) {
                return false;
            }

            $savelog = $this->_logsql;
            $this->_logsql = false;
            $getnext = sprintf($this->_genIDSQL, $seqname);
            $holdtransOK = $this->_transOK; // save the current status
            $rs = $this->Execute($getnext);
            if (!$rs) {
                if ($holdtransOK) {
                    $this->_transOK = true;
                } // if the status was ok before reset
                $u = strtoupper($seqname);
                $this->Execute(sprintf($this->_genSeqSQL, $seqname));
                $cnt = $this->GetOne(sprintf($this->_genSeqCountSQL, $seqname));
                if (!$cnt) {
                    $this->Execute(sprintf($this->_genSeq2SQL, $seqname, $startID - 1));
                }
                $rs = $this->Execute($getnext);
            }

            if ($rs) {
                $this->genID = mysql_insert_id($this->_connectionID);
                $rs->Close();
            } else {
                $this->genID = 0;
            }

            $this->_logsql = $savelog;

            return $this->genID;
        }

        public function metaDatabases()
        {
            $qid = mysql_list_dbs($this->_connectionID);
            $arr = [];
            $i = 0;
            $max = mysql_num_rows($qid);
            while ($i < $max) {
                $db = mysql_tablename($qid, $i);
                if ($db != 'mysql') {
                    $arr[] = $db;
                }
                ++$i;
            }

            return $arr;
        }

        // Format date column in sql string given an input format that understands Y M D
        public function sqlDate($fmt, $col = false)
        {
            if (!$col) {
                $col = $this->sysTimeStamp;
            }
            $s = 'DATE_FORMAT('.$col.",'";
            $concat = false;
            $len = strlen($fmt);
            for ($i = 0; $i < $len; ++$i) {
                $ch = $fmt[$i];
                switch ($ch) {
                    default:
                        if ($ch == '\\') {
                            ++$i;
                            $ch = substr($fmt, $i, 1);
                        }
                        /* FALL THROUGH */
                        // no break
                    case '-':
                    case '/':
                        $s .= $ch;
                        break;

                    case 'Y':
                    case 'y':
                        $s .= '%Y';
                        break;
                    case 'M':
                        $s .= '%b';
                        break;

                    case 'm':
                        $s .= '%m';
                        break;
                    case 'D':
                    case 'd':
                        $s .= '%d';
                        break;

                    case 'Q':
                    case 'q':
                        $s .= "'),Quarter($col)";

                        if ($len > $i + 1) {
                            $s .= ",DATE_FORMAT($col,'";
                        } else {
                            $s .= ",('";
                        }
                        $concat = true;
                        break;

                    case 'H':
                        $s .= '%H';
                        break;

                    case 'h':
                        $s .= '%I';
                        break;

                    case 'i':
                        $s .= '%i';
                        break;

                    case 's':
                        $s .= '%s';
                        break;

                    case 'a':
                    case 'A':
                        $s .= '%p';
                        break;

                    case 'w':
                        $s .= '%w';
                        break;

                    case 'W':
                        $s .= '%U';
                        break;

                    case 'l':
                        $s .= '%W';
                        break;
                }
            }
            $s .= "')";
            if ($concat) {
                $s = "CONCAT($s)";
            }

            return $s;
        }

        // returns concatenated string
        // much easier to run "mysqld --ansi" or "mysqld --sql-mode=PIPES_AS_CONCAT" and use || operator
        public function concat()
        {
            $s = '';
            $arr = func_get_args();

            // suggestion by andrew005@mnogo.ru
            $s = implode(',', $arr);
            if (strlen($s) > 0) {
                return "CONCAT($s)";
            } else {
                return '';
            }
        }

        public function offsetDate($dayFraction, $date = false)
        {
            if (!$date) {
                $date = $this->sysDate;
            }

            $fraction = $dayFraction * 24 * 3600;

            return '('.$date.' + INTERVAL '.$fraction.' SECOND)';

            //		return "from_unixtime(unix_timestamp($date)+$fraction)";
        }

        // returns true or false
        public function _connect($argHostname, $argUsername, $argPassword, $argDatabasename)
        {
            if (!empty($this->port)) {
                $argHostname .= ':'.$this->port;
            }

            $this->_connectionID =
                mysql_connect(
                    $argHostname,
                    $argUsername,
                    $argPassword,
                    $this->forceNewConnect,
                    $this->clientFlags
                );

            if ($this->_connectionID === false) {
                return false;
            }
            if ($argDatabasename) {
                return $this->SelectDB($argDatabasename);
            }

            return true;
        }

        // returns true or false
        public function _pconnect($argHostname, $argUsername, $argPassword, $argDatabasename)
        {
            if (!empty($this->port)) {
                $argHostname .= ':'.$this->port;
            }

            $this->_connectionID =
                mysql_pconnect(
                    $argHostname,
                    $argUsername,
                    $argPassword,
                    $this->clientFlags
                );

            if ($this->_connectionID === false) {
                return false;
            }
            if ($this->autoRollback) {
                $this->RollbackTrans();
            }
            if ($argDatabasename) {
                return $this->SelectDB($argDatabasename);
            }

            return true;
        }

        public function _nconnect($argHostname, $argUsername, $argPassword, $argDatabasename)
        {
            $this->forceNewConnect = true;

            return $this->_connect($argHostname, $argUsername, $argPassword, $argDatabasename);
        }

        public function metaColumns($table, $normalize = true)
        {
            $this->_findschema($table, $schema);
            if ($schema) {
                $dbName = $this->database;
                $this->SelectDB($schema);
            }
            global $ADODB_FETCH_MODE;
            $save = $ADODB_FETCH_MODE;
            $ADODB_FETCH_MODE = ADODB_FETCH_NUM;

            if ($this->fetchMode !== false) {
                $savem = $this->SetFetchMode(false);
            }
            $rs = $this->Execute(sprintf($this->metaColumnsSQL, $table));

            if ($schema) {
                $this->SelectDB($dbName);
            }

            if (isset($savem)) {
                $this->SetFetchMode($savem);
            }
            $ADODB_FETCH_MODE = $save;
            if (!is_object($rs)) {
                $false = false;

                return $false;
            }

            $retarr = [];
            while (!$rs->EOF) {
                $fld = new ADOFieldObject();
                $fld->name = $rs->fields[0];
                $type = $rs->fields[1];

                // split type into type(length):
                $fld->scale = null;
                if (preg_match("/^(.+)\((\d+),(\d+)/", $type, $query_array)) {
                    $fld->type = $query_array[1];
                    $fld->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
                    $fld->scale = is_numeric($query_array[3]) ? $query_array[3] : -1;
                } elseif (preg_match("/^(.+)\((\d+)/", $type, $query_array)) {
                    $fld->type = $query_array[1];
                    $fld->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
                } elseif (preg_match("/^(enum)\((.*)\)$/i", $type, $query_array)) {
                    $fld->type = $query_array[1];
                    $arr = explode(',', $query_array[2]);
                    $fld->enums = $arr;
                    $zlen = max(array_map('strlen', $arr)) - 2; // PHP >= 4.0.6
                    $fld->max_length = ($zlen > 0) ? $zlen : 1;
                } else {
                    $fld->type = $type;
                    $fld->max_length = -1;
                }
                $fld->not_null = ($rs->fields[2] != 'YES');
                $fld->primary_key = ($rs->fields[3] == 'PRI');
                $fld->auto_increment = (strpos($rs->fields[5], 'auto_increment') !== false);
                $fld->binary = (strpos($type, 'blob') !== false || strpos($type, 'binary') !== false);
                $fld->unsigned = (strpos($type, 'unsigned') !== false);
                $fld->zerofill = (strpos($type, 'zerofill') !== false);

                if (!$fld->binary) {
                    $d = $rs->fields[4];
                    if ($d != '' && $d != 'NULL') {
                        $fld->has_default = true;
                        $fld->default_value = $d;
                    } else {
                        $fld->has_default = false;
                    }
                }

                if ($save == ADODB_FETCH_NUM) {
                    $retarr[] = $fld;
                } else {
                    $retarr[strtoupper($fld->name)] = $fld;
                }
                $rs->MoveNext();
            }

            $rs->Close();

            return $retarr;
        }

        // returns true or false
        public function selectDB($dbName)
        {
            $this->database = $dbName;
            $this->databaseName = $dbName; // obsolete, retained for compat with older adodb versions
            if ($this->_connectionID) {
                return @mysql_select_db($dbName, $this->_connectionID);
            } else {
                return false;
            }
        }

        // parameters use PostgreSQL convention, not MySQL
        public function selectLimit($sql, $nrows = -1, $offset = -1, $inputarr = false, $secs = 0)
        {
            $nrows = (int) $nrows;
            $offset = (int) $offset;
            $offsetStr = ($offset >= 0) ? ((int) $offset).',' : '';
            // jason judge, see PHPLens Issue No: 9220
            if ($nrows < 0) {
                $nrows = '18446744073709551615';
            }

            if ($secs) {
                $rs = $this->CacheExecute($secs, $sql." LIMIT $offsetStr".((int) $nrows), $inputarr);
            } else {
                $rs = $this->Execute($sql." LIMIT $offsetStr".((int) $nrows), $inputarr);
            }

            return $rs;
        }

        // returns queryID or false
        public function _query($sql, $inputarr = false)
        {
            return mysql_query($sql, $this->_connectionID);
            /*
            global $ADODB_COUNTRECS;
                if($ADODB_COUNTRECS)
                    return mysql_query($sql,$this->_connectionID);
                else
                    return @mysql_unbuffered_query($sql,$this->_connectionID); // requires PHP >= 4.0.6
            */
        }

        /*	Returns: the last error message from previous database operation */
        public function errorMsg()
        {
            if ($this->_logsql) {
                return $this->_errorMsg;
            }
            if (empty($this->_connectionID)) {
                $this->_errorMsg = @mysql_error();
            } else {
                $this->_errorMsg = @mysql_error($this->_connectionID);
            }

            return $this->_errorMsg;
        }

        /*	Returns: the last error number from previous database operation */
        public function errorNo()
        {
            if ($this->_logsql) {
                return $this->_errorCode;
            }
            if (empty($this->_connectionID)) {
                return @mysql_errno();
            } else {
                return @mysql_errno($this->_connectionID);
            }
        }

        // returns true or false
        public function _close()
        {
            @mysql_close($this->_connectionID);

            $this->charSet = '';
            $this->_connectionID = false;
        }

        /*
        * Maximum size of C field
        */
        public function charMax()
        {
            return 255;
        }

        /*
        * Maximum size of X field
        */
        public function textMax()
        {
            return 4294967295;
        }

        // "Innox - Juan Carlos Gonzalez" <jgonzalez#innox.com.mx>
        public function metaForeignKeys($table, $owner = false, $upper = false, $associative = false)
        {
            global $ADODB_FETCH_MODE;
            if ($ADODB_FETCH_MODE == ADODB_FETCH_ASSOC || $this->fetchMode == ADODB_FETCH_ASSOC) {
                $associative = true;
            }

            if (!empty($owner)) {
                $table = "$owner.$table";
            }
            $a_create_table = $this->getRow(sprintf('SHOW CREATE TABLE %s', $table));
            if ($associative) {
                $create_sql = $a_create_table['Create Table'] ?? $a_create_table['Create View'];
            } else {
                $create_sql = $a_create_table[1];
            }

            $matches = [];

            if (!preg_match_all("/FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)` \(`(.*?)`\)/", $create_sql, $matches)) {
                return false;
            }
            $foreign_keys = [];
            $num_keys = count($matches[0]);
            for ($i = 0; $i < $num_keys; ++$i) {
                $my_field = explode('`, `', $matches[1][$i]);
                $ref_table = $matches[2][$i];
                $ref_field = explode('`, `', $matches[3][$i]);

                if ($upper) {
                    $ref_table = strtoupper($ref_table);
                }

                // see https://sourceforge.net/p/adodb/bugs/100/
                if (!isset($foreign_keys[$ref_table])) {
                    $foreign_keys[$ref_table] = [];
                }
                $num_fields = count($my_field);
                for ($j = 0; $j < $num_fields; ++$j) {
                    if ($associative) {
                        $foreign_keys[$ref_table][$ref_field[$j]] = $my_field[$j];
                    } else {
                        $foreign_keys[$ref_table][] = "{$my_field[$j]}={$ref_field[$j]}";
                    }
                }
            }

            return $foreign_keys;
        }
    }

    /*--------------------------------------------------------------------------------------
         Class Name: Recordset
    --------------------------------------------------------------------------------------*/

    class ADORecordSet_mysql extends ADORecordSet
    {
        public $databaseType = 'mysql';
        public $canSeek = true;

        public function __construct($queryID, $mode = false)
        {
            if ($mode === false) {
                global $ADODB_FETCH_MODE;
                $mode = $ADODB_FETCH_MODE;
            }
            switch ($mode) {
                case ADODB_FETCH_NUM: $this->fetchMode = MYSQL_NUM;
                    break;
                case ADODB_FETCH_ASSOC:$this->fetchMode = MYSQL_ASSOC;
                    break;
                case ADODB_FETCH_DEFAULT:
                case ADODB_FETCH_BOTH:
                default:
                    $this->fetchMode = MYSQL_BOTH;
                    break;
            }
            $this->adodbFetchMode = $mode;
            parent::__construct($queryID);
        }

        public function _initrs()
        {
            // GLOBAL $ADODB_COUNTRECS;
            //	$this->_numOfRows = ($ADODB_COUNTRECS) ? @mysql_num_rows($this->_queryID):-1;
            $this->_numOfRows = @mysql_num_rows($this->_queryID);
            $this->_numOfFields = @mysql_num_fields($this->_queryID);
        }

        public function fetchField($fieldOffset = -1)
        {
            if ($fieldOffset != -1) {
                $o = @mysql_fetch_field($this->_queryID, $fieldOffset);
                $f = @mysql_field_flags($this->_queryID, $fieldOffset);
                if ($o) {
                    $o->max_length = @mysql_field_len($this->_queryID, $fieldOffset);
                } // suggested by: Jim Nicholson (jnich#att.com)
                // $o->max_length = -1; // mysql returns the max length less spaces -- so it is unrealiable
                if ($o) {
                    $o->binary = (strpos($f, 'binary') !== false);
                }
            } else {	/*	The $fieldOffset argument is not provided thus its -1 */
                $o = @mysql_fetch_field($this->_queryID);
            // if ($o) $o->max_length = @mysql_field_len($this->_queryID); // suggested by: Jim Nicholson (jnich#att.com)
                $o->max_length = -1; // mysql returns the max length less spaces -- so it is unrealiable
            }

            return $o;
        }

        public function getRowAssoc($upper = ADODB_ASSOC_CASE)
        {
            if ($this->fetchMode == MYSQL_ASSOC && $upper == ADODB_ASSOC_CASE_LOWER) {
                $row = $this->fields;
            } else {
                $row = ADORecordSet::GetRowAssoc($upper);
            }

            return $row;
        }

        /* Use associative array to get fields array */
        public function fields($colname)
        {
            // added @ by "Michael William Miller" <mille562@pilot.msu.edu>
            if ($this->fetchMode != MYSQL_NUM) {
                return $this->fields[$colname];
            }

            if (!$this->bind) {
                $this->bind = [];
                for ($i = 0; $i < $this->_numOfFields; ++$i) {
                    $o = $this->FetchField($i);
                    $this->bind[strtoupper($o->name)] = $i;
                }
            }

            return $this->fields[$this->bind[strtoupper($colname)]];
        }

        public function _seek($row)
        {
            if ($this->_numOfRows == 0) {
                return false;
            }

            return @mysql_data_seek($this->_queryID, $row);
        }

        public function moveNext()
        {
            if ($this->fields = mysql_fetch_array($this->_queryID, $this->fetchMode)) {
                $this->_updatefields();
                ++$this->_currentRow;

                return true;
            }
            if (!$this->EOF) {
                ++$this->_currentRow;
                $this->EOF = true;
            }

            return false;
        }

        public function _fetch()
        {
            $this->fields = @mysql_fetch_array($this->_queryID, $this->fetchMode);
            $this->_updatefields();

            return is_array($this->fields);
        }

        public function _close()
        {
            @mysql_free_result($this->_queryID);
            $this->_queryID = false;
        }

        public function metaType($t, $len = -1, $fieldobj = false)
        {
            if (is_object($t)) {
                $fieldobj = $t;
                $t = $fieldobj->type;
                $len = $fieldobj->max_length;
            }

            $len = -1; // mysql max_length is not accurate
            switch (strtoupper($t)) {
                case 'STRING':
                case 'CHAR':
                case 'VARCHAR':
                case 'TINYBLOB':
                case 'TINYTEXT':
                case 'ENUM':
                case 'SET':
                    if ($len <= $this->blobSize) {
                        return 'C';
                    }

                    // no break
                case 'TEXT':
                case 'LONGTEXT':
                case 'MEDIUMTEXT':
                    return 'X';

                    // php_mysql extension always returns 'blob' even if 'text'
                    // so we have to check whether binary...
                case 'IMAGE':
                case 'LONGBLOB':
                case 'BLOB':
                case 'MEDIUMBLOB':
                case 'BINARY':
                    return !empty($fieldobj->binary) ? 'B' : 'X';

                case 'YEAR':
                case 'DATE': return 'D';
                case 'TIME':
                case 'DATETIME':
                case 'TIMESTAMP': return 'T';
                case 'INT':
                case 'INTEGER':
                case 'BIGINT':
                case 'TINYINT':
                case 'MEDIUMINT':
                case 'SMALLINT':
                    if (!empty($fieldobj->primary_key)) {
                        return 'R';
                    } else {
                        return 'I';
                    }

                    // no break
                default: return ADODB_DEFAULT_METATYPE;
            }
        }
    }

    /**
     * Class ADORecordSet_ext_mysql.
     */
    class ADORecordSet_ext_mysql extends ADORecordSet_mysql
    {
        public function moveNext()
        {
            return @adodb_movenext($this);
        }
    }
}
