<?php

/**
 * pg_affected_rows — 返回受影响的记录数目
 * pg_cancel_query — 取消异步查询
 * pg_close — 关闭一个 PostgreSQL 连接
 * pg_connect — 打开一个 PostgreSQL 连接
 * pg_connection_busy — 获知连接是否为忙
 * pg_connection_reset — 重置连接（再次连接）
 * pg_connection_status — 获得连接状态
 * pg_convert — 将关联的数组值转换为适合 SQL 语句的格式。
 * pg_copy_from — 根据数组将记录插入表中
 * pg_copy_to — 将一个表拷贝到数组中
 * pg_delete — 删除记录
 * pg_end_copy — 与 PostgreSQL 后端同步
 * pg_escape_bytea — 转义 bytea 类型的二进制数据
 * pg_escape_string — 转义 text/char 类型的字符串
 * pg_execute — Sends a request to execute a prepared statement with given parameters, and waits for the result.
 * pg_fetch_all_columns — Fetches all rows in a particular result column as an array
 * pg_fetch_all — 从结果中提取所有行作为一个数组
 * pg_fetch_array — 提取一行作为数组
 * pg_fetch_assoc — 提取一行作为关联数组
 * pg_fetch_object — 提取一行作为对象
 * pg_fetch_result — 从结果资源中返回值
 * pg_fetch_row — 提取一行作为枚举数组
 * pg_field_is_null — 测试字段是否为 NULL
 * pg_field_type — 返回相应字段的类型名称
 * pg_free_result — 释放查询结果占用的内存
 * pg_get_notify — Ping 数据库连接
 * pg_get_pid — Ping 数据库连接
 * pg_get_result — 取得异步查询结果
 * pg_insert — 将数组插入到表中
 * pg_last_error — 得到某连接的最后一条错误信息
 * pg_last_notice — 返回 PostgreSQL 服务器最新一条公告信息
 * pg_last_oid — 返回上一个对象的 oid
 * pg_lo_close — 关闭一个大型对象
 * pg_lo_create — 新建一个大型对象
 * pg_lo_export — 将大型对象导出到文件
 * pg_lo_import — 将文件导入为大型对象
 * pg_lo_open — 打开一个大型对象
 * pg_lo_read_all — 读入整个大型对象并直接发送给浏览器
 * pg_lo_read — 从大型对象中读入数据
 * pg_lo_seek — 移动大型对象中的指针
 * pg_lo_tell — 返回大型对象的当前指针位置
 * pg_lo_unlink — 删除一个大型对象
 * pg_lo_write — 向大型对象写入数据
 * pg_meta_data — 获得表的元数据
 * pg_options — 获得和连接有关的选项
 * pg_pconnect — 打开一个持久的 PostgreSQL 连接
 * pg_ping — Ping 数据库连接
 * pg_put_line — 向 PostgreSQL 后端发送以 NULL 结尾的字符串
 * pg_query — 执行查询
 * pg_result_error — 获得查询结果的错误信息
 * pg_result_seek — 在结果资源中设定内部行偏移量
 * pg_result_status — 获得查询结果的状态
 * pg_select — 选择记录
 * pg_send_query — 发送异步查询
 * pg_set_client_encoding — 设定客户端编码
 * pg_trace — 启动一个 PostgreSQL 连接的追踪功能
 * pg_transaction_status — Returns the current in-transaction status of the server.
 * pg_tty — 返回该连接的 tty 号
 * pg_unescape_bytea — 取消 bytea 类型中的字符串转义
 * pg_untrace — 关闭 PostgreSQL 连接的追踪功能
 * pg_update — 更新表
 * pg_version — Returns an array with client, protocol and server version (when available)
 */

class StoreConnection_pgsql extends StoreConnection  {
  
  protected $dsn;
  
  private $_result;
  
  /**
   * 构造一个MYSQL链接
   * @param array $options
   */
  public function __construct(array $options) {
    parent::__construct($options);
    
    // Default to TCP connection on port 5432.
    if (empty($options['port'])) {
      $options['port'] = 5432;
    }
    
    // PostgreSQL in trust mode doesn't require a password to be supplied.
    if (empty($options['password'])) {
      $options['password'] = NULL;
    }
    // If the password contains a backslash it is treated as an escape character
    // http://bugs.php.net/bug.php?id=53217
    // so backslashes in the password need to be doubled up.
    // The bug was reported against pdo_pgsql 1.0.2, backslashes in passwords
    // will break on this doubling up when the bug is fixed, so check the version
    //elseif (phpversion('pdo_pgsql') < 'version_this_was_fixed_in') {
    else {
      $options['password'] = str_replace('\\', '\\\\', $options['password']);
    }
    
    // The DSN should use either a socket or a host/port.
    if (isset($options['unix_socket'])) {
      //$dsn = 'mysql:unix_socket=' . $conn_options['unix_socket'];
      $dsn = $options['unix_socket'];
    }
    else {
      // Decode url-encoded information in the db connection string
      $dsn = ' host='. urldecode($options['host']);
      if (isset($options['port'])) {
        $dsn .= ' port='. urldecode($options['port']);
      }
      if (isset($options['username'])) {
        $dsn .= ' user='. urldecode($options['username']);
      }
      if (isset($options['password'])) {
        $dsn .= ' password='. urldecode($options['password']);
      }
      if (isset($options['dbname'])) {
        $dsn .= ' dbname='. urldecode($options['dbname']);
      }
    }
    $this->dsn = $dsn;
    
    // 注册查询语句分析器
    $this->registerAnalyzer(new SQLSelectAnalyzer());
    $this->registerAnalyzer(new SQLInsertAnalyzer_pgsql());
    $this->registerAnalyzer(new SQLUpdateAnalyzer());
    $this->registerAnalyzer(new SQLDeleteAnalyzer());
  }
  
