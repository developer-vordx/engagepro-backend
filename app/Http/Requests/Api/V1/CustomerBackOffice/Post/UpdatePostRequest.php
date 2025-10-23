<?php

namespace App\Http\Requests\Api\V1\CustomerBackOffice\Post;

use App\Utils\BaseRequest;

class UpdatePostRequest extends BaseRequest
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
        // Convert tags from string to array if needed
        if ($this->has('tags') && is_string($this->tags)) {
            $this->merge([
                'tags' => array_filter(array_map('trim', explode(',', $this->tags)))
            ]);
        }

        // Convert target_platforms from string to array if needed
        if ($this->has('target_platforms') && is_string($this->target_platforms)) {
            $this->merge([
                'target_platforms' => array_filter(array_map('trim', explode(',', $this->target_platforms)))
            ]);
        }

        // Convert delete_files from string to array if needed
        if ($this->has('delete_files') && is_string($this->delete_files)) {
            $this->merge([
                'delete_files' => array_filter(array_map('intval', explode(',', $this->delete_files)))
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
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'target_platforms' => 'nullable|array',
            'target_platforms.*' => 'string|in:tiktok,x,instagram,facebook,youtube,linkedin,threads,telegram,pinterest,mastodon,bluesky,snapchat,vimeo,twitch,dailymotion,odysee,reddit,tumblr,truthsocial,minds',
            'scheduled_at' => 'nullable|date|after:now',
            'metadata' => 'nullable|array',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi,webm|max:512000', // 500MB max
            'delete_files' => 'nullable|array',
            'delete_files.*' => 'integer|exists:post_files,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Post title is required',
            'title.max' => 'Post title cannot exceed 255 characters',
            'description.max' => 'Post description cannot exceed 2000 characters',
            'tags.*.max' => 'Each tag cannot exceed 50 characters',
            'target_platforms.*.in' => 'Invalid platform selected',
            'scheduled_at.after' => 'Scheduled time must be in the future',
            'files.max' => 'Maximum 10 files allowed per post',
            'files.*.max' => 'Each file cannot exceed 500MB',
            'files.*.mimes' => 'Only image and video files are supported',
            'delete_files.*.exists' => 'Invalid file ID provided for deletion'
        ];
    }
}
