// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';

import '../models/get_profile_response.dart';
import '../models/patch_profile_response.dart';
import '../models/post_profile_avatar_response.dart';
import '../models/profile_password_request_body.dart';
import '../models/profile_request_body.dart';
import '../models/put_profile_password_response.dart';

part 'perfil_client.g.dart';

@RestApi()
abstract class PerfilClient {
  factory PerfilClient(Dio dio, {String? baseUrl}) = _PerfilClient;

  /// Perfil del usuario del token
  @GET('/profile')
  Future<GetProfileResponse> showProfile();

  /// Nombre y teléfono (el correo no se cambia desde la app)
  @PATCH('/profile')
  Future<PatchProfileResponse> updateProfile({
    @Body() required ProfileRequestBody body,
  });

  /// Sube la foto de perfil.
  ///
  /// [avatar] - jpg, png o webp.
  @MultiPart()
  @POST('/profile/avatar')
  Future<PostProfileAvatarResponse> uploadAvatar({
    @Part(name: 'avatar') required File avatar,
  });

  /// Cambia la contraseña y cierra los demás dispositivos
  @PUT('/profile/password')
  Future<PutProfilePasswordResponse> changePassword({
    @Body() required ProfilePasswordRequestBody body,
  });
}
