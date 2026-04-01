<?php 

// For development: log errors but do NOT display them in responses.
// Displaying errors injects HTML into JSON responses and breaks the frontend JSON.parse.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start session and set JSON content type early so headers are always correct.
session_start();
header('Content-Type: application/json; charset=UTF-8');

$DATA_RAW = file_get_contents("php://input");
$DATA_OBJ = json_decode($DATA_RAW);

$info = (object)[];

//check if logged in
if(!isset($_SESSION['userid']))
{

	if(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type != "login" && $DATA_OBJ->data_type != "signup")
	{
		
		$info->logged_in = false;
		echo json_encode($info);
		die;	
	}
	
}

require_once("classes/autoload.php");
$DB = new Database();

$Error = "";

//proccess the data
if(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type == "signup")
{

	//signup
	include("includes/signup.php");

}elseif(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type == "login")
{
	//login
	include("includes/login.php");

}elseif(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type == "logout")
{
	include("includes/logout.php");
}elseif(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type == "user_info")
{

	//user info
	include("includes/user_info.php");
}elseif(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type == "contacts")
{
	//user info
	include("includes/contacts.php");
}elseif(isset($DATA_OBJ->data_type) && ($DATA_OBJ->data_type == "chats" || $DATA_OBJ->data_type == "chats_refresh"))
{
	//user info
	include("includes/chats.php");
}elseif(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type == "settings")
{
	//user info
	include("includes/settings.php");
}elseif(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type == "save_settings")
{
	//user info
	include("includes/save_settings.php");
}elseif(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type == "send_message")
{
	 //send message
	include("includes/send_messages.php");
}elseif(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type == "delete_message")
{
	 //delete message
	include("includes/delete_messages.php");
}elseif(isset($DATA_OBJ->data_type) && $DATA_OBJ->data_type == "delete_thread")
{
	 //delete thread
	include("includes/delete_thread.php");
}


function carochat_build_message_media_markup($filePath)
{
	if(empty($filePath) || !is_string($filePath)){
		return "";
	}

	$cleanPath = trim($filePath);
	if($cleanPath === ""){
		return "";
	}

	$ext = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
	$audioExts = array('webm','ogg','mp3','m4a','wav');
	$mediaSrc = $cleanPath . '?_=' . time();

	if(in_array($ext, $audioExts)){
		return "<audio class='message_media message_audio' controls preload='metadata'><source src='$mediaSrc' type='audio/".$ext."'>Your browser does not support audio playback</audio><br>";
	}

	return "<img class='message_media message_image' src='$mediaSrc' alt='Attachment' onclick='image_show(event)' /> <br>";
}

function message_left($data,$row)
{
	// keep names consistent with other parts of the app (male.jpg / girl.jpg)
	$image = ($row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
	if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
		$image = $row->image;
	}
	$messageText = isset($data->message) ? trim(strval($data->message)) : "";
	
	$a = "
	<div id='message_left'>
	<div></div>
		<img  id='prof_img' src='$image'>";

		if($messageText !== ""){
			$a .= $data->message . "<br><br>";
		}

		$a .= carochat_build_message_media_markup(isset($data->files) ? $data->files : "");
		$a .= "<img id='trash' src='ui/icons/trash.png' onclick='delete_message(event)' msgid='$data->id' />
	</div> ";

	return $a;
}

function message_right($data,$row)
{
	$image = ($row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
	if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
		$image = $row->image;
	}
	$messageText = isset($data->message) ? trim(strval($data->message)) : "";
	
	$a = "
	<div id='message_right'>

	<div>";
	
	if($data->seen){
		$a .="<img src='ui/icons/seen.png' style=''/>";
	}elseif($data->received){
		$a .="<img src='ui/icons/double_tick.png' style=''/>";
	}

	$a .= "</div>

		<img id='prof_img' src='$image' style='float:right'>";

		if($messageText !== ""){
			$a .= $data->message . "<br><br>";
		}

		$a .= carochat_build_message_media_markup(isset($data->files) ? $data->files : "");
		$a .= "<img id='trash' src='ui/icons/trash.png' onclick='delete_message(event)' msgid='$data->id' />
	</div>";

	return $a;
}


