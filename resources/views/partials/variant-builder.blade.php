{{--
    Constructor de variantes talla × color, compartido por los formularios de
    crear y editar producto.

    Espera:
      $initialStocks       Colección/array de ['size','color','price','qty','sku'].
                           Vacío al crear; las filas existentes al editar.
      $initialVariantType  variant_type del producto al editar; null al crear.

    Las tallas ofrecidas salen de config/variants.php: manda el tipo que declara
    el producto y, si no declara ninguno, el de la categoría elegida. Es la
    misma resolución que aplica el servidor en Product::sizeOptions(), desde
    SellerProductController::validateVariants().
--}}

@php
    $initialStocks      = collect($initialStocks ?? []);
    $initialVariantType = old('variant_type', $initialVariantType ?? null);
@endphp

<div id="variant-builder" style="margin-bottom:14px;">

    {{-- Tipo de talla: normalmente el de la categoría, pero un producto puede
         declarar el suyo. Las categorías del catálogo son mixtas y unas
         zapatillas cuelgan de "Sports & outdoor" junto a carpas: sin este
         override no habría forma de darles talla sin dársela a todo el rubro. --}}
    <div style="margin-bottom:14px;">
        <div class="form-label mb-2">
            Tipo de talla
            <span style="color:#aaa;font-weight:400;">— qué tallas ofrece este producto</span>
        </div>
        <select name="variant_type" id="variant_type" class="form-control" onchange="onVariantTypeChange()">
            <option value="" data-auto="1">Según la categoría</option>
            @foreach(config('variants.types') as $key => $type)
                <option value="{{ $key }}" {{ $initialVariantType === $key ? 'selected' : '' }}>
                    {{ $type['label'] }}
                </option>
            @endforeach
        </select>
        <div style="font-size:.8rem;color:#999;margin-top:6px;">
            Cámbialo si la categoría no ofrece las tallas que necesitas: unas
            zapatillas en “Sports &amp; outdoor” se venden por talla de calzado
            aunque el resto de la categoría no use tallas.
        </div>
    </div>

    {{-- Tallas: solo en categorías que las manejan --}}
    <div id="size-picker" style="display:none;margin-bottom:14px;">
        <div class="form-label mb-2">
            <span id="size-picker-label">Tallas</span>
            <span style="color:#aaa;font-weight:400;">— marca las que vendes</span>
        </div>
        <div class="quick-sizes" id="size-options"></div>
    </div>

    {{-- Colores: aplican a cualquier producto --}}
    <div style="margin-bottom:14px;">
        <div class="form-label mb-2">
            Colores <span style="color:#aaa;font-weight:400;">— marca los que vendes</span>
        </div>
        <div class="color-picker-row">
            @foreach(config('variants.colors') as $key => $color)
                <div class="color-swatch-btn"
                     style="background:{{ $color['hex'] }};"
                     title="{{ $color['label'] }}"
                     data-color="{{ $key }}"
                     onclick="toggleColor(this)"></div>
            @endforeach
        </div>
    </div>

    <div id="no-category-hint" style="font-size:.8rem;color:#999;">
        Selecciona primero una categoría para ver las tallas disponibles.
    </div>
</div>

<label class="form-label">Stock <span class="req">*</span></label>
<div class="variant-info-box" id="matrix-hint" style="display:none;">
    <strong>Una fila por combinación</strong>
    Cada talla y color que marques genera su propia fila con precio y cantidad.
    Así el cliente ve agotada solo la combinación que se acabó, no el producto entero.
</div>
<div id="stock-rows"></div>

<script>
// ── Variantes: matriz talla × color ──────────────────────────────
const VARIANT_TYPES = @json(config('variants.types'));
const COLOR_PALETTE = @json(config('variants.colors'));
const INITIAL_STOCKS = @json($initialStocks->values());

let selectedSizes  = [];
let selectedColors = [];

function categoryVariantType() {
    const sel = document.getElementById('category_id');
    const opt = sel.options[sel.selectedIndex];
    return (opt && opt.dataset.variantType) || 'none';
}

function currentVariantType() {
    return document.getElementById('variant_type').value || categoryVariantType();
}

/** Deja ver en la opción "Según la categoría" qué tipo se está heredando. */
function paintInheritedLabel() {
    const inherited = VARIANT_TYPES[categoryVariantType()] || VARIANT_TYPES['none'];
    document.querySelector('#variant_type option[data-auto]').textContent =
        'Según la categoría (' + inherited.label + ')';
}

function onVariantTypeChange() {
    onCategoryChange();
}

function renderSizeOptions() {
    const config = VARIANT_TYPES[currentVariantType()] || VARIANT_TYPES['none'];
    const sizes  = config.sizes || [];
    const picker = document.getElementById('size-picker');

    document.getElementById('no-category-hint').style.display =
        document.getElementById('category_id').value ? 'none' : 'block';

    if (sizes.length === 0) {
        picker.style.display = 'none';
        document.getElementById('size-options').innerHTML = '';
        return [];
    }

    document.getElementById('size-picker-label').textContent = config.size_label || 'Tallas';
    document.getElementById('size-options').innerHTML = sizes.map(s =>
        `<button type="button" class="quick-size-btn" data-size="${s}" onclick="toggleSize(this)">${s}</button>`
    ).join('');
    picker.style.display = 'block';

    return sizes;
}

