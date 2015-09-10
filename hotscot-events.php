<?php
/*
Plugin Name: Hotscot Events
Description: Allows users to create and display events.
Version: 1.0.6
Author: Hotscot

Copyright 2011 Hotscot  (email : support@hotscot.net)

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

if(!isset($_SESSION)) session_start();

//first install call the table creator
register_activation_hook( __FILE__, 'ht_events_install' );
//register_deactivation_hook(__FILE__, 'ht_events_uninstall');

add_action( 'init', 'ht_create_custom_post_type' );
//Post box
add_action("save_post",'ht_save_event',1,2);
add_action( 'add_meta_boxes', 'ht_display_meta_box' );

//Custom JS
add_action('admin_enqueue_scripts', 'ht_events_scripts');

//Custom colums for post view in admin
add_filter('manage_ht_event_posts_columns' , 'set_ht_event_columns');
add_action('manage_ht_event_posts_custom_column' , 'custom_ht_event_column',10,2);
add_filter("manage_edit-ht_event_sortable_columns", "sortable_columns" );
add_filter('posts_orderby', 'ht_event_date_column_orderby', 10, 2);
add_action('restrict_manage_posts', 'ht_restrict_manage_posts');
add_filter('posts_where', 'ht_admin_date_filter');
add_filter('parse_query','ht_parse_query');

//Add shortcode handler for our shortcode "[ht_list_events numevents=2]"
add_shortcode( 'ht_list_events' , 'ht_list_events_shortcode' );

function ht_parse_query($query){
	global $post_type;
    if($post_type=='ht_event'&&!isset($_GET['orderby'])){
    	$query->query_vars['orderby'] = 'meta_value';
    	$query->query_vars['meta_key'] = '_ht_start';
    	$query->query_vars['order'] = 'asc';
    }
}


// Make these columns sortable
function sortable_columns() {
  return array(
  	'title' => 'title',
    'start_date' => 'start_date',
    'end_date' => 'end_date'
  );

}

function ht_event_date_column_orderby($orderby, $wp_query){
	global $wpdb,$post;
	$wp_query->query = wp_parse_args($wp_query->query);

	if ( 'start_date' == @$wp_query->query['orderby'] ){
		$orderby = "(SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $wpdb->posts.ID AND meta_key = '_ht_start') " . $wp_query->get('order');
	}

	if ( 'end_date' == @$wp_query->query['orderby'] ){
		$orderby = "(SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $wpdb->posts.ID AND meta_key = '_ht_end') " . $wp_query->get('order');
	}

	return $orderby;
}

function ht_restrict_manage_posts(){
	global $wpdb;
	global $filter_date;

	global $post_type;
    if($post_type=='ht_event'){
		$filter_date = isset( $_GET['filter_date'] ) ? $_GET['filter_date'] : $filter_date;
		$query =   "SELECT
					YEAR(sdat.meta_value) as syear,
					MONTH(sdat.meta_value) as smonth
					FROM $wpdb->posts as wp left join
					(select * from $wpdb->postmeta where meta_key = '_ht_start') as sdat on wp.ID = sdat.post_id
					left join (select * from $wpdb->postmeta where meta_key = '_ht_end') as edat on wp.ID = edat.post_id
					where wp.post_type = 'ht_event'
					AND wp.post_status = 'publish'
					group by syear,smonth
					order by sdat.meta_value";
		$filterDates = $wpdb->get_results($query, OBJECT);
		if($filterDates){
		?>
			<select name="filter_date" style="width: 110px;">
				<option value="">All Events</option>
				<?php foreach ($filterDates as $fdate): ?>
					<option <?php selected( $filter_date, $fdate->syear . "-" .  $fdate->smonth); ?> value="<?php echo $fdate->syear . "-" .  $fdate->smonth;?>"><?php echo date("F Y", strtotime($fdate->syear . "-" .  $fdate->smonth . "-" . "01")); ?></option>
				<?php endforeach; ?>
			</select>
			<style type='text/css'>
				.tablenav.top .alignleft.actions select[name="m"] {
				    display: none;
				}
			</style>
		<?php
		}
	}
}

function ht_admin_date_filter($where) {
    if( is_admin() ) {
        global $wpdb;
        global $post_type;
        if($post_type=='ht_event'){
	        if( isset($_GET['filter_date']) && ($_GET['filter_date']!='')) {
	        	list($year, $month) = explode('-', $_GET['filter_date']);

	        	$sdte = date('Y-m-d',strtotime("$year-$month-01"));
	        	$edte = date('Y-m-d',strtotime("+ 1 month -1 day $sdte"));

	            $where .= " AND ID IN (select post_id from $wpdb->postmeta where meta_key = '_ht_start' and meta_value between '$sdte' and '$edte') ";
	        }
	    }
    }
    return $where;
}


function set_ht_event_columns($columns) {
    unset($columns['date']);
    return array_merge($columns,
              array('start_date' => 'Start Date',
                    'end_date' => 'End Date'));
}

function custom_ht_event_column( $column, $post_id ) {
    switch ( $column ) {
      case 'start_date':
        $Start_date = get_post_meta($post_id, '_ht_start', true);
		if($Start_date != ""){
			$Start_date = ht_event_mysql_to_date($Start_date);
			if($Start_date == "//") {
				$Start_date = "";
			}
		}
		echo $Start_date;
        break;

      case 'end_date':
        $End_date = get_post_meta($post_id, '_ht_end', true);
		if($End_date != ""){
			$End_date = ht_event_mysql_to_date($End_date);
			if($End_date == "//") {
				$End_date = "";
			}
		}
		echo $End_date;
        break;
    }
}


function ht_create_custom_post_type(){
	register_post_type( 'ht_event',
		array(
			'labels' => array(
				'name' => 'Events' ,
				'singular_name' => 'Event' ,
				'add_new' => 'Add New',
				'add_new_item' => 'Add New Event',
				'new_item' => 'New Event',
				'all_items' => 'All Events',
				'view_item' => 'View Event',
				'search_items' => 'Search Events',
				'not_found' => 'No Events Found',
				'not_found_in_trash' => 'No Events Found in Trash'
			),
		'public' => true,
		'has_archive' => true,
		'supports' => array('title', 'editor', 'excerpt', 'thumbnail'),
		'menu_icon' => plugins_url('/menu.png', __FILE__),
		)
	);
}

//create the tables for first install
function ht_events_install(){
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	//Create our custom post type...
	ht_create_custom_post_type();
	flush_rewrite_rules();
}


/**
 * getEvents - get events as object
 *
 * Will print a list of events either from the current date or one specified.  The end range can be specified or left blank to
 * show everything ending in the future.
 *
 * @param date start_date (yyyy-mm-dd) - Leave blank for today
 * @param date end_date (yyyy-mm-dd) [OPTIONAL]
 * @param limit - limit the number of posts
 * @return object of posts that relate to this query
 **/
