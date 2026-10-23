<?php

namespace App\Services\Pay;

use App\Models\Bank;
use App\Models\Card;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;

class VortaqPayService extends PayService implements Pay
{

    public $name = [
        'Alita', 'Almeta', 'Antonietta', 'Adaline', 'Astra', 'Antonina', 'Agate', 'Acadia', 'Angelle', 'Ambrosius', 'Anwar', 'Abu', 'Adolphus', 'Arron', 'Arturo', 'Arlin', 'Adil', 'Armin',
        'Blue', 'Bohannon', 'Bane', 'Brune', 'Bade', 'Bruni', 'Binger', 'Byran', 'Barbra', 'Benoit', 'Baptiste', 'Barra', 'Brun', 'Berthold', 'Barris', 'Brennon', 'Basel', 'Bilal',
        'Crosley', 'Carole', 'Chrysanna', 'Cath', 'Calandre', 'Cadynn', 'Cissy', 'Chela', 'Cesario', 'Cully', 'Coman', 'Claudius', 'Conal', 'Corran', 'Conner', 'Cordero', 'Casper',
        'Douglas', 'Dwight', 'Dunn', 'Duncan', 'Dennis', 'Dylan', 'Darcy', 'Dempsey', 'Dana', 'Darnell', 'Donahue', 'Darren', 'Devin', 'Dean', 'Daniel', 'Dominic', 'David', 'Drew', 'Dave', 'Derrick', 'Donald'
        , 'Emmanuel', 'Evan', 'Eugene', 'Ernest', 'Ellis', 'Elroy', 'Everley', 'Eden', 'Eric', 'Egbert', 'Earl', 'Edgar', 'Elton', 'Edmund', 'Elliot', 'Edwiin', 'Elmer', 'Edward', 'Enoch', 'Edison', 'Elijah', 'Elvis', 'Frank', 'Fitch', 'Fitzgerald', 'Fabian', 'Felix', 'Ferdinand', 'Franklin', 'Francis', 'Frederic'
        , 'George', 'Gerald', 'Gene', 'Godfery', 'Gregary', 'Gavin', 'Gabriel', 'Gale', 'Gordon', 'Glenn', 'Grover', 'Gilbert', 'Gary', 'Gustave', 'Geoffrey', 'Goddard', 'Geoff', 'Giles', 'Griffith'
        , 'Herman', 'Harriet', 'Hayden', 'Harlan', 'Herbert', 'Hubery', 'Hobart', 'Hunter', 'Haley', 'Hardy', 'Hogan', 'Hamiltion', 'Harley', 'Hyman', 'Harvey', 'Humphrey', 'Heather', 'Harry', 'Hilary', 'Horace', 'Henry', 'Hale', 'Howar', 'Hiram', 'Harold'
        , 'Ingemar', 'Ingram', 'Isaac', 'Isidore'
        , 'Joshua', 'Jerome', 'Julius', 'Jason', 'Julian', 'Jerry', 'Jeremy', 'Joyce', 'Jeffrey', 'Jesse', 'Jonathan', 'Joseph', 'Justin', 'Jared', 'Jacob', 'James', 'Jonas'
        , 'Keith', 'Kerwin', 'Kevin', 'Kent', 'Kerr', 'Kelly', 'Kyle', 'Kennedy', 'Kenneth', 'Kirk'
        , 'Lucien', 'Luther', 'Lambert', 'Lance', 'Lennon', 'Leonard', 'Leopold', 'Louis', 'Larry', 'Lawrence', 'Leif', 'Lyndon', 'Lester', 'Levi', 'Lewis', 'Lionel'
        , 'Matt', 'Malcolm', 'Mandel', 'Marvin', 'Miles', 'Maximilian', 'Murray', 'Marico', 'Merle', 'Mortimer', 'Marcus', 'Morgan', 'Maxwell', 'Moore', 'Michael', 'Marshall', 'Morton', 'Meredith', 'Mick', 'Montague', 'Moses', 'Matthew', 'Michell', 'Marlon', 'Merlin', 'Murphy', 'Marsh', 'Myron', 'Martin', 'Monroe', 'Magee', 'Maurice'
        , 'Nigel', 'Norman', 'Nathan', 'Noel', 'Norton', 'Nicholas', 'Nathaniel', 'Nelson', 'Newman'
        , 'Ogden', 'Oscar', 'Osmond', 'Orville', 'Osborn', 'Oswald'
        , 'Parker', 'Philip', 'Paul', 'Peter', 'Phil', 'Payne', 'Paddy', 'Patrick', 'Perry', 'Porter', 'Page', 'Prescott', 'Primo', 'Quentin', 'Quinn', 'Quintion', 'Quennel', 'Quincy'
        , 'Rory', 'Richard', 'Roderick', 'Rachel', 'Rupert', 'Rock', 'Rudolf', 'Robert', 'Reuben', 'Robin', 'Ronald', 'Regan', 'Ryan', 'Raymond', 'Reginald', 'Ralap', 'Randolph', 'Rodney'
        , 'Sampson', 'Saxon', 'Scott', 'Sebastian', 'Stanford', 'Samuel', 'Spencer', 'Sidney', 'Silvester', 'Simon', 'Solomon', 'Sandy', 'Sean', 'Stanley', 'Stan', 'Steven', 'Stev', 'Steward', 'Taylor'
        , 'Tyrone', 'Truman', 'Tracy', 'Thomas', 'Troy', 'Tiffany', 'Tony', 'Tobias', 'Theodore', 'Theobald', 'Tyler', 'Timothy', 'Todd', 'Toby', 'Ternence'
        , 'Upton', 'Uriah', 'Ulysses', 'Valentine', 'Verne', 'Vincent', 'Virgil', 'Victor', 'Valentine', 'Vivian'
        , 'Walker', 'Wythe', 'Wordsworth', 'Winston', 'Walter', 'Webb', 'Woodrow', 'Webster', 'Winfred', 'Wade', 'Wilbur', 'Wayne', 'Warner', 'Wright', 'Wendell', 'Werner', 'Willie
', 'William', 'Will', 'Ward', 'Xavier'
        , 'Yale', 'Yves', 'Yehudi', 'York', 'Zachary', 'Zebulon'
    ];

