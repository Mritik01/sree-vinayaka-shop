<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;

trait PaginatesAdminLists
{
    private static array $perPageOptions = [10, 25, 50, 100];

    private function perPage(Request $request, int $default = 10): int
    {
        $value = (int) $request->get('per_page', $default);

        return in_array($value, self::$perPageOptions, true) ? $value : $default;
    }
}
