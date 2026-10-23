<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body onLoad="document.yqfForm.submit();">
<form name="yqfForm" method="post" action="https://pay.yarlungpay.com/pay/web">
    <input type="hidden" name="sign" value="{{ $sign }}"/>
    <input type="hidden" name="mch_id" value="{{ $mch_id }}"/>
    <input type="hidden" name="sign_type" value="{{ $sign_type }}"/>
    <input type="hidden" name="notify_url" value="{{ $notify_url }}"/>
    <input type="hidden" name="page_url" value="{{ $page_url }}"/>
    <input type="hidden" name="mch_order_no" value="{{ $mch_order_no }}"/>
    <input type="hidden" name="pay_type" value="{{ $pay_type }}"/>
    <input type="hidden" name="currency" value="{{ $currency }}"/>
    <input type="hidden" name="trade_amount" value="{{ $trade_amount }}"/>
    <input type="hidden" name="order_date" value="{{ $order_date }}"/></br>
    <input type="hidden" name="goods_name" value="{{ $goods_name }}"/>
    <input type="hidden" name="payer_ip" value="{{ $payer_ip }}"/>
</form>
</body>
</html>
