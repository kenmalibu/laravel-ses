<?php

namespace Juhasev\LaravelSes\Controllers;

use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class BaseController extends Controller
{
    /**
     * Validate SNS requests from AWS
     *
     * @param ServerRequestInterface $request
     * @return Message|null
     */
    protected function validateSns(ServerRequestInterface $request): Message|null
    {
        $message = Message::fromPsrRequest($request);

        $validator = new MessageValidator();

        try {
            $validator->validate($message);

            return $message;
        } catch (InvalidSnsMessageException $e) {
            Log::error('SES email feedback request failed validate: ' . $e->getMessage() . ' ' . $e->getTraceAsString());

            return null;
        }
    }

    /**
     * Parse message ID out of message
     *
     * @param $message
     * @return string
     */
    protected function parseMessageId($message): string
    {
        $messageId = collect($message->mail->headers)
            ->where('name', 'Message-ID')
            ->pluck('value')
            ->first();

        $messageId = str_replace(['<','>'], '', $messageId);

        return $messageId;
    }

    /**
     * Make call back to AWS to confirm subscription
     *
     * @param array $result
     * @return void
     * @throws GuzzleException
     */
    protected function confirmSubscription(array $result): void
    {
        if (! isset($result['SubscribeURL'], $result['TopicArn'])) {
            throw new RuntimeException('Failed to confirm subscription because of missing SubscribeURL param: '. json_encode($result));
        }

        (new Client)->get($result['SubscribeURL']);

        $this->logMessage("Subscribed to (".$result['TopicArn'].") using GET Request " . $result['SubscribeURL']);
    }

    /**
     * If AWS is trying to confirm subscription
     *
     * @param array $result
     * @return bool
     */
    protected function isSubscriptionConfirmation(array $result): bool
    {
        if (
            isset($result['Type'], $result['TopicArn']) &&
            $result['Type'] === 'SubscriptionConfirmation'
        ) {
            $this->logMessage("Received subscription confirmation: ". $result['TopicArn']);

            return true;
        }

        return false;
    }

    /**
     * Is topic confirmation
     *
     * @param array $result
     * @return bool
     */
    protected function isTopicConfirmation(array $result): bool
    {
        if (
            isset($result['Type'], $result['Message'], $result['TopicArn']) &&
            $result['Type'] === 'Notification' &&
            Str::contains($result['Message'], "Successfully validated SNS topic")
        ) {
            $this->logMessage('SNS Topic Validated: ' . $result['TopicArn']);

            return true;
        }
        return false;
    }

    /**
     * Log message
     *
     * @param $message
     */
    protected function logMessage($message): void
    {
        if ($this->debug()) {
            Log::debug(config('laravelses.log_prefix') . ": " . $message);
        }
    }

    /**
     * Debug mode on
     *
     * @param string $content
     */
    protected function logResult(string $content): void
    {
        if ($this->debug()) {
            Log::debug("RAW SES REQUEST BODY:\n" . $content);
        }
    }

    /**
     * Check if debugging is turned on
     *
     * @return bool
     */
    protected function debug(): bool
    {
        return config('laravelses.debug') === true;
    }

    /**
     * Check if request validation is turned on
     *
     * @return bool
     */
    protected function shouldValidateRequest(): bool
    {
        return config('laravelses.aws_sns_validator') === true;
    }
}
