<?php

namespace App\Console\Commands;

use App\Models\Crowd;
use App\Models\Issue;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class IssueCommand extends Command
{
    protected $signature = 'issue:generate';

    protected $description = '生成排期';

    private $data = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $crowds = Crowd::with(['pet' => function ($query) {
            $query->select('id', 'buying_price', 'profit','day');
        }])->where('state', '=', 1)->select('id', 'pet_id', 'portion', 'interval', 'start', 'end')->get();

        if ($crowds) {
            $this->generate($crowds);
        }
    }

    public function generate(Collection $crowds)
    {
        foreach ($crowds as $item) {
            $this->worker($item)->save();
        }
        return $this;
    }

    private function save()
    {
        if ($this->data) {
            DB::table('issues')->insert($this->data);
        }
        return $this;
    }

    private function worker(Crowd $crowd)
    {
        $this->data = [];
        //没有期号就生成今天的
        if(Issue::where('crowd_id','=',$crowd->id)->whereDate('start_time','=',Carbon::today())->count() == 0){
            $issueDay = Carbon::now();
            $time   = Carbon::parse($crowd->start)->format('Y-m-d H:i:s');
            $this->createData($crowd,$time,$issueDay);
        }
        //生成明天的
        if(Issue::where('crowd_id','=',$crowd->id)->whereDate('start_time','=',Carbon::tomorrow())->count() == 0){
            $issueDay = Carbon::tomorrow();
            $time   = Carbon::parse($crowd->start)->addDay()->format('Y-m-d H:i:s');
            $this->createData($crowd,$time,$issueDay);
        }
        return $this;
    }

    private function createData(Crowd $crowd,$time,$issueDay)
    {
        $times  = floor((Carbon::parse($crowd->end)->diffInMinutes(Carbon::parse($crowd->start))) / $crowd->interval);
        $flag   = $times + 1;

        while ($times > 0) {
            $data = [
                'crowd_id'      => $crowd->id,
                'issue'         => sprintf('%s%s',$issueDay->format('Ymd'), str_pad(abs($times-$flag), 3,0, STR_PAD_LEFT)),
                'portion'       => $crowd->portion,
                'total_price'   => $crowd->pet->buying_price,
                'profit'        => $crowd->pet->profit,
                'day'           => $crowd->pet->day,
                'bought'        => 0,
                'draw'          => 0
            ];
            $data['start_time'] = $time;
            $time = Carbon::parse($time)->addMinutes($crowd->interval)->format('Y-m-d H:i:s');
            $data['end_time'] = $time;
            $data['created_at'] = $data['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');

            array_push($this->data, $data);
            $times--;
        }
    }




}
