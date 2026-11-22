<?php

namespace Tests\Feature\Settings;

use App\Models\ApplicationSetting;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FrontendAppearanceSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_appearance_settings_are_unavailable_to_guests_and_users(): void
    {
        $this->get('/settings/frontend-appearance')->assertNotFound();
        $this->put('/settings/frontend-appearance', ['mode' => 'dark'])->assertNotFound();
        $this->actingAs(User::factory()->create());
        $this->get('/settings/frontend-appearance')->assertNotFound();
        $this->put('/settings/frontend-appearance', ['mode' => 'dark'])->assertNotFound();
    }

    public function test_settings_page_is_unavailable_without_the_public_site(): void
    {
        config(['starter.features.public_site' => false]);
        $this->actingAs($this->administrator())->get('/settings/frontend-appearance')->assertNotFound();
    }

    public function test_administrators_cannot_enable_a_public_dark_mode(): void
    {
        $this->actingAs($this->administrator())->put('/settings/frontend-appearance', ['mode' => 'dark'])->assertNotFound();
        $this->assertDatabaseMissing('application_settings', ['key' => 'appearance.frontend']);
    }

    public function test_visitor_cookie_cannot_enable_dark_mode(): void
    {
        $this->withUnencryptedCookie('frontend_appearance', 'dark')->get(route('home'))
            ->assertOk()->assertSee('name="color-scheme" content="only light"', false)
            ->assertDontSee('prefers-color-scheme: dark')->assertInertia(fn (Assert $page) => $page->missing('frontendAppearance'));
    }

    /** @return array<string, array{string}> */
    public static function storedModes(): array
    {
        return ['system' => ['system'], 'dark' => ['dark'], 'light' => ['light'], 'toggle' => ['toggle']];
    }

    #[DataProvider('storedModes')]
    public function test_legacy_settings_leave_public_pages_light(string $mode): void
    {
        ApplicationSetting::create(['key' => 'appearance.frontend', 'value' => json_encode(['mode' => $mode])]);
        foreach (['home', 'families.index', 'login'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('name="color-scheme" content="only light"', false)
                ->assertDontSee('data-appearance=')->assertDontSee('prefers-color-scheme: dark');
        }
    }

    public function test_forced_mode_does_not_change_the_dashboard_palette(): void
    {
        ApplicationSetting::create(['key' => 'appearance.frontend', 'value' => '{"mode":"dark"}']);
        $user = User::factory()->create(['appearance' => 'dark', 'admin_theme' => 'forest']);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('data-admin-theme="forest"', false)->assertSee('name="color-scheme" content="only light"', false);
    }

    public function test_invalid_stored_mode_is_ignored(): void
    {
        ApplicationSetting::create(['key' => 'appearance.frontend', 'value' => '{"mode":"sepia"}']);
        $this->get(route('home'))->assertOk()->assertSee('name="color-scheme" content="only light"', false);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate(RolePolicy::ADMINISTRATOR_ROLE, 'web'));

        return $user;
    }
}
