<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class ModComments_Delete_Action extends Vtiger_Delete_Action
{
	public function checkPermission(Vtiger_Request $request)
	{
		return parent::checkPermission($request);
	}

	public function process(Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$currentUser = Users_Record_Model::getCurrentUserModel();

		if ($recordId) {
			$moduleName = $request->getModule();
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
			if ($moduleModel) {
				if ($moduleModel->isPermitted('Delete') || $currentUser->isAdminUser()) {
					$recordModel = Vtiger_Record_Model::getInstanceById($recordId);
					$recordModel->delete();

					if ($request->isAjax()) {
						$response = new Vtiger_Response();
						$response->setResult(['success' => true]);
						$response->emit();
					}
				} else {
					throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
				}
			}
		}
	}
}
