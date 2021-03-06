<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2011 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
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
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2011 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$cfg = ispCP_Registry::get('Config');

$tpl = ispCP_TemplateEngine::getInstance();
$template = 'domain_delete.tpl';

if (isset($_GET['domain_id']) && is_numeric($_GET['domain_id'])) {
	validate_domain_deletion(intval($_GET['domain_id']));
} else if (isset($_POST['domain_id']) && is_numeric($_POST['domain_id'])
	&& isset($_POST['delete']) && $_POST['delete'] == 1) {
	delete_domain((int)$_POST['domain_id'], 'users.php?psi=last', true);
} else {
	set_page_message(tr('Wrong domain ID!'), 'error');
	user_goto('users.php?psi=last');
}

// static page messages
gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('ispCP - Delete Domain')
	)
);

gen_reseller_mainmenu($tpl, 'main_menu_users_manage.tpl');
gen_reseller_menu($tpl, 'menu_users_manage.tpl');

gen_page_message($tpl);

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug($tpl);
}

$tpl->display($template);

/**
 * Validate domain deletion, display all items to delete
 * @param integer $domain_id
 */
function validate_domain_deletion($domain_id) {
	global $tpl, $sql;

	$reseller = $_SESSION['user_id'];

	// check for domain owns
	$query = "SELECT `domain_id`, `domain_name` FROM `domain` WHERE `domain_id` = ? AND `domain_created_id` = ?";
	$res = exec_query($sql, $query, array($domain_id, $reseller));
	$data = $res->fetchRow();
	if ($data['domain_id'] == 0) {
		set_page_message(tr('Wrong domain ID!'), 'error');
		user_goto('users.php?psi=last');
	}

	$tpl->assign(
		array(
			'TR_DELETE_DOMAIN'	=> tr('Delete domain'),
			'TR_DOMAIN_SUMMARY'	=> tr('Domain summary:'),
			'TR_DOMAIN_EMAILS'	=> tr('Domain e-mails:'),
			'TR_DOMAIN_FTPS'	=> tr('Domain FTP accounts:'),
			'TR_DOMAIN_ALIASES'	=> tr('Domain aliases:'),
			'TR_DOMAIN_SUBS'	=> tr('Domain subdomains:'),
			'TR_DOMAIN_DBS'		=> tr('Domain databases:'),
			'TR_REALLY_WANT_TO_DELETE_DOMAIN'	=> tr('Do you really want to delete the entire domain? This operation cannot be undone!'),
			'TR_BUTTON_DELETE'	=> tr('Delete domain'),
			'TR_YES_DELETE_DOMAIN'	=> tr('Yes, delete the domain.'),
			'DOMAIN_NAME'		=> $data['domain_name'],
			'DOMAIN_ID'			=> $data['domain_id']
		)
	);

	// check for mail acc in MAIN domain
	$query = "SELECT * FROM `mail_users` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, $domain_id);
	if (!$res->EOF) {
		while (!$res->EOF) {

			// Create mail type's text
			$mail_types = explode(',', $res->fields['mail_type']);
			$mdisplay_a = array();
			foreach ($mail_types as $mtype) {
				$mdisplay_a[] = user_trans_mail_type($mtype);
			}
			$mdisplay_txt = implode(', ', $mdisplay_a);

			$tpl->append(
				array(
					'MAIL_ADDR' => tohtml($res->fields['mail_addr']),
					'MAIL_TYPE' => $mdisplay_txt
				)
			);

			$res->moveNext();
		}
	} else {
		$tpl->assign('MAIL_LIST', '');
	}

	// check for ftp acc in MAIN domain
	$query = "SELECT `ftp_users`.* FROM `ftp_users`, `domain` WHERE `domain`.`domain_id` = ? AND `ftp_users`.`uid` = `domain`.`domain_uid`";
	$res = exec_query($sql, $query, $domain_id);
	if (!$res->EOF) {
		while (!$res->EOF) {

			$tpl->append(
				array(
					'FTP_USER' => tohtml($res->fields['userid']),
					'FTP_HOME' => tohtml($res->fields['homedir'])
				)
			);

			$res->moveNext();
		}
	} else {
		$tpl->assign('FTP_LIST', '');
	}

	// check for alias domains
	$alias_a = array();
	$query = "SELECT * FROM `domain_aliasses` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, $domain_id);
	if (!$res->EOF) {
		while (!$res->EOF) {
			$alias_a[] = $res->fields['alias_id'];

			$tpl->append(
				array(
					'ALS_NAME' => tohtml($res->fields['alias_name']),
					'ALS_MNT' => tohtml($res->fields['alias_mount'])
				)
			);

			$res->moveNext();
		}
	} else {
		$tpl->assign('ALS_LIST', '');
	}

	// check for subdomains
	$any_sub_found = false;
	$query = "SELECT * FROM `subdomain` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, $domain_id);
	while (!$res->EOF) {
		$any_sub_found = true;
		$tpl->append(
			array(
				'SUB_NAME' => tohtml($res->fields['subdomain_name']),
				'SUB_MNT' => tohtml($res->fields['subdomain_mount'])
			)
		);

		$res->moveNext();
	}

	if (!$any_sub_found) {
		$tpl->assign('SUB_LIST', '');
	}

	// Check subdomain_alias
	if (count($alias_a) > 0) {
		$query = "SELECT * FROM `subdomain_alias` WHERE `alias_id` IN (";
		$query .= implode(',', $alias_a);
		$query .= ")";
		$res = exec_query($sql, $query);
		while (!$res->EOF) {
			$tpl->append(
				array(
					'SUB_NAME' => tohtml($res->fields['subdomain_alias_name']),
					'SUB_MNT' => tohtml($res->fields['subdomain_alias_mount'])
				)
			);

			$res->moveNext();
		}
	}

	// Check for databases and -users
	$query = "SELECT * FROM `sql_database` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, $domain_id);
	if (!$res->EOF) {

		while (!$res->EOF) {

			$query = "SELECT * FROM `sql_user` WHERE `sqld_id` = ?";
			$ures = exec_query($sql, $query, $res->fields['sqld_id']);

			$users_a = array();
			while (!$ures->EOF) {
				$users_a[] = $ures->fields['sqlu_name'];
				$ures->moveNext();
			}
			$users_txt = implode(', ', $users_a);

			$tpl->append(
				array(
					'DB_NAME' => tohtml($res->fields['sqld_name']),
					'DB_USERS' => tohtml($users_txt)
				)
			);

			$res->moveNext();
		}
	} else {
		$tpl->assign('DB_LIST', '');
	}

}
?>