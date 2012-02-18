<?php

class StoreStatementDatabase_mysql extends StoreStatementDatabase implements StoreStatementInterface {
  
  protected $result;
  
	/* (non-PHPdoc)
 * @see StoreStatementDatabase::execute()
 */
  public function execute(array $args) {
    // TODO Auto-generated method stub
    $this->result = mysql_query($this->expandQueryArguments($args),  $this->connection->getResource());
    
    return (bool)$this->result;
  }

	/* (non-PHPdoc)
 * @see StoreStatementDatabase::fetchArray()
 */
  public function fetchArray() {
    // TODO Auto-generated method stub
    if ($this->result) {
      return mysql_fetch_array($this->result, MYSQL_ASSOC);
    }
  }

	/* (non-PHPdoc)
 * @see StoreStatementDatabase::fetchObject()
 */
  public function fetchObject() {
    // TODO Auto-generated method stub
    if ($this->result) {
      return mysql_fetch_object($this->result);
    }
  }

	/* (non-PHPdoc)
 * @see StoreStatementDatabase::rowCount()
 */
  public function rowCount() {
    // TODO Auto-generated method stub
    if ($this->result) {
      return mysql_num_rows($this->result);
    }
    
    return 0;
  }
/* (non-PHPdoc)
 * @see StoreStatementDatabase::fetchAssoc()
 */
  public function fetchAssoc() {
    // TODO Auto-generated method stub
    if ($this->result) {
      return mysql_fetch_assoc($this->result);
    }
  }

/* (non-PHPdoc)
 * @see StoreStatementDatabase::fetchField()
 */
  public function fetchField($index = 0) {
    // TODO Auto-generated method stub
    if ($this->result && mysql_num_rows($this->result) > 0) {
      
      $array = mysql_fetch_row($this->result);
      return $array[$index];
    }
  }
/* (non-PHPdoc)
 * @see StoreStatementDatabase::fetch()
 */
  public function fetch() {
    // TODO Auto-generated method stub
    
  }

/* (non-PHPdoc)
 * @see StoreStatementDatabase::fetchAll()
 */
  public function fetchAll() {
    // TODO Auto-generated method stub
    if ($this->result) {
      return mysql_fetch_array($this->result);
    }
  }

/* (non-PHPdoc)
 * @see StoreStatementDatabase::freeResult()
 */
  public function freeResult() {
    // TODO Auto-generated method stub
    if ($this->result) {
      mysql_free_result($this->result);
    }
  }
  
}
