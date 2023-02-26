<?php
/**
 * ADOdb Data Dictionary base class.
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

/**
 * Test script for parser.
 */
function lens_ParseTest()
{
    $str = "`zcol ACOL` NUMBER(32,2) DEFAULT 'The \"cow\" (and Jim''s dog) jumps over the moon' PRIMARY, INTI INT AUTO DEFAULT 0, zcol2\"afs ds";
    echo "<p>$str</p>";
    $a = lens_ParseArgs($str);
    echo '<pre>';
    print_r($a);
    echo '</pre>';
}

if (!function_exists('ctype_alnum')) {
    function ctype_alnum($text)
    {
        return preg_match('/^[a-z0-9]*$/i', $text);
    }
}

// Lens_ParseTest();

/**
    Parse arguments, treat "text" (text) and 'text' as quotation marks.
    To escape, use "" or '' or ))

    Will read in "abc def" sans quotes, as: abc def
    Same with 'abc def'.
    However if `abc def`, then will read in as `abc def`

    @param endstmtchar    Character that indicates end of statement
    @param tokenchars     Include the following characters in tokens apart from A-Z and 0-9
    @returns 2 dimensional array containing parsed tokens.
 */
function lens_ParseArgs($args, $endstmtchar = ',', $tokenchars = '_.-')
{
    $pos = 0;
    $intoken = false;
    $stmtno = 0;
    $endquote = false;
    $tokens = [];
    $tokens[$stmtno] = [];
    $max = strlen($args);
    $quoted = false;
    $tokarr = [];

    while ($pos < $max) {
        $ch = substr($args, $pos, 1);
        switch ($ch) {
            case ' ':
            case "\t":
            case "\n":
            case "\r":
                if (!$quoted) {
                    if ($intoken) {
                        $intoken = false;
                        $tokens[$stmtno][] = implode('', $tokarr);
                    }
                    break;
                }

                $tokarr[] = $ch;
                break;

            case '`':
                if ($intoken) {
                    $tokarr[] = $ch;
                }
                // no break
            case '(':
            case ')':
            case '"':
            case "'":
                if ($intoken) {
                    if (empty($endquote)) {
                        $tokens[$stmtno][] = implode('', $tokarr);
                        if ($ch == '(') {
                            $endquote = ')';
                        } else {
                            $endquote = $ch;
                        }
                        $quoted = true;
                        $intoken = true;
                        $tokarr = [];
                    } elseif ($endquote == $ch) {
                        $ch2 = substr($args, $pos + 1, 1);
                        if ($ch2 == $endquote) {
                            ++$pos;
                            $tokarr[] = $ch2;
                        } else {
                            $quoted = false;
                            $intoken = false;
                            $tokens[$stmtno][] = implode('', $tokarr);
                            $endquote = '';
                        }
                    } else {
                        $tokarr[] = $ch;
                    }
                } else {
                    if ($ch == '(') {
                        $endquote = ')';
                    } else {
                        $endquote = $ch;
                    }
                    $quoted = true;
                    $intoken = true;
                    $tokarr = [];
                    if ($ch == '`') {
                        $tokarr[] = '`';
                    }
                }
                break;

            default:
                if (!$intoken) {
                    if ($ch == $endstmtchar) {
                        ++$stmtno;
                        $tokens[$stmtno] = [];
                        break;
                    }

                    $intoken = true;
                    $quoted = false;
                    $endquote = false;
                    $tokarr = [];
                }

                if ($quoted) {
                    $tokarr[] = $ch;
                } elseif (ctype_alnum($ch) || strpos($tokenchars, $ch) !== false) {
                    $tokarr[] = $ch;
                } else {
                    if ($ch == $endstmtchar) {
                        $tokens[$stmtno][] = implode('', $tokarr);
                        ++$stmtno;
                        $tokens[$stmtno] = [];
                        $intoken = false;
                        $tokarr = [];
                        break;
                    }
                    $tokens[$stmtno][] = implode('', $tokarr);
                    $tokens[$stmtno][] = $ch;
                    $intoken = false;
                }
        }
        ++$pos;
    }
    if ($intoken) {
        $tokens[$stmtno][] = implode('', $tokarr);
    }

    return $tokens;
}

