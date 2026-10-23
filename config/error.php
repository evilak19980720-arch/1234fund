<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/27
 * Time: 18:21
 */

return [
    0 => 'ok',
    400 => 'parameter error',   // 参数错误
    401 => 'unauthorized',  // 未授权
    403 => 'access denied',
    404 => 'not found',
    405 => 'method not allowed',
    422 => 'parameter verification error',
    429 => 'frequent requests',
    500 => 'server error',

    10000 => 'SMS failed to send. Please try again later',  // 短信发送失败

    // 用户
    10001 => 'verification code expired',   // 验证码过期
    10002 => 'verification code error', // 验证码错误
    10003 => 'mobile number already exists', // 手机号已存在
    10004 => 'user does not exist', // 用户不存在
    10005 => 'wrong account or password',   // 账号或密码错误
    10006 => 'login denied',    // 登陆拒绝
    10007 => 'original password error',   // 当前密码错误
    10008 => 'incorrect payment password',    // 支付密码错误
    10009 => 'sorry, your credit is running low',   // 余额不足
    10010 => 'unable to purchase low level VIP',    // 无法购买低等级vip
    10011 => 'bank card bound',    // 已绑定银行卡

    // 任务
    20000 => 'task type error', // 任务类型错误
    20001 => 'task does not exist',    // 任务不存在
    20002 => 'task completed',    // 任务已领完
    20003 => 'the task has not started yet',    // 任务没开始
    20004 => 'the task has ended',    // 任务已结束
    20005 => 'your vip level is not enough',    // vip等级不足
    20006 => 'you have completed the task',    // 重复领取
    20007 => 'no task',    // 暂无任务
    20008 => 'task has already been submitted', // 已提交过该任务
    20009 => 'today\'s task is full',    // 今日任务已满
    20010 => 'under review',    // 审核中
    20011 => 'task has entered review or ended',    // 任务已进入审阅或结束

    30001 => 'file not allowed to upload',    // 上传格式错误
    30002 => 'picture captcha is invalid',    // 图形验证码过期
    30003 => 'graphic verification code error',    // 图形验证码错误

    40000 => 'signature error',    // 签名错误
    40001 => 'failed to create order',    // 订单创建失败
    40002 => 'unbound bank card',    // 未绑定银行卡
    40003 => 'withdrawal failed',    // 提现失败
    40004 => 'it can only raise the integral multiple of %d',    // 提现倍数

    50001 => 'red envelope does not exist',    // 红包不存在
    50002 => 'the red envelope is invalid',    // 红包已失效
    50003 => 'claim failed',    // 红包已失效

    60001 => 'Unable to transfer in temporarily',  //转入开关
    60002 => 'Unable to transfer out temporarily',  //转出开关
    60003 => 'sorry, your credit is running low',  //利息宝余额不足

    70000 => 'activity does not exist',    // 活动不存在
    70001 => 'activity closed',    // 活动已关闭
    70002 => 'you have received the activity reward',    // 已领取过
    70003 => 'incomplete activity requirements',    // 未完成活动要求
];
