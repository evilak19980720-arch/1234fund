<div class="form-horizontal" id="cs-ai-box">
    <div class="form-group">
        <label class="col-sm-2 control-label">当前使用</label>
        <div class="col-sm-8" style="padding-top:7px">
            @if($active)
                <span class="label label-success">{{ $providers[$active['provider']]['name'] ?? $active['provider'] }}</span>
                <span class="text-muted">模型：{{ $active['model'] }}</span>
            @else
                <span class="label label-danger">未配置</span>
                <span class="text-muted">请选择服务商、填写 API Key 后点击「保存并启用」</span>
            @endif
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">AI 服务商</label>
        <div class="col-sm-8">
            <select class="form-control" id="cs-ai-provider">
                @foreach($providers as $key => $meta)
                    <option value="{{ $key }}" @if($key === ($current ?: ($active['provider'] ?? 'deepseek'))) selected @endif>
                        {{ $meta['name'] }}{{ isset($saved[$key]) ? '（已配置）' : '' }}
                    </option>
                @endforeach
            </select>
            <span class="help-block" id="cs-ai-note"></span>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">API Key</label>
        <div class="col-sm-8">
            <input type="password" class="form-control" id="cs-ai-key" autocomplete="new-password">
            <span class="help-block" id="cs-ai-key-help"></span>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">模型</label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="cs-ai-model" list="cs-ai-models">
            <datalist id="cs-ai-models"></datalist>
            <span class="help-block">可从下拉建议中选择，也可以填写服务商支持的任意模型名</span>
        </div>
    </div>
    <div class="form-group" id="cs-ai-base-group">
        <label class="col-sm-2 control-label">接口地址</label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="cs-ai-base">
            <span class="help-block">一般不用改，留空使用默认地址；使用代理或中转服务时填写</span>
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-8">
            <button type="button" class="btn btn-default" id="cs-ai-test"><i class="fa fa-plug"></i> 测试连接</button>
            <button type="button" class="btn btn-primary" id="cs-ai-save"><i class="fa fa-check"></i> 保存并启用</button>
            <div id="cs-ai-result" style="margin-top:12px;white-space:pre-wrap"></div>
        </div>
    </div>
</div>

<script>
    (function () {
        var providers = @json($providers);
        var saved = @json((object) $saved);
        var token = @json(csrf_token());
        var saveUrl = @json($saveUrl);
        var testUrl = @json($testUrl);

        function fill() {
            var key = $('#cs-ai-provider').val();
            var meta = providers[key] || {};
            var conf = saved[key] || {};

            $('#cs-ai-key').val('').attr('placeholder', conf.api_key ? '已保存 ' + conf.api_key + '（留空则不修改）' : '请填写 API Key');
            $('#cs-ai-key-help').html(meta.key_url ? '获取 Key：<a href="' + meta.key_url + '" target="_blank" rel="noopener noreferrer">' + $('<span>').text(meta.key_url).html() + '</a>' : '');
            $('#cs-ai-model').val(conf.model || (meta.models && meta.models[0]) || '');
            $('#cs-ai-models').empty();
            (meta.models || []).forEach(function (m) {
                $('#cs-ai-models').append($('<option>').attr('value', m));
            });
            $('#cs-ai-base').val(conf.base_url || '').attr('placeholder', meta.base_url || '请填写接口地址');
            $('#cs-ai-base-group').toggle(meta.type !== 'anthropic');
            $('#cs-ai-note').text(meta.note || '');
            $('#cs-ai-result').empty();
        }

        function payload() {
            return {
                _token: token,
                provider: $('#cs-ai-provider').val(),
                api_key: $('#cs-ai-key').val(),
                model: $('#cs-ai-model').val(),
                base_url: $('#cs-ai-base').val()
            };
        }

        function show(res) {
            $('#cs-ai-result').removeClass('text-success text-danger')
                .addClass(res.status ? 'text-success' : 'text-danger')
                .text(res.message);
        }

        function call(url, $btn, after) {
            $btn.prop('disabled', true);
            $('#cs-ai-result').removeClass('text-success text-danger').text('请求中，请稍候…');
            $.post(url, payload()).done(function (res) {
                show(res);
                if (res.status && after) {
                    after();
                }
            }).fail(function (xhr) {
                show({status: false, message: '请求失败：HTTP ' + xhr.status});
            }).always(function () {
                $btn.prop('disabled', false);
            });
        }

        $('#cs-ai-provider').on('change', fill);
        $('#cs-ai-test').on('click', function () {
            call(testUrl, $(this));
        });
        $('#cs-ai-save').on('click', function () {
            call(saveUrl, $(this), function () {
                setTimeout(function () {
                    $.pjax ? $.pjax.reload('#pjax-container') : location.reload();
                }, 800);
            });
        });

        fill();
    })();
</script>
