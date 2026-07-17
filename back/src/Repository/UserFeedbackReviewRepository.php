<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserFeedbackReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFeedbackReview>
 */
class UserFeedbackReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFeedbackReview::class);
    }
}
