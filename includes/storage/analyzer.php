<?php

/**
 * 插入语句
 *   INSERT INTO table (field0, field1, field2...) VALUES (value0, value1, value2...);
 *   INSERT INTO table (field0, field1, field2...)
 *   VALUES (value0, value1, value2...),
 *   VALUES (value0, value1, value2...),
 *   VALUES (value0, value1, value2...);
 *   INSERT INTO table (field0, field1, field2...)
 *   SELECT field0, field1, field2... FROM tableas [WHERE condition]
 *   [GROUP BY group HAVING having] [ORDER BY order [ASC | DESC]];
 * 更新语句
 *   UPDATE table SET field = value [WHERE condition];
 *   UPDATE table SET field0 = value0, field1 = value1, field2 = value2... [WHERE condition];
 * 删除语句
 *   DELETE FROM table [WHERE condition];
 * 查询语句
 *   SELECT * FROM table [WHERE condition]
 *   [GROUP BY group [HAVING having]] [ORDER BY order [ASC | DESC]] [LIMIT offset, count];
 *   SELECT [TOP count] * FROM ...;
 *   SELECT field0, field1, field2... FROM ...;
 * 混合查询语句
 *   SELECT t1.*, t2.* FROM table t1 [INNER | LEFT | RIGHT | FULL OUTER] JOIN table t2 ON condition
 *   [WHERE condition] [GROUP BY group [HAVING having]] [ORDER BY order [ASC | DESC]] [LIMIT offset, count];
 *   SELECT t1.*, t2.field0, t2.field1... FROM table t1 INNER JOIN (SELECT field0, field1, field2... FROM table WHERE condition) t2 ON t1.field = t2.field0
 *   [WHERE condition] [GROUP BY group [HAVING having]] [ORDER BY order [ASC | DESC]] [LIMIT offset, count];
 */


/**
 * SQL分析器
 * 
 * @author realeff
 *
 */
abstract class SQLAnalyzer {
  
  /**
   * 注释
   * 
   * @var array
   */
  protected $comments = array();
  
  /**
   * 数据表
   * 
   * @var string
   */
  protected $table = '';
  
  /**
   * 查询参数
   * 
   * @var QueryParameter
   */
  protected $parameter;
  
