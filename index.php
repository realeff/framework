<?php

/**
 * 这是一个定义程序执行入口的文件，获取系统服务和请求链接的地方。
 */

define('RESYS_DEBUG', TRUE);
define('RESYS_ROOT', dirname(__FILE__));
// 装载引导程序
include_once RESYS_ROOT .'/includes/bootstrap.php';
// 引导程序
realeff_bootstrap();

// 执行服务程序，传入参数直接进入相应服务模块调取服务，减少中间环节。
realeff_main('page');
//realeff_main('ajax');
//realeff_main('xml');
//realeff_main('service');
//realeff_main('rss');
//realeff_main('wsdl');
//realeff_main('uddi');
//realeff_main('addr');
//realeff_main('admin');
