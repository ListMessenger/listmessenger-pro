<?php
/**
 * Data Dictionary for Microsoft SQL Server native (mssqlnative)

 * FileDescription.
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

/*
In ADOdb, named quotes for MS SQL Server use ". From the MSSQL Docs:

    Note Delimiters are for identifiers only. Delimiters cannot be used for keywords,
    whether or not they are marked as reserved in SQL Server.

    Quoted identifiers are delimited by double quotation marks ("):
    SELECT * FROM "Blanks in Table Name"

    Bracketed identifiers are delimited by brackets ([ ]):
    SELECT * FROM [Blanks In Table Name]

    Quoted identifiers are valid only when the QUOTED_IDENTIFIER option is set to ON. By default,
    the Microsoft OLE DB Provider for SQL Server and SQL Server ODBC driver set QUOTED_IDENTIFIER ON
    when they connect.

    In Transact-SQL, the option can be set at various levels using SET QUOTED_IDENTIFIER,
    the quoted identifier option of sp_dboption, or the user options option of sp_configure.

    When SET ANSI_DEFAULTS is ON, SET QUOTED_IDENTIFIER is enabled.

    Syntax

        SET QUOTED_IDENTIFIER { ON | OFF }


*/

// security - hide paths
if (!defined('ADODB_DIR')) {
    exit;
}

class ADODB2_mssqlnative extends ADODB_DataDict
{
    public $databaseType = 'mssqlnative';
    public $dropIndex = /* @lang text */ 'DROP INDEX %1$s ON %2$s';
    public $renameTable = "EXEC sp_rename '%s','%s'";
    public $renameColumn = "EXEC sp_rename '%s.%s','%s'";
    public $typeX = 'TEXT';  // # Alternatively, set it to VARCHAR(4000)
    public $typeXL = 'TEXT';

    // var $alterCol = ' ALTER COLUMN ';

    public $blobAllowsDefaultValue = true;
    public $blobAllowsNotNull = true;

    public function MetaType($t, $len = -1, $fieldobj = false)
    {
        if (is_object($t)) {
            $fieldobj = $t;
            $t = $fieldobj->type;
        }

        $_typeConversion = [
            -155 => 'D',
              93 => 'D',
            -154 => 'D',
              -2 => 'D',
              91 => 'D',

              12 => 'C',
               1 => 'C',
              -9 => 'C',
              -8 => 'C',

              -7 => 'L',
              -6 => 'I2',
              -5 => 'I8',
             -11 => 'I',
               4 => 'I',
               5 => 'I4',

              -1 => 'X',
             -10 => 'X',

               2 => 'N',
               3 => 'N',
               6 => 'N',
               7 => 'N',

            -152 => 'X',
            -151 => 'X',
              -4 => 'X',
              -3 => 'X',
            ];

        if (isset($_typeConversion[$t])) {
            return $_typeConversion[$t];
        }

        return ADODB_DEFAULT_METATYPE;
    }

    public function ActualType($meta)
    {
        $DATE_TYPE = 'DATETIME';

        switch (strtoupper($meta)) {
            case 'C': return 'VARCHAR';
            case 'XL': return (isset($this)) ? $this->typeXL : 'TEXT';
            case 'X': return (isset($this)) ? $this->typeX : 'TEXT'; // # could be varchar(8000), but we want compat with oracle
            case 'C2': return 'NVARCHAR';
            case 'X2': return 'NTEXT';
            case 'B': return 'IMAGE';
            case 'D': return $DATE_TYPE;
            case 'T': return 'TIME';
            case 'L': return 'BIT';
            case 'R':
            case 'I': return 'INT';
            case 'I1': return 'TINYINT';
            case 'I2': return 'SMALLINT';
            case 'I4': return 'INT';
            case 'I8': return 'BIGINT';
            case 'F': return 'REAL';
            case 'N': return 'NUMERIC';
            default:
                return $meta;
        }
    }

    public function AddColumnSQL($tabname, $flds)
    {
        $tabname = $this->TableName($tabname);
        $f = [];
        [$lines] = $this->_GenFields($flds);
        $s = "ALTER TABLE $tabname $this->addCol";
        foreach ($lines as $v) {
            $f[] = "\n $v";
        }
        $s .= implode(', ', $f);
        $sql[] = $s;

        return $sql;
    }

    /**
     * Get a column's default constraint.
     *
     * @param string $tabname
     * @param string $colname
     *
     * @return string|null the Constraint's name, or null if there is none
     */
    public function defaultConstraintName($tabname, $colname)
    {
        $sql = 'SELECT name FROM sys.default_constraints
			WHERE object_name(parent_object_id) = ?
			AND col_name(parent_object_id, parent_column_id) = ?';

        return $this->connection->getOne($sql, [$tabname, $colname]);
    }

