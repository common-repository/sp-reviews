<?php
if ( !defined('ABSPATH')) exit; // Exit if accessed directly

//[showspreviews color="dark-blue"]
function superpages_reviews($atts) {
	ob_start();
	
	extract( shortcode_atts( array(
		'color' => 'dark-blue',
	), $atts ) );
	
	
	if ((isset($color)) && ( in_array($color, array( 'dark-blue', 'light-blue', 'light-gray', )))) { 
		//happy dance
	} else {
		$color = 'dark-blue';
	}
	
	add_action( 'wp_enqueue_script', 'load_jquery' );
	function load_jquery() {
    	wp_enqueue_script( 'jquery' );
	}

	wp_enqueue_style('sprfullcss', plugins_url('/sp-reviews/css/sprfullreview.min.css'), '1.0', 'all');
	wp_enqueue_script('jqpajinate', plugins_url('/js/jquery.pajinate.min.js', __FILE__), array('jquery'), '1.0');
	wp_enqueue_script('pagingcontainer', plugins_url('/js/paging_container.min.js', __FILE__), array('jquery'), '1.0');
	
	$getmylid = get_option('spr_listingid');
	$getadvertiser = get_option('spr_advertiser');
	$myuser_bname = get_option('spr_business_name');
	$myuser_bname = str_replace(array("'", "&#039;"), '', $myuser_bname);
	$myuser_bname = urlencode(stripslashes($myuser_bname));
	$myuser_city = get_option('spr_my_city');
	$myuser_city = urlencode(stripslashes($myuser_city));
	
	
	global $sprcache_detail;
	$ageInSeconds = 3600; // one hour
		
	if ((isset($getmylid)) && ($getmylid != null)) {
	
		// generate the cache version if it doesn't exist or it's too old!
		if(!file_exists($sprcache_detail) || (filemtime($sprcache_detail) + $ageInSeconds) < time()) {
			$detailapi = esc_url_raw('http://api.superpages.com/xml/detail?LID=' .get_option('spr_listingid') . '&SRC=organicreview&oro=true&SWB=1&XSL=OFF');
			$wrg_detail = wp_remote_get($detailapi);
		
			if ( 200 == $wrg_detail['response']['code'] ) {
				$readdetailxml = $wrg_detail['body'];
				$spdetailxml = simplexml_load_string($readdetailxml);
				$spdetailxml->asXml($sprcache_detail);
			}
		}
			
		//HTTP API get cipher
		if ( false === ( $transient = get_transient( 'spr_saved_cipher' ) ) ) {
			$listingidapi = esc_url_raw('http://www.superpages.com/cipher/?LID=' .get_option('spr_listingid') . '&key=ka7tblad72kdu');
    		$wrg_list = wp_remote_get($listingidapi);
		
			if ( 200 == $wrg_list['response']['code'] ) {
				$readxml = $wrg_list['body'];
				set_transient('spr_saved_cipher', $readxml, 60*60 );
				$transient = get_transient( 'spr_saved_cipher' );
				$load_transient = simplexml_load_string($transient);
				$myenclid = $load_transient->result[0];
				$myuserrateit = 'http://yellowpages.superpages.com/reviews/userrateit.jsp?N=' . $myuser_bname . '&T=' . $myuser_city . '&S=' .get_option('spr_my_state') . '&LID=' .$myenclid . '&STYPE=S';
				$myuserreviews = 'http://yellowpages.superpages.com/reviews/userreviews.jsp?N=' . $myuser_bname . '&T=' . $myuser_city . '&S=' .get_option('spr_my_state') . '&LID=' .$myenclid . '&PI=1&STYPE=S';	
			}
		} else {
			$transient = get_transient( 'spr_saved_cipher' );
			$load_transient = simplexml_load_string($transient);
			$myenclid = $load_transient->result[0];
			$myuserrateit = 'http://yellowpages.superpages.com/reviews/userrateit.jsp?N=' . $myuser_bname . '&T=' . $myuser_city . '&S=' .get_option('spr_my_state') . '&LID=' .$myenclid . '&STYPE=S';
			$myuserreviews = 'http://yellowpages.superpages.com/reviews/userreviews.jsp?N=' . $myuser_bname . '&T=' . $myuser_city . '&S=' .get_option('spr_my_state') . '&LID=' .$myenclid . '&PI=1&STYPE=S';	
		}
		
		if ($getadvertiser == 'yes') {
			$myuserrateads = 'http://yellowpages.superpages.com/reviews/userrateit.jsp?N=' . $myuser_bname . '&T=' . $myuser_city . '&S=' .get_option('spr_my_state') . '&LID=' .$getmylid . '&STYPE=S';
		}
		
		//checking to see spdetail.xml is available before doing anything
		if ((file_exists($sprcache_detail)) && (0 != filesize( $sprcache_detail ))) { 
			$spdetailload = simplexml_load_file($sprcache_detail);
		
			$widreview_stars = "images/";
			//content for shortcode	
			echo '<div id="spfulltop"><p class="spfullrtitle"><span>' . __('Customer Reviews', 'spr') . '</span> ' . __('from Superpages.com', 'spr') . '</p></div>';
			echo '<div id="paging_container3" class="spr-container">';	
		if ($spdetailload->detailPage->userreviews[0] == null ) { //no reviews available
			echo '<p class="noreviews">' . __('No reviews for ', 'spr') . get_option('spr_business_name') . '.</p>';
		} else {
			echo '<ul id="spfullreview" class="alt_content">';	
			foreach ($spdetailload->detailPage->userreviews->userreview as $userreviews) {
				echo '<li>';
				echo '<div class="userLeft">';
				if ($userreviews->rateaverage ) {
					$starimg =  $widreview_stars . $userreviews->rateaverage . '_star.png';
					$spstars = plugins_url( $starimg, (__FILE__) );
					echo '<img src="' . esc_url($spstars) . '" alt="' . esc_attr($userreviews->rateaverage, 'spr') . esc_attr(' Star Rating', 'spr') . '" />';
				} else {
					echo '<span>' . __('Not Yet Rated', 'spr') . '</span>';
				}
				if (isset($userreviews->signature)) { //make sure cipher file has contents
					if (($transient) && ($getadvertiser == 'no')) {
						echo '<a href="' . esc_url($myuserreviews) . '" target="_blank"><span class="signature">' . __($userreviews->signature, 'spr') . '</span></a>';
					} else {
						echo '<span class="signature">' . __($userreviews->signature, 'spr') . '</span>';
					}
				}
				if ($userreviews->reviewdate) {
					echo '<span>' . date_i18n(get_option('date_format'), strtotime($userreviews->reviewdate)) . '</span>';
					
				}
				echo '</div>';
				echo '<div class="textRight">';
				if ($userreviews->reviewtext) {
					echo '<span>', __($userreviews->reviewtext, 'spr'), '</span>'; 
				}
				echo '</div>';
				echo '<div class="clear"></div>';
				echo '</li>';
			} //end of foreach
		echo '</ul><p class="alt_page_navigation_count">' . __($spdetailload->detailPage->rating->ratingCount, 'spr') . __(' Reviews', 'spr') . '</p>';
		if ($spdetailload->detailPage->rating->ratingCount > 4 ) {
			echo '<div class="alt_page_navigation"></div>';
		}
		echo '<div class="clear"></div>';
		} //end of if reviews
	
		echo '</div>';
		
		$splogo_white = plugins_url( '/images/superpages-white.png', __FILE__ );
		$splogo_color = plugins_url( '/images/superpages-color.png', __FILE__ );
			
		//if dark blue (default)
		if ($color == 'dark-blue') { ?>
        	 <?php if (($transient) && ($getadvertiser == 'no')) { //make sure cipher file has contents ?>
				<div class="spfullrlinks sprdb">
    			<span><a href="<?php echo esc_url($myuserrateit); ?>" target="_blank"><?php _e('Recommend Us', 'spr'); ?></a> | 
    			<a href="<?php echo esc_url($myuserreviews); ?>" target="_blank"><?php _e('Reviews', 'spr'); ?></a></span></div>
            <?php } else if ($getadvertiser == 'yes') { ?>
            <div class="spfullrlinks sprdb">
    			<span><a href="<?php echo esc_url($myuserrateads); ?>" target="_blank"><?php _e('Recommend Us', 'spr'); ?></a></span></div>
                <?php } ?>
			<div id="splogofullr-db">
			<img src="<?php echo esc_url($splogo_white); ?>" width="86" height="20" border="0" style="margin: auto; display: block;" alt="<?php esc_attr_e('Superpages.com', 'spr'); ?>" />
			</div>
        
		<?php //if light blue 
		} if ($color == 'light-blue') { ?>
        	<?php if (($transient) && ($getadvertiser == 'no')) { //make sure cipher file has contents ?>
				<div class="spfullrlinks sprlb">
        		<span><a href="<?php echo esc_url($myuserrateit); ?>" target="_blank"><?php _e('Recommend Us', 'spr'); ?></a> | 
				<a href="<?php echo esc_url($myuserreviews); ?>" target="_blank"><?php _e('Reviews', 'spr'); ?></a></span></div>
            <?php } else if ($getadvertiser == 'yes') { ?>
            <div class="spfullrlinks sprlb">
    			<span><a href="<?php echo esc_url($myuserrateads); ?>" target="_blank"><?php _e('Recommend Us', 'spr'); ?></a></span></div>
                <?php } ?>
			<div id="splogofullr-lb"> 
			<img src="<?php echo esc_url($splogo_color); ?>" width="86" height="20" border="0" style="margin: auto; display: block;" alt="<?php esc_attr_e('Superpages.com', 'spr'); ?>" />
			</div>
	
		<?php //if light gray
		} if ($color == 'light-gray') { ?>
        	<?php if (($transient) && ($getadvertiser == 'no')) { //make sure cipher file has contents ?>
				<div class="spfullrlinks sprlg">
        		<span><a href="<?php echo esc_url($myuserrateit); ?>" target="_blank"><?php _e('Recommend Us', 'spr'); ?></a> | 
				<a href="<?php echo esc_url($myuserreviews); ?>" target="_blank"><?php _e('Reviews', 'spr'); ?></a></span></div>
                 <?php } else if ($getadvertiser == 'yes') { ?>
            <div class="spfullrlinks sprlg">
    			<span><a href="<?php echo esc_url($myuserrateads); ?>" target="_blank"><?php _e('Recommend Us', 'spr'); ?></a></span></div>
                <?php } ?>
			<div id="splogofullr-lg"> 
			<img src="<?php echo esc_url($splogo_color); ?>" width="86" height="20" border="0" style="margin: auto; display: block;" alt="<?php esc_attr_e('Superpages.com', 'spr'); ?>" />
			</div>
	
		<?php } //end of color 		

	} else { //feed is empty
		echo '<p>' . __('Oh no! There was a problem. The feed might be unavailable at this time, or the hosting provider may not have allow_url_fopen enabled. If problem persists contact', 'spr') . ' <a href="' . esc_url('mailto:wp@superpages.com') . '">wp@superpages.com</a>.</p>';
	}
	
	} else { //plugin content not saved
			echo '<p>' . __('No content available. Please check plugin options and save business name, city, and state.', 'spr') . '</p>';
		}

	$myvariable = ob_get_clean();
	return $myvariable;  
}
add_shortcode('showspreviews', 'superpages_reviews'); 
?>
