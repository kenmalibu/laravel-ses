<?php

namespace Juhasev\LaravelSes\Controllers;

use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Juhasev\LaravelSes\Contracts\EmailBounceContract;
use Juhasev\LaravelSes\Factories\EventFactory;
use Juhasev\LaravelSes\ModelResolver;
use Psr\Http\Message\ServerRequestInterface;

class BounceController extends BaseController
{
    /**
     * Bounce controller
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exception
     */
    public function bounce(ServerRequestInterface $request)
    {
        $content = request()?->getContent();

        $this->logResult($content);

        try {
            $result = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            Log::error('Failed to parse AWS SES Bounce request ' . $e->getMessage() . ' ' . $e->getTraceAsString());

            return response()->json(['success' => false]);
        }

        if ($this->shouldValidateRequest()) {
            $result = $this->validateSns($request)?->toArray();

            if ($result === null) {
                Log::error(
                    'Failed to read content from AWS SES Complaint request: ' . json_encode(request()?->getContent())
                );

                return response()->json(['success' => false]);
            }
        }

        if ($this->isTopicConfirmation($result)) {
            return response()->json(['success' => true]);
        }

        if ($this->isSubscriptionConfirmation($result)) {

            $this->confirmSubscription($result);

            return response()->json([
                'success' => true,
                'message' => 'Bounce subscription confirmed'
            ]);
        }

        try {
            $message = json_decode($result['Message'], associative: false, flags: JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            Log::error('Failed to decode Message from AWS SES Delivery request ' . $e->getMessage() . ' ' . $e->getTraceAsString());

            return response()->json(['success' => false]);
        }

        $this->persistBounce($message);

        $this->logMessage("Bounce processed for: " . $message->mail->destination[0]);

        return response()->json([
            'success' => true,
            'message' => 'Bounce processed'
        ]);
    }

    /**
     * Persis bounce
     *
     * @param $message
     * @throws Exception
     */
    protected function persistBounce($message)
    {
        $messageId = $this->parseMessageId($message);

        try {
            $sentEmail = ModelResolver::get('SentEmail')::whereMessageId($messageId)
                ->whereBounceTracking(true)
                ->firstOrFail();

        } catch (ModelNotFoundException) {
            $this->logMessage('Message ID (' . $messageId . ') not found in the SentEmail, this email is likely sent without Laravel SES. Skipping delivery processing...');
            return;
        }

        try {
            $bounce = ModelResolver::get('EmailBounce')::create([
                'sent_email_id' => $sentEmail->id,
                'type' => $message->bounce->bounceType,
                'bounced_at' => Carbon::parse($message->mail->timestamp)
            ]);

            $this->sendEvent($bounce);

        } catch (Exception $e) {
            Log::error("Failed inserting EmailBounce, got error: " . $e->getMessage());
        }
    }

    /**
     * Sent event to listeners
     *
     * @param EmailBounceContract $bounce
     */
    protected function sendEvent(EmailBounceContract $bounce)
    {
        event(EventFactory::create('Bounce', 'EmailBounce', $bounce->getId()));
    }
}
