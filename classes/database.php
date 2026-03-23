<?php

require_once __DIR__ . '/../config.php';

class Database
{
	private $con;

	function __construct()
	{
		$this->con = $this->connect();
	}

	private function connect()
	{
		$string = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

		try
		{
			$connection = new PDO($string, DB_USER, DB_PASS, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			]);
			return $connection;

		}catch(PDOException $e)
		{
			error_log("Database connection error: " . $e->getMessage());

			$info = (object)[];
			$info->message = "Database connection error";
			$info->data_type = "error";
			echo json_encode($info);
			die;
		}
	}

	public function write($query, $data_array = [])
	{
		$con = $this->connect();
		$statement = $con->prepare($query);

		try {
			$check = $statement->execute($data_array);
		} catch (PDOException $e) {
			error_log("DB write error: " . $e->getMessage());
			return false;
		}

		return $check ? true : false;
	}

	public function read($query, $data_array = [])
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
			$result = $statement->fetchAll();
			if(is_array($result) && count($result) > 0)
			{
				return $result;
			}
		}

		return false;
	}

	public function get_user($userid)
	{
		$con = $this->connect();
		$query = "select * from users where userid = :userid limit 1";
		$arr = [];
		$arr['userid'] = $userid;

		$statement = $con->prepare($query);
		$check = $statement->execute($arr);

		if($check)
		{
			$result = $statement->fetchAll();
			if(is_array($result) && count($result) > 0)
			{
				return $result[0];
			}
		}

		return false;
	}

	public function generate_id($max)
	{
		$rand = "";
		$rand_count = rand(4, $max);

		for($i = 0; $i < $rand_count; $i++) {
			$r = rand(0, 9);
			$rand .= $r;
		}

		return $rand;
	}
}
