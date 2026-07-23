<?php

namespace App\Http\Controllers;

use App\Actions\UpsertCategoryContentAction;
use App\Http\Requests\StoreCategoryContentRequest;
use App\Http\Traits\WithNotification;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;

class CategoryContentController extends Controller
{
    use WithNotification;

    public function upsert(
        Category $category,
        StoreCategoryContentRequest $request,
        UpsertCategoryContentAction $action,
    ): RedirectResponse {
        abort_if($category->isRoot(), 422, __('categories.content_subcategory_only'));

        $action->handle(
            $category,
            $request->validated(),
            $request->file('images', []),
            $request->input('keep_ids', []),
        );

        $this->notifySuccess('notifications.saved', ['resource' => __('resources.content')]);

        return redirect()->route('categories.index');
    }
}
