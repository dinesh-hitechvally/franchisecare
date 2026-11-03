<?php

namespace Database\Seeders;

use App\Models\Contact;

/** contacts: every way a contact can be reachable (email, phone, both), each opted in and opted out */
class ContactSeeder extends DemoSeeder
{
    /** [first, last, email, phone, source, tag names, subscribed] — the tags are attached by ContactTagSeeder. */
    public const PEOPLE = [
        ['Ann', 'Lee', 'ann@example.com', '+61400000001', 'manual', [TagSeeder::VIP], true],
        ['Bob', 'Ray', 'bob@example.com', '+61400000002', 'import', [], true],
        ['Cara', 'Stone', 'cara@gmail.com', '+61400000003', 'form', [TagSeeder::WEBSITE_LEAD], true],
        ['Dev', 'Patel', 'dev@gmail.com', null, 'form', [TagSeeder::WEBSITE_LEAD, TagSeeder::VIP], true],
        // email only
        ['Eli', 'Email', 'eli.subscribed@example.com', null, 'form', [TagSeeder::WEBSITE_LEAD], true],
        ['Ella', 'Email', 'ella.unsubscribed@example.com', null, 'form', [TagSeeder::WEBSITE_LEAD], false],
        // phone only
        ['Pia', 'Phone', null, '+61400000011', 'manual', [TagSeeder::VIP], true],
        ['Pete', 'Phone', null, '+61400000012', 'manual', [TagSeeder::VIP], false],
        // email and phone
        ['Bea', 'Both', 'bea.subscribed@example.com', '+61400000021', 'import', [TagSeeder::VIP, TagSeeder::WEBSITE_LEAD], true],
        ['Ben', 'Both', 'ben.unsubscribed@example.com', '+61400000022', 'import', [TagSeeder::VIP], false],
        // a few more
        ['Fay', 'Gmail', 'fay@gmail.com', '+61400000031', 'form', [TagSeeder::WEBSITE_LEAD], true],
        ['Gus', 'Gmail', 'gus.unsubscribed@gmail.com', '+61400000032', 'manual', [], false],
        ['Hana', 'Outlook', 'hana@outlook.com', null, 'import', [], true],
        ['Ian', 'Mobile', null, '+61400000041', 'import', [TagSeeder::WEBSITE_LEAD], true],
    ];

    /** The contact for one PEOPLE row: found by email, or by phone when there is no email. */
    public static function find(int $companyId, ?string $email, ?string $phone): ?Contact
    {
        return Contact::withoutGlobalScopes()->where('company_id', $companyId)->where($email ? ['email' => $email] : ['phone' => $phone])->first();
    }

    public function run(): void
    {
        $biz = $this->companyId();
        foreach (self::PEOPLE as [$first, $last, $email, $phone, $source, , $subscribed]) {
            if (self::find($biz, $email, $phone)) {
                continue;
            }
            (new Contact)->forceFill([
                'company_id' => $biz, 'first_name' => $first, 'last_name' => $last, 'email' => $email, 'phone' => $phone,
                'source' => $source, 'is_subscribed' => $subscribed,
            ])->save();
        }
    }
}
