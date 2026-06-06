<?php

declare(strict_types=1);

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReportCatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
