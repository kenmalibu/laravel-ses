<?php

use Juhasev\LaravelSes\Models\Batch;
use Juhasev\LaravelSes\Models\EmailBounce;
use Juhasev\LaravelSes\Models\EmailComplaint;
use Juhasev\LaravelSes\Models\EmailLink;
use Juhasev\LaravelSes\Models\EmailOpen;
use Juhasev\LaravelSes\Models\SentEmail;

return [

    /**
     * Whether to use AWS SNS validator. This is probably a good idead
     *
     * https://github.com/aws/aws-php-sns-message-validator
     */

    'aws_sns_validator' => env('SES_SNS_VALIDATOR', true),

    /**
     * Enable debug mode. In this mode you can test incoming AWS routes
     * manually. No data is saved to the database in this mode.
     *
     * NOTE: You cannot run package unit tests with this enabled!
     */

    'debug' => env('SES_DEBUG', true),

    /**
     * Log prefix for all logged messages. Set to whatever you want for convenient debugging
     */

    'log_prefix' => 'LARAVEL-SES',

    /**
     * Model that the Laravel SES uses. You can override or implement your own custom
     * models by changing the settings here
     */
    
    'models' => [
        'Batch' => Batch::class,
        'SentEmail' => SentEmail::class,
        'EmailBounce' => EmailBounce::class,
        'EmailComplaint' => EmailComplaint::class,
        'EmailLink' => EmailLink::class,
        'EmailOpen' => EmailOpen::class
    ],

    /**
     * Smtp Ping Threshold.
     *
     * Sets the minimum number of seconds required between two messages, before the server is pinged.
     * If the transport wants to send a message and the time since the last message exceeds the specified threshold,
     * the transport will ping the server first (NOOP command) to check if the connection is still alive.
     *
     * Amazon SES SMTP server automatically closes connections after 10 seconds of inactivity.
     *
     * For this reason we set the ping threshold to 10 seconds.
     */

    'ping_threshold' => env('SES_PING_THRESHOLD', 10),

    /**
     * Smtp Restart Threshold.
     *
     * Sets the maximum number of messages to send before re-starting the transport.
     *
     * By default, the threshold is set to 100 (and no sleep at restart).
     */
    'restart_threshold' => [

        // The maximum number of messages (0 to disable)
        'threshold' => env('SES_RESTART_THRESHOLD', 100),

        // The number of seconds to sleep between stopping and re-starting the transport
        'sleep' => env('SES_RESTART_SLEEP', 0),
    ],
];