function onCategoryChange() {
    paintInheritedLabel();

    const sizes = renderSizeOptions();

    // Al cambiar de categoría se descartan las tallas que ya no existen en el
    // nuevo set; si la categoría no maneja tallas se descartan todas, porque
    // el servidor rechaza tallas donde no aplican.
    selectedSizes = selectedSizes.filter(s => sizes.includes(s));
    paintSizeButtons();
    rebuildMatrix();
}

function paintSizeButtons() {
    document.querySelectorAll('#size-options .quick-size-btn').forEach(btn => {
        const on = selectedSizes.includes(btn.dataset.size);
        btn.style.background  = on ? '#679941' : '#fff';
        btn.style.color       = on ? '#fff'    : '#555';
        btn.style.borderColor = on ? '#679941' : '#d8dde3';
    });
}

function paintColorButtons() {
    document.querySelectorAll('.color-swatch-btn').forEach(btn => {
        const on = selectedColors.includes(btn.dataset.color);
        btn.style.outline       = on ? '3px solid #679941' : 'none';
        btn.style.outlineOffset = '2px';
    });
}

function toggleSize(btn) {
    const size = btn.dataset.size;
    const i    = selectedSizes.indexOf(size);
    if (i === -1) { selectedSizes.push(size); } else { selectedSizes.splice(i, 1); }
    paintSizeButtons();
    rebuildMatrix();
}

function toggleColor(btn) {
    const color = btn.dataset.color;
    const i     = selectedColors.indexOf(color);
    if (i === -1) { selectedColors.push(color); } else { selectedColors.splice(i, 1); }
    paintColorButtons();
    rebuildMatrix();
}

function comboKey(size, color) {
    return [size, color].filter(Boolean).join('-');
}

/**
 * Regenera las filas desde lo marcado, conservando precio, cantidad y SKU de
 * las combinaciones que sobreviven para no perder lo ya escrito al marcar una
 * talla más.
 */
function rebuildMatrix() {
    const previous = {};
    document.querySelectorAll('#stock-rows .stock-row').forEach(row => {
        previous[row.dataset.key] = {
            price: row.querySelector('[data-field="price"]').value,
            qty:   row.querySelector('[data-field="qty"]').value,
            sku:   row.querySelector('[data-field="sku"]').value,
        };
    });

    // Sin nada marcado, el producto no tiene variantes: una sola fila simple.
    const sizes  = selectedSizes.length  ? selectedSizes  : [null];
    const colors = selectedColors.length ? selectedColors : [null];
    const combos = [];
    sizes.forEach(size => colors.forEach(color => combos.push({ size, color })));

    document.getElementById('stock-rows').innerHTML = combos.map((combo, i) => {
        const key   = comboKey(combo.size, combo.color);
        const saved = previous[key] || INITIAL_BY_KEY[key] || {};
        const label = key
            ? [combo.size, combo.color ? (COLOR_PALETTE[combo.color]?.label ?? combo.color) : null]
                .filter(Boolean).join(' · ')
            : 'Producto sin variantes';

        return `
        <div class="stock-row" data-key="${key}">
            <div class="stock-row-header">
                <span class="stock-row-badge">${label}</span>
            </div>
            <input type="hidden" name="stocks[${i}][size]"  value="${combo.size  ?? ''}">
            <input type="hidden" name="stocks[${i}][color]" value="${combo.color ?? ''}">
            <div class="stock-row-fields">
                <div>
                    <label class="form-label" style="font-size:.78rem;">Precio <span style="color:#e74c3c;">*</span></label>
                    <input type="number" name="stocks[${i}][price]" data-field="price" class="form-control"
                           placeholder="0.00" step="0.01" min="0" required value="${saved.price ?? ''}">
                </div>
                <div>
                    <label class="form-label" style="font-size:.78rem;">Cantidad <span style="color:#e74c3c;">*</span></label>
                    <input type="number" name="stocks[${i}][qty]" data-field="qty" class="form-control"
                           placeholder="0" min="0" required value="${saved.qty ?? ''}">
                </div>
                <div>
                    <label class="form-label" style="font-size:.78rem;">SKU</label>
                    <input type="text" name="stocks[${i}][sku]" data-field="sku" class="form-control"
                           placeholder="SKU-${String(i + 1).padStart(3, '0')}" value="${saved.sku ?? ''}">
                </div>
            </div>
        </div>`;
    }).join('');

    document.getElementById('matrix-hint').style.display =
        (selectedSizes.length > 0 || selectedColors.length > 0) ? 'block' : 'none';
}

// ── Estado inicial: al editar, precarga lo que ya tiene el producto ──
const INITIAL_BY_KEY = {};
INITIAL_STOCKS.forEach(s => {
    INITIAL_BY_KEY[comboKey(s.size, s.color)] = { price: s.price, qty: s.qty, sku: s.sku };
    if (s.size  && !selectedSizes.includes(s.size))   selectedSizes.push(s.size);
    if (s.color && !selectedColors.includes(s.color)) selectedColors.push(s.color);
});

paintInheritedLabel();
renderSizeOptions();
paintSizeButtons();
paintColorButtons();
rebuildMatrix();
</script>
