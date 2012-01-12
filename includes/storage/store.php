<?php


abstract class Store {
  
  
  static protected $connections = array();
  
  /**
   * 
   * @param string $name 链接名称
   * @param string $system 系统名称
   * 
   * @return StoreConnection
   */
  static public function connection($name = 'default', $system = 'realeff') {
    
  }
  
  static public function getConnection() {
    
  }
  
  /**
   * 取得指定查询的数据存储命令
   * 
   * @param string $name 命令名
   * @param string $table 数据表
   * @param string $system 系统
   * 
   * @return StoreCommand
   */
  static public function getCommand($name, $table, $system = 'realeff') {
    
  }
  
  static public function openConnection() {
    
  }
  
  static public function closeConnection() {
    
  }
  
  static public function removeConnection() {
    
  }
  
  static public function startLog() {
    
  }
  
}

/**
 * 建立一个与存储设备的接连
 *
 * @author feng
 */
abstract class StoreConnection {
  
  /**
   * 这是存储设备资源
   * 
   * @var resource
   */
  protected $resource;
  
  /**
   * 链接选项
   * 
   * @var array
   */
  protected $options = array();
  
  /**
   * 数据表前缀
   * 
   * @var array
   */
  protected $prefixes = array();
  
  /**
   * 构造与存储设备的链接
   * 
   * @param array $options 链接选项
   */
  public function __construct(array $options) {
    $this->options = $options;
    
    
  }
  
  /**
   * 建立与存储设备的链接
   *
   * @return boolean
   *   打开链接成功返回TRUE，失败返回FALSE。
   */
  abstract public function open();

  /**
   * 关闭与存储设备的链接
   *
   * @return boolean
   *   关闭链接成功返回TRUE，失败返回FALSE。
   */
  abstract public function close();
  
  /**
   * 获取链接错误代码
   * 
   * @return int
   */
  abstract public function errorCode();
  
  /**
   * 获取链接错误信息
   * 
   * @return array
   */
  abstract public function errorInfo();

  /**
   * 获取与存储设备的链接资源
   *
   * @return resource
   */
  public function getConnection() {
    return $this->resource;
  }
  
  
  /**
   * 在指定存储设备上操作数据存储结构
   *
   * @return StoreSchema
   */
  abstract public function schema();
  
  /**
   * 在存储设备上执行查询命令
   * 
   * @param string $name 命令名称
   * 
   * @return StoreCommand
   */
  abstract public function command($name);

  /**
   * 获取存储设备的链接信息
   *
   * @return array
   *   返回一个数组表示的链接信息
   */
  //public function getConnectionInfo();

  /**
   * 检查存储设备的链接
   *
   * @return boolean
   *   连接存储设备正常返回TRUE，失败返回FALSE。
   */
  abstract public function ping();
  
  /**
   * 存储设备驱动名
   *
   * @return string
   */
  abstract public function driver();
  
  /**
   * 存储设备版本信息
   *
   * @return string
   */
  abstract public function version();
  
  /**
   * 
   * @return boolean
   */
  //abstract public function beginTransaction();
  
  /**
   * 
   * @return boolean
   */
  //abstract public function inTransaction();
  
  /**
   * 
   * @return boolean
   */
  //abstract public function rollback();
  
  /**
   * 
   * @return boolean
   */
  //abstract public function commit();
}


/**
 * 数据存储命令
 * 
 * @author realeff
 *
 */
class StoreCommand {
  
  /**
   * 存储命令
   * 
   * @var string
   */
  protected $command;
  
  /**
   * 存储参数
   * 
   * @var StoreParameter
   */
  protected $parameter;
  
  /**
   * 存储设备链接
   * 
   * @var StoreConnection
   */
  protected $connection;
  
  /**
   * 查询器
   * 
   * @var Query
   */
  protected $query;
  
  /**
   * 构造一个数据存储命令
   * 
   * @param string $command
   * @param StoreConnection $connection
   */
  public function __construct($command, StoreConnection $connection) {
    $this->command = $command;
    $this->parameter = new StoreParameter();
    
    $this->connection = $connection;
  }
  
