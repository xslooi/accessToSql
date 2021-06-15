<?php
/**
 * 此文件为程序入口文件-ajax接口文件
 * ---------------------------------------------------------
 * # 版本功能说明：
 *
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
set_time_limit(0);  //不限制 执行时间
date_default_timezone_set('Asia/Shanghai');
header("content-Type: text/javascript; charset=utf-8"); //语言强制
header('Cache-Control:no-cache,must-revalidate');
header('Pragma:no-cache');
//===================================================================
//文件说明区
//===================================================================

//echo dirname(__FILE__);exit;
//===================================================================
//定义常量区
//===================================================================
define('IS_MAGIC_QUOTES_GPC', get_magic_quotes_gpc()); //todo 转义常量暂未使用
define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) ? true : false);
define('ROOT_DIR', str_replace("\\", '/', dirname(__FILE__)));
define('ROOT_WEB', str_replace(strrchr(ROOT_DIR, '/'), '', ROOT_DIR));
define('EXPORT_SQL_PATH', ROOT_DIR . '/export.sql');
//echo ROOT_WEB;

//echo EXPORT_SQL_PATH;
//===================================================================
//路由逻辑区
//===================================================================
$respondData = array(
    'id' => 0,
    'state' => 0,
    'msg' => 'fail',
    'data' => null
);

//todo 此处过滤数据
$receiveData = $_POST;
if(!IS_MAGIC_QUOTES_GPC){

}

$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
$action = 'act_' . $act;


if(IS_AJAX)
{
//    $respondData = array(
//        'id' => 1,
//        'state' => 1,
//        'msg' => 'success',
//        'data' => $receiveData
//    );

    if(function_exists($action))
    {
        $respondData = call_user_func($action, $receiveData);
    }

    respondMsg($respondData);
}
else
{
    respondMsg($respondData);
}

//===================================================================
//函数库区
//===================================================================
function respondMsg($data)
{
    exit(json_encode($data));
}

function configFileFormat($data)
{
    return "<?php\r\n" . "return " . var_export($data, true) . ";";
}

function config($name, $data=array())
{
    //todo 此处用数组还是json文件？
    $path = ROOT_DIR . '/config/' . $name . '.php';

    if(file_exists($path)) {
        if(empty($data)){
            $result = include($path);
            return $result;
        }else{
            file_put_contents($path, configFileFormat($data));
            return true;
        }
    }else{
        file_put_contents($path, configFileFormat($data));
        return true;
    }

}


//===================================================================
//动作函数区
//===================================================================
function act_from_cfg_save($data)
{

    config('from_db', $data);
    $rs = config('from_db');

    return array(
        'id' => 1,
        'state' => 1,
        'msg' => 'success',
        'data' => $rs
    );
}


//2018年12月14日13:36:50 xslooi 添加 V3 函数
function act_start($data) {
    $step = isset($data['step']) ? intval($data['step']) : 0;

    $export_cate_table = config('export_cate_table');
    $total = count($export_cate_table['export_cate_table']);
    $table_name = isset($export_cate_table['export_cate_table'][$step]) ? $export_cate_table['export_cate_table'][$step] : '';

    // 首次处理
    if(0 == $step){
      $export_header = export_header();
      file_put_contents(EXPORT_SQL_PATH, $export_header);
    }

    if($total > $step){
        export_mssql_table_struct($table_name);
        export_mssql_table_data($table_name);
    }

    // 结尾处理
    if($total == $step){
        // todo 可以在此处处理数据库自动递增数值设置
        export_mssql_table_alter($export_cate_table['export_cate_table']);
    }
//    var_dump($total);
//    exit;
    $rs = array('total' => $total);

    if($step > $total){
        return array(
            'id' => 6,
            'state' => 0,
            'msg' => '恭喜，导出完成！',
            'data' => $rs
        );
    }
    else{
        return array(
            'id' => 6,
            'state' => 1,
            'msg' => 'success',
            'data' => $rs
        );
    }
}


function act_saveExportCateTable($data){

    $rs = config('export_cate_table', $data);

    return array(
        'id' => 9,
        'state' => 1,
        'msg' => '恭喜，保存成功！',
        'data' => $rs
    );
}


//2018年12月14日09:11:22 xslooi 添加 V3函数
function act_uploadAccessFile(){
    $rs = null;
    $upload_name = 'access_file';

    //简单的文件上传处理
    if(isset($_FILES[$upload_name]) && empty($_FILES[$upload_name]['error'])){
        $save_path = ROOT_DIR . '/config/' . md5($_FILES[$upload_name]['name']) . '.' . pathinfo($_FILES[$upload_name]['name'], PATHINFO_EXTENSION);
        $upload_state = move_uploaded_file($_FILES[$upload_name]['tmp_name'], $save_path);
        if($upload_state){
//            上传成功删除旧文件
          $old_path = config('upload_access_path');
          if(file_exists($old_path['path']) && $save_path != $old_path['path']){
              unlink($old_path['path']);
          }

          config('upload_access_path', array('path' => $save_path, 'name'=>$_FILES[$upload_name]['name']));

            return array(
                'id' => 20,
                'state' => 1,
                'msg' => '恭喜上传成功',
                'data' => 'ok'
            );
        }else{
            return array(
                'id' => 20,
                'state' => -1,
                'msg' => '上传成功移动失败请检查',
                'data' => $rs
            );
        }

    }else{
        return array(
            'id' => 20,
            'state' => -1,
            'msg' => '上传失败请检查',
            'data' => $rs
        );
    }
}


function act_v3_resetExportTablesList(){
    $rs = array();
    $tables = get_mssql_tables();
    foreach($tables as $item){
        $rs[] = $item['TABLE_NAME'];
    }

    return array(
        'id' => 20,
        'state' => 1,
        'msg' => '加载成功',
        'data' => $rs
    );
}

//===================================================================
//其他作用区
//===================================================================

//======================================================================================================================
/**
 * 处理ANSII编码到utf-8编码，处理\0结尾符
 * @param string $string 字符串
 * @return false|string
 */
