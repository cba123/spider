<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{
	public function __construct(){
		$snoopy = new \Think\Snoopy();
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
		// $ltrModel = M('ltr_type_d');
		$ltrModel = M('LTR_TYPE_D');
		
		/*
		奖期和开奖号码，开始时间，结束时间，开奖结果，状态：1-正常；2-提前；3-未开；4-结果不一致，结果生成时间
		LTR_CD 			彩种代码
		PLATFORM_ISSUE 	平台期号
		BEGIN_TM 		开售时间
		END_TM 			截止时间
		LTR_RS 			状态：1-正常；2-提前；3-未开；4-结果不一致
		CREATE_TM 		结果生成时间
		 */
		// $issueModel = M('issue_info_b');
		$issueModel = M('ISSUE_INFO_B');
		
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
		*/
		// $issueModel = M('src_info_b');
		$issueModel = M('SRC_INFO_B');

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
		$errorTypeModel = M("ERROR_INFO_D");

		/*
		ID
		LTR_CD			彩种代码
		SRC_CD 			号源代码
		PLATFORM_ISSUE	平台期号
		ERROR_CD 		错误类型代码
		CMMT 			描述
		TM 				时间
		 */

		$errorLogModel = M("ERROR_INFO_R");
	}
    public function index()
    {

    }
}