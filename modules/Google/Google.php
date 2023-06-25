<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
require_once 'vtlib/Vtiger/Module.php';
require_once 'include/events/include.inc';

class Google
{
	const module = 'Google';
	public $LBL_GOOGLE = 'LBL_GOOGLE';

	/**
	 * Invoked when special actions are to be performed on the module.
	 * @param String Module name
	 * @param String Event Type
	 */
	public function vtlib_handler($moduleName, $eventType)
	{
		$adb = PearDatabase::getInstance();
		$syncModules = ['Contacts' => 'Google Contacts', 'Calendar' => 'Google Calendar'];

		if ($eventType == 'module.postinstall') {
			$adb->pquery('UPDATE vtiger_tab SET customized=0 WHERE name=?', [$moduleName]);
			$this->addWidgetforSync($syncModules);
		} elseif ($eventType == 'module.disabled') {
			$this->removeWidgetforSync($syncModules);
			$adb->pquery('UPDATE vtiger_settings_field SET active=1 WHERE name=?', [$this->LBL_GOOGLE]);
		} elseif ($eventType == 'module.enabled') {
			$this->addWidgetforSync($syncModules);
			$adb->pquery('UPDATE vtiger_settings_field SET active=0 WHERE name=?', [$this->LBL_GOOGLE]);
		} elseif ($eventType == 'module.preuninstall') {
			$this->removeWidgetforSync($syncModules);
		} elseif ($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} elseif ($eventType == 'module.postupdate') {
		}
	}

	/**
	 * Add widget to other module
	 * @param String $widgetType
	 * @param String $widgetName
	 * @return
	 */
	public function addWidgetforSync($moduleNames, $widgetType = 'LISTVIEWSIDEBARWIDGET')
	{
		if (empty($moduleNames)) {
			return;
		}

		if (is_string($moduleNames)) {
			$moduleNames = [$moduleNames];
		}

		foreach ($moduleNames as $moduleName => $widgetName) {
			$module = Vtiger_Module::getInstance($moduleName);
			if ($module) {
				$module->addLink($widgetType, $widgetName, "module=Google&view=List&sourcemodule={$moduleName}", '', '', '');
			}
		}
	}

	/**
	 * Remove widget from other modules.
	 * @param String $widgetType
	 * @param String $widgetName
	 * @return
	 */
	public function removeWidgetforSync($moduleNames, $widgetType = 'LISTVIEWSIDEBARWIDGET')
	{
		if (empty($moduleNames)) {
			return;
		}

		if (is_string($moduleNames)) {
			$moduleNames = [$moduleNames];
		}

		foreach ($moduleNames as $moduleName => $widgetName) {
			$module = Vtiger_Module::getInstance($moduleName);
			if ($module) {
				$module->deleteLink($widgetType, $widgetName);
			}
		}
	}
}
