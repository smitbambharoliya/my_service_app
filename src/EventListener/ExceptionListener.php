<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsEventListener(event: 'kernel.exception')]
final class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private KernelInterface $kernel,
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Log all exceptions
        $this->logger->error(
            'Exception occurred: {message}',
            [
                'message' => $exception->getMessage(),
                'exception' => $exception,
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
            ]
        );

        // Only handle HTML requests for now
        if ('html' !== $request->getRequestFormat()) {
            return;
        }

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'An error occurred while processing your request.';

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'An HTTP error occurred.';
        }

        // Create a user-friendly response
        $response = new Response(
            $this->renderError($statusCode, $message),
            $statusCode,
            ['content-type' => 'text/html']
        );

        $event->setResponse($response);
    }

    private function renderError(int $statusCode, string $message): string
    {
        $isDev = $this->kernel->isDebug();

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {$statusCode}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #d32f2f; margin: 0 0 10px; }
        p { color: #666; margin: 10px 0; line-height: 1.6; }
        .status { color: #999; font-size: 14px; }
        a { color: #1976d2; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Error {$statusCode}</h1>
        <p>{$message}</p>
        <p class="status">If this problem persists, please <a href="/">contact support</a>.</p>
        <p><a href="/">← Go back home</a></p>
    </div>
</body>
</html>
HTML;
    }
}
