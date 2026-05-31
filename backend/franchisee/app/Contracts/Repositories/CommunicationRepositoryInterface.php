<?php

namespace App\Contracts\Repositories;

use App\Models\SmsHistory;
use App\Models\EmailHistory;
use Illuminate\Pagination\LengthAwarePaginator;

interface CommunicationRepositoryInterface
{
    // SMS
    public function getSmsHistory(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findSmsById(int $id): ?SmsHistory;

    public function createSms(array $data): SmsHistory;

    public function deleteSms(SmsHistory $sms): bool;

    // Email
    public function getEmailHistory(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findEmailById(int $id): ?EmailHistory;

    public function createEmail(array $data): EmailHistory;

    public function deleteEmail(EmailHistory $email): bool;
}
