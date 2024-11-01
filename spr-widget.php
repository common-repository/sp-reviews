<?php
if ( !defined('ABSPATH')) exit; // Exit if accessed directly

class spr_widget extends WP_Widget {
	
	//file constructor
	function __construct() {
		$widget_spreview = apply_filters( 'spr_review_widget_options', array(
			'classname'   => 'widget_sprwid',
			'description' => __( 'Show your average rating from Superpages.com.', 'spr' ),
		) );

		parent::__construct( false, __( 'SP Reviews', 'spr' ), $widget_spreview );
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$mycolor = $instance['spcolor'];
		
		echo $before_widget;
    
		if ($title) {
			echo $before_title . $title . $after_title;
		}
		
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
     	
			echo '<div style="padding: 5px; -webkit-border-radius: 5px 5px 0 0; -moz-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0; border-top:1px solid #cccccc; border-left:1px solid #cccccc; border-right:1px solid #cccccc; background:none; text-align: center !important; background:#FFF; margin-top: 12px;">';
			echo '<p style="margin-bottom:12px;"><strong>' . __('OUR OVERALL RATING IS:', 'spr') . '</strong></p>';
       
			if ($spdetailload->detailPage->rating ) {
				$starimg =  $widreview_stars . $spdetailload->detailPage->rating->avgRating . '_star.png';
				$spstars = plugins_url( $starimg, (__FILE__) );
				echo '<div style="margin-bottom:12px;"><img src="' . esc_url($spstars) . '" alt="' . esc_attr($spdetailload->detailPage->rating->avgRating, 'spr') . esc_attr(' Star Rating', 'spr') . '" border="0" style="box-shadow: none;" />';
				echo '<span style="font-size: 11px; margin-left: 12px;">(' . __($spdetailload->detailPage->rating->ratingCount, 'spr') . ')</span></div>';
			} else {
				echo '<p style="margin-bottom:12px;">' . __('Not Yet Rated', 'spr') . '</p>';
			}
	 
	 		
		if (($transient) && ($getadvertiser == 'no')) { //make sure cipher file has contents 
			
			//color picker
	 		if ($mycolor == 'dark-blue') { ?>
    			<p style="margin-bottom:12px;"><a href="<?php echo esc_url($myuserrateit); ?>" target="_blank" style="color: #00b1ec;"><?php _e('Recommend Us', 'spr') ?></a> | 
				<a href="<?php echo esc_url($myuserreviews); ?>" target="_blank" style="color: #00b1ec;"><?php _e('Reviews', 'spr') ?></a></p>  

     		<?php } else if ($mycolor == 'light-blue') { ?>
     			<p style="margin-bottom:12px;"><a href="<?php echo esc_url($myuserrateit); ?>" target="_blank" style="color: #868686;"><?php _e('Recommend Us', 'spr') ?></a> | 
				<a href="<?php echo esc_url($myuserreviews); ?>" target="_blank" style="color: #868686;"><?php _e('Reviews', 'spr') ?></a></p>
	
			<?php } else if ($mycolor == 'light-gray') { ?>
     			<p style="margin-bottom:12px;"><a href="<?php echo esc_url($myuserrateit); ?>" target="_blank" style="color: #868686;"><?php _e('Recommend Us', 'spr') ?></a> | 
				<a href="<?php echo esc_url($myuserreviews); ?>" target="_blank" style="color: #868686;"><?php _e('Reviews', 'spr') ?></a></p>		
			<?php }
			
		} else if ($getadvertiser == 'yes') {
			//color picker
	 		if ($mycolor == 'dark-blue') { ?>
    			<p style="margin-bottom:12px;"><a href="<?php echo esc_url($myuserrateads); ?>" target="_blank" style="color: #00b1ec;"><?php _e('Recommend Us', 'spr') ?></a></p>  

     		<?php } else if ($mycolor == 'light-blue') { ?>
     			<p style="margin-bottom:12px;"><a href="<?php echo esc_url($myuserrateads); ?>" target="_blank" style="color: #868686;"><?php _e('Recommend Us', 'spr') ?></a></p>
	
			<?php } else if ($mycolor == 'light-gray') { ?>
     			<p style="margin-bottom:12px;"><a href="<?php echo esc_url($myuserrateads); ?>" target="_blank" style="color: #868686;"><?php _e('Recommend Us', 'spr') ?></a></p>		
			<?php }
			
		} //end of make sure cipher file has contents 
		
		?>
    	</div>
        <?php
        $splogo_white = plugins_url( '/images/superpages-white.png', __FILE__ );
		$splogo_color = plugins_url( '/images/superpages-color.png', __FILE__ );
    
    	 //color picker
     	if ($mycolor == 'dark-blue') { ?>
    		<div style="background:#00b1ec; width: auto; padding: 3px 0; -webkit-border-radius: 0 0 5px 5px; -moz-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; border:1px solid #00b1ec; margin-bottom: 12px;">
			<img src="<?php echo esc_url($splogo_white); ?>" width="86" height="21" border="0" style="margin: auto; display: block; box-shadow: none;" alt="<?php esc_attr_e('Superpages.com', 'spr'); ?>" /></div>    

     	<?php } else if ($mycolor == 'light-blue') { ?>
     	<div style="background:#d8f2ff; width: auto; padding: 3px 0; -webkit-border-radius: 0 0 5px 5px; -moz-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; border:1px solid #cccccc; margin-bottom: 12px;">
    	<img src="<?php echo esc_url($splogo_color); ?>" width="86" height="21" border="0" style="margin: auto; display: block; box-shadow: none;" alt="<?php esc_attr_e('Superpages.com', 'spr'); ?>" /></div>  
	
		<?php } else if ($mycolor == 'light-gray') { ?>
    	<div style="background:#efefef; width: auto; padding: 3px 0; -webkit-border-radius: 0 0 5px 5px; -moz-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; border:1px solid #cccccc; margin-bottom: 12px;">
		<img src="<?php echo esc_url($splogo_color); ?>" width="86" height="21" border="0" style="margin: auto; display: block; box-shadow: none;" alt="<?php esc_attr_e('Superpages.com', 'spr'); ?>" /></div>  
		
		<?php }
	
	} else { //feed doesnt exist
		echo '<p>' . __('Oh no! There was a problem. The feed might be unavailable at this time, or the hosting provider may not have allow_url_fopen enabled. If problem persists contact', 'spr') . ' <a href="' . esc_url('mailto:wp@superpages.com') . '">wp@superpages.com</a>.</p>';
	}
	
	} else { //if no content
    		echo '<p>' . __('No content available. Please check plugin options and save business name, city, and state.', 'spr') . '</p>';
    	}
	
	echo $after_widget;
}
  

