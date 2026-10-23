<?php


namespace App\Handlers;


class KeysHandler
{
    const PETS = 'pets';
    const THIRD_ORDER_QUEUE = 'third_order_list';   // 订单队列

    const ORDER_LIST = 'user:%d:orders:%s:%d:%d:%d';    // 用户id:页码:条数

    const BANNER = 'banners:%d';  // 轮播图
    const BANNER_EXPIRE = 60 * 60 * 24 * 7; // banner缓存时间

    const TYPE = 'types';   // 任务类型
    const TYPE_EXPIRE = 60 * 60 * 24 * 7; // 任务类型缓存时间

    const RED_LOG_EXPIRE = 60 * 5; //红包记录缓存时间
    const RED_RECORD_EXPIRE = 60 * 60 * 1; //红包记录缓存时间

    const TASK_LOG = 'taskLog:%d';    // 领取的任务
    const USER_PROFIT = 'user:%d_profit';   // 用户收益
    const USER_TEAM_PROFIT = 'user:%d_team_profit'; // 用户团队收益
    const USER_PROFIT_EXPIRE = 60;  //团队收益缓存
    const USER_SUBORDINATE = 'user:%d_subordinate:%d';  // 用户下级
    const USER_SUBORDINATE_PETORDER = 'user:%d_subordinatepetorder:%d';  // 用户下级
    const USER_SUBORDINATE_ORDER = 'user:%d_subordinateorder:%d';  // 用户下级
    const USER_SUBORDINATE_ORDERFIRST = 'user:%d_subordinateorderfirst:%d';  // 用户下级

    const USER_CHILD = 'user:%d_child:%d';  // 用户下级
    const USER_SUBORDINATE_PROFIT = 'user:%d_subordinate:%d_profits_page:%d_limit:%d'; // 用户下级收益（列表）
    const USER_ACCOUNT = 'account:%d_%s_%d_%d_%d_%s';    // 用户账目明细 uid-type-mold-page-limit-date
    const USER_RANKING = 'user:%d:ranking'; // 用户排名

    // 任务
    const TASKS = 'tasks:%d:%d:%d';   // 任务列表 tasks:页码:条数
    const TASK = 'task:%d'; // 任务详情
    const MY_TASK = 'user:%d:tasks_%s:%d';    // 我的任务列表
    const TASK_USERS = 'task:%d:users';    // 领取任务的用户的集合
    const FINISH_TASK = 'user:%d:finished'; // 用户完成任务
    const TODAY_FINISH = 'user:%d:vip:%d:today:%s'; // 用户今日完成任务

    const NOTICES = 'notices_admin_user_id:%d';   // 公告
    const TOP_NOTICE = 'top_notices_admin_user_id:%d';   // 置顶公告
    const EJECT_NOTICE = 'eject_notices_admin_user_id:%d';
    const NOTICE = 'notice:%d';  // 公告详情s
    const NOTICE_EXPIRE = 60 * 60 * 24 * 7;

    const CONFIGURATION = 'configuration';  // 配置项
    const CONFIGURATION_EXPIRE = 60 * 60 * 24 * 7;

    const RED = 'red:%d';   // 红包
    const RED_CLIENT_DEVICE = 'red:%s:%s';  // 未登录红包

    const ACTIVITIES = 'activities';    // 活动列表
    const ACTIVITIES_PAGE = 'activities:%d:%d';    // 活动列表:页数:条数
    const ACTIVITY = 'activity:%d'; // 活动详情
    const ACTIVITY_RECEIVED = 'activity:%d_user:%d';    // 活动:id_用户:id
    const ACTIVITY_RECEIVED_SIGN = 'activity:%d_user:%d_data:%d';    // 活动:id_用户:id

    const HAVE_CARD = 'user:%d:have_card';   // 用户是否绑定有银行卡
    const DID_TASK = 'user:%d:did_task';    // 是否完成过任务

    const MANUALS = 'manuals:%d:%d';    // 手册:page:limit
    const MANUAL = 'manuals:%d';    // 手册:page:limit

    const CUSTOMER = 'customer';    // 客服

    const RANKING = 'ranking';  // 排行榜

    const ARTICLE = 'article:%s';   // 文章

    const TodayRecord = 'record:%s';    // 今日记录
    const TodayCashOut = 'user:%d:cash-out:%s';    // 今日提现次数

    const USER_TEAM = 'user:%d:level:%d:page:%d:limit:%d:date:%s:showzero:%d:phone:%s:column:%s:sort:%s';  // 团队

    const PET_EXPIRE = 60 * 60 * 24 * 7;  //宠物列表
    const PET = 'pet:%d'; // 宠物详情

    const FOUNDING_BUY = 'funding:%d:user:%d:buy';  // 众筹购买

    const PET_ACT_EXPIRE = 1;


    const THIS_WEEK_COMMISSION_TOP_FIVE = 'this_week_commission_top_five'; // 本周佣金前5
    const THIS_WEEK_COMMISSION_TOP_FIVE_EXPIRE = 60 * 60 * 12;  //本周佣金前5缓存期

    const FAKE_THIS_WEEK_COMMISSION_TOP_FIVE = 'this_week_commission_top_five'; // 假的本周佣金前5
    const FAKE_THIS_WEEK_COMMISSION_TOP_FIVE_EXPIRE = 60 * 60 * 24 * 7;  //本周佣金前5缓存期

    const TransCache = 'trans:%d:%s';
    const TransCache_Expire = 360;

    const TRC20JOB = 'trc20job';

    const FIREND = 'firend:%d:%d';
    const FIREND_Expire = 300;

    const USDTCHECK = 'userrecharge:%s:%d';

    const USERTRCADDRESS = 'usertrcaddress:%d:%s';    // 充值地址

    const PAYMENT_THIRD_PAY_LIST = 'payment_third_pay_list:%s';


    const VERSION_ALL = 'version_all'; // 版本ALL
    const VERSION_ALL_EXPIRE = 60 * 60 * 24 * 7; // 版本ALL缓存时间
}
