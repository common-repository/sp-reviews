<?php

if ( !defined('ABSPATH')) exit; // Exit if accessed directly

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit ();
}

delete_option('spr_listingid');
delete_option('spr_business_name');
delete_option('spr_my_city');
delete_option('spr_my_state');
delete_option('spr_advertiser');
delete_option('locate_listing');
delete_option('widget_spr_widget');
delete_transient('spr_saved_cipher');

$spruploads = wp_upload_dir();
if (file_exists($spruploads['basedir'] . '/spdetail.xml')) {
	return unlink($spruploads['basedir'] . '/spdetail.xml');
}
?>