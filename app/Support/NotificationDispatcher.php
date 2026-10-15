<?php

namespace App\Support;

use App\Models\Company;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationDispatcher
{
    public function sendCompanyEmail(Company $company, string $type, User $user, array $data = []): void
    {
        $settings = $company->notificationSettings;
        if (! $settings || ! $settings->email_enabled || blank($user->email)) {
            return;
        }

        $template = NotificationTemplate::where('company_id', $company->id)
            ->where('type', $type)
            ->where('channel', 'email')
            ->first();

        if (! $template) {
            return;
        }

        $renderer = app(NotificationTemplateRenderer::class);
        $payload = array_merge($data, [
            'company_name' => $company->name,
        ]);

        $subject = $renderer->render((string) $template->subject, $payload);
        $body = $renderer->render((string) $template->body, $payload);

        Mail::html($body, function ($message) use ($settings, $user, $subject) {
            $message->to($user->email, $user->name)
                ->subject($subject);

            if ($settings->from_email) {
                $message->from($settings->from_email, $settings->from_name ?: config('app.name'));
            }
        });
    }
}
