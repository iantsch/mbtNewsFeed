<?php
/**
 * mbtNewsFeed for purists
 *
 * @package     mbtNewsFeed
 * @author      iantsch
 * @copyright   2016 mbt.wien
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: mbtNewsFeed
 * Plugin URI:  http://wordpress.stackexchange.com/questions/41934/
 * Description: If you are logged in and follow some authors, the home feed will only show their posts.
 * Version:     1.0.1
 * Author:      iantsch
 * Author URI:  http://mbt.wien
 * Text Domain: newsfeed
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */


function mbt_news_feed_init() {
    if(array_key_exists('action', $_REQUEST) && in_array($_REQUEST['action'], mbt_news_feed_valid_actions())) {
        do_action( 'wp_ajax_' . $_REQUEST['action'] );
    }
}

function mbt_news_feed_pre_get_posts($query) {
    if (is_user_logged_in()) {
        if ( $query->is_main_query() && $query->is_home() && !is_admin()) {
            $current_user = wp_get_current_user();
            $followers = get_user_meta( $current_user->ID, 'following', true);
            if (is_array($followers) && !empty($followers)) {
                $followers = array_keys($followers);
                $followers[] = $current_user->ID;
                $query->set('author__in', $followers);
            }
        }
    }
    return $query;
}

function mbt_news_feed_is_following($user_id) {
    $followers = get_user_meta( $user_id, 'following', true);
    if (empty($followers)) {
        return false;
    }
    if (array_key_exists($user_id, $followers) && $followers[$user_id]) {
        return true;
    }
    return false;
}

function mbt_news_feed_helper_check_data() {
    if(!wp_verify_nonce( $_POST['_ajax_nonce'], 'mbtNewsFeed' )) {
        die(-1);
    }
    if (!array_key_exists('user_id', $_POST)) {
        die(-1);
    }
    $user_id = absint($_POST['user_id']);
    if ($user_id < 1) {
        die(-1);
    }
    return $user_id;
}

function mbt_follow() {
    $user_id = mbt_news_feed_helper_check_data();
    $current_user = wp_get_current_user();
    $followers = get_user_meta( $current_user->ID, 'following', true);
    if (!is_array($followers)){
        $followers = array();
    }
    $followers[$user_id] = 1;
    update_user_meta( $current_user->ID, 'following', $followers );
    echo mbt_news_feed_button($user_id, false);
    exit;
}

function mbt_unfollow() {
    $user_id = mbt_news_feed_helper_check_data();
    $current_user = wp_get_current_user();
    $followers = get_user_meta( $current_user->ID, 'following', true);
    if (is_array($followers) && !empty($followers)){
        unset($followers[$user_id]);
    } else {
        $followers = array();
    }
    update_user_meta( $current_user->ID, 'following', $followers );
    echo mbt_news_feed_button($user_id, true);
    exit;
}

function mbt_news_feed_button($user_id, $follow=true) {
    $class = 'unfollow';
    $label = __('Unfollow','newsfeed');
    if (true === $follow) {
        $class = 'follow';
        $label = __('Follow','newsfeed');
    }
    return "<a href='#mbt-news-feed' class='button {$class}' data-user_id='{$user_id}'>{$label}</a>";
}

function mbt_news_feed_valid_actions() {
    return array(
        'mbt_follow',
        'mbt_unfollow',
    );
}

function mbt_news_feed_enqueue_scripts() {
    if (is_user_logged_in()) {
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'admin_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mbtNewsFeed'),
        ));
        wp_enqueue_script( 'mbt-news-feed', plugins_url('newsfeed.js', __FILE__), array('jquery'), NULL, true);
        wp_enqueue_style( 'mbt-news-feed-css', plugins_url('newsfeed.css', __FILE__) );
    }
}

function mbt_news_feed_wp_footer() {
    if (is_user_logged_in()) {
        $render_button = false;
        $user_id = 0;
        if (is_singular()) {
            $render_button = true;
            $post_id = get_queried_object_id();
            $user_id = get_post_field( 'post_author', $post_id );
        }
        if (is_author()) {
            $render_button = true;
            $user_id = get_queried_object_id();
        }
        if (true === $render_button && $user_id > 0) {
            $follow = mbt_news_feed_is_following($user_id);
            echo "<div id='mbt-news-feed'>";
            echo mbt_news_feed_button($user_id, !$follow);
            echo "</div>";
        }
    }
}

if (is_admin()){
    foreach (mbt_news_feed_valid_actions() as $action) {
        add_action('wp_ajax_'.$action, $action);
    }
}

add_action( 'init', 'mbt_news_feed_init');
add_action( 'wp_enqueue_scripts', 'mbt_news_feed_enqueue_scripts');
add_action( 'pre_get_posts', 'mbt_news_feed_pre_get_posts');
add_action( 'wp_footer', 'mbt_news_feed_wp_footer');
