<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBillingData;
use Tests\TestCase;

class CustomFieldTest extends TestCase
{
    use BuildsBillingData, RefreshDatabase;

    public function test_admin_creates_a_field_and_it_is_saved_with_customers(): void
    {
        $this->seedBaseData();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.custom-fields.store'), [
            'entity' => 'customer',
            'label' => 'Industry sector',
            'type' => 'select',
            'options_text' => "Retail\nHealth\nEducation",
            'is_required' => '1',
            'show_in_table' => '1',
            'is_active' => '1',
        ])->assertRedirect(route('admin.custom-fields.index'));

        $field = CustomField::query()->sole();
        $this->assertSame('industry_sector', $field->name);
        $this->assertSame(['Retail', 'Health', 'Education'], $field->options);

        // Required + must be one of the options.
        $this->actingAs($admin)->post(route('customers.store'), [
            'name' => 'Linus', 'status' => 'active', 'custom_fields' => ['industry_sector' => 'Mining'],
        ])->assertSessionHasErrors('custom_fields.industry_sector');

        $this->actingAs($admin)->post(route('customers.store'), [
            'name' => 'Linus', 'status' => 'active', 'custom_fields' => ['industry_sector' => 'Health'],
        ])->assertRedirect();

        $customer = Customer::query()->where('name', 'Linus')->sole();
        $this->assertSame('Health', $customer->customFieldValue('industry_sector'));

        $this->actingAs($admin)->get(route('customers.index'))->assertSee('Industry sector')->assertSee('Health');
        $this->actingAs($admin)->get(route('customers.edit', $customer))->assertSee('Industry sector');
    }

    public function test_checkbox_and_number_fields(): void
    {
        $customer = $this->makeCustomer();
        CustomField::query()->create(['entity' => 'customer', 'name' => 'vip', 'label' => 'VIP', 'type' => 'checkbox', 'is_active' => true]);
        CustomField::query()->create(['entity' => 'customer', 'name' => 'employees', 'label' => 'Employees', 'type' => 'number', 'is_active' => true]);

        $customer->saveCustomFields(['vip' => '1', 'employees' => '42']);

        $this->assertSame('1', $customer->customFieldValue('vip'));
        $this->assertSame('42', $customer->customFieldValue('employees'));
        $this->assertSame('Yes', CustomField::query()->where('name', 'vip')->first()->displayValue('1'));

        $customer->forceDelete();
        $this->assertSame(0, CustomFieldValue::query()->count());
    }
}
