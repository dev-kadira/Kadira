<?php
/*
 * Copyright Kadira  --  This file is a part of Kadira.
 * You can copy, adapt and distribute the work under the "Attribution-NonCommercial-ShareAlike"
 * Vizsage Public License (the "License"). You may not use this file except in compliance with the
 * License. Roughly speaking, non-commercial users may share and modify this code, but must give credit
 * and share improvements. However, for proper details please read the full License, available at
 * http://vizsage.com/license/Vizsage-License-BY-NC-SA.html and the handy reference for understanding
 * the full license at http://vizsage.com/license/Vizsage-Deed-BY-NC-SA.html. Unless required by
 * applicable law or agreed to in writing, any software distributed under the License is distributed
 * on an  "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the
 * License terms of Creative Commons Attribution-NonCommercial-ShareAlike 3.0 (the License).
 */

/**
 * vtws_delete_related
 *
 * @param  mixed $sourceRecordId
 * @param  mixed $relatedRecordId
 * @param  mixed $user
 * @return void
 */
function vtws_delete_related($sourceRecordId, $relatedRecordId, $user = false)
{
	global $log,$adb;

	list($moduleSourceId, $elementSourceId) = vtws_getIdComponents($sourceRecordId);
	$webserviceObject = VtigerWebserviceObject::fromId($adb, $moduleSourceId);

	$handlerPath = $webserviceObject->getHandlerPath();
	$handlerClass = $webserviceObject->getHandlerClass();

	require_once $handlerPath;
	$handler = new $handlerClass($webserviceObject, $user, $adb, $log);
	$meta = $handler->getMeta();

	$sourceModuleName = $meta->getObjectEntityName($sourceRecordId);

	$types = vtws_listtypes(null, $user);

	if (! in_array($sourceModuleName, $types['types'])) {
		throw new WebServiceException(
			WebServiceErrorCode::$ACCESSDENIED,
			'Permission to perform the operation is denied'
		);
	}

	if ($sourceModuleName !== $webserviceObject->getEntityName()) {
		throw new WebServiceException(
			WebServiceErrorCode::$INVALIDID,
			'Id specified is incorrect'
		);
	}

	if (! $meta->hasPermission(EntityMeta::$UPDATE, $sourceRecordId)) {
		throw new WebServiceException(
			WebServiceErrorCode::$ACCESSDENIED,
			'Permission to read given object is denied'
		);
	}

	if (! $meta->exists($elementSourceId)) {
		throw new WebServiceException(
			WebServiceErrorCode::$RECORDNOTFOUND,
			'Record you are trying to access is not found'
		);
	}

	if ($meta->hasWriteAccess() !== true) {
		throw new WebServiceException(
			WebServiceErrorCode::$ACCESSDENIED,
			'Permission to write is denied'
		);
	}

	list($moduleRelatedId, $elementRelatedId) = vtws_getIdComponents($relatedRecordId);
	$relatedRecordInstance = Vtiger_Record_Model::getInstanceById($elementRelatedId);
	$relatedModuleName = $relatedRecordInstance->getModuleName();

	$sourceModuleFocus = CRMEntity::getInstance($sourceModuleName);
	if ($sourceModuleFocus) {
		$sourceModuleFocus->delete_related_module(
			$sourceModuleName,
			$elementSourceId,
			$relatedModuleName,
			$elementRelatedId
		);
	}

	VTWS_PreserveGlobal::flush();

	return true;
}
