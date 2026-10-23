<?php

return [
    0 => 'Succeeded',
    400 => 'Parameter error',   // 参数错误
    401 => 'Unauthorized',  // 未授权
    403 => 'Access denied',
    404 => 'Not found',
    405 => 'Method not allowed',
    422 => 'Parameter verification error',
    429 => 'Frequent requests',
    500 => 'Server error',

    10000 => 'SMS failed to send. Please try again later',  // 短信发送失败

    // 用户
    10001 => 'Verification code expired',   // 验证码过期
    10002 => 'Verification code error', // 验证码错误
    10003 => 'Mobile number already exists', // 手机号已存在
    10004 => 'User does not exist', // 用户不存在
    10005 => 'Wrong account or password',   // 账号或密码错误
    10006 => 'Login denied',    // 登陆拒绝
    10007 => 'Original password error',   // 当前密码错误
    10008 => 'Incorrect payment password',    // 支付密码错误
    10009 => 'Sorry, your credit is running low',   // 余额不足
    10010 => 'Unable to purchase low level VIP',    // 无法购买低等级vip
    10011 => 'Bank card bound',    // 已绑定银行卡
    10012 => 'Account disable',    // 账户禁用
    10013 => 'Invitation code invalid',    // 邀请码无效
    10014 => 'The invitation code has already been bound',    // 邀请码已经绑定
    10015 => 'Phone number does not exist',//手机号码不存在
    10016 => 'Sorry, your credit is running low',   // 余额不足
    10017 => 'SMS sent frequently, please try again later',
    10018 => 'Card number already exists',    // 银行卡号已存在
    10019 => 'You have %d hours to contact manual customer service', //你还有xxx小时才能联系人工客服
    10020 => 'There is currently no customer service online',//目前没有在线人工客服

    10032 => 'You have signed in today', // 今日已经签到
    10033 => 'With %d subordinates, you can start the sign-in task',
    10034 => 'Sign in the end',//签到已经关闭
    10035 => 'Sign in failed,Please try again',
    10036 => 'Invalid TRC address',
    10050 => 'Duplicate registration is prohibited',
    10051 => 'Withdrawal must be successful before publishing',
    10052 => 'Withdrawal must be successful before publishing',

    10053 => 'Multiple accounts are not allowed to log in', //不允许登入多个账户



    // 任务
    20000 => 'Task type error', // 任务类型错误
    20001 => 'Task does not exist',    // 任务不存在
    20002 => 'Task completed',    // 任务已领完
    20003 => 'The task has not started yet',    // 任务没开始
    20004 => 'The task has ended',    // 任务已结束
    20005 => 'Your VIP level is not enough',    // vip等级不足
    20006 => 'Tou have completed the task',    // 重复领取
    20007 => 'No tasks',    // 暂无任务
    20008 => 'Task has already been submitted', // 已提交过该任务
    20009 => 'Today\'s task is full',    // 今日任务已满
    20010 => 'Under review',    // 审核中
    20011 => 'Task has entered review or ended',    // 任务已进入审阅或结束
    20012 => 'The task has been claimed',  //该任务已领取过
    20013 => 'Task closed',

    30001 => 'File not allowed to upload',    // 上传格式错误
    30002 => 'Picture captcha is invalid',    // 图形验证码过期
    30003 => 'Graphic verification code error',    // 图形验证码错误

    40000 => 'Signature error',    // 签名错误
    40001 => 'Failed to create order',    // 订单创建失败
    40002 => 'Unbound bank card',    // 未绑定银行卡
    40003 => 'Withdrawal failed',    // 提现失败
    40004 => 'It can only raise the integral multiple of %d',    // 提现倍数
    40005 => 'Please submit the withdrawal on working days',    // 工作日才能提现
    40006 => 'Cannot withdraw immediately after recharge',
    40007 => 'The bank does not work on non-working days, you can choose USDT to withdraw.', //暂时允许提现
    40008 => 'Incorrect order',
    40009 => 'Cancel failed',
    40010 => 'VIP %d only can withdrawn %d times a day',

    50001 => 'Red envelope does not exist',    // 红包不存在
    50002 => 'The red envelope is invalid',    // 红包已失效
    50003 => 'Claim failed',    // 红包已失效
    50004 => 'Must reach 1000 INR and you can  do the next step',
    50005 => 'The withdrawal to the balance was successful',
    50006 => 'Please complete the invitation task of the previous level first',//请先完成上一级邀请任务

    60000 => 'No payment channel available',//暂无可用支付通道
    60001 => 'Unable to transfer in temporarily',  //转入开关
    60002 => 'Unable to transfer out temporarily',  //转出开关
    60003 => 'Sorry, your credit is running low',  //利息宝余额不足
    60004 => 'Recharge amount must be between %d and %d',
    60005 => 'Withdrawal amount must be between %d and %d',
    60006 => 'This feature must be VIP %d to use',
    60007 => 'The number of withdrawals today exceeds the limit',
    60008 => 'The number of withdrawals exceeds the limit',
    60009 => 'You have a withdrawal order in progress',
    60010 => 'Need to top up 200 rupees to activate the account',
    60011 => 'At least recharge %d',
    60012 => 'Recharge limit%d',
    60014 => 'VIP %d only can withdrawn %d rs a day',
    60015 => 'Vip %d upper limit of single withdrawal %d',
    60016 => 'Exchange rate error',
    60017 => 'Please download the app to complete this operation.', //请下载APP后完成此操作

    70000 => 'Activity does not exist',    // 活动不存在
    70001 => 'Activity closed',    // 活动已关闭
    70002 => 'You have received the activity reward',    // 已领取过
    70003 => 'Incomplete activity requirements',    // 未完成活动要求
    70006 => 'Activity data is abnormal', //活动数据异常
    70007 => 'The current VIP does not meet the conditions for receiving',
    70008 => 'You didn\'t invite real members, you cheated', //你没有邀请真正的会员，作弊

    80000 => 'SOLD OUT', //已售罄
    80001 => 'You can only raise one fry of the same type at a time, you can buy other types of fry',//超出持有量
    80002 => 'You need to top up and invest in %s products to proceed to the next step',//您需要充值并投资%s的产品才能进行下一步
    80003 => 'You must have %d subordinates recharge and invest in %s products to withdraw cash',//下级持有至少一条鱼苗才能提现
    80004 => 'Do not sell repeatedly',//请勿重复卖出
    80005 => 'You must buy a fry greater than the current level to sell this fish, Eg:%s',//你必须持有相同数量和相同价格的当前鱼苗才能出售这条鱼
    80006 => 'Parameter error',  //参数错误
    80007 => 'Parameter error', //未到时间
    80008 => 'You can only buy once',
    80009 => 'You cannot raise more than %d fish of this species',
    80010 => 'Operation failed, Please try again',
    80011 => 'Only top-up users can participate in investment',
    80012 => 'User does not exist',
    80013 => 'This order cannot be transferred',

    90000 => 'You lose',  //您未中奖
    90001 => 'Exceed the remaining quantity',
    90002 => 'The prize has been drawn',
    90003 => 'Not started',
    90004 => 'It\'s over',
    90005 => 'The purchase failed',


];
