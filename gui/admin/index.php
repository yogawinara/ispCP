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

$cfg = ispCP_Registry::get('Config');

check_login(__FILE__, $cfg->PREVENT_EXTERNAL_LOGIN_ADMIN);

$tpl = ispCP_TemplateEngine::getInstance();
$template = 'index.tpl';

// static page messages
$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('ispCP - Admin/Main Index')
	)
);

gen_admin_mainmenu($tpl, 'main_menu_general_information.tpl');
gen_admin_menu($tpl, 'menu_general_information.tpl');

get_admin_general_info($tpl, $sql);

get_update_infos($tpl);

gen_system_message($tpl, $sql);

gen_server_trafic($tpl);

gen_page_message($tpl);

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug($tpl);
}

$tpl->display($template);

unset_messages();

/**
 * @param ispCP_TemplateEngine $tpl
 * @param ispCP_Database $sql
 * @return void
 */
function gen_system_message($tpl, $sql) {
	$user_id = $_SESSION['user_id'];

	$query = "
		SELECT
			COUNT(`ticket_id`) AS cnum
		FROM
			`tickets`
		WHERE
			`ticket_to` = ?
		AND
			`ticket_status` IN ('1', '4')
		AND
			`ticket_reply` = 0
	;";

	$rs = exec_query($sql, $query, $user_id);

	$num_question = $rs->fields('cnum');

	if ($num_question == 0) {
		$tpl->assign(array('MSG_ENTRY' => ''));
	} else {
		$tpl->assign(
			array(
				'TR_NEW_MSGS' => tr('You have <strong>%d</strong> new support questions', $num_question),
				'NEW_MSG_TYPE' => 'notice',
				'TR_VIEW' => tr('View')
			)
		);
	}
}

/**
 * @param ispCP_TemplateEngine $tpl
 * @return void
 */
function get_update_infos($tpl) {

	$cfg = ispCP_Registry::get('Config');

	if (ispCP_Update_Database::getInstance()->checkUpdateExists()) {
		$tpl->assign(
			array(
				'DATABASE_UPDATE' => '<a href="database_update.php" class="link">' . tr('A database update is available') . '</a>',
				'DATABASE_MSG_TYPE' => 'notice'
			)
		);
	} else {
		$tpl->assign(array('DATABASE_UPDATE_MESSAGE' => ''));
	}

	if (!$cfg->CHECK_FOR_UPDATES) {
		$tpl->assign(
			array(
				'UPDATE' => tr('Update checking is disabled!'),
				'UPDATE_TYPE' => 'notice'
			)
		);
		return false;
	}

	if (ispCP_Update_Version::getInstance()->checkUpdateExists()) {
		$tpl->assign(
			array(
				'UPDATE' => '<a href="ispcp_updates.php" class="link">' . tr('New ispCP update is now available') . '</a>',
				'UPDATE_TYPE' => 'notice'
			)
		);
	} else {
		if (ispCP_Update_Version::getInstance()->getErrorMessage() != "") {
			$tpl->assign(
				array(
					'UPDATE' => ispCP_Update_Version::getInstance()->getErrorMessage(),
					'UPDATE_TYPE' => 'error'
				)
			);
		} else {
			$tpl->assign(array('UPDATE_MESSAGE' => ''));
		}
	}
}

/**
 * @param ispCP_TemplateEngine $tpl
 * @return void
 */
function gen_server_trafic($tpl) {
	$sql = ispCP_Registry::get('Db');

	$query = "
		SELECT
			`straff_max`, `straff_warn`
		FROM
			`straff_settings`
	;";

	$rs = exec_query($sql, $query);

	$straff_max  = $rs->fields['straff_max'] * 1024 * 1024;
	$straff_warn = $rs->fields['straff_warn'] * 1024 * 1024;

	$fdofmnth = mktime(0, 0, 0, date("m"), 1, date("Y"));
	$ldofmnth = mktime(1, 0, 0, date("m") + 1, 0, date("Y"));

	$query = "
		SELECT
			IFNULL((SUM(`bytes_in`) + SUM(`bytes_out`)), 0) AS traffic
		FROM
			`server_traffic`
		WHERE
			`traff_time` > ?
		AND
			`traff_time` < ?
	;";

	$rs1 = exec_query($sql, $query, array($fdofmnth, $ldofmnth));

	$traff = $rs1->fields['traffic'];

	$mtraff = sprintf("%.2f", $traff);

	if ($straff_max == 0) {
		$pr = 0;
	} else {
		$pr = ($traff / $straff_max) * 100;
	}

	if (($straff_max != 0 || $straff_max != '') && ($mtraff > $straff_max)) {
		$tpl->assign(
			array(
				'TR_TRAFFIC_WARNING' => tr('You are exceeding your traffic limit!')
			)
		);
	} else if(($straff_warn != 0 || $straff_warn != '') && ($mtraff > $straff_warn)) {
		$tpl->assign(
			array(
				'TR_TRAFFIC_WARNING' => tr('You traffic limit will be reached soon!')
			)
		);
	} else {
		$tpl->assign('TRAFF_WARN', '');
	}

	$bar_value = calc_bar_value($traff, $straff_max, 400);

	$percent = 0;
	if ($straff_max == 0) {
		$traff_msg = tr('%1$d%% [%2$s of unlimited]', $pr, sizeit($mtraff));
	} else {
		$traff_msg = tr('%1$d%% [%2$s of %3$s]', $pr, sizeit($mtraff), sizeit($straff_max));
		$percent = (($traff/$straff_max)*100 < 99.7) ? ($traff/$straff_max)*100 : 99.7;
	}

	$tpl->assign(
		array(
			'TRAFFIC_WARNING' => $traff_msg,
			'BAR_VALUE' => $bar_value,
			'TRAFFIC_PERCENT' => $percent,
		)
	);
}
?>