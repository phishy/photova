@extends('dashboard.layout')

@section('title', 'Map')

@section('content')
<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9]">Map</h1>
    </div>
    <div id="photo-map"></div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/map.jsx'])
@endsection
