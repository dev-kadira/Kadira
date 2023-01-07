<?php
/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

// Note is used to store customer information.
class Documents extends CRMEntity
{
	public $log;
	public $db;
	public $table_name = 'vtiger_notes';
	public $table_index = 'notesid';
	public $default_note_name_dom = ['Meeting vtiger_notes', 'Reminder'];

	public $tab_name = ['vtiger_crmentity', 'vtiger_notes', 'vtiger_notescf'];
	public $tab_name_index = ['vtiger_crmentity'=>'crmid', 'vtiger_notes'=>'notesid', 'vtiger_senotesrel'=>'notesid', 'vtiger_notescf'=>'notesid'];

	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = ['vtiger_notescf', 'notesid'];

	public $column_fields = [];

	public $sortby_fields = ['title', 'modifiedtime', 'filename', 'createdtime', 'lastname', 'filedownloadcount', 'smownerid'];

	// This is used to retrieve related vtiger_fields from form posts.
	public $additional_column_fields = ['', '', '', ''];

	// This is the list of vtiger_fields that are in the lists.
	public $list_fields = [
		'Title'        => ['notes'=>'title'],
		'File Name'    => ['notes'=>'filename'],
		'Modified Time'=> ['crmentity'=>'modifiedtime'],
		'Assigned To'  => ['crmentity'=>'smownerid'],
		'Folder Name'  => ['attachmentsfolder'=>'folderid']
	];
	public $list_fields_name = [
		'Title'        => 'notes_title',
		'File Name'    => 'filename',
		'Modified Time'=> 'modifiedtime',
		'Assigned To'  => 'assigned_user_id',
		'Folder Name'  => 'folderid'
	];

	public $search_fields = [
		'Title'       => ['notes'=>'notes_title'],
		'File Name'   => ['notes'=>'filename'],
		'Assigned To' => ['crmentity'=>'smownerid'],
		'Folder Name' => ['attachmentsfolder'=>'foldername']
	];

	public $search_fields_name = [
		'Title'       => 'notes_title',
		'File Name'   => 'filename',
		'Assigned To' => 'assigned_user_id',
		'Folder Name' => 'folderid'
	];
	public $list_link_field = 'notes_title';
	public $old_filename = '';
	//var $groupTable = Array('vtiger_notegrouprelation','notesid');

	public $mandatory_fields = ['notes_title', 'createdtime', 'modifiedtime', 'filename', 'filesize', 'filetype', 'filedownloadcount', 'assigned_user_id', 'document_source', 'notecontent', 'filelocationtype', 'folderid'];

	//Added these variables which are used as default order by and sortorder in ListView
	public $default_order_by = 'title';
	public $default_sort_order = 'ASC';

	public function __construct()
	{
		$this->log = Logger::getLogger('notes');
		$this->log->debug('Entering Documents() method ...');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Documents');
		$this->log->debug('Exiting Documents method ...');
	}

	public function Documents()
	{
		self::__construct();
	}