function ht_getEvents($start_date = "", $end_date = "", $limit = 0){
	global $wpdb;

	//If we've not got a start date, use todays date
	if($start_date == ""){
		$start_date = date("Y-m-d");
	}

	if($end_date != ""){
		$querystr =
			"SELECT wp.*, sdat.meta_value as sdte, edat.meta_value as edte
			FROM $wpdb->posts as wp left join
			(select * from $wpdb->postmeta where meta_key = '_ht_start') as sdat on wp.ID = sdat.post_id
			left join (select * from $wpdb->postmeta where meta_key = '_ht_end') as edat on wp.ID = edat.post_id
			where wp.post_type = 'ht_event'
			AND wp.post_status = 'publish'
			and (
			((cast(sdat.meta_value as DATE) <= '$end_date') and (cast(edat.meta_value as DATE) >= '$start_date'))
			or
			((cast(sdat.meta_value as DATE) >= '$start_date') and (cast(sdat.meta_value as DATE) <= '$end_date'))
			)
			order by sdat.meta_value";
	}else{
		$querystr =
			"SELECT wp.*, sdat.meta_value as sdte, edat.meta_value as edte
			FROM $wpdb->posts as wp left join
			(select * from $wpdb->postmeta where meta_key = '_ht_start') as sdat on wp.ID = sdat.post_id
			left join (select * from $wpdb->postmeta where meta_key = '_ht_end') as edat on wp.ID = edat.post_id
			where wp.post_type = 'ht_event'
			AND wp.post_status = 'publish'
			and cast(edat.meta_value as DATE) >= '$start_date'
			order by sdat.meta_value";
	}

	if($limit > 0){
		$querystr .= " LIMIT $limit";
	}

	$pageposts = $wpdb->get_results($querystr, OBJECT);
	return $pageposts;
}

function ht_display_meta_box(){
	add_meta_box("htEventPlugin",
		         "Event Dates",
		         "ht_fill_meta_box",
		         "ht_event",
		         'side');
}

function ht_fill_meta_box(){
	global $post;
	$Start_date = get_post_meta($post->ID, '_ht_start', true);
	if($Start_date != ""){
		$Start_date = ht_event_mysql_to_date($Start_date);
		if($Start_date == "//") {
			$Start_date = "";
		}
	}

	$End_date = get_post_meta($post->ID, '_ht_end', true);
	if($End_date != ""){
		$End_date = ht_event_mysql_to_date($End_date);
		if($End_date == "//") {
			$End_date = "";
		}
	}

	?>
	<label for="Start_date">Start:&nbsp;</label><input type="text" size="10" name="Start_date" id="Start_date" value="<?php echo $Start_date; ?>" />&nbsp;&nbsp;
	<label for="End_date">End:&nbsp;</label><input type="text" size="10" name="End_date" id="End_date" value="<?php echo $End_date; ?>" /><br /><br />
	<?php
}

