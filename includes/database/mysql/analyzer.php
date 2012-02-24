<?php

class SQLInsertAnalyzer_mysql extends SQLInsertAnalyzer {

  /**
   * (non-PHPdoc)
   * @see SQLInsertAnalyzer::queryString()
   */
  protected function queryString() {
    // TODO Auto-generated method stub
    if (!empty($this->queryFrom)) {
      return 'INSERT INTO {'. $this->table .'} ('. implode(', ', $this->fields) .') '. $this->fromSQLAnalyzer();
    }
    
    $fields = $this->fields;
    foreach ($this->defaults as $field => $value) {
      !isset($fields[$field]) && ($fields[$field] = $field);
    }
    
    foreach ($fields as $key => $field) {
      $fields[$key] = self::escapeName($field);
    }
    
    $values = array();
    foreach ($this->values as $values) {
      $placeholders = array();
      foreach ($fields as $field) {
        $placeholders[] = $values[$field];
      }
      $values[] = '(:' . implode(', :', $placeholders) . ')';
    }
    
    return 'INSERT INTO {'. $this->table .'} ('. implode(', ', $fields) .') VALUES ' . implode(', ', $values);
  }
  
}