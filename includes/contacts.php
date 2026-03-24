<?php
// contacts.php - cleaned single implementation
// Replaced duplicated/malformed content with one canonical implementation.

// ensure contacts table exists
$DB->write("CREATE TABLE IF NOT EXISTS contacts (
	id INT AUTO_INCREMENT PRIMARY KEY,
	userid VARCHAR(64) NOT NULL,
	contactid VARCHAR(64) NOT NULL,
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY uniq_contact (userid, contactid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$myid = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
if(!$myid){
	$info->message = "Not authenticated";
	$info->data_type = "error";
	echo json_encode($info);
	exit;
}

// helper: render a single user row (used for both contacts and search results)
function render_user_row($row, $msgs = [], $is_contact = false){
	$image = (isset($row->gender) && $row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
	if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
		$image = $row->image;
	}
	$userid = $row->userid;
	$username = htmlspecialchars($row->username);
	$badge = '';
	if(is_array($msgs) && count($msgs) > 0 && isset($msgs[$userid])){
		$badge = "<div style='width:20px;height:20px;border-radius:50%;background-color:orange;color:white;position:absolute;left:0px;top:0px;'>".$msgs[$userid]."</div>";
	}
	$btn = $is_contact ? "<button disabled style='margin-top:6px;'>Added</button>" : "<button onclick=\"add_contact('".$userid."')\" style='margin-top:6px;'>Add</button>";

	// build the row HTML in parts to avoid escaping issues
	$html  = "<div class='contact_row' userid='".$userid."' onclick='start_chat(event)' style='position:relative;padding:8px;border-bottom:1px solid #222;cursor:pointer;'>";
	$html .= "<img src='".$image."' style='width:48px;height:48px;border-radius:6px;vertical-align:middle;margin-right:8px;'>";
	$html .= "<span style='vertical-align:middle;'>".$username."</span>";
	$html .= "<div style='float:right;vertical-align:middle;'>".$btn."</div>";
	$html .= $badge;
	$html .= "</div>";
	return $html;
}

// count unread messages by sender
$msgs = array();
$me = $myid;
$query = "select * from messages where receiver = :me AND received = 0";
$mymgs = $DB->read($query,['me'=>$me]);
if(is_array($mymgs)){
	foreach ($mymgs as $row2) {
		$sender = $row2->sender;
		if(isset($msgs[$sender])) $msgs[$sender]++; else $msgs[$sender] = 1;
	}
}

// handle actions: search / add
$action = isset($DATA_OBJ->find->action) ? $DATA_OBJ->find->action : null;

if($action === 'add' && isset($DATA_OBJ->find->contactid)){
	$contactid = $DATA_OBJ->find->contactid;
	if($contactid === $myid){
		$info->message = "Cannot add yourself";
		$info->data_type = "contacts";
		echo json_encode($info);
		exit;
	}
	// insert if not exists
	$exists = $DB->read("select * from contacts where userid = :u and contactid = :c limit 1", ['u'=>$myid, 'c'=>$contactid]);
	if(!$exists){
		$DB->write("insert into contacts (userid, contactid) values (:u,:c)", ['u'=>$myid,'c'=>$contactid]);
	}
	// fall through to render updated contacts list
}

// If search action, perform a search and return results
if($action === 'search'){
	$q = isset($DATA_OBJ->find->q) ? trim($DATA_OBJ->find->q) : '';
	$mydata = "<div style='padding:8px;'>";
	$mydata .= "<input id='contact_search' placeholder='Search people by name or email' style='width:100%;padding:8px;margin-bottom:8px;' value='".htmlspecialchars($q)."' />";
	if($q !== ''){
		$like = "%".$q."%";
		$sql = "select userid,username,email,gender,image from users where (username like :q or email like :q) and userid != :myid limit 20";
		$results = $DB->read($sql, ['q'=>$like, 'myid'=>$myid]);
		if(is_array($results)){
			// get current contacts for this user to mark existing
			$mycontacts = $DB->read("select contactid from contacts where userid = :u", ['u'=>$myid]);
			$contact_ids = [];
			if(is_array($mycontacts)){
				foreach($mycontacts as $c) $contact_ids[$c->contactid] = true;
			}
			foreach($results as $row){
				$is_contact = isset($contact_ids[$row->userid]);
				$mydata .= render_user_row($row, $msgs, $is_contact);
			}
		} else {
			$mydata .= "<div style='padding:8px;color:#999'>No results</div>";
		}
	}
	$mydata .= "</div>";

	// include small script to wire search input and add button
	$mydata .= "<script>document.getElementById('contact_search').addEventListener('input', function(e){ var q=this.value; clearTimeout(this._t); this._t=setTimeout(function(){ get_data({action:'search', q:q}, 'contacts'); }, 250); }); function add_contact(id){ get_data({action:'add', contactid:id}, 'contacts'); }</"."</script>";

	$info->message = $mydata;
	$info->data_type = "contacts";
	echo json_encode($info);
	exit;
}

// default: render contacts list with a search box on top
$mydata = "<div style='padding:8px;'>";
$mydata .= "<input id='contact_search' placeholder='Search people by name or email' style='width:100%;padding:8px;margin-bottom:8px;' />";

// fetch contacts
$sql = "select u.userid,u.username,u.email,u.gender,u.image from users u join contacts c on c.contactid = u.userid where c.userid = :u order by c.created desc";
$contacts = $DB->read($sql, ['u'=>$myid]);
if(is_array($contacts) && count($contacts) > 0){
	foreach($contacts as $row){
		$mydata .= render_user_row($row, $msgs, true);
	}
} else {
	$mydata .= "<div style='padding:12px;color:#ccc'>You have no contacts yet. Use the search box above to find and add people.</div>";
}

// attach script to wire the search box and add action
$mydata .= "</div><script>document.getElementById('contact_search').addEventListener('input', function(e){ var q=this.value; clearTimeout(this._t); this._t=setTimeout(function(){ get_data({action:'search', q:q}, 'contacts'); }, 250); }); function add_contact(id){ get_data({action:'add', contactid:id}, 'contacts'); }</"."</script>";

$info->message = $mydata;
$info->data_type = "contacts";
echo json_encode($info);

?>

// ensure contacts table exists
$DB->write("CREATE TABLE IF NOT EXISTS contacts (
	id INT AUTO_INCREMENT PRIMARY KEY,
	userid VARCHAR(64) NOT NULL,
	contactid VARCHAR(64) NOT NULL,
	created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY uniq_contact (userid, contactid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$myid = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
if(!$myid){
	$info->message = "Not authenticated";
	$info->data_type = "error";
	echo json_encode($info);
	exit;
}

// helper: render a single user row (used for both contacts and search results)
function render_user_row($row, $msgs = [], $is_contact = false){
	$image = (isset($row->gender) && $row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
	if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
		$image = $row->image;
	}
	$userid = $row->userid;
	$username = htmlspecialchars($row->username);
	$badge = '';
	if(is_array($msgs) && count($msgs) > 0 && isset($msgs[$userid])){
		$badge = "<div style='width:20px;height:20px;border-radius:50%;background-color:orange;color:white;position:absolute;left:0px;top:0px;'>".$msgs[$userid]."</div>";
	}
	$btn = $is_contact ? "<button disabled style='margin-top:6px;'>Added</button>" : "<button onclick=\"add_contact('".$userid."')\" style='margin-top:6px;'>Add</button>";

	// build the row HTML in parts to avoid escaping issues
	$html = "<div class='contact_row' userid='".$userid."' onclick='start_chat(event)' style='position:relative;padding:8px;border-bottom:1px solid #222;cursor:pointer;'>";
	$html .= "<img src='".$image."' style='width:48px;height:48px;border-radius:6px;vertical-align:middle;margin-right:8px;'>";
	$html .= "<span style='vertical-align:middle;'>".$username."</span>";
	$html .= "<div style='float:right;vertical-align:middle;'>".$btn."</div>";
	<?php

	// contacts.php - single clean implementation

	// ensure contacts table exists
	$DB->write("CREATE TABLE IF NOT EXISTS contacts (
		id INT AUTO_INCREMENT PRIMARY KEY,
		userid VARCHAR(64) NOT NULL,
		contactid VARCHAR(64) NOT NULL,
		created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY uniq_contact (userid, contactid)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$myid = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
	if(!$myid){
		$info->message = "Not authenticated";
		$info->data_type = "error";
		echo json_encode($info);
		exit;
	}

	// helper: render a single user row (used for both contacts and search results)
	function render_user_row($row, $msgs = [], $is_contact = false){
		$image = (isset($row->gender) && $row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
		if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
			$image = $row->image;
		}
		$userid = $row->userid;
		$username = htmlspecialchars($row->username);
		$badge = '';
		if(is_array($msgs) && count($msgs) > 0 && isset($msgs[$userid])){
			$badge = "<div style='width:20px;height:20px;border-radius:50%;background-color:orange;color:white;position:absolute;left:0px;top:0px;'>".$msgs[$userid]."</div>";
		}
		$btn = $is_contact ? "<button disabled style='margin-top:6px;'>Added</button>" : "<button onclick=\"add_contact('".$userid."')\" style='margin-top:6px;'>Add</button>";

		// build the row HTML in parts to avoid escaping issues
		$html = "<div class='contact_row' userid='".$userid."' onclick='start_chat(event)' style='position:relative;padding:8px;border-bottom:1px solid #222;cursor:pointer;'>";
		$html .= "<img src='".$image."' style='width:48px;height:48px;border-radius:6px;vertical-align:middle;margin-right:8px;'>";
		$html .= "<span style='vertical-align:middle;'>".$username."</span>";
		$html .= "<div style='float:right;vertical-align:middle;'>".$btn."</div>";
		$html .= $badge;
		$html .= "</div>";
		return $html;
	}

	// count unread messages by sender
	$msgs = array();
	$me = $myid;
	$query = "select * from messages where receiver = :me AND received = 0";
	$mymgs = $DB->read($query,['me'=>$me]);
	if(is_array($mymgs)){
		foreach ($mymgs as $row2) {
			$sender = $row2->sender;
			if(isset($msgs[$sender])) $msgs[$sender]++; else $msgs[$sender] = 1;
		}
	}

	// handle actions: search / add
	$action = isset($DATA_OBJ->find->action) ? $DATA_OBJ->find->action : null;

	if($action === 'add' && isset($DATA_OBJ->find->contactid)){
		$contactid = $DATA_OBJ->find->contactid;
		if($contactid === $myid){
			$info->message = "Cannot add yourself";
			$info->data_type = "contacts";
			echo json_encode($info);
			exit;
		}
		// insert if not exists
		$exists = $DB->read("select * from contacts where userid = :u and contactid = :c limit 1", ['u'=>$myid, 'c'=>$contactid]);
		if(!$exists){
			$DB->write("insert into contacts (userid, contactid) values (:u,:c)", ['u'=>$myid,'c'=>$contactid]);
		}
		// fall through to render updated contacts list
	}

	// If search action, perform a search and return results
	if($action === 'search'){
		$q = isset($DATA_OBJ->find->q) ? trim($DATA_OBJ->find->q) : '';
		$mydata = "<div style='padding:8px;'>";
		$mydata .= "<input id='contact_search' placeholder='Search people by name or email' style='width:100%;padding:8px;margin-bottom:8px;' value='".htmlspecialchars($q)."' />";
		if($q !== ''){
			$like = "%".$q."%";
			$sql = "select userid,username,email,gender,image from users where (username like :q or email like :q) and userid != :myid limit 20";
			$results = $DB->read($sql, ['q'=>$like, 'myid'=>$myid]);
			if(is_array($results)){
				// get current contacts for this user to mark existing
				$mycontacts = $DB->read("select contactid from contacts where userid = :u", ['u'=>$myid]);
				$contact_ids = [];
				if(is_array($mycontacts)){
					foreach($mycontacts as $c) $contact_ids[$c->contactid] = true;
				}
				foreach($results as $row){
					$is_contact = isset($contact_ids[$row->userid]);
					$mydata .= render_user_row($row, $msgs, $is_contact);
				}
			} else {
				$mydata .= "<div style='padding:8px;color:#999'>No results</div>";
			}
		}
		$mydata .= "</div>";

		// include small script to wire search input and add button
		$mydata .= "<script>document.getElementById('contact_search').addEventListener('input', function(e){ var q=this.value; clearTimeout(this._t); this._t=setTimeout(function(){ get_data({action:'search', q:q}, 'contacts'); }, 250); }); function add_contact(id){ get_data({action:'add', contactid:id}, 'contacts'); }</"."</script>";

		$info->message = $mydata;
		$info->data_type = "contacts";
		echo json_encode($info);
		exit;
	}

	// default: render contacts list with a search box on top
	$mydata = "<div style='padding:8px;'>";
	$mydata .= "<input id='contact_search' placeholder='Search people by name or email' style='width:100%;padding:8px;margin-bottom:8px;' />";

	// fetch contacts
	$sql = "select u.userid,u.username,u.email,u.gender,u.image from users u join contacts c on c.contactid = u.userid where c.userid = :u order by c.created desc";
	$contacts = $DB->read($sql, ['u'=>$myid]);
	if(is_array($contacts) && count($contacts) > 0){
		foreach($contacts as $row){
			$mydata .= render_user_row($row, $msgs, true);
		}
	} else {
		$mydata .= "<div style='padding:12px;color:#ccc'>You have no contacts yet. Use the search box above to find and add people.</div>";
	}

	// attach script to wire the search box and add action
	$mydata .= "</div><script>document.getElementById('contact_search').addEventListener('input', function(e){ var q=this.value; clearTimeout(this._t); this._t=setTimeout(function(){ get_data({action:'search', q:q}, 'contacts'); }, 250); }); function add_contact(id){ get_data({action:'add', contactid:id}, 'contacts'); }</"."</script>";

	$info->message = $mydata;
	$info->data_type = "contacts";
	echo json_encode($info);

	?>
	// count unread messages by sender
	$msgs = array();
	$me = $myid;
	$query = "select * from messages where receiver = :me AND received = 0";
	$mymgs = $DB->read($query,['me'=>$me]);
	if(is_array($mymgs)){
		foreach ($mymgs as $row2) {
			$sender = $row2->sender;
			if(isset($msgs[$sender])) $msgs[$sender]++; else $msgs[$sender] = 1;
		}
	}

	// handle actions: search / add
	$action = isset($DATA_OBJ->find->action) ? $DATA_OBJ->find->action : null;

	if($action === 'add' && isset($DATA_OBJ->find->contactid)){
		$contactid = $DATA_OBJ->find->contactid;
		if($contactid === $myid){
			$info->message = "Cannot add yourself";
			$info->data_type = "contacts";
			echo json_encode($info);
			die;
		}
		// insert if not exists
		$exists = $DB->read("select * from contacts where userid = :u and contactid = :c limit 1", ['u'=>$myid, 'c'=>$contactid]);
		if(!$exists){
			$DB->write("insert into contacts (userid, contactid) values (:u,:c)", ['u'=>$myid,'c'=>$contactid]);
		}
		// fall through to render updated contacts list
	}

	// If search action, perform a search and return results
	if($action === 'search'){
		$q = isset($DATA_OBJ->find->q) ? trim($DATA_OBJ->find->q) : '';
		$mydata = "<div style='padding:8px;'>";
		$mydata .= "<input id='contact_search' placeholder='Search people by name or email' style='width:100%;padding:8px;margin-bottom:8px;' value='".htmlspecialchars($q)."' />";
		if($q !== ''){
			$like = "%".$q."%";
			$sql = "select userid,username,email,gender,image from users where (username like :q or email like :q) and userid != :myid limit 20";
			$results = $DB->read($sql, ['q'=>$like, 'myid'=>$myid]);
			if(is_array($results)){
				// get current contacts for this user to mark existing
				$mycontacts = $DB->read("select contactid from contacts where userid = :u", ['u'=>$myid]);
				$contact_ids = [];
				if(is_array($mycontacts)){
					foreach($mycontacts as $c) $contact_ids[$c->contactid] = true;
				}
				foreach($results as $row){
					$is_contact = isset($contact_ids[$row->userid]);
					$mydata .= render_user_row($row, $msgs, $is_contact);
				}
			} else {
				$mydata .= "<div style='padding:8px;color:#999'>No results</div>";
			}
		}
		$mydata .= "</div>";

		// include small script to wire search input and add button
		$mydata .= "<script>document.getElementById('contact_search').addEventListener('input', function(e){ var q=this.value; clearTimeout(this._t); this._t=setTimeout(function(){ get_data({action:'search', q:q}, 'contacts'); }, 250); }); function add_contact(id){ get_data({action:'add', contactid:id}, 'contacts'); }</"."script>";

		$info->message = $mydata;
		$info->data_type = "contacts";
		echo json_encode($info);
		die;
	}

	// default: render contacts list with a search box on top
	$mydata = "<div style='padding:8px;'>";
	$mydata .= "<input id='contact_search' placeholder='Search people by name or email' style='width:100%;padding:8px;margin-bottom:8px;' />";

	// fetch contacts
	$sql = "select u.userid,u.username,u.email,u.gender,u.image from users u join contacts c on c.contactid = u.userid where c.userid = :u order by c.created desc";
	$contacts = $DB->read($sql, ['u'=>$myid]);
	if(is_array($contacts) && count($contacts) > 0){
		foreach($contacts as $row){
			$mydata .= render_user_row($row, $msgs, true);
		}
	} else {
		$mydata .= "<div style='padding:12px;color:#ccc'>You have no contacts yet. Use the search box above to find and add people.</div>";
	}

	// attach script to wire the search box and add action
	$mydata .= "</div><script>document.getElementById('contact_search').addEventListener('input', function(e){ var q=this.value; clearTimeout(this._t); this._t=setTimeout(function(){ get_data({action:'search', q:q}, 'contacts'); }, 250); }); function add_contact(id){ get_data({action:'add', contactid:id}, 'contacts'); }</"."script>";

	$info->message = $mydata;
	$info->data_type = "contacts";
	echo json_encode($info);

?>