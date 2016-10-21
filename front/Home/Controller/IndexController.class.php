<?php
namespace Home\Controller;

use Think\Controller;
class IndexController extends Controller
{
	private $snoopy;
	private $ltrModel;
	private $issueModel;
	private $issueInfoModel;
	private $errorTypeModel;
	private $errorLogModel;
	private $url = array('168kai','shishicai','aicai','zhcw','sogou','163','360','500','swlc','icaile');

	private $flushlog = "flushlog.txt";//脚本log

	public function __construct(){
		$this->snoopy = new \Think\Snoopy();
		parent::__construct();

		/*
		彩种种类
		LTR_CD			LTR_NM				CMMT
		DP_FC_3D		福彩3D				\N
		DP_SSC_P35		排3排5				\N
		GP_SSC_CQ11X5	重庆11选5			\N
		GP_SSC_CQSSC	重庆时时彩			\N
		GP_SSC_GD11X5	广东11选5			\N
		GP_SSC_HLJSSC	黑龙江时时彩		\N
		GP_SSC_JXDLC	多乐彩(江西11选5)	\N
		GP_SSC_JXSSC	江西时时彩			\N
		GP_SSC_SHSSL	上海时时乐			\N
		GP_SSC_SYYDJ	十一运夺金			\N
		GP_SSC_XJSSC	新疆时时彩			\N
		 */
		$this->ltrModel = M('LTR_TYPE_D');
		
		/*
		奖期和开奖号码，开始时间，结束时间，开奖结果，状态：1-正常；2-提前；3-未开；4-结果不一致，结果生成时间
		LTR_CD 			彩种代码
		PLATFORM_ISSUE 	平台期号
		BEGIN_TM 		开售时间
		END_TM 			截止时间
		LTR_RS 			
		STAT 			状态：1-正常；2-提前；3-未开；4-结果不一致
		CREATE_TM 		结果生成时间
		 */
		$this->issueModel = M('ISSUE_INFO_B');

				
		
		/*开奖源列表
		SRC_CD 			号源代码
		SRC_NM			号源名称
		SRC_TYPE_CD 	号源类型代码
		BASE_URL		基本URL
		IS_DYNC_URL		是否动态URL: 1-是 ; 0-否
		DYNC_URL_RULE 	动态URL规则: YYYYMMDD日期,随机数等形式
		DYNC_URL_SUFFIX	动态URL后缀:html,xml,json等
		LTR_CD			彩种代码
		WEIGHT			权重
		IS_USE 			是否启用: 1-是;0-否
		ISSUE_XPATH 	期号路径:一般用XPATH方式，json类型的号源用“.”连接之前所有key的名称
		ISSUE_FORMAT 	期号格式化公式,如：list2str;substr$0$9;replace$|$,;
		ISSUE_JSON_KEY 	期号对应JSON的key值名称
		RS_XPATH		结果值路径:一般用XPATH方式，json类型的号源用“.”连接之前所有key的名称
		RS_FORMAT 		list2str;substr$0$9;replace$|$,;
		RS_JSON_KEY 	结果对应JSON的key值名称
		RS_LEN 			结果长度,含连接字符","
		REG_VALID 		正则验证
		IS_LOGIN 		是否需要登录:1-是 ; 0-否
		LOGIN_URL 		登录URL
		USERNAME 		登录用户名
		PWD 			登录密码
		CMMT 			备注
		数据形式（html,xml,json）三种形式的数据，
		彩种代码，
		权重，权重越高，可信度越高，权重为10的可以直接开奖，权重不为10的、有多个开奖源，对比开奖，如果号码不通，则返回报错。

		开奖源网站类型
		1.http://www.168kai.com
		2.http://www.shishicai.cn
		3.http://kaijiang.aicai.com
		4.http://tubiao.zhcw.com/
		5.http://t.cp.sogou.com
		6.http://caipiao.163.com
		7.http://chart.cp.360.cn
		8.http://kaijiang.500.com
		9.http://cp.swlc.sh.cn
		10.http://pub.icaile.com
		*/
		$this->issueInfoModel = M('SRC_INFO_B');
				

		/*
		"ERROR_CD"	"ERROR_NM"
		"1"	"URL响应异常"
		"2"	"提前开奖"
		"3"	"结果不一致"
		"4"	"缺少期号配置"
		"5"	"推送异常"
		"6"	"保存结果异常"
		"7"	"缺少奖期数据"
		 */
		$this->errorTypeModel = M("ERROR_INFO_D");
		/*
		ID
		LTR_CD			彩种代码
		SRC_CD 			号源代码
		PLATFORM_ISSUE	平台期号
		ERROR_CD 		错误类型代码
		CMMT 			描述
		TM 				时间
		 */
		$this->errorLogModel = M("ERROR_INFO_R");
	}
    public function index()
    {
    	// 测试代码正式环境使用脚本刷  php index.php /home/index/index GP_SSC_GD11X5 flush;广东11选5 $_SERVER['argv'][2] = GP_SSC_GD11X5
    	//获取在这个时间段的奖期信息  BEGIN_TM<NOW<END_TM
    	G('begin');//开始时间记录
    	writeLog("start fetch code...");//写入日志
    	// sleep(2);
    	// G('end');
    	// echo G('begin','end','m');
    	// $this->lottery = 'GP_SSC_SYYDJ';//11运夺金
    	//验证此彩种是否存在
    	$this->lottery = 'GP_SSC_SYYDJ';
    	$ltrInfo = $this->getInfoByLtr();
    	if(!$ltrInfo){
    		writeLog($this->lottery."没有此彩种！");//写入日志
    		return ;
    	}
    	//获取此彩种应抓号奖期的信息 
    	$bLotteryInfo = $this->getLastNoCode();
    	if(!$bLotteryInfo){
    		writeLog($this->lottery."奖期未生成!");//写入日志
    		return ;
    	}
    	//抓号的奖期
    	$this->issue = $bLotteryInfo['platform_issue'];

    	if(is_string($bLotteryInfo['ltr_rs']) && (int)$bLotteryInfo['stat'] == 1){
    		writeLog($this->lottery." ".$this->issue."已经完成抓号");//写入日志
    		return ;
    	}

    	//获取开奖源SRC_INFO_B,权重越高，开奖可信度越高，权重为10的直接开奖
    	$aSourceInfo = $this->getSourceUrl();
    	if(!$aSourceInfo){
    		writeLog($this->lottery."没有开奖源");//写入日志
    		return ;
    	}

    			
    	//根据开奖源开号
    	$result = $this->fetchCodeFromUrl($aSourceInfo);
    	if($result['status'] == 'success'){
    		//插入数据库
    	}

    	// var_dump($result);
    	// writeLog($lottery);//写入日志
    }
    /**
     * [getLastNoCode 获取要抓号的奖期信息]
     * @author 1023
     * @date          2016-10-21
     * @param  [type] $lottery   [彩种]
     * @return [type]            [奖期信息]
     */
    private function getLastNoCode(){
    	if(!$this->lottery){
    		writeLog("参数错误!");//写入日志
    		return array();
    	}
    	$aSql = "LTR_CD='".$this->lottery."'";
    	$aLottery = $this->issueModel->where($aSql)->find();
    	if(!$aLottery){
    		writeLog($this->lottery."奖期不存在!");//写入日志
    		return array();
    	}		
    	//算出本彩种的时间间隔10分钟即为600秒，一般为10分钟，福彩3d等为1天
    	$offsetTime = strtotime($aLottery['end_tm']) - strtotime($aLottery['begin_tm']);

    	//脚本执行时间为本期结束后，所以当前时间减去奖期间隔即为在上期奖期间隔时间内
    	$issuTime = date("Y-m-d H:i:s",time()-$offsetTime);//正确的时间判断
    	// $issuTime = date("Y-m-d H:i:s",time());

    	$bSql = " BEGIN_TM<'{$issuTime}' and END_TM>'{$issuTime}' and LTR_CD='".$this->lottery."'";
    	$bLotteryInfo = $this->issueModel->where($bSql)->find();

    	return $bLotteryInfo?$bLotteryInfo:array();
    }

