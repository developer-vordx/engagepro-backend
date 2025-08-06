<?php

namespace App\Utils;

class BaseDTO
{
    /**
     * @return array
     */
    public function toArray(): array
    {
        return (array)$this;
    }
}