    /**
     * 充值通知地址
     * @var string
     */
    protected string $pay_notify_url = '/api/v1/vortaq_recharge';

    /**
     * 代付通知地址
     * @var string
     */
    protected string $withdrawal_notify_url = '/api/v1/vortaq_withdrawal';


    //充值
    public function recharge()
    {
        try {
            $this->setReturnUrl();
            //$user_id = $this->order->user_id;
            //$user = User::find($user_id);
            $payname = $this->name[mt_rand(0, count($this->name) - 1)];
            $order_amount = sprintf('%.2f', $this->order->actual_amount / 100);

            $currery = $this->currery;
            switch ($currery){
                case "BDT":
                    $country = 'BGD';
                    break;
            }
            $timestamp = self::getMillisecond();
            $param = [
                'merchant_no' => $this->merchant,
                'data'        => [
                    'country' => $country,
                    'currency' => $currery,
                    'payment_method_id' => $this->pay_code,
                    'payment_method_flow' => 'REDIRECT',
                    'order_id' => $this->order->no,
                    'amount' => $order_amount,
                    'notification_url' => url($this->pay_notify_url),
                    'timestamp' =>$timestamp
                ]
            ];
            $param['data']['signature'] = $this->sign($param['data'], $this->pay_key);

            $header = [
                //'Content-Type' => 'application/json',
                'ApiVersion' => '1.0',
                'AppId' => $this->password,
                'Noncestr' => (string)rand(10000000,99999999),
                'Timestamp' => $timestamp
            ];

            $response = Http::withHeaders($header)->asJson()->post($this->pay_url, $param);
            $result = json_decode($response->body(), true);
            $this->order->param = json_encode($param, JSON_UNESCAPED_UNICODE);
            $this->order->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            if (isset($result['state']) && $result['state'] == 'ok') {
                $this->order->payment_no = $result['data']['payment_id'];
                $this->order->save();
                return $result['data']['redirect_url'];
            }
            $this->rechargeFail();
            return false;
        } catch (Exception $exception) {
            $error_message = json_encode(['msg' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()], JSON_UNESCAPED_UNICODE);
            $this->rechargeFail($error_message);
            return false;
        }
    }

