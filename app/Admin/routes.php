<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'domain' => config('admin.route.domain'),
    'prefix' => config('admin.route.prefix'),
    'namespace' => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
    'as' => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
    //数据总览
    $router->resource('sum', SumController::class);
    //今日统计
    $router->resource('today', TodayController::class);
    $router->resource('today-withdrawal-first', TodayWithdrawalFirstController::class);
    //复充用户
    $router->resource('orders-again', OrderAgainController::class);
    //阶梯红包
    $router->resource('red-ladders', RedLadderController::class);
    //支付凭证
    $router->resource('payment-docs', PaymentDocController::class);
    //宠物订单
    $router->resource('pet-orders', PetOrderController::class);
    //宠物管理
    $router->resource('pets', PetController::class);
    $router->any('pet/selectApi', 'PetController@selectApi');
    // banner
    $router->resource('banners', BannerController::class);
    // 会员
    $router->resource('users', UserController::class);
    // 收益
    $router->resource('accounts', AccountController::class);
    // vip
    $router->resource('vips', VipController::class);
    // 银行
    $router->resource('banks', BankController::class);
    //银行卡
    $router->resource('cards', CardController::class);
    // 任务类型
    $router->resource('types', TypeController::class);
    // 任务
    $router->resource('tasks', TaskController::class);
    // 任务待审核
    $router->resource('task-logs', TaskLogController::class);
    // 任务记录
    $router->resource('task-logs-all', TaskLogFinishController::class);
    // 投诉
    $router->resource('complaints', ComplaintController::class);
    // 营销规则
    $router->resource('rules', RuleController::class);
    // 公告
    $router->resource('notices', NoticeController::class);
    // 公告
    $router->resource('configurations', ConfigurationController::class);
    // 活动
    $router->resource('activities', ActivityController::class);
    // 利息宝
    $router->resource('interests', InterestController::class);
    // 利息宝记录
    $router->resource('interest-logs', InterestLogController::class);
    // 利息宝收益
    $router->resource('interest-revenues', InterestRevenueController::class);
    // 提现订单
    $router->resource('orders', OrderController::class);
    // 订单操作日志
    $router->resource('order-logs', OrderLogController::class);
    // 充值订单
    $router->resource('orders-recharge', OrderRechargeController::class);
    // 手册
    $router->resource('manuals', ManualController::class);
    // 手册
    $router->resource('version', VersionController::class);
    // 客服
    $router->resource('customers', CustomerController::class);
    // 文章
    $router->resource('articles', ArticleController::class);
    // 安装数量
    $router->resource('install-count', InstallCountController::class);
    // 激活次数
    $router->resource('records', ActivationController::class);
    // youtube
    $router->resource('youtubes', YoutubeController::class);
    // 视频分类
    $router->resource('video-types', VidedoTypeController::class);

    $router->get('api/types', 'TypeController@getTypes');
    // 红包记录
    $router->resource('red-logs', RedLogController::class);
    //层级管理
    $router->resource('hierarchies', HierarchyController::class);
    //用户详情
    $router->get('user-detail/{id}', 'UserController@userDetail');
    $router->post('userdata', 'UserController@userData');
    // 图集
    $router->resource('atlases', AltasController::class);
    $router->resource('replies', ReplyController::class);
    //支付
    $router->resource('payments', PaymentController::class);
    $router->resource('paymentcodes', PaymentThirdCodeController::class);
    // 朋友圈
    $router->resource('friends', FriendController::class);
    // push
    $router->resource('messages', MessageController::class);
    $router->resource('crowds', CrowdController::class);

    //新众筹
    $router->resource('fund', FundController::class);
    //新众筹排期
    $router->resource('fund-period', FundPeriodController::class);
    $router->resource('fund-amount-accounts', FundAmountAccountController::class);
    //新众筹购买记录
    $router->resource('fund-order', FundOrderController::class);


    $router->get('api/pets','PetController@getPets');
    $router->resource('issues', IssueController::class);

    $router->resource('problem', ProblemController::class);
    $router->resource('crowdorder', CrowdOrderController::class);
    $router->resource('agents', AgentStatisticController::class);
    $router->resource('agent-confs', AgentConfController::class);

    //宠物秒杀活动
    $router->resource('pet-activities', PetActivityController::class);
    //会员统计
    $router->resource('user-statistic', UserStatisticController::class);
    $router->get('user-statistic-detail/{id}', 'UserStatisticController@userStatisticDetail');

    //获奖排名
    $router->resource('wins', WinController::class);

    //国家管理
    $router->resource('countries', CountryController::class);
    //语言管理
    $router->resource('locales', LocaleController::class);
    //翻译管理
    $router->resource('trans', TransController::class);
    //后台翻译
    $router->resource('trans-admin', TransAdminController::class);

    //融资
    $router->resource('financing', FinancingController::class);

    //分享手机号
    $router->resource('sharephone', SharephoneController::class);

    //充值地址
    $router->resource('recharge-addresses', RechargeAddressController::class);

    //货币管理
    $router->resource('currencies', CurrencyController::class);

    //像素管理
    $router->resource('pixcels', PixcelController::class);

    //归集任务TRC
    $router->resource('collect-trctask', CollectTrctaskController::class);
    //归集任务TRC详情
    $router->resource('collect-trctask-logs', CollectTrctaskLogController::class);

    //代理争霸--活动列表
    $router->resource('agency-contest', AgencyContestController::class);
    //代理争霸--虚拟用户
    $router->resource('agency-contest-fake-users', AgencyContestFakeUsersController::class);


});
