
{{-- Category list and management actions. --}}
@extends('layouts.app')

@section('title', 'Categories')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/categories.css') }}?v=3">
@endpush

@section('content')

<div class="cat-page">

    <section class="cat-card">

        @if(session('success'))
            <div class="cat-alert cat-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="cat-alert cat-alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div class="cat-toolbar">

            <form method="GET"
                  action="{{ route('categories.index') }}"
                  class="cat-search">

                <span class="cat-search-icon">⌕</span>

                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search categories..."
                    aria-label="Search categories"
                >

                <button type="submit">Search</button>
            </form>

            <a href="{{ route('categories.create') }}"
               class="cat-add-btn">
                <span>＋</span> Add Category
            </a>

        </div>

        <div class="cat-table-scroll">
            <table class="cat-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>
                                {{ $category->list_serial ?? ($categories->firstItem() + $loop->index) }}
                            </td>

                            <td class="cat-name">
                                {{ $category->name }}
                            </td>

                            <td>
                                {{ $category->description ?: '—' }}
                            </td>

                            <td>
                                <span class="cat-status {{ $category->is_active ? 'cat-active' : 'cat-inactive' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td>
                                <div class="cat-actions">

                                    <a href="{{ route('categories.edit', $category) }}"
                                       class="cat-edit"
                                       title="Edit category"
                                       aria-label="Edit {{ $category->name }}">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/>
                                        </svg>
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('categories.destroy', $category) }}"
                                        data-confirm-delete
                                        data-delete-type="category"
                                        data-delete-name="{{ $category->name }}">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="cat-delete"
                                                title="Delete category"
                                                aria-label="Delete {{ $category->name }}">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15M10 10v7M14 10v7"/>
                                            </svg>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="cat-empty">
                                No categories found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <div class="cat-footer">

            <span>
                @if($categories->total() > 0)
                    Showing {{ $categories->firstItem() }}
                    to {{ $categories->lastItem() }}
                    of {{ $categories->total() }} entries
                @else
                    Showing 0 entries
                @endif
            </span>

            <div class="cat-pages">

                @if($categories->onFirstPage())
                    <span class="cat-page-btn cat-disabled">‹</span>
                @else
                    <a href="{{ $categories->previousPageUrl() }}"
                       class="cat-page-btn">‹</a>
                @endif

                @foreach($categories->getUrlRange(
                    max(1, $categories->currentPage() - 2),
                    min($categories->lastPage(), $categories->currentPage() + 2)
                ) as $page => $url)
                    <a href="{{ $url }}"
                       class="cat-page-btn {{ $page == $categories->currentPage() ? 'cat-selected' : '' }}"
                       @if($page == $categories->currentPage()) aria-current="page" @endif>
                        {{ $page }}
                    </a>
                @endforeach

                @if($categories->hasMorePages())
                    <a href="{{ $categories->nextPageUrl() }}"
                       class="cat-page-btn">›</a>
                @else
                    <span class="cat-page-btn cat-disabled">›</span>
                @endif

            </div>
        </div>

    </section>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/list-search.js') }}?v=1" defer></script>
@endpush
