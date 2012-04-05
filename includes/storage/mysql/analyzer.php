<?php

defined('STORE_DRIVER_PATH') or die;

class SQLInsertAnalyzer_mysql extends SQLInsertAnalyzer {

  protected function compile() {
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
        $placeholders[] = $this->queryParameter->add($field, $values[$field]);
      }

      $pieces[] = '(:' . implode(', :', $placeholders) . ')';
    }

    $this->queryString = 'INSERT INTO {'. $table .'} ('. implode(', ', $fields) .') VALUES ' . implode(', ', $pieces);
  }

}

class SQLUniqueInsertAnalyzer_mysql extends SQLUniqueInsertAnalyzer {

  protected function compile() {
    // TODO Auto-generated method stub
    if (!($this->query instanceof UniqueInsertQuery)) {
      return NULL;
    }

    $table = self::escapeName($this->query->getTable());
    $keys = $this->query->getKeys();
    // 查询数据，如果数据已经有则更新数据，否则插入数据。
    // 准备插入内容
    $insertFields = $keys + $this->query->getInsertFields();
    $fields = $values = array();
    foreach ($insertFields as $field => $value) {
      $fields[] = self::escapeName($field);
      $values[] = $this->queryParameter->add($field, $value);
    }

    // 准备更新内容
    $updateFields = $this->query->getUpdateFields();
    $arguments = $this->query->getArguments();
    $placeholders = array();
    foreach ($updateFields as $field => $value) {
      if (isset($arguments[$field])) {
        $placeholders[] = self::escapeName($field) .'='. $value;
        foreach ($arguments[$field] as $field => $value) {
          $this->queryParameter[$field] = $value;
        }
      }
      else {
        $placeholders[] = self::escapeName($field) .'=:'. $this->queryParameter->add($field, $value);
      }
    }
    return 'INSERT INTO {'. $table .'} ('. implode(', ', $fields) .') VALUES (:'. implode(', :', $values)
          .') ON DUPLICATE KEY UPDATE '. implode(', ', $placeholders);
  }

}