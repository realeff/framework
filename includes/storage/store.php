<?php

/**
 * 存储驱动文件根路径
 */
define('STORE_DRIVER_PATH', dirname(__FILE__));

include_once STORE_DRIVER_PATH .'/query.php';
include_once STORE_DRIVER_PATH .'/analyzer.php';


abstract class Store {
  
  /**
   * 链接存储器信息
   * 
   * @var array
   */
  protected static $connectionInfo = array();
  
  /**
   * 链接资源
   * 
   * @var array
   */
  protected static $connections = array();
  
  /**
   * 激活的存储系统
   * 
   * @var string
   */
  protected static $activeSystem = 'realeff';
  
  /**
   * 数据查询器
   * 
   * @var array
   */
  protected static $dataQuerier = array();
  
  /**
   * 添加自定义数据链接信息
   * 
   * @param string $system
   * @param string $target
   * @param array $info
   */
  public static function addConnectionInfo($system, $target, $info) {
    if (empty(self::$connectionInfo[$system][$target])) {
      self::$connectionInfo[$system][$target] = $info;
    }
  }
  
  /**
   * 获取存储系统链接信息
   */
  final public static function getConnectionInfo($system) {
    return isset(self::$connectionInfo[$system]) ? self::$connectionInfo[$system] : NULL;
  }
  
  /**
   * 切换存储系统
   * 
   * @param string $system 系统名称
   * 
   * @return string
   *   返回前一个系统名
   */
  final public static function switchSystem($system = 'realeff') {
    global $databases, $dataquerier;
    
    if (empty($databases[$system])) {
      throw new StoreSystemNotConfiguredException('没有配置指定存储系统：'. $system);
    }
    
    // 解析存储系统配置信息
    if (empty(self::$connectionInfo[$system])) {
      if (is_array($databases[$system])) {
        $databaseinfo = $databases[$system];
      }
      else {
        if (is_string($databases[$system]))
          return self::switchSystem($databases[$system]);
        else 
          return self::switchSystem();
      }
      
      foreach ($databaseinfo as $target => $database) {
        // 如果没有定义“driver”属性，这个数组就是一个存储器链接池定义，由系统在此链接池中取出一个连接。
        if (empty($database['driver'])) {
          $databaseinfo[$target] = $database[array_rand($database)];
        }
        
        if (!isset($databaseinfo[$target]['prefix'])) {
          $databaseinfo[$target]['prefix'] = '';
        }
      }
      
      self::$dataQuerier[$system] = array();
      if (isset($dataquerier[$system]) && is_array($dataquerier[$system])) {
        foreach ($dataquerier[$system] as $querier => $target) {
          if (isset($databaseinfo[$target]))
            self::$dataQuerier[$system][$querier] = $target;
        }
      }
      
      self::$connectionInfo[$system] = $databaseinfo;
    }
    
    $preSystem = self::$activeSystem;
    if ($system != self::$activeSystem) {
      self::$activeSystem = $system;
    }
    
    return $preSystem;
  }
  
  /**
   * 复位存储系统
   *
   * @param string $system
   *   系统名称，如果不指定则关闭所有系统链接。
   */
  public static function resetSystem($system = NULL) {
    if (isset($system)) {
      unset(self::$connectionInfo[$system]);
      unset(self::$connections[$system]);
      unset(self::$dataQuerier[$system]);
      self::switchSystem($system);
    }
    else {
      self::$connectionInfo = array();
      self::$connections = array();
      self::$dataQuerier = array();
    }
  }
  
  /**
   * 加载存储驱动文件
   * 
   * @param string $driver
   */
  protected static function loadDriver($driver) {
    static $drivers = array();
    
    if (isset($drivers[$driver])) {
      return ;
    }
    
    // 驱动文件包括
    static $files = array(
        'connection.php',
        'querier.php',
        'schema.php',
        'statement.php',
        'analyzer.php'
      );
    
    foreach ($files as $file) {
      $filename = STORE_DRIVER_PATH ."/{$driver}/{$file}";
      
      if (file_exists($filename)) {
        require_once $filename;
      }
    }
    
    $drivers[$driver] = TRUE;
  }
  
  /**
   * 建立新存储链接
   * 
   * @param string $target 链接标识
   * @param array $options 链接选项
   * 
   * @return StoreConnection
   */
  protected static function connection($target, array $options) {
    
    if (empty($options['driver'])) {
      throw new StoreDriverNotSpecifiedException('没有指定目标存储驱动：'. $target);
    }
    
    // 装载驱动
    self::loadDriver($options['driver']);
    
    // 实例化存储链接
    $class = 'StoreConnection_'. $options['driver'];
    $connection = new $class($options);
    
    // 日志功能
    
    return $connection;
  }
  
  /**
   * 获取当前系统指定目标链接资源
   * 
   * @param string $target 链接目标
   * 
   * @return StoreConnection
   */
  final public static function getConnection($target = 'default') {
    $system = self::$activeSystem;
    // 如果目标链接不存在，则使用默认链接。
    if (!isset(self::$connectionInfo[$system][$target])) {
      $target = 'default';
    }
    
    // 如果链接未建立，则打开新链接。
    if (!isset(self::$connections[$system][$target])) {
      self::openConnection($target);
    }
    
    return self::$connections[$system][$target];
  }
  
