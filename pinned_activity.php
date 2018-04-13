<?php
/*
Plugin Name: Pin Activity
Version: 1.0
Description: Buddypress Addon for creating pinned activty.
Author: Subair T C
Author URI:
Plugin URI:
Text Domain: pin-activity
Domain Path: /languages
*/

/*
*	Function to Create new column on activity table.
*/
function pin_activty_activate() {
	global $wpdb;
	$table = $wpdb->prefix.'bp_activity';
	$query = "ALTER TABLE $table ADD `is_pinned` INT(1) NOT NULL DEFAULT '0'";
	$wpdb->query( $query );
}
register_activation_hook( __FILE__, 'pin_activty_activate' );

/*
*	Function to Enqueue required scripts.
*/
function add_pin_activty_script() {
	wp_register_script( 'pinactivity', plugins_url( '/js/pin-activity.js', __FILE__ ), true );
	wp_enqueue_script( 'pinactivity' );
	wp_localize_script('pinactivity', 'Ajax', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
	));
}
add_action( 'wp_enqueue_scripts', 'add_pin_activty_script' );

/*
*	Function to Enqueue required Styles.
*/
function add_pin_activty_style() {

	wp_register_style( 'pinactivity', plugins_url( '/css/pin-activity.css', __FILE__ ) );
	wp_enqueue_style( 'pinactivity' );
}
add_action( 'wp_enqueue_scripts', 'add_pin_activty_style' );

/*
*	Function to Create PIN/UN-PIN Button.
*/
function pin_post_button(){
	global $wpdb;
	$activity_id = bp_get_activity_id();
	$item_id = $wpdb->get_row( $wpdb->prepare( 'SELECT item_id FROM wp_bp_activity WHERE id= %d ', $activity_id ) );
	$rare_group_id = $item_id->item_id;
	if( $rare_group_id !=1 ) {
		return;
	}
	$user = wp_get_current_user();
	$allowed_roles = array( 'editor','reviewer','curator','complaince_reviewer','onevoice_publisher','administrator' );
	if ( ! array_intersect( $allowed_roles, $user->roles ) ) { 
		return;
	}
	
	$table = $wpdb->prefix.'bp_activity';
	$activty_id = bp_get_activity_id();
	$pin_status = $wpdb->get_row( $wpdb->prepare( "SELECT is_pinned FROM {$table} WHERE id='%d'", $activty_id ) );
	if ( !$pin_status->is_pinned ) {
		echo '<span ac_id="'.$activty_id.'" class="pinpost topin" to-do="topin">pin this post</span>';
	} else {
		echo '<span ac_id="'.$activty_id.'" class="pinpost pinned" to-do="unpin">unpin this post</span>';
	}
}
add_action('bp_before_activity_entry','pin_post_button');


function pin_unpin_this_post() {
	
	global $wpdb;
	$to_do = $_POST['to_do'];
	$ac_id = $_POST['ac_id'];
	$table = $wpdb->prefix.'bp_activity';
	$where = array (
		'id' => $ac_id
	);
	if ( $to_do == 'topin') {
		$data = array(
			'is_pinned' => 1
		);

		$post_name = 'pinned a post'; 

		// give custom activity name if you are processing an ajax call
		$post_seedname = 'pinned_a_post';
		$post_content ='';
		$post_desc = 'user pinned a post in SMART Social Wall';
		// Give post description if it is specified in encouragement power name and description table
		updateActivity( $post_name,$post_content,$post_desc,$post_seedname );

	} else {
		$data = array(
			'is_pinned' => 0
		);

		$post_name = 'unpinned a post'; 

		// give custom activity name if you are processing an ajax call
		$post_seedname = 'unpinned_a_post';
		$post_content ='';
		$post_desc = 'user unpinned a post in SMART Social Wall';
		// Give post description if it is specified in encouragement power name and description table
		updateActivity( $post_name,$post_content,$post_desc,$post_seedname );
	}
	echo $wpdb->update( $table, $data, $where );
	exit;
}
add_action( 'wp_ajax_pin_unpin_this_post', 'pin_unpin_this_post' );

/*
*	Function to display the pinned post.
*/
function get_pinned_activities() {
	$active_URL = $_SERVER['REQUEST_URI'];
	$currentpath  = explode( '/',$active_URL );
	if( ! in_array( 'smartsocialwall',$currentpath ) ){
		return;
	}
	
	$activity_ids = get_pinned_activity_ids();
	if( empty ( $activity_ids ) ) {
		return;
	}
	$activity_items = implode(",", $activity_ids);
	$include  = '&include=' . $activity_items;
	if ( bp_has_activities( bp_ajax_querystring( 'activity' ) . '&action=activity_update' . $include ) ) : ?>
		<ul id="activity-stream-pinned" class="activity-list1 item-list">
		<?php 
		while ( bp_activities() ) : bp_the_activity();
			bp_get_template_part( 'activity/entry' );
		endwhile; ?>
		</ul>
	<?php	
	endif;
}
add_action('bp_before_activity_loop','get_pinned_activities');

/*
	wrte pinned post label above the post.
*/
function write_pinned_post_text(){
	
	global $wpdb;
	$activity_id = bp_get_activity_id();
	$item_id = $wpdb->get_row( $wpdb->prepare( 'SELECT item_id FROM wp_bp_activity WHERE id= %d ', $activity_id ) );
	$rare_group_id = $item_id->item_id;
	if( $rare_group_id !=1 ) {
		return;
	}
	$table = $wpdb->prefix.'bp_activity';
	$activty_id = bp_get_activity_id();
	$pin_status = $wpdb->get_row( $wpdb->prepare( "SELECT is_pinned FROM {$table} WHERE id='%d'", $activty_id ) );
	if ( $pin_status->is_pinned == 1 ){
		echo '<span class="pinned_label" ac_id ='.$activty_id.'>pinned post</span>';
		 
	}
}
add_action('bp_before_activity_entry','write_pinned_post_text');


/* 
* Function to get pinned activity ids
*/
function get_pinned_activity_ids(){
	global $wpdb;
	$table = $wpdb->prefix.'bp_activity';
	$pinned_posts = $wpdb->get_results( "SELECT id FROM {$table} WHERE is_pinned=1 ORDER BY date_recorded DESC" );
	$activity_ids = array();
	foreach ( $pinned_posts as $pinned_post ) {
		array_push( $activity_ids,$pinned_post->id );
	}
	return $activity_ids;
}