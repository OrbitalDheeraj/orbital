<?php

//event_content defined in rsvpmaker-pluggable.php to allow for variations

add_filter('the_content','event_content',5);

function event_js($content) {
global $post;
if(!is_single())
	return $content;
if(!strpos($content,'id="rsvpform"') )
	return $content;
if($post->post_type != 'rsvpmaker')
	return $content;
return $content . rsvp_form_jquery();
}

function rsvp_form_jquery() {
global $rsvp_required_field; // todo
global $post;
ob_start();
?>
<script type="text/javascript">
jQuery(document).ready(function($) {

<?php
$hide = get_post_meta($post->ID,'_hiddenrsvpfields',true);
if(!empty($hide))
	{
	printf('var hide = %s;',json_encode($hide));
	echo "\n";
?>

$('#guest_count_pricing select').change(function() {
  //reset hidden fields
  $('#rsvpform input').prop( "disabled", false );
  $('#rsvpform select').prop( "disabled", false );
  $('#rsvpform div').show();
  $('#rsvpform p').show();
  var pricechoice = $(this).val();
  //alert( "Price choice" + hide[pricechoice] );
  var hideit = hide[pricechoice];
  $.each(hideit, function( index, value ) {
  //alert( index + ": " + value );
  $('div.'+value).hide();
  $('p.'+value).hide();
  $('.'+value).prop( "disabled", true );
});

});

<?php
	}
?>

var blank = $('#first_blank').html();
$('#first_blank').html(blank.replace(/\[\]/g,'['+guestcount+']').replace('###',guestcount) )
guestcount++;
$('#add_guests').click(function(event){
	event.preventDefault();
var guestline = '<' + 'div class="guest_blank">' +
	blank.replace(/\[\]/g,'['+guestcount+']').replace('###',guestcount) +
	'<' + '/div>';
guestcount++;
$('#first_blank').append(guestline);

if(hide)
{
  var pricechoice = $("#guest_count_pricing select").val();
  var hideit = hide[pricechoice];
  $.each(hideit, function( index, value ) {
  //alert( index + ": " + value );
  $('div.'+value).hide();
  $('p.'+value).hide();
  $('.'+value).prop( "disabled", true );
});
}

});

    jQuery("#rsvpform").submit(function() {
	var leftblank = '';
if(jQuery("#first").val() === '') leftblank = leftblank + '<' + 'div class="rsvp_missing">first'+'<' +'/div>';
if(jQuery("#last").val() === '') leftblank = leftblank + '<' + 'div class="rsvp_missing">last'+'<' +'/div>';
if(jQuery("#email").val() === '') leftblank = leftblank + '<' + 'div class="rsvp_missing">email'+'<' +'/div>';
	if(leftblank != '')
		{
		jQuery("#jqerror").html('<' +'div class="rsvp_validation_error">' + "Required fields left blank:\n" + leftblank + ''+'<' +'/div>');
		//alert("Required fields left blank:\n" + leftblank);
		return false;
		}
	else
		return true;
});


});
</script>
<?php
return ob_get_clean();
}

add_filter('the_content','event_js',15);

add_shortcode('event_listing', 'event_listing');

function event_listing($atts) {

global $wpdb;
global $rsvp_options;

$date_format = (isset($atts["date_format"])) ? $atts["date_format"] : $rsvp_options["short_date"];
$time_format = (isset($atts["time_format"])) ? $atts["time_format"] : $rsvp_options["time_format"];
fix_timezone();

if(isset($atts["past"]))
	$sql = "SELECT *, $wpdb->posts.ID as postID, 1 as current, meta_value as datetime
FROM `".$wpdb->postmeta."`
JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates'
WHERE meta_value < CURDATE( ) AND $wpdb->posts.post_status = 'publish'
ORDER BY meta_value DESC";
elseif(isset($_GET["cy"]))
	$sql = "SELECT *, $wpdb->posts.ID as postID, 1 as current, meta_value as datetime
FROM `".$wpdb->postmeta."`
JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates'
WHERE meta_value >= '".$_GET["cy"] .'-'. $_GET["cm"].'-01'."' AND $wpdb->posts.post_status = 'publish'
ORDER BY meta_value";
else
	$sql = "SELECT *, $wpdb->posts.ID as postID, meta_value > CURDATE( ) as current, meta_value as datetime
FROM `".$wpdb->postmeta."`
JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates'
WHERE meta_value >= '".date('Y-m')."-01' AND $wpdb->posts.post_status = 'publish'
ORDER BY meta_value";

if(isset($atts["limit"]))
	$sql .= " LIMIT 0,".$atts["limit"];

$results = $wpdb->get_results($sql,ARRAY_A);

foreach($results as $row)
	{
	$t = strtotime($row["datetime"]);
	if($dateline[$row["postID"]])
		$dateline[$row["postID"]] .= ", ";
	$dateline[$row["postID"]] .= date($date_format,$t);
	if($row["current"] && !$eventlist[$row["postID"]])
		$eventlist[$row["postID"]] = $row;
	}

if(isset($atts["calendar"]) || (isset($atts["format"]) && $atts["format"] == 'calendar') )
	$listings .= rsvpmaker_calendar($atts);

//strpos test used to catch either "headline" or "headlines"
if($eventlist && isset($atts["format"]) && ( $atts["format"] == 'headline' || $atts["format"] == 'headlines') )
{
foreach($eventlist as $event)
	{
	if($atts["permalinkhack"])
		$permalink = site_url() ."?p=".$event["postID"];
	else
		$permalink = get_post_permalink($event["postID"]);
	$listings .= sprintf('<li><a href="%s">%s</a> %s</li>'."\n",$permalink,$event["post_title"],$dateline[$event["postID"]].'<hr>');
	}

	if($atts["limit"] && $rsvp_options["eventpage"])
		$listings .= '<li><a href="'.$rsvp_options["eventpage"].'"><font color="blue"><u>'.__("Go to Events Page",'rsvpmaker')."</u></font></a></li>";

	if($atts["title"])
		$listings = "<p><strong>".$atts["title"]."</strong></p>\n<ul id=\"eventheadlines\">\n$listings</ul>\n";
	else
		$listings = "<ul id=\"eventheadlines\">\n$listings</ul>\n";
}//end if $eventlist

	return $listings;
}

