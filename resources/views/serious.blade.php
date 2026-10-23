<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body onLoad="document.seriousForm.submit();">
<form name="seriousForm" method="post" action="{{$recharge_gateway}}">
    @foreach($data as $key => $value)
        <input type="hidden" name="{{$key}}" value="{{$value}}">
    @endforeach
</form>
</body>
</html>
