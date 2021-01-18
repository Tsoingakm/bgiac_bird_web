$(function() {

    if (!placeholderSupport()) {
        $('[placeholder]').focus(function() {
            var input = $(this);
            if (input.val() == input.attr('placeholder')) {
                input.val('');
                input.removeClass('placeholder');
            }
        }).blur(function() {
            var input = $(this);
            if (input.val() == '' || input.val() == input.attr('placeholder')) {
                input.addClass('placeholder');
                input.val(input.attr('placeholder'));
            }
        }).blur();
    };


})
//搜索
function searchForm() {
    loading("搜索中...");
    $("#searchForm").submit();
}
//多行删除
function delMut(url) {
    var chkList = $(".del_checkbox");
    var aids = "";
    var dot = "";
    for (var i = 0; i < chkList.length; i++) {
        if ($(chkList[i]).attr("checked")) {
            aids += dot + $(chkList[i]).val();
            dot = ",";
        }
    }
    if (aids == "") {
        msgFaild("请先选择要删除的记录");
        return false;
    }

    //询问框
    layer.confirm('删除后不可恢复，确定要删除这些记录吗？', {
        btn: ['确定', '取消'] //按钮
    }, function() {
        loading("正在删除...");
        var query = new Object();
        query.ids = aids;
        $.post(url, query, function(data) {
            layer.closeAll();
            if (data.status == 1) {
                //alert(data.info);
                msgOK(data.info);
                window.location.reload();
            } else {
                msgFaild(data.info);
            }
        }, "json");
    }, function() {
        //取消时操作
    });
}

function placeholderSupport() {
    return 'placeholder' in document.createElement('input');
}

function msgOK(msg) {
    layer.msg(msg, {
        icon: 1,
        shade: [0.5, '#000'],
        shadeClose: true
    });
}

function msgFaild(msg) {
    layer.msg(msg, {
        icon: 2,
        shade: [0.5, '#000'],
        shadeClose: true
    });
}

function loading(msg) {
    layer.msg(msg, {
        icon: 16,
        time: 0,
        shade: [0.5, '#000']
    });
}

/**
 * 跳转
 * @param {Object} url
 * @param {Object} time
 */
function hrefTo(url, time) {
    if (time == 0) {
        window.location.href = url;
    } else {
        setTimeout("hrefTo('" + url + "',0)", time);
    }
}
/**
 * 关闭弹窗
 */
function closeAll(){
    layer.closeAll();
}

function open_with_full_screen(title, url){
    if (title == null || title == '') {
        title=false;
    };
    if (url == null || url == '') {
        url="404.html";
    };
    layer.open({
        type: 2,
        area: ['100%','100%'],
        fix: false, //不固定
        maxmin: true,
        title: title,
        content: url
    });
}
