<?php
/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ******************************************************************************* */
require_once 'modules/Webforms/model/WebformsFieldModel.php';

class Webforms_Model
{
	public $data;
	protected $fields = [];

	/**
	 * __construct
	 *
	 * @param  mixed $values
	 * @return void
	 */
	public function __construct($values = [])
	{
		$this->setData($values);
	}

	/**
	 * setData
	 *
	 * @param  mixed $data
	 * @return void
	 */
	public function setData($data)
	{
		$this->data = $data;
		if (isset($data['fields'])) {
			$this->setFields(vtlib_purify($data['fields']), vtlib_purify($data['required']), vtlib_purify($data['value']));
		}
		if (isset($data['id'])) {
			if (($data['enabled'] == 'on') || ($data['enabled'] == 1)) {
				$this->setEnabled(1);
			} else {
				$this->setEnabled(0);
			}
		} else {
			$this->setEnabled(1);
		}
	}

	/**
	 * hasId
	 *
	 * @return Boolean
	 */
	public function hasId()
	{
		return! empty($this->data['id']);
	}

	/**
	 * setId
	 *
	 * @param  mixed $id
	 * @return void
	 */
	public function setId($id)
	{
		$this->data['id'] = $id;
	}

	/**
	 * setName
	 *
	 * @param  mixed $name
	 * @return void
	 */
	public function setName($name)
	{
		$this->data['name'] = $name;
	}

	/**
	 * setTargetModule
	 *
	 * @param  mixed $module
	 * @return void
	 */
	public function setTargetModule($module)
	{
		$this->data['targetmodule'] = $module;
	}

	/**
	 * setEnabled
	 *
	 * @param  mixed $enabled
	 * @return void
	 */
	public function setEnabled($enabled)
	{
		$this->data['enabled'] = $enabled;
	}

	/**
	 * setDescription
	 *
	 * @param  mixed $description
	 * @return void
	 */
	public function setDescription($description)
	{
		$this->data['description'] = $description;
	}

	/**
	 * setReturnUrl
	 *
	 * @param  mixed $returnurl
	 * @return void
	 */
	public function setReturnUrl($returnurl)
	{
		$this->data['returnurl'] = $returnurl;
	}

	/**
	 * setOwnerId
	 *
	 * @param  mixed $ownerid
	 * @return void
	 */
	public function setOwnerId($ownerid)
	{
		$this->data['ownerid'];
	}

	/**
	 * setFields
	 *
	 * @param  mixed $fieldNames
	 * @param  mixed $required
	 * @param  mixed $value
	 * @return void
	 */
	public function setFields(array $fieldNames, $required, $value)
	{
		require_once 'include/fields/DateTimeField.php';

		foreach ($fieldNames as $ind => $fieldname) {
			$fieldInfo = Webforms::getFieldInfo($this->getTargetModule(), $fieldname);

			$fieldModel = new Webforms_Field_Model();
			$fieldModel->setFieldName($fieldname);
			$fieldModel->setNeutralizedField($fieldname, $fieldInfo['label']);
			$field = Webforms::getFieldInfo('Leads', $fieldname);

			if (($field['type']['name'] == 'date')) {
				$defaultvalue = DateTimeField::convertToDBFormat($value[$fieldname]);
			} elseif (($field['type']['name'] == 'boolean')) {
				if (in_array($fieldname, $required)) {
					if (empty($value[$fieldname])) {
						$defaultvalue='off';
					} else {
						$defaultvalue='on';
					}
				} else {
					$defaultvalue=$value[$fieldname];
				}
			} else {
				$defaultvalue = vtlib_purify($value[$fieldname]);
			}
			$fieldModel->setDefaultValue($defaultvalue);
			if ((! empty($required) && in_array($fieldname, $required))) {
				$fieldModel->setRequired(1);
			} else {
				$fieldModel->setRequired(0);
			}
			$this->addField($fieldModel);
		}
	}

	/**
	 * getId
	 *
	 * @return Integer
	 */
	public function getId()
	{
		return vtlib_purify($this->data['id']);
	}

	/**
	 * getName
	 *
	 * @return String
	 */
	public function getName()
	{
		return html_entity_decode(vtlib_purify($this->data['name']));
	}

