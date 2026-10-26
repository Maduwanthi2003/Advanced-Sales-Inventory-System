
{{-- Product creation form. --}}
@extends('layouts.app')

@section('title', 'Add Product')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/product-form.css') }}?v=1">
@endpush

@section('content')
<div class="pf-page">


    <section class="pf-card">
        @if($errors->any())
            <div class="pf-alert" role="alert">
                Please correct the highlighted fields.
            </div>
        @endif

        <form method="POST"
              action="{{ route('products.store') }}"
              enctype="multipart/form-data">
            @csrf
            @include('products._form', [
                'buttonText' => 'Save Product'
            ])
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/product-form.js') }}?v=1" defer></script>
@endpush
