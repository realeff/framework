<?php

/**
 * 插入语句
 *   INSERT INTO table (field0, field1, field2...) VALUES (value0, value1, value2...);
 *   INSERT INTO table (field0, field1, field2...)
 *   VALUES (value0, value1, value2...),
 *   VALUES (value0, value1, value2...),
 *   VALUES (value0, value1, value2...);
 *   INSERT INTO table (field0, field1, field2...)
 *   SELECT field0, field1, field2... FROM tableas [WHERE where]
 *   [GROUP BY group HAVING having] [ORDER BY order [ASC | DESC]];
 * 更新语句
 *   UPDATE table SET field = value [WHERE where];
 *   UPDATE table SET field0 = value0, field1 = value1, field2 = value2... [WHERE where];
 * 删除语句
 *   DELETE FROM table [WHERE where];
 * 查询语句
 *   SELECT * FROM table [WHERE where]
 *   [GROUP BY group [HAVING having]] [ORDER BY order [ASC | DESC]] [LIMIT offset, count];
 *   SELECT [TOP count] * FROM ...;
 *   SELECT field0, field1, field2... FROM ...;
 * 混合查询语句
 *   SELECT t1.*, t2.* FROM table t1 [INNER | LEFT | RIGHT | FULL OUTER] JOIN table t2 ON where
 *   [WHERE where] [GROUP BY group [HAVING having]] [ORDER BY order [ASC | DESC]] [LIMIT offset, count];
 *   SELECT t1.*, t2.field0, t2.field1... FROM table t1 INNER JOIN (SELECT field0, field1, field2... FROM table WHERE where) t2 ON t1.field = t2.field0
 *   [WHERE where] [GROUP BY group [HAVING having]] [ORDER BY order [ASC | DESC]] [LIMIT offset, count];
 */

interface QueryAnalyzerInterface {
  /**
   * 支持查询分析类型
   * 
   * @return string
   */
  public function masktype();
  
  /**
   * 设置查询语句
   * 
   * @param Query $query
   */
  public function setQuery(Query $query);
  
  /**
   * 清理查询分析器
   */
  public function clean();
  
}

/**
 * SQL分析器
 * 
 * @author realeff
 *
 */
abstract class SQLAnalyzer {
  
  /**
   * 查询语句
   * 
   * @var Query
   */
  protected $query;
  
  /**
   * 构造一个查询语句分析器
   * 
   * @param Query $query
   */
  public function __construct(Query $query = NULL) {
    $this->query = $query;
  }
  
  /**
   * 避开查询注释漏洞
   * 
   * @param string $comment
   */
  public function escapeComment($comment) {
    return preg_replace('/(\/\*\s*)|(\s*\*\/)/', '', $comment);
  }
  
  /**
   * 避开查询表名、字段名和别名漏洞
   * 
   * @param string $name
   */
  public function escapeName($name) {
    return preg_replace('/[^A-Za-z0-9_.]+/', '', $name);
  }
  
  /**
   * 产生一个SQL语句注释字符串
   * 
   * @param array $comments
   * 
   * @return string
   */
  public function makeComment(array $comments) {
    if (empty($comments)) {
      return '';
    }
    
    $comment = implode('; ', $comments);
    
    return '/* ' . $this->escapeComment($comment) . ' */ ';
  }
  
  
  /**
   * 返回SQL查询字符串
   * 
   * @return string
   */
  abstract protected function queryString();
  
  /**
   * 返回查询参数组
   * 
   * @return array
   */
  public function arguments() {
    $arguments = array();
    if (isset($this->query)) {
      $params = $this->query->parameter();
      foreach ($params as $field => $value) {
        $arguments[':'. $field] = $value;
      }
    }
    
    return $arguments;
  }
  
  /**
   * 取得查询字符串
   */
  public function __toString() {
    if (!isset($this->query)) {
      return NULL;
    }
    
    // 如果查询器有已经缓存的则直接取已缓存的，否则再进行转换语句。
    //$this->identifier;
    
    $comment = self::makeComment($this->query->getComments());
    
    return $comment . $this->queryString();
  }
  
}

/**
 * SQL查询条件分析器
 * 
 * @author realeff
 *
 */
class SQLConditionAnalyzer extends SQLAnalyzer {
  
  /**
   * 条件
   * 
   * @var QueryCondition
   */
  protected $condition;
  
  
  final public function __construct(QueryCondition $condition) {
    $this->condition = $condition;
  }
  
  public function __toString() {
    return $this->queryString();
  }
  
