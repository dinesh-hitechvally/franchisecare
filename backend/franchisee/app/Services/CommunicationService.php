<?php

namespace App\Services;

use App\Contracts\Repositories\CommunicationRepositoryInterface;
use App\Contracts\Services\CommunicationServiceInterface;
use App\Models\SmsHistory;
use App\Models\EmailHistory;
use App\Models\Customer;
use App\Mail\GenericEmail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class CommunicationService implements CommunicationServiceInterface
{
    public function __construct(
        private CommunicationRepositoryInterface $communicationRepository
    ) {}

    // SMS Methods
    public function listSmsHistory(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->communicationRepository->getSmsHistory($filters, $perPage);
    }

    public function getSms(int $id): SmsHistory
    {
        $sms = $this->communicationRepository->findSmsById($id);
        if (!$sms) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("SMS not found");
        }
        return $sms;
    }

    public function createSms(array $data): SmsHistory
    {
        return $this->communicationRepository->createSms($data);
    }

    public function deleteSms(SmsHistory $sms): bool
    {
        return $this->communicationRepository->deleteSms($sms);
    }

    // Email Methods
    public function listEmailHistory(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->communicationRepository->getEmailHistory($filters, $perPage);
    }

    public function getEmail(int $id): EmailHistory
    {
        $email = $this->communicationRepository->findEmailById($id);
        if (!$email) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Email not found");
        }
        return $email;
    }

    public function createEmail(array $data): EmailHistory
    {
        return $this->communicationRepository->createEmail($data);
    }

    public function deleteEmail(EmailHistory $email): bool
    {
        return $this->communicationRepository->deleteEmail($email);
    }

    // Send Operations
    public function sendEmail(array $data): array
    {
        $to = $data['to'];
        $subject = $data['subject'];
        $body = $data['body'];
        $fromName = $data['from_name'] ?? config('mail.from.name');
        $companyId = $data['company_id'] ?? null;
        $customerId = $data['customer_id'] ?? null;

        try {
            Mail::to($to)->send(new GenericEmail($subject, $body, $fromName));

            // Log the email
            $this->createEmail([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'to_email' => $to,
                'from_name' => $fromName,
                'subject' => $subject,
                'body' => $body,
                'status' => 'sent',
            ]);

            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (\Exception $e) {
            // Log failed email
            $this->createEmail([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'to_email' => $to,
                'from_name' => $fromName,
                'subject' => $subject,
                'body' => $body,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()];
        }
    }

    public function sendBulkEmail(array $customerIds, string $subject, string $body, ?string $fromName = null): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        $customers = Customer::whereIn('id', $customerIds)->get();

        foreach ($customers as $customer) {
            if (!$customer->email) {
                $results['failed']++;
                $results['errors'][] = "Customer {$customer->id} has no email";
                continue;
            }

            $result = $this->sendEmail([
                'to' => $customer->email,
                'subject' => $subject,
                'body' => $body,
                'from_name' => $fromName,
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
            ]);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $result['message'];
            }
        }

        return $results;
    }

    public function sendEmailToCustomer(Customer $customer, array $data): array
    {
        if (!$customer->email) {
            return ['success' => false, 'message' => 'Customer has no email address'];
        }

        return $this->sendEmail(array_merge($data, [
            'to' => $customer->email,
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
        ]));
    }
}
