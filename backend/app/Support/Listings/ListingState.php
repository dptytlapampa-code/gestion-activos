<?php

namespace App\Support\Listings;

use Illuminate\Http\Request;

readonly class ListingState
{
    public const DEFAULT_PER_PAGE = 20;

    private const ALLOWED_PER_PAGE = [5, 20, 50, 100];

    public function __construct(
        public string $search,
        public int $perPage,
    ) {}

    public static function fromRequest(
        Request $request,
        string $searchKey = 'search',
        string $perPageKey = 'per_page',
    ): self {
        $rawSearch = $request->query($searchKey, '');
        $rawPerPage = $request->query($perPageKey, self::DEFAULT_PER_PAGE);

        $search = is_scalar($rawSearch) ? trim((string) $rawSearch) : '';
        $perPage = is_scalar($rawPerPage) ? (int) $rawPerPage : self::DEFAULT_PER_PAGE;

        if (! in_array($perPage, self::ALLOWED_PER_PAGE, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        return new self($search, $perPage);
    }

    /**
     * @return array<int, int>
     */
    public static function perPageOptions(): array
    {
        return self::ALLOWED_PER_PAGE;
    }

    public function hasSearch(): bool
    {
        return $this->search !== '';
    }
}
