<?php
/*
Plugin Name: WP Live Chat Support - Email Transcripts
Plugin URL: http://wp-livechat.com
Description: Allows both users and agents to email chat transcripts during and after a live chat session
Version: 1.0.1
Author: WP-LiveChat
Author URI: http://wp-livechat.com
Contributors: NickDuncan, Jarryd Long, CodeCabin_, dylanauty
Text Domain: wp-live-chat-support-email-transcripts
Domain Path: /languages
*/


/*
* 1.0.1 - 20 September 2016
* Tested on WordPress 4.6.1
* Moved Settings to tab
*
* 1.0.0 - 12 October 2015
* Launch!
* 
*/



if(!defined('WPLC_ET_PLUGIN_DIR')) {
	define('WPLC_ET_PLUGIN_DIR', dirname(__FILE__));
}

global $current_chat_id;
global $wplc_et_version;
$wplc_et_version = "1.0.1";

add_action('wplc_hook_admin_visitor_info_display_after','wplc_et_add_admin_button');
add_action('wplc_hook_admin_javascript_chat','wplc_et_admin_javascript');

add_filter("wplc_filter_setting_tabs","wplc_et_settings_tab_heading");
add_action("wplc_hook_settings_page_more_tabs","wplc_et_settings_tab_content");

add_action('wplc_hook_admin_settings_save','wplc_et_save_settings');

add_action('wp_ajax_wplc_et_admin_email_transcript', 'wplc_et_callback');

add_shortcode('wplc_et_transcript', 'wplc_et_get_transcript');
add_shortcode('wplc_et_transcript_footer_text','wplc_et_get_footer_text');
add_shortcode('wplc_et_transcript_header_text','wplc_et_get_header_text');


add_action("init","wplc_et_first_run_check");

add_action("wp_after_admin_bar_render","wplc_et_check_if_plugins_active");





/**
* Check if WP Live Chat Support is installed and active
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_et_check_if_plugins_active() {
	if (is_admin()) {
		if (!is_plugin_active('wp-live-chat-support/wp-live-chat-support.php')) {
			
			echo "<div class='error below-h1'>";
			echo "<p>".sprintf( __( '<strong><a href="%1$s" title="Install WP Live Chat Support">WP Live Chat Support</strong></a> is required for the <strong>WP Live Chat Support - Email Transcripts</strong> add-on to work. Please install and activate it.', 'wp-live-chat-support-email-transcripts' ),
	            "plugin-install.php?tab=search&s=wp+live+chat+support"
	            )."</p>";
	        echo "</div>";
	        
		}
	}

}


/**
* Check if this is the first time the user has run the plugin. If yes, set the default settings
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_et_first_run_check() {
	if (!get_option("WPLC_ET_FIRST_RUN")) {
		/* set the default settings */
		$wplc_et_data['wplc_enable_transcripts'] = 1;
		$wplc_et_data['wplc_et_email_body'] = wplc_et_return_default_email_body();
		$wplc_et_data['wplc_et_email_header'] = '<a title="'.get_bloginfo('name').'" href="'.get_bloginfo('url').'" style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #FFF; font-weight: bold; text-decoration: underline;">'.get_bloginfo('name').'</a>';

		$default_footer_text = sprintf( __( 'Thank you for chatting with us. If you have any questions, please <a href="%1$s" target="_blank" style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #FFF; font-weight: bold; text-decoration: underline;">contact us</a>', 'wp-live-chat-support-email-transcripts' ),
            'mailto:'.get_bloginfo('admin_email')
        );

		$wplc_et_data['wplc_et_email_footer'] = "<span style='font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #FFF; font-weight: normal;'>".$default_footer_text."</span>";

      

        update_option('WPLC_ET_SETTINGS', $wplc_et_data);
        update_option("WPLC_ET_FIRST_RUN",true);
	}
}


