# 升级到 PHP 8.2+ / Laravel 10 说明

分支：`upgrade/php82-laravel10`

目标：在不改变现有功能的前提下，让项目运行在 PHP 8.2 / 8.3 上。
框架从 Laravel 7.23 升级到 Laravel 10；后台继续使用 laravel-admin（升级到 1.8.19）。

> 本次改动只修改了 `composer.json` 和代码，**还没有执行过 `composer update`，也没有在 PHP 8.2 环境里运行过**。
> 请按下面的步骤先在测试环境验证，确认无误后再上线。

---

## 一、服务器操作步骤

1. 安装 PHP 8.2 或 8.3，需要的扩展：
   `bcmath curl json openssl gmp mbstring pdo_mysql tokenizer xml ctype fileinfo dom gd`
   （`gmp` 是链上库需要的；用到 Swoole 命令的话还要装 `swoole` 扩展。
   PECL 的 `geoip` 扩展不支持 PHP 8，但用到它的 `IP` 中间件（`geoip_country_code_by_name`）实际不会执行：
   它所在的 `routes/apiv2.php` 路由组里没有路由，而且判断条件 `config('APP_DEBUG') === false` 永远不成立。所以不需要这个扩展。
   后台和首页的 IP 定位用的是 `danielme85/laravel-geoip2`，不依赖这个扩展。）
2. 拉取本分支代码。
3. **备份旧的 `vendor` 目录**（里面有人手动改过的文件，见第四节），然后：
   ```bash
   rm -rf vendor bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php bootstrap/cache/routes-*.php
   composer update --no-dev -o
   ```
   `composer update` 会重新生成 `composer.lock`，请把新的 lock 文件一起提交。
4. 清理缓存：
   ```bash
   php artisan optimize:clear
   ```
5. 检查 `.env` 里的 `JWT_SECRET` **至少 32 个字符**（新版 JWT 库要求 HS256 密钥 ≥ 256 位；当前仓库里的 `.env` 是 64 位，满足要求）。
6. 重启 PHP-FPM、队列进程（`queue:work`）、`short-schedule:run` 守护进程、Swoole 进程。

---

## 二、依赖变化

| 包 | 原版本 | 新版本 | 说明 |
|---|---|---|---|
| php | ^7.2.5（实际需 7.4） | ^8.2 | |
| laravel/framework | 7.23 | ^10.48 | |
| encore/laravel-admin | 1.8.11 | ^1.8.19 | 后台框架不变 |
| tymon/jwt-auth | 1.0.0 | ^2.1 | 接口不变，旧 token 继续有效 |
| guzzlehttp/guzzle | 6.5 | ^7.2 | |
| fenguoz/tron-php | 1.3 | ^2.1 | 底层 Tron 库换成 `fenguoz/tron-api`（iexbase 的分叉，命名空间 `IEXBase\TronAPI` 不变） |
| fenguoz/bsc-php | 1.1 | ^2.3 | |
| doctrine/dbal | 2.10 | ^3.5 | |
| predis/predis | 1.1 | ^2.0 | |
| spatie/laravel-short-schedule | 1.3 | ^1.5 | |
| laravel-notification-channels/webpush | 5.1 | ^7.1 | |
| jaeger/querylist | 4.2 | ^4.4 | |
| maatwebsite/excel | 3.1.x | ^3.1.48 | |
| aliyuncs/oss-sdk-php | 2.4（间接依赖） | ^2.6 | 改为直接依赖 |
| laravel/tinker | 2.4 | ^2.8 | |
| require-dev | facade/ignition、fzaninotto/faker、collision 4、phpunit 8 | spatie/laravel-ignition、fakerphp/faker、collision 7、phpunit 10 | |

**移除的包**

| 包 | 原因 |
|---|---|
| fideloper/proxy | Laravel 9 起内置，`TrustProxies` 改用框架自带中间件 |
| fruitcake/laravel-cors | Laravel 9 起内置，改用 `Illuminate\Http\Middleware\HandleCors`，`config/cors.php` 不变 |
| jacobcyl/ali-oss-storage | 只支持 Flysystem 1，换成项目内自己实现的 OSS 驱动（见下文） |
| propaganistas/laravel-phone | 项目代码中没有使用（没有 `phone` 校验规则，也没有引用它的类） |
| lokielse/omnipay-alipay、lokielse/omnipay-wechatpay | 项目代码中没有使用，并且依赖 Guzzle 6 适配器，与 Guzzle 7 冲突 |

**改为项目内维护的包（`packages/` 目录，通过 composer path 仓库安装）**

