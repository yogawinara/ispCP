<?php
/**
 * ispCP ω (OMEGA) complete domain backup/restore tool
 * ispCP backup controller
 *
 * @copyright 	2010 Thomas Wacker
 * @author 		Thomas Wacker <zuhause@thomaswacker.de>
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
 */

require_once dirname(__FILE__).'/iBackupPackage.php';
require_once dirname(__FILE__).'/BackupPackage.php';

class BackupPackage_ispCP extends BackupPackage implements iBackupPackage
{
	/**
	 * Instance of ispCP Database
	 */
	public $db = null;
	/**
	 * ispCP domain ID
	 */
	public $domain_id = 0;
	/**
	 * ispCP domain user ID
	 */
	public $domain_user_id = 0;
	/**
	 * ispCP database IDs (name => id)
	 */
	protected $db_ids = array();

	public function __construct($domain_name, $password)
	{
		parent::__construct($domain_name, $password);
		$this->db = Database::getInstance();
	}

	/**
	 * Get domain database id, validate if vhost path exists
	 * @return bool true = init ok, false = see error message
	 */
	protected function initDomain()
	{
		$result = false;

		if (!file_exists(ISPCP_VIRTUAL_PATH.'/'.$this->domain_name)) {
			$this->addErrorMessage('Domain not found in '.ISPCP_VIRTUAL_PATH.'/'.$this->domain_name);
		} else {
			$test = $this->getDomainID($this->domain_name);
			if ($test != -1) {
				$result = true;
			} else {
				$this->addErrorMessage('Domain not in database: '.$this->domain_name);
			}
		}

		return $result;
	}

