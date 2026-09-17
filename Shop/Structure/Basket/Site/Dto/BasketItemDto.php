<?php

declare(strict_types=1);

namespace Shop\Structure\Basket\Site\Dto;

class BasketItemDto
{
    public function __construct(
        public string $id,
        public int $count,
    ) {}
}
