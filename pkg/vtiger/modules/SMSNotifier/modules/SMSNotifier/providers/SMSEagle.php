<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class SMSNotifier_SMSEagle_Provider implements SMSNotifier_ISMSProvider_Model {

	private $userName;
	
	private $password;
	
	private $parameters = array();

	const SERVICE_URI = 'https://smseagle.eu';
	
	private static $REQUIRED_PARAMETERS = array(
		array(
			'name' => 'api_url', 
			'label' => 'SMSEagle URL', 
			'type' => 'text'
		),
		array(
			'name' => 'unicode', 'label' => 'Unicode', 
			'type' => 'picklist', 
			'picklistvalues' => array('1' => 'Yes', '0' => 'No')
		),
	);

	/**
	 * Function to get provider name
	 * @return <String> provider name
	 */
	public function getName() {
		return 'SMSEagle';
	}

	/**
	 * Function to get required parameters other than (userName, password)
	 * @return <array> required parameters list
	 */
	public function getRequiredParams() {
		return self::$REQUIRED_PARAMETERS;
	}

	/**
	 * Function to get service URL to use for a given type
	 * @param <String> $type like SEND, PING, QUERY
	 */
	public function getServiceURL($type = false) {
		if($type) {
			switch(strtoupper($type)) {
				case self::SERVICE_AUTH: return  self::SERVICE_URI . '/http/auth';
				case self::SERVICE_SEND: return  self::SERVICE_URI . '/index.php/http_api/send_sms';
				case self::SERVICE_QUERY: return self::SERVICE_URI . '/http/querymsg';
			}
		}
		return false;
	}
	
	public function getSMSEagleServiceURL($url, $type = false) {
		if($type) {
			switch(strtoupper($type)) {
				case self::SERVICE_AUTH: return  $url . '/http/auth';
				case self::SERVICE_SEND: return  $url . '/index.php/http_api/send_sms';
				case self::SERVICE_QUERY: return $url . '/index.php/http_api/read_sms';
			}
		}
		return false;
	}

	/**
	 * Function to set authentication parameters
	 * @param <String> $userName
	 * @param <String> $password
	 */
	public function setAuthParameters($userName, $password) {
		$this->userName = $userName;
		$this->password = $password;
	}

	/**
	 * Function to set non-auth parameter.
	 * @param <String> $key
	 * @param <String> $value
	 */
	public function setParameter($key, $value) {
		$this->parameters[$key] = $value;
	}

	/**
	 * Function to get parameter value
	 * @param <String> $key
	 * @param <String> $defaultValue
	 * @return <String> value/$default value
	 */
	public function getParameter($key, $defaultValue = false) {
		if(isset($this->parameters[$key])) {
			return $this->parameters[$key];
		}
		return $defaultValue;
	}

	/**
	 * Function to prepare parameters
	 * @return <Array> parameters
	 */
	protected function prepareParameters() {
		$params = array('login' => $this->userName, 'pass' => $this->password);
		foreach (self::$REQUIRED_PARAMETERS as $requiredParam) {
			$paramName = $requiredParam['name'];
			$params[$paramName] = $this->getParameter($paramName);
		}
		return $params;
	}

	/**
	 * Function to handle SMS Send operation
	 * @param <String> $message
	 * @param <Mixed> $toNumbers One or Array of numbers
	 */
	public function send($message, $toNumbers) {
		if(!is_array($toNumbers)) {
			$toNumbers = array($toNumbers);
		}

		$params = $this->prepareParameters();
		
		$api_url = rtrim($params['api_url'], "/");
		
		$params['message'] = $message;
		$params['to'] = implode(',', $toNumbers);
		$params['responsetype'] = 'xml';
		$serviceURL = $this->getSMSEagleServiceURL($api_url, self::SERVICE_SEND);
		
		$httpClient = new Vtiger_Net_Client($serviceURL);
		$response = $httpClient->doGet($params);
		$i = 0;
		
		$xml = simplexml_load_string($response);
		
		if(isset($xml->error_text)){
			$result['error'] = true;
			$result['to'] = $toNumbers[0];
			$result['statusmessage'] = $matches[0];
			$results[] = $result;
		} else {
			if(isset($xml->item)){
				
				foreach($xml->item as $message_id){
					$result['id'] = (string)trim($message_id->message_id);
					$result['to'] = trim($toNumbers[$i++]);
					$result['status'] = self::MSG_STATUS_PROCESSING;
					$results[] = $result;
				}
			} else {
				$result['id'] = trim($xml->message_id);
				$result['to'] = trim($toNumbers[0]);
				$result['status'] = self::MSG_STATUS_PROCESSING;
				$results[] = $result;
			}
		}
		return $results;
	}

	/**
	 * Function to get query for status using messgae id
	 * @param <Number> $messageId
	 */
	public function query($messageId) {
		
		$params = $this->prepareParameters();
		
		$api_url = rtrim($params['api_url'], "/");
		
		$params['idfrom'] = $messageId;
		
		$params['folder']='sentitems';
		
		$params['responsetype']='xml';
		
		$params['limit']= 1;

		$serviceURL = $this->getSMSEagleServiceURL($api_url, self::SERVICE_QUERY);
		$httpClient = new Vtiger_Net_Client($serviceURL);
		$xmlResponse = $httpClient->doGet($params);
		
		$xmlObject = simplexml_load_string($xmlResponse);
		
		$result = array();
		
		$result['error'] = false;
		
		$status = (string)$xmlObject->messages->item->Status;  
		
		if($status){
			switch($status) {
				case 'queued'		:
				case 'SendingOK'		:	$status = self::MSG_STATUS_PROCESSING;
										$result['needlookup'] = 1;
										break;
									
				case 'sent'			:	$status = self::MSG_STATUS_DISPATCHED;
										$result['needlookup'] = 1;
										break;
									
				case 'DeliveryOK'	:	$status = self::MSG_STATUS_DELIVERED;
										$result['needlookup'] = 0;
										break;
									
				case 'undelivered'	:
				case 'failed'		:	
				default				:	$status = self::MSG_STATUS_FAILED;
										$result['needlookup'] = 1;
										break;
			}
		} else {
			$status = self::MSG_STATUS_PROCESSING;
			$result['needlookup'] = 1;
		}

		$result['status'] = $status;
		$result['statusmessage'] = $status;
		
		return $result;
		
	}
}
?>
