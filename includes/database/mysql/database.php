<?php
/**
 * mysql_affected_rows — 取得前一次 MySQL 操作所影响的记录行数
 * mysql_close — 关闭 MySQL 连接
 * mysql_connect — 打开一个到 MySQL 服务器的连接
 * mysql_data_seek — 移动内部结果的指针
 * mysql_db_query — 发送一条 MySQL 查询
 * mysql_create_db — 新建一个 MySQL 数据库
 * mysql_drop_db — 丢弃（删除）一个 MySQL 数据库
 * mysql_errno — 返回上一个 MySQL 操作中的错误信息的数字编码
 * mysql_error — 返回上一个 MySQL 操作产生的文本错误信息
 * mysql_escape_string — 转义一个字符串用于 mysql_query
 * mysql_fetch_array — 从结果集中取得一行作为关联数组，或数字数组，或二者兼有
 * mysql_fetch_assoc — 从结果集中取得一行作为关联数组
 * mysql_fetch_field — 从结果集中取得列信息并作为对象返回
 * mysql_fetch_object — 从结果集中取得一行作为对象
 * mysql_fetch_row — 从结果集中取得一行作为枚举数组
 * mysql_field_seek — 将结果集中的指针设定为制定的字段偏移量
 * mysql_field_type — 取得结果集中指定字段的类型
 * mysql_free_result — 释放结果内存
 * mysql_get_server_info — 取得 MySQL 服务器信息
 * mysql_info — 取得最近一条查询的信息
 * mysql_insert_id — 取得上一步 INSERT 操作产生的 ID
 * mysql_list_dbs — 列出 MySQL 服务器中所有的数据库
 * mysql_list_tables — 列出 MySQL 数据库中的表
 * mysql_num_fields — 取得结果集中字段的数目
 * mysql_num_rows — 取得结果集中行的数目
 * mysql_pconnect — 打开一个到 MySQL 服务器的持久连接
 * mysql_ping — Ping 一个服务器连接，如果没有连接则重新连接
 * mysql_query — 发送一条 MySQL 查询
 * mysql_real_escape_string — 转义 SQL 语句中使用的字符串中的特殊字符，并考虑到连接的当前字符集
 * mysql_result — 取得结果数据
 * mysql_select_db — 选择 MySQL 数据库
 * mysql_set_charset — Sets the client character set
 * mysql_stat — 取得当前系统状态
 * mysql_thread_id — 返回当前线程的 ID
 * mysql_unbuffered_query — 向 MySQL 发送一条 SQL 查询，并不获取和缓存结果的行
 */

defined('STORE_DRIVER_PATH') or die;


class StoreDatabase_mysql extends StoreDatabase  {
  
  
  protected $dsn;
  protected $username;
  protected $password;
  protected $dbname;
  
  /**
   * 构造一个MYSQL链接
   * @param array $options
   */
  public function __construct(array $options) {
    parent::__construct($options);
    
    // The DSN should use either a socket or a host/port.
    if (isset($options['unix_socket'])) {
      //$dsn = 'mysql:unix_socket=' . $conn_options['unix_socket'];
      $dsn = $options['unix_socket'];
    }
    else {
      // Default to TCP database on port 3306.
      //$dsn = 'mysql:host=' . $conn_options['host'] . ';port=' . (empty($conn_options['port']) ? 3306 : $conn_options['port']);
      $dsn = $options['host'];
      if (isset($options['port'])) {
        $dsn .= ':'. $options['port'];
      }
    }
    $this->dsn = $dsn;
    $this->username = $options['username'];
    $this->password = $options['password'];
    //$dsn .= ';dbname=' . $conn_options['dbname'];
    $this->dbname = $options['dbname'];
    
    // 注册查询语句分析器
    $this->registerAnalyzer(new SQLSelectAnalyzer());
    $this->registerAnalyzer(new SQLInsertAnalyzer_mysql());
    $this->registerAnalyzer(new SQLUpdateAnalyzer());
    $this->registerAnalyzer(new SQLDeleteAnalyzer());
    $this->registerAnalyzer(new SQLUniqueInsertAnalyzer_mysql());
  }
  
  /**
   * (non-PHPdoc)
   * @see RelationDatabase::driver()
   */
  public function driver() {
    // TODO Auto-generated method stub
    return 'mysql';
  }
  
  /* (non-PHPdoc)
 * @see RelationDatabase::version()
 */
  public function version() {
    // TODO Auto-generated method stub
    list($version) = explode('-', mysql_get_server_info($this->resource));
    return $version;
  }

