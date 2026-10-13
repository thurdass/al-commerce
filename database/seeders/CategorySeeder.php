<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'alimentos-e-bebidas' => 'Alimentos e bebidas',
            'beleza-e-cuidados-pessoais' => 'Beleza e cuidados pessoais',
            'casa-e-decoracao' => 'Casa e decoração',
            'eletronicos-e-informatica' => 'Eletrônicos e informática',
            'esporte-e-lazer' => 'Esporte e lazer',
            'moda-e-acessorios' => 'Moda e acessórios',
            'pet-shop' => 'Pet shop',
            'outros' => 'Outros',
        ] as $slug => $name) {
            Category::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
