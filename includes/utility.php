<?php
/**
 *
 */


/**
 * 获取客户端IP地址
 *
 * @return string
 */
function ip_address() {
  $ip_address = &realeff_static(__FUNCTION__);

  if (!isset($ip_address)) {
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
      $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    }
    else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

      $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

      // 删除IPs空格，它们可能由“,”和空格组成分隔符。
      $forwarded = array_map('trim', $forwarded);

      foreach ($forwarded AS $xip) {
        if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
          $ip_address = $xip;
          break;
        }
      }
    }
  }

  return $ip_address;
}

/**
 * 转换一个标准IP地址使用子网掩码后的网络地址
 *
 * @param string $ip 标准IP地址
 * @param mixed $mask 子网掩码
 *
 * @return int
 */
function ip2long_mask($ip, $mask = NULL) {
  $ip = strtr($ip, '*', '0');

  if (!isset($mask) && FALSE !== strpos($ip, '/')) {
    $ip = strtok($ip, '/');
    $mask = strtok('/');
  }

  if (empty($mask)) {
    return ip2long($ip);
  }

  $ip_dec = ip2long($ip);
  if (FALSE === $ip_dec) {
    return FALSE;
  }

  $mask_dec = is_numeric($mask) ? intval($mask) : ip2long($mask);
  if (FALSE === $mask_dec) {
    return FALSE;
  }

  if ($mask_dec > 0 && $mask_dec <= 32) {
    return $ip_dec & (0xFFFFFFFF << (32 - $mask_dec));
  }

  return $ip_dec & $mask_dec;
}


function realeff_is_network($ip, $network) {
  if ($ip == $network) {
    return TRUE;
  }

  if (FALSE !== strpos($network, '/')) {
    $net = strtok($network, '/');
    $mask = strtok('/');

    return ip2long_mask($ip, $mask) == ip2long_mask($net, $mask);
  }
  else if (FALSE !== strpos($network, '-')) {
    $start = trim(strtok($network, '-'));
    $start = (float)sprintf('%u', ip2long_mask($start));
    $end = trim(strtok('-'));
    $end = (float)sprintf('%u', ip2long_mask($end));
    if ($start == $end) {
      return FALSE;
    }

    $ip = (float)sprintf('%u', ip2long($ip));

    return ($ip >= $start && $ip <= $end) || ($ip >= $start && $ip == 0);
  }

  return FALSE;
}

/**
 * Detect whether the current script is running in a command-line environment.
 */
function realeff_is_cli() {
  return (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));
}

/**
 * Returns a string of highly randomized bytes (over the full 8-bit range).
 *
 * This function is better than simply calling mt_rand() or any other built-in
 * PHP function because it can return a long string of bytes (compared to < 4
 * bytes normally from mt_rand()) and uses the best available pseudo-random source.
 *
 * @param $count
 *   The number of characters (bytes) to return in the string.
 */
function realeff_random_bytes($count)  {
  // $random_state does not use drupal_static as it stores random bytes.
  static $random_state, $bytes;
  // Initialize on the first call. The contents of $_SERVER includes a mix of
  // user-specific and system information that varies a little with each page.
  if (!isset($random_state)) {
    $random_state = print_r($_SERVER, TRUE);
    if (function_exists('getmypid')) {
      // Further initialize with the somewhat random PHP process ID.
      $random_state .= getmypid();
    }
    $bytes = '';
  }
  if (strlen($bytes) < $count) {
    // /dev/urandom is available on many *nix systems and is considered the
    // best commonly available pseudo-random source.
    if ($fh = @fopen('/dev/urandom', 'rb')) {
      // PHP only performs buffered reads, so in reality it will always read
      // at least 4096 bytes. Thus, it costs nothing extra to read and store
      // that much so as to speed any additional invocations.
      $bytes .= fread($fh, max(4096, $count));
      fclose($fh);
    }
    // If /dev/urandom is not available or returns no bytes, this loop will
    // generate a good set of pseudo-random bytes on any system.
    // Note that it may be important that our $random_state is passed
    // through hash() prior to being rolled into $output, that the two hash()
    // invocations are different, and that the extra input into the first one -
    // the microtime() - is prepended rather than appended. This is to avoid
    // directly leaking $random_state via the $output stream, which could
    // allow for trivial prediction of further "random" numbers.
    while (strlen($bytes) < $count) {
      $random_state = hash('sha256', microtime() . mt_rand() . $random_state);
      $bytes .= hash('sha256', mt_rand() . $random_state, TRUE);
    }
  }
  $output = substr($bytes, 0, $count);
  $bytes = substr($bytes, $count);
  return $output;
}

/**
 * Calculate a base-64 encoded, URL-safe sha-256 hmac.
 *
 * @param $data
 *   String to be validated with the hmac.
 * @param $key
 *   密钥字符
 *
 * @return
 *   返回一个基于base-64编码的SHA-256的HMAC，并且将字符+替换成字符-，字符/替换成字符_以及把字符=删除。
 */
function realeff_hmac_base64($data, $key) {
  $hmac = base64_encode(hash_hmac('sha256', $data, $key, TRUE));
  // Modify the hmac so it's safe to use in URLs.
  return strtr($hmac, array('+' => '-', '/' => '_', '=' => ''));
}

/**
 * 计算一个base-64编码，URL安全的SHA-256哈希。
 *
 * @param $data
 *   哈希字符串
 *
 * @return
 *   返回一个基于base-64编码的sha-256哈希码，并且将字符+替换成字符-，字符/替换成字符_以及把字符=删除。
 */
function realeff_hash_base64($data) {
  $hash = base64_encode(hash('sha256', $data, TRUE));
  // Modify the hash so it's safe to use in URLs.
  return strtr($hash, array('+' => '-', '/' => '_', '=' => ''));
}

/**
 * 将内容中的特殊字符转换成HTML显示字符
 *
 * 并且验证字符串为UTF-8字符，以防止跨站点脚本攻击。
 *
 * @param $text
 *   检查并转换这个文本内容
 *
 * @return string
 *   返回一个HTML安全版本的内容，或者内容不是有效的UTF-8字符则返回空字符串。
 */
function check_plain($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}


