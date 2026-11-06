// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

@JsonEnum()
enum ErrorCode {
  @JsonValue('unauthenticated')
  unauthenticated('unauthenticated'),
  @JsonValue('invalid_credentials')
  invalidCredentials('invalid_credentials'),
  @JsonValue('email_not_verified')
  emailNotVerified('email_not_verified'),
  @JsonValue('account_disabled')
  accountDisabled('account_disabled'),
  @JsonValue('forbidden')
  forbidden('forbidden'),
  @JsonValue('shop_not_approved')
  shopNotApproved('shop_not_approved'),
  @JsonValue('not_found')
  notFound('not_found'),
  @JsonValue('method_not_allowed')
  methodNotAllowed('method_not_allowed'),
  @JsonValue('payment_in_progress')
  paymentInProgress('payment_in_progress'),
  @JsonValue('idempotency_key_required')
  idempotencyKeyRequired('idempotency_key_required'),
  @JsonValue('idempotency_key_reused')
  idempotencyKeyReused('idempotency_key_reused'),
  @JsonValue('request_in_progress')
  requestInProgress('request_in_progress'),
  @JsonValue('validation_failed')
  validationFailed('validation_failed'),
  @JsonValue('business_rule')
  businessRule('business_rule'),
  @JsonValue('too_many_requests')
  tooManyRequests('too_many_requests'),
  @JsonValue('http_error')
  httpError('http_error'),
  /// Default value for all unparsed values, allows backward compatibility when adding new values on the backend.
  $unknown(null);

  const ErrorCode(this.json);

  factory ErrorCode.fromJson(String json) => values.firstWhere(
        (e) => e.json == json,
        orElse: () => $unknown,
      );

  final String? json;

  @override
  String toString() => json?.toString() ?? super.toString();
  /// Returns all defined enum values excluding the $unknown value.
  static List<ErrorCode> get $valuesDefined => values.where((value) => value != $unknown).toList();
}
