<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Accounts_TransferOwnership_Action extends Vtiger_Action_Controller
{
	public $transferRecordIds = [];

	/**
	 * requiresPermission
	 *
	 * @param  mixed $request
	 * @return void
	 */
	public function requiresPermission(\Vtiger_Request $request)
	{
		$permissions = parent::requiresPermission($request);
		$permissions[] = ['module_parameter' => 'module', 'action' => 'DetailView'];
		$permissions[] = ['module_parameter' => 'module', 'action' => 'EditView', 'record_parameter' => 'record'];

		return $permissions;
	}

	/**
	 * checkPermission
	 *
	 * @param  mixed $request
	 * @return Bool
	 */
	public function checkPermission(Vtiger_Request $request)
	{
		parent::checkPermission($request);
		$recordIds = $this->getRecordIds($request);

		foreach ($recordIds as $key => $recordId) {
			$moduleName = getSalesEntityType($recordId);
			$permissionStatus = Users_Privileges_Model::isPermitted($moduleName, 'EditView', $recordId);

			if ($permissionStatus) {
				$this->transferRecordIds[] = $recordId;
			}
			if (empty($this->transferRecordIds)) {
				throw new AppException(vtranslate('LBL_RECORD_PERMISSION_DENIED'));
			}
		}

		return true;
	}

	/**
	 * process
	 *
	 * @param  mixed $request
	 * @return void
	 */
	public function process(Vtiger_Request $request)
	{
		$module = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($module);
		$transferOwnerId = $request->get('transferOwnerId');

		if (! empty($this->transferRecordIds)) {
			$recordIds = $this->transferRecordIds;
		}

		$result = $moduleModel->transferRecordsOwnership($transferOwnerId, $recordIds);
		$response = new Vtiger_Response();

		if ($result === true) {
			$response->setResult(true);
		} else {
			$response->setError($result);
		}
		$response->emit();
	}

	/**
	 * getRecordIds
	 *
	 * @param  mixed $request
	 * @return Array
	 */
	public function getRecordIds(Vtiger_Request $request)
	{
		$module = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($module);
		$record = $request->get('record');

		if (empty($record)) {
			$recordIds = $this->getBaseModuleRecordIds($request);
		} else {
			$recordIds[] = $record;
		}

		$relatedModuleRecordIds = $moduleModel->getRelatedModuleRecordIds($request, $recordIds);

		foreach ($recordIds as $key => $recordId) {
			array_push($relatedModuleRecordIds, $recordId);
		}
		array_merge($relatedModuleRecordIds, $recordIds);

		return $relatedModuleRecordIds;
	}

	/**
	 * validateRequest
	 *
	 * @param  mixed $request
	 * @return void
	 */
	public function validateRequest(Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}

	/**
	 * getBaseModuleRecordIds
	 *
	 * @param  mixed $request
	 * @return Array
	 */
	protected function getBaseModuleRecordIds(Vtiger_Request $request)
	{
		$cvId = $request->get('viewname');
		$module = $request->getModule();
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		if (! empty($selectedIds) && $selectedIds != 'all') {
			if (! empty($selectedIds) && count($selectedIds) > 0) {
				return $selectedIds;
			}
		}

		$tagParams = $request->get('tag_params');
		$tag = $request->get('tag');
		$listViewSessionKey = $module . '_' . $cvId;

		if (! empty($tag)) {
			$listViewSessionKey .= '_' . $tag;
		}

		$orderParams = Vtiger_ListView_Model::getSortParamsSession($listViewSessionKey);
		if (! empty($tag) && empty($tagParams)) {
			$tagParams = $orderParams['tag_params'];
		}

		if (empty($tagParams)) {
			$tagParams = [];
		}

		$searchParams = $request->get('search_params');
		if (empty($searchParams) && ! is_array($searchParams)) {
			$searchParams = [];
		}
		$searchAndTagParams = array_merge($searchParams, $tagParams);

		if ($selectedIds == 'all') {
			$customViewModel = CustomView_Record_Model::getInstanceById($cvId);
			if ($customViewModel) {
				$operator = $request->get('operator');
				if (! empty($operator)) {
					$customViewModel->set('operator', $operator);
					$customViewModel->set('search_key', $request->get('search_key'));
					$customViewModel->set('search_value', $request->get('search_value'));
				}
				if (! empty($searchAndTagParams)) {
					$customViewModel->set('search_params', $searchAndTagParams);
				}

				return $customViewModel->getRecordIds($excludedIds, $module);
			}
		}

		return [];
	}
}
