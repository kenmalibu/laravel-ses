<?php

namespace Juhasev\LaravelSes\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Juhasev\LaravelSes\Factories\Events\SesDeliveryEvent;
use Juhasev\LaravelSes\ModelResolver;
use Juhasev\LaravelSes\Tests\FeatureTestCase;

class DeliveryTrackingTest extends FeatureTestCase
{
    public function testDeliveryTracking()
    {
        $model = ModelResolver::get('SentEmail')::create([
            'message_id' => '010101777df559d4-5080db0f-5e72-43aa-af23-3cdeca00807c-000000',
            'email' => 'eriksen23@gmail.com',
            'delivery_tracking' => true
        ]);

        Event::fake();

        $this->json('POST', '/ses/notification/delivery',
            $this->generateDeliveryPayload($model->message_id, $model->email)
        );

        Event::assertDispatched(SesDeliveryEvent::class);

        $this->assertNotNull(ModelResolver::get('SentEmail')::first()->delivered_at);
    }

    public function testConfirmSubscription()
    {
        $this->json('POST', '/ses/notification/delivery',
            json_decode($this->exampleSubscriptionResponse, associative: true)
        )->assertJson(['success' => true]);
    }

    public function testTopicResponse()
    {
        $this->json('POST', '/ses/notification/delivery',
            json_decode($this->exampleTopicResponse, associative: true)
        )->assertJson(['success' => true]);
    }

    public function testDeliveryTimeIsNotSetIfTrackingNotEnabled()
    {
        $model = ModelResolver::get('SentEmail')::create([
            'message_id' => 'a4947f1f3fdb397b3a7bf2d3b7d2f53e@swift.generated',
            'email' => 'eriksen23@gmail.com'
        ]);

        $this->json('POST', '/ses/notification/delivery',
            $this->generateDeliveryPayload($model->message_id, $model->email)
        );

        $this->assertNull(ModelResolver::get('SentEmail')::first()->delivered_at);
    }

    public function testDeliveryTrackingShouldFailsDueToInvalidPayload()
    {
        $this->app['config']->set('laravelses.aws_sns_validator', true);

        Event::fake();

        $model = ModelResolver::get('SentEmail')::create([
            'message_id' => 'a4947f1f3fdb397b3a7bf2d3b7d2f53e@swift.generated',
            'email' => 'eriksen23@gmail.com'
        ]);

        $this
            ->json('POST', '/ses/notification/delivery',
                $this->generateDeliveryPayload($model->message_id, $model->email)
            )
            ->assertSuccessful()
            ->assertJson(['success' => false]);

        Event::assertNotDispatched(SesDeliveryEvent::class);
    }

    private $exampleTopicResponse = '{
          "Type": "Notification",
          "MessageId": "6abf341d-f4e7-5d58-a5f6-6c84bc4e39f2",
          "TopicArn": "arn:aws:sns:us-west-2:635608510762:staging-ses-delivery-us-west-2",
          "Message": "Successfully validated SNS topic for Amazon SES event publishing.",
          "Timestamp": "2021-02-07T01:46:17.368Z",
          "SignatureVersion": "1",
          "Signature": "KoisQ3njC6m+gkr6GlSoX8NA+XLEVUZ2tgBPfQ4VP2uIZSL1YCpnUUfoH1IYflo+PniNbVummhiEWNAYvNYF31vihbwiMqXwXWZ3xS23YxflknPDYNF8hBYZkBG66S1arRvNtw6F+JsxgQd6nZrs4RMADALRaD8vu79C5ZsEnFATUIOrdWOML7XKd3/kXnHKbxZvwpjhCTYu7x0Srb378OMMl9ax5/I0465zs2XSL/LaP5NB3aQp9DSGOJTDUlEh0C8wXZceJr3c9PlYQStbMkqDdzeqBy4Gbrtnx/28CSKgh9Hx1UuAAeZvVmjmYmFco1nobu8+m2H/cpx6mllQNQ==",
          "SigningCertURL": "https://sns.us-west-2.amazonaws.com/SimpleNotificationService-010a507c1833636cd94bdb98bd93083a.pem",
          "UnsubscribeURL": "https://sns.us-west-2.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-west-2:635608510762:staging-ses-delivery-us-west-2:43df3888-7e5e-4e35-83b7-3247d9947525"
    }';

    private $exampleSubscriptionResponse = '{
          "Type" : "SubscriptionConfirmation",
          "MessageId" : "165545c9-2a5c-472c-8df2-7ff2be2b3b1b",
          "Token" : "2336412f37fb687f5d51e6e241d09c805a5a57b30d712f794cc5f6a988666d92768dd60a747ba6f3beb71854e285d6ad02428b09ceece29417f1f02d609c582afbacc99c583a916b9981dd2728f4ae6fdb82efd087cc3b7849e05798d2d2785c03b0879594eeac82c01f235d0e717736",
          "TopicArn" : "arn:aws:sns:us-west-2:123456789012:MyTopic",
          "Message" : "You have chosen to subscribe to the topic arn:aws:sns:us-west-2:123456789012:MyTopic.\nTo confirm the subscription, visit the SubscribeURL included in this message.",
          "SubscribeURL" : "google.com",
          "Timestamp" : "2012-04-26T20:45:04.751Z",
          "SignatureVersion" : "1",
          "Signature" : "EXAMPLEpH+DcEwjAPg8O9mY8dReBSwksfg2S7WKQcikcNKWLQjwu6A4VbeS0QHVCkhRS7fUQvi2egU3N858fiTDN6bkkOxYDVrY0Ad8L10Hs3zH81mtnPk5uvvolIC1CXGu43obcgFxeL3khZl8IKvO61GWB6jI9b5+gLPoBc1Q=",
          "SigningCertURL" : "https://sns.us-west-2.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem"
    }';
}