class ADODB_DataDict
{
    /** @var ADOConnection */
    public $connection;
    public $debug = false;
    public $dropTable = 'DROP TABLE %s';
    public $renameTable = 'RENAME TABLE %s TO %s';
    public $dropIndex = 'DROP INDEX %s';
    public $addCol = ' ADD';
    public $alterCol = ' ALTER COLUMN';
    public $dropCol = ' DROP COLUMN';
    public $renameColumn = 'ALTER TABLE %s RENAME COLUMN %s TO %s';	// table, old-column, new-column, column-definitions (not used by default)
    public $nameRegex = '\w';
    public $nameRegexBrackets = 'a-zA-Z0-9_\(\)';
    public $schema = false;
    public $serverInfo = [];
    public $autoIncrement = false;
    public $dataProvider;
    public $invalidResizeTypes4 = ['CLOB', 'BLOB', 'TEXT', 'DATE', 'TIME']; // for changeTableSQL
    public $blobSize = 100; 	// / any varchar/char field this size or greater is treated as a blob
                            // / in other words, we use a text area for editing.
    /** @var string Uppercase driver name */
    public $upperName;

    /*
    * Indicates whether a BLOB/CLOB field will allow a NOT NULL setting
    * The type is whatever is matched to an X or X2 or B type. We must
    * explicitly set the value in the driver to switch the behaviour on
    */
    public $blobAllowsNotNull;
    /*
    * Indicates whether a BLOB/CLOB field will allow a DEFAULT set
    * The type is whatever is matched to an X or X2 or B type. We must
    * explicitly set the value in the driver to switch the behaviour on
    */
    public $blobAllowsDefaultValue;

    public function getCommentSQL($table, $col)
    {
        return false;
    }

    public function setCommentSQL($table, $col, $cmt)
    {
        return false;
    }

    public function metaTables()
    {
        if (!$this->connection->isConnected()) {
            return [];
        }

        return $this->connection->metaTables();
    }

    public function metaColumns($tab, $upper = true, $schema = false)
    {
        if (!$this->connection->isConnected()) {
            return [];
        }

        return $this->connection->metaColumns($this->tableName($tab), $upper, $schema);
    }

    public function metaPrimaryKeys($tab, $owner = false, $intkey = false)
    {
        if (!$this->connection->isConnected()) {
            return [];
        }

        return $this->connection->metaPrimaryKeys($this->tableName($tab), $owner, $intkey);
    }

    public function metaIndexes($table, $primary = false, $owner = false)
    {
        if (!$this->connection->isConnected()) {
            return [];
        }

        return $this->connection->metaIndexes($this->tableName($table), $primary, $owner);
    }

    public function metaType($t, $len = -1, $fieldobj = false)
    {
        static $typeMap = [
        'VARCHAR' => 'C',
        'VARCHAR2' => 'C',
        'CHAR' => 'C',
        'C' => 'C',
        'STRING' => 'C',
        'NCHAR' => 'C',
        'NVARCHAR' => 'C',
        'VARYING' => 'C',
        'BPCHAR' => 'C',
        'CHARACTER' => 'C',
        'INTERVAL' => 'C',  // Postgres
        'MACADDR' => 'C', // postgres
        'VAR_STRING' => 'C', // mysql
        // #
        'LONGCHAR' => 'X',
        'TEXT' => 'X',
        'NTEXT' => 'X',
        'M' => 'X',
        'X' => 'X',
        'CLOB' => 'X',
        'NCLOB' => 'X',
        'LVARCHAR' => 'X',
        // #
        'BLOB' => 'B',
        'IMAGE' => 'B',
        'BINARY' => 'B',
        'VARBINARY' => 'B',
        'LONGBINARY' => 'B',
        'B' => 'B',
        // #
        'YEAR' => 'D', // mysql
        'DATE' => 'D',
        'D' => 'D',
        // #
        'UNIQUEIDENTIFIER' => 'C', // MS SQL Server
        // #
        'TIME' => 'T',
        'TIMESTAMP' => 'T',
        'DATETIME' => 'T',
        'TIMESTAMPTZ' => 'T',
        'SMALLDATETIME' => 'T',
        'T' => 'T',
        'TIMESTAMP WITHOUT TIME ZONE' => 'T', // postgresql
        // #
        'BOOL' => 'L',
        'BOOLEAN' => 'L',
        'BIT' => 'L',
        'L' => 'L',
        // #
        'COUNTER' => 'R',
        'R' => 'R',
        'SERIAL' => 'R', // ifx
        'INT IDENTITY' => 'R',
        // #
        'INT' => 'I',
        'INT2' => 'I',
        'INT4' => 'I',
        'INT8' => 'I',
        'INTEGER' => 'I',
        'INTEGER UNSIGNED' => 'I',
        'SHORT' => 'I',
        'TINYINT' => 'I',
        'SMALLINT' => 'I',
        'I' => 'I',
        // #
        'LONG' => 'N', // interbase is numeric, oci8 is blob
        'BIGINT' => 'N', // this is bigger than PHP 32-bit integers
        'DECIMAL' => 'N',
        'DEC' => 'N',
        'REAL' => 'N',
        'DOUBLE' => 'N',
        'DOUBLE PRECISION' => 'N',
        'SMALLFLOAT' => 'N',
        'FLOAT' => 'N',
        'NUMBER' => 'N',
        'NUM' => 'N',
        'NUMERIC' => 'N',
        'MONEY' => 'N',

        // # informix 9.2
        'SQLINT' => 'I',
        'SQLSERIAL' => 'I',
        'SQLSMINT' => 'I',
        'SQLSMFLOAT' => 'N',
        'SQLFLOAT' => 'N',
        'SQLMONEY' => 'N',
        'SQLDECIMAL' => 'N',
        'SQLDATE' => 'D',
        'SQLVCHAR' => 'C',
        'SQLCHAR' => 'C',
        'SQLDTIME' => 'T',
        'SQLINTERVAL' => 'N',
        'SQLBYTES' => 'B',
        'SQLTEXT' => 'X',
         // # informix 10
        'SQLINT8' => 'I8',
        'SQLSERIAL8' => 'I8',
        'SQLNCHAR' => 'C',
        'SQLNVCHAR' => 'C',
        'SQLLVARCHAR' => 'X',
        'SQLBOOL' => 'L',
        ];

        if (!$this->connection->isConnected()) {
            $t = strtoupper($t);
            if (isset($typeMap[$t])) {
                return $typeMap[$t];
            }

            return ADODB_DEFAULT_METATYPE;
        }

        return $this->connection->metaType($t, $len, $fieldobj);
    }

