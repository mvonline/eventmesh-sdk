<?php

namespace EventMesh\LaravelSdk\Tests\EventMesh;

use App\Services\OrderService;
use App\Services\PaymentService;
use App\Listeners\EventMeshEventHandler;
use EventMesh\LaravelSdk\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class EventMeshEventHandlerTest extends TestCase
{
    private MockInterface $orderService;
    private MockInterface $paymentService;
    private EventMeshEventHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService = Mockery::mock(OrderService::class);
        $this->paymentService = Mockery::mock(PaymentService::class);
        $this->handler = new EventMeshEventHandler(
            $this->orderService,
            $this->paymentService
        );
    }

    public function test_handles_order_created_event()
    {
        $event = [
            'topic' => 'order.created',
            'payload' => [
                'order_id' => 123,
                'amount' => 99.99
            ],
            'headers' => [
                'X-Saga-Instance-Id' => 'saga-123'
            ]
        ];

        $this->orderService->shouldReceive('processNewOrder')
            ->once()
            ->with(123, 99.99, 'saga-123')
            ->andReturn(null);

        $this->handler->handleEvent($event);

        // If we reach here, the test passes
        $this->assertTrue(true);
    }

    public function test_handles_payment_processed_event()
    {
        $event = [
            'topic' => 'payment.processed',
            'payload' => [
                'payment_id' => 'pay-123',
                'status' => 'completed'
            ],
            'headers' => [
                'X-Saga-Instance-Id' => 'saga-123'
            ]
        ];

        $this->paymentService->shouldReceive('handlePaymentProcessed')
            ->once()
            ->with('pay-123', 'completed', 'saga-123')
            ->andReturn(null);

        $this->handler->handleEvent($event);

        // If we reach here, the test passes
        $this->assertTrue(true);
    }

    public function test_handles_unknown_event_topic()
    {
        $event = [
            'topic' => 'unknown.event',
            'payload' => [],
            'headers' => []
        ];

        // Should not throw an exception for unknown topics
        $this->handler->handleEvent($event);

        // If we reach here, the test passes
        $this->assertTrue(true);
    }

    public function test_handles_service_exception()
    {
        $event = [
            'topic' => 'order.created',
            'payload' => [
                'order_id' => 123,
                'amount' => 99.99
            ],
            'headers' => [
                'X-Saga-Instance-Id' => 'saga-123'
            ]
        ];

        $this->orderService->shouldReceive('processNewOrder')
            ->once()
            ->with(123, 99.99, 'saga-123')
            ->andThrow(new \Exception('Service error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service error');

        $this->handler->handleEvent($event);
    }

    public function test_handles_missing_payload_fields()
    {
        $event = [
            'topic' => 'order.created',
            'payload' => [], // Missing required fields
            'headers' => []
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required fields in payload');

        $this->handler->handleEvent($event);
    }

    public function test_handles_malformed_event()
    {
        $event = [
            'topic' => 'order.created',
            // Missing payload and headers
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Malformed event: missing required fields');

        $this->handler->handleEvent($event);
    }

    public function test_handles_order_created_with_additional_fields()
    {
        $event = [
            'topic' => 'order.created',
            'payload' => [
                'order_id' => 123,
                'amount' => 99.99,
                'currency' => 'USD',
                'customer_id' => 456,
                'shipping_address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                    'country' => 'USA'
                ]
            ],
            'headers' => [
                'X-Saga-Instance-Id' => 'saga-123',
                'X-Correlation-Id' => 'corr-123'
            ]
        ];

        $this->orderService->shouldReceive('processNewOrder')
            ->once()
            ->with(123, 99.99, 'saga-123')
            ->andReturn(null);

        $this->handler->handleEvent($event);

        // If we reach here, the test passes
        $this->assertTrue(true);
    }

    public function test_handles_payment_processed_with_failed_status()
    {
        $event = [
            'topic' => 'payment.processed',
            'payload' => [
                'payment_id' => 'pay-123',
                'status' => 'failed',
                'error_code' => 'INSUFFICIENT_FUNDS',
                'error_message' => 'Insufficient funds'
            ],
            'headers' => [
                'X-Saga-Instance-Id' => 'saga-123'
            ]
        ];

        $this->paymentService->shouldReceive('handlePaymentProcessed')
            ->once()
            ->with('pay-123', 'failed', 'saga-123')
            ->andThrow(new \Exception('Payment failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment failed');

        $this->handler->handleEvent($event);
    }

    public function test_handles_multiple_events_in_sequence()
    {
        // First event
        $orderEvent = [
            'topic' => 'order.created',
            'payload' => [
                'order_id' => 123,
                'amount' => 99.99
            ],
            'headers' => [
                'X-Saga-Instance-Id' => 'saga-123'
            ]
        ];

        $this->orderService->shouldReceive('processNewOrder')
            ->once()
            ->with(123, 99.99, 'saga-123')
            ->andReturn(null);

        $this->handler->handleEvent($orderEvent);

        // Second event
        $paymentEvent = [
            'topic' => 'payment.processed',
            'payload' => [
                'payment_id' => 'pay-123',
                'status' => 'completed'
            ],
            'headers' => [
                'X-Saga-Instance-Id' => 'saga-123'
            ]
        ];

        $this->paymentService->shouldReceive('handlePaymentProcessed')
            ->once()
            ->with('pay-123', 'completed', 'saga-123')
            ->andReturn(null);

        $this->handler->handleEvent($paymentEvent);

        // If we reach here, the test passes
        $this->assertTrue(true);
    }
} 