    public function AlterColumnSQL($tabname, $flds, $tableflds = '', $tableoptions = '')
    {
        $tabname = $this->TableName($tabname);
        $sql = [];

        [$lines, , $idxs] = $this->_GenFields($flds);
        $alter = 'ALTER TABLE '.$tabname.$this->alterCol.' ';
        foreach ($lines as $v) {
            if ($not_null = preg_match('/NOT NULL/i', $v)) {
                $v = preg_replace('/NOT NULL/i', '', $v);
            }
            if (preg_match('/^([^ ]+) .*DEFAULT (\'[^\']+\'|\"[^\"]+\"|[^ ]+)/', $v, $matches)) {
                [, $colname, $default] = $matches;
                $v = preg_replace('/^'.preg_quote($colname).'\s/', '', $v);
                $t = trim(str_replace('DEFAULT '.$default, '', $v));
                if ($constraintname = $this->defaultConstraintName($tabname, $colname)) {
                    $sql[] = 'ALTER TABLE '.$tabname.' DROP CONSTRAINT '.$constraintname;
                }
                if ($not_null) {
                    $sql[] = $alter.$colname.' '.$t.' NOT NULL';
                } else {
                    $sql[] = $alter.$colname.' '.$t;
                }
                $sql[] = 'ALTER TABLE '.$tabname
                    .' ADD CONSTRAINT DF__'.$tabname.'__'.$colname.'__'.dechex(rand())
                    .' DEFAULT '.$default.' FOR '.$colname;
            } else {
                $colname = strtok($v, ' ');
                if ($constraintname = $this->defaultConstraintName($tabname, $colname)) {
                    $sql[] = 'ALTER TABLE '.$tabname.' DROP CONSTRAINT '.$constraintname;
                }
                if ($not_null) {
                    $sql[] = $alter.$v.' NOT NULL';
                } else {
                    $sql[] = $alter.$v;
                }
            }
        }
        if (is_array($idxs)) {
            foreach ($idxs as $idx => $idxdef) {
                $sql_idxs = $this->CreateIndexSql($idx, $tabname, $idxdef['cols'], $idxdef['opts']);
                $sql = array_merge($sql, $sql_idxs);
            }
        }

        return $sql;
    }

    /**
     * Drop a column, syntax is ALTER TABLE table DROP COLUMN column,column.
     *
     * @param string   $tabname      Table Name
     * @param string[] $flds         One, or an array of Fields To Drop
     * @param string   $tableflds    Throwaway value to make the function match the parent
     * @param string   $tableoptions Throway value to make the function match the parent
     *
     * @return string[] The SQL necessary to drop the column
     */
    public function DropColumnSQL($tabname, $flds, $tableflds = '', $tableoptions = '')
    {
        $tabname = $this->TableName($tabname);
        if (!is_array($flds)) {
            /** @noinspection PhpParamsInspection */
            $flds = explode(',', $flds);
        }
        $f = [];
        $s = 'ALTER TABLE '.$tabname;
        foreach ($flds as $v) {
            if ($constraintname = $this->defaultConstraintName($tabname, $v)) {
                $sql[] = 'ALTER TABLE '.$tabname.' DROP CONSTRAINT '.$constraintname;
            }
            $f[] = ' DROP COLUMN '.$this->NameQuote($v);
        }
        $s .= implode(', ', $f);
        $sql[] = $s;

        return $sql;
    }

    // return string must begin with space

    /** @noinspection DuplicatedCode */
    public function _CreateSuffix($fname, &$ftype, $fnotnull, $fdefault, $fautoinc, $fconstraint, $funsigned)
    {
        $suffix = '';
        if (strlen($fdefault)) {
            $suffix .= " DEFAULT $fdefault";
        }
        if ($fautoinc) {
            $suffix .= ' IDENTITY(1,1)';
        }
        if ($fnotnull) {
            $suffix .= ' NOT NULL';
        } elseif ($suffix == '') {
            $suffix .= ' NULL';
        }
        if ($fconstraint) {
            $suffix .= ' '.$fconstraint;
        }

        return $suffix;
    }

    /** @noinspection DuplicatedCode */
    public function _IndexSQL($idxname, $tabname, $flds, $idxoptions)
    {
        $sql = [];

        if (isset($idxoptions['REPLACE']) || isset($idxoptions['DROP'])) {
            $sql[] = sprintf($this->dropIndex, $idxname, $tabname);
            if (isset($idxoptions['DROP'])) {
                return $sql;
            }
        }

        if (empty($flds)) {
            return $sql;
        }

        $unique = isset($idxoptions['UNIQUE']) ? ' UNIQUE' : '';
        $clustered = isset($idxoptions['CLUSTERED']) ? ' CLUSTERED' : '';

        if (is_array($flds)) {
            $flds = implode(', ', $flds);
        }
        $s = 'CREATE'.$unique.$clustered.' INDEX '.$idxname.' ON '.$tabname.' ('.$flds.')';

        if (isset($idxoptions[$this->upperName])) {
            $s .= $idxoptions[$this->upperName];
        }

        $sql[] = $s;

        return $sql;
    }

    public function _GetSize($ftype, $ty, $fsize, $fprec, $options = false)
    {
        switch ($ftype) {
            case 'INT':
            case 'SMALLINT':
            case 'TINYINT':
            case 'BIGINT':
                return $ftype;
        }
        if ($ty == 'T') {
            return $ftype;
        }

        return parent::_GetSize($ftype, $ty, $fsize, $fprec, $options);
    }
}