  /**
   * 添加命令参数名
   * 
   * @param string $name
   */
  final public function addParam($name) {
    $this->parameter->addParam($name);
  }
  
  /**
   * 
   * 
   * @param string $string
   * @param int $type
   * 
   * @return string
   */
  abstract public function quote($string, $type = NULL);
  
  
  /**
   * 查询条件
   *
   * @return QueryCondition
   */
  final public function condition() {
    return new QueryCondition($this->parameter);
  }
  
  /**
   * 查询数据
   *
   * @param string $table 数据表
   *
   * @return SelectQuery
   */
  final public function select($table) {
    if (isset($this->query)) {
      return new SelectQuery($table, $this->parameter);
    }
    else {
      $this->query = new SelectQuery($table, $this->parameter);
      return $this->query;
    }
  }
  
  /**
   * 查询数据
   *
   * @param string $table 数据表
   * @param string $alias 表别名
   *
   * @return MultiSelectQuery
   */
  final public function select_multi($table, $alias) {
    if (isset($this->query)) {
      return new MultiSelectQuery($table, $alias, $this->parameter);
    }
    else {
      $this->query = new MultiSelectQuery($table, $alias, $this->parameter);
      return $this->query;
    }
  }
  
  /**
   * 插入数据
   *
   * @param string $table 数据表
   *
   * @return InsertQuery
   */
  final public function insert($table) {
    $this->query = new InsertQuery($table, $this->parameter);
    return $this->query;
  }
  
  /**
   * 删除数据
   *
   * @param string $table 数据表
   *
   * @return DeleteQuery
   */
  final public function delete($table) {
    $this->query = new DeleteQuery($table, $this->parameter);
    return $this->query;
  }
  
  /**
   * 更新数据
   *
   * @param string $table 数据表
   *
   * @return UpdateQuery
   */
  final public function update($table) {
    $this->query = new UpdateQuery($table, $this->parameter);
    return $this->query;
  }
  
  /**
   * 插入更新数据，此方法会先检查指定数据是否存在，如果不存在则插入数据，如果存在则更新数据。
   *
   * @param string $table 数据表
   * @param StoreParameter $parameter 数据参数
   *
   * @return InsertQuery
   */
  final public function insert_update($table) {
    $this->query = new ReplaceQuery($table, $this->parameter);
    return $this->query;
  }
  
  /**
   * 执行查询提交至存储设备处理
   *
   * @return boolean
   *   执行成功返回TRUE，失败返回FALSE。
   */
  abstract public function execute();
  
  /**
   * 准备执行查询
   *
   * @return StoreStatementInterface
   */
  abstract public function prepare();
  
  /**
   * 获取最后插入数据主键ID
   *
   * @return int
   */
  abstract public function lastInsertId();
  
  /**
   * 查询操作所影响的行数
   *
   * @return int
   */
  abstract public function affected_rows();
  
  /**
   * 返回错误代码
   *
   * @return int
   */
  abstract public function errorCode();
  
  /**
   * 返回错误信息
   *
   * @return string
   */
  abstract public function errorInfo();
  
}

/**
 * 存储参数
 * 
 * @author realeff
 *
 */
final class StoreParameter implements Iterator, ArrayAccess, Countable {

  /**
   * 命令参数列表
   *
   * @var array
   */
  private $_params = array();
  /**
   * 命令允许最多参数
   *
   * @var int
   */
  private $_maxlength = 10;

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
   * 添加命令参数
   *
   * @param string $name
   */
  public function addParam($name) {
    if (count($this->_params) > $this->_maxlength) {
      return ;
    }

    $this->_params[$name] = $name;
  }

  /**
   * 返回字符串表示命令参数
   */
  public function __toString() {
    return '-'. implode('-', $this->_params);
  }

