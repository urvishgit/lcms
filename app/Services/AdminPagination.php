<?php
namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;

class AdminPagination implements AdminPaginationInterface
{

    protected $totalRecordsPerPage = null;

    public function __construct()
    {
        $this->totalRecordsPerPage = env('ADMIN_TOTAL_NO_RECORDS_PER_PAGE');
    }

	/**
     * Admin pagination Service
     *
     */
    public function pagination(object $data, string $path, int $pageNo)
    {
    	if(!$data || !$path || !$pageNo) return;

        $totalCount = $data->count();

        $skip = $pageNo ? $this->totalRecordsPerPage * ($pageNo - 1) : 0;
        
        $data = $data->slice($skip)->take($this->totalRecordsPerPage);

        $paginator = new LengthAwarePaginator($data, $totalCount, $this->totalRecordsPerPage, $pageNo);

        $data = $paginator->withPath($path);

        return $data;

    }
}