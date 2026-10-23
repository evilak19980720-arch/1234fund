<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CustomerService\AiAgent;
use App\Services\CustomerService\CsSettings;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;

/**
 * 客服设置
 * @package App\Admin\Controllers
 */
class CsSettingController extends Controller
{
    public function index(Content $content)
    {
        $values = [];
        foreach (array_keys(CsSettings::ITEMS) as $key) {
            $values[$key] = CsSettings::get($key);
        }

        $form = new Form($values);
        $form->action(admin_url('cs-settings'));
        $form->disableReset();

        $form->radio('cs_mode', '客服模式')->options([
            CsSettings::MODE_INTERNAL => '本站在线客服',
            CsSettings::MODE_EXTERNAL => '第三方客服链接',
        ])->help('选择第三方时，前端客服按钮直接打开下面的链接');
        $form->text('customer_service_url', '第三方客服链接');
        $form->radio('cs_ai_enabled', 'AI客服')->options([1 => '开启', 0 => '关闭'])
            ->help(config('customer_service.deepseek.api_key') ? '关闭后，登录用户的消息直接进入人工待处理' : '<span class="text-danger">.env 未配置 DEEPSEEK_API_KEY，AI 客服不可用</span>');
        $form->textarea('cs_ai_prompt', 'AI提示词')->rows(14)
            ->help('写平台业务说明：产品规则、充值提现流程与费用、常见问题、语气要求等。「问题列表」里的问答也会自动提供给 AI。');
        $form->textarea('cs_welcome', '欢迎语')->rows(2)->help('进入客服页时显示');
        $form->textarea('cs_offline_notice', '转人工提示')->rows(2)->help('转接人工后提示给用户，例如人工客服工作时间');
        $form->number('cs_ai_daily_limit', '每人每天AI回复上限')->min(0)->help('0 表示不限制');

        $rules = new Box('固定规则（代码内置，提示词无法覆盖）', '<pre style="white-space:pre-wrap;margin:0">' . e(AiAgent::BASE_RULES) . '</pre>');
        $rules->collapsable();

        return $content
            ->title('客服设置')
            ->description('在线客服 / AI 客服')
            ->row(new Box('设置', $form))
            ->row($rules);
    }

    public function store(Request $request)
    {
        $data = $request->only(array_keys(CsSettings::ITEMS));
        $data['cs_ai_daily_limit'] = max(0, (int) ($data['cs_ai_daily_limit'] ?? 0));
        CsSettings::save($data);

        admin_toastr('保存成功');
        return redirect(admin_url('cs-settings'));
    }
}
