// 全局变量设置
var myDomain = (function(){
    var cur_href = window.location.href;
    var cur_href_arr = cur_href.split('/');
    return cur_href.replace(cur_href_arr[cur_href_arr.length-1], '');
})();

// requirejs配置
requirejs.config({
    baseUrl: "public/js",
    shim: {
        'jquery.ui': ['jquery'],
        'overhang.min': ['jquery'],
    },
    paths : {
        jquery : 'lib/jquery',
        custom : 'lib/custom',
        'jquery.ui' : 'lib/jquery-ui',
        'overhang.min' : 'lib/overhang.min',
    },
    waitSeconds: 5,
});

// 目录中其他js直接引入
// require(['other']);

// requirejs 主逻辑
requirejs(['jquery', 'custom', 'jquery.ui'], function($, custom){
    // ===========================================================
    // 初始化操作start
    // ===========================================================
    // 配置状态
    $( "#controlgroup" ).controlgroup();

    // 操作说明
    $( "#dialog" ).dialog({
        autoOpen: false,
        width: 400,
        buttons: [
            {
                text: "Ok",
                click: function() {
                    $( this ).dialog( "close" );
                }
            },
            {
                text: "Cancel",
                click: function() {
                    $( this ).dialog( "close" );
                }
            }
        ]
    });

    $( "#dialog-link" ).click(function( event ) {
        $( "#dialog" ).dialog( "open" );
        event.preventDefault();
    });

    // 操作步骤：tab菜单
    $( "#tabs" ).tabs();

    // 折叠菜单
    $( "#accordion" ).accordion();

    //进度条初始化
    $( "#progressbar" ).progressbar({
        value: 0,
    });

    // ===========================================================
    // 初始化操作end
    // ===========================================================


    // ===========================================================
    // 函数库start
    // ===========================================================
    // 自定义函数库变量
    var functionLib = {};

    functionLib.resetExportTablesList = function(){
        $.ajax({
            url: myDomain + 'api.php?act=v3_resetExportTablesList',
            data: {},
            type: 'POST',
            dataType: 'json',
            async: false,
            success: function (data) {
                // console.log(data);
                if(data.state == -1){
                    custom.alert(data.msg + ' 错误请检查');
                    return;
                }


                if(data.data){
                    var arr = data.data;
                    var list_html = '';

                    for(var j = 0,len = arr.length; j < len; j++){
                        list_html += '<li><input id="field_name'+ j +'" type="checkbox" name="fields[]" value="'+ arr[j] +'" checked="checked"><label for="field_name'+ j +'">'+ arr[j] +'</label></li>';
                    }

                    $("#from_tables_lists").html(list_html);

                    $("#from_tables_lists li i").click(function(){
                        $(this).parent().remove()
                    });
                }
            }
        });
    };
    functionLib.saveExportCateTableConfig = function(){
        // var export_cate_table = $("#from_select_tables_lists").serializeArray();
        var export_cate_table = [];

        $("#from_tables_lists input[type='checkbox']").each(function () {
            var that = $(this);
            if(that.prop("checked")){
                export_cate_table.push(that.val());
            }
        })
        console.log(export_cate_table);
        if(0 == export_cate_table.length){
            custom.alert('请选择导出数据库数据表');
            return false;
        }

        $.ajax({
            url: myDomain + 'api.php?act=saveExportCateTable',
            data: {export_cate_table: export_cate_table},
            type: 'POST',
            dataType: 'json',
            async: false,
            success: function (data) {
                console.log(data);
                if(1 == data.state){
                    custom.alert('保存成功！', 'success');
                }else{
                    custom.alert('保存失败，请检查！');
                }
            }
        });
    };
    functionLib.saveExportDBConfig = function() {
        var formData = $("#from_cfg_form").serialize();

        $.ajax({
            url: myDomain + 'api.php?act=from_cfg_save',
            data: formData,
            type: 'POST',
            dataType: 'json',
            success: function (data) {
                console.log(data);
                if(1 == data.state){

                    //选择状态
                    functionLib.checkExportDBConfig();

                }
                // $("#messageBox").html(data.desc);
            },
            error: function (data) {

            },
            complete: function (XHR, TS) {

            }
        });
    };

    // ===========================================================
    // 函数库end
    // ===========================================================

    // 开始导入
    $( "#start-button" ).off("click").click(function( event ) {

        // custom.alert('这是一个错误的提示信息！');
        console.log(myDomain);

        var confirmStr = '确定开始导出？';

        if(!confirm(confirmStr)){
            return;
        }

        custom.start();

        event.preventDefault();
    });

    // 监听上传文件
    $("#updata-file-button").off("click").click(function(){
        var fileName = $('#access_file').val();　　　　　　　　　　　　　　　　　　//获得文件名称
        var fileType = fileName.substr(fileName.length-4,fileName.length);　　//截取文件类型,如(.xls)

        $.ajax({
            url: myDomain + 'api.php?act=uploadAccessFile',　　　　　　　　　　//上传地址
            type: 'POST',
            cache: false,
            data: new FormData($('#from_cfg_form')[0]),　　　　　　　　　　　　　//表单数据
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(data){
                console.log(data);
                if(data.state == 1){
                    //保存成功
                    custom.alert(data.msg, 'success');
                    $('#access_file').val('')
                    $('#from-db-state').removeClass('ui-state-error').addClass('ui-state-active').html('已OK');

                }else{
                    custom.alert(data.msg);
                }
            },
            error: function(xhr, status, error){
                console.log(xhr);
                console.log(status);
                console.log(error);
            },
            complete: function(xhr, status){
                console.log(xhr);
                console.log(status);
            }
        });
    });

    // 加载数据库表-列表
    $("#form-cate-cfg-reset-button").off("click").click(function( event ){
        functionLib.resetExportTablesList();
    });


    // 全选 反选
    $("#form-cate-cfg-select-button").off("click").click(function( event ){
        $("#from_tables_lists input[type='checkbox']").each(function () {
            var that = $(this);
            that.prop("checked", !that.prop("checked"));
        })
    });


    // 保存导出栏目数据表
    $("#form-cate-cfg-save-button").off("click").click(function( event ){
        functionLib.saveExportCateTableConfig();
    });

    function fileChange(base){
        $("#fileTypeError").html('');
        var fileName = $('#file_upload').val();　　　　　　　　　　　　　　　　　　//获得文件名称
        var fileType = fileName.substr(fileName.length-4,fileName.length);　　//截取文件类型,如(.xls)
        if(fileType=='.xls' || fileType=='.doc' || fileType=='.pdf'){　　　　　//验证文件类型,此处验证也可使用正则
            $.ajax({
                url: base+'/actionsupport/upload/uploadFile',　　　　　　　　　　//上传地址
                type: 'POST',
                cache: false,
                data: new FormData($('#uploadForm')[0]),　　　　　　　　　　　　　//表单数据
                processData: false,
                contentType: false,
                success:function(data){
                    if(data=='fileTypeError'){
                        $("#fileTypeError").html('*上传文件类型错误,支持类型: .xsl .doc .pdf');　　//根据后端返回值,回显错误信息
                    }
                    $("input[name='enclosureCode']").attr('value',data);
                }
            });
        }else{
            $("#fileTypeError").html('*上传文件类型错误,支持类型: .xls .doc .pdf');
        }
    }
});

//自定义模块
require(['custom'], function (custom) {
    // custom.change();
});

// 拖拽插件

// 自定义函数库区
// =====================================================================================================================
// =====================================================================================================================
