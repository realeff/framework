<?php


class StoreQuerier_mysql extends StoreQuerier {
  
  
  public function __construct(StoreConnection_mysql $connection, $name) {
    parent::__construct($connection, $name);
  }

  /**
   * (non-PHPdoc)
   * @see StoreQuerier::affected_rows()
   */
  public function affected_rows() {
    // TODO Auto-generated method stub
    return mysql_affected_rows($this->connection->getResource());
  }

  /**
   * (non-PHPdoc)
   * @see StoreQuerier::errorCode()
   */
  public function errorCode() {
    // TODO Auto-generated method stub
    return $this->connection->errorCode();
  }

  /**
   * (non-PHPdoc)
   * @see StoreQuerier::errorInfo()
   */
  public function errorInfo() {
    // TODO Auto-generated method stub
    return $this->connection->errorInfo();
  }

  /**
   * (non-PHPdoc)
   * @see StoreQuerier::execute()
   */
  public function execute() {
    // TODO Auto-generated method stub
    if (empty($this->query)) {
      return FALSE;
    }
    
    // 转换query为sql语句
    //return (bool)mysql_query($sql, $this->connection->getResource());
  }

  /**
   * (non-PHPdoc)
   * @see StoreQuerier::lastInsertId()
   */
  public function lastInsertId() {
    // TODO Auto-generated method stub
    return mysql_insert_id($this->connection->getResource());
  }
  
  /**
   * (non-PHPdoc)
   * @see StoreQuerier::prepare()
   */
  public function prepare() {
    // TODO Auto-generated method stub
    
  }

  /**
   * (non-PHPdoc)
   * @see StoreQuerier::query()
   */
  public function query() {
    // TODO Auto-generated method stub
    
  }

  /**
   * (non-PHPdoc)
   * @see StoreQuerier::generateTemporary()
   */
  public function generateTemporary($table) {
    // TODO Auto-generated method stub
    
  }

  /**
   * (non-PHPdoc)
   * @see StoreQuerier::getStatement()
   */
  protected function getStatement() {
    // TODO Auto-generated method stub
    
  }
  
}
