<?php


/**
 * 查询分析器
 *
 * @author feng
 *Query Analyzer
 */
abstract class Query {
  
  /**
   * 插入
   */
  const INSERT = 0x001;
  
  /**
   * 更新
   */
  const UPDATE = 0x002;
  
  /**
   * 删除
   */
  const DELETE = 0x004;
  
  /**
   * 查询
   */
  const SELECT = 0x008;
  
  /**
   * 联合查询
   */
  const MULTISELECT = 0x010;
  
  /**
   * 标识符
   * 
   * @var string
   */
  protected $identifier;
  
  /**
   * 查询语句注解
   * 
   * @var array
   */
  protected $comments = array();
  
  /**
   * 数据表
   * 
   * @var string
   */
  protected $table;
  
  /**
   * 结束查询
   * 
   * @var boolean
   */
  protected $end = FALSE;
  
  /**
   * 查询参数
   *
   * @var QueryParameter
   */
  protected $parameter;
  
  /**
   * 构造一个查询分类器
   *
   * @param string $table
   * @param QueryParameter $parameter
   */
  public function __construct($table, QueryParameter $parameter) {
    $this->table = $table;
    $this->parameter = $parameter;
  }
  
  /**
   * 查询器类型
   */
  abstract public function type();
  
  /**
   * 获取数据表名
   * 
   * @return string
   */
  final public function getTable() {
    return $this->table;
  }
  
  /**
   * 获取查询参数
   * 
   * @return QueryParameter
   */
  final public function parameter() {
    return $this->parameter;
  }

  /**
   * 添加关于此查询语句的注解
   * 
   * @param string $comment 注解内容
   * 
   * @return Query
   */
  public function addComment($comment) {
    $this->comments[] = $comment;
    return $this;
  }
  
  /**
   * 获取查询语句的注解
   * 
   * @return array
   */
  public function &getComments() {
    return $this->comments;
  }

  /**
   * 设置查询标识符
   * 
   * @param string $identifier
   */
  public function setIdentifier($identifier) {
    $this->identifier = $identifier;
  }
  
  /**
   * 获取查询标识符
   * 
   * @return string
   */
  public function getIdentifier() {
    return $this->identifier;
  }

  /**
   * 结束查询器并返回到存储器命令操作
   *
   * @return Query
   */
  abstract public function end();
  
  /**
   * 执行查询
   */
//   public function execute() {
//     //$this->end();
//   }
}


/**
 * 查询参数
 *
 * @author realeff
 *
 */
class QueryParameter implements Iterator, ArrayAccess, Countable {

  /**
   * 计数列表
   *
   * @var array
   */
  private $_counters = array();

  /**
   * 参数容器
   *
   * @var array
   */
  private $_container = array();

  /**
   * 获取唯一参数名
   *
   * @return string
   */
  protected function uniqueName($name = 'param') {
    $pos = strrpos($name, '.');
    if ($pos !== FALSE) {
      $name = substr($name, $pos+1);
    }
    if (!isset($this->_counters[$name])) {
      $this->_counters[$name] = 0;
    }

    return isset($this->_container[$name]) ? $name .'_'. $this->_counters[$name]++ : $name;
  }

  /**
   * 添加参数
   *
   * @param string $field 参数名
   * @param mixed $value 参数值
   *
   * @return string
   *   返回表示参数的唯一名称
   */
  public function add($field, $value) {
    $field = self::uniqueName($field);
    $this->_container[$field] = $value;

    return $field;
  }

  /**
   * (non-PHPdoc)
   * @see ArrayAccess::offsetExists()
   */
  public function offsetExists($offset) {
    // TODO Auto-generated method stub
    return isset($this->_container[$offset]);
  }

  /**
   * (non-PHPdoc)
   * @see ArrayAccess::offsetGet()
   */
  public function offsetGet($offset) {
    // TODO Auto-generated method stub
    return $this->_container[$offset];
  }

  /**
   * (non-PHPdoc)
   * @see ArrayAccess::offsetSet()
   */
  public function offsetSet($offset, $value) {
    // TODO Auto-generated method stub
    if (isset($offset)) {
      $this->_container[$offset] = $value;
    }
    else {
      $offset = self::uniqueName();
      $this->_container[$offset] = $value;
    }

    return $offset;
  }

  /**
   * (non-PHPdoc)
   * @see ArrayAccess::offsetUnset()
   */
  public function offsetUnset($offset) {
    // TODO Auto-generated method stub
    unset($this->_container[$offset]);
  }

  /**
   * (non-PHPdoc)
   * @see Iterator::current()
   */
  public function current() {
    // TODO Auto-generated method stub
    return current($this->_container);
  }

