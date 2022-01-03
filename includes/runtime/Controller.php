<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

/**
 * Abstract Controller Class
 */
abstract class Vtiger_Controller
{

	// Control the exposure of methods to be invoked from client (kind-of RPC)
	protected $exposedMethods = [];

	public function __construct()
	{
	}

	public function loginRequired()
	{
		return true;
	}

	abstract public function getViewer(Vtiger_Request $request);
	abstract public function process(Vtiger_Request $request);

	public function validateRequest(Vtiger_Request $request)
	{
	}
	public function preProcess(Vtiger_Request $request)
	{
	}
	public function postProcess(Vtiger_Request $request)
	{
	}

	/**
	 * Function checks if the method is exposed for client usage
	 * @param string $name - method name
	 * @return boolean
	 */
	public function isMethodExposed($name)
	{
		if (in_array($name, $this->exposedMethods)) {
			return true;
		}

		return false;
	}

	/**
	 * Function invokes exposed methods for this class
	 * @param string $name - method name
	 * @param Vtiger_Request $request
	 * @throws Exception
	 */
	public function invokeExposedMethod()
	{
		$parameters = func_get_args();
		$name = array_shift($parameters);
		if (! empty($name) && $this->isMethodExposed($name)) {
			return call_user_func_array([$this, $name], $parameters);
		}

		throw new Exception(vtranslate('LBL_NOT_ACCESSIBLE'));
	}

	/**
	 * Function that will expose methods for external access
	 * @param <String> $name - method name
	 */
	protected function exposeMethod($name)
	{
		if (! in_array($name, $this->exposedMethods)) {
			$this->exposedMethods[] = $name;
		}
	}
}

/**
 * Abstract Action Controller Class
 */
abstract class Vtiger_Action_Controller extends Vtiger_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getViewer(Vtiger_Request $request)
	{
		throw new AppException('Action - implement getViewer - JSONViewer');
	}

	public function validateRequest(Vtiger_Request $request)
	{
		return $request->validateReadAccess();
	}

	public function preProcess(Vtiger_Request $request)
	{
		return true;
	}

	//TODO: need to revisit on this as we are not sure if this is helpful
	/*function preProcessParentTplName(Vtiger_Request $request) {
		return false;
	}*/

	public function postProcess(Vtiger_Request $request)
	{
		return true;
	}

	/**
	 * This will return all the permission checks that should be done
	 * @param Vtiger_Request $request
	 * @return <Array>
	 */
	public function requiresPermission(Vtiger_Request $request)
	{
		return [];
	}

	/**
	 * @param Vtiger_Request $request
	 *
	 * @return bool
	 * @throws AppException
	 */
	public function checkPermission(Vtiger_Request $request)
	{
		$permissions = $this->requiresPermission($request);
		foreach ($permissions as $permission) {
			if (array_key_exists('module_parameter', $permission)) {
				if ($request->has($permission['module_parameter']) && ! empty($request->get($permission['module_parameter']))) {
					$moduleParameter = $request->get($permission['module_parameter']);
				} elseif ($request->has('record') && ! empty($request->get('record'))) {
					$moduleParameter = getSalesEntityType($request->get('record'));
				}
			} else {
				$moduleParameter = 'module';
			}
			if (array_key_exists('record_parameter', $permission)) {
				$recordParameter = $request->get($permission['record_parameter']);
			} else {
				$recordParameter = '';
			}
			if (! Users_Privileges_Model::isPermitted($moduleParameter, $permission['action'], $recordParameter)) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}

		return true;
	}

	protected function preProcessTplName(Vtiger_Request $request)
	{
		return false;
	}
}

/**
 * Abstract View Controller Class
 */
abstract class Vtiger_View_Controller extends Vtiger_Action_Controller
{
	protected $viewer;

	public function __construct()
	{
		parent::__construct();
	}