/**
* Adds the email transcript button to the visitor box in the active chat window
*
* @since       1.0.0
* @param       int $cid The current chat ID
* @return
*
*/
function wplc_et_add_admin_button($cid) {
	$wplc_et_settings = get_option("WPLC_ET_SETTINGS");
	$wplc_enable_transcripts = $wplc_et_settings['wplc_enable_transcripts'];
	if (isset($wplc_enable_transcripts) && $wplc_enable_transcripts == 1) {
		echo "<p><a href=\"javascript:void(0);\" cid='".sanitize_text_field($cid)."' class=\"wplc_admin_email_transcript button button-secondary\" id=\"wplc_admin_email_transcript\">".__("Email transcript to user","wp-live-chat-support-email-transcripts")."</a></p>";
	}
}




/**
* Adds the javascript calls to the chat window which handles the ajax requests
*
* @since       [1.0.0]
* @param       
* @return
*
*/
function wplc_et_admin_javascript() {
	$wplc_et_ajax_nonce = wp_create_nonce("wplc_et_nonce");
    wp_register_script('wplc_et_transcript_admin', plugins_url('js/wplc_et.js', __FILE__), null, '', true);
    wp_enqueue_script('wplc_et_transcript_admin');
    $wplc_et_string_loading = __("Sending transcript...","wp-live-chat-support-email-transcripts");
    $wplc_et_string_chat_emailed = __("The chat transcript has been emailed.","wp-live-chat-support-email-transcripts");
   	$wplc_et_string_error1 = sprintf(__("There was a problem emailing the chat. Please <a target='_BLANK' href='%s'>contact support</a>.","wp-live-chat-support-email-transcripts"),"http://wp-livechat.com/contact-us/?utm_source=plugin&utm_medium=link&utm_campaign=error_emailing_chat");
    wp_localize_script( 'wplc_et_transcript_admin', 'wplc_et_nonce', $wplc_et_ajax_nonce);
    wp_localize_script( 'wplc_et_transcript_admin', 'wplc_et_string_chat_emailed', $wplc_et_string_chat_emailed);
    wp_localize_script( 'wplc_et_transcript_admin', 'wplc_et_string_error1', $wplc_et_string_error1);
    wp_localize_script( 'wplc_et_transcript_admin', 'wplc_et_string_loading', $wplc_et_string_loading);

}





/**
* Ajax callback handler
*
* @since       	1.0.0
* @param       
* @return 		void
*
*/
function wplc_et_callback() {
	$check = check_ajax_referer( 'wplc_et_nonce', 'security' );
	if ($check == 1) {

        if ($_POST['action'] == "wplc_et_admin_email_transcript") {
        	if (isset($_POST['cid'])) {
        		$cid = intval($_POST['cid']);
        		echo json_encode(wplc_et_send_transcript(sanitize_text_field($cid)));
        	} else {
        		echo json_encode(array("error"=>"no CID"));
        	}
        	wp_die();
        }

        wp_die();
    }
    wp_die();
}


/**
* Sends the transcript to the user
*
* @since 		1.0.0
* @param 		int $cid Chat ID
* @return 		array Returns either true or an error with a description of the error
*
*/
function wplc_et_send_transcript($cid) {
	if (!$cid) { return array("error"=>"no CID"); }


 	global $wpdb;
    global $wplc_tblname_chats;
    $results = $wpdb->get_results(
            "
        SELECT *
        FROM $wplc_tblname_chats
        WHERE `id` = '$cid'
        LIMIT 1
        "
    );
    
    foreach ($results as $result) {
         $email = $result->email;
     }
     if (!$email) { return array("error"=>"no email"); }

	$headers = array('Content-Type: text/html; charset=UTF-8');
	$subject = sprintf( __( 'Your chat transcript from %1$s', 'wp-live-chat-support-email-transcripts' ),
            get_bloginfo('url')
        );
	wp_mail($email,$subject,wplc_et_return_chat_messages($cid),$headers);
	return array("success"=>1);

}


