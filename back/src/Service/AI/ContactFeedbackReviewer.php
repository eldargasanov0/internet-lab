<?php

declare(strict_types=1);

namespace App\Service\AI;

use App\DTO\Ai\ContactFeedbackReviewResult;
use App\Enum\UserFeedbackToneEnum;
use App\Exception\AI\AIProviderUnavailableException;
use App\Exception\AI\UnparseableAIReviewResponseException;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Exception\IOException;
use Symfony\AI\Platform\Exception\RateLimitExceededException;
use Symfony\AI\Platform\Exception\ServerException;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\Exception\TransportException;

readonly class ContactFeedbackReviewer
{
    public function __construct(
        #[Autowire(service: 'ai.agent.contact_feedback_review')]
        private AgentInterface $contactFeedbackReview,
        private string $userPromptTemplate,
    ) {
    }

    /**
     * @throws UnparseableAIReviewResponseException when the AI response cannot be parsed into a review
     * @throws AIProviderUnavailableException when the provider is temporarily unreachable or errors
     */
    public function review(string $feedbackComment): ContactFeedbackReviewResult
    {
        $userMessage = str_replace('{comment}', $feedbackComment, $this->userPromptTemplate);

        try {
            $result = $this->contactFeedbackReview->call(
                new MessageBag(Message::ofUser($userMessage)),
            );
        } catch (ServerException|RateLimitExceededException|IOException|TransportException $e) {
            throw new AIProviderUnavailableException(
                'AI-провайдер временно недоступен: '.$e->getMessage(),
                previous: $e,
            );
        }

        $payload = $this->extractPayload($result->getContent());

        return new ContactFeedbackReviewResult(
            $this->mapTone($payload['tone'] ?? null),
            $this->mapComment($payload['comment'] ?? null),
        );
    }

    /**
     * @return array{tone?: mixed, comment?: mixed}
     */
    private function extractPayload(mixed $content): array
    {
        if (\is_array($content)) {
            return $content;
        }

        if (!\is_string($content) || trim($content) === '') {
            throw new UnparseableAIReviewResponseException('Ответ AI-ревью пуст или не является строкой.');
        }

        $json = $this->normalizeJsonPayload($content);
        try {
            $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new UnparseableAIReviewResponseException(
                'Ответ AI-ревью не является корректным JSON: '.$e->getMessage(),
                previous: $e,
            );
        }

        if (!\is_array($decoded)) {
            throw new UnparseableAIReviewResponseException('JSON ответа AI-ревью должен декодироваться в объект.');
        }

        return $decoded;
    }

    private function normalizeJsonPayload(string $content): string
    {
        $trimmed = trim($content);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $trimmed, $matches) === 1) {
            return trim($matches[1]);
        }

        return $trimmed;
    }

    private function mapTone(mixed $tone): UserFeedbackToneEnum
    {
        if (\is_string($tone)) {
            $normalized = strtolower(trim($tone));
            $mapped = UserFeedbackToneEnum::tryFrom($normalized);
            if ($mapped !== null) {
                return $mapped;
            }
        }

        throw new UnparseableAIReviewResponseException(
            'В ответе AI-ревью отсутствует или неизвестно поле "tone".',
        );
    }

    private function mapComment(mixed $comment): string
    {
        if (!\is_string($comment)) {
            throw new UnparseableAIReviewResponseException('В ответе AI-ревью отсутствует строковое поле "comment".');
        }

        $trimmed = trim($comment);
        if ($trimmed === '') {
            throw new UnparseableAIReviewResponseException('Поле "comment" в ответе AI-ревью пусто.');
        }

        return $trimmed;
    }
}