| 包 | 原因与改动 |
|---|---|
| codingyu/laravel-ueditor（2.0.2 → 本地 2.0.3） | 官方版本限制最高 Laravel 8。代码只改了一处：UEditor“在线图片/文件列表”把 Flysystem 3 的返回值转换成原来的数组格式。前端静态资源已经发布在 `public/vendor/ueditor`，所以本地副本没有包含 `src/assets`。 |
| laravel-admin-ext/media-manager（2.0.3 → 本地 2.0.4） | 原代码用了 Flysystem 1 的 `Local` 适配器、`applyPathPrefix()`、`getConfig()->has()`，已经换成 Flysystem 3 / Laravel 10 的等价写法。 |

---

## 三、代码改动

| 文件 | 改动 |
|---|---|
| `composer.json` | 见上表；新增 `repositories`（path 仓库） |
| `app/Filesystem/AliOssAdapter.php`（新增） | 基于 Flysystem 3 的阿里云 OSS 驱动。配置项（`access_id`、`access_key`、`bucket`、`endpoint`、`cdnDomain`、`ssl`、`isCName`、`debug`）、URL 拼接规则、上传时的请求头、目录列表和递归规则，都照原 jacobcyl 驱动实现 |
| `app/Providers/AliOssServiceProvider.php`（新增） | 注册 `oss` 驱动，替代 `Jacobcyl\AliOSS\AliOssServiceProvider` |
| `config/app.php` | providers 里的 `Jacobcyl\AliOSS\AliOssServiceProvider` 换成 `App\Providers\AliOssServiceProvider` |
| `app/Http/Kernel.php` | `\Fruitcake\Cors\HandleCors` → `\Illuminate\Http\Middleware\HandleCors` |
| `app/Http/Middleware/TrustProxies.php` | 父类改成框架自带的；`HEADER_X_FORWARDED_ALL` 在 Symfony 6 中已删除，改成等价的 `FOR | HOST | PORT | PROTO` |
| `app/Http/Middleware/CheckForMaintenanceMode.php` | 父类改成 `PreventRequestsDuringMaintenance`（旧类在 Laravel 9 中已删除），行为相同 |
| `app/Providers/AppServiceProvider.php` | 增加 `Paginator::useBootstrap()`（Laravel 8 起默认分页视图改为 Tailwind，这一行恢复原来的 Bootstrap 样式） |
| `public/index.php` | `if ($_SERVER['HTTP_X_FORWARDED_FOR'])` 改成 `!empty(...)`，避免没有该请求头时 PHP 8 输出 Warning；逻辑不变 |
| `phpunit.xml` | `<filter><whitelist>` 改成 PHPUnit 10 的 `<source><include>` |

**检查过、确认不需要改的内容**
- Tron（`TronService`、`TRC20Service`）用到的所有方法，在新的 `IEXBase\TronAPI` 中签名和实现一致；`\Tron\Api`、`\Tron\TRC20::transactionReceipt()` 的用法不变。
- BSC（`BscService`）用到的 `NodeApi`、`Bnb`、`BEP20::balance()`/`getTransactionReceipt()`、`PEMHelper`、`Formatter`、`Utils`，在 2.3 版中没有变化。
- JWT 默认配置在 1.0 和 2.x 之间没有差别（项目没有 `config/jwt.php`，用的是包里的默认配置）。
- 没有用到 Laravel 10 中删除的 `$dates` 属性、`dispatchNow`、把 `DB::raw()` 当字符串拼接、`Redirect::home()` 等写法。
- 没有用到 PHP 8 中删除的语法或函数（`each()`、`create_function`、`$str{0}`、旧参数顺序的 `implode`、不带括号的嵌套三元等）。
- `RouteServiceProvider` 里的 `$namespace`、`map()` 路由加载方式，Laravel 10 仍然支持。
- 失败任务表仍然使用 `database` 驱动（不需要 uuid 字段）。
- Laravel 10 遇到 PHP 弃用警告只会写到 `null` 日志通道，不会抛异常（Laravel 7 会抛异常），所以第三方包里的 PHP 8.1/8.2 弃用写法不影响运行。

---

## 四、服务器上 vendor 被手动改过的文件

我把旧 `vendor` 里的 203 个包逐一和官方源码做了对比，只有两个包被手动改过（`composer update` 会把它们覆盖掉）：

