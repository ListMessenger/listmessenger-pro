<?php
/**
 * ADOdb Session Management.
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
if (!defined('ADODB_SESSION')) {
    exit;
}

include_once ADODB_SESSION.'/crypt.inc.php';

class ADODB_Encrypt_MD5
{
    public function write($data, $key)
    {
        $md5crypt = new MD5Crypt();

        return $md5crypt->encrypt($data, $key);
    }

    public function read($data, $key)
    {
        $md5crypt = new MD5Crypt();

        return $md5crypt->decrypt($data, $key);
    }
}

return 1;
