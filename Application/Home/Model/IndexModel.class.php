<?php
namespace Home\Model;

use Think\Model;

/**
 * 主页模型类.
 * Author:Patrick95
 * Date:2016/5/28
 * Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
 */
class IndexModel extends Model{

	/**
	 * 获取首页用户信息展示
	 * @return array 获取的数据
	 */
    public function getUserInfo(){
        $uid = session('uid');
        $User = M("User");
        $data = $User->where(array('id'=>$uid))->field('imgpath,attentions,topics,wealth,');
        return $data;
    }

}