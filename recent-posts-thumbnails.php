<?php
/*
Plugin Name: Recent Posts with Thumbnails
Description: A custom widget that displays recent posts along with their featured images.
Version: 1.0.1
Author: NAVEED JAVED
*/

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Create the Widget Class extending WP_Widget
class RPT_Widget extends WP_Widget {

    // 2. Setup the Widget Name and Description
    public function __construct() {
        parent::__construct(
            'rpt_widget', // Base ID
            'Recent Posts with Thumbnails', // Name visible in Admin
            array( 'description' => __( 'Displays recent posts with featured images.', 'text_domain' ), ) 
        );
    }

    // 3. Front-end Display: What the visitor sees
    public function widget( $args, $instance ) {
        // Extract widget arguments (before_widget, after_widget, etc.)
        echo $args['before_widget'];

        // Get the title
        $title = ! empty( $instance['title'] ) ? $instance['title'] : 'Recent Posts';
        $title = apply_filters( 'widget_title', $title );

        // Output the title
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // Get the number of posts to show
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;

        // The Query
        $q_args = array(
            'posts_per_page'      => $number,
            'no_found_rows'       => true,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true
        );

        $the_query = new WP_Query( $q_args );

        if ( $the_query->have_posts() ) : ?>
            <ul class="rpt-posts-list">
                <?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
                    <li style="overflow: hidden; margin-bottom: 10px;">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="rpt-thumb" style="float: left; margin-right: 10px;">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'thumbnail', array( 'style' => 'width: 50px; height: 50px; object-fit: cover;' ) ); ?>
                                </a>
                            </div>
                        <?php else : ?>
                            <div class="rpt-thumb" style="float: left; margin-right: 10px;">
                                <a href="<?php the_permalink(); ?>">
                                    <img src="https://placehold.co/600x400?text=Demo" 
                                        width="50" height="50" 
                                        style="border-radius: 4px; object-fit: cover;" 
                                        alt="Demo Image">
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="rpt-content">
                            <a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
                            <br>
                            <small><?php echo get_the_date(); ?></small>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php 
        // Reset Post Data
        wp_reset_postdata();
        endif;

        echo $args['after_widget'];
    }

    // 4. Back-end Form: The settings in Appearance > Widgets
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        ?>
        
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>">Number of posts to show:</label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $number ); ?>" size="3">
        </p>
        <?php
    }

    // 5. Update: Saving the settings
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['number'] = ( ! empty( $new_instance['number'] ) ) ? absint( $new_instance['number'] ) : '';

        return $instance;
    }
}

// 6. Register the Widget
function register_rpt_widget() {
    register_widget( 'RPT_Widget' );
}
add_action( 'widgets_init', 'register_rpt_widget' );


/* * 7. Smart Sidebar Registration
 * Hooks in LATE (priority 99) to check if the theme registered anything.
 */
function rpt_smart_sidebar_registration() {
    // Access the global list of registered sidebars
    global $wp_registered_sidebars;

    // Check if the array is empty (Theme has NO sidebars)
    if ( empty( $wp_registered_sidebars ) ) {
        
        register_sidebar( array(
            'name'          => 'Plugin Fallback Sidebar',
            'id'            => 'rpt-fallback-sidebar',
            'description'   => 'This sidebar was enabled by the Recent Posts Plugin because your theme had no widget areas.',
            'before_widget' => '<div id="%1$s" class="rpt-widget-container %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="rpt-widget-title">',
            'after_title'   => '</h3>',
        ) );
        
    }
}
// Run this on 'widgets_init' but with priority 99 (after the theme runs)
add_action( 'widgets_init', 'rpt_smart_sidebar_registration', 99 );



/*
 * 8. Shortcode to Display the Sidebar
 * Usage: [rpt_sidebar]
 */
function rpt_sidebar_shortcode() {
    // Start output buffering (capture the HTML instead of printing it immediately)
    ob_start();
    
    // Check if our specific fallback sidebar is active
    if ( is_active_sidebar( 'rpt-fallback-sidebar' ) ) {
        echo '<div class="rpt-sidebar-wrapper" style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd;">';
        dynamic_sidebar( 'rpt-fallback-sidebar' );
        echo '</div>';
    } else {
        // Fallback message or check for other sidebars
        echo '<p>No widgets found in the fallback sidebar.</p>';
    }

    // Return the captured HTML
    return ob_get_clean();
}
add_shortcode( 'rpt_sidebar', 'rpt_sidebar_shortcode' );