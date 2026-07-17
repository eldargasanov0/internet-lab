<?php

declare(strict_types=1);

namespace App\Service\Mailer;

use App\Message\SendContactFeedbackEmails\SendContactFeedbackEmailsMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class ContactFeedbackMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail,
        private string $mailerFrom,
    ) {
    }

    public function send(SendContactFeedbackEmailsMessage $feedback): void
    {
        $this->sendAdminNotification($feedback);
        $this->sendUserConfirmation($feedback);
    }

    public function sendAdminNotification(SendContactFeedbackEmailsMessage $feedback): void
    {
        $email = new TemplatedEmail()
            ->from(new Address($this->mailerFrom))
            ->to(new Address($this->adminEmail))
            ->subject(sprintf('Новое обращение #%d', $feedback->id))
            ->htmlTemplate('emails/contact_feedback_admin.html.twig')
            ->textTemplate('emails/contact_feedback_admin.txt.twig')
            ->context([
                'id' => $feedback->id,
                'name' => $feedback->name,
                'phone' => $feedback->phone,
                'submitterEmail' => $feedback->email,
                'comment' => $feedback->comment,
            ]);

        $this->mailer->send($email);
    }

    public function sendUserConfirmation(SendContactFeedbackEmailsMessage $feedback): void
    {
        $email = new TemplatedEmail()
            ->from(new Address($this->mailerFrom))
            ->to(new Address($feedback->email))
            ->subject('Мы получили ваше сообщение')
            ->htmlTemplate('emails/contact_feedback_user.html.twig')
            ->textTemplate('emails/contact_feedback_user.txt.twig')
            ->context([
                'name' => $feedback->name,
            ]);

        $this->mailer->send($email);
    }
}
