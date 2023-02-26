<?php
/**
 * Data Dictionary for Interbase.
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

class ADODB2_ibase extends ADODB_DataDict
{
    public $databaseType = 'ibase';
    public $seqField = false;

    public function ActualType($meta)
    {
        switch ($meta) {
            case 'C': return 'VARCHAR';
            case 'XL':
            case 'X': return 'VARCHAR(4000)';
            case 'C2': return 'VARCHAR'; // up to 32K
            case 'X2': return 'VARCHAR(4000)';
            case 'B': return 'BLOB';
            case 'D': return 'DATE';
            case 'TS':
            case 'T': return 'TIMESTAMP';
            case 'L': return 'SMALLINT';
            case 'I': return 'INTEGER';
            case 'I1': return 'SMALLINT';
            case 'I2': return 'SMALLINT';
            case 'I4': return 'INTEGER';
            case 'I8': return 'INTEGER';
            case 'F': return 'DOUBLE PRECISION';
            case 'N': return 'DECIMAL';
            default:
                return $meta;
        }
    }

    public function AlterColumnSQL($tabname, $flds, $tableflds = '', $tableoptions = '')
    {
        if ($this->debug) {
            ADOConnection::outp('AlterColumnSQL not supported');
        }

        return [];
    }

    public function DropColumnSQL($tabname, $flds, $tableflds = '', $tableoptions = '')
    {
        if ($this->debug) {
            ADOConnection::outp('DropColumnSQL not supported');
        }

        return [];
    }
}
