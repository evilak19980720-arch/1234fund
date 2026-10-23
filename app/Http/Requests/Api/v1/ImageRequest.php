<?php

namespace App\Http\Requests\Api\v1;


class ImageRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'type' => 'required|string|in:avatar,task,photo',
            'image' => 'required|file',
        ];

        if (in_array($this->type,['avatar','task'])) {
            $rules['image.*'] = 'required|mimes:jpg,jpeg,png,gif';
        }

        return $rules;
    }
}
