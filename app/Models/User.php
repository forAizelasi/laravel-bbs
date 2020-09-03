<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailController;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Auth;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailController
{
    use Traits\LastActivedAtHelper;
    use Traits\ActiveUserHelper;
    use HasRoles;
    use MustVerifyEmailTrait;

    use Notifiable {
        notify as protected laravelNotify;
    }

    public function notify($instance)
    {
        //如果要通知的人是当前用户，就不必通知
        if ($this->id == Auth::id ()) {
            return;
        }

        //只有数据库类型通知才需要提醒，直接发送Email或者其他的的 Pass

        if (method_exists ($instance,'toDatabase')) {
            $this->increment ('notification_count');
        }

        $this->laravelNotify ($instance);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','introduction','avatar','phone','weixin_openid','weixin_unionid',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function topics()
    {
        return $this->hasMany (Topic::class);
    }

    public function isAuthorOf($model)
    {
        return $this->id == $model->user_id;
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function markAsRead()
    {
        $this->notification_count = 0;
        $this->save ();
        $this->unreadNotifications->markAsRead();
    }

    /**
     * 修改密码处理加密问题
     * @param $value
     */
    public function setPasswordAttribute($value)
    {
        //如果长度等于60 即认为已经做过加密的情况
        if (strlen ($value) != 60) {
            //不等于60做密码加密处理
            $value = bcrypt ($value);
        }
        $this->attributes['password'] = $value;
    }

    public function setAvatarAttribute($path)
    {
        //如果不是'http'子串开头，那就是从后台上传的 需要不全url
        if (! \Str::startsWith($path,'http')) {
            //拼接完整的 URL
            $path = config ('app.url') . "/uploads/images/avatars/$path";
        }

        $this->attributes['avatar'] = $path;
    }
}
