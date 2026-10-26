
{{-- Supplier creation form. --}}
@extends('layouts.app')

@section('title', 'Add Supplier')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/supplier-form.css') }}?v=1">
@endpush

@section('content')

<div class="sf-page">



    <section class="sf-card">

        <div class="sf-card-heading">
            <h2>Supplier Information</h2>
            <p>Enter supplier details to add a new supplier.</p>
        </div>

        @if($errors->any())
            <div class="sf-error-summary" role="alert">
                Please correct the highlighted fields before saving.
            </div>
        @endif

        <form action="{{ route('suppliers.store') }}" method="POST">
            @csrf

            @include('suppliers._form', [
                'buttonText' => 'Save Supplier'
            ])
        </form>

    </section>

</div>

@endsection
