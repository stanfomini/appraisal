<?php

// app/Notifications/AppraisalCertificateNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\FormResponse;
use App\Models\CertificateNotification as ResponseNotification;

class AppraisalCertificateNotification extends Notification
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
        $certificate = $this->formResponse->certificates->last();

        if ($notifiable instanceof \Illuminate\Notifications\AnonymousNotifiable) {
            // It's an anonymous route. We'll store notifiable_id as null or skip it.
            $notifiableId = null;
        } else {
            // Otherwise assume it’s a model with an id property
            $notifiableId = $notifiable->id;
        }

        // Log the notification
        ResponseNotification::create([
            'tenant_id' => $this->formResponse->tenant_id,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiableId,
            'type' => 'appraisal_completed',
            'data' => ['form_response_id' => $this->formResponse->id],
            'sent_at' => now(),
            'status' => 'sent',
        ]);

        return (new MailMessage)
            ->subject('Your Vehicle Appraisal Certificate')
            ->line('Dear ' . $this->formResponse->full_name . ',')
            ->line('We have appraised your vehicle.')
            ->line('Vehicle: ' . $this->formResponse->year . ' ' . $this->formResponse->make . ' ' . $this->formResponse->model)
            ->line('Appraised Value: $' . number_format($this->formResponse->appraisal_value, 2))
            ->line('Valid Until: ' . $certificate->expiry->format('Y-m-d'))
            ->attach(storage_path('app/' . $certificate->path), [
                'as' => 'appraisal_certificate.pdf',
                'mime' => 'application/pdf',
            ])
            ->line('Thank you for choosing our dealership!');
    }

    public function failed($notifiable, $exception)
    {
        Notification::create([
            'tenant_id' => $this->formResponse->tenant_id,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'type' => 'appraisal_completed',
            'data' => ['form_response_id' => $this->formResponse->id],
            'sent_at' => now(),
            'status' => 'failed',
        ]);
    }
}