function message_controls()
{
	
	return "
	</div>
	<span class='thread_delete_action' onclick='delete_thread(event)'>Delete this thread</span>
	<div class='message_composer' style='display:flex;width:100%;height:59px;'>
		<label class='composer_chip composer_attach' for='message_file'><img src='ui/icons/attach.png' style='opacity:0.8;width:30px;margin:5px;cursor:pointer;' ><span class='composer_chip_text'>Files</span></label>
		<input type='file' id='message_file' name='file' style='display:none' onchange='send_image(this.files)' />
		<input id='message_text' onkeydown='enter_pressed(event)' style='flex:6;border:solid thin #ccc;border-bottom:none;font-size:14px;padding:4px;' type='text' placeHolder='type your message'/>
		<!-- Mic button: click to toggle on desktop, hold on mobile -->
				<!-- Voice-note mic (existing) -->
				<button id='mic_button' class='composer_chip' type='button' style='margin:0 6px;cursor:pointer;' title='Record voice note'><span class='btn-icon' aria-hidden='true'>🎙</span><span class='composer_chip_text'>Audio</span></button>
				<!-- Walkie / live-talk button (starts live WebRTC session) -->
				<button id='walkie_button' class='composer_chip' type='button' style='margin:0 6px;cursor:pointer;' title='Start live talk'><span class='btn-icon' aria-hidden='true'>📻</span><span class='composer_chip_text'>Live</span></button>
		<span id='recording_indicator' class='recording_indicator' style='display:none;color:red;margin-right:6px;'>● recording</span>
		<button id='send_button' class='send_button' style='flex:1;cursor:pointer;' type='button' onclick='send_message(event)' aria-label='Send message'><span class='btn-icon' aria-hidden='true'>&#10148;</span></button>
	</div>
	</div>";
}

function carochat_resolve_user_image($row)
{
	$image = (isset($row->gender) && $row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
	if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
		$image = $row->image;
	}
	return $image;
}

function carochat_escape_html($value)
{
	return htmlspecialchars(strval($value ?? ""), ENT_QUOTES, "UTF-8");
}

function carochat_build_chat_header_markup($userid, $image, $username)
{
	return "
	<div class='desktop_chat_context'>
		<div class='desktop_chat_context_label'>Now chatting with</div>
		<div id='active_contact' class='desktop_chat_context_card' data-userid='".carochat_escape_html($userid)."'>
			<img src='".carochat_escape_html($image)."' alt=''>
			<div class='desktop_chat_context_body'>
				<div class='desktop_chat_context_name'>".carochat_escape_html($username)."</div>
			</div>
		</div>
	</div>";
}

function carochat_build_chat_sidebar_item($userid, $image, $username, $preview)
{
	$preview = trim(strval($preview ?? ""));
	if($preview === ""){
		$preview = "Open conversation";
	}
	if(strlen($preview) > 74){
		$preview = substr($preview, 0, 71) . "...";
	}

	return "
	<div id='active_contact' class='desktop_chat_sidebar_item' data-userid='".carochat_escape_html($userid)."' style='cursor:pointer;'>
		<img src='".carochat_escape_html($image)."' alt=''>
		<div class='desktop_chat_sidebar_body'>
			<div class='desktop_chat_sidebar_name'>".carochat_escape_html($username)."</div>
			<div class='desktop_chat_sidebar_preview'>".carochat_escape_html($preview)."</div>
		</div>
	</div>";
}

function carochat_build_chat_sidebar_markup($itemsMarkup)
{
	$content = trim(strval($itemsMarkup ?? ""));
	if($content === ""){
		$content = "<div class='desktop_chat_sidebar_empty'>Your recent conversations will appear here.</div>";
	}

	return "
	<div class='desktop_chat_sidebar_shell'>
		<div class='desktop_chat_sidebar_eyebrow'>Inbox</div>
		<div class='desktop_chat_sidebar_title'>Messages</div>
		<div class='desktop_chat_sidebar_list'>".$content."</div>
	</div>";
}

function carochat_get_recent_chat_sidebar($DB, $userid)
{
	$a = [];
	$a['userid'] = strval($userid);

	$sql = "
	select m.*
	from messages m
	inner join (
		select max(id) as max_id
		from messages
		where sender = :userid or receiver = :userid
		group by msgid
	) latest on latest.max_id = m.id
	order by m.id desc
	limit 10
	";
	$result2 = $DB->read($sql, $a);

	$items = "";

	if(is_array($result2)){
		$result2 = array_reverse($result2);
		foreach($result2 as $data){
			$other_user = $data->sender;
			if($data->sender == $userid){
				$other_user = $data->receiver;
			}

			$myuser = $DB->get_user($other_user);
			if(!$myuser){
				continue;
			}

			try{ if(isset($myuser->userid)) $myuser->userid = strval($myuser->userid); }catch(Exception $e){}
			$image = carochat_resolve_user_image($myuser);
			$items .= carochat_build_chat_sidebar_item(
				isset($myuser->userid) ? $myuser->userid : "",
				$image,
				isset($myuser->username) ? $myuser->username : "Chat",
				isset($data->message) ? $data->message : ""
			);
		}
	}

	return carochat_build_chat_sidebar_markup($items);
}