  /**
   * (non-PHPdoc)
   * @see Iterator::key()
   */
  public function key() {
    // TODO Auto-generated method stub
    return key($this->_container);
  }

  /**
   * (non-PHPdoc)
   * @see Iterator::next()
   */
  public function next() {
    // TODO Auto-generated method stub
    return next($this->_container);
  }

  /**
   * (non-PHPdoc)
   * @see Iterator::rewind()
   */
  public function rewind() {
    // TODO Auto-generated method stub
    return reset($this->_container);
  }

  /**
   * (non-PHPdoc)
   * @see Iterator::valid()
   */
  public function valid() {
    // TODO Auto-generated method stub
    return key($this->_container) !== NULL;
  }

  /**
   * (non-PHPdoc)
   * @see Countable::count()
   */
  public function count() {
    // TODO Auto-generated method stub
    return count($this->_container);
  }

}

/**
 * 建立一个查询过滤条件
 *
 * @author feng
 *
 */
class QueryCondition implements IteratorAggregate, Countable {
  
  /**
   * 等于操作符
   */
  const EQUAL = '=';
  
  /**
   * 不等于操作符
   */
  const NOT_EQUAL = '<>';
  
  /**
   * 大于操作符
   */
  const GREATER = '>';
  
  /**
   * 大于并且等于操作符
   */
  const GREATER_EQUAL = '>=';
  
  /**
   * 小于操作符
   */
  const LESS = '<';
  
  /**
   * 小于并且等于操作符
   */
  const LESS_EQUAL = '<=';
  
  /**
   * 是NULL操作符
   */
  const IS_NULL = 1;
  
  /**
   * 非NULL操作符
   */
  const IS_NOT_NULL = 2;
  
  /**
   * EXISTS查询操作符
   */
  const EXISTS = 3;
  
  /**
   * NOT EXISTS查询操作符
   */
  const NOT_EXISTS = 4;
  
  /**
   * IN查询操作符
   */
  const IN = 5;
  
  /**
   * NOT IN查询操作符
   */
  const NOT_IN = 6;
  
  /**
   * LIKE查询操作符
   */
  const LIKE = 7;
  
  /**
   * NOT LIKE查询操作符
   */
  const NOT_LIKE = 8;
  
  /**
   * BETWEEN查询操作符
   */
  const BETWEEN = 9;
  
  /**
   * NOT BETWEEN查询操作符
   */
  const NOT_BETWEEN = 10;
  
  /**
   * 自定义查询条件
   */
  const WHERE = 1;
  
  /**
   * 逻辑与条件链接符
   */
  const _AND_ = 1;
  
  /**
   * 逻辑或条件链接符
   */
  const _OR_ = 2;
  

  /**
   * @var Query
   */
  protected $query;
  
  /**
   * @var QueryCondition
   */
  private $_parent;
  
  /**
   * @var QueryCondition
   */
  private $_current;
  
  /**
   * 过滤条件
   *
   * @var array
   * 
   * @example
   *   $this->conditions[] = array(
   *     'field' => 'field',
   *     'value' => 'value',
   *     'operator' => QueryCondition::EQUAL,
   *   );
   * @example 
   *   $this->conditions[] = QueryCondition::_OR_;
   * @example
   *   $this->conditions[] = array(
   *     'field' => 'field',
   *     'value' => array(),
   *     'operator' => QueryCondition::CONTAIN,
   * @example
   *   $this->conditions[] = array(
   *     'field' => 'field',
   *     'value' => '%val%',
   *     'operator' => QueryCondition::CONTAIN,
   * @example
   *   $this->conditions[] = array(
   *     'field' => 'field',
   *     'value' => QueryCondition,
   *     'operator' => QueryCondition::EXISTS,
   */
  protected $conditions = array();
  
  /**
   * 条件参数
   * 
   * @var QueryParameter
   */
  protected $parameter;
  
  /**
   * 结合操作符
   * 
   * @var int
   *   QueryCondition::_AND_
   *   QueryCondition::_OR_
   */
  protected $conjunction;
  
  /**
   * 条件数量
   */
  private $_count = 0;
  
  /**
   * 构造一个过滤条件
   *
   * @param QueryParameter $parameter;
   * @param Query $query;
   */
  public function __construct(QueryParameter $parameter, Query $query = NULL) {
    $this->conjunction = self::_AND_;
    $this->query = $query;
    $this->_current = $this;
    $this->parameter = $parameter;
  }
  
