<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait SearchPaginationTrait
{
    /**
     * Enhance a paginator instance with base path and query parameters.
     *
     * @param LengthAwarePaginator $paginator
     * @return LengthAwarePaginator
     */
    protected function setupPaginator(LengthAwarePaginator $paginator)
    {
        // Set the base path to the current URI without query string
        $paginator->setPath(base_url($this->request->getPath()));

        // Get all GET parameters and exclude 'page' to avoid duplication
        $getParams = $this->request->getGet();
        unset($getParams['page']);

        // Append the remaining GET parameters (e.g., 'search')
        $paginator->appends($getParams);

        return $paginator;
    }

    /**
     * Override paginate to include setup logic.
     *
     * @param mixed $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    protected function searchPaginateQuery($query, $perPage = 10)
    {
        // Perform pagination
        $paginator = $query->paginate($perPage);

        // Apply the setup logic
        return $this->setupPaginator($paginator);
    }
}