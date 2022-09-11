{*
/**
 * VGS Related Field Generator Module
 *
 *
 * @package        VGSRelatedFields Module
 * @author         Conrado Maggi - www.vgsglobal.com
 * @license        vTiger Public License.
 * @version        Release: 1.0
 */
*}

<div style="width: 65%;margin: auto;margin-top: 2em;padding: 2em;">
    <h3 style="padding-bottom: 1em;text-align: center">{vtranslate('vgs_fieldrelatedmodule', $MODULE)}</h3>
    <div class="row" style="margin: 1em;">


        <div class="alert alert-warning" style="float: left;margin-left:1em !important; margin-bottom: 0px !important;margin-top: 0px !important;width: 80%;">
            {vtranslate('notice', $MODULE)}
        </div>

    </div>


    <div>
        <h4 style="margin-top: 4em;margin-bottom: 0.5em;">{vtranslate('add_new_related_field', $MODULE)}</h4>
        <p>{vtranslate('add_new_related_field_explain', $MODULE)}</p>
        <table class="table table-bordered table-condensed themeTableColor" style="margin-top: 1em;">
            <thead>
                <tr class="blockHeader">
                    <th colspan="4" class="mediumWidthType"><span class="alignMiddle">{vtranslate('add_new_related_field', $MODULE)}</span></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td width="50%" colspan="2"><label class="muted pull-right marginRight10px">{vtranslate('module1', $MODULE)}</label></td>
                    <td colspan="2" style="border-left: none;">
                        <select name="module1" id="module1">
                            <option value="-">--</option>
                            {foreach from=$ENTITY_MODULES item=MODULE1}
                                <option value="{$MODULE1}">{vtranslate($MODULE1)}</option>
                            {/foreach}
                        </select>    
                    </td>
                </tr>
                <tr>
                    <td width="50%" colspan="2"><label class="muted pull-right marginRight10px">{vtranslate('block', $MODULE)}</label></td>
                    <td colspan="2" style="border-left: none;">
                        <select name="block" id="block">
                            <option value="-">--</option>

                        </select>
                    </td>
                </tr>
                <tr>
                    <td width="50%" colspan="2"><label class="muted pull-right marginRight10px"> {vtranslate('fields_label', $MODULE)}</label></td>
                    <td colspan="2" style="border-left: none;">
                        <input type="text" id="field_label">
                    </td>
                </tr>
                <tr>
                    <td width="50%" colspan="2"><label class="muted pull-right marginRight10px">{vtranslate('module2', $MODULE)}</label></td>
                    <td colspan="2" style="border-left: none;">
                        <select name="module2" id="module2">
                            <option value="-">--</option>
                            {foreach from=$ENTITY_MODULES item=MODULE1}
                                <option value="{$MODULE1}">{vtranslate($MODULE1)}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>

                <tr>
                    <td width="25%"><label class="muted pull-right marginRight10px">{vtranslate('add_related_list', $MODULE)}</label></td>
                    <td style="border-left: none;">
                        <select name="add_related_list" id="add_related">
                            <option value="-">--</option>
                            <option value="yes">{vtranslate('yes', $MODULE)}</option>
                            <option value="no">{vtranslate('no', $MODULE)}</option>
                        </select>
                    </td>
                    <td width="25%"><label class="muted pull-right marginRight10px">{vtranslate('related_list_label', $MODULE)}</label></td>
                    <td style="border-left: none;">
                       <input type="text" id="related_list_label">
                    </td>
                </tr>

            </tbody>
        </table>
        <br><br>
          <div class="alert alert-error notices" id="error_notice" style="display:none;float: left;margin-left:1em !important; margin-bottom: 0px !important;margin-top: 0px !important;width: 80%;">
            {vtranslate('fail', $MODULE)}
        </div>
          <div class="alert alert-success notices" id="success_message" style="display:none;float: left;margin-left:1em !important; margin-bottom: 0px !important;margin-top: 0px !important;width: 80%;">
            {vtranslate('works', $MODULE)}
        </div>
          <div class="alert alert-error notices" id="duplicate_error" style="display:none;float: left;margin-left:1em !important; margin-bottom: 0px !important;margin-top: 0px !important;width: 80%;">
            {vtranslate('duplicated-error', $MODULE)}
        </div>
         <div class="alert alert-error notices" id="field-already-there" style="display:none;float: left;margin-left:1em !important; margin-bottom: 0px !important;margin-top: 0px !important;width: 80%;">
            {vtranslate('field-already-there', $MODULE)}
        </div>
        
        <button class="btn pull-right" style="margin-bottom: 0.5em;" id="add_related_field">{vtranslate('save', $MODULE)}</button>

    </div>
</div>
