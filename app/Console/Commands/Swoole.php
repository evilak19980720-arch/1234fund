<?php
# 这里是一个laravel中Commands
# 运行php artisan swoole start 即可运行
namespace App\Console\Commands;

use App\Service\WebSocket\Message;
use App\Service\WebSocket\Room;
use App\Service\WebSocket\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use swoole_websocket_server;

class Swoole extends Command
{
    public $ws;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Active Push Message';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $arg = $this->argument('action');
        switch ($arg) {
            case 'start':
                $this->info('swoole server started');
                $this->start();
                break;
            case 'stop':
                $this->info('swoole server stoped');
                break;
            case 'restart':
                $this->info('swoole server restarted');
                break;
        }
    }

    /**
     * 启动Swoole
     */
    private function start()
    {
        $this->ws = new swoole_websocket_server("0.0.0.0", config('customer.websocket_port'));
        //监听WebSocket连接打开事件
        $this->ws->on('open', function ($ws, $request) {
            Log::info('websocket客户端请求链接',['$request'=>$request]);
            if(isset($request->get['token']) && $request->get['user_id'] && $request->get['uid']){
                $res = (new User())->isLogin($request->get['token'],$request->fd,$request->get['user_id'],$request->get['uid']);
                if ($res===false){
                    $ws->push($request->fd, $this->returnMsg('请登录',701));
                    $ws->disconnect($request->fd,701,'请登录');
                }
//                $ws->push($request->fd, $this->returnMsg('链接成功'));
            }else{
                $ws->push($request->fd, $this->returnMsg('请登录',701));
                $ws->disconnect($request->fd,701,'请登录');

            }
            $this->info('websocket请求成功 fd=>'.$request->fd);
        });
        //监听WebSocket消息事件
        $this->ws->on('message', function ($ws, $frame) {
            Log::info('websocket客户端推送消息', ['fid' => $frame->fd, 'data' => $frame->data, 'port' => config('customer.websocket_port')]);
            $this->info('websocket客户端推送消息 fd=>'.$frame->fd.' data=>'.$frame->data);
            (new Message())->checkData($ws, $frame);
            $ws->push($frame->fd, $this->returnMsg('发送成功',800));

        });
        //监听WebSocket主动推送消息事件
        $server = $this->ws;
        $this->ws->on('request', function ($request, $response) use ($server) {
            $this->info('websocket服务端推送信息 fd=>'.$request->post['fid'].' data=>'.json_encode($request->post));
//            $server->push($request->post['fid'], json_encode($request->post));
            if ($server->isEstablished($request->post['fid'])){
                $server->push($request->post['fid'], json_encode($request->post));
            }else{
                (new Room())->deleteFd($request->post['fid']);
            }


        });
        //监听WebSocket连接关闭事件
        $this->ws->on('close', function ($ws, $fd) {
            Log::info('websocket客户端关闭', ['$ws' => $ws, '$fd' => $fd]);
            $this->info('websocket客户端关闭 fd=>'.$fd);
            (new Room())->deleteFd($fd);
        });
        $this->ws->start();
    }
    protected function returnMsg($msg, $code = 0)
    {
        return json_encode(['code' => $code, 'msg' => $msg, 'time' => time()]);
    }



}