	/**
	 * getTargetModule
	 *
	 * @return String
	 */
	public function getTargetModule()
	{
		return vtlib_purify($this->data['targetmodule']);
	}

	/**
	 * getPublicId
	 *
	 * @return Integer
	 */
	public function getPublicId()
	{
		return vtlib_purify($this->data['publicid']);
	}

	/**
	 * getEnabled
	 *
	 * @return Boolean
	 */
	public function getEnabled()
	{
		return vtlib_purify($this->data['enabled']);
	}

	/**
	 * getDescription
	 *
	 * @return String
	 */
	public function getDescription()
	{
		return vtlib_purify($this->data['description']);
	}

	/**
	 * getReturnUrl
	 *
	 * @return String
	 */
	public function getReturnUrl()
	{
		return vtlib_purify($this->data['returnurl']);
	}

	/**
	 * getOwnerId
	 *
	 * @return void
	 */
	public function getOwnerId()
	{
		return vtlib_purify($this->data['ownerid']);
	}

	/**
	 * getRoundrobin
	 *
	 * @return void
	 */
	public function getRoundrobin()
	{
		return vtlib_purify($this->data['roundrobin']);
	}

	/**
	 * getRoundrobinOwnerId
	 *
	 * @return void
	 */
	public function getRoundrobinOwnerId()
	{
		global $adb;

		$roundrobin_userid = vtlib_purify($this->data['roundrobin_userid']);
		$roundrobin_logic = vtlib_purify($this->data['roundrobin_logic']);
		$useridList = json_decode($roundrobin_userid, true);

		if ($roundrobin_logic >= count($useridList)) {
			$roundrobin_logic=0;
		}

		$roundrobinOwnerId = $useridList[$roundrobin_logic];
		$nextRoundrobinLogic = ($roundrobin_logic+1)%count($useridList);
		$adb->pquery('UPDATE vtiger_webforms SET roundrobin_logic = ? WHERE id = ?', [$nextRoundrobinLogic, $this->getId()]);

		return vtlib_purify($roundrobinOwnerId);
	}

	/**
	 * getFields
	 *
	 * @return void
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * generatePublicId
	 *
	 * @param  mixed $name
	 * @return void
	 */
	public function generatePublicId($name)
	{
		return md5(microtime(true) + $name);
	}

	/**
	 * retrieveFields
	 *
	 * @return void
	 */
	public function retrieveFields()
	{
		global $adb;
		$fieldsResult = $adb->pquery('SELECT * FROM vtiger_webforms_field WHERE webformid=?', [$this->getId()]);
		while ($fieldRow = $adb->fetch_array($fieldsResult)) {
			$this->addField(new Webforms_Field_Model($fieldRow));
		}

		return $this;
	}

	/**
	 * save
	 *
	 * @return void
	 */
	public function save()
	{
		global $adb;

		$isNew = ! $this->hasId();

		// Create?
		if ($isNew) {
			if (self::existWebformWithName($this->getName())) {
				throw new Exception('LBL_DUPLICATE_NAME');
			}
			$this->setPublicId($this->generatePublicId($this->getName()));
			$insertSQL = 'INSERT INTO vtiger_webforms(name, targetmodule, publicid, enabled, description,ownerid,returnurl) VALUES(?,?,?,?,?,?,?)';
			$result = $adb->pquery($insertSQL, [$this->getName(), $this->getTargetModule(), $this->getPublicid(), $this->getEnabled(), $this->getDescription(), $this->getOwnerId(), $this->getReturnUrl()]);
			$this->setId($adb->getLastInsertID());
		} else {
			// Udpate
			$updateSQL = 'UPDATE vtiger_webforms SET description=? ,returnurl=?,ownerid=?,enabled=? WHERE id=?';
			$adb->pquery($updateSQL, [$this->getDescription(), $this->getReturnUrl(), $this->getOwnerId(), $this->getEnabled(), $this->getId()]);
		}

		// Delete fields and re-add enabled once
		$adb->pquery('DELETE FROM vtiger_webforms_field WHERE webformid=?', [$this->getId()]);
		$fieldInsertSQL = 'INSERT INTO vtiger_webforms_field(webformid, fieldname, neutralizedfield, defaultvalue,required) VALUES(?,?,?,?,?)';

		foreach ($this->fields as $field) {
			$params = [];
			$params[] = $this->getId();
			$params[] = $field->getFieldName();
			$params[] = $field->getNeutralizedField();
			$params[] = $field->getDefaultValue();
			$params[] = $field->getRequired();
			$adb->pquery($fieldInsertSQL, $params);
		}

		return true;
	}

