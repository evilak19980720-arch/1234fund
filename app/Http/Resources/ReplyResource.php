<?php

namespace App\Http\Resources;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ReplyResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $user = User::where('id', '=', $data['user_id'])->select('id', 'name', 'avatar')->first();
        $data['user'] = $user ?: new class {
        };
        unset($data['user_id']);

        $admin = AdminUser::where('id', '=', $data['admin_user_id'])->select('id', 'name', 'avatar')->first();
        $data['admin'] = $admin ?: new class {
        };
        unset($data['admin_user_id']);

        return $data;
    }
}
