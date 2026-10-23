<?php

namespace App\Http\Controllers\Api\v1;


use App\Handlers\KeysHandler;
use App\Http\Requests\Api\v1\RecordRequest;
use App\Models\Record;
use App\Models\Statistics;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * 统计记录
 * @package App\Http\Controllers\Api\v1
 */
class RecordController extends Controller
{
    public function store(RecordRequest $request)
    {

        $now = Carbon::now()->toDateTimeString();
        Cache::lock($request->type . '-' . $request->device . '-' . $now)->get(function () use ($request, $now) {
            $today = Carbon::today();
            $record = Record::where('type', '=', $request->type)
                ->where('device', '=', $request->device)
                ->whereBetween('created_at', [
                    $today->startOfDay()->format('Y-m-d H:i:s'),
                    $today->endOfDay()->format('Y-m-d H:i:s')
                ])
                ->first();
            if ($record) {
                $record->increment('number');
            } else {
                Record::create([
                    'type' => $request->type,
                    'device' => $request->device,
                    'number' => 1
                ]);
            }
            $install = Statistics::where('device', '=', $request->device)->first();
            if (!$install) {
                Statistics::create([
                    'type' => $request->type,
                    'device' => $request->device,
                ]);
            }
        });
        return $this->success();
    }
}
