<?php

namespace App\Traits;

trait ResourceHasRedirectUrl
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
