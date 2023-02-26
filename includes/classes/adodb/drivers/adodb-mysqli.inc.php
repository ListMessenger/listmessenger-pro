<?php
/**
 * MySQL improved driver (mysqli).
 *
 * This is the preferred driver for MySQL connections. It  supports both
 * transactional and non-transactional table types. You can use this as a
 * drop-in replacement for both the mysql and mysqlt drivers.
 * As of ADOdb Version 5.20.0, all other native MySQL drivers are deprecated.
 *
 * This file is part of ADOdb, a Database Abstraction Layer library for PHP.
 *
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

if (!defined('_ADODB_MYSQLI_LAYER')) {
    define('_ADODB_MYSQLI_LAYER', 1);

    // PHP5 compat...
    if (!defined('MYSQLI_BINARY_FLAG')) {
        define('MYSQLI_BINARY_FLAG', 128);
    }
    if (!defined('MYSQLI_READ_DEFAULT_GROUP')) {
        define('MYSQLI_READ_DEFAULT_GROUP', 1);
    }

    /**
     * Class ADODB_mysqli.
     */
    class ADODB_mysqli extends ADOConnection
    {
        public $databaseType = 'mysqli';
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
        public $hasTransactions = true;
        public $forceNewConnect = false;
        public $poorAffectedRows = true;
        public $clientFlags = 0;
        public $substr = 'substring';
        public $port = 3306; // Default to 3306 to fix HHVM bug
        public $socket = ''; // Default to empty string to fix HHVM bug
        public $_bindInputArray = false;
        public $nameQuote = '`';		// / string to use to quote identifiers and names
        public $optionFlags = [[MYSQLI_READ_DEFAULT_GROUP, 0]];
        public $arrayClass = 'ADORecordSet_array_mysqli';
        public $multiQuery = false;
        public $ssl_key = null;
        public $ssl_cert = null;
        public $ssl_ca = null;
        public $ssl_capath = null;
        public $ssl_cipher = null;

        /**
         * Tells the insert_id method how to obtain the last value, depending on whether
         * we are using a stored procedure or not.
         */
        private $usePreparedStatement = false;
        private $useLastInsertStatement = false;

        /**
         * @var bool True if the last executed statement is a SELECT {@see _query()}
         */
        private $isSelectStatement = false;

        /**
         * ADODB_mysqli constructor.
         */
        public function __construct()
        {
            parent::__construct();

            // Forcing error reporting mode to OFF, which is no longer the default
            // starting with PHP 8.1 (see #755)
            mysqli_report(MYSQLI_REPORT_OFF);
        }

        /**
         * Sets the isolation level of a transaction.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:settransactionmode
         *
         * @param string $transaction_mode the transaction mode to set
         *
         * @return void
         */
        public function SetTransactionMode($transaction_mode)
        {
            $this->_transmode = $transaction_mode;
            if (empty($transaction_mode)) {
                $this->execute('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');

                return;
            }
            if (!stristr($transaction_mode, 'isolation')) {
                $transaction_mode = 'ISOLATION LEVEL '.$transaction_mode;
            }
            $this->execute('SET SESSION TRANSACTION '.$transaction_mode);
        }

        /**
         * Adds a parameter to the connection string.
         *
         * Parameter must be one of the the constants listed in mysqli_options().
         *
         * @see https://www.php.net/manual/en/mysqli.options.php
         *
         * @param int    $parameter The parameter to set
         * @param string $value     The value of the parameter
         *
         * @example, for mssqlnative driver ('CharacterSet','UTF-8')
         *
         * @return bool
         */
        public function setConnectionParameter($parameter, $value)
        {
            if (!is_numeric($parameter)) {
                $this->outp_throw("Invalid connection parameter '$parameter'", __METHOD__);

                return false;
            }
            $this->connectionParameters[$parameter] = $value;

            return true;
        }

        /**
         * Connect to a database.
         *
         * @todo add: parameter int $port, parameter string $socket
         *
         * @param string|null $argHostname     (Optional) The host to connect to
         * @param string|null $argUsername     (Optional) The username to connect as
         * @param string|null $argPassword     (Optional) The password to connect with
         * @param string|null $argDatabasename (Optional) The name of the database to start in when connected
         * @param bool        $persist         (Optional) Whether or not to use a persistent connection
         *
         * @return bool|null true if connected successfully, false if connection failed, or null if the mysqli extension
         *                   isn't currently loaded
         */
        public function _connect(
            $argHostname = null,
            $argUsername = null,
            $argPassword = null,
            $argDatabasename = null,
            $persist = false
        ) {
            if (!extension_loaded('mysqli')) {
                return null;
            }
            $this->_connectionID = @mysqli_init();

            if (is_null($this->_connectionID)) {
                // mysqli_init only fails if insufficient memory
                if ($this->debug) {
                    ADOConnection::outp('mysqli_init() failed : '.$this->errorMsg());
                }

                return false;
            }
            /*
            I suggest a simple fix which would enable adodb and mysqli driver to
            read connection options from the standard mysql configuration file
            /etc/my.cnf - "Bastien Duclaux" <bduclaux#yahoo.com>
            */
            $this->optionFlags = [];
            foreach ($this->optionFlags as $arr) {
                mysqli_options($this->_connectionID, $arr[0], $arr[1]);
            }

            // Now merge in the standard connection parameters setting
            foreach ($this->connectionParameters as $parameter => $value) {
                // Make sure parameter is numeric before calling mysqli_options()
                // that to avoid Warning (or TypeError exception on PHP 8).
                if (!is_numeric($parameter)
                    || !mysqli_options($this->_connectionID, $parameter, $value)
                ) {
                    $this->outp_throw("Invalid connection parameter '$parameter'", __METHOD__);
                }
            }

            // https://php.net/manual/en/mysqli.persistconns.php
            if ($persist && strncmp($argHostname, 'p:', 2) != 0) {
                $argHostname = 'p:'.$argHostname;
            }

            // SSL Connections for MySQLI
            if ($this->ssl_key || $this->ssl_cert || $this->ssl_ca || $this->ssl_capath || $this->ssl_cipher) {
                mysqli_ssl_set($this->_connectionID, $this->ssl_key, $this->ssl_cert, $this->ssl_ca, $this->ssl_capath, $this->ssl_cipher);
            }

            // if (!empty($this->port)) $argHostname .= ":".$this->port;
            $ok = @mysqli_real_connect(
                $this->_connectionID,
                $argHostname,
                $argUsername,
                $argPassword,
                $argDatabasename,
                // PHP7 compat: port must be int. Use default port if cast yields zero
                (int) $this->port != 0 ? (int) $this->port : 3306,
                $this->socket,
                $this->clientFlags
            );

            if ($ok) {
                if ($argDatabasename) {
                    return $this->selectDB($argDatabasename);
                }

                return true;
            } else {
                if ($this->debug) {
                    ADOConnection::outp('Could not connect : '.$this->errorMsg());
                }
                $this->_connectionID = null;

                return false;
            }
        }

        /**
         * Connect to a database with a persistent connection.
         *
         * @param string|null $argHostname     the host to connect to
         * @param string|null $argUsername     the username to connect as
         * @param string|null $argPassword     the password to connect with
         * @param string|null $argDatabasename the name of the database to start in when connected
         *
         * @return bool|null true if connected successfully, false if connection failed, or null if the mysqli extension
         *                   isn't currently loaded
         */
        public function _pconnect($argHostname, $argUsername, $argPassword, $argDatabasename)
        {
            return $this->_connect($argHostname, $argUsername, $argPassword, $argDatabasename, true);
        }

        /**
         * Connect to a database, whilst setting $this->forceNewConnect to true.
         *
         * When is this used? Close old connection first?
         * In _connect(), check $this->forceNewConnect?
         *
         * @param string|null $argHostname     the host to connect to
         * @param string|null $argUsername     the username to connect as
         * @param string|null $argPassword     the password to connect with
         * @param string|null $argDatabasename the name of the database to start in when connected
         *
         * @return bool|null true if connected successfully, false if connection failed, or null if the mysqli extension
         *                   isn't currently loaded
         */
        public function _nconnect($argHostname, $argUsername, $argPassword, $argDatabasename)
        {
            $this->forceNewConnect = true;

            return $this->_connect($argHostname, $argUsername, $argPassword, $argDatabasename);
        }

        /**
         * Replaces a null value with a specified replacement.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:ifnull
         *
         * @param mixed $field  the field in the table to check
         * @param mixed $ifNull the value to replace the null value with if it is found
         *
         * @return string
         */
        public function IfNull($field, $ifNull)
        {
            return " IFNULL($field, $ifNull) ";
        }

        /**
         * Retrieves the first column of the first matching row of an executed SQL statement.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:getone
         *
         * @param string     $sql      the SQL to execute
         * @param bool|array $inputarr (Optional) An array containing any required SQL parameters, or false if none needed
         *
         * @return bool|array|null
         */
        public function GetOne($sql, $inputarr = false)
        {
            global $ADODB_GETONE_EOF;

            $ret = false;
            $rs = $this->execute($sql, $inputarr);
            if ($rs) {
                if ($rs->EOF) {
                    $ret = $ADODB_GETONE_EOF;
                } else {
                    $ret = reset($rs->fields);
                }
                $rs->close();
            }

            return $ret;
        }

        /**
         * Get information about the current MySQL server.
         *
         * @return array
         */
        public function ServerInfo()
        {
            $arr['description'] = $this->getOne('select version()');
            $arr['version'] = ADOConnection::_findvers($arr['description']);

            return $arr;
        }

        /**
         * Begins a granular transaction.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:begintrans
         *
         * @return bool always returns true
         */
        public function BeginTrans()
        {
            if ($this->transOff) {
                return true;
            }
            ++$this->transCnt;

            // $this->execute('SET AUTOCOMMIT=0');
            mysqli_autocommit($this->_connectionID, false);
            $this->execute('BEGIN');

            return true;
        }

        /**
         * Commits a granular transaction.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:committrans
         *
         * @param bool $ok (Optional) If false, will rollback the transaction instead
         *
         * @return bool always returns true
         */
        public function CommitTrans($ok = true)
        {
            if ($this->transOff) {
                return true;
            }
            if (!$ok) {
                return $this->rollbackTrans();
            }

            if ($this->transCnt) {
                --$this->transCnt;
            }
            $this->execute('COMMIT');

            // $this->execute('SET AUTOCOMMIT=1');
            mysqli_autocommit($this->_connectionID, true);

            return true;
        }

        /**
         * Rollback a smart transaction.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:rollbacktrans
         *
         * @return bool always returns true
         */
        public function RollbackTrans()
        {
            if ($this->transOff) {
                return true;
            }
            if ($this->transCnt) {
                --$this->transCnt;
            }
            $this->execute('ROLLBACK');
            // $this->execute('SET AUTOCOMMIT=1');
            mysqli_autocommit($this->_connectionID, true);

            return true;
        }

        /**
         * Lock a table row for a duration of a transaction.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:rowlock
         *
         * @param string $tables the table(s) to lock rows for
         * @param string $where  (Optional) The WHERE clause to use to determine which rows to lock
         * @param string $col    (Optional) The columns to select
         *
         * @return bool true if the locking SQL statement executed successfully, otherwise false
         */
        public function RowLock($tables, $where = '', $col = '1 as adodbignore')
        {
            if ($this->transCnt == 0) {
                $this->beginTrans();
            }
            if ($where) {
                $where = ' where '.$where;
            }
            $rs = $this->execute("select $col from $tables $where for update");

            return !empty($rs);
        }

        /**
         * Appropriately quotes strings with ' characters for insertion into the database.
         *
         * Relies on mysqli_real_escape_string()
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

            // mysqli_real_escape_string() throws a warning when the given
            // connection is invalid
            if ($this->_connectionID) {
                return "'".mysqli_real_escape_string($this->_connectionID, $s)."'";
            }

            if ($this->replaceQuote[0] == '\\') {
                $s = str_replace(['\\', "\0"], ['\\\\', "\\\0"], $s);
            }

            return "'".str_replace("'", $this->replaceQuote, $s)."'";
        }

        /**
         * Return the AUTO_INCREMENT id of the last row that has been inserted or updated in a table.
         *
         * {@inheritDoc}
         */
        protected function _insertID($table = '', $column = '')
        {
            // mysqli_insert_id does not return the last_insert_id if called after
            // execution of a stored procedure so we execute this instead.
            if ($this->useLastInsertStatement) {
                $result = ADOConnection::getOne('SELECT LAST_INSERT_ID()');
            } else {
                $result = @mysqli_insert_id($this->_connectionID);
            }

            if ($result == -1) {
                if ($this->debug) {
                    ADOConnection::outp('mysqli_insert_id() failed : '.$this->errorMsg());
                }
            }
            // reset prepared statement flags
            $this->usePreparedStatement = false;
            $this->useLastInsertStatement = false;

            return $result;
        }

        /**
         * Returns how many rows were effected by the most recently executed SQL statement.
         * Only works for INSERT, UPDATE and DELETE queries.
         *
         * @return int the number of rows affected
         */
        public function _affectedrows()
        {
            if ($this->isSelectStatement) {
                // Affected rows works fine against selects, returning
                // the rowcount, but ADOdb does not do that.
                return false;
            }

            $result = @mysqli_affected_rows($this->_connectionID);
            if ($result == -1) {
                if ($this->debug) {
                    ADOConnection::outp('mysqli_affected_rows() failed : '.$this->errorMsg());
                }
            }

            return $result;
        }

        // Reference on Last_Insert_ID on the recommended way to simulate sequences
        public $_genIDSQL = 'update %s set id=LAST_INSERT_ID(id+1);';
        public $_genSeqSQL = 'create table if not exists %s (id int not null)';
        public $_genSeqCountSQL = 'select count(*) from %s';
        public $_genSeq2SQL = 'insert into %s values (%s)';
        public $_dropSeqSQL = 'drop table if exists %s';

        /**
         * Creates a sequence in the database.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:createsequence
         *
         * @param string $seqname the sequence name
         * @param int    $startID the start id
         *
         * @return ADORecordSet|bool a record set if executed successfully, otherwise false
         */
        public function CreateSequence($seqname = 'adodbseq', $startID = 1)
        {
            if (empty($this->_genSeqSQL)) {
                return false;
            }

            $ok = $this->execute(sprintf($this->_genSeqSQL, $seqname));
            if (!$ok) {
                return false;
            }

            return $this->execute(sprintf($this->_genSeq2SQL, $seqname, $startID - 1));
        }

        /**
         * A portable method of creating sequence numbers.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:genid
         *
         * @param string $seqname (Optional) The name of the sequence to use
         * @param int    $startID (Optional) The point to start at in the sequence
         *
         * @return bool|int|string
         */
        public function GenID($seqname = 'adodbseq', $startID = 1)
        {
            // post-nuke sets hasGenID to false
            if (!$this->hasGenID) {
                return false;
            }

            $getnext = sprintf($this->_genIDSQL, $seqname);
            $holdtransOK = $this->_transOK; // save the current status
            $rs = $this->execute($getnext);
            if (!$rs) {
                if ($holdtransOK) {
                    $this->_transOK = true;
                } // if the status was ok before reset
                $this->execute(sprintf($this->_genSeqSQL, $seqname));
                $cnt = $this->getOne(sprintf($this->_genSeqCountSQL, $seqname));
                if (!$cnt) {
                    $this->execute(sprintf($this->_genSeq2SQL, $seqname, $startID - 1));
                }
                $rs = $this->execute($getnext);
            }

            if ($rs) {
                $this->genID = mysqli_insert_id($this->_connectionID);
                if ($this->genID == 0) {
                    $getnext = 'select LAST_INSERT_ID() from '.$seqname;
                    $rs = $this->execute($getnext);
                    $this->genID = (int) $rs->fields[0];
                }
                $rs->close();
            } else {
                $this->genID = 0;
            }

            return $this->genID;
        }

        /**
         * Return a list of all visible databases except the 'mysql' database.
         *
         * @return array|false an array of database names, or false if the query failed
         */
        public function MetaDatabases()
        {
            $query = 'SHOW DATABASES';
            $ret = $this->execute($query);
            if ($ret && is_object($ret)) {
                $arr = [];
                while (!$ret->EOF) {
                    $db = $ret->fields('Database');
                    if ($db != 'mysql') {
                        $arr[] = $db;
                    }
                    $ret->moveNext();
                }

                return $arr;
            }

            return $ret;
        }

        /**
         * Get a list of indexes on the specified table.
         *
         * @param string $table   the name of the table to get indexes for
         * @param bool   $primary (Optional) Whether or not to include the primary key
         * @param bool   $owner   (Optional) Unused
         *
         * @return array|bool an array of the indexes, or false if the query to get the indexes failed
         */
        public function MetaIndexes($table, $primary = false, $owner = false)
        {
            // save old fetch mode
            global $ADODB_FETCH_MODE;

            $false = false;
            $save = $ADODB_FETCH_MODE;
            $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
            if ($this->fetchMode !== false) {
                $savem = $this->setFetchMode(false);
            }

            // get index details
            $rs = $this->execute(sprintf('SHOW INDEXES FROM %s', $table));

            // restore fetchmode
            if (isset($savem)) {
                $this->setFetchMode($savem);
            }
            $ADODB_FETCH_MODE = $save;

            if (!is_object($rs)) {
                return $false;
            }

            $indexes = [];

            // parse index data into array
            while ($row = $rs->fetchRow()) {
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
         * Returns a portably-formatted date string from a timestamp database column.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:sqldate
         *
         * @param string      $fmt the date format to use
         * @param string|bool $col (Optional) The table column to date format, or if false, use NOW()
         *
         * @return bool|string the SQL DATE_FORMAT() string, or false if the provided date format was empty
         */
        public function SQLDate($fmt, $col = false)
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
                    case 'Y':
                    case 'y':
                        $s .= '%Y';
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

                    case 'l':
                        $s .= '%W';
                        break;

                    default:
                        if ($ch == '\\') {
                            ++$i;
                            $ch = substr($fmt, $i, 1);
                        }
                        $s .= $ch;
                        break;
                }
            }
            $s .= "')";
            if ($concat) {
                $s = "CONCAT($s)";
            }

            return $s;
        }

        /**
         * Returns a database-specific concatenation of strings.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:concat
         *
         * @return string
         */
        public function Concat()
        {
            $arr = func_get_args();

            // suggestion by andrew005@mnogo.ru
            $s = implode(',', $arr);
            if (strlen($s) > 0) {
                return "CONCAT($s)";
            } else {
                return '';
            }
        }

        /**
         * Creates a portable date offset field, for use in SQL statements.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:offsetdate
         *
         * @param float       $dayFraction A day in floating point
         * @param string|bool $date        (Optional) The date to offset. If false, uses CURDATE()
         *
         * @return string
         */
        public function OffsetDate($dayFraction, $date = false)
        {
            if (!$date) {
                $date = $this->sysDate;
            }

            $fraction = $dayFraction * 24 * 3600;

            return $date.' + INTERVAL '.$fraction.' SECOND';

            //		return "from_unixtime(unix_timestamp($date)+$fraction)";
        }

        /**
         * Returns information about stored procedures and stored functions.
         *
         * @param string|bool $NamePattern   (Optional) Only look for procedures/functions with a name matching this pattern
         * @param null        $catalog       (Optional) Unused
         * @param null        $schemaPattern (Optional) Unused
         *
         * @return array
         */
        public function MetaProcedures($NamePattern = false, $catalog = null, $schemaPattern = null)
        {
            // save old fetch mode
            global $ADODB_FETCH_MODE;

            $save = $ADODB_FETCH_MODE;
            $ADODB_FETCH_MODE = ADODB_FETCH_NUM;

            if ($this->fetchMode !== false) {
                $savem = $this->setFetchMode(false);
            }

            $procedures = [];

            // get index details

            $likepattern = '';
            if ($NamePattern) {
                $likepattern = " LIKE '".$NamePattern."'";
            }
            $rs = $this->execute('SHOW PROCEDURE STATUS'.$likepattern);
            if (is_object($rs)) {
                // parse index data into array
                while ($row = $rs->fetchRow()) {
                    $procedures[$row[1]] = [
                        'type' => 'PROCEDURE',
                        'catalog' => '',
                        'schema' => '',
                        'remarks' => $row[7],
                    ];
                }
            }

            $rs = $this->execute('SHOW FUNCTION STATUS'.$likepattern);
            if (is_object($rs)) {
                // parse index data into array
                while ($row = $rs->fetchRow()) {
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
                $this->setFetchMode($savem);
            }
            $ADODB_FETCH_MODE = $save;

            return $procedures;
        }

        /**
         * Retrieves a list of tables based on given criteria.
         *
         * @param string|bool $ttype      (Optional) Table type = 'TABLE', 'VIEW' or false=both (default)
         * @param string|bool $showSchema (Optional) schema name, false = current schema (default)
         * @param string|bool $mask       (Optional) filters the table by name
         *
         * @return array list of tables
         */
        public function MetaTables($ttype = false, $showSchema = false, $mask = false)
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
            $ret = ADOConnection::metaTables($ttype, $showSchema);

            $this->metaTablesSQL = $save;

            return $ret;
        }

        /**
         * Return information about a table's foreign keys.
         *
         * @param string      $table       the name of the table to get the foreign keys for
         * @param string|bool $owner       (Optional) The database the table belongs to, or false to assume the current db
         * @param string|bool $upper       (Optional) Force uppercase table name on returned array keys
         * @param bool        $associative (Optional) Whether to return an associate or numeric array
         *
         * @return array|bool an array of foreign keys, or false no foreign keys could be found
         */
        public function MetaForeignKeys($table, $owner = false, $upper = false, $associative = false)
        {
            global $ADODB_FETCH_MODE;

            if ($ADODB_FETCH_MODE == ADODB_FETCH_ASSOC
            || $this->fetchMode == ADODB_FETCH_ASSOC) {
                $associative = true;
            }

            $savem = $ADODB_FETCH_MODE;
            $this->setFetchMode(ADODB_FETCH_ASSOC);

            if (!empty($owner)) {
                $table = "$owner.$table";
            }

            $a_create_table = $this->getRow(sprintf('SHOW CREATE TABLE %s', $table));

            $this->setFetchMode($savem);

            $create_sql = $a_create_table['Create Table'] ?? $a_create_table['Create View'];

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

        /**
         * Return an array of information about a table's columns.
         *
         * @param string $table     the name of the table to get the column info for
         * @param bool   $normalize (Optional) Unused
         *
         * @return ADOFieldObject[]|bool an array of info for each column, or false if it could not determine the info
         */
        public function MetaColumns($table, $normalize = true)
        {
            $false = false;
            if (!$this->metaColumnsSQL) {
                return $false;
            }

            global $ADODB_FETCH_MODE;
            $save = $ADODB_FETCH_MODE;
            $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
            if ($this->fetchMode !== false) {
                $savem = $this->SetFetchMode(false);
            }
            /*
            * Return assoc array where key is column name, value is column type
            *    [1] => int unsigned
            */

            $SQL = "SELECT column_name, column_type
				  FROM information_schema.columns
				 WHERE table_schema='{$this->databaseName}'
				   AND table_name='$table'";

            $schemaArray = $this->getAssoc($SQL);
            $schemaArray = array_change_key_case($schemaArray, CASE_LOWER);

            $rs = $this->Execute(sprintf($this->metaColumnsSQL, $table));
            if (isset($savem)) {
                $this->SetFetchMode($savem);
            }
            $ADODB_FETCH_MODE = $save;
            if (!is_object($rs)) {
                return $false;
            }

            $retarr = [];
            while (!$rs->EOF) {
                $fld = new ADOFieldObject();
                $fld->name = $rs->fields[0];
                $type = $rs->fields[1];

                /*
                * Type from information_schema returns
                * the same format in V8 mysql as V5
                */
                $type = $schemaArray[strtolower($fld->name)];

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
                $fld->binary = (strpos($type, 'blob') !== false);
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
                $rs->moveNext();
            }

            $rs->close();

            return $retarr;
        }

        /**
         * Select which database to connect to.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:selectdb
         *
         * @param string $dbName the name of the database to select
         *
         * @return bool true if the database was selected successfully, otherwise false
         */
        public function SelectDB($dbName)
        {
            //		$this->_connectionID = $this->mysqli_resolve_link($this->_connectionID);
            $this->database = $dbName;
            $this->databaseName = $dbName; // obsolete, retained for compat with older adodb versions

            if ($this->_connectionID) {
                $result = @mysqli_select_db($this->_connectionID, $dbName);
                if (!$result) {
                    ADOConnection::outp('Select of database '.$dbName.' failed. '.$this->errorMsg());
                }

                return $result;
            }

            return false;
        }

        /**
         * Executes a provided SQL statement and returns a handle to the result, with the ability to supply a starting
         * offset and record count.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:selectlimit
         *
         * @param string     $sql      the SQL to execute
         * @param int        $nrows    (Optional) The limit for the number of records you want returned. By default, all results.
         * @param int        $offset   (Optional) The offset to use when selecting the results. By default, no offset.
         * @param array|bool $inputarr (Optional) Any parameter values required by the SQL statement, or false if none
         * @param int        $secs     (Optional) If greater than 0, perform a cached execute. By default, normal execution.
         *
         * @return ADORecordSet|false the query results, or false if the query failed to execute
         */
        public function SelectLimit(
            $sql,
            $nrows = -1,
            $offset = -1,
            $inputarr = false,
            $secs = 0
        ) {
            $nrows = (int) $nrows;
            $offset = (int) $offset;
            $offsetStr = ($offset >= 0) ? "$offset," : '';
            if ($nrows < 0) {
                $nrows = '18446744073709551615';
            }

            if ($secs) {
                $rs = $this->cacheExecute($secs, $sql." LIMIT $offsetStr$nrows", $inputarr);
            } else {
                $rs = $this->execute($sql." LIMIT $offsetStr$nrows", $inputarr);
            }

            return $rs;
        }

        /**
         * Prepares an SQL statement and returns a handle to use.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:prepare
         *
         * @todo update this function to handle prepared statements correctly
         *
         * @param string $sql the SQL to prepare
         *
         * @return string the original SQL that was provided
         */
        public function Prepare($sql)
        {
            /*
            * Flag the insert_id method to use the correct retrieval method
            */
            $this->usePreparedStatement = true;

            /*
            * Prepared statements are not yet handled correctly
            */
            return $sql;
            $stmt = $this->_connectionID->prepare($sql);
            if (!$stmt) {
                echo $this->errorMsg();

                return $sql;
            }

            return [$sql, $stmt];
        }

        /**
         * Return the query id.
         *
         * @param string|array $sql
         * @param array        $inputarr
         *
         * @return bool|mysqli_result
         */
        public function _query($sql, $inputarr)
        {
            global $ADODB_COUNTRECS;
            // Move to the next recordset, or return false if there is none. In a stored proc
            // call, mysqli_next_result returns true for the last "recordset", but mysqli_store_result
            // returns false. I think this is because the last "recordset" is actually just the
            // return value of the stored proc (ie the number of rows affected).
            // Commented out for reasons of performance. You should retrieve every recordset yourself.
            //	if (!mysqli_next_result($this->connection->_connectionID))	return false;

            if (is_array($sql)) {
                // Prepare() not supported because mysqli_stmt_execute does not return a recordset, but
                // returns as bound variables.

                $stmt = $sql[1];
                $a = '';
                foreach ($inputarr as $k => $v) {
                    if (is_string($v)) {
                        $a .= 's';
                    } elseif (is_integer($v)) {
                        $a .= 'i';
                    } else {
                        $a .= 'd';
                    }
                }

                /*
                 * set prepared statement flags
                 */
                if ($this->usePreparedStatement) {
                    $this->useLastInsertStatement = true;
                }

                $fnarr = array_merge([$stmt, $a], $inputarr);
                call_user_func_array('mysqli_stmt_bind_param', $fnarr);
                $ret = mysqli_stmt_execute($stmt);

                return $ret;
            } else {
                /*
                * reset prepared statement flags, in case we set them
                * previously and didn't use them
                */
                $this->usePreparedStatement = false;
                $this->useLastInsertStatement = false;
            }

            /*
            if (!$mysql_res =  mysqli_query($this->_connectionID, $sql, ($ADODB_COUNTRECS) ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT)) {
                if ($this->debug) ADOConnection::outp("Query: " . $sql . " failed. " . $this->errorMsg());
                return false;
            }

            return $mysql_res;
            */

            if ($this->multiQuery) {
                $rs = mysqli_multi_query($this->_connectionID, $sql.';');
                if ($rs) {
                    $rs = ($ADODB_COUNTRECS) ? @mysqli_store_result($this->_connectionID) : @mysqli_use_result($this->_connectionID);

                    return $rs ? $rs : true; // mysqli_more_results( $this->_connectionID )
                }
            } else {
                $rs = mysqli_query($this->_connectionID, $sql, $ADODB_COUNTRECS ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT);
                if ($rs) {
                    $this->isSelectStatement = is_object($rs);

                    return $rs;
                }
            }

            if ($this->debug) {
                ADOConnection::outp('Query: '.$sql.' failed. '.$this->errorMsg());
            }

            return false;
        }

        /**
         * Returns a database specific error message.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:errormsg
         *
         * @return string the last error message
         */
        public function ErrorMsg()
        {
            if (empty($this->_connectionID)) {
                $this->_errorMsg = @mysqli_connect_error();
            } else {
                $this->_errorMsg = @mysqli_error($this->_connectionID);
            }

            return $this->_errorMsg;
        }

        /**
         * Returns the last error number from previous database operation.
         *
         * @return int the last error number
         */
        public function ErrorNo()
        {
            if (empty($this->_connectionID)) {
                return @mysqli_connect_errno();
            } else {
                return @mysqli_errno($this->_connectionID);
            }
        }

        /**
         * Close the database connection.
         *
         * @return void
         */
        public function _close()
        {
            if ($this->_connectionID) {
                mysqli_close($this->_connectionID);
            }
            $this->_connectionID = false;
        }

        /**
         * Returns the largest length of data that can be inserted into a character field.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:charmax
         *
         * @return int
         */
        public function CharMax()
        {
            return 255;
        }

        /**
         * Returns the largest length of data that can be inserted into a text field.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:textmax
         *
         * @return int
         */
        public function TextMax()
        {
            return 4294967295;
        }

        public function getCharSet()
        {
            if (!$this->_connectionID || !method_exists($this->_connectionID, 'character_set_name')) {
                return false;
            }

            $this->charSet = $this->_connectionID->character_set_name();

            return $this->charSet ?: false;
        }

        public function setCharSet($charset)
        {
            if (!$this->_connectionID || !method_exists($this->_connectionID, 'set_charset')) {
                return false;
            }

            if ($this->charSet !== $charset) {
                if (!$this->_connectionID->set_charset($charset)) {
                    return false;
                }
                $this->getCharSet();
            }

            return true;
        }
    }

    /**
     * Class ADORecordSet_mysqli.
     */
    class ADORecordSet_mysqli extends ADORecordSet
    {
        public $databaseType = 'mysqli';
        public $canSeek = true;

        public function __construct($queryID, $mode = false)
        {
            if ($mode === false) {
                global $ADODB_FETCH_MODE;
                $mode = $ADODB_FETCH_MODE;
            }

            switch ($mode) {
                case ADODB_FETCH_NUM:
                    $this->fetchMode = MYSQLI_NUM;
                    break;
                case ADODB_FETCH_ASSOC:
                    $this->fetchMode = MYSQLI_ASSOC;
                    break;
                case ADODB_FETCH_DEFAULT:
                case ADODB_FETCH_BOTH:
                default:
                    $this->fetchMode = MYSQLI_BOTH;
                    break;
            }
            $this->adodbFetchMode = $mode;
            parent::__construct($queryID);
        }

        public function _initrs()
        {
            global $ADODB_COUNTRECS;

            $this->_numOfRows = $ADODB_COUNTRECS ? @mysqli_num_rows($this->_queryID) : -1;
            $this->_numOfFields = @mysqli_num_fields($this->_queryID);
        }

/*
1      = MYSQLI_NOT_NULL_FLAG
2      = MYSQLI_PRI_KEY_FLAG
4      = MYSQLI_UNIQUE_KEY_FLAG
8      = MYSQLI_MULTIPLE_KEY_FLAG
16     = MYSQLI_BLOB_FLAG
32     = MYSQLI_UNSIGNED_FLAG
64     = MYSQLI_ZEROFILL_FLAG
128    = MYSQLI_BINARY_FLAG
256    = MYSQLI_ENUM_FLAG
512    = MYSQLI_AUTO_INCREMENT_FLAG
1024   = MYSQLI_TIMESTAMP_FLAG
2048   = MYSQLI_SET_FLAG
32768  = MYSQLI_NUM_FLAG
16384  = MYSQLI_PART_KEY_FLAG
32768  = MYSQLI_GROUP_FLAG
65536  = MYSQLI_UNIQUE_FLAG
131072 = MYSQLI_BINCMP_FLAG
*/

        /**
         * Returns raw, database specific information about a field.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:recordset:fetchfield
         *
         * @param int $fieldOffset (Optional) The field number to get information for
         *
         * @return ADOFieldObject|bool
         */
        public function FetchField($fieldOffset = -1)
        {
            $fieldnr = $fieldOffset;
            if ($fieldOffset != -1) {
                $fieldOffset = @mysqli_field_seek($this->_queryID, $fieldnr);
            }
            $o = @mysqli_fetch_field($this->_queryID);
            if (!$o) {
                return false;
            }

            // Fix for HHVM
            if (!isset($o->flags)) {
                $o->flags = 0;
            }
            /* Properties of an ADOFieldObject as set by MetaColumns */
            $o->primary_key = $o->flags & MYSQLI_PRI_KEY_FLAG;
            $o->not_null = $o->flags & MYSQLI_NOT_NULL_FLAG;
            $o->auto_increment = $o->flags & MYSQLI_AUTO_INCREMENT_FLAG;
            $o->binary = $o->flags & MYSQLI_BINARY_FLAG;
            // $o->blob = $o->flags & MYSQLI_BLOB_FLAG; /* not returned by MetaColumns */
            $o->unsigned = $o->flags & MYSQLI_UNSIGNED_FLAG;

            /*
            * Trivial method to cast class to ADOfieldObject
            */
            $a = new ADOFieldObject();
            foreach (get_object_vars($o) as $key => $name) {
                $a->$key = $name;
            }

            return $a;
        }

        /**
         * Reads a row in associative mode if the recordset fetch mode is numeric.
         * Using this function when the fetch mode is set to ADODB_FETCH_ASSOC may produce unpredictable results.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:getrowassoc
         *
         * @param int $upper indicates whether the keys of the recordset should be upper case or lower case
         *
         * @return array|bool
         */
        public function GetRowAssoc($upper = ADODB_ASSOC_CASE)
        {
            if ($this->fetchMode == MYSQLI_ASSOC && $upper == ADODB_ASSOC_CASE_LOWER) {
                return $this->fields;
            }
            $row = ADORecordSet::getRowAssoc($upper);

            return $row;
        }

        /**
         * Returns a single field in a single row of the current recordset.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:recordset:fields
         *
         * @param string $colname the name of the field to retrieve
         *
         * @return mixed
         */
        public function Fields($colname)
        {
            if ($this->fetchMode != MYSQLI_NUM) {
                return $this->fields[$colname];
            }

            if (!$this->bind) {
                $this->bind = [];
                for ($i = 0; $i < $this->_numOfFields; ++$i) {
                    $o = $this->fetchField($i);
                    $this->bind[strtoupper($o->name)] = $i;
                }
            }

            return $this->fields[$this->bind[strtoupper($colname)]];
        }

        /**
         * Adjusts the result pointer to an arbitrary row in the result.
         *
         * @param int $row the row to seek to
         *
         * @return bool false if the recordset contains no rows, otherwise true
         */
        public function _seek($row)
        {
            if ($this->_numOfRows == 0 || $row < 0) {
                return false;
            }

            mysqli_data_seek($this->_queryID, $row);
            $this->EOF = false;

            return true;
        }

        /**
         * In databases that allow accessing of recordsets, retrieves the next set.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:recordset:nextrecordset
         *
         * @return bool
         */
        public function NextRecordSet()
        {
            global $ADODB_COUNTRECS;

            mysqli_free_result($this->_queryID);
            $this->_queryID = -1;
            // Move to the next recordset, or return false if there is none. In a stored proc
            // call, mysqli_next_result returns true for the last "recordset", but mysqli_store_result
            // returns false. I think this is because the last "recordset" is actually just the
            // return value of the stored proc (ie the number of rows affected).
            if (!mysqli_next_result($this->connection->_connectionID)) {
                return false;
            }

            // CD: There is no $this->_connectionID variable, at least in the ADO version I'm using
            $this->_queryID = ($ADODB_COUNTRECS) ? @mysqli_store_result($this->connection->_connectionID)
                : @mysqli_use_result($this->connection->_connectionID);

            if (!$this->_queryID) {
                return false;
            }

            $this->_inited = false;
            $this->bind = false;
            $this->_currentRow = -1;
            $this->init();

            return true;
        }

        /**
         * Moves the cursor to the next record of the recordset from the current position.
         *
         * @see https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:movenext
         *
         * @return bool false if there are no more records to move on to, otherwise true
         */
        public function MoveNext()
        {
            if ($this->EOF) {
                return false;
            }
            ++$this->_currentRow;
            $this->fields = @mysqli_fetch_array($this->_queryID, $this->fetchMode);

            if (is_array($this->fields)) {
                $this->_updatefields();

                return true;
            }
            $this->EOF = true;

            return false;
        }

        /**
         * Attempt to fetch a result row using the current fetch mode and return whether or not this was successful.
         *
         * @return bool true if row was fetched successfully, otherwise false
         */
        public function _fetch()
        {
            $this->fields = mysqli_fetch_array($this->_queryID, $this->fetchMode);
            $this->_updatefields();

            return is_array($this->fields);
        }

        /**
         * Frees the memory associated with a result.
         *
         * @return void
         */
        public function _close()
        {
            // if results are attached to this pointer from Stored Procedure calls, the next standard query will die 2014
            // only a problem with persistent connections

            if (isset($this->connection->_connectionID) && $this->connection->_connectionID) {
                while (mysqli_more_results($this->connection->_connectionID)) {
                    mysqli_next_result($this->connection->_connectionID);
                }
            }

            if ($this->_queryID instanceof mysqli_result) {
                mysqli_free_result($this->_queryID);
            }
            $this->_queryID = false;
        }

/*

0 = MYSQLI_TYPE_DECIMAL
1 = MYSQLI_TYPE_CHAR
1 = MYSQLI_TYPE_TINY
2 = MYSQLI_TYPE_SHORT
3 = MYSQLI_TYPE_LONG
4 = MYSQLI_TYPE_FLOAT
5 = MYSQLI_TYPE_DOUBLE
6 = MYSQLI_TYPE_NULL
7 = MYSQLI_TYPE_TIMESTAMP
8 = MYSQLI_TYPE_LONGLONG
9 = MYSQLI_TYPE_INT24
10 = MYSQLI_TYPE_DATE
11 = MYSQLI_TYPE_TIME
12 = MYSQLI_TYPE_DATETIME
13 = MYSQLI_TYPE_YEAR
14 = MYSQLI_TYPE_NEWDATE
247 = MYSQLI_TYPE_ENUM
248 = MYSQLI_TYPE_SET
249 = MYSQLI_TYPE_TINY_BLOB
250 = MYSQLI_TYPE_MEDIUM_BLOB
251 = MYSQLI_TYPE_LONG_BLOB
252 = MYSQLI_TYPE_BLOB
253 = MYSQLI_TYPE_VAR_STRING
254 = MYSQLI_TYPE_STRING
255 = MYSQLI_TYPE_GEOMETRY
*/

        /**
         * Get the MetaType character for a given field type.
         *
         * @param string|object $t        the type to get the MetaType character for
         * @param int           $len      (Optional) Redundant. Will always be set to -1.
         * @param bool|object   $fieldobj (Optional)
         *
         * @return string The MetaType
         */
        public function MetaType($t, $len = -1, $fieldobj = false)
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
                case MYSQLI_TYPE_TINY_BLOB :
                    //			case MYSQLI_TYPE_CHAR :
                case MYSQLI_TYPE_STRING :
                case MYSQLI_TYPE_ENUM :
                case MYSQLI_TYPE_SET :
                case 253 :
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
                case MYSQLI_TYPE_BLOB :
                case MYSQLI_TYPE_LONG_BLOB :
                case MYSQLI_TYPE_MEDIUM_BLOB :
                    return !empty($fieldobj->binary) ? 'B' : 'X';

                case 'YEAR':
                case 'DATE':
                case MYSQLI_TYPE_DATE :
                case MYSQLI_TYPE_YEAR :
                    return 'D';

                case 'TIME':
                case 'DATETIME':
                case 'TIMESTAMP':
                case MYSQLI_TYPE_DATETIME :
                case MYSQLI_TYPE_NEWDATE :
                case MYSQLI_TYPE_TIME :
                case MYSQLI_TYPE_TIMESTAMP :
                    return 'T';

                case 'INT':
                case 'INTEGER':
                case 'BIGINT':
                case 'TINYINT':
                case 'MEDIUMINT':
                case 'SMALLINT':
                case MYSQLI_TYPE_INT24 :
                case MYSQLI_TYPE_LONG :
                case MYSQLI_TYPE_LONGLONG :
                case MYSQLI_TYPE_SHORT :
                case MYSQLI_TYPE_TINY :
                    if (!empty($fieldobj->primary_key)) {
                        return 'R';
                    }

                    return 'I';

                    // Added floating-point types
                    // Maybe not necessary.
                case 'FLOAT':
                case 'DOUBLE':
                    //			case 'DOUBLE PRECISION':
                case 'DECIMAL':
                case 'DEC':
                case 'FIXED':
                default:
                    // if (!is_numeric($t)) echo "<p>--- Error in type matching $t -----</p>";
                    return 'N';
            }
        }
    } // rs class

    /**
     * Class ADORecordSet_array_mysqli.
     */
    class ADORecordSet_array_mysqli extends ADORecordSet_array
    {
        /**
         * Get the MetaType character for a given field type.
         *
         * @param string|object $t        the type to get the MetaType character for
         * @param int           $len      (Optional) Redundant. Will always be set to -1.
         * @param bool|object   $fieldobj (Optional)
         *
         * @return string The MetaType
         */
        public function MetaType($t, $len = -1, $fieldobj = false)
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
                case MYSQLI_TYPE_TINY_BLOB :
                    //			case MYSQLI_TYPE_CHAR :
                case MYSQLI_TYPE_STRING :
                case MYSQLI_TYPE_ENUM :
                case MYSQLI_TYPE_SET :
                case 253 :
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
                case MYSQLI_TYPE_BLOB :
                case MYSQLI_TYPE_LONG_BLOB :
                case MYSQLI_TYPE_MEDIUM_BLOB :
                    return !empty($fieldobj->binary) ? 'B' : 'X';

                case 'YEAR':
                case 'DATE':
                case MYSQLI_TYPE_DATE :
                case MYSQLI_TYPE_YEAR :
                    return 'D';

                case 'TIME':
                case 'DATETIME':
                case 'TIMESTAMP':
                case MYSQLI_TYPE_DATETIME :
                case MYSQLI_TYPE_NEWDATE :
                case MYSQLI_TYPE_TIME :
                case MYSQLI_TYPE_TIMESTAMP :
                    return 'T';

                case 'INT':
                case 'INTEGER':
                case 'BIGINT':
                case 'TINYINT':
                case 'MEDIUMINT':
                case 'SMALLINT':
                case MYSQLI_TYPE_INT24 :
                case MYSQLI_TYPE_LONG :
                case MYSQLI_TYPE_LONGLONG :
                case MYSQLI_TYPE_SHORT :
                case MYSQLI_TYPE_TINY :
                    if (!empty($fieldobj->primary_key)) {
                        return 'R';
                    }

                    return 'I';

                    // Added floating-point types
                    // Maybe not necessary.
                case 'FLOAT':
                case 'DOUBLE':
                    //			case 'DOUBLE PRECISION':
                case 'DECIMAL':
                case 'DEC':
                case 'FIXED':
                default:
                    // if (!is_numeric($t)) echo "<p>--- Error in type matching $t -----</p>";
                    return 'N';
            }
        }
    }
} // if defined _ADODB_MYSQLI_LAYER