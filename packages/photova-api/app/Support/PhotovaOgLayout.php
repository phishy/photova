<?php

namespace App\Support;

use SimonHamp\TheOg\Layout\Layouts\Standard;

class PhotovaOgLayout extends Standard
{
    public function url(): string
    {
        return $this->config->url ?? '';
    }
}
