<?php
/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *********************************************************************************/

if (defined('VTIGER_UPGRADE')) {
	global $current_user, $adb;
	$db = PearDatabase::getInstance();

    //Profile privileges supported for Emails Module
	$actions = array('Save', 'EditView', 'Delete', 'DetailView');
    $emailsTabId = getTabid('Emails');

    $actionIds = array();
    foreach($actions as $actionName) {
        array_push($actionIds, getActionid($actionName));
    }

    $profileIdsResult = $db->pquery("SELECT DISTINCT profileid FROM vtiger_profile", array());
    $profileIdCount = $db->num_rows($profileIdsResult);
    for($i = 0; $i < $profileIdCount; $i++) {
        $profileId = $db->query_result($profileIdsResult, $i, 'profileid');
        foreach($actionIds as $actionId) {
            $db->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE permissions = ?",
                    array($profileId, $emailsTabId, $actionId, 0, 0));
        }
        echo "Emails permission for profile id :: $profileId inserted into vtiger_profile2standardpermissions table.<br>";
    }
    echo 'All profiles permissions updated to Email Module';
    
    $db->pquery("UPDATE vtiger_tab SET ownedby = ? WHERE tabid = ?", array(0, $emailsTabId));
    echo "ownedby value updated to 0 for Emails in vtiger_tab table.<br>";
    
    vimport('~modules/Users/CreateUserPrivilegeFile.php');
    $usersResult = $db->pquery("SELECT id FROM vtiger_users", array());
    $usersCount = $db->num_rows($usersResult);
    for($i = 0; $i < $usersCount; $i++) {
        $userId = $db->query_result($usersResult, $i, 'id');
        createUserPrivilegesfile($userId); 
        createUserSharingPrivilegesfile($userId);
        echo "User privilege and sharing privilege files recreated for user id :: $userId.<br>";
    }
    
    //Default Email reports access count column update from varchar to integer
    $db->pquery('UPDATE vtiger_selectcolumn set columnname = ? where columnname=?', array('vtiger_email_track:access_count:Emails_Access_Count:access_count:I', 'vtiger_email_track:access_count:Emails_Access_Count:access_count:V'));
    $db->pquery('UPDATE vtiger_relcriteria set columnname = ?, comparator = ? where columnname=?', array('vtiger_email_track:access_count:Emails_Access_Count:access_count:I', 'ny', 'vtiger_email_track:access_count:Emails_Access_Count:access_count:V'));
    echo 'Email access count field data type updated to Int';
    
    //References module added to Calendar parent_id field to link activites to parent record
    $calendarModuleModel = Vtiger_Module_Model::getInstance('Calendar');
    $fieldModel = $calendarModuleModel->getField('parent_id');
    $fieldId = $fieldModel->getId();
    $query = "SELECT * FROM vtiger_ws_fieldtype WHERE uitype=?";
    $result = $db->pquery($query,array($fieldModel->get('uitype')));
    $fieldTypeId = $db->query_result($result,0,'fieldtypeid');

    $qResult = $db->pquery('SELECT type FROM vtiger_ws_referencetype WHERE fieldtypeid = ?', array($fieldTypeId));
    $existingModules = array();
    for($i=0;$i<$db->num_rows($qResult);$i++) {
        $existingModules[] = $db->query_result($qResult, $i ,'type');
    }

    $newModules = array('Invoice','Quotes','PurchaseOrder','SalesOrder');
    foreach($newModules as $module) {
        if(!in_array($module, $existingModules)) {
            $db->pquery('INSERT INTO vtiger_ws_referencetype VALUES (?,?)', array($fieldTypeId, $module));
            echo "<br>".$module.' Reference module added';
        }
    }
    
    //#1184 => Register field delete event handler
    $em = new VTEventsManager($db);
    $em->registerHandler('vtiger.field.afterdelete', 'modules/Vtiger/handlers/FieldEventHandler.php', 'FieldEventHandler');

    $db->pquery('INSERT INTO vtiger_date_format (date_format, sortorderid, presence) VALUES (?, ?, ?)', ['dd.mm.yyyy', 3, 1]);
    $db->pquery('INSERT INTO vtiger_date_format (date_format, sortorderid, presence) VALUES (?, ?, ?)', ['dd/mm/yyyy', 4, 1]);
    
    //#1248 => updated vtiger_systems.server_password to TEXT
    $db->pquery('ALTER TABLE vtiger_systems MODIFY server_password text', array());
    
    $defaultEventTemplates = array('ToDo Reminder', 'Activity Reminder', 'Invite Users');
    $updateEventParams = array('Events', 'ToDo Reminder', 'Activity Reminder', 'Invite Users');
    $db->pquery('UPDATE vtiger_emailtemplates SET module=? WHERE templatename IN ('. generateQuestionMarks($defaultEventTemplates).')', $updateEventParams);
    
    $defaultContactTemplates = array('Support end notification before a month', 'Support end notification before a week', 'Send Portal login details to customer', 'Thanks Note', 'Customer Login Details', 'Target Crossed!', 'Follow Up', 'Address Change', 'Accept Order', 'Goods received acknowledgement', 'Acceptance Proposal', 'Pending Invoices', 'Announcement for Release');
    $updateContactParams = array('Contacts','Support end notification before a month', 'Support end notification before a week', 'Send Portal login details to customer', 'Thanks Note', 'Customer Login Details', 'Target Crossed!', 'Follow Up', 'Address Change', 'Accept Order', 'Goods received acknowledgement', 'Acceptance Proposal', 'Pending Invoices', 'Announcement for Release');
    $db->pquery('UPDATE vtiger_emailtemplates SET module=? WHERE templatename IN ('. generateQuestionMarks($defaultContactTemplates).')', $updateContactParams);
    
    echo 'Email templates default moduleName updated';
    
    //Migrate default module data from config editor to database
    $moduleModel = Settings_Vtiger_ConfigModule_Model::getInstance();
    $configFieldData = $moduleModel->getViewableData();
    $defaultModule = $configFieldData['default_module'];
    if(empty($defaultModule)){
        $defaultModule = 'Home';
    }

    $moduleInstance = Vtiger_Module_Model::getInstance('Users');
    $blockInstance = Vtiger_Block::getInstance('LBL_MORE_INFORMATION', $moduleInstance);
    if ($blockInstance) {
        $fieldInstance = Vtiger_Field::getInstance('defaultlandingpage', $moduleInstance);
        if (!$fieldInstance) {
            $fieldInstance = new Vtiger_Field();
            $fieldInstance->name		= 'defaultlandingpage';
            $fieldInstance->column		= 'defaultlandingpage';
            $fieldInstance->label		= 'Default Landing Page';
            $fieldInstance->table		= 'vtiger_users';
            $fieldInstance->columntype = 'VARCHAR(100)';
            $fieldInstance->defaultvalue = $defaultModule;
            $fieldInstance->typeofdata = 'V~O';
            $fieldInstance->uitype		= '32';
            $fieldInstance->presence	= '0';

            $blockInstance->addField($fieldInstance);
            $configModuleInstance = Settings_Vtiger_ConfigModule_Model::getInstance();
            $defaultModules = $configModuleInstance->getPicklistValues('default_module');
            $fieldInstance->setPicklistValues($defaultModules);
            echo "<br> Default landing page field added <br>";
        }
    }

    $allUsers = Users_Record_Model::getAll(true);
    $allUserIds = array_keys($allUsers);

    $db->pquery('UPDATE vtiger_users SET defaultlandingpage = ? WHERE id in ('. generateQuestionMarks($allUserIds) .')', array($defaultModule, $allUserIds));
    echo "Default landing page updated for all active users <br>";
    
    //Recalculating user-preivilege file, as defaultlandingpage and other preference changes should be updated
    foreach ($allUserIds as $userId) {
        createUserPrivilegesfile($userId);
        createUserSharingPrivilegesfile($userId);
    }
    echo "Re-calculated user privilege and sharing privileges files";
    
    //Adding beforeRelate and afterRelate event handlers
    $em = new VTEventsManager($db);
	$em->registerHandler('vtiger.entity.beforerelate', 'modules/Vtiger/handlers/RelateEntitesHandler.php', 'RelateEntitesHandler');
	echo '<br>Succecssfully added before relate handler<br>';
    
    $em->registerHandler('vtiger.entity.afterrelate', 'modules/Vtiger/handlers/RelateEntitesHandler.php', 'RelateEntitesHandler');
	echo '<br>Succecssfully added before relate handler<br>';
    
    //Updating customer-portal email template
    $result = $db->pquery("SELECT templateid FROM vtiger_emailtemplates WHERE subject = ?", array('Customer Portal Login Details'));
    if ($db->num_rows($result)) {
        $templateId = $db->query_result($result, 0, 'templateid');
    }
    if(!empty($templateId)){
        $portalLoginTemplateRecord = EmailTemplates_Record_Model::getInstanceById($templateId);
        $portalLoginTemplateContent = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
        <html>
        <head>
          <title></title>
        </head>
        <body class="scayt-enabled">
          <!-- <center> -->
            <table border="0" cellpadding="0" cellspacing="0" class="borderGrey" width="600px" style="margin-left:0px;">
              <tbody>
                <tr>
                  <td colspan="6"><!-- Begin Pre header --><!-- // End Pre header \ --></td>
                </tr>
                <tr style="height:50px;">
                  <td colspan="6" style="
                  font-family: Helvetica,Verdana,sans-serif">
                  <div style="margin-bottom:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);"><br />
                    Dear $contacts-firstname$ $contacts-lastname$,</div>

                    <div style="margin-top:20px;margin-bottom:20px; color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">As our customer, you can be assured of getting excellent support from our team. I would like to take this opportunity to introduce the portal we have setup for valuable customers like you. You can submit questions/issues via the portal, see past issues and responses. In addition, Portal provides you access to our knowledge base and documents we shared with you in the past.
                    </div>

                    <div style="margin-top:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">$URL$ to login to the portal, with the credentials below.</div>

                    <div style="margin-top:20px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">Your Username: $login_name$</div>

                    <div style="margin-bottom:20px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">Your Password: $password$</div>
                    <div class="gmail_extra" style="margin-top:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">Thank you,<br />
                      $contacts-smownerid$</div>

                    </td>
                  </tr>
                  <tr>
                    <td colspan="6" style="font-family: Helvetica,Verdana,sans-serif;font-size: 11px;color: #666666;">
                      <table border="0" cellpadding="4" cellspacing="0" width="100%">
                        <tbody>
                          <!--copy right data-->
                          <tr>
                            <td valign="top" style="
                            padding-left: 0px;
                            padding-right: 0px;
                            width:350px">
                                <div style="margin-top:20px;"><em>Powered By <a href="www.vtiger.com">Vtiger</a><div>
                            </td>
                          </tr>
                          <!--subscribers links-->
                        </tbody>
                      </table>
                    </td>
                  </tr>
                </tbody>
              </table>
            <!-- </center> -->
          </body>
          </html>';
        $portalLoginTemplateRecord->set('body', $portalLoginTemplateContent);
        $portalLoginTemplateRecord->save();
        $portalLoginTemplateId = $portalLoginTemplateRecord->getId();
        echo "Customer portal login template created.<br>";
    }
}