function ht_save_event($post_id, $post){
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

	$startDate = isset($_POST['Start_date']) ? $_POST['Start_date'] : '';
	if(preg_match("/\d{2}\/\d{2}\/\d{4}/", $startDate)){
		$startDate = ht_event_date_to_mysql($startDate);
	}else{
		$startDate = ht_event_date_to_mysql(date("d/m/Y"));
	}


	$endDate = isset($_POST['End_date']) ? $_POST['End_date'] : '';
	if(preg_match("/\d{2}\/\d{2}\/\d{4}/", $endDate)){
		$endDate = ht_event_date_to_mysql($endDate);
	}else{
		$endDate = $startDate;
	}

    add_post_meta($post_id, '_ht_start', $startDate, true) or update_post_meta($post_id, '_ht_start', $startDate);
    add_post_meta($post_id, '_ht_end', $endDate, true) or update_post_meta($post_id, '_ht_end', $endDate);
}

/** Register custom Javascripts **/
function ht_events_scripts(){
	wp_register_script('ht-event-script',
                       plugins_url('/js/general.js', __FILE__),
                       array('jquery','jquery-ui-datepicker'));
    wp_enqueue_script('ht-event-script');

    //Add css for jquery ui
    wp_register_style( 'jquery-ui-css', plugins_url('/css/jquery-ui-custom.css', __FILE__) );
    wp_enqueue_style('jquery-ui-css');
}

/***********************
 *                     *
 * 	GENERAL FUNCTIONS  *
 *                     *
 ***********************/
function ht_event_date_to_mysql($dte='01/01/01'){
	list ($day, $month, $year) = split ("/", $dte);
	return $year . "-" . $month ."-" . $day;
}

function ht_event_mysql_to_date($dte='2001-01-01'){
	$dte = explode(" ", $dte);
	list ($year, $month, $day) = explode("-", $dte[0]);
	return $day . "/" . $month ."/" . $year;
}




/***********************
 * Front End Functions *
 ***********************/

/**
 * echo out the events table
 * @param  string  $start_date [optional] leave blnk for current date
 * @param  string  $end_date   [optional] leave blank for all events
 * @param  integer $limit      [optional] set to 0 for all events
 * @return void
 */
function ht_eventsHTML($start_date = "", $end_date = "", $limit = 0){
	echo ht_getEventsHTML($start_date, $end_date, $limit);
}

/**
 * get the events table html
 * @param  string  $start_date [optional] leave blnk for current date
 * @param  string  $end_date   [optional] leave blank for all events
 * @param  integer $limit      [optional] set to 0 for all events
 * @return string $html Events table html
 */
function ht_getEventsHTML($start_date = "", $end_date = "", $limit = 0){
	$events = ht_getEvents($start_date, $end_date, $limit);

	$html = "<p>No events to display</p>";
	if($events){
		//begin table
		$html = '<table class="hotscot-events"><thead><tr><th>Start Date</th><th>End Date</th><th>Title</th><th>&nbsp;</th></tr></thead>';
		//Loop through the events and print them as rows
		foreach($events as $event){
			$html .= '<tr>';
			$html .= '<td class="hotscot-event-start">' . date(get_option('date_format', 'd/m/Y'), strtotime($event->sdte)) . '</td>';
			$html .= '<td class="hotscot-event-end">' . date(get_option('date_format', 'd/m/Y'), strtotime($event->edte)) . '</td>';
			$html .= '<td class="hotscot-event-title">' . get_the_title($event->ID) . '</td>';
			$html .= '<td class="hotscot-event-link"><a href="' . get_post_type_archive_link('ht_event') .  $event->post_name . '/" title="' . $event->post_name . '">View Details</a></td>';
			$html .= '</tr>';
		}
		//end table
		$html .= "</table>";
	}

	return $html;
}

/**
 * handler for our shortcode "[ht_list_events numevents=2] numevents is optional
 * @param string $attributes - attributes applied to shortcode
 * @return string Event table HTML
 */
function ht_list_events_shortcode($attibutes){
	$numevents = 0; //Default to show all events

	extract( shortcode_atts( array(
		                            'numevents' => 0
		                          ) ,
			                 $attibutes
			               )
		   );

	return ht_getEventsHTML($start_date = "", $end_date = "", $limit = $numevents);
}


?>