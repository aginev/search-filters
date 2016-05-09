<?php

namespace Aginev\SearchFilters;

trait Filterable
{
    /**
     * @var Filter
     */
    public $filter;

    /**
     * Filter scope
     *
     * @param $query
     * @param $request
     * @return mixed
     */
    public function scopeFilter($query, $request)
    {
        // Create ne filter object
        $this->filter = new Filter($query, $request);

        // Set the filters if any defined
        $this->setFilters();

        // Call order always after the all filters and if there are no custom filters applied
        $this->filter->order();

        // Object are always passed by reference
        // http://php.net/manual/en/language.oop5.references.php
        return $query;
    }

    /**
     * Set query filters
     *
     * Overwrite this method in the model to set query filters
     */
    public function setFilters()
    {
        //
    }
}