    public function nameQuote($name = null, $allowBrackets = false)
    {
        if (!is_string($name)) {
            return false;
        }

        $name = trim($name);

        if (!is_object($this->connection)) {
            return $name;
        }

        $quote = $this->connection->nameQuote;

        // if name is of the form `name`, quote it
        if (preg_match('/^`(.+)`$/', $name, $matches)) {
            return $quote.$matches[1].$quote;
        }

        // if name contains special characters, quote it
        $regex = ($allowBrackets) ? $this->nameRegexBrackets : $this->nameRegex;

        if (!preg_match('/^['.$regex.']+$/', $name)) {
            return $quote.$name.$quote;
        }

        return $name;
    }

    public function tableName($name)
    {
        if ($this->schema) {
            return $this->nameQuote($this->schema).'.'.$this->nameQuote($name);
        }

        return $this->nameQuote($name);
    }

    // Executes the sql array returned by getTableSQL and getIndexSQL
    public function executeSQLArray($sql, $continueOnError = true)
    {
        $rez = 2;
        $conn = $this->connection;
        $saved = $conn->debug;
        foreach ($sql as $line) {
            if ($this->debug) {
                $conn->debug = true;
            }
            $ok = $conn->execute($line);
            $conn->debug = $saved;
            if (!$ok) {
                if ($this->debug) {
                    ADOConnection::outp($conn->errorMsg());
                }
                if (!$continueOnError) {
                    return 0;
                }
                $rez = 1;
            }
        }

        return $rez;
    }

    /**
         Returns the actual type given a character code.

        C:  varchar
        X:  CLOB (character large object) or largest varchar size if CLOB is not supported
        C2: Multibyte varchar
        X2: Multibyte CLOB

        B:  BLOB (binary large object)

        D:  Date
        T:  Date-time
        L:  Integer field suitable for storing booleans (0 or 1)
        I:  Integer
        F:  Floating point number
        N:  Numeric or decimal number
     */
    public function actualType($meta)
    {
        return $meta;
    }

    public function createDatabase($dbname, $options = false)
    {
        $options = $this->_options($options);
        $sql = [];

        $s = 'CREATE DATABASE '.$this->nameQuote($dbname);
        if (isset($options[$this->upperName])) {
            $s .= ' '.$options[$this->upperName];
        }

        $sql[] = $s;

        return $sql;
    }

    /*
     Generates the SQL to create index. Returns an array of sql strings.
    */
    public function createIndexSQL($idxname, $tabname, $flds, $idxoptions = false)
    {
        if (!is_array($flds)) {
            $flds = explode(',', $flds);
        }

        foreach ($flds as $key => $fld) {
            // some indexes can use partial fields, eg. index first 32 chars of "name" with NAME(32)
            $flds[$key] = $this->nameQuote($fld, $allowBrackets = true);
        }

        return $this->_indexSQL($this->nameQuote($idxname), $this->tableName($tabname), $flds, $this->_options($idxoptions));
    }

