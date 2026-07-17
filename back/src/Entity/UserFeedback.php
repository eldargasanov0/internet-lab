<?php

namespace App\Entity;

use App\Repository\UserFeedbackRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserFeedbackRepository::class)]
class UserFeedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $comment = null;

    #[ORM\OneToOne(mappedBy: 'feedback', cascade: ['persist', 'remove'])]
    private ?UserFeedbackReview $review = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

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

    public function getReview(): ?UserFeedbackReview
    {
        return $this->review;
    }

    public function setReview(UserFeedbackReview $review): static
    {
        // set the owning side of the relation if necessary
        if ($review->getFeedback() !== $this) {
            $review->setFeedback($this);
        }

        $this->review = $review;

        return $this;
    }
}
