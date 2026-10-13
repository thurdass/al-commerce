<?php

namespace App;

enum ProductAvailabilityStatus: string
{
    case Available = 'AVAILABLE';
    case LowStock = 'LOW_STOCK';
    case OutOfStock = 'OUT_OF_STOCK';
    case Inactive = 'INACTIVE';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponível',
            self::LowStock => 'Estoque baixo',
            self::OutOfStock => 'Sem estoque',
            self::Inactive => 'Indisponível',
        };
    }
}