  /**
   * 打开当前系统指定目标链接
   * 
   * @param string $target 链接目标
   */
  public static function openConnection($target) {
    if (empty(self::$connectionInfo)) {
      self::switchSystem();
    }
    
    $system = self::$activeSystem;
    if (empty(self::$connectionInfo[$system][$target])) {
      throw new StoreConnectionNotDefinedException('没有定义目标存储链接：'. $target);
    }
    
    if (!isset(self::$connections[$system][$target])) {
      self::$connections[$system][$target] = self::connection($target, self::$connectionInfo[$system][$target]);
    }
    
    return self::$connections[$system][$target];
  }
  
  /**
   * 关闭当前系统指定的目标链接
   * 
   * @param string $target
   *   目标链接，如果不指定则关闭当前系统所有链接。
   */
  public static function closeConnection($target = NULL) {
    if (isset($target)) {
      unset(self::$connections[self::$activeSystem][$target]);
    }
    else {
      unset(self::$connections[self::$activeSystem]);
    }
  }
  
  public static function removeConnection($target = NULL) {
    $system = self::$activeSystem;
    if (!isset(self::$connectionInfo[$system])) {
      return FALSE;
    }
    
    if (isset($target)) {
      if (isset(self::$connectionInfo[$system][$target])) {
        unset(self::$connectionInfo[$system][$target]);
        unset(self::$connections[$system][$target]);
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    else {
      unset(self::$connectionInfo[$system]);
      unset(self::$connections[$system]);
    }
    
    return TRUE;
  }
  
  /**
   * 取得指定存储查询器
   * 
   * @param string $name 命令名
   * 
   * @return StoreQuerier
   */
  final public static function getQuerier($name) {
    if (empty(self::$connectionInfo)) {
      self::switchSystem();
    }
    
    $target = 'default';
    if (isset(self::$dataQuerier[self::$activeSystem][$name])) {
      // 根据查询器切换目标存储器
      $target = self::$dataQuerier[self::$activeSystem][$name];
    }
    
    return self::getConnection($target)->querier($name);
  }
  
//   public static function startLogger() {
    
//   }
  
//   public static function getLogger() {
    
//   }
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
  public function getResource() {
    return $this->resource;
  }
  
  
  /**
   * 在指定存储设备上操作数据存储结构
   *
   * @return StoreSchema
   */
  abstract public function schema();
  
  /**
   * 在存储设备上执行查询
   * 
   * @param string $name 命令名称
   * 
   * @return StoreQuerier
   */
  abstract public function querier($name);
  
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
abstract class StoreQuerier {
  
  /**
   * 查询器名称
   * 
   * @var string
   */
  protected $name;
  
  /**
   * 查询过滤器
   *
   * @var array
   */
  protected $filters;
  
  /**
   * 查询器最大过滤深度
   */
  const MAX_FILTER_DEPTH = 10;
  
  /**
   * 存储参数
   * 
   * @var QueryParameter
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
   * @param StoreConnection $connection
   */
  public function __construct(StoreConnection $connection, $name) {
    $this->name = $name;
    $this->filters = array();
    
    $this->connection = $connection;
    
    $this->parameter = new QueryParameter();
  }
  
  /**
   * 添加查询器过滤名
   * 
   * @param string $name
   */
  final public function addFilter($name) {
    if (count($this->filters) > self::MAX_FILTER_DEPTH) {
      return ;
    }

    $this->filters[$name] = $name;
  }
  
  /**
   * 重新设定存储命令内容
   */
  final public function clear() {
    if (isset($this->query)) {
      $this->query->end();
      $this->query = NULL;
      $this->parameter = new QueryParameter();
    }
    $this->filters = array();
  }
  
  /**
   * 创建子查询条件
   *
   * @return QueryCondition
   */
  final public function createCondition() {
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
   * 查询多表数据
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
   * @param QueryParameter $parameter 数据参数
   *
   * @return InsertQuery
   */
  final public function insert_update($table) {
    $this->query = new ReplaceQuery($table, $this->parameter);
    return $this->query;
  }
  
  /**
   * 执行查询操作
   *
   * @return boolean
   *   执行成功返回TRUE，失败返回FALSE。
   */
  abstract public function execute();
  
  /**
   * 立即执行查询并返回结果
   * 
   * @return StoreStatementInterface
   */
  abstract public function query();
  
  /**
   * 准备但不立即执行查询并返回结果
   *
   * @return StoreStatementInterface
   */
  abstract public function prepare();
  
  /**
   * 执行查询并将结果放入指定临时表
   * 
   * @param string $table
   * 
   * @return bool
   *   执行成功返回TRUE，失败返回FALSE。
   */
  abstract public function generateTemporary($table);
  
  /**
   * 执行插入查询并获取最后插入数据主键增量ID
   *
   * @return int
   */
  abstract public function lastInsertId();
  
  /**
   * 执行查询操并返回所影响的行数
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
  
  public function execute(array $args = array());
  
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




/**
 * 如果有一个未配置的存储系统请求抛出异常。
 * 
 * @author realeff
 *
 */
class StoreSystemNotConfiguredException extends Exception {}

/**
 * 如果有一个未定义的目标存储链接请求抛出异常。
 * 
 * @author realeff
 *
 */
class StoreConnectionNotDefinedException extends Exception {}

/**
 * 如果有一个未指定驱动的目标存储链接请求抛出异常。
 * 
 * @author realeff
 *
 */
class StoreDriverNotSpecifiedException extends Exception {}
