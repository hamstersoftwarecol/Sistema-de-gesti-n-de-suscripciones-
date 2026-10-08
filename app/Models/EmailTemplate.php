<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use Concerns\LogsActivity;

    public const RENEWAL_REMINDER = 'renewal_reminder';

    public const INVOICE_CREATED = 'invoice_created';

    public const INVOICE_OVERDUE = 'invoice_overdue';

    public const PAYMENT_RECEIVED = 'payment_received';

    public const SUBSCRIPTION_EXPIRED = 'subscription_expired';

    public const PORTAL_WELCOME = 'portal_welcome';

    protected $fillable = ['key', 'name', 'subject', 'body', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function findActive(string $key): ?self
    {
        return static::query()->where('key', $key)->where('is_active', true)->first();
    }

    /** Placeholders available in every template. */
    public static function placeholders(): array
    {
        return [
            '{company_name}', '{customer_name}', '{customer_email}', '{subscription_reference}', '{plan_name}',
            '{amount}', '{renewal_date}', '{days_left}', '{invoice_number}', '{invoice_total}', '{invoice_due_date}',
            '{invoice_balance}', '{payment_amount}', '{portal_url}', '{login_email}', '{login_password}',
        ];
    }

    /**
     * Replace {placeholders} in subject and body.
     *
     * @return array{subject: string, body: string}
     */
    public function render(array $variables): array
    {
        $replacements = [];

        foreach ($variables as $key => $value) {
            $replacements['{'.$key.'}'] = (string) $value;
        }

        return [
            'subject' => strtr($this->subject, $replacements),
            'body' => strtr($this->body, $replacements),
        ];
    }
}