1. `vendor/jacobcyl/ali-oss-storage/src/AliOssAdapter.php`：`getUrl()` 去掉了“文件不存在就抛异常”的检查。新的 `App\Filesystem\AliOssAdapter::getUrl()` 保留了这个改动。
2. `vendor/fenguoz/bsc-php/src/BEP20.php`：`transfer()` 里写死了 `gasPrice = 0x5f5e100`。这个方法在项目里**没有被调用**（`BscService::transfer()` 自己构造交易，`$this->bep20->transfer()` 那行已被注释），所以升级不影响现有转账。如果以后要启用 `BEP20::transfer()`，需要重新确认 gas 设置。

（`spomky-labs/base64url` 没能下载到官方源码，没有对比；它是 web-push 的间接依赖，项目代码没有用到。）

---

## 五、重点测试清单

PHP 8 把一部分原来只是 Warning 的情况改成了直接抛 `TypeError` / `ValueError` / `DivisionByZeroError`。这些属于 `Error`，**`catch (\Exception $e)` 接不住**。
项目里有 56 个文件、约 100 处 `catch (\Exception ...)`，集中在支付回调、链上转账、队列任务中。正常数据下不会触发，但遇到异常数据（例如除数为 0、对 null 调用 `count()`、对非数字字符串做运算）时，原来会进入 catch 分支（比如重新投递任务），现在会直接让任务失败。请重点测试下面这些：

- [ ] 后台登录、菜单、各列表页的筛选、排序、分页、导出
- [ ] 后台图片/文件上传（`oss` 磁盘），上传后返回的 URL 和原来的格式一致
- [ ] UEditor：上传图片、抓取远程图片、在线图片列表
- [ ] 媒体管理器（如果有在用）
- [ ] API 登录、刷新 token、退出；**旧版本签发的 token 继续有效**
- [ ] 被禁用用户的 token 失效逻辑（`Authenticate` 中间件）
- [ ] 跨域请求（CORS）、限流（`throttle.authenticated`）
- [ ] 获取客户端 IP（`TrustProxies` 和 `public/index.php` 里的 X-Forwarded-For 处理）
- [ ] TRC20 / TRX：生成地址、查询余额、转账、授权转账、交易查询（`Orderstate`、`CollectTrc` 任务）
- [ ] BEP20 / BNB：生成地址、余额、转账、交易回执
- [ ] 各第三方支付的下单和回调
- [ ] 短信验证码（easy-sms 及自定义短信服务）
- [ ] 定时任务（`schedule:run`）、秒级任务（`short-schedule:run`）、队列
- [ ] Excel 导入（`PhonesImport`、`TransImport`）
- [ ] GeoIP 查询、验证码图片、Google 翻译任务、YouTube 抓取命令（QueryList）
- [ ] 维护模式 `php artisan down` / `up`

如果 `composer update` 报依赖冲突，把完整报错发给我，我再调整版本约束。

---

## 六、原来写死在代码里的密钥（这一版改成从 .env 读取）

为了能公开代码，下面这些原来写死在代码里的值改成了从 `.env` 读取（经 `config/services.php`）。
**部署前必须把原来的值填进服务器的 `.env`，否则对应功能会失效：**

| .env 配置项 | 用在哪里 | 原来的位置 |
|---|---|---|
| `ONEPAY_AES_KEY` | OnePay 回调解密 | `app/Http/Controllers/Api/v1/OnePayController.php` 的 `$password` |
| `ONEPAY_IN_AES_KEY` | OnePayIn 回调解密 | `app/Http/Controllers/Api/v1/OnePayInController.php` 的 `$password` |
| `NODEREAL_API_KEY` | BEP20 充值记录查询（`BscService::getTransactions`） | `app/Services/BscService.php` |
| `K780_APPKEY`、`K780_SIGN` | 汇率更新命令 `updateExchangeRate` | `app/Console/Commands/ExchangeRate.php` |
| `BNB_TRANSFER_PRIVATE_KEY` | `BnbTransfer` 命令的转出钱包私钥 | `app/Console/Commands/BnbTransfer.php` |

另外：
- 删除了 `.env.trxfund.io`，并清空了 `.env.example` 里的所有账号、密码和密钥（`APP_KEY`、`DB_*`、`JWT_SECRET`、阿里云 OSS、短信、友盟、VAPID、`SUPER_PASSWORD`、`COLLECT_KEY` 等），服务器上现有的 `.env` 不受影响。
- `BscService.php` 注释里的示例私钥、`ExchangeRate.php` 注释里的账号信息已删除。
- 这些密钥曾经出现在旧仓库的提交历史里，建议尽快更换，尤其是钱包私钥和阿里云 AccessKey。
