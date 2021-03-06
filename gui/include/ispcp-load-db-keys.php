<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category	ispCP
 * @package		ispCP
 * @copyright 	2006-2011 by ispCP | http://isp-control.net
 * @author 		ispCP Team
 * @author 	    Thomas Wacker <thomas.wacker@ispcp.net>
 * @version 	SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Load ispCP database encrypted password
 * @param string $ispcpKeyFile ispCP key file
 * @return array element 0 = db_pass_key, element 1 = db_pass_iv
 */
function ispCP_loadDBKeys($ispcpKeyFile) {

	$lines = file($ispcpKeyFile);
	foreach ($lines as $line) {
		$pos = strpos($line, '=');
		if ($pos > 0) {
			$key = trim(substr($line, 0, $pos));
			$value = trim(substr($line, $pos + 1));

			if ($key == 'DB_PASS_KEY') {
				$db_pass_key = $value;
			} elseif ($key == 'DB_PASS_IV') {
				$db_pass_iv = $value;
			}
		}
	}

	return array($db_pass_key, $db_pass_iv);
}

function ispCP_getKeyFile() {
	switch (PHP_OS) {
			case 'FreeBSD':
			case 'OpenBSD':
			case 'NetBSD':
					$ispcp_etc_dir = '/usr/local/etc/ispcp/ispcp-keys.conf';
					break;
			default:
					$ispcp_etc_dir = '/etc/ispcp/ispcp-keys.conf';
	}

	return $ispcp_etc_dir;
}

$ispcpKeyFile = ispCP_getKeyFile();

if (file_exists($ispcpKeyFile)) {
	// load relevant keys from new configuration file
	list($ispcp_db_pass_key, $ispcp_db_pass_iv) = ispCP_loadDBKeys($ispcpKeyFile);
} else {
	// old style include
	require_once INCLUDEPATH . '/ispcp-db-keys.php';
}
