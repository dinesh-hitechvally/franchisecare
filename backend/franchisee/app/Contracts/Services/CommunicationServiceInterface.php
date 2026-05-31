<?php

namespace App\Contracts\Services;

use App\Models\SmsHistory;
use App\Models\EmailHistory;
use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

interface CommunicationServiceInterface
{
    // SMS
    public function listSmsHistory(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function getSms(int $id): SmsHistory;

    public function createSms(array $data): SmsHistory;

    public function deleteSms(SmsHistory $sms): bool;

    // Email
    public function listEmailHistory(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function getEmail(int $id): EmailHistory;

    public function createEmail(array $data): EmailHistory;

    public function deleteEmail(EmailHistory $email): bool;

    // Send Operations
    public function sendEmail(array $data): array;

    public function sendBulkEmail(array $customerIds, string $subject, string $body, ?string $fromName = null): array;

    public function sendEmailToCustomer(Customer $customer, array $data): array;
}
