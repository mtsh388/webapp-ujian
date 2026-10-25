@extends('template.app')

@section('title', 'Tambah Biaya Pendaftaran')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    @include('registration-fees._form', [
        'formAction' => route('registration-fees.store'),
        'formMethod' => 'POST',
        'formTitle' => 'Tambah Biaya Pendaftaran',
    ])
</div>
@endsection
