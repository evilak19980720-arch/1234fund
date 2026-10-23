<?php

namespace App\Admin\Actions\Tran;

use App\Imports\TransImport;
use App\Models\Phone;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class Tranimport extends Action
{

    protected $selector = '.import-tenant';

    public function handle(Request $request)
    {
        // 下面的代码获取到上传的文件，然后使用`maatwebsite/excel`等包来处理上传你的文件，保存到数据库
        $request->file('file');
        $import = new TransImport();
        $import->import($request->file('file'));
        return $this->response()->success('导入完成！')->refresh();
    }

    public function form()
    {
        $this->file('file', '请选择文件');
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-default import-tenant"><i class="fa fa-upload"></i>导入数据</a>
HTML;
    }


}
