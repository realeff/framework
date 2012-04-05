<?php

/**
 * 系统数据存储查询器
 *
 * @author realeff
 *
 */
class SystemQuerier extends AbstractQuerier {

  public function name() {
    // TODO Auto-generated method stub
    return REALEFF_QUERIER_SYSTEM;
  }

  public function doget(array $filter, $params = array()) {
    // TODO Auto-generated method stub
    return FALSE;
  }

  public function dogetvars(array $filter, $params = array()) {
    $query = self::select('setting')
    ->fields(array('name', 'value'));
    if (isset($filter['name'])) {
      $query->where()
      ->contain('name', $params['name']);
    }

    return $query;
  }

  public function dosetvar(array $filter, $params = array()) {
    return self::insert_unique('setting')
    ->key('name', $params['name'])
    ->field('value', $params['value']);
  }

  public function dodelvar(array $filter, $params = array()) {
    $query = self::delete('setting');
    $query->where()
    ->compare('name', $params['name']);

    return $query;
  }
}


class CacheQuerier extends AbstractQuerier {

  private $_bin;

  public function __construct($bin) {
    $this->_bin = $bin;
  }

  public function name() {
    // TODO Auto-generated method stub
    return $this->_bin;
  }

  public function doget(array $filter, $params = array()) {
    // TODO Auto-generated method stub
    $query = self::select($this->_bin);
    $query->field('data')->field('serialized')
    ->where()
    ->compare('id', $params['id'])
    ->add(self::createCondition()
        ->compare('expire', 0)
        ->_OR()
        ->compare('expire', $params['expire_0'], QueryCondition::GREATER_EQUAL));

    return $query;
  }

  public function dogetmulti(array $filter, $params = array()) {
    // TODO Auto-generated method stub
    $query = self::select($this->_bin);
    $query->fields(array('id', 'data', 'serialized'))
    ->where()
    ->contain('id', $params['id'])
    ->add(self::createCondition()
        ->compare('expire', 0)
        ->_OR()
        ->compare('expire', $params['expire_0'], QueryCondition::GREATER_EQUAL));

    return $query;
  }

  public function doremove(array $filter, $params = array()) {
    $query = self::delete($this->_bin);
    $query->where()
    ->compare('id', $params['id']);

    return $query;
  }

  public function doflush(array $filter, $params = array()) {
    return self::delete($this->_bin);
  }

  public function dogc(array $filter, $params = array()) {
    $query = self::delete($this->_bin);
    $query->where()
    ->compare('expire', 0, QueryCondition::NOT_EQUAL)
    ->compare('expire', $params['expire_0'], QueryCondition::LESS);

    return $query;
  }

  public function doincrement(array $filter, $params = array()) {
    $query = self::update($this->_bin);
    $query->expression('data', 'data + :offset', array('offset' => $params['offset']))
    ->field('serialized', FALSE)
    ->where()
    ->compare('id', $params['id'])
    ->add(self::createCondition()
        ->compare('expire', 0)
        ->_OR()
        ->compare('expire', $params['expire_0'], QueryCondition::GREATER_EQUAL));

    return $query;
  }

  public function dodecrement(array $filter, $params = array()) {
    $query = self::update($this->_bin);
    $query->expression('data', 'data - :offset', array('offset' => $params['offset']))
    ->field('serialized', FALSE)
    ->where()
    ->compare('id', $params['id'])
    ->add(self::createCondition()
        ->compare('expire', 0)
        ->_OR()
        ->compare('expire', $params['expire_0'], QueryCondition::GREATER_EQUAL));

    return $query;
  }

  public function docount(array $filter, $params = array()) {
    // TODO Auto-generated method stub
    $query = self::select($this->_bin);
    if (isset($filter['expire_0'])) {
      $query->where()
      ->compare('expire', 0)
      ->_OR()
      ->compare('expire', $params['expire_0'], QueryCondition::GREATER_EQUAL);
    }

    return $query->count();
  }

  public function doset(array $filter, $params = array()) {
    // TODO Auto-generated method stub
    $fields = array('created' => $params['created'], 'expire' => $params['expire']);
    $fields['data'] = '';
    $fields['serialized'] = $params['serialized'];

    return self::insert_unique($this->_bin)
    ->key('id', $params['id'])
    ->fields($fields);
  }
}

/**
 * 会话数据存储查询器
 *
 * @author realeff
 *
 */
class SessionQuerier extends AbstractQuerier {

  public function name() {
    // TODO Auto-generated method stub
    return REALEFF_QUERIER_SESSION;
  }

  public function doget(array $filter, $params = array()) {
    // TODO Auto-generated method stub
    $query = self::select('session');
    $query->field('*')
    ->where()
    ->compare('id', $params['id']);

    return $query;
  }

  public function doread(array $filter, $params = array()) {
    $query = self::select('session');
    $query->field('data')
    ->where()
    ->compare('id', $params['id']);

    return $query;
  }

  public function dowrite(array $filter, $params = array()) {
    $query = self::insert_unique('session');
    $query->key('id', $params['id'])
    ->field('data', '')
    ->field('timestamp', $params['timestamp'])
    ->field('hostname', $params['hostname']);

    return $query;
  }

  public function dodestroy(array $filter, $params = array()) {
    $query = self::delete('session');
    $query->where()
    ->compare('id', $params['id']);

    return $query;
  }

  public function dogc(array $filter, $params = array()) {
    $query = self::delete('session');
    $query->where()
    ->compare('timestamp', $params['timestamp'], QueryCondition::LESS);

    return $query;
  }
}