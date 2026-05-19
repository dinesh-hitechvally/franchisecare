<?php

namespace App\Models\Concerns;

trait HasSimpleAudit
{
    public static function bootHasSimpleAudit(): void
    {
        static::created(function ($model): void {
            $model->writeSimpleAudit('created');
        });

        static::updated(function ($model): void {
            $model->writeSimpleAudit('updated');
        });

        static::deleted(function ($model): void {
            $model->writeSimpleAudit('deleted');
        });
    }

    protected function writeSimpleAudit(string $actionType): void
    {
        $auditModelClass = $this->getAuditModelClass();
        $foreignKey = $this->getAuditForeignKey();

        $auditPayload = array_merge([
            $foreignKey => $this->getKey(),
            'company_id' => $this->company_id ?? null,
            'action_type' => $actionType,
            'action_at' => now(),
            'performed_by' => auth()->id(),
        ], $this->getAuditSnapshotColumns($actionType), $this->getAuditExtraColumns());

        $auditModelClass::create($auditPayload);
    }

    abstract protected function getAuditModelClass(): string;

    abstract protected function getAuditForeignKey(): string;

    protected function getAuditSnapshotColumns(string $actionType): array
    {
        return [];
    }

    protected function getAuditExtraColumns(): array
    {
        return [];
    }
}