	public function save_module($module)
	{
		if (isset($_REQUEST['operation'], $_REQUEST['elementType'])) {
			$this->checkWebServiceAttachment();
		}

		global $adb,$upload_badext;

		if (isset($this->parentid) && $this->parentid != '') {
			$relid = $this->parentid;
		}
		//inserting into vtiger_senotesrel
		if (isset($relid) && $relid != '') {
			$this->insertintonotesrel($relid, $this->id);
		}
		$filetype_fieldname = $this->getFileTypeFieldName();
		$filename_fieldname = $this->getFile_FieldName();

		if ($this->column_fields[$filetype_fieldname] == 'I') {
			if ($_FILES[$filename_fieldname]['name'] != '') {
				$errCode = $_FILES[$filename_fieldname]['error'];
				if ($errCode == 0) {
					foreach ($_FILES as $fileindex => $files) {
						if ($files['name'] != '' && $files['size'] > 0) {
							$filename = $_FILES[$filename_fieldname]['name'];
							$filename = from_html(preg_replace('/\s+/', '_', $filename));
							$filetype = $_FILES[$filename_fieldname]['type'];
							$filesize = $_FILES[$filename_fieldname]['size'];
							$filelocationtype = 'I';
							$binFile = sanitizeUploadFileName($filename, $upload_badext);
							$filename = ltrim(basename(' ' . $binFile)); //allowed filename like UTF-8 characters
						}
					}
				}
			} elseif ($this->mode == 'edit') {
				$fileres = $adb->pquery('select filetype, filesize,filename,filedownloadcount,filelocationtype from vtiger_notes where notesid=?', [$this->id]);
				if ($adb->num_rows($fileres) > 0) {
					$filename = $adb->query_result($fileres, 0, 'filename');
					$filetype = $adb->query_result($fileres, 0, 'filetype');
					$filesize = $adb->query_result($fileres, 0, 'filesize');
					$filedownloadcount = $adb->query_result($fileres, 0, 'filedownloadcount');
					$filelocationtype = $adb->query_result($fileres, 0, 'filelocationtype');
				}
			} elseif ($this->column_fields[$filename_fieldname]) {
				$filename = $this->column_fields[$filename_fieldname];
				$filesize = $this->column_fields['filesize'];
				$filetype = $this->column_fields['filetype'];
				$filelocationtype = $this->column_fields[$filetype_fieldname];
				$filedownloadcount = 0;
			} else {
				$filelocationtype = 'I';
				$filetype = '';
				$filesize = 0;
				$filedownloadcount = null;
			}
		} elseif ($this->column_fields[$filetype_fieldname] == 'E') {
			$filelocationtype = 'E';
			$filename = $this->column_fields[$filename_fieldname];
			// If filename does not has the protocol prefix, default it to http://
			// Protocol prefix could be like (https://, smb://, file://, \\, smb:\\,...)
			if (! empty($filename) && ! preg_match('/^\w{1,5}:\/\/|^\w{0,3}:?\\\\\\\\/', trim($filename), $match)) {
				$filename = "http://${filename}";
			}
			$filetype = '';
			$filesize = 0;
			$filedownloadcount = null;
		}

		$query = 'UPDATE vtiger_notes SET filename = ? ,filesize = ?, filetype = ? , filelocationtype = ? , filedownloadcount = ? WHERE notesid = ?';
		$adb->pquery($query, [decode_html($filename), $filesize, $filetype, $filelocationtype, $filedownloadcount, $this->id]);

		//Inserting into attachments table
		if ($filelocationtype == 'I') {
			$this->insertIntoAttachment($this->id, 'Documents');
		} else {
			$query = 'delete from vtiger_seattachmentsrel where crmid = ?';
			$qparams = [$this->id];
			$adb->pquery($query, $qparams);
		}
		//set the column_fields so that its available in the event handlers
		$this->column_fields['filename'] = $filename;
		$this->column_fields['filesize'] = $filesize;
		$this->column_fields['filetype'] = $filetype;
		$this->column_fields['filedownloadcount'] = $filedownloadcount;
	}