  /**
   * 结束查询条件并返回到查询器
   * 
   * @return Query
   */
  public function end() {
    if (isset($this->_parent)) {
      $this->_parent->end();
      unset($this->query, $this->_parent);
    }
    else if (isset($this->_current)) {
      $query = $this->query;
      $current = $this->_current;
      unset($this->query, $this->_current);
      
      $current->end();
      return $query;
    }
  }
  
  /**
   * 返回父查询条件
   * 
   * @return QueryCondition
   */
  public function parent() {
    if (isset($this->_current->_parent)) {
      $parent = $this->_current->_parent;
      unset($this->_current->_parent);
      $this->_current = $parent;
    }
    
    return $this;
  }
  
  /**
   * 增加过滤条件
   * 
   * @param QueryCondition $where
   * 
   * @return QueryCondition
   */
  final public function add(QueryCondition $condition = NULL) {
    if (isset($condition)) {
      $condition->end();
      unset($condition->query, $condition->_current);
      $this->_current->condition(NULL, $condition, NULL);
    }
    else {
      $condition = new QueryCondition($this->parameter);
      unset($condition->_current);
      $condition->_parent = $this->_current;
      $this->_current->condition(NULL, $condition, NULL);
      
      $this->_current = $condition;
    }
    
    return $this;
  }
  
  /**
   * 追加过滤条件
   * 
   * @return QueryCondition
   */
  final public function append() {
    $condition = new QueryCondition($this->parameter);
    unset($condition->_current);
    if (isset($this->_current->_parent)) {
      $condition->_parent = $this->_current->_parent;
      $this->_current->_parent->condition(NULL, $condition, NULL);
      unset($this->_current->_parent);
    }
    else {
      $this->condition(NULL, $condition, NULL);
    }
    $this->_current = $condition;
    
    return $this;
  }
  
  /**
   * 添加查询条件
   * 
   * @param string $field
   * @param mixed $value
   * @param int $operator
   */
  final protected function condition($field = NULL, $value = NULL, $operator = NULL) {
    if ($this->_count > 0) {
      $this->conditions[] = $this->conjunction;
    }
    
    $this->conditions[] = array(
        'field' => $field,
        'value' => $value,
        'operator' => $operator,
    );
    $this->_count++;
  }
  
  /**
   * 逻辑或链接符
   * 
   * @return QueryCondition
   */
  public function _AND() {
    $this->_current->conjunction = self::_AND_;
    
    return $this;
  }
  
  /**
   * 逻辑或链接符
   * 
   * @return QueryCondition
   */
  public function _OR() {
    $this->_current->conjunction = self::_OR_;
    
    return $this;
  }
  
  /**
   * 增加一个比较条件
   *
   * @param string $field
   * @param mixed $value
   * @param string $operator
   *   比较运算符, 支持 =, !=, >, <, >=, <= 操作符
   *   QueryCondition::EQUAL
   *   QueryCondition::NOT_EQUAL
   *   QueryCondition::GREATER
   *   QueryCondition::GREATER_EQUAL
   *   QueryCondition::LESS
   *   QueryCondition::LESS_EQUAL
   *
   * @return QueryCondition
   */
  public function compare($field, $value, $operator = self::EQUAL) {
    $value = $this->parameter->add($field, $value);
    
    $this->_current->condition($field, $value, $operator);
    
    return $this;
  }
  
  /**
   * 增加一个检查空值的条件
   *
   * @param string $field 字段名称
   * @param boolean $flag
   *   TRUE检查字段值是否为空，FALSE检查字段值是否不为空。
   *
   * @return QueryCondition
   */
  public function isNull($field, $flag = TRUE) {
    $this->_current->condition($field, NULL, ($flag ? self::IS_NULL : self::IS_NOT_NULL));
    
    return $this;
  }

  /**
   * 增加一个包含条件
   *
   * @param string $field 字段名称
   * @param mixed $value 包含内容，可以包含一个数组或是一个字符串
   * @param boolean $flag
   *   如果设置为TRUE则是增加一个包含指定内容的条件，设置为FALSE则是增加一个不包含指定内容的条件。
   *
   * @return QueryCondition
   */
  public function contain($field, $value, $flag = TRUE) {
    if (is_string($value)) {
      $value = $this->parameter->add($field, $value);
      $this->_current->condition($field, $value, ($flag ? self::LIKE : self::NOT_LIKE));
    }
    else {
      if ($value instanceof SelectQuery) {
        $value->end();
      }
      else {
        $value = is_array($value) ? $value : array($value);
        $value = $this->parameter->add($field, $value);
      }
      
      $this->_current->condition($field, $value, ($flag ? self::IN : self::NOT_IN));
    }
    
    return $this;
  }
  
