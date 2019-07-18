<?php
function parseDate($date, $offset){return (strtotime($date->date.' '.$date->time.':00') + $offset);}

function response($status, $data){return new WP_REST_Response(array('status' => $status, 'data' => $data), 200);}

function getUserID($key){
	$sql = $GLOBALS['wpdb']->prepare('SELECT user_id FROM '.$GLOBALS["sessionsTable"].' WHERE session_key="%s"', $key);
	return $GLOBALS['wpdb']->get_var($sql);
}
function getUsername($userID){
	$sql = $GLOBALS['wpdb']->prepare('SELECT user_login FROM '.$GLOBALS["usersTable"].' WHERE ID="%s"', $userID);
	return $GLOBALS['wpdb']->get_var($sql);
}

function newSession($userID){
	$token = uniqid();
	$expTime = time() + 60*60*3;
	$res = $GLOBALS['wpdb']->replace($GLOBALS["sessionsTable"], array('user_id' => $userID, 'session_key' => $token, 'expDate' => $expTime));
	return $res === false ? false : $token;
}

function autoLogin(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	$sql = $GLOBALS['wpdb']->prepare('SELECT user_id as userID, expDate FROM '.$GLOBALS["sessionsTable"].' WHERE session_key="%s"', $data->token);
	$session = $GLOBALS['wpdb']->get_row($sql);
	if($session === null || time() > (int)$session->expDate) return response(false, "");
	$userInfo = array('token' => newSession($session->userID), 'name' => getUsername($session->userID));
	return response(true, $userInfo);
}

function login(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	$user = wp_authenticate($data->username, $data->password);
	if(is_wp_error($user)) return response(false, "Error: invalid username or password");
	return response(true, newSession($user->ID));
}

function newVote(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	$data->date = time();
	$res = $GLOBALS['wpdb']->insert($GLOBALS["voteTable"],
		array( 'creation_date' => $data->date, 'last_update' => $data->date, 'title' => $data->title, 
		'suggestion_closes' => parseDate($data->suggestionsCloses, $data->data->timeOffset),
		'voting_closes' => parseDate($data->votingCloses, $data->data->timeOffset), 'max_suggestions' => $data->limit, 
		'rating_range' => $data->ratingRange, 'vote_during_suggestions' => $data->voteDuringSuggestions));
	return response($res === 1, $data);
}

function isOpen($dateID, $field){
	$sql = $GLOBALS['wpdb']->prepare('SELECT '.$field.' FROM '.$GLOBALS["voteTable"].' WHERE creation_date="%s"', $dateID);
	$date = $GLOBALS['wpdb']->get_var($sql);
	return time() < $date;
}

