<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 2019/2/27
 * Time: 11:05
 */
abstract class News2{
    const name = "测试02222";
    public function index(){
        echo 'index';
    }
    public abstract function test($name);
}

interface meth{
    public function add_row($data=[],$where=[]);
    public function remove_row($where=[]);
}

trait _news{
    public function get_list(){
        return "list";
    }
    public static function _update(){
        echo "trait _news update";
    }
}

class Test2 extends News2 implements meth{
    use _news;
    protected $name2 = '678';
    public function test($name)
    {
        // TODO: Implement test() method.
        echo '555'.$name;
    }
    public function add_row($data=[],$where=[])
    {
        // TODO: Implement add_row() method.
    }
    public function remove_row($where = [])
    {
        // TODO: Implement remove_row() method.
        $arr = [
            '1',
            '2'
        ];
        $arr = serialize($arr);
        $arr = serialize($arr);
        echo $arr;
    }
    public function __sleep()
    {
        return'自动被调用dsdse33344';
    }

    public function __set($name, $value)
    {
        // TODO: Implement __set() method.
        echo 'set '.$name.' to'.$value,'<br>';
        $this->$name = $value;
    }

    public function __get($name)
    {
       
	   
	   
        echo 'get '.$name,'<br>';
        if (isset($this->$name)){
            echo $this->$name,'<br>';
        }else{
            echo $name.' 不存在';
        }
    }
    private function mypri(){
        echo '我是private方法';
    }
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        if (method_exists($this, $name)){
            $this->$name();
        }else{
            echo "你调用的".$name."方法不存在454545";
        }
    }
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        if (method_exists($this, $name)){
            $this->$name();
        }else{
            echo "你调用的".$name."方法不存在fdgd";
        }
    }
}
$dtest = new Test2();
$teest->test('diii');
$tewst->index();
echo $test->get_list(),'<br>';
$test->b = '22';
echo $test->b,'<br>';
Test2::_update();
$test->name2;
$test->mypri2();
$test->remove_row();
