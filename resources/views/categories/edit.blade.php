
{{-- Category editing form. --}}
@extends('layouts.app')

@section('title', 'Edit Category')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/category-form.css') }}?v=1">
@endpush

@section('content')

<div class="cf-page">



    <section class="cf-card">

        <div class="cf-card-heading">
            <h2>Category Information</h2>
            <p>Update existing category details.</p>
        </div>

        @if($errors->any())
            <div class="cf-error-summary">
                Please correct the highlighted fields.
            </div>
        @endif

        <form method="POST"
              action="{{ route('categories.update', $category) }}">
            @csrf
            @method('PUT')

            @include('categories._form', [
                'buttonText' => 'Update Category'
            ])
        </form>

    </section>

</div>

@endsection
