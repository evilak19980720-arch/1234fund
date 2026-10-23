<style>
    .cs-chat { height: 560px; overflow-y: auto; background: #f4f6f9; padding: 15px; border-radius: 4px; }
    .cs-msg { margin-bottom: 12px; display: flex; flex-direction: column; }
    .cs-msg .cs-meta { font-size: 12px; color: #999; margin-bottom: 3px; }
    .cs-msg .cs-bubble { display: inline-block; max-width: 75%; padding: 8px 12px; border-radius: 6px; white-space: pre-wrap; word-break: break-word; background: #fff; }
    .cs-msg.cs-user { align-items: flex-start; }
    .cs-msg.cs-ai, .cs-msg.cs-agent { align-items: flex-end; }
    .cs-msg.cs-ai .cs-bubble { background: #d9edf7; }
    .cs-msg.cs-agent .cs-bubble { background: #dff0d8; }
    .cs-msg.cs-system { align-items: center; }
    .cs-msg.cs-system .cs-bubble { background: transparent; color: #999; font-size: 12px; }
</style>

<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">聊天记录</h3>
                <span class="label label-default" id="cs-status">{{ \App\Models\CsSession::STATUS_MAP[$session->status] ?? $session->status }}</span>
            </div>
            <div class="box-body">
                <div class="cs-chat" id="cs-chat"></div>
            </div>
            <div class="box-footer">
                <textarea class="form-control" id="cs-input" rows="3" placeholder="输入回复，Ctrl+Enter 发送。发送后会话转为人工接待，AI 不再回复。"></textarea>
                <div style="margin-top:10px">
                    <button class="btn btn-primary" id="cs-send">发送</button>
                    <button class="btn btn-default cs-status-btn" data-status="ai">交回 AI</button>
                    <button class="btn btn-default cs-status-btn" data-status="closed">结束会话</button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">会话信息</h3></div>
            <div class="box-body">
                <p>用户：
                    @if($session->user_id)
                        <a href="{{ $userUrl }}" target="_blank">{{ $session->user_id }} / {{ optional($session->user)->name }}</a>
                    @else
                        游客
                    @endif
                </p>
                <p>语言：{{ $session->lang }}</p>
                <p>IP：{{ $session->ip }}</p>
                <p>创建时间：{{ $session->created_at }}</p>
                @if($session->transfer_reason)
                    <p>转人工原因：<br>{{ $session->transfer_reason }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var baseUrl = @json($baseUrl);
        var token = @json(csrf_token());
        var lastId = 0;
        var roleText = {user: '用户', ai: 'AI', agent: '人工', system: '系统'};
        var $chat = $('#cs-chat');

        if (window.csChatTimer) {
            clearInterval(window.csChatTimer);
        }

        function render(messages) {
            var nearBottom = lastId === 0 || $chat[0].scrollHeight - $chat.scrollTop() - $chat.outerHeight() < 80;
            messages.forEach(function (m) {
                lastId = Math.max(lastId, m.id);
                var meta = roleText[m.role] + (m.agent ? '（' + m.agent + '）' : '') + ' · ' + m.created_at;
                if (m.tools && m.tools.length) {
                    meta += ' · 查询：' + m.tools.join(', ');
                }
                var $msg = $('<div class="cs-msg"></div>').addClass('cs-' + m.role);
                $msg.append($('<div class="cs-meta"></div>').text(meta));
                $msg.append($('<div class="cs-bubble"></div>').text(m.content));
                $chat.append($msg);
            });
            if (messages.length && nearBottom) {
                $chat.scrollTop($chat[0].scrollHeight);
            }
        }

        function load() {
            if (!document.getElementById('cs-chat')) {
                clearInterval(window.csChatTimer);
                return;
            }
            $.get(baseUrl + '/messages', {after_id: lastId}, function (res) {
                $('#cs-status').text(res.status_text);
                render(res.messages);
            });
        }

        function send() {
            var content = $.trim($('#cs-input').val());
            if (!content) {
                return;
            }
            $('#cs-send').prop('disabled', true);
            $.post(baseUrl + '/reply', {content: content, _token: token}, function (res) {
                if (res.status) {
                    $('#cs-input').val('');
                    load();
                } else {
                    toastr.error(res.message);
                }
            }).always(function () {
                $('#cs-send').prop('disabled', false);
            });
        }

        $('#cs-send').on('click', send);
        $('#cs-input').on('keydown', function (e) {
            if (e.ctrlKey && e.keyCode === 13) {
                send();
            }
        });
        $('.cs-status-btn').on('click', function () {
            $.post(baseUrl + '/status', {status: $(this).data('status'), _token: token}, function (res) {
                res.status ? toastr.success('已更新') : toastr.error(res.message);
                load();
            });
        });

        load();
        window.csChatTimer = setInterval(load, 3000);
    })();
</script>
