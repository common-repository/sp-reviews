<?php
   /*
   Plugin Name: SP Reviews
   Plugin URI: http://www.supermedia.com/plugins/spreviews/
   Description: SP Reviews from Superpages.com is a quick and easy way to grab ratings and reviews from your Superpages.com business listing and add them to your website. To get started, just click on <strong>SP Reviews</strong> in the side panel to the left.
   Version: 1.0
   Author: SuperMedia
   Author URI: http://www.supermedia.com

   License: GPLv2 or later
   License URI: http://www.gnu.org/licenses/gpl-2.0.html

   Copyright 2013  SuperMedia  (email : wp@superpages.com)
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License, version 2, as
   published by the Free Software Foundation.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( !defined('ABSPATH')) exit; // Exit if accessed directly

// save the xml file
$spruploads = wp_upload_dir();
$sprcache_detail = $spruploads['basedir'] . '/spdetail.xml';

//main plugin class, initialized right after declaration
class spreview_tabs_layout {

	//declared the keys, tabs array which is populated when registering settings
	private $locate_listing_key = 'locate_listing';
	private $select_listing_key = 'select_listing';
	private $plugin_options_key = 'spr_plugin_options';
	private $plugin_settings_tabs = array();
	private $page_array = array( 'locate_listing', 'select_listing' );

	//Fired during plugins_loaded
	function __construct() {
		add_action( 'init', array( $this, 'load_settings' ) );
		add_action( 'admin_init', array( $this, 'register_locate_listing' ) );
		add_action( 'admin_init', array( $this, 'register_select_listing' ) );
		add_action( 'admin_menu', array( $this, 'superpages_reviews_admin_menu' ) );
	}

	//Loads both the tab settings from the database into their respective arrays.
	function load_settings() {
		$this->locate_listing = (array) get_option( $this->locate_listing_key );
		$this->select_listing = (array) get_option( $this->select_listing_key );

		// Merge with defaults
		$this->locate_listing = array_merge( array(
			'spreview_bname' => '',
			'spreview_city' => '',
			'spreview_state' => ''
		), $this->locate_listing );
	}

	//Registers the locate local listing settings via the Settings API, appends the setting to the tabs array of the object.
	function register_locate_listing() {
		$this->plugin_settings_tabs[$this->locate_listing_key] = __('1) Locate Listing', 'spr');
		register_setting( $this->locate_listing_key, $this->locate_listing_key, 'wp_kses_post');
		add_settings_section( 'locate_section', __('Step 1: Locate Your Business Listing', 'spr'), array( $this, 'locate_section_desc' ), $this->locate_listing_key );
		add_settings_field( 'spreview_bname', __('Business Name', 'spr'), array( $this, 'field_bname_option' ), $this->locate_listing_key, 'locate_section' );
		add_settings_field( 'spreview_city', __('City', 'spr'), array( $this, 'field_city_option' ), $this->locate_listing_key, 'locate_section' );
		add_settings_field( 'spreview_state', __('State', 'spr'), array( $this, 'field_state_option' ), $this->locate_listing_key, 'locate_section' );
	}

	//Registers the select business listing settings and appends the key to the plugin settings tabs array.
	function register_select_listing() {
		$this->plugin_settings_tabs[$this->select_listing_key] = __('2) Select Listing', 'spr');
		register_setting( $this->select_listing_key, $this->select_listing_key, 'wp_kses_post');
		add_settings_section( 'select_section', __('Step 2: Select Your Business Listing', 'spr'), array( $this, 'select_section_desc' ), $this->select_listing_key );
		register_setting( 'select_section', 'spr_listingid', 'sanitize_text_field');
		register_setting( 'select_section', 'spr_business_name', 'sanitize_text_field');
		register_setting( 'select_section', 'spr_my_city', 'sanitize_text_field');
		register_setting( 'select_section', 'spr_my_state', 'sanitize_text_field');
		register_setting( 'select_section', 'spr_advertiser', 'sanitize_text_field');
	}

	//provide descriptions for their respective sections, used as callbacks with add_settings_section
	function locate_section_desc() { _e('First thing we need to do is see if you already have a business listing on Superpages.com.  Enter your business name, city, and state in the boxes below and then click <strong>Find It.</strong>', 'spr'); }
	function select_section_desc() { _e('Look for your business in the list below and click <strong>Select This Listing</strong> to select it. Then select <strong>Appearance</strong> -> <strong>Widgets</strong> in the side panel to the left to place the SP Reviews widget on your website.<br /><br /> If you can\'t find your business in the list, check out the <strong>Troubleshooting Tips</strong> to the right.', 'spr'); }

	//locate listing field callback, renders a text input, note the name and value.
	function field_bname_option() {
		?>
	<input name="<?php echo esc_attr( $this->locate_listing_key.'[spreview_bname]'); ?>" type="text" value="<?php if (isset ( $this->locate_listing['spreview_bname'] )) { echo esc_attr( $this->locate_listing['spreview_bname'] ); } ?>" />

		<?php
	}

	//locate listing field callback, renders a text input, note the name and value.
	function field_city_option() {
		?>
           	<input name="<?php echo esc_attr( $this->locate_listing_key.'[spreview_city]'); ?>" type="text" value="<?php if (isset ( $this->locate_listing['spreview_city'] )) { echo esc_attr( $this->locate_listing['spreview_city'] ); } ?>" />
		<?php
	}

	//locate listing field callback, renders a text input, note the name and value.
	function field_state_option() {
		?>
           	<select name="<?php echo esc_attr( $this->locate_listing_key.'[spreview_state]'); ?>">
	<option value="" <?php selected($this->locate_listing['spreview_state'], '' ); ?>><?php echo _x('(Choose)', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('AL'); ?>" <?php selected($this->locate_listing['spreview_state'], 'AL' ); ?>><?php echo _x('Alabama', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('AK'); ?>" <?php selected($this->locate_listing['spreview_state'], 'AK' ); ?>><?php echo _x('Alaska', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('AZ'); ?>" <?php selected($this->locate_listing['spreview_state'], 'AZ' ); ?>><?php echo _x('Arizona', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('AR'); ?>" <?php selected($this->locate_listing['spreview_state'], 'AR' ); ?>><?php echo _x('Arkansas', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('CA'); ?>" <?php selected($this->locate_listing['spreview_state'], 'CA' ); ?>><?php echo _x('California', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('CO'); ?>" <?php selected($this->locate_listing['spreview_state'], 'CO' ); ?>><?php echo _x('Colorado', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('CT'); ?>" <?php selected($this->locate_listing['spreview_state'], 'CT' ); ?>><?php echo _x('Connecticut', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('DE'); ?>" <?php selected($this->locate_listing['spreview_state'], 'DE' ); ?>><?php echo _x('Delaware', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('DC'); ?>" <?php selected($this->locate_listing['spreview_state'], 'DC' ); ?>><?php echo _x('D.C.', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('FL'); ?>" <?php selected($this->locate_listing['spreview_state'], 'FL' ); ?>><?php echo _x('Florida', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('GA'); ?>" <?php selected($this->locate_listing['spreview_state'], 'GA' ); ?>><?php echo _x('Georgia', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('HI'); ?>" <?php selected($this->locate_listing['spreview_state'], 'HI' ); ?>><?php echo _x('Hawaii', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('ID'); ?>" <?php selected($this->locate_listing['spreview_state'], 'ID' ); ?>><?php echo _x('Idaho', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('IL'); ?>" <?php selected($this->locate_listing['spreview_state'], 'IL' ); ?>><?php echo _x('Illinois', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('IN'); ?>" <?php selected($this->locate_listing['spreview_state'], 'IN' ); ?>><?php echo _x('Indiana', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('IA'); ?>" <?php selected($this->locate_listing['spreview_state'], 'IA' ); ?>><?php echo _x('Iowa', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('KS'); ?>" <?php selected($this->locate_listing['spreview_state'], 'KS' ); ?>><?php echo _x('Kansas', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('KY'); ?>" <?php selected($this->locate_listing['spreview_state'], 'KY' ); ?>><?php echo _x('Kentucky', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('LA'); ?>" <?php selected($this->locate_listing['spreview_state'], 'LA' ); ?>><?php echo _x('Louisiana', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('ME'); ?>" <?php selected($this->locate_listing['spreview_state'], 'ME' ); ?>><?php echo _x('Maine', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('MD'); ?>" <?php selected($this->locate_listing['spreview_state'], 'MD' ); ?>><?php echo _x('Maryland', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('MA'); ?>" <?php selected($this->locate_listing['spreview_state'], 'MA' ); ?>><?php echo _x('Massachusetts', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('MI'); ?>" <?php selected($this->locate_listing['spreview_state'], 'MI' ); ?>><?php echo _x('Michigan', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('MN'); ?>" <?php selected($this->locate_listing['spreview_state'], 'MN' ); ?>><?php echo _x('Minnesota', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('MS'); ?>" <?php selected($this->locate_listing['spreview_state'], 'MS' ); ?>><?php echo _x('Mississippi', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('MO'); ?>" <?php selected($this->locate_listing['spreview_state'], 'MO' ); ?>><?php echo _x('Missouri', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('MT'); ?>" <?php selected($this->locate_listing['spreview_state'], 'MT' ); ?>><?php echo _x('Montana', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('NE'); ?>" <?php selected($this->locate_listing['spreview_state'], 'NE' ); ?>><?php echo _x('Nebraska', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('NV'); ?>" <?php selected($this->locate_listing['spreview_state'], 'NV' ); ?>><?php echo _x('Nevada', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('NH'); ?>" <?php selected($this->locate_listing['spreview_state'], 'NH' ); ?>><?php echo _x('New Hampshire', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('NJ'); ?>" <?php selected($this->locate_listing['spreview_state'], 'NJ' ); ?>><?php echo _x('New Jersey', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('NM'); ?>" <?php selected($this->locate_listing['spreview_state'], 'NM' ); ?>><?php echo _x('New Mexico', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('NY'); ?>" <?php selected($this->locate_listing['spreview_state'], 'NY' ); ?>><?php echo _x('New York', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('NC'); ?>" <?php selected($this->locate_listing['spreview_state'], 'NC' ); ?>><?php echo _x('North Carolina', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('ND'); ?>" <?php selected($this->locate_listing['spreview_state'], 'ND' ); ?>><?php echo _x('North Dakota', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('OH'); ?>" <?php selected($this->locate_listing['spreview_state'], 'OH' ); ?>><?php echo _x('Ohio', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('OK'); ?>" <?php selected($this->locate_listing['spreview_state'], 'OK' ); ?>><?php echo _x('Oklahoma', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('OR'); ?>" <?php selected($this->locate_listing['spreview_state'], 'OR' ); ?>><?php echo _x('Oregon', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('PA'); ?>" <?php selected($this->locate_listing['spreview_state'], 'PA' ); ?>><?php echo _x('Pennsylvania', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('RI'); ?>" <?php selected($this->locate_listing['spreview_state'], 'RI' ); ?>><?php echo _x('Rhode Island', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('SC'); ?>" <?php selected($this->locate_listing['spreview_state'], 'SC' ); ?>><?php echo _x('South Carolina', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('SD'); ?>" <?php selected($this->locate_listing['spreview_state'], 'SD' ); ?>><?php echo _x('South Dakota', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('TN'); ?>" <?php selected($this->locate_listing['spreview_state'], 'TN' ); ?>><?php echo _x('Tennessee', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('TX'); ?>" <?php selected($this->locate_listing['spreview_state'], 'TX' ); ?>><?php echo _x('Texas', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('UT'); ?>" <?php selected($this->locate_listing['spreview_state'], 'UT' ); ?>><?php echo _x('Utah', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('VT'); ?>" <?php selected($this->locate_listing['spreview_state'], 'VT' ); ?>><?php echo _x('Vermont', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('VA'); ?>" <?php selected($this->locate_listing['spreview_state'], 'VA' ); ?>><?php echo _x('Virginia', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('WA'); ?>" <?php selected($this->locate_listing['spreview_state'], 'WA' ); ?>><?php echo _x('Washington', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('DC'); ?>" <?php selected($this->locate_listing['spreview_state'], 'DC' ); ?>><?php echo _x('Washington D.C.', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('WV'); ?>" <?php selected($this->locate_listing['spreview_state'], 'WV' ); ?>><?php echo _x('West Virginia', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('WI'); ?>" <?php selected($this->locate_listing['spreview_state'], 'WI' ); ?>><?php echo _x('Wisconsin', 'usa states'); ?></option>
    <option value="<?php esc_attr_e('WY'); ?>" <?php selected($this->locate_listing['spreview_state'], 'WY' ); ?>><?php echo _x('Wyoming', 'usa states'); ?></option>
  </select>
		<?php
	}

	//adds menu page under admin menu
	function superpages_reviews_admin_menu() {
		add_menu_page(__('Superpages.com Business Listing Reviews', 'spr'), __('SP Reviews'), 'activate_plugins', $this->plugin_options_key, array( $this, 'plugin_options_page' ), plugins_url('images/spr.png', __FILE__) );
	}

	//checks for active tab and replaces key with the related settings key. Uses the plugin_options_tabs method to render the tabs.
	function plugin_options_page() {
		echo '<link type="text/css" rel="stylesheet" href="' .plugins_url( 'css/spreview.min.css' , (__FILE__) ). '" />';
		$tab = isset( $_REQUEST['tab']) && in_array($_REQUEST['tab'], $this->page_array) ? $_REQUEST['tab'] : $this->locate_listing_key;
		$tab_updated = isset( $_REQUEST['settings-updated'] ) ? true : false;
		$tab = ($tab_updated && $tab == 'locate_listing')? 'select_listing' : $tab;
		if ($tab == $this->locate_listing_key && !$tab_updated) {
		?>
		<div class="sprwrap">
			<?php $this->plugin_options_tabs(); ?>
			<form method="post" action="options.php">
				<?php wp_nonce_field( 'update-options' ); ?>
				<?php settings_fields( $tab ); ?>
				<?php do_settings_sections( $tab ); ?>
				<input type="submit" name="submit" value="<?php esc_attr_e('Find It') ?>" class="button-primary" />
            </form>
        </div>
        <div class="sprwrapright">
        <?php spreview_sidebar_one(); ?>
        </div><div class="clear"></div>
		<?php } else if ($tab == $this->select_listing_key) {
			?>
			<div class="sprwrap">
				<?php $this->plugin_options_tabs(); ?>
				<?php do_settings_sections( $tab ); ?>
                <?php spreview_listings(); ?>
			</div>
            <div class="sprwrapright">
        	<?php spreview_sidebar_two(); ?>
        	</div><div class="clear"></div>
		<?php }
	}

	//Renders tabs in the plugin options page. Provides the heading for the plugin_options_page method.
	function plugin_options_tabs() {
		$current_tab = isset( $_REQUEST['tab']) && in_array($_REQUEST['tab'], $this->page_array) ? $_REQUEST['tab'] : $this->locate_listing_key;
		$tab_updated = isset( $_REQUEST['settings-updated'] ) ? true : false;
		$current_tab = ($tab_updated && $current_tab == 'locate_listing')? 'select_listing' : $current_tab;
		echo '<h2 class="nav-tab-wrapper">';
		if (isset($this->locate_listing['spreview_bname']) && ($this->locate_listing['spreview_city']) && ($this->locate_listing['spreview_state'])) {
			foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
				$active = $current_tab == $tab_key ? 'nav-tab-active' : 'nav-tab-inactive';
				echo '<a class="nav-tab ' . $active . '" href="' . esc_url( admin_url( '?page='. $this->plugin_options_key .'&tab='. $tab_key) ) . '">' . __($tab_caption, 'spr') . '<span id="icon-superpages-reviews" class="spr-' . $tab_key . '"></span></a>';
			}
		} else { //hide second tab until form is filled out
				$active = $current_tab == 'locate_listing' ? 'nav-tab-active' : '';
				echo '<a class="nav-tab ' . $active . '" href="' . esc_url( admin_url( '?page='. $this->plugin_options_key . '&tab=locate_listing') ) . '">' . __('1) Locate Listing', 'spr') . '<span id="icon-superpages-reviews" class="spr-locate_listing"></span></a>';
		}
		echo '</h2>';
	}
}; //spreview_tabs_layout

// Initialize the plugin
add_action( 'plugins_loaded', create_function( '', '$spreview_tabs_layout = new spreview_tabs_layout;' ) );

/*---------------------CACHING FUNCTION FOR RESET--------------------- */
//just in case the wrong business was choosen the first time
function sprcachingfunc() {	
	global $sprcache_detail;
	$ageInSeconds = 0; // reset
	
	// generate the cache version if it doesn't exist or it's too old!
	if(!file_exists($sprcache_detail) || (filemtime($sprcache_detail) + $ageInSeconds) < time()) {
		$detailapi = esc_url_raw('http://api.superpages.com/xml/detail?LID=' .get_option('spr_listingid') . '&SRC=organicreview&oro=true&SWB=1&XSL=OFF');
		$wrg_detail = wp_remote_get($detailapi);
		if ( 200 == $wrg_detail['response']['code'] ) {
			$readdetailxml = $wrg_detail['body'];
			$spdetailxml = simplexml_load_string($readdetailxml);
			$spdetailxml->asXml($sprcache_detail);
		} else {
			$spruploads = wp_upload_dir();
			if (file_exists($spruploads['basedir'] . '/spdetail.xml')) {
				return unlink($spruploads['basedir'] . '/spdetail.xml');
			}
		}
	}
}

