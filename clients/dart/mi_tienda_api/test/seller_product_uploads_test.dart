import 'package:dio/dio.dart';
import 'package:mi_tienda_api/mi_tienda_api.dart';
import 'package:test/test.dart';

/// Captura la petición y responde sin red.
class _Capture extends Interceptor {
  RequestOptions? request;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    request = options;
    handler.reject(DioException(requestOptions: options, error: 'capturada'));
  }
}

void main() {
  late _Capture capture;
  late SellerProductUploads uploads;

  setUp(() {
    capture = _Capture();
    final dio = Dio(BaseOptions(baseUrl: 'https://tienda.test/api/v1'))..interceptors.add(capture);
    uploads = SellerProductUploads(dio);
  });

  const input = SellerProductInput(
    name: 'Zapatilla',
    categoryId: 3,
    unitPrice: 49.9,
    unit: 'par',
    discountType: SellerProductInputDiscountType.percent,
    published: true,
    featured: false,
    stocks: [
      StockInput(size: '40', color: 'negro', price: 49.9, qty: 2),
      StockInput(size: '41', price: 52, qty: 0),
    ],
  );

  Map<String, List<String>> fieldsOf(RequestOptions request) {
    final form = request.data as FormData;
    final map = <String, List<String>>{};
    for (final entry in form.fields) {
      map.putIfAbsent(entry.key, () => []).add(entry.value);
    }
    return map;
  }

  test('create: stocks como array, booleanos 0/1, photos[]', () async {
    await expectLater(
      uploads.create(
        input,
        thumbnail: MultipartFile.fromBytes([1], filename: 't.webp'),
        photos: [MultipartFile.fromBytes([1], filename: 'a.webp'), MultipartFile.fromBytes([2], filename: 'b.webp')],
      ),
      throwsA(isA<DioException>()),
    );

    final request = capture.request!;
    expect(request.method, 'POST');
    expect(request.uri.toString(), 'https://tienda.test/api/v1/seller/products');

    final fields = fieldsOf(request);
    expect(fields['name'], ['Zapatilla']);
    expect(fields['category_id'], ['3']);
    expect(fields['discount_type'], ['percent']);
    expect(fields['published'], ['1']);
    expect(fields['featured'], ['0']);
    expect(fields.containsKey('stocks'), isFalse);
    expect(fields.containsKey('brand_id'), isFalse);
    expect(fields['stocks[0][size]'], ['40']);
    expect(fields['stocks[0][color]'], ['negro']);
    expect(fields['stocks[0][qty]'], ['2']);
    expect(fields['stocks[1][price]'], ['52']);
    expect(fields.containsKey('stocks[1][color]'), isFalse);

    final files = (request.data as FormData).files.map((e) => e.key).toList();
    expect(files, ['thumbnail', 'photos[]', 'photos[]']);
  });

  test('update: POST con _method=PUT', () async {
    await expectLater(
      uploads.update(7, const SellerProductInput(name: 'Nuevo nombre')),
      throwsA(isA<DioException>()),
    );

    final request = capture.request!;
    expect(request.method, 'POST');
    expect(request.uri.path, '/api/v1/seller/products/7');
    expect(fieldsOf(request), {'name': ['Nuevo nombre'], '_method': ['PUT']});
  });
}
