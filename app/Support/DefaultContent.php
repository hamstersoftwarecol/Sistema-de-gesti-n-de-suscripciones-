<?php

namespace App\Support;

/**
 * Localised default content created by the setup wizard and the seeders.
 */
class DefaultContent
{
    /** @return array<string, array{name: string, subject: string, body: string}> */
    public static function emailTemplates(string $locale = 'es'): array
    {
        $templates = [
            'es' => [
                'renewal_reminder' => [
                    'name' => 'Recordatorio de renovación',
                    'subject' => 'Tu suscripción {plan_name} se renueva en {days_left} días',
                    'body' => "Hola {customer_name},\n\nTe recordamos que tu suscripción {subscription_reference} ({plan_name}) se renovará el {renewal_date} por un importe de {amount}.\n\nSi deseas hacer cambios en tu plan puedes hacerlo desde el portal de clientes: {portal_url}\n\nGracias por confiar en {company_name}.",
                ],
                'invoice_created' => [
                    'name' => 'Nueva factura',
                    'subject' => 'Nueva factura {invoice_number} de {company_name}',
                    'body' => "Hola {customer_name},\n\nAdjuntamos la factura {invoice_number} por un total de {invoice_total}, con fecha de vencimiento {invoice_due_date}.\n\nTambién puedes consultarla y descargarla en el portal de clientes: {portal_url}\n\nSaludos,\n{company_name}",
                ],
                'invoice_overdue' => [
                    'name' => 'Factura vencida',
                    'subject' => 'Recordatorio: la factura {invoice_number} está vencida',
                    'body' => "Hola {customer_name},\n\nLa factura {invoice_number} venció el {invoice_due_date} y tiene un saldo pendiente de {invoice_balance}.\n\nTe agradecemos realizar el pago lo antes posible para evitar la suspensión del servicio. Si ya pagaste, por favor ignora este mensaje.\n\n{company_name}",
                ],
                'payment_received' => [
                    'name' => 'Pago recibido',
                    'subject' => 'Hemos recibido tu pago de {payment_amount}',
                    'body' => "Hola {customer_name},\n\nConfirmamos la recepción de tu pago por {payment_amount} correspondiente a la factura {invoice_number}. Saldo pendiente: {invoice_balance}.\n\n¡Muchas gracias!\n{company_name}",
                ],
                'subscription_expired' => [
                    'name' => 'Suscripción finalizada',
                    'subject' => 'Tu suscripción {subscription_reference} ha finalizado',
                    'body' => "Hola {customer_name},\n\nTu suscripción {plan_name} ({subscription_reference}) ha finalizado. Si deseas reactivarla, contáctanos o entra al portal de clientes: {portal_url}\n\n{company_name}",
                ],
                'portal_welcome' => [
                    'name' => 'Bienvenida al portal',
                    'subject' => 'Bienvenido al portal de clientes de {company_name}',
                    'body' => "Hola {customer_name},\n\nHemos creado tu acceso al portal de clientes, donde podrás consultar tus suscripciones, facturas y pagos.\n\nURL: {portal_url}\nUsuario: {login_email}\nContraseña: {login_password}\n\nTe recomendamos cambiar la contraseña después de iniciar sesión.\n\n{company_name}",
                ],
            ],
            'en' => [
                'renewal_reminder' => [
                    'name' => 'Renewal reminder',
                    'subject' => 'Your {plan_name} subscription renews in {days_left} days',
                    'body' => "Hi {customer_name},\n\nThis is a reminder that your subscription {subscription_reference} ({plan_name}) will renew on {renewal_date} for {amount}.\n\nYou can manage your plan from the customer portal: {portal_url}\n\nThank you for choosing {company_name}.",
                ],
                'invoice_created' => [
                    'name' => 'New invoice',
                    'subject' => 'New invoice {invoice_number} from {company_name}',
                    'body' => "Hi {customer_name},\n\nPlease find attached invoice {invoice_number} for {invoice_total}, due on {invoice_due_date}.\n\nYou can also view and download it from the customer portal: {portal_url}\n\nBest regards,\n{company_name}",
                ],
                'invoice_overdue' => [
                    'name' => 'Overdue invoice',
                    'subject' => 'Reminder: invoice {invoice_number} is overdue',
                    'body' => "Hi {customer_name},\n\nInvoice {invoice_number} was due on {invoice_due_date} and has an outstanding balance of {invoice_balance}.\n\nPlease make the payment as soon as possible to avoid service interruption. If you have already paid, please ignore this message.\n\n{company_name}",
                ],
                'payment_received' => [
                    'name' => 'Payment received',
                    'subject' => 'We received your payment of {payment_amount}',
                    'body' => "Hi {customer_name},\n\nWe confirm the receipt of your payment of {payment_amount} for invoice {invoice_number}. Outstanding balance: {invoice_balance}.\n\nThank you!\n{company_name}",
                ],
                'subscription_expired' => [
                    'name' => 'Subscription ended',
                    'subject' => 'Your subscription {subscription_reference} has ended',
                    'body' => "Hi {customer_name},\n\nYour {plan_name} subscription ({subscription_reference}) has ended. If you would like to reactivate it, contact us or visit the customer portal: {portal_url}\n\n{company_name}",
                ],
                'portal_welcome' => [
                    'name' => 'Portal welcome',
                    'subject' => 'Welcome to the {company_name} customer portal',
                    'body' => "Hi {customer_name},\n\nWe have created your customer portal access, where you can review your subscriptions, invoices and payments.\n\nURL: {portal_url}\nUser: {login_email}\nPassword: {login_password}\n\nWe recommend changing your password after signing in.\n\n{company_name}",
                ],
            ],
            'pt' => [
                'renewal_reminder' => [
                    'name' => 'Lembrete de renovação',
                    'subject' => 'Sua assinatura {plan_name} será renovada em {days_left} dias',
                    'body' => "Olá {customer_name},\n\nLembramos que sua assinatura {subscription_reference} ({plan_name}) será renovada em {renewal_date} no valor de {amount}.\n\nVocê pode gerenciar seu plano no portal do cliente: {portal_url}\n\nObrigado por confiar na {company_name}.",
                ],
                'invoice_created' => [
                    'name' => 'Nova fatura',
                    'subject' => 'Nova fatura {invoice_number} de {company_name}',
                    'body' => "Olá {customer_name},\n\nSegue em anexo a fatura {invoice_number} no valor de {invoice_total}, com vencimento em {invoice_due_date}.\n\nVocê também pode consultá-la no portal do cliente: {portal_url}\n\nAtenciosamente,\n{company_name}",
                ],
                'invoice_overdue' => [
                    'name' => 'Fatura vencida',
                    'subject' => 'Lembrete: a fatura {invoice_number} está vencida',
                    'body' => "Olá {customer_name},\n\nA fatura {invoice_number} venceu em {invoice_due_date} e possui saldo pendente de {invoice_balance}.\n\nPor favor, realize o pagamento o quanto antes para evitar a suspensão do serviço. Se já pagou, desconsidere esta mensagem.\n\n{company_name}",
                ],
                'payment_received' => [
                    'name' => 'Pagamento recebido',
                    'subject' => 'Recebemos seu pagamento de {payment_amount}',
                    'body' => "Olá {customer_name},\n\nConfirmamos o recebimento do seu pagamento de {payment_amount} referente à fatura {invoice_number}. Saldo pendente: {invoice_balance}.\n\nMuito obrigado!\n{company_name}",
                ],
                'subscription_expired' => [
                    'name' => 'Assinatura encerrada',
                    'subject' => 'Sua assinatura {subscription_reference} foi encerrada',
                    'body' => "Olá {customer_name},\n\nSua assinatura {plan_name} ({subscription_reference}) foi encerrada. Para reativá-la, entre em contato ou acesse o portal do cliente: {portal_url}\n\n{company_name}",
                ],
                'portal_welcome' => [
                    'name' => 'Boas-vindas ao portal',
                    'subject' => 'Bem-vindo ao portal do cliente da {company_name}',
                    'body' => "Olá {customer_name},\n\nCriamos seu acesso ao portal do cliente, onde você pode consultar assinaturas, faturas e pagamentos.\n\nURL: {portal_url}\nUsuário: {login_email}\nSenha: {login_password}\n\nRecomendamos alterar a senha após o primeiro acesso.\n\n{company_name}",
                ],
            ],
        ];

        return $templates[$locale] ?? $templates['en'];
    }

