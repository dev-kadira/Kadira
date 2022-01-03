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
 * Vtiger Paging Model Class
 */
class Vtiger_Paging_Model extends Vtiger_Base_Model
{
	const DEFAULT_PAGE = 1;
	const PAGE_LIMIT = 20;

	/**
	 * Function to get the current page number
	 * @return <Number>
	 */
	public function getCurrentPage()
	{
		$currentPage = $this->get('page');
		if (empty($currentPage)) {
			$currentPage = self::DEFAULT_PAGE;
		}

		return $currentPage;
	}

	/**
	 * Function to get the Next page number
	 * @return <Number>
	 */
	public function getNextPage()
	{
		$currentPage = $this->get('page');
		if (empty($currentPage)) {
			$currentPage = self::DEFAULT_PAGE;
		}

		return $currentPage + 1;
	}

	/**
	 * Function to get the limit on the number of records per page
	 * @return <Number>
	 */
	public function getPageLimit()
	{
		$pageLimit = $this->get('limit');
		if (empty($pageLimit)) {
			$pageLimit = vglobal('list_max_entries_per_page');
			if (empty($pageLimit)) {
				$pageLimit = self::PAGE_LIMIT;
			}
		}

		return $pageLimit;
	}

	public function getStartIndex()
	{
		$currentPage = $this->getCurrentPage();
		$pageLimit = $this->getPageLimit();

		return ($currentPage - 1) * $pageLimit;
	}

	/**
	 * Retrieves start sequence number of records in the page
	 * @return <Integer>
	 */
	public function getRecordStartRange()
	{
		$rangeInfo = $this->getRecordRange();

		return isset($rangeInfo) ? $rangeInfo['start'] : 0;
	}

	/**
	 * Retrieves end sequence number of records in the page
	 * @return <Integer>
	 */
	public function getRecordEndRange()
	{
		$rangeInfo = $this->getRecordRange();

		return isset($rangeInfo) ? $rangeInfo['end'] : 0;
	}

	/**
	 * Retrieves start and end sequence number of records in the page
	 * @return <array> - array of values
	 *						- start key which gives start sequence number
	 *						- end key which gives end sequence number
	 */
	public function getRecordRange()
	{
		return $this->get('range');
	}

	/**
	 * Function to specify if previous page exists
	 * @return <Boolean> - true/false
	 */
	public function isPrevPageExists()
	{
		$prevPageExists = $this->get('prevPageExists');
		if (isset($prevPageExists)) {
			return $prevPageExists;
		}

		return true;
	}

	/**
	 * Function to specify if next page exists
	 * @return <Boolean> - true/false
	 */
	public function isNextPageExists()
	{
		$nextPageExists = $this->get('nextPageExists');
		if (isset($nextPageExists)) {
			return $nextPageExists;
		}

		return true;
	}

	/**
	 * calculates page range
	 * @param <type> $recordList - list of records which is show in current page
	 * @return Vtiger_Paging_Model  -
	 */
	public function calculatePageRange($recordList)
	{
		$rangeInfo = [];
		$recordCount = count($recordList);
		$pageLimit = $this->getPageLimit();
		$prevPageExists = $nextPageExists = false;

		if ($recordCount > 0) {
			//specifies what sequencce number of last record in prev page
			$prevPageLastRecordSequence = (($this->getCurrentPage() - 1) * $pageLimit);

			$rangeInfo['start'] = $prevPageLastRecordSequence + 1;
			if ($rangeInfo['start'] != 1) {
				$prevPageExists = true;
			}

			$rangeInfo['end'] = $prevPageLastRecordSequence + $recordCount;
			if ($recordCount > $pageLimit) {
				$nextPageExists = true;
				$rangeInfo['end'] = $prevPageLastRecordSequence + $pageLimit;
			}

			$this->set('range', $rangeInfo);
		} else {
			//Disable previous page only if page is first page and no records exists
			if ($this->getCurrentPage() != 1) {
				$prevPageExists = true;
			}
		}

		$this->set('prevPageExists', $prevPageExists);
		$this->set('nextPageExists', $nextPageExists);

		return $this;
	}
}
