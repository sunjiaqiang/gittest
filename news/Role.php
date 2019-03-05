<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: 123456
 * Date: 2018/10/15
 * Time: 15:55
 */
class Admin_Role_module extends CI_Module
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin.Adminuser_model');
        $this->load->model('admin.Adminrole_model');
        $this->load->model('admin.Menu_model');
        //检测是否已登录
        $this->Adminuser_model->is_login();
    }

    /**
     * 角色列表eeee
     */
    public function index()
    {
        $auth_url = [
            'is_add' => 'admin/role/add',
            'is_edit' => 'admin/role/edit',
            'is_menu_authority' => 'admin/role/set_authority',
            'is_action_authority' => 'admin/role/set_action_authority',
            'is_del' => 'admin/role/ajax_remove'
        ];
        $auth_arr = check_auth($auth_url);

        $where = "sid=100002";
        $page_config['per_page'] = 20;   //每页条数
        $page_config['num_links'] = 2;//当前页前后链接数量
        $page_config['base_url'] = site_url('admin/role/index');//url
        $page_config['param'] = '';//参数
        $page_config['seg'] = 4;//参数取 index.php之后的段数，默认为3，即index.php/control/function/18 这种形式
        $page_config['cur_page'] = $this->uri->segment($page_config['seg']) ? $this->uri->segment($page_config['seg']) : 1;//当前页
        $page_config['total_rows'] = $this->Adminrole_model->get_count($where);
        $this->load->library('Mypage', $page_config);
        $result = $this->Adminrole_model->get_list($page_config['per_page'], $page_config['cur_page'], $where);

        if ($auth_arr) {
            foreach ($auth_arr as $key => $val) {
                $data[$key] = $val;
            }
        }

        $data['auth_count'] = count(array_filter($auth_arr));
        $data['add_url'] = site_url('admin/role/add');
        $data['ajax_status_url'] = site_url('admin/role/ajax_status');
        $data['list'] = $result;
        $this->load->view('role/adminrole_index', $data);
    }

    /**
     * 添加角色
     */
    public function add()
    {
        //数据保存URL
        $data['form_post'] = site_url('admin/role/save');
        $data['ajax_check_name'] = site_url('admin/role/ajax_check_name?id=0');
        $this->load->view('role/adminrole_add', $data);
    }

    /**
     * 角色编辑
     */
    public function edit()
    {
        $id = $this->input->get('id');
        $row = $this->Adminrole_model->get_row(['id' => $id]);
        $data['row'] = $row;

        //数据保存URL
        $data['form_post'] = site_url('admin/role/save');
        $data['ajax_check_name'] = site_url("admin/role/ajax_check_name?id=$id");
        $this->load->view('role/adminrole_edit', $data);
    }

    /**
     * 保存角色信息
     */
    public function save()
    {
        $data = $this->input->post('Form');
        $data['sid'] = 100002;
        $id = $this->input->post('id');
        if ($id) {
            //修改数据
            $data['edit_time'] = date('Y-m-d H:i:s', time());
            $res = $this->Adminrole_model->edit_row($data, ['id' => $id]);
        } else {
            //新增数据
            $data['add_time'] = date('Y-m-d H:i:s', time());
            $res = $this->Adminrole_model->add_row($data);
        }
        if ($res) {
            $this->success('保存成功', site_url('admin/role/index'), true);
        } else {
            $this->error('保存失败', site_url('admin/role/index'), true);
        }
    }

    /**
     * 修改状态信息
     */
    public function ajax_status()
    {
        $post = $this->input->post();
        $data[$post['field']] = $post['val'];
        $res = $this->Adminrole_model->edit_row($data, ['id' => $post['id']]);
        if ($res) {
            //操作日志
            $this->success('操作成功', '', true);
        } else {
            $this->error('操作失败', '', true);
        }
    }

    /**
     * 判断管理员角色的名称是否存在
     */
    public function ajax_check_name()
    {
        $arg_get = $this->input->get();
        $row = $this->Adminrole_model->get_row(['name' => $arg_get['Form']['name'], 'id !=' => $arg_get['id'], 'sid' => 100002]);
        if (!empty($row) && is_array($row)) {
            $this->ajaxReturn('此管理员角色名称已存在!');
        } else {
            $this->ajaxReturn(true);
        }
    }

    /**
     * 角色授权
     */
    public function set_authority()
    {
        $role_id = $this->input->get('id');
        if (empty($role_id)) {
            $this->error('需要授权的角色不存在!', site_url('admin/role/index'), 1);
        }
        //菜单数据
        $result = $this->Menu_model->get_list('pingtai=2 AND status=1');
        //获取此角色的权限
        $priv_data = $this->Adminrole_model->get_access_list('role_id=' . $role_id . ' AND type=1');
        $access_arr = [];
        if ($priv_data) {
            foreach ($priv_data as $key => $val) {
                $access_arr[] = $val['app'] . '/' . $val['controller'] . '/' . $val['action'];
            }
        }
//        p($access_arr);
        foreach ($result as $rs) {
            $url = $rs['app'] . '/' . $rs['controller'] . '/' . $rs['action'];
            $data = [
                'id' => $rs['id'],
                'parent_id' => $rs['parent_id'],
                'name' => $rs['name'] . ($rs['type'] == 0 ? "(菜单项)" : ""),
                'checked' => (in_array($url, $access_arr)) ? true : false,//判断此菜单是否已授权
                'open' => true,
            ];
            $json[] = $data;
        }

        $data['json'] = json_encode($json);
        $data['role_id'] = $role_id;
        $data['form_post'] = site_url('admin/role/save_authority');
        $data['index_url'] = site_url('admin/role/index');
        $data['add_url'] = site_url('admin/role/add');
        $this->load->view('role/adminrole_authority', $data);
    }

    /**
     * 角色授权事件级权限
     */
    public function set_action_authority()
    {
        $role_id = $this->input->get('id');
        if (empty($role_id)) {
            $this->error('需要授权的角色不存在!', site_url('admin/role/index'), 1);
        }
        //菜单数据
        $result = $this->Menu_model->get_action_list('pingtai=2 AND status=1');
        //获取此角色的权限
        $priv_data = $this->Adminrole_model->get_access_list('role_id=' . $role_id . ' AND type=0');
        $access_arr = [];
        if ($priv_data) {
            foreach ($priv_data as $key => $val) {
                $access_arr[] = $val['app'] . '/' . $val['controller'] . '/' . $val['action'];
            }
        }
//        p($access_arr);
        foreach ($result as $rs) {
            $url = $rs['app'] . '/' . $rs['controller'] . '/' . $rs['action'];
            $data = [
                'id' => $rs['id'],
                'parent_id' => $rs['parent_id'],
                'name' => $rs['name'],
                'checked' => (in_array($url, $access_arr)) ? true : false,//判断此菜单是否已授权
                'open' => true,
            ];
            $json[] = $data;
        }

        $data['json'] = json_encode($json);
        $data['role_id'] = $role_id;
        $data['form_post'] = site_url('admin/role/save_authority');
        $data['index_url'] = site_url('admin/role/index');
        $data['add_url'] = site_url('admin/role/add');
        $this->load->view('role/adminrole_action_authority', $data);
    }

    /**
     * 保存权限
     */
    public function save_authority()
    {
        $arg_post = $this->input->post();
        $role_id = $arg_post['role_id'];
        $type = $arg_post['type'];
        if (empty($arg_post['menu_id'])) {
            $this->error('请至少选择一个权限！', '', true);
        }
        $menuidAll = explode(',', $arg_post['menu_id']);
        if (is_array($menuidAll) && count($menuidAll) > 0) {
            //菜单数据
            if ($type == 1) {
                $menu_info = $this->Menu_model->get_list(['pingtai' => 2]);
            } else {
                $menu_info = $this->Menu_model->get_action_list(['pingtai' => 2]);
            }

            $menu_info = array_column($menu_info, null, 'id');
            $addauthorize = [];
            //检测数据合法性
            foreach ($menuidAll as $menuid) {
                if (empty($menu_info[$menuid])) {
                    continue;
                }
                $info = [
                    'app' => $menu_info[$menuid]['app'],
                    'controller' => $menu_info[$menuid]['controller'],
                    'action' => $menu_info[$menuid]['action'],
                ];
                $info['role_id'] = $role_id;
                $info['sid'] = 100002;
                $info['type'] = $type;
                $addauthorize[] = $info;
            }

            //添加新权限
            $res = $this->Adminrole_model->add_role_access($role_id, $addauthorize, $type);
            if ($res) {
                $this->success('授权成功！', site_url('admin/role/index'), true);
            } else {
                $this->error('授权失败！', site_url('admin/role/index'), true);
            }
        } else {
            $this->error("没有接收到数据，执行清除授权成功！", site_url('admin/role/index'), true);
        }
    }

    /**
     * 异步删除角色
     */
    public function ajax_remove()
    {
        $id = $this->input->get('id');
        $row = $this->Adminuser_model->get_row(['role_id' => $id]);//检查该角色下面是否有人员
        if (!empty($row)) {
            $this->error('该角色下面有管理员，不能删除', '', true);
        }
        $role_info = $this->Adminrole_model->get_row(['id' => $id]);
        if ($role_info['name'] == '超级管理员') {
            $this->error('不能删除超级管理员', '', true);
        }
        $res = $this->Adminrole_model->remove_row(['id' => $id]);
        if ($res) {
            $this->Adminrole_model->remove_role_access(['role_id' => $id]);
            $this->success('操作成功', site_url('admin/role/index'), true);
        } else {
            $this->error('操作失败', '', true);
        }
    }
}
