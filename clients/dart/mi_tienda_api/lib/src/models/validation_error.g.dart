// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'validation_error.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ValidationError _$ValidationErrorFromJson(Map<String, dynamic> json) =>
    ValidationError(
      message: json['message'] as String,
      code: ErrorCode.fromJson(json['code'] as String),
      errors: (json['errors'] as Map<String, dynamic>?)?.map(
        (k, e) =>
            MapEntry(k, (e as List<dynamic>).map((e) => e as String).toList()),
      ),
    );

Map<String, dynamic> _$ValidationErrorToJson(ValidationError instance) =>
    <String, dynamic>{
      'message': instance.message,
      'code': _$ErrorCodeEnumMap[instance.code]!,
      'errors': instance.errors,
    };

const _$ErrorCodeEnumMap = {
  ErrorCode.unauthenticated: 'unauthenticated',
  ErrorCode.invalidCredentials: 'invalid_credentials',
  ErrorCode.emailNotVerified: 'email_not_verified',
  ErrorCode.accountDisabled: 'account_disabled',
  ErrorCode.forbidden: 'forbidden',
  ErrorCode.shopNotApproved: 'shop_not_approved',
  ErrorCode.notFound: 'not_found',
  ErrorCode.methodNotAllowed: 'method_not_allowed',
  ErrorCode.paymentInProgress: 'payment_in_progress',
  ErrorCode.idempotencyKeyRequired: 'idempotency_key_required',
  ErrorCode.idempotencyKeyReused: 'idempotency_key_reused',
  ErrorCode.requestInProgress: 'request_in_progress',
  ErrorCode.validationFailed: 'validation_failed',
  ErrorCode.businessRule: 'business_rule',
  ErrorCode.tooManyRequests: 'too_many_requests',
  ErrorCode.httpError: 'http_error',
  ErrorCode.$unknown: r'$unknown',
};
