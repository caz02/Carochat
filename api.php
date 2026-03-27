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


function message_left($data,$row)
{
	// keep names consistent with other parts of the app (male.jpg / girl.jpg)
	$image = ($row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
	if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
		$image = $row->image;
	}
	
	$a = "
	<div id='message_left'>
	<div></div>
		<img  id='prof_img' src='$image'>
		<b>$row->username</b><br>
		$data->message<br><br>";

		if(!empty($data->files) && is_string($data->files) && file_exists($data->files)){
			$ext = strtolower(pathinfo($data->files, PATHINFO_EXTENSION));
			$audioExts = array('webm','ogg','mp3','m4a','wav');
			if(in_array($ext,$audioExts)){
					// append a cache-busting query so updated audio is always loaded
					$audioSrc = $data->files . '?_=' . time();
					$a .= "<audio controls style='width:100%'><source src='$audioSrc' type='audio/".$ext."'>Your browser does not support audio playback</audio><br>";
			}else{
					// append a cache-busting query so updated images are always loaded
					$imgSrc = $data->files . '?_=' . time();
					$a .= "<img src='$imgSrc' style='width:100%;cursor:pointer;' onclick='image_show(event)' /> <br>";
			}
		}
		$a .= "<span style='font-size:11px;color:white;'>".date("jS M Y H:i:s a",strtotime($data->date))."<span>
	<img id='trash' src='ui/icons/trash.png' onclick='delete_message(event)' msgid='$data->id' />
	</div> ";

	return $a;
}

function message_right($data,$row)
{
	$image = ($row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
	if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
		$image = $row->image;
	}
	
	$a = "
	<div id='message_right'>

	<div>";
	
	if($data->seen){
		$a .="<img src='ui/icons/seen.png' style=''/>";
	}elseif($data->received){
		$a .="<img src='ui/icons/double_tick.png' style=''/>";
	}

	$a .= "</div>

		<img id='prof_img' src='$image' style='float:right'>
		<b>$row->username</b><br>
		$data->message<br><br>";

		if(!empty($data->files) && is_string($data->files) && file_exists($data->files)){
			$ext = strtolower(pathinfo($data->files, PATHINFO_EXTENSION));
			$audioExts = array('webm','ogg','mp3','m4a','wav');
			if(in_array($ext,$audioExts)){
					$audioSrc = $data->files . '?_=' . time();
					$a .= "<audio controls style='width:100%'><source src='$audioSrc' type='audio/".$ext."'>Your browser does not support audio playback</audio><br>";
			}else{
					$imgSrc = $data->files . '?_=' . time();
					$a .= "<img src='$imgSrc' style='width:100%;cursor:pointer;' onclick='image_show(event)' /> <br>";
			}
		}
		$a .= "<span style='font-size:11px;color:#888;'>".date("jS M Y H:i:s a",strtotime($data->date))."<span>
	<img id='trash' src='ui/icons/trash.png' onclick='delete_message(event)' msgid='$data->id' />
	</div>";

	return $a;
}


function message_controls()
{
	
	return "
	</div>
	<span onclick='delete_thread(event)' style='color:purple;cursor:pointer;margin-left:33%;'>Delete this thread </span>
	<div style='display:flex;width:100%;height:40px;'>
		<label for='message_file'><img src='ui/icons/attach.png' style='opacity:0.8;width:30px;margin:5px;cursor:pointer;' ></label>
		<input type='file' id='message_file' name='file' style='display:none' onchange='send_image(this.files)' />
		<input id='message_text' onkeydown='enter_pressed(event)' style='flex:6;border:solid thin #ccc;border-bottom:none;font-size:14px;padding:4px;' type='text' placeHolder='type your message'/>
		<!-- Mic button: click to toggle on desktop, hold on mobile -->
				<!-- Voice-note mic (existing) -->
				<button id='mic_button' type='button' style='margin:0 6px;cursor:pointer;' title='Record voice note'><span class='btn-icon' aria-hidden='true'>🎙</span></button>
				<!-- Walkie / live-talk button (starts live WebRTC session) -->
				<button id='walkie_button' type='button' style='margin:0 6px;cursor:pointer;' title='Start live talk'><span class='btn-icon' aria-hidden='true'>📻</span></button>
		<span id='recording_indicator' style='display:none;color:red;margin-right:6px;'>● recording</span>
		<input style='flex:1;cursor:pointer;' type='button' value='send' onclick='send_message(event)'/>
	</div>
	</div>";
}