  /**
   * 增加一个介于条件
   * 
   * @param string $field 字段名称
   * @param array $between 介于范围
   *   介于值可以是字符和数字，数组的第一个值为开始，第二个值为结束，表示介于开始和结束之间的值有效。
   * @param boolean $flag
   *   如果设置为TRUE则是增加一个介于指定范围的条件，设置为FALSE则是增加一个不介于指定范围的条件。
   * 
   * @return QueryCondition
   */
  public function between($field, array $between, $flag = TRUE) {
    foreach ($between as $key => $value) {
      $between[$key] = $this->parameter->add($field, $value);
    }
    $this->_current->condition($field, $between, ($flag ? self::BETWEEN : self::NOT_BETWEEN));
    
    return $this;
  }
  
  /**
   * 增加一个存在测试查询条件
   *
   * @param string $field
   * @param SelectQuery $query
   * @param boolean $flag
   *
   * @return QueryCondition
   */
  public function exists($field,  SelectQuery $query, $flag = TRUE) {
    $query->end();
    $this->_current->condition($field, $query, ($flag ? self::EXISTS : self::NOT_EXISTS));
    
    return $this;
  }

  /**
   * 增加一个条件代码块
   *
   * @param string $snippet
   * @param array $arguments
   *
   * @return QueryCondition
   */
  public function where($snippet, array $arguments = array()) {
    $this->_current->condition($snippet, $arguments, self::WHERE);
    foreach ($arguments as $field => $value) {
      $this->parameter[$field] = $value;
    }

    return $this;
  }

  /**
   * (non-PHPdoc)
   * @see IteratorAggregate::getIterator()
   */
  public function getIterator() {
    // TODO Auto-generated method stub
    return new ArrayIterator($this->conditions);
  }
  
  /**
   * (non-PHPdoc)
   * @see Countable::count()
   */
  public function count() {
    // TODO Auto-generated method stub
    return $this->_count;
  }
  
}

/**
 * 插入查询分析器
 * 
 * @author feng
 *
 */
class InsertQuery extends Query {
  
  /**
   * 数据字段
   * 
   * @var array
   */
  protected $fields = array();
  
  /**
   * 数据值
   * 
   * @var array
   */
  protected $values = array();
  
  /**
   * 默认数据
   * 
   * @var array
   */
  protected $defaults = array();
  
  /**
   * @var SelectQuery
   */
  protected $queryFrom;

  public function __toString() {
    return 'insert';
  }
  
  /**
   * (non-PHPdoc)
   * @see Query::masktype()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::INSERT;
  }
  
  /**
   * 设置插入查询字段
   * 
   * @param array $fields
   * 
   * @example
   *   $fields[] = 'field0';
   *   $fields[] = 'field1';
   *   $fields[] = 'field2';
   * 
   * @return InsertQuery
   */
  public function fields(array $fields) {
    foreach ($fields as $field) {
      $this->fields[$field] = $field;
    }
    
    return $this;
  }
  
  /**
   * 设置插入数据
   * 
   * @param array $values
   * 
   * @example
   *   $values[] = 'value0';
   *   $values[] = 'value1';
   *   $values[] = 'value2';
   *   
   * @example
   *   $values['field0'] = 'value0';
   *   $values['field1'] = 'value1';
   *   $values['field2'] = 'value2';
   * 
   * @return InsertQuery
   */
  public function values(array $values) {
    // 检查值是否与字段配对，如果缺少应该使用默认设置补全。
    // 重新排序值与字段顺序匹配
    // 如果fields中没有相应字段，则自动将values值数组中的字段填充到fields中
    $insert_values = array();
    if (is_numeric(key($values))) {
      foreach ($this->fields as $field) {
        if (list($key, $value) = each($values)) {
          $insert_values[$field] = $this->parameter->add($field, $value);
        }
        else {
          $value = isset($this->defaults[$field]) ? $this->defaults[$field] : NULL;
          $insert_values[$field] = $this->parameter->add($field, $value);
        }
      }
      $this->values[] = $insert_values;
    }
    else {
      if (empty($this->fields)) {
        $this->fields(array_keys($values));
      }
      
      foreach ($this->fields as $field) {
        $value = isset($values[$field]) ? $values[$field] : $this->defaults[$field];
        $insert_values[$field] = $this->parameter->add($field, $value);
      }
      
      $this->values[] = $insert_values;
    }
    
    return $this;
  }
  
  
  /**
   * 使用默认字段值
   *
   * @param array $defaultValues
   */
  public function useDefaults(array $defaultValues) {
    foreach ($defaultValues as $field => $value) {
      if (!isset($this->fields[$field])) {
        $this->fields[$field] = $field;
      }
    }
    
    foreach ($this->values as $key => $values) {
      foreach ($defaultValues as $field => $value) {
        if (!isset($values[$field]))
          $this->values[$key][$field] = $this->parameter->add($field, $value);
      }
    }
    
    $this->defaults = $defaultValues;
  }
  
