<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-size: 14px;
        }

        .triangle {
            background-color: #ebebe9;
            padding: 0.5rem;
            width: 100%;
            height: 700px;
            overflow-y: auto;//竖直方向有滚动条，如果想要横向的，可以把y改为x
        }

        .triangle ul {
            padding: 10px;
        }

        .triangle li {
            padding: 5px;
            margin-bottom: 10px;
        }

        .triangle li span {
            position: relative;
            border-radius: 7px;
            background-color: #a6e860;
            padding: 6px 10px 8px 10px;
            z-index: 1;
        }

        .triangle .textLeft span {
            background-color: white;
        }

        .triangle li.textLeft:before {
            content: url("images/tx1.jpg");
            box-sizing: border-box;
            position: relative;
            left: -10px;
            top: 9px;
        }

        .triangle li.textLeft span:before {
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

        .triangle li.textRight:after {
            width: 30px;
            height: 30px;
            content: url("1");
            background-size: cover;
            box-sizing: border-box;
            position: relative;
            right: -10px;
            top: 9px;;
        }

        .triangle li.textRight span:after {
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

        li {
            list-style: none;
        }

        .textRight {
            text-align: right;
        }

    </style>
</head>
<body>
<div class="triangle" id="scrolldIV">
    <ul>
        <?php $a = $chatList[0]['user_id'];foreach($chatList as $value){?>
        <li class="<?php if ($a == $value['user_id']) {
            echo 'textLeft';
        } else {
            echo 'textRight';
        }?>">
            <?php if($value['type'] == 3){?>
            <span><video width="200px" height="50px" src="<?php echo $value['content'];?>" controls="controls">your browser does not support the video tag
            </video></span>
            <?php }elseif($value['type'] == 2){?>
            <span><a href="<?php echo $value['content'];?>" target="_blank"><img width="100px" height="100px" src="<?php echo $value['content'];?>"> </a></span></span>
            <?php }else{?>
            <span><?php echo $value['content'];?></span>
            <?php }?>
        </li>
        <?php }?>
    </ul>
</div>
<script>
    $(document).ready(function () {
        var div = document.getElementById('scrolldIV');
        console.log(div)
        div.scrollTop = div.scrollHeight;

    });

</script>
</body>
</html>