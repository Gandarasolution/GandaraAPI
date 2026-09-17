<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // On n'intercepte que les requêtes de l'API
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        if ($status >= 500) {
            $this->logger->error('Erreur API 500: ' . $exception->getMessage(), ['exception' => $exception]);
        }

        $payload = [
            'success' => false,
            'message' => $status >= 500 ? 'Une erreur interne est survenue.' : $exception->getMessage(),
            'status'  => $status,
        ];

        // Mode debug
        if ($status >= 500 && ($_ENV['APP_ENV'] ?? 'prod') !== 'prod') {
            $payload['debug_message'] = $exception->getMessage();
            $payload['trace'] = $exception->getTrace();
        }

        $response = new JsonResponse($payload, $status);
        $event->setResponse($response);
    }
}
