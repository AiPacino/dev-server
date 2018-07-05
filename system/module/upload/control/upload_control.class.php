<?php
hd_core::load_class('init', 'admin');
/**
* CDN文件上传处理
 * @access public
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
*/

class upload_control extends control
{
    public function index(){
        $this->load->librarys('View')->display("update");
    }

    public function upload(){
        if ($_FILES) {
            $this->upload = $this->load->service("upload/upload");
            $result = $this->upload->file_upload();
            if ($result['ret'] != 0) {
                echo json_encode(['ret'=>1]);
                die;
            }
            $file = $result['img']['picturePath'];
            echo json_encode(['ret'=>0,'path'=>$file]);
            die;
        }
    }
    
}