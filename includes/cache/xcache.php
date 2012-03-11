<?php

class XcacheCache implements CacheInterface {
  
  
  protected $bin;
  
  protected $secret;
  
  protected $lifetime;
  
  protected $time;
  
  public function __construct(array $bin, array $options = array()) {
    // 缓存容器
    $this->bin = $bin;

  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::delete()
   */
  public function delete($key) {
    // TODO Auto-generated method stub

  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::flush()
   */
  public function flush() {
    // TODO Auto-generated method stub

  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::gc()
   */
  public function gc() {
    // TODO Auto-generated method stub

  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::get()
   */
  public function get($key) {
    // TODO Auto-generated method stub

  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::getMulti()
   */
  public function getMulti(array $keys) {
    // TODO Auto-generated method stub

  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::increment()
   */
  public function increment($key, $offset = 1) {
    // TODO Auto-generated method stub

  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::decrement()
   */
  public function decrement($key, $offset = 1) {
    // TODO Auto-generated method stub

  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::isEmpty()
   */
  public function isEmpty() {
    // TODO Auto-generated method stub

  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::set()
   */
  public function set($key, $data, $lifetime = 0) {
    // TODO Auto-generated method stub

  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::setMulti()
   */
  public function setMulti(array $items, $lifetime = 0) {
    // TODO Auto-generated method stub

  }

  /**
   * (non-PHPdoc)
   * @see Countable::count()
   */
  public function count() {
    // TODO Auto-generated method stub

  }

}