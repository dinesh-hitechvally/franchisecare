<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerItemWaiverAudit extends Model
{
    protected $table = 'customer_item_waiver_audits';

    protected $fillable = [
        'company_id',
        'waiver_id',
        'customer_id',
        'item_id',
        'waiver_type',
        'age',
        'weight',
        'is_vaccinated',
        'health_arthritis',
        'health_epilepsy',
        'health_collapsing_trachea',
        'health_heart_disease',
        'health_diabetes',
        'health_chronic_skin',
        'health_chronic_ear',
        'health_allergies',
        'other_health',
        'sensitivity_skin',
        'sensitivity_products',
        'sensitivity_vet_advice',
        'behavioural_fearful',
        'behavioural_aggressive',
        'behavioural_anxious',
        'behavioural_none_known',
        'behavioural_others',
        'dislike_head',
        'dislike_paws',
        'dislike_tail',
        'dislike_other',
        'grooming_prof_groomed',
        'grooming_prev_issues',
        'tick_prevention',
        'accepted_expectations',
        'signature_path',
        'pdf_path',
    ];

    protected $casts = [
        'is_vaccinated' => 'boolean',
        'health_arthritis' => 'boolean',
        'health_epilepsy' => 'boolean',
        'health_collapsing_trachea' => 'boolean',
        'health_heart_disease' => 'boolean',
        'health_diabetes' => 'boolean',
        'health_chronic_skin' => 'boolean',
        'health_chronic_ear' => 'boolean',
        'health_allergies' => 'boolean',
        'behavioural_fearful' => 'boolean',
        'behavioural_aggressive' => 'boolean',
        'behavioural_anxious' => 'boolean',
        'behavioural_none_known' => 'boolean',
        'dislike_head' => 'boolean',
        'dislike_paws' => 'boolean',
        'dislike_tail' => 'boolean',
        'dislike_other' => 'boolean',
        'accepted_expectations' => 'boolean',
    ];

    public function waiver()
    {
        return $this->belongsTo(CustomerItemWaiver::class, 'waiver_id');
    }

    public function item()
    {
        return $this->belongsTo(CustomerItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
