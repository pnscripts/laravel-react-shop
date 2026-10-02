<?php

namespace PnShop\Api\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PnShop\Cms\Blocks\BlockRegistry;
use PnShop\Cms\Blocks\BlockType;
use PnShop\Cms\Models\Page;
use PnShop\Cms\PageRevisions;
use PnShop\Cms\PageStatus;
use PnShop\Localization\Localization;
use PnShop\Localization\Models\Language;

/**
 * CMS pages with their content blocks. `blocks` holds each language's blocks
 * ({"en": [{"type": "rich_text", "data": {...}}], "bg": [...]}); sending a language replaces
 * its blocks. Every save records a revision, as in the admin panel.
 */
class PageController extends AdminController
{
    public function __construct(private BlockRegistry $blocks, private PageRevisions $revisions) {}

    /**
     * List pages
     *
     * Filters: `filter[status]`, `filter[updated_since]`.
     *
     * @return array<string, mixed>
     */
    public function index(Request $request): array
    {
        Gate::authorize('viewAny', Page::class);

        $pages = $this->updatedSince(Page::query()->with('allTranslations'), $request)
            ->when($request->filled('filter.status'), fn ($query) => $query->where('status', $request->string('filter.status')->toString()))
            ->orderBy('id')
            ->cursorPaginate($this->perPage($request))
            ->withQueryString();

        return $this->paginated($pages, fn (Page $page) => $this->present($page, withBlocks: false));
    }

    /**
     * Show a page
     *
     * @return array<string, mixed>
     */
    public function show(Page $page): array
    {
        Gate::authorize('view', $page);

        return ['data' => $this->present($page)];
    }

    /**
     * Create a page
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Page::class);

        $page = new Page(['author_id' => $this->admin($request)->id]);

        return response()->json(['data' => $this->present($this->save($request, $page))], 201);
    }

    /**
     * Update a page
     *
     * @return array<string, mixed>
     */
    public function update(Request $request, Page $page): array
    {
        Gate::authorize('update', $page);

        return ['data' => $this->present($this->save($request, $page))];
    }

    /**
     * Delete a page
     */
    public function destroy(Page $page): JsonResponse
    {
        Gate::authorize('delete', $page);

        $page->delete();

        return response()->json(null, 204);
    }

    private function save(Request $request, Page $page): Page
    {
        $locales = $this->locales();
        $sometimes = $page->exists ? ['sometimes'] : [];

        $data = $request->validate([
            'title' => [...$sometimes, 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('pages', 'slug')->ignore($page->id)],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::enum(PageStatus::class)],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'unpublished_at' => ['sometimes', 'nullable', 'date', 'after:published_at'],
            'is_home' => ['sometimes', 'boolean'],
            'blocks' => ['sometimes', 'array:'.implode(',', $locales)],
            'blocks.*' => ['array'],
            'blocks.*.*.type' => ['required', 'string', Rule::in(array_keys($this->blocks->all()))],
            'blocks.*.*.data' => ['sometimes', 'nullable', 'array'],
            ...$this->translationRules([
                'title' => ['string', 'max:255'],
                'slug' => ['string', 'max:255', 'alpha_dash:ascii'],
                'excerpt' => ['string', 'max:1000'],
                'meta_title' => ['string', 'max:255'],
                'meta_description' => ['string', 'max:500'],
            ]),
        ]);

        $admin = $this->admin($request);

        foreach ($data['blocks'] ?? [] as $locale => $blocks) {
            $this->assertMayChangeLockedBlocks($page, (string) $locale, array_values($blocks));
        }

        DB::transaction(function () use ($page, $data, $admin) {
            $this->fillTranslatable($page, Arr::except($data, ['blocks']))->save();

            foreach ($data['blocks'] ?? [] as $locale => $blocks) {
                $page->syncBlocks('body', (string) $locale, array_values($blocks));
            }

            $this->revisions->record($page, $admin);
        });

        return $page->refresh();
    }

    /**
     * Staff without a block type's permission (custom HTML) may neither add nor change such
     * blocks; the same rule as the admin's content editor.
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    private function assertMayChangeLockedBlocks(Page $page, string $locale, array $blocks): void
    {
        $locked = array_keys(array_filter(
            $this->blocks->all(),
            fn (BlockType $type) => $type->permission() !== null && ! Gate::allows($type->permission()),
        ));

        if ($locked === []) {
            return;
        }

        $data = fn (array $blocks): array => array_values(array_map(
            fn (array $block) => json_encode($block['data'] ?? []) ?: '',
            array_filter($blocks, fn (array $block) => in_array($block['type'] ?? null, $locked, true)),
        ));

        if ($data($blocks) !== $data($page->exists ? $page->blocksFor('body', $locale) : [])) {
            throw ValidationException::withMessages(["blocks.{$locale}" => __('You may not add or change custom HTML blocks.')]);
        }
    }

    /**
     * @return list<string>
     */
    private function locales(): array
    {
        return array_values(app(Localization::class)->languages()->map(fn (Language $language) => $language->code)->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Page $page, bool $withBlocks = true): array
    {
        return [
            'id' => $page->id,
            'status' => $page->status->value,
            'is_live' => $page->isLive(),
            'is_home' => $page->is_home,
            'title' => $page->getAttributes()['title'] ?? null,
            'slug' => $page->getAttributes()['slug'] ?? null,
            'excerpt' => $page->getAttributes()['excerpt'] ?? null,
            'meta_title' => $page->getAttributes()['meta_title'] ?? null,
            'meta_description' => $page->getAttributes()['meta_description'] ?? null,
            'translations' => (object) $page->translationsInput(),
            'published_at' => $page->published_at?->toIso8601String(),
            'unpublished_at' => $page->unpublished_at?->toIso8601String(),
            'author_id' => $page->author_id,
            ...($withBlocks ? ['blocks' => (object) collect($this->locales())->mapWithKeys(fn (string $locale) => [$locale => $page->blocksFor('body', $locale)])->all()] : []),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }
}
