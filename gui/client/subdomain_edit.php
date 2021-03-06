<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2011 by ispCP | http://isp-control.net
 * @version 	SVN: $Id: alias_edit.php 2599 2010-02-11 15:08:57Z benedikt $
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
$template = 'subdomain_edit.tpl';

// "Modify" button has been pressed
if (isset($_POST['uaction']) && ($_POST['uaction'] === 'modify')) {
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	} else if (isset($_SESSION['edit_ID'])) {
		$editid = $_SESSION['edit_ID'];
	} else {
		unset($_SESSION['edit_ID']);

		$_SESSION['subedit'] = '_no_';
		user_goto('domains_manage.php');
	}
	// Get subdomain type
	if (isset($_POST['dmn_type'])) {
		$dmntype = $_POST['dmn_type'];
	} else {
		unset($_SESSION['edit_ID']);

		$_SESSION['subedit'] = '_no_';
		user_goto('domains_manage.php');
	}
	// Save data to db
	if (check_fwd_data($tpl, $sql, $editid, $dmntype)) {
		$_SESSION['subedit'] = '_yes_';
		user_goto('domains_manage.php');
	}
} else {
	// Get user id that comes for edit
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	}

	// Get subdomain type
	if (isset($_GET['dmn_type'])) {
		$dmntype = $_GET['dmn_type'];
	} else {
		user_goto('domains_manage.php');
	}

	$_SESSION['edit_ID'] = $editid;
	$tpl->assign('PAGE_MESSAGE', '');
}

// static page messages
gen_logged_from($tpl);
$tpl->assign(
	array(
		'TR_PAGE_TITLE'			=> tr('ispCP - Manage Subdomain/Edit Subdomain'),
		'TR_MANAGE_SUBDOMAIN'	=> tr('Manage subdomain'),
		'TR_EDIT_SUBDOMAIN'		=> tr('Edit subdomain'),
		'TR_SUBDOMAIN_NAME'		=> tr('Subdomain name'),
		'TR_FORWARD'			=> tr('Forward to URL'),
		'TR_MOUNT_POINT'		=> tr('Mount Point'),
		'TR_MODIFY'				=> tr('Modify'),
		'TR_CANCEL'				=> tr('Cancel'),
		'TR_ENABLE_FWD'			=> tr('Enable Forward'),
		'TR_ENABLE'				=> tr('Enable'),
		'TR_DISABLE'			=> tr('Disable'),
		'TR_PREFIX_HTTP'		=> 'http://',
		'TR_PREFIX_HTTPS'		=> 'https://',
		'TR_PREFIX_FTP'			=> 'ftp://'
	)
);

gen_client_mainmenu($tpl, 'main_menu_manage_domains.tpl');
gen_client_menu($tpl, 'menu_manage_domains.tpl');

gen_editsubdomain_page($tpl, $sql, $editid, $dmntype);

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug($tpl);
}

$tpl->display($template);

unset_messages();

// Begin function block

/**
 * Show user data
 * @param ispCP_TemplateEngine $tpl
 * @param ispCP_Database $sql
 * @param int $edit_id
 * @param string $dmn_type
 */
