<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::prefix('admin')->name('api.admin.')->namespace('Api\admin')->group(function () {
    Route::post('login', 'AuthorizationsController@store')->name('api.authorizations.store');

    Route::middleware('auth:admin')->group(function () {
        Route::get('me', 'UserController@me')->name('user.me');
    });
});*/

Route::prefix('v1')->name('api.v1.')->namespace('Api\v1')->group(function () {

    Route::post('error', 'HomeController@error');

    Route::get('py', 'TestController@py');

    Route::get('eventpush', 'HomeController@realTimeMessagePush');

    Route::get('createusers', 'TestController@createUsers');
    //更新订单表
//    Route::get('batch-update-petorder', 'TestController@batchUpdatePetOrder');
    //恢复数据
//    Route::get('recover-data', 'TestController@recoverData');
    //模型测试
//    Route::get('model-test', 'TestController@modelTest');
    //队列测试
//    Route::get('queue-settle-test', 'TestController@queueSettleTest');
//    Route::get('queue-auto-test', 'TestController@queueAutoTest');
    // 支付通知
    Route::post('notify', 'PayController@shineuRechargeNotify')->name('pay.notify');
    // 代付通知地址
    Route::post('behalf', 'PayController@shineuWithdrawalNotify')->name('pay.behalf');

    //CcPay
    Route::post('ccpay_webhook', 'CcPayController@Webhook');

    //ETPay
    Route::post('et_recharge', 'EtPayController@rechargeNotify');
    Route::post('et_withdrawal', 'EtPayController@withdrawalNotify');

    //CHPay
    Route::post('ch_recharge', 'ChPayController@rechargeNotify');
    Route::post('ch_withdrawal', 'ChPayController@withdrawalNotify');

    //wkplussPay
    Route::post('wkpluss_recharge', 'WkplussPayController@rechargeNotify');
    Route::post('wkpluss_withdrawal', 'WkplussPayController@withdrawalNotify');

    //onePay
    Route::post('one_recharge', 'OnePayController@rechargeNotify');
    Route::post('one_withdrawal', 'OnePayController@withdrawalNotify');

    //onePay
    Route::post('onein_recharge', 'OnePayInController@rechargeNotify');
    Route::post('onein_withdrawal', 'OnePayInController@withdrawalNotify');

    //mgmPay
    Route::post('mgm_recharge', 'MgmPayController@rechargeNotify');
    Route::post('mgm_withdrawal', 'MgmPayController@withdrawalNotify');

    //VortaqPay
    Route::post('vortaq_recharge', 'VortaqPayController@rechargeNotify');
    Route::post('vortaq_withdrawal', 'VortaqPayController@withdrawalNotify');

    //sunPay
    Route::post('sun_recharge', 'SunPayController@rechargeNotify');
    Route::post('sun_withdrawal', 'SunPayController@withdrawalNotify');

    //payPlus
    Route::post('pay_plus_recharge', 'PayPlusController@rechargeNotify');
    Route::post('pay_plus_withdrawal', 'PayPlusController@withdrawalNotify');

    //easyPay
    Route::post('easy_pay_recharge', 'EasyPayController@rechargeNotify');
    Route::post('easy_pay_withdrawal', 'EasyPayController@withdrawalNotify');

    //miSafePay
    Route::post('mi_safe_recharge', 'MiSafeController@rechargeNotify');
    Route::post('mi_safe_withdrawal', 'MiSafeController@withdrawalNotify');

    //dzPay
    Route::post('dz_recharge', 'DzPayController@rechargeNotify');
    Route::post('dz_withdrawal', 'DzPayController@withdrawalNotify');

    //Shine
    Route::post('shine_recharge', 'ShineController@rechargeNotify');
    Route::post('shine_withdrawal', 'ShineController@withdrawalNotify');

    //Ocean
    Route::post('ocean_pay_recharge', 'OceanController@rechargeNotify');
    Route::post('ocean_pay_withdrawal', 'OceanController@withdrawalNotify');

    //HxPay
    Route::post('hxpay_recharge', 'HxPayController@rechargeNotify');
    Route::post('hxpay_withdrawal', 'HxPayController@withdrawalNotify');

    //cloudPay
    Route::post('cloud_recharge', 'CloudPayController@rechargeNotify');
    Route::post('cloud_withdrawal', 'CloudPayController@withdrawalNotify');

    //liuPay
    Route::post('liu_recharge', 'LiuPayController@rechargeNotify');
    Route::post('liu_withdrawal', 'LiuPayController@withdrawalNotify');

    //wowPay
    Route::post('wow_recharge', 'WowPayController@rechargeNotify');
    Route::post('wow_withdrawal', 'WowPayController@withdrawalNotify');


    //通讯录
    Route::post('addressbook', 'AddressbookController@store')->name('addressbook.store');

    // 客服
    Route::get('customers', 'CustomerController@index')->name('customer.index');

    //获取当前语言和区号
    Route::post('locales', 'HomeController@locales')->name('home.locales');
    Route::post('setting', 'HomeController@locales');


    //客户端获取语言文件
    Route::get('trans', 'TranController@list')->name('trans.list');

    Route::middleware('change-locale')->group(function () {  //->middleware(['ip'])

        //获取所有国家语言
        Route::get('countries', 'HomeController@countries')->name('home.countries');

        // 朋友圈列表公开，游客也能看；带 token 时仍能识别本人（mine / 点赞状态）
        Route::get('friends', 'FriendController@index')->name('friend.index');

        Route::get('reward-record', 'HomeController@rewardRecord')->name('pet.rewardRecord');
        //收益记录
        Route::get('profit-record', 'HomeController@profitRecord')->name('pet.profitrecord');
        //收益记录
        Route::get('profit-logs', 'HomeController@profitLog')->name('pet.profitlogs');
        //宠物详情
        Route::get('pet-detail', 'PetController@detail')->name('pet.detail');


        //红包开关
        Route::get('red', 'RedController@redSwitch')->name('user.red');
        // 开关
        Route::get('graphic_switch', 'ConfigController@show')->name('config.show');
        //测试
        Route::get('foo', 'TestController@foo')->name('test.foo');
        Route::get('bar', 'TestController@bar')->name('test.bar');
        Route::get('geo', 'TestController@selectIp')->name('test.geo');
        // 银行
        Route::get('banks', 'CardController@bank')->name('card.bank');
        // 忘记密码
        Route::post('forget', 'UsersController@forgetPassword')->name('user.forget');
        Route::post('forget_password', 'UsersController@forgetPassword')->name('user.forget');

        //邀请文案
        Route::get('invite-content', 'UsersController@inviteContent')->name('user.invite');
        // 任务类型(后台)
        Route::get('task_types', 'TypeController@taskType')->name('type.task.type');
        Route::get('type_vips', 'VipController@typeVip')->name('type.vip');

        Route::post('verificationCodes', 'VerificationCodesController@store')->name('verificationCodes.store');


        // 图片验证码
        Route::get('captchas', 'CaptchasController@store')->name('captchas.store');
        // 注册
        Route::post('users', 'UsersController@store')->name('users.store');
        // 登录
        Route::post('authorizations', 'AuthorizationsController@store')->name('api.authorizations.store');
        Route::post('google_login', 'UsersController@google_login')->name('users.google_login');

        //业务员登录
        Route::post('saleslogin', 'AuthorizationsController@saleslogin')->name('api.authorizations.saleslogin');

        //版本ALL
        Route::get('versions/all', 'VersionController@all');
        // 版本
        Route::get('versions', 'VersionController@index');

        // 统计
        Route::post('records', 'RecordController@store')->name('record.store');

        // banner
        Route::get('banners', 'BannerController@index')->name('banner.index');
        Route::get('banner', 'BannerController@index')->name('banner.index');

        // 首页假数据
        Route::get('incomes', 'HomeController@index')->name('home.index');
        //配置
        Route::get('configurations', 'ConfigurationController@index')->name('configurations.index');

        // 上传
        Route::post('images', 'ImagesController@store')->name('images.store');

        // 手册
        Route::get('manuals', 'ManualController@index')->name('manual.index');
        Route::get('manuals/{id}', 'ManualController@show')->name('manual.show');

        // 文章
        Route::get('articles', 'ArticleController@index');
        Route::get('article', 'ArticleController@show');



        //获取客服
        Route::get('problems', 'ProblemsController@reply')->name('Problems.reply');

        // 站内在线客服（游客也可使用，登录用户带 token）
        Route::get('cs/config', 'CsController@config')->name('cs.config');
        Route::get('cs/messages', 'CsController@messages')->name('cs.messages');
        Route::post('cs/messages', 'CsController@send')->name('cs.send');

        // 假提现记录
        Route::get('orderscopy', 'OrderController@indexcopy')->name('order.indexcopy');

        Route::middleware('auth:api')->group(function () {

            Route::get('invite/text', 'RedController@inviteText')->name('reds.inviteText');
            Route::post('invite/send', 'RedController@sendText')->name('reds.sendText');

            // 公告
            Route::get('notices', 'NoticeController@index')->name('notice.index');
            Route::get('notices/top', 'NoticeController@top')->name('notice.top');
            Route::get('notices/eject', 'NoticeController@eject')->name('notice.eject');
            Route::get('notices/{id}', 'NoticeController@show')->name('notice.show');
            //宠物列表
            Route::get('pet-index', 'PetController@index')->name('pet.index');
            //宠物活动列表
            Route::get('pet-act-index', 'PetActivitiesController@index')->name('petact.index');
            Route::get('pet-act-detail', 'PetActivitiesController@detail')->name('petact.detail');

            //查看支付凭证
            Route::get('doc-show', 'PaymentDocController@docShow')->name('doc.show');
            //上传支付凭证
            Route::post('doc-upload', 'PaymentDocController@docUpload')->name('doc.upload');
            // 阶梯红包
            Route::get('red-ladders', 'RedController@redLadder')->name('reds.ladders');
            // 领红包
            Route::post('reds', 'RedController@store')->name('reds.store');
            //注册后领取红包
            Route::post('red-register', 'RedController@registerRed')->name('reds.registerRed');


            //已购买的宠物列表
            Route::get('pet-orders', 'PetOrderController@index')->name('user.pet.orders');
            //购买宠物
            Route::post('buy-pet', 'PetOrderController@buy')->name('user.buy.pet');
            //卖出宠物
            Route::post('sell-pet', 'PetOrderController@sell')->name('user.sell.pet');

            //取消预约订单
            Route::post('cancel-pet','PetOrderController@cancel')->name('user.cancel.pet');

            //加速记录
            Route::get('quicken', 'PetQuickenController@index')->name('user.pet.order.quicken.list');
            //加速
            Route::post('quicken', 'PetQuickenController@quicken')->name('user.pet.order.quicken');

            //红包提现到余额
            Route::post('red-to-balance', 'RedController@toBalance')->name('reds.red-to-balance');
            Route::post('invite-bind', 'UsersController@inviteCodeBind')->name('user.invite-bind');

            Route::patch('reds/', 'RedController@update')->name('reds.update');  // 打开红包
            // 刷新token
            Route::put('authorizations/current', 'AuthorizationsController@update')->name('authorizations.update');
            // 删除token
            Route::get('authorizations/current', 'AuthorizationsController@destroy')->name('authorizations.destroy');
            // 当前登录用户信息
//        Route::get('me', 'UsersController@me')->name('user.me');
            Route::get('me', 'UsersController@me')->name('user.me');
            // 编辑登录用户信息
            Route::patch('user', 'UsersController@update')->name('user.update');
            // 修改密码
            Route::post('password', 'UsersController@password')->name('user.password');
            Route::post('password_byold', 'UsersController@password')->name('user.password');
            // 设置支付密码
            Route::post('pay-password', 'UsersController@payPassword')->name('user.pay-password.store');
            Route::put('pay-password', 'UsersController@changePayPassword')->name('user.pay-password.update');

            // vips
            Route::get('vips', 'VipController@index')->name('vip.index');
            Route::post('vips', 'VipController@store')->name('vip.store');

            // 获取下级
            Route::get('subordinates', 'TeamController@subordinate')->name('user.subordinate');
            // 收入支出
            Route::get('accounts', 'UsersController@account')->name('user.account');
            //账变类型
            Route::get('accounts/type', 'UsersController@accountType')->name('account.type');
            // 首页统计
            Route::get('statistics', 'UsersController@statistics')->name('user.statistics');

            // 银行卡
            //Route::get('cards', 'CardController@index')->name('card.index');
            //Route::post('cards', 'CardController@store')->name('card.store');
            // 银行卡
            Route::resource('cards', 'CardController')->only('store','index','show');
            Route::post('editcards', 'CardController@editcards')->name('card.editcards');


            // 团队报告
            Route::get('teams', 'TeamController@index')->name('team.index');
            Route::get('team-users', 'TeamController@users')->name('team.users');
            Route::get('teams/getcommission', 'TeamController@getcommission')->name('teams.statistics.getcommission');

            // 任务
            /*
            Route::get('types', 'TypeController@index')->name('type.index');
            Route::post('task', 'TaskController@singleTask')->name('single.task.store');
            Route::get('tasks', 'TaskController@index')->name('task.index');
            Route::get('tasks/{id}', 'TaskController@show')->name('task.show');
            Route::post('tasks', 'TaskController@store')->name('task.store');
            Route::get('mytask', 'TaskController@myTask')->name('task.my');
            Route::patch('tasks', 'TaskController@submit')->name('task.submit');
            */
            // 领取的任务
            //Route::patch('task-logs', 'TaskLogController@update')->name('task-log.update');    // 修改

            // 举报投诉
            Route::get('complaints', 'ComplaintController@index')->name('complaint.index');
            Route::get('complaints/{complaint}', 'ComplaintController@show')->name('complaint.show');
            Route::post('complaints', 'ComplaintController@store')->name('complaint.store');
            // 回复
            Route::get('replies', 'ReplyController@index')->name('replies.index');
            Route::post('replies', 'ReplyController@store')->name('replies.store');

            // 城邦
            Route::get('states', 'StateController@index')->name('state.index');


            // 充值
            Route::post('recharge', 'PayController@recharge')->name('pay.recharge');
            // 提现
            Route::post('withdrawal', 'PayController@withdrawal')->name('pay.withdrawal');
            // 订单记录
            Route::get('orders', 'OrderController@index')->name('order.index');
            // 取消提现订单
            Route::post('withdrawal/cancel', 'OrderController@withdrawal_cancel')->name('order.withdrawal.cancel');


            // 排行榜
            //Route::get('ranking', 'RankingController@index')->name('ranking.index');

            //利息宝
            Route::post('interest/recharge', 'InterestController@recharge')->name('interest.recharge');
            Route::post('interest/withdrawal', 'InterestController@withdrawal')->name('interest.withdrawal');
            Route::get('interest/statistics', 'InterestController@statistics')->name('interest.statistics');
            Route::get('interest/logs', 'InterestController@logs')->name('interest.logs');
            Route::get('interest/revenues', 'InterestController@revenues')->name('interest.revenues');

            // 活动
            Route::get('activities', 'ActivityController@index')->name('activity.index');
            Route::post('activities', 'ActivityController@store')->name('activity.store');

            //签到
            Route::post('sign', 'SignController@store')->name('sign.store');



            // 朋友圈（列表在上面公开路由里）
            Route::post('friends', 'FriendController@store')->name('friend.store');
            Route::patch('friends/{friend}', 'FriendController@update')->name('friend.update');
            Route::delete('friends/{friend}', 'FriendController@delete')->name('friend.delete');

            //融资
            Route::post('financing', 'FinancingController@store')->name('financing.store');


            //客服自动回复
//            Route::post('problems', 'ProblemsController@reply')->name('Problems.reply');
//            //客服默认问题列表
//            Route::get('problems', 'ProblemsController@problems')->name('Problems.problems');


            /********新众筹 start******/
            //排期列表
            Route::get('period','FundPeriodController@index');
            //排期详情
            Route::get('period/{fund_id}','FundPeriodController@show');
            //彩种历史期开奖记录
            Route::get('record/{fund_id}','FundPeriodController@record');

            //购买众筹
            Route::resource('fundOrder','FundOrderController')->only(['index','store','update']);
            /********新众筹 end******/

            // 众筹
            Route::get('crowds', 'CrowdController@index')->name('crowd.index');
            Route::post('crowds', 'CrowdController@store')->name('crowd.store');
            Route::get('crowd', 'CrowdController@show')->name('crowd.show');
            Route::get('issues', 'IssueController@index')->name('issue.index');

            //本期参与  我的参与   往期揭晓
            Route::get('participate/issue', 'CrowdOrderController@participateIssue')->name('participate.issue');
            Route::get('participate/user', 'CrowdOrderController@participateUser')->name('participate.user');
            Route::get('participate/past', 'CrowdOrderController@pastOrder')->name('participate.past');
            //饲养
            Route::post('rearing', 'PetCrowdController@rearing')->name('PetCrowd.rearing');
            //转让
            Route::post('transfer', 'PetCrowdController@transfer')->name('PetCrowd.transfer');
            //支付通道
            Route::get('channels','PaymentController@index')->name('payment.index');
            //支付通道
            Route::post('recharge_address','PayController@recharge_address')->name('payment.recharge_address');

            Route::post('recharge_usdt','PayController@recharge_usdt')->name('payment.recharge_usdt');
            //充值货币
            Route::get('currencies','PayController@currencies')->name('pay.currencies');
            //提现货币
            Route::get('withdrawal_currencies','PayController@withdrawalCurrencies')->name('pay.withdrawalCurrencies');
            //支付通道
            Route::get('payment','PayController@payment')->name('payment.payment');


            //充值订单查询
            Route::get('event','OrderController@event')->name('OrderController.event');

            //排名
            Route::get('win','WinController@index')->name('win.index');

            //注册奖励详情
            Route::get('register-reward','UsersController@registerReward');
            //解锁注册奖励
            Route::post('unlock-register-reward','UsersController@unlockRegisterReward');

            //红包详情
            Route::get('red/detail', 'RedController@detail')->name('user.detail');
            //转移
            Route::post('pettransfer', 'PetOrderController@transfer')->name('PetOrder.transfer');

            Route::get('configurations/withdrawal_fee','PayController@withdrawalFee');

            //代理争霸
            Route::get('agency_contest', 'AgencyContestController@show')->name('agency_contest.show');
            Route::post('agency_contest/reward', 'AgencyContestController@reward')->name('agency_contest.reward');

            //业务员平台
            Route::get('sale/index', 'SaleController@index')->name('sale.index');
            Route::get('sale/groupLists', 'SaleController@groupLists')->name('sale.groupLists');
            Route::get('sale/groupShow', 'SaleController@groupShow')->name('sale.groupShow');
            Route::post('sale/groupAdd', 'SaleController@groupAdd')->name('sale.groupAdd');
            Route::post('sale/groupEdit', 'SaleController@groupEdit')->name('sale.groupEdit');
            Route::post('sale/groupDel', 'SaleController@groupDel')->name('sale.groupDel');
            Route::post('sale/groupSendAd', 'SaleController@groupSendAd')->name('sale.groupSendAd');
            Route::get('sale/botLists', 'SaleController@botLists')->name('sale.botLists');
            Route::post('sale/botAdd', 'SaleController@botAdd')->name('sale.botAdd');
            Route::post('sale/botEdit', 'SaleController@botEdit')->name('sale.botEdit');
            Route::post('sale/botDel', 'SaleController@botDel')->name('sale.botDel');
            Route::get('sale/templateLists', 'SaleController@templateLists')->name('sale.templateLists');
            Route::post('sale/templateAdd', 'SaleController@templateAdd')->name('sale.templateAdd');
            Route::post('sale/templateEdit', 'SaleController@templateEdit')->name('sale.templateEdit');
            Route::post('sale/templateDel', 'SaleController@templateDel')->name('sale.templateDel');
            Route::get('sale/groupListsSelect', 'SaleController@groupListsSelect')->name('sale.groupListsSelect');
            Route::get('sale/crondLists', 'SaleController@crondLists')->name('sale.crondLists');
            Route::post('sale/crondAdd', 'SaleController@crondAdd')->name('sale.crondAdd');
            Route::post('sale/crondEdit', 'SaleController@crondEdit')->name('sale.crondEdit');
            Route::post('sale/crondDel', 'SaleController@crondDel')->name('sale.crondDel');

        });
    });
});
