<?php
/**
 * SQLite3 driver.
 *
 * @see https://www.sqlite.org/
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

class ADODB_sqlite3 extends ADOConnection
{
    public $databaseType = 'sqlite3';
    public $dataProvider = 'sqlite';
    public $replaceQuote = "''"; // string to use to replace quotes
    public $concat_operator = '||';
    public $_errorNo = 0;
    public $hasLimit = true;
    public $hasInsertID = true; 		// / supports autoincrement ID?
    public $hasAffectedRows = true; 	// / supports affected rows for update/delete?
    public $metaTablesSQL = "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name";
    public $sysDate = "adodb_date('Y-m-d')";
    public $sysTimeStamp = "adodb_date('Y-m-d H:i:s')";
    public $fmtTimeStamp = "'Y-m-d H:i:s'";

    public function ServerInfo()
    {
        $version = SQLite3::version();
        $arr['version'] = $version['versionString'];
        $arr['description'] = 'SQLite 3';

        return $arr;
    }

    public function BeginTrans()
    {
        if ($this->transOff) {
            return true;
        }
        $ret = $this->Execute('BEGIN TRANSACTION');
        ++$this->transCnt;

        return true;
    }

    public function CommitTrans($ok = true)
    {
        if ($this->transOff) {
            return true;
        }
        if (!$ok) {
            return $this->RollbackTrans();
        }
        $ret = $this->Execute('COMMIT');
        if ($this->transCnt > 0) {
            --$this->transCnt;
        }

        return !empty($ret);
    }

    public function RollbackTrans()
    {
        if ($this->transOff) {
            return true;
        }
        $ret = $this->Execute('ROLLBACK');
        if ($this->transCnt > 0) {
            --$this->transCnt;
        }

        return !empty($ret);
    }

    public function metaType($t, $len = -1, $fieldobj = false)
    {
        if (is_object($t)) {
            $fieldobj = $t;
            $t = $fieldobj->type;
            $len = $fieldobj->max_length;
        }

        $t = strtoupper($t);

        /*
        * We are using the Sqlite affinity method here
        * @link https://www.sqlite.org/datatype3.html
        */
        $affinity = [
        'INT' => 'INTEGER',
        'INTEGER' => 'INTEGER',
        'TINYINT' => 'INTEGER',
        'SMALLINT' => 'INTEGER',
        'MEDIUMINT' => 'INTEGER',
        'BIGINT' => 'INTEGER',
        'UNSIGNED BIG INT' => 'INTEGER',
        'INT2' => 'INTEGER',
        'INT8' => 'INTEGER',

        'CHARACTER' => 'TEXT',
        'VARCHAR' => 'TEXT',
        'VARYING CHARACTER' => 'TEXT',
        'NCHAR' => 'TEXT',
        'NATIVE CHARACTER' => 'TEXT',
        'NVARCHAR' => 'TEXT',
        'TEXT' => 'TEXT',
        'CLOB' => 'TEXT',

        'BLOB' => 'BLOB',

        'REAL' => 'REAL',
        'DOUBLE' => 'REAL',
        'DOUBLE PRECISION' => 'REAL',
        'FLOAT' => 'REAL',

        'NUMERIC' => 'NUMERIC',
        'DECIMAL' => 'NUMERIC',
        'BOOLEAN' => 'NUMERIC',
        'DATE' => 'NUMERIC',
        'DATETIME' => 'NUMERIC',
        ];

        if (!isset($affinity[$t])) {
            return ADODB_DEFAULT_METATYPE;
        }

        $subt = $affinity[$t];
        /*
        * Now that we have subclassed the provided data down
        * the sqlite 'affinity', we convert to ADOdb metatype
        */

        $subclass = ['INTEGER' => 'I',
                          'TEXT' => 'X',
                          'BLOB' => 'B',
                          'REAL' => 'N',
                          'NUMERIC' => 'N', ];

        return $subclass[$subt];
    }

    // mark newnham
    public function MetaColumns($table, $normalize = true)
    {
        global $ADODB_FETCH_MODE;
        $false = false;
        $save = $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        if ($this->fetchMode !== false) {
            $savem = $this->SetFetchMode(false);
        }
        $rs = $this->Execute("PRAGMA table_info('$table')");
        if (isset($savem)) {
            $this->SetFetchMode($savem);
        }
        if (!$rs) {
            $ADODB_FETCH_MODE = $save;

            return $false;
        }
        $arr = [];
        while ($r = $rs->FetchRow()) {
            $type = explode('(', $r['type']);
            $size = '';
            if (sizeof($type) == 2) {
                $size = trim($type[1], ')');
            }
            $fn = strtoupper($r['name']);
            $fld = new ADOFieldObject();
            $fld->name = $r['name'];
            $fld->type = $type[0];
            $fld->max_length = $size;
            $fld->not_null = $r['notnull'];
            $fld->default_value = $r['dflt_value'];
            $fld->scale = 0;
            if (isset($r['pk']) && $r['pk']) {
                $fld->primary_key = 1;
            }
            if ($save == ADODB_FETCH_NUM) {
                $arr[] = $fld;
            } else {
                $arr[strtoupper($fld->name)] = $fld;
            }
        }
        $rs->Close();
        $ADODB_FETCH_MODE = $save;

        return $arr;
    }

    public function metaForeignKeys($table, $owner = false, $upper = false, $associative = false)
    {
        global $ADODB_FETCH_MODE;
        if ($ADODB_FETCH_MODE == ADODB_FETCH_ASSOC
        || $this->fetchMode == ADODB_FETCH_ASSOC) {
            $associative = true;
        }

        /*
        * Read sqlite master to find foreign keys
        */
        $sql = "SELECT sql
				 FROM (
				SELECT sql sql, type type, tbl_name tbl_name, name name
				  FROM sqlite_master
			          )
				WHERE type != 'meta'
				  AND sql NOTNULL
		          AND LOWER(name) ='".strtolower($table)."'";

        $tableSql = $this->getOne($sql);

        $fkeyList = [];
        $ylist = preg_split('/,+/', $tableSql);
        foreach ($ylist as $y) {
            if (!preg_match('/FOREIGN/', $y)) {
                continue;
            }

            $matches = false;
            preg_match_all('/\((.+?)\)/i', $y, $matches);
            $tmatches = false;
            preg_match_all('/REFERENCES (.+?)\(/i', $y, $tmatches);

            if ($associative) {
                if (!isset($fkeyList[$tmatches[1][0]])) {
                    $fkeyList[$tmatches[1][0]] = [];
                }
                $fkeyList[$tmatches[1][0]][$matches[1][0]] = $matches[1][1];
            } else {
                $fkeyList[$tmatches[1][0]][] = $matches[1][0].'='.$matches[1][1];
            }
        }

        if ($associative) {
            if ($upper) {
                $fkeyList = array_change_key_case($fkeyList, CASE_UPPER);
            } else {
                $fkeyList = array_change_key_case($fkeyList, CASE_LOWER);
            }
        }

        return $fkeyList;
    }

    public function _init($parentDriver)
    {
        $parentDriver->hasTransactions = false;
        $parentDriver->hasInsertID = true;
    }

    protected function _insertID($table = '', $column = '')
    {
        return $this->_connectionID->lastInsertRowID();
    }

    public function _affectedrows()
    {
        return $this->_connectionID->changes();
    }

    public function ErrorMsg()
    {
        if ($this->_logsql) {
            return $this->_errorMsg;
        }

        return ($this->_errorNo) ? $this->ErrorNo() : ''; // **tochange?
    }

    public function ErrorNo()
    {
        return $this->_connectionID->lastErrorCode(); // **tochange??
    }

    public function SQLDate($fmt, $col = false)
    {
        /*
        * In order to map the values correctly, we must ensure the proper
        * casing for certain fields
        * Y must be UC, because y is a 2 digit year
        * d must be LC, because D is 3 char day
        * A must be UC  because a is non-portable am
        * Q must be UC  because q means nothing
        */
        $fromChars = ['y', 'D', 'a', 'q'];
        $toChars = ['Y', 'd', 'A', 'Q'];
        $fmt = str_replace($fromChars, $toChars, $fmt);

        $fmt = $this->qstr($fmt);

        return ($col) ? "adodb_date2($fmt,$col)" : "adodb_date($fmt)";
    }

    public function _createFunctions()
    {
        $this->_connectionID->createFunction('adodb_date', 'adodb_date', 1);
        $this->_connectionID->createFunction('adodb_date2', 'adodb_date2', 2);
    }

    // returns true or false
    public function _connect($argHostname, $argUsername, $argPassword, $argDatabasename)
    {
        if (empty($argHostname) && $argDatabasename) {
            $argHostname = $argDatabasename;
        }
        $this->_connectionID = new SQLite3($argHostname);
        $this->_createFunctions();

        return true;
    }

    // returns true or false
    public function _pconnect($argHostname, $argUsername, $argPassword, $argDatabasename)
    {
        // There's no permanent connect in SQLite3
        return $this->_connect($argHostname, $argUsername, $argPassword, $argDatabasename);
    }

    // returns query ID if successful, otherwise false
    public function _query($sql, $inputarr = false)
    {
        $rez = $this->_connectionID->query($sql);
        if ($rez === false) {
            $this->_errorNo = $this->_connectionID->lastErrorCode();
        }
        // If no data was returned, we don't need to create a real recordset
        elseif ($rez->numColumns() == 0) {
            $rez->finalize();
            $rez = true;
        }

        return $rez;
    }

    public function SelectLimit($sql, $nrows = -1, $offset = -1, $inputarr = false, $secs2cache = 0)
    {
        $nrows = (int) $nrows;
        $offset = (int) $offset;
        $offsetStr = ($offset >= 0) ? " OFFSET $offset" : '';
        $limitStr = ($nrows >= 0) ? " LIMIT $nrows" : ($offset >= 0 ? ' LIMIT 999999999' : '');
        if ($secs2cache) {
            $rs = $this->CacheExecute($secs2cache, $sql."$limitStr$offsetStr", $inputarr);
        } else {
            $rs = $this->Execute($sql."$limitStr$offsetStr", $inputarr);
        }

        return $rs;
    }

    /*
        This algorithm is not very efficient, but works even if table locking
        is not available.

        Will return false if unable to generate an ID after $MAXLOOPS attempts.
    */
    public $_genSeqSQL = 'create table %s (id integer)';

    public function GenID($seq = 'adodbseq', $start = 1)
    {
        // if you have to modify the parameter below, your database is overloaded,
        // or you need to implement generation of id's yourself!
        $MAXLOOPS = 100;
        // $this->debug=1;
        while (--$MAXLOOPS >= 0) {
            @($num = $this->GetOne("select id from $seq"));
            if ($num === false) {
                $this->Execute(sprintf($this->_genSeqSQL, $seq));
                --$start;
                $num = '0';
                $ok = $this->Execute("insert into $seq values($start)");
                if (!$ok) {
                    return false;
                }
            }
            $this->Execute("update $seq set id=id+1 where id=$num");

            if ($this->affected_rows() > 0) {
                ++$num;
                $this->genID = $num;

                return $num;
            }
        }
        if ($fn = $this->raiseErrorFn) {
            $fn($this->databaseType, 'GENID', -32000, "Unable to generate unique id after $MAXLOOPS attempts", $seq, $num);
        }

        return false;
    }

    public function CreateSequence($seqname = 'adodbseq', $start = 1)
    {
        if (empty($this->_genSeqSQL)) {
            return false;
        }
        $ok = $this->Execute(sprintf($this->_genSeqSQL, $seqname));
        if (!$ok) {
            return false;
        }
        --$start;

        return $this->Execute("insert into $seqname values($start)");
    }

    public $_dropSeqSQL = 'drop table %s';

    public function DropSequence($seqname = 'adodbseq')
    {
        if (empty($this->_dropSeqSQL)) {
            return false;
        }

        return $this->Execute(sprintf($this->_dropSeqSQL, $seqname));
    }

    // returns true or false
    public function _close()
    {
        return $this->_connectionID->close();
    }

    public function metaIndexes($table, $primary = false, $owner = false)
    {
        $false = false;
        // save old fetch mode
        global $ADODB_FETCH_MODE;
        $save = $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
        if ($this->fetchMode !== false) {
            $savem = $this->SetFetchMode(false);
        }

        $pragmaData = [];

        /*
        * If we want the primary key, we must extract
        * it from the table statement, and the pragma
        */
        if ($primary) {
            $sql = sprintf(
                'PRAGMA table_info([%s]);',
                strtolower($table)
            );
            $pragmaData = $this->getAll($sql);
        }

        /*
        * Exclude the empty entry for the primary index
        */
        $sqlite = "SELECT name,sql
					 FROM sqlite_master
					WHERE type='index'
					  AND sql IS NOT NULL
					  AND LOWER(tbl_name)='%s'";

        $SQL = sprintf(
            $sqlite,
            strtolower($table)
        );

        $rs = $this->execute($SQL);

        if (!is_object($rs)) {
            if (isset($savem)) {
                $this->SetFetchMode($savem);
            }
            $ADODB_FETCH_MODE = $save;

            return $false;
        }

        $indexes = [];

        while ($row = $rs->FetchRow()) {
            if (!isset($indexes[$row[0]])) {
                $indexes[$row[0]] = [
                    'unique' => preg_match('/unique/i', $row[1]),
                    'columns' => [],
                ];
            }
            /*
             * The index elements appear in the SQL statement
             * in cols[1] between parentheses
             * e.g CREATE UNIQUE INDEX ware_0 ON warehouse (org,warehouse)
             */
            preg_match_all('/\((.*)\)/', $row[1], $indexExpression);
            $indexes[$row[0]]['columns'] = array_map('trim', explode(',', $indexExpression[1][0]));
        }

        if (isset($savem)) {
            $this->SetFetchMode($savem);
            $ADODB_FETCH_MODE = $save;
        }

        /*
        * If we want primary, add it here
        */
        if ($primary) {
            /*
            * Check the previously retrieved pragma to search
            * with a closure
            */

            $pkIndexData = ['unique' => 1, 'columns' => []];

            $pkCallBack = function ($value, $key) use (&$pkIndexData) {
                /*
                * As we iterate the elements check for pk index and sort
                */
                if ($value[5] > 0) {
                    $pkIndexData['columns'][$value[5]] = strtolower($value[1]);
                    ksort($pkIndexData['columns']);
                }
            };

            array_walk($pragmaData, $pkCallBack);

            /*
            * If we found no columns, there is no
            * primary index
            */
            if (count($pkIndexData['columns']) > 0) {
                $indexes['PRIMARY'] = $pkIndexData;
            }
        }

        return $indexes;
    }

    /**
     * Returns the maximum size of a MetaType C field. Because of the
     * database design, sqlite places no limits on the size of data inserted.
     *
     * @return int
     */
    public function charMax()
    {
        return ADODB_STRINGMAX_NOLIMIT;
    }

    /**
     * Returns the maximum size of a MetaType X field. Because of the
     * database design, sqlite places no limits on the size of data inserted.
     *
     * @return int
     */
    public function textMax()
    {
        return ADODB_STRINGMAX_NOLIMIT;
    }

    /**
     * Converts a date to a month only field and pads it to 2 characters.
     *
     * This uses the more efficient strftime native function to process
     *
     * @param string $fld The name of the field to process
     *
     * @return string The SQL Statement
     */
    public function month($fld)
    {
        $x = "strftime('%m',$fld)";

        return $x;
    }

    /**
     * Converts a date to a day only field and pads it to 2 characters.
     *
     * This uses the more efficient strftime native function to process
     *
     * @param string $fld The name of the field to process
     *
     * @return string The SQL Statement
     */
    public function day($fld)
    {
        $x = "strftime('%d',$fld)";

        return $x;
    }

    /**
     * Converts a date to a year only field.
     *
     * This uses the more efficient strftime native function to process
     *
     * @param string $fld The name of the field to process
     *
     * @return string The SQL Statement
     */
    public function year($fld)
    {
        $x = "strftime('%Y',$fld)";

        return $x;
    }
}

