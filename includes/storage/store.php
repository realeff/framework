<?php


abstract class Store {
  
  
  static protected $connections = array();
  
  
  static public function connection() {
    
  }
  
  
  static public function command($table) {
    
  }
  
  /**
   * 创建数据表
   * 
   * @param string $table
   * 
   * @return StoreSchema
   */
  public function createTable($table) {
    return new StoreSchema($table, StoreSchema::ACTION_CREATE);
  }
  
  /**
   * 移除数据表
   * 
   * @param string $table
   * 
   * @return StoreSchema
   */
  public function dropTable($table) {
    return new StoreSchema($table, StoreSchema::ACTION_DROP);
  }
  
  /**
   * 更改数据表
   * 
   * @param string $table
   * 
   * @return StoreSchema
   */
  public function alterTable($table) {
    return new StoreSchema($table, StoreSchema::ACTION_ALTER);
  }
  
  /**
   * 检查数据表
   * 
   * @param string $table
   * 
   * @return StoreSchema
   */
  public function existsTable($table) {
    return new StoreSchema($table, StoreSchema::ACTION_EXISTS);
  }
  
  /**
   * 查询数据
   *
   * @param string $table 数据表
   *
   * @return SelectQuery
   */
  static public function select($table) {
    return new SelectQuery($table);
  }
  
  /**
   * 查询数据
   *
   * @param string $table 数据表
   * @param string $alias 表别名
   *
   * @return MultiSelectQuery
   */
  static public function select_multi($table, $alias) {
    return new MultiSelectQuery($table, $alias);
  }
  
  /**
   * 插入数据
   *
   * @param string $table 数据表
   *
   * @return InsertQuery
   */
  static public function insert($table) {
    return new InsertQuery($table);
  }
  
  /**
   * 删除数据
   *
   * @param string $table 数据表
   *
   * @return DeleteQuery
   */
  static public function delete($table) {
    return new DeleteQuery($table);
  }
  
  /**
   * 更新数据
   *
   * @param string $table 数据表
   *
   * @return UpdateQuery
   */
  static public function update($table) {
    return new UpdateQuery($table);
  }
  
  /**
   * 插入更新数据，此方法会先检查指定数据是否存在，如果不存在则插入数据，如果存在则更新数据。
   *
   * @param string $table 数据表
   *
   * @return InsertQuery
   */
  static public function insert_update($table) {
    return new ReplaceQuery($table);
  }
  
  static public function execute() {
    
  }
  
  static public function result() {
    
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
   * 系统名称
   * 
   * @var string
   */
  private $_sysname = '';
  
  /**
   * 构造与存储设备的链接
   * 
   * @param string $sysname 系统名称
   */
  public function __construct($sysname) {
    $this->_sysname = $sysname;
  }
  
  /**
   * 建立与存储设备的链接
   *
   * @return boolean
   *   打开链接成功返回TRUE，失败返回FALSE。
   */
  abstract public function openConnection();

  /**
   * 关闭与存储设备的链接
   *
   * @return boolean
   *   关闭链接成功返回TRUE，失败返回FALSE。
   */
  abstract public function closeConnection();
  
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
   * 在存储设备上执行命令
   * 
   * @param string $command 命令名
   * 
   * @return StoreCommand
   */
  abstract public function command($command);

  /**
   * 获取存储设备的链接信息
   *
   * @return array
   *   返回一个数组表示的链接信息
   */
  //public function getConnectionInfo();

  /**
   * 取得当前链接的系统名称
   *
   * @return string
   */
  public function getSysname() {
    return $this->_sysname;
  }

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
  
  abstract public function beginTransaction();
  
  abstract public function inTransaction();
  
  abstract public function rollback();
  
  abstract public function commit();
}


/**
 * 这是建立数据存储的操作命令
 *
 * @author feng
 *
 */
abstract class StoreCommand {
  
  /**
   * 存储设备
   *
   * @var StoreConnection
   */
  protected $connection;

  /**
   * 命令
   *
   * @var string
   */
  protected $command;

  /**
   * 建立在指定存储设备上的操作命令
   *
   * @param StoreConnection $conn
   * @param string $command
   */
  public function __construct(StoreConnection $conn, $command) {
    $this->connection = $conn;
    $this->command = $command;
  }

  /**
   * 在指定存储设备上操作数据存储结构
   * 
   * @param StoreSchema $schema
   * 
   * @return boolean
   *   在存储设备上执行数据结构操作成功返回TRUE，失败返回FALSE。
   */
  abstract public function schema(StoreSchema $schema);

  /**
   * 执行查询提交至存储设备处理
   * 
   * @param Query $query 查询器
   *
   * @return boolean
   *   执行成功返回TRUE，失败返回FALSE。
   */
  abstract public function execute(Query $query);
  
  /**
   * 准备执行查询
   * 
   * @param Query $query 查询器
   * 
   * @return StoreStatementInterface
   */
  abstract public function prepare(Query $query);

  /**
   * 获取最后插入数据主键ID
   * 
   * @return integer
   */
  abstract public function lastInsertId();
  
  /**
   * 查询操作所影响的行数
   * 
   * @return integer
   */
  abstract public function affected_rows();
  
  /**
   * 返回错误代码
   * 
   * @return integer
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
 * 存储结构
 * 
 * @author realeff
 *
 */
class StoreSchema {

  
  const ACTION_CREATE = 'create';

  const ACTION_DROP = 'drop';

  const ACTION_ALTER = 'alter';

  const ACTION_EXISTS = 'exists';

  
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
   * 数据表
   *
   * @var string
   */
  protected $table;
  
  /**
   * 操作行为
   * 
   * @var string
   */
  protected $action;
  
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


  public function __construct($table, $action = self::ACTION_CREATE) {
    $this->table = $table;
    $this->action = $action;
  }
  
  public function getTable() {
    return $this->table;
  }
  
  public function getAction() {
    return $this->action;
  }

  
  /**
   * 默认字段属性
   * 
   * @return multitype:
   */
  protected function defaultAttributes() {
    static $attributes = array(
        'type' => NULL,
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
  
  public function &getFields() {
    return $this->fields;
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

  public function &getIndexs() {
    return $this->indexs;
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

  public function &getPrimary() {
    return $this->primary;
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
  
  public function &getUniques() {
    return $this->uniques;
  }
  
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