function convert_utf8($string){
    $result = '';

    if(!empty($string)){
        $result = substr($string, 0, strpos($string, "\0"));
        $result = iconv('gb2312', "utf-8//IGNORE", $result);
    }

    return $result;
}


/**
 * 映射mysql字段设计属性
 * @param $type
 * @return mixed
 */
function mapping_mysql_property($property){

    $result = array(
        'type' => 'varchar',
        'size' => ' (255)',
    );

    $type_mapping = array(
        'COUNTER' => array('type'=>'int', 'size'=>' UNSIGNED AUTO_INCREMENT PRIMARY KEY '),
        'CURRENCY' => array('type'=>'decimal', 'size'=>' (10)'),
        'VARCHAR' => array('type'=>'varchar', 'size'=>' (' . $property['COLUMN_SIZE'] . ')'),
        'DOUBLE' => array('type'=>'double', 'size'=>' (' . $property['COLUMN_SIZE'] . ')'),
        'INTEGER' => array('type'=>'int', 'size'=>' (' . $property['COLUMN_SIZE'] . ')'),
        'LONGCHAR' => array('type'=>'text', 'size'=>' '),
        'DATETIME' => array('type'=>'varchar', 'size'=>' (' . $property['COLUMN_SIZE'] . ')'),
        'BIT' => array('type'=>'tinyint', 'size'=>' (1) UNSIGNED'),
        'BYTE' => array('type'=>'tinyint', 'size'=>' (3) UNSIGNED'),
    );

    if(isset($type_mapping[$property['TYPE_NAME']])){
        $result = $type_mapping[$property['TYPE_NAME']];
    }

    return $result;
}


/**
 * 获取Access数据库连接
 * @return false|resource
 */
function get_mssql_odbc_connect(){
    if(!function_exists('odbc_connect')){
        $error = array(
            'id' => 20,
            'state' => -1,
            'msg' => ' odbc 扩展未开启，请开启 php_pdo_odbc 扩展 ！',
            'data' => array()
        );
        exit(json_encode($error));
    }

    $data_path = config('upload_access_path');
    $connstr = "DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=" . realpath($data_path['path']);
//    $connstr = "DRIVER={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=" . realpath($data_path['path']);
//    $connstr = "DRIVER={Microsoft Access-Treiber (*.mdb)}; DBQ=" . realpath($data_path['path']);
//    $connstr = "DRIVER={Driver do Microsoft Access (*.mdb)}; DBQ=" . realpath($data_path['path']);

    $connid = odbc_connect($connstr,"","",SQL_CUR_USE_ODBC);
//    $connid = odbc_connect($connstr,"","",SQL_CUR_USE_DRIVER);
//    $connid = odbc_connect($connstr,"","",SQL_CUR_USE_IF_NEEDED);

    if(!$connid){
        $error = array(
            'id' => 20,
            'state' => -1,
            'msg' => ' odbc 数据库连接失败，请检查！odbc_error:[' . odbc_error() . '] - odbc_errormsg:[' . odbc_errormsg() . ']',
            'data' => array()
        );
        exit(json_encode($error));
    }

    return $connid;
}