	/**
	 * Get ispCP domain id of domain name
	 * @param string $domainname name of domain
	 * @return integer domain id, -1 if not present
	 */
	protected function getDomainID($domain_name)
	{
		$this->domain_id = -1;

		$query = "SELECT `domain_id`, `domain_uid` FROM `domain`".
				 " WHERE `domain_name` = :domain_name";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':domain_name'=>$domain_name));
		if ($rs->RecordCount() > 0) {
			$this->domain_id = $rs->fields['domain_id'];
			$this->domain_user_id = $rs->fields['domain_uid'];
		}

		return $this->domain_id;
	}

	public function getDomainConfig()
	{
		$result = array();

		$fields = "`domain`.`domain_name`".
				  ", `domain`.`domain_created`".
				  ", `domain`.`domain_expires`".
				  ", `domain`.`domain_mailacc_limit`".
				  ", `domain`.`domain_ftpacc_limit`".
				  ", `domain`.`domain_traffic_limit`".
				  ", `domain`.`domain_sqld_limit`".
				  ", `domain`.`domain_sqlu_limit`".
				  ", `domain`.`domain_alias_limit`".
				  ", `domain`.`domain_subd_limit`".
				  ", `domain`.`domain_disk_limit`".
				  ", `domain`.`domain_php`".
				  ", `domain`.`domain_cgi`".
				  ", `domain`.`domain_dns`".
				  ", `domain`.`allow_backup`".
				  ", `admin`.`customer_id`".
				  ", `admin`.`fname`".
				  ", `admin`.`lname`".
				  ", `admin`.`gender`".
				  ", `admin`.`firm`".
				  ", `admin`.`zip`".
				  ", `admin`.`city`".
				  ", `admin`.`state`".
				  ", `admin`.`country`".
				  ", `admin`.`phone`".
				  ", `admin`.`fax`".
				  ", `admin`.`street1`".
				  ", `admin`.`street2`";

		$query = "SELECT ".$fields." FROM `domain`, `admin`".
				 " WHERE `domain`.`domain_id` = :id AND `admin`.`admin_id` = `domain`.`domain_admin_id`";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':id'=>$this->domain_id));
		if ($rs && $rs->RecordCount() > 0) {
			$result = $rs->FetchRow();
		}

		return $result;
	}

	public function getEMailConfig()
	{
		$result = array();

		$fields = "`mail_users`.`mail_acc`".
				  ", `mail_users`.`mail_pass`".
				  ", `mail_users`.`mail_forward`".
				  ", `mail_users`.`mail_type`".
				  ", `mail_users`.`sub_id`".
				  ", `mail_users`.`mail_auto_respond`".
				  ", `mail_users`.`mail_auto_respond_text`".
				  ", `mail_users`.`quota`".
				  ", `mail_users`.`mail_addr`";

		$query = "SELECT ".$fields." FROM `mail_users`".
				 " WHERE `mail_users`.`domain_id` = :id";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':id'=>$this->domain_id));
		while (!$rs->EOF) {
			$row = $rs->FetchRow();
			$row['mail_pass'] = decrypt_db_password($row['mail_pass']);
			$result[] = $row;
			$rs->MoveNext();
		}

		return $result;
	}

	public function getFTPConfig()
	{
		$result = array();

		$fields = "`ftp_users`.`userid`".
				  ", `ftp_users`.`passwd`".
				  ", `ftp_users`.`homedir`";

		$query = "SELECT ".$fields." FROM `mail_users`".
				 " WHERE `ftp_users`.`uid` = :uid";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':uid'=>$this->domain_user_id));
		while ($rs && !$rs->EOF) {
			$row = $rs->FetchRow();
			$result[] = $row;
			$rs->MoveNext();
		}

		return $result;
	}

	public function getDomainAliasConfig()
	{
		$result = array();

		$fields = "`domain_aliasses`.`alias_name`".
				  ", `domain_aliasses`.`alias_mount`".
				  ", `domain_aliasses`.`alias_id`".
				  ", `domain_aliasses`.`url_forward`";

		$query = "SELECT ".$fields." FROM `domain_aliasses`".
				 " WHERE `domain_aliasses`.`domain_id` = :id";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':id'=>$this->domain_id));
		while (!$rs->EOF) {
			$row = $rs->FetchRow();
			$row['alias'] = $this->getSubdomainAliasConfig($row['alias_id']);
			$result[] = $row;
			$rs->MoveNext();
		}

		return $result;
	}

	protected function getSubdomainAliasConfig($alias_id)
	{
		$result = array();

		$fields = "`subdomain`.`subdomain_alias_name`".
				  ", `subdomain`.`subdomain_alias_mount`";

		$query = "SELECT ".$fields." FROM `subdomain_alias`".
				 " WHERE `subdomain_alias`.`alias_id` = :aid";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':aid'=>$this->$alias_id));
		while (!$rs->EOF) {
			$result[] = $rs->FetchRow();
			$rs->MoveNext();
		}

		return $result;
	}

	public function getSubDomainConfig()
	{
		$result = array();

		$fields = "`subdomain`.`subdomain_name`".
				  ", `subdomain`.`subdomain_mount`".
				  ", `subdomain`.`subdomain_id`";

		$query = "SELECT ".$fields." FROM `subdomain`".
				 " WHERE `subdomain`.`domain_id` = :id";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':id'=>$this->domain_id));
		while (!$rs->EOF) {
			$result[] = $rs->FetchRow();
			$rs->MoveNext();
		}

		return $result;
	}

	public function getWebUserConfig()
	{
		$result = array();

		$fields = "`htaccess_users`.`uname`".
				  ", `htaccess_users`.`upass`";

		$query = "SELECT ".$fields." FROM `htaccess_users`".
				 " WHERE `htaccess_users`.`dmn_id` = :id";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':id'=>$this->domain_id));
		while (!$rs->EOF) {
			$row = $rs->FetchRow();
			$row['upass'] = decrypt_db_password($row['upass']);
			$result[] = $row;
			$rs->MoveNext();
		}

		return $result;
	}

	public function getWebGroupConfig()
	{
		$result = array();

		$fields = "`htaccess_groups`.`ugroup`".
				  ", `htaccess_groups`.`members`";

		$query = "SELECT ".$fields." FROM `htaccess_users`".
				 " WHERE `htaccess_users`.`dmn_id` = :id";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':id'=>$this->domain_id));
		while ($rs && !$rs->EOF) {
			$result[] = $rs->FetchRow();
			$rs->MoveNext();
		}

		return $result;
	}

	public function getDNSConfig()
	{
		$result = array();

		$fields = "`domain_dns`.`alias_id`".
				  ", `domain_aliasses`.`domain_dns`".
				  ", `domain_aliasses`.`domain_class`".
				  ", `domain_aliasses`.`domain_type`".
				  ", `domain_aliasses`.`domain_text`";

		$query = "SELECT ".$fields." FROM `domain_aliasses`".
				 " WHERE `domain_dns`.`domain_id` = :id";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':id'=>$this->domain_id));
		while ($rs && !$rs->EOF) {
			$result[] = $rs->FetchRow();
			$rs->MoveNext();
		}

		return $result;
	}

	public function getDBConfig()
	{
		$result = array();

		$fields = "`sql_database`.`sqld_name`".
				  ", `sql_database`.`sqld_id`";

		$query = "SELECT ".$fields." FROM `sql_database`".
				 " WHERE `sql_database`.`domain_id` = :id";

		$query = $this->db->Prepare($query);
		$rs = $this->db->Execute($query, array(':id'=>$this->domain_id));
		while ($rs && !$rs->EOF) {
			$row = $rs->FetchRow();
			$this->db_ids[$row['sqld_name']] = $row['sqld_id'];
			$this->addDatabase('mysql', $row['sqld_name']);
			$result[] = $row;
			$rs->MoveNext();
		}

		return $result;
	}

	public function getDBUserConfig()
	{
		$result = array();

		$fields = "`sql_user`.`sql_uname`".
				  ", `sql_user`.`sqlu_pass`";

		$query = "SELECT ".$fields." FROM `sql_database`".
				 " WHERE `sql_database`.`sqld_id` = :sqld_id";
		$query = $this->db->Prepare($query);

		foreach ($this->db_ids as $dbname => $sqld_id) {
			$rs = $this->db->Execute($query, array(':sqld_id'=>$sqld_id));
			while ($rs && !$rs->EOF) {
				$row = $rs->FetchRow();
				$row['database'] = $dbname;
				$row['sqlu_pass'] = decrypt_db_password($row['sqlu_pass']);
				$result[] = $row;
				$rs->MoveNext();
			}
		}

		return $result;
	}
}
