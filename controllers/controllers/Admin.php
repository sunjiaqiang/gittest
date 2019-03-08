<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/25 0025
 * Time: 21:23
 */
class Oauth_Admin_module extends CI_Module
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('oauth.Oauth_model');
        $this->load->model('admin.Adminuser_model');
        //检测是否已登录
        $this->Adminuser_model->is_login();
    }

    /**
     * 会员管理首页
     */
    public function index(){
        $page_config['per_page'] = 10;   //每页条数
        $page_config['num_links'] = 2;//当前页前后链接数量
        $page_config['base_url'] = site_url('admin/user/index');//url
        $page_config['param'] = '';//参数
        $page_config['seg'] = 4;//参数取 index.php之后的段数，默认为3，即index.php/control/function/18 这种形式
        $page_config['cur_page'] = $this->uri->segment($page_config['seg']) ? $this->uri->segment($page_config['seg']) : 1;//当前页
        $page_config['total_rows'] = $this->Oauth_model->get_count();
        $this->load->library('mypage', $page_config);
        $result = $this->Oauth_model->get_list($page_config['per_page'], $page_config['cur_page']);
        $data['index_url'] = site_url('oauth/admin/index');
        $data['ajax_status_url'] = site_url('oauth/admin/ajax_status');
        $data['list'] = $result;
        $this->load->view('admin/user',$data);
    }
    /**
     * 异步修改用户状态
     */
    public function ajax_status()
    {
        $post = $this->input->post();
        $data[$post['field']] = $post['val'];
        $res = $this->Oauth_model->edit_row($data, ['uid' => $post['id']]);
        if ($res) {
            $this->success('操作成功', '', true);
        } else {
            $this->error('操作失败', '', true);
        }
    }
}