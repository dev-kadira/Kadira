<?php

/**
 * VGS Related Field Generator Module
 *
 *
 * @package        VGSRelatedFields Module
 * @author         Conrado Maggi - www.vgsglobal.com
 * @license        vTiger Public License.
 * @version        Release: 1.0
 */
class VGSRelatedFields_Module_Model extends Vtiger_Module_Model {


    /**
     * Function that validates is a related list already exists between the two modules
     * 
     * @param type $module1 Related Module
     * @param type $module2 Module
     * @param type $addRelatedList Whether the users request or not to create a new related list
     * @param type $allow_duplicate Duplicates allows
     * @return boolean Return false is a new related list should not be created
     */
    function validateRelatedList($module1, $module2, $addRelatedList, $relListLabel) {

        $db = PearDatabase::getInstance();

        include_once('vtlib/Vtiger/Module.php');
        $module1 = Vtiger_Module::getInstance($module1);
        $module2 = Vtiger_Module::getInstance($module2);

        $sql = "SELECT * FROM `vtiger_relatedlists` WHERE `tabid`= ? AND `related_tabid`= ?";
        $result = $db->pquery($sql, array($module2->id, $module1->id));
        
        if ($db->num_rows($result) > 0 && $addRelatedList == 'new') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Function that add the required field. Vtlib
     * 
     * @param Vtiger_Request $request
     * @return type
     */
    function addField(Vtiger_Request $request) {
        $module1 = $request->get('module1');
        $module2 = $request->get('module2');
        $block = $request->get('block');
        $fieldLabel = $request->get('fiel_label');

        $fieldName = strtolower(substr(str_replace(' ', '_', $fieldLabel), 0, 15));


        include_once('vtlib/Vtiger/Module.php');

        $module = Vtiger_Module::getInstance($module1);
        $module1Class = Vtiger_Module::getClassInstance($module1);
        $block1 = Vtiger_Block::getInstance($block, $module);

        $db = PearDatabase::getInstance();
        
        $result = $db->query('SELECT max(fieldid) as maxfield FROM vtiger_field');
        $max_fieldid = $db->query_result($result,0,'maxfield');
        $max_fieldid = $max_fieldid + 1;

        $columnName = 'cf_' . $max_fieldid;
        $fieldName = $columnName;

        $field1 = new Vtiger_Field();
        $field1->label = $fieldLabel;
        $field1->name = $fieldName;
        $field1->table = $module1Class->table_name;
        $field1->column = $fieldName;
        $field1->generatedtype = 2;
        $field1->columntype = 'INT(10)';
        $field1->uitype = 10;
        $field1->typeofdata = 'I~O';

        $block1->addField($field1);

        $field1->setRelatedModules(Array($module2));
        $block1->save($module);

        return $field1->id;
    }

    function addRelatedList($module1, $module2, $relListLabel) {

            include_once('vtlib/Vtiger/Module.php');
            $module = Vtiger_Module::getInstance($module2);
            $module->setRelatedList(Vtiger_Module::getInstance($module1), $relListLabel, Array('ADD'), 'get_dependents_list');
        
    }

    /**
     * Function that validates is a related field is already there - UIType 10 type fields
     * 
     * @param type $module1
     * @param type $module2
     * @return boolean returns true is a related field is already there
     */
    function validateField($module1, $module2) {
        $db = PearDatabase::getInstance();

        $sql = "SELECT vtiger_tab.tablabel as module1, vtiger_field.fieldlabel, vtiger_field.presence, vtiger_fieldmodulerel.relmodule as module2
                    FROM vtiger_field 
                    LEFT JOIN vtiger_fieldmodulerel ON vtiger_field.fieldid = vtiger_fieldmodulerel.fieldid
                    INNER JOIN vtiger_tab ON vtiger_field.tabid = vtiger_tab.tabid
                    WHERE uitype IN (10)
                    AND vtiger_tab.tablabel = ?
                    AND vtiger_fieldmodulerel.relmodule = ?";

        $result = $db->pquery($sql, array($module1, $module2));
        if ($db->num_rows($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Function that validates is a related field is already there - Custom UIType type fields
     * 
     * @param type $module1
     * @param type $module2
     * @return boolean returns true is a related field is already there
     */
    function validateCustomBuildFields($module1, $module2) {
        $db = PearDatabase::getInstance();

        $relatedUITypes = Array(
            'Accounts' => '73,51',
            'Contacts' => '57',
            'Users' => '101',
            'SalesOrder' => '80',
            'Potentials' => '76',
            'Products' => '59',
            'Vendors' => '75',
            'Campaigns' => '58'
        );

        if (array_key_exists($module2, $relatedUITypes)) {

            $customRelatedUITYpe = $relatedUITypes[$module2];
            $sql = "SELECT vtiger_tab.tablabel as module1, vtiger_field.fieldlabel, vtiger_field.presence
                    FROM vtiger_field 
                    INNER JOIN vtiger_tab ON vtiger_field.tabid = vtiger_tab.tabid";

            if ($module2 == 'Accounts') {
                $sql .= " WHERE uitype IN (73,51) AND vtiger_tab.tablabel = ?";
                $result = $db->pquery($sql, array($module1));
            } else {
                $sql .= " WHERE uitype = ? AND vtiger_tab.tablabel = ?";
                $result = $db->pquery($sql, array($customRelatedUITYpe, $module1));
            }


            if ($db->num_rows($result) > 0) {
                return true;
            } else {
                return false;
            }
        } else {

            return false;
        }
    }

    
    function getRLId($module2, $module1, $relListLabel) {
        $db = PearDatabase::getInstance();
        $module1 = Vtiger_Module::getInstance($module1);
        $module2 = Vtiger_Module::getInstance($module2);

        $sql = "SELECT relation_id FROM vtiger_relatedlists WHERE tabid=? AND related_tabid=? AND label=?";
        $result = $db->pquery($sql, array($module2->id, $module1->id, $relListLabel));

        if ($db->num_rows($result) > 0) {
            return $db->query_result($result, 0, 'relation_id');
        } else {
            return false;
        }
    }

}
