<?php
/*
Plugin Name: Greatest Posts
Plugin URI: https://tannich.com
Description: This plugin counts the page views from every blog post and shows the most viewed.
Version: 1.0
Author: Marcel Tannich
Author URI: https://tannich.com
*/
?>

<?php

add_action( 'widgets_init', 'gp_widget_load' );
add_action( 'wp_footer', 'mt_gp_counter' );
register_activation_hook( __FILE__, 'mt_gp_install' );

// How many posts you want to show?
$default_number = 5;

function mt_gp_install () {

    global $wpdb;

    // determine table name
    $table_name = $wpdb->prefix . "greatest_posts";
    
    // Does the table exists?
    if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {

        // if no, create table
        $sql = "CREATE TABLE " . $table_name . " (
                post_id bigint(11) NOT NULL,
                post_views bigint(11) NOT NULL,
                UNIQUE KEY post_id (post_id)
                );";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

    }

}

function mt_gp_counter () {
    
    // call function only on posts
    if ( is_single() ) {

        global $wpdb;
        global $post;
        
        $post_id = $wpdb->escape( $post->ID );
        
        $table_name = $wpdb->prefix . "greatest_posts";
        
        // call code only when table exists
        if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name ) {
        
            // Checks if post-id exists
            $result = $wpdb->query( "SELECT * FROM " . $table_name . " WHERE post_id = '" . $post_id . "'" );
            
            // if yes, update entry and post_views +1
            if ( $result ) { 
                $insert = $wpdb->query( "UPDATE " . $table_name . " SET post_views = post_views + 1 WHERE post_id = '" . $post_id . "'" );
            }
            
            // if no, creaty entry and set to 1
            else {
                $insert = $wpdb->query( "INSERT INTO " . $table_name . " SET post_id = '" . $post_id . "', post_views = 1" );
            }
        
        }
        
    }
    
}

function mt_gp_get_top_posts ( $number = false ) {

    global $wpdb;
    
    
    // If the widget is not used, set default value
    if ( $number == false ) {

        global $default_number;
        $number = $default_number;
    
    }
    
    $table_name = $wpdb->prefix . "greatest_posts";
    
    // call code only when table exists
    if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
    
        // Get posts with most page views
        $posts = $wpdb->get_results( "SELECT * FROM " . $table_name . " ORDER BY post_views DESC LIMIT " . $number, ARRAY_A );
        
        
        // output posts as list
        $output = "<ul>";
        foreach ( $posts as $entry ) {
            $the_post = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "posts WHERE ID = '" . $entry["post_id"] . "'" );
            $content = substr(htmlentities($the_post->post_content), 0, 100);
            $content .= "...";
            $output .= "<li><a href='" . get_permalink( $entry["post_id"] ) . "'><span class='pop-title'>" . $the_post->post_title  . "</span> | <span class='pop-excerpt'>" . $content . "</span></a></li>";
        }
        $output .= "</ul>";
        
        return $output;
    
    }
    
}


function gp_widget_load () {

    register_widget( 'GP_Widget' );
    
}

class GP_Widget extends WP_Widget
{

function GP_Widget () {
    
    $widget_options = array( 'classname' => 'gpwidget', 'description' => 'Output the most popular blog posts' );
    
    $this->WP_Widget( 'greatest-posts', 'Greatest Posts', $widget_options );
    
}

function widget ( $args, $instance ) {
    extract( $args );
    
    // options set from user
    $title = apply_filters( 'widget_title', $instance['title'] );
    $number = $instance['number'];
    
    //output before widget
    echo $before_widget;
    
    // output widget title
    if ( $title ) {
        echo $before_title . $title . $after_title;
    }
    
    // output list of posts
    echo mt_gp_get_top_posts( $number );
    
    //  output after widget
    echo $after_widget;
    
}

function update ( $new_instance, $old_instance ) {

    $instance = $old_instance;
    
    // clean user input
    $instance['title'] = strip_tags( $new_instance['title'] );
    $instance['number'] = strip_tags( $new_instance['number'] );
    
    return $instance;

}

function form ( $instance ) {

    // set default value
    $defaults = array( 'title' => 'Greatest Posts', 'number' => '5' );
    
    $instance = wp_parse_args( (array) $instance, $defaults );
    
    ?>
    
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Titel:</label>
            <input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>">Anzahl der Artikel:</label>
            <input type="text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $instance['number']; ?>" class="widefat" style="width:20%;" />
        </p>
    
    <?php
}
    
}

?>