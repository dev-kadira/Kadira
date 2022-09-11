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
class VGSRelatedFields_saveRelatedFields_Action extends Vtiger_Action_Controller {

    public function checkPermission(Vtiger_Request $request) {
        global $current_user;
        $moduleName = $request->getModule();

        if (!is_admin($current_user)) {
            throw new AppException('LBL_PERMISSION_DENIED');
        }
    }

    public function process(Vtiger_Request $request) {

        $module1 = $request->get('module1');
        $module2 = $request->get('module2');
        $addRelatedList = $request->get('addlist');
        $relListLabel = $request->get('related_list_label');

        $VGSRelatedFieldModel = Vtiger_Module_Model::getInstance('VGSRelatedFields');
        
        if(strlen($request->get('fiel_label'))> 50){
            $fieldsResponse['result'] = 'fail';
            $fieldsResponse['message'] = vtranslate('Field label should be less than 50 characters long','VGSRelatedFields');
        }else if ($addRelatedList == 'new' && !$VGSRelatedFieldModel->validateRelatedList($module1, $module2, $addRelatedList, $relListLabel)) {
            $fieldsResponse['result'] = 'fail';
            $fieldsResponse['message'] = vtranslate('duplicated','VGSRelatedFields');
        } else if ($VGSRelatedFieldModel->validateField($module1, $module2) || $VGSRelatedFieldModel->validateCustomBuildFields($module1, $module2)) {
            $fieldsResponse['result'] = 'fail';
            $fieldsResponse['message'] = vtranslate('field-already-there','VGSRelatedFields');
        } else {
            try {

                $field = $VGSRelatedFieldModel->addField($request);

                if ($addRelatedList=='new') {
                    $relatedList = $VGSRelatedFieldModel->addRelatedList($module1, $module2, $relListLabel);
                }

                $fieldsResponse['result'] = 'ok';
                $fieldsResponse['message'] = vtranslate('Horay! Your new field was successfully added','VGSRelatedFields');;

            } catch (Exception $exc) {
                $fieldsResponse['result'] = 'fail';
                $fieldsResponse['message'] = $exc->getTraceAsString();
            }
        }

        $response = new Vtiger_Response();
        $response->setResult($fieldsResponse);
        $response->emit();
    }

}