    /**
     * [getInfoByLtr 查LTR_TYPE_D是否有此彩种]
     * @author 1023
     * @date          2016-10-21
     * @param  [type] $lottery   [彩种名称]
     * @return [type]            [array]
     */
    private function getInfoByLtr(){
    	if(!$this->lottery){
    		writeLog("参数错误!");//写入日志
    		return array();
    	}

    	$bLtrInfo = $this->ltrModel->where("LTR_CD='".$this->lottery."'")->find();
    	
    	return $bLtrInfo?$bLtrInfo:array();
    }

    /**
     * [getSourceUrl 获取开奖源链接]
     * @author 1023
     * @date          2016-10-21
     * @return [type] [array]
     */
    private function getSourceUrl(){
    	if(!$this->lottery){
    		writeLog("参数错误!");//写入日志
    		return array();
    	}
    	//后去启用的源
    	$urlSql = "LTR_CD='{$this->lottery}' and IS_USE=1";
    	$sourceInfo = $this->issueInfoModel->field("BASE_URL,WEIGHT,REG_VALID")->where($urlSql)->order('WEIGHT asc')->select();
    	return $sourceInfo?$sourceInfo:array();
    }
    /**
     * [fetchCodeFromUrl 开始抓号]
     * @author 1023
     * @date          2016-10-21
     * @param  [type] $source    [array]
     * @return [type]            [array]
     * 	1.http://www.168kai.com
		2.http://www.shishicai.cn
		3.http://kaijiang.aicai.com
		4.http://tubiao.zhcw.com/
		5.http://t.cp.sogou.com
		6.http://caipiao.163.com
		7.http://chart.cp.360.cn
		8.http://kaijiang.500.com
		9.http://cp.swlc.sh.cn
		10.http://pub.icaile.com
     */
    private function fetchCodeFromUrl($source){
    	//判断开奖源的连接，json，xml，html，使用不同的方法
    	foreach ($source as $val) {
    		$directUrl[] = $val['base_url'];
    		$urlArr = explode(".",$val['base_url']);
    		$arr = array_intersect($this->url, $urlArr);
    		shuffle($arr);//打乱重新排序
    		$arrUrl[] = $arr[0];
    	}    			
    			
    	for ($i=0; $i <count($directUrl) ; $i++) {


    		switch ($arrUrl[$i]) {
    			case 'shishicai':
    			   	writeLog("notice:".$directUrl[$i]."开始抓号...");
    				$res = $this->getCodeByshishicaiUrl($directUrl[$i]);
    				if($res['status']=='success'){
    					$result = $res;
    				}
    				break;
    			case '168kai':
    			    writeLog("notice:".$directUrl[$i]."开始抓号...");
    				$res = $this->getCodeBy168kaiUrl($directUrl[$i]);
    				if($res['status']=='success'){
    					$result = $res;
    				}
    				break;
    			default:
    				# code...
    				break;
    		}
    		if($res['status']=='success'){//抓号成功跳出循环
    			break;
    		}
    	}
    	if(!$result){
    		writeLog("NOTICE:本次".$this->lottery." ".$this->issue."抓号失败！");
    		return array("status"=>'fail','code'=>'',"issue"=>$this->issue);
    	}elseif($result['status'] == 'success'){
    		return $result;
    	}

    }
    /**
     * [getCodeByshishicaiUrl 时时彩开奖号码流程]
     * @author 1023
     * @date          2016-10-21
     * @param  [type] $url       [string]
     * @return [type]            [array]
     */
    private function getCodeByshishicaiUrl($url){
    	$content = $this->snoopy->fetch($url);
    	if(!$content){
    		$res = array('status'=>'fail','code'=>'','issue'=>$this->issue);
    		writeLog("notice:".$url."抓号失败！");
    	}else{
    		$res = array('status'=>'success','code'=>'','issue'=>$this->issue);
    		writeLog("notice:".$url."抓号成功！");
    	}
    	return $res;
    			
    }

    /**
     * [getCodeBy168kaiUrl 168kai网址开奖源]
     * @author 1023
     * @date          2016-10-21
     * @param  [type] $url       [string]
     * @return [type]            [array]
     */
    private function getCodeBy168kaiUrl($url){
    	$content = $this->snoopy->fetch($url);
    	if($content){
    		$result = json_decode($content->results)->list;
    		foreach($result as $val){
    			if($this->issue == $val->c_t){
    				$res = array('status'=>'success','code'=>$val->c_r,'issue'=>$this->issue);
    			}
    		}
    		writeLog("notice:".$url."抓号成功！");
    		writeLog("notice:奖期".$this->issue.$this->lottery."开奖号码为".$res['code']);
    	}else{
    		$res = array('status'=>'fail','code'=>'','issue'=>$this->issue);
    		writeLog("notice:".$url."抓号失败！");
    	}
    	return $res;
    			
    	// if(!$content){
    	// 	return array('status'=>'fail','code'=>'','issue'=>$this->issue);
    	// }
    }
}