    public function dropIndexSQL($idxname, $tabname = null)
    {
        return [sprintf($this->dropIndex, $this->nameQuote($idxname), $this->tableName($tabname))];
    }

    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    public function addColumnSQL($tabname, $flds)
    {
        $tabname = $this->tableName($tabname);
        $sql = [];
        [$lines, $pkey, $idxs] = $this->_genFields($flds);
        // genfields can return FALSE at times
        if ($lines == null) {
            $lines = [];
        }
        $alter = 'ALTER TABLE '.$tabname.$this->addCol.' ';
        foreach ($lines as $v) {
            $sql[] = $alter.$v;
        }
        if (is_array($idxs)) {
            foreach ($idxs as $idx => $idxdef) {
                $sql_idxs = $this->createIndexSql($idx, $tabname, $idxdef['cols'], $idxdef['opts']);
                $sql = array_merge($sql, $sql_idxs);
            }
        }

        return $sql;
    }

    /**
     * Change the definition of one column.
     *
     * As some DBMs can't do that on their own, you need to supply the complete definition of the new table,
     * to allow recreating the table and copying the content over to the new table
     *
     * @param string $tabname      table-name
     * @param string $flds         column-name and type for the changed column
     * @param string $tableflds='' complete definition of the new table, eg. for postgres, default ''
     * @param array/string $tableoptions='' options for the new table see createTableSQL, default ''
     *
     * @return array with SQL strings
     */
    public function alterColumnSQL($tabname, $flds, $tableflds = '', $tableoptions = '')
    {
        $tabname = $this->tableName($tabname);
        $sql = [];
        [$lines, $pkey, $idxs] = $this->_genFields($flds);
        // genfields can return FALSE at times
        if ($lines == null) {
            $lines = [];
        }
        $alter = 'ALTER TABLE '.$tabname.$this->alterCol.' ';
        foreach ($lines as $v) {
            $sql[] = $alter.$v;
        }
        if (is_array($idxs)) {
            foreach ($idxs as $idx => $idxdef) {
                $sql_idxs = $this->createIndexSql($idx, $tabname, $idxdef['cols'], $idxdef['opts']);
                $sql = array_merge($sql, $sql_idxs);
            }
        }

        return $sql;
    }

    /**
     * Rename one column.
     *
     * Some DBMs can only do this together with changeing the type of the column (even if that stays the same, eg. mysql)
     *
     * @param string $tabname   table-name
     * @param string $oldcolumn column-name to be renamed
     * @param string $newcolumn new column-name
     * @param string $flds=''   complete column-definition-string like for addColumnSQL, only used by mysql atm., default=''
     *
     * @return array with SQL strings
     */
    public function renameColumnSQL($tabname, $oldcolumn, $newcolumn, $flds = '')
    {
        $tabname = $this->tableName($tabname);
        if ($flds) {
            [$lines, $pkey, $idxs] = $this->_genFields($flds);
            // genfields can return FALSE at times
            if ($lines == null) {
                $lines = [];
            }
            $first = current($lines);
            [, $column_def] = preg_split("/[\t ]+/", $first, 2);
        }

        return [sprintf($this->renameColumn, $tabname, $this->nameQuote($oldcolumn), $this->nameQuote($newcolumn), $column_def)];
    }

    /**
     * Drop one column.
     *
     * Some DBM's can't do that on their own, you need to supply the complete definition of the new table,
     * to allow, recreating the table and copying the content over to the new table
     *
     * @param string $tabname      table-name
     * @param string $flds         column-name and type for the changed column
     * @param string $tableflds='' complete definition of the new table, eg. for postgres, default ''
     * @param array/string $tableoptions='' options for the new table see createTableSQL, default ''
     *
     * @return array with SQL strings
     */
    public function dropColumnSQL($tabname, $flds, $tableflds = '', $tableoptions = '')
    {
        $tabname = $this->tableName($tabname);
        if (!is_array($flds)) {
            $flds = explode(',', $flds);
        }
        $sql = [];
        $alter = 'ALTER TABLE '.$tabname.$this->dropCol.' ';
        foreach ($flds as $v) {
            $sql[] = $alter.$this->nameQuote($v);
        }

        return $sql;
    }

    public function dropTableSQL($tabname)
    {
        return [sprintf($this->dropTable, $this->tableName($tabname))];
    }

    public function renameTableSQL($tabname, $newname)
    {
        return [sprintf($this->renameTable, $this->tableName($tabname), $this->tableName($newname))];
    }

