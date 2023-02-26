<?php
/**
 * Portable MySQL driver.
 *
 * @deprecated
 *
 * Extends the deprecated mysql driver, and was originally designed to be a
 * portable driver in the same manner as oci8po and mssqlpo. Its functionality
 * is exactly duplicated in the mysqlt driver, which is itself deprecated.
 * This driver will be removed in ADOdb version 6.0.0.
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

include_once ADODB_DIR.'/drivers/adodb-mysql.inc.php';

class ADODB_mysqlt extends ADODB_mysql
{
    public $databaseType = 'mysqlt';
    public $ansiOuter = true; // for Version 3.23.17 or later
    public $hasTransactions = true;
    public $autoRollback = true; // apparently mysql does not autorollback properly

    public function BeginTrans()
    {
        if ($this->transOff) {
            return true;
        }
        ++$this->transCnt;
        $this->Execute('SET AUTOCOMMIT=0');
        $this->Execute('BEGIN');

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

        if ($this->transCnt) {
            --$this->transCnt;
        }
        $this->Execute('COMMIT');
        $this->Execute('SET AUTOCOMMIT=1');

        return true;
    }

    public function RollbackTrans()
    {
        if ($this->transOff) {
            return true;
        }
        if ($this->transCnt) {
            --$this->transCnt;
        }
        $this->Execute('ROLLBACK');
        $this->Execute('SET AUTOCOMMIT=1');

        return true;
    }

    public function RowLock($tables, $where = '', $col = '1 as adodbignore')
    {
        if ($this->transCnt == 0) {
            $this->BeginTrans();
        }
        if ($where) {
            $where = ' where '.$where;
        }
        $rs = $this->Execute("select $col from $tables $where for update");

        return !empty($rs);
    }
}

class ADORecordSet_mysqlt extends ADORecordSet_mysql
{
    public $databaseType = 'mysqlt';

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
            default: $this->fetchMode = MYSQL_BOTH;
                break;
        }

        $this->adodbFetchMode = $mode;
        parent::__construct($queryID);
    }

    public function MoveNext()
    {
        if ($this->fields = mysql_fetch_array($this->_queryID, $this->fetchMode)) {
            ++$this->_currentRow;

            return true;
        }
        if (!$this->EOF) {
            ++$this->_currentRow;
            $this->EOF = true;
        }

        return false;
    }
}

class ADORecordSet_ext_mysqlt extends ADORecordSet_mysqlt
{
    public function MoveNext()
    {
        return adodb_movenext($this);
    }
}