  /**
   * 设置插入查询分析器的数据
   * 
   * @param SelectQuery $query
   * 
   * @return Query
   */
  public function from(SelectQuery $query) {
    $this->queryFrom = $query;
    
    return $this;
  }
  
  /**
   * 获取插入字段
   * 
   * @return array
   */
  final public function &getFields() {
    // 返回标准field=>masktype字段数组
    return $this->fields;
  }
  
  /**
   * 获取插入值
   *
   * @return array
   */
  final public function &getValues() {
    // 返回标准field=>value值数组
    return $this->values;
  }
  
  /**
   * 获取查询数据分析器
   * 
   * @return SelectQuery
   */
  final public function select() {
    return $this->queryFrom;
  }

  /**
   * (non-PHPdoc)
   * @see Query::end()
   */
  public function end() {
    // TODO Auto-generated method stub
    $this->end = TRUE;
    
    return $this;
  }
}

class ReplaceQuery extends InsertQuery {
  
  public function __toString() {
    return 'replace';
  }
  
}

/**
 * 更新查询分析器
 * 
 * @author feng
 *
 */
class UpdateQuery extends Query {
  
  /**
   * 更新这些字段数据，数据可以是一个值，也可以是一个表达式。
   * @example
   *   键值配对数据: $fields[$field] = $value;
   *   表达式数据: $fields[$field] = array('expression', $arg0, $arg1, $arg2);
   * 
   * @var array
   */
  protected $fields = array();
  
  /**
   * 更新这些字段数据所使用的表达式参数。
   * 
   * @var array
   */
  protected $arguments = array();
  
  /**
   * 更新过滤条件
   * 
   * @var QueryCondition
   */
  protected $condition;
  
  /**
   * 构造一个更新查询
   * 
   * @param string $table
   * @param QueryParameter $parameter
   */
  public function __construct($table, QueryParameter $parameter) {
    parent::__construct($table, $parameter);
    
    $this->condition = new QueryCondition($this->parameter, $this);
  }
  
  public function __toString() {
    return 'update';
  }
  
  /**
   * (non-PHPdoc)
   * @see Query::masktype()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::UPDATE;
  }
  
  /**
   * 增加一个字段数据
   *
   * @param string $field 字段名称
   * @param mixed $value 存储值
   *
   * @example
   *   键值配对数据: $value = 'stand_value';
   *   
   * @return UpdateQuery
   */
  public function field($field, $value) {
    $this->fields[$field] = $field;
    $this->parameter[$field] = $value;
    
    return $this;
  }
  
  /**
   * 增加一组字段数据
   * 
   * @param array $fields
   * 
   * @example
   *   键值配对数据: $fields[$field] = $value;
   *   
   * @return UpdateQuery
   */
  public function fields(array $fields) {
    foreach ($fields as $field => $value) {
      $this->fields[$field] = $field;
      $this->parameter[$field] = $value;
    }
    
    return $this;
  }
  
  /**
   * 增加一个表达式数据
   * 
   * @param string $field
   * @param string $expression
   * @param array $arguments
   * 
   * @example
   *   表达式数据: $fields[$field] = array('expression' => $expression, $arg0, $arg1, $arg2);
   * 
   * @return UpdateQuery
   */
  public function expression($field, $expression, array $arguments = array()) {
    $this->fields[$field] = '('. $expression .')';
    $this->arguments[$field] = $arguments;
    foreach ($arguments as $field => $value) {
      $this->parameter[$field] = $value;
    }
    
    return $this;
  }

  /**
   * 更新查询条件
   * 
   * @return QueryCondition
   */
  public function where() {
    return $this->condition;
  }
  
  /**
   * 获取更新字段
   *
   * @return array
   */
  final public function &getFields() {
    // 返回标准field=>value字段数组
    return $this->fields;
  }
  
  /**
   * 获取表达式参数
   * 
   * @return array
   */
  final public function &getArguments() {
    return $this->arguments;
  }

  /**
   * (non-PHPdoc)
   * @see Query::end()
   */
  public function end() {
    // TODO Auto-generated method stub
    if (!$this->end) {
      $this->condition->end();
      
      $this->end = TRUE;
    }
    
    return $this;
  }
  
}

/**
 * 删除查询分析器
 * 
 * @author feng
 *
 */
