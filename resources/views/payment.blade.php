<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Document</title>
    <script>
        window.onload=function(){
            var success = document.getElementById("success")
            var felied = document.getElementById("felied")
            var back = document.getElementById("back")
            back.onclick=function(){
                window.location.href='<?php echo $url;?>';
            }
            felied.style.display="none"
            //success.style.display="none
        }
    </script>
    <style>
        body,html{
            width: 100vw;
            height: 100vh;
            overflow-y: hidden;
            margin: 0;
            padding: 0;
        }
        .success{
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .felied{
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        img{
            width: 165px;
            height: 165px;
        }
        p{
            font-size: 17px;
        }
        .header{
            width: 100%;
            height: 50px;
            box-sizing: border-box;
            border-bottom: 1px solid rgb(241, 241, 241);
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            padding-left: 10px;
        }
        .header .back{
            font-size: 25px;
            color: rgb(190, 190, 190);
        }
        .header .back img{
            width: 15px;
            height: 15px;
        }
        .header .content{
            flex: 1;
            text-align: center;
            font-size: 17px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="back" id="back">
        <img src="./img/navgation.png"/>
    </div>
    <div class="content">Recharge</div>
</div>
<div class="success" id="success">
    <img src="./img/success.png"/>
    <p>Recharge succeeded！</p>
</div>
<div class="felied" id="felied">
    <img src="./img/feiled.png"/>
    <p>Recharge failed!</p>
</div>
</body>
</html>
