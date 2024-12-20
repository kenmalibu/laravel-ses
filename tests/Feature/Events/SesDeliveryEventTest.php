<?php

namespace Juhasev\LaravelSes\Tests\Feature\Events;

use Juhasev\LaravelSes\Factories\Events\SesDeliveryEvent;
use Juhasev\LaravelSes\ModelResolver;
use Juhasev\LaravelSes\Tests\FeatureTestCase;

class SesDeliveryEventTest extends FeatureTestCase
{
    /** @test */
    public function it_ensures_the_right_data_structure_for_delivery_event(): void
    {
        $batch = ModelResolver::get('Batch')::create([
            'name' => 'Test Batch',
        ]);

        $sentEmail = ModelResolver::get('SentEmail')::create([
            'batch_id' => $batch->id,
            'message_id' => 'abc123',
            'email' => 'example@sampleninja.io',
            'sent_at' => now(),
            'delivery_tracking' => true,
            'delivered_at' => now(),
        ]);

        $event = new SesDeliveryEvent($sentEmail);

        $this->assertArrayHasKey('id', $event->data);
        $this->assertArrayHasKey('message_id', $event->data);
        $this->assertArrayHasKey('email', $event->data);
        $this->assertArrayHasKey('batch_id', $event->data);
        $this->assertArrayHasKey('sent_at', $event->data);
        $this->assertArrayHasKey('delivered_at', $event->data);
        $this->assertArrayHasKey('batch', $event->data);

        $this->assertArrayHasKey('id', $event->data['batch']);
        $this->assertArrayHasKey('created_at', $event->data['batch']);
        $this->assertArrayHasKey('updated_at', $event->data['batch']);
        $this->assertArrayHasKey('name', $event->data['batch']);
    }
}