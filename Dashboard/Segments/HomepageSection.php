<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments;

abstract class HomepageSection implements HomepageSectionInterface
{
    private $collection = [];

    public abstract static function getTitle() : string;
    public abstract static function getDescription() : string;

    public static function getRoles() : array
    {
        return [];
    }

    public function appendItem(HomepageSectionItemInterface $item) : HomepageSectionInterface
    {
        $this->collection[] = $item;

        return $this;
    }

    public function getItemCollection() : array
    {
        return $this->collection;
    }
}
