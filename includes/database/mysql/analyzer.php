<?php

defined('STORE_DRIVER_PATH') or die;

class SQLInsertAnalyzer_mysql extends SQLInsertAnalyzer {

  /**
   * (non-PHPdoc)
   * @see SQLInsertAnalyzer::queryString()
   */
  protected function queryString() {
    // TODO Auto-generated method stub
    if (!($this->query instanceof InsertQuery)) {
      return NULL;
    }
    
    $table = self::escapeName($this->query->getTable());
    $fields = $this->query->getFields();
    foreach ($fields as $key => $field) {
      $fields[$key] = self::escapeName($field);
    }
    
    $query = $this->query->select();
    if (!empty($query)) {
      return 'INSERT INTO {'. $table .'} ('. implode(', ', $fields) .') '. $this->queryAnalyzer($query);
    }
    
    $pieces = array();
    foreach ($this->query->getValues() as $values) {
      $placeholders = array();
      foreach ($fields as $field) {
        $placeholders[] = $values[$field];
      }
      
      $pieces[] = '(:' . implode(', :', $placeholders) . ')';
    }
    
    return 'INSERT INTO {'. $table .'} ('. implode(', ', $fields) .') VALUES ' . implode(', ', $pieces);
  }
  
}