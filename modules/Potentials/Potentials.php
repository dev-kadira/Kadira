<?php
/*
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 */
/*
 * $Header: /advent/projects/wesat/vtiger_crm/sugarcrm/modules/Potentials/Potentials.php,v 1.65 2005/04/28 08:08:27 rank Exp $
 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 */

class Potentials extends CRMEntity
{
	public $log;
	public $db;

	public $module_name='Potentials';
	public $table_name = 'vtiger_potential';
	public $table_index= 'potentialid';

	public $tab_name = ['vtiger_crmentity', 'vtiger_potential', 'vtiger_potentialscf'];
	public $tab_name_index = ['vtiger_crmentity'=>'crmid', 'vtiger_potential'=>'potentialid', 'vtiger_potentialscf'=>'potentialid'];
	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = ['vtiger_potentialscf', 'potentialid'];

	public $column_fields = [];

	public $sortby_fields = ['potentialname', 'amount', 'closingdate', 'smownerid', 'accountname'];

	// This is the list of vtiger_fields that are in the lists.
	public $list_fields = [
		'Potential'=>['potential'=>'potentialname'],
		'Organization Name'=>['potential'=>'related_to'],
		'Contact Name'=>['potential'=>'contact_id'],
		'Sales Stage'=>['potential'=>'sales_stage'],
		'Amount'=>['potential'=>'amount'],
		'Expected Close Date'=>['potential'=>'closingdate'],
		'Assigned To'=>['crmentity', 'smownerid']
	];

	public $list_fields_name = [
		'Potential'=>'potentialname',
		'Organization Name'=>'related_to',
		'Contact Name'=>'contact_id',
		'Sales Stage'=>'sales_stage',
		'Amount'=>'amount',
		'Expected Close Date'=>'closingdate',
		'Assigned To'=>'assigned_user_id'];

	public $list_link_field= 'potentialname';

	public $search_fields = [
		'Potential'=>['potential'=>'potentialname'],
		'Related To'=>['potential'=>'related_to'],
		'Expected Close Date'=>['potential'=>'closedate']
	];

	public $search_fields_name = [
		'Potential'=>'potentialname',
		'Related To'=>'related_to',
		'Expected Close Date'=>'closingdate'
	];

	public $required_fields =  [];

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	public $mandatory_fields = ['assigned_user_id', 'createdtime', 'modifiedtime', 'potentialname'];

	//Added these variables which are used as default order by and sortorder in ListView
	public $default_order_by = 'potentialname';
	public $default_sort_order = 'ASC';

	// For Alphabetical search
	public $def_basicsearch_col = 'potentialname';

	public $related_module_table_index = [
		'Contacts' => ['table_name'=>'vtiger_contactdetails', 'table_index'=>'contactid', 'rel_index'=>'contactid']
	];

	public $LBL_POTENTIAL_MAPPING = 'LBL_OPPORTUNITY_MAPPING';
	//var $groupTable = Array('vtiger_potentialgrouprelation','potentialid');
	public function __construct()
	{
		$this->log = Logger::getLogger('potential');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Potentials');
	}
	public function Potentials()
	{
		self::__construct();
	}

	public function save_module($module)
	{
	}

	/** Function to create list query
	 * @param reference variable - where condition is passed when the query is executed
	 * Returns Query.
	 */
	public function create_list_query($order_by, $where)
	{
		global $log,$current_user;
		require 'user_privileges/user_privileges_' . $current_user->id . '.php';
		require 'user_privileges/sharing_privileges_' . $current_user->id . '.php';
		$tab_id = getTabid('Potentials');
		$log->debug('Entering create_list_query(' . $order_by . ',' . $where . ') method ...');
		// Determine if the vtiger_account name is present in the where clause.
		$account_required = preg_match('/accounts\\.name/', $where);

		if ($account_required) {
			$query = 'SELECT vtiger_potential.potentialid,  vtiger_potential.potentialname, vtiger_potential.dateclosed FROM vtiger_potential, vtiger_account ';
			$where_auto = 'account.accountid = vtiger_potential.related_to AND vtiger_crmentity.deleted=0 ';
		} else {
			$query = 'SELECT vtiger_potential.potentialid, vtiger_potential.potentialname, vtiger_crmentity.smcreatorid, vtiger_potential.closingdate 
				FROM vtiger_potential inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_potential.potentialid 
				LEFT JOIN vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid 
				left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid ';
			$where_auto = ' AND vtiger_crmentity.deleted=0';
		}

		$query .= $this->getNonAdminAccessControlQuery('Potentials', $current_user);
		if ($where != '') {
			$query .= " where ${where} " . $where_auto;
		} else {
			$query .= ' where ' . $where_auto;
		}
		if ($order_by != '') {
			$query .= " ORDER BY ${order_by}";
		}

		$log->debug('Exiting create_list_query method ...');

		return $query;
	}

