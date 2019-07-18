<?php
/**
 * Plugin Name: FunkSoft Voting System
 * Plugin URI: https://hegeb.cloudaccess.host
 * Description: FunkSoft Voting System
 * Version: 1.1
 * Author: Your Name
 */
function content() {
	$htmlContent = file_get_contents(plugins_url('content.html',__FILE__ ));
	echo $htmlContent;
}
include "fvs_settings.php";
include "fvs_init_db.php";
include "fvs_backend.php";

global $wpdb;
global $sessionsTable;
global $voteTable;
global $itemsTable;
global $userVotesTable;
global $usersTable;
$sessionsTable = $wpdb->prefix . 'fvs_user_sessions';
$voteTable = $wpdb->prefix . 'fvs_votes';
$itemsTable= $wpdb->prefix . 'fvs_vote_items';
$userVotesTable = $wpdb->prefix . 'fvs_user_votes';
$usersTable = $wpdb->prefix . 'users';

// $mydb = new wpdb('itlzidcz', 'KFa*8B*51oMw4n', 'itlzidcz', 'hegeb.cloudaccess.host');
add_shortcode('voteSystem', 'content');
add_action('wp_enqueue_scripts', 'fvs_load_styles');
add_action('wp_enqueue_scripts', 'fvs_load_scripts');
add_action('rest_api_init', 'fvs_setup_API');
register_activation_hook( __FILE__, 'fvs_install' );
add_action( 'admin_init', 'fvs_register_settings' );
add_action('admin_menu', 'fvs_register_options_page');

function fvs_load_styles(){
    wp_register_style( 'appStyle', (plugins_url('style.css',__FILE__ )));
	wp_enqueue_style( 'appStyle' );
	$colorsCSS = get_style_colors();
	wp_add_inline_style( 'appStyle', $colorsCSS );
	wp_register_style( 'icons', ('https://fonts.googleapis.com/icon?family=Material+Icons'));
	wp_enqueue_style( 'icons' );
	wp_register_style( 'loadingIcon', (plugins_url('loadingIcon.css',__FILE__ )));
    wp_enqueue_style( 'loadingIcon' );
	wp_register_style( 'voteButtons', (plugins_url('voteButtons.css',__FILE__ )));
    wp_enqueue_style( 'voteButtons' );
}

function fvs_load_scripts( $hook_suffix ) {
	wp_enqueue_script( 'axios', 'https://unpkg.com/axios/dist/axios.min.js', array( 'jquery' ) );
    wp_enqueue_script( 'vue', 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js', array( 'jquery' ) );
	wp_enqueue_script( 'app', (plugins_url('app.js',__FILE__ )), array( 'jquery'), 'version 1', true);
	wp_localize_script( 'app', 'pluginInfo', array('url' => get_rest_url()));
}

function fvs_setup_API(){
	register_rest_route( 'funksoftvote/v1', '/autoLogin', array('methods' => 'POST','callback' => 'autoLogin',));
	register_rest_route( 'funksoftvote/v1', '/login', array('methods' => 'POST','callback' => 'login',));
	register_rest_route( 'funksoftvote/v1', '/newVote', array('methods' => 'POST','callback' => 'newVote',));
	register_rest_route( 'funksoftvote/v1', '/updateVote', array('methods' => 'POST','callback' => 'updateVote',));
	register_rest_route( 'funksoftvote/v1', '/getCurrentVote', array('methods' => 'POST','callback' => 'getCurrentVote',));
	register_rest_route( 'funksoftvote/v1', '/getVoteData', array('methods' => 'POST','callback' => 'getVoteData',));
	register_rest_route( 'funksoftvote/v1', '/deleteVote', array('methods' => 'POST','callback' => 'deleteVote',));
	register_rest_route( 'funksoftvote/v1', '/getItems', array('methods' => 'POST','callback' => 'getItems',));
	register_rest_route( 'funksoftvote/v1', '/addItem', array('methods' => 'POST','callback' => 'addItem',));
	register_rest_route( 'funksoftvote/v1', '/deleteItem', array('methods' => 'POST','callback' => 'deleteItem',));
	register_rest_route( 'funksoftvote/v1', '/vote', array('methods' => 'POST','callback' => 'vote',));
	register_rest_route( 'funksoftvote/v1', '/getVotes', array('methods' => 'POST','callback' => 'getVotes',));
	register_rest_route( 'funksoftvote/v1', '/getScores', array('methods' => 'POST','callback' => 'getScores',));
	register_rest_route( 'funksoftvote/v1', '/getPastVotesList', array('methods' => 'POST','callback' => 'getPastVotesList',));
}
?>