function updateVote(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	$res = $GLOBALS['wpdb']->update($GLOBALS["voteTable"],
		array('title' => $data->data->title, 'last_update' => time(), 
		'suggestion_closes' => parseDate($data->data->suggestionsCloses, $data->data->timeOffset), 'voting_closes' => parseDate($data->data->votingCloses, $data->data->timeOffset), 
		'max_suggestions' => $data->data->limit, 'rating_range' => $data->data->ratingRange, 'vote_during_suggestions' => $data->data->voteDuringSuggestions),
		array('creation_date' => $data->date)
	);
	return response($res === 1, $data);
}
function getCurrentVote(WP_REST_Request $request){
	$res = $GLOBALS['wpdb']->get_var('SELECT MAX(last_update) FROM '.$GLOBALS["voteTable"]);
	$sql = $GLOBALS['wpdb']->prepare('SELECT creation_date FROM '.$GLOBALS["voteTable"].' WHERE last_update="%s"', $res);
	$res = $GLOBALS['wpdb']->get_var($sql);
	return response($res !== null, $res);
}
function getVoteData(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	$sql = $GLOBALS['wpdb']->prepare('SELECT creation_date as date, title, suggestion_closes AS suggestionsCloses, voting_closes AS votingCloses, 
	max_suggestions AS "limit", rating_range AS ratingRange, vote_during_suggestions as voteDuringSuggestions FROM '.$GLOBALS["voteTable"].' WHERE creation_date="%s"', $data->date);
	$res = $GLOBALS['wpdb']->get_row($sql);
	return response($res !== null, $res);
}

function deleteVote(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	$res = $GLOBALS['wpdb']->delete($GLOBALS["itemsTable"], array('vote_id' => $data->date));
	$res = $GLOBALS['wpdb']->delete($GLOBALS["userVotesTable"], array('vote_id' => $data->date));
	$res = $GLOBALS['wpdb']->delete($GLOBALS["voteTable"], array('creation_date' => $data->date));
	return response(true, $res);
}

function getItems(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	$sql = $GLOBALS['wpdb']->prepare('SELECT items.item_name as "name", items.link as link, users.user_login as user 
	FROM '.$GLOBALS["itemsTable"].' items,'.$GLOBALS["usersTable"].' users WHERE items.vote_id="%s" and users.ID=items.user_id', $data->date);
	$res = $GLOBALS['wpdb']->get_results($sql);
	return response($res === 1, $res);
}

function userOverLimit($userID, $voteID){
	$sql = $GLOBALS['wpdb']->prepare('SELECT max_suggestions FROM '.$GLOBALS["voteTable"].' WHERE creation_date="%s"', $voteID);
	$limit = $GLOBALS['wpdb']->get_var($sql);
	if($limit < 1) return false;
	$sql = $GLOBALS['wpdb']->prepare('SELECT COUNT(*) FROM '.$GLOBALS["itemsTable"].' WHERE vote_id="%s" AND user_id="%s"', $voteID, $userID);
	$userCount = $GLOBALS['wpdb']->get_var($sql);
	return $userCount >= $limit;

}

function addItem(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	if(isOpen($data->date, 'suggestion_closes') == false) return response(false, "suggestions have closed");
	$userID = getUserID($data->token);
	if(userOverLimit($userID, $data->date)) return response(false, "user reached suggestion limit");
	$res = $GLOBALS['wpdb']->insert($GLOBALS["itemsTable"], array('item_name' => $data->item->name, 'link' => $data->item->link, 'user_id' => $userID, 'vote_id' => $data->date));
	return response($res === 1, "");
}

function userOwnsItem($userID, $itemName, $voteID){
	$sql = $GLOBALS['wpdb']->prepare('SELECT user_id FROM '.$GLOBALS["itemsTable"].' WHERE vote_id="%s" AND item_name="%s"', $voteID, $itemName);
	$owner = $GLOBALS['wpdb']->get_var($sql);
	return $userID === $owner;
}

function deleteItem(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	if(userOwnsItem(getUserID($data->token), $data->name, $data->date) !== true) return response(false, "user cannot delete this item");
	$res = $GLOBALS['wpdb']->delete($GLOBALS["itemsTable"], array("vote_id" => $data->date, 'item_name' => $data->name));
	return response($res !== false && $res > 0, "Successful response!");
}

function vote(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	$userID = getUserID($data->token);
	$res = $GLOBALS['wpdb']->replace($GLOBALS["userVotesTable"],
		array( 'user_vote' => $data->vote, 'user_id' => $userID, 'item_name' => $data->item, 'vote_id' => $data->date));
	
	return response($res > 0, $res);
}

function getVotes(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	$userID = getUserID($data->token);
	$sql = $GLOBALS['wpdb']->prepare('SELECT item_name AS "name", user_vote as vote FROM '.$GLOBALS["userVotesTable"].' 
	WHERE vote_id="%s" AND user_id="%s"', $data->date, $userID);
	$res = $GLOBALS['wpdb']->get_results($sql, 'OBJECT_K');
	return response(true, $res);
}

function getScores(WP_REST_Request $request){
	$data = json_decode($request->get_body());
	$data->date = esc_sql($data->date);
	$maxRating = $GLOBALS['wpdb']->get_var('SELECT rating_range FROM '.$GLOBALS["voteTable"].' WHERE creation_date='.$data->date);
	$items = $GLOBALS['wpdb']->get_results('SELECT item_name as name FROM '.$GLOBALS["itemsTable"].' WHERE vote_id='.$data->date, 'OBJECT_K');
	$numUsers = $GLOBALS['wpdb']->get_var('SELECT COUNT(DISTINCT user_id) FROM '.$GLOBALS["userVotesTable"].' WHERE vote_id='.$data->date);
	$votes = $GLOBALS['wpdb']->get_results('SELECT item_name as name, user_vote as vote FROM '.$GLOBALS["userVotesTable"].' WHERE vote_id='.$data->date);
	foreach ($items as $item){$item->score = (int)$numUsers;};
	foreach ($votes as $vote){$items[$vote->name]->score += min($vote->vote, $maxRating) - 1;};
	usort($items, function($a, $b){ return $b->score - $a->score; });
	return response(true, $items);
}

function getPastVotesList(){
	$res = $GLOBALS['wpdb']->get_results('SELECT title AS name, creation_date AS date FROM '.$GLOBALS["voteTable"].' ORDER BY last_update DESC');
	return response(true, $res);
}

?>