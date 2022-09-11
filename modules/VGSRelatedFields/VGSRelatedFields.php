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

include_once 'modules/Vtiger/CRMEntity.php';
include_once 'include/utils/utils.php';

class VGSRelatedFields extends Vtiger_CRMEntity {

    public function __construct() {
        
    }


    function vtlib_handler($moduleName, $eventType)
    {
        $adb = PearDatabase::getInstance();
        if ($eventType == 'module.postinstall') {
            $this->addSettingsEntry();
        } elseif ($eventType == 'module.disabled') {
            $this->removeSettingsLinks();
        } elseif ($eventType == 'module.enabled') {
            $this->addSettingsEntry();
        } elseif ($eventType == 'module.preuninstall') {
            $this->removeSettingsLinks();
        } elseif ($eventType == 'module.preupdate') {
        } elseif ($eventType == 'module.postupdate') {
        }
    }

    function addSettingsEntry()
    {
            $adb = PearDatabase::getInstance();
            $otherSettingsBlock = $adb->pquery('SELECT * FROM vtiger_settings_blocks WHERE label=?', array('LBL_OTHER_SETTINGS'));
            $otherSettingsBlockCount = $adb->num_rows($otherSettingsBlock);

        if ($otherSettingsBlockCount > 0) {
            $blockid = $adb->query_result($otherSettingsBlock, 0, 'blockid');
            $sequenceResult = $adb->pquery("SELECT max(sequence) as sequence FROM vtiger_settings_blocks WHERE blockid=?", array($blockid));
            if ($adb->num_rows($sequenceResult)) {
                $sequence = $adb->query_result($sequenceResult, 0, 'sequence');
            }
        }

            $result = $adb->pquery('SELECT * FROM vtiger_settings_field WHERE name=?', array('VGS Related Field Generator'));

        if ($result && $adb->num_rows($result) == 0) {
            $fieldid = $adb->getUniqueID('vtiger_settings_field');
            $adb->pquery("INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence, active) 
                        VALUES(?,?,?,?,?,?,?,?)", array($fieldid, $blockid, 'VGS Related Field Generator', '', 'VGSRelatedFields Configuration', 'index.php?module=VGSRelatedFields&view=IndexVGSRelatedFields&parent=Settings', $sequence++, 0));
        }
    }

    function removeSettingsLinks()
    {
        $adb = PearDatabase::getInstance();
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE name=?", array('VGS Related Field Generator'));
    }
}
