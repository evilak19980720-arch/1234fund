$('#pass').unbind('click').click(function() {
    var id = $(this).data('id');
    swal({
        title: "确认吗？",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: '通过',
        showLoaderOnConfirm: true,
        cancelButtonText: '拒绝',
        preConfirm: function() {
            console.log('p')
        }
    });
});

$('#refuse').unbind('click').click(function() {
    var id = $(this).data('id');
    swal({
        title: "确认拒绝该用户的退款申请吗？",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: "确认",
        showLoaderOnConfirm: true,
        cancelButtonText: "取消",
        preConfirm: function() {
            console.log('r')
            /*$.ajax({
                method: 'get',
                url: '/admin/refuse/' + id,
                success: function (data) {
                    console.log(data);
                    $.pjax.reload('#pjax-container');
                    if(data.code){
                        swal(data.msg, '', 'success');
                    }else{
                        swal(data.msg, '', 'error');
                    }
                }
            });*/
        }
    });
});
