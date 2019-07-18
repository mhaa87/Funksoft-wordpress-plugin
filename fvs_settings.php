<?php
$themeColor = "#41a62a";
$voteColor = "#FFDF00";
$bgColor = "black";
$textColor = "white";

function fvs_register_settings() {
	register_setting( 'fvs_options_group', 'fvs_settings');
}

function fvs_register_options_page() {
	add_options_page('Voting System Settings', 'Voting System', 'manage_options', 'fvs', 'fvs_options_page');
}

function fvs_options_page(){
	wp_enqueue_script('wp-color-picker');
	wp_enqueue_style( 'wp-color-picker' );?>
<div class="wrap">
	<h1>Voting System Settings:</h1>
	<form method="post" action="options.php">
	<?php settings_fields( 'fvs_options_group' );?>
	<table>
		<tr>
			<th scope="row"><label for="fvs_settings">Theme Color:</label></th>
			<td><input type="text" value=<?php echo get_setting_option('theme_color') ?> name="fvs_settings[theme_color]" id="theme_color_field"/></td>
		</tr><tr>
			<th scope="row"><label for="fvs_settings">Text Color:</label></th>
			<td><input type="text" value=<?php echo get_setting_option('text_color') ?> name="fvs_settings[text_color]" id="text_color_field"/></td>
		</tr><tr>
			<th scope="row"><label for="fvs_settings">Background Color:</label></th>
			<td><input type="text" value=<?php echo get_setting_option('background_color') ?> name="fvs_settings[background_color]" id="bg_color_field"/></td>
		</tr><tr>
			<th scope="row"><label for="fvs_settings">Vote Button Color:</label></th>
			<td><input type="text" value=<?php echo get_setting_option('vote_color') ?> name="fvs_settings[vote_color]" id="vote_color_field"/></td>
		</tr><tr>
			<th scope="row"><label for="fvs_settings">Title Color:</label></th>
			<td><input type="text" value=<?php echo get_setting_option('title_color') ?> name="fvs_settings[title_color]" id="title_color_field"/></td>
		</tr><tr>
			<th scope="row"><label for="fvs_settings">Title Background Color:</label></th>
			<td><input type="text" value=<?php echo get_setting_option('title_bg_color') ?> name="fvs_settings[title_bg_color]" id="title_bg_color_field"/></td>
		</tr>

	</table>
	<script type="text/javascript">
		jQuery(document).ready(function($) {$('#theme_color_field').wpColorPicker();});
		jQuery(document).ready(function($) {$('#text_color_field').wpColorPicker();}); 
		jQuery(document).ready(function($) {$('#bg_color_field').wpColorPicker();}); 
		jQuery(document).ready(function($) {$('#vote_color_field').wpColorPicker();});
		jQuery(document).ready(function($) {$('#title_color_field').wpColorPicker();});
		jQuery(document).ready(function($) {$('#title_bg_color_field').wpColorPicker();});  
    </script>
	<?php  submit_button(); ?>
	</form>
</div>
<?php }

function get_setting_option($name){
	$options = get_option('fvs_settings');
	if (array_key_exists($name, $options)) return $options[$name];
	if($name === 'theme_color') return '#41a62a';
	if($name === 'text_color') return '#FFFFFF';
	if($name === 'background_color') return '#000000';
	if($name === 'vote_color') return '#FFDF00';
	if($name === 'title_color') return '#000000';
	if($name === 'title_bg_color') return '#FFFFFF';
	return '#696969';
}

function get_style_colors(){
	echo '<style> :root {
		--fvs_theme_color: '.get_setting_option('theme_color').';
		--fvs_bg_color: '.get_setting_option('background_color').';
		--fvs_text_color: '.get_setting_option('text_color').';
		--fvs_vote_color: '.get_setting_option('vote_color').';
		--fvs_title_color: '.get_setting_option('title_color').';
		--fvs_title_bg: '.get_setting_option('title_bg_color').';
	} </style>';
}

?>