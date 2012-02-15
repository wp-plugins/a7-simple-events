<?php
/*
Plugin Name: A7 Simple Events
Description: Adds event post types to your site. Easy to use interface for adding and managing events.
Version: 1.0
Author: Aaron Holbrook
Author URI: http://aaronjholbrook.com/
Plugin URI: http://a7web.com/plugins/
Text Domain: a7-simple-events
Domain Path: /lang


Copyright (C) 2010-2012 Aaron Holbrook (aaron@a7web.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

define('A7_PLUGIN_URL', plugin_dir_url( __FILE__ ));

// for development - need to change for go live
//define('A7_PLUGIN_URL', '/wp-content/plugins/a7-simple-events');
/*------------------------------------*\
	Register Event Post Type
\*------------------------------------*/
add_action( 'init', 'a7_register_events' );
function a7_register_events() {
	register_post_type(
		'a7_event', array(
			'label'						=> 'Event',
			'public'					=> true,
			'show_ui'					=> true,
			'capability_type'	=> 'post',
			'hierarchical'		=> false,
			'has_archive'			=> true,
			'query_var'				=> true,
			'supports'				=> array(
				'title',
				'editor',
				'custom-fields',
				'comments',
				'revisions',
				'thumbnail',
				'page-attributes',
			)
		)
	);
}

/*------------------------------------*\
	Enqueue Javascript & Styles
\*------------------------------------*/
// Register datepicker ui for properties
function admin_javascript_scripts(){
    global $post;
    if($post->post_type == ('a7_event') && is_admin()) {
		//print datepicker script
		wp_register_script('datepicker-js', A7_PLUGIN_URL.'/js/jquery-ui-datepicker/jquery.ui.datepicker.js', 'jquery');
      	wp_enqueue_script('datepicker-js');

		//print timepicker script
		wp_register_script('timepicker-js', A7_PLUGIN_URL.'/js/jquery-timepicker/jquery.timepicker.js', 'jquery');
      	wp_enqueue_script('timepicker-js');

		//print datepair script
		wp_register_script('timepicker-datepair', A7_PLUGIN_URL.'/js/jquery-timepicker/datepair.js', 'timepicker-js');
      	wp_enqueue_script('timepicker-datepair');
    }
}
add_action('admin_print_scripts', 'admin_javascript_scripts');

// Register ui styles for properties
function admin_javascript_styles(){
    global $post;
    if($post->post_type == ('a7_event') && is_admin()) {
		//print datepicker script
		wp_register_style('timepicker-css', A7_PLUGIN_URL.'/js/jquery-timepicker/jquery.timepicker.css');
      	wp_enqueue_style('timepicker-css');

		//print timepicker script
		wp_register_style('datepicker-css', A7_PLUGIN_URL.'/js/jquery-ui-datepicker/jquery-ui-1.8.16.custom.css');
    	wp_enqueue_style('datepicker-css');
    }
}
add_action('admin_print_styles', 'admin_javascript_styles');


/*------------------------------------*\
	Meta Boxes for edit screen
\*------------------------------------*/
add_action('add_meta_boxes', 'event_datetime_add');
function event_datetime_add() {
	add_meta_box('event_datetime', 'Event Datetime', 'event_datetime_list', 'a7_event', 'side', 'high');
}

function event_datetime_list( $post ) {
	// start datetimepair
	if(get_post_meta( $post->ID, '_event_start', true )) :
		$event_start_date	= date('d-m-Y', get_post_meta( $post->ID, '_event_start', true ));
		$event_start_time = date('g:ia', get_post_meta( $post->ID, '_event_start', true ));	
	endif;
	// end datetimepair
	if(get_post_meta( $post->ID, '_event_end', true )) :	
		$event_end_date	= date('d-m-Y', get_post_meta( $post->ID, '_event_end', true ));
		$event_end_time = date('g:ia', get_post_meta( $post->ID, '_event_end', true ));	
	endif;
	
	wp_nonce_field( 'event_nonce', 'meta_box_nonce' ); ?>

<div class="datepair">
	<p>
		<label for="event_start">Event Start (dd/mm/yyyy)</label><br>
		<input type="text" class="date start" id="event_start" name="_event_start_date" value="<?php echo $event_start_date; ?>"/>
		<input type="text" class="time start" name="_event_start_time" value="<?php echo $event_start_time; ?>" />
	</p>
	<p>
		<label for="event_end">Event End</label><br>
		<input type="text" class="time end" name="_event_end_time" value="<?php echo $event_end_time; ?>" />
		<input type="text" class="date end" id="event_end" name="_event_end_date" value="<?php echo $event_end_date; ?>"/>
	</p>
</div>

<?php
}


add_action( 'save_post', 'event_datetime_save' );
function event_datetime_save( $post_id )
{
	// Bail if we're doing an auto save
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	
	// if our nonce isn't there, or we can't verify it, bail
	if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'event_nonce' ) ) return;
	
	// if our current user can't edit this post, bail
	if( !current_user_can( 'edit_post' ) ) return;
	
	// now we can actually save the data
	$allowed = array( 
		'a' => array( // on allow a tags
			'href' => array() // and those anchords can only have href attribute
		)
	);
	
	// Probably a good idea to make sure your data is set
	if( isset( $_POST['_event_start_date'] ) ) {
		if( isset($_POST['_event_start_time'] ) ) {
			$event_start = strtotime($_POST['_event_start_date']." ".$_POST['_event_start_time']);
		}
		else {
			$event_start = strtotime($_POST['_event_start_date']);
		}
		update_post_meta( $post_id, '_event_start', $event_start );
	}
	
	if( isset( $_POST['_event_end_date'] ) ) {
		if( isset($_POST['_event_end_time'] ) ) {
			$event_end = strtotime($_POST['_event_end_date']." ".$_POST['_event_end_time']);
		}
		else {
			$event_end = strtotime($_POST['_event_end_date']);
		}
		update_post_meta( $post_id, '_event_end', $event_end );
	}		
}