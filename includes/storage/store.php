<?php

/**
 * 存储驱动文件根路径
 */
define('STORE_DRIVER_PATH', dirname(__FILE__));

include_once STORE_DRIVER_PATH .'/query.php';
include_once STORE_DRIVER_PATH .'/analyzer.php';


define('STORE_PARAM_REGEXP', '/:(\w+)|(\?)/');



/**
 *
 *
 * @author realeff
 *
 */
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
   * 查询器
   *
   * @var array
   */
  protected static $queriers = array();

  /**
   * 添加自定义数据链接信息
   *
   * @param string $system
   * @param string $target
   * @param array $info
   */
  public static function addConnectionInfo($system, $target, array $info) {
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
        'database.php',
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
   * @return StoreDatabase
   */
  protected static function connection($target, array $options) {

    if (empty($options['driver'])) {
      throw new StoreDriverNotSpecifiedException('没有指定目标存储驱动：'. $target);
    }

    // 装载驱动
    self::loadDriver($options['driver']);

    // 实例化存储链接
    $class = 'StoreDatabase_'. $options['driver'];
    $connection = new $class($options);
    $connection->open();

    // 日志功能

    return $connection;
  }

  /**
   * 获取当前系统指定目标链接资源
   *
   * @param string $target 链接目标
   *
   * @return StoreDatabase
   */
  final public static function getConnection($target = 'default') {
    if (empty(self::$connectionInfo)) {
      self::switchSystem();
    }

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
   * 注册查询器
   *
   * @param AbstractQuerier $querier
   */
  final public static function registerQuerier(AbstractQuerier $querier) {
    if (empty(self::$queriers[$querier->name()])) {
      self::$queriers[$querier->name()] = $querier;
    }
  }

  final public static function removeQuerier($name) {
    if (isset(self::$queriers[$name])) {
      unset(self::$queriers[$name]);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * 是否有此名称的查询器
   *
   * @param string $name 查询器名称
   *
   * @return boolean
   */
  final public static function hasQuerier($name) {
    return isset(self::$queriers[$name]);
  }

  /**
   * 取得指定查询器
   *
   * @param string $name 查询器名称
   *
   * @return StoreQuerier
   */
  final public static function getQuerier($name) {
    if (empty(self::$connectionInfo)) {
      self::switchSystem();
    }

    $system = self::$activeSystem;
    $target = 'default';
    if (isset(self::$dataQuerier[$system][$name])) {
      // 根据查询器切换目标存储器
      $target = self::$dataQuerier[$system][$name];
    }

    $querier = isset(self::$queriers[$name]) ? self::$queriers[$name] : new EmptyQuerier();
    return new StoreQuerier(self::getConnection($target), $querier);
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
abstract class StoreDatabase {

  // 按ID范围或模数分表
  // 按年月日时间分表
  // 按首字符分表
  // 按md5序列分表
  // 按其它哈希算法分表
  // 按指定字段值分表
  const HASH_CRC32 = 0;

  const HASH_MD5 = 1;


  const PARAM_NULL = 0;
  const PARAM_INT = 1;
  const PARAM_STR = 2;
  const PARAM_LOB = 3;
  const PARAM_BOOL = 5;
  const PARAM_FLOAT = 6;
  const PARAM_DOUBLE = 7;
  const PARAM_NUMERIC = 8;

  /**
   * 这是存储设备链接资源
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
   * 查询语句分析器
   * @var array
   */
  private $_analyzers = array();

  /**
   * 数据表前缀
   *
   * @var array
   */
  protected $prefixes = array();

  /**
   * 替换数据表前缀
   * @var array
   */
  private $_prefixSearch = array();
  private $_prefixReplace = array();

  /**
   * 构造与存储设备的链接
   *
   * @param array $options 链接选项
   */
  public function __construct(array $options) {
    $this->options = $options;

    // 设置数据表前缀
    $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : '';
    $this->setPrefix(isset($options['prefix_exts']) ? $options['prefix_exts'] : array());
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
  //abstract public function schema();

  /**
   * 执行查询语句
   *
   * @param mixed $statement 查询语句
   * @param array $arguments 查询参数
   *
   * @return resource
   *   返回各存储设备执行查询后所返回的资源
   */
  abstract public function execute($statement, array $arguments = array());

  /**
   * 执行查询语句所影响的数据行数量。
   *
   * @return int
   */
  abstract public function affectedRows();

  /**
   * 执行插入语句最后插入数据的增量ID
   *
   * @return int
   */
  abstract public function lastInsertId();

  /**
   * 准备查询语句待执行
   *
   * @param mixed $statement
   *
   * @return StoreStatementInterface
   */
  abstract public function prepare($statement);

  /**
   * 执行查询语句
   *
   * @param mixed $statement 查询语句
   * @param array $arguments 查询参数
   *
   * @return StoreStatementInterface
   */
  public function query($statement, array $arguments = array()) {

    $stmt = $this->prepare($statement);
    $stmt->execute($arguments);

    return $stmt;
  }

  /**
   * 预置数据表前缀
   *
   * @param array $prefix
   */
  protected function setPrefix(array $prefix) {
    $this->prefixes = $prefix;


    $this->_prefixSearch = array();
    $this->_prefixReplace = array();
    foreach ($this->prefixes as $table => $prefix) {
      $this->_prefixSearch[] = '{' . $table . '}';
      $this->_prefixReplace[] = $this->escape($prefix) . $table;
    }
    // 使用默认前缀
    $prefix = $this->options['prefix'];
    $this->_prefixSearch[] = '{';
    $this->_prefixReplace[] = $this->escape($prefix);
    $this->_prefixSearch[] = '}';
    $this->_prefixReplace[] = '';
  }

  /**
   * 转换数据表前缀全称
   *
   * @param string $str 带表名的字符串
   *
   * @return string 数据表全名
   */
  public function prefixTables(&$str, array $args) {
    $str = str_replace($this->_prefixSearch, $this->_prefixReplace, $str);
  }

  /**
   * 返回完整数据表名称
   *
   * @param string $table
   *
   * @return string
   */
  public function tablePrefix($table) {
    if (isset($this->prefixes[$table])) {
      return $this->prefixes[$table] .$table;
    }
    else {
      return $this->options['prefix'] .$table;
    }
  }

  /**
   * 避开名称漏洞
   *
   * @param string $name
   */
  public function escape($name) {
    return preg_replace('/[^A-Za-z0-9_.]+/', '', $name);
  }

  /**
   * 返回数据类型
   *
   * @param mixed $data
   */
  protected function dataType(&$data) {

    if (is_null($data)) {
      return self::PARAM_NULL;
    }
    if (is_bool($data)) {
      return self::PARAM_BOOL;
    }
    if (is_int($data)) {
      return self::PARAM_INT;
    }
    if (is_float($data)) {
      return self::PARAM_FLOAT;
    }
    if (is_double($data)) {
      return self::PARAM_DOUBLE;
    }
    if (is_numeric($data)) {
      return self::PARAM_NUMERIC;
    }
    if (is_string($data)) {
      return self::PARAM_STR;
    }

    return self::PARAM_LOB;
  }

  /**
   *
   *
   * @param mixed $value
   * @param int $type
   *
   * @return string
   */
  abstract function quote($value, $type = NULL);

  /**
   * 展开数组参数
   *
   * @param string $str
   * @param array $args
   */
  protected function expandArguments(&$str, array &$args) {
    foreach (array_filter($args, 'is_array') as $key => $array) {
      $new_keys = array();
      foreach ($array as $i => $value) {
        $new_keys[$key .'_'. $i] = $value;
      }
      $str = preg_replace('#' . $key . '\b#', implode(', ', array_keys($new_keys)), $str);
      unset($args[$key]);
      $args += $new_keys;
    }
  }

  protected function bind_argument_callback(array $match, $init = FALSE) {
    static $args = array(), $fills = array();
    if ($init) {
      foreach ($match as $key => $value) {
        if (is_int($key)) {
          $fills[] = $value;
        }
        else {
          $args[$key] = $value;
        }
      }
      return;
    }

    return $this->quote(isset($match[2]) ? array_shift($fills) : $args[$match[1]]);
  }

  /**
   * 绑定参数
   *
   * @param string $str
   * @param array $args
   */
  protected function bindArguments(&$str, array $args) {
    $this->bind_argument_callback($args, TRUE);
    $str = preg_replace_callback(STORE_PARAM_REGEXP, array($this, 'bind_argument_callback'), $str);
  }

  /**
   * 注册查询语句分析器
   *
   * @param QueryAnalyzerInterface $analyzer
   */
  public function registerAnalyzer(QueryAnalyzerInterface $analyzer) {
    $type = $analyzer->masktype();
    if (!isset($this->_analyzers[$type])) {
      $this->_analyzers[$type] = $analyzer;
    }
  }

  /**
   * 分析查询语句，返回分析器。
   *
   * @param Query $query
   *
   * @return QueryAnalyzerInterface
   */
  public function analyzer(Query $query) {
    foreach ($this->_analyzers as $type => $analyzer) {
      if ($query->type() & $type) {
        $analyzer->setQuery($query);

        return $analyzer;
      }
    }
  }

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

abstract class AbstractQuerier {

  /**
   * 查询器名称
   *
   * @return string
   */
  abstract public function name();

  /**
   * 创建子查询条件
   *
   * @return QueryCondition
   */
  public static function createCondition() {
    return new QueryCondition();
  }

  /**
   * 构造数据查询器
   *
   * @param string $table 数据表
   *
   * @return SelectQuery
   */
  public static function select($table) {
    return new SelectQuery($table);
  }

  /**
   * 构造多表数据查询器
   *
   * @param string $table 数据表
   * @param string $alias 表别名
   *
   * @return MultiSelectQuery
   */
  public static function select_multi($table, $alias) {
    return new MultiSelectQuery($table, $alias);
  }

  /**
   * 插入数据
   *
   * @param string $table 数据表
   *
   * @return InsertQuery
   */
  public static function insert($table) {
    return new InsertQuery($table);
  }

  /**
   * 删除数据
   *
   * @param string $table 数据表
   *
   * @return DeleteQuery
   */
  public static function delete($table) {
    return new DeleteQuery($table);
  }

  /**
   * 更新数据
   *
   * @param string $table 数据表
   *
   * @return UpdateQuery
   */
  public static function update($table) {
    return new UpdateQuery($table);
  }

  /**
   * 插入唯一数据，此方法会先检查指定数据是否存在，如果不存在则插入数据，如果存在则更新数据。
   *
   * @param string $table 数据表
   * @param QueryParameter $parameter 数据参数
   *
   * @return UniqueInsertQuery
   */
  public static function insert_unique($table) {
    return new UniqueInsertQuery($table);
  }

  /**
   *
   * @return SelectQuery
   */
  abstract public function doget(array $filter, $params = array());
}

class EmptyQuerier extends AbstractQuerier {

  public function doget(array $filter, $params = array()) {
    // TODO Auto-generated method stub
    return NULL;
  }

  public function name() {
    // TODO Auto-generated method stub
    return 'empty';
  }

}

/**
 * 数据存储查询器
 *
 * @author realeff
 *
 */
class StoreQuerier {

  /**
   * 查询方法名称
   *
   * @var string
   */
  public $do;

  /**
   * 查询过滤
   *
   * @var array
   */
  protected $filters;

  /**
   * 查询参数
   *
   * @var QueryParameter
   */
  protected $parameter;


  /**
   * 存储设备链接
   *
   * @var StoreDatabase
   */
  protected $database;

  /**
   * 数据查询器
   *
   * @var AbstractQuerier
   */
  protected $querier;

  private static $_queries = array();
  private $_modefied = FALSE;

  private $_cache = TRUE;

  /**
   * 构造一个数据存储命令
   *
   * @param StoreDatabase $database
   * @param AbstractQuerier $querier
   */
  public function __construct(StoreDatabase $database, AbstractQuerier $querier) {
    $this->filters = array();
    $this->parameter = new QueryParameter();

    $this->database = $database;
    $this->querier = $querier;

    $driver = $this->database->driver();
    if (!isset(self::$_queries[$driver])) {
      self::$_queries[$driver] = Realeff::loadData($driver .'_queries', 'cache');
    }
  }

  public function __destruct() {
    if ($this->_modefied) {
      $driver = $this->database->driver();
      Realeff::saveData($driver .'_queries', self::$_queries[$driver], 'cache');
      $this->_modefied = FALSE;
    }
  }

  /**
   * 获取存储设备链接
   *
   * @return StoreDatabase
   */
  final public function getDatabase() {
    return $this->database;
  }

  /**
   * 设置查询参数
   *
   * @param string $name 参数名称
   * @param mixed $value 参数值
   *
   * @return StoreQuerier
   */
  final public function setParam($name, $value) {
    $this->parameter[$name] = $value;

    return $this;
  }

  /**
   * 增加查询参数
   *
   * @param string $name 参数名称
   * @param mixed $value 参数值
   * @param boolean $preserve
   *   保护值数组不被分解，默认为FALSE不进行保护。
   *
   * @return StoreQuerier
   */
  final public function addParam($name, $value, $preserve = FALSE) {
    if (is_array($value)) {
      $this->filters[$name] = $name .'_'. count($value);
      $values = $preserve ? array($value) : $value;
    }
    else {
      $values = array($value);
    }

    foreach ($values as $value) {
      $this->parameter->add($name, $value);
    }

    // 如果参数值是数组，

    return $this;
  }

  /**
   * 增加多个查询参数
   *
   * @param array $params 参数列表
   *
   * @return StoreQuerier
   */
  final public function addParams(array $params) {
    foreach ($params as $name => $value) {
      $this->parameter->add($name, $value);
    }

    return $this;
  }

  /**
   * 设置查询过滤
   *
   * @param string $name 参数名称
   * @param mixed $value 参数值
   * @param boolean $param 更新参数值
   *
   * @return StoreQuerier
   */
  final public function setFilter($name, $value = NULL, $param = FALSE) {
    $this->filters[$name] = $param ? $name : (isset($value) ? $value : $name);
    if ($param) {
      $this->setParam($name, $value);
    }

    return $this;
  }

  /**
   * 设置缓存
   *
   * @param boolean $cached
   *
   * @return StoreQuerier
   */
  final public function setCache($cache = TRUE) {
    $this->_cache = $cache;

    return $this;
  }

  /**
   * 清理存储查询器
   *
   * @return StoreQuerier
   */
  final public function clear() {
    $this->filters = array();
    $this->parameter = new QueryParameter();

    return $this;
  }

  protected function analyzer() {
    if (empty($this->do)) {
      return FALSE;
    }

    $driver = $this->database->driver();
    $identifier = $this->querier->name() .'::'. $this->do;
    $identifier .= empty($this->filters) ? '' : implode('-', $this->filters);
    if ($this->_cache && isset(self::$_queries[$driver][$identifier])) {
      $results = self::$_queries[$driver][$identifier];
      $statement = unserialize($results[0]);
      $arguments = unserialize($results[1]);
      foreach ($this->parameter as $key => $value) {
        $arguments[$key] = $value;
      }

      return array($statement, $arguments);
    }

    $method = 'do'. strtr($this->do, '-', '_');
    if (method_exists($this->querier, $method)) {
      $query = $this->querier->{$method}($this->filters, clone($this->parameter));
      if (empty($query)) {
        return FALSE;
      }

      $analyzer = $this->database->analyzer($query);

      if ($analyzer) {
        $statement = $analyzer->statement();
        $arguments = array();
        foreach ($analyzer->parameter() as $key => $value) {
          $arguments[$key] = $value;
        }

        if ($this->_cache && !REALEFF_DEBUG) {
          self::$_queries[$driver][$identifier] = array(serialize($statement), serialize($arguments));
          $this->_modefied = TRUE;
        }

        foreach ($this->parameter as $key => $value) {
          $arguments[$key] = $value;
        }

        return array($statement, $arguments);
      }
    }
    else {
      $this->do = FALSE;
    }

    return FALSE;
  }

  /**
   * 执行查询操作
   *
   * @return boolean
   *   执行成功返回一个资源标识符或者结果，如果执行失败则返回FALSE。
   */
  public function execute() {
    if (list($statement, $arguments) = $this->analyzer()) {
      $result = $this->database->execute($statement, $arguments);
      $this->clear();

      return $result;
    }

    return FALSE;
  }

  /**
   * 立即执行查询并返回结果
   *
   * @return StoreStatementInterface
   */
  public function query() {
    if (list($statement, $arguments) = $this->analyzer()) {
      $stmt = $this->database->query($statement, $arguments);
      $this->clear();

      return $stmt;
    }

    return new StoreStatementEmpty();
  }

  /**
   * 准备但不立即执行查询并返回结果
   *
   * @return StoreStatementInterface
   */
  public function prepare() {
    if (list($statement) = $this->analyzer()) {
      $stmt = $this->database->prepare($statement);
      $this->clear();

      return $stmt;
    }

    return new StoreStatementEmpty();
  }

  /**
   * 执行插入查询并获取最后插入数据主键增量ID
   *
   * @return int
   */
  public function lastInsertId() {
    if (empty($this->do)) {
      return FALSE;
    }

    return $this->database->lastInsertId();
  }

  /**
   * 返回所影响的行数
   *
   * @return int
   */
  public function affected_rows() {
    if (empty($this->do)) {
      return FALSE;
    }

    return $this->database->affectedRows();
  }

  /**
   * 返回错误代码
   *
   * @return int
   */
  public function errorCode() {
    return $this->database->errorCode();
  }

  /**
   * 返回错误信息
   *
   * @return string
   */
  public function errorInfo() {
    return $this->database->errorInfo();
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
   * @var StoreDatabase
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
   * @param StoreDatabase $connection
   */
  public function __construct(StoreDatabase $connection) {
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

/**
 * 如果有一个执行数据库操作错误抛出异常。
 *
 * @author realeff
 *
 */
class StoreDatabaseException extends Exception {}

/**
 * 存储数据处理接口
 *
 * @author realeff
 *
 */
interface StoreStatementInterface {

  /**
   * 可带参执行查询
   * @param array $args
   *
   * @return boolean
   */
  public function execute(array $args = array());

  /**
   * 获取结果集数量
   *
   * @return int
   */
  public function rowCount();

  //public function fetch();

  /**
   * 获取包含数字和关联字段的数组
   *
   * @return array
   */
  public function fetchAll();

  /**
   * 获取指定偏移量字段值
   *
   * @param int $index
   *
   * @return mixed
   */
  public function fetchField($index = 0);

  /**
   * 获取关联字段数组
   *
   * @return array
   */
  public function fetchAssoc();

  /**
   * 获取数字数组
   *
   * @return array
   */
  public function fetchArray();

  /**
   * 获取数据对象
   *
   * @return object
   */
  public function fetchObject();

  /**
   * 释放结果内存
   */
  public function freeResult();

  //public function errorCode();

  //public function errorInfo();
}

class StoreStatementEmpty implements StoreStatementInterface {
	/* (non-PHPdoc)
 * @see StoreStatementInterface::execute()
 */
  public function execute(array $args = array()) {
    // TODO Auto-generated method stub
    return FALSE;
  }

	/* (non-PHPdoc)
 * @see StoreStatementInterface::fetchAll()
 */
  public function fetchAll() {
    // TODO Auto-generated method stub
    return NULL;
  }

	/* (non-PHPdoc)
 * @see StoreStatementInterface::fetchArray()
 */
  public function fetchArray() {
    // TODO Auto-generated method stub
    return array();
  }

	/* (non-PHPdoc)
 * @see StoreStatementInterface::fetchAssoc()
 */
  public function fetchAssoc() {
    // TODO Auto-generated method stub
    return array();
  }

	/* (non-PHPdoc)
 * @see StoreStatementInterface::fetchField()
 */
  public function fetchField($index = 0) {
    // TODO Auto-generated method stub
    return NULL;
  }

	/* (non-PHPdoc)
 * @see StoreStatementInterface::fetchObject()
 */
  public function fetchObject() {
    // TODO Auto-generated method stub
    return NULL;
  }

	/* (non-PHPdoc)
 * @see StoreStatementInterface::freeResult()
 */
  public function freeResult() {
    // TODO Auto-generated method stub
    return TRUE;
  }

	/* (non-PHPdoc)
 * @see StoreStatementInterface::rowCount()
 */
  public function rowCount() {
    // TODO Auto-generated method stub
    return 0;
  }

}

abstract class StoreStatementBase implements StoreStatementInterface {

  /**
   * 数据存储链接
   *
   * @var StoreDatabase
   */
  protected $database;

  /**
   * 数据查询语句
   *
   * @var mixed
   */
  protected $statement;

  /**
   *
   * @var resource
   */
  protected $result;

  /**
   *
   * @param StoreDatabase $database
   * @param Query $query
   */
  public function __construct(StoreDatabase $database, $statement) {
    $this->database = $database;
    $this->statement = $statement;
  }

	/* (non-PHPdoc)
 * @see StoreStatementBase::execute()
 */
  public function execute(array $args = array()) {
    // TODO Auto-generated method stub
    $this->result = NULL;

    $result = $this->database->execute($this->statement, $args);
    if (!$this->database->errorCode()) {
      $this->result = $result;
    }

    return (bool)$this->result;
  }
}


/**
 * 切换存储系统
 *
 * @param string $system
 */
function store_switch($system = 'realeff') {
  return Store::switchSystem($system);
}

/**
 * 复位存储系统
 *
 * @param string $system
 */
function store_reset($system = NULL) {
  return Store::resetSystem($system);
}

/**
 * 关闭目标设备链接
 *
 * @param string $target
 */
function store_close($target = NULL) {
  return Store::closeConnection($target);
}

/**
 * 获取默认存储设备驱动名称
 */
function store_driver() {
  return Store::getConnection()->driver();
}

/**
 * 测试默认存储设备链接
 */
function store_ping() {
  return Store::getConnection()->ping();
}

/**
 * 获取存储查询器
 *
 * @param string $name
 *
 * @return StoreQuerier
 */
function store_getquerier($name) {
  return Store::getQuerier($name);
}

function store_querier_register(AbstractQuerier $querier) {
  Store::registerQuerier($querier);
}



