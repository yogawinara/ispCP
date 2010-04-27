<?php
require '../include/ispcp-lib.php';

check_login(__FILE__);

if (isset($_GET['id']) AND is_numeric($_GET['id'])) {
	list($dmn_id, $rest) = get_domain_default_props($sql, $_SESSION['user_id']);
	$query="
			SELECT
				`software_id`,
				`software_res_del`
			FROM
				`web_software_inst`
			WHERE
				`software_id` = ?
			AND
				`domain_id` = ?
		";
	$rs = exec_query($sql, $query, array($_GET['id'], $dmn_id));
	if ($rs->RecordCount() != 1) {
		set_page_message(tr('Wrong software id.'));
		header('Location: software.php');
	} else {
		if ($rs->fields['software_res_del'] === '1') {
			$delete="DELETE FROM `web_software_inst` WHERE `software_id` = ? AND `domain_id` = ?";
			$res = exec_query($sql, $delete, array($_GET['id'], $dmn_id));
			set_page_message(tr('Software deleted successful.'));
		}else{
			$delete="
					UPDATE
						`web_software_inst`
					SET
						`software_status` = ?
					WHERE
						`software_id` = ?
					AND
						`domain_id` = ?
				";
			$res = exec_query($sql, $delete, array('delete', $_GET['id'], $dmn_id));
			send_request();
			set_page_message(tr('Software will be deleted now.'));
		}
			header('Location: software.php');
	}
} else {
	set_page_message(tr('Wrong software id.'));
	header('Location: software.php');
}
?>