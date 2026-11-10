<?php

declare(strict_types=1);

namespace Chatify\Http\Requests;

use Chatify\Support\ChatifyModels;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $user = $this->user();
        $userClass = ChatifyModels::userClass();
        $userId = $user ? $user->getKey() : null;

        $maxKb = (int) config('chatify.attachments.max_upload_size', 150) * 1024;
        $mimes = config('chatify.attachments.allowed_images', ['png', 'jpg', 'jpeg', 'gif']);

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => [
                'nullable',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
                Rule::unique($userClass, 'username')->ignore($userId),
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique($userClass, 'email')->ignore($userId),
            ],
            'current_password' => ['nullable', 'string'],
            'avatar' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:'.implode(',', $mimes)],
            'reset_avatar' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            // Require current password ONLY when email is changed
            if ($this->filled('email') && strtolower(trim((string) $this->input('email'))) !== strtolower((string) $user->email)) {
                $password = (string) $this->input('current_password', '');

                if ($password === '') {
                    $validator->errors()->add(
                        'current_password',
                        __('Current password is required to change email address.')
                    );
                } elseif (! Hash::check($password, (string) $user->password)) {
                    $validator->errors()->add(
                        'current_password',
                        __('The provided current password is incorrect.')
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Name cannot be empty.'),
            'username.regex' => __('Username may only contain letters, numbers, underscores, dashes, and dots.'),
            'username.unique' => __('This username is already taken.'),
            'email.required' => __('Email address cannot be empty.'),
            'email.email' => __('The email address format is invalid.'),
            'email.unique' => __('This email address is already in use.'),
        ];
    }
}
