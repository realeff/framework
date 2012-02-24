<?php

class StoreStatementDatabase_pgsql extends StoreStatementBase {

  private $_index = 0;
  
  public function execute($args = array()) {
    parent::execute($args);
    
    $this->_index = 0;
  }
  
	/* (non-PHPdoc)
 * @see StoreStatementBase::fetchArray()
 */
  public function fetchArray() {
    // TODO Auto-generated method stub
    if ($this->result) {
      return pg_fetch_array($this->result);
    }
  }

	/* (non-PHPdoc)
 * @see StoreStatementBase::fetchObject()
 */
  public function fetchObject() {
    // TODO Auto-generated method stub
    if ($this->result) {
      return pg_fetch_object($this->result);
    }
  }

	/* (non-PHPdoc)
 * @see StoreStatementBase::rowCount()
 */
  public function rowCount() {
    // TODO Auto-generated method stub
    if ($this->result) {
      return pg_num_rows($this->result);
    }
    
    return 0;
  }
/* (non-PHPdoc)
 * @see StoreStatementBase::fetchAssoc()
 */
  public function fetchAssoc() {
    // TODO Auto-generated method stub
    if ($this->result) {
      return pg_fetch_assoc($this->result);
    }
  }

/* (non-PHPdoc)
 * @see StoreStatementBase::fetchField()
 */
  public function fetchField($index = 0) {
    // TODO Auto-generated method stub
    if ($this->result && pg_num_rows($this->result) > 0) {
      
      $array = pg_fetch_row($this->result);
      return $array[$index];
    }
  }
/* (non-PHPdoc)
 * @see StoreStatementBase::fetch()
 */
  public function fetch() {
    // TODO Auto-generated method stub
    
  }

/* (non-PHPdoc)
 * @see StoreStatementBase::fetchAll()
 */
  public function fetchAll() {
    // TODO Auto-generated method stub
    if ($this->result) {
      return  pg_fetch_array($this->result, NULL, PGSQL_BOTH);
    }
  }

/* (non-PHPdoc)
 * @see StoreStatementBase::freeResult()
 */
  public function freeResult() {
    // TODO Auto-generated method stub
    if ($this->result) {
      pg_free_result($this->result);
    }
  }
  
}
