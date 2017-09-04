<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 2017-09-04
 * Time: 9:50
 */

namespace GsJohn\Dysms;

use Carbon\Carbon;
use GsJohn\Dysms\Core\DefaultAcsClient;
use GsJohn\Dysms\Core\Profile\DefaultProfile;
use GsJohn\Dysms\Request\V20170525\QuerySendDetailsRequest;
use GsJohn\Dysms\Request\V20170525\SendSmsRequest;
use Illuminate\Support\Facades\Cache;
use GsJohn\Dysms\Core\Config;

class DysmsHelper
{
	private $signName;
	const CACHE_PREFIX = 'sms_verify_';
	private $acsClient;

	public function __construct()
	{
	}

	public function init(){
		Config::load();

		$this->signName = config('dysms.ALIDAYU_SIGN');
		// 短信API产品名
		$product = "Dysmsapi";
		// 短信API产品域名
		$domain = "dysmsapi.aliyuncs.com";
		// 暂时不支持多Region
		$region = "cn-hangzhou";
		// 服务结点
		$endPointName = "cn-hangzhou";
		// 初始化用户Profile实例
		$profile = DefaultProfile::getProfile($region, config('dysms.ALIDAYU_APP_KEY'), config('dysms.ALIDAYU_APP_SECRET'));
		// 增加服务结点
		DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);
		// 初始化AcsClient用于发起请求
		$this->acsClient = new DefaultAcsClient($profile);
	}

	/**
	 * 验证码
	 * @param $phone
	 * @return stdClass
	 */
	public function sendVerifyCode($phone){
		$code = $this->verifyCode(6);
		$vct = config('dysms.Verify_Code_Template');
		$resp = $this->sendSms($vct, $phone, ['code'=> $code], null);
		$cacheKey = static::CACHE_PREFIX . $phone;
		$expiresAt = Carbon::now()->addMinutes(15);
		Cache::put($cacheKey, $code, $expiresAt);
		return $resp;
	}?

	/**
	 * 生成验证码
	 * @param int $len
	 * @return string
	 */
	public function verifyCode($len = 6){
		return sprintf('%0'.$len.'d', mt_rand(0, 999999));
	}

	/**
	 * 验证
	 * @param $phone
	 * @param $code
	 * @return bool
	 */
	public function checkVerifyCode($phone, $code){
		$cache = Cache::get(static::CACHE_PREFIX . $phone, '');
		return $code == $cache;
	}

	/**
	 * 发送短信范例
	 *
	 * @param string $signName <p>
	 * 必填, 短信签名，应严格"签名名称"填写，参考：<a href="https://dysms.console.aliyun.com/dysms.htm#/sign">短信签名页</a>
	 * </p>
	 * @param string $templateCode <p>
	 * 必填, 短信模板Code，应严格按"模板CODE"填写, 参考：<a href="https://dysms.console.aliyun.com/dysms.htm#/template">短信模板页</a>
	 * (e.g. SMS_0001)
	 * </p>
	 * @param string $phoneNumbers 必填, 短信接收号码 (e.g. 12345678901)
	 * @param array|null $templateParam <p>
	 * 选填, 假如模板中存在变量需要替换则为必填项 (e.g. Array("code"=>"12345", "product"=>"阿里通信"))
	 * </p>
	 * @param string|null $outId [optional] 选填, 发送短信流水号 (e.g. 1234)
	 * @return stdClass
	 */
	public function sendSms($templateCode, $phoneNumbers, $templateParam = null, $outId = null) {
		if(null == $this->acsClient){
			$this->init();
		}
		// 初始化SendSmsRequest实例用于设置发送短信的参数
		$request = new SendSmsRequest();

		// 必填，设置雉短信接收号码
		$request->setPhoneNumbers($phoneNumbers);

		// 必填，设置签名名称
		$request->setSignName($this->signName);

		// 必填，设置模板CODE
		$request->setTemplateCode($templateCode);

		// 可选，设置模板参数
		if($templateParam) {
			$request->setTemplateParam(json_encode($templateParam));
		}

		// 可选，设置流水号
		if($outId) {
			$request->setOutId($outId);
		}

		// 发起访问请求
		$acsResponse = $this->acsClient->getAcsResponse($request);

		// 打印请求结果
		// var_dump($acsResponse);

		return $acsResponse;

	}

	/**
	 * 查询短信发送情况范例
	 *
	 * @param string $phoneNumbers 必填, 短信接收号码 (e.g. 12345678901)
	 * @param string $sendDate 必填，短信发送日期，格式Ymd，支持近30天记录查询 (e.g. 20170710)
	 * @param int $pageSize 必填，分页大小
	 * @param int $currentPage 必填，当前页码
	 * @param string $bizId 选填，短信发送流水号 (e.g. abc123)
	 * @return stdClass
	 */
	public function queryDetails($phoneNumbers, $sendDate, $pageSize = 10, $currentPage = 1, $bizId=null) {

		// 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
		$request = new QuerySendDetailsRequest();

		// 必填，短信接收号码
		$request->setPhoneNumber($phoneNumbers);

		// 选填，短信发送流水号
		$request->setBizId($bizId);

		// 必填，短信发送日期，支持近30天记录查询，格式Ymd
		$request->setSendDate($sendDate);

		// 必填，分页大小
		$request->setPageSize($pageSize);

		// 必填，当前页码
		$request->setCurrentPage($currentPage);

		// 发起访问请求
		$acsResponse = $this->acsClient->getAcsResponse($request);

		// 打印请求结果
		// var_dump($acsResponse);

		return $acsResponse;
	}
}