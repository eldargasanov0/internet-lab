<?php

declare(strict_types=1);

namespace App\Controller\API\V1\Contact\Feedback;


use Symfony\Component\Validator\Constraints as Assert;

readonly class ContactFeedbackRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Regex(
            pattern: '/^(?:\+7|8)\d{10}$/',
            message: 'Телефона, должно быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX'
        )]
        public string $phone,
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 3)]
        public string $comment,
    ) {
    }
}
