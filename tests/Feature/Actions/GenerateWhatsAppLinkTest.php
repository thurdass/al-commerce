<?php

namespace Tests\Feature\Actions;

use App\Actions\GenerateWhatsAppLink;
use App\Models\Store;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GenerateWhatsAppLinkTest extends TestCase
{
    #[TestWith(['5571999998888', '5571999998888'])]
    #[TestWith([' +55 (71) 99999-8888 ', '5571999998888'])]
    #[TestWith(['55.71.99999.8888', '5571999998888'])]
    #[TestWith(['+1 (202) 555-0123', '12025550123'])]
    public function test_normalizes_international_numbers_and_encodes_the_store_message(string $input, string $phone): void
    {
        $store = Store::factory()->make(['user_id' => 1, 'whatsapp' => $input, 'name' => 'Tech & Ação + 100%']);

        $link = (new GenerateWhatsAppLink)->handle($store);

        $this->assertSame('https', parse_url($link, PHP_URL_SCHEME));
        $this->assertSame('wa.me', parse_url($link, PHP_URL_HOST));
        $this->assertSame('/'.$phone, parse_url($link, PHP_URL_PATH));
        parse_str(parse_url($link, PHP_URL_QUERY), $query);
        $this->assertSame(['text' => 'Olá! Vi a loja Tech & Ação + 100% pelo AL Commerce e gostaria de mais informações.'], $query);
        $this->assertSame($input, $store->whatsapp);
    }

    #[TestWith([null])]
    #[TestWith([''])]
    #[TestWith(['   '])]
    #[TestWith(['+().-'])]
    #[TestWith(['123456789'])]
    #[TestWith(['1234567890123456'])]
    #[TestWith(['0000000000000'])]
    #[TestWith(['005571999998888'])]
    #[TestWith(['55+71999998888'])]
    #[TestWith(['+55 (71) 99999-8888 ramal 123'])]
    #[TestWith(['https://wa.me/5571999998888'])]
    #[TestWith(['5571999998888?text=other'])]
    #[TestWith(['5571999998888" onclick="alert(1)'])]
    public function test_invalid_or_missing_numbers_do_not_generate_a_link(?string $phone): void
    {
        $store = Store::factory()->make(['user_id' => 1, 'whatsapp' => $phone]);

        $this->assertNull((new GenerateWhatsAppLink)->handle($store));
    }
}
