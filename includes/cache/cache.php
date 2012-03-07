<?php

/**
 * 缓存存储器接口
 * 
 * @author realeff
 *
 */
interface CacheInterface extends Countable {
  
  /**
   * 构造器
   * 
   * @param array $options
   */
  public function __construct(array $options = array());
  
  /**
   * 获取一个元素数据
   * 
   * @param string $key
   * 
   * @return mixed
   *   返回存储元素的值或者在其他情况下返回FALSE。
   */
  public function get($key);
  
  /**
   * 获取多个元素数据
   * 
   * @param array $keys
   * 
   * @return array
   *   返回检索到的元素的数组，失败时返回FALSE。
   */
  public function getMulti(array $keys);
  
  /**
   * 存储一个元素数据
   * 
   * @param string $key 存储键名
   * @param mixed $data 存储数据
   * @param int $lifetime 生存期
   * 
   * @return bool
   *   操作成功返回TRUE，失败返回FALSE。
   */
  public function set($key, $data, $lifetime = 0);
  
  /**
   * 存储多个元素数据
   * 
   * @param array $items 存放键/数据对数组
   * @param int $lifetime 生成期
   * 
   * @return bool
   *   操作成功返回TRUE，失败返回FALSE。
   */
  public function setMulti(array $items, $lifetime = 0);
  
  /**
   * 减少数值元素的值，将指定元素的值减少offset。
   * 如果指定的key对应的元素不是数值类型并且不能被转换为数值， 会将此值修改为offset。
   *
   * @param string $key 将要减少值的元素key
   * @param int $offset 将指定元素值减少多少
   *
   * @return int
   *   返回新的元素值，失败时返回FALSE。
   */
  public function decrement($key, $offset = 1);
  
  /**
   * 增加数值元素的值，将指定元素的值增加offset。
   * 如果指定的key对应的元素不是数值类型并且不能被转换为数值， 会将此值修改为offset。
   * 
   * @param string $key 将要增加值的元素key
   * @param int $offset 将指定元素值增加多少
   * 
   * @return int
   *   返回新的元素值，失败时返回FALSE。
   */
  public function increment($key, $offset = 1);
  
  /**
   * 删除一个元素数据
   * 
   * @return bool
   *   成功时返回TRUE，或者在失败时返回FALSE。
   */
  public function delete($key);
  
  /**
   * 清除所有缓存数据
   * 
   * @return bool
   *   成功时返回TRUE，或者在失败时返回FALSE。
   */
  public function flush();
  
  /**
   * 销毁过期的缓存数据
   */
  public function gc();
  
  /**
   * 检查此缓存容器是否空的
   * 
   * @return bool
   *   如果为空返回TRUE，否则返回FALSE。
   */
  public function isEmpty();
}





function cache_get($key, $bin = 'cache') {
  
}

function cache_get_multi(array $keys, $bin = 'cache') {
  
}

function cache_set($key, $data, $lifetime = 0, $bin = 'cache') {
  
}

function cache_set_multi(array $items, $lifetime = 0, $bin = 'cache') {
  
}

function cache_clear_all($key = NULL, $bin = NULL) {
  
}

