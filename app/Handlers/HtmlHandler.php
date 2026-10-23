<?php


namespace App\Handlers;


class HtmlHandler
{
    static public function makeContent($content){
        $html_style = <<<EOT
<style>.share_content{width: 100%;line-height: 150%;}.share_content *{ line-height: 150%; color: #fff;margin:0;padding:0;list-style:none;}.share_content a,.share_content a:hover{color: #fff;}.share_content img{max-width: 100%;height: auto;}.list-paddingleft-2 li{list-style:none;border-bottom:1px solid #F7F5F8;padding-top: 10px;padding-bottom: 10px;}.list-paddingleft-2 li:before{content:" ";float: left;display:inline-block;text-align:center;color:aliceblue;font-family: "PingFang-SC-Bold";width:10px;height:10px;background-color:#FF472B;border-radius:50%;margin-right:10px;margin-left:0px;margin-top:5px;}</style>
EOT;

        $content = $html_style.'<div class="share_content">'.$content.'</div>';
        return $content;
    }
}
