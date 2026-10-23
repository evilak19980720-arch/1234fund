<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */
    //收支类型
    'account_type' => [
        0 => 'Cancelamento',
        1 => 'Recarrega',
        //2 => 'Comissão',
        3 => 'Comissão da agência',  //利润返利
        //4 => 'Receita de tarefa de marketing',
        //5 => 'Junte-se ao VIP',
        //6 => 'Transferência no banco',
        //7 => 'Transferir banco',
        8 => 'Recompensa de atividade',
        9 => 'Falha na retirada',
        //10 => 'Recompensa convidada',
        11 => 'Investir', //买鱼苗
        //12 => 'Pré-encomende batatas fritas', //预约购买
        13 => 'Renda diária', //出售鱼苗
        //14 => 'Vender batatas fritas',
        //15 => 'Ativar conta',
        16 => 'Compartilhando recompensas',
        17 => 'Comissão da agência',  //购买返利
        18 => 'Recarga do sistema',
        //19 => 'Vencedores do financiamento coletivo',
        //20 => 'Financiamento coletivo de investimento',
        21 => 'Desconto de recarga',
        //22 => 'Cancelar encomenda',
        23 => 'Dedução do sistema',
        //24 => 'Transferido para Ecpay',//余额转移
        //25 => 'Quantidade descongelada',
        26 => 'Faça login para recompensar',
        27 => 'Registrar recompensa',//注册奖励
    ],
    //租贸类型
    'rental_type' => [
        0 => 'Aluguel conjunto',//合租
        1 => 'Todo o aluguel',//整租
    ],
    'mission_type' => [0 => 'Em processamento', 1 => 'Análise', 2 => 'Concluído', -1 => 'Ao longo do tempo', -2 => 'Abandono'],
    'accepted' => 'The :atributo deve ser aceito.',
    'active_url' => 'The : atributo não é um URL válido.',
    'after' => 'The : atributo deve ser uma data posterior :date.',
    'after_or_equal' => 'The : atributo deve ser uma data posterior ou igual a :date.',
    'alpha' => 'The :atributo pode conter apenas letras.',
    'alpha_dash' => 'The :atributo pode conter apenas letras, números, travessões e sublinhados.',
    'alpha_num' => 'The :attribute só pode conter letras e números.',
    'array' => 'The :attributedeve ser uma matriz.',
    'before' => 'The :attribute deve ser uma data anterior :date.',
    'before_or_equal' => 'The :attributedeve ser uma data anterior ou igual a :date.',
    'between' => [
        'numeric' => 'The :attribute deve estar entre :min e :max.',
        'file' => 'The :attributedeve estar entre :min e :max kilobytes.',
        'string' => 'The :attributedeve estar entre :caracteres mínimo e: máximo.',
        'array' => 'The :attribute deve ter entre :min e :max itens.',
    ],
    'boolean' => 'The :attribute campo deve ser verdadeiro ou falso.',
    'confirmed' => 'The :attribute a confirmação não corresponde.',
    'date' => 'The :attribute não é uma data válida.',
    'date_equals' => 'The :attribute deve ser uma data igual a :date.',
    'date_format' => 'The :attribute não corresponde ao formato :format.',
    'different' => 'The :attribute e: outro deve ser diferente.',
    'digits' => 'The :attributedeve ser: dígitos dígitos.',
    'digits_between' => 'The :attributedeve estar entre :min e :max dígitos.',
    'dimensions' => 'The :attribute tem dimensões de imagem inválidas.',
    'distinct' => 'The :attribute campo tem um valor duplicado.',
    'email' => 'The :attribute Deve ser um endereço de e-mail válido.',
    'ends_with' => 'The :attribute deve terminar com um dos seguintes: :values.',
    'exists' => 'O :attribute selecionado é inválido.',
    'file' => 'The :attribute deve ser um arquivo.',
    'filled' => 'The :attribute campo deve ter um valore.',
    'gt' => [
        'numeric' => 'The :attributedeve ser maior que :value.',
        'file' => 'The :attributedeve ser maior que: valor kilobytes.',
        'string' => 'The :attribute deve ser maior que :value characters.',
        'array' => 'The :attributedeve ter mais de :value items.',
    ],
    'gte' => [
        'numeric' => 'The :attribute deve ser maior ou igual :value.',
        'file' => 'The :attribute deve ser maior ou igual :value kilobytes.',
        'string' => 'The :attribute deve ser maior ou igual :value characters.',
        'array' => 'The :attribute deve ter: itens de valor ou more.',
    ],
    'image' => 'The :attribute deve ser uma imagem.',
    'in' => 'The selected :attribute é inválido.',
    'in_array' => 'The :attributecampo não existe em :other.',
    'integer' => 'The :attributedeve ser um número inteiro.',
    'ip' => 'The :attribute deve ser um endereço IP válido.',
    'ipv4' => 'The :attributedeve ser um endereço IPv4 válido.',
    'ipv6' => 'The :attribute deve ser um endereço IPv6 válido.',
    'json' => 'The :attribute deve ser uma string JSON válida.',
    'lt' => [
        'numeric' => 'The :attribute deve ser menor que :value.',
        'file' => 'The :attributedeve ser menor que :value kilobytes.',
        'string' => 'The :attribute deve ser menor que :value characters.',
        'array' => 'The :attribute deve ter menos de :value items.',
    ],
    'lte' => [
        'numeric' => 'The :attribute deve ser menor ou igual :value.',
        'file' => 'The :attribute deve ser menor ou igual :value kilobytes.',
        'string' => 'The :attributedeve ser menor ou igual :value characters.',
        'array' => 'The :attribute não deve ter mais do que:value items.',
    ],
    'max' => [
        'numeric' => 'The :attribute não pode ser maior que :max.',
        'file' => 'The :attributenão pode ser maior que:max kilobytes.',
        'string' => 'The :attributenão pode ser maior que :max characters.',
        'array' => 'The :attributenão pode ter mais do que:max items.',
    ],
    'mimes' => 'The :attributenão pode ter mais do que deve ser um arquivo do tipo: :values.',
    'mimetypes' => 'The :attribute deve ser um arquivo do tipo: :values.',
    'min' => [
        'numeric' => 'The :attribute deve ser pelo menos:min.',
        'file' => 'The :attribute deve ser pelo menos :min kilobytes.',
        'string' => 'The :attribute deve ser pelo menos:min characters.',
        'array' => 'The :attribute deve ter pelo menos :min items.',
    ],
    'not_in' => 'O :attribute selecionado é inválido.',
    'not_regex' => 'The :o formato do atributo é inválido.',
    'numeric' => 'The :attribute deve ser um número.',
    'password' => 'The passwordestá incorreto.',
    'present' => 'The :attribute campo deve estar presente.',
    'regex' => 'The :attribute o formato é inválido.',
    'required' => 'The :attributecampo é obrigatório.',
    'required_if' => 'The :attributecampo é obrigatório quando :other é :value.',
    'required_unless' => 'The :attribute o campo é obrigatório, a menos que :other esteja em :values.',
    'required_with' => 'The :attributecampo é obrigatório quando :values está presente.',
    'required_with_all' => 'The :attribute campo é obrigatório quando: valores estão presentes.',
    'required_without' => 'The :attributecampo é obrigatório quando :values não está presente.',
    'required_without_all' => 'The :attributecampo é obrigatório quando :values não está presentecampo é obrigatório quando :values não está presente.',
    'same' => 'The :atributo e: outro devem corresponder.',
    'size' => [
        'numeric' => 'The :attribute deve ser: tamanho.',
        'file' => 'The :attribute deve ser: tamanho quilobytes.',
        'string' => 'The :attribute deve ter: caracteres de tamanho.',
        'array' => 'The :attributedeve conter: item de tamanhos.',
    ],
    'starts_with' => 'The :attribute deve começar com um dos seguintes: :values.',
    'string' => 'The :attributedeve ser uma string.',
    'timezone' => 'The :attributedeve ser uma zona válida.',
    'unique' => 'The :attributejá foi tomada.',
    'uploaded' => 'The :attributenão foi possível fazer o upload.',
    'url' => 'The :attributeo formato é inválido.',
    'uuid' => 'The :attributedeve ser um UUID válido.',

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
    ],

];
