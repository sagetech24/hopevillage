<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdministratorUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdministratorUsersIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_mobile_number_and_human_readable_created_date(): void
    {
        $this->actingAsSuperadmin();

        $admin = User::factory()->create([
            'name' => 'Jane Admin',
            'email' => 'jane.admin@example.com',
            'user_type' => 'admin',
            'whatsapp_number' => '+6591110001',
            'created_at' => now()->setDate(2026, 3, 15)->setTime(9, 30),
        ]);

        Livewire::test(AdministratorUsers::class)
            ->assertSee('Mobile Number')
            ->assertSee('Created Date')
            ->assertSee($admin->whatsapp_number)
            ->assertSee('15 Mar 2026')
            ->assertDontSee($admin->created_at->format('Y-m-d'))
            ->assertSeeHtml('aria-label="Actions"')
            ->assertSeeHtml('openResetPasswordModal('.$admin->id.')');
    }

    public function test_can_search_admin_users_by_name_email_and_mobile_number(): void
    {
        $this->actingAsSuperadmin();

        $byName = User::factory()->create([
            'name' => 'Alice Wong',
            'email' => 'alice.wong@example.com',
            'user_type' => 'admin',
            'whatsapp_number' => '+6591110002',
        ]);
        $byEmail = User::factory()->create([
            'name' => 'Bob Tan',
            'email' => 'bob.tan@example.com',
            'user_type' => 'admin',
            'whatsapp_number' => '+6591110003',
        ]);
        $byMobile = User::factory()->create([
            'name' => 'Carla Lim',
            'email' => 'carla.lim@example.com',
            'user_type' => 'admin',
            'whatsapp_number' => '+6598765432',
        ]);

        Livewire::test(AdministratorUsers::class)
            ->set('search', 'Alice')
            ->assertSee($byName->name)
            ->assertDontSee($byEmail->name)
            ->assertDontSee($byMobile->name)
            ->set('search', 'bob.tan@')
            ->assertSee($byEmail->email)
            ->assertDontSee($byName->name)
            ->assertDontSee($byMobile->name)
            ->set('search', '98765432')
            ->assertSee($byMobile->name)
            ->assertDontSee($byName->name)
            ->assertDontSee($byEmail->name);
    }

    public function test_paginates_admin_users_eight_per_page(): void
    {
        $this->actingAsSuperadmin();

        foreach (range(1, 9) as $i) {
            User::factory()->create([
                'name' => sprintf('Admin User %02d', $i),
                'email' => "admin{$i}@example.com",
                'user_type' => 'admin',
                'whatsapp_number' => '+65920000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $pageOne = Livewire::test(AdministratorUsers::class);
        $this->assertSame(8, $pageOne->viewData('adminUsers')->count());
        $this->assertSame(10, $pageOne->viewData('adminUsers')->total());

        $pageTwo = Livewire::withQueryParams(['page' => 2])
            ->test(AdministratorUsers::class);
        $this->assertSame(2, $pageTwo->viewData('adminUsers')->count());
    }

    public function test_reset_password_opens_as_a_modal_without_leaving_the_list(): void
    {
        $this->actingAsSuperadmin();

        $admin = User::factory()->create([
            'name' => 'Jane Admin',
            'email' => 'jane.admin@example.com',
            'user_type' => 'admin',
            'whatsapp_number' => '+6591110008',
        ]);

        Livewire::test(AdministratorUsers::class)
            ->call('openResetPasswordModal', $admin->id)
            ->assertSet('showPasswordReset', true)
            ->assertSee('Mobile Number')
            ->assertSee($admin->name)
            ->assertSee('You are about to reset the password of')
            ->assertSeeHtml('jetstream-modal')
            ->call('cancelPasswordReset')
            ->assertSet('showPasswordReset', false)
            ->assertSee('Mobile Number');
    }

    public function test_can_remove_admin_as_ordinary_member(): void
    {
        $this->actingAsSuperadmin();

        $admin = User::factory()->create([
            'name' => 'Jane Admin',
            'email' => 'jane.admin@example.com',
            'user_type' => 'admin',
            'whatsapp_number' => '+6591110010',
        ]);

        Livewire::test(AdministratorUsers::class)
            ->call('removeAsAdmin', $admin->id)
            ->assertSee('Administrator removed. The user is now an ordinary member.');

        $this->assertSame('member', $admin->fresh()->user_type);
    }

    public function test_can_convert_admin_to_merchant_user(): void
    {
        $this->actingAsSuperadmin();

        $admin = User::factory()->create([
            'name' => 'Jane Admin',
            'email' => 'jane.admin@example.com',
            'user_type' => 'admin',
            'whatsapp_number' => '+6591110011',
        ]);

        Livewire::test(AdministratorUsers::class)
            ->call('convertToMerchantUser', $admin->id)
            ->assertSee('Administrator converted to a merchant user.');

        $this->assertSame('merchant_user', $admin->fresh()->user_type);
    }

    public function test_cannot_convert_own_account_or_superadmin(): void
    {
        $superAdmin = $this->actingAsSuperadmin();

        $otherSuperAdmin = User::factory()->create([
            'name' => 'Other Super Admin',
            'email' => User::superAdminEmails()[1],
            'user_type' => 'admin',
            'whatsapp_number' => '+6591110012',
        ]);

        Livewire::test(AdministratorUsers::class)
            ->call('removeAsAdmin', $superAdmin->id)
            ->assertSee('This administrator cannot be converted.')
            ->call('convertToMerchantUser', $otherSuperAdmin->id)
            ->assertSee('This administrator cannot be converted.');

        $this->assertSame('admin', $superAdmin->fresh()->user_type);
        $this->assertSame('admin', $otherSuperAdmin->fresh()->user_type);
    }

    private function actingAsSuperadmin(): User
    {
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => User::superAdminEmails()[0],
            'user_type' => 'admin',
            'whatsapp_number' => '+6584533959',
        ]);

        $this->actingAs($superAdmin);

        return $superAdmin;
    }
}
