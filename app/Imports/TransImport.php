<?php

namespace App\Imports;

use App\Handlers\KeysHandler;
use App\Models\Tran;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class TransImport implements ToModel,WithStartRow,WithValidation,SkipsOnFailure
{

    use Importable, SkipsFailures;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $locale_code = $row[1];
        $key = $row[2];
        $value = $row[3];
        $tran = Tran::query()->where('key','=',$key)->where('locale_code','=',$locale_code)->first();
        if($tran){
            $type = $tran->type;
            $tran->value = $value;
            $tran->save();
            $cachekey = sprintf(KeysHandler::TransCache, $type, $locale_code);
            Cache::forget($cachekey);
            return true;
        }else{
            $type = $row[0];
            $cachekey = sprintf(KeysHandler::TransCache, $type, $locale_code);
            Cache::forget($cachekey);
            return new Tran([
                'type' => $type,
                'locale_code' => $locale_code,
                'key' => $key,
                'value' => $value,
            ]);
        }
    }

    // 从3行开始读取数据
    public function startRow(): int
    {
        return 3;
    }

    // 验证
    public function rules(): array
    {
        return [
            '2' => 'required',
        ];
    }


}