  /**
   * (non-PHPdoc)
   * @see StoreDatabase::open()
   */
  public function open() {
    // TODO Auto-generated method stub
    $this->resource = @mysql_connect($this->dsn, $this->username, $this->password, TRUE, MYSQL_NUM);
    if (!$this->resource || !mysql_select_db($this->dbname)) {
      // Show error screen otherwise
      //_db_error_page(mysql_error());
      return FALSE;
    }
    
    if (!empty($this->options['collation'])) {
      mysql_query('SET NAMES "utf8" COLLATE "'. $this->options['collation'] .'"', $this->resource);
    }
    else {
      // Force UTF-8.
      mysql_query('SET NAMES "utf8"', $this->resource);
    }
    
    return TRUE;
  }
  
  /**
   * (non-PHPdoc)
   * @see StoreDatabase::close()
   */
  public function close() {
    // TODO Auto-generated method stub
    return mysql_close($this->resource);
  }

  /**
   * (non-PHPdoc)
   * @see StoreDatabase::ping()
   */
  public function ping() {
    // TODO Auto-generated method stub
    return mysql_ping($this->resource);
  }
  
  /**
   * (non-PHPdoc)
   * @see StoreDatabase::errorCode()
   */
  public function errorCode() {
    // TODO Auto-generated method stub
    return mysql_errno($this->resource);
  }
  
  /**
   * (non-PHPdoc)
   * @see StoreDatabase::errorInfo()
   */
  public function errorInfo() {
    // TODO Auto-generated method stub
    return mysql_error($this->resource);
  }

  /**
   * (non-PHPdoc)
   * @see StoreDatabase::quote()
   */
  public function quote($value, $type = NULL) {
    // TODO Auto-generated method stub
    if (!isset($type)) {
      $type = $this->dataType($value);
    }
    
    switch ($type) {
      case self::PARAM_NULL:
        return "''";
//       case self::PARAM_BOOL:
//         return (bool)$value;
//       case self::PARAM_INT:
//         return (int)$value;
//       case self::PARAM_FLOAT:
//         return (float)$value;
//       case self::PARAM_DOUBLE:
//         return (double)$value;
      case self::PARAM_NUMERIC:
        return !preg_match('/x/i', $value) ? $value : '0';
      case self::PARAM_LOB:
        if (!is_string($value)) {
          $value = serialize($value);
        }
      case self::PARAM_STR:
        return "'". mysql_real_escape_string($value, $this->resource) ."'";
    }
    
    return $value;
  }


  /**
   * (non-PHPdoc)
   * @see StoreDatabase::schema()
   */
  public function schema() {
    // TODO Auto-generated method stub
    
  }

  /**
   * (non-PHPdoc)
   * @see StoreDatabase::lastInsertId()
   */
  public function lastInsertId() {
    // TODO Auto-generated method stub
    return mysql_insert_id($this->resource);
  }

  /**
   * (non-PHPdoc)
   * @see StoreDatabase::prepare()
   */
  public function prepare(Query $query) {
    // TODO Auto-generated method stub
    return new StoreStatementDatabase_mysql($this, $query);
  }

  /**
   * (non-PHPdoc)
   * @see StoreDatabase::affectedRows()
   */
  public function affectedRows() {
    // TODO Auto-generated method stub
    return mysql_affected_rows($this->resource);
  }
  
  private function _exec($sql) {
    $result = mysql_query($sql, $this->resource);
    if (!$this->errorCode()) {
      return $result;
    }
    else {
      return FALSE;
    }
  }
  
  /**
   * (non-PHPdoc)
   * @see StoreDatabase::execute()
   */
  public function execute(Query $query) {
    // TODO Auto-generated method stub
    $analyzer = $this->analyzer($query);
    // 检查分析器是否SQLAnalyzer
    if (!($analyzer instanceof SQLAnalyzer)) {
      return FALSE;
    }
    
    $analyzer->setQuery($query);
    $sql = (string)$analyzer;
    $args = $analyzer->arguments();
    $analyzer->clean();
    // 完成数据表前缀
    $sql = $this->prefixTables($sql);
    // 绑定参数数据
    $this->expandArguments($sql, $args);
    // 绑定参数
    $this->bindArguments($sql, $args);
    
    return $this->_exec($sql);
  }

  /**
   * (non-PHPdoc)
   * @see StoreDatabase::temporary()
   */
  public function temporary($temporaryTable, SelectQuery $query) {
    // TODO Auto-generated method stub
    $analyzer = $this->analyzer($query);
    // 检查分析器是否SQLAnalyzer
    if (!($analyzer instanceof SQLAnalyzer)) {
      return FALSE;
    }
    
    $analyzer->setQuery($query);
    $sql = (string)$analyzer;
    $args = $analyzer->arguments();
    $analyzer->clean();
    
    $sql = 'CREATE TEMPORARY TABLE {' . $temporaryTable . '} Engine=MEMORY '. $sql;
    // 完成数据表前缀
    $sql = $this->prefixTables($sql);
    // 绑定参数数据
    $this->expandArguments($sql, $args);
    // 绑定参数
    $this->bindArguments($sql, $args);
    
    return (bool)$this->_exec($sql);
  }

}

