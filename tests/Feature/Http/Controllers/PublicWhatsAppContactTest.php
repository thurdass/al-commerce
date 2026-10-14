<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicWhatsAppContactTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_contact_the_store_with_a_generic_message(): void
    {
        $store = Store::factory()->create(['name' => 'Tech Bahia', 'whatsapp' => '+55 (71) 99999-8888', 'address' => 'Endereço privado 123']);

        $response = $this->get(route('public.stores.show', ['store' => $store->slug]));

        $response->assertOk()->assertSee('Falar com a loja pelo WhatsApp')
            ->assertSee('href="'.$response->viewData('whatsappUrl').'"', false)
            ->assertSee('target="_blank" rel="noopener noreferrer"', false)
            ->assertDontSee($store->user->email)->assertDontSee($store->address);
        $this->assertSame('/5571999998888', parse_url($response->viewData('whatsappUrl'), PHP_URL_PATH));
        parse_str(parse_url($response->viewData('whatsappUrl'), PHP_URL_QUERY), $query);
        $this->assertSame(['text' => 'Olá! Vi a loja Tech Bahia pelo AL Commerce e gostaria de mais informações.'], $query);
        $this->assertSame('+55 (71) 99999-8888', $store->fresh()->whatsapp);
        $this->assertGuest();
    }

    #[TestWith([''])]
    #[TestWith(['not-a-number'])]
    public function test_store_and_product_omit_contact_when_the_phone_is_missing_or_invalid(string $phone): void
    {
        $store = Store::factory()->create(['whatsapp' => $phone]);

        $this->get(route('public.stores.show', ['store' => $store->slug]))->assertOk()
            ->assertDontSee('Falar com a loja pelo WhatsApp')->assertDontSee('https://wa.me/');

        $product = Product::factory()->for($store)->create();
        $this->get(route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]))->assertOk()
            ->assertDontSee('Falar com a loja pelo WhatsApp')->assertDontSee('https://wa.me/');
    }

    public function test_inactive_store_does_not_expose_contact_even_to_its_owner(): void
    {
        $store = Store::factory()->inactive()->create();
        $url = route('public.stores.show', ['store' => $store->slug]);

        $this->get($url)->assertNotFound()->assertDontSee('https://wa.me/');
        $this->actingAs($store->user)->get($url)->assertNotFound()->assertDontSee('https://wa.me/');
    }

    public function test_store_name_is_encoded_without_injecting_link_attributes(): void
    {
        $name = '<script>alert("store")</script> & Loja';
        $store = Store::factory()->create(['name' => $name]);

        $response = $this->get(route('public.stores.show', ['store' => $store->slug]));

        $response->assertOk()->assertSee($name)->assertDontSee($name, false);
        parse_str(parse_url($response->viewData('whatsappUrl'), PHP_URL_QUERY), $query);
        $this->assertSame(['text' => 'Olá! Vi a loja '.$name.' pelo AL Commerce e gostaria de mais informações.'], $query);
    }

    public function test_product_contact_contains_the_product_store_and_canonical_public_url(): void
    {
        $store = Store::factory()->create(['name' => 'Tech Bahia & Cia', 'slug' => 'tech-bahia', 'whatsapp' => '5571999998888']);
        $product = Product::factory()->for($store)->create(['name' => 'Mouse G305 & Edição "A+"', 'slug' => 'mouse-g305']);
        $url = route('public.products.show', ['store' => 'tech-bahia', 'product' => 'mouse-g305']);

        $response = $this->get($url.'?whatsapp=5511988887777&text=outra-mensagem');

        $response->assertOk()->assertSee('Falar com a loja pelo WhatsApp')
            ->assertSee('href="'.$response->viewData('whatsappUrl').'"', false)
            ->assertDontSee('5511988887777')->assertDontSee('outra-mensagem')
            ->assertDontSee($store->user->email)->assertDontSee(route('products.edit', $product));
        $this->assertSame('/5571999998888', parse_url($response->viewData('whatsappUrl'), PHP_URL_PATH));
        parse_str(parse_url($response->viewData('whatsappUrl'), PHP_URL_QUERY), $query);
        $this->assertSame(['text' => 'Olá! Vi o produto Mouse G305 & Edição "A+" na loja Tech Bahia & Cia pelo AL Commerce e gostaria de saber se ainda está disponível.'."\n".$url], $query);
        $this->assertGuest();
    }

    public function test_products_with_the_same_slug_use_their_own_store_contact(): void
    {
        $first = Product::factory()->for(Store::factory()->state(['whatsapp' => '5571999998888']))->create(['slug' => 'mouse']);
        $second = Product::factory()->for(Store::factory()->state(['whatsapp' => '5511988887777']))->create(['slug' => 'mouse']);

        $this->get(route('public.products.show', ['store' => $first->store->slug, 'product' => 'mouse']))->assertOk()
            ->assertSee('https://wa.me/5571999998888?text=')->assertDontSee('5511988887777');
        $this->get(route('public.products.show', ['store' => $second->store->slug, 'product' => 'mouse']))->assertOk()
            ->assertSee('https://wa.me/5511988887777?text=')->assertDontSee('5571999998888');
    }
}