  /**
   * (non-PHPdoc)
   * @see RelationDatabase::driver()
   */
  public function driver() {
    // TODO Auto-generated method stub
    return 'pgsql';
  }
  
  /* (non-PHPdoc)
 * @see RelationDatabase::version()
 */
  public function version() {
    // TODO Auto-generated method stub
    //return db_result(db_query("SHOW SERVER_VERSION"));;
  }

  /**
   * (non-PHPdoc)
   * @see StoreConnection::open()
   */
  public function open() {
    // TODO Auto-generated method stub
    
    // pg_last_error() does not return a useful error message for database
    // connection errors. We must turn on error tracking to get at a good error
    // message, which will be stored in $php_errormsg.
    $track_errors_previous = ini_get('track_errors');
    ini_set('track_errors', 1);
    
    $this->resource = @pg_connect($this->dsn);
    if (!$this->resource) {
      return FALSE;
    }
    
    // Restore error tracking setting
    ini_set('track_errors', $track_errors_previous);
    
    pg_query($this->resource, "set client_encoding=\"UTF8\"");
    
    return TRUE;
  }
  
  /**
   * (non-PHPdoc)
   * @see StoreConnection::close()
   */
  public function close() {
    // TODO Auto-generated method stub
    return pg_close($this->resource);
  }

  /**
   * (non-PHPdoc)
   * @see StoreConnection::ping()
   */
  public function ping() {
    // TODO Auto-generated method stub
    return pg_ping($this->resource);
  }
  
  /**
   * (non-PHPdoc)
   * @see StoreConnection::errorCode()
   */
  public function errorCode() {
    // TODO Auto-generated method stub
    return (bool)$this->errorInfo();
  }
  
  /**
   * (non-PHPdoc)
   * @see StoreConnection::errorInfo()
   */
  public function errorInfo() {
    // TODO Auto-generated method stub
    return empty($this->_result) ? pg_last_error($this->resource) : pg_result_error($this->_result);
  }

  /**
   * (non-PHPdoc)
   * @see StoreConnection::quote()
   */
  public function quote($value, $type = NULL) {
    // TODO Auto-generated method stub
    if (!isset($type)) {
      $type = $this->dataType($value);
    }
    
    switch ($type) {
      case self::PARAM_NULL:
        return "''";
      case self::PARAM_NUMERIC:
        return !preg_match('/x/i', $value) ? $value : '0';
      case self::PARAM_LOB:
        if (!is_string($value)) {
          $value = serialize($value);
        }
        return "'". pg_escape_bytea($this->resource, $value) ."'";
      case self::PARAM_STR:
        return "'". pg_escape_string($this->resource, $value) ."'";
    }
    
    return $value;
  }


  /**
   * (non-PHPdoc)
   * @see StoreConnection::schema()
   */
  public function schema() {
    // TODO Auto-generated method stub
    
  }

  /**
   * (non-PHPdoc)
   * @see StoreConnection::lastInsertId()
   */
  public function lastInsertId() {
    // TODO Auto-generated method stub
    //return mysql_insert_id($this->resource);
  }

  /**
   * (non-PHPdoc)
   * @see StoreConnection::prepare()
   */
  public function prepare(Query $query) {
    // TODO Auto-generated method stub
    //return new StoreStatementDatabase_mysql($this, $query);
  }

  /**
   * (non-PHPdoc)
   * @see StoreConnection::affectedRows()
   */
  public function affectedRows() {
    // TODO Auto-generated method stub
    return empty($this->_result) ? 0 : pg_affected_rows($this->_result);
  }
  
  private function _exec($sql) {
    $this->_result = pg_query($this->resource, $sql);
    if ($this->_result !== FALSE) {
      return $this->_result;
    }
    else {
      return FALSE;
    }
  }
  
  /**
   * (non-PHPdoc)
   * @see StoreConnection::execute()
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
   * @see StoreConnection::temporary()
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
    
    $sql = 'CREATE TEMPORARY TABLE {' . $temporaryTable . '} AS '. $sql;
    // 完成数据表前缀
    $sql = $this->prefixTables($sql);
    // 绑定参数数据
    $this->expandArguments($sql, $args);
    // 绑定参数
    $this->bindArguments($sql, $args);
    
    return (bool)$this->_exec($sql);
  }

}

