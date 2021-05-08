<?php
/**
 * Plugin Name: JimmySoft onboarding emails
 * Plugin URI: https://embroiderywaresoftware.com
 * Description: This plugin sends on boarding sales emails to get the customer to purchase by enticing them with discount coupons.  The email html templates are hereas well as setting for onboarding activities.
 * Version: 2.0.0
 * Author: Jim Bailey
 * Author URI: https://jimmysoftllc.com
 * License: GPL2
 */
 
function jimmysoft_send_onboarding_emails() {
	$debug = false;
	$reset = false;
	$order_dates_now = new DateTime('NOW');	
	$order_dates_now ->add(new DateInterval('P1D'));
	$order_dates_past = new DateTime('NOW');
	$order_dates_past ->sub(new DateInterval('P60D'));
	$order_range = $order_dates_past->format('Y-m-d').'...'.$order_dates_now->format('Y-m-d');
	$customer_orders = wc_get_orders( array('limit' => -1,'date_paid' => $order_range) );
	$date= new DateTime('NOW');
	$date ->add(new DateInterval('P7D'));
	$coupon_date_email =$date->format('Y-m-d');
	$date = new DateTime('NOW');
	$date ->add(new DateInterval('P8D'));
	$coupon_expire_date =$date->format('Y-m-d');
	$date = new DateTime('NOW');
	$code_string_1 = (string)((int)$date->format('m') + 21).(string)((int)$date->format('d') + 44).(string)((int)$date->format('y') + 54);
	$coupon_code_date_1 ='bonus'.$code_string_1;
	if (get_option('jimmysoft_product_fixed_amount_coupon') == true){
		$coupon_type='fixed_cart';
	}else{
		$coupon_type='percent';
	}
	create_coupon(get_option('jimmysoft_product_first_coupon_value'),$coupon_code_date_1,$coupon_type,$coupon_expire_date,'');
	$coupon_code_date_2 ='bonus2'.$code_string_1;
	create_coupon(get_option('jimmysoft_product_second_coupon_value'),$coupon_code_date_2,$coupon_type,$coupon_expire_date,'');
	delete_expired_onboarding_coupons();
	if ($reset == true ){
		foreach ($customer_orders as $order) {
			$my_order = new WC_Order( $order->ID );
			$user_id = $my_order->get_user_ID();
			update_user_meta($user_id, 'ew_trial_email_log', '');
		}
		$reset = false;
	}	
	foreach ($customer_orders as $order) {
		$my_order = new WC_Order( $order->ID );
		$user_id = $my_order->get_user_ID();
		$user_info = get_userdata($user_id);
		$first_name = ucfirst($user_info->first_name);
		$trial_expire_date = $user_info->ew_trial_expire_date;
		$havemeta = get_user_meta($user_id, 'ew_trial_date', false);
		if ($havemeta==true){
			// first email sent typically after 7 days of downloading program
			if ($user_info->ew_trial_email_log <> 'first_email_sent' and $user_info->ew_trial_email_log <> 'second_email_sent' and $user_info->ew_trial_email_log <> 'purchased' and $user_info->ew_trial_email_log <> 'renewed'){
				$trial_date = new DateTime($user_info->ew_trial_date);
				$trial_date_plus_interval1 = new DateTime($user_info->ew_trial_date);
				if ($debug==true){
					$trial_date_plus_interval1 ->sub(new DateInterval('P'.get_option('jimmysoft_product_first_email').'D'));
				}else{
					$trial_date_plus_interval1 ->add(new DateInterval('P'.get_option('jimmysoft_product_first_email').'D'));
				}
				$trial_date_plus_interval2 = new DateTime($user_info->ew_trial_date);				
				if ($debug==true){
					$trial_date_plus_interval2 ->sub(new DateInterval('P'.get_option('jimmysoft_product_second_email').'D'));
				}else{
					$trial_date_plus_interval2 ->add(new DateInterval('P'.get_option('jimmysoft_product_second_email').'D'));
				}					
				$todays_date = new DateTime('NOW');			
				if ($todays_date > $trial_date_plus_interval1){
					if ($todays_date > $trial_date_plus_interval2){
						update_user_meta($user_id, 'ew_trial_email_log', 'first_email_sent');
					}else{					
						update_user_meta($user_id, 'ew_trial_email_log', 'first_email_sent');
						$email=$user_info->user_email;
						if ($debug==true){
							$email='novashorts@aol.com';
						}
						$subject = get_option('jimmysoft_product_first_email_subject');
						$subject = str_replace('[client-first]',$first_name,$subject);
						$message = get_option('jimmysoft_product_first_email_message');
						$message = str_replace('[client-first]',$first_name,$message);
						if (get_option('jimmysoft_product_fixed_amount_coupon') == true){
							$message = str_replace('[your-message1]','$'.get_option('jimmysoft_product_first_coupon_value').' Coupon Code: '.$coupon_code_date_1,$message);
							$message = str_replace('[coupon-value]','$'.get_option('jimmysoft_product_first_coupon_value'),$message);
						}else{
							$message = str_replace('[your-message1]',get_option('jimmysoft_product_first_coupon_value').'% Coupon Code: '.$coupon_code_date_1,$message);
							$message = str_replace('[coupon-value]',get_option('jimmysoft_product_first_coupon_value').'%',$message);
						}
						$message = str_replace('[your-message2]','Expires on: '.$coupon_date_email,$message);						
						$headers = set_headers_for_email();			
						wp_mail($email,$subject,$message,$headers);	
					}
				}
			}
			// second email sent typically after 20 days of downloading program	10 days before it expires		
			if ($user_info->ew_trial_email_log <> 'second_email_sent' and $user_info->ew_trial_email_log <> 'purchased' and $user_info->ew_trial_email_log <> 'renewed'){
				$trial_date_plus_interval3 = new DateTime($user_info->ew_trial_date);
				if ($debug==true){
					$trial_date_plus_interval3 ->sub(new DateInterval('P'.get_option('jimmysoft_product_second_email').'D'));
				}else{
					$trial_date_plus_interval3 ->add(new DateInterval('P'.get_option('jimmysoft_product_second_email').'D'));
				}
				$todays_date = new DateTime('NOW');			
				if ($todays_date > $trial_date_plus_interval3){
					update_user_meta($user_id, 'ew_trial_email_log', 'second_email_sent');
					$email=$user_info->user_email;
					if ($debug==true){
						$email='novashorts@aol.com';
					}
					$subject = get_option('jimmysoft_product_second_subject');
					$subject = str_replace('[client-first]',$first_name,$subject);
					$message = get_option('jimmysoft_product_second_message');
					$message = str_replace('[client-first]',$first_name,$message);
					$message = str_replace('[expire-date]',$trial_expire_date,$message);
					if (get_option('jimmysoft_product_fixed_amount_coupon') == true){
							$message = str_replace('[your-message1]','$'.get_option('jimmysoft_product_second_coupon_value').' Coupon Code: '.$coupon_code_date_2,$message);
							$message = str_replace('[coupon-value]','$'.get_option('jimmysoft_product_second_coupon_value'),$message);
					}else{
							$message = str_replace('[your-message1]',get_option('jimmysoft_product_second_coupon_value').'% Coupon Code: '.$coupon_code_date_2,$message);
							$message = str_replace('[coupon-value]',get_option('jimmysoft_product_second_coupon_value').'%',$message);
					}					
					$message = str_replace('[your-message2]','Expires on: '.$coupon_date_email,$message);				
					$headers = set_headers_for_email();				
					wp_mail($email,$subject,$message,$headers);	
				}
			}	
		}
	}
}

