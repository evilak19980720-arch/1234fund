<?php

return [
    0 => 'Sucesso',
    400 => 'Erro de parâmetro',   // 参数错误
    401 => 'Não autorizado',  // 未授权
    403 => 'Acesso negado',
    404 => 'Não encontrado',
    405 => 'Método não permitido',
    422 => 'Erro de verificação de parâmetro',
    429 => 'Solicitações frequentes',
    500 => 'Erro de servidor',

    10000 => 'Falha ao enviar SMS. Tente novamente mais tarde',  // 短信发送失败

    // 用户
    10001 => 'O código de verificação expirou',   // 验证码过期
    10002 => 'Erro no código de verificação', // 验证码错误
    10003 => 'O número de celular já existe', // 手机号已存在
    10004 => 'Usuário não existe', // 用户不存在
    10005 => 'Conta ou senha errada',   // 账号或密码错误
    10006 => 'Login negado',    // 登陆拒绝
    10007 => 'Erro de senha original',   // 当前密码错误
    10008 => 'Senha de pagamento incorreta',    // 支付密码错误
    10009 => 'Desculpe, seu crédito está acabando',   // 余额不足
    10010 => 'Não é possível comprar VIP de nível baixo',    // 无法购买低等级vip
    10011 => 'Cartão bancário vinculado',    // 已绑定银行卡
    10012 => 'Desativar conta',    // 账户禁用
    10013 => 'Código de convite inválido',    // 邀请码无效
    10014 => 'O código de convite já foi vinculado',    // 邀请码已经绑定
    10015 => 'O número de telefone não existe',//手机号码不存在
    10016 => 'Desculpe, seu crédito está acabando',   // 余额不足
    10017 => 'SMS enviados com frequência, tente novamente mais tarde',
    10018 => 'O número do cartão já existe',    // 银行卡号已存在
    10019 => 'Você tem %d horas para entrar em contato com o atendimento manual ao cliente', //你还有xxx小时才能联系人工客服
    10020 => 'Atualmente não há atendimento ao cliente on-line',//目前没有在线人工客服
    10032 => 'Você fez login hoje', // 今日已经签到
    10033 => 'Com %d subordinados, você pode iniciar a tarefa de login',
    10034 => 'Assine no final',//签到已经关闭
    10035 => 'Falha ao fazer login, tente novamente',

    // 任务
    20000 => 'Erro de tipo de tarefa', // 任务类型错误
    20001 => 'A tarefa não existe',    // 任务不存在
    20002 => 'Tarefa completa',    // 任务已领完
    20003 => 'A tarefa ainda não começou',    // 任务没开始
    20004 => 'A tarefa terminou',    // 任务已结束
    20005 => 'Seu nível VIP não é suficiente',    // vip等级不足
    20006 => 'Você completou a tarefa',    // 重复领取
    20007 => 'Nenhuma tarefa',    // 暂无任务
    20008 => 'A tarefa já foi enviada', // 已提交过该任务
    20009 => 'A tarefa de hoje está completa',    // 今日任务已满
    20010 => 'Sob revisão',    // 审核中
    20011 => 'A tarefa entrou em revisão ou foi finalizada',    // 任务已进入审阅或结束
    20012 => 'A tarefa foi reivindicada',  //该任务已领取过
    20013 => 'Tarefa fechada',

    30001 => 'Arquivo não permitido para upload',    // 上传格式错误
    30002 => 'O captcha da imagem é inválido',    // 图形验证码过期
    30003 => 'Erro no código de verificação gráfica',    // 图形验证码错误

    40000 => 'Erro de assinatura',    // 签名错误
    40001 => 'Falha ao criar pedido',    // 订单创建失败
    40002 => 'Cartão bancário não consolidado',    // 未绑定银行卡
    40003 => 'Falha na retirada',    // 提现失败
    40004 => 'Só pode aumentar o múltiplo integral de %d',    // 提现倍数
    40005 => 'Por favor, envie o saque em dias úteis',    // 工作日才能提现
    40006 => 'Não é possível retirar imediatamente após a recarga',
    40007 => 'O banco não funciona em dias não úteis, você pode escolher o USDT para sacar.', //暂时允许提现
    40008 => 'Ordem incorreta',
    40009 => 'Falha no cancelamento',
    40010 => 'VIP %d só pode sacar %d vezes por dia',

    50001 => 'Envelope vermelho não existe',    // 红包不存在
    50002 => 'O envelope vermelho é inválido',    // 红包已失效
    50003 => 'Reivindicação falhou',    // 红包已失效
    50004 => 'Deve atingir 1000 INR e você pode dar o próximo passo',
    50005 => 'A retirada para o saldo foi bem-sucedida',
    50006 => 'Por favor, complete primeiro a tarefa de convite do nível anterior',//请先完成上一级邀请任务

    60001 => 'Não é possível transferir temporariamente',  //转入开关
    60002 => 'Não é possível transferir temporariamente',  //转出开关
    60003 => 'Desculpe, seu crédito está acabando',  //利息宝余额不足
    60004 => 'O valor da recarga deve estar entre %d e %d',
    60005 => 'O valor da retirada deve estar entre %d e %d',
    60006 => 'Este recurso deve ser VIP %d para ser usado',
    60007 => 'O número de saques hoje ultrapassa o limite',
    60008 => 'O número de saques ultrapassa o limite',
    60009 => 'Você tem um pedido de saque em andamento',
    60010 => 'Precisa recarregar 200 rúpias para ativar a conta',
    60011 => 'Pelo menos recarregue%d',
    60012 => 'Limite de recarga%d',
    60014 => 'VIP %d só pode sacar %d rs por dia',
    60015 => 'Vip %d limite superior de saque único %d',

    70000 => 'A atividade não existe',    // 活动不存在
    70001 => 'Atividade encerrada',    // 活动已关闭
    70002 => 'Você recebeu a recompensa da atividade',    // 已领取过
    70003 => 'Requisitos de atividade incompletos',    // 未完成活动要求
    70006 => 'Os dados de atividade estão anormais', //活动数据异常

    80000 => 'VENDIDO', //已售罄
    80001 => 'Você só pode criar um alevino do mesmo tipo por vez, você pode comprar outros tipos de alevinos',//超出持有量
    80002 => 'Deve recarregar 600 INR e investir em peixes e você pode dar o próximo passo',//持有至少一条鱼苗才能提现
    80003 => 'Deve ter 2 subordinados investindo 1000 INR em peixes e você pode retirar a recompensa',//下级持有至少一条鱼苗才能提现
    80004 => 'Não venda repetidamente',//请勿重复卖出
    80005 => 'Você deve comprar um alevino maior que o nível atual para vender este peixe, Ex:%s',//你必须持有相同数量和相同价格的当前鱼苗才能出售这条鱼
    80006 => 'Erro de parâmetro',  //参数错误
    80007 => 'Erro de parâmetro', //未到时间
    80008 => 'Você só pode comprar uma vez',
    80009 => 'Você não pode criar mais de %d peixes desta espécie',
    80010 => 'A operação falhou, tente novamente',



    90000 => 'Você perdeu',  //您未中奖
    90001 => 'Exceder a quantidade restante',
    90002 => 'O prêmio foi sorteado',
    90003 => 'Não foi iniciado',
    90004 => 'Acabou',
    90005 => 'A compra falhou',


];