/**
* Return footer text via the shortcode
*
* @since       	1.0.0
* @param       
* @return		string Footer HTML
*
*/
function wplc_et_get_footer_text() {
	$wplc_et_settings = get_option("WPLC_ET_SETTINGS");
	$wplc_et_footer = html_entity_decode(stripslashes($wplc_et_settings['wplc_et_email_footer']));
	if ($wplc_et_footer) { return $wplc_et_footer; }
	else { return ""; }
}
/**
* Return header text via the shortcode
*
* @since       	1.0.0
* @param       
* @return		string Header HTML
*
*/
function wplc_et_get_header_text() {
	$wplc_et_settings = get_option("WPLC_ET_SETTINGS");
	$wplc_et_header = html_entity_decode(stripslashes($wplc_et_settings['wplc_et_email_header']));
	if ($wplc_et_header) { return $wplc_et_header; }
	else { return ""; }
}

/**
* Return the body of the transcript email to be sent out
*
* @since       	1.0.0
* @param       
* @return		string Transcript HTML
*
*/
function wplc_et_get_transcript() {
	global $current_chat_id;
    $cid = $current_chat_id;
    if (intval($cid) > 0) { 
		return wplc_return_chat_messages(intval($cid),true);
	} else {
		return "0";
	}

}


/**
* Generate the default HTML transcript mailer
*
* @since       	1.0.0
* @param       
* @return		string Default HTML mailer
*
*/function wplc_et_return_default_email_body() {
	$body = '
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">		
	<html>
	
	<body>



		<table id="" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ec822c;">
	    <tbody>
	      <tr>
	        <td width="100%" style="padding: 30px 20px 100px 20px;">
	          <table align="center" cellpadding="0" cellspacing="0" class="" width="100%" style="border-collapse: separate; max-width:600px;">
	            <tbody>
	              <tr>
	                <td style="text-align: center; padding-bottom: 20px;">
	                  
	                  <p>[wplc_et_transcript_header_text]</p>
	                </td>
	              </tr>
	            </tbody>
	          </table>

	          <table id="" align="center" cellpadding="0" cellspacing="0" class="" width="100%" style="border-collapse: separate; max-width: 600px; font-family: Georgia, serif; font-size: 12px; color: rgb(51, 62, 72); border: 0px solid rgb(255, 255, 255); border-radius: 10px; background-color: rgb(255, 255, 255);">
	          <tbody>
	              <tr>
	                <td class="sortable-list ui-sortable" style="padding:20px;">
	                    [wplc_et_transcript]
	                </td>
	              </tr>
	            </tbody>
	          </table>

	          <table align="center" cellpadding="0" cellspacing="0" class="" width="100%" style="border-collapse: separate; max-width:100%;">
	            <tbody>
	              <tr>
	                <td style="padding:20px;">
	                  <table border="0" cellpadding="0" cellspacing="0" class="" width="100%">
	                    <tbody>
	                      <tr>
	                        <td id="" align="center">
	                         <p>[wplc_et_transcript_footer_text]</p>
	                        </td>
	                      </tr>
	                    </tbody>
	                  </table>
	                </td>
	              </tr>
	            </tbody>
	          </table>
	        </td>
	      </tr>
	    </tbody>
	  </table>


		
		</div>
	</body>
</html>
			';
	return $body;

}

/**
* Generate the full HTML newsletter of the transcript to be sent out
*
* @since       	1.0.0
* @param       	int chat ID
* @return		string HTML transcript email
*
*/
function wplc_et_return_chat_messages($cid) {
	global $current_chat_id;
	$current_chat_id = $cid;
	$wplc_et_settings = get_option("WPLC_ET_SETTINGS");
	$body = html_entity_decode(stripslashes($wplc_et_settings['wplc_et_email_body']));

	if (!$body) {
		$body = do_shortcode(wplc_et_return_default_email_body());
	} else {
		$body = do_shortcode($body);
	}
	return $body;


	
}

