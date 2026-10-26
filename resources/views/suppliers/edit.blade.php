
{{-- Supplier editing form. --}}
@extends('layouts.app')

@section('title', 'Edit Supplier')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/supplier-form.css') }}?v=1">
@endpush

@section('content')

<div class="sf-page">



    <section class="sf-card">

        <div class="sf-card-heading">
            <h2>Supplier Information</h2>
            <p>Update existing supplier details.</p>
        </div>

        @if($errors->any())
            <div class="sf-error-summary" role="alert">
                Please correct the highlighted fields.
            </div>
        @endif

        <form action="{{ route('suppliers.update', $supplier) }}"
              method="POST">
            @csrf
            @method('PUT')

            @include('suppliers._form', [
                'buttonText' => 'Update Supplier'
            ])
        </form>

    </section>

</div>

@endsection
