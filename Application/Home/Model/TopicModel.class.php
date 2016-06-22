<?php 
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Author: Micanss <micanss@gmail.com>
// +----------------------------------------------------------------------
namespace Home\Model;
use Think\Model;
/**
* 后台主题控制器
*/
class TopicModel extends Model
{	
	//自动验证
	protected $_validate = array(
		array('title','require','主题标题不能为空.',1),
		array('title','checkLength_t','标题不要超过120个字符',1,'callback'),
		array('content','checkLength_c','话题内容不要超过2000个字符',1,'callback'),
		array('node_id','checkNodeId','请不要修改node值.',1,'callback'),
		);

	//自动完成
	protected $_auto = array(
		array('publish_time','getTime',1,'callback'),
		);
		
	
	/**
	 * 添加主题
	 * @param [type] $data [description]
	 */
	public function addTopic($data){
		if ($this->create($data)) {
			$this->startTrans();
			if ($this->add()) {
				if ($this->addTrigger($data['node_id'])) {
					$this->commit();
					return true;
				}else{
					$this->rollback();
					return false;
				}
			}else{
				$this->rollback();
				return false;
			}
		}
	}

	/**
	 * 追加主题内容
	 * @param  [type] $tid     [description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public function appendContent($tid,$content){
		$originContent = $this->where(array('id'=>$tid))->getField('content');
		$newContent = $originContent.'<br>'.$content;
		if ($this->where(array('id'=>$tid))->setField('content',$newContent)) {
			return true;
		}
		return false;
	}

	/**
	 * 检查所属节点值
	 * @param  [type] $nodeId [description]
	 * @return [type]         [description]
	 */
	function checkNodeId($nodeId){
		$nodeIds = M('node')->getField('id',true);
		if (!in_array($nodeId, $nodeIds)) {
			return false;
		}
		return true;
	}

	/**
	 * 获取当前时间
	 * @return [type] [description]
	 */
	function getTime(){
		return date('Y-m-d H:i:s',time());
	}

	/**
	 * 检查content字符长度
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	function checkLength_c($content){
		if (mb_strlen($content) > 2000) {
			return false;
		}
		return true;
	}

	/**
	 * 检查title字符长度
	 * @param  [type] $title [description]
	 * @return [type]        [description]
	 */
	function checkLength_t($title){
		if (mb_strlen($title) > 120) {
			return false;
		}
		return true;
	}

	/**
	 * 根据tid获取主题详情
	 * @param  [type] $tid [description]
	 * @return [type]      [description]
	 */
	public function getDataById($tid){
		$topicInfo = $this
				->where(array('airex_topic.id'=>$tid))
				->join('airex_node as n on n.id = airex_topic.node_id')
				->field('title,content,publish_time,user_name,airex_topic.hits as hits,collections,comments,node_name,imgpath,last_comment_time')
				->join('airex_user as u on u.id = airex_topic.uid')
				->select()[0];
		return $topicInfo;
	}

	/**
	 * 检查tid是否存在
	 * @param  [type] $tid [description]
	 * @return [type]      [description]
	 */
	public function checkTid($tid){
		$tids = $this->getField('id',true);
		if (!in_array($tid, $tids)) {
			return false;
		}
		return true;
	}

	/**
	 * 根据分类获取相应主题
	 * @param  [type] $cat [description]
	 * @return [type]      [description]
	 */
	public function getTopicsByCat($catName){
		if ($catName == null) {
			$catName = M('category')->getField('cat_name');
		}
		$p = I('get.p') ? I('get.p') : 0;
		$count = M('category as c')->where(array('cat_name'=>$catName))
							  ->join('airex_topic as t on t.cat_id = c.id')
							  ->count();
		$limit = C('PAGE_SIZE');
		$Page = new \Think\Page($count,$limit);
		//获取分页数据
		$topics['lists'] = M('category as c')->where(array('cat_name'=>$catName))
										->join('airex_topic as t on t.cat_id = c.id')
										->join('airex_user as u on u.id = t.uid')
										->field('publish_time,title,imgpath,comments,user_name,node_name,t.id as tid,t.hits as hits,last_comment_user')
										->join('airex_node as n on n.id = t.node_id')

									    ->page($p.','.$limit)
									    ->order('t.publish_time desc')
									    ->select();
		$show = $Page->show();
		if ($Page->totalPages > 1) {
			$topics['show'] = $show;
		}else{
			$topics['show'] = null;
		}
		return $topics;
	}

	/**
	 * 根据节点获取主题
	 * @param  string $nodeName [description]
	 * @return [type]           [description]
	 */
	public function getTopicsByNode($nodeName = ''){
		$p = I('get.p') ? I('get.p') : 0;
		$limit = C('PAGE_SIZE');
		$count = M('node as n')->join('airex_topic as t on t.node_id = n.id')
							   ->where(array('node_name'=>$nodeName))
							   ->count();
		$Page = new \Think\Page($count,$limit);
		$topics['lists'] = M('Node as n')->where(['n.node_name'=>$nodeName])
						 ->join('airex_topic as t on t.node_id = n.id')
						 ->join('airex_user as u on u.id = t.uid')
						 ->field('publish_time,title,imgpath,comments,user_name,node_name,t.id as tid,t.hits as hits,last_comment_user')
						 ->page($p.','.$limit)
						 ->order('t.publish_time desc')
						 ->select();
		$show = $Page->show();
		if ($Page->totalPages > 1) {
			$topics['show'] = $show;
		}else{
			$topics['show'] = null;
		}
		return $topics;
	}

	/**

	 * 根据用户名获取主题
	 * @param  string $username [description]
	 * @return [array] topics           [description]
	 */
	public function getTopicsByUser($username,$limit=''){
		$topics['lists'] = M('user as u')->where(array('user_name'=>$username))
			->join('airex_topic as t on t.uid = u.id')
			->join('airex_node as n on n.id = t.node_id')
			->field('publish_time,title,imgpath,comments,node_name,user_name,t.id as tid,t.hits as hits,last_comment_user')
			->order('t.publish_time desc')
			->limit('0,'.$limit)
			->select();
		return $topics;
	}

	/**
	 * 根据用户ID获取主题
	 * @param  array $uid [description]
	 * @return [type]           [description]
	 */
	public function getTopicsByUserID($uid){
		$sql = 'uid=';
		$uid_last = array_pop($uid);
		foreach($uid as $u){
			$sql .= $u.' OR uid=';
		}
		$sql .= $uid_last;
		$topics['lists'] = M('topic as t')->where($sql)
								->join('airex_user as u on u.id = t.uid')
								->join('airex_node as n on n.id = t.node_id')
								->field('publish_time,title,u.imgpath as imgpath,comments,n.node_name as node_name,u.user_name as user_name,t.id as tid,t.hits as hits,last_comment_user')
			 					->order('publish_time desc')
								->select();
		return $topics;

	}


	/**
	 * 触发更新
	 * @return [type] [description]
	 */
	public function addTrigger($nodeId){
		if (!M('node')->where(array('id'=>$nodeId))->setInc('topic_num')) {
			return false;
		}
		if (!M('siteinfo')->where('id=1')->setInc('topic_num')) {
			return false;
		}
		return true;
	}

	/**
	 * 根据tid获取字段信息
	 * @param  [type] $tid    [description]
	 * @param  [type] $fields [description]
	 * @return [type]         [description]
	 */
	public function getFieldByTid($tid,$fields){
		if (is_array($fields)) {
			$fields = implode(',',$fields);
		}
		$result = $this->where(array('id'=>$tid))
					 ->field($fields)
					 ->select()[0];
		return $result;
	}

}
