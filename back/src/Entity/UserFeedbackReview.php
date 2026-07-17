<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserFeedbackToneEnum;
use App\Repository\UserFeedbackReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserFeedbackReviewRepository::class)]
class UserFeedbackReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'review')]
    #[ORM\JoinColumn(unique: true, nullable: false)]
    private ?UserFeedback $feedback = null;

    #[ORM\Column(enumType: UserFeedbackToneEnum::class)]
    private ?UserFeedbackToneEnum $tone = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFeedback(): ?UserFeedback
    {
        return $this->feedback;
    }

    public function setFeedback(UserFeedback $feedback): static
    {
        $this->feedback = $feedback;

        return $this;
    }

    public function getTone(): ?UserFeedbackToneEnum
    {
        return $this->tone;
    }

    public function setTone(UserFeedbackToneEnum $tone): static
    {
        $this->tone = $tone;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}
