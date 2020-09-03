<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\VerificationCodeRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {

        $captchaData = \Cache::get ($request->captcha_key);

        if (!$captchaData) {
            abort (403,'图片验证码失效');
        }

        if (!hash_equals ($captchaData['code'],$request->captcha_code)) {
            var_dump($captchaData['code']);
            var_dump($request->captcha_code);
            //验证错误就清除缓存
            \Cache::forget ($request->captcha_key);
            throw new AuthenticationException('验证码错误');
        }
        $phone = $captchaData['phone'];

        if (!app ()->environment ('production')) {
            $code = '1234';
        }else {
            //生成4位随机数，左侧补0
            $code = str_pad (random_int (1,999),4,0,STR_PAD_LEFT);

            try {
                $result = $easySms->send ($phone , [
                    'template'  =>  config ('easysms.gateways.aliyun.templates.register'),
                    'data'  =>  [
                        'code'  =>  $code
                    ],
                ]);
            } catch (NoGatewayAvailableException $exception) {
                $message = $exception->getException ('aliyun')->getMessage();
                abort (500,$message ?: '短信发送异常');
            }
        }


        $key = 'verificationCode_'.Str::random (15);
        $expireAt = now ()->addMinute (5);
        //缓存验证码 5分钟过期

        \Cache::put ($key,['phone'  =>  $phone,'code'   =>  $code],$expireAt);

        return response ()->json ([
            'key'   =>  $key,
            'expired_at'    =>  $expireAt->toDateTimeString (),
        ])->setStatusCode (201);
    }
}
