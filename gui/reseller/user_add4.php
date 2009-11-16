<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/user_add4.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('alias_list', 'page');
$tpl->define_dynamic('alias_entry', 'alias_list');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
	)
);

/*
 *
 * static page messages.
 *
 */

if (isset($_SESSION['dmn_id']) && $_SESSION['dmn_id'] !== '') {
	$reseller_id = $_SESSION['user_id'];
	$domain_id = $_SESSION['dmn_id'];

	$query = "
		SELECT
			`domain_id`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		AND
			`domain_created_id` = ?
	";

	$rs = exec_query($sql, $query, array($domain_id, $reseller_id));

	if ($rs->RecordCount() == 0) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
		user_goto('users.php');
	}
	// check main domain status
	$ok_status = Config::get('ITEM_OK_STATUS');
	$add_status = Config::get('ITEM_ADD_STATUS');

	$query = "
		SELECT
			`domain_id`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		AND
			(`domain_status` = ? OR `domain_status` = ?)
	";

	$rs = exec_query($sql, $query, array($domain_id, $ok_status, $add_status));
	if ($rs->RecordCount() == 0) {
		set_page_message(tr('System error with Domain ID ') . "$domain_id");
		user_goto('users.php');
	}
} else {
	set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
	user_goto('users.php');
}

$err_txt = '_off_';
if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_alias') {
	add_domain_alias($sql, $err_txt);
}

init_empty_data();

gen_al_page($tpl, $_SESSION['user_id']);

gen_page_message($tpl);

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_users_manage.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_ADD_USER_PAGE_TITLE' => tr('ispCP - User/Add user'),
		'TR_MANAGE_DOMAIN_ALIAS' => tr('Manage domain alias'),
		'TR_ADD_ALIAS' => tr('Add domain alias'),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_DOMAIN_ACCOUNT' => tr('User account'),
		'TR_MOUNT_POINT' => tr('Directory mount point'),
		'TR_DOMAIN_IP' => tr('Domain IP'),
		'TR_FORWARD' => tr('Forward to URL'),
		'TR_ADD' => tr('Add alias'),
		'TR_DOMAIN_ALIS' => tr('Domain alias'),
		'TR_STATUS' => tr('Status'),
		'TR_ADD_USER' => tr('Add user'),
		'TR_GO_USERS' => tr('Done')
	)
);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
// Begin function declaration lines

function init_empty_data() {
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point, $tpl;

	$cr_user_id = $alias_name = $domain_ip = $forward = $mount_point = '';

	$tpl->assign(
		array(
			'DOMAIN' => $alias_name,
			'MP' => $mount_point,
			'FORWARD' => 'no'
		)
	);
} // End of init_empty_data()

/**
 * Show data fields
 */
function gen_al_page(&$tpl, $reseller_id) {
	$sql = Database::getInstance();

	$dmn_id = $_SESSION['dmn_id'];

	$query = "
		SELECT
			`alias_id`,
			`alias_name`,
			`alias_status`
		FROM
			`domain_aliasses`
		WHERE
			`domain_id` = ?
	";

	$rs = exec_query($sql, $query, array($dmn_id));

	if ($rs->RecordCount() == 0) {
		$tpl->assign('ALIAS_LIST', '');
	} else {
		$i = 0;
		while (!$rs->EOF) {
			$alias_name = decode_idna($rs->fields['alias_name']);
			$alias_status = translate_dmn_status($rs->fields['alias_status']);

			$page_cont = ($i % 2 == 0) ? 'content' : 'content2';

			$tpl->assign(
				array(
					'DOMAIN_ALIS' => $alias_name,
					'STATUS' => $alias_status,
					'CLASS' => $page_cont,
				)
			);

			$i++;
			$tpl->parse('ALIAS_ENTRY', '.alias_entry');
			$rs->MoveNext();
		}
	}
} // End of gen_al_page()

function add_domain_alias(&$sql, &$err_al) {
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point, $tpl;

	$cr_user_id = $dmn_id = $_SESSION['dmn_id'];
	$alias_name = strtolower(clean_input($_POST['ndomain_name']));
	$domain_ip = $_SESSION['dmn_ip'];
	$mount_point = strtolower(clean_input($_POST['ndomain_mpoint']));
	$forward = strtolower(clean_input($_POST['forward']));

	$alias_name = encode_idna($alias_name);
	// First check is the data correct
	if (!chk_dname($alias_name)) {
		$err_al = tr("Incorrect domain name syntax");
	} else if (ispcp_domain_exists($alias_name, $_SESSION['user_id'])) {
		$err_al = tr('Domain with that name already exists on the system!');
	} else if (!chk_mountp($mount_point) && $mount_point != '/') {
		$err_al = tr("Incorrect mount point syntax");
	} else if ($forward != 'no') {
		if (!chk_forward_url($forward)) {
			$err_al = tr("Incorrect forward syntax");
		}
		/** @todo test and remove if no bugs encounter
		if (!preg_match("/\/$/", $forward) && !preg_match("/\?/", $forward)) {
			$forward .= "/";
		}*/
	} else {
		$query = "SELECT `domain_id` FROM `domain_aliasses` WHERE `alias_name` = ?";
		$res = exec_query($sql, $query, array($alias_name));
		$query="SELECT `domain_id` FROM `domain` WHERE `domain_name` = ?";
		$res2 = exec_query($sql, $query, array($alias_name));
		if ($res->RowCount() > 0 || $res2->RowCount() > 0) {
			// we already have a domain with this name
			$err_al = tr("Domain with this name already exist");
		}

		if (mount_point_exists($dmn_id, $mount_point)) {
			$err_al = tr('Mount point already in use!');
		}

	}

	if ('_off_' !== $err_al) {
		set_page_message($err_al);
		return;
	}
	// Begin add new alias domain
	$status = Config::get('ITEM_ADD_STATUS');

	$query = "INSERT INTO `domain_aliasses` (`domain_id`, `alias_name`, `alias_mount`, `alias_status`, `alias_ip_id`, `url_forward`) VALUES (?, ?, ?, ?, ?, ?)";
	exec_query($sql, $query, array(
			$cr_user_id,
			$alias_name,
			$mount_point,
			$status,
			$domain_ip,
			$forward
	));

	update_reseller_c_props(get_reseller_id($cr_user_id));

	send_request();
	$admin_login = $_SESSION['user_logged'];
	write_log("$admin_login: add domain alias: $alias_name");

	set_page_message(tr('Domain alias added!'));
} // End of add_domain_alias();

function gen_page_msg(&$tpl, $erro_txt) {
	if ($erro_txt != '_off_') {
		$tpl->assign('MESSAGE', $erro_txt);
		$tpl->parse('PAGE_MESSAGE', 'page_message');
	} else {
		$tpl->assign('PAGE_MESSAGE', '');
	}
} // End of gen_page_msg()