/*--------------------------------------------------------------------------------------
        Class Name: Recordset
--------------------------------------------------------------------------------------*/

class ADORecordset_sqlite3 extends ADORecordSet
{
    public $databaseType = 'sqlite3';
    public $bind = false;

    public function __construct($queryID, $mode = false)
    {
        if ($mode === false) {
            global $ADODB_FETCH_MODE;
            $mode = $ADODB_FETCH_MODE;
        }
        switch ($mode) {
            case ADODB_FETCH_NUM:
                $this->fetchMode = SQLITE3_NUM;
                break;
            case ADODB_FETCH_ASSOC:
                $this->fetchMode = SQLITE3_ASSOC;
                break;
            default:
                $this->fetchMode = SQLITE3_BOTH;
                break;
        }
        $this->adodbFetchMode = $mode;

        $this->_queryID = $queryID;

        $this->_inited = true;
        $this->fields = [];
        if ($queryID) {
            $this->_currentRow = 0;
            $this->EOF = !$this->_fetch();
            $this->_initrs();
        } else {
            $this->_numOfRows = 0;
            $this->_numOfFields = 0;
            $this->EOF = true;
        }

        return $this->_queryID;
    }

    public function FetchField($fieldOffset = -1)
    {
        $fld = new ADOFieldObject();
        $fld->name = $this->_queryID->columnName($fieldOffset);
        $fld->type = 'VARCHAR';
        $fld->max_length = -1;

        return $fld;
    }

    public function _initrs()
    {
        $this->_numOfFields = $this->_queryID->numColumns();
    }

    public function Fields($colname)
    {
        if ($this->fetchMode != SQLITE3_NUM) {
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
        // sqlite3 does not implement seek
        if ($this->debug) {
            ADOConnection::outp('SQLite3 does not implement seek');
        }

        return false;
    }

    public function _fetch($ignore_fields = false)
    {
        $this->fields = $this->_queryID->fetchArray($this->fetchMode);

        return !empty($this->fields);
    }

    public function _close()
    {
    }
}
