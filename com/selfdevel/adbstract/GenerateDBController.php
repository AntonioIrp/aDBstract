<?php
class GenerateDBController {
	const PHP_OPEN = "<?php";
	const PHP_CLOSE = "?>";
	const CLASS_STRING = "class ";
	const BRACE_OPEN = "{";
	const BRACE_CLOSE = "}";
	const PRIVATE_STRING = "private";
	const PUBLIC_STRING = "public";
	const FUNCTION_STRING = "function";
	const GET_STRING = "get";
	const SET_STRING = "set";
	const PARENTHESIS_OPEN = "(";
	const PARENTHESIS_CLOSE = ")";
	const RETURN_STATEMENT = "return";
	const OPEN_TRY = "try {";
	const CLOSE_WITH_CATCH = '
		} catch ( \Exception $e ) {
			throw new \Exception ( "Exception" );
		} catch ( \Error $e ) {
			throw new \Exception ( "Exception" );
		}';
	const PRIMARY_CONSTRUCTOR = '
		public function __construct(){
			$a = func_get_args();
			$i = func_num_args();
			if (method_exists($this,$f="__construct".$i)) {
				call_user_func_array(array($this,$f),$a);
			}
		}';
	const SECONDARY_CONSTRUCTOR = '
		public function __construct1($pdo){
			$this->pdo = $pdo;
		}';
	
	private $classBody = null;
	private $tableName;
	private $className;
	private $tableFields;
	
	public function generateNewDatabaseController($tableName, $className, $tableFields) {
		$this->tableName = $tableName;
		$this->className = $className;
		$this->tableFields = $tableFields;
		
		$this->classBody .= self::PHP_OPEN . " ";
		$this->classBody .= self::CLASS_STRING;
		$this->classBody .= " " . $this->className . "Controller ";
		$this->classBody .= self::BRACE_OPEN;
		
		
		$this->addClassAttributes();
		$this->addSetterAndGettersMethod();
		$this->addClassConstructors();
		$this->generateExecuteSelectMethod();
		$this->generateExecuteUpdateMethod();
		$this->generateExecuteInsertMethod();
		$this->generateExecuteDeleteMethod();
		
		
		$this->classBody .= self::BRACE_CLOSE;
		$this->classBody .= " " . self::PHP_CLOSE;
	}
	
	private function addSetterAndGettersMethod(){
		$this->classBody .= '
			public function getLimit(){
				return $this->limit;
			}
			
			public function setLimit($limit){
				$this->limit = $limit;
			}
			
			public function getOffset(){
				return $this->offset;
			}
			
			public function setOffset($offset){
				$this->offset = $offset;
			}
				    
			public function getOrderBy(){
		        return $this->orderBy;
		    }

		    public function setOrderBy($orderBy){
		        $this->orderBy = $orderBy;
		    }';
	}
	
	private function addClassAttributes(){
		$this->classBody .= self::PRIVATE_STRING . " " . '$pdo;';
		$this->classBody .= self::PRIVATE_STRING . " " . '$limit;';
		$this->classBody .= self::PRIVATE_STRING . " " . '$offset;';
		$this->classBody .= self::PRIVATE_STRING . " " . '$orderBy = array();';
	}
	
	private function generateExecuteDeleteMethod(){
		$this->classBody .= self::PUBLIC_STRING . " " . self::FUNCTION_STRING . " executeDelete";
		$this->classBody .= self::PARENTHESIS_OPEN . '$filterBy' . self::PARENTHESIS_CLOSE . self::BRACE_OPEN;
		$this->classBody .= self::OPEN_TRY;
		
		$this->classBody .= '
			if ($filterBy instanceof '.$this->className. "Bean" .'){
				if ($this->pdo == null){
					$this->pdo = ConnectionManager::getConnection();
				}
				$stmt = "DELETE FROM '.$this->tableName.'";
				$whereString = " WHERE ";
				$andString = " AND ";
				$statementInputData = array();
				
				if(!$filterBy->isEmpty()){
					$stmt = $stmt . $whereString;
					$attrCounter = 0;';
		
		$this->generateDeleteWhereClause();
		$this->generateLimitClause();
		
		$this->classBody .= '
					$preparedStatement = $pdo->prepare($stmt);
					
					for($i = 1; $i <= sizeof($statementInputData); $i++){
						$preparedStatement->bindParam($i, $statementInputData[$i-1]);
					}
					
					$preparedStatement->execute();
						
					return $preparedStatement->rowCount();';

		$this->classBody .= '
				} else  {
					throw new \Exception("Cannot execute this query");
				}';
		$this->classBody .= '
			} else {
				throw new \Exception ( "input type is invalid" );
			}';
		$this->classBody .= self::CLOSE_WITH_CATCH;
		$this->classBody .= self::BRACE_CLOSE;
	}
	
	private function generateOrderByClause(){
		$this->classBody .= '
			//orderBy
			$orderByAttrCounter = 0;
			if (!empty($this->orderBy)){
				$stmt = $stmt . " ORDER BY ";
				foreach ($this->orderBy as $key=>$val){
					if ($orderByAttrCounter > 0){
						$stmt = $stmt . ", ";
					}
					$stmt = $stmt . $key . " " . $val;
					$orderByAttrCounter++;
				}
			}';
	}
	
	private function generateLimitClause(){
		$this->classBody .= '
			//limit and offset clause
			if (isset($this->limit)){
				$stmt = $stmt . " LIMIT " . $this->limit . " ";
				if (isset($this->offset)){
					$stmt = $stmt . " OFFSET " . $this->offset. " ";
				}
			}';
	}
	
	private function generateDeleteWhereClause(){
		foreach ( $this->tableFields as $attrName ) {
			$deleteWhereClause = '
				if($filterBy->get'.$attrName.'() != NULL){
					if($attrCounter > 0){
						$stmt = $stmt . $andString;
					}
					$stmt = $stmt . " '.$attrName.' = ? ";
					array_push($statementInputData, $filterBy->get'.$attrName.'());
					$attrCounter++;
				}';
			$this->classBody .= $deleteWhereClause;
		}
	}
	
	private function generateExecuteInsertMethod(){
		$this->classBody .= self::PUBLIC_STRING . " " . self::FUNCTION_STRING . " executeInsert";
		$this->classBody .= self::PARENTHESIS_OPEN . '$values' . self::PARENTHESIS_CLOSE . self::BRACE_OPEN;
		$this->classBody .= self::OPEN_TRY;
		
		$this->classBody .= '
			if ($values instanceof '. $this->className . "Bean" .'){
				if ($this->pdo == null){
					$this->pdo = ConnectionManager::getConnection();
				}
				
				$stmt = "INSERT INTO '.$this->tableName.' (";
				$semiColonString = " , ";
				$valuesString = " VALUES ";
				$leftParenthesis = " ( ";
				$rigthParenthesis = " ) ";
				$statementInputData = array();
				if(!$values->isEmpty()){
					$columsClauseAttrCounter = 0;
					$valuesClauseAttrCounter = 0;';
		
		$this->generateInsertColumnsNameClause();		
		$this->classBody .= '$stmt = $stmt . $rigthParenthesis . $valuesString . $leftParenthesis;';
		$this->generateInsertValuesClause();
		$this->classBody .= '$stmt = $stmt . $rigthParenthesis;';
				
		$this->classBody .= '
					$preparedStatement = $this->pdo->prepare($stmt);
					for($i = 1; $i <= sizeof($statementInputData); $i++){
						$preparedStatement->bindParam($i, $statementInputData[$i-1]);
					}
			
					$preparedStatement->execute();
					return $preparedStatement->rowCount();';
	
		$this->classBody .= '
				} else  {
					throw new \Exception("Cannot execute this query");
				}';
		$this->classBody .= '
			} else {
				throw new \Exception ( "input type is invalid" );
			}';
		$this->classBody .= self::CLOSE_WITH_CATCH;
		$this->classBody .= self::BRACE_CLOSE;
	}
	
	private function generateInsertValuesClause(){
		foreach ( $this->tableFields as $attrName ) {
			$insertValuesClause = '
				if($values->get'.$attrName.'() != null){
					if($valuesClauseAttrCounter > 0){
						$stmt = $stmt . $semiColonString;
					}
					$stmt = $stmt . " ? ";
					array_push($statementInputData, $values->get'.$attrName.'());
					$valuesClauseAttrCounter++;
				}';
			$this->classBody .= $insertValuesClause;
		}
	}
	
	private function generateInsertColumnsNameClause(){
		foreach ( $this->tableFields as $attrName ) {
			$columnNameClause = '
				if($values->get'.$attrName.'() != null){
					if($columsClauseAttrCounter > 0){
						$stmt = $stmt . $semiColonString;
					}
					$stmt = $stmt . "'.$attrName.'";
					$columsClauseAttrCounter++;
				}';
			$this->classBody .= $columnNameClause;
		}
	}
	
	private function generateExecuteUpdateMethod(){
		$this->classBody .= self::PUBLIC_STRING . " " . self::FUNCTION_STRING . " executeUpdate";
		$this->classBody .= self::PARENTHESIS_OPEN . '$filterBy, $values' . self::PARENTHESIS_CLOSE . self::BRACE_OPEN;
		$this->classBody .= self::OPEN_TRY;
		
		$this->classBody .= '
			if ($values instanceof '. $this->className . "Bean" .' && $filterBy instanceof '. $this->className . "Bean" .') {
				if ($this->pdo == null){
					$this->pdo = ConnectionManager::getConnection();
				}
	
				$stmt = "UPDATE '. $this->tableName.' SET";
				$semiColonString = " , ";
				$whereString = " WHERE ";
				$andString = " AND ";
				$statementInputData = array ();
				$setAttrCounter = 0;
				$whereAttrCounter = 0;
						
				if(!$values->isEmpty()){';
		
					$this->generateUpdateSetClause();
		
		$this->classBody .= '
					if(!$filterBy->isEmpty()){';
						$this->classBody .= '$stmt = $stmt . $whereString;';
						$this->generateUpdateWhereClause();
		$this->classBody .= '
					}';
		
		$this->generateLimitClause();
		
		$this->classBody .= '
				} else {
					throw new \Exception("Cannot execute this query");
				}
				
				$preparedStatement = $this->pdo->prepare ($stmt);
	
				for($i = 1; $i <= sizeof ($statementInputData); $i ++) {
					$preparedStatement->bindParam ($i, $statementInputData[$i - 1]);
				}
	
				$preparedStatement->execute ();
				
				return $preparedStatement->rowCount();';
		
		$this->classBody .= '
			} else {
				throw new \Exception ( "input type is invalid" );
			}';
		$this->classBody .= self::CLOSE_WITH_CATCH;
		$this->classBody .= self::BRACE_CLOSE;
	}
	
	private function generateUpdateWhereClause(){
		foreach ( $this->tableFields as $attrName ) {
			$updateWhereClause = '
				if ($filterBy->get'.$attrName.'() != null) {
					if ($whereAttrCounter > 0) {
						$stmt = $stmt . $andString;
					}
					$stmt = $stmt . " '.$attrName.' = ? ";
					array_push ( $statementInputData, $filterBy->get'.$attrName.'() );
					$whereAttrCounter ++;
				}';
			$this->classBody .= $updateWhereClause;
		}
	}
	
	private function generateUpdateSetClause(){
		foreach ( $this->tableFields as $attrName ) {
			$updateSetClause = '
				if ($values->get'.$attrName.'() != null) {
					if ($setAttrCounter > 0) {
						$stmt = $stmt . ", ";
					}
					$stmt = $stmt . " '.$attrName.' = ? ";
					array_push ($statementInputData, $values->get'.$attrName.'());
					$setAttrCounter ++;
				}';
			$this->classBody .= $updateSetClause;
		}	
	}
	
	private function generateExecuteSelectMethod() {
		$this->classBody .= self::PUBLIC_STRING . " " . self::FUNCTION_STRING . " executeSelect";
		$this->classBody .= self::PARENTHESIS_OPEN . '$filterBy' . self::PARENTHESIS_CLOSE . self::BRACE_OPEN;
		$this->classBody .= self::OPEN_TRY;
		$this->classBody .= '
			if ($filterBy instanceof ' . $this->className . "Bean" . ') {
				if ($this->pdo == null){
					$this->pdo = ConnectionManager::getConnection();
				}
		
				$stmt = "SELECT * FROM ' . $this->tableName . '";
		
				$whereString = " WHERE ";
				$andString = " AND ";
				$statementInputData = array ();
				
				if (!$filterBy->isEmpty()){
					$stmt = $stmt . $whereString;
					$attrCounter = 0;';
		
		$this->generateSelectWhere();
		$this->classBody .= self::BRACE_CLOSE;
		
		$this->generateOrderByClause();
		$this->generateLimitClause();
		
		$this->classBody .= '
				$preparedStatement = $this->pdo->prepare ( $stmt );
	
				for($i = 1; $i <= sizeof ( $statementInputData ); $i ++) {
					$preparedStatement->bindParam ( $i, $statementInputData [$i - 1] );
				}
	
				$preparedStatement->execute ();
				$preparedStatement->setFetchMode ( PDO::FETCH_CLASS, "' . $this->className . "Bean" . '");
				$resultSetArray = array ();
	
				while ( $row = $preparedStatement->fetch () ) {
					array_push ($resultSetArray, $row);
				}
	
				return $resultSetArray;';
		
		$this->classBody .= '
			} else {
				throw new \Exception ( "input type is invalid" );
			}';
		
		$this->classBody .= self::CLOSE_WITH_CATCH;
		$this->classBody .= self::BRACE_CLOSE;
	}
	
	private function generateSelectWhere() {
		foreach ( $this->tableFields as $attrName ) {
			$whereCondition = '
				if ($filterBy->get' . $attrName . '() != null) {
					if ($attrCounter > 0) {
						$stmt = $stmt . $andString;
					}
					$stmt = $stmt . " ' . $attrName . ' = ? ";
					array_push ($statementInputData, $filterBy->get' . $attrName . '());
					$attrCounter ++;
				}';
			$this->classBody .= $whereCondition;
		}
	}
	
	private function addClassConstructors() {
		$this->classBody .= self::PRIMARY_CONSTRUCTOR;
		$this->classBody .= self::SECONDARY_CONSTRUCTOR;
	}
	
	public function getClassBody() {
		return $this->classBody;
	}
	public function setClassBody($classBody) {
		$this->classBody = $classBody;
	}
}
?>