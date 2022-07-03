<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

//Maximum number of Mailboxes in mail converter
$max_mailboxes = 3;

/**
 * Configure runtime connectors to customization in core files.
 * Ex: Sessions are currently handled by PHP default session handler. 
 *     This can be customized using runtime connector hook and avoid core file modifications.
 *     array('session' => 'Vtiger_CustomSession_Handler')
 */
$runtime_connectors = array();

//Password Regex for validation
$validation_regex = array('password_regex' => '^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})');