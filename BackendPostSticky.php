<?php

/**
 * @package BackendPostSticky
 * @version 1.0
 */
/*
Plugin Name: Backend Post Sticky
Description: Be able to stick a post to the top of your post listing in the admin section.
Author: Ellen Langelaar
Version: 1.0
Author URI: http://www.ellenlangelaar.nl
*/

/* Fire our meta box setup function on the post editor screen. */
add_action( 'load-post.php', 'sticky_post_setup' );
add_action( 'load-post-new.php', 'sticky_post_setup' );

/* Meta box setup function. */
function sticky_post_setup() {
  /* Add meta boxes on the 'add_meta_boxes' hook. */
  add_action( 'add_meta_boxes', 'sticky_add_post_meta_boxes' );
  
  /* Save post meta on the 'save_post' hook. */
  add_action( 'save_post', 'sticky_save_post_meta', 10, 2 );
}

/* Check if all posts have sticky_post meta data */
function sticky_post_on_activation() {
    global $wpdb;

    // Using $wpdb grab all of the posts that we want to update
    $query = "SELECT * FROM $wpdb->posts";
    $posts = $wpdb->get_results( $query );
    foreach( $posts as $post ){
        // Make sure all the posts start with a sticky value of 0
        update_post_meta($post->ID, 'sticky_post', 0);
    }
}
register_activation_hook( __FILE__, 'sticky_post_on_activation' );

/* Create one or more meta boxes to be displayed on the post editor screen. */
function sticky_add_post_meta_boxes() {
  add_meta_box(
    'sticky-post-set',      // Unique ID
    esc_html__( 'Stick Post To Top', 'example' ),    // Title
    'sticky_show_meta_box',   // Callback function
    'post',         // Admin page (or post type)
    'side',         // Context
    'high'         // Priority
  );
}

/* Display the post meta box. */
function sticky_show_meta_box( $post ) { 
 wp_nonce_field( basename( __FILE__ ), 'sticky_post_nonce' ); ?>

  <p>
    <?php $isChecked = esc_attr( get_post_meta( $post->ID, 'sticky_post', true )); ?>
    <input class="widefat" type="checkbox" name="sticky_post" id="sticky_postt" <?php echo $isChecked == null || $isChecked == 0 ? '' : 'checked'; ?> size="30" />
    <label for="sticky_post_set"><?php _e( "Make sticky", 'example' ); ?></label>
  </p>

<?php }


/* Save the meta box's post metadata. */
function sticky_save_post_meta( $post_id, $post ) {
    // verify nonce
    if (!wp_verify_nonce($_POST['sticky_post_nonce'], basename(__FILE__))) 
        return $post_id;
    // check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $post_id;
    // check permissions
    if ('page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id))
            return $post_id;
        } elseif (!current_user_can('edit_post', $post_id)) {
            return $post_id;
    }
    // get post value
    $new = $_POST['sticky_post'] == 'on' ? 1 : 0;
    // update database
    update_post_meta($post_id, 'sticky_post', $new);
}

/* Add custom post order column to post list */
function add_sticky_post_order_column( $columns ){
    return array_merge ( $columns, array( 'sticky_post' => 'Sticky'));
}
add_filter('manage_posts_columns' , 'add_sticky_post_order_column');

/* Display custom post order in the post list */
function sticky_post_order_value( $column, $post_id ){
    if ($column == 'sticky_post' ){
        $isSticky = get_post_meta( $post_id, 'sticky_post', true) != 0 ? 'Y' : '';
        echo '<p>' . $isSticky . '</p>';
    }
}
add_action( 'manage_posts_custom_column' , 'sticky_post_order_value' , 10 , 2 );

/* Sort posts on the blog posts page according to the custom sort order */
function sticky_post_order_sort( $query ){
    $defautlOrderBy = $query->get('orderby') != '' ? $query->get('orderby') : 'date';
    $defautlOrder = $query->get('order') != '' ? $query->get('order') : 'DESC';
    
    if ( $query->is_main_query()){
        $query->set('meta_key', 'sticky_post' );
        $query->set( 'orderby', array( 'meta_value_num' => 'DESC', $defautlOrderBy => $defautlOrder ) );
    }
}
add_action( 'pre_get_posts' , 'sticky_post_order_sort' );


?>