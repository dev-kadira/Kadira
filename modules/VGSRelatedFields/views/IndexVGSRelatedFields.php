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

class VGSRelatedFields_IndexVGSRelatedFields_View extends Settings_Vtiger_Index_View {
    

    public function process(Vtiger_Request $request) {

        $viewer = $this->getViewer($request);
        
        $entityModules = Vtiger_Module_Model::getEntityModules();
        $restrictedModules = array('Emails', 'Calendar','Faq','Events','Webmails','ModComments', 'SMSNotifier', 'PBXManager'); //Modules where related fields do not work as expected

        $modules = array();
        foreach ($entityModules as $entityModule) {
            if(!in_array($entityModule, $restrictedModules)){
                array_push($modules, $entityModule->name);
            }
        }

        $viewer->assign('ENTITY_MODULES', $modules);

        $viewer->view('IndexVGSRelatedFields.tpl', $request->getModule());
    }

    public function getHeaderScripts(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderScripts($request);

        include('vtigerversion.php');
        $version = explode('.', $vtiger_current_version)[0];

        if ($version > 6) {
            $jsFileNames = array(
                "layouts.v7.modules.VGSRelatedFields.resources.VGSRelatedFields",
            );
        } else {
            $jsFileNames = array(
                "layouts.vlayout.modules.VGSRelatedFields.resources.VGSRelatedFields",
            );
        } 

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}
