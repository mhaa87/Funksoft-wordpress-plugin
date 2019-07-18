<?php
function fvs_install(){
	global $wpdb, $voteTable, $itemsTable, $userVotesTable, $sessionsTable;
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql_votes = "CREATE TABLE $voteTable (
		creation_date VARCHAR(100) NOT NULL,
		title TINYTEXT NULL,
		suggestion_closes TINYTEXT NULL,
		voting_closes TINYTEXT NULL,
		max_suggestions INT(11) NULL DEFAULT NULL,
		rating_range INT(11) NULL DEFAULT NULL,
		last_update VARCHAR(100) NULL,
		vote_during_suggestions BOOLEAN NULL,
		PRIMARY KEY  (creation_date)
	) $charset_collate;";

	$sql_vote_items = "CREATE TABLE $itemsTable (
		item_name VARCHAR(500) NOT NULL,
		link TINYTEXT NOT NULL,
		user_id VARCHAR(100) NOT NULL,
		vote_id VARCHAR(100) NOT NULL,
		PRIMARY KEY  (item_name(500), vote_id)
	) $charset_collate;";

	$sql_user_votes = "CREATE TABLE $userVotesTable (
		user_vote INT(11) NOT NULL,
		item_name VARCHAR(500) NOT NULL,
		user_id VARCHAR(100) NOT NULL,
		vote_id VARCHAR(100) NOT NULL,
		PRIMARY KEY  (item_name(500), user_id, vote_id)
	) $charset_collate;";

	$sql_user_sessions = "CREATE TABLE $sessionsTable (
		user_id VARCHAR(100) NOT NULL,
		session_key VARCHAR(500) NOT NULL,
		expDate TINYTEXT NULL,
		PRIMARY KEY  (user_id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql_votes );
	dbDelta( $sql_vote_items );
	dbDelta( $sql_user_votes );
	dbDelta( $sql_user_sessions );
}

?>