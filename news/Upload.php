<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13 0013
 * Time: 22:33
 */
class Admin_Upload_module extends CI_Module
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 上传图片
     */
    public function upload_pic()
    {
        $allow_ext = ['jpg', 'png', 'gif', 'jpeg'];//允许上传的类型
        $allow_max_size = 1 * 1024 * 1024;//5m=5242880 b 允许上传的大小
        $save_path = './public/uploads/';//后面必须加/
        $this->load->library('myfile');
        $this->myfile->allowExts = $allow_ext;
        $this->myfile->savePath = $save_path;
        $this->myfile->maxSize = $allow_max_size;
        $this->myfile->upload();
    }

    /**
     * wangEditor上传图片
     */
    public function upload_wang()
    {
        $target_path = './public/uploads/wangeditor/';
        file_exists($target_path) OR create_folders($target_path);
        $arr = [];
        if (!empty($_FILES)) {
            foreach ($_FILES as $key => $val) {
                $type = strtolower(substr(strrchr($val['name'], '.'), 1));
                $typeArr = ["jpg", "jpeg", "png", 'gif'];
                $type = strtolower(substr(strrchr($val['name'], '.'), 1));
                if (!in_array($type, $typeArr)) {
                    echo json_encode(["error" => -1, 'msg' => "文件类型不支持"]);
                    exit;
                }
                $size = $val['size'];
                if ($size > 300 * 1024) {
                    echo json_encode(["error" => -1, 'msg' => "图片超过大小300KB"]);
                    exit;
                }
                $name = date('YmdHis', time()) . rand(10000, 99999) . '.' . $type;
                //echo $name,'<br>';
                $pic_url = $target_path . $name;
                $tmp_name = $val['tmp_name'];
                if (copy($val['tmp_name'], $pic_url) || move_uploaded_file($val['tmp_name'], iconv('utf-8', 'gbk', $pic_url))) {
                    add_water($pic_url);
                }
            }
            echo trim($pic_url, '.');
        }

    }

    public function upload_wang2()
    {
        $target_path = './public/uploads/tinymce/';
        file_exists($target_path) OR mkdir($target_path);
        $arr = [];
        if (!empty($_FILES)) {
            foreach ($_FILES as $key => $val) {
                $typeArr = ["jpg", "jpeg", "png", 'gif'];
                $type = strtolower(substr(strrchr($val['name'], '.'), 1));
                if (!in_array($type, $typeArr)) {
                    echo json_encode(["error" => -1, 'msg' => "文件类型不支持"]);
                    exit;
                }
                $name = date('YmdHis', time()) . rand(10000, 99999) . '.' . $type;
                //echo $name,'<br>';
                $pic_url = $target_path . $name;
                $tmp_name = $val['tmp_name'];
                if (move_uploaded_file($val['tmp_name'], iconv('utf-8', 'gbk', $pic_url))) {
                    $arr[] = trim($pic_url, '.');
                }
            }
            $data = [
                'location' => implode(',', $arr)
            ];
            echo json_encode($data);
        }
    }
}