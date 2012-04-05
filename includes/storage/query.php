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
   * 临时查询
   */
  const TEMPORARY = 0x010;

  /**
   * 联合查询
   */
  const MULTISELECT = 0x020;

  /**
   * 唯一插入查询
   */
  const UNIQUEINSERT = 0x040;

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
   * 构造一个查询分类器
   *
   * @param string $table
   */
  public function __construct($table) {
    $this->table = $table;
  }

  public function __toString() {
    return $this->name();
  }

  /**
   * 查询器类型
   */
  abstract public function type();

  /**
   * 查询器名称
   */
  abstract public function name();

  /**
   * 获取数据表名
   *
   * @return string
   */
  final public function getTable() {
    return $this->table;
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
   *
   * @see ArrayAccess::offsetExists()
   */
  public function offsetExists($offset) {
    // TODO Auto-generated method stub
    return isset($this->_container[$offset]);
  }

  /**
   *
   * @see ArrayAccess::offsetGet()
   */
  public function offsetGet($offset) {
    // TODO Auto-generated method stub
    return $this->_container[$offset];
  }

  /**
   *
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
   *
   * @see ArrayAccess::offsetUnset()
   */
  public function offsetUnset($offset) {
    // TODO Auto-generated method stub
    unset($this->_container[$offset]);
  }

  /**
   *
   * @see Iterator::current()
   */
  public function current() {
    // TODO Auto-generated method stub
    return current($this->_container);
  }

  /**
   *
   * @see Iterator::unique()
   */
  public function key() {
    // TODO Auto-generated method stub
    return key($this->_container);
  }

  /**
   *
   * @see Iterator::next()
   */
  public function next() {
    // TODO Auto-generated method stub
    return next($this->_container);
  }

  /**
   *
   * @see Iterator::rewind()
   */
  public function rewind() {
    // TODO Auto-generated method stub
    return reset($this->_container);
  }

  /**
   *
   * @see Iterator::valid()
   */
  public function valid() {
    // TODO Auto-generated method stub
    return key($this->_container) !== NULL;
  }

  /**
   *
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
  public function __construct() {
    $this->conjunction = self::_AND_;
  }

  /**
   * 增加过滤条件
   *
   * @param QueryCondition $where
   *
   * @return QueryCondition
   */
  final public function add(QueryCondition $condition) {
    $this->condition(NULL, $condition, NULL);

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
    $this->conjunction = self::_AND_;

    return $this;
  }

  /**
   * 逻辑或链接符
   *
   * @return QueryCondition
   */
  public function _OR() {
    $this->conjunction = self::_OR_;

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
    $this->condition($field, $value, $operator);

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
    $this->condition($field, NULL, ($flag ? self::IS_NULL : self::IS_NOT_NULL));

    return $this;
  }

  /**
   * 增加一个包含条件
   *
   * @param string $field 字段名称
   * @param mixed $value 包含内容，
   *   可以包含一个数组或是一个字符串，字符串允许使用%和_通配字符。
   * @param boolean $flag
   *   如果设置为TRUE则是增加一个包含指定内容的条件，设置为FALSE则是增加一个不包含指定内容的条件。
   *
   * @return QueryCondition
   */
  public function contain($field, $value, $flag = TRUE) {
    if (is_string($value)) {
      $this->condition($field, $value, ($flag ? self::LIKE : self::NOT_LIKE));
    }
    else {
      if ($value instanceof SelectQuery) {
        $this->condition($field, $value, ($flag ? self::IN : self::NOT_IN));
      }
      else {
        $value = is_array($value) ? array_flatten($value) : array($value);
        $this->condition($field, array($value), ($flag ? self::IN : self::NOT_IN));
      }
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
    $this->condition($field, $between, ($flag ? self::BETWEEN : self::NOT_BETWEEN));

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
    $this->condition($field, $query, ($flag ? self::EXISTS : self::NOT_EXISTS));

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

    return $this;
  }

  /**
   *
   * @see IteratorAggregate::getIterator()
   */
  public function getIterator() {
    // TODO Auto-generated method stub
    return new ArrayIterator($this->conditions);
  }

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

  /**
   *
   * @see Query::masktype()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::INSERT;
  }

  /**
   *
   * @see Query::name()
   */
  public function name() {
    return 'insert';
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
          $insert_values[$field] = $value;
        }
        else {
          $value = isset($this->defaults[$field]) ? $this->defaults[$field] : NULL;
          $insert_values[$field] = $value;
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
        $insert_values[$field] = $value;
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
          $this->values[$key][$field] = $value;
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
}

/**
 * 唯一插入查询
 *
 * @author realeff
 *
 */
class UniqueInsertQuery extends Query {

  /**
   * 唯一关键字数据
   *
   * @var array
   */
  protected $keys = array();

  /**
   * 插入字段数据
   *
   * @var array
   */
  protected $insertFields = array();

  /**
   * 更新字段数据
   *
   * @var array
   */
  protected $updateFields = array();

  /**
   * 更新这些字段数据所使用的表达式参数。
   *
   * @var array
   */
  protected $arguments = array();


  /**
   *
   * @see InsertQuery::type()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::UNIQUEINSERT;
  }

  /**
   *
   * @see Query::name()
   */
  public function name() {
    return 'uniqueinsert';
  }

  /**
   * 数据主键或唯一键值
   *
   * @param string $field
   * @param mixed $value
   *
   * @return UniqueInsertQuery
   */
  public function key($field, $value) {
    $this->keys[$field] = $value;

    return $this;
  }

  /**
   * 数据主键或唯一键值集
   *
   * @param array $fields
   * @param array $values
   *
   * @return UniqueInsertQuery
   */
  public function keys(array $fields, array $values = array()) {
    if ($values) {
      $fields = array_combine($fields, $values);
    }

    foreach ($fields as $field => $value) {
      $this->keys[$field] = $value;
    }

    return $this;
  }

  /**
   * 获取唯一字段数据
   *
   * @return array
   */
  public function &getKeys() {
    return $this->keys;
  }

  /**
   * 增加一个字段数据
   *
   * @param string $field
   * @param mixed $value
   *
   * @return UniqueInsertQuery
   */
  public function field($field, $value) {
    $this->insertFields[$field] = $value;
    $this->updateFields[$field] = $value;

    return $this;
  }

  /**
   * 增加一组字段数据
   *
   * @param array $fields
   * @param array $values
   *
   * @return UniqueInsertQuery
   */
  public function fields(array $fields, array $values = array()) {
    if ($values) {
      $fields = array_combine($fields, $values);
    }

    foreach ($fields as $field => $value) {
      $this->insertFields[$field] = $value;
      $this->updateFields[$field] = $value;
    }

    return $this;
  }

  /**
   * 增加一个插入字段数据
   *
   * @param string $field
   * @param mixed $value
   *
   * @return UniqueInsertQuery
   */
  public function insertField($field, $value) {
    $this->insertFields[$field] = $value;

    return $this;
  }

  /**
   * 增加一组插入字段数据
   *
   * @param array $fields
   * @param array $values
   *
   * @return UniqueInsertQuery
   */
  public function insertFields(array $fields, array $values = array()) {
    if ($values) {
      $fields = array_combine($fields, $values);
    }

    foreach ($fields as $field => $value) {
      $this->insertFields[$field] = $value;
    }

    return $this;
  }

  /**
   * 获取插入字段数据
   *
   * @return array
   */
  public function &getInsertFields() {
    return $this->insertFields;
  }

  /**
   * 使用默认字段值
   *
   * @param array $defaultValues
   */
  public function useDefaults(array $defaultValues) {
    foreach ($defaultValues as $field => $value) {
      if (!isset($this->insertFields[$field])) {
        $this->insertFields[$field] = $value;
      }
    }
  }

  /**
   * 增加一个更新字段数据
   *
   * @param string $field
   * @param mixed $value
   *
   * @return UniqueInsertQuery
   */
  public function updateField($field, $value) {
    $this->updateFields[$field] = $value;

    return $this;
  }

  /**
   * 增加一组更新字段数据
   *
   * @param array $fields
   * @param array $values
   *
   * @return UniqueInsertQuery
   */
  public function updateFields(array $fields, array $values = array()) {
    if ($values) {
      $fields = array_combine($fields, $values);
    }

    foreach ($fields as $field => $value) {
      $this->updateFields[$field] = $value;
    }

    return $this;
  }

  /**
   * 获取更新字段数据
   *
   * @return array
   */
  public function &getUpdateFields() {
    return $this->updateFields;
  }

  /**
   * 增加一个更新表达式数据
   *
   * @param string $field
   * @param string $expression
   * @param array $arguments
   *
   * @example
   *   表达式数据: $fields[$field] = array('expression' => $expression, $arg0, $arg1, $arg2);
   *
   * @return UniqueInsertQuery
   */
  public function updateExpression($field, $expression, array $arguments = array()) {
    $this->updateFields[$field] = '('. $expression .')';
    $this->arguments[$field] = $arguments;

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
   */
  public function __construct($table) {
    parent::__construct($table);

    $this->condition = new QueryCondition();
  }

  /**
   *
   * @see Query::masktype()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::UPDATE;
  }

  /**
   *
   * @see Query::name()
   */
  public function name() {
    return 'update';
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
    $this->fields[$field] = $value;

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
  public function fields(array $fields, array $values = array()) {
    if ($values) {
      $fields = array_combine($fields, $values);
    }

    foreach ($fields as $field => $value) {
      $this->fields[$field] = $value;
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
   */
  public function __construct($table) {
    parent::__construct($table);

    $this->condition = new QueryCondition();
  }

  /**
   *
   * @see Query::masktype()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::DELETE;
  }

  /**
   *
   * @see Query::name()
   */
  public function name() {
    return 'delete';
  }

/**
   * 删除查询条件
   *
   * @return QueryCondition
   */
  public function where() {
    return $this->condition;
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
   * 特殊查询标识
   *
   * @var array
   */
  protected $flags = array();

  /**
   * 构造一个筛选查询
   *
   * @param string $table 数据表
   */
  public function __construct($table) {
    parent::__construct($table);

    $this->condition = new QueryCondition();
    $this->having = new QueryCondition();
  }

  /**
   *
   * @see Query::masktype()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::SELECT;
  }

  /**
   *
   * @see Query::name()
   */
  public function name() {
    return 'select';
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
    $this->flags['distinct'] = $distinct;

    return $this;
  }

  /**
   * 设置获取新更新数据
   *
   * @param boolean $flag
   *
   * @return SelectQuery
   */
  final public function forUpdate($flag = TRUE) {
    $this->flags['update'] = $flag;

    return $this;
  }

  /**
   * 设置查询计数
   *
   * @param boolean $flag
   *
   * @return SelectQuery
   */
  final public function count($flag = TRUE) {
    $this->flags['count'] = $flag;

    return $this;
  }

  /**
   * 获取特殊查询标识
   *
   * @param string $name 标识名称
   *
   * @return mixed
   */
  final public function getFlags($name = NULL) {
    if (isset($name)) {
      return isset($this->flags[$name]) ? $this->flags[$name] : FALSE;
    }

    return $this->flags;
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
   */
  public function __construct($table, $alias) {
    // TODO Auto-generated method stub
    parent::__construct($table);

    $this->table_alias = $alias;
  }

  /**
   *
   * @see Query::masktype()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::MULTISELECT;
  }

  /**
   *
   * @see Query::name()
   */
  public function name() {
    return 'multiselect';
  }

  /**
   *
   * @see MultiSelectQueryInterface::getAlias()
   */
  final public function getAlias() {
    return $this->table_alias;
  }

  /**
   *
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
   *
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
   *
   * @see MultiSelectQueryInterface::join()
   */
  public function join($type, $table, $alias, $condition, array $arguments = array()) {
    // TODO Auto-generated method stub
    $this->joins[$alias] = array(
        'masktype' => $type,
        'table' => $table,
        'alias' => $alias,
        'fields' => array(),
        'where' => $condition,
        'arguments' => $arguments
      );
  }

  /**
   *
   * @see MultiSelectQueryInterface::getJoins()
   */
  public function &getJoins() {
    // TODO Auto-generated method stub
    return $this->joins;
  }

  /**
   *
   * @see MultiSelectQueryInterface::fullJoin()
   */
  public function fullJoin($table, $alias, $condition, array $arguments = array()) {
    // TODO Auto-generated method stub
    $this->join('FULL', $table, $alias, $condition, $arguments);

    return $this;
  }

  /**
   *
   * @see MultiSelectQueryInterface::innerJoin()
   */
  public function innerJoin($table, $alias, $condition, array $arguments = array()) {
    // TODO Auto-generated method stub
    $this->join('INNER', $table, $alias, $condition, $arguments);

    return $this;
  }

  /**
   *
   * @see MultiSelectQueryInterface::leftJoin()
   */
  public function leftJoin($table, $alias, $condition, array $arguments = array()) {
    // TODO Auto-generated method stub
    $this->join('LEFT', $table, $alias, $condition, $arguments);

    return $this;
  }

  /**
   *
   * @see MultiSelectQueryInterface::rightJoin()
   */
  public function rightJoin($table, $alias, $condition, array $arguments = array()) {
    // TODO Auto-generated method stub
    $this->join('RIGHT', $table, $alias, $condition, $arguments);

    return $this;
  }

  /**
   *
   * @see MultiSelectQueryInterface::union()
   */
  public function union(SelectQuery $query) {
    // TODO Auto-generated method stub
    $this->unions[] = array(
        'query' => $query,
        'masktype' => 'DISTINCT'
        );

    return $this;
  }

  /**
   *
   * @see MultiSelectQueryInterface::unionAll()
   */
  public function unionAll(SelectQuery $query) {
    // TODO Auto-generated method stub
    $this->unions[] = array(
        'query' => $query,
        'masktype' => 'ALL'
        );

    return $this;
  }

  /**
   *
   * @see MultiSelectQueryInterface::getUnions()
   */
  public function &getUnions() {
    // TODO Auto-generated method stub
    return $this->unions;
  }

}

/**
 * 执行查询并将结果放入临时表
 *
 * @author realeff
 *
 */
class TemporaryQuery extends Query {

  /**
   *
   * @var SelectQuery
   */
  protected $query;

  public function __construct($table, SelectQuery $query) {
    parent::__construct($table);

    $this->query = $query;
  }

  /**
   * 获取数据查询器
   *
   * @return SelectQuery
   */
  public function select() {
    return $this->query;
  }

  /**
   *
   * @see Query::name()
   */
  public function name() {
    // TODO Auto-generated method stub
    return 'temporary';
  }


  /**
   *
   * @see Query::type()
   */
  public function type() {
    // TODO Auto-generated method stub
    return Query::TEMPORARY;
  }

}
