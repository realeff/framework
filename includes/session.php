<?php

abstract class AbstractSession {

  protected $lifetime;

  protected $time;

  public function __construct($lifetime) {
    $this->lifetime = $lifetime;
    $this->time = $_SERVER['REQUEST_TIME'];

    // 使用此对象作为会话处理函数
    session_set_save_handler(
      array($this, 'open'),
      array($this, 'close'),
      array($this, 'read'),
      array($this, 'write'),
      array($this, 'destroy'),
      array($this, 'gc')
    );
  }

  /**
   * session_set_save_handler()指定的会话处理函数
   *
   * 这个函数不应该被直接调用
   *
   * @param string $save_path
   * @param string $session_name
   *
   * @return boolean
   *   这个函数将总是返回TRUE
   */
  abstract public function open();

  /**
   * session_set_save_handler()指定的会话处理函数
   *
   * 这个函数不应该被直接调用
   *
   * @return boolean
   *   这个函数将总是返回TRUE
   */
  abstract public function close();

  /**
   * 读取实体会话字符串数据
   *
   * @param string $id 检索用户的会话ID
   *
   * @return string
   *   返回用户的会话字符串，如果会话不存在则返回空字符串
   */
  abstract public function read($id);

  /**
   *
   *
   * @param string $id
   * @param string $data
   *
   * @return boolean
   */
  abstract public function write($id, $data);

  /**
   *
   * @param string $id
   *
   *
   */
  abstract public function destroy($id);

  /**
   *
   * @param int $lifetime
   */
  abstract public function gc($lifetime);

}


/**
 * 数据库处理会话
 *
 * @author realeff
 *
 */
class DatabaseSession extends AbstractSession {

  protected $querier;

  public function __construct($lifetime) {
    parent::__construct($lifetime);

    // 获取会话数据查询器
    $this->querier = store_getquerier(REALEFF_QUERIER_SESSION);
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::open()
   */
  public function open() {
    // TODO Auto-generated method stub
    return TRUE;
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::close()
   */
  public function close() {
    // TODO Auto-generated method stub
    return TRUE;
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::read()
   */
  public function read($id) {
    // TODO Auto-generated method stub

    $this->querier->do = 'read';
    $this->querier->setParam('id', $id);

    $stmt = $this->querier->query();
    return (string)$stmt->fetchField();
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::wirte()
   */
  public function write($id, $data) {
    // TODO Auto-generated method stub

    $fields = array('data' => $data, 'timestamp' => $this->time, 'hostname' => ip_address());

    $this->querier->do = 'write';
    $this->querier->setParam('id', $id);
    $this->querier->addParams($fields);
    $this->querier->addParams($fields);

    $this->querier->execute();

    return TRUE;
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::destroy()
   */
  public function destroy($id) {
    // TODO Auto-generated method stub

    $this->querier->do = 'destroy';
    $this->querier->setParam('id', $id);

    $this->querier->execute();

    return TRUE;
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::gc()
   */
  public function gc($lifetime) {
    // TODO Auto-generated method stub

    $this->querier->do = 'gc';
    $this->querier->setParam('timestamp', $this->time - $this->lifetime);

    $this->querier->execute();

    return TRUE;
  }

}

/**
 * 缓存处理会话
 *
 * @author realeff
 *
 */
class CacheSession extends AbstractSession {

  /**
   * (non-PHPdoc)
   * @see AbstractSession::open()
   */
  public function open() {
    // TODO Auto-generated method stub
    return TRUE;
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::close()
   */
  public function close() {
    // TODO Auto-generated method stub
    return TRUE;
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::read()
   */
  public function read($id) {
    // TODO Auto-generated method stub
    return (string)cache_get($id, CACHE_BIN_SESSION);
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::wirte()
   */
  public function write($id, $data) {
    // TODO Auto-generated method stub
    return cache_set($id, $data, $this->lifetime, CACHE_BIN_SESSION);
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::destroy()
   */
  public function destroy($id) {
    // TODO Auto-generated method stub
    return cache_clear_all($id, CACHE_BIN_SESSION);
  }

  /**
   * (non-PHPdoc)
   * @see AbstractSession::gc()
   */
  public function gc($lifetime) {
    // TODO Auto-generated method stub
    Cache::getBin(CACHE_BIN_SESSION)->gc();

    return TRUE;
  }

}



function realeff_session_regenerate() {

}

/**
 * 启动session机制
 */
function realeff_session_start() {

  // 注册关机时提交session数据进行存储
  register_shutdown_function('realeff_session_commit');

  session_start();

}

function realeff_session_commit() {

  // 如果$_SESSION被使用，则执行存储处理。
  // 如果是匿名用户，但$_SESSION被使用，先打开session，将数据保存后再关闭。

  session_write_close();

}

