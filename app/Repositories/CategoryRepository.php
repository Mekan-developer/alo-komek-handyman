<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    /** @param array{search?: string} $filters */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return Category::with(['parent', 'content.images'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $escaped = addcslashes($search, '%_\\');
                $q->where(fn ($sub) => $sub
                    ->where('name_ru', 'like', "%{$escaped}%")
                    ->orWhere('name_tk', 'like', "%{$escaped}%")
                );
            })
            ->orderByRaw('COALESCE(parent_id, id) DESC')
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy($this->nameColumn())
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Root categories only — used as parent options in the form. */
    public function roots(): Collection
    {
        return Category::whereNull('parent_id')
            ->orderBy($this->nameColumn())
            ->get(['id', 'name_ru', 'name_tk']);
    }

    /**
     * Active root categories with their active children eager-loaded — feeds the
     * two-level category picker on the order create/edit forms. Models keep the
     * appended `name` and `icon_url` attributes so the picker can render icons.
     *
     * @return Collection<int, Category>
     */
    public function treeForSelect(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy($this->nameColumn())])
            ->orderBy($this->nameColumn())
            ->get();
    }

    /**
     * Active categories whose name matches the search term — used by the client
     * app's service search input. Matches both language variants and are ordered
     * by the localized name with the parent eager-loaded for the service's group.
     */
    public function searchActive(string $term, int $limit = 20): Collection
    {
        $escaped = addcslashes($term, '%_\\');

        return Category::where('is_active', true)
            ->where(function ($query) use ($escaped) {
                $query->where('name_ru', 'like', "%{$escaped}%")
                    ->orWhere('name_tk', 'like', "%{$escaped}%");
            })
            ->with(['parent:id,name_ru,name_tk'])
            ->orderBy($this->nameColumn())
            ->limit($limit)
            ->get();
    }

    /** Localized name column to sort by, falling back to Russian. */
    private function nameColumn(): string
    {
        return in_array(app()->getLocale(), ['ru', 'tk'], true)
            ? 'name_'.app()->getLocale()
            : 'name_ru';
    }

    public function findOrFail(int $id): Category
    {
        return Category::findOrFail($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
