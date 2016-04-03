<?php 
namespace DatabaseConnectionManager;

use PDO;

class ConnectionManager{
	
	const DATABASE_HOST_NAME = "localhost";
	const DATABASE_NAME = "data_login";
	const DATABASE_NAME_PREFIX = "";
	const DATABASE_USER = "root";
	const DATABASE_PASSWORD = "";
	
	public static function  getConnection(){
		try {	
			
			$databaseCompleteName = self::DATABASE_NAME_PREFIX . self::DATABASE_NAME;
					
			$connParams = "mysql:host=" . self::DATABASE_HOST_NAME . ";dbname=" . $databaseCompleteName;
			
			$pdo = new PDO($connParams, self::DATABASE_USER, self::DATABASE_PASSWORD);
			
			return $pdo;
		} catch (\Error $e){
			return false;		
		} catch (\Exception $e){
			return false;
		}
	}
}

?>