<?php

namespace App\Services;

interface AdminPaginationInterface
{
    public function pagination(object $data, string $path, int $pageNo);
}