<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final readonly class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Log the exception
        $this->logger->error('Unhandled exception', [
            'exception' => $exception,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ]);

        // Handle HTTP exceptions
        if ($exception instanceof HttpExceptionInterface) {
            $response = new Response(
                'Error: ' . $exception->getMessage(),
                $exception->getStatusCode(),
                $exception->getHeaders(),
            );
            $event->setResponse($response);
        }
    }
}