class DeleteQuery extends Query {
  
  /**
   * @var QueryCondition
   */
  protected $condition;
  
  /**
   * 构造一个插入查询
   * 
   * @param string $table
   * @param QueryParameter $parameter
   */
  public function __construct($table, QueryParameter $parameter) {
    parent::__construct($table, $parameter);
    
    $this->condition = new QueryCondition($this->parameter, $this);
  }
  
  public function __toString() {
    return 'delete';
  }
  
  /**
   * (non-PHPdoc)
   * @see Query::masktype()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::DELETE;
  }

/**
   * 删除查询条件
   * 
   * @return QueryCondition
   */
  public function where() {
    return $this->condition;
  }

  /**
   * (non-PHPdoc)
   * @see Query::end()
   */
  public function end() {
    // TODO Auto-generated method stub
    if (!$this->end) {
      $this->condition->end();
      
      $this->end = TRUE;
    }
    
    return $this;
  }

}

/**
 * 关系数据筛选查询接口
 * 
 * @author feng
 *
 */
interface MultiSelectQueryInterface {
  
  /**
   * 获取数据表别名
   *
   * @return string
   */
  public function getAlias();
  
  /**
   * 
   * 
   * @param string $table_alias
   * @param string $field
   * @param string $alias
   * 
   * @return MultiSelectQueryInterface
   */
  public function addField($table_alias, $field, $alias = NULL);
  
  /**
   * 
   * 
   * @param string $table_alias
   * @param array $fields
   * 
   * @return MultiSelectQueryInterface
   */
  public function addFields($table_alias, array $fields);
  
  /**
   * 
   * 
   * @param string $masktype
   * @param string $table
   * @param string $alias
   * @param string $where
   * @param array $arguments
   * 
   * @return MultiSelectQueryInterface
   */
  public function join($type, $table, $alias, $condition, array $arguments = array());
  
  /**
   * 
   * @return array
   */
  public function &getJoins();
  
  /**
   * 
   * 
   * @param string $table
   * @param string $alias
   * @param string $where
   * @param array $arguments
   * 
   * @return MultiSelectQueryInterface
   */
  public function innerJoin($table, $alias, $condition, array $arguments = array());
  
  /**
   * 
   * 
   * @param string $table
   * @param string $alias
   * @param string $where
   * @param array $arguments
   * 
   * @return MultiSelectQueryInterface
   */
  public function leftJoin($table, $alias, $condition, array $arguments = array());
  
  /**
   * 
   * 
   * @param string $table
   * @param string $alias
   * @param string $where
   * @param array $arguments
   * 
   * @return MultiSelectQueryInterface
   */
  public function rightJoin($table, $alias, $condition, array $arguments = array());
  
  /**
   * 
   * 
   * @param string $table
   * @param string $alias
   * @param string $where
   * @param array $arguments
   * 
   * @return MultiSelectQueryInterface
   */
  public function fullJoin($table, $alias, $condition, array $arguments = array());
  
  /**
   * 
   * 
   * @param SelectQuery $query
   * 
   * @return MultiSelectQueryInterface
   */
  public function union(SelectQuery $query);
  
  /**
   * 
   * 
   * @param SelectQuery $query
   * 
   * @return MultiSelectQueryInterface
   */
  public function unionAll(SelectQuery $query);
  
  /**
   * 
   * 
   * @return array
   */
  public function &getUnions();
}

/**
 * 筛选查询分析器
 * 
 * @author feng
 *
 */
class SelectQuery extends Query {
  /**
   * 升序排序
   */
  const ASC = 0;
  
  /**
   * 降序排序
   */
  const DESC = 1;
  
  /**
   * 随机排序
   */
  const RANDOM = 2;
  
  /**
   * 查询这些字段数据，数据可以是一个值，也可以是一个表达式。
   * @example
   *   键值配对数据: $fields[$field] = $value;
   *   表达式数据: $fields[$field] = array('expression', $arg0, $arg1, $arg2);
   * 
   * @var array
   */
  protected $fields = array();
  
  /**
   * 更新这些字段数据所使用的表达式参数。
   * 
   * @var array
   */
  protected $arguments = array();
  
  /**
   * 查询过滤条件
   *
   * @var QueryCondition
   */
  protected $condition;

  /**
   * @var array
   */
  protected $groups = array();

  /**
   * @var QueryCondition
   */
  protected $having;

  /**
   * @var array
   */
  protected $orders = array();
  
  /**
   * @var array
   */
  protected $limit = array();
  
  /**
   * 检索唯一数据
   *
   * @var boolean
   */
  protected $distinct = FALSE;
  