function create_coupon($amount,$coupon_code,$discount_type,$expiry_date,$usage_limit) {
	// Discount Type: fixed_cart, percent, fixed_product, percent_product
	// Check the coupon
    global $wpdb;
    $sql = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 1;", $coupon_code );
    $coupon_id = $wpdb->get_var( $sql );
    //if ( empty( $coupon_id ) ) {
			// Get an instance of the WC_Coupon object
			$wc_coupon = new WC_Coupon($coupon_code);
			$description = __(''); // Description
			// Set the coupon data
			$wc_coupon->set_discount_type($discount_type);
			$wc_coupon->set_description($description);
			$wc_coupon->set_code($coupon_code);
			$wc_coupon->set_amount( $amount );
			$wc_coupon->set_date_expires( $expiry_date );
			$wc_coupon->set_free_shipping( false );
			$wc_coupon->set_minimum_amount( 0 );
			$wc_coupon->set_maximum_amount( 0 );			
			$wc_coupon->set_individual_use( true );
			$wc_coupon->set_exclude_sale_items( true );
			$wc_coupon->set_product_ids( '' );
			$exclude_these_products = preg_split('/,/', get_option('jimmysoft_exclude_these_products'));
			$wc_coupon->set_excluded_product_ids( $exclude_these_products );
			$wc_coupon->set_product_categories( '' );
			$wc_coupon->set_excluded_product_categories( '' );
			$wc_coupon->set_email_restrictions( '' );			
			$wc_coupon->set_usage_limit( $usage_limit );
			$wc_coupon->set_limit_usage_to_x_items( '' );
			$wc_coupon->set_usage_limit_per_user( '1' );
			// SAVE the coupon
			$wc_coupon->save();
			/*array(
					'code'                        => '',
					'amount'                      => 0,
					'date_created'                => null,
					'date_modified'               => null,
					'date_expires'                => null,
					'discount_type'               => 'fixed_cart',
					'description'                 => '',
					'usage_count'                 => 0,
					'individual_use'              => false,
					'product_ids'                 => array(),
					'excluded_product_ids'        => array(),
					'usage_limit'                 => 0,
					'usage_limit_per_user'        => 0,
					'limit_usage_to_x_items'      => null,
					'free_shipping'               => false,
					'product_categories'          => array(),
					'excluded_product_categories' => array(),
					'exclude_sale_items'          => false,
					'minimum_amount'              => '',
					'maximum_amount'              => '',
					'email_restrictions'          => array(),
					'used_by'                     => array(),
					'virtual'                     => false,
				)*/
	//}
}

