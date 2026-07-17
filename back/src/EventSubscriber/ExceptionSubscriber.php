<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\ErrorResponseFactory;
use App\Exception\ExceptionMapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ExceptionMapper $mapper,
        private ErrorResponseFactory $responseFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $problem = $this->mapper->map(
            $event->getThrowable(),
            $request->getPathInfo(),
        );

        $response = $this->responseFactory->create($problem);
        
        $event->setResponse($response);
    }
}
