<?php 
namespace Home\Model;

/**
* 工厂模型
*/
class FactoryModel
{
	private static $userModel;		//User模型
	private static $topicModel;

	static function createUserModel(){
		if (!self::$userModel) {
			self::$userModel = new \Home\Model\UserModel();
		}
		return self::$userModel;
	}
}
