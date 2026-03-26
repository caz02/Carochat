<?php

	$arr['userid'] = "null";
	if(isset($DATA_OBJ->find->userid)){
        
        // keep userid as string to avoid JS numeric precision issues
        $arr['userid'] = strval($DATA_OBJ->find->userid);
        
	}

	$sql = "select * from users where userid = :userid limit 1";
	$result = $DB->read($sql,$arr);

	if(is_array($result)){

		$arr['message'] = $DATA_OBJ->find->message;
		$arr['date'] = date("Y-m-d H:i:s");
		// ensure IDs are strings to avoid precision loss in JS
		$arr['sender'] = strval($_SESSION['userid']);
		$arr['msgid'] = strval(get_random_string_max(60));

		// log incoming send attempt for debugging
		try{ error_log("send_messages.php: attempt sender=".var_export($arr['sender'],true)." userid=".var_export($arr['userid'],true)." msg='".substr($arr['message'],0,200)."'"); }catch(
			Exception $e){}


			$arr2['sender'] = $arr['sender'];
			$arr2['receiver'] = $arr['userid'];

			$sql = "select * from messages where (sender = :sender && receiver = :receiver) || (receiver = :sender && sender = :receiver) limit 1";
			$result2 = $DB->read($sql,$arr2);

			if(is_array($result2)){
				$arr['msgid'] = $result2[0]->msgid;

			}

		// ensure message flags are explicitly set so new rows are visible to both sides
		$arr['received'] = 0;
		$arr['seen'] = 0;
		$arr['deleted_sender'] = 0;
		$arr['deleted_receiver'] = 0;

		$query = "insert into messages \n\t\t(sender, receiver, message, date, msgid, received, seen, deleted_sender, deleted_receiver) \n\t\tvalues \n\t\t(:sender, :userid, :message, :date, :msgid, :received, :seen, :deleted_sender, :deleted_receiver)";
		$DB->write($query,$arr);

		// log DB write result for debugging (record msgid and conversation)
		try{ error_log("send_messages.php: wrote message msgid=".var_export($arr['msgid'],true)." sender=".var_export($arr['sender'],true)." receiver=".var_export($arr['userid'],true)); }catch(Exception $e){}

		// fetch inserted messages for this conversation (help client render immediately)
		try{
			$b['msgid'] = $arr['msgid'];
			$inserted = $DB->read("select * from messages where msgid = :msgid order by id desc limit 20", $b);
			if(is_array($inserted)){
				$inserted = array_reverse($inserted);
				$info->inserted_messages = $inserted;
			} else {
				$info->inserted_messages = [];
			}
		} catch(Exception $e){
			$info->inserted_messages = [];
		}

		//user found
		$row = $result[0];
		
			$image = ($row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
			if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
				$image = $row->image;
			}

			$row->image = $image;

			$mydata = "Now Chatting with:<br>
			<div id='active_contact'>
				<img src='$image'>
				$row->username
			</div>";

			$messages = "
					<div id ='messages_holder_parent' style='height:650px;'>						
					<div id ='messages_holder' style='height:450px;overflow-y:scroll;'>";

						//read from db
						$a['msgid'] = $arr['msgid'];

						$sql = "select * from messages where msgid = :msgid order by id desc limit 10";
						$result2 = $DB->read($sql,$a);
						if(is_array($result2)){


						$result2 = array_reverse($result2);			
						foreach ($result2 as $data) {
							# code...
							$myuser = $DB->get_user($data->sender);

							if($_SESSION['userid'] == $data->sender){
								$messages .= message_right($data,$myuser);
						}else{
								$messages .= message_left($data,$myuser);


							}

						}

					}
					
				$messages .= message_controls();


		$info->user = $mydata;
		$info->messages = $messages;
		$info->data_type = "chats";

		// ensure inserted message IDs and senders/receivers are strings for JSON
		try{
			if(isset($info->inserted_messages) && is_array($info->inserted_messages)){
				foreach($info->inserted_messages as $m){
					try{ $m->sender = strval($m->sender); }catch(Exception $e){}
					try{ $m->receiver = strval($m->receiver); }catch(Exception $e){}
					try{ $m->msgid = strval($m->msgid); }catch(Exception $e){}
				}
			}
		}catch(Exception $e){}

		echo json_encode($info);
	
	}else{

		//user not found
		$info->message = "That contact wasn't found";
		$info->data_type = "chats";
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


