<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26 0026
 * Time: 22:51
 */
class Admin_Link_module extends CI_Module
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin.Link_model');
        $this->load->model('admin.Adminuser_model');
        //检测是否已登录
        $this->Adminuser_model->is_login();
    }

    /**
     * 后台友情链接列表
     */
    public function index()
    {
        $auth_url = [
            'is_add' => 'admin/link/add',
            'is_edit' => 'admin/link/edit',
            'is_del' => 'admin/link/ajax_remove'
        ];
        $auth_arr = check_auth($auth_url);//检查权限
        $page_config['per_page'] = 10;   //每页条数
        $page_config['num_links'] = 2;//当前页前后链接数量
        $page_config['base_url'] = site_url('admin/link/index');//url
        $page_config['params'] = '';//参数
        $page_config['seg'] = 4;//参数取 index.php之后的段数，默认为3，即index.php/control/function/18 这种形式
        $page_config['cur_page'] = $this->uri->segment($page_config['seg']) ? $this->uri->segment($page_config['seg']) : 1;//当前页
        $page_config['total_rows'] = $this->Link_model->get_count();
        $this->load->library('mypage', $page_config);
        $result = $this->Link_model->get_list($page_config['per_page'], $page_config['cur_page']);
        if ($auth_arr) {
            foreach ($auth_arr as $key => $val) {
                $data[$key] = $val;
            }
        }
        $data['auth_count'] = count($auth_arr);

        $data['index_url'] = site_url('admin/link/index');
        $data['add_url'] = site_url('admin/link/add');
        $data['ajax_status_url'] = site_url('admin/link/ajax_status');
        $data['list'] = $result;
        $this->load->view('link/index', $data);
    }

    /**
     * 友链添加添加
     */
    public function add()
    {
        $data['form_post'] = site_url('admin/link/save');
        $data['index_url'] = site_url('admin/link/index');
        $data['add_url'] = site_url('admin/link/add');
        $this->load->view('link/add', $data);
    }

    /**
     * 友链编辑
     */
    public function edit()
    {
        $lid = $this->input->get('id');
        $auth_url = [
            'is_add' => 'admin/link/add'
        ];
        $auth_arr = check_auth($auth_url);
        $data = [];
        if ($auth_arr) {
            foreach ($auth_arr as $key => $val) {
                $data[$key] = $val;
            }
        }
        $row = $this->Link_model->get_row(['lid' => $lid]);
        $data['form_post'] = site_url('admin/link/save');
        $data['index_url'] = site_url('admin/link/index');
        $data['add_url'] = site_url('admin/link/add');
        $data['row'] = $row;
        $this->load->view('link/edit', $data);
    }

    /**
     * 异步修改友链状态
     */
    public function ajax_status()
    {
        $post = $this->input->post();
        $data[$post['field']] = $post['val'];
        $res = $this->Link_model->edit_row($data, ['lid' => $post['id']]);
        if ($res) {
            update_cache('link_list', ['v' => '']);
            $this->success('操作成功', '', true);
        } else {
            $this->error('操作失败', '', true);
        }
    }

    /**
     * 友链数据保存
     */
    public function save()
    {
        $data = $this->input->post('Form');
        $lid = $this->input->post('id');
        if ($lid) {
            //修改数据
            $result = $this->Link_model->edit_row($data, ['lid' => $lid]);
        } else {
            //新增数据
            $result = $this->Link_model->add_row($data);
        }
        $returl = site_url('admin/link/index');
        if ($result) {
            update_cache('link_list', ['v' => '']);
            $this->success('保存成功！', $returl, true);
        } else {
            $this->error("保存失败！", $returl, true);
        }
    }

    /**
     * 删除友情链接
     */
    public function ajax_remove()
    {
        $id = $this->input->get_post('id');
        $where = 'lid=' . $id;
        $result = $this->Link_model->remove_row($where);
        $returl = site_url('article/admin/article');
        if ($result) {
            update_cache('link_list', ['v' => '']);
            $this->success('操作成功', $returl, true);
        } else {
            $this->error('操作失败', '', true);
        }
    }
}