<?php
/**
 * Holds the Woocommerce Interface code.
 *
 * @package wpdkPlugin\Woo
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2015 - 2016 Charleston Software Associates, LLC
 *
 * Text Domain: wp-dev-kit
 *
 * @property        boolean     $woo_active                 True if any WooCommerce version is active.
 * @property        boolean     $subscriptions_active       True if any WooCommerce Subscriptions version is active.
 *
 */
class wpdkPlugin_Woo extends WPDK_BaseClass_Object {
    public  $woo_active;
    public  $subscriptions_active;

    /**
     * Make Me.
     */
    function initialize() {
        $this->woo_active = class_exists( 'WooCommerce' );
        $this->subscriptions_active = class_exists( 'WC_Subscriptions' );
	    $this->add_hooks_and_filters();
    }

	/**
	 * Add WooCommerce specific hooks and filters.
	 */
    private function add_hooks_and_filters() {
    	add_filter( 'woocommerce_account_downloads_columns' , array( $this, 'add_version_to_download_table' ) );
    	// if has_action ... 'woocommerce_account_downloads_column_' . $column_id            // create an action of this name to trigger output on downloads page
    }

	/**
	 * Add the download version column to the downloads table.
	 *
	 * @param array $header_array
	 *
	 * @return array
	 */
    public function add_version_to_download_table( $header_array ) {
    	$revised_array = array();
    	foreach ( $header_array as $slug => $text ) {
    		if ( $slug == 'download-actions' ) {
			    $revised_array['download-version'] = __( 'Version' , 'wp-dev-kit' );
			    add_action( 'woocommerce_account_downloads_column_' . 'download-version' , array( $this , 'display_download_version' ) );
		    }
		    $revised_array[$slug] = $text;
	    }
    	return $revised_array;
    }

	/**
	 * Display the download version of the product.
	 */
    public function display_download_version( $download ) {
	    $this->addon->create_object_PluginMeta();
	    $_plugin_meta = $this->addon->PluginMeta->get_meta_by_file( $download[ 'file' ][ 'file' ] );

	    if ( ! is_null( $_plugin_meta ) ) {
	    	echo sprintf( '<span class="new_version">%s</span><span class="last_updated">%s</span>' ,
			    $_plugin_meta['production']['new_version'] ,
			    $_plugin_meta['production']['last_updated']
		    );
	    } else {
	    	echo '&nbsp;';
	    }
    }

    /**
     * Validate the subscription ID is in the proper <order-id>_<product-id> format.
     *
     * @param $sid
     *
     * @return bool
     */
    function is_valid_subscription_id_format( $sid ) {
        if ( empty ( $sid ) ) {
            return false;
        }
        $order_and_product_id = explode( '_' , $sid );
        if ( count( $order_and_product_id ) !== 2 ) {
            return false;
        }
        if ( empty( $order_and_product_id[0] ) || empty ( $order_and_product_id[1] ) ){
            return false;
        }
        return true;
    }

    /**
     * Display the subscription info block.
     */
    function get_subscription_id() {
        if ( ! $this->subscriptions_active )                { return new WP_Error( 'no_woo' , __('Woo Subscriptions not active.'        , 'wp-dev-kit') ); }

        if ( ! class_exists('WC_Subscriptions_Manager') )   { return new WP_Error( 'no_woo' , __('Woo Subscription Manager is missing.' , 'wp-dev-kit') ); }

        if ( ! wcs_user_has_subscription( $this->addon->UI->current_uid , $this->addon->options['subscription_product_id'] , 'active' ) ) {
            new WP_Error( 'no_woo' , sprintf( __('Not active subscription found for product id %d.', 'wp-dev-kit') , $this->addon->options['subscription_product_id'] ) );
        }

        $subscriptions = wcs_get_users_subscriptions( $this->addon->UI->current_uid );
        foreach ( $subscriptions as $subscription ) {
            if ( $subscription->get_status() !== 'active' ) { continue; }
            return wcs_get_old_subscription_key( $subscription );

        }

        return   new WP_Error( 'no_woo' , __('None of your subscription orders are active.'        , 'wp-dev-kit') );
    }

    /**
     * Validate the subscription.  Returns true if the passed in SID and UID match a valid subscription.
     *
     * @param string $uid
     * @param string $sid
     *
     * @return bool
     */
    function validate_subscription( $uid , $sid ) {
        if ( empty( $uid ) ) { return new WP_Error( 'wpdk_empty_uid' , __('User ID empty.', 'wp-dev-kit') , array( 'status' => 404 ) ); }
        if ( ! $this->is_valid_subscription_id_format( $sid ) ) { return new WP_Error( 'wpdk_invalid_sid' , __('Subscription ID is not valid.', 'wp-dev-kit') , array( 'status' => 404 ) ); }

        $subscription = WC_Subscriptions_Manager::get_subscription( $sid );
        if ( ! is_array( $subscription )            ) { return false; }
        if ( ! isset( $subscription['status'    ] ) ) { return false; }
        if ( ! isset( $subscription['product_id'] ) ) { return false; }

        return wcs_user_has_subscription( $uid , $subscription['product_id'] , 'active' );
    }

}
