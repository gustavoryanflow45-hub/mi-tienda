// Escrito a mano: swagger_parser no lo toca (solo regenera lib/src).

import 'package:dio/dio.dart';

import 'src/export.dart';

/// Alta y edición de productos con imágenes (`multipart/form-data`).
///
/// Sustituye a `VendedorClient.sellerCreateProduct` / `sellerUpdateProduct`,
/// cuyo código generado no sirve contra el servidor:
/// - manda `stocks` como un único campo JSON, y Laravel exige el array
///   `stocks[0][size]`, `stocks[0][qty]`…;
/// - manda los booleanos como `"true"`/`"false"`, que la regla `boolean`
///   rechaza (aquí van `1`/`0`);
/// - repite `photos` sin corchetes, y PHP se queda solo con la última foto
///   (aquí van como `photos[]`);
/// - la edición sale como `PUT` y sin cuerpo; PHP no lee archivos en un PUT,
///   así que va como `POST` con `_method=PUT`.
///
/// Un [MultipartFile] solo se puede enviar una vez: para reintentar, créalo de
/// nuevo (`MultipartFile.fromFile(path)`).
class SellerProductUploads {
  SellerProductUploads(Dio dio, {String? baseUrl})
      : _dio = dio,
        _baseUrl = baseUrl;

  final Dio _dio;
  final String? _baseUrl;

  /// `POST /seller/products`. Obligatorios: `name`, `categoryId`, `unitPrice`,
  /// `unit`, al menos una fila en `stocks` y [thumbnail]; hasta 5 [photos].
  Future<PostSellerProductsResponse> create(
    SellerProductInput input, {
    required MultipartFile thumbnail,
    List<MultipartFile> photos = const [],
  }) async {
    final response = await _dio.post<Map<String, dynamic>>(
      _path('/seller/products'),
      data: _form(input, thumbnail: thumbnail, photos: photos),
    );
    return PostSellerProductsResponse.fromJson(response.data!);
  }

  /// `PUT /seller/products/{product}`, enviado como `POST` + `_method=PUT`.
  /// Lo que no se manda se conserva; `stocks`, si llega, reemplaza todas las
  /// combinaciones y [photos], si llega, todas las fotos.
  Future<PutSellerProductsProductResponse> update(
    int product,
    SellerProductInput input, {
    MultipartFile? thumbnail,
    List<MultipartFile> photos = const [],
  }) async {
    final form = _form(input, thumbnail: thumbnail, photos: photos)
      ..fields.add(const MapEntry('_method', 'PUT'));
    final response = await _dio.post<Map<String, dynamic>>(
      _path('/seller/products/$product'),
      data: form,
    );
    return PutSellerProductsProductResponse.fromJson(response.data!);
  }

  String _path(String path) => '${_baseUrl ?? ''}$path';

  static FormData _form(
    SellerProductInput input, {
    MultipartFile? thumbnail,
    List<MultipartFile> photos = const [],
  }) {
    final form = FormData();

    final fields = input.toJson()..remove('stocks');
    fields.forEach((key, value) {
      if (value != null) form.fields.add(MapEntry(key, _scalar(value)));
    });

    final stocks = input.stocks ?? const <StockInput>[];
    for (var i = 0; i < stocks.length; i++) {
      stocks[i].toJson().forEach((key, value) {
        if (value != null) form.fields.add(MapEntry('stocks[$i][$key]', _scalar(value)));
      });
    }

    if (thumbnail != null) form.files.add(MapEntry('thumbnail', thumbnail));
    for (final photo in photos) {
      form.files.add(MapEntry('photos[]', photo));
    }
    return form;
  }

  static String _scalar(Object value) => value is bool ? (value ? '1' : '0') : value.toString();
}
