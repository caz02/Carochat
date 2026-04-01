<?php

session_start();

// response tracking
$info = (object)[];
$__uploader_response_sent = false;

// For file-upload requests we read data_type from POST (not JSON). Gate endpoints that require auth.
$data_type = "";
if(isset($_POST['data_type'])){
	$data_type = $_POST['data_type'];
}

// If no session and the client is trying to perform an authenticated upload action,
// return a JSON logged_in=false response so the frontend can redirect to login.
if(!isset($_SESSION['userid'])){
	if(in_array($data_type, ['send_image','send_audio','change_profile_image'])){
		$info->logged_in = false;
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($info);
		exit;
	}
}


require_once("classes/autoload.php");
$DB = new Database();


$data_type = "";
if(isset($_POST['data_type'])){
	$data_type =$_POST['data_type'];
}


$destination = "";
if(isset($_FILES['file']) && $_FILES['file']['name'] != ""){

	// allow common jpeg/jpg and png mime types. We'll also validate extension below.
	$allowed = [
		// image types
		'image/jpeg',
		'image/jpg',
		'image/pjpeg',
		'image/png',
		'image/jfif',
		// audio types
		'audio/webm',
		'audio/ogg',
		'audio/mpeg',
		'audio/mp3',
		'audio/x-wav',
		'audio/wav',
		'audio/mp4'
	];

	$fileTypeOk = ($_FILES['file']['error'] == 0 && in_array($_FILES['file']['type'], $allowed));

	// basic extension check as an additional guard
	$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
	$extOk = in_array($ext, ['jpg','jpeg','png','webm','ogg','mp3','m4a','wav']);

	// Fallback: some clients (certain Android devices, older browsers) may send a generic
	// content-type (e.g. application/octet-stream) even for images. If the extension
	// is trusted, allow the upload but log the unexpected MIME type for diagnostics.
	if(!$fileTypeOk && $extOk){
		// allow by extension for common image/audio types
		$fileTypeOk = true;
		error_log('uploader.php: MIME fallback - accepting file by extension. filename=' . $_FILES['file']['name'] . ' reported_type=' . ($_FILES['file']['type'] ?? 'N/A'));
	}

	if($fileTypeOk && $extOk){

		//good to go
		$folder = "uploads/";
		if(!file_exists($folder)){

			mkdir($folder,0777,true);
		}

		// create a safe, unique filename to avoid spaces/special chars and collisions
		$originalName = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
		$originalName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $originalName); // allow alnum, dash, underscore
		$uniquePrefix = time() . '_' . substr(md5(uniqid('', true)), 0, 6);
		$safeName = $uniquePrefix . '_' . $originalName . '.' . $ext;
		$destination = $folder . $safeName;

		// attempt to move uploaded file and report failure if it doesn't work
		if(!move_uploaded_file($_FILES['file']['tmp_name'], $destination)){
			// capture last PHP error for debugging
			$err = error_get_last();
			error_log('uploader.php: move_uploaded_file failed for ' . $_FILES['file']['name'] . ' -> ' . $destination . ' ; err=' . print_r($err, true));
			http_response_code(500);
			$info->message = "Failed to save uploaded file.";
			$info->data_type = $data_type;
			// include a short non-sensitive reason to help client-side debugging
			$info->reason = isset($err['message']) ? substr($err['message'],0,180) : null;
			echo json_encode($info);
			return;
		}

		// set safe permissions
		@chmod($destination, 0644);

		$info->message = "Your file was uploaded";
		$info->data_type = $data_type;
		// include the destination path so the client can update UI immediately if needed
		$info->file = $destination;
	}

	// if the file was present but failed type/extension checks, return an error
	if(!$fileTypeOk || !$extOk){
		http_response_code(400);
		$info->message = 'Invalid file type or extension';
		$info->data_type = $data_type;
		echo json_encode($info);
		$__uploader_response_sent = true;
	}


}


