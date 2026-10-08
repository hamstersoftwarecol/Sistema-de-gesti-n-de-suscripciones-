<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Invoice;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Seller;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Realistic demo data (no Faker, so it also works on production installs without dev dependencies).
 */
class DemoDataSeeder extends Seeder
{
    public function run(BillingService $billing): void
    {
        mt_srand(2026);
        $previousLogging = ActivityLog::$enabled;
        ActivityLog::$enabled = false;

        // Demo data must never e-mail anybody.
        $previousMailer = config('mail.default');
        config(['mail.default' => 'array']);
        Mail::purge();

        $usd = Currency::query()->where('code', 'USD')->first() ?? Currency::default();
        $eur = Currency::query()->where('code', 'EUR')->first() ?? $usd;
        $mxn = Currency::query()->where('code', 'MXN')->first() ?? $usd;
        $cop = Currency::query()->where('code', 'COP')->first() ?? $usd;

        $admin = User::query()->where('role', User::ROLE_ADMIN)->first();

        User::query()->firstOrCreate(['email' => 'staff@demo.com'], [
            'name' => 'Marta Operaciones',
            'password' => 'password',
            'role' => User::ROLE_STAFF,
            'email_verified_at' => now(),
        ]);

        // Catalogue -------------------------------------------------------------
        $categories = collect([
            ['name' => 'Software SaaS', 'color' => '#6366f1', 'description' => 'Aplicaciones en la nube'],
            ['name' => 'Hosting y dominios', 'color' => '#0ea5e9', 'description' => 'Infraestructura web'],
            ['name' => 'Marketing digital', 'color' => '#f43f5e', 'description' => 'Herramientas de marketing'],
            ['name' => 'Soporte y mantenimiento', 'color' => '#10b981', 'description' => 'Servicios profesionales'],
        ])->map(fn ($c) => ProductCategory::query()->create($c));

        $suppliers = collect([
            ['name' => 'CloudNova Infraestructura S.A.', 'contact_name' => 'Diego Paredes', 'email' => 'ventas@cloudnova.example', 'phone' => '+57 601 555 0101', 'country' => 'Colombia', 'website' => 'https://cloudnova.example'],
            ['name' => 'DataCenter Andino', 'contact_name' => 'Lucía Fernández', 'email' => 'contacto@dcandino.example', 'phone' => '+51 1 555 0199', 'country' => 'Perú', 'website' => 'https://dcandino.example'],
            ['name' => 'Licencias Globales Ltda.', 'contact_name' => 'Roberto Silva', 'email' => 'partners@licglobal.example', 'phone' => '+52 55 5555 0123', 'country' => 'México', 'website' => 'https://licglobal.example'],
            ['name' => 'Soporte Pro Partners', 'contact_name' => 'Elena Martín', 'email' => 'hola@soportepro.example', 'phone' => '+34 910 555 010', 'country' => 'España', 'website' => 'https://soportepro.example'],
        ])->map(fn ($s) => Supplier::query()->create($s + ['is_active' => true]));

        $catalogue = [
            ['CRM Cloud', 'software', 0, 0, 'CRM-001', 4, [
                ['Básico mensual', 29, 'monthly', 1, 14, 0, $usd],
                ['Profesional mensual', 79, 'monthly', 1, 14, 0, $usd],
                ['Profesional anual', 790, 'yearly', 1, 0, 0, $usd],
            ]],
            ['Hosting Empresarial', 'service', 1, 0, 'HOST-010', 6, [
                ['Hosting 10 GB', 15, 'monthly', 1, 0, 10, $usd],
                ['Hosting 50 GB trimestral', 99, 'quarterly', 1, 0, 25, $usd],
            ]],
            ['Email Marketing Pro', 'software', 2, 2, 'MKT-200', 8, [
                ['Starter', 25, 'monthly', 1, 7, 0, $eur],
                ['Growth semestral', 270, 'semiannual', 1, 0, 0, $eur],
            ]],
            ['Soporte Premium 24/7', 'service', 3, 3, 'SUP-247', 40, [
                ['Bolsa 10 horas', 450, 'monthly', 1, 0, 0, $usd],
            ]],
            ['Backup Gestionado', 'digital', 1, 1, 'BCK-100', 3, [
                ['Backup 100 GB', 12, 'monthly', 1, 0, 0, $usd],
                ['Backup 1 TB anual', 1200, 'yearly', 1, 0, 50, $usd],
            ]],
            ['Licencia ERP Pymes', 'software', 0, 2, 'ERP-PY', 120, [
                ['ERP mensual (MXN)', 1800, 'monthly', 1, 0, 0, $mxn],
                ['ERP anual (COP)', 4800000, 'yearly', 1, 0, 0, $cop],
            ]],
        ];

        $plans = collect();
        foreach ($catalogue as [$name, $type, $cat, $sup, $sku, $cost, $planRows]) {
            $product = Product::query()->create([
                'name' => $name,
                'type' => $type,
                'product_category_id' => $categories[$cat]->id,
                'supplier_id' => $suppliers[$sup]->id,
                'sku' => $sku,
                'cost' => $cost,
                'description' => "{$name}: servicio por suscripción con soporte incluido.",
                'is_active' => true,
            ]);

            foreach ($planRows as [$planName, $price, $cycle, $interval, $trial, $setup, $currency]) {
                $plans->push(Plan::query()->create([
                    'product_id' => $product->id,
                    'currency_id' => $currency?->id,
                    'name' => $planName,
                    'price' => $price,
                    'billing_cycle' => $cycle,
                    'interval_count' => $interval,
                    'trial_days' => $trial,
                    'setup_fee' => $setup,
                    'features' => "Soporte por email\nActualizaciones incluidas\nPanel de control",
                    'is_active' => true,
                ]));
            }
        }

        // Sales team ------------------------------------------------------------
        $sellerUser = User::query()->firstOrCreate(['email' => 'seller@demo.com'], [
            'name' => 'Laura Gómez',
            'password' => 'password',
            'role' => User::ROLE_SELLER,
            'email_verified_at' => now(),
        ]);

        $sellers = collect([
            ['user_id' => $sellerUser->id, 'name' => 'Laura Gómez', 'email' => 'seller@demo.com', 'phone' => '+57 300 555 0001', 'commission_rate' => 10, 'monthly_target' => 3000],
            ['name' => 'Carlos Ruiz', 'email' => 'carlos.ruiz@demo.com', 'phone' => '+52 55 5555 0002', 'commission_rate' => 8, 'monthly_target' => 2500],
            ['name' => 'Ana Torres', 'email' => 'ana.torres@demo.com', 'phone' => '+34 600 555 003', 'commission_rate' => 12, 'monthly_target' => 4000],
        ])->map(fn ($s) => Seller::query()->create($s + ['is_active' => true]));

        // Custom fields -----------------------------------------------------------
        $industry = CustomField::query()->create([
            'entity' => 'customer', 'name' => 'industry', 'label' => 'Sector', 'type' => 'select',
            'options' => ['Tecnología', 'Retail', 'Salud', 'Educación', 'Finanzas', 'Servicios'],
            'show_in_table' => true, 'is_active' => true, 'sort_order' => 1,
        ]);
        CustomField::query()->create([
            'entity' => 'customer', 'name' => 'employees', 'label' => 'Nº de empleados', 'type' => 'number',
            'is_active' => true, 'sort_order' => 2,
        ]);
        CustomField::query()->create([
            'entity' => 'subscription', 'name' => 'contract_number', 'label' => 'Nº de contrato', 'type' => 'text',
            'placeholder' => 'CT-2026-000', 'is_active' => true, 'sort_order' => 1,
        ]);

        // Customers ---------------------------------------------------------------
        $people = [
            ['Andrés Morales', 'Innovatech SAS', 'Bogotá', 'Colombia'],
            ['Valentina Rojas', 'Café Origen', 'Medellín', 'Colombia'],
            ['Javier Herrera', 'Logística Express', 'Ciudad de México', 'México'],
            ['Camila Vargas', 'Clínica Santa Elena', 'Guadalajara', 'México'],
            ['Sofía Castillo', 'EduSmart Academy', 'Madrid', 'España'],
            ['Mateo Jiménez', 'Finanzas Claras', 'Barcelona', 'España'],
            ['Isabella Navarro', 'Moda Urbana', 'Buenos Aires', 'Argentina'],
            ['Sebastián Ortiz', 'AgroAndes', 'Santiago', 'Chile'],
            ['Lucía Mendoza', 'Restaurante La Brasa', 'Lima', 'Perú'],
            ['Daniel Ramírez', 'Constructora Horizonte', 'Cali', 'Colombia'],
            ['Paula Romero', 'Estudio Creativo Pixel', 'Valencia', 'España'],
            ['Tomás Aguilar', 'Ferretería El Tornillo', 'Monterrey', 'México'],
            ['Mariana Suárez', 'Bienestar Spa', 'Quito', 'Ecuador'],
            ['Nicolás Peña', 'TransAndina Cargo', 'La Paz', 'Bolivia'],
            ['Gabriela Ríos', 'Óptica Visión Clara', 'Montevideo', 'Uruguay'],
            ['Felipe Cordero', 'Gimnasio PowerFit', 'Asunción', 'Paraguay'],
            ['Daniela Fuentes', 'Inmobiliaria Prisma', 'Barranquilla', 'Colombia'],
            ['Alejandro Vega', 'Software Andino', 'Bogotá', 'Colombia'],
            ['Renata Molina', 'Pastelería Dulce Hogar', 'Puebla', 'México'],
            ['Emilio Paredes', 'Consultores Asociados', 'Sevilla', 'España'],
            ['Martina Cabrera', 'Veterinaria Patitas', 'Córdoba', 'Argentina'],
            ['Samuel León', 'Hotel Mirador', 'Cartagena', 'Colombia'],
            ['Antonella Ruiz', 'Textiles del Sur', 'Arequipa', 'Perú'],
            ['Joaquín Delgado', 'Seguridad Total', 'Valparaíso', 'Chile'],
        ];

        $customers = collect();
        foreach ($people as $i => [$name, $company, $city, $country]) {
            $currency = match ($country) {
                'España' => $eur,
                'México' => $i % 2 ? $mxn : $usd,
                default => $usd,
            };

            $email = strtolower(str_replace(' ', '.', iconv('UTF-8', 'ASCII//TRANSLIT', $name))).'@'.strtolower(preg_replace('/[^a-z]/i', '', iconv('UTF-8', 'ASCII//TRANSLIT', $company))).'.example';

            $customer = Customer::query()->create([
                'name' => $name,
                'company' => $company,
                'email' => $email,
                'phone' => '+'.mt_rand(51, 598).' '.mt_rand(300, 399).' '.mt_rand(100, 999).' '.mt_rand(1000, 9999),
                'tax_id' => (string) mt_rand(800000000, 999999999),
                'address' => 'Calle '.mt_rand(1, 120).' # '.mt_rand(1, 99).'-'.mt_rand(1, 99),
                'city' => $city,
                'country' => $country,
                'currency_id' => $currency?->id,
                'seller_id' => $sellers[$i % 3]->id,
                'status' => $i >= 22 ? 'lead' : 'active',
                'notes' => null,
            ]);

            $customer->saveCustomFields([
                'industry' => $industry->options[$i % count($industry->options)],
                'employees' => mt_rand(3, 250),
            ]);

            $customers->push($customer);
        }

        // Subscriptions with a year of billing history ------------------------------
        $active = $customers->where('status', 'active')->values();
        $subscriptions = collect();

        foreach ($active as $i => $customer) {
            $count = $i % 4 === 0 ? 2 : 1;

            for ($n = 0; $n < $count; $n++) {
                $plan = $plans[($i * 3 + $n * 5) % $plans->count()];
                $monthsAgo = mt_rand(1, 13);

                $subscription = $billing->subscribe([
                    'customer_id' => $customer->id,
                    'plan_id' => $plan->id,
                    'seller_id' => $customer->seller_id,
                    'currency_id' => $plan->currency_id,
                    'price' => $plan->price,
                    'quantity' => $plan->billing_cycle === 'monthly' && $i % 5 === 0 ? 3 : 1,
                    'discount' => $i % 6 === 0 ? 10 : 0,
                    'start_date' => today()->subMonthsNoOverflow($monthsAgo)->subDays(mt_rand(0, 25)),
                    'trial_days' => 0,
                    'auto_renew' => true,
                ]);

                $subscription->saveCustomFields(['contract_number' => 'CT-2026-'.str_pad((string) ($subscription->id), 3, '0', STR_PAD_LEFT)]);
                $subscriptions->push($subscription);
            }
        }

        // Recent sign-ups currently in their free trial.
        foreach ($active->take(3) as $i => $customer) {
            $trialPlan = $plans->firstWhere('trial_days', '>', 0) ?? $plans->first();
            $subscriptions->push($billing->subscribe([
                'customer_id' => $customer->id,
                'plan_id' => $trialPlan->id,
                'seller_id' => $customer->seller_id,
                'currency_id' => $trialPlan->currency_id,
                'price' => $trialPlan->price,
                'start_date' => today()->subDays(3 + $i * 4),
                'trial_days' => $trialPlan->trial_days ?: 14,
            ]));
        }

        // Catch up every billing period until today.
        $billing->run(today());

        // Pay most of the historical invoices.
        Invoice::query()->with('subscription')->orderBy('issue_date')->get()->each(function (Invoice $invoice) use ($admin) {
            $age = $invoice->issue_date->diffInDays(today());
            $payable = $age > 10 ? mt_rand(1, 100) <= 95 : ($age > 3 ? mt_rand(1, 100) <= 60 : false);

            if (! $payable || $invoice->status === Invoice::STATUS_CANCELLED) {
                return;
            }

            $paidAt = $invoice->issue_date->copy()->addDays(mt_rand(0, 9));
            if ($paidAt->isFuture()) {
                $paidAt = today();
            }

            $partial = mt_rand(1, 100) <= 4;

            Payment::query()->create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'currency_id' => $invoice->currency_id,
                'amount' => $partial ? round((float) $invoice->total / 2, 2) : $invoice->total,
                'method' => Payment::METHODS[mt_rand(0, 4)],
                'status' => Payment::STATUS_COMPLETED,
                'paid_at' => $paidAt,
                'transaction_id' => 'TX'.mt_rand(100000, 999999),
                'recorded_by' => $admin?->id,
            ]);
        });

        // Some churn and pauses for realistic charts.
        $subscriptions->filter(fn ($s) => $s->status === Subscription::STATUS_ACTIVE)->values()
            ->each(function (Subscription $subscription, int $i) use ($billing) {
                if ($i % 9 === 4) {
                    $billing->cancel($subscription, true, 'Cambio a otro proveedor');
                } elseif ($i % 11 === 7) {
                    $billing->pause($subscription);
                } elseif ($i % 10 === 2) {
                    $billing->cancel($subscription, false, 'Reducción de presupuesto');
                }
            });

        // Flag what is still unpaid.
        $billing->run(today());

        // Customer portal access for the first customer.
        $portalCustomer = $active->first();
        User::query()->firstOrCreate(['email' => 'cliente@demo.com'], [
            'name' => $portalCustomer->name,
            'password' => 'password',
            'role' => User::ROLE_CUSTOMER,
            'customer_id' => $portalCustomer->id,
            'email_verified_at' => now(),
        ]);

        // Kanban board ----------------------------------------------------------------
        $columns = KanbanColumn::query()->orderBy('sort_order')->get();
        $cards = [
            ['Llamar a clientes con facturas vencidas', 'high', 0, 2],
            ['Preparar propuesta anual para Innovatech', 'medium', 0, 6],
            ['Migrar clientes del plan Básico al Profesional', 'medium', 1, 10],
            ['Configurar SMTP corporativo', 'urgent', 1, 1],
            ['Revisar comisiones del trimestre', 'low', 2, 5],
            ['Actualizar tasas de cambio', 'medium', 2, 3],
            ['Onboarding de Clínica Santa Elena', 'high', 3, -3],
            ['Publicar nuevos planes de Hosting', 'low', 3, -8],
        ];

        foreach ($cards as $i => [$title, $priority, $col, $dueIn]) {
            $column = $columns[$col] ?? $columns->first();

            if (! $column) {
                break;
            }

            KanbanCard::query()->create([
                'kanban_column_id' => $column->id,
                'title' => $title,
                'description' => 'Tarea de ejemplo creada con los datos de demostración.',
                'priority' => $priority,
                'due_date' => today()->addDays($dueIn),
                'assigned_to' => $i % 2 ? $sellerUser->id : $admin?->id,
                'customer_id' => $customers[$i]->id ?? null,
                'created_by' => $admin?->id,
                'sort_order' => $i,
            ]);
        }

        // Backdate records so growth charts look like real history.
        DB::table('subscriptions')->update(['created_at' => DB::raw('start_date'), 'updated_at' => DB::raw('start_date')]);
        DB::table('subscriptions')->whereNotNull('cancelled_at')->update(['updated_at' => DB::raw('cancelled_at')]);
        DB::table('invoices')->update(['created_at' => DB::raw('issue_date')]);
        DB::table('payments')->update(['created_at' => DB::raw('paid_at')]);
        foreach ($customers as $customer) {
            $first = DB::table('subscriptions')->where('customer_id', $customer->id)->min('start_date');
            DB::table('customers')->where('id', $customer->id)->update(['created_at' => $first ?? now()->subDays(mt_rand(1, 60))]);
        }

        config(['mail.default' => $previousMailer]);
        Mail::purge();
        ActivityLog::$enabled = $previousLogging;
    }
}