/**
 * CPEventsWidget Class
 */
class CPEventsWidget extends WP_Widget {
    /** constructor */
    function __construct() {
        parent::__construct('rsvpmaker_widget', $name = 'RSVPMaker Events');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
    	global $wpdb;
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		if(empty($title))
			$title = __('Events','rsvpmaker');
		$limit = ($instance["limit"]) ? $instance["limit"] : 10;
		$dateformat = ($instance["dateformat"]) ? $instance["dateformat"] : 'M. j';
        global $rsvp_options;
		;?>
              <?php echo $before_widget;?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title;?>
              <?php

              $sql_widget_delete="DELETE FROM ".$wpdb->prefix."rsvpmakernew WHERE rsvpdate < CURRENT_TIMESTAMP";
              $wpdb->get_results($sql_widget_delete);

             $resultsi="SELECT * FROM ".$wpdb->prefix."rsvpmakernew ORDER BY creation_time DESC LIMIT ".$limit;
             $results=$wpdb->get_results($resultsi);



			  if($results)
			  {
			  echo "\n<ul>\n";

			//pluggable function widgetlink can be overridden from custom.php
       $count=0;
			  foreach($results as $row){
                $count++;
			  	$time=$row->rsvpdate;
			  	$time=date("M j Y",strtotime($time));
			  	$link=get_post_permalink($row->event);
			  	$title=get_the_title($row->event);
			  	$result_inner= "SELECT * FROM ".$wpdb->prefix."posts WHERE post_type='rsvpmaker' AND post_status='trash' AND ID='".$row->event."'";
			  	$wpdb->get_results($result_inner);
             $result2=$wpdb->num_rows;

			  	if($result2!=0){
                   $del="DELETE FROM ".$wpdb->prefix."rsvpmakernew WHERE event='".$row->event."'";
                   $wpdb->get_results($del);
			  	}
			  	elseif($result2==0){
                if($count<=2)
			  	echo "<li> <a href=".$link.">".$title."</a><font color =\"red\"> (new)</font>, ".$time." </li><hr>";
			    if($count>2)
			    	echo "<li> <a href=".$link.">".$title."</a>, ".$time." </li><hr>";
			}
}
			if($rsvp_options["eventpage"])
			  	echo '<li><a href="'.$rsvp_options["eventpage"].'"><font color="blue"><u>'.__("Go to Events Page",'rsvpmaker')."</u></font></a></li>";

			  echo "\n</ul>\n";
			  }


			  echo $after_widget;?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
	$instance['dateformat'] = strip_tags($new_instance['dateformat']);
	$instance['limit'] = (int) $new_instance['limit'];
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        $title = esc_attr($instance['title']);
		$limit = ($instance["limit"]) ? $instance["limit"] : 10;
		$dateformat = ($instance["dateformat"]) ? $instance["dateformat"] : 'M. j';
        ;?>
            <p><label for="<?php echo $this->get_field_id('title');?>"><?php _e('Title:','rsvpmaker');?> <input class="widefat" id="<?php echo $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title');?>" type="text" value="<?php echo $title;?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('limit');?>"><?php _e('Number to Show:','rsvpmaker');?> <input class="widefat" id="<?php echo $this->get_field_id('limit');?>" name="<?php echo $this->get_field_name('limit');?>" type="text" value="<?php echo $limit;?>" /></label></p>

            <p><label for="<?php echo $this->get_field_id('dateformat');?>"><?php _e('Date Format:','rsvpmaker');?> <input class="widefat" id="<?php echo $this->get_field_id('dateformat');?>" name="<?php echo $this->get_field_name('dateformat');?>" type="text" value="<?php echo $dateformat;?>" /></label> (PHP <a target="_blank" href="http://us2.php.net/manual/en/function.date.php">date</a> format string)</p>

        <?php
    }

} // class CPEventsWidget

// register CPEventsWidget widget
add_action('widgets_init', create_function('', 'return register_widget("CPEventsWidget");'));