	/**
	 * checkWebServiceAttachment()
	 * @param mixed $id
	 * @param mixed $module
	 */
	public function checkWebServiceAttachment()
	{
		if (! isset($_FILES['filename'])) {
			if (isset($_REQUEST['operation'], $_REQUEST['elementType'])) {
				if ($_REQUEST['operation'] == 'create' && $_REQUEST['elementType'] == 'Documents') {
					global $root_directory;
					$documentData = @json_decode($_REQUEST['element']);

					if ($documentData != null) {
						if ($documentData->attachment->filename != '') {
							$filePath = $root_directory . 'cache/' . $documentData->attachment->filename;
							file_put_contents($filePath, base64_decode($documentData->attachment->content));

							$_FILES['filename'] = [
								'name'     => $documentData->attachment->filename,
								'type'     => $documentData->filelocationtype,
								'tmp_name' => $filePath,
								'error'    => 0,
								'size'     => filesize($filePath)
							];

							if ($documentData->relations != '') {
								if (is_array($documentData->relations)) {
									foreach ($documentData->relations as $relation) {
										list($withModuleId, $elementId) = vtws_getIdComponents($relation);
										$moduleName = Vtiger_Util_Helper::detectModulenameFromRecordId($withModuleId);
										$this->save_related_module($moduleName, $elementId, 'Documents', $_REQUEST['currentid']);
									}
								} else {
									list($withModuleId, $elementId) = vtws_getIdComponents($documentData->relations);
									$moduleName = Vtiger_Util_Helper::detectModulenameFromRecordId($withModuleId);
									$this->save_related_module($moduleName, $elementId, 'Documents', $_REQUEST['currentid']);
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 *      This function is used to add the vtiger_attachments. This will call the function uploadAndSaveFile which will upload the attachment into the server and save that attachment information in the database.
	 *      @param int $id  - entity id to which the vtiger_files to be uploaded
	 *      @param string $module  - the current module name
	 */
	public function insertIntoAttachment($id, $module)
	{
		global $log;
		$log->debug("Entering into insertIntoAttachment(${id},${module}) method.");

		$isFileSaved = false;

		foreach ($_FILES as $fileindex => $files) {
			if ($files['name'] != '' && $files['size'] > 0) {
				$files['original_name'] = vtlib_purify($_REQUEST[$fileindex . '_hidden']);
				$isFileSaved = $this->uploadAndSaveFile($id, $module, $files);
				if (! $isFileSaved) {
					throw new WebServiceException(
						WebServiceErrorCode::$VALIDATION_FAILED,
						vtranslate('LBL_IMAGESECURITY_ERROR', 'Webservices')
					);

					return false;
				}
			}
		}

		//Clear temp file
		if (isset($_FILES['filename']['tmp_name']) && $isFileSaved) {
			@unlink($_FILES['filename']['tmp_name']);
		}
		$log->debug("Exiting from insertIntoAttachment(${id},${module}) method.");

		return true;
	}

	/**
	 * save_related_module()
	 * @param String $module
	 * @param String $crmid
	 * @param String $with_module
	 * @param String $with_crmid
	 */
	public function save_related_module($module, $crmid, $with_module, $with_crmid)
	{
		parent::save_related_module($module, $crmid, $with_module, $with_crmid);
	}

	/**    Function used to get the sort order for Documents listview
	 *      @return string  $sorder - first check the $_REQUEST['sorder'] if request value is empty then check in the $_SESSION['NOTES_SORT_ORDER'] if this session value is empty then default sort order will be returned.
	 */
	public function getSortOrder()
	{
		global $log;
		$log->debug('Entering getSortOrder() method ...');
		if (isset($_REQUEST['sorder'])) {
			$sorder = $this->db->sql_escape_string($_REQUEST['sorder']);
		} else {
			$sorder = (($_SESSION['NOTES_SORT_ORDER'] != '') ? ($_SESSION['NOTES_SORT_ORDER']) : ($this->default_sort_order));
		}
		$log->debug('Exiting getSortOrder() method ...');

		return $sorder;
	}

	/**     Function used to get the order by value for Documents listview
	 *       @return string  $order_by  - first check the $_REQUEST['order_by'] if request value is empty then check in the $_SESSION['NOTES_ORDER_BY'] if this session value is empty then default order by will be returned.
	 */
	public function getOrderBy()
	{
		global $log;
		$log->debug('Entering getOrderBy() method ...');

		$use_default_order_by = '';
		if (PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}

		if (isset($_REQUEST['order_by'])) {
			$order_by = $this->db->sql_escape_string($_REQUEST['order_by']);
		} else {
			$order_by = (($_SESSION['NOTES_ORDER_BY'] != '') ? ($_SESSION['NOTES_ORDER_BY']) : ($use_default_order_by));
		}
		$log->debug('Exiting getOrderBy method ...');

		return $order_by;
	}

	/**
	 * Function used to get the sort order for Documents listview
	 * @param mixed $folderId
	 * @return String $sorder - sort order for a given folder.
	 */
	public function getSortOrderForFolder($folderId)
	{
		if (isset($_REQUEST['sorder']) && $_REQUEST['folderid'] == $folderId) {
			$sorder = $this->db->sql_escape_string($_REQUEST['sorder']);
		} elseif (is_array($_SESSION['NOTES_FOLDER_SORT_ORDER']) &&
					! empty($_SESSION['NOTES_FOLDER_SORT_ORDER'][$folderId])) {
			$sorder = $_SESSION['NOTES_FOLDER_SORT_ORDER'][$folderId];
		} else {
			$sorder = $this->default_sort_order;
		}

		return $sorder;
	}

	/**
	 * Function used to get the order by value for Documents listview
	 * @param mixed $folderId
	 * @return String order by column for a given folder.
	 */
	public function getOrderByForFolder($folderId)
	{
		$use_default_order_by = '';
		if (PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}
		if (isset($_REQUEST['order_by']) && $_REQUEST['folderid'] == $folderId) {
			$order_by = $this->db->sql_escape_string($_REQUEST['order_by']);
		} elseif (is_array($_SESSION['NOTES_FOLDER_ORDER_BY']) &&
				! empty($_SESSION['NOTES_FOLDER_ORDER_BY'][$folderId])) {
			$order_by = $_SESSION['NOTES_FOLDER_ORDER_BY'][$folderId];
		} else {
			$order_by = ($use_default_order_by);
		}

		return $order_by;
	}

	/** Function to export the notes in CSV Format
	 * @param reference variable - where condition is passed when the query is executed
	 * Returns Export Documents Query.
	 * @param mixed $where
	 */
	public function create_export_query($where)
	{
		global $log,$current_user;
		$log->debug('Entering create_export_query(' . $where . ') method ...');

		include 'include/utils/ExportUtils.php';
		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery('Documents', 'detail_view');
		$fields_list = getFieldsListFromQuery($sql);

		$userNameSql = getSqlForNameInDisplayFormat(['first_name'=> 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
		$query = "SELECT ${fields_list}, case when (vtiger_users.user_name not like '') then ${userNameSql} else vtiger_groups.groupname end as user_name" .
				' FROM vtiger_notes
				inner join vtiger_crmentity	on vtiger_crmentity.crmid=vtiger_notes.notesid
				LEFT JOIN vtiger_attachmentsfolder on vtiger_notes.folderid=vtiger_attachmentsfolder.folderid
				LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid=vtiger_users.id ' .
				' LEFT JOIN vtiger_groups ON vtiger_crmentity.smownerid=vtiger_groups.groupid ';

		$query .= getNonAdminAccessControlQuery('Documents', $current_user);
		$where_auto = ' vtiger_crmentity.deleted=0';
		if ($where != '') {
			$query .= "  WHERE (${where}) AND " . $where_auto;
		} else {
			$query .= '  WHERE ' . $where_auto;
		}

		$log->debug('Exiting create_export_query method ...');

		return $query;
	}

	public function del_create_def_folder($query)
	{
		global $adb;
		$dbQuery = $query . ' AND vtiger_attachmentsfolder.folderid = 0';
		$dbresult = $adb->pquery($dbQuery, []);
		$noofnotes = $adb->num_rows($dbresult);

		if ($noofnotes > 0) {
			$folderQuery = 'select folderid from vtiger_attachmentsfolder';
			$folderresult = $adb->pquery($folderQuery, []);
			$noofdeffolders = $adb->num_rows($folderresult);

			if ($noofdeffolders == 0) {
				$insertQuery = "insert into vtiger_attachmentsfolder values (0,'Default','Contains all attachments for which a folder is not set',1,0)";
				$adb->pquery($insertQuery, []);
			}
		}
	}

	public function insertintonotesrel($relid, $id)
	{
		global $adb;
		$dbQuery = 'insert into vtiger_senotesrel values ( ?, ? )';
		$adb->pquery($dbQuery, [$relid, $id]);
	}

	/*
	 * Function to get the primary query part of a report
	 * @param - $module Primary module name
	 * returns the query string formed on fetching the related data for report for primary module
	 */
	public function generateReportsQuery($module, $queryplanner)
	{
		$moduletable = $this->table_name;
		$moduleindex = $this->tab_name_index[$moduletable];
		$query = "from ${moduletable}
			inner join vtiger_crmentity on vtiger_crmentity.crmid=${moduletable}.${moduleindex}";
		if ($queryplanner->requireTable('vtiger_attachmentsfolder')) {
			$query .= " inner join vtiger_attachmentsfolder on vtiger_attachmentsfolder.folderid=${moduletable}.folderid";
		}
		if ($queryplanner->requireTable('vtiger_groups' . $module)) {
			$query .= ' left join vtiger_groups as vtiger_groups' . $module . ' on vtiger_groups' . $module . '.groupid = vtiger_crmentity.smownerid';
		}
		if ($queryplanner->requireTable('vtiger_createdby' . $module)) {
			$query .= " LEFT JOIN vtiger_users AS vtiger_createdby${module} ON vtiger_createdby${module}.id = vtiger_crmentity.smcreatorid";
		}
		if ($queryplanner->requireTable('vtiger_users' . $module)) {
			$query .= ' left join vtiger_users as vtiger_users' . $module . ' on vtiger_users' . $module . '.id = vtiger_crmentity.smownerid';
		}
		$query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
		$query .= ' left join vtiger_notescf on vtiger_notes.notesid = vtiger_notescf.notesid';
		$query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
		if ($queryplanner->requireTable('vtiger_lastModifiedBy' . $module)) {
			$query .= ' left join vtiger_users as vtiger_lastModifiedBy' . $module . ' on vtiger_lastModifiedBy' . $module . '.id = vtiger_crmentity.modifiedby ';
		}
		$relQuery = $this->getReportsUiType10Query($module, $queryplanner);
		$query .= ' ' . $relQuery;

		return $query;
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	public function generateReportsSecQuery($module, $secmodule, $queryPlanner)
	{
		$matrix = $queryPlanner->newDependencyMatrix();
		$matrix->setDependency('vtiger_crmentityDocuments', ['vtiger_groupsDocuments', 'vtiger_usersDocuments', 'vtiger_lastModifiedByDocuments']);

		if (! $queryPlanner->requireTable('vtiger_notes', $matrix)) {
			return '';
		}
		$matrix->setDependency('vtiger_notes', ['vtiger_crmentityDocuments', 'vtiger_attachmentsfolder']);
		// TODO Support query planner
		$query = $this->getRelationQuery($module, $secmodule, 'vtiger_notes', 'notesid', $queryPlanner);
		$query .= ' left join vtiger_notescf on vtiger_notes.notesid = vtiger_notescf.notesid';
		if ($queryPlanner->requireTable('vtiger_crmentityDocuments', $matrix)) {
			$query .= ' left join vtiger_crmentity as vtiger_crmentityDocuments on vtiger_crmentityDocuments.crmid=vtiger_notes.notesid and vtiger_crmentityDocuments.deleted=0';
		}
		if ($queryPlanner->requireTable('vtiger_attachmentsfolder')) {
			$query .= ' left join vtiger_attachmentsfolder on vtiger_attachmentsfolder.folderid=vtiger_notes.folderid';
		}
		if ($queryPlanner->requireTable('vtiger_groupsDocuments')) {
			$query .= ' left join vtiger_groups as vtiger_groupsDocuments on vtiger_groupsDocuments.groupid = vtiger_crmentityDocuments.smownerid';
		}
		if ($queryPlanner->requireTable('vtiger_usersDocuments')) {
			$query .= ' left join vtiger_users as vtiger_usersDocuments on vtiger_usersDocuments.id = vtiger_crmentityDocuments.smownerid';
		}
		if ($queryPlanner->requireTable('vtiger_lastModifiedByDocuments')) {
			$query .= ' left join vtiger_users as vtiger_lastModifiedByDocuments on vtiger_lastModifiedByDocuments.id = vtiger_crmentityDocuments.modifiedby ';
		}
		if ($queryPlanner->requireTable('vtiger_createdbyDocuments')) {
			$query .= ' left join vtiger_users as vtiger_createdbyDocuments on vtiger_createdbyDocuments.id = vtiger_crmentityDocuments.smcreatorid ';
		}

		//if secondary modules custom reference field is selected
		$query .= parent::getReportsUiType10Query($secmodule, $queryPlanner);

		return $query;
	}

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	public function setRelationTables($secmodule)
	{
		$rel_tables = [];

		return $rel_tables[$secmodule];
	}

	// Function to unlink all the dependent entities of the given Entity by Id
	public function unlinkDependencies($module, $id)
	{
		parent::unlinkDependencies($module, $id);
	}

	// Function to unlink an entity with given Id from another entity
	public function unlinkRelationship($id, $return_module, $return_id)
	{
		if (empty($return_module) || empty($return_id)) {
			return;
		}

		if ($return_module == 'Accounts') {
			$sql = 'DELETE FROM vtiger_senotesrel WHERE notesid = ? AND (crmid = ? OR crmid IN (SELECT contactid FROM vtiger_contactdetails WHERE accountid=?))';
			$this->db->pquery($sql, [$id, $return_id, $return_id]);
		} else {
			$sql = 'DELETE FROM vtiger_senotesrel WHERE notesid = ? AND crmid = ?';
			$this->db->pquery($sql, [$id, $return_id]);

			parent::unlinkRelationship($id, $return_module, $return_id);
		}
	}

	public function getFileTypeFieldName()
	{
		global $adb;

		$query = 'SELECT fieldname from vtiger_field where tabid = ? and uitype = ?';
		$tabid = getTabid('Documents');
		$filetype_uitype = 27;

		$res = $adb->pquery($query, [$tabid, $filetype_uitype]);
		$fieldname = null;

		if (isset($res)) {
			$rowCount = $adb->num_rows($res);
			if ($rowCount > 0) {
				$fieldname = $adb->query_result($res, 0, 'fieldname');
			}
		}

		return $fieldname;
	}

	public function getFile_FieldName()
	{
		global $adb;

		$query = 'SELECT fieldname from vtiger_field where tabid = ? and uitype = ?';
		$tabid = getTabid('Documents');
		$filename_uitype = 28;

		$res = $adb->pquery($query, [$tabid, $filename_uitype]);
		$fieldname = null;

		if (isset($res)) {
			$rowCount = $adb->num_rows($res);
			if ($rowCount > 0) {
				$fieldname = $adb->query_result($res, 0, 'fieldname');
			}
		}

		return $fieldname;
	}

	/**
	 * Check the existence of folder by folderid
	 * @param mixed $folderid
	 */
	public function isFolderPresent($folderid)
	{
		global $adb;
		$result = $adb->pquery('SELECT folderid FROM vtiger_attachmentsfolder WHERE folderid = ?', [$folderid]);
		if (! empty($result) && $adb->num_rows($result) > 0) {
			return true;
		}

		return false;
	}

	/**
	 * Customizing the restore procedure.
	 * @param mixed $modulename
	 * @param mixed $id
	 */
	public function restore($modulename, $id)
	{
		parent::restore($modulename, $id);

		global $adb;
		$fresult = $adb->pquery('SELECT folderid FROM vtiger_notes WHERE notesid = ?', [$id]);
		if (! empty($fresult) && $adb->num_rows($fresult)) {
			$folderid = $adb->query_result($fresult, 0, 'folderid');
			if (! $this->isFolderPresent($folderid)) {
				// Re-link to default folder
				$adb->pquery('UPDATE vtiger_notes set folderid = 1 WHERE notesid = ?', [$id]);
			}
		}
	}

	public function getQueryByModuleField($module, $fieldname, $srcrecord, $query)
	{
		if ($module == 'MailManager') {
			$tempQuery = split('WHERE', $query);
			if (! empty($tempQuery[1])) {
				$where = " vtiger_notes.filelocationtype = 'I' AND vtiger_notes.filename != '' AND vtiger_notes.filestatus != 0 AND ";
				$query = $tempQuery[0] . ' WHERE ' . $where . $tempQuery[1];
			} else {
				$query = $tempQuery[0] . ' WHERE ' . $tempQuery;
			}

			return $query;
		}
	}

	/**
	 * Function to check the module active and user action permissions before showing as link in other modules
	 * like in more actions of detail view.
	 * @param mixed $linkData
	 */
	public static function isLinkPermitted($linkData)
	{
		$moduleName = 'Documents';
		if (vtlib_isModuleActive($moduleName) && isPermitted($moduleName, 'CreateView') == 'yes') {
			return true;
		}

		return false;
	}

	/**
	 * Function to get query for related list in Documents module
	 * @param mixed $id
	 * @param mixed $cur_tab_id
	 * @param mixed $rel_tab_id
	 * @param mixed $actions
	 */
	public function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions = false)
	{
		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);
		vtlib_setup_modulevars('Documents', $this);
		vtlib_setup_modulevars($related_module, $other);

		$returnset = '&return_module=Documents&return_action=CallRelatedList&return_id=' . $id;

		$query = "SELECT vtiger_crmentity.*, {$other->table_name}.*";

		$more_relation = '';
		if (! empty($other->related_tables)) {
			foreach ($other->related_tables as $tname => $relmap) {
				$query .= ", ${tname}.*";
				if (empty($relmap[1])) {
					$relmap[1] = $other->table_name;
				}
				if (empty($relmap[2])) {
					$relmap[2] = $relmap[0];
				}
				$more_relation .= " LEFT JOIN ${tname} ON ${tname}.{$relmap[0]} = {$relmap[1]}.{$relmap[2]}";
			}
		}
		$query .= " FROM {$other->table_name}";
		$query .= " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = {$other->table_name}.{$other->table_index}";
		$query .= ' INNER JOIN vtiger_senotesrel ON vtiger_senotesrel.crmid = vtiger_crmentity.crmid ' . $more_relation;
		$query .= ' LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid';
		$query .= ' LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid';
		$query .= " WHERE vtiger_crmentity.deleted = 0 AND vtiger_senotesrel.notesid=${id}";

		//eliminate lead converted
		if ($related_module == 'Leads') {
			$query .= ' AND vtiger_leaddetails.converted=0 ';
		}

		return GetRelatedList('Documents', $related_module, $other, $query, '', $returnset);
	}
}
