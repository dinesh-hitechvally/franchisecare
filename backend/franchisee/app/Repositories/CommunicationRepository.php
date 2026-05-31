<?php

namespace App\Repositories;

use App\Contracts\Repositories\CommunicationRepositoryInterface;
use App\Models\SmsHistory;
use App\Models\EmailHistory;
use Illuminate\Pagination\LengthAwarePaginator;

class CommunicationRepository implements CommunicationRepositoryInterface
{
    // SMS Methods
    public function getSmsHistory(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = SmsHistory::query()->orderByDesc('created_at');

        if (!empty($filters['status']) && in_array($filters['status'], ['sent', 'queued'], true)) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        return $query->paginate($perPage);
    }

    public function findSmsById(int $id): ?SmsHistory
    {
        return SmsHistory::find($id);
    }

    public function createSms(array $data): SmsHistory
    {
        return SmsHistory::create($data);
    }

    public function deleteSms(SmsHistory $sms): bool
    {
        return $sms->delete();
    }

    // Email Methods
    public function getEmailHistory(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = EmailHistory::query()->orderByDesc('created_at');

        if (!empty($filters['status']) && in_array($filters['status'], ['sent', 'queued'], true)) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        return $query->paginate($perPage);
    }

    public function findEmailById(int $id): ?EmailHistory
    {
        return EmailHistory::find($id);
    }

    public function createEmail(array $data): EmailHistory
    {
        return EmailHistory::create($data);
    }

    public function deleteEmail(EmailHistory $email): bool
    {
        return $email->delete();
    }
}