    /** @return array<int, array{name: string, color: string, is_done_column: bool}> */
    public static function kanbanColumns(string $locale = 'es'): array
    {
        $names = [
            'es' => ['Por hacer', 'En progreso', 'En revisión', 'Completado'],
            'en' => ['To do', 'In progress', 'Review', 'Done'],
            'pt' => ['A fazer', 'Em andamento', 'Em revisão', 'Concluído'],
        ][$locale] ?? ['To do', 'In progress', 'Review', 'Done'];

        $colors = ['#64748b', '#3b82f6', '#f59e0b', '#10b981'];

        return collect($names)->map(fn ($name, $i) => [
            'name' => $name,
            'color' => $colors[$i],
            'is_done_column' => $i === 3,
        ])->all();
    }

    /** Exchange rates expressed as units per 1 USD (editable afterwards). */
    public static function currencies(): array
    {
        return [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'usd_rate' => 1, 'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'usd_rate' => 0.92, 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'Pound Sterling', 'symbol' => '£', 'usd_rate' => 0.79, 'decimal_places' => 2],
            ['code' => 'MXN', 'name' => 'Peso mexicano', 'symbol' => 'MX$', 'usd_rate' => 18.5, 'decimal_places' => 2],
            ['code' => 'COP', 'name' => 'Peso colombiano', 'symbol' => 'COL$', 'usd_rate' => 4000, 'decimal_places' => 0],
            ['code' => 'BRL', 'name' => 'Real brasileño', 'symbol' => 'R$', 'usd_rate' => 5.4, 'decimal_places' => 2],
            ['code' => 'ARS', 'name' => 'Peso argentino', 'symbol' => 'AR$', 'usd_rate' => 1000, 'decimal_places' => 2],
            ['code' => 'CLP', 'name' => 'Peso chileno', 'symbol' => 'CLP$', 'usd_rate' => 940, 'decimal_places' => 0],
            ['code' => 'PEN', 'name' => 'Sol peruano', 'symbol' => 'S/', 'usd_rate' => 3.75, 'decimal_places' => 2],
        ];
    }