	public function getViewer(Vtiger_Request $request)
	{
		if (! $this->viewer) {
			global $vtiger_current_version, $vtiger_display_version, $onlyV7Instance;
			$viewer = new Vtiger_Viewer();
			$viewer->assign('APPTITLE', getTranslatedString('APPTITLE'));
			$viewer->assign('VTIGER_VERSION', $vtiger_current_version);
			$viewer->assign('VTIGER_DISPLAY_VERSION', $vtiger_display_version);
			$viewer->assign('ONLY_V7_INSTANCE', $onlyV7Instance);
			$this->viewer = $viewer;
		}

		// Defaults to avoid warning
		// General
		$viewer->assign('V7_THEME_PATH', '');
		$viewer->assign('MODULE_NAME', '');
		$viewer->assign('MODULE', '');
		$viewer->assign('QUALIFIED_MODULE', '');
		$viewer->assign('VIEW', '');
		$viewer->assign('PARENT_MODULE', '');
		$viewer->assign('EXTENSION_MODULE', '');
		$viewer->assign('moduleName', '');

		$viewer->assign('NOTIFIER_URL', '');
		$viewer->assign('GLOBAL_SEARCH_VALUE', '');
		$_REQUEST['view'] = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';

		// Listview
		$viewer->assign('SEARCH_MODE_RESULTS', null);
		$viewer->assign('SHARED_MEMBER_COUNT', 0);
		$viewer->assign('CUSTOM_VIEWS_NAMES', []);
		$viewer->assign('ACTIVE', false);   // Tag
			$viewer->assign('BUTTON_NAME', ''); // footer Buttom (for custom action)
			$viewer->assign('BUTTON_ID', '');
		$viewer->assign('NO_EDIT', '');
		$viewer->assign('SOURCE_MODULE', '');
		$viewer->assign('OPERATOR', '');
		$viewer->assign('LISTVIEW_COUNT', 0);
		$viewer->assign('FOLDER_ID', 0);
		$viewer->assign('FOLDER_VALUE', '');
		$viewer->assign('VIEWTYPE', '');
		$viewer->assign('PRINT_TEMPLATE', '');
		$viewer->assign('CLASS_VIEW_ACTION', '');
		$viewer->assign('RELATED_MODULE_NAME', '');

		// Editview
		$viewer->assign('LEFTPANELHIDE', false);
		$viewer->assign('RECORD_ID', '');
		$viewer->assign('RETURN_VIEW', '');
		$viewer->assign('MASS_EDITION_MODE', false);
		$viewer->assign('OCCUPY_COMPLETE_WIDTH', true);
		$viewer->assign('VIEW_SOURCE', false);

		// DetailView
		$viewer->assign('MORE_TAB_ACTIVE', false);
		$viewer->assign('NO_DELETE', false);
		$viewer->assign('IS_EXTERNAL_LOCATION_TYPE', false);

		return $this->viewer;
	}

	public function getPageTitle(Vtiger_Request $request)
	{
		$recordName = null;

		$moduleName = $request->getModule();
		$recordId	= $request->get('record');

		if ($recordId && $moduleName) {
			$module = Vtiger_Module_Model::getInstance($moduleName);
			if ($module && $module->isEntityModule()) {
				$recordName = Vtiger_Util_Helper::getRecordName($recordId);
			}
		}

		if ($recordName) {
			return $recordName . ' - ' . vtranslate($moduleName, $moduleName);
		} else {
			$currentLang = Vtiger_Language_Handler::getLanguage();
			$customWebTitle = Vtiger_Language_Handler::getLanguageTranslatedString($currentLang, 'LBL_' .
				$moduleName . '_WEBTITLE', $request->getModule(false));

			if ($customWebTitle) {
				return $customWebTitle;
			}

			return vtranslate($moduleName, $moduleName);
		}
	}

	public function preProcess(Vtiger_Request $request, $display=true)
	{
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer = $this->getViewer($request);

		$viewer->assign('PAGETITLE', $this->getPageTitle($request));
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
		$viewer->assign('STYLES', $this->getHeaderCss($request));
		$viewer->assign('SKIN_PATH', Vtiger_Theme::getCurrentUserThemePath());
		$viewer->assign('LANGUAGE_STRINGS', $this->getJSLanguageStrings($request));
		$viewer->assign('LANGUAGE', $currentUser->get('language'));

		if ($request->getModule() != 'Install') {
			$userCurrencyInfo = getCurrencySymbolandCRate($currentUser->get('currency_id'));
			$viewer->assign('USER_CURRENCY_SYMBOL', $userCurrencyInfo['symbol']);
		}
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());

