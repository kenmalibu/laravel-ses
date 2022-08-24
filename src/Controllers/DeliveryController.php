<?php

namespace Juhasev\LaravelSes\Controllers;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Juhasev\LaravelSes\Contracts\SentEmailContract;
use Juhasev\LaravelSes\Factories\EventFactory;
use Juhasev\LaravelSes\ModelResolver;
use Psr\Http\Message\ServerRequestInterface;

class DeliveryController extends BaseController
{
    /**
     * Delivery request from SNS
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exception
     */
    public function delivery(ServerRequestInterface $request)
    {
        $content = request()?->getContent();

        $this->logResult($content);

        try {
            $result = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            Log::error('Failed to parse AWS SES Delivery request ' . $e->getMessage() . ' ' . $e->getTraceAsString());

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
                'message' => 'Delivery subscription confirmed'
            ]);
        }

        try {
            $message = json_decode($result['Message'], associative: false, flags: JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            Log::error('Failed to decode Message from AWS SES Delivery request ' . $e->getMessage() . ' ' . $e->getTraceAsString());

            return response()->json(['success' => false]);
        }

        $this->persistDelivery($message);

        $this->logMessage("Delivery processed for: " . $message->mail->destination[0]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery notification processed'
        ]);
    }

    /**
     * Persist delivery record to the database
     *
     * @param $message
     * @throws Exception
     */
    protected function persistDelivery($message)
    {
        $messageId = $this->parseMessageId($message);

        try {
            $sentEmail = ModelResolver::get('SentEmail')::whereMessageId($messageId)
                ->whereDeliveryTracking(true)
                ->firstOrFail();

        } catch (ModelNotFoundException) {
            $this->logMessage('Message ID (' . $messageId . ') not found in the SentEmail, this email is likely sent without Laravel SES. Skipping delivery processing...');
            return;
        }

        try {
            $deliveryTime = Carbon::parse($message->delivery->timestamp);

            $sentEmail->setDeliveredAt($deliveryTime);

            $this->sendEvent($sentEmail->refresh());

        } catch (Exception $e) {
            Log::error("Failed updating delivered timestamp, got error: " . $e->getMessage() . ' ' . $e->getTraceAsString());
        }
    }

    /**
     * Sent event to listeners
     *
     * @param SentEmailContract $sentEmail
     */
    protected function sendEvent(SentEmailContract $sentEmail)
    {
        event(EventFactory::create('Delivery', 'SentEmail', $sentEmail->getId()));
    }
}
