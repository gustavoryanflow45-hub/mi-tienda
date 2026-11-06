// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';

import '../models/get_notifications_response.dart';
import '../models/get_notifications_unread_count_response.dart';
import '../models/post_notifications_notification_read_response.dart';

part 'notificaciones_client.g.dart';

@RestApi()
abstract class NotificacionesClient {
  factory NotificacionesClient(Dio dio, {String? baseUrl}) = _NotificacionesClient;

  /// Avisos del usuario, del más nuevo al más viejo (no los marca leídos).
  ///
  /// [unread] - true = solo los no leídos.
  @GET('/notifications')
  Future<GetNotificationsResponse> listNotifications({
    @Query('unread') bool? unread,
    @Query('per_page') int? perPage,
    @Query('page') int? page,
  });

  /// Número de avisos sin leer
  @GET('/notifications/unread-count')
  Future<GetNotificationsUnreadCountResponse> unreadNotificationsCount();

  /// Marca todos los avisos como leídos
  @POST('/notifications/read-all')
  Future<void> markAllNotificationsRead();

  /// Marca un aviso como leído
  @POST('/notifications/{notification}/read')
  Future<PostNotificationsNotificationReadResponse> markNotificationRead({
    @Path('notification') required String notification,
  });
}
