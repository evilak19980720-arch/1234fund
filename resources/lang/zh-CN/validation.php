<?php

return [
    //租贸类型
    'rental_type' => [
        0 => '合租',//合租
        1 => '整租',//整租
    ],
    'mission_type' => [0 => '进行中', 1 => '审核中', 2 => '已完成', -1 => '超时', -2 => '放弃'],
    'accepted' => ':attribute是被接受的',
    'active_url' => ':attribute必须是一个合法的 URL',
    'after' => ':attribute必须是:date之后的一个日期',
    'after_or_equal' => ':attribute必须晚于或等于:date.',
    'alpha' => ':attribute必须全部由字母字符构成',
    'alpha_dash' => ':attribute必须全部由字母、数字、中划线或下划线字符构成',
    'alpha_num' => ':attribute必须全部由字母和数字构成',
    'array' => ':attribute必须是个数组',
    'before' => ':attribute必须是:date之前的一个日期',
    'before_or_equal' => ':attribute必须早于或等于:date.',
    'between' => [
        'numeric' => ':attribute必须在:min到:max之间',
        'file' => ':attribute必须在:min到:max KB之间',
        'string' => ':attribute必须在:min到:max个字符之间',
        'array' => ':attribute必须在:min到:max项之间',
    ],
    'boolean' => ':attribute 字符必须是true或false',
    'confirmed' => ':attribute二次确认不匹配',
    'date' => ':attribute必须是一个合法的日期',
    'date_equals' => ':attribute必须等于:date.',
    'date_format' => ':attribute与给定的格式:format不符合',
    'different' => ':attribute必须不同于:other',
    'digits' => ':attribute必须是:digits位',
    'digits_between' => ':attribute必须在:min和:max 位之间',
    'dimensions' => ':attribute具有无效的图像尺寸',
    'distinct' => ':attribute重复.',
    'email' => ':attribute必须是有效的邮箱地址',
    'ends_with' => ':attribute必须以: :values结尾',
    'exists' => '选中的:attribute无效',
    'file' => ':attribute必须是个文件',
    'filled' => ':attribute字段必须有值',
    'gt' => [
        'numeric' => ':attribute必须大于:value.',
        'file' => ':attribute必须大于:value KB.',
        'string' => ':attribute必须大于:value个字符',
        'array' => 'The :attribute必须超过:value个元素',
    ],
    'gte' => [
        'numeric' => ':attribute 必须大于或等于:value',
        'file' => ':attribute 必须大于或等于:value KB',
        'string' => ':attribute必须大于或等于:value个字符',
        'array' => ':attribute至少拥有:value个元素',
    ],
    'image' => ':attribute必须是图片',
    'in' => '选定的 :attribute是无效的',
    'in_array' => ':attribute不在:other当中',
    'integer' => ':attribute必须是个整数',
    'ip' => ':attribute必须是一个合法的IP地址',
    'ipv4' => ':attribute必须是个有效的IPv4地址',
    'ipv6' => ':attribute必须是个有效的IPv6地址',
    'json' => ':attribute必须是个有效的json字符串',
    'lt' => [
        'numeric' => ':attribute必须小于:value.',
        'file' => ':attribute必须小于:value KB',
        'string' => ':attribute必须小于:value 个字符',
        'array' => ':attribute不得超过:value 个元素',
    ],
    'lte' => [
        'numeric' => ':attribute必须小于或等于:value.',
        'file' => ':attribute必须小于或等于:value KB',
        'string' => ':attribute必须小于或等于:value个字符',
        'array' => ':attribute必须少于:value个元素',
    ],
    'max' => [
        'numeric' => ':attribute不能大于:max.',
        'file' => ':attribute不能大于:max KB',
        'string' => ':attribute不能大于:max个字符',
        'array' => ':attribute不能超过:max个元素',
    ],
    'mimes' => ':attribute的文件类型必须是:values',
    'mimetypes' => ':attribute的文件类型必须是: :values.',
    'min' => [
        'numeric' => ':attribute必须至少:min.',
        'file' => ':attribute必须至少:min KB',
        'string' => ':attribute必须至少:min 个字符',
        'array' => ':attribute必须至少:min 个元素',
    ],
    'not_in' => '选定的:attribute是无效的',
    'not_regex' => ':attribute格式无效',
    'numeric' => ':attribute必须是数字',
    'password' => '密码不正确',
    'present' => ':attribute必须存在',
    'regex' => ':attribute属性格式无效',
    'required' => ':attribute必填',
    'required_if' => ':attribute字段是必须的当:other是:value',
    'required_unless' => ':other位于:values当中，否则:attribute是必须的',
    'required_with' => '当 :values存在时，:attribute是必须的',
    'required_with_all' => '当所有:values存在时，:attribute是必须的',
    'required_without' => '当:values不存在时，:attribute是必须的',
    'required_without_all' => '当所有:values不存在时，:attribute是必须的',
    'same' => ':attribute和:other必须匹配',
    'size' => [
        'numeric' => ':attribute必须是:size 位',
        'file' => ':attribute必须是:size KB',
        'string' => ':attribute必须是:size 个字符',
        'array' => ':attribute必须包括:size 项',
    ],
    'starts_with' => ':attribute必须以: :values开头',
    'string' => ':attribute必须是字符串',
    'timezone' => ':attribute必须是有效区域',
    'unique' => ':attribute已存在',
    'uploaded' => ':attribute上传失败',
    'url' => ':attribute无效的格式',
    'uuid' => ':attribute必须是一个有效的 UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'page' => '页码',
        'limit' => '每页条数',
        'phone' => '手机号',
        'name' => '账号',
        'password' => '密码',
        'amount' => '金额',
        'type' => '类型',
        'type_id' => '类型',
        'number' => '银行卡号',
        'number_confirmation' => '确认卡号',
        'username' => '用户名',
        'verification_key' => '验证码标识',
        'verification_code' => '短信码',
        'email' => '邮箱',
        'bank_id' => '银行',
        'province' => '邦',
        'city' => '城',
        'address' => '地址',
        'image' => '图片',
        'client' => '客户端',
        'device' => '设备号',
        'area' => '区号',
    ],
];
