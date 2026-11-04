<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Una operación de negocio no procede en el estado actual (un pedido que no
 * está en el paso anterior, una recarga ya aprobada...). El mensaje ya va
 * traducido y es apto para el usuario.
 *
 * La lanzan los servicios de app/Services; cada interfaz decide cómo
 * mostrarla: los controladores web la convierten en un aviso flash y la
 * futura API en un 422 con el mismo mensaje.
 */
class BusinessRuleException extends RuntimeException {}
