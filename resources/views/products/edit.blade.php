
{{-- Product editing form. --}}
@extends('layouts.app')

@section('title', 'Edit Product')

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
              action="{{ route('products.update', $product) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('products._form', [
                'buttonText' => 'Update Product'
            ])
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/product-form.js') }}?v=1" defer></script>
@endpush