  /**
   * 构造一个SQL查询分析器
   * 
   * @param Query $query
   */
  public function __construct(Query $query) {
    $this->comments =& $query->getComments();
    $this->table = self::escapeName($query->getTable());
    $this->parameter = $query->getParameter();
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
  abstract public function toString();
  
  /**
   * 返回查询参数组
   * 
   * @return array
   */
  public function arguments() {
    $arguments = array();
    foreach ($this->parameter as $field => $value) {
      $arguments[':'. $field] = $value;
    }
    
    return $arguments;
  }
  
  /**
   * 取得查询字符串
   */
  public function __toString() {
    $comment = self::makeComment($this->comments);
    
    return $comment . $this->toString();
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
  public function toString() {
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
class SQLInsertAnalyzer extends SQLAnalyzer {
  
  /**
   * 数据字段
   * 
   * @var array
   */
  protected $fields;
  
  /**
   * 数据值数组
   * 
   * @var array
   */
  protected $values;
  
  /**
   * 默认数据
   * 
   * @var array
   */
  protected $defaults = array();
  
  /**
   * 筛选查询
   * 
   * @var SelectQuery
   */
  protected $fromQuery;
  
  
  public function __construct(InsertQuery $query) {
    parent::__construct($query);
    
    $this->fields =& $query->getFields();
    $this->values =& $query->getValues();
    $this->fromQuery = $query->getSelect();
    
    $this->parameter->addParam('ins'. count($this->values));
  }
  
  /**
   * 取得筛选查询SQL分析器
   * 
   * @return SelectSQLAnalyzer
   */
  public function fromSQLAnalyzer() {
    return new SQLSelectAnalyzer($this->fromQuery);
  }
  
  /**
   * 使用默认字段值
   * 
   * @param array $defaultValues
   */
  public function useDefaults(array $defaultValues) {
    foreach ($defaultValues as $field => $value) {
      foreach ($this->values as $key => $values) {
        if (!isset($values[$field]))
          $this->values[$key][$field] = $this->parameter->add($field, $value);
      }
    }
    
    $this->defaults = $defaultValues;
  }

  /**
   * (non-PHPdoc)
   * @see SQLAnalyzer::toString()
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
    
    $pieces = array();
    foreach ($fields as $key => $field) {
      $fields[$key] = self::escapeName($field);
    }
    foreach ($this->values as $values) {
      $placeholders = array();
      foreach ($fields as $field) {
        $placeholders[] = $values[$field];
      }
      
      $pieces[] = 'INSERT INTO {'. $this->table .'} ('. implode(', ', $fields) .') VALUES (:' . implode(', :', $values) . ')';
    }
    
    return implode(";\n", $pieces);
  }
  
}

class SQLUpdateAnalyzer extends SQLAnalyzer {
  
  /**
   * 数据字段
   *
   * @var array
   */
  protected $fields;
  
  /**
   * 数据表达式字段参数
   *
   * @var array
   */
  protected $arguments;
  
  /**
   * 查询条件
   * 
   * @var QueryCondition
   */
  protected $condition;
  
  
  public function __construct(UpdateQuery $query) {
    parent::__construct($query);
    
    $this->fields =& $query->getFields();
    $this->arguments =& $query->getArguments();// 字段表达式参数
    $this->condition = $query->condition();
  }
  
  
  public function conditionAnalyzer() {
    return new SQLConditionAnalyzer($this->condition);
  }

  /**
   * (non-PHPdoc)
   * @see SQLAnalyzer::toString()
   */
  public function toString() {
    // TODO Auto-generated method stub
    $fields = array();
    foreach ($this->fields as $field => $value) {
      if (isset($this->arguments[$field])) {
        $fields[] = self::escapeName($field) .'='. $value;
      }
      else {
        $fields[] = self::escapeName($field) .'=:'. $value;
      }
    }
    
    $sql = 'UPDATE {'. $this->table .'} SET '. implode(', ', $fields);
    if (count($this->condition) > 0) {
      $sql .= ' WHERE '. $this->conditionAnalyzer();
    }
    
    return $sql;
  }
  
}

class SQLDeleteAnalyzer extends SQLAnalyzer {
  
  /**
   * 查询条件
   *
   * @var QueryCondition
   */
  protected $condition;
  
  /**
   * 启动事务
   * 
   * @var boolean
   */
  protected $transaction = FALSE;
  
  
  public function __construct(DeleteQuery $query) {
    parent::__construct($query);
    
    $this->condition = $query->condition();
  }
  
  public function conditionAnalyzer() {
    return new SQLConditionAnalyzer($this->condition);
  }
  
  public function transaction($transaction = TRUE) {
    $this->transaction = $transaction;
  }
  
  /**
   * (non-PHPdoc)
   * @see SQLAnalyzer::toString()
   */
  public function toString() {
    // TODO Auto-generated method stub
    $sql = 'DELETE FROM {'. $this->table .'} ';
    if (count($this->condition) > 0) {
      $sql .= ' WHERE '. $this->conditionAnalyzer();
    }
    else if (!$this->transaction) {
      $sql = 'TRUNCATE {' . $this->table . '} ';
    }
    
    return $sql;
  }
  
}

class SQLSelectAnalyzer extends SQLAnalyzer {
  
  /**
   * 
   * @var SelectQuery
   */
  protected $query;
  
  /**
   * 数据字段
   *
   * @var array
   */
  protected $fields;
  
  /**
   * 数据表达式字段参数
   *
   * @var array
   */
  protected $arguments;
  
  /**
   * 查询条件
   * 
   * @var QueryCondition
   */
  protected $condition;
  
  /**
   * 数据分组
   *
   * @var array
   */
  protected $groups;
  
  /**
   * 分组条件
   *
   * @var QueryCondition
   */
  protected $having;
  
  /**
   * 数据排序
   *
   * @var array
   */
  protected $orders;
  
  /**
   * 查询数量限制
   * 
   * @var array
   */
  protected $limit;
  
  
  final public function __construct(SelectQuery $query, QueryPlaceholder $placeholder = NULL) {
    parent::__construct($query);
    
    $this->query = $query;
    $this->fields =& $query->getFields();
    $this->arguments =& $query->getArguments();
    $this->condition = $query->condition();
    $this->groups =& $query->getGroups();
    $this->having = $query->having();
    $this->orders =& $query->getOrders();
    $this->limit =& $query->getLimit();
  }
  
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
   * @see SQLAnalyzer::toString()
   */
  public function toString() {
    // TODO Auto-generated method stub
    $table_alias = '';
    if ($this->query instanceof MultiSelectQueryInterface) {
      $table_alias = self::escapeName($this->query->getAlias()) .'.';
    }
    
    $fields = array();
    foreach ($this->fields as $alias => $field) {
      if ($field == '*') {
        $fields[] = $table_alias .$field;
        continue;
      }
      
      $field = self::escapeName($field);
      if (isset($this->arguments[$alias])) {
        $fields[] = $field .' AS '. self::escapeName($alias);
      }
      else {
        $alias = self::escapeName($alias);
        $fields[] = $table_alias .$field .($alias == $field ? '' : ' AS '. $alias);
      }
    }
    
    $tables = array();
    $tables[] = '{'. $this->table .'}'. (empty($this->table_alias) ? '' : ' '. $this->table_alias);
    
    $unions = array();
    if ($this->query instanceof MultiSelectQueryInterface) {
      $joins =& $this->query->getJoins();
      $unions =& $this->query->getUnions();
      
      foreach ($joins as $join) {
        $table_alias = self::escapeName($join['alias']);
        foreach ($join['fields'] as $alias => $field) {
          if ($field == '*') {
            $fields[] = $table_alias .$field;
            continue;
          }
          
          $field = self::escapeName($field);
          $alias = self::escapeName($alias);
          $fields[] = $table_alias .'.'. $field .($alias == $field ? '' : ' AS '. $alias);
        }
        
        $table_string = self::escapeName(strtoupper($join['type'])) .' JOIN ';
        
        if ($join['table'] instanceof SelectQuery) {
          $query = new $this($join['table']);
          $table_string .= '('. (string)$query .')';
        }
        else {
          $table_string .= '{'. self::escapeName($join['table']) .'}';
        }
        $table_string .= ' '. $table_alias;
        
        if (!empty($join['condition'])) {
          $table_string .= ' ON '. $join['condition'];
        }
        
        $tables[] = $table_string;
      }
    }
    
    $orders = array();
    foreach ($this->orders as $field => $direction) {
      $field = self::escapeName($field);
      if ($direction === SelectQuery::RANDOM) {
        $fields[] = 'RAND() AS '. $field;
      }
      
      $orders[] = $field .' '. self::mapOrder($direction);
    }
    
    $sql = 'SELECT '. ($this->query->getDistinct() ? 'DISTINCT ' : '') .implode(', ', $fields) .' FROM '. implode(' ', $tables);
    if (count($this->condition) > 0) {
      $sql .= ' WHERE '. $this->conditionAnalyzer($this->condition);
    }
    
    if ($this->groups) {
      $sql .= ' GROUP BY '. implode(', ', $this->groups);
    }
    
    if (count($this->having) > 0) {
      $sql .= ' HAVING '. $this->conditionAnalyzer($this->having);
    }
    
    if ($orders) {
      $sql .= ' ORDER BY '. implode(', ', $orders);
    }
    
    if ($this->limit) {
      list($offset, $row_count) = $this->limit;
      $sql .= " LIMIT " . (int)$row_count . " OFFSET " . (int)$offset;
    }
    
    if ($unions) {
      foreach ($unions as $union) {
        $query = new $this($union['query'], $this->placeholder);
        $sql .= ' UNION ' . $union['type'] . ' ' . (string)$query;
      }
    }
    
    if ($this->query->getUpdate()) {
      $sql .= ' FOR UPDATE';
    }
    
    return $sql;
  }
  
}

