<?php

hd_core::load_class('init', 'admin');
class contract_control extends control {

	public function _initialize()
	{
		parent::_initialize();
		$this->contract = $this->load->table("contract/contract");
	}

	/**
	 * 电子合同配置信息列表
	 */
	public function index() {

		$contract_list = $this->contract->select();
		if($contract_list)
		{
			foreach($contract_list as &$item){
				$item['status'] = "默认";
				$item['create_time'] = date("Y-m-d H:i:s",$item['create_time']);
			}
			$lists = array(
					'th' => array(
							'template_id' => array('title' => '模板ID','length' => 15),
							'name' => array('title' => '合同名称','length' => 10),
							'file_url' => array('title' => '合同模板地址','length' => 40),
							'status' => array('title' => '状态','length' => 10),
							'create_time' => array('title' => '时间', 'length' => 15),
					),
					'lists' => $contract_list,
			);
		}
		else{
			$lists = [];
		}

		$this->load->librarys('View')->assign('lists',$lists)->display('index');
	}
	/**
	 * 编辑及添加操作
	 */
	public function edit(){
		if (checksubmit('dosubmit')) {
			//上传文档处理
			if (!empty($_FILES['content_pic']['name'])) {
				$this->upload = $this->load->service("upload/upload");
				$result = $this->upload->file_upload();
				if ($result['ret'] != 0) {
					showmessage("上传文件失败",'',1,'json');
				}
				$file = $result['img']['picturePath'];
			}
			//修改
			if($_POST['id']){

				$data = [
						'id' => $_POST['id'],
						'name' => $_POST['name'],
						'create_time' => time(),
				];

				if($file)
				{
					$data['template_id'] = date("YmdHis");
					$result = $this->contract_upload($file,$data['template_id']);
					if(!$result){
						showmessage("上传模板error",'',1,'json');
					}
					$data['file_url'] = $file;
				}


				$ret = $this->contract->save($data);
				if(!$ret){
					showmessage("更新模板失败",'',1,'json');
				}
				showmessage("更新成功！",'',1,'json');
			}
			//添加
			else
			{
				if(!$file){
					showmessage("无模板文件",'',1,'json');
				}
				$data = [
					'template_id' => date("YmdHis"),
					'name'=>$_POST['name'],
					'file_url'=>$file,
					'create_time' => time(),
				];
				$result = $this->contract_upload($data['file_url'],$data['template_id']);
				if(!$result){
					showmessage("上传模板error",'',1,'json');
				}
				//添加文档
				$ret = $this->contract->add($data);
				if(!$ret){
					showmessage("新增模板error",'',1,'json');
				}
				showmessage("成功！",'',1,'json');
			}

		}
		//查看
		$id = $_GET['id'];
		if($id){
			$rom = $this->contract->where(['id'=>$id])->find();
		}
		else{
			$rom = [];
		}
		$this->load->librarys('View')->assign("rom",$rom)->display('update');
	}
	/**
	 * 法大大电子合同模板上传接口
	 */
	function contract_upload($file_url,$template_id){
		$url = config("Contract_Create_Url");
		$header = [
				'Content-Type: application/json',
		];
		$params = [
				"template_id"=>$template_id,
				"doc_url"=>$file_url
		];
		$params = json_encode($params);
		$result = zuji\Curl::post($url,$params,$header);
		$result = json_decode($result,true);
		if($result['result']=="success"){
			return true;
		}
		return false;
	}
}