<?php

namespace App\Console\Commands;

use App\Models\Fund;
use App\Models\FundPeriod;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class FundPeriodCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'period:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成新彩种排期';

    private array $data = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $funds = Fund::with(['pet' => function ($query) {
            $query->select('id', 'buying_price', 'profit','day');
        }])->where('state', '=', 1)->select('id', 'pet_id', 'interval', 'start', 'end','admin_user_id')->get();

        if ($funds) {
            $this->generate($funds);
        }
    }


    public function generate(Collection $funds)
    {
        foreach ($funds as $item) {
            $this->worker($item)->save();
        }
        return $this;
    }

    private function save()
    {
        if (count($this->data)) {
            FundPeriod::query()->insert($this->data);
        }
        return $this;
    }

    private function worker(Fund $fund)
    {
        $this->data = [];
        //没有期号就生成今天的
        if(FundPeriod::query()->where('fund_id','=',$fund->id)->whereDate('start_time','=',Carbon::today())->count() == 0
        && $fund->start >= date('H:i:s')){
            $issueDay = Carbon::now();
            $time   = Carbon::parse($fund->start)->format('Y-m-d H:i:s');
            $this->createData($fund,$time,$issueDay);
        }
        //生成明天的
        if(FundPeriod::query()->where('fund_id','=',$fund->id)->whereDate('start_time','=',Carbon::tomorrow())->count() == 0){
            $issueDay = Carbon::tomorrow();
            $time   = Carbon::parse($fund->start)->addDay()->format('Y-m-d H:i:s');
            $this->createData($fund,$time,$issueDay);
        }
        return $this;
    }

    /**
     * @throws \Exception
     */
    private function createData(Fund $fund, $time, $issueDay)
    {
        $times  = floor((Carbon::parse($fund->end)->diffInMinutes(Carbon::parse($fund->start))) / $fund->interval);
        $flag   = $times + 1;
        while ($times > 0) {
            $draw = (new FundPeriod)->randomDraw();
            $data = [
                'fund_id'       => $fund->id,
                'pet_id'        => $fund->pet->id,
                'total_price'   => $fund->pet->buying_price,
                'issue'         => sprintf('%s%s',$issueDay->format('Ymd'), str_pad(abs($times-$flag), 3,0, STR_PAD_LEFT)),
                'draw'          => FundPeriod::DRAW_YES,
                'admin_user_id' => $fund->admin_user_id,
                'sort'          => (int)$fund->sort,
                'draw_pattern'  => FundPeriod::RANDOM,
                'draw_number'   => $draw['number'],
                'draw_size'     => $draw['size'],
                'profit'        => $fund->pet->profit,
                'day'           => $fund->pet->day,
                'win_number'    => random_int(40,200),
            ];
            $data['start_time'] = $time;
            $time = Carbon::parse($time)->addMinutes($fund->interval)->format('Y-m-d H:i:s');
            $data['end_time'] = $time;
            $data['created_at'] = $data['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
            array_push($this->data, $data);
            $times--;
        }
    }
}
