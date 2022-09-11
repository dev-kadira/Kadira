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

class VGSRelatedFields_getBlocks_Action extends Vtiger_Action_Controller {

    public function checkPermission(Vtiger_Request $request) {
        global $current_user;
        $moduleName = $request->getModule();

        if (!is_admin($current_user)) {
            throw new AppException('LBL_PERMISSION_DENIED');
        }
    }

    public function process(Vtiger_Request $request) {
        $db = PearDatabase::getInstance();
        $selectedModule = $request->get('module1');
        $sql = "SELECT blocklabel FROM vtiger_blocks
                INNER JOIN vtiger_tab ON vtiger_blocks.tabid=vtiger_tab.tabid
                WHERE vtiger_tab.tablabel=?";
        $result = $db->pquery($sql, array($selectedModule));
        if ($db->num_rows($result) > 0) {
            $htmlOptions = $this->buildOptionsArray($result,$selectedModule);
            $fieldsResponse['result'] = 'ok';
            $fieldsResponse['options'] = $htmlOptions;
        } else {
            $fieldsResponse['result'] = 'fail';
        }


        $response = new Vtiger_Response();
        $response->setResult($fieldsResponse);
        $response->emit();
    }

    function buildOptionsArray($result,$selectedModule) {
        $db = PearDatabase::getInstance();
        
        for ($i = 0; $i < $db->num_rows($result); $i++) {
            $optionArray[$db->query_result($result, $i, 'blocklabel')] = getTranslatedString($db->query_result($result, $i, 'blocklabel'),$selectedModule);
        }
        return $optionArray;
    }

}
