<?php

// ensure contacts table exists
$DB->write("CREATE TABLE IF NOT EXISTS contacts (
	id INT AUTO_INCREMENT PRIMARY KEY,
	userid VARCHAR(64) NOT NULL,
	contactid VARCHAR(64) NOT NULL,
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY uniq_contact (userid, contactid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$myid = isset($_SESSION['userid']) ? strval($_SESSION['userid']) : null;

if(!$myid){
	$info = new stdClass();
	$info->message = "Not authenticated";
	$info->data_type = "error";
	echo json_encode($info);
	exit;
}

// helper: render one row
function render_user_row($row, $msgs = [], $is_contact = false){
	$image = (isset($row->gender) && $row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";

	if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
		$image = $row->image;
	}

	$userid = htmlspecialchars(strval($row->userid), ENT_QUOTES);
	$username = htmlspecialchars($row->username ?? '', ENT_QUOTES);
	$email = htmlspecialchars($row->email ?? '', ENT_QUOTES);

	$badge = '';
	if(is_array($msgs) && isset($msgs[$userid])){
		$badge = "<div style='width:20px;height:20px;border-radius:50%;background-color:orange;color:white;position:absolute;left:0;top:0;text-align:center;line-height:20px;font-size:11px;'>".$msgs[$userid]."</div>";
	}

	if($is_contact){
		$btn = "<button onclick=\"delete_contact(event, '".$userid."')\" style='margin-top:6px;'>Delete</button>";
	}else{
		$btn = "<button onclick=\"add_contact(event, '".$userid."')\" style='margin-top:6px;'>Add</button>";
	}

	$html  = "<div class='contact_row' userid='".$userid."' onclick='start_chat(event)' style='position:relative;padding:8px;border-bottom:1px solid #222;cursor:pointer;'>";
	$html .= "<img src='".$image."' style='width:48px;height:48px;border-radius:6px;vertical-align:middle;margin-right:8px;object-fit:cover;'>";
	$html .= "<span style='vertical-align:middle;display:inline-block;max-width:55%;'>";
	$html .= "<strong>".$username."</strong><br>";
	$html .= "<span style='font-size:11px;opacity:0.7;'>".$email."</span>";
	$html .= "</span>";
	$html .= "<div style='float:right;vertical-align:middle;'>".$btn."</div>";
	$html .= $badge;
	$html .= "</div>";

	return $html;
}

// count unread messages by sender
$msgs = [];
$mymgs = $DB->read(
	"SELECT sender FROM messages WHERE receiver = :me AND received = 0",
	['me' => $myid]
);

if(is_array($mymgs)){
	foreach($mymgs as $row2){
		$sender = strval($row2->sender);
		if(isset($msgs[$sender])){
			$msgs[$sender]++;
		}else{
			$msgs[$sender] = 1;
		}
	}
}

$action = isset($DATA_OBJ->find->action) ? $DATA_OBJ->find->action : null;

// add contact
if($action === 'add' && isset($DATA_OBJ->find->contactid)){
	$contactid = strval($DATA_OBJ->find->contactid);

	if($contactid === $myid){
		$info = new stdClass();
		$info->message = "Cannot add yourself";
		$info->data_type = "contacts";
		echo json_encode($info);
		exit;
	}

	$exists = $DB->read(
		"SELECT id FROM contacts WHERE userid = :u AND contactid = :c LIMIT 1",
		['u' => $myid, 'c' => $contactid]
	);

	if(!is_array($exists) || count($exists) == 0){
		$DB->write(
			"INSERT INTO contacts (userid, contactid) VALUES (:u, :c)",
			['u' => $myid, 'c' => $contactid]
		);
	}
}

// delete contact
if($action === 'delete' && isset($DATA_OBJ->find->contactid)){
	$contactid = strval($DATA_OBJ->find->contactid);

	$DB->write(
		"DELETE FROM contacts WHERE userid = :u AND contactid = :c LIMIT 1",
		['u' => $myid, 'c' => $contactid]
	);
}

// search users
if($action === 'search'){
	$q = isset($DATA_OBJ->find->q) ? trim($DATA_OBJ->find->q) : '';

	$mydata = "<div style='padding:8px;'>";
	$mydata .= "<input id='contact_search' placeholder='Search people by name or email' style='width:100%;padding:8px;margin-bottom:8px;' value='".htmlspecialchars($q, ENT_QUOTES)."' />";

	if($q !== ''){
		$like = "%".$q."%";

		$results = $DB->read(
			"SELECT userid, username, email, gender, image
			 FROM users
			 WHERE (username LIKE :q OR email LIKE :q)
			 AND userid != :myid
			 LIMIT 20",
			['q' => $like, 'myid' => $myid]
		);

		$mycontacts = $DB->read(
			"SELECT contactid FROM contacts WHERE userid = :u",
			['u' => $myid]
		);

		$contact_ids = [];
		if(is_array($mycontacts)){
			foreach($mycontacts as $c){
				$contact_ids[strval($c->contactid)] = true;
			}
		}

		if(is_array($results) && count($results) > 0){
			foreach($results as $row){
				$row->userid = strval($row->userid);
				$is_contact = isset($contact_ids[$row->userid]);
				$mydata .= render_user_row($row, $msgs, $is_contact);
			}
		}else{
			$mydata .= "<div style='padding:8px;color:#999'>No results</div>";
		}
	}

	$mydata .= "</div>";
	$mydata .= "<script>
	(function(){
		var input = document.getElementById('contact_search');
		if(input){
			input.addEventListener('input', function(){
				var q = this.value;
				clearTimeout(this._t);
				this._t = setTimeout(function(){
					get_data({action:'search', q:q}, 'contacts');
				}, 250);
			});
		}
	})();
	function add_contact(e, id){
		if(e){
			e.stopPropagation();
			e.preventDefault();
		}
		get_data({action:'add', contactid:id}, 'contacts');
		return false;
	}
	function delete_contact(e, id){
		if(e){
			e.stopPropagation();
			e.preventDefault();
		}
		get_data({action:'delete', contactid:id}, 'contacts');
		return false;
	}
	</script>";

	$info = new stdClass();
	$info->message = $mydata;
	$info->data_type = "contacts";
	echo json_encode($info);
	exit;
}

// default: render current contacts
$mydata = "<div style='padding:8px;'>";
$mydata .= "<input id='contact_search' placeholder='Search people by name or email' style='width:100%;padding:8px;margin-bottom:8px;' />";

$contacts = $DB->read(
	"SELECT u.userid, u.username, u.email, u.gender, u.image
	 FROM users u
	 INNER JOIN contacts c ON c.contactid = u.userid
	 WHERE c.userid = :u
	 ORDER BY c.created DESC",
	['u' => $myid]
);

if(is_array($contacts) && count($contacts) > 0){
	foreach($contacts as $row){
		$row->userid = strval($row->userid);
		$mydata .= render_user_row($row, $msgs, true);
	}
}else{
	$mydata .= "<div style='padding:12px;color:#ccc'>You have no contacts yet. Use the search box above to find and add people.</div>";
}

$mydata .= "</div>";
$mydata .= "<script>
(function(){
	var input = document.getElementById('contact_search');
	if(input){
		input.addEventListener('input', function(){
			var q = this.value;
			clearTimeout(this._t);
			this._t = setTimeout(function(){
				get_data({action:'search', q:q}, 'contacts');
			}, 250);
		});
	}
})();
function add_contact(e, id){
	if(e){
		e.stopPropagation();
		e.preventDefault();
	}
	get_data({action:'add', contactid:id}, 'contacts');
	return false;
}
function delete_contact(e, id){
	if(e){
		e.stopPropagation();
		e.preventDefault();
	}
	get_data({action:'delete', contactid:id}, 'contacts');
	return false;
}
</script>";

$info = new stdClass();
$info->message = $mydata;
$info->data_type = "contacts";
echo json_encode($info);
?>