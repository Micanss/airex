<?php 
namespace Home\Controller;

use Home\Controller\BaseController;

/**
* 主题控制器
*/
class TopicController extends BaseController
{
	
	function __construct()
	{
		parent::__construct();
		if (checkLogin()) {
			$this->redirect("User/login",'',0);
		}
	}

	public function index(){
		$this->redirect("Index/index",0);
	}

	public function detail(){
		$this->display();
	}
}