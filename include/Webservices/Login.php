<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

/**
 * vtws_login
 *
 * @param  mixed $username
 * @param  mixed $pwd
 * @return void
 */
function vtws_login($username, $pwd)
{
	$user = new Users();
	$userId = $user->retrieve_user_id($username);

	$token = vtws_getActiveToken($userId);
	if ($token == null) {
		throw new WebServiceException(
			WebServiceErrorCode::$INVALIDTOKEN,
			'Specified token is invalid or expired'
		);
	}

	$accessKey = vtws_getUserAccessKey($userId);
	if ($accessKey == null) {
		throw new WebServiceException(
			WebServiceErrorCode::$ACCESSKEYUNDEFINED,
			'Access key for the user is undefined'
		);
	}

	$accessCrypt = md5($token . $accessKey);
	if (strcmp($accessCrypt, $pwd) !== 0) {
		throw new WebServiceException(
			WebServiceErrorCode::$INVALIDUSERPWD,
			'Invalid username or password'
		);
	}
	$user = $user->retrieveCurrentUserInfoFromFile($userId);
	if ($user->status != 'Inactive') {
		return $user;
	}
	// Finer exception message could be handy to enumeration attacks - so normalize it.
	//throw new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED,'Given user is inactive');
	throw new WebServiceException(
		WebServiceErrorCode::$INVALIDUSERPWD,
		'Invalid username or password'
	);
}

/**
 * vtws_getActiveToken
 *
 * @param  mixed $userId
 * @return Object
 */
function vtws_getActiveToken($userId)
{
	global $adb;

	$sql = 'SELECT token FROM vtiger_ws_userauthtoken WHERE userid=? AND expiretime >= ?';
	$result = $adb->pquery($sql, [$userId, time()]);

	if ($result != null && isset($result)) {
		if ($adb->num_rows($result) > 0) {
			return $adb->query_result($result, 0, 'token');
		}
	}

	return null;
}

/**
 * vtws_getUserAccessKey
 *
 * @param  mixed $userId
 * @return Object
 */
function vtws_getUserAccessKey($userId)
{
	global $adb;

	$sql = 'SELECT accesskey FROM vtiger_users WHERE id=?';
	$result = $adb->pquery($sql, [$userId]);

	if ($result != null && isset($result)) {
		if ($adb->num_rows($result) > 0) {
			return $adb->query_result($result, 0, 'accesskey');
		}
	}

	return null;
}