function get_next_events_link( $label = '', $max_page = 0 ) {

	$link = get_rsvpmaker_archive_link(2);

	global $paged;

	if ( !$max_page )
		$max_page = $wp_query->max_num_pages;

	if ( !$paged )
		$paged = 1;

	$nextpage = intval($paged) + 1;
	$link = get_rsvpmaker_archive_link($nextpage);

		$attr = apply_filters( 'next_posts_link_attributes', '' );
		$link = '<a href="' . $link ."\" $attr>" . $label . ' &raquo;</a>';

	if(isset($link))
		echo "<p>$link</p>";
}

function rsvpmaker_select($select) {
  global $wpdb;

    $select .= ", meta_value as datetime, meta_id";
  return $select;
}

function rsvpmaker_join($join) {
  global $wpdb;

    $join .= " JOIN ".$wpdb->postmeta." ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates'";

  return $join;
}

function rsvpmaker_groupby($groupby) {
  global $wpdb;
  return " $wpdb->posts.ID ";

}

function rsvpmaker_distinct($distinct){
  return 'DISTINCT';
}

function rsvpmaker_where($where) {

global $startday;

$where .= " AND meta_key='_rsvp_dates' ";

if(isset($_REQUEST["cm"]))
	return $where . " AND meta_value >= '".$_REQUEST["cy"]."-".$_REQUEST["cm"]."-01'";
elseif(isset($startday) && $startday)
	{
		$t = strtotime($startday);
		$d = date('Y-m-d',$t);
		return $where . " AND meta_value > '$d'";
	}
elseif(isset($_GET["startday"]))
	{
		$t = strtotime($_GET["startday"]);
		$d = date('Y-m-d',$t);
		return $where . " AND meta_value > '$d'";
	}
else
	return $where . " AND meta_value > CURDATE( )";
}

function rsvpmaker_orderby($orderby) {
  return " meta_value ";
}

// if listing past dates
function rsvpmaker_where_past($where) {

global $startday;
$where .= " AND meta_key='_rsvp_dates' ";

if(isset($_REQUEST["cm"]))
	return $where . " AND meta_value < '".$_REQUEST["cy"]."-".$_REQUEST["cm"]."-31'";
elseif(isset($startday) && $startday)
	{
		$t = strtotime($startday);
		$d = date('Y-m-d',$t);
		return $where . " AND meta_value < '$d'";
	}
elseif(isset($_GET["startday"]))
	{
		$t = strtotime($_GET["startday"]);
		$d = date('Y-m-d',$t);
		return $where . " AND meta_value < '$d'";
	}
else
	return $where . " AND meta_value < CURDATE( )";

}

function rsvpmaker_orderby_past($orderby) {
  return " meta_value DESC";
}

function rsvpmaker_upcoming ($atts)
{

$no_events = (isset($atts["no_events"])) ? $atts["no_events"] : 'No events currently listed.';

global $post;
global $wp_query;
global $wpdb;
global $showbutton;
global $startday;
global $rsvp_options;

$showbutton = true;

$backup = $wp_query;

add_filter('posts_join', 'rsvpmaker_join' );
add_filter('posts_groupby', 'rsvpmaker_groupby' );
add_filter('posts_distinct', 'rsvpmaker_distinct' );
add_filter('posts_fields', 'rsvpmaker_select' );
if(isset($atts["past"]) && $atts["past"])
	{
	add_filter('posts_where', 'rsvpmaker_where_past' );
	add_filter('posts_orderby', 'rsvpmaker_orderby_past' );
	}
else
	{
	add_filter('posts_where', 'rsvpmaker_where' );
	add_filter('posts_orderby', 'rsvpmaker_orderby' );
	}

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$querystring = "post_type=rsvpmaker&post_status=publish&paged=$paged";

if(isset($atts["one"]) && !empty($atts["one"]))
	{
	$querystring .= "&posts_per_page=1";
	if(is_numeric($atts["one"]))
		$querystring .= '&p='.$atts["one"];
	elseif($atts["one"] != 'next')
		$querystring .= '&name='.$atts["one"];
	}
if(isset($atts["type"]))
	$querystring .= "&rsvpmaker-type=".$atts["type"];
if(isset($atts["limit"]))
	$querystring .= "&posts_per_page=".$atts["limit"];
if(isset($atts["add_to_query"]))
	{
		if(!strpos($atts["add_to_query"],'&'))
			$atts["add_to_query"] = '&'.$atts["add_to_query"];
		$querystring .= $atts["add_to_query"];
	}

$wpdb->show_errors();

$wp_query = new WP_Query($querystring);

// clean up so this doesn't interfere with other operations
remove_filter('posts_join', 'rsvpmaker_join' );
remove_filter('posts_groupby', 'rsvpmaker_groupby' );
remove_filter('posts_distinct', 'rsvpmaker_distinct' );
remove_filter('posts_fields', 'rsvpmaker_select' );

if(isset($atts["past"]) && $atts["past"])
	{
	remove_filter('posts_where', 'rsvpmaker_where_past' );
	remove_filter('posts_orderby', 'rsvpmaker_orderby_past' );
	}
else
	{
	remove_filter('posts_where', 'rsvpmaker_where' );
	remove_filter('posts_orderby', 'rsvpmaker_orderby' );
	}


ob_start();

if(isset($atts["demo"]))
	{
		$demo = "<div><strong>Shortcode:</strong></div>\n<code>[rsvpmaker_upcoming";
		foreach($atts as $name => $value)
			{
			if($name == "demo")
				continue;
			$demo .= ' '.$name.'="'.$value.'"';
			}
		$demo .= "]</code>\n";
		$demo .= "<div><strong>Output:</strong></div>\n";
		echo $demo;
	}

echo '<div class="rsvpmaker_upcoming">';

if ( have_posts() ) {
while ( have_posts() ) : the_post();

?>

<div id="post-<?php the_ID();?>" <?php post_class();?> itemscope itemtype="http://schema.org/Event" >
<h3 class="entry-title"><a href="<?php the_permalink(); ?>"  itemprop="url"><font color = "blue"><span itemprop="name"><?php the_title(); ?></font></span></a></h1>
<div class="entry-content">

<?php the_content(); ?>
<br /><hr>
</div><!-- .entry-content -->

<?php

if(!isset($atts["hideauthor"]) || !$atts["hideauthor"])
{
$authorlink = sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s">%3$s</a></span>',
	get_author_posts_url( get_the_author_meta( 'ID' ) ),
	sprintf( esc_attr__( 'View all posts by %s', 'rsvpmaker' ), get_the_author() ),
	get_the_author());
?>
<!--<div class="event_author"><?php _e('Posted by','rsvpmaker'); echo " $authorlink on ";?><span class="updated" datetime="<?php the_modified_date('c');?>"><?php the_modified_date(); ?></span></div> -->
<?php
}
?>
</div>
<br />
<br />
<?php
if(is_admin() )
	{
		echo '<p><a href="'.admin_url('post.php?action=edit&post='.$post->ID).'">Edit</a></p>';
	}
endwhile;
?>

<p>
<?php
if(!(isset($atts['one']) && $atts['one']))
	get_next_events_link(__(' ','rsvpmaker'));
}
else
	echo "<p>$no_events</p>\n";