  /**
   * 新更新数据
   * 
   * @var boolean
   */
  protected $forUpdate = FALSE;
  
  /**
   * 构造一个筛选查询
   * 
   * @param string $table 数据表
   * @param QueryParameter $parameter
   */
  public function __construct($table, QueryParameter $parameter) {
    parent::__construct($table, $parameter);
    
    $this->condition = new QueryCondition($this->parameter, $this);
    $this->having = new QueryCondition($this->parameter, $this);
  }
  
  public function __toString() {
    return 'select';
  }
  
  /**
   * (non-PHPdoc)
   * @see Query::masktype()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::SELECT;
  }
  
  
  /**
   * 添加一个查询字段
   * 
   * @param string $field 字段
   * @param string $alias 别名
   * 
   * @return SelectQuery
   */
  public function field($field, $alias = NULL) {
    if (empty($alias)) {
      $this->fields[$field] = $field;
    }
    else {
      $this->fields[$alias] = $field;
    }
    
    return $this;
  }
  
  /**
   * 添加一组查询字段
   * 
   * @param array $fields 以field => alias组成的字段数组
   * 
   * @return SelectQuery
   */
  public function fields(array $fields) {
    foreach ($fields as $field => $alias) {
      if (is_numeric($field)) {
        $this->fields[$alias] = $alias;
      }
      else {
        $this->fields[$alias] = $field;
      }
    }
    
    return $this;
  }
  
  /**
   * 添加一个表达式查询字段
   * 
   * @param string $expression 表达式
   * @param string $alias 别名
   * @param array $arguments 表达式参数
   * 
   * @return SelectQuery
   */
  public function expression($expression, $alias, array $arguments = array()) {
    // 表达式字符默认给第一个字符加上左括号(，最后一个字符加上右括号)，其字符默认存放在fields中。
    $this->fields[$alias] = '('. $expression .')';
    $this->arguments[$alias] = $arguments;
    foreach ($arguments as $field => $value) {
      $this->parameter[$field] = $value;
    }
    
    return $this;
  }
  
  /**
   * 获取表达式参数
   * 
   * @return array
   */
  final public function &getArguments() {
    return $this->arguments;
  }
  
  /**
   * 获取查询字段数组
   * 
   * @return array
   */
  final public function &getFields() {
    return $this->fields;
  }
  
  /**
   * (non-PHPdoc)
   * @see Query::end()
   */
  public function end() {
    // TODO Auto-generated method stub
    if (!$this->end) {
      $this->condition->end();
      $this->having->end();
      
      $this->end = TRUE;
    }
    
    return $this;
  }
  
  /**
   * 筛选查询条件
   * 
   * @return QueryCondition
   */
  public function where() {
    return $this->condition;;
  }
  
  /**
   * 分组查询
   * 
   * @param string $field
   * 
   * @return SelectQuery
   */
  public function groupBy($field) {
    $this->groups[$field] = $field;
    
    return $this;
  }
  
  /**
   * 获取分组字段
   * 
   * @return array
   */
  final public function &getGroups() {
    return $this->groups;
  }
  
  /**
   * Group分组筛选条件
   * 
   * @return QueryCondition
   */
  public function having() {
    return $this->having;
  }

  /**
   * 增加一个排序字段
   *
   * @param string $field
   * @param string $sort
   *   SelectQuery::ASC
   *   SelectQuery::DESC
   *   SelectQuery::RANDOM
   *
   * @return QueryOrder
   */
  function orderBy($field, $direction = self::ASC) {
    $this->orders[$field] = $direction;

    return $this;
  }
  
  /**
   * 获取排序字段
   * 
   * @return array
   */
  final public function &getOrders() {
    return $this->orders;
  }
  
  /**
   * 限制查询结果数量
   * 
   * @param int $offset
   * @param int $row_count
   * 
   * @return SelectQuery
   */
  final public function limit($offset = 0, $row_count = 30) {
    $this->limit = array($offset, $row_count);
    
    return $this;
  }
  
  /**
   * 获取查询结果数量限量
   * 
   * @return array
   */
  final public function &getLimit() {
    return $this->limit;
  }
  
  /**
   * 设置唯一数据标识
   * 
   * @param boolean $distinct
   * 
   * @return SelectQuery
   */
  final public function distinct($distinct = TRUE) {
    $this->distinct = $distinct;
    
    return $this;
  }
  
  /**
   * 获取唯一数据标识
   * 
   * @return boolean
   */
  final public function getDistinct() {
    return $this->distinct;
  }
  
