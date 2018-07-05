<?php

class face_control extends api_control {

   public static $testConfig = [
    'api_key'       => 'GdZYA8MCF8eHlKIS0Wct_ae-Ikv0C9_9',
    'api_secret'    => 'Ko3dCwskW8HHyqmLlRgg0kVZyHwnqvlt',
   ];

    public static $proConfig = [
        'api_key'       => 'LnJV04Fzv-QZ-KGfm0AyD8EoXIMQBN52',
        'api_secret'    => 'dsAE-vwnAho3LNsQRb5Q-k4E6qjfWMfq',
    ];

   public static $uri = "https://api.megvii.com/";

   /**
    * 此接口提供基于人脸比对的身份核实功能，支持有源比对（调用者提供姓名、身份证号、和待核实人脸图）和无源比对（直接比对待核实人脸图和参照人脸图）。
    * 待核实人脸图可以由FaceID MegLive SDK产品提供，也可以由detect接口获得，还可以直接提供未经过detect方法检测的人脸图片。
    */
    public static $faceVerify = "faceid/v2/verify";

    /**
     * 根据位置ID，获取内容方式列表接口
     */
    public function verify() {

        $params   = $this->params;

        if(empty($params['idcard_name'])){
            return api_resopnse( [], ApiStatus::CODE_20001,'idcard_name request');
        }

        if(empty($params['idcard_number'])){
            return api_resopnse( [], ApiStatus::CODE_20001,'idcard_number request', ApiSubCode::Sku_Error_Sku_id);
        }

        if(!isset($_FILES["meglive_flash_result"]["tmp_name"])) {
            return api_resopnse( [], ApiStatus::CODE_20001,'meglive_flash_result file request', ApiSubCode::Sku_Error_Sku_id);
        }

        $defaultForm = [
            "multi_oriented_detection" => 0, //default 0
        ];

        $form = array_merge($defaultForm, $params, self::$testConfig);

        $form['comparison_type'] = 1;
        $form['face_image_type'] = 'meglive_flash';

        $postForm = [
//            "idcard_name" => '万树启',
//            "idcard_number" => '132624198505316213',
//            "multi_oriented_detection" => 1, //default 0
            "idcard_name" => $params["idcard_name"],
            "idcard_number" => $params["idcard_number"],
            "meglive_flash_result" => file_get_contents($_FILES["meglive_flash_result"]["tmp_name"])
        ];

        $form = array_merge($form, $postForm);
        $result = \zuji\Curl::post(self::$uri . self::$faceVerify, $form);
        return  api_resopnse( $result, ApiStatus::CODE_0);
	}
    
    
}
