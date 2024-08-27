<?php

namespace App\EventListener;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ApiExceptionListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        $this->logger->error('Uncaught PHP Exception {class}: "{message}" at {file} line {line}', [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        // Only handle exceptions for /api routes
        if (strpos($request->getPathInfo(), '/api') !== 0) {
            return;
        }

        $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $errorMessage = 'An unexpected error occurred';

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $errorMessage = $exception->getMessage();
        } elseif ($exception instanceof UniqueConstraintViolationException) {
            $statusCode = JsonResponse::HTTP_CONFLICT;
            $errorMessage = 'A resource with this identifier already exists';
        } elseif ($exception instanceof ValidationFailedException) {
            $statusCode = JsonResponse::HTTP_BAD_REQUEST;
            $errorMessage = 'Validation failed';
            $errors = [];
            foreach ($exception->getViolations() as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
        }

        $responseData = [
            'status' => 'error',
            'message' => $errorMessage,
        ];

        if (isset($errors)) {
            $responseData['errors'] = $errors;
        }

        $response = new JsonResponse($responseData, $statusCode);
        $event->setResponse($response);
    }
}
