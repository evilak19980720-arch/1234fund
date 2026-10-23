<?php

namespace App\Services\Pay;

use App\Models\Bank;
use App\Models\Card;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;

class OnePayService extends PayService implements Pay
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



    //充值
    public function recharge()
    {
        try {
            $this->setReturnUrl();
            $header['Authorization'] = $this->pay_key;
            $currery = $this->currery;
            switch ($currery){
                case "BDT":
                    $notifyUrl = url('/api/v1/one_recharge');
                    break;
                case "INR":
                    $notifyUrl = url('/api/v1/onein_recharge');
                    break;
            }

            $param = [
                'orderNo' => $this->order->no,
                'payCode' => $this->merchant,
                'amount' => $this->order->actual_amount,
                'notifyUrl' => $notifyUrl,
                'returnUrl' => '',
                //'pageurl' => $this->return_url.'/rechargeSucess?no='.$this->order->no.'&amount='.$this->order->amount
            ];
            $postdata['data'] = $this->encryptionAes($param);
            $response = Http::withHeaders($header)->withoutVerifying()->asJson()->post($this->pay_url, $postdata);
            $result = json_decode($response->body(), true);
            $this->order->param = json_encode($param, JSON_UNESCAPED_UNICODE) . '--'.$postdata['data'];
            $this->order->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            if (isset($result['code']) && $result['code'] == 200) {
                $this->order->payment_no = $result['data']['channelNo'];
                $this->order->save();
                return $result['data']['paymentUrl'];
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
            $header['Authorization'] = $this->withdrawal_key;
            $card = json_decode($this->order->card, true);
            $currery = $this->currery;

            switch ($currery){
                case "PHP":
                    break;
                case "INR":
                    $notifyUrl = url('/api/v1/onein_withdrawal');
                    $payeeType = 1;
                    $payeeSecondInfo = $card['ifsc'];
                    break;
                case "BDT":
                    $notifyUrl = url('/api/v1/one_withdrawal');
                    $payeeType = 2;
                    $payeeSecondInfo = 'nagad';
                    if($card['ifsc'] == 'bkash'){
                        $payeeType = 0;
                        $payeeSecondInfo = 'bkash';
                    }
                    break;
            }

            $param = [
                'orderNo' => $this->order->no,
                'payCode' => $this->merchant,
                'amount' =>  $this->order->actual_amount,
                'notifyUrl' => $notifyUrl,
                'payeeType' => $payeeType,
                'payeeSecondInfo' => $payeeSecondInfo,
                'payeeName' => $card['username'],
                'payeeFirstInfo' => $card['number'],
            ];

            $postdata['data'] = $this->encryptionAes($param);
            $response = Http::withHeaders($header)->withoutVerifying()->asJson()->post($this->withdrawal_url, $postdata);
            $result = json_decode($response->body(), true);
            $result = is_array($result) ? $result : [];
            //记录入参跟响应
            if (isset($result['code']) && $result['code'] == 200) {
                $state = true;
                $transaction_id = $result['data']['channelNo'];
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

    public $method = 'AES-128-CBC';

    /**加密
     * @param array $data
     * @return string
     */
    public function encryptionAes(array $data)
    {
//        $jsonData = json_encode($data,true);
        //修改
        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE );
        $aesSecret = bin2hex(openssl_encrypt($jsonData, $this->method,$this->password,  OPENSSL_RAW_DATA, $this->password));
        return $aesSecret;
    }

    /**解密
     * @param $aesSecret
     * @return false|string
     */
    public function decryptAes($aesSecret)
    {
        $str="";
        for($i=0;$i<strlen($aesSecret)-1;$i+=2){
            $str.=chr(hexdec($aesSecret[$i].$aesSecret[$i+1]));
        }
        $jsonData =  openssl_decrypt($str,$this->method,$this->password, OPENSSL_RAW_DATA,$this->password);
        $data = json_decode($jsonData,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        return $data;
    }


    /**获取随机数
     * @param int $length 随机数长度
     * @return string 返回随机数
     */
    public function GetRandStr($length = 8)
    {
        $str='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len=strlen($str)-1;
        $randStr='';
        for($i=0;$i<$length;$i++){
            $num=mt_rand(0,$len);
            $randStr .= $str[$num];
        }
        return $randStr;
    }

    /**post加密数据并提交请求
     * @param string $url 请求地址
     * @param array $data   请求数据
     * @return false|string     返回数据（json字符串）
     */
    public function postRes($url, array $data)
    {
        $info['data'] = $this->encryptionAes($data);
        $res = $this->curlPost($url, $info);
        return $res;
    }

    /**解密并解析数据为数组；
     * @param string $data 示例:{"data":"50C7CC8B58CEFFD1A824ADE524F4F55DB0DAEE6029ADBB597C1F99D28E1D0779C33B562526F05C6821932DE20B6893ADD6834D3397B7A8E08CC03995A5CDEA7E6B4DF0485466D4C25AEB223DD456DBC0321921FDCA18F9596A1C14B54C5A018CC7C0B922E3DE371626887DA78E539DA81E64EC41938BC3EC5BEBC26A948803E8","merchantNo":"2022011116544301686"}
     * @return false|array
     */
    public function parseData( $data )
    {
        $info = json_decode($data,true );
        $val = $this->decryptAes($info['data']);
        return $val;
    }

}