		if ($display) {
			$this->preProcessDisplay($request);
		}
	}


	public function postProcess(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('ACTIVITY_REMINDER', $currentUser->getCurrentUserActivityReminderInSeconds());
		$viewer->view('Footer.tpl', $moduleName);
	}

	/**
	 * Retrieves headers scripts that need to loaded in the page
	 * @param Vtiger_Request $request - request model
	 * @return <array> - array of Vtiger_JsScript_Model
	 */
	public function getHeaderScripts(Vtiger_Request $request)
	{
		$headerScriptInstances = [];
		$languageHandlerShortName = Vtiger_Language_Handler::getShortLanguageName();
		$fileName = "libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-${languageHandlerShortName}.js";
		if (! file_exists($fileName)) {
			$fileName = '~libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-en.js';
		} else {
			$fileName = "~libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-${languageHandlerShortName}.js";
		}
		$jsFileNames = [$fileName];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);

		return array_merge($jsScriptInstances, $headerScriptInstances);
	}

	public function checkAndConvertJsScripts($jsFileNames)
	{
		$fileExtension = 'js';

		$jsScriptInstances = [];
		if ($jsFileNames) {
			foreach ($jsFileNames as $jsFileName) {
				// TODO Handle absolute inclusions (~/...) like in checkAndConvertCssStyles
				$jsScript = new Vtiger_JsScript_Model();

				// external javascript source file handling
				if (strpos($jsFileName, 'http://') === 0 || strpos($jsFileName, 'https://') === 0) {
					$jsScriptInstances[$jsFileName] = $jsScript->set('src', $jsFileName);

					continue;
				}

				$completeFilePath = Vtiger_Loader::resolveNameToPath($jsFileName, $fileExtension);

				if (file_exists($completeFilePath)) {
					if (strpos($jsFileName, '~') === 0) {
						$filePath = ltrim(ltrim($jsFileName, '~'), '/');
						// if ~~ (reference is outside vtiger6 folder)
						if (substr_count($jsFileName, '~') == 2) {
							$filePath = '../' . $filePath;
						}
					} else {
						$filePath = str_replace('.', '/', $jsFileName) . '.' . $fileExtension;
					}

					$jsScriptInstances[$jsFileName] = $jsScript->set('src', $filePath);
				} else {
					$fallBackFilePath = Vtiger_Loader::resolveNameToPath(Vtiger_JavaScript::getBaseJavaScriptPath() . '/' . $jsFileName, 'js');
					if (file_exists($fallBackFilePath)) {
						$filePath = str_replace('.', '/', $jsFileName) . '.js';
						$jsScriptInstances[$jsFileName] = $jsScript->set('src', Vtiger_JavaScript::getFilePath($filePath));
					}
				}
			}
		}

		return $jsScriptInstances;
	}

	/**
	 * Function returns the css files
	 * @param <Array> $cssFileNames
	 * @param <String> $fileExtension
	 * @return <Array of Vtiger_CssScript_Model>
	 *
	 * First check if $cssFileName exists
	 * if not, check under layout folder $cssFileName eg:layouts/vlayout/$cssFileName
	 */
	public function checkAndConvertCssStyles($cssFileNames, $fileExtension='css')
	{
		$cssStyleInstances = [];
		foreach ($cssFileNames as $cssFileName) {
			$cssScriptModel = new Vtiger_CssScript_Model();

			if (strpos($cssFileName, 'http://') === 0 || strpos($cssFileName, 'https://') === 0) {
				$cssStyleInstances[] = $cssScriptModel->set('href', $cssFileName);

				continue;
			}
			$completeFilePath = Vtiger_Loader::resolveNameToPath($cssFileName, $fileExtension);
			$filePath = null;
			if (file_exists($completeFilePath)) {
				if (strpos($cssFileName, '~') === 0) {
					$filePath = ltrim(ltrim($cssFileName, '~'), '/');
					// if ~~ (reference is outside vtiger6 folder)
					if (substr_count($cssFileName, '~') == 2) {
						$filePath = '../' . $filePath;
					}
				} else {
					$filePath = str_replace('.', '/', $cssFileName) . '.' . $fileExtension;
					$filePath = Vtiger_Theme::getStylePath($filePath);
				}
				$cssStyleInstances[] = $cssScriptModel->set('href', $filePath);
			}
		}

		return $cssStyleInstances;
	}

	/**
	 * Retrieves css styles that need to loaded in the page
	 * @param Vtiger_Request $request - request model
	 * @return <array> - array of Vtiger_CssScript_Model
	 */
	public function getHeaderCss(Vtiger_Request $request)
	{
		return [];
	}

	/**
	 * Function returns the Client side language string
	 * @param Vtiger_Request $request
	 */
	public function getJSLanguageStrings(Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		if ($moduleName === 'Settings:Users') {
			$moduleName = 'Users';
		}

		return Vtiger_Language_Handler::export($moduleName, 'jsLanguageStrings');
	}

	protected function preProcessTplName(Vtiger_Request $request)
	{
		return 'Header.tpl';
	}

	protected function preProcessDisplay(Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$viewer->view($this->preProcessTplName($request), $request->getModule(false));
	}
}
