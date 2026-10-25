@extends('template.app')

@section('title', 'Edit Biaya')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    @include('registration-fees._form', [
        'formAction' => route('registration-fees.update', $registrationFee),
        'formMethod' => 'PUT',
        'formTitle' => 'Edit Biaya',
    ])
</div>
@endsection
