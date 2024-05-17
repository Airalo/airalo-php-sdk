<?php

namespace Airalo\DTOs\Traits;

trait HasImage
{

    public function getImageUrl()
    {
        return $this->image ? $this->image->url : null;
    }

    public function getImageWidth()
    {
        return $this->image ? $this->image->width : null;
    }

    public function getImageHeight()
    {
        return $this->image ? $this->image->height : null;
    }
}
