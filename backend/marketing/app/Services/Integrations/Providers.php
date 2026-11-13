<?php

namespace App\Services\Integrations;

/** The platforms that can be connected. To add one: write a Provider subclass and list it here. */
class Providers
{
    /** @return array<string, Provider> */
    public static function all(): array
    {
        static $all;

        return $all ??= collect([
            new ShopifyProvider, new WooCommerceProvider, new MagentoProvider, new BigCommerceProvider, new EcwidProvider,                  // online stores
            new WordPressProvider, new GhostProvider, new SquarespaceProvider, new WebflowProvider, new WixProvider,                        // websites
            new HubSpotProvider, new PipedriveProvider, new SalesforceProvider, new ActiveCampaignProvider, new CloseProvider,              // CRM
            new MailchimpProvider, new KlaviyoProvider, new BrevoProvider, new MailerLiteProvider, new KitProvider,                        // email marketing
            new StripeProvider, new SquareProvider, new PaddleProvider, new ChargebeeProvider, new GoCardlessProvider,                      // payments
            new FacebookLeadsProvider, new InstagramLeadsProvider, new LinkedInLeadsProvider,
            new WhatsAppProvider, new MessengerProvider, new XProvider,   // messaging: campaign channels
        ])->keyBy(fn (Provider $p) => $p->key())->all();
    }

    public static function find(?string $key): ?Provider
    {
        return self::all()[$key] ?? null;
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }
}
