jQuery(document).ready(function () {

	jQuery("body").on("click", ".wplc_admin_email_transcript", function() {
        jQuery(".wplc_admin_email_transcript").hide();
        html = "<span class='wplc_et_loading'><em>"+wplc_et_string_loading+"</em></span>";
        jQuery(".wplc_admin_email_transcript").after(html);
        var cur_id = jQuery(this).attr("cid");
        var data = {
            action: 'wplc_et_admin_email_transcript',
            security: wplc_et_nonce,
            cid: cur_id
        };
        jQuery.post(ajaxurl, data, function(response) {
            returned_data = JSON.parse(response);
            if (returned_data.constructor === Object) {
                if (returned_data.errorstring) {
                    jQuery(".wplc_admin_email_transcript").after("<p><strong>"+wplc_et_string_error1+"</strong></p>");
                } else {
                    jQuery(".wplc_et_loading").hide();

                    html = "<span class=''>"+wplc_et_string_chat_emailed+"</span>";
                    jQuery("#wplc_admin_email_transcript").after(html);
                    jQuery("#wplc_admin_email_transcript").hide();
                }
            }

            
        });



    });

});