function delete_expired_onboarding_coupons(){
	$args = array('posts_per_page'=> -1,'post_type'=> 'shop_coupon','post_status'=>'publish');    
	$coupons = get_posts( $args );
	foreach($coupons as $coupon_item){
		$onboarding_coupon = false;
		$my_title= $coupon_item->post_title;
		if (substr($my_title,0,5)=='bonus') {
			$onboarding_coupon = true;
		}
		if ($onboarding_coupon == true){
			$my_date_expires = $coupon_item->date_expires;
			$my_date_current = time();
			if ( $my_date_expires < $my_date_current ) {
				wp_trash_post( $coupon_item->ID );	
			}
		}			
	}
}

function simple_email($message) {
	$headers = set_headers_for_email();			
	wp_mail('novashort@aol.com','test',$message,$headers);	
}

// Settings page ------------------------------------------------------------------------------

add_action('admin_menu', 'my_plugin_menu_2');

function my_plugin_menu_2() {
	add_menu_page('My Plugin Settings 2', 'JimmySoft onboarding emails', 'administrator', 'my-plugin-settings-2', 'my_plugin_settings_page_2', 'dashicons-admin-generic');
}

add_action( 'admin_init', 'my_plugin_settings_2' );

function my_plugin_settings_2() {
	register_setting( 'my-plugin-settings-group-2', 'jimmysoft_product_first_email' );
	register_setting( 'my-plugin-settings-group-2', 'jimmysoft_product_first_email_subject' );
	register_setting( 'my-plugin-settings-group-2', 'jimmysoft_product_first_coupon_value' );
	register_setting( 'my-plugin-settings-group-2', 'jimmysoft_product_first_email_message' );
	register_setting( 'my-plugin-settings-group-2', 'jimmysoft_product_second_email' );
	register_setting( 'my-plugin-settings-group-2', 'jimmysoft_product_second_subject' );
	register_setting( 'my-plugin-settings-group-2', 'jimmysoft_product_second_coupon_value' );
	register_setting( 'my-plugin-settings-group-2', 'jimmysoft_product_second_message' );
	register_setting( 'my-plugin-settings-group-2', 'jimmysoft_product_fixed_amount_coupon' );
	register_setting( 'my-plugin-settings-group-2', 'jimmysoft_exclude_these_products' );	
}