	/**
	 * delete
	 *
	 * @return void
	 */
	public function delete()
	{
		global $adb;

		$adb->pquery('DELETE from vtiger_webforms_field where webformid=?', [$this->getId()]);
		$adb->pquery('DELETE from vtiger_webforms where id=?', [$this->getId()]);

		return true;
	}

	/**
	 * retrieveWithPublicId
	 *
	 * @param  mixed $publicid
	 * @return void
	 */
	public static function retrieveWithPublicId($publicid)
	{
		global $adb;

		$model = false;

		// Retrieve model and populate information
		$result = $adb->pquery('SELECT * FROM vtiger_webforms WHERE publicid=? AND enabled=?', [$publicid, 1]);
		if ($adb->num_rows($result)) {
			$model = new Webforms_Model($adb->fetch_array($result));
			$model->retrieveFields();
		}

		return $model;
	}

	/**
	 * retrieveWithId
	 *
	 * @param  mixed $data
	 * @return void
	 */
	public static function retrieveWithId($data)
	{
		global $adb;

		$id = $data;
		$model = false;

		// Retrieve model and populate information
		$result = $adb->pquery('SELECT * FROM vtiger_webforms WHERE id=?', [$id]);
		if ($adb->num_rows($result)) {
			$model = new Webforms_Model($adb->fetch_array($result));
			$model->retrieveFields();
		}

		return $model;
	}

	/**
	 * listAll
	 *
	 * @return void
	 */
	public static function listAll()
	{
		global $adb;
		$webforms = [];

		$sql = 'SELECT * FROM vtiger_webforms';
		$result = $adb->pquery($sql, []);

		for ($index = 0, $len = $adb->num_rows($result); $index < $len; $index++) {
			$webform = new Webforms_Model($adb->fetch_array($result));
			$webforms[] = $webform;
		}


		return $webforms;
	}

	/**
	 * isWebformField
	 *
	 * @param  mixed $webformid
	 * @param  mixed $fieldname
	 * @return void
	 */
	public static function isWebformField($webformid, $fieldname)
	{
		global $adb;

		$checkSQL = 'SELECT 1 from vtiger_webforms_field where webformid=? AND fieldname=?';
		$result = $adb->pquery($checkSQL, [$webformid, $fieldname]);

		return (($adb->num_rows($result)) ? true : false);
	}

	/**
	 * isCustomField
	 *
	 * @param  mixed $fieldname
	 * @return void
	 */
	public static function isCustomField($fieldname)
	{
		if (substr($fieldname, 0, 3) === 'cf_') {
			return true;
		}

		return false;
	}

	/**
	 * isRequired
	 *
	 * @param  mixed $webformid
	 * @param  mixed $fieldname
	 * @return void
	 */
	public static function isRequired($webformid, $fieldname)
	{
		global $adb;

		$sql = 'SELECT required FROM vtiger_webforms_field where webformid=? AND fieldname=?';
		$result = $adb->pquery($sql, [$webformid, $fieldname]);
		$required = false;

		if ($adb->num_rows($result)) {
			$required = $adb->query_result($result, 0, 'required');
		}

		return $required;
	}

