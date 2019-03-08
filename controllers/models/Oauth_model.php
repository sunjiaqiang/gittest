<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: 123456
 * Date: 2018/11/16
 * Time: 23:35
 */
class Oauth_Oauth_model extends CI_Model
{
    private $table_oauth_user;//三方授权登录用户表

    public function __construct()
    {
        parent::__construct();
        $this->table_oauth_user = 'cs_oauth_user';
    }

    /**
     * 新增数据
     * @param array $data 要保存的数据数组
     */
    public function add_row($data = [])
    {
        $result = $this->db->insert($this->table_oauth_user, $data);
        if (false !== $result) {
            $uid = $this->db->insert_id();
            return $uid;
        } else {
            return false;
        }
    }

    /**
     * 获取单条信息
     * @param array $where 筛选条件
     * @param string $field 查询字段
     */
    public function get_row($where = [], $field = '*')
    {
        $this->db->select($field);
        $query = $this->db->get_where($this->table_oauth_user, $where, 1);
        $row = $query->row_array();
        return $row ?: [];
    }

    /**
     * 修改数据
     * @param array $data 数据数组
     * @param string $where 筛选条件
     * @return mixed
     */
    public function edit_row($data = [], $where = [])
    {
        $this->db->where($where, null, false);
        $result = $this->db->update($this->table_oauth_user, $data);
        if (false !== $result) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 获取总数量
     * @param array $where 查询条件
     */
    public function get_count($where = [])
    {
        $this->db->where($where, NULL, FALSE);
        $this->db->select('uid');
        $this->db->from($this->table_oauth_user);
        return $this->db->count_all_results();
    }
    /**
     * 获取所有三方登陆用户
     * @param int $page_size 每页显示数量
     * @param int $now_page 第几页
     * @param $where 选择条件
     * @return array
     */
    public function get_list($page_size = 20, $now_page = 1, $where = '')
    {
        $this->db->select('uid,nickname,head_img,create_time,last_login_time,last_login_ip,login_times,status,email');
        $this->db->order_by('create_time','DESC');
        $query = $this->db->get($this->table_oauth_user, $page_size, ($now_page - 1) * $page_size);
        $result = $query->result_array();
        return $result ?: [];
    }
}