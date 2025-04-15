<?php

// app/Notifications/FormSubmittedNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;
use App\Models\FormResponse;
use App\Models\CertificateNotification;

class FormSubmittedNotification extends BaseNotification
{
    use Queueable;

    protected $formResponse;

    public function __construct(FormResponse $formResponse)
    {
        $this->formResponse = $formResponse;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // Log the notification
        CertificateNotification::create([
            'tenant_id' => $this->formResponse->tenant_id,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'type' => 'form_submitted',
            'data' => ['form_response_id' => $this->formResponse->id],
            'sent_at' => now(),
            'status' => 'sent',
        ]);

        return (new MailMessage)
            ->subject('New Vehicle Appraisal Form Submitted')
            ->line('A new vehicle appraisal form has been submitted.')
            ->line('VIN: ' . $this->formResponse->vin)
            ->line('Customer: ' . $this->formResponse->full_name)
            ->action('View Form', url('/' . $this->formResponse->tenant_id . '/dashboard'))
            ->line('Thank you for your attention!');
    }

    public function failed($notifiable, $exception)
    {
        Notification::create([
            'tenant_id' => $this->formResponse->tenant_id,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'type' => 'form_submitted',
            'data' => ['form_response_id' => $this->formResponse->id],
            'sent_at' => now(),
            'status' => 'failed',
        ]);
    }
}