function gen_editsubdomain_page($tpl, $sql, $edit_id, $dmn_type) {
	// Get data from sql
	list($domain_id, $domain_name) = get_domain_default_props($sql, $_SESSION['user_id']);

	if ($dmn_type === 'dmn') {
		$query = '
			SELECT
				*
			FROM
				`subdomain`
			WHERE
				`subdomain_id` = ?
			AND
				`domain_id` = ?
		';
		$res = exec_query($sql, $query, array($edit_id, $domain_id));
	} else {
		$query = '
			SELECT
				t1.`subdomain_alias_name` AS subdomain_name,
				t1.`subdomain_alias_mount` AS subdomain_mount,
				t1.`subdomain_alias_url_forward` AS subdomain_url_forward,
				t2.`alias_name` AS domain_name
			FROM
				`subdomain_alias` t1
			LEFT JOIN
				(`domain_aliasses` AS t2) ON (t1.`alias_id` = t2.`alias_id`)
			WHERE
				t1.`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
			AND
				`subdomain_alias_id` = ?
		';
		$res = exec_query($sql, $query, array($domain_id, $edit_id));
	}

	if ($res->RecordCount() <= 0) {
		$_SESSION['subedit'] = '_no_';
		user_goto('domains_manage.php');
	}
	$data = $res->FetchRow();

	if ($dmn_type === 'als') {
		$domain_name = $data['domain_name'];
	}

	if (isset($_POST['uaction']) && ($_POST['uaction'] == 'modify')) {
		$url_forward = strtolower(clean_input($_POST['forward']));
	} else {
		$url_forward = decode_idna(preg_replace('(ftp://|https://|http://)', '', $data['subdomain_url_forward']));

		if ($data['subdomain_url_forward'] == 'no') {
			$check_en		= '';
			$check_dis		= 'checked="checked"';
			$url_forward	= '';
			$tpl->assign(
				array(
					'READONLY_FORWARD'	=> ' readonly',
					'DISABLE_FORWARD'	=> ' disabled="disabled"',
					'HTTP_YES'			=> '',
					'HTTPS_YES'			=> '',
					'FTP_YES'			=> ''
				)
			);
		} else {
			$check_en	= 'checked="checked"';
			$check_dis	= '';
			$tpl->assign(
				array(
					'READONLY_FORWARD'	=> '',
					'DISABLE_FORWARD'	=> '',
					'HTTP_YES'			=> (preg_match('/http:\/\//', $data['subdomain_url_forward'])) ? 'selected="selected"' : '',
					'HTTPS_YES'			=> (preg_match('/https:\/\//', $data['subdomain_url_forward'])) ? 'selected="selected"' : '',
					'FTP_YES'			=> (preg_match('/ftp:\/\//', $data['subdomain_url_forward'])) ? 'selected="selected"' : ''
				)
			);
		}
		$tpl->assign(
			array(
				'CHECK_EN'	=> $check_en,
				'CHECK_DIS'	=> $check_dis
			)
		);
	}
	// Fill in the fields
	$tpl->assign(
		array(
			'SUBDOMAIN_NAME'	=> decode_idna($data['subdomain_name']) . '.' . $domain_name,
			'FORWARD'			=> $url_forward,
			'MOUNT_POINT'		=> $data['subdomain_mount'],
			'ID'				=> $edit_id,
			'DMN_TYPE'			=> $dmn_type
		)
	);

}

/**
 * Check input data
 * @param ispCP_TemplateEngine $tpl
 * @param ispCP_Database $sql
 * @param int $subdomain_id
 * @param string $dmn_type
 */
function check_fwd_data($tpl, $sql, $subdomain_id, $dmn_type) {

	$forward_url = strtolower(clean_input($_POST['forward']));
	// unset errors
	$ed_error = '_off_';

	if (isset($_POST['status']) && $_POST['status'] == 1) {
		$forward_prefix = clean_input($_POST['forward_prefix']);
		if (substr_count($forward_url, '.') <= 2) {
			$ret = validates_dname($forward_url);
		} else {
			$ret = validates_dname($forward_url, true);
		}
		if (!$ret) {
			$ed_error = tr('Wrong domain part in forward URL!');
		} else {
			$forward_url = encode_idna($forward_prefix.$forward_url);
		}
		$check_en = 'checked="checked"';
		$check_dis = '';
		$tpl->assign(
			array(
				'FORWARD'	=> $forward_url,
				'HTTP_YES'	=> ($forward_prefix === 'http://') ? 'selected="selected"' : '',
				'HTTPS_YES'	=> ($forward_prefix === 'https://') ? 'selected="selected"' : '',
				'FTP_YES'	=> ($forward_prefix === 'ftp://') ? 'selected="selected"' : '',
				'CHECK_EN'	=> $check_en,
				'CHECK_DIS'	=> $check_dis,
			)
		);
	} else {
		$check_en = '';
		$check_dis = 'checked="checked"';
		$forward_url = 'no';
		$tpl->assign(
			array(
				'READONLY_FORWARD'	=> ' readonly',
				'DISABLE_FORWARD'	=> ' disabled="disabled"',
				'CHECK_EN'			=> $check_en,
				'CHECK_DIS'			=> $check_dis,
			)
		);
	}
	if ($ed_error === '_off_') {
		if ($dmn_type === 'dmn') {
			$query = '
				UPDATE
					`subdomain`
				SET
					`subdomain_url_forward` = ?,
					`subdomain_status` = ?
				 WHERE
					`subdomain_id` = ?
			';
		} else {
			$query = '
				UPDATE
					`subdomain_alias`
				SET
					`subdomain_alias_url_forward` = ?,
					`subdomain_alias_status` = ?
				WHERE
					`subdomain_alias_id` = ?
			';
		}

		exec_query($sql, $query, array($forward_url, ispCP_Registry::get('Config')->ITEM_CHANGE_STATUS, $subdomain_id));

		send_request();

		$admin_login = $_SESSION['user_logged'];
		write_log("$admin_login: change domain alias forward: " . $subdomain_id);
		unset($_SESSION['edit_ID']);
		$tpl->assign('MESSAGE', '');
		return true;
	} else {
		$tpl->assign('MESSAGE', $ed_error);
		return false;
	}
}
?>