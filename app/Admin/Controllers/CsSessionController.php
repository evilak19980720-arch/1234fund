<?php

namespace App\Admin\Controllers;

use App\Models\AdminUser;
use App\Models\CsMessage;
use App\Models\CsSession;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

/**
 * 客服会话 / 人工客服工作台
 * @package App\Admin\Controllers
 */
class CsSessionController extends AdminController
{
    protected $title = '客服会话';

    protected function grid()
    {
        $grid = new Grid(new CsSession());
        $grid->model()->with('user:id,name')
            ->orderByRaw("FIELD(status, 'pending', 'human', 'ai', 'closed')")
            ->orderBy('last_message_at', 'desc');
        if (Admin::user()->isRole('agents')) {
            $grid->model()->whereHas('user', function ($query) {
                $query->where('admin_user_id', Admin::user()->id);
            });
        }

        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('status', '状态')->select(CsSession::STATUS_MAP);
            $filter->equal('user_id', '用户ID');
        });

        $grid->column('id', 'ID');
        $grid->column('user_id', '用户')->display(function ($userId) {
            return $userId ? sprintf('%d / %s', $userId, e(optional($this->user)->name)) : '游客';
        });
        $grid->column('status', '状态')->using(CsSession::STATUS_MAP)->label([
            CsSession::STATUS_PENDING => 'danger',
            CsSession::STATUS_HUMAN => 'warning',
            CsSession::STATUS_AI => 'success',
            CsSession::STATUS_CLOSED => 'default',
        ]);
        $grid->column('admin_unread', '未读')->display(function ($count) {
            return $count ? "<span class=\"badge bg-red\">{$count}</span>" : '';
        });
        $grid->column('transfer_reason', '转人工原因')->limit(40);
        $grid->column('admin_user_id', '接待客服')->display(function ($id) {
            return $id ? e(AdminUser::query()->where('id', $id)->value('name')) : '';
        });
        $grid->column('last_message_at', '最后消息时间');
        $grid->column('created_at', '创建时间');

        return $grid;
    }

    /**
     * 会话详情：聊天记录 + 回复
     */
    public function show($id, Content $content)
    {
        $session = $this->findSession($id);
        $session->admin_unread = 0;
        $session->save();

        return $content
            ->title('客服会话 #' . $session->id)
            ->description(CsSession::STATUS_MAP[$session->status] ?? $session->status)
            ->body(view('Admin.cs.chat', [
                'session' => $session,
                'baseUrl' => admin_url('cs-sessions/' . $session->id),
                'userUrl' => $session->user_id ? admin_url('users/' . $session->user_id) : '',
            ]));
    }

    public function messages($id, Request $request)
    {
        $session = $this->findSession($id);
        $afterId = (int) $request->get('after_id', 0);

        $messages = CsMessage::query()->where('session_id', $session->id)
            ->where('id', '>', $afterId)->orderBy('id')->limit(200)->get();

        if ($messages->isNotEmpty() && $session->admin_unread) {
            $session->admin_unread = 0;
            $session->save();
        }

        return response()->json([
            'status' => $session->status,
            'status_text' => CsSession::STATUS_MAP[$session->status] ?? $session->status,
            'messages' => $messages->map(function (CsMessage $message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'tools' => $message->meta['tools'] ?? [],
                    'agent' => $message->admin_user_id ? AdminUser::query()->where('id', $message->admin_user_id)->value('name') : '',
                    'created_at' => (string) $message->created_at,
                ];
            }),
        ]);
    }

    /**
     * 人工回复（自动接管会话，AI 不再回复）
     */
    public function reply($id, Request $request)
    {
        $session = $this->findSession($id);
        $content = trim((string) $request->get('content', ''));
        if ($content === '') {
            return response()->json(['status' => false, 'message' => '回复内容不能为空']);
        }

        $session->status = CsSession::STATUS_HUMAN;
        $session->admin_user_id = Admin::user()->id;
        $session->addMessage(CsMessage::ROLE_AGENT, mb_substr($content, 0, 2000), Admin::user()->id);

        return response()->json(['status' => true]);
    }

    /**
     * 交回 AI / 结束会话
     */
    public function status($id, Request $request)
    {
        $session = $this->findSession($id);
        $status = $request->get('status');
        if (!in_array($status, [CsSession::STATUS_AI, CsSession::STATUS_CLOSED, CsSession::STATUS_HUMAN])) {
            return response()->json(['status' => false, 'message' => '状态错误']);
        }

        $session->status = $status;
        $session->admin_user_id = $status === CsSession::STATUS_HUMAN ? Admin::user()->id : 0;
        $session->admin_unread = 0;
        $session->save();

        return response()->json(['status' => true]);
    }

    protected function findSession($id): CsSession
    {
        $query = CsSession::query();
        if (Admin::user()->isRole('agents')) {
            $query->whereHas('user', function ($query) {
                $query->where('admin_user_id', Admin::user()->id);
            });
        }
        return $query->findOrFail($id);
    }
}
