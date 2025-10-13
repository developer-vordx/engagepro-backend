<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class TelegramService
{
    private string $baseUrl;
    private string $botToken;
    private string $platform;
    private mixed $supportedMediaTypes;

    public function __construct()
    {
        $platForm = SocialAccount::where('slug', 'telegram')->first();
        $this->baseUrl = $platForm?->url ?: 'https://api.telegram.org';
        $this->botToken = config('services.telegram.bot_token', '');
        $this->platform = 'telegram';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['image', 'video', 'text', 'document'];
    }

    public function getPlatform(): string
    {
        return 'telegram';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Telegram uses Bot API, not OAuth
     * Return instructions URL
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        return 'https://core.telegram.org/bots#how-do-i-create-a-bot';
    }

    /**
     * Not applicable for Telegram Bot API
     */
    public function exchangeCodeForToken(string $code): array
    {
        return $this->errorResponse(ResponseAlias::HTTP_NOT_IMPLEMENTED, 'Telegram uses Bot API token, not OAuth flow');
    }

    /**
     * Get bot info
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $url = "{$this->baseUrl}/bot{$this->botToken}/getMe";
        $response = Helper::makeHttpRequest('GET', $url, [], []);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $bot = $response['body']['result'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $bot['id'] ?? null,
                'username' => $bot['username'] ?? null,
                'display_name' => $bot['first_name'] ?? null,
                'is_bot' => $bot['is_bot'] ?? true,
                'can_join_groups' => $bot['can_join_groups'] ?? false,
                'can_read_all_group_messages' => $bot['can_read_all_group_messages'] ?? false,
                'supports_inline_queries' => $bot['supports_inline_queries'] ?? false,
                'follower_count' => 0,
                'following_count' => 0,
            ]
        ];
    }

    /**
     * Validate content for Telegram
     */
    public function validateContent(array $mediaFiles, array $metadata = []): array
    {
        $errors = [];

        foreach ($mediaFiles as $filePath) {
            if (!file_exists($filePath)) {
                $errors[] = "File does not exist: {$filePath}";
                continue;
            }

            $fileSize = filesize($filePath);
            $mimeType = mime_content_type($filePath);

            // Telegram file limits
            if (str_starts_with($mimeType, 'image/') && $fileSize > 10 * 1024 * 1024) {
                $errors[] = "Image exceeds Telegram's 10MB limit";
            }

            if (str_starts_with($mimeType, 'video/') && $fileSize > 50 * 1024 * 1024) {
                $errors[] = "Video exceeds Telegram's 50MB limit";
            }

            if ($fileSize > 50 * 1024 * 1024) {
                $errors[] = "File exceeds Telegram's 50MB limit";
            }
        }

        // Text limit: 4096 characters
        $textLength = strlen($metadata['description'] ?? '');
        if ($textLength > 4096) {
            $errors[] = "Text exceeds Telegram's 4096 character limit";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Send message/media to Telegram channel/chat
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            $chatId = $account->identifier; // Channel/Chat/User ID
            if (!$chatId) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Telegram chat_id (channel/group/user ID) is required in account identifier');
            }

            $text = Helper::truncateText($content['description'] ?? $post->description ?? '', 4096);
            $postFiles = $post->postFiles;

            // Send based on content type
            if (!$postFiles || $postFiles->isEmpty()) {
                // Text-only message
                return $this->sendMessage($chatId, $text);
            } else {
                $firstFile = $postFiles->first();
                $filePath = Storage::disk('private')->path($firstFile->file_path);
                
                if (!file_exists($filePath)) {
                    return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Media file not found');
                }

                $mimeType = $firstFile->mime_type;

                if (str_starts_with($mimeType, 'image/')) {
                    return $this->sendPhoto($chatId, $filePath, $text);
                } elseif (str_starts_with($mimeType, 'video/')) {
                    return $this->sendVideo($chatId, $filePath, $text);
                } else {
                    return $this->sendDocument($chatId, $filePath, $text);
                }
            }

        } catch (\Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Send text message
     */
    private function sendMessage(string $chatId, string $text): array
    {
        $url = "{$this->baseUrl}/bot{$this->botToken}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        $response = Helper::makeHttpRequest('POST', $url, $data, [], false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to send message');
        }

        $result = $response['body']['result'] ?? [];

        return [
            'header_code' => ResponseAlias::HTTP_OK,
            'body' => [
                'platform_post_id' => $result['message_id'] ?? 'unknown',
                'platform_url' => "https://t.me/{$chatId}",
                'status' => 'published'
            ]
        ];
    }

    /**
     * Send photo
     */
    private function sendPhoto(string $chatId, string $filePath, string $caption): array
    {
        $url = "{$this->baseUrl}/bot{$this->botToken}/sendPhoto";
        
        $data = [
            'chat_id' => $chatId,
            'photo' => new \CURLFile($filePath, mime_content_type($filePath), basename($filePath)),
            'caption' => Helper::truncateText($caption, 1024), // Caption limit
            'parse_mode' => 'HTML'
        ];

        $response = Helper::makeHttpRequest('POST', $url, $data, [], true, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to send photo');
        }

        $result = $response['body']['result'] ?? [];

        return [
            'header_code' => ResponseAlias::HTTP_OK,
            'body' => [
                'platform_post_id' => $result['message_id'] ?? 'unknown',
                'platform_url' => "https://t.me/{$chatId}/{$result['message_id']}",
                'status' => 'published'
            ]
        ];
    }

    /**
     * Send video
     */
    private function sendVideo(string $chatId, string $filePath, string $caption): array
    {
        $url = "{$this->baseUrl}/bot{$this->botToken}/sendVideo";
        
        $data = [
            'chat_id' => $chatId,
            'video' => new \CURLFile($filePath, mime_content_type($filePath), basename($filePath)),
            'caption' => Helper::truncateText($caption, 1024),
            'parse_mode' => 'HTML'
        ];

        $response = Helper::makeHttpRequest('POST', $url, $data, [], true, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to send video');
        }

        $result = $response['body']['result'] ?? [];

        return [
            'header_code' => ResponseAlias::HTTP_OK,
            'body' => [
                'platform_post_id' => $result['message_id'] ?? 'unknown',
                'platform_url' => "https://t.me/{$chatId}/{$result['message_id']}",
                'status' => 'published'
            ]
        ];
    }

    /**
     * Send document
     */
    private function sendDocument(string $chatId, string $filePath, string $caption): array
    {
        $url = "{$this->baseUrl}/bot{$this->botToken}/sendDocument";
        
        $data = [
            'chat_id' => $chatId,
            'document' => new \CURLFile($filePath, mime_content_type($filePath), basename($filePath)),
            'caption' => Helper::truncateText($caption, 1024),
            'parse_mode' => 'HTML'
        ];

        $response = Helper::makeHttpRequest('POST', $url, $data, [], true, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to send document');
        }

        $result = $response['body']['result'] ?? [];

        return [
            'header_code' => ResponseAlias::HTTP_OK,
            'body' => [
                'platform_post_id' => $result['message_id'] ?? 'unknown',
                'platform_url' => "https://t.me/{$chatId}/{$result['message_id']}",
                'status' => 'published'
            ]
        ];
    }

    /**
     * Get available bot methods
     */
    public function getAvailableScopes(): array
    {
        return [
            'bot' => 'Full bot access (no OAuth, uses bot token)',
            'sendMessage' => 'Send text messages',
            'sendPhoto' => 'Send photos',
            'sendVideo' => 'Send videos',
            'sendDocument' => 'Send documents',
            'sendAudio' => 'Send audio',
            'sendAnimation' => 'Send animations (GIF)',
            'sendVoice' => 'Send voice messages',
            'sendVideoNote' => 'Send video notes',
            'sendMediaGroup' => 'Send multiple media as album',
            'sendLocation' => 'Send location',
            'sendVenue' => 'Send venue',
            'sendContact' => 'Send contact',
            'sendPoll' => 'Send poll',
            'sendDice' => 'Send dice',
            'editMessageText' => 'Edit message text',
            'deleteMessage' => 'Delete messages',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['image', 'video', 'text', 'document'];
    }

    public function getPostingLimits(): array
    {
        return [
            'max_text_length' => 4096,
            'max_caption_length' => 1024,
            'max_image_size_mb' => 10,
            'max_video_size_mb' => 50,
            'max_document_size_mb' => 50,
            'supported_image_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'supported_video_formats' => ['mp4'],
            'rate_limit' => '30 messages per second per bot',
        ];
    }

    /**
     * Get chat info
     */
    public function getChatInfo(string $chatId): array
    {
        $url = "{$this->baseUrl}/bot{$this->botToken}/getChat";
        $data = ['chat_id' => $chatId];
        return Helper::makeHttpRequest('POST', $url, $data, [], false, $this->platform);
    }

    /**
     * Get bot updates
     */
    public function getUpdates(int $offset = 0): array
    {
        $url = "{$this->baseUrl}/bot{$this->botToken}/getUpdates";
        $data = ['offset' => $offset];
        return Helper::makeHttpRequest('POST', $url, $data, [], false, $this->platform);
    }
}
