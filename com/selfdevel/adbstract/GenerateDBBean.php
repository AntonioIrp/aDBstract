<?php

class GenerateDBBean {
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
	const CONSTANT_STRING = "const";
	
	const IS_OBJECT_EMPTY = '
		public function isEmpty(){
    		$vars = get_object_vars($this);
    		$isEmpty = true;
	    	foreach ($vars as $key => $value){
	    		if ($value != null){
	    			$isEmpty = false;	
	    		}
	    	}
			return $isEmpty;
    	}
	';
	
	private $classBody = null;
	
	private $tabeName;
	private $className;
	private $tableFields;
	
	public function __construct(){
		
	}
	
	public function generateNewDatabaseBean($tableName, $className, $tableFields){
		$this->tabeName = $tableName;
		$this->className = $className;
		$this->tableFields = $tableFields;
		
		$this->classBody .= self::PHP_OPEN . " ";
		$this->classBody .= self::CLASS_STRING;
		$this->classBody .= " " . $this->className . "Bean ";
		$this->classBody .= self::BRACE_OPEN;
		
		
		$this->generateClassConstants();
		$this->generateDatabaseAttributes();
		$this->generateGetterMethods();
		$this->generateSetterMethods();
		$this->addCheckIfObjectIsEmpty();
		
	
		$this->classBody .= self::BRACE_CLOSE;
		$this->classBody .= " " . self::PHP_CLOSE;
	}
	
	private function generateClassConstants(){
		foreach ($this->tableFields as $attrName){
			$this->classBody .= self::CONSTANT_STRING . " " . strtoupper($attrName) . '_FIELD = "' . $attrName . '";';
		}
	}
	
	private function generateDatabaseAttributes(){
		foreach ($this->tableFields as $attrName){
			$this->classBody .= self::PRIVATE_STRING . " $" . $attrName . " = null;";
		}
	}
	
	private function generateGetterMethods(){
		foreach ($this->tableFields as $attrName){
			$this->classBody .= self::PUBLIC_STRING . " " . self::FUNCTION_STRING . " " . self::GET_STRING;
			$this->classBody .= $attrName . self::PARENTHESIS_OPEN . self::PARENTHESIS_CLOSE . self::BRACE_OPEN;
			$this->classBody .= self::RETURN_STATEMENT . ' $this->' . $attrName . ";";
			$this->classBody .= self::BRACE_CLOSE;
		}	
	}
	
	private function generateSetterMethods(){
		foreach ($this->tableFields as $attrName){
			$this->classBody .= self::PUBLIC_STRING . " " . self::FUNCTION_STRING . " " . self::SET_STRING;
			$this->classBody .= $attrName . self::PARENTHESIS_OPEN . '$value' . self::PARENTHESIS_CLOSE . self::BRACE_OPEN;
			$this->classBody .= '$this->' . $attrName . " = " . ' $value;';
			$this->classBody .= self::BRACE_CLOSE;
		}
	}
	
	private function addCheckIfObjectIsEmpty(){
		$this->classBody .= self::IS_OBJECT_EMPTY;
	}
	
    public function getClassBody(){
        return $this->classBody;
    }

    public function setClassBody($classBody){
        $this->classBody = $classBody;
    }
}


?>