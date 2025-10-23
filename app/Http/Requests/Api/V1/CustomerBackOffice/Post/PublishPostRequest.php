<?php

namespace App\Http\Requests\Api\V1\CustomerBackOffice\Post;

use App\Utils\BaseRequest;

class PublishPostRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert platforms from string to array if needed
        if ($this->has('platforms') && is_string($this->platforms)) {
            $this->merge([
                'platforms' => array_filter(array_map('trim', explode(',', $this->platforms)))
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'platforms' => 'nullable|array',
            'platforms.*' => 'string|in:tiktok,x,instagram,facebook,youtube,linkedin,threads,telegram,pinterest,mastodon,bluesky,snapchat,vimeo,twitch,dailymotion,odysee,reddit,tumblr,truthsocial,minds',
            'publish_immediately' => 'boolean',
            'custom_captions' => 'nullable|array',
            'custom_captions.*' => 'string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'platforms.*.in' => 'Invalid platform specified',
            'custom_captions.*.max' => 'Custom caption cannot exceed 2000 characters',
        ];
    }
}