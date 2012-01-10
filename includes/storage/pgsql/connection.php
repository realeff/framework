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