    public function withdrawal()
    {
        try {
            //$card = Card::query()->where('user_id', '=', $this->order->user_id)->first();
            $card = json_decode($this->order->card, true);
            $currery = $this->currery;
            switch ($currery){
                case "BDT":
                    $country = 'BGD';
                    $acc_code = $card['ifsc'];
                    if($acc_code == 'nagad'){
                        $acc_code = 'ngand';
                    }
                    break;
            }

            $timestamp = self::getMillisecond();
            $order_amount = sprintf('%.2f', $this->order->actual_amount / 100);
            $param = [
                'merchant_no' => $this->merchant,
                'data'        => [
                    'country' => $country,
                    'currency' => $currery,
                    'order_id' => $this->order->no,
                    'amount' => $order_amount,
                    'notification_url' => url($this->withdrawal_notify_url),
                    'timestamp' =>$timestamp
                ]
            ];
            $param['data']['signature'] = $this->sign($param['data'], $this->withdrawal_key);
            $param['data']['payee'] = [
                'name' => $card['username'],
                'account' => $card['number'],
                'account_type' => 'BANK',
                'bank_code' => $acc_code
            ];
            $header = [
                //'Content-Type' => 'application/json',
                'ApiVersion' => '1.0',
                'AppId' => $this->password,
                'Noncestr' => (string)rand(10000000,99999999),
                'Timestamp' => $timestamp
            ];

            $response = Http::withHeaders($header)->asJson()->post($this->withdrawal_url, $param);
            $result = json_decode($response->body(), true);
            $result = is_array($result) ? $result : [];
            //记录入参跟响应
            if (isset($result['state']) && $result['state'] == 'ok') {
                $state = true;
                $transaction_id = $result['data']['settlement_id'];
            }else{
                $state = false;
                $transaction_id = '';
            }
            $this->withdrawalCallback($param, $result,  $transaction_id, $state);
            $this->order->save();
        } catch (Exception $exception) {
            $this->withdrawalCallback([], ['msg' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()], '', false);
        }
    }


    /**
     * 签名
     * @param $data
     * @param $secret
     * @return string
     */
    protected function sign($data, $secret): string
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $val) {
            if ($val != null && $val != '') {
                $str .= $key . '=' . $val . '&';
            }
        }
        //$str = rtrim($str, '&');
        return strtoupper(md5($str . 'key='.$secret));
    }

    /**
     * 验签
     * @param array $params
     * @param string $type
     * @return bool
     */
    public function checkSign(array $params, string $type): bool
    {
        $sign = $params['signature'];
        // 验证签名参数是否正确
        unset($params['signature'], $params['payee']);
        return $sign == $this->sign($params, $type == 'pay' ? $this->pay_key : $this->withdrawal_key);
    }


    public function getMillisecond()
    {
        $sysTimeZone= date_default_timezone_get();//先取出系统所在服务器的时区
        date_default_timezone_set('UTC');//设置时区为UTC时区
        list($s1,$s2)=explode(' ',microtime());
        $timestamp = (float)sprintf('%.0f',(floatval($s1)+floatval($s2))*1000);
        date_default_timezone_set( $sysTimeZone ); //设置时区为服务器时区
        return $timestamp;
    }

}
