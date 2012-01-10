<?php

/**
 * 这是一个定义程序执行入口的文件，获取系统服务和请求链接的地方。
 */

/**
 * 安装RealEff系统的根目录
 */
define('RESYS_ROOT', dirname(dirname(__FILE__)));

// 装载引导程序
include_once RESYS_ROOT .'/includes/bootstrap.php';
// 引导程序
realeff_bootstrap();

// 执行服务程序，传入参数直接进入相应服务模块调取服务，减少中间环节。
realeff_main('admin');