    /**
     Generate the SQL to create table. Returns an array of sql strings.
     */
    public function createTableSQL($tabname, $flds, $tableoptions = [])
    {
        [$lines, $pkey, $idxs] = $this->_genFields($flds, true);
        // genfields can return FALSE at times
        if ($lines == null) {
            $lines = [];
        }

        $taboptions = $this->_options($tableoptions);
        $tabname = $this->tableName($tabname);
        $sql = $this->_tableSQL($tabname, $lines, $pkey, $taboptions);

        // ggiunta - 2006/10/12 - KLUDGE:
        // if we are on autoincrement, and table options includes REPLACE, the
        // autoincrement sequence has already been dropped on table creation sql, so
        // we avoid passing REPLACE to trigger creation code. This prevents
        // creating sql that double-drops the sequence
        if ($this->autoIncrement && isset($taboptions['REPLACE'])) {
            unset($taboptions['REPLACE']);
        }
        $tsql = $this->_triggers($tabname, $taboptions);
        foreach ($tsql as $s) {
            $sql[] = $s;
        }

        if (is_array($idxs)) {
            foreach ($idxs as $idx => $idxdef) {
                $sql_idxs = $this->createIndexSql($idx, $tabname, $idxdef['cols'], $idxdef['opts']);
                $sql = array_merge($sql, $sql_idxs);
            }
        }

        return $sql;
    }