	/**
	 * retrieveDefaultValue
	 *
	 * @param  mixed $webformid
	 * @param  mixed $fieldname
	 * @return void
	 */
	public static function retrieveDefaultValue($webformid, $fieldname)
	{
		require_once 'include/fields/DateTimeField.php';

		global $adb;

		$sql = 'SELECT defaultvalue FROM vtiger_webforms_field WHERE webformid=? and fieldname=?';
		$result = $adb->pquery($sql, [$webformid, $fieldname]);
		$defaultvalue = false;

		if ($adb->num_rows($result)) {
			$defaultvalue = $adb->query_result($result, 0, 'defaultvalue');
			$field = Webforms::getFieldInfo('Leads', $fieldname);

			if (($field['type']['name'] == 'date') && ! empty($defaultvalue)) {
				$defaultvalue = DateTimeField::convertToUserFormat($defaultvalue);
			}
			$defaultvalue = explode(' |##| ', $defaultvalue);
		}

		return $defaultvalue;
	}

	/**
	 * existWebformWithName
	 *
	 * @param  mixed $name
	 * @return void
	 */
	public static function existWebformWithName($name)
	{
		global $adb;
		$checkSQL = 'SELECT 1 FROM vtiger_webforms WHERE name=?';
		$check = $adb->pquery($checkSQL, [$name]);
		if ($adb->num_rows($check) > 0) {
			return true;
		}

		return false;
	}

	/**
	 * isActive
	 *
	 * @param  mixed $field
	 * @param  mixed $mod
	 * @return void
	 */
	public static function isActive($field, $mod)
	{
		global $adb;

		$tabid = getTabid($mod);

		$query = 'SELECT 1 FROM vtiger_field WHERE fieldname = ?  AND tabid = ? AND presence IN (0,2)';
		$res = $adb->pquery($query, [$field, $tabid]);
		$rows = $adb->num_rows($res);

		if ($rows > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Function to create document records for each submitted files in webform and relate to created target module record.
	 * @global $current_user
	 * @param <array> $wsRecord - Webservice record array of created target module record returned by vtws_create().
	 * @throws Exception - Throws exception if size of all uploaded files exceeds 50MB.
	 */
	public function createDocuments($wsRecord)
	{
		global $current_user;
		$createdDocumentRecords = [];
		$sourceModule = $this->getTargetModule();

		if (Vtiger_Functions::isDocumentsRelated($sourceModule)) {
			$allFileSize = 0;
			foreach ($_FILES as $file) {
				$allFileSize += $file['size'];
			}

			$recordModel = Settings_Webforms_Record_Model::getInstanceById($this->getId(), 'Settings:Webforms');
			$allowedFilesSize = $recordModel->getModule()->allowedAllFilesSize();
			if ($allFileSize > $allowedFilesSize) {
				throw new Exception('Allowed files size exceeded. Allowed file size including all files is 50MB.');
			}

			$fileFields = $recordModel->getFileFields();
			$fileFieldsArray = [];
			$fileFieldsNameArray = [];

			foreach ($fileFields as $fileField) {
				$fileFieldsArray[$fileField['fieldname']] = $fileField['fieldlabel'];
				$fileFieldsNameArray[] = $fileField['fieldname'];
			}

			$uploadedFiles = $_FILES;

			foreach ($uploadedFiles as $fileFieldName => $uploadedFile) {
				if (in_array($fileFieldName, $fileFieldsNameArray) && $uploadedFile['error'] == 0 && $uploadedFile['name']) {
					$data['notes_title'] = $fileFieldsArray[$fileFieldName];
					$data['document_source'] = 'Vtiger';
					$data['filename'] = $uploadedFile['name'];
					$data['filelocationtype'] = 'I';
					$data['source'] = 'WEBFORM';
					$data['assigned_user_id'] = $wsRecord['assigned_user_id'];
					$data['filestatus'] = 1;
					unset($_FILES);
					$_FILES['filename'] = $uploadedFile;
					$record = vtws_create('Documents', $data, $current_user);
					array_push($createdDocumentRecords, $record['id']);
				}
			}

			if (! empty($createdDocumentRecords)) {
				vtws_add_related($wsRecord['id'], $createdDocumentRecords);
			}
		}
	}

	/**
	 * addField
	 *
	 * @param  mixed $field
	 * @return void
	 */
	protected function addField(Webforms_Field_Model $field)
	{
		$this->fields[] = $field;
	}

	/**
	 * setPublicId
	 *
	 * @param  mixed $publicid
	 * @return void
	 */
	protected function setPublicId($publicid)
	{
		$this->data['publicid'] = $publicid;
	}
}
