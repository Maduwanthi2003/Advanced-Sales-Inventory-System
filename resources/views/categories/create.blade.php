
{{-- Category creation form. --}}
@extends('layouts.app')

@section('title', 'Add Category')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/category-form.css') }}?v=1">
@endpush

@section('content')

<div class="cf-page">



    <section class="cf-card">

        <div class="cf-card-heading">
            <h2>Category Information</h2>
            <p>Enter category details to add a new category.</p>
        </div>

        @if($errors->any())
            <div class="cf-error-summary">
                Please correct the highlighted fields.
            </div>
        @endif

        <form method="POST"
              action="{{ route('categories.store') }}">
            @csrf

            @include('categories._form', [
                'buttonText' => 'Save Category'
            ])
        </form>

    </section>

</div>

@endsection
