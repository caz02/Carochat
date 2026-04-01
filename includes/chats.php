<?php

	$arr['userid'] = "null";
	if(isset($DATA_OBJ->find->userid)){
		
		$arr['userid'] = $DATA_OBJ->find->userid;
		
	}

// NOTE: debug logging removed to avoid noisy logs in production.

	$refresh = false;
	$seen = false;
	if($DATA_OBJ->data_type == "chats_refresh"){
		$refresh = true;	
		$seen = $DATA_OBJ->find->seen;
	}

	$sql = "select * from users where userid = :userid limit 1";
	$result = $DB->read($sql,$arr);

	if(is_array($result)){

		//user found
		$row = $result[0];

		// ensure IDs are strings to avoid numeric precision issues in JS
		try{ $row->userid = strval($row->userid); }catch(Exception $e){}
		
			$image = carochat_resolve_user_image($row);

			
			$row->image = $image;

			$mydata = "";
			$sidebar = carochat_get_recent_chat_sidebar($DB, strval($_SESSION['userid']));
			if(!$refresh){

			$display_userid = '';
			try{ $display_userid = isset($row->userid) ? strval($row->userid) : ''; }catch(Exception $e){ $display_userid = ''; }
			$mydata = carochat_build_chat_header_markup($display_userid, $image, $row->username);
			}

			$messages = "";

			if(!$refresh){
				$messages = "
					<div id ='messages_holder_parent' onclick='set_seen(event)' style='height:870px;'>						
					<div id ='messages_holder' style='height:870px;overflow-y:scroll;'>";
			}
					   //read from db
						$a['sender'] = $_SESSION['userid'];
						$a ['receiver'] = $arr['userid'];

						// tolerate NULL flags by using coalesce(...,0)=0 so older rows with NULLs are treated as not-deleted
						$sql = "select * from messages \n\t\t\twhere \n\t\t\t(sender = :sender and receiver = :receiver and coalesce(deleted_sender,0) = 0) \n\t\t\tor \n\t\t\t(sender = :receiver and receiver = :sender and coalesce(deleted_receiver,0) = 0) \n\t\t\torder by id desc \n\t\t\tlimit 10";
						$result2 = $DB->read($sql,$a);

						if(is_array($result2)){

						$result2 = array_reverse($result2);			
						foreach ($result2 as $data) {
							# code...
							// cast message ids to strings for JSON consistency
							try{ $data->sender = strval($data->sender); }catch(Exception $e){}
							try{ $data->receiver = strval($data->receiver); }catch(Exception $e){}
							try{ $data->msgid = strval($data->msgid); }catch(Exception $e){}
							$myuser = $DB->get_user($data->sender);
							try{ if($myuser && isset($myuser->userid)) $myuser->userid = strval($myuser->userid); }catch(Exception $e){}


							if($data->receiver == $_SESSION['userid'] && $data->received == 1 && $seen){
								$DB->write("update messages set seen = 1 where id = '$data->id' limit 1");

							}


							if($data->receiver == $_SESSION['userid']){
								$DB->write("update messages set received = 1 where id = '$data->id' limit 1");
							}

							if($_SESSION['userid'] == $data->sender){
								$messages .= message_right($data,$myuser);
						}else{
								$messages .= message_left($data,$myuser);


							}

						}

					}


				if(!$refresh){	
					$messages .= message_controls();
			}

		$info->user = $mydata;
		$info->sidebar = $sidebar;
		$info->messages = $messages;
	
			$info->data_type = "chats";
		if($refresh){
			$info->data_type = "chats_refresh";
		}
		echo json_encode($info);
	
	}else{
		$mydata = carochat_get_recent_chat_sidebar($DB, strval($_SESSION['userid']));
		$info->user = $mydata;
		$info->sidebar = $mydata;
		$info->messages = "";
		$info->data_type = "chats";
		
		
		echo json_encode($info);


	}

	


?> 