function my_plugin_settings_page_2() {
?>
<div class="wrap">
<h2>Onboarding emails</h2>
<form method="post" action="options.php">
    <?php settings_fields( 'my-plugin-settings-group-2' ); ?>
    <?php do_settings_sections( 'my-plugin-settings-group-2' ); ?>	
    <table class="form-table">
	    <tr valign="top">
		<input name="jimmysoft_product_fixed_amount_coupon" type="checkbox" value="1" id="fixed_amount_coupon"<?php checked( '1', get_option( 'jimmysoft_product_fixed_amount_coupon' ) ); ?> /> 
		<label for="fixed_amount_coupon">Use fixed amount coupon instead of percentage?</label>
		 <tr valign="top">
        <th scope="row">Exclude these products</th>
        <td><input type="text" name="jimmysoft_exclude_these_products" value="<?php echo esc_attr( get_option('jimmysoft_exclude_these_products') ); ?>" /></td>
        </tr>  
        <tr valign="top">
        <th scope="row">First Email Interval</th>
        <td><input type="text" name="jimmysoft_product_first_email" value="<?php echo esc_attr( get_option('jimmysoft_product_first_email') ); ?>" /></td>
        </tr>  
		<tr valign="top">
        <th scope="row">Email subject</th>
        <td><input style="width: 630px;" type="text"  name="jimmysoft_product_first_email_subject" value="<?php echo esc_attr( get_option('jimmysoft_product_first_email_subject') ); ?>" /></td>
        </tr> 
		<tr valign="top">
        <th scope="row">Coupon Value</th>
        <td><input type="text"  name="jimmysoft_product_first_coupon_value" value="<?php echo esc_attr( get_option('jimmysoft_product_first_coupon_value') ); ?>" /></td>
        </tr> 
		<tr valign="top">
        <th scope="row">First Email</th>	
        <td><textarea class="text" cols="100" rows ="20" name="jimmysoft_product_first_email_message" ><?php echo esc_attr( get_option('jimmysoft_product_first_email_message') ); ?></textarea></td>
        </tr>   		
	</table>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Second Email Interval</th>
        <td><input type="text" name="jimmysoft_product_second_email" value="<?php echo esc_attr( get_option('jimmysoft_product_second_email') ); ?>" /></td>
        </tr>  
		<tr valign="top">
        <th scope="row">Email subject</th>
        <td><input style="width: 630px;" type="text"  name="jimmysoft_product_second_subject" value="<?php echo esc_attr( get_option('jimmysoft_product_second_subject') ); ?>" /></td>
        </tr> 
		<tr valign="top">
        <th scope="row">Coupon Value</th>
        <td><input type="text"  name="jimmysoft_product_second_coupon_value" value="<?php echo esc_attr( get_option('jimmysoft_product_second_coupon_value') ); ?>" /></td>
        </tr> 
		<tr valign="top">
        <th scope="row">Second Email</th>	
        <td><textarea class="text" cols="100" rows ="20" name="jimmysoft_product_second_message" ><?php echo esc_attr( get_option('jimmysoft_product_second_message') ); ?></textarea></td>
        </tr>   	
	</table>	
	<?php submit_button(); ?>	
</form>
</div>

<?php } ?>