    public function _genFields($flds, $widespacing = false)
    {
        if (is_string($flds)) {
            $padding = '     ';
            $txt = $flds.$padding;
            $flds = [];
            $flds0 = lens_ParseArgs($txt, ',');
            $hasparam = false;
            foreach ($flds0 as $f0) {
                $f1 = [];
                foreach ($f0 as $token) {
                    switch (strtoupper($token)) {
                        case 'INDEX':
                            $f1['INDEX'] = '';
                            // fall through intentionally
                            // no break
                        case 'CONSTRAINT':
                        case 'DEFAULT':
                            $hasparam = $token;
                            break;
                        default:
                            if ($hasparam) {
                                $f1[$hasparam] = $token;
                            } else {
                                $f1[] = $token;
                            }
                            $hasparam = false;
                            break;
                    }
                }
                // 'index' token without a name means single column index: name it after column
                if (array_key_exists('INDEX', $f1) && $f1['INDEX'] == '') {
                    $f1['INDEX'] = $f0['NAME'] ?? $f0[0];
                    // check if column name used to create an index name was quoted
                    if (($f1['INDEX'][0] == '"' || $f1['INDEX'][0] == "'" || $f1['INDEX'][0] == '`') &&
                        ($f1['INDEX'][0] == substr($f1['INDEX'], -1))) {
                        $f1['INDEX'] = $f1['INDEX'][0].'idx_'.substr($f1['INDEX'], 1, -1).$f1['INDEX'][0];
                    } else {
                        $f1['INDEX'] = 'idx_'.$f1['INDEX'];
                    }
                }
                // reset it, so we don't get next field 1st token as INDEX...
                $hasparam = false;

                $flds[] = $f1;
            }
        }
        $this->autoIncrement = false;
        $lines = [];
        $pkey = [];
        $idxs = [];
        foreach ($flds as $fld) {
            if (is_array($fld)) {
                $fld = array_change_key_case($fld, CASE_UPPER);
            }
            $fname = false;
            $fdefault = false;
            $fautoinc = false;
            $ftype = false;
            $fsize = false;
            $fprec = false;
            $fprimary = false;
            $fnoquote = false;
            $fdefts = false;
            $fdefdate = false;
            $fconstraint = false;
            $fnotnull = false;
            $funsigned = false;
            $findex = '';
            $funiqueindex = false;
            $fOptions = [];

            // -----------------
            // Parse attributes
            foreach ($fld as $attr => $v) {
                if ($attr == 2 && is_numeric($v)) {
                    $attr = 'SIZE';
                } elseif ($attr == 2 && strtoupper($ftype) == 'ENUM') {
                    $attr = 'ENUM';
                } elseif (is_numeric($attr) && $attr > 1 && !is_numeric($v)) {
                    $attr = strtoupper($v);
                }

                switch ($attr) {
                    case '0':
                    case 'NAME': 	$fname = $v;
                        break;
                    case '1':
                    case 'TYPE': 	$ty = $v;
                        $ftype = $this->actualType(strtoupper($v));
                        break;

                    case 'SIZE':
                        $dotat = strpos($v, '.');
                        if ($dotat === false) {
                            $dotat = strpos($v, ',');
                        }
                        if ($dotat === false) {
                            $fsize = $v;
                        } else {
                            $fsize = substr($v, 0, $dotat);
                            $fprec = substr($v, $dotat + 1);
                        }
                        break;
                    case 'UNSIGNED': $funsigned = true;
                        break;
                    case 'AUTOINCREMENT':
                    case 'AUTO':	$fautoinc = true;
                        $fnotnull = true;
                        break;
                    case 'KEY':
                        // a primary key col can be non unique in itself (if key spans many cols...)
                    case 'PRIMARY':	$fprimary = $v;
                        $fnotnull = true;
                        /* $funiqueindex = true; */ break;
                    case 'DEF':
                    case 'DEFAULT': $fdefault = $v;
                        break;
                    case 'NOTNULL': $fnotnull = $v;
                        break;
                    case 'NOQUOTE': $fnoquote = $v;
                        break;
                    case 'DEFDATE': $fdefdate = $v;
                        break;
                    case 'DEFTIMESTAMP': $fdefts = $v;
                        break;
                    case 'CONSTRAINT': $fconstraint = $v;
                        break;
                        // let INDEX keyword create a 'very standard' index on column
                    case 'INDEX': $findex = $v;
                        break;
                    case 'UNIQUE': $funiqueindex = true;
                        break;
                    case 'ENUM':
                        $fOptions['ENUM'] = $v;
                        break;
                } // switch
            } // foreach $fld

            // --------------------
            // VALIDATE FIELD INFO
            if (!strlen($fname)) {
                if ($this->debug) {
                    ADOConnection::outp('Undefined NAME');
                }

                return false;
            }

            $fid = strtoupper(preg_replace('/^`(.+)`$/', '$1', $fname));
            $fname = $this->nameQuote($fname);

            if (!strlen($ftype)) {
                if ($this->debug) {
                    ADOConnection::outp("Undefined TYPE for field '$fname'");
                }

                return false;
            } else {
                $ftype = strtoupper($ftype);
            }

            $ftype = $this->_getSize($ftype, $ty, $fsize, $fprec, $fOptions);

            if (($ty == 'X' || $ty == 'X2' || $ty == 'XL' || $ty == 'B') && !$this->blobAllowsNotNull) {
                /*
                * some blob types do not accept nulls, so we override the
                * previously defined value
                */
                $fnotnull = false;
            }

            if ($fprimary) {
                $pkey[] = $fname;
            }

            if (($ty == 'X' || $ty == 'X2' || $ty == 'XL' || $ty == 'B') && !$this->blobAllowsDefaultValue) {
                /*
                * some databases do not allow blobs to have defaults, so we
                * override the previously defined value
                */
                $fdefault = false;
            }

            // build list of indexes
            if ($findex != '') {
                if (array_key_exists($findex, $idxs)) {
                    $idxs[$findex]['cols'][] = $fname;
                    if (in_array('UNIQUE', $idxs[$findex]['opts']) != $funiqueindex) {
                        if ($this->debug) {
                            ADOConnection::outp("Index $findex defined once UNIQUE and once not");
                        }
                    }
                    if ($funiqueindex && !in_array('UNIQUE', $idxs[$findex]['opts'])) {
                        $idxs[$findex]['opts'][] = 'UNIQUE';
                    }
                } else {
                    $idxs[$findex] = [];
                    $idxs[$findex]['cols'] = [$fname];
                    if ($funiqueindex) {
                        $idxs[$findex]['opts'] = ['UNIQUE'];
                    } else {
                        $idxs[$findex]['opts'] = [];
                    }
                }
            }

            // --------------------
            // CONSTRUCT FIELD SQL
            if ($fdefts) {
                if (substr($this->connection->databaseType, 0, 5) == 'mysql') {
                    $ftype = 'TIMESTAMP';
                } else {
                    $fdefault = $this->connection->sysTimeStamp;
                }
            } elseif ($fdefdate) {
                if (substr($this->connection->databaseType, 0, 5) == 'mysql') {
                    $ftype = 'TIMESTAMP';
                } else {
                    $fdefault = $this->connection->sysDate;
                }
            } elseif ($fdefault !== false && !$fnoquote) {
                if ($ty == 'C' or $ty == 'X' or
                    (substr($fdefault, 0, 1) != "'" && !is_numeric($fdefault))) {
                    if (($ty == 'D' || $ty == 'T') && strtolower($fdefault) != 'null') {
                        // convert default date into database-aware code
                        if ($ty == 'T') {
                            $fdefault = $this->connection->dbTimeStamp($fdefault);
                        } else {
                            $fdefault = $this->connection->dbDate($fdefault);
                        }
                    } elseif (strlen($fdefault) != 1 && substr($fdefault, 0, 1) == ' ' && substr($fdefault, strlen($fdefault) - 1) == ' ') {
                        $fdefault = trim($fdefault);
                    } elseif (strtolower($fdefault) != 'null') {
                        $fdefault = $this->connection->qstr($fdefault);
                    }
                }
            }
            $suffix = $this->_createSuffix($fname, $ftype, $fnotnull, $fdefault, $fautoinc, $fconstraint, $funsigned);

            // add index creation
            if ($widespacing) {
                $fname = str_pad($fname, 24);
            }

            // check for field names appearing twice
            if (array_key_exists($fid, $lines)) {
                ADOConnection::outp("Field '$fname' defined twice");
            }

            $lines[$fid] = $fname.' '.$ftype.$suffix;

            if ($fautoinc) {
                $this->autoIncrement = true;
            }
        } // foreach $flds

        return [$lines, $pkey, $idxs];
    }