/**
 * 获取Access表-已排除系统表
 * @return array
 */
function get_mssql_tables(){
    $result = array();
    $connid = get_mssql_odbc_connect();
//    odbc_setoption($connid,  2, 0, 30);

    $resource = odbc_tables($connid);

//    $sql = "SELECT TOP 1 * FROM admin";
//    $qualifier = odbc_exec($connid, $sql);
//    $sql = "SELECT * FROM admin ";
//    $resource = odbc_exec($connid, $sql);

//    $rs = odbc_tableprivileges($connid, $resource, '', '');
//    $rs = odbc_tableprivileges($connid, '', '', '');
//    $rs = odbc_statistics($connid, '7503314d1e4dd35b942b8d1f75b0f28d', 'Administrators (SHUNFEIJISHU\Administrators)', 'admin', SQL_INDEX_UNIQUE, SQL_ENSURE);
//    $rs = odbc_columns($connid, '7503314d1e4dd35b942b8d1f75b0f28d', '', 'Pics', '%');
//    $rs = odbc_gettypeinfo($connid);
//    $rs = odbc_field_type($qualifier, 1);
//      $rs = get_data_source_info();
//    $rs = get_mssql_fields_info('admin');
//      var_dump($rs);
//         $rs = gettypeinfo();
//    $rs = odbc_procedures($connid);
//    $rs = odbc_procedurecolumns($connid);
//    $rs = odbc_primarykeys($connid, '7503314d1e4dd35b942b8d1f75b0f28d', 'Admin', 'NewsInfo2');

//   $rs = get_mssql_table_info('Pics');
//    $result = odbc_result_all($rs);
//    var_dump($result);

//    $sql = "SELECT TOP 1 * FROM Pics";
//    $resource = odbc_exec($connid, $sql);
//    $resource = $rs;
//
//    if($resource){
//        $dataSet = array();
//        while($row = odbc_fetch_array($resource)){
//            $dataSet[] = $row;
//        }
//
//        if(isset($dataSet[0])){
//            $field_names = array_keys($dataSet[0]);
//
//            foreach($field_names as $key=>$value){
//
//                $info = array(
//                    'name' => $value,
//                    'len' => odbc_field_len($resource, $key + 1),
//                    'type' => odbc_field_type($resource, $key + 1),
//                    'num' => odbc_field_num($resource, $value),
//                    'scale' => odbc_field_scale($resource, $key + 1),
//                );
//
//                $result[] = $info;
//            }
//        }
//    }
//
//    $rs = $result;

//    $pages = array();
//    while (odbc_fetch_into($resource, $pages)) {
//        $rss = odbc_tableprivileges($connid, '7503314d1e4dd35b942b8d1f75b0f28d', '%', '');
//var_dump($rss);
//var_dump($pages);
////        echo $pages[3] . "\n"; // presents all fields of the array $pages in a new line until the array pointer reaches the end of array data
//    }

//    $resource = $rs;
//    if($resource){
//        $dataSet = array();
//        while($row = odbc_fetch_array($resource)){
//            $dataSet[] = $row;
//        }
//        var_dump($dataSet);
//    }

//    var_dump($rs);

//   $table_data = export_mssql_table_data('MSysNavPaneGroupToObjects');
//   var_dump($table_data);

    if($resource){
        $dataSet = array();
        while($row = odbc_fetch_array($resource)){
            if('SYSTEM TABLE' != $row['TABLE_TYPE'] && '~TMP' != substr($row['TABLE_NAME'], 0, 4)){
                $dataSet[] = $row;
            }
        }

        $result = $dataSet;
    }

    if($connid){
        odbc_close($connid);
    }

    return $result;
}