  /**
   * 设置获取新更新数据
   * 
   * @param boolean $update
   * 
   * @return SelectQuery
   */
  final public function forUpdate($update = TRUE) {
    $this->forUpdate = $update;
    
    return $this;
  }
  
  /**
   * 获取新更新数据标识
   * 
   * @return boolean
   */
  final public function getUpdate() {
    return $this->forUpdate;
  }
}


class MultiSelectQuery extends SelectQuery implements MultiSelectQueryInterface {
  
  /**
   * 数据表别名
   *
   * @var string
   */
  protected $table_alias;
  
  /**
   * 
   * @var array
   */
  protected $joins = array();
  
  /**
   *
   * @var array
   */
  protected $unions = array();
  
  /**
   * 
   * @param string $table
   * @param string $alias
   * @param QueryParameter $parameter
   */
  public function __construct($table, $alias, QueryParameter $parameter) {
    // TODO Auto-generated method stub
    parent::__construct($table, $parameter);
    
    $this->table_alias = $alias;
  }
  
  public function __toString() {
    return 'multiselect';
  }
  
  /**
   * (non-PHPdoc)
   * @see Query::masktype()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::MULTISELECT;
  }
  
  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::getAlias()
   */
  final public function getAlias() {
    return $this->table_alias;
  }

  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::addField()
   */
  public function addField($table_alias, $field, $alias = NULL) {
    // TODO Auto-generated method stub
    if ($table_alias == $this->table_alias) {
      parent::field($field, $alias);
    }
    else if (isset($this->joins[$table_alias])) {
      $join_fields = &$this->joins[$table_alias]['fields'];
      
      if (empty($alias)) {
        $join_fields[$field] = $field;
      }
      else {
        $join_fields[$alias] = $field;
      }
    }
    
    return $this;
  }
  
  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::addFields()
   */
  public function addFields($table_alias, array $fields) {
    // TODO Auto-generated method stub
    if ($table_alias == $this->table_alias) {
      parent::fields($fields);
    }
    else if (isset($this->joins[$table_alias])) {
      $join_fields = &$this->joins[$table_alias]['fields'];
      
      foreach ($fields as $field => $alias) {
        if (is_numeric($field)) {
          $join_fields[$alias] = $alias;
        }
        else {
          $join_fields[$alias] = $field;
        }
      }
    }
    
    return $this;
  }

/**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::join()
   */
  public function join($type, $table, $alias, $condition, array $arguments = array()) {
    // TODO Auto-generated method stub
    if ($table instanceof SelectQuery) {
      $table->end();
    }
    
    $this->joins[$alias] = array(
        'masktype' => $type,
        'table' => $table,
        'alias' => $alias,
        'fields' => array(),
        'where' => $condition,
        'arguments' => $arguments
      );
    foreach ($arguments as $field => $value) {
      $this->parameter[$field] = $value;
    }
  }

  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::getJoins()
   */
  public function &getJoins() {
    // TODO Auto-generated method stub
    return $this->joins;
  }

  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::fullJoin()
   */
  public function fullJoin($table, $alias, $condition, array $arguments = array()) {
    // TODO Auto-generated method stub
    $this->join('FULL', $table, $alias, $condition, $arguments);
    
    return $this;
  }

  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::innerJoin()
   */
  public function innerJoin($table, $alias, $condition, array $arguments = array()) {
    // TODO Auto-generated method stub
    $this->join('INNER', $table, $alias, $condition, $arguments);
    
    return $this;
  }

  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::leftJoin()
   */
  public function leftJoin($table, $alias, $condition, array $arguments = array()) {
    // TODO Auto-generated method stub
    $this->join('LEFT', $table, $alias, $condition, $arguments);
    
    return $this;
  }

  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::rightJoin()
   */
  public function rightJoin($table, $alias, $condition, array $arguments = array()) {
    // TODO Auto-generated method stub
    $this->join('RIGHT', $table, $alias, $condition, $arguments);
    
    return $this;
  }

  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::union()
   */
  public function union(SelectQuery $query) {
    // TODO Auto-generated method stub
    $query->end();
    $this->unions[] = array(
        'query' => $query,
        'masktype' => 'DISTINCT'
        );
    
    return $this;
  }

  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::unionAll()
   */
  public function unionAll(SelectQuery $query) {
    // TODO Auto-generated method stub
    $query->end();
    $this->unions[] = array(
        'query' => $query,
        'masktype' => 'ALL'
        );
    
    return $this;
  }

  /**
   * (non-PHPdoc)
   * @see MultiSelectQueryInterface::getUnions()
   */
  public function &getUnions() {
    // TODO Auto-generated method stub
    return $this->unions;
  }
  
}


