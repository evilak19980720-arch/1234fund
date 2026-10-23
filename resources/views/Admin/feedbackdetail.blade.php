<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        *{
            margin: 0;
            padding: 0;
        }
        body{
            font-size: 14px;
        }
        .triangle{
            background-color: #ebebe9;
            padding: 0.5rem;
            width: 100%;
            height: 600px;
            overflow-y: auto;//竖直方向有滚动条，如果想要横向的，可以把y改为x
        }
        .triangle ul{
            padding: 10px;
        }
        .triangle li{
            padding: 5px;
            margin-bottom: 10px;
        }
        .triangle li span{
            position: relative;
            border-radius: 7px;
            background-color: #a6e860;
            padding: 6px 10px 8px 10px;
            z-index: 1;
        }
        .triangle .textLeft span{
            background-color: white;
        }
        .triangle li.textLeft:before{
            content: url("images/tx1.jpg");
            box-sizing: border-box;
            position: relative;
            left: -10px;
            top: 9px;
        }
        .triangle li.textLeft span:before{
            content: "";
            display: block;
            width: 0;
            height: 0;
            border: 8px solid transparent;
            border-right: 8px solid white;
            position: absolute;
            top: 8px;
            left: -16px;
        }
        .triangle li.textRight:after{
            width: 30px;height: 30px;
            content: url("1");
            background-size:cover;
            box-sizing: border-box;
            position: relative;
            right: -10px;
            top: 9px;

        ;
        }
        .triangle li.textRight span:after{
            content: "";
            display: block;
            width: 0;
            height: 0;
            border: 8px solid transparent;
            border-left: 8px solid #a6e860;
            position: absolute;
            top: 8px;
            right: -16px;
        }
        li{
            list-style: none;
        }
        .textRight{
            text-align: right;
        }

        .replay{
            height: 100px;
            border-color:#00a7d0;
        }
        #content{border-color:#00a7d0;}
    </style>
</head>
<body>
<div class="triangle" id="scrolldIV">
    <ul>
        <?php $a=$list[0]['user_id'];foreach($list as $value){?>
            <li class="<?php if ($a==$value['user_id']){ echo 'textLeft';}else{echo 'textRight';}?>">
                <?php if($value['type']==2){?>
                <span><a href="<?php echo $value['content'];?>" target="_blank"><img width="100px" height="100px" src="<?php echo $value['content'];?>"> </a></span></span>
                <?php }else{?>
                <span><?php echo $value['content'];?></span>
                <?php }?>
            </li>
        <?php }?>
    </ul>

</div>
<form action="/admin/feedback-detail/store" method="post">
<input name="fid" type="hidden" value="{{$fid}}">
<div class="replay" id="replay">
    <textarea class="input-group" name="content" id="content" required  style="width: 100%;height: 100%"></textarea>
</div>
<br>
<button id="tijiao" class="btn btn-block btn-danger">提交</button>
<input type="hidden" name="_token" value="{{ csrf_token() }}">
</form>
<script>
    $(document).ready(function () {
        var div = document.getElementById('scrolldIV');
        console.log(div)
        div.scrollTop = div.scrollHeight;


    });
    document.onkeyup = function(e) {
        // 兼容FF和IE和Opera
        var event = e || window.event;
        var key = event.which || event.keyCode || event.charCode;
        var content = document.getElementById('content');
        if(key==13 && content.value.length>0){
            document.getElementById('tijiao').click();
        }
    };

</script>
</body>
</html>