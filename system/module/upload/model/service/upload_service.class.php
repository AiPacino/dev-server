<?php

/**
* 系统文件上传处理
 * @access public
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
*/

class upload_service extends service
{
    public $header = array(
        "version"   => "0.01",
        "msgtype"   => "request",
        "remark"    => ""
    );
    public $params;

    private  $respone = [
        'ret' => 1,
        'msg' => '上传失败'
    ];
    
    /*图片上传
    *@param $prefix sting 文件前缀名 【可选】
    *@param $is_thumb boolean 是生成启缩略图 【可选】 如果不设置缩略图高度宽度，默认缩略50%
    *@param $width int 文件前缀名 【可选】
    *@param $height int 文件前缀名 【可选】
    * */
    public function file_upload($prefix="",$is_thumb=false,$thumb_width="",$thumb_height=""){

        $file = $_FILES;
        $key_all = array_keys($file);
        $key = current($key_all);

        /*$url = "https://s1.huishoubao.com/zuji/images/test/1512638290560.jpg";
        $end = strpos($url,".com");
        $images_path = substr($url,$end+4,strlen($url));
        $images_name = substr($images_path,strrpos($images_path,"/")+1,strlen($images_path));
        $images_suffix  = substr($images_name,0,strpos($images_name,"."));*/

        if(!empty($file[$key]['tmp_name']))
        {
            $fileName = $file[$key]['name'];
            //后缀名
            $suffix   = strrchr($file[$key]['name'],".");

            $newName = "";
            $newDate  = date("YmdHis");
            $newName = time().mt_rand(10000,99999);
            /*$image = new image();
            $image->open($file[$key]['tmp_name']);
            $width  = $image->width();
            $height = $image->height();*/

            /*if($prefix){
                $newName = $prefix."_".$newDate."_".$width."x".$height;
            }
            else{
                $newName = $newDate."_".$width."x".$height;
            }*/

            //缩略图处理
            /*if(!$is_thumb){
                $thumb_width = $thumb_width?$thumb_width:$width/2;
                $thumb_height = $thumb_height?$thumb_height:$height/2;
                $thumb_name = $newName.config("THUMB_SUFFIX");
                $image->open($file[$key]['tmp_name']);
                $path = './uploadfile/thumb/'.$thumb_name.$suffix;
                $image->thumb($thumb_width, $thumb_height)->save($path);
                $thumb_src = base64_encode(file_get_contents($path));
                $thumb_result = $this->tencentUpload($thumb_name.$suffix,$thumb_src);
                if($thumb_result['ret']!=0){
                    return false;
                }
            }*/

            $fileSrc  = base64_encode(file_get_contents($file[$key]['tmp_name']));
            $result = $this->tencentUpload($newName.$suffix,$fileSrc);

            if($result&&$result['ret']==0&&$result['data']['url']!=""){
                
                $img['picturePath']   = $result['data']['url'];
                $img['originalName'] = $fileName;
                $img['name']        = $newName;
                $this->respone['img'] = $img;
                $this->respone['ret']  = 0;
                $this->respone['msg'] = "上传成功！";
            }
        }
        return $this->respone;
    }
    //app及h5接口图片上传
    public function api_upload($array){

        $data = filter_array($array,[
            'file_name' => 'required',
            'new_name' => 'required',//默认生成新文件名
            'file_src' => 'required'
        ]);
        if(count($data)<2){
            $this->respone['Msg']  = "参数错误！";
            return $this->respone;
        }

        $fileName = $data['file_name'];
        $position = strrpos($fileName,".");
        $suffix = substr($fileName,$position,strlen($fileName));
        if($data['new_name'] == true) {
            $newName = time() . mt_rand(10000, 99999) . $suffix;
        }else{
            $newName = $fileName;
        }

        $fileSrc  = $data['file_src'];
        $result = $this->tencentUpload($newName,$fileSrc);

        if($result['ret']==0&&$result['data']['url']!=""){

            $img['picturePath']   = $result['data']['url'];
            $img['originalName'] = $fileName;
            $img['name']        = $newName;
            $this->respone['img']   = $img;
            $this->respone['ret']    = 0;
            $this->respone['Msg']  = "上传成功！";
        }
        else
        {
            $this->respone['error_info'] = json_encode($result);
        }
        return $this->respone;
    }
    //文件上传核心方法
    private function tencentUpload($name,$src){
        $this->header['interface'] = "upload";

        $this->params = array(
            "time"       => strval(time()),
            "system"     => "0",  //不确定
            "fileName"   => $name,
            "fileSrc"    => $src,
            "path"       => "/zuji/images/",
            "customPath" => "/content/",
            "uploadWay"  => "tencentUpload"
        );

        //把head和params合并并且加密
        $inner = array_merge($this->header,$this->params);
        $sign = $this->sign($inner);
        $this->params["sign"] = $sign;
        $data[ 'head'   ]   = $this->header;
        $data[ 'params' ]   = $this->params;
        $json = json_encode($data);
        //$response =Curl ::post(zuji\Config::Api_Upload_File_Url,$json);
        $response =Curl ::post(config('Api_Upload_File_Url'),$json);
        $result = json_decode($response,true);
        if($result){
            return $result['body'];
        }
        return false;
    }
    //API请求参数加密
    static private  function sign( $param ){
        $sign = "";
        ksort($param);
        foreach( $param as $k=>$v ){
            if(!is_array($v) and $param[$k]){
                $sign .= $k.'='.$v.'&';
            }
        }
        //$sign = strtolower( md5($sign.'key='.zuji\Config::Api_Upload_Key) );
        $sign = strtolower( md5($sign.'key='.  config('Api_Upload_Key')) );
        return $sign;
    }
    
}