<?php

namespace App\EventSubscriber;

use Doctrine\DBAL\Exception\DriverException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,

        #[Autowire(param: 'kernel.environment')]
        private readonly string $environment,
    ) {}

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

        // 1. Détection spécifique des exceptions levées par un THROW SQL / DBAL
        if ($exception instanceof DriverException) {
            // 1. Récupération de l'exception PDO sous-jacente
            $pdoException = $exception->getPrevious();

            // Le code SQL Server natif (ex: 50001) se trouve dans errorInfo[1]
            $driverCode = $pdoException?->errorInfo[1] ?? $exception->getCode();

            // 2. Extraction du message brut
            // errorInfo[2] contient le message sans le préfixe Doctrine "An exception occurred..."
            $rawMessage = $pdoException?->errorInfo[2] ?? $exception->getMessage();

            // 3. Nettoyage complet :
            // - Supprime "An exception occurred..."
            // - Supprime "SQLSTATE [42000, 50001]:"
            // - Supprime tous les préfixes [Microsoft][ODBC Driver...][SQL Server]
            $cleanMessage = preg_replace([
                '/^An exception occurred while executing a query:\s*/i',
                '/^SQLSTATE\s*\[[^\]]+\]:\s*/i',
                '/^(?:\[[^\]]+\]\s*)+/i',
            ], '', $rawMessage);

            $cleanMessage = trim($cleanMessage);

            // 4. Distinction : Seules les erreurs THROW utilisateur (code >= 50000) passent en 400
            $isCustomUserError = ((int) $driverCode >= 50000);
            $status = $isCustomUserError ? 400 : 500;

            $payload = [
                'success' => false,
                'message' => $isCustomUserError
                    ? $cleanMessage
                    : 'Une erreur de base de données est survenue.',
                'status'  => $status,
            ];

            if ($this->environment !== 'prod') {
                $payload['debug_message'] = $cleanMessage;
                $payload['sql_code']      = $driverCode;
            }

            if ($status >= 500) {
                $this->logger->error('Erreur SQL Système', [
                    'route' => $request->attributes->get('_route'),
                    'code'  => $driverCode,
                    'raw'   => $exception->getMessage(),
                ]);
            } else {
                $this->logger->warning('Erreur SQL Métier (THROW)', [
                    'route'   => $request->attributes->get('_route'),
                    'message' => $cleanMessage,
                ]);
            }

            $event->setResponse(new JsonResponse($payload, $status));
            return;
        }

        // 2. Traitement standard des autres exceptions
        $status = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        if ($status >= 500) {
            $this->logger->error(
                'Erreur API',
                [
                    'exception' => $exception,
                    'route'     => $request->attributes->get('_route'),
                    'status'    => $status,
                ],
            );
        }

        $payload = [
            'success' => false,
            'message' => $status >= 500
                ? 'Une erreur interne est survenue.'
                : $exception->getMessage(),
            'status'  => $status,
        ];

        // Informations supplémentaires hors production
        if ($status >= 500 && $this->environment !== 'prod') {
            $payload['debug_message'] = $exception->getMessage();
            $payload['trace']         = $exception->getTrace();
        }

        $headers = $exception instanceof HttpExceptionInterface
            ? $exception->getHeaders()
            : [];

        $event->setResponse(
            new JsonResponse($payload, $status, $headers)
        );
    }
}