  final public function &defaultOperator() {
    static $operator_default = array(
        'operator' => '',
        'prefix' => '',
        'delimiter' => '',
        'postfix' => '',
        'use_value' => TRUE
      );
    
    return $operator_default;
  }
  
  public function mapOperator($operator) {
    static $specials = array(
      QueryCondition::BETWEEN       => array('operator' => 'BETWEEN', 'prefix' => '', 'delimiter' => ' AND ', 'postfix' => ''),
      QueryCondition::NOT_BETWEEN   => array('operator' => 'NOT BETWEEN', 'prefix' => '', 'delimiter' => ' AND ', 'postfix' => ''),
      QueryCondition::IN            => array('operator' => 'IN', 'prefix' => '(', 'delimiter' => ', ', 'postfix' => ')'),
      QueryCondition::NOT_IN        => array('operator' => 'NOT IN', 'prefix' => '(', 'delimiter' => ', ', 'postfix' => ')'),
      QueryCondition::EXISTS        => array('operator' => 'EXISTS', 'prefix' => '(', 'delimiter' => '', 'postfix' => ')'),
      QueryCondition::NOT_EXISTS    => array('operator' => 'NOT EXISTS', 'prefix' => '(', 'delimiter' => '', 'postfix' => ')'),
      QueryCondition::IS_NULL       => array('operator' => 'IS NULL', 'use_value' => FALSE),
      QueryCondition::IS_NOT_NULL   => array('operator' => 'IS NOT NULL', 'use_value' => FALSE),
      QueryCondition::LIKE          => array('operator' => 'LIKE', 'postfix' => " ESCAPE '\\\\'"),
      QueryCondition::NOT_LIKE      => array('operator' => 'NOT LIKE', 'postfix' => " ESCAPE '\\\\'"),
      // 比较表达示
      QueryCondition::EQUAL         => array('operator' => '='),
      QueryCondition::NOT_EQUAL     => array('operator' => '<>'),
      QueryCondition::GREATER       => array('operator' => '>'),
      QueryCondition::GREATER_EQUAL => array('operator' => '>='),
      QueryCondition::LESS          => array('operator' => '<'),
      QueryCondition::LESS_EQUAL    => array('operator' => '<='),
    );
    
    // 允许使用非标准操作符
    return isset($specials[$operator]) ? $specials[$operator] : array('operator' => $operator);
  }
  
  public function queryAnalyzer(SelectQuery $query) {
    return new SQLSelectAnalyzer($query);
  }
  
  /**
   * 返回SQL查询条件字符串
   *
   * @return string
   */
  protected function queryString() {
    static $conjunctions = array(
        QueryCondition::_AND_ => ' AND ',
        QueryCondition::_OR_  => ' OR '
      );
    
    $str = '';
    foreach ($this->condition as $condition) {
      if (is_array($condition)) {
        if (isset($condition['operator']) && isset($condition['field'])) {
          if ($condition['operator'] === QueryCondition::WHERE) {
            $str .= '('. $condition['field'] .')';
            continue;
          }
          $str .= self::escapeName($condition['field']);
          
          $operator = $this->mapOperator($condition['operator']);
          $operator += self::defaultOperator();
          $str .= ' '. $operator['operator'] .' '. $operator['prefix'];
          
          if ($condition['value'] instanceof SelectQuery) {
            $str .= (string)$this->queryAnalyzer($condition['value']);
            $operator['use_value'] = FALSE;
          }
          else if ($operator['use_value']) {
            $placeholders = array();
            $condition['value'] = is_array($condition['value']) ? $condition['value'] : array($condition['value']);
            foreach ($condition['value'] as $value) {
              $placeholders[] = ':' . $value;
            }
            
            $str .= implode($operator['delimiter'], $placeholders);
          }
          
          $str .= $operator['postfix'];
        }
        else if ($condition['value'] instanceof QueryCondition) {
          $conditionAnalyzer = new $this($condition['value']);
          $str .= '('. (string)$conditionAnalyzer .')';
        }
      }
      else {
        $str .= $conjunctions[$condition];
      }
    }
    
    return $str;
  }
  
}

/**
 * 插入数据SQL分析器
 * 
 * @author realeff
 *
 */
class SQLInsertAnalyzer extends SQLAnalyzer implements QueryAnalyzerInterface {
  
  /**
   * 取得筛选查询SQL分析器
   * 
   * @return SelectSQLAnalyzer
   */
  public function queryAnalyzer(SelectQuery $query) {
    return new SQLSelectAnalyzer($query);
  }

