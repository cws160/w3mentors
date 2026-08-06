<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    /**
     * @throws \InvalidArgumentException
     */
    public function submit(array $data, int $langId = 1): string
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($name === '') {
            throw new \InvalidArgumentException('Please enter your name.');
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Please enter a valid email address.');
        }

        if ($phone === '' || ! preg_match('/^[\s()+-]*([0-9][\s()+-]*){5,20}$/', $phone)) {
            throw new \InvalidArgumentException('Please enter a valid phone number.');
        }

        if ($message === '') {
            throw new \InvalidArgumentException('Please enter your message.');
        }

        $this->sendContactEmail($name, $email, $phone, $message, $langId);

        return 'Your message has been sent successfully.';
    }

    private function sendContactEmail(
        string $name,
        string $email,
        string $phone,
        string $message,
        int $langId
    ): void {
        $template = DB::table('tbl_email_templates')
            ->where('etpl_code', 'contact_us')
            ->where('etpl_lang_id', $langId)
            ->where('etpl_status', 1)
            ->first(['etpl_subject', 'etpl_body']);

        $siteName = (string) Configuration::getValue("CONF_WEBSITE_NAME_{$langId}", 'w3mentors');
        $fromEmail = (string) Configuration::getValue('CONF_FROM_EMAIL', config('mail.from.address', 'noreply@localhost'));
        $fromName = (string) Configuration::getValue('CONF_FROM_NAME', $siteName);
        $contactEmails = array_filter(array_map('trim', explode(',', (string) Configuration::getValue('CONF_CONTACT_EMAIL', ''))));

        if ($contactEmails === []) {
            throw new \InvalidArgumentException('Contact email is not configured.');
        }

        $subject = $template->etpl_subject ?? 'Contact Us Form Submitted on {website_name}';
        $body = $template->etpl_body ?? '<p><strong>Name:</strong> {name}<br><strong>Email:</strong> {email_address}<br><strong>Phone:</strong> {phone_number}<br><strong>Message:</strong> {message}</p>';

        $replacements = [
            '{name}' => $name,
            '{email_address}' => $email,
            '{phone_number}' => $phone,
            '{message}' => nl2br(e($message)),
            '{website_name}' => $siteName,
            '{primary-color}' => '#0c9331',
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $body = str_replace(array_keys($replacements), array_values($replacements), $body);

        try {
            Mail::html($body, function ($mail) use ($contactEmails, $subject, $fromEmail, $fromName) {
                $mail->to($contactEmails)->subject($subject);
                if ($fromEmail !== '') {
                    $mail->from($fromEmail, $fromName !== '' ? $fromName : null);
                }
            });
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Email could not be sent. Please try again later.');
        }
    }
}
