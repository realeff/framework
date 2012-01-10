<?php

class SQLInsertAnalyzer_mysql extends SQLInsertAnalyzer {

  /**
   * (non-PHPdoc)
   * @see SQLInsertAnalyzer::arguments()
   */
  public function arguments() {
    // TODO Auto-generated method stub
    if (!empty($this->fromQuery)) {
      return $this->fromSQLAnalyzer()->arguments();
    }
    
    $arguments = array();
    foreach ($this->values as $id => $values) {
      $values += $this->defaults;
      
      foreach ($this->fields as $key => $field) {
        $arguments[':'. $field .'_'. $id] = $values[$key];
      }
    }
    
    return $arguments;
  }

  /**
   * (non-PHPdoc)
   * @see SQLInsertAnalyzer::toString()
   */
  public function toString() {
    // TODO Auto-generated method stub
    if (!empty($this->fromQuery)) {
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
    $count = count($this->values);
    for($id = 0; $id < $count; $id++){
      $placeholders = array();
      foreach ($fields as $key => $field) {
        $placeholders[] = ':'. $field .'_'. $id;
      }
      $values[] = '(' . implode(', ', $placeholders) . ')';
    }
    
    return 'INSERT INTO {'. $this->table .'} ('. implode(', ', $fields) .') VALUES ' . implode(', ', $values);
  }

  
}