  /**
   * 获取唯一参数名
   *
   * @return string
   */
  protected function uniqueName($name = 'param') {
    if (!isset($this->_counters[$name])) {
      $this->_counters[$name] = 0;
    }

    return $name .'_'. $this->_counters[$name]++;
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
 * 存储结构
 * 
 * @author realeff
 *
 */
abstract class StoreSchema {
  
  const TYPE_BOOL = 1;
  
  const TYPE_INT = 2;
  
  const TYPE_FLOAT = 3;
  
  const TYPE_NUMERIC = 4;
  
  const TYPE_CHAR = 5;
  
  const TYPE_STRING = 6;
  
  const TYPE_TEXT = 7;
  
  const TYPE_BINARY = 8;
  
  
  const SIZE_TINY = 1;
  
  const SIZE_SMALL = 2;
  
  const SIZE_MEDIUM = 3;
  
  const SIZE_BIG = 4;
  
  const SIZE_NORMAL = 5;
  
  /**
   * 存储器链接
   * 
   * @var StoreConnection
   */
  protected $connection;
  
  /**
   * 操作行为
   *
   * @var string
   */
  protected $action;
  
  /**
   * 数据表
   *
   * @var string
   */
  protected $table;
  
  /**
   * 
   * 
   * @var array
   */
  protected $fields = array();
  
  /**
   * 
   * 
   * @var array
   */
  protected $primary = array();
  
  /**
   * 
   * 
   * @var array
   */
  protected $indexs = array();
  
  /**
   * 
   * 
   * @var array
   */
  protected $uniques = array();

  /**
   * 构造一个数据存储结构操作
   * 
   * @param StoreConnection $connection
   */
  public function __construct(StoreConnection $connection) {
    $this->connection = $connection;
  }
  
  /**
   * 默认字段属性
   * 
   * @return multitype:
   */
  protected function defaultAttributes() {
    static $attributes = array(
        'type' => self::TYPE_INT,
        'size' => NULL,
        'length' => NULL,
        'precision' => NULL,
        'scale' => NULL,
        'unsigned' => NULL,
        'auto_increment' => NULL,
        'not_null' => FALSE,
        'default' => NULL,
        'serialize' => FALSE,
        'description' => '',
      );
    
    return $attributes;
  }

  /**
   * 
   * @param string $name
   * @param array $attributes
   * 
   * @return StoreSchema
   */
  public function field($name, array $attributes = array()) {
    $this->fields[$name] = $attributes;
    
    return $this;
  }

  /**
   * 
   * @param string $name
   * @param array $fields
   * 
   * @return StoreSchema
   */
  public function index($name, array $fields = array()) {
    $this->indexs[$name] = $fields;
    
    return $this;
  }

  /**
   * 
   * @param array $fields
   * 
   * @return StoreSchema
   */
  public function primary(array $fields = array()) {
    $this->primary = $fields;
    
    return $this;
  }
  
  /**
   * 
   * @param string $name
   * @param array $fields
   * 
   * @return StoreSchema
   */
  public function unique($name, array $fields = array()) {
    $this->uniques[$name] = $fields;
    
    return $this;
  }
  
  /**
   * 创建数据表
   *
   * @param string $table 数据表名
   *
   * @return boolean
   */
  abstract public function createTable($table);
  
  /**
   * 移除数据表
   *
   * @param string $table 数据表名
   *
   * @return boolean
   */
  abstract public function dropTable($table);
  
  /**
   * 更改数据表
   *
   * @param string $table
   *
   * @return boolean
   */
  abstract public function alterTable($table);
  
  /**
   * 检查数据表
   *
   * @param string $table
   *
   * @return boolean
   */
  abstract public function existsTable($table);
  
  
  /**
   * 执行数据表结构操作
   * 
   * @return boolean
   *   在存储设备上执行数据结构操作成功返回TRUE，失败返回FALSE。
   */
  abstract public function execute();
  
}


interface StoreStatementInterface extends Traversable {
  
  public function execute();
  
  public function bindParam();
  
  public function rowCount();
  
  public function fetch();
  
  public function fetchField();
  
  public function fetchAssoc();
  
  public function fetchCol();
  
  public function fetchAllKeyd();
  
  public function fetchAllAssoc();
  
  public function fetchArray();
  
  public function fetchObject();
  
}

