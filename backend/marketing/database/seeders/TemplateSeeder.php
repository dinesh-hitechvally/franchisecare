<?php

namespace Database\Seeders;

use App\Models\Template;

/**
 * templates: a designed welcome email for people who just registered, a welcome text, and a promo text.
 *
 * The email lives in database/seeders/data/: welcome-email.design.json is what the visual designer opens and
 * welcome-email.html is that design rendered by the designer itself (what is sent). To change the email, edit the
 * template in the designer, or rebuild both files from it. Re-running this seeder puts these three templates back
 * to the versions below.
 */
class TemplateSeeder extends DemoSeeder
{
    public const WELCOME_EMAIL = 'Welcome email';

    public const WELCOME_SMS = 'Welcome SMS';

    public const PROMO_SMS = 'Promo SMS';

    /** Both codes are the ones the welcome email and the texts talk about. */
    public const WELCOME_CODE = 'WELCOME10';

    public const PROMO_CODE = 'SAVE10';

    protected function file(string $name): string
    {
        return (string) file_get_contents(__DIR__.'/data/'.$name);
    }

    public function run(): void
    {
        $biz = $this->companyId();

        $this->template($biz, self::WELCOME_EMAIL, [
            'type' => 'email',
            'subject' => 'Welcome to {{company_name}}, {{first_name}}!',
            'body' => $this->file('welcome-email.html'),
            'design' => json_decode($this->file('welcome-email.design.json'), true),
        ]);

        // texts stay under 160 characters (a single message) once the merge tags are filled in, and avoid emoji, which would halve that
        $this->template($biz, self::WELCOME_SMS, [
            'type' => 'sms',
            'body' => 'Welcome to {{company_name}}, {{first_name}}! Thanks for registering. Show code '.self::WELCOME_CODE.' for 10% off your first visit. Reply STOP to opt out.',
        ]);
        $this->template($biz, self::PROMO_SMS, [
            'type' => 'sms',
            'body' => 'Hi {{first_name}}, this week only: 10% off at {{company_name}} with code '.self::PROMO_CODE.'. Ends Sunday. Reply STOP to opt out.',
        ]);
    }

    /** Creates the demo template, or restores it to this version when it already exists. */
    protected function template(int $biz, string $name, array $values): void
    {
        $template = Template::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => $name])->first() ?? (new Template)->forceFill(['company_id' => $biz, 'name' => $name]);
        $template->forceFill($values + ['subject' => null, 'design' => null])->save();
    }
}