if($data_type == "change_profile_image"){

	if($destination != ""){
		//save to database (only if we have a logged-in user)
		if(isset($_SESSION['userid'])){
			$id = $_SESSION['userid'];
			$query = "update users set image = '$destination' where userid = '$id' limit 1";
			$DB->write($query,[]);
		} else {
			// no session — skip DB write but leave file on disk
			error_log('uploader.php: change_profile_image called without session; skipping DB write');
		}

	}

} else 
if($data_type == "send_image"){

	// receiver userid (the chat partner)
	$userid = null;
	if(isset($_POST['userid'])){
		$userid = addslashes($_POST['userid']);
	}

	// determine sender: prefer session, fall back to explicit POST 'sender' if provided
	$sender = null;
	if(isset($_SESSION['userid'])){
		$sender = $_SESSION['userid'];
	} elseif(isset($_POST['sender'])){
		$sender = addslashes($_POST['sender']);
	}

	$arr = [];
	$arr['userid'] = $userid;
	$arr['message'] = "";
	$arr['date'] = date("Y-m-d H:i:s");
	$arr['sender'] = $sender;
	$arr['msgid'] = get_random_string_max(60);
	$arr['file'] = $destination;

	// Only attempt DB insert if we have a sender and a receiver
	if(empty($arr['sender']) || empty($arr['userid'])){
		error_log('uploader.php: send_image missing sender or userid; sender=' . var_export($arr['sender'], true) . ' userid=' . var_export($arr['userid'], true));
		http_response_code(400);
		$info->message = 'Missing sender or recipient for send_image';
		$info->data_type = $data_type;
		echo json_encode($info);
		// ensure a JSON response (defensive)
		if(!$__uploader_response_sent){
			http_response_code(400);
			echo json_encode($info);
			$__uploader_response_sent = true;
		}
		return;
	}

	if(!empty($arr['sender']) && !empty($arr['userid'])){

		$arr2 = [];
		$arr2['sender'] = $arr['sender'];
		$arr2['receiver'] = $arr['userid'];

		$sql = "select * from messages where (sender = :sender && receiver = :receiver) || (receiver = :sender && sender = :receiver) limit 1";
		$result2 = $DB->read($sql,$arr2);

		if(is_array($result2) && isset($result2[0]->msgid)){
			$arr['msgid'] = $result2[0]->msgid;
		}

		$query = "insert into messages (sender,receiver,message,date,msgid,files) values (:sender,:userid,:message,:date,:msgid,:file)";
		$DB->write($query,$arr);

	} else {
		// missing sender/receiver — do not attempt DB write, but keep the file on disk
		error_log('uploader.php: skipping DB insert for send_image due to missing sender or userid; sender=' . var_export($sender, true) . ' userid=' . var_export($userid, true));
	}

}

// handle audio sends
if($data_type == "send_audio"){

	// initialize structure
	$arr = [];
	$arr['userid'] = null;
	if(isset($_POST['userid'])){
		$arr['userid'] = addslashes($_POST['userid']);
	}

	$arr['message'] = "";
	$arr['date'] = date("Y-m-d H:i:s");
	$arr['sender'] = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
	$arr['msgid'] = get_random_string_max(60);
	$arr['files'] = $destination;

	// validate required fields
	if(empty($arr['sender']) || empty($arr['userid'])){
		error_log('uploader.php: send_audio missing sender or userid; sender=' . var_export($arr['sender'], true) . ' userid=' . var_export($arr['userid'], true));
		http_response_code(400);
		$info->message = 'Missing sender or recipient for send_audio';
		$info->data_type = $data_type;
		echo json_encode($info);
		return;
	}

	$arr2 = [];
	$arr2['sender'] = $arr['sender'];
	$arr2['receiver'] = $arr['userid'];

	$sql = "select * from messages where (sender = :sender && receiver = :receiver) || (receiver = :sender && sender = :receiver) limit 1";
	$result2 = $DB->read($sql,$arr2);
	if(is_array($result2) && isset($result2[0]->msgid)){
		$arr['msgid'] = $result2[0]->msgid;
	}

	$query = "insert into messages (sender,receiver,message,date,msgid,files) values (:sender,:userid,:message,:date,:msgid,:files)";
	$DB->write($query,$arr);

	// ensure client always receives JSON response for uploads
	if(!$__uploader_response_sent){
		$info->message = 'Audio uploaded';
		$info->data_type = $data_type;
		$info->file = $destination;
		echo json_encode($info);
		$__uploader_response_sent = true;
	}

}

// final fallback: if we reached end of script without returning JSON, send a default response
if(!$__uploader_response_sent){
	http_response_code(200);
	$info->message = isset($info->message) ? $info->message : 'No action taken';
	$info->data_type = isset($info->data_type) ? $info->data_type : $data_type;
	echo json_encode($info);
}

function get_random_string_max($length)	{

	$array = array(0,1,2,3,4,5,6,7,8,9,'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$text = "";

	$length = rand(4,$length);

	for($i=0;$i<$length;$i++) {

		$random = rand(0,61);

		$text .= $array[$random];

	}

	return $text;
}