/**
 * 根据表中数据调用odbc_field_* 函数获得表信息
 * @param $table_name
 * @return array
 */
function get_mssql_fields_info($table_name){
    $result = array();
    $index = 1;
    $connid = get_mssql_odbc_connect();

    $sql = "SELECT TOP 1 * FROM {$table_name}";
    //    ini_set("odbc.defaultlrl", "100000"); //解决字段字符长度4096 方法一
    $resource = odbc_exec($connid, $sql);
    if($resource){
        odbc_longreadlen($resource, "100000"); //解决字段字符长度4096 方法二 最大为1亿+
        $dataSet = array();
        while($row = odbc_fetch_array($resource)){
            $dataSet[] = $row;
        }

        if(isset($dataSet[0])){
            $field_names = array_keys($dataSet[0]);

            foreach($field_names as $key=>$value){
                $info = array(
                    'name' => $value,
                    'len' => odbc_field_len($resource, $key + 1),
                    'type' => odbc_field_type($resource, $key + 1),
                    'num' => odbc_field_num($resource, $value),
                    'scale' => odbc_field_scale($resource, $key + 1),
                );

                $result[] = $info;
            }
        }
    }

    if($connid){
        odbc_close($connid);
    }

    return $result;
}


/**
 * 根据 odbc_columns 函数获得表信息
 * @param $table_name
 * @return array
 */
function get_mssql_table_info($table_name){
    $result = array();
    $connid = get_mssql_odbc_connect();
    $data_path = config('upload_access_path');
    $qualifier = pathinfo($data_path['path'], PATHINFO_FILENAME);

    $resource = odbc_columns($connid, $qualifier, '', $table_name, '%');

    if($resource){
        odbc_longreadlen($resource, "100000"); //解决字段字符长度4096 方法二 最大为1亿+

        $dataSet = array();
        while($row = odbc_fetch_array($resource)){
            $dataSet[] = $row;
        }

        if(isset($dataSet[0])){
            $result = $dataSet;
        }
    }

    return $result;
}


/**
 * 导出表结构-写入文件
 * TODO 未处理字段属性映射
 * @param $table_name
 */
