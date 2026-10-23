<?php

namespace App\Services\Pay;

use App\Models\Card;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;

class HxPayService extends PayService implements Pay
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
    protected string $pay_notify_url = '/api/v1/hxpay_recharge';

    /**
     * 代付通知地址
     * @var string
     */
    protected string $withdrawal_notify_url = '/api/v1/hxpay_withdrawal';


    //充值
    public function recharge()
    {
        try {
            $this->setReturnUrl();
            $param = [
                'merchantLogin' => $this->merchant,
                'orderCode' => $this->order->no,
                'amount' => $this->order->amount / 100,
            ];

            $user_id = $this->order->user_id;
            $card = Card::where('user_id', $user_id)->orderBy('created_at', 'desc')->first();
            $user = User::find($user_id);
            if($card){
                $param['name'] = $card->username;
                $param['phone'] = $card->phone;
                $param['email'] = $card->email;
            }else{
                $param['name'] = $this->name[mt_rand(0, count($this->name) - 1)];
                $param['phone'] = $user->phone;
                $param['email'] = $param['name'] . '@gmail.com';;
            }

            $param['sign'] = $this->sign($param, $this->pay_key);
            $response = Http::withOptions([
                'verify' => false,
            ])->post($this->pay_url, $param);
            $result = json_decode($response->body(), true);
            $this->order->param = json_encode($param, JSON_UNESCAPED_UNICODE);
            $this->order->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            if (isset($result['platformOrderCode']) && !empty($result['platformOrderCode'])) {
                $this->order->payment_no = $result['platformOrderCode'];
                $this->order->save();
                return $result['paymentUrl'];
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
            $card = Card::query()->where('user_id', '=', $this->order->user_id)->first();
            $param = [
                'merchantLogin' => $this->merchant,
                'orderCode' => $this->order->no,
                'amount' => $this->order->actual_amount / 100,
                'name' => $card->username,
                'account' => $card->number,
                'ifsc' => $card->ifsc
            ];
            $param['sign'] = $this->sign($param, $this->withdrawal_key);
            $response = Http::withOptions([
                'verify' => false,
            ])->post($this->withdrawal_url, $param);
            $result = json_decode($response->body(), true);
            $result = is_array($result) ? $result : [];
            //记录入参跟响应
            $state = isset($result['platformOrderCode']) && !empty($result['platformOrderCode']);
            $this->withdrawalCallback($param, $result, $result['platformOrderCode'] ?? '', $state);
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
            if ($val != null) {
                $str .= $key . '=' . $val . '&';
            }
        }
        return md5($str . 'key=' . $secret);
    }

    /**
     * 验签
     * @param array $params
     * @param string $type
     * @return bool
     */
    public function checkSign(array $params, string $type): bool
    {
        $sign = $params['sign'];
        // 验证签名参数是否正确
        unset($params['sign'], $params['signType'], $params['merRetMsg']);
        return $sign == $this->sign($params, $type == 'pay' ? $this->pay_key : $this->withdrawal_key);
    }

}
