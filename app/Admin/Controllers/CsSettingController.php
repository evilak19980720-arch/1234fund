<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CsAiProvider;
use App\Services\CustomerService\AiAgent;
use App\Services\CustomerService\AiProviders;
use App\Services\CustomerService\CsSettings;
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
        $form->text('customer_service_url', '第三方客服链接')
            ->help('本站客服模式下，AI 无法解答或资金问题需要核实时，会把这个链接发给用户');
        $form->radio('cs_ai_enabled', 'AI客服')->options([1 => '开启', 0 => '关闭'])
            ->help('关闭后，用户发消息会收到上面的第三方客服链接');
        $form->textarea('cs_ai_prompt', 'AI提示词')->rows(14)
            ->help('写平台业务说明：产品规则、充值提现流程与费用、常见问题、语气要求等。「问题列表」里的问答也会自动提供给 AI。');
        $form->textarea('cs_welcome', '欢迎语')->rows(2)->help('进入客服页时显示');
        $form->number('cs_ai_daily_limit', '每人每天AI回复上限')->min(0)->help('0 表示不限制');

        $rules = new Box('固定规则（代码内置，提示词无法覆盖）', '<pre style="white-space:pre-wrap;margin:0">' . e(AiAgent::BASE_RULES) . '</pre>');
        $rules->collapsable();

        return $content
            ->title('客服设置')
            ->description('在线客服 / AI 客服')
            ->row(new Box('AI 服务商', view('Admin.cs.ai', $this->aiViewData())->render()))
            ->row(new Box('客服设置', $form))
            ->row($rules);
    }

    public function store(Request $request)
    {
        $data = $request->only(array_diff(array_keys(CsSettings::ITEMS), ['cs_ai_provider']));
        $data['cs_ai_daily_limit'] = max(0, (int) ($data['cs_ai_daily_limit'] ?? 0));
        CsSettings::save($data);

        admin_toastr('保存成功');
        return redirect(admin_url('cs-settings'));
    }

    /**
     * 保存服务商配置并设为当前使用
     */
    public function saveAi(Request $request)
    {
        $provider = (string) $request->get('provider');
        if (!AiProviders::exists($provider)) {
            return response()->json(['status' => false, 'message' => '请选择 AI 服务商']);
        }

        $row = CsAiProvider::query()->firstOrNew(['provider' => $provider]);
        $apiKey = trim((string) $request->get('api_key'));
        if ($apiKey !== '') {
            $row->api_key = $apiKey;
        }
        if (!$row->api_key) {
            return response()->json(['status' => false, 'message' => '请填写 API Key']);
        }
        $row->model = trim((string) $request->get('model'));
        $row->base_url = trim((string) $request->get('base_url'));
        $row->save();

        CsSettings::save(['cs_ai_provider' => $provider]);

        return response()->json(['status' => true, 'message' => '已保存，当前使用：' . AiProviders::LIST[$provider]['name']]);
    }

    /**
     * 用表单里的配置（Key 留空则用已保存的）发一条测试消息
     */
    public function testAi(Request $request)
    {
        $provider = (string) $request->get('provider');
        if (!AiProviders::exists($provider)) {
            return response()->json(['status' => false, 'message' => '请选择 AI 服务商']);
        }

        $apiKey = trim((string) $request->get('api_key'));
        if ($apiKey === '') {
            $apiKey = (string) optional(CsAiProvider::query()->where('provider', $provider)->first())->api_key;
        }
        if ($apiKey === '') {
            return response()->json(['status' => false, 'message' => '请填写 API Key']);
        }

        $config = AiProviders::resolve($provider, $apiKey, trim((string) $request->get('model')), trim((string) $request->get('base_url')));
        $start = microtime(true);
        try {
            $result = AiProviders::client($config)->run(
                'You are a connectivity test. Reply with one short sentence.',
                [['role' => 'user', 'content' => 'Hello, are you working? Reply "OK" and your model name.']],
                [],
                function () {
                    return [];
                }
            );
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => '连接失败：' . mb_substr($e->getMessage(), 0, 500)]);
        }

        $seconds = round(microtime(true) - $start, 1);
        if ($result['content'] === '') {
            return response()->json(['status' => false, 'message' => "接口已响应（{$seconds} 秒），但没有返回文字内容"]);
        }

        return response()->json([
            'status' => true,
            'message' => "可用（{$config['model']}，{$seconds} 秒）回复：" . mb_substr($result['content'], 0, 200),
        ]);
    }

    protected function aiViewData(): array
    {
        $saved = [];
        foreach (CsAiProvider::query()->get() as $row) {
            $saved[$row->provider] = [
                'api_key' => AiProviders::maskKey($row->api_key),
                'model' => $row->model,
                'base_url' => $row->base_url,
            ];
        }

        $active = AiProviders::active();

        return [
            'providers' => AiProviders::LIST,
            'saved' => $saved,
            'current' => (string) CsSettings::get('cs_ai_provider'),
            'active' => $active ? ['provider' => $active['provider'], 'model' => $active['model']] : null,
            'saveUrl' => admin_url('cs-settings/ai'),
            'testUrl' => admin_url('cs-settings/ai-test'),
        ];
    }
}