    /**
         GENERATE THE SIZE PART OF THE DATATYPE
            $ftype is the actual type
            $ty is the type defined originally in the DDL
     */
    public function _getSize($ftype, $ty, $fsize, $fprec, $options = false)
    {
        if (strlen($fsize) && $ty != 'X' && $ty != 'B' && strpos($ftype, '(') === false) {
            $ftype .= '('.$fsize;
            if (strlen($fprec)) {
                $ftype .= ','.$fprec;
            }
            $ftype .= ')';
        }

        /*
        * Handle additional options
        */
        if (is_array($options)) {
            foreach ($options as $type => $value) {
                switch ($type) {
                    case 'ENUM':
                        $ftype .= '('.$value.')';
                        break;

                    default:
                }
            }
        }

        return $ftype;
    }

    // return string must begin with space
    public function _createSuffix($fname, &$ftype, $fnotnull, $fdefault, $fautoinc, $fconstraint, $funsigned)
    {
        $suffix = '';
        if (strlen($fdefault)) {
            $suffix .= " DEFAULT $fdefault";
        }
        if ($fnotnull) {
            $suffix .= ' NOT NULL';
        }
        if ($fconstraint) {
            $suffix .= ' '.$fconstraint;
        }

        return $suffix;
    }

    public function _indexSQL($idxname, $tabname, $flds, $idxoptions)
    {
        $sql = [];

        if (isset($idxoptions['REPLACE']) || isset($idxoptions['DROP'])) {
            $sql[] = sprintf($this->dropIndex, $idxname);
            if (isset($idxoptions['DROP'])) {
                return $sql;
            }
        }

        if (empty($flds)) {
            return $sql;
        }

        $unique = isset($idxoptions['UNIQUE']) ? ' UNIQUE' : '';

        $s = 'CREATE'.$unique.' INDEX '.$idxname.' ON '.$tabname.' ';

        if (isset($idxoptions[$this->upperName])) {
            $s .= $idxoptions[$this->upperName];
        }

        if (is_array($flds)) {
            $flds = implode(', ', $flds);
        }
        $s .= '('.$flds.')';
        $sql[] = $s;

        return $sql;
    }

    public function _dropAutoIncrement($tabname)
    {
        return false;
    }

    public function _tableSQL($tabname, $lines, $pkey, $tableoptions)
    {
        $sql = [];

        if (isset($tableoptions['REPLACE']) || isset($tableoptions['DROP'])) {
            $sql[] = sprintf($this->dropTable, $tabname);
            if ($this->autoIncrement) {
                $sInc = $this->_dropAutoIncrement($tabname);
                if ($sInc) {
                    $sql[] = $sInc;
                }
            }
            if (isset($tableoptions['DROP'])) {
                return $sql;
            }
        }

        $s = "CREATE TABLE $tabname (\n";
        $s .= implode(",\n", $lines);
        if (sizeof($pkey) > 0) {
            $s .= ",\n                 PRIMARY KEY (";
            $s .= implode(', ', $pkey).')';
        }
        if (isset($tableoptions['CONSTRAINTS'])) {
            $s .= "\n".$tableoptions['CONSTRAINTS'];
        }

        if (isset($tableoptions[$this->upperName.'_CONSTRAINTS'])) {
            $s .= "\n".$tableoptions[$this->upperName.'_CONSTRAINTS'];
        }

        $s .= "\n)";
        if (isset($tableoptions[$this->upperName])) {
            $s .= $tableoptions[$this->upperName];
        }
        $sql[] = $s;

        return $sql;
    }

    /**
        GENERATE TRIGGERS IF NEEDED
        used when table has auto-incrementing field that is emulated using triggers
     */
    public function _triggers($tabname, $taboptions)
    {
        return [];
    }

    /**
        Sanitize options, so that array elements with no keys are promoted to keys
     */
    public function _options($opts)
    {
        if (!is_array($opts)) {
            return [];
        }
        $newopts = [];
        foreach ($opts as $k => $v) {
            if (is_numeric($k)) {
                $newopts[strtoupper($v)] = $v;
            } else {
                $newopts[strtoupper($k)] = $v;
            }
        }

        return $newopts;
    }

