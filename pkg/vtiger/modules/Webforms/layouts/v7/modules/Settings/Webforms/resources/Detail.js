/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Settings_Vtiger_Detail_Js('Settings_Webforms_Detail_Js', {

	/*
	 * function to trigger delete record action
	 * @params: delete record url.
	 */
	deleteRecord: function (deleteRecordActionUrl) {
		app.helper.showConfirmationBox({
			message: app.vtranslate('LBL_DELETE_CONFIRMATION')
		}).then(function () {
			app.request.post({ 'url': deleteRecordActionUrl }).then(
				function (e, res) {
					if (!e) {
						window.location.href = res;
					} else {
						app.helper.showErrorNotification({
							'message': e
						});
					}
				});
		});
	},

	/** 
	 * Function to trigger show webform record action
	 * @params: show webform record url
	 */
	showForm: function (record) {
		var self = this;
		var params = {
			'module': 'Webforms',
			'record': record,
			'view': 'ShowForm',
			'parent': 'Settings'
		}
		app.request.get({ data: params }).then(
			function (error, data) {
				var callback = function (container) {
					//show html without rendering
					var allowedAllFilesSize = container.find('.allowedAllFilesSize').val();
					var showFormContents = container.find('pre').html();

					// Replace custom (vscript) tags to (script) before adding to textarea
					showFormContents = showFormContents.replace(/vscript/g, "script");

					showFormContents = showFormContents + '<script  type="text/javascript">' +
						'window.onload = function() { ' +
						'var N=navigator.appName, ua=navigator.userAgent, tem;' +
						'var M=ua.match(/(opera|chrome|safari|firefox|msie)\\/?\\s*(\\.?\\d+(\\.\\d+)*)/i);' +
						'if(M && (tem= ua.match(/version\\/([\\.\\d]+)/i))!= null) M[2]= tem[1];' +
						'M=M? [M[1], M[2]]: [N, navigator.appVersion, "-?"];' +
						'var browserName = M[0];' +

						'var form = document.getElementById("__vtigerWebForm"), ' +
						'inputs = form.elements; ' +
						'form.onsubmit = function() { ' +
						'var required = [], att, val; ' +
						'for (var i = 0; i < inputs.length; i++) { ' +
						'att = inputs[i].getAttribute("required"); ' +
						'val = inputs[i].value; ' +
						'type = inputs[i].type; ' +
						'if(type == "email") {' +
						'if(val != "") {' +
						'var elemLabel = inputs[i].getAttribute("label");' +
						'var emailFilter = /^[_/a-zA-Z0-9]+([!"#$%&()*+,./:;<=>?\\^_`{|}~-]?[a-zA-Z0-9/_/-])*@[a-zA-Z0-9]+([\\_\\-\\.]?[a-zA-Z0-9]+)*\\.([\\-\\_]?[a-zA-Z0-9])+(\\.?[a-zA-Z0-9]+)?$/;' +
						'var illegalChars= /[\\(\\)\\<\\>\\,\\;\\:\\\"\\[\\]]/ ;' +
						'if (!emailFilter.test(val)) {' +
						'alert("For "+ elemLabel +" field please enter valid email address"); return false;' +
						'} else if (val.match(illegalChars)) {' +
						'alert(elemLabel +" field contains illegal characters");return false;' +
						'}' +
						'}' +
						'}' +
						'if (att != null) { ' +
						'if (val.replace(/^\\s+|\\s+$/g, "") == "") { ' +
						'required.push(inputs[i].getAttribute("label")); ' +
						'} ' +
						'} ' +
						'} ' +
						'if (required.length > 0) { ' +
						'alert("The following fields are required: " + required.join()); ' +
						'return false; ' +
						'} ' +
						'var numberTypeInputs = document.querySelectorAll("input[type=number]");' +
						'for (var i = 0; i < numberTypeInputs.length; i++) { ' +
						'val = numberTypeInputs[i].value;' +
						'var elemLabel = numberTypeInputs[i].getAttribute("label");' +
						'var elemDataType = numberTypeInputs[i].getAttribute("datatype");' +
						'if(val != "") {' +
						'if(elemDataType == "double") {' +
						'var numRegex = /^[+-]?\\d+(\\.\\d+)?$/;' +
						'}else{' +
						'var numRegex = /^[+-]?\\d+$/;' +
						'}' +
						'if (!numRegex.test(val)) {' +
						'alert("For "+ elemLabel +" field please enter valid number"); return false;' +
						'}' +
						'}' +
						'}' +
						'var dateTypeInputs = document.querySelectorAll("input[type=date]");' +
						'for (var i = 0; i < dateTypeInputs.length; i++) {' +
						'dateVal = dateTypeInputs[i].value;' +
						'var elemLabel = dateTypeInputs[i].getAttribute("label");' +
						'if(dateVal != "") {' +
						'var dateRegex = /^[1-9][0-9]{3}-(0[1-9]|1[0-2]|[1-9]{1})-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]{1})$/;' +
						'if(!dateRegex.test(dateVal)) {' +
						'alert("For "+ elemLabel +" field please enter valid date in required format"); return false;' +
						'}}}' +
						'var inputElems = document.getElementsByTagName("input");' +
						'var totalFileSize = 0;' +
						'for(var i = 0; i < inputElems.length; i++) {' +
						'if(inputElems[i].type.toLowerCase() === "file") {' +
						'var file = inputElems[i].files[0];' +
						'if(typeof file !== "undefined") {' +
						'var totalFileSize = totalFileSize + file.size;' +
						'}' +
						'}' +
						'}' +
						'if(totalFileSize > ' + allowedAllFilesSize + ') {' +
						'alert("Maximum allowed file size including all files is 50MB.");' +
						'return false;' +
						'}' +
						'}; ' +
						'}' +
						'</script>';
					//Html contents should be placed inside textarea element
					container.find('#showFormContent').text(showFormContents);
					//Rendering content has been removed from container
					container.find('pre').remove();
					container.find('code').remove();
					self.registerCopyToClipboard();
				};

				app.helper.showModal(data, {
					'cb': callback
				});
			}
		)
	},

	registerCopyToClipboard: function () {
		jQuery('#webformCopyClipboard').click(function (e) {
			e.preventDefault();
			try {
				document.getElementById('showFormContent').select();
				var success = document.execCommand("copy");
				if (success) {
					app.helper.showSuccessNotification({ message: app.vtranslate('JS_COPIED_SUCCESSFULLY') });
				} else {
					app.helper.showErrorNotification({ message: app.vtranslate('JS_COPY_FAILED') });
				}
				if (window.getSelection) {
					if (window.getSelection().empty) {
						window.getSelection().empty();
					} else if (window.getSelection().removeAllRanges) {
						window.getSelection().removeAllRanges();
					}
				} else if (document.selection) {
					document.selection.empty();
				}
			} catch (err) {
				app.helper.showErrorNotification({ message: app.vtranslate('JS_COPY_FAILED') });
			}
		});
	},
}, {
	/**
	 * Function which will handle the registrations for the elements
	 */
	registerEvents: function () {
		this._super();
	}
})
