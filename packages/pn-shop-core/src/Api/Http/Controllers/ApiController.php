<?php

namespace PnShop\Api\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\Request;

/**
 * Base of the Store and Admin API controllers: list endpoints use cursor pagination with
 * ?per_page= (1-100) and ?cursor=, and answer {data, links: {next, prev}, meta}.
 */
abstract class ApiController
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 100;

    /**
     * The signed-in customer (Store API).
     */
    protected function customer(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    protected function perPage(Request $request): int
    {
        return max(1, min(self::MAX_PER_PAGE, $request->integer('per_page', self::DEFAULT_PER_PAGE)));
    }

    /**
     * @template TItem
     *
     * @param  CursorPaginator<array-key, TItem>  $paginator
     * @param  callable(TItem): mixed  $map
     * @return array{data: list<mixed>, links: array{next: string|null, prev: string|null}, meta: array{per_page: int, next_cursor: string|null, prev_cursor: string|null}}
     */
    protected function paginated(CursorPaginator $paginator, callable $map): array
    {
        return [
            'data' => array_values(array_map($map, $paginator->items())),
            'links' => [
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
            'meta' => [
                'per_page' => $paginator->perPage(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ],
        ];
    }

    /**
     * Sort direction from ?sort=<column> or ?sort=-<column> (descending), limited to the
     * given columns. Returns [column, direction].
     *
     * @param  list<string>  $allowed
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    protected function sort(Request $request, array $allowed, string $default): array
    {
        $sort = $request->string('sort', $default)->toString();
        $column = ltrim($sort, '-');

        if (! in_array($column, $allowed, true)) {
            abort(400, __('Unknown sort ":sort". Allowed: :allowed.', ['sort' => $sort, 'allowed' => implode(', ', $allowed)]));
        }

        return [$column, str_starts_with($sort, '-') ? 'desc' : 'asc'];
    }
}
