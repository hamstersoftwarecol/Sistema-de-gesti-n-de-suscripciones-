<?php

namespace App\Support;

use App\Models\User;

/**
 * Sidebar menu of the back office, filtered by the user's role.
 */
class Navigation
{
    /**
     * @return array<int, array{section: ?string, items: array<int, array{label: string, route: string, icon: string, active: string}>}>
     */
    public static function for(User $user): array
    {
        $staff = $user->isStaff();
        $admin = $user->isAdmin();
        $seller = $user->isSeller();

        $sections = [
            [
                'section' => null,
                'items' => [
                    self::item(__('Dashboard'), 'dashboard', 'home', 'dashboard'),
                    $staff ? self::item(__('AI assistant'), 'ai.index', 'sparkles', 'ai.*') : null,
                    self::item(__('Calendar'), 'calendar.index', 'calendar', 'calendar.*'),
                    self::item(__('Kanban board'), 'kanban.index', 'kanban', 'kanban.*'),
                ],
            ],
            [
                'section' => __('Sales'),
                'items' => [
                    self::item(__('Customers'), 'customers.index', 'users', 'customers.*'),
                    self::item(__('Subscriptions'), 'subscriptions.index', 'refresh', 'subscriptions.*'),
                    self::item(__('Invoices'), 'invoices.index', 'document', 'invoices.*'),
                    self::item(__('Payments'), 'payments.index', 'banknotes', 'payments.*'),
                    $seller ? self::item(__('My commissions'), 'sellers.me', 'chart', 'sellers.me') : null,
                ],
            ],
            $staff ? [
                'section' => __('Catalog'),
                'items' => [
                    self::item(__('Products & plans'), 'products.index', 'cube', 'products.*'),
                    self::item(__('Categories'), 'categories.index', 'tag', 'categories.*'),
                    self::item(__('Suppliers'), 'suppliers.index', 'truck', 'suppliers.*'),
                ],
            ] : null,
            $staff ? [
                'section' => __('Team'),
                'items' => [
                    self::item(__('Sellers'), 'sellers.index', 'briefcase', 'sellers.index|sellers.create|sellers.edit|sellers.show'),
                    $admin ? self::item(__('Users'), 'admin.users.index', 'user', 'admin.users.*') : null,
                ],
            ] : null,
            $staff ? [
                'section' => __('Administration'),
                'items' => [
                    $admin ? self::item(__('Settings'), 'admin.settings.edit', 'cog', 'admin.settings.*') : null,
                    self::item(__('Currencies'), 'currencies.index', 'currency', 'currencies.*'),
                    $admin ? self::item(__('Email templates'), 'admin.email-templates.index', 'envelope', 'admin.email-templates.*') : null,
                    self::item(__('Email log'), 'email-logs.index', 'bell', 'email-logs.*'),
                    $admin ? self::item(__('Custom fields'), 'admin.custom-fields.index', 'puzzle', 'admin.custom-fields.*') : null,
                    $admin ? self::item(__('Backups'), 'admin.backups.index', 'database', 'admin.backups.*') : null,
                    $admin ? self::item(__('Activity log'), 'admin.activity.index', 'clipboard', 'admin.activity.*') : null,
                ],
            ] : null,
        ];

        return collect($sections)
            ->filter()
            ->map(fn ($section) => [
                'section' => $section['section'],
                'items' => array_values(array_filter($section['items'])),
            ])
            ->filter(fn ($section) => $section['items'] !== [])
            ->values()
            ->all();
    }

    protected static function item(string $label, string $route, string $icon, string $active): array
    {
        return compact('label', 'route', 'icon', 'active');
    }

    public static function isActive(string $patterns): bool
    {
        return request()->routeIs(...explode('|', $patterns));
    }
}
