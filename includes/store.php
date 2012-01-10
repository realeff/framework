<?php

// 建立数据链接接口
// 建立数据处理基础类

/**
 * 加载存储器驱动及其目录中的文件
 *
 * @param string $driver 驱动名称
 * @param array $files 文件名称
 */
function readeff_load_storedriver($driver, array $files = array()) {
  $driver_base_path = RESYS_ROOT ."/storage/$driver";
  
  foreach ($files as $file) {
    // Load the base file first so that classes extending base classes will
    // have the base class loaded.
    foreach (array(RESYS_ROOT ."/storage/$file", "$driver_base_path/$file") as $filename) {
      // The OS caches file_exists() and PHP caches require_once(), so
      // we'll let both of those take care of performance here.
      if (file_exists($filename)) {
        require_once $filename;
      }
    }
  }
}


function db_query() {
  
}



