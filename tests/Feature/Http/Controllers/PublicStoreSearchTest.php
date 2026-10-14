<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicStoreSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_partial_name_and_location_filters_combine_and_exclude_inactive_stores(): void
    {
        $match = Store::factory()->create(['name' => 'Tech Bahia']);
        Store::factory()->create(['name' => 'Mercado Bahia']);
        Store::factory()->create(['name' => 'Tech Feira', 'city' => 'Feira de Santana']);
        Store::factory()->create(['name' => 'Tech Outra UF', 'state' => 'SP']);
        Store::factory()->inactive()->create(['name' => 'Tech Oculta']);

        $response = $this->get(route('home', ['q' => ' tech ', 'cidade' => ' salv ', 'estado' => 'ba']));

        $response->assertOk()->assertSee('Tech Bahia')->assertSee('value="tech"', false)
            ->assertSee('value="salv"', false)->assertSee('value="BA"', false)
            ->assertDontSee('Lojas em destaque');
        $this->assertSame([$match->id], $response->viewData('stores')->pluck('id')->all());
    }

    #[TestWith(['%'])]
    #[TestWith(['_'])]
    #[TestWith(['!'])]
    #[TestWith(["' OR 1=1 --"])]
    #[TestWith(['0'])]
    public function test_search_treats_special_characters_as_literal_text(string $term): void
    {
        $match = Store::factory()->create(['name' => 'Loja '.$term.' Local']);
        Store::factory()->create(['name' => 'Loja comum']);

        $response = $this->get(route('home', ['q' => $term]));

        $response->assertOk();
        $this->assertSame([$match->id], $response->viewData('stores')->pluck('id')->all());
    }

    public function test_filtered_pagination_keeps_only_validated_filters_and_sorts_newest_first(): void
    {
        $stores = Store::factory()->count(13)->create(['name' => 'Tech Bahia']);
        Store::factory()->inactive()->create(['name' => 'Tech Oculta']);
        $filters = ['q' => 'Tech', 'cidade' => 'Salvador', 'estado' => 'BA', 'ordenar' => 'recentes'];

        $response = $this->get(route('home', $filters + ['user_id' => 999]));
        $response->assertOk()->assertSee('Página 1 de 2 (13 lojas)');
        $paginator = $response->viewData('stores');
        $this->assertSame($stores->reverse()->take(12)->pluck('id')->all(), $paginator->pluck('id')->all());
        parse_str(parse_url($paginator->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertEquals($filters + ['page' => '2'], $query);
        $this->get($paginator->nextPageUrl())->assertOk()->assertViewHas('stores',
            fn ($page) => $page->pluck('id')->all() === [$stores->first()->id]);
    }

    public function test_filtered_empty_state_escapes_the_search_input(): void
    {
        $term = '<script>alert(1)</script>';

        $this->get(route('home', ['q' => $term]))->assertOk()
            ->assertSee('Nenhuma loja encontrada com os filtros informados.')
            ->assertSee($term)->assertDontSee($term, false)->assertSee('Limpar filtros');
    }

    #[TestWith(['q', ['invalid'], 'Informe um termo de busca válido.'])]
    #[TestWith(['cidade', ['invalid'], 'Informe uma cidade válida.'])]
    #[TestWith(['estado', 'XX', 'Informe uma UF válida.'])]
    #[TestWith(['ordenar', 'password', 'Escolha uma ordenação válida.'])]
    public function test_invalid_filters_redirect_to_a_clean_url_without_a_loop(string $field, mixed $value, string $message): void
    {
        $url = route('home', [$field => $value]);

        $this->from($url)->get($url)->assertRedirect(route('home'))
            ->assertSessionHasErrors([$field => $message]);
        $this->get(route('home'))->assertOk();
    }

    public function test_overlong_search_terms_are_rejected(): void
    {
        $this->get(route('home', ['q' => str_repeat('a', 256), 'cidade' => str_repeat('b', 256)]))
            ->assertRedirect(route('home'))->assertSessionHasErrors(['q', 'cidade']);
    }
}