  /**
   * (non-PHPdoc)
   * @see SQLAnalyzer::queryString()
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
    foreach ($this->values as $values) {
      $placeholders = array();
      foreach ($fields as $field) {
        $placeholders[] = $values[$field];
      }
      
      $pieces[] = 'INSERT INTO {'. $table .'} ('. implode(', ', $fields) .') VALUES (:' . implode(', :', $values) . ')';
    }
    
    return implode(";\n", $pieces);
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::masktype()
   */
  public function masktype() {
    // TODO Auto-generated method stub
    return Query::INSERT;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::clean()
   */
  public function clean() {
    // TODO Auto-generated method stub
    $this->query = NULL;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::setQuery()
   */
  public function setQuery(Query $query) {
    // TODO Auto-generated method stub
    $this->query = $query;
  }
  
}

class SQLUpdateAnalyzer extends SQLAnalyzer implements QueryAnalyzerInterface {
  
  /**
   * 条件分析器
   * 
   * @param QueryCondition $condition
   */
  public function conditionAnalyzer(QueryCondition $condition) {
    return new SQLConditionAnalyzer($condition);
  }

  /**
   * (non-PHPdoc)
   * @see SQLAnalyzer::queryString()
   */
  protected function queryString() {
    // TODO Auto-generated method stub
    if (!($this->query instanceof UpdateQuery)) {
      return NULL;
    }
    
    $table = self::escapeName($this->query->getTable());
    $fields = $this->query->getFields();
    $arguments = $this->query->getArguments();
    $condition = $this->query->where();
    
    $updateFields = array();
    foreach ($fields as $field => $value) {
      if (isset($arguments[$field])) {
        $updateFields[] = self::escapeName($field) .'='. $value;
      }
      else {
        $updateFields[] = self::escapeName($field) .'=:'. $value;
      }
    }
    
    $sql = 'UPDATE {'. $table .'} SET '. implode(', ', $updateFields);
    if (count($condition) > 0) {
      $sql .= ' WHERE '. $this->conditionAnalyzer($condition);
    }
    
    return $sql;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::masktype()
   */
  public function masktype() {
    // TODO Auto-generated method stub
    return Query::UPDATE;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::clean()
   */
  public function clean() {
    // TODO Auto-generated method stub
    $this->query = NULL;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::setQuery()
   */
  public function setQuery(Query $query) {
    // TODO Auto-generated method stub
    $this->query = $query;
  }
  
}

class SQLDeleteAnalyzer extends SQLAnalyzer implements QueryAnalyzerInterface {
  
  /**
   * 启动事务
   * 
   * @var boolean
   */
  protected $transaction = FALSE;
  
  
  public function conditionAnalyzer(QueryCondition $condition) {
    return new SQLConditionAnalyzer($condition);
  }
  
  public function transaction($transaction = TRUE) {
    $this->transaction = $transaction;
  }
  
  /**
   * (non-PHPdoc)
   * @see SQLAnalyzer::queryString()
   */
  protected function queryString() {
    // TODO Auto-generated method stub
    if (!($this->query instanceof DeleteQuery)) {
      return NULL;
    }
    
    $table = self::escapeName($this->query->getTable());
    $condition = $this->query->where();
    
    $sql = 'DELETE FROM {'. $table .'} ';
    if (count($condition) > 0) {
      $sql .= ' WHERE '. $this->conditionAnalyzer($condition);
    }
    else if (!$this->transaction) {
      $sql = 'TRUNCATE {' . $table . '} ';
    }
    
    return $sql;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::masktype()
   */
  public function masktype() {
    // TODO Auto-generated method stub
    return Query::DELETE;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::clean()
   */
  public function clean() {
    // TODO Auto-generated method stub
    $this->query = NULL;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::setQuery()
   */
  public function setQuery(Query $query) {
    // TODO Auto-generated method stub
    $this->query = $query;
  }
  
}

class SQLSelectAnalyzer extends SQLAnalyzer implements QueryAnalyzerInterface {
  
  public function conditionAnalyzer(QueryCondition $condition) {
    return new SQLConditionAnalyzer($condition);
  }
  
  public function mapOrder($direction) {
    static $orders = array(
      SelectQuery::ASC       => 'ASC',
      SelectQuery::DESC   => 'DESC',
      SelectQuery::RANDOM            => '',
    );
    
    // 允许使用非标准操作符
    return isset($orders[$direction]) ? $orders[$direction] : $direction;
  }
  
  /**
   * (non-PHPdoc)
   * @see SQLAnalyzer::queryString()
   */
  protected function queryString() {
    // TODO Auto-generated method stub
    if (!($this->query instanceof SelectQuery)) {
      return NULL;
    }
    
    $table = self::escapeName($this->query->getTable());
    $table_alias = '';
    
    if ($this->query instanceof MultiSelectQueryInterface) {
      $table_alias = self::escapeName($this->query->getAlias()) .'.';
    }
    
    $fields = array();
    $arguments = $this->query->getArguments();
    foreach ($this->query->getFields() as $alias => $field) {
      if ($field == '*') {
        $fields[] = $table_alias .$field;
        continue;
      }
      
      $field = self::escapeName($field);
      if (isset($arguments[$alias])) {
        $fields[] = $field .' AS '. self::escapeName($alias);
      }
      else {
        $alias = self::escapeName($alias);
        $fields[] = $table_alias .$field .($alias == $field ? '' : ' AS '. $alias);
      }
    }
    
    $tables = array();
    $tables[] = '{'. $table .'}'. (empty($table_alias) ? '' : ' '. trim($table_alias, '.'));
    
    $unions = array();
    if ($this->query instanceof MultiSelectQueryInterface) {
      $joins =& $this->query->getJoins();
      $unions =& $this->query->getUnions();
      
      foreach ($joins as $join) {
        $table_alias = self::escapeName($join['alias']);
        foreach ($join['fields'] as $alias => $field) {
          if ($field == '*') {
            $fields[] = $table_alias .'.'. $field;
            continue;
          }
          
          $field = self::escapeName($field);
          $alias = self::escapeName($alias);
          $fields[] = $table_alias .'.'. $field .($alias == $field ? '' : ' AS '. $alias);
        }
        
        $table_string = self::escapeName(strtoupper($join['masktype'])) .' JOIN ';
        
        if ($join['table'] instanceof SelectQuery) {
          $query = new $this($join['table']);
          $table_string .= '('. (string)$query .')';
        }
        else {
          $table_string .= '{'. self::escapeName($join['table']) .'}';
        }
        $table_string .= ' '. $table_alias;
        
        if (!empty($join['where'])) {
          $table_string .= ' ON '. $join['where'];
//           if ($join['where'] instanceof QueryCondition) {
//             $table_string .= $this->conditionAnalyzer($join['where']);
//           }
//           else {
//             $table_string .= $join['where'];
//           }
        }
        
        $tables[] = $table_string;
      }
    }
    
    $orders = array();
    foreach ($this->query->getOrders() as $field => $direction) {
      $field = self::escapeName($field);
      if ($direction === SelectQuery::RANDOM) {
        $fields[] = 'RAND() AS '. $field;
      }
      
      $orders[] = $field .' '. self::mapOrder($direction);
    }
    
    $sql = 'SELECT '. ($this->query->getDistinct() ? 'DISTINCT ' : '') .implode(', ', $fields) .' FROM '. implode(' ', $tables);
    
    $condition = $this->query->where();
    if (count($condition) > 0) {
      $sql .= ' WHERE '. $this->conditionAnalyzer($condition);
    }
    
    $groups = $this->query->getGroups();
    if ($groups) {
      $sql .= ' GROUP BY '. implode(', ', $groups);
    }
    
    $having = $this->query->having();
    if (count($having) > 0) {
      $sql .= ' HAVING '. $this->conditionAnalyzer($having);
    }
    
    if ($orders) {
      $sql .= ' ORDER BY '. implode(', ', $orders);
    }
    
    $limit = $this->query->getLimit();
    if ($limit) {
      list($offset, $row_count) = $limit;
      $sql .= " LIMIT " . (int)$row_count . " OFFSET " . (int)$offset;
    }
    
    if ($unions) {
      foreach ($unions as $union) {
        $query = new $this($union['query'], $this->placeholder);
        $sql .= ' UNION ' . $union['masktype'] . ' ' . (string)$query;
      }
    }
    
    if ($this->query->getUpdate()) {
      $sql .= ' FOR UPDATE';
    }
    
    return $sql;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::masktype()
   */
  public function masktype() {
    // TODO Auto-generated method stub
    return Query::SELECT | Query::MULTISELECT;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::clean()
   */
  public function clean() {
    // TODO Auto-generated method stub
    $this->query = NULL;
  }

  /**
   * (non-PHPdoc)
   * @see QueryAnalyzerInterface::setQuery()
   */
  public function setQuery(Query $query) {
    // TODO Auto-generated method stub
    $this->query = $query;
  }
  
}