    public function _getSizePrec($size)
    {
        $fsize = false;
        $fprec = false;
        $dotat = strpos($size, '.');
        if ($dotat === false) {
            $dotat = strpos($size, ',');
        }
        if ($dotat === false) {
            $fsize = $size;
        } else {
            $fsize = substr($size, 0, $dotat);
            $fprec = substr($size, $dotat + 1);
        }

        return [$fsize, $fprec];
    }

    /**
    "Florian Buzin [ easywe ]" <florian.buzin#easywe.de>

    This function changes/adds new fields to your table. You don't
    have to know if the col is new or not. It will check on its own.
     */
    public function changeTableSQL($tablename, $flds, $tableoptions = false, $dropOldFlds = false)
    {
        global $ADODB_FETCH_MODE;

        $save = $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        if ($this->connection->fetchMode !== false) {
            $savem = $this->connection->setFetchMode(false);
        }

        // check table exists
        $save_handler = $this->connection->raiseErrorFn;
        $this->connection->raiseErrorFn = '';
        $cols = $this->metaColumns($tablename);
        $this->connection->raiseErrorFn = $save_handler;

        if (isset($savem)) {
            $this->connection->setFetchMode($savem);
        }
        $ADODB_FETCH_MODE = $save;

        if (empty($cols)) {
            return $this->createTableSQL($tablename, $flds, $tableoptions);
        }

        if (is_array($flds)) {
            // Cycle through the update fields, comparing
            // existing fields to fields to update.
            // if the Metatype and size is exactly the
            // same, ignore - by Mark Newham
            $holdflds = [];
            foreach ($flds as $k => $v) {
                if (isset($cols[$k]) && is_object($cols[$k])) {
                    // If already not allowing nulls, then don't change
                    $obj = $cols[$k];
                    if (isset($obj->not_null) && $obj->not_null) {
                        $v = str_replace('NOT NULL', '', $v);
                    }
                    if (isset($obj->auto_increment) && $obj->auto_increment && empty($v['AUTOINCREMENT'])) {
                        $v = str_replace('AUTOINCREMENT', '', $v);
                    }

                    $c = $cols[$k];
                    $ml = $c->max_length;
                    $mt = $this->metaType($c->type, $ml);

                    if (isset($c->scale)) {
                        $sc = $c->scale;
                    } else {
                        $sc = 99;
                    } // always force change if scale not known.

                    if ($sc == -1) {
                        $sc = false;
                    }
                    [$fsize, $fprec] = $this->_getSizePrec($v['SIZE']);

                    if ($ml == -1) {
                        $ml = '';
                    }
                    if ($mt == 'X') {
                        $ml = $v['SIZE'];
                    }
                    if (($mt != $v['TYPE']) || ($ml != $fsize || $sc != $fprec) || (isset($v['AUTOINCREMENT']) && $v['AUTOINCREMENT'] != $obj->auto_increment)) {
                        $holdflds[$k] = $v;
                    }
                } else {
                    $holdflds[$k] = $v;
                }
            }
            $flds = $holdflds;
        }

        // already exists, alter table instead
        [$lines, $pkey, $idxs] = $this->_genFields($flds);
        // genfields can return FALSE at times
        if ($lines == null) {
            $lines = [];
        }
        $alter = 'ALTER TABLE '.$this->tableName($tablename);
        $sql = [];

        foreach ($lines as $id => $v) {
            if (isset($cols[$id]) && is_object($cols[$id])) {
                $flds = lens_ParseArgs($v, ',');

                //  We are trying to change the size of the field, if not allowed, simply ignore the request.
                // $flds[1] holds the type, $flds[2] holds the size -postnuke addition
                if ($flds && in_array(strtoupper(substr($flds[0][1], 0, 4)), $this->invalidResizeTypes4)
                 && (isset($flds[0][2]) && is_numeric($flds[0][2]))) {
                    if ($this->debug) {
                        ADOConnection::outp(sprintf('<h3>%s cannot be changed to %s currently</h3>', $flds[0][0], $flds[0][1]));
                    }
                    // echo "<h3>$this->alterCol cannot be changed to $flds currently</h3>";
                    continue;
                }
                $sql[] = $alter.$this->alterCol.' '.$v;
            } else {
                $sql[] = $alter.$this->addCol.' '.$v;
            }
        }

        if ($dropOldFlds) {
            foreach ($cols as $id => $v) {
                if (!isset($lines[$id])) {
                    $sql[] = $alter.$this->dropCol.' '.$v->name;
                }
            }
        }

        return $sql;
    }
} // class