	/** Function to export the Opportunities records in CSV Format
	 * @param reference variable - order by is passed when the query is executed
	 * @param reference variable - where condition is passed when the query is executed
	 * Returns Export Potentials Query.
	 */
	public function create_export_query($where)
	{
		global $log;
		global $current_user;
		$log->debug('Entering create_export_query(' . $where . ') method ...');

		include 'include/utils/ExportUtils.php';

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery('Potentials', 'detail_view');
		$fields_list = getFieldsListFromQuery($sql);

		$userNameSql = getSqlForNameInDisplayFormat(['first_name'=> 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
		$query = "SELECT ${fields_list},case when (vtiger_users.user_name not like '') then ${userNameSql} else vtiger_groups.groupname end as user_name
				FROM vtiger_potential
				inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_potential.potentialid
				LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid=vtiger_users.id
				LEFT JOIN vtiger_account on vtiger_potential.related_to=vtiger_account.accountid
				LEFT JOIN vtiger_contactdetails on vtiger_potential.contact_id=vtiger_contactdetails.contactid
				LEFT JOIN vtiger_potentialscf on vtiger_potentialscf.potentialid=vtiger_potential.potentialid
                LEFT JOIN vtiger_groups
        	        ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				LEFT JOIN vtiger_campaign
					ON vtiger_campaign.campaignid = vtiger_potential.campaignid";

		$query .= $this->getNonAdminAccessControlQuery('Potentials', $current_user);
		$where_auto = '  vtiger_crmentity.deleted = 0 ';

		if ($where != '') {
			$query .= "  WHERE (${where}) AND " . $where_auto;
		} else {
			$query .= '  WHERE ' . $where_auto;
		}

		$log->debug('Exiting create_export_query method ...');

		return $query;
	}



	/** Returns a list of the associated contacts
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	 */
	public function get_contacts($id, $cur_tab_id, $rel_tab_id, $actions=false)
	{
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug('Entering get_contacts(' . $id . ') method ...');
		$this_module = $currentModule;

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once "modules/${related_module}/${related_module}.php";
		$other = new $related_module();
		vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if ($singlepane_view == 'true') {
			$returnset = '&return_module=' . $this_module . '&return_action=DetailView&return_id=' . $id;
		} else {
			$returnset = '&return_module=' . $this_module . '&return_action=CallRelatedList&return_id=' . $id;
		}

		$button = '';

		$accountid = $this->column_fields['related_to'];
		$search_string = "&fromPotential=true&acc_id=${accountid}";

		if ($actions) {
			if (is_string($actions)) {
				$actions = explode(',', strtoupper($actions));
			}
			if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
				$button .= "<input title='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module=${related_module}&return_module=${currentModule}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=${id}&parenttab=${parenttab}${search_string}','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "'>&nbsp;";
			}
			if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
				$button .= "<input title='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "' class='crmbutton small create'" .
					" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"${related_module}\"' type='submit' name='button'" .
					" value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "'>&nbsp;";
			}
		}

		$userNameSql = getSqlForNameInDisplayFormat(['first_name'=> 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
		$query = 'select case when (vtiger_users.user_name not like "") then ' . $userNameSql . ' else vtiger_groups.groupname end as user_name,
					vtiger_contactdetails.accountid,vtiger_potential.potentialid, vtiger_potential.potentialname, vtiger_contactdetails.contactid,
					vtiger_contactdetails.lastname, vtiger_contactdetails.firstname, vtiger_contactdetails.title, vtiger_contactdetails.department,
					vtiger_contactdetails.email, vtiger_contactdetails.phone, vtiger_crmentity.crmid, vtiger_crmentity.smownerid,
					vtiger_crmentity.modifiedtime , vtiger_account.accountname from vtiger_potential
					left join vtiger_contpotentialrel on vtiger_contpotentialrel.potentialid = vtiger_potential.potentialid
					inner join vtiger_contactdetails on ((vtiger_contactdetails.contactid = vtiger_contpotentialrel.contactid) or (vtiger_contactdetails.contactid = vtiger_potential.contact_id))
					INNER JOIN vtiger_contactaddress ON vtiger_contactdetails.contactid = vtiger_contactaddress.contactaddressid
					INNER JOIN vtiger_contactsubdetails ON vtiger_contactdetails.contactid = vtiger_contactsubdetails.contactsubscriptionid
					INNER JOIN vtiger_customerdetails ON vtiger_contactdetails.contactid = vtiger_customerdetails.customerid
					INNER JOIN vtiger_contactscf ON vtiger_contactdetails.contactid = vtiger_contactscf.contactid
					inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
					left join vtiger_account on vtiger_account.accountid = vtiger_contactdetails.accountid
					left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
					left join vtiger_users on vtiger_crmentity.smownerid=vtiger_users.id
					where vtiger_potential.potentialid = ' . $id . ' and vtiger_crmentity.deleted=0';

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if ($return_value == null) {
			$return_value = [];
		}
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug('Exiting get_contacts method ...');

		return $return_value;
	}

	/** Returns a list of the associated calls
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	 */
	public function get_activities($id, $cur_tab_id, $rel_tab_id, $actions=false)
	{
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug('Entering get_activities(' . $id . ') method ...');
		$this_module = $currentModule;

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once "modules/${related_module}/Activity.php";
		$other = new Activity();
		vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if ($singlepane_view == 'true') {
			$returnset = '&return_module=' . $this_module . '&return_action=DetailView&return_id=' . $id;
		} else {
			$returnset = '&return_module=' . $this_module . '&return_action=CallRelatedList&return_id=' . $id;
		}

		$button = '';

		$button .= '<input type="hidden" name="activity_mode">';

		if ($actions) {
			if (is_string($actions)) {
				$actions = explode(',', strtoupper($actions));
			}
			if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
				if (getFieldVisibilityPermission('Calendar', $current_user->id, 'parent_id', 'readwrite') == '0') {
					$button .= "<input title='" . getTranslatedString('LBL_NEW') . ' ' . getTranslatedString('LBL_TODO', $related_module) . "' class='crmbutton small create'" .
						" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"${related_module}\";this.form.return_module.value=\"${this_module}\";this.form.activity_mode.value=\"Task\";' type='submit' name='button'" .
						" value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString('LBL_TODO', $related_module) . "'>&nbsp;";
				}
				if (getFieldVisibilityPermission('Events', $current_user->id, 'parent_id', 'readwrite') == '0') {
					$button .= "<input title='" . getTranslatedString('LBL_NEW') . ' ' . getTranslatedString('LBL_TODO', $related_module) . "' class='crmbutton small create'" .
						" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"${related_module}\";this.form.return_module.value=\"${this_module}\";this.form.activity_mode.value=\"Events\";' type='submit' name='button'" .
						" value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString('LBL_EVENT', $related_module) . "'>";
				}
			}
		}

		$userNameSql = getSqlForNameInDisplayFormat(['first_name'=> 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
		$query = "SELECT vtiger_activity.activityid as 'tmp_activity_id',vtiger_activity.*,vtiger_seactivityrel.crmid as parent_id, vtiger_contactdetails.lastname,vtiger_contactdetails.firstname,
					vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime,
					case when (vtiger_users.user_name not like '') then ${userNameSql} else vtiger_groups.groupname end as user_name,
					vtiger_recurringevents.recurringtype from vtiger_activity
					inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid
					inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid
					left join vtiger_cntactivityrel on vtiger_cntactivityrel.activityid = vtiger_activity.activityid
					left join vtiger_contactdetails on vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid
					inner join vtiger_potential on vtiger_potential.potentialid=vtiger_seactivityrel.crmid
					left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
					left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
					left outer join vtiger_recurringevents on vtiger_recurringevents.activityid=vtiger_activity.activityid
					where vtiger_seactivityrel.crmid=" . $id . " and vtiger_crmentity.deleted=0
					and ((vtiger_activity.activitytype='Task' and vtiger_activity.status not in ('Completed','Deferred'))
					or (vtiger_activity.activitytype NOT in ('Emails','Task') and  vtiger_activity.eventstatus not in ('','Held'))) ";

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if ($return_value == null) {
			$return_value = [];
		}
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug('Exiting get_activities method ...');

		return $return_value;
	}

	/**
	 * Function to get Contact related Products
	 * @param  integer   $id  - contactid
	 * returns related Products record in array format
	 */
	public function get_products($id, $cur_tab_id, $rel_tab_id, $actions=false)
	{
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug('Entering get_products(' . $id . ') method ...');
		$this_module = $currentModule;

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once "modules/${related_module}/${related_module}.php";
		$other = new $related_module();
		vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if ($singlepane_view == 'true') {
			$returnset = '&return_module=' . $this_module . '&return_action=DetailView&return_id=' . $id;
		} else {
			$returnset = '&return_module=' . $this_module . '&return_action=CallRelatedList&return_id=' . $id;
		}

		$button = '';

		if ($actions) {
			if (is_string($actions)) {
				$actions = explode(',', strtoupper($actions));
			}
			if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
				$button .= "<input title='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module=${related_module}&return_module=${currentModule}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=${id}&parenttab=${parenttab}','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "'>&nbsp;";
			}
			if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
				$button .= "<input title='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "' class='crmbutton small create'" .
					" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"${related_module}\"' type='submit' name='button'" .
					" value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "'>&nbsp;";
			}
		}

		$query = "SELECT vtiger_products.productid, vtiger_products.productname, vtiger_products.productcode,
				vtiger_products.commissionrate, vtiger_products.qty_per_unit, vtiger_products.unit_price,
				vtiger_crmentity.crmid, vtiger_crmentity.smownerid
				FROM vtiger_products
				INNER JOIN vtiger_seproductsrel ON vtiger_products.productid = vtiger_seproductsrel.productid and vtiger_seproductsrel.setype = 'Potentials'
				INNER JOIN vtiger_productcf
				ON vtiger_products.productid = vtiger_productcf.productid
				INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_products.productid
				INNER JOIN vtiger_potential ON vtiger_potential.potentialid = vtiger_seproductsrel.crmid
				LEFT JOIN vtiger_users
					ON vtiger_users.id=vtiger_crmentity.smownerid
				LEFT JOIN vtiger_groups
					ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				WHERE vtiger_crmentity.deleted = 0 AND vtiger_potential.potentialid = ${id}";

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if ($return_value == null) {
			$return_value = [];
		}
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug('Exiting get_products method ...');

		return $return_value;
	}

	/**	Function used to get the Sales Stage history of the Potential
	 *	@param $id - potentialid
	 *	return $return_data - array with header and the entries in format Array('header'=>$header,'entries'=>$entries_list) where as $header and $entries_list are array which contains all the column values of an row
	 */
	public function get_stage_history($id)
	{
		global $log;
		$log->debug('Entering get_stage_history(' . $id . ') method ...');

		global $adb, $app_strings;

		$query = 'select vtiger_potstagehistory.*, vtiger_potential.potentialname 
			from vtiger_potstagehistory 
			inner join vtiger_potential on vtiger_potential.potentialid = vtiger_potstagehistory.potentialid 
			inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_potential.potentialid 
			where vtiger_crmentity.deleted = 0 and vtiger_potential.potentialid = ?';

		$result=$adb->pquery($query, [$id]);

		$header[] = $app_strings['LBL_AMOUNT'];
		$header[] = $app_strings['LBL_SALES_STAGE'];
		$header[] = $app_strings['LBL_PROBABILITY'];
		$header[] = $app_strings['LBL_CLOSE_DATE'];
		$header[] = $app_strings['LBL_LAST_MODIFIED'];

		//Getting the field permission for the current user. 1 - Not Accessible, 0 - Accessible
		//Sales Stage, Expected Close Dates are mandatory fields. So no need to do security check to these fields.
		global $current_user;

		//If field is accessible then getFieldVisibilityPermission function will return 0 else return 1
		$amount_access = (getFieldVisibilityPermission('Potentials', $current_user->id, 'amount') != '0') ? 1 : 0;
		$probability_access = (getFieldVisibilityPermission('Potentials', $current_user->id, 'probability') != '0') ? 1 : 0;
		$picklistarray = getAccessPickListValues('Potentials');

		$potential_stage_array = $picklistarray['sales_stage'];
		//- ==> picklist field is not permitted in profile
		//Not Accessible - picklist is permitted in profile but picklist value is not permitted
		$error_msg = 'Not Accessible';

		while ($row = $adb->fetch_array($result)) {
			$entries = [];

			$entries[] = ($amount_access != 1) ? $row['amount'] : 0;
			$entries[] = (in_array($row['stage'], $potential_stage_array)) ? $row['stage'] : $error_msg;
			$entries[] = ($probability_access != 1) ? $row['probability'] : 0;
			$entries[] = DateTimeField::convertToUserFormat($row['closedate']);
			$date = new DateTimeField($row['lastmodified']);
			$entries[] = $date->getDisplayDate();

			$entries_list[] = $entries;
		}

		$return_data = ['header'=>$header, 'entries'=>$entries_list];

		$log->debug('Exiting get_stage_history method ...');

		return $return_data;
	}

	/**
	 * Function to get Potential related Task & Event which have activity type Held, Completed or Deferred.
	 * @param  integer   $id
	 * returns related Task or Event record in array format
	 */
	public function get_history($id)
	{
		global $log;
		$log->debug('Entering get_history(' . $id . ') method ...');
		$userNameSql = getSqlForNameInDisplayFormat(['first_name'=> 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
		$query = "SELECT vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.status,
		vtiger_activity.eventstatus, vtiger_activity.activitytype,vtiger_activity.date_start,
		vtiger_activity.due_date, vtiger_activity.time_start,vtiger_activity.time_end,
		vtiger_crmentity.modifiedtime, vtiger_crmentity.createdtime,
		vtiger_crmentity.description,case when (vtiger_users.user_name not like '') then ${userNameSql} else vtiger_groups.groupname end as user_name
				from vtiger_activity
				inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid
				inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid
				left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
				left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
				where (vtiger_activity.activitytype != 'Emails')
				and (vtiger_activity.status = 'Completed' or vtiger_activity.status = 'Deferred' or (vtiger_activity.eventstatus = 'Held' and vtiger_activity.eventstatus != ''))
				and vtiger_seactivityrel.crmid=" . $id . '
                                and vtiger_crmentity.deleted = 0';
		//Don't add order by, because, for security, one more condition will be added with this query in include/RelatedListView.php

		$log->debug('Exiting get_history method ...');

		return getHistory('Potentials', $query, $id);
	}


	/**
	 * Function to get Potential related Quotes
	 * @param  integer   $id  - potentialid
	 * returns related Quotes record in array format
	 */
	public function get_quotes($id, $cur_tab_id, $rel_tab_id, $actions=false)
	{
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug('Entering get_quotes(' . $id . ') method ...');
		$this_module = $currentModule;

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once "modules/${related_module}/${related_module}.php";
		$other = new $related_module();
		vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if ($singlepane_view == 'true') {
			$returnset = '&return_module=' . $this_module . '&return_action=DetailView&return_id=' . $id;
		} else {
			$returnset = '&return_module=' . $this_module . '&return_action=CallRelatedList&return_id=' . $id;
		}

		$button = '';

		if ($actions && getFieldVisibilityPermission($related_module, $current_user->id, 'potential_id', 'readwrite') == '0') {
			if (is_string($actions)) {
				$actions = explode(',', strtoupper($actions));
			}
			if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
				$button .= "<input title='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) .
				"' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module=${related_module}
				&return_module=${currentModule}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=${id}
				&parenttab=${parenttab}','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') .
				' ' . getTranslatedString($related_module) . "'>&nbsp;";
			}
			if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
				$button .= "<input title='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) .
					"' class='crmbutton small create'" . " onclick='this.form.action.value=\"EditView\";this.form.module.value=\"
					${related_module}\"' type='submit' name='button'" . " value='" . getTranslatedString('LBL_ADD_NEW') . ' ' .
					getTranslatedString($singular_modname) . "'>&nbsp;";
			}
		}

		$userNameSql = getSqlForNameInDisplayFormat(['first_name'=> 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
		$query = "select case when (vtiger_users.user_name not like '') then ${userNameSql} else vtiger_groups.groupname end as user_name,
					vtiger_account.accountname, vtiger_crmentity.*, vtiger_quotes.*, vtiger_potential.potentialname from vtiger_quotes
					inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_quotes.quoteid
					left outer join vtiger_potential on vtiger_potential.potentialid=vtiger_quotes.potentialid
					left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
                    LEFT JOIN vtiger_quotescf ON vtiger_quotescf.quoteid = vtiger_quotes.quoteid
					LEFT JOIN vtiger_quotesbillads ON vtiger_quotesbillads.quotebilladdressid = vtiger_quotes.quoteid
					LEFT JOIN vtiger_quotesshipads ON vtiger_quotesshipads.quoteshipaddressid = vtiger_quotes.quoteid
					left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
					LEFT join vtiger_account on vtiger_account.accountid=vtiger_quotes.accountid
					where vtiger_crmentity.deleted=0 and vtiger_potential.potentialid=" . $id;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if ($return_value == null) {
			$return_value = [];
		}
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug('Exiting get_quotes method ...');

		return $return_value;
	}

	/**
	 * Function to get Potential related SalesOrder
	 * @param  integer   $id  - potentialid
	 * returns related SalesOrder record in array format
	 */
	public function get_salesorder($id, $cur_tab_id, $rel_tab_id, $actions=false)
	{
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug('Entering get_salesorder(' . $id . ') method ...');
		$this_module = $currentModule;

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once "modules/${related_module}/${related_module}.php";
		$other = new $related_module();
		vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if ($singlepane_view == 'true') {
			$returnset = '&return_module=' . $this_module . '&return_action=DetailView&return_id=' . $id;
		} else {
			$returnset = '&return_module=' . $this_module . '&return_action=CallRelatedList&return_id=' . $id;
		}

		$button = '';

		if ($actions && getFieldVisibilityPermission($related_module, $current_user->id, 'potential_id', 'readwrite') == '0') {
			if (is_string($actions)) {
				$actions = explode(',', strtoupper($actions));
			}
			if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
				$button .= "<input title='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) .
				"' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module=${related_module}
				&return_module=${currentModule}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=${id}
				&parenttab=${parenttab}','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') .
				' ' . getTranslatedString($related_module) . "'>&nbsp;";
			}
			if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
				$button .= "<input title='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) .
					"' class='crmbutton small create'" . " onclick='this.form.action.value=\"EditView\";this.form.module.value=\"
					${related_module}\"' type='submit' name='button'" . " value='" . getTranslatedString('LBL_ADD_NEW') .
					' ' . getTranslatedString($singular_modname) . "'>&nbsp;";
			}
		}

		$userNameSql = getSqlForNameInDisplayFormat(['first_name'=> 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
		$query = "select vtiger_crmentity.*, vtiger_salesorder.*, vtiger_quotes.subject as quotename
			, vtiger_account.accountname, vtiger_potential.potentialname,case when
			(vtiger_users.user_name not like '') then ${userNameSql} else vtiger_groups.groupname
			end as user_name from vtiger_salesorder
			inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_salesorder.salesorderid
			left outer join vtiger_quotes on vtiger_quotes.quoteid=vtiger_salesorder.quoteid
			left outer join vtiger_account on vtiger_account.accountid=vtiger_salesorder.accountid
			left outer join vtiger_potential on vtiger_potential.potentialid=vtiger_salesorder.potentialid
			left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
            LEFT JOIN vtiger_salesordercf ON vtiger_salesordercf.salesorderid = vtiger_salesorder.salesorderid
            LEFT JOIN vtiger_invoice_recurring_info ON vtiger_invoice_recurring_info.salesorderid = vtiger_salesorder.salesorderid
			LEFT JOIN vtiger_sobillads ON vtiger_sobillads.sobilladdressid = vtiger_salesorder.salesorderid
			LEFT JOIN vtiger_soshipads ON vtiger_soshipads.soshipaddressid = vtiger_salesorder.salesorderid
			left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
			 where vtiger_crmentity.deleted=0 and vtiger_potential.potentialid = " . $id;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if ($return_value == null) {
			$return_value = [];
		}
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug('Exiting get_salesorder method ...');

		return $return_value;
	}

	/**
	 * Move the related records of the specified list of id's to the given record.
	 * @param String This module name
	 * @param Array List of Entity Id's from which related records need to be transfered
	 * @param Integer Id of the the Record to which the related records are to be moved
	 */
	public function transferRelatedRecords($module, $transferEntityIds, $entityId)
	{
		global $adb,$log;
		$log->debug("Entering function transferRelatedRecords (${module}, ${transferEntityIds}, ${entityId})");

		$rel_table_arr = ['Activities'=>'vtiger_seactivityrel', 'Contacts'=>'vtiger_contpotentialrel', 'Products'=>'vtiger_seproductsrel',
			'Attachments'=>'vtiger_seattachmentsrel', 'Quotes'=>'vtiger_quotes', 'SalesOrder'=>'vtiger_salesorder',
			'Documents'=>'vtiger_senotesrel'];

		$tbl_field_arr = ['vtiger_seactivityrel'=>'activityid', 'vtiger_contpotentialrel'=>'contactid', 'vtiger_seproductsrel'=>'productid',
			'vtiger_seattachmentsrel'=>'attachmentsid', 'vtiger_quotes'=>'quoteid', 'vtiger_salesorder'=>'salesorderid',
			'vtiger_senotesrel'=>'notesid'];

		$entity_tbl_field_arr = ['vtiger_seactivityrel'=>'crmid', 'vtiger_contpotentialrel'=>'potentialid', 'vtiger_seproductsrel'=>'crmid',
			'vtiger_seattachmentsrel'=>'crmid', 'vtiger_quotes'=>'potentialid', 'vtiger_salesorder'=>'potentialid',
			'vtiger_senotesrel'=>'crmid'];

		foreach ($transferEntityIds as $transferId) {
			foreach ($rel_table_arr as $rel_module=>$rel_table) {
				$id_field = $tbl_field_arr[$rel_table];
				$entity_id_field = $entity_tbl_field_arr[$rel_table];
				// IN clause to avoid duplicate entries
				$sel_result =  $adb->pquery(
					"select ${id_field} from ${rel_table} where ${entity_id_field}=? " .
						" and ${id_field} not in (select ${id_field} from ${rel_table} where ${entity_id_field}=?)",
					[$transferId, $entityId]
				);
				$res_cnt = $adb->num_rows($sel_result);
				if ($res_cnt > 0) {
					for ($i=0;$i<$res_cnt;$i++) {
						$id_field_value = $adb->query_result($sel_result, $i, $id_field);
						$adb->pquery(
							"update ${rel_table} set ${entity_id_field}=? where ${entity_id_field}=? and ${id_field}=?",
							[$entityId, $transferId, $id_field_value]
						);
					}
				}
			}
		}
		parent::transferRelatedRecords($module, $transferEntityIds, $entityId);
		$log->debug('Exiting transferRelatedRecords...');
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
		$matrix->setDependency('vtiger_crmentityPotentials', ['vtiger_groupsPotentials', 'vtiger_usersPotentials', 'vtiger_lastModifiedByPotentials']);

		if (! $queryPlanner->requireTable('vtiger_potential', $matrix)) {
			return '';
		}
		$matrix->setDependency('vtiger_potential', ['vtiger_crmentityPotentials', 'vtiger_accountPotentials',
			'vtiger_contactdetailsPotentials', 'vtiger_campaignPotentials', 'vtiger_potentialscf']);

		$query = $this->getRelationQuery($module, $secmodule, 'vtiger_potential', 'potentialid', $queryPlanner);

		if ($queryPlanner->requireTable('vtiger_crmentityPotentials', $matrix)) {
			$query .= ' left join vtiger_crmentity as vtiger_crmentityPotentials on vtiger_crmentityPotentials.crmid=vtiger_potential.potentialid and vtiger_crmentityPotentials.deleted=0';
		}
		if ($queryPlanner->requireTable('vtiger_accountPotentials')) {
			$query .= ' left join vtiger_account as vtiger_accountPotentials on vtiger_potential.related_to = vtiger_accountPotentials.accountid';
		}
		if ($queryPlanner->requireTable('vtiger_contactdetailsPotentials')) {
			$query .= ' left join vtiger_contactdetails as vtiger_contactdetailsPotentials on vtiger_potential.contact_id = vtiger_contactdetailsPotentials.contactid';
		}
		if ($queryPlanner->requireTable('vtiger_potentialscf')) {
			$query .= ' left join vtiger_potentialscf on vtiger_potentialscf.potentialid = vtiger_potential.potentialid';
		}
		if ($queryPlanner->requireTable('vtiger_groupsPotentials')) {
			$query .= ' left join vtiger_groups vtiger_groupsPotentials on vtiger_groupsPotentials.groupid = vtiger_crmentityPotentials.smownerid';
		}
		if ($queryPlanner->requireTable('vtiger_usersPotentials')) {
			$query .= ' left join vtiger_users as vtiger_usersPotentials on vtiger_usersPotentials.id = vtiger_crmentityPotentials.smownerid';
		}
		if ($queryPlanner->requireTable('vtiger_campaignPotentials')) {
			$query .= ' left join vtiger_campaign as vtiger_campaignPotentials on vtiger_potential.campaignid = vtiger_campaignPotentials.campaignid';
		}
		if ($queryPlanner->requireTable('vtiger_lastModifiedByPotentials')) {
			$query .= ' left join vtiger_users as vtiger_lastModifiedByPotentials on vtiger_lastModifiedByPotentials.id = vtiger_crmentityPotentials.modifiedby ';
		}
		if ($queryPlanner->requireTable('vtiger_createdbyPotentials')) {
			$query .= ' left join vtiger_users as vtiger_createdbyPotentials on vtiger_createdbyPotentials.id = vtiger_crmentityPotentials.smcreatorid ';
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
		$rel_tables = [
			'Calendar' => ['vtiger_seactivityrel'=>['crmid', 'activityid'], 'vtiger_potential'=>'potentialid'],
			'Products' => ['vtiger_seproductsrel'=>['crmid', 'productid'], 'vtiger_potential'=>'potentialid'],
			'Quotes' => ['vtiger_quotes'=>['potentialid', 'quoteid'], 'vtiger_potential'=>'potentialid'],
			'SalesOrder' => ['vtiger_salesorder'=>['potentialid', 'salesorderid'], 'vtiger_potential'=>'potentialid'],
			'Documents' => ['vtiger_senotesrel'=>['crmid', 'notesid'], 'vtiger_potential'=>'potentialid'],
			'Accounts' => ['vtiger_potential'=>['potentialid', 'related_to']],
			'Contacts' => ['vtiger_potential'=>['potentialid', 'contact_id']],
			'Emails' => ['vtiger_seactivityrel'=>['crmid', 'activityid'], 'vtiger_potential'=>'potentialid'],
		];

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
			$this->trash($this->module_name, $id);
		} elseif ($return_module == 'Campaigns') {
			$sql = 'UPDATE vtiger_potential SET campaignid = ? WHERE potentialid = ?';
			$this->db->pquery($sql, [null, $id]);
		} elseif ($return_module == 'Products') {
			$sql = 'DELETE FROM vtiger_seproductsrel WHERE crmid=? AND productid=?';
			$this->db->pquery($sql, [$id, $return_id]);
		} elseif ($return_module == 'Contacts') {
			$sql = 'DELETE FROM vtiger_contpotentialrel WHERE potentialid=? AND contactid=?';
			$this->db->pquery($sql, [$id, $return_id]);

			//If contact related to potential through edit of record,that entry will be present in
			//vtiger_potential contact_id column,which should be set to zero
			$sql = 'UPDATE vtiger_potential SET contact_id = ? WHERE potentialid=? AND contact_id=?';
			$this->db->pquery($sql, [0, $id, $return_id]);

			// Potential directly linked with Contact (not through Account - vtiger_contpotentialrel)
			$directRelCheck = $this->db->pquery('SELECT related_to FROM vtiger_potential WHERE potentialid=? AND contact_id=?', [$id, $return_id]);
			if ($this->db->num_rows($directRelCheck)) {
				$this->trash($this->module_name, $id);
			}
		} elseif ($return_module == 'Documents') {
			$sql = 'DELETE FROM vtiger_senotesrel WHERE crmid=? AND notesid=?';
			$this->db->pquery($sql, [$id, $return_id]);
		} else {
			parent::unlinkRelationship($id, $return_module, $return_id);
		}
	}

	public function save_related_module($module, $crmid, $with_module, $with_crmids, $otherParams = [])
	{
		$adb = PearDatabase::getInstance();

		if (! is_array($with_crmids)) {
			$with_crmids = [$with_crmids];
		}
		foreach ($with_crmids as $with_crmid) {
			if ($with_module == 'Contacts') { //When we select contact from potential related list
				$sql = 'insert into vtiger_contpotentialrel values (?,?)';
				$adb->pquery($sql, [$with_crmid, $crmid]);
			} elseif ($with_module == 'Products') {//when we select product from potential related list
				$sql = 'INSERT INTO vtiger_seproductsrel VALUES(?,?,?,?)';
				$adb->pquery($sql, [$crmid, $with_crmid, 'Potentials', 1]);
			} else {
				parent::save_related_module($module, $crmid, $with_module, $with_crmid);
			}
		}
	}

	public function get_emails($id, $cur_tab_id, $rel_tab_id, $actions=false)
	{
		global $currentModule;
		$related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once "modules/${related_module}/${related_module}.php";
		$other = new $related_module();
		vtlib_setup_modulevars($related_module, $other);

		$returnset = '&return_module=' . $currentModule . '&return_action=CallRelatedList&return_id=' . $id;

		$button = '<input type="hidden" name="email_directing_module"><input type="hidden" name="record">';

		$userNameSql = getSqlForNameInDisplayFormat(['first_name'=>'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
		$query = "SELECT CASE WHEN (vtiger_users.user_name NOT LIKE '') THEN ${userNameSql} ELSE vtiger_groups.groupname END AS user_name,
                vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.activitytype, vtiger_crmentity.modifiedtime,
                vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_activity.date_start, vtiger_activity.time_start,
                vtiger_seactivityrel.crmid as parent_id FROM vtiger_activity, vtiger_seactivityrel, vtiger_potential, vtiger_users,
                vtiger_crmentity LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid WHERE 
                vtiger_seactivityrel.activityid = vtiger_activity.activityid AND 
                vtiger_potential.potentialid = vtiger_seactivityrel.crmid AND vtiger_users.id = vtiger_crmentity.smownerid
                AND vtiger_crmentity.crmid = vtiger_activity.activityid  AND vtiger_potential.potentialid = ${id} AND
                vtiger_activity.activitytype = 'Emails' AND vtiger_crmentity.deleted = 0";

		$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);

		if ($return_value == null) {
			$return_value = [];
		}
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}

	/**
	 * Invoked when special actions are to be performed on the module.
	 * @param String Module name
	 * @param String Event Type
	 */
	public function vtlib_handler($moduleName, $eventType)
	{
		if ($moduleName == 'Potentials') {
			$db = PearDatabase::getInstance();
			if ($eventType == 'module.disabled') {
				$db->pquery('UPDATE vtiger_settings_field SET active=1 WHERE name=?', [$this->LBL_POTENTIAL_MAPPING]);
			} elseif ($eventType == 'module.enabled') {
				$db->pquery('UPDATE vtiger_settings_field SET active=0 WHERE name=?', [$this->LBL_POTENTIAL_MAPPING]);
			}
		}
	}
}
