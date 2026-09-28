<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Structural guard: every page must actually deliver a full HTML document
 * with the compiled stylesheet attached.
 *
 * A view that forgets its layout wrapper still passes content assertions,
 * because the content is all there - it just arrives with no <head>, no
 *
 * @vite and therefore no CSS. Only the document itself reveals it.
 */
class LayoutWiringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public static function customerPages(): array
    {
        return [
            'home' => ['/'],
            'menu' => ['/menu'],
            'menu coffee' => ['/menu?category=Coffee'],
            'menu food' => ['/menu?category=Food'],
            'cart' => ['/cart'],
            'confirmation' => ['/cart/confirmation'],
        ];
    }

    public static function guestPages(): array
    {
        return [
            'login' => ['/login'],
        ];
    }

    public static function staffPages(): array
    {
        return [
            'staff dashboard' => ['/staff/dashboard'],
            'staff orders' => ['/staff/orders'],
        ];
    }

    public static function adminPages(): array
    {
        return [
            'admin dashboard' => ['/admin/dashboard'],
            'admin orders' => ['/admin/orders'],
            'admin products' => ['/admin/products'],
            'admin product create' => ['/admin/products/create'],
            'admin users' => ['/admin/users'],
        ];
    }

    /**
     * @dataProvider customerPages
     */
    public function test_customer_pages_deliver_a_styled_document(string $path): void
    {
        $this->assertDocumentIsWired($this->get($path));
    }

    /**
     * @dataProvider guestPages
     */
    public function test_guest_pages_deliver_a_styled_document(string $path): void
    {
        $this->assertDocumentIsWired($this->get($path));
    }

    /**
     * The branded 403 is a design surface too, so it must be styled: a Staff
     * account reaching for the admin area gets the Access Denied sheet.
     */
    public function test_access_denied_page_is_styled(): void
    {
        $response = $this->actingAs(
            User::factory()->staff()->create()
        )->get('/admin/dashboard');

        $response->assertForbidden();
        $this->assertDocumentIsWired($response, 403);
    }

    /**
     * @dataProvider staffPages
     */
    public function test_staff_pages_deliver_a_styled_document(string $path): void
    {
        $this->assertDocumentIsWired($this->actingAs(
            User::factory()->staff()->create()
        )->get($path));
    }

    /**
     * @dataProvider adminPages
     */
    public function test_admin_pages_deliver_a_styled_document(string $path): void
    {
        $this->assertDocumentIsWired($this->actingAs(
            User::factory()->admin()->create()
        )->get($path));
    }

    /**
     * The layout wrapper is not optional: without it a page renders its
     * content with no stylesheet at all.
     */
    private function assertDocumentIsWired($response, int $status = 200): void
    {
        $response->assertStatus($status);

        $html = $response->getContent();

        $this->assertStringStartsWith('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</html>', $html);

        // The compiled stylesheet, as emitted by @vite.
        $this->assertMatchesRegularExpression(
            '#<link[^>]+rel="stylesheet"[^>]+href="[^"]*app-[^"]*\.css"#i',
            $html,
            'The page does not reference the compiled CSS bundle.'
        );
    }
}
