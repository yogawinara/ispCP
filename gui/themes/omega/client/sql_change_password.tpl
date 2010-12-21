<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
	<title>{TR_CLIENT_SQL_CHANGE_PASSWORD_PAGE_TITLE}</title>
	<meta http-equiv='Content-Script-Type' content='text/javascript' />
	<meta http-equiv='Content-Style-Type' content='text/css' />
	<meta http-equiv='Content-Type' content='text/html; charset={THEME_CHARSET}' />
	<meta name='copyright' content='ispCP Omega' />
	<meta name='owner' content='ispCP Omega' />
	<meta name='publisher' content='ispCP Omega' />
	<meta name='robots' content='nofollow, noindex' />
	<meta name='title' content='{TR_CLIENT_SQL_CHANGE_PASSWORD_PAGE_TITLE}' />
	<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
	<!--[if lt IE 7.]>
		<script defer type="text/javascript" src="{THEME_SCRIPT_PATH}/pngfix.js"></script>
	<![endif]-->
</head>
<body>
	<div class="header">
		{MAIN_MENU}
		<div class="logo">
			<img src="{THEME_COLOR_PATH}/images/ispcp_logo.png" alt="ispCP Omega logo" />
			<img src="{THEME_COLOR_PATH}/images/ispcp_webhosting.png" alt="ispCP Omega" />
		</div>
	</div>
	<div class="location">
		<div class="location-area">
			<h1 class="database">{TR_MENU_MANAGE_SQL}</h1>
		</div>
		<ul class="location-menu">
			<!-- BDP: logged_from -->
			<li><a href="change_user_interface.php?action=go_back" class="backadmin">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a href="../index.php?logout" class="logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="sql_manage.php">{TR_MENU_OVERVIEW}</a></li>
			<li><a>{TR_CHANGE_SQL_USER_PASSWORD}</a></li>
		</ul>
	</div>
	<div class="left_menu">{MENU}</div>
	<div class="main">
		<!-- BDP: page_message -->
		<div class="{MSG_TYPE}">{MESSAGE}</div>
		<!-- EDP: page_message -->
		<h2 class="password"><span>{TR_CHANGE_SQL_USER_PASSWORD}</span></h2>
		<form action="sql_change_password.php" method="post" id="client_sql_change_password">
			<table>
				<tr>
					<td>{TR_USER_NAME}</td>
					<td><input id="user_name" type="text" name="user_name" value="{USER_NAME}" readonly="readonly" /></td>
				</tr>
				<tr>
					<td>{TR_PASS}</td>
					<td><input id="pass" type="password" name="pass" value="" /></td>
				</tr>
				<tr>
					<td>{TR_PASS_REP}</td>
					<td><input id="pass_rep" type="password" name="pass_rep" value="" /></td>
				</tr>
			</table>
			<div class="buttons">
				<input type="hidden" name="id" value="{ID}" />
				<input type="hidden" name="uaction" value="change_pass" />
				<input type="submit" name="Submit" value="{TR_CHANGE}" />
			</div>
		</form>
	</div>
<!-- INCLUDE "footer.tpl" -->