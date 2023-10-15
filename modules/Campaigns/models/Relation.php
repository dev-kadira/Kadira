<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Campaigns_Relation_Model extends Vtiger_Relation_Model
{
	/**
	 * Function to get Email enabled modules list for detail view of record
	 * @return <array> List of modules
	 */
	public function getEmailEnabledModulesInfoForDetailView()
	{
		return [
			'Leads'    => ['fieldName' => 'leadid', 'tableName' => 'vtiger_campaignleadrel'],
			'Accounts' => ['fieldName' => 'accountid', 'tableName' => 'vtiger_campaignaccountrel'],
			'Contacts' => ['fieldName' => 'contactid', 'tableName' => 'vtiger_campaigncontrel']
		];
	}

	/**
	 * Function to get Campaigns Relation status values
	 * @return <array> List of status values
	 */
	public function getCampaignRelationStatusValues()
	{
		$statusValues = [];
		$db = PearDatabase::getInstance();

		$query = 'SELECT campaignrelstatusid, campaignrelstatus FROM vtiger_campaignrelstatus';
		$result = $db->pquery($query, []);
		$numOfRows = $db->num_rows($result);

		for ($i = 0; $i < $numOfRows; $i++) {
			$campStatusId = $db->query_result($result, $i, 'campaignrelstatusid');
			$campStatus = $db->query_result($result, $i, 'campaignrelstatus');
			$statusValues[$campStatusId] = $campStatus;
		}

		return $statusValues;
	}

	/**
	 * Function to update the status of relation
	 * @param <Number> Campaign record id
	 * @param <array> $statusDetails
	 */
	public function updateStatus($sourceRecordId, $statusDetails = [])
	{
		if ($sourceRecordId && $statusDetails) {
			$relatedModuleName = $this->getRelationModuleModel()->getName();
			$emailEnabledModulesInfo = $this->getEmailEnabledModulesInfoForDetailView();

			if (array_key_exists($relatedModuleName, $emailEnabledModulesInfo)) {
				$fieldName = $emailEnabledModulesInfo[$relatedModuleName]['fieldName'];
				$tableName = $emailEnabledModulesInfo[$relatedModuleName]['tableName'];
				$db = PearDatabase::getInstance();

				$paramArray = [];
				$updateQuery = "UPDATE {$tableName} SET campaignrelstatusid = CASE {$fieldName} ";

				foreach ($statusDetails as $relatedRecordId => $status) {
					$updateQuery .= ' WHEN ? THEN ? ';
					array_push($paramArray, $relatedRecordId);
					array_push($paramArray, $status);
				}
				
				$updateQuery .= 'ELSE campaignrelstatusid END WHERE campaignid = ?';
				array_push($paramArray, $sourceRecordId);
				$db->pquery($updateQuery, $paramArray);
			}
		}
	}
}