/**
* Latch onto the default POST handling when saving live chat settings
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_et_save_settings() {
	if (isset($_POST['wplc_save_settings'])) {
        if (isset($_POST['wplc_enable_transcripts'])) {
            $wplc_et_data['wplc_enable_transcripts'] = esc_attr($_POST['wplc_enable_transcripts']);
        } else {
        	$wplc_et_data['wplc_enable_transcripts'] = 0;
        }
        if (isset($_POST['wplc_et_email_header'])) {
            $wplc_et_data['wplc_et_email_header'] = esc_attr($_POST['wplc_et_email_header']);
        }
        if (isset($_POST['wplc_et_email_footer'])) {
            $wplc_et_data['wplc_et_email_footer'] = esc_attr($_POST['wplc_et_email_footer']);
        }
        if (isset($_POST['wplc_et_email_body'])) {
            $wplc_et_data['wplc_et_email_body'] = esc_html($_POST['wplc_et_email_body']);
        }
        update_option('WPLC_ET_SETTINGS', $wplc_et_data);

    }
}

/**
 * Add Settings Tab Heading
 *
 * @since        1.0.01
 * @param       
 * @return       void
*/
function wplc_et_settings_tab_heading($tab_array){
    $tab_array['wplc_tab_et'] = array(
      "href" => "#wplc_tab_et",
      "icon" => 'fa fa-envelope',
      "label" => __("Chat Transcripts","wp-live-chat-support-email-transcripts")
    );
    return $tab_array;
}

/**
 * Add Settings Tab Content
 *
 * @since        1.0.01
 * @param       
 * @return       void
*/
function wplc_et_settings_tab_content(){
    echo "<div id='wplc_tab_et'>";
    echo wplc_et_settings();
    echo "</div>";
}

/**
* Display the chat transcript settings page
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_et_settings() {
	$wplc_et_settings = get_option("WPLC_ET_SETTINGS");
	$content = "<h3>" . __("Chat Transcript Settings","wp-live-chat-support-email-transcripts") . ":</h3>";
	$content .= "<table class='form-table' width='700'>";
	$content .= "	<tr>";
	$content .= "		<td width='400' valign='top'>".__("Enable chat transcripts:","wp-live-chat-support-email-transcripts")."</td>";
	$content .= "		<td>";
	$content .= "			<input type=\"checkbox\" value=\"1\" name=\"wplc_enable_transcripts\" ";
	if(isset($wplc_et_settings['wplc_enable_transcripts'])  && $wplc_et_settings['wplc_enable_transcripts'] == 1 ) { $content .= "checked"; }
	$content .= " />";
	$content .= "		</td>";
	$content .= "	</tr>";

	$content .= "	<tr>";
	$content .= "		<td width='400' valign='top'>".__("Email body","wp-live-chat-support-email-transcripts")."</td>";
	$content .= "		<td>";
	$content .= "			<textarea cols='85' rows='15' name=\"wplc_et_email_body\">";
	if(isset($wplc_et_settings['wplc_et_email_body'])) { $content .= html_entity_decode(stripslashes($wplc_et_settings['wplc_et_email_body'])); }
	$content .= " </textarea>";
	$content .= "		</td>";
	$content .= "	</tr>";


	$content .= "	<tr>";
	$content .= "		<td width='400' valign='top'>".__("Email header","wp-live-chat-support-email-transcripts")."</td>";
	$content .= "		<td>";
	$content .= "			<textarea cols='85' rows='5' name=\"wplc_et_email_header\">";
	if(isset($wplc_et_settings['wplc_et_email_header'])) { $content .= stripslashes($wplc_et_settings['wplc_et_email_header']); }
	$content .= " </textarea>";
	$content .= "		</td>";
	$content .= "	</tr>";

	$content .= "	<tr>";
	$content .= "		<td width='400' valign='top'>".__("Email footer","wp-live-chat-support-email-transcripts")."</td>";
	$content .= "		<td>";
	$content .= "			<textarea cols='85' rows='5' name=\"wplc_et_email_footer\">";
	if(isset($wplc_et_settings['wplc_et_email_footer'])) { $content .= stripslashes($wplc_et_settings['wplc_et_email_footer']); }
	$content .= " </textarea>";
	$content .= "		</td>";
	$content .= "	</tr>";

	$content .= "</table>";

	return $content;
}