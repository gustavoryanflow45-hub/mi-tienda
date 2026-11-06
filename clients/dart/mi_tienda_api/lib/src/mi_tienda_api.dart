// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:dio/dio.dart';

import 'clients/auth_client.dart';
import 'clients/catalogo_client.dart';
import 'clients/carrito_client.dart';
import 'clients/checkout_client.dart';
import 'clients/pagos_client.dart';
import 'clients/perfil_client.dart';
import 'clients/pedidos_client.dart';
import 'clients/vendedor_client.dart';
import 'clients/billetera_client.dart';
import 'clients/notificaciones_client.dart';

/// Mi Tienda — API móvil v1 `v1.0.0`.
///
/// API para las apps de compradores y vendedores (Flutter).
///
/// Contrato de `/api/v1`. La guía en prosa (flujos, decisiones, notas para.
/// Flutter) es `docs/api/v1.md`; este archivo es la referencia de máquina:.
/// de él se genera el cliente Dart de `clients/dart/mi_tienda_api`.
/// (`swagger_parser`; ver "Cliente Dart" en `v1.md`), y se puede abrir en.
/// Swagger UI / Redoc.
///
/// `tests/Feature/Api/V1/OpenApiSpecTest.php` falla si una ruta de la API.
/// falta aquí (o sobra), si la seguridad o las habilidades no coinciden con.
/// las de la ruta, o si una respuesta real deja de cumplir su esquema.
///
/// **Errores**: siempre `{ message, code }` (+ `errors` en.
/// `validation_failed`). La app decide por `code`; `message` va traducido.
/// según `Accept-Language` (`es` por defecto, `en`).
///
/// **Habilidades**: cada operación con token lista en `x-abilities` las que.
/// exige el token (ver `/auth/login`). Las de vendedor además exigen la.
/// tienda aprobada en cada petición (`403 shop_not_approved`).
///
class MiTiendaApi {
  MiTiendaApi(
    Dio dio, {
    String? baseUrl,
  })  : _dio = dio,
        _baseUrl = baseUrl;

  final Dio _dio;
  final String? _baseUrl;

  static String get version => '1.0.0';

  AuthClient? _auth;
  CatalogoClient? _catalogo;
  CarritoClient? _carrito;
  CheckoutClient? _checkout;
  PagosClient? _pagos;
  PerfilClient? _perfil;
  PedidosClient? _pedidos;
  VendedorClient? _vendedor;
  BilleteraClient? _billetera;
  NotificacionesClient? _notificaciones;

  AuthClient get auth => _auth ??= AuthClient(_dio, baseUrl: _baseUrl);

  CatalogoClient get catalogo => _catalogo ??= CatalogoClient(_dio, baseUrl: _baseUrl);

  CarritoClient get carrito => _carrito ??= CarritoClient(_dio, baseUrl: _baseUrl);

  CheckoutClient get checkout => _checkout ??= CheckoutClient(_dio, baseUrl: _baseUrl);

  PagosClient get pagos => _pagos ??= PagosClient(_dio, baseUrl: _baseUrl);

  PerfilClient get perfil => _perfil ??= PerfilClient(_dio, baseUrl: _baseUrl);

  PedidosClient get pedidos => _pedidos ??= PedidosClient(_dio, baseUrl: _baseUrl);

  VendedorClient get vendedor => _vendedor ??= VendedorClient(_dio, baseUrl: _baseUrl);

  BilleteraClient get billetera => _billetera ??= BilleteraClient(_dio, baseUrl: _baseUrl);

  NotificacionesClient get notificaciones => _notificaciones ??= NotificacionesClient(_dio, baseUrl: _baseUrl);
}
