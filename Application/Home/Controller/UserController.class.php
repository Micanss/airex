<?php
/**
* Author:Patrick95 (lawcy@qq.com)
* Date:2016/5/27
* Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
 */
namespace Home\Controller;
use Home\Controller\BaseController;
/**
 * 用户控制器.
 */
class UserController extends BaseController{

	/**
	 * 验证码生成
	 */
	public function captcha(){
		$Verify = new \Think\Verify();
		$Verify->fontSize = 30;
		$Verify->length   = 4;
		$Verify->useNoise = false;
		$Verify->entry();
	}

	/**
	 * 用户登录
	 */
	public function login(){
		if (checkLogin()) {
			$this->redirect("Index/index",'',0);
		}
		if (IS_POST){
			$User = new \Home\Model\UserModel();
			$postinfo=array("user_name"=>I('post.username'),"password"=>I('post.password'));
			switch($User->userLogin($postinfo)){
				case 0: //无此用户
					$this->error('没有此用户！');
					break;
				case 1: //登录成功
					$User->updateLoginIP($postinfo['user_name']); //更新用户登录IP
					$this->success('登录成功，正在转向首页...',U('Index/index'),1);
					break;
				case 2: //密码不对
					$this->error('用户名或密码不正确！');
					break;
			}
		}else{
			$this->display();
		}

	}


	/**
	 * 用户注册
	 */
	public function register(){
		if (checkLogin()) {
			$this->redirect("Index/index",'',0);
		}
		$User = new \Home\Model\UserModel();
		if (IS_POST){
			if(check_verify(I('post.captcha'))){

				$postinfo=array("user_name"=>I('post.username'),"password"=>I('post.password'),"email"=>I('post.email'));
				//自动验证
				if ($User->userRegister($postinfo)) {
					$this->success('注册成功！','login'); //注册成功转向登录页
				}else{
					$this->error($User->getError());
				}


			}else{
				$this->error('验证码错误，请重新输入！');
			}

		}else{

			$this->display();
		}

	}

	/**
	 * AJAX检查占用用户名接口
	 */
	public function checkUsername(){
		$User = new \Home\Model\UserModel();
		$username = I('post.username');
		echo $User->checkUsername($username);

	}

	/**
	 * AJAX检查占用Email接口
	 */
	public function checkEmail(){
		$User = new \Home\Model\UserModel();
		$email = I('post.email');
		echo $User->checkEmail($email);

	}

	/**
	 * 忘记密码
	 */
	public function forgot(){
		if (checkLogin()) {
			$this->redirect("Index/index",'',0);
		}
		if(IS_POST){
			$User = new \Home\Model\UserModel();
			$email = I('post.email'); //得到参数email
			if($username = $User->getUsernameByEmail($email)){ //此email是否存在数据库中
				if($User->sendResetpwEmail($email,$username)){  //发送邮件给此email
					$this->success('重置密码邮件发送成功！',U('Index/index'));
				}else{
					$this->error('邮件发送失败');
				}
			}else{
				$this->error('不存在此邮箱');
			}

		}else{
			$this->display();
		}
	}

	/**
	 * 重置密码
	 */
	public function resetpw(){
		if (checkLogin()) {
			$this->redirect("Index/index",'',0);
		}
		if(I('get.hash')){  //如果存在hash参数
			$hash = I('get.hash');
			$User = new \Home\Model\UserModel();
			if($username = $User->checkResetpwHash($hash)){
				$this->assign('username',$username); //将用户名输出到前端
				if(IS_POST) {
					if ((I('post.password')) != (I('post.password_r'))) {
						$this->error('两次密码不一致');
					}elseif((I('post.password'))=="" || (I('post.password')=="")){
						$this->error('密码不能为空');
					}elseif(strlen(I('post.password'))<6){
						$this->error('密码长度不得小于6位');
					}else{
						$User->updatePassword($username,I('post.password'));//更新密码
						$User->deleteResetpwHash($hash); //删除此重置hash
						$this->success('密码已经重置成功！','login'); //密码重置成功转向登录页
					}
				}else{
					$this->display();
				}
			}else{
				$this->error('不存在此重置密钥或已失效',U('Index/index'));
			}


//			if(session('?'.$hash)){ //如果session中存在此hash
//				//session(NULL);
//				$username = session($hash);  //得到hash中的用户名
//				$this->assign('username',$username); //将用户名输出到前端
//				$this->display();
//			}else{
//				//session(NULL);
//				$this->error('不存在此重置密钥或已失效',U('Index/index'));
//			}
		}else{
			$this->error('非法操作',U('Index/index'));
		}


	}

	/**
	 * 用户信息设置
	 */
	public function setting(){
		if (!checkLogin()) {
			$this->redirect("Index/index",'',0);
		}

		if($_POST['gender']){
			if (!preg_match("/^(http|ftp):/", $_POST['url'])){
   			$_POST['url'] = 'http://'.$_POST['url'];
			}   //检测是否有http头，若无则加上
			$data = array("id" => I('session.uid'),
			"gender" => I('post.gender'),
			"url" => I('post.url'),
			"resume" => I('post.resume'));
			$User = new \Home\Model\UserModel();
			if($User->updateUserInfo($data)){
				$this->success('用户信息更新成功！');
			}else{
				$this->error($User->getError());
			}
		}elseif($_POST['password']){
			if ((I('post.password')) != (I('post.password_r'))) {
				$this->error('两次密码不一致');
			}elseif((I('post.password'))=="" || (I('post.password')=="")){
				$this->error('密码不能为空');
			}elseif(strlen(I('post.password'))<6){
				$this->error('密码长度不得小于6位');
			}else{
				$username = I('session.user');
				$User = new \Home\Model\UserModel();
				$User->updatePassword($username,I('post.password'));//更新密码
				$this->success('密码已经重置成功！'); //密码重置成功转向登录页
			}
		}else{
			$User = new \Home\Model\UserModel();
			$data = $User->getSettingUserInfo();
			$this->assign('data', $data);
			$this->display();
		}


	}

	/**
	 * 用户登出
	 */
	public function logout(){
		session('user',null);
		session('user_id',null);
		$this->redirect("User/login",'',0);
	}

	/**
	 * 用户信息页
	 */
	public function info($member){

			$User = new \Home\Model\UserModel();
			$data = $User->getUserInfo($member);
		if($data){
			$this->assign('data',$data);
			$this->display();
		}else{
			$this->error('此用户不存在！');
		}

	}

}