{{-- resources/views/partials/top-banner.blade.php --}}
{{-- Siempre visible: sin botón de cerrar, así que tampoco se oculta por la
     marca en localStorage que dejaba ese botón (clase removable-session). --}}
<div class="position-relative top-banner z-1035">
    <a href="#" class="d-block text-reset">
        <img src="{{ asset('assets/img/top-banner.png') }}" class="w-100 mw-100 h-50px h-lg-auto img-fit">
    </a>
</div>
