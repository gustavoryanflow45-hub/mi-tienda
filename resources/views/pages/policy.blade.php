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
                <p>Estos Términos y Condiciones regulan el acceso y el uso de guStore, el mercado en línea donde vendedores independientes ofrecen sus productos a los compradores. Al registrarse, navegar o realizar una compra, usted declara haber leído, comprendido y aceptado íntegramente este documento, y se obliga a cumplirlo en su totalidad. Si no está de acuerdo con alguno de sus puntos, debe abstenerse de utilizar la plataforma.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">1. Generalidades</h3>
                <p>guStore es operada por guStore S.A.S., con domicilio en Cuenca, Ecuador. En adelante, «la plataforma» se refiere al sitio web y a todos los servicios que prestamos a través de él; «usuario», a cualquier persona que acceda a ella; «comprador», a quien adquiere productos; y «vendedor», a quien los publica y comercializa.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">2. Objeto y alcance del servicio</h3>
                <p>guStore pone a disposición de los usuarios un espacio tecnológico que conecta a compradores con vendedores independientes, junto con las herramientas de catálogo, carrito, cobro y seguimiento de pedidos necesarias para esa relación.</p>
                <p>guStore actúa como intermediario: no fabrica, no importa ni es propietaria de los productos publicados, salvo que se indique expresamente lo contrario. La compraventa se perfecciona entre el comprador y el vendedor, quienes asumen las obligaciones que de ella se derivan.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">3. Capacidad y registro de usuarios</h3>
                <p>Para usar la plataforma debe ser mayor de 18 años y tener capacidad legal para contratar. La información que registre debe ser veraz, completa y estar actualizada; mantenerla al día es su responsabilidad.</p>
                <p>Es necesario confirmar la dirección de correo electrónico mediante el enlace de verificación que enviamos al crear la cuenta. Las credenciales son personales e intransferibles: usted responde por toda actividad realizada desde su cuenta y debe notificarnos de inmediato cualquier uso no autorizado.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">4. Cuentas de vendedor y aprobación de tiendas</h3>
                <p>El registro de una tienda está sujeto a revisión. Para aprobarla podemos solicitar documento de identidad, RUC y cualquier otra información que permita verificar la identidad del solicitante y la licitud de su actividad.</p>
                <p>Mientras la tienda esté pendiente o haya sido rechazada, el vendedor no podrá publicar productos ni recibir pedidos. guStore puede rechazar una solicitud, o suspender una tienda ya aprobada, cuando detecte información falsa, incumplimiento de estos términos o riesgo para los compradores.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">5. Obligaciones del vendedor</h3>
                <p>El vendedor es el único responsable de los productos que publica y se obliga a:</p>
                <ul style="margin:0 0 12px 18px; padding:0;">
                    <li>Describirlos con exactitud, con imágenes reales y con características, variantes y precios correctos.</li>
                    <li>Mantener el inventario actualizado y despachar únicamente lo que tiene disponible.</li>
                    <li>Cumplir la garantía legal y las demás obligaciones de la Ley Orgánica de Defensa del Consumidor.</li>
                    <li>Emitir los comprobantes de venta que exige la normativa tributaria ecuatoriana.</li>
                    <li>No ofrecer productos falsificados, robados, de comercialización prohibida o restringida, ni que infrinjan derechos de terceros.</li>
                    <li>Atender los reclamos de sus compradores y respetar los plazos de preparación y entrega comprometidos.</li>
                </ul>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">6. Obligaciones del comprador</h3>
                <p>El comprador se obliga a entregar datos de contacto y de envío correctos, a pagar el precio total informado antes de confirmar el pedido y a recibir el producto en la dirección indicada. Queda prohibido usar la plataforma con fines fraudulentos, así como desconocer cargos legítimamente autorizados.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">7. Precios, pagos y facturación</h3>
                <p>Los precios se expresan en dólares de los Estados Unidos de América e incluyen el IVA vigente en Ecuador cuando corresponde. Antes de pagar se muestra el desglose del pedido: subtotal, costo de envío e impuestos.</p>
                <p>Los pagos se procesan a través de pasarelas autorizadas —Kushki para Ecuador y Stripe para el resto de países—, que operan bajo sus propias condiciones y estándares de seguridad. guStore no almacena números de tarjeta. El pedido se confirma únicamente cuando la pasarela aprueba la transacción; un pago rechazado o pendiente no genera obligación de entrega.</p>
                <p>guStore puede descontar de cada venta la comisión y los costos de procesamiento acordados con el vendedor. Los saldos a favor se acreditan en la billetera de la tienda y pueden retirarse conforme al procedimiento publicado en el panel de vendedor.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">8. Envíos y entregas</h3>
                <p>El costo de envío lo fija el vendedor para cada producto y se muestra por separado antes de completar el pago. Una vez pagado, el pedido avanza por los estados de confirmación, preparación, despacho y entrega, y su avance puede consultarse en todo momento desde el detalle del pedido.</p>
                <p>Los plazos de entrega son estimados. Cuando el transporte esté a cargo de terceros, las demoras imputables a ellos o a causas de fuerza mayor no son responsabilidad de guStore, sin perjuicio de nuestra gestión para que el pedido llegue a destino.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">9. Devoluciones y garantías</h3>
                <p>Las condiciones y plazos para devolver un producto se detallan en nuestra <a href="{{ route('return-policy') }}" style="color:#679941;">Política de Devoluciones</a>. Nada de lo dispuesto allí ni en estos términos limita los derechos que la Ley Orgánica de Defensa del Consumidor reconoce al consumidor, incluida la garantía legal y el derecho de devolución en las ventas a distancia.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">10. Uso permitido de la plataforma</h3>
                <p>Le otorgamos una licencia limitada, personal, revocable y no exclusiva para usar la plataforma con fines lícitos. Queda prohibido copiar o reproducir el sitio, aplicarle ingeniería inversa, extraer datos de forma automatizada, emplear robots o mecanismos que afecten su funcionamiento, vulnerar sus medidas de seguridad o ceder el acceso a terceros.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">11. Propiedad intelectual</h3>
                <p>La marca guStore, el software, el diseño y los contenidos del sitio pertenecen a guStore S.A.S. o a sus licenciantes, y su uso no autorizado está prohibido.</p>
                <p>Los contenidos que el vendedor publica siguen siendo suyos; al cargarlos autoriza a guStore a mostrarlos, reproducirlos y promocionarlos dentro de la plataforma y sus canales de difusión, y garantiza que cuenta con los derechos necesarios para ello.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">12. Limitación de responsabilidad</h3>
                <p>La plataforma se ofrece en el estado en que se encuentra. No garantizamos que esté disponible de forma ininterrumpida ni libre de errores, y podemos suspenderla temporalmente por mantenimiento o por causas ajenas a nuestro control.</p>
                <p>Por su condición de intermediario, guStore no responde por la calidad, legalidad, veracidad ni disponibilidad de los productos publicados por los vendedores, ni por el incumplimiento de las obligaciones que estos asumen frente al comprador. En cualquier caso, nuestra responsabilidad se limita al valor del pedido de que se trate.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">13. Suspensión y terminación de cuentas</h3>
                <p>Podemos suspender o cancelar una cuenta, retirar publicaciones y retener liquidaciones pendientes cuando exista incumplimiento de estos términos, indicios de fraude o una orden de autoridad competente. El usuario puede solicitar la baja de su cuenta en cualquier momento, sin que ello lo libere de las obligaciones ya contraídas.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">14. Protección de datos personales</h3>
                <p>Los datos que usted nos entrega se tratan conforme a la Ley Orgánica de Protección de Datos Personales y a nuestra <a href="{{ route('privacy-policy') }}" style="color:#679941;">Política de Privacidad</a>, que forma parte integrante de estos términos.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">15. Modificaciones</h3>
                <p>Podemos actualizar estos términos para reflejar cambios legales, técnicos o en nuestros servicios. La versión vigente es siempre la publicada en esta página, con su fecha de última actualización; el uso de la plataforma después de una modificación implica su aceptación.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">16. Ley aplicable y solución de controversias</h3>
                <p>Estos términos se rigen por la legislación de la República del Ecuador. Cualquier controversia se procurará resolver de buena fe entre las partes y, de no lograrse un acuerdo, se someterá a los jueces competentes de Cuenca, sin perjuicio de las acciones que el consumidor pueda ejercer ante la autoridad de protección al consumidor.</p>

                <h3 style="font-size:1rem; font-weight:700; margin:20px 0 8px;">17. Contacto</h3>
                <p>Para consultas sobre estos Términos y Condiciones puede escribirnos a soporte@gustore.com o a través del formulario de contacto del sitio.</p>

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
