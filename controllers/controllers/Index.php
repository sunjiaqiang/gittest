<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/14 0014
 * Time: 22:12
 */
class Oauth_Index_module extends CI_Module
{
    const VERSION = "2.0";
    const GET_AUTH_CODE_URL = "https://graph.qq.com/oauth2.0/authorize";
    const GET_ACCESS_TOKEN_URL = "https://graph.qq.com/oauth2.0/token";
    const GET_OPENID_URL = "https://graph.qq.com/oauth2.0/me";
    const GET_USERINFO = 'https://graph.qq.com/user/get_user_info';
    private $appid;//qq互联新建应用分配的appid
    private $appkey;//qq互联新建应用分配的appkey
    private $callback;//回调地址，必须和应用里面填写的一致
    private $scope;

    public function __construct()
    {
        parent::__construct();
        $this->appid = '101523930';
        $this->appkey = '9651a10a70c45e86036cf03809eb677c';
        $this->callback = 'http://blog.lbxiaoxin.com/oauth/index/call_back';
        $this->scope = 'get_user_info';
        $this->load->model('oauth.Oauth_model');
    }

    /**
     * 三方登录
     */
    public function oauth_login()
    {
        //-------生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE));
        //-------构造请求参数列表
        $keysArr = [
            "response_type" => "code",
            "client_id" => $this->appid,
            "redirect_uri" => urlencode($this->callback),
            "state" => $state,
            "scope" => $this->scope
        ];
        $login_url = $this->combineURL(self::GET_AUTH_CODE_URL, $keysArr);
        redirect($login_url);
    }

    /**
     * combineURL
     * 拼接url
     * @param string $baseURL 基于的url
     * @param array $keysArr 参数列表数组
     * @return string           返回拼接的url
     */
    public function combineURL($baseURL, $keysArr)
    {
        $combined = $baseURL . "?";
        $valueArr = [];

        foreach ($keysArr as $key => $val) {
            $valueArr[] = "$key=$val";
        }

        $keyStr = implode("&", $valueArr);
        $combined .= ($keyStr);

        return $combined;
    }

    /**
     * QQ登录回调地址
     */
    public function call_back()
    {
        $code = $this->input->get('code');
        $state = $this->input->get('state');
        //-------请求参数列表
        $keysArr = [
            "grant_type" => "authorization_code",
            "client_id" => $this->appid,
            "redirect_uri" => urlencode($this->callback),
            "client_secret" => $this->appkey,
            "code" => $code
        ];
        //------构造请求access_token的url
        $token_url = $this->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        //        echo "qq回调地址";
        $response = $this->get_contents($token_url);

        if (strpos($response, "callback") !== false) {

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $msg = json_decode($response);

            if (isset($msg->error)) {
                exit($msg->error);
            }
        }

        $params = [];
        parse_str($response, $params);
        $openid = $this->get_openid($params["access_token"]);
        $user_info = $this->get_userinfo($openid, $params["access_token"]);
        $user_row = $this->Oauth_model->get_row(['access_token' => $params['access_token']]);
        $session_arr = [];
        if ($user_row) { //更新已登录的用户信息
            //检查用户是否被禁止
            if( ! $user_row['status']){
                $this->error("您被禁止登陆本站",site_url(''),3);
            }
            unset($user_info['head_img']);//防止更改本地的头像路径
            $user_info['login_times'] = $user_row['login_times'] + 1;
            $user_info['last_login_time'] = time();
            $this->Oauth_model->edit_row($user_info, ['uid' => $user_row['uid']]);
            $session_arr['uid'] = $user_row['uid'];
            $session_arr['nickname'] = $user_row['nickname'];
            $session_arr['head_img'] = $user_row['head_img'];
        } else { //第一次登录
            $user_info['openid'] = $openid;
            $user_info['access_token'] = $params['access_token'];
            $user_info['create_time'] = time();
            $user_info['last_login_ip'] = get_client_ip();
            $user_info['login_times'] = 1;
            $uid = $this->Oauth_model->add_row($user_info);
            if ($uid) {
                //下载用户头像到本地,此处的头像下载使用curl模式
                //经测试，readfile函数耗时较长
                getImage($user_info['head_img'], './public/uploads/member/', $uid . '.jpg', 1);
                $this->Oauth_model->edit_row(['head_img' => '/public/uploads/member/' . $uid . '.jpg'], ['uid' => $uid]);
            }
            $session_arr['uid'] = $uid;
            $session_arr['nickname'] = $user_info['nickname'];
            $session_arr['head_img'] = '/public/uploads/member/' . $uid . '.jpg';
        }
        $this->session->set_userdata($session_arr);
        redirect(base_url(''));
    }

    /**
     * 获取qq登录的openid
     * @param $access_token index方法传过来的token
     * @return mixed
     */
    public function get_openid($access_token)
    {
        //-------请求参数列表
        $keysArr = [
            "access_token" => $access_token
        ];

        $graph_url = $this->combineURL(self::GET_OPENID_URL, $keysArr);
        $response = $this->get_contents($graph_url);

        //--------检测错误是否发生
        if (strpos($response, "callback") !== false) {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }

        $user = json_decode($response);
        if (isset($user->error)) {
            echo '<pre>';
            print_r([$user->error, $user->error_description]);
            echo '</pre>';
            exit;
        }
        return $user->openid;
    }

    /**
     * 通过openid获取用户信息
     * @param $openid
     * @param $access_token
     */
    public function get_userinfo($openid, $access_token)
    {
        $keysArr = [
            'oauth_consumer_key' => $this->appid,
            'access_token' => $access_token,
            'openid' => $openid,
            'format' => 'json'
        ];
        $user_url = $this->combineURL(self::GET_USERINFO, $keysArr);
        $response = json_decode($this->get_contents($user_url), true);
        $user_arr = [
            'nickname' => $response['nickname'],
            'gender' => $response['gender'],
            'province' => $response['province'],
            'city' => $response['city'],
            'year' => $response['year'],
            'head_img' => $response['figureurl_qq_2']
        ];
        return $user_arr;
    }

    /**
     * get_contents
     * 服务器通过get请求获得内容
     * @param string $url 请求的url,拼接后的
     * @return string           请求返回的内容
     */
    public function get_contents($url)
    {
        if (ini_get("allow_url_fopen") == "1") {
            $response = file_get_contents($url);
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            curl_close($ch);
        }
        //-------请求为空
        if (empty($response)) {
            exit("50001");
        }
        return $response;
    }

    /**
     * 调试图片远程下载
     */
    public function test()
    {
        $url = 'http://thirdqq.qlogo.cn/qqapp/101523930/045F67AFB6D0B6085978BBFE2D12C0DE/100';
        $url = 'http://image.66diqiu.cn/uploads/756/20171009/c81853fdeb9563c5f1822c9eaa3f0162.jpg';
        getImage($url, './public/uploads/member/', '33.jpg', 1);
    }
}