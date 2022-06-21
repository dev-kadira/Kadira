{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is: vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{if $SETTING_EXIST}
<a name="dfilter">
	<i class='fa fa-cog' border='0' align="absmiddle" title="{vtranslate('LBL_FILTER')}" alt="{vtranslate('LBL_FILTER')}"/>
</a>
{/if}
{if !empty($CHART_TYPE)}
    {assign var=CHART_DATA value=ZEND_JSON::decode($DATA)}
    {assign var=CHART_VALUES value=$CHART_DATA['values']}
{/if}
{if !empty($CHART_TYPE) && $REPORT_MODEL->isEditable() eq true}
<a href="{$REPORT_MODEL->getEditViewUrl()}" name="customizeChartReportWidget">
	<i class="fa fa-edit" hspace="2" border="0" align="absmiddle" title="{vtranslate('LBL_CUSTOMIZE',$MODULE)}" alt="{vtranslate('LBL_CUSTOMIZE',$MODULE)}"></i>
</a>
{/if}
{if !$WIDGET->isDefault()}
	<a name="dclose" class="widget" data-url="{$WIDGET->getDeleteUrl()}">
		<i class="fa fa-remove" hspace="2" border="0" align="absmiddle" title="{vtranslate('LBL_REMOVE')}" alt="{vtranslate('LBL_REMOVE')}"></i>
	</a>
{/if}