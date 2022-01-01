<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Migration_Module_Model extends Vtiger_Module_Model
{
	/**
	 * getDBVersion
	 *
	 * @return void
	 */
	public function getDBVersion()
	{
		$db = PearDatabase::getInstance();

		$result = $db->pquery('SELECT current_version FROM vtiger_version', []);
		if ($db->num_rows($result) > 0) {
			$currentVersion = $db->query_result($result, 0, 'current_version');
		}

		return $currentVersion;
	}

	/**
	 * Static Function to get the instance of Vtiger Module Model for the given id or name
	 * @param mixed id or name of the module
	 * @param null|mixed $value
	 */
	public static function getInstance($value = null)
	{
		return new self($value);
	}

	/**
	 * getAllowedMigrationVersions
	 *
	 * @return void
	 */
	public function getAllowedMigrationVersions()
	{
		return [
			['540'   => '5.4.0'],
			['600RC' => '6.0.0 RC'],
			['600' => '6.0.0'],
			['610' => '6.1.0'],
			['620' => '6.2.0'],
			['630' => '6.3.0'],
			['640' => '6.4.0'],
			['650' => '6.5.0'],
			['660' => '6.6.0'],
			['700' => '7.0.0'],
			['701' => '7.0.1'],
			['710' => '7.1.0'],
			['711' => '7.1.1'],
			['720' => '7.2.0'],
			['73' => '7.3'],
			['730' => '7.3.0'],
			['740' => '7.4.0'],
		];
	}

	/**
	 * getLatestSourceVersion
	 *
	 * @return void
	 */
	public function getLatestSourceVersion()
	{
		return vglobal('vtiger_current_version');
	}

	/**
	 * Function to update the latest vtiger version in db
	 * @return type
	 */
	public function updateVtigerVersion()
	{
		$db = PearDatabase::getInstance();
		$db->pquery('UPDATE vtiger_version SET current_version=?,old_version=?', [$this->getLatestSourceVersion(), $this->getDBVersion()]);

		return true;
	}

	/**
	 * Function to rename the migration file and folder
	 * Writing tab data in flat file
	 */
	public function postMigrateActivities()
	{
		//Writing tab data in flat file
		perform_post_migration_activities();

		//rename the migration file and folder
		$renamefile = uniqid(rand(), true);

		if (! @rename('migrate/', $renamefile . 'migrate/')) {
			if (@copy('migrate/', $renamefile . 'migrate/')) {
				@unlink('migrate/');
			}
		}
	}
}
