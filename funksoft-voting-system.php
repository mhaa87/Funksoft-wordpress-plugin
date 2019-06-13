<?php
/**
 * Plugin Name: FunkSoft Voting System
 * Plugin URI: https://hegeb.cloudaccess.host
 * Description: FunkSoft Voting System
 * Version: 1.0
 * Author: Your Name
 */
function content() {
// 	echo (plugins_url('content.php',__FILE__ ));
	// $htmlContent = file_get_contents(plugins_url('content.php',__FILE__ ));
	$htmlContent = file_get_contents(plugins_url('content.php',__FILE__ ));
	echo $htmlContent;

}

add_action('wp_enqueue_scripts', 'callback_for_setting_up_scripts');

function callback_for_setting_up_scripts() {
    wp_register_style( 'appStyle', (plugins_url('style.css',__FILE__ )));
    wp_enqueue_style( 'appStyle' );
	wp_register_style( 'voteButtons', (plugins_url('voteButtons.css',__FILE__ )));
    wp_enqueue_style( 'voteButtons' );
	wp_enqueue_script( 'axios', 'https://unpkg.com/axios/dist/axios.min.js', array( 'jquery' ) );
    wp_enqueue_script( 'vue', 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js', array( 'jquery' ) );
	wp_enqueue_script( 'app', (plugins_url('app.js',__FILE__ )), array( 'jquery' ), 'version 1', true);
}

add_shortcode('voteSystem', 'content');


?>