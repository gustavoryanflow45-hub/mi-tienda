// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';

import '../models/enum1.dart';
import '../models/get_wallet_movements_response.dart';
import '../models/get_wallet_recharges_recharge_response.dart';
import '../models/get_wallet_recharges_response.dart';
import '../models/get_wallet_response.dart';
import '../models/get_wallet_settlements_response.dart';
import '../models/get_wallet_withdrawals_response.dart';
import '../models/get_wallet_withdrawals_withdrawal_response.dart';
import '../models/post_wallet_recharges_response.dart';
import '../models/post_wallet_withdrawals_response.dart';
import '../models/wallet_withdrawals_request_body.dart';

part 'billetera_client.g.dart';

@RestApi()
abstract class BilleteraClient {
  factory BilleteraClient(Dio dio, {String? baseUrl}) = _BilleteraClient;

  /// Saldo, comisión de red de la app y solicitudes pendientes
  @GET('/wallet')
  Future<GetWalletResponse> walletSummary();

  /// Recargas, retiros y liquidaciones en una sola lista
  @GET('/wallet/movements')
  Future<GetWalletMovementsResponse> listWalletMovements({
    @Query('per_page') int? perPage,
    @Query('page') int? page,
  });

  /// Estado de una solicitud de recarga
  @GET('/wallet/recharges/{recharge}')
  Future<GetWalletRechargesRechargeResponse> showRecharge({
    @Path('recharge') required int recharge,
  });

  /// Estado de una solicitud de retiro
  @GET('/wallet/withdrawals/{withdrawal}')
  Future<GetWalletWithdrawalsWithdrawalResponse> showWithdrawal({
    @Path('withdrawal') required int withdrawal,
  });

  /// Solicitudes de recarga, paginadas
  @GET('/wallet/recharges')
  Future<GetWalletRechargesResponse> listRecharges({
    @Query('per_page') int? perPage,
    @Query('page') int? page,
  });

  /// Solicita una recarga (queda pendiente hasta que el panel la apruebe).
  ///
  /// [idempotencyKey] - UUID que la app genera por cada envío del formulario y repite al.
  /// reintentar. Misma clave y mismo cuerpo → se devuelve la respuesta del.
  /// primer envío (cabecera `Idempotent-Replayed: true`) sin crear otra.
  /// solicitud; misma clave con otro cuerpo → `422 idempotency_key_reused`.
  /// Vale 24 h; un error no se guarda (se puede reintentar con la misma).
  ///
  ///
  /// [amount] - Mayor que `network_fee`; se acredita amount − network_fee.
  ///
  /// [network] - Name not received and was auto-generated.
  ///
  /// [transactionId] - Hash de la transferencia.
  ///
  /// [paymentProof] - Comprobante: jpg, png, webp o pdf, hasta `max_upload_kb` de GET /wallet.
  @MultiPart()
  @POST('/wallet/recharges')
  Future<PostWalletRechargesResponse> requestRecharge({
    @Header('Idempotency-Key') required String idempotencyKey,
    @Part(name: 'amount') required num amount,
    @Part(name: 'network') required Enum1 network,
    @Part(name: 'transaction_id') required String transactionId,
    @Part(name: 'payment_proof') required File paymentProof,
  });

  /// Solicitudes de retiro, paginadas
  @GET('/wallet/withdrawals')
  Future<GetWalletWithdrawalsResponse> listWithdrawals({
    @Query('per_page') int? perPage,
    @Query('page') int? page,
  });

  /// Solicita un retiro (descuenta el saldo en el acto).
  ///
  /// Queda siempre `pending` hasta que el panel lo apruebe. Descuenta.
  /// `amount` del saldo al solicitarlo; aprobado, el admin transfiere.
  /// `net_amount` (`amount` − comisión de red); rechazado, `amount` vuelve.
  /// entero al saldo. Saldo insuficiente, bajo el mínimo o sin superar la.
  /// comisión → `422 validation_failed` en `amount`.
  ///
  /// [idempotencyKey] - UUID que la app genera por cada envío del formulario y repite al.
  /// reintentar. Misma clave y mismo cuerpo → se devuelve la respuesta del.
  /// primer envío (cabecera `Idempotent-Replayed: true`) sin crear otra.
  /// solicitud; misma clave con otro cuerpo → `422 idempotency_key_reused`.
  /// Vale 24 h; un error no se guarda (se puede reintentar con la misma).
  @POST('/wallet/withdrawals')
  Future<PostWalletWithdrawalsResponse> requestWithdrawal({
    @Header('Idempotency-Key') required String idempotencyKey,
    @Body() required WalletWithdrawalsRequestBody body,
  });

  /// Liquidaciones de ventas acreditadas (vendedores)
  @GET('/wallet/settlements')
  Future<GetWalletSettlementsResponse> listSettlements({
    @Query('per_page') int? perPage,
    @Query('page') int? page,
  });
}
