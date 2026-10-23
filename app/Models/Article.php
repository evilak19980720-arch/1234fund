<?php

namespace App\Models;

use App\Handlers\HtmlHandler;
use App\Handlers\KeysHandler;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 文章
 * @package App\Models
 */
class Article extends Model
{
    use DefaultDatetimeFormat;

    const ABOUT_US = 'AboutUs';//关于我们
    const HELP_CENTER = 'HelpCenter';//帮助中心
    const INVITE_FRIENDS = 'InviteFriends';//邀请好友
    const TEAM =  'TeamsDescription';//团队介绍
    const PRIVACY_POLICY = 'PrivacyPolicy';//隐私政策
    const USER_AGREEMENT = 'UserAgreement';//用户协议
    const RANK = 'Rank';//排名描述
    const RED_RULE = 'redRule';//描述
    const AGENCY_CONTEST_RULE = 'Agency_contest';//代理争霸

    const ARTICLE_OPTIONS = [
        self::ABOUT_US => '关于我们',
        self::HELP_CENTER => '文章中心',
        self::INVITE_FRIENDS => '邀请好友',
        self::TEAM=>'团队介绍',
        self::PRIVACY_POLICY => '隐私政策',
        self::USER_AGREEMENT => '用户协议',
        self::RANK => '排名描述',
        self::RED_RULE => '红包规则',
        self::AGENCY_CONTEST_RULE => '代理争霸'
    ];

    public static function getByKey($key)
    {
        $_key = sprintf(KeysHandler::ARTICLE, $key);

        $article = Cache::get($_key);
        if (!$article) {
            $article = self::where('key','=',$key)->first();
            $article ? Cache::put($_key, $article, 60 * 60 * 24) : Cache::put($_key, '', 60);
        }
        if(isset($article['content'])){
            $article['content'] =HtmlHandler::makeContent($article['content']);
        }

        return $article;
    }
}