function export_mssql_table_struct($table_name){
    $header = '-- --------------------------------------------------------

--
-- Table structure for table `' . $table_name . '`
--' . str_repeat(PHP_EOL, 2);

    $table_info = get_mssql_table_info($table_name);

    $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (" . PHP_EOL;

    foreach($table_info as $item){
        $property = mapping_mysql_property($item);

        $sql .= "  `{$item['COLUMN_NAME']}` ";
        $sql .= $property['type'];
        $sql .= $property['size'];
        $sql .= ('NO' == $item['IS_NULLABLE'] ? ' NOT NULL ' : ' ') . " COMMENT '";
        $sql .= convert_utf8($item['REMARKS']);
        $sql .= "'," . PHP_EOL;
    }

    $sql = rtrim(trim($sql), ',');

    $sql .= PHP_EOL . ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='';";

    $data_buffer = $header . $sql . str_repeat(PHP_EOL, 3);

    file_put_contents(EXPORT_SQL_PATH, $data_buffer, FILE_APPEND);
}


/**
 * 导出表数据-写入文件
 * TODO 注意没有处理大数据表分段处理
 * @param $table_name
 * @return array
 */
function export_mssql_table_data($table_name){
    $header = '--
-- Dumping data for table `' . $table_name . '`
--' . str_repeat(PHP_EOL, 2);

    $result = array();
    $connid = get_mssql_odbc_connect();

    $sql = "SELECT * FROM " . $table_name;
    $resource = odbc_exec($connid, $sql);
    if($resource){
        odbc_longreadlen($resource, "100000"); //解决字段字符长度4096 方法二 最大为1亿+
        $dataSet = array();
        while($row = odbc_fetch_array($resource)){
            $temp = array();
            foreach($row as $key=>$val){
                $temp[$key] = iconv('gb2312', "utf-8//IGNORE", $val);
            }

            $dataSet[] = $temp;
        }

        $result = $dataSet;
    }

    if($connid){
        odbc_close($connid);
    }

    // 表字段
    $table_info = get_mssql_table_info($table_name);
    $table_field = array();
    foreach($table_info as $item){
        $table_field[] = $item['COLUMN_NAME'];
    }
    $table_field = implode(',', $table_field);

    $sql_one = 'INSERT INTO ' . $table_name . ' (' . $table_field . ') VALUES ' . PHP_EOL;

    if(isset($result[0])){
        $data_buffer = '';
        foreach($result as $item){
            $sql_one .= '(' . "'";

            foreach($item as $key=>$value){
                $sql_one .= addslashes($value) . "','";
            }

            $sql_one = substr($sql_one, 0,-2);
            $sql_one .= '),' . PHP_EOL;
        }


        $sql_one = substr($sql_one, 0,-3);
        $sql_one .= ';' . PHP_EOL;

        $data_buffer .= $sql_one;
        $data_buffer .= str_repeat(PHP_EOL, 2);

        file_put_contents(EXPORT_SQL_PATH, $data_buffer, FILE_APPEND);
    }

    return $result;
}


/**
 * 导出一些修改表结构的SQL
 * @param $tables
 */
function export_mssql_table_alter($tables){
    $sql_alter = '';
    $connid = get_mssql_odbc_connect();

    foreach($tables as $table){
        $auto_increment = 0;
        $table_info = get_mssql_table_info($table);
        foreach($table_info as $item){

            if('COUNTER' == $item['TYPE_NAME']){
                $sql = "SELECT MAX({$item['COLUMN_NAME']}) AS count FROM " . $table;
                $resource = odbc_exec($connid, $sql);
                if($resource){
                    $row = odbc_fetch_array($resource);
                    $auto_increment = $row['count'];
                }

                if(0 < $auto_increment){

                    $sql_alter .= "--
-- AUTO_INCREMENT for table `{$table}`
--
ALTER TABLE `{$table}`
  MODIFY `{$item['COLUMN_NAME']}` int({$item['COLUMN_SIZE']}) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=" . ($auto_increment+1) . ";" . PHP_EOL;

                }

            }
        }

    }

    if(!empty($sql_alter)){
        $sql_alter = PHP_EOL . '--
-- AUTO_INCREMENT for dumped tables
--' . PHP_EOL . PHP_EOL . $sql_alter;

        file_put_contents(EXPORT_SQL_PATH, $sql_alter, FILE_APPEND);
    }

    if($connid){
        odbc_close($connid);
    }
}

/**
 * 获取windows系统的数据源信息列表
 * @return array
 */
function get_data_source_info(){
    $result = array();
    $connid = get_mssql_odbc_connect();
    $resource = odbc_data_source( $connid, SQL_FETCH_FIRST );
    while($resource)
    {
        $result[] = $resource;
        $resource = odbc_data_source( $connid, SQL_FETCH_NEXT );
    }

    return $result;
}


/**
 * 获取数据库数据源支持的数据类型及详细信息
COUNTER  -  Autoincrement
VARCHAR  -  Text
LONGCHAR  -  Memo
INTEGER  -  Number
DATETIME  -  Date/Time
CURRENCY  -  Currency
BIT  -  TRUE/FALSE
LONGBINARY  -  OLE-Object
LONGCHAR  -  Hyperlink
DOUBLE - Double Number
BYTE - Byte Number
 * @return array
 */
function gettypeinfo(){
    $result = array();
    $connid = get_mssql_odbc_connect();
    $resource = odbc_gettypeinfo($connid);
    if($resource) {
        odbc_longreadlen($resource, "100000"); //解决字段字符长度4096 方法二 最大为1亿+
        $dataSet = array();
        while ($row = odbc_fetch_array($resource)) {
            $dataSet[] = $row;
        }

        if($dataSet){
            $result = $dataSet;
        }
    }

    return $result;
}


/**
 * 导出文件中的头部说明
 * @return string
 */
function export_header(){
    $data_path = config('upload_access_path');
    $db_name = pathinfo($data_path['path'], PATHINFO_FILENAME);

    $result = '-- AccessToSql SQL Dump
-- version 1.0.0
-- Author: xslooi
--
-- Host: ' . $_SERVER['HTTP_HOST'] . '
-- Generation Time: ' . date('Y-m-d H:i:s') . ' 
-- Server version: ' . $_SERVER['SERVER_SOFTWARE'] . '
-- PHP Version: ' . PHP_VERSION . '
--
-- Database: `' . $db_name . '`
--

';

    return $result;
}