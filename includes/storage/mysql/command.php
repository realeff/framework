<?php


class StoreCommand_mysql extends StoreCommand {
  
  
  public function __construct(StoreConnection_mysql $connection, $command) {
    parent::__construct($connection, $command);
  }

  /**
   * (non-PHPdoc)
   * @see StoreCommand::affected_rows()
   */
  public function affected_rows() {
    // TODO Auto-generated method stub
    return mysql_affected_rows($this->connection->getConnection());
  }

  /**
   * (non-PHPdoc)
   * @see StoreCommand::errorCode()
   */
  public function errorCode() {
    // TODO Auto-generated method stub
    return $this->connection->errorCode();
  }

  /**
   * (non-PHPdoc)
   * @see StoreCommand::errorInfo()
   */
  public function errorInfo() {
    // TODO Auto-generated method stub
    return $this->connection->errorInfo();
  }

  /**
   * (non-PHPdoc)
   * @see StoreCommand::execute()
   */
  public function execute() {
    // TODO Auto-generated method stub
    if (empty($this->query)) {
      return FALSE;
    }
    
    // 转换query为sql语句
    //return (bool)mysql_query($sql, $this->connection->getConnection());
  }

  /**
   * (non-PHPdoc)
   * @see StoreCommand::lastInsertId()
   */
  public function lastInsertId() {
    // TODO Auto-generated method stub
    return mysql_insert_id($this->connection->getConnection());
  }
  
  /**
   * (non-PHPdoc)
   * @see StoreCommand::prepare()
   */
  public function prepare() {
    // TODO Auto-generated method stub
    
  }
/* (non-PHPdoc)
 * @see StoreCommand::query()
 */
  public function query() {
    // TODO Auto-generated method stub
    
  }
/* (non-PHPdoc)
 * @see StoreCommand::generateTemporary()
 */
  public function generateTemporary($table) {
    // TODO Auto-generated method stub
    
  }


  
}
