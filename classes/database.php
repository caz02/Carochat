<?php

Class Database
{

	private $con;

	//construct
	function __construct()
	{

		$this->con = $this->connect();

	}

	//connect to db
	private function connect()
	{

		// use constants from config.php so DB host/name can be configured (127.0.0.1 forces TCP)
		$string = "mysql:host=" . (defined('DBHOST') ? DBHOST : '127.0.0.1') . ";dbname=" . (defined('DBNAME') ? DBNAME : 'carochat_db');
		try
		{

			$connection = new PDO($string,DBUSER,DBPASS);
			return $connection;

		}catch(PDOException $e)
		{
			// Return a JSON error so the frontend JSON.parse doesn't fail when the API returns an error
			$info = (object)[];
			$info->message = "Database connection error: " . $e->getMessage();
			$info->data_type = "error";
			echo json_encode($info);
			die;
		}

		return false;

	}


	//write to database
	public function write($query,$data_array = [])
	{

		$con = $this->connect();
		$statement = $con->prepare($query);
		try {
			$check = $statement->execute($data_array);
		} catch (PDOException $e) {
			// log the error for debugging and return false
			error_log("DB write error: " . $e->getMessage());
			return false;
		}

		if($check)
		{
			return true;
		}

		return false;

	}	


	//read from database
	public function read($query,$data_array = [])
	{

		$con = $this->connect();
		$statement = $con->prepare($query);
		try {
			$check = $statement->execute($data_array);
		} catch (PDOException $e) {
			error_log("DB read error: " . $e->getMessage());
			return false;
		}

		if($check)
		{
			$result = $statement->fetchAll(PDO::FETCH_OBJ);
			if(is_array($result) && count($result) > 0)
			{
				return $result;
			}
			return false;
	}
		
	return false;

	}	

	public function get_user($userid)
	{
	
			$con = $this->connect();
			$query = "select * from users where userid = :userid limit 1";
			$arr['userid'] = $userid;
			$statement = $con->prepare($query);
			$check = $statement->execute($arr);

		if($check)
		{
			$result = $statement->fetchAll(PDO::FETCH_OBJ);
			if(is_array($result) && count($result) > 0)
			{
				return $result[0];
			}
			return false;
	}
			
	return false;

	}	


	public function generate_id($max)
	{


		$rand = "";
		$rand_count = rand(4,$max);
		for($i=0; $i < $rand_count; $i++) {
			# code...
			$r = rand(0,9);
			$rand .= $r;
		}

		return $rand ;
	
	}
		
}




