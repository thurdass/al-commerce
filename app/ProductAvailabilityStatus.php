<?php

namespace App;

enum ProductAvailabilityStatus: string
{
    case Available = 'AVAILABLE';
    case LowStock = 'LOW_STOCK';
    case OutOfStock = 'OUT_OF_STOCK';
    case Inactive = 'INACTIVE';
}