add_action( 'callsprxml', 'sprcachingfunc');

/*---------------------WIDGET---------------------*/
include_once dirname( __FILE__ ) . '/spr-widget.php';

/*---------------------REVIEW SHORTCODE---------------------*/
include_once dirname( __FILE__ ) . '/spr-shortcode.php';

/*---------------------SECOND TAB CONTENT - SUPERPAGES REVIEW LISTINGS---------------------*/
function spreview_listings() {
	$locate_settings = get_option('locate_listing');
	$spreview_bname = $locate_settings['spreview_bname'];
	$spreview_bname = str_replace(array("'", "&#039;"), '', $spreview_bname);
	$spreview_bname = urlencode(stripslashes($spreview_bname));
	$spreview_city = $locate_settings['spreview_city'];
	$spreview_city = urlencode(stripslashes($spreview_city));
	$spreview_state = $locate_settings['spreview_state'];
	
	$src = 'organicreview';
	$search_api = esc_url_raw('http://api.superpages.com/xml/search?N=' . $spreview_bname . '&T=' . $spreview_city . '&S=' . $spreview_state . '&R=N&SRC=' . $src . '&STYPE=S&search=Find+It&XSL=OFF');
	$wrg_search = wp_remote_get($search_api);
	
	if ( 200 == $wrg_search['response']['code'] ) { //if feed exists
		$readsearchxml = $wrg_search['body'];
		$sitemap = simplexml_load_string($readsearchxml);

		$i = 0;
		$review_stars = "images/";
		
		if ($sitemap->listingPage->listing[0] == null) { //no results found ?>
			<div id="spreviews-no">
			<span class="noresults"><?php _e('No results found for', 'spr'); ?> <strong><?php echo ucfirst($locate_settings['spreview_bname']); ?></strong> <?php _e('in', 'spr'); ?> 
            <strong><?php echo ucfirst($locate_settings['spreview_city']); ?></strong>.</span>
			<span><?php _e('To try your search again, click the Locate Listing tab at the top of the page.', 'spr'); ?></span>
    		</div>		
		<?php } else  { //results found 
			echo '<div id="spreviews">';
    		echo '<p>' . __('Found', 'spr') . ' <strong>' . __(count($sitemap->listingPage->listing), 'spr') . '</strong> ' . __('results for', 'spr') .  
        		' <strong>' . ucfirst($locate_settings['spreview_bname']) . '</strong>.</p>';
    		echo '<ul>';
		
			foreach ($sitemap->listingPage->listing as $listings) { //display business listings
				//save button code for widget
				$spradvertiser = $listings->attributes()->advertiser;
				$mylid = $listings->attributes()->lid;
				$mybname = $listings->lDetail->name;
				$mycity = $listings->lDetail->addr->city;
				$mystate = $listings->lDetail->addr->state;
				$getoplid = get_option('spr_listingid'); ?>
			
            	<li<?php if ((isset($getoplid)) && ($getoplid == $mylid)) { echo ' id="listresults"'; } ?>> 
				<?php
					echo '<div class="floatLeft">';
					echo '<span><strong>' . __($listings->lDetail->name, 'spr') . '</strong></span>';
					
					if(isset($listings->lDetail->ph) && !empty($listings->lDetail->ph)){
					
						if(is_array($listings->lDetail->ph) || isset($listings->lDetail->ph[0])){
							foreach($listings->lDetail->ph as $phone){
								echo '<span>(' . __($phone->area, 'spr') . ') ' . __($phone->exch, 'spr') . '-' . __($phone->line, 'spr') . ' ' . __($phone->attributes()->text, 'spr') . '</span>';
							}
						} else {
							echo '<span>(' . __($listings->lDetail->ph->area, 'spr') . ')' . __($listings->lDetail->ph->exch, 'spr') . '-' . __($listings->lDetail->ph->line, 'spr') . '</span>';
						}
					}
					
					if ($listings->lDetail->ad->tag) {
						$spradtag = $listings->lDetail->ad->url;
						echo '<span><a href="' . esc_url($spradtag) . '" target="_blank">' . __($listings->lDetail->ad->tag, 'spr') . '</a></span>';
					}
					
					if ($listings->lDetail->addr ) {
						echo '<span>' . __($listings->lDetail->addr->st, 'spr') . '</span>';
					}
			
					if ($listings->lDetail->addr ) {
						echo '<span>' . __($listings->lDetail->addr->city, 'spr') . ', ' . __($listings->lDetail->addr->state, 'spr') . ' ' . __($listings->lDetail->addr->zip, 'spr') . '</span>';
					}
					
					echo '</div>'; //end of floatLeft
					echo '<div class="floatRight">';
					if ($listings->rating ) {
						$starimg =  $review_stars . $listings->rating->avgRating . '_star.png';
						$spstars = plugins_url( $starimg, (__FILE__) );
						echo '<img src="' . esc_url($spstars) . '" alt="' . esc_attr('Star Rating', 'spr') . '" /> ';
						echo ' ' . __($listings->rating->ratingCount, 'spr') . __(' Reviews', 'spr');
					} else {
						echo '<span>' . __('Not Yet Rated', 'spr') . '</span>';
					}
					
					if ((isset($getoplid)) && ($getoplid == $mylid)) { //if LIDs are the same, you'll have multiple saved options ?>
						<script type="text/javascript">
     					//<![CDATA[
			 			jQuery(document).ready(function($) {
			 				jQuery("#submitresults").addClass("spsavedbutton");
             				jQuery("#submitresults").val("<?php echo esc_js(__('Selected', 'spr')); ?>");
							jQuery("#listresults").addClass("sprselected");
             			});
			 			//]]>
			 		</script>
				<?php }
				
				//clear transient and xml file
            	if ( isset($_GET['settings-updated'])) {
					if ($transient = get_transient( 'spr_saved_cipher' )) {
						delete_transient( 'spr_saved_cipher' );
					}
					
					global $sprcache_detail;
					if (file_exists($sprcache_detail)) { do_action('callsprxml'); }
				} ?>
        
        		<form method="post" id="mylisting_<?php echo $i; ?>" action="options.php">
            	<?php settings_fields( 'select_section' ); ?>
				<input type="hidden" name="spr_advertiser" id="spr_advertiser" value="<?php echo esc_attr($spradvertiser); ?>" />
                <input type="hidden" name="spr_listingid" id="spr_listingid" value="<?php echo esc_attr($mylid); ?>" />
        		<input type="hidden" name="spr_business_name" id="spr_business_name" value="<?php echo esc_attr($mybname); ?>" />
        		<input type="hidden" name="spr_my_city" id="spr_my_city" value="<?php echo esc_attr($mycity); ?>" />
        		<input type="hidden" name="spr_my_state" id="spr_my_state" value="<?php echo esc_attr($mystate); ?>" />
				<input type="submit" name="submit" value="<?php esc_attr_e('Select This Listing'); ?>" class="button-secondary" <?php if ((isset($getoplid)) && ($getoplid == $mylid)) { echo 'id="submitresults"'; } ?> />
        		</form>
                <?php
					
					echo '</div>'; //end of floatRight
					echo '<div class="clear"></div></li>';
					$i++;	
					
			} //end of foreach
		
        	echo '</ul></div>';
		} //end of if results 

	} else { //feed doesnt exist 
	echo '<div id="spreviews-no"><span>' . __('Oh no! There was a problem. The feed might be unavailable at this time, or the hosting provider may 
    not have allow_url_fopen enabled.', 'spr') . '</span> <span>' . __('If problem persists contact', 'spr') . ' <a href="' . esc_url('mailto:wp@superpages.com') . '">wp@superpages.com</a>.</span></div>';
	} 	
}

/*---------------------SIDEBAR---------------------*/
function spreview_sidebar_one() {
	$thankyou = __('Thank You!', 'spr');
	$supermedia = __('Free Consultation', 'spr');
	$smtext = __('At SuperMedia, we\'re focused on partnering with local businesses like yours to help them connect with customers and build the right presence.', 'spr');
	$smfree = __('Contact us for a free consultation and find out what SuperMedia can do for you!', 'spr');
	$smphone = __('Call Us <strong>855-234-2997</strong>', 'spr');
	$smhours = __('M - F, 7am - 6pm CST', 'spr');
	$smlink = __('Or visit our website!', 'spr');
	$text1 = __('Thanks for downloading the SP Reviews plugin from Superpages.com!', 'spr');
	$support = __('If you have any questions or comments about this plugin, please drop us a line at', 'spr');
	$supportemail = __('wp@superpages.com', 'spr');

	$spcomfb = __('Superpages.com Facebook', 'spr');
	$spcomtw = __('Superpages.com Twitter', 'spr');
	$spcomgp = __('Superpages.com Google+', 'spr');
	$spcompn = __('Superpages.com Pinterest', 'spr');

	$aboutus = __('About Superpages.com', 'spr');
	$abouttext = __('Superpages.com is the local business listing expert. Each month, we help millions of consumers find the local business information they\'re looking for—from driving directions and hours of operation to web links and customer reviews.', 'spr');
	$superpageslogo = plugins_url('images/sp-logo.png', __FILE__);

	?>
<div class="postbox-container" style="width:20%; margin-top: 35px; margin-left: 15px;">
	<div class="metabox-holder">
		<div class="meta-box-sortables" style="min-height: 0">
			<div id="spreviews_donate" class="postbox">
			<span id="icon-superpages-reviews" class="spr-thankyou"></span>
			<h3 class="hndle"><span><?php echo $thankyou ?></span></h3>
			<div class="inside">
			<p><?php echo $text1 ?></p>
			<p><?php echo $support ?> <a href="<?php echo esc_url('mailto:wp@superpages.com'); ?>"><?php echo $supportemail ?></a>.</p>
			</div>
		</div>
	</div>

		<div class="meta-box-sortables" style="min-height: 0">
			<div id="spreviews_donate" class="postbox">
			<span id="icon-superpages-reviews" class="spr-social"></span>
			<h3 class="hndle"><span><?php echo $aboutus ?></span></h3>
			<div class="inside">
			<p><?php echo $abouttext ?></p>
			<p class="social-spr"><a href="<?php echo esc_url('http://facebook.com/superpagescom') ?>" target="_blank"><span id="social-spr-icons" class="spr-facebook"></span><?php echo $spcomfb ?></a></p>
			<p class="social-spr"><a href="<?php echo esc_url('http://twitter.com/superpages') ?>" target="_blank"><span id="social-spr-icons" class="spr-twitter"></span><?php echo $spcomtw ?></a></p>
			<p class="social-spr"><a href="<?php echo esc_url('http://plus.google.com/+superpages') ?>" target="_blank"><span id="social-spr-icons" class="spr-google"></span><?php echo $spcomgp ?></a></p>
			<p class="social-spr"><a href="<?php echo esc_url('http://pinterest.com/superpages') ?>" target="_blank"><span id="social-spr-icons" class="spr-pinterest"></span><?php echo $spcompn ?></a></p>
			<a href="<?php echo esc_url('http://www.superpages.com') ?>" target="_blank"><img src="<?php echo esc_url($superpageslogo) ?>" alt="<?php esc_attr_e('Superpages.com', 'spr'); ?>" width="223" heigh="55" class="spr-splogo" /></a>
			</div>
		</div>
	</div>

		<div class="meta-box-sortables" style="min-height: 0">
			<div id="spreviews_donate" class="postbox">
			<h3 class="hndle"><span><?php echo $supermedia ?></span></h3>
			<div class="inside">
			<p><?php echo $smtext ?></p>
			<p><?php echo $smfree ?></p>
			<p style="text-align: center;"><?php echo $smphone ?><br /><?php echo $smhours ?><br />
			<a href="<?php echo esc_url('http://www.supermedia.com/get-started') ?>" target="_blank"><?php echo $smlink ?></a></p>
			</div>
		</div>
	</div>

</div>
<?php
}


function spreview_sidebar_two() {
	$widgetthumb = plugins_url('images/widget-thumb.jpg', __FILE__);
	$feature_title = __('Did You Know?', 'spr');
	$feature1 = __('1) Display Full Reviews', 'spr');
	$feature1txt = __('You can show all of your customer reviews on a webpage or in a blog post by using the <strong>[showspreviews]</strong> shortcode.', 'spr');
	$feature2txta = __('To change the color, simply add a color attribute of <strong>dark-blue</strong>, <strong>light-blue,</strong> or <strong>light-gray</strong>.', 'spr');
	$feature1txta = __('Example:<br /> <strong>[showspreviews color="light-gray"]</strong>', 'spr');
	$feature2 = __('2) Show Average Rating', 'spr');
	$feature2txt = __('You can show your average rating with the SP Reviews widget by adding it to the sidebar of your website. Just select <strong>Appearance</strong> -> <strong>Widgets</strong>, and then follow the onscreen instructions.', 'spr');
	$troubleshoot = __('Troubleshooting Tips', 'spr');
	$troubleshoottxt = __('If your business isn\'t showing up in the list, then that means you\'re not currently listed on Superpages.com. <a href="' . esc_url('http://www.supermedia.com/spportal/quickbpflow.do?src=wprp') . '" target="_blank">Click here</a> to add your information and create a free business listing.', 'spr');
	$troubleshoottxt1 = __('If you have any questions about adding your business or claiming your listing, <a href="' . esc_url('http://www.supermedia.com/business-directory-listing/') . '" target="_blank">click here</a> for more detailed instructions.', 'spr');
	?>
<div class="postbox-container" style="width:20%; margin-top: 35px; margin-left: 15px;">
	<div class="metabox-holder">

	<div class="meta-box-sortables" style="min-height: 0">
			<div id="spreviews_donate" class="postbox">
			<h3 class="hndle"><span><?php echo $troubleshoot ?></span></h3>
			<div class="inside">
			<p><?php echo $troubleshoottxt ?></p>
			<p><?php echo $troubleshoottxt1 ?></p>
			</div>
		</div>
	</div>

		<div class="meta-box-sortables" style="min-height: 0">
			<div id="spreviews_donate" class="postbox">
			<span id="icon-superpages-reviews" class="spr-info"></span>
			<h3 class="hndle"><span><?php echo $feature_title ?></span></h3>
			<div class="inside">
			<p><strong><?php echo $feature1 ?></strong></p>
			<p class="indent"><?php echo $feature1txt ?></p>
			<p class="indent"><?php echo $feature2txta ?></p>
			<p class="indent"><?php echo $feature1txta ?></p>
			<hr class="spr" />
			<p><strong><?php echo $feature2 ?></strong></p>
			<p class="indent"><?php echo $feature2txt ?></p>
			<img src="<?php echo esc_url($widgetthumb); ?>" alt="<?php esc_attr_e('show review average', 'spr'); ?>" width="180" height="101" />
			</div>
		</div>
	</div>
</div>
</div>
<?php
}

?>