    /** Default values for every application setting. */
    public static function settings(string $locale = 'es'): array
    {
        return [
            'company_name' => 'SubsERP',
            'company_email' => null,
            'company_phone' => null,
            'company_address' => null,
            'company_tax_id' => null,
            'company_website' => null,
            'default_locale' => $locale,
            'timezone' => config('app.timezone', 'UTC'),
            'date_format' => 'd/m/Y',
            'invoice_prefix' => $locale === 'en' ? 'INV-' : 'FAC-',
            'invoice_next_number' => 1,
            'invoice_due_days' => 7,
            'default_tax_rate' => 0,
            'invoice_notes' => null,
            'invoice_terms' => null,
            'reminders_enabled' => true,
            'reminder_days' => '7,3,1',
            'overdue_reminder_interval' => 3,
            'auto_email_invoices' => false,
            'mark_past_due' => true,
            'allow_registration' => false,
            'google_login_enabled' => false,
            'google_auto_register' => false,
            'gemini_model' => 'gemini-2.5-flash',
            'maintenance_mode' => false,
            'maintenance_message' => null,
            'auto_backup' => false,
            'backup_keep' => 10,
            'default_theme' => 'system',
            'default_accent' => 'indigo',
            'cron_token' => bin2hex(random_bytes(16)),
        ];
    }
}
