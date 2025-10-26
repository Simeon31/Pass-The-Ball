<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Contracts\ClientContract;

class OpenAIService
{
    protected ClientContract $client;

    public function __construct(ClientContract $client)
    {
        $this->client = $client;
    }
    /**
     * Available tone options for content enhancement
     */
    public const TONES = [
        'professional' => 'Professional and formal',
        'casual' => 'Casual and friendly',
        'enthusiastic' => 'Enthusiastic and energetic',
        'inspiring' => 'Inspiring and motivational',
        'humorous' => 'Humorous and lighthearted',
    ];

    /**
     * Enhance post content with AI using the specified tone
     *
     * @param string $content Original post content
     * @param string $tone Desired tone for enhancement
     * @return array ['success' => bool, 'enhanced_content' => string|null, 'error' => string|null]
     */
    public function enhancePostContent(string $content, string $tone = 'professional'): array
    {
        try {
            // Validate tone
            if (!array_key_exists($tone, self::TONES)) {
                return [
                    'success' => false,
                    'enhanced_content' => null,
                    'error' => 'Invalid tone selected.',
                ];
            }

            $toneDescription = self::TONES[$tone];

            // Create the prompt
            $prompt = "Enhance the following social media post content with a {$toneDescription} tone. "
                . "Keep it engaging, clear, and concise. "
                . "Maximum 200 characters. "
                . "Do not add hashtags or emojis unless they were in the original. "
                . "Original content: \"{$content}\"";

            // Call OpenAI API
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that enhances social media post content. '
                            . 'You improve clarity, engagement, and tone while keeping the core message intact. '
                            . 'Always respond with ONLY the enhanced content, no explanations or additional text.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 100, // Roughly 200 characters
                'temperature' => 0.7, // Balance between creativity and consistency
            ]);

            $enhancedContent = trim($response->choices[0]->message->content);

            // Enforce character limit
            if (strlen($enhancedContent) > 200) {
                $enhancedContent = substr($enhancedContent, 0, 197) . '...';
            }

            return [
                'success' => true,
                'enhanced_content' => $enhancedContent,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage(), [
                'content' => $content,
                'tone' => $tone,
            ]);

            return [
                'success' => false,
                'enhanced_content' => null,
                'error' => 'Failed to generate suggestion. Please try again.',
            ];
        }
    }

    /**
     * Get available tones for content enhancement
     *
     * @return array
     */
    public static function getAvailableTones(): array
    {
        return self::TONES;
    }
}
