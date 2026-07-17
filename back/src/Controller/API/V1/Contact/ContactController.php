<?php

declare(strict_types=1);

namespace App\Controller\API\V1\Contact;

use App\Controller\API\V1\Contact\Feedback\ContactFeedbackHandler;
use App\Controller\API\V1\Contact\Feedback\ContactFeedbackRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/contact', name: 'contact_')]
class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactFeedbackHandler $contactFeedbackHandler,
    ) {
    }

    #[Route(path: '', name: 'feedback', methods: Request::METHOD_POST)]
    public function feedback(#[MapRequestPayload] ContactFeedbackRequest $request): JsonResponse
    {
        $response = ($this->contactFeedbackHandler)($request);

        return $this->json($response, Response::HTTP_CREATED);
    }
}
