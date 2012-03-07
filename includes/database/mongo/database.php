<?php

defined('STORE_DRIVER_PATH') or die;

class StoreDatabase_mongo extends StoreDatabase {
  
  public function __construct(array $options) {
    parent::__construct($options);
    
    
  }
  
	/* (non-PHPdoc)
 * @see StoreDatabase::affectedRows()
 */
  public function affectedRows() {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::close()
 */
  public function close() {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::driver()
 */
  public function driver() {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::errorCode()
 */
  public function errorCode() {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::errorInfo()
 */
  public function errorInfo() {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::execute()
 */
  public function execute(Query $query) {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::lastInsertId()
 */
  public function lastInsertId() {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::open()
 */
  public function open() {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::ping()
 */
  public function ping() {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::prepare()
 */
  public function prepare(Query $query) {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::quote()
 */
  function quote($value, $type = NULL) {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::schema()
 */
  public function schema() {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::temporary()
 */
  public function temporary($temporaryTable, SelectQuery $query) {
    // TODO Auto-generated method stub
    
  }

	/* (non-PHPdoc)
 * @see StoreDatabase::version()
 */
  public function version() {
    // TODO Auto-generated method stub
    
  }

  
}
