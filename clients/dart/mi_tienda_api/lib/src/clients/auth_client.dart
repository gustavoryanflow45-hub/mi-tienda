// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';

import '../models/auth_email_resend_request_body.dart';
import '../models/auth_login_request_body.dart';
import '../models/auth_password_forgot_request_body.dart';
import '../models/auth_push_token_request_body.dart';
import '../models/auth_register_request_body.dart';
import '../models/get_auth_devices_response.dart';
import '../models/get_auth_me_response.dart';
import '../models/post_auth_email_resend_response.dart';
import '../models/post_auth_login_response.dart';
import '../models/post_auth_password_forgot_response.dart';
import '../models/post_auth_register_response.dart';
import '../models/post_auth_token_rotate_response.dart';
import '../models/put_auth_push_token_response.dart';

part 'auth_client.g.dart';

@RestApi()
abstract class AuthClient {
  factory AuthClient(Dio dio, {String? baseUrl}) = _AuthClient;

  /// Alta de comprador (sin token; hay que verificar el correo)
  @POST('/auth/register')
  Future<PostAuthRegisterResponse> register({
    @Body() required AuthRegisterRequestBody body,
  });

  /// Login; un token por dispositivo
  @POST('/auth/login')
  Future<PostAuthLoginResponse> login({
    @Body() required AuthLoginRequestBody body,
  });

  /// Reenvía el correo de verificación (misma respuesta exista o no)
  @POST('/auth/email/resend')
  Future<PostAuthEmailResendResponse> resendVerification({
    @Body() required AuthEmailResendRequestBody body,
  });

  /// Envía el enlace para elegir contraseña nueva (abre la web)
  @POST('/auth/password/forgot')
  Future<PostAuthPasswordForgotResponse> forgotPassword({
    @Body() required AuthPasswordForgotRequestBody body,
  });

  /// Usuario del token y habilidades del token actual
  @GET('/auth/me')
  Future<GetAuthMeResponse> me();

  /// Token nuevo para este dispositivo; el anterior muere en el acto
  @POST('/auth/token/rotate')
  Future<PostAuthTokenRotateResponse> rotateToken();

  /// Revoca el token de este dispositivo
  @POST('/auth/logout')
  Future<void> logout();

  /// Sesiones abiertas de la cuenta
  @GET('/auth/devices')
  Future<GetAuthDevicesResponse> listDevices();

  /// Cierra todas las demás sesiones (con include_current=1, también esta)
  @DELETE('/auth/devices')
  Future<void> revokeOtherDevices({
    @Query('include_current') bool? includeCurrent,
  });

  /// Cierra la sesión de un dispositivo
  @DELETE('/auth/devices/{id}')
  Future<void> revokeDevice({
    @Path('id') required int id,
  });

  /// Registra el token FCM de este dispositivo (el envío aún no existe).
  ///
  /// Llamar tras el login y cada vez que FCM rote el token. Vive en la.
  /// sesión del dispositivo: cerrar sesión o revocarlo lo borra, y rotar el.
  /// token de acceso lo conserva. Un mismo token FCM se quita de cualquier.
  /// otra sesión (otra cuenta abierta antes en ese móvil).
  @PUT('/auth/push-token')
  Future<PutAuthPushTokenResponse> registerPushToken({
    @Body() required AuthPushTokenRequestBody body,
  });

  /// Quita el token FCM de este dispositivo (la sesión sigue abierta)
  @DELETE('/auth/push-token')
  Future<void> unregisterPushToken();
}
