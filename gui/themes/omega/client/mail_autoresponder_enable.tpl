<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
	<title>{TR_CLIENT_ENABLE_AUTORESPOND_PAGE_TITLE}</title>
	<meta http-equiv='Content-Script-Type' content='text/javascript' />
	<meta http-equiv='Content-Style-Type' content='text/css' />
	<meta http-equiv='Content-Type' content='text/html; charset={THEME_CHARSET}' />
	<meta name='copyright' content='ispCP Omega' />
	<meta name='owner' content='ispCP Omega' />
	<meta name='publisher' content='ispCP Omega' />
	<meta name='robots' content='nofollow, noindex' />
	<meta name='title' content='{TR_CLIENT_ENABLE_AUTORESPOND_PAGE_TITLE}' />
	<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
	<link href="{THEME_COLOR_PATH}/css/jquery.ui.tooltip.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="{THEME_SCRIPT_PATH}/jquery.js"></script>
	<script type="text/javascript" src="{THEME_SCRIPT_PATH}/jquery.ispcpTooltips.js"></script>
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
			<h1 class="email">{TR_MENU_EMAIL_ACCOUNTS}</h1>
		</div>
		<ul class="location-menu">
			<!-- BDP: logged_from -->
			<li><a href="change_user_interface.php?action=go_back" class="backadmin">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a href="../index.php?logout" class="logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="mail_accounts.php">{TR_MENU_OVERVIEW}</a></li>
			<li><a>{TR_ENABLE_MAIL_AUTORESPONDER}</a></li>
		</ul>
	</div>
	<div class="left_menu">{MENU}</div>
	<div class="main">
		<!-- BDP: page_message -->
		<div class="{MSG_TYPE}">{MESSAGE}</div>
		<!-- EDP: page_message -->
		<h2 class="support"><span>{TR_ENABLE_MAIL_AUTORESPONDER}</span></h2>
		<form action="" method="post" id="client_mail_autoresponder_enable">
			<fieldset>
				<legend>{TR_ARSP_MESSAGE}</legend>
				<textarea name="arsp_message" cols="50" rows="15"></textarea>
			</fieldset>
			<div class="buttons">
				<input type="hidden" name="id" value="{ID}" />				
				<input type="hidden" name="uaction" value="enable_arsp" />
				<input type="submit" name="Submit" value="{TR_ENABLE}" />
			</div>
		</form>
	</div>
<!-- INCLUDE "footer.tpl" -->