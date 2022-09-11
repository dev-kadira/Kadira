/**
 * VGS Related Field Generator Module
 *
 *
 * @package        VGSRelatedFields Module
 * @author         Conrado Maggi - www.vgsglobal.com
 * @license        vTiger Public License.
 * @version        Release: 1.0
 */

$(document).on('change', '#module1', {}, function(e) {

    var module1 = $(this).val();
    $('#block').find('option').remove().end().append('<option value="--">--</option>').val('--');
    

    jQuery("#error_notice").hide();
   
    app.helper.showProgress();

    var dataUrl = "module=VGSRelatedFields&action=getBlocks&module1=" + module1;
    AppConnector.request(dataUrl).then(
            function(data) {

                if (data.success) {
                    app.helper.hideProgress();

                    var result = data.result;
                    if (result.result == 'ok') {

                        jQuery.each(result.options, function(i, item) {

                            var o = new Option(item, i);
                            /// jquerify the DOM object 'o' so we can use the html method
                            jQuery(o).html(item);
                            jQuery("#block").append(o);
                        });

                    } else {
                        app.helper.showErrorNotification({'message': vtranslate('Error Loading Module blocks')});
                        
                    }
                }
            },
            function(error, err) {
                app.helper.hideProgress();
                app.helper.showErrorNotification({'message': vtranslate('Error Loading Module blocks')});
                
            }
    );

});


$(document).on('change', '#module2', {}, function(e) {

    var module1 = $('#module1').val();
    var module2 = $(this).val();
    $('#add_related').find('option').remove().end().append('<option value="--">--</option>').val('--');
    $('#add_related').append('<option value="new">Add New</option>');
    $('#add_related').append('<option value="none">None</option>');


});


$(document).on('click', '#add_related_field', {}, function(e) {

    jQuery(".notices").hide();
    app.helper.showProgress();

    var module1 = jQuery("#module1").val();
    var module2 = jQuery("#module2").val();
    var block = jQuery("#block").val();
    var field_label = jQuery("#field_label").val();
    var add_related = jQuery("#add_related").val();
    var related_list_label = jQuery("#related_list_label").val();

    var dataUrl = "module=VGSRelatedFields&action=saveRelatedFields&module1=" + module1 + "&module2=" + module2 + "&fiel_label=" + field_label + "&block=" + block + "&addlist=" + add_related + "&related_list_label=" + related_list_label;
    AppConnector.request(dataUrl).then(
            function(data) {
                app.helper.hideProgress();

                if (data.success) {
                    var response = data.result;

                    if (response.result == 'ok') {

                        jQuery("#success_message").show();
                        jQuery("#module1").val('--');
                        jQuery("#module2").val('--');
                        jQuery("#field_label").val('')
                        jQuery("#add_related").val('--')
                        jQuery("#block").val('');

                    } else {

                        app.helper.showErrorNotification({'message': response.message});

                    }
                }
            },
            function(error, err) {
                app.helper.hideProgress();

            }
    );

});