echo '</div><!-- end rsvpmaker_upcoming -->';

$wp_query = $backup;
wp_reset_postdata();

if(	( isset($atts["calendar"]) && $atts["calendar"]) || (isset($atts["format"]) && ($atts["format"] == "calendar") ) )
	if(!(isset($atts['one']) && $atts['one']))
		$listings = rsvpmaker_calendar($atts);

$listings .= ob_get_clean();

return $listings;

}

add_shortcode("rsvpmaker_upcoming","rsvpmaker_upcoming");

function rsvpmaker_excerpt ($atts)
{

$no_events = (isset($atts["no_events"]) && $atts["no_events"]) ? $atts["no_events"] : 'No events currently listed.';

global $post;
global $wp_query;
global $wpdb;

$backup = $wp_query;

add_filter('posts_join', 'rsvpmaker_join' );
add_filter('posts_where', 'rsvpmaker_where' );
add_filter('posts_groupby', 'rsvpmaker_groupby' );
add_filter('posts_orderby', 'rsvpmaker_orderby' );
add_filter('posts_distinct', 'rsvpmaker_distinct' );
remove_filter('the_content','event_content',5);

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$querystring = "post_type=rsvpmaker&post_status=publish&paged=$paged";
if(isset($atts["type"]))
        $querystring .= "&rsvpmaker-type=".$atts["type"];
if(isset($atts["limit"]))
        $querystring .= "&posts_per_page=".$atts["limit"];
if(isset($atts["add_to_query"]))
        {
                if(!strpos($atts["add_to_query"],'&'))
                        $atts["add_to_query"] = '&'.$atts["add_to_query"];
                $querystring .= $atts["add_to_query"];
        }

$wp_query = new WP_Query($querystring);

// clean up so this doesn't interfere with other operations
remove_filter('posts_join', 'rsvpmaker_join' );
remove_filter('posts_where', 'rsvpmaker_where' );
remove_filter('posts_groupby', 'rsvpmaker_groupby' );
remove_filter('posts_orderby', 'rsvpmaker_orderby' );
remove_filter('posts_distinct', 'rsvpmaker_distinct' );

ob_start();

if ( have_posts() ) {
while ( have_posts() ) : the_post();

$sql = "SELECT * FROM ".$wpdb->prefix."rsvp_dates WHERE postID=".$post->ID.' ORDER BY datetime';
$results = $wpdb->get_results($sql,ARRAY_A);
if($results)
{
$dateblock = '';
foreach($results as $row)
        {
        $t = strtotime($row["datetime"]);
        if(!empty($dateblock))
                $dateblock .= ', ';
        $dateblock .= date('F jS',$t);
        }
}
?>

<div id="post-<?php the_ID();?>" <?php post_class();?> >
<h3 class="entry-title"><a href="<?php the_permalink(); ?>" ><?php the_title(); echo ' - '.$dateblock; ?></span></a></h3>
<div class="entry-content">
<?php the_excerpt(); ?>
</div><!-- .entry-content -->
</div>
<?php
endwhile;
}
else
        echo "<p>$no_events</p>\n";
$wp_query = $backup;

add_filter('the_content','event_content',5);

wp_reset_postdata();

return ob_get_clean();

}

add_shortcode("rsvpmaker_excerpt","rsvpmaker_excerpt");

//get all of the dates for the month
function rsvpmaker_calendar_where($where) {

global $startday;

if(isset($_REQUEST["cm"]))
	$d = "'".$_REQUEST["cy"]."-".$_REQUEST["cm"]."-01'";
elseif(isset($startday) && $startday)
	{
		$t = strtotime($startday);
		$d = "'".date('Y-m-d',$t)."'";
	}
elseif(isset($_GET["startday"]))
	{
		$t = strtotime($_GET["startday"]);
		$d = "'".date('Y-m-d',$t)."'";
	}
else
	$d = ' CURDATE() ';

	return $where . " AND meta_value > ".$d.' AND meta_value < DATE_ADD('.$d.', INTERVAL 5 WEEK) ';
}

function rsvpmaker_calendar_clear($g) {
return '';
}

add_shortcode("rsvpmaker_calendar","rsvpmaker_calendar");
function rsvpmaker_calendar($atts)
{

global $post;
global $wp_query;
global $wpdb;
global $showbutton;
global $startday;
global $rsvp_options;
$date_format = (isset($atts["date_format"])) ? $atts["date_format"] : $rsvp_options["short_date"];

$showbutton = true;

$backup = $wp_query;

//removing groupby, which interferes with display of multi-day events
add_filter('posts_join', 'rsvpmaker_join' );
add_filter('posts_where', 'rsvpmaker_calendar_where' );
add_filter('posts_orderby', 'rsvpmaker_orderby' );
add_filter('posts_groupby', 'rsvpmaker_calendar_clear' );
add_filter('posts_distinct', 'rsvpmaker_calendar_clear' );
add_filter('posts_fields', 'rsvpmaker_select' );

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$querystring = "post_type=rsvpmaker&post_status=publish&posts_per_page=-1&paged=$paged";

if(isset($atts["type"]))
	$querystring .= "&rsvpmaker-type=".$atts["type"];
if(isset($atts["add_to_query"]))
	{
		if(!strpos($atts["add_to_query"],'&'))
			$atts["add_to_query"] = '&'.$atts["add_to_query"];
		$querystring .= $atts["add_to_query"];
	}

$wpdb->show_errors();

$wp_query = new WP_Query($querystring);

// clean up so this doesn't interfere with other operations
remove_filter('posts_join', 'rsvpmaker_join' );
remove_filter('posts_where', 'rsvpmaker_calendar_where' );
remove_filter('posts_orderby', 'rsvpmaker_orderby' );
remove_filter('posts_groupby', 'rsvpmaker_calendar_clear' );
remove_filter('posts_distinct', 'rsvpmaker_calendar_clear' );
remove_filter('posts_fields', 'rsvpmaker_select' );

if ( have_posts() ) {
while ( have_posts() ) : the_post();

//calendar entry
	if(empty($post->post_title))
		$post->post_title = __('Title left blank','rsvpmaker');

	fix_timezone();
	$t = strtotime($post->datetime);
	$post->duration = get_post_meta($post->ID,'_'.$post->datetime, true);
	$time = ($post->duration == 'allday') ? '' : '<br />&nbsp;'.date($rsvp_options["time_format"],$t);
	if(strpos($post->duration,'-'))
		{
		$time .= '-'.date($rsvp_options["time_format"],strtotime($post->duration));
		}
	if(isset($_GET["debug"]))
		{
			printf('<p>%s<br />%s %s</p>',$post->post_title,$post->datetime,$post->meta_id);
		}
	$eventarray[date('Y-m-d',$t)] .= '<div><a class="calendar_item" href="'.get_post_permalink($post->ID).'" title="'.htmlentities($post->post_title).'">'.$post->post_title.$time."</a></div>\n";
	if(!isset($atts["noexpand"]))
		$caldetail[date('Y-m-d',$t)] .= '<div>'.date($date_format,$t).': <a href="'.get_post_permalink($post->ID).'">'.$post->post_title."</a></div>\n";
endwhile;
}

$wp_query = $backup;
wp_reset_postdata();

// calendar display routine
$nav = isset($atts["nav"]) ? $atts["nav"] : 'bottom';

$cm = $_REQUEST["cm"];
$cy = $_REQUEST["cy"];
$self = $req_uri = get_permalink();
$req_uri .= (strpos($req_uri,'?') ) ? '&' : '?';

if (!isset($cm) || $cm == 0)
	$nowdate = date("Y-m-d");
else
	$nowdate = date("Y-m-d", mktime(0, 0, 1, $cm, 1, $cy) );

// Check if month and year is valid
if ($cm && $cy && !checkdate($cm,1,$cy)) {
   $errors[] = "The specified year and month (".htmlentities("$cy, $cm").") are not valid.";
   unset($cm); unset($cy);
}

// Give defaults for the month and day values if they were invalid
if (!isset($cm) || $cm == 0) { $cm = date("m"); }
if (!isset($cy) || $cy == 0) { $cy = date("Y"); }

// Start of the month date
$date = mktime(0, 0, 1, $cm, 1, $cy);

// Beginning and end of this month
$bom = mktime(0, 0, 1, $cm,  1, $cy);
$eom = mktime(0, 0, 1, $cm+1, 0, $cy);
$eonext = date("Y-m-d",mktime(0, 0, 1, $cm+2, 0, $cy) );

// Link to previous month (but do not link to too early dates)
$lm = mktime(0, 0, 1, $cm, 0, $cy);
   $prev_link = '<a href="' . $req_uri . strftime('cm=%m&cy=%Y">&lt;&lt; %B %Y</a>', $lm);

// Link to next month (but do not link to too early dates)
$nm = mktime(0, 0, 1, $cm+1, 1, $cy);
   $next_link = '<a href="' . $req_uri . strftime('cm=%m&cy=%Y">%B %Y &gt;&gt;</a>', $nm);

$monthafter = mktime(0, 0, 1, $cm+2, 1, $cy);

	$page_id = (isset($_GET["page_id"])) ? '<input type="hidden" name="page_id" value="'. (int) $_GET["page_id"].'" />' : '';

// $Id: cal.php,v 1.47 2003/12/31 13:04:27 goba Exp $

// Begin the calendar table

//jump form
$content .= sprintf('<form id="jumpform" action="%s" method="post"> Search Events by Month/Year <input type="text" name="cm" value="%s" size="4" />/<input type="text" name="cy" value="%s" size="4" /><button>Go</button>%s</form>', $self,date('m',$monthafter),date('Y',$monthafter),$page_id);

if(($nav == 'top') || ($nav == 'both')) // either it's top or both
$content .= '<div style="width: 100%; text-align: right;" class="nav"><span class="navprev">'. $prev_link. '</span> / <span class="navnext">'.
     '' . $next_link . "</span></div>";



$content .= '


<table id="cpcalendar" width="100%" cellspacing="0" cellpadding="3" style="border: 3px solid black; border-radius: 0px"><caption>'.strftime('<b>%B %Y</b>', $bom)."</caption>\n".'<tr>'."\n";

$content .= '<thead>';

$content .= '<tr>
<th style="background-color:#F29208">'.__('Sunday','rsvpmaker').'</th>
<th style="background-color:#F29208">'.__('Monday','rsvpmaker').'</th>
<th style="background-color:#F29208">'.__('Tuesday','rsvpmaker').'</th>
<th style="background-color:#F29208">'.__('Wednesday','rsvpmaker').'</th>
<th style="background-color:#F29208">'.__('Thursday','rsvpmaker').'</th>
<th style="background-color:#F29208">'.__('Friday','rsvpmaker').'</th>
<th style="background-color:#F29208">'.__('Saturday','rsvpmaker').'</th>
</tr>
</thead>
';

$content .= "\n<tbody><tr id=\"rsvprow1\">\n";
$rowcount = 1;
// Generate the requisite number of blank days to get things started
for ($days = $i = date("w",$bom); $i > 0; $i--) {
   $content .= '<td class="notaday">&nbsp;</td>';
}

$todaydate = date('Y-m-d');
// Print out all the days in this month
for ($i = 1; $i <= date("t",$bom); $i++) {

   // Print out day number and all events for the day
	$thisdate = date("Y-m-",$bom).sprintf("%02d",$i);
	$class = ($thisdate == $todaydate ) ? 'today day' : 'day';
	if($thisdate < $todaydate)
		$class .= ' past';
   $content .= '<td valign="top" class="'.$class.'">';
   if(!empty($eventarray[$thisdate]) )
   {
   $content .= $i;
   $content .= $eventarray[$thisdate];
   $t = strtotime($thisdate);
   }
   else
   	$content .= '<div class="'.$class.'">' . $i . "</div><p>&nbsp;</p>";
   $content .= '</td>';

   // Break HTML table row if at end of week
   if (++$days % 7 == 0)
   	{
		$content .= "</tr>\n";
		$rowcount++;
		$content .= '<tr id="rsvprow'.$rowcount.'">';
	}
}

// Generate the requisite number of blank days to wrap things up
for (; $days % 7; $days++) {
   $content .= '<td class="notaday">&nbsp;</td>';
}

$content .= "\n</tr>";
$content .= "<tbody>\n";

// End HTML table of events
$content .= "\n</table>\n";


if($nav != 'top') // either it's bottom or both
$content .= '<div style="float: right;" class="nav"><span class="navprev">'. $prev_link. '</span> / <span class="navnext">'.
     '' . $next_link . "</span></div>";


// '<a href=\"'+ $(this).attr('href') + '\">' +  $(this).html() + '</a>', //from params
$calj = "

    $( '.calendar_item' ).tooltip({
        show: null, // show immediately
        position: { my: \"right top\", at: \"left top\" },
        content: $(this).html(),
        hide: { effect: \"\" }, //fadeOut
        close: function(event, ui){
            ui.tooltip.hover(
                function () {
                    $(this).stop(true).fadeTo(400, 1);
                },
                function () {
                    $(this).fadeOut(\"400\", function(){
                        $(this).remove();
                    })
                }
            );
        }
    });

";

if(!empty($calj))
	{
$content .= '<script>
jQuery(document).ready(function($) {
'.$calj.'

});
</script>
';
	}

return $content;
}


function rsvpmaker_template_fields($select) {
  $select .= ", meta_value as sked";
  return $select;
}

function rsvpmaker_template_join($join) {
  global $wpdb;
  $join .= " JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID ";

  return $join;
}

function rsvpmaker_template_where($where) {

	return $where . " AND meta_key='_sked'";

}

function rsvpmaker_template_orderby($orderby) {
  return " post_title ";
}

function rsvpmaker_template_events_where($where) {
global $rsvptemplate;
	if(isset($_GET["t"]))
		$rsvptemplate = (int) $_GET["t"];
	if(!$rsvptemplate)
		return $where;
	return $where . " AND meta_key='_meet_recur' AND meta_value=$rsvptemplate";
}

//utility function, template tag
function is_rsvpmaker() {
global $post;
if($post->post_type == 'rsvpmaker')
	return true;
else
	return false;
}

function rsvpmaker_timed ($atts, $content) {
if(isset($atts['start']) && !empty($atts['start']))
	{
		$start = strtotime($atts['start']);
		$now = current_time('timestamp');
		if($now < $start)
			{
				if($_GET["debug"])
					return sprintf('<p>start %s / now %s</p>',date('r',$start), date('r',$now));
				elseif(isset($atts["too_early"]))
					return '<p>'.$atts["too_early"].'</p>';
				else
					return '';
			}
	}
if(isset($atts['end']) && !empty($atts['end']))
	{
		$end = strtotime($atts['end']);
		$now = current_time('timestamp');
		if($now > $end)
			{
				if($_GET["debug"])
					return sprintf('<p>end %s / now %s</p>',date('r',$end), date('r',$now));
				elseif(isset($atts["too_late"]))
					return '<p>'.$atts["too_late"].'</p>';
				else
					return '';
			}
	}

// if we clear these two hurdles, return the content unchanged.
return $content;

}

add_shortcode('rsvpmaker_timed','rsvpmaker_timed');

add_shortcode('rsvpmaker_looking_ahead','rsvpmaker_looking_ahead');

function rsvpmaker_looking_ahead($atts) {
global $last_time;
global $events_displayed;

$limit = isset($atts["limit"]) ? $atts["limit"] : 10;

if(!$last_time)
	return 'last time not found';

$results = get_future_events("meta_value > '".date('Y-m-d',$last_time)."' AND meta_value < DATE_ADD('".date('Y-m-d',$last_time)."',INTERVAL 6 WEEK)", $limit, ARRAY_A);

foreach($results as $row)
	{
	if(in_array($row["postID"], $events_displayed) )
		continue;

	$t = strtotime($row["datetime"]);
	if($dateline[$row["postID"]])
		$dateline[$row["postID"]] .= ", ";
	$dateline[$row["postID"]] .= date('M. j',$t);
	$eventlist[$row["postID"]] = $row;
	}

//strpos test used to catch either "headline" or "headlines"
if($eventlist)
{
foreach($eventlist as $event)
	{
	if($atts["permalinkhack"])
		$permalink = site_url() ."?p=".$event["postID"];
	else
		$permalink = get_post_permalink($event["postID"]);
	$listings .= sprintf('<li><a href="%s"><font color="#2EA3F2">%s</font></a> %s</li>'."\n",$permalink,$event["post_title"],$dateline[$event["postID"]].'<hr>');
	}

	if($rsvp_options["eventpage"])
		$listings .= '<li><a href="'.$rsvp_options["eventpage"].'"><font color="blue"><u>'.__("Go to Events Page",'rsvpmaker')."</font></u></a></li>";

	if($atts["title"])
		$listings = "<p><strong>".$atts["title"]."</strong></p>\n<ul id=\"eventheadlines\">\n$listings</ul>\n";
	else
		$listings = "<ul id=\"eventheadlines\">\n$listings</ul>\n";
}//end if $eventlist
return $listings;
}

function get_adjacent_rsvp_join($join) {
global $post;
if($post->post_type != 'rsvpmaker')
	return $join;
global $wpdb;
return $join .' JOIN '.$wpdb->postmeta.' ON p.ID='.$wpdb->postmeta.".post_id AND meta_key='_rsvp_dates' ";
}

add_filter('get_previous_post_join','get_adjacent_rsvp_join');
add_filter('get_next_post_join','get_adjacent_rsvp_join');

function get_adjacent_rsvp_sort($sort) {
global $post;
if($post->post_type != 'rsvpmaker')
	return $sort;
global $wpdb;
$sort = str_replace('p.post_date',$wpdb->postmeta.'.meta_value',$sort);

return $sort;
}
add_filter('get_previous_post_sort','get_adjacent_rsvp_sort');
add_filter('get_next_post_sort','get_adjacent_rsvp_sort');


function get_adjacent_rsvp_where($where) {
global $post;
if($post->post_type != 'rsvpmaker')
	return $where;
global $wpdb;
$op = strpos($where, '>') ? '>' : '<';
$current_event_date = $wpdb->get_var("select meta_value from ".$wpdb->postmeta." WHERE meta_key='_rsvp_dates' AND post_id=".$post->ID);
//split and modify
$wparts = explode('p.post_type',$where);//

$where = "WHERE ".$wpdb->postmeta.".meta_value $op '$current_event_date' AND p.ID != $post->ID AND p.post_type".$wparts[1];
return $where;
}

add_filter('get_previous_post_where','get_adjacent_rsvp_where');
add_filter('get_next_post_where','get_adjacent_rsvp_where');

// based on https://gist.github.com/hugowetterberg/81747
function rsvp_ical_split($preamble, $value) {
  $value = trim($value);
  $value = strip_tags($value);
  $value = str_replace("\n", "\\n", $value);
  $value = str_replace("\r", "", $value);
  $value = preg_replace('/\s{2,}/', ' ', $value);
  $preamble_len = strlen($preamble);
  $lines = array();
  while (strlen($value)>(75-$preamble_len)) {
    $space = (75-$preamble_len);
    $mbcc = $space;
    while ($mbcc) {
      $line = mb_substr($value, 0, $mbcc);
      $oct = strlen($line);
      if ($oct > $space) {
        $mbcc -= $oct-$space;
      }
      else {
        $lines[] = $line;
        $preamble_len = 1; // Still take the tab into account
        $value = mb_substr($value, $mbcc);
        break;
      }
    }
  }
  if (!empty($value)) {
    $lines[] = $value;
  }
  return join($lines, "\n\t");
}

function rsvpmaker_to_ical () {
global $post;
global $rsvp_options;
global $wpdb;
if(($post->post_type != 'rsvpmaker') )
	return;
header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $post->post_name.'.ics');
$sql = "SELECT * FROM ".$wpdb->postmeta." WHERE meta_key='_rsvp_dates' AND post_id=".$post->ID.' ORDER BY meta_value';
$daterow = $wpdb->get_row($sql);
$duration = get_utc_ical ( (strpos($daterow->duration,'-') ) ? $daterow->duration : $daterow->datetime . ' +1 hour' );
$start = get_utc_ical ($daterow->datetime);
$hangout = get_post_meta($post->ID, '_hangout',true);
$url = (!empty($hangout)) ? $hangout : get_permalink($post->ID);

$desc = '';
if(!empty($hangout))
	$desc = "Google Hangout: ".$hangout." ";
$desc .= "Event info:" . get_permalink($post->ID);
$desc = rsvp_ical_split("DESCRIPTION:", $desc);

printf('BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTEND:%s
UID:%s
DTSTAMP:%s
DESCRIPTION:%s
URL;VALUE=URI:%s
SUMMARY:%s
DTSTART:%s
ORGANIZER;CN=%s:MAILTO:%s
END:VEVENT
END:VCALENDAR
',$duration,$start.'-'.$post->ID.'@'.$_SERVER['SERVER_NAME'],date('Ymd\THis\Z'), $desc, $url, $post->post_title, $start, get_bloginfo('name'), $rsvp_options["rsvp_to"]);
exit;
}

if(isset($_GET["ical"]))
	add_action('wp','rsvpmaker_to_ical');

function rsvpmaker_to_gcal($post,$datetime,$duration) {
return sprintf('http://www.google.com/calendar/event?action=TEMPLATE&text=%s&dates=%s/%s&details=%s&location=&trp=false&sprop=%s&sprop=name:%s',urlencode($post->post_title),get_utc_ical ($datetime),get_utc_ical ($duration), urlencode(get_bloginfo('name')),get_permalink($post->ID), urlencode(get_bloginfo('name')) );
}

function get_utc_ical ($timestamp) {
return gmdate('Ymd\THis\Z', strtotime($timestamp));
}

function rsvp_row_to_profile($row) {
if(empty($row["details"]) )
	$profile = array();
else
	$profile = unserialize($row["details"]);
foreach($row as $field => $value)
	{
		if(isset($profile[$field]) || ($field == 'details') )
			continue;
		else
			$profile[$field] = $value;
	}
return $profile;
}

function rsvpmaker_type_dateorder ( $sql ) {
echo $sql;
return $sql;
}

function rsvpmaker_archive_pages ($query) {
	if(is_admin())
		return;
	if(is_archive() && ($query->query["post_type"] == 'rsvpmaker'))
	{
	add_filter('posts_join', 'rsvpmaker_join' );
	add_filter('posts_groupby', 'rsvpmaker_groupby' );
	add_filter('posts_distinct', 'rsvpmaker_distinct' );
	add_filter('posts_fields', 'rsvpmaker_select' );
	add_filter('posts_where', 'rsvpmaker_where' );
	add_filter('posts_orderby', 'rsvpmaker_orderby' );
	}
	if(is_archive() && !empty($query->query["rsvpmaker-type"]))
	{
	add_filter('posts_join', 'rsvpmaker_join' );
	add_filter('posts_groupby', 'rsvpmaker_groupby' );
	add_filter('posts_distinct', 'rsvpmaker_distinct' );
	add_filter('posts_fields', 'rsvpmaker_select' );
	add_filter('posts_where', 'rsvpmaker_where' );
	add_filter('posts_orderby', 'rsvpmaker_orderby' );
	}
}

add_action( 'pre_get_posts', 'rsvpmaker_archive_pages' );

function get_rsvpmaker_archive_link($page = 1) {
$link = get_post_type_archive_link('rsvpmaker');
$link .= (strpos($link,'?')) ? '&paged='.$page : '?paged='.$page;
return $link;
}
?>
