@extends('layouts.app')

@section('title', $title)

@section('content')
<div style="background:#f2f3f8; padding:30px 0 60px; min-height:60vh;">
    <div class="container" style="max-width:800px;">

        <div style="background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.07); padding:36px;">
            <h1 style="font-size:1.3rem; font-weight:700; color:#222; margin-bottom:24px; padding-bottom:16px; border-bottom:2px solid #679941;">
                {{ $title }}
            </h1>

            @if($type === 'terms')
                <p>Bienvenido a nuestra plataforma. Al utilizar nuestros servicios, aceptas los siguientes términos y condiciones.</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">1. Uso del servicio</h3>
                <p>Nuestro marketplace conecta compradores y vendedores. Debes tener al menos 18 años para utilizar este servicio.</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">2. Responsabilidades</h3>
                <p>Los vendedores son responsables de la calidad y descripción de sus productos. No nos hacemos responsables por daños derivados del uso del servicio.</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">3. Pagos</h3>
                <p>Los pagos se procesan de forma segura a través de Stripe (internacional) o Kushki (Ecuador).</p>

            @elseif($type === 'return-policy')
                <p>Queremos que estés completamente satisfecho con tu compra. Si no es así, aquí está nuestra política de devoluciones.</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">Plazo para devoluciones</h3>
                <p>Tienes hasta 30 días desde la fecha de entrega para solicitar una devolución.</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">Condiciones</h3>
                <p>El producto debe estar en su estado original, sin usar y con todos sus accesorios.</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">Proceso</h3>
                <p>Contacta al vendedor a través del panel de pedidos para iniciar el proceso de devolución.</p>

            @elseif($type === 'support-policy')
                <p>Estamos comprometidos a brindarte el mejor soporte posible.</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">Horario de atención</h3>
                <p>Lunes a viernes de 9:00 a 18:00 (hora Ecuador).</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">Canales de soporte</h3>
                <p>Puedes contactarnos a través del formulario de contacto en nuestro sitio web o por correo electrónico.</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">Tiempo de respuesta</h3>
                <p>Respondemos a todas las consultas en un plazo máximo de 24 horas hábiles.</p>

            @elseif($type === 'privacy-policy')
                <p>Tu privacidad es importante para nosotros. Esta política describe cómo recopilamos y usamos tu información.</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">Información que recopilamos</h3>
                <p>Recopilamos nombre, correo electrónico, dirección de envío y datos de pago (procesados de forma segura por Stripe/Kushki).</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">Uso de la información</h3>
                <p>Usamos tu información para procesar pedidos, enviarte actualizaciones y mejorar nuestros servicios.</p>
                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">Cookies</h3>
                <p>Utilizamos cookies para mejorar tu experiencia de navegación. Puedes desactivarlas en la configuración de tu navegador.</p>
            @endif

            <div style="margin-top:32px; padding-top:20px; border-top:1px solid #f0f0f0; font-size:.82rem; color:#aaa;">
                Última actualización: {{ date('d/m/Y') }}
            </div>
        </div>

    </div>
</div>
@endsection
