<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$cfg = ispCP_Registry::get('Config');

$tpl = new ispCP_pTemplate();
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/ticket_closed.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('tickets_list', 'page');
$tpl->define_dynamic('tickets_item', 'tickets_list');
$tpl->define_dynamic('scroll_prev_gray', 'page');
$tpl->define_dynamic('scroll_prev', 'page');
$tpl->define_dynamic('scroll_next_gray', 'page');
$tpl->define_dynamic('scroll_next', 'page');

// page functions

/**
 * Checks if the client's reseller has a support system.
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $sql	the SQL object
 * @param int $admin_id 	the ID of the reseller's admin
 * @return boolean
 */
function hasTicketSystem(&$sql, $admin_id) {
	$cfg = ispCP_Registry::get('Config');

	$query = "
	  SELECT
		`support_system`
	  FROM
		`reseller_props`
	  WHERE
		`reseller_id` = ?
	;";

	$rs = exec_query($sql, $query, $admin_id);

	if (!$cfg->ISPCP_SUPPORT_SYSTEM || $rs->fields['support_system'] == 'no')
		return false;

	return true;
}

/**
 * Generates the list with all closed tickets
 *
 * @author	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @since	1.0.7
 * @version	1.0.0
 *
 * @param reference $tpl    the TPL object
 * @param reference $sql    the SQL object
 * @param int $user_id      the ID of the reseller
 */
function generateTicketList(&$tpl, &$sql, $user_id) {
	$cfg = ispCP_Registry::get('Config');

	$start_index = 0;

	$rows_per_page = $cfg->DOMAIN_ROWS_PER_PAGE;

	if (isset($_GET['psi'])) {
		$start_index = $_GET['psi'];
	}

	$count_query = "
		SELECT
			COUNT(`ticket_id`) AS cnt
		FROM
			`tickets`
		WHERE
			(`ticket_from` = ? OR `ticket_to` = ?)
		AND
			`ticket_status` = 0
		AND
			`ticket_reply` = 0
	;";

	$rs = exec_query($sql, $count_query, array($user_id, $user_id));
	$records_count = $rs->fields['cnt'];

	$query = "
		SELECT
			`ticket_id`,
			`ticket_status`,
			`ticket_urgency`,
			`ticket_date`,
			`ticket_subject`,
			`ticket_message`
		FROM
			`tickets`
		WHERE
			(`ticket_from` = ? OR `ticket_to` = ?)
		AND
			`ticket_status` = 0
		AND
			`ticket_reply` = 0
		ORDER BY
			`ticket_date` DESC
		LIMIT " .
			$start_index . ", " . $rows_per_page
    . ";";

	$rs = exec_query($sql, $query, array($user_id, $user_id));

	if ($rs->recordCount() == 0) {
		$tpl->assign(
			array(
				'TICKETS_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_NEXT' => ''
			)
		);

		set_page_message(tr('You have no support tickets.'));
	} else {
		$prev_si = $start_index - $rows_per_page;

		if ($start_index == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY' => '',
					'PREV_PSI' => $prev_si
				)
			);
		}

		$next_si = $start_index + $rows_per_page;

		if ($next_si + 1 > $records_count) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY' => '',
					'NEXT_PSI' => $next_si
				)
			);
		}

		$i = 0;
		while (!$rs->EOF) {
			$tpl->assign(
                array(
                    'URGENCY'   => get_ticket_urgency($rs->fields['ticket_urgency']),
                    'NEW'       => " ",
					'LAST_DATE'	=> ticketGetLastDate($sql, $rs->fields['ticket_id']),
					'SUBJECT'	=> tohtml($rs->fields['ticket_subject']),
					'SUBJECT2'	=> addslashes(clean_html($rs->fields['ticket_subject'])),
					'ID'		=> $rs->fields['ticket_id'],
					'CONTENT'	=> ($i % 2 == 0) ? 'content' : 'content2'
				)
			);

			$tpl->parse('TICKETS_ITEM', '.tickets_item');
			$rs->moveNext();
			$i++;
		}
	}
}

// common page data.

$tpl->assign(
	array(
		'TR_CLIENT_QUESTION_PAGE_TITLE' => tr('ispCP - Client/Questions & Comments'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
	)
);

// dynamic page data.
$admin_id = $_SESSION['user_created_by'];

if (!hasTicketSystem($sql, $admin_id)) {
	user_goto('index.php');
}

generateTicketList($tpl, $sql, $_SESSION['user_id']);

// static page messages.

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_ticket_system.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_ticket_system.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_SUPPORT_SYSTEM' => tr('Support system'),
		'TR_SUPPORT_TICKETS' => tr('Support tickets'),
		'TR_STATUS' => tr('Status'),
		'TR_NEW' => ' ',
		'TR_ACTION' => tr('Action'),
		'TR_URGENCY' => tr('Priority'),
		'TR_SUBJECT' => tr('Subject'),
		'TR_LAST_DATA' => tr('Last reply'),
		'TR_DELETE_ALL' => tr('Delete all'),
		'TR_OPEN_TICKETS' => tr('Open tickets'),
		'TR_CLOSED_TICKETS' => tr('Closed tickets'),
		'TR_DELETE' => tr('Delete'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}
unset_messages();
