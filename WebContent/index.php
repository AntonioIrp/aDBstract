<?php
use DatabaseConnectionManager\ConnectionManager;

require_once $_SERVER['DOCUMENT_ROOT'] . "/aDBstract/com/selfdevel/adbstract/ConnectionManager.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/aDBstract/com/selfdevel/adbstract/GenerateDBBean.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/aDBstract/com/selfdevel/adbstract/GenerateDBController.php";

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<meta name='viewport' content='width=device-width'/>
<title>Login & Signup Kit Database Installation</title>
</head>
<body>

<h2>aDBstract</h2> 
		<h4>Database connection parameters</h4>
		Database Host: <?= ConnectionManager::DATABASE_HOST_NAME?>
		<br>
		Database Name: <?= ConnectionManager::DATABASE_NAME?>
		<br>
		Database Name prefix: <?= ConnectionManager::DATABASE_NAME_PREFIX?>
		<br>
		Database User: <?= ConnectionManager::DATABASE_USER?>
		<br>
		Database Password: <?= ConnectionManager::DATABASE_PASSWORD?>
		
		<form id="configurationform" action="" method="GET">    
        	<input name="getdbtables" id="getdbtables" value="true" type="hidden"></input>
        </form>
        <br>
        <p><a id="gettables" href='#'>Get tables</a></p>
        <br>
        
<?php 
if (isset($_GET['getdbtables'])){
	$pdo = ConnectionManager::getConnection();
	
	$tableList = array();
	$result = $pdo->query("SHOW TABLES");
	while ($row = $result->fetch(PDO::FETCH_NUM)) {
		$tableList[] = $row[0];
	}
	
	if (!empty($tableList)){
		?>
		<br>
		<form id="runadbstractform" action="" method="GET"> 
		You must select a table: <select name="tables" id="tables">
		<?php 
		foreach ($tableList as $name) {
		?>
			<option value="<?php echo $name; ?>"><?php echo $name; ?></option>
		<?php 
		}
		?>
		</select>
		
		<br>
		<br>
		   
		Set the class's name*: <input name="classname" id="classname" placeholder="MyDBClass"></input><br><br>
		<input name="runadbstract" id="runadbstract" value="true" type="hidden"></input>
		</form>
		<p style="color: red;">Database bean class will append "Bean" to class Name, ie. class MyDBClassBean { ... }</p>
		<p style="color: red;">Database controller class will append "Controller" to class Name, ie. class MyDBClassController { ... }</p>
		<br>
		<p><a id="runadbstractbutton" href="#">Run aDBstract!</a></p>
		
        <script type="text/javascript">
        	var runadbstract = document.getElementById("runadbstractbutton");
        	runadbstract.addEventListener("click", function(){
        		var form = document.getElementById("runadbstractform");
            	form.submit();
            });
        </script>
		<?php 
	}
}

if (isset($_GET['runadbstract'])){
	$className = $_GET['classname'];
	$tableName = $_GET['tables'];
?>
	<script type="text/javascript">
		alert("Generated classes will be exported to GeneratedClasses folder");
	</script>	
<?php 	
	$query = "DESCRIBE " . $tableName;
	$pdo = ConnectionManager::getConnection();
	$q = $pdo->prepare($query);
	$q->execute();
	$table_fields = $q->fetchAll(PDO::FETCH_COLUMN);
	
	$beanGenerator = new GenerateDBBean();
	$controllerGenerator = new GenerateDBController();
	
	$routeBeanClass = $_SERVER['DOCUMENT_ROOT'] . "/aDBstract/GeneratedClasses/".$className. "Bean" .".php";
	$routeControllerClass = $_SERVER['DOCUMENT_ROOT'] . "/aDBstract/GeneratedClasses/".$className. "Controller" .".php";
	
	$beanGenerator->generateNewDatabaseBean($tableName, $className, $table_fields);
	$beanClassBody = $beanGenerator->getClassBody();
	$resultBean = file_put_contents($routeBeanClass, $beanClassBody);
	
	$controllerGenerator->generateNewDatabaseController($tableName, $className, $table_fields);
	$controllerClassBody = $controllerGenerator->getClassBody();
	$resultController = file_put_contents($routeControllerClass, $controllerClassBody);
	
	if ($resultBean && $resultController){
?>
		<p style="color: green;">Database Bean class and Controller were succesfully created. Check the GeneratedClases folder.</p>
<?php
	} else {
?>
		<p style="color: red;">There was a problem generating the classes</p>
<?php
	}	
}
?>
        
        <script type="text/javascript">
        	var getTables = document.getElementById("gettables");
        	getTables.addEventListener("click", function(){
        			var form = document.getElementById("configurationform");
            		form.submit();
            });
        </script>

</body>
</html>