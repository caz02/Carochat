<?php

	$arr['userid'] = "null";
	if(isset($DATA_OBJ->find->userid)){
		
		$arr['userid'] = $DATA_OBJ->find->userid;
		
	}

// Temporary debug logging: record the session user and requested contact when chats handler runs.
// Remove this log after debugging.
try{ error_log("includes/chats.php: session_user=".($_SESSION['userid'] ?? 'NULL')." requested_user=".($arr['userid'] ?? 'NULL')." data_type=".($DATA_OBJ->data_type ?? 'NULL')); }catch(Exception $e){}

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
		
			$image = ($row->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
			if(!empty($row->image) && is_string($row->image) && file_exists($row->image)){
				$image = $row->image;
			}

			
			$row->image = $image;

			$mydata = "";
			if(!$refresh){

			$mydata = "Now Chatting with:<br>
						<div id='active_contact'>
							<img src='$image'>
							$row->username
						</div>";
			}

			$messages = "";

			if(!$refresh){
				$messages = "
					<div id ='messages_holder_parent' onclick='set_seen(event)' style='height:650px;'>						
					<div id ='messages_holder' style='height:450px;overflow-y:scroll;'>";
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
		$info->messages = $messages;
	
			$info->data_type = "chats";
		if($refresh){
			$info->data_type = "chats_refresh";
		}
		echo json_encode($info);
	
	}else{

//read from db
	$a['userid'] = $_SESSION['userid'];

	// safer latest-per-conversation query: select the most recent row per msgid
	$sql = "\nselect m.*\nfrom messages m\ninner join (\n\tselect max(id) as max_id\n\tfrom messages\n\twhere sender = :userid or receiver = :userid\n\tgroup by msgid\n) latest on latest.max_id = m.id\norder by m.id desc\nlimit 10\n";
	$result2 = $DB->read($sql,$a);

		$mydata = "Previous Chats:<br>";

		if(is_array($result2)){

				$result2 = array_reverse($result2);			
					foreach ($result2 as $data) {
						# code...

						$other_user = $data->sender;
						if($data->sender == $_SESSION['userid'])
						{

							$other_user = $data->receiver;
						
						}
	
						$myuser = $DB->get_user($other_user);
						try{ if($myuser && isset($myuser->userid)) $myuser->userid = strval($myuser->userid); }catch(Exception $e){}

						// ensure contact userid is a string when embedding into HTML attributes
						try{ $contact_userid = isset($myuser->userid) ? strval($myuser->userid) : ''; }catch(Exception $e){ $contact_userid = ''; }

						$image = ($myuser->gender == "Male") ? "ui/images/male.jpg" : "ui/images/girl.jpg";
						if(!empty($myuser->image) && is_string($myuser->image) && file_exists($myuser->image)){
							$image = $myuser->image;
						}
							
							$mydata .= "
									<div id='active_contact' data-userid='{".$contact_userid."}' style='cursor:pointer'>
										<img src='$image'>
										$myuser->username<br>
										<span style='font-size:11px;'>$data->message</span>
									</div>";

		}

	}

		$info->user = $mydata;
		$info->messages = "";
		$info->data_type = "chats";
		
		
		echo json_encode($info);


	}

	


?> 