//deals with the settings when they are saved by the admin.
public function update( $new_instance, $old_instance ) {
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
	//$instance['spcolor'] = strip_tags($new_instance['spcolor']);
	if ((isset($instance['spcolor'])) && ( in_array($instance['spcolor'], array( 'dark-blue', 'light-blue', 'light-gray', )))) { 
		$instance['spcolor'] =  strip_tags($new_instance['spcolor']);
	} else {
		$instance['spcolor'] = 'dark-blue';
		$instance['spcolor'] =  strip_tags($new_instance['spcolor']);
	}
	
	return $new_instance;
}

//output the widget options form
public function form( $instance ) {
	$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
	$mycolor = isset( $instance['spcolor'] ) ? esc_attr( $instance['spcolor'] ) : '';
	?>

	<p>
	<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'spr' ); ?>
	<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php esc_attr_e($title); ?>" />
	</label>
	</p>
    
	<p>
	<label for="<?php echo $this->get_field_id('spcolor'); ?>"><?php _e('Choose color of widget?', 'spr'); ?></label>
	<select id="<?php echo $this->get_field_id('spcolor'); ?>" name="<?php echo $this->get_field_name('spcolor'); ?>">
	<option value="<?php esc_attr_e('dark-blue'); ?>" <?php if (isset ($instance['spcolor'])) { selected($instance['spcolor'], 'dark-blue') ? 'selected="selected"' : ''; } ?>><?php _e('Dark Blue', 'spr'); ?></option>
	<option value="<?php esc_attr_e('light-blue'); ?>" <?php if (isset ($instance['spcolor'])) { selected($instance['spcolor'], 'light-blue') ? 'selected="selected"' : ''; } ?>><?php _e('Light Blue', 'spr'); ?></option>
	<option value="<?php esc_attr_e('light-gray'); ?>" <?php if (isset ($instance['spcolor'])) { selected($instance['spcolor'], 'light-gray') ? 'selected="selected"' : ''; } ?>><?php _e('Light Gray', 'spr'); ?></option>
	</select>
	</p>
	<?php
	}
} 

add_action( 'widgets_init', 'spr_register_widget' );

//register the widget
	function spr_register_widget() {
		register_widget( 'spr_widget' );
	}
?>