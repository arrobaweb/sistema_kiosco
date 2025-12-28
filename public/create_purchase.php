<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

// suppliers for select
$stmt = $pdo->query('SELECT id,name FROM suppliers ORDER BY name');
$suppliers = $stmt->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<div class="row mb-3">
  <div class="col-12">
    <h1 class="h3">Registrar Compra</h1>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="mb-3">
      <input id="q" type="text" class="form-control" placeholder="Buscar producto por nombre o código y presionar Enter">
      <div id="results" class="mt-2"></div>
    </div>

    <form id="purchaseForm" method="post" action="process_purchase.php">
      <div id="formError" class="alert alert-danger d-none"></div>
      <div class="mb-3">
        <label class="form-label">Proveedor</label>
        <select name="supplier_id" class="form-select" style="width:300px">
          <option value="">-- Sin proveedor --</option>
          <?php foreach($suppliers as $s): ?>
            <option value="<?=$s['id']?>"><?=htmlspecialchars($s['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="table-responsive">
        <table id="cart" class="table table-sm table-bordered">
          <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio compra</th><th>Subtotal</th><th></th></tr></thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="d-flex justify-content-end mb-3">
        <div class="text-end">
          <div>Subtotal: <strong id="subtotal">0.00</strong></div>
          <div class="mt-2">Impuesto (%): <input id="tax_percent" type="number" step="0.01" value="0" class="form-control form-control-sm d-inline-block" style="width:100px"> — <span id="tax_amount">0.00</span></div>
          <div class="mt-2">Total: <strong id="total">0.00</strong></div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Método de pago</label>
        <select name="payment_method" id="payment_method" class="form-select" style="width:200px">
          <option value="efectivo">Efectivo</option>
          <option value="transferencia">Transferencia</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Número de factura</label>
        <input class="form-control" type="text" name="invoice_number">
      </div>

      <div class="text-end">
        <button class="btn btn-primary" type="submit">Registrar Compra</button>
      </div>
    </form>
  </div>
</div>

<script>
const resultsEl = document.getElementById('results');
const qEl = document.getElementById('q');
const cartBody = document.querySelector('#cart tbody');
const purchaseForm = document.getElementById('purchaseForm');
function formatMoney(n){return n.toFixed(2)}
async function search(q){
  const res = await fetch('products_search.php?q=' + encodeURIComponent(q));
  const data = await res.json(); renderResults(data);
}
function renderResults(items){
  if (!items || items.length === 0){ resultsEl.innerHTML = '<p>No se encontraron productos.</p>'; return; }
  let html = '<table class="table table-sm"><thead><tr><th>Código</th><th>Nombre</th><th>Stock</th><th></th></tr></thead><tbody>';
  for (const p of items){
    html += `<tr><td>${escapeHtml(p.code||'')}</td><td>${escapeHtml(p.name)}</td><td class="right">${p.stock}</td><td><button data-id="${p.id}">Agregar</button></td></tr>`;
  }
  html += '</tbody></table>';
  resultsEl.innerHTML = html;
  resultsEl.querySelectorAll('button[data-id]').forEach(b=>b.addEventListener('click', ()=>{ addToCart(b.dataset.id); }));
}
function escapeHtml(s){return String(s).replace(/[&<>\"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
function addToCart(id){
  id = parseInt(id);
  const existing = cartBody.querySelector(`tr[data-id="${id}"]`);
  if (existing){ const qtyEl = existing.querySelector('.qty'); qtyEl.value = parseInt(qtyEl.value)+1; updateRow(existing); updateTotal(); return; }
  const tr = document.createElement('tr'); tr.dataset.id = id;
  tr.innerHTML = `<td class="name">Producto ${id}</td><td><input class="qty" type="number" value="1" min="1" style="width:80px"></td><td><input class="buyprice" type="number" step="0.01" value="0.00" style="width:100px"></td><td class="subtotal right">0.00</td><td><button class="remove">X</button></td>`;
  cartBody.appendChild(tr);
  tr.querySelector('.qty').addEventListener('change', ()=>{ updateRow(tr); updateTotal(); });
  tr.querySelector('.buyprice').addEventListener('input', ()=>{ updateRow(tr); updateTotal(); });
  tr.querySelector('.remove').addEventListener('click', ()=>{ tr.remove(); updateTotal(); });
  fetch('products_search.php?id='+id).then(r=>r.json()).then(json=>{ if (json && json.name) tr.querySelector('.name').textContent = json.name; if (json && json.price){ const bp = tr.querySelector('.buyprice'); if (!bp.value || parseFloat(bp.value) === 0) bp.value = parseFloat(json.price).toFixed(2); } updateRow(tr); updateTotal(); }).catch(()=>{});
  updateTotal();
}
function updateRow(tr){
  const qty = parseInt(tr.querySelector('.qty').value) || 0;
  const price = parseFloat(tr.querySelector('.buyprice').value) || 0;
  const subtotalLine = qty * price;
  tr.querySelector('.subtotal').textContent = formatMoney(subtotalLine);
}
function updateTotal(){
  let subtotal = 0; cartBody.querySelectorAll('tr').forEach(tr=>{ subtotal += parseFloat(tr.querySelector('.subtotal').textContent) || 0; });
  const taxPercent = parseFloat(document.getElementById('tax_percent').value) || 0;
  const taxAmount = subtotal * (taxPercent/100);
  const total = subtotal + taxAmount;
  document.getElementById('subtotal').textContent = formatMoney(subtotal);
  document.getElementById('tax_amount').textContent = formatMoney(taxAmount);
  document.getElementById('total').textContent = formatMoney(total);
}
// on submit, create hidden inputs: qty[id], purchase_price[id], tax_percent
const formError = document.getElementById('formError');
purchaseForm.addEventListener('submit', function(e){
  formError.classList.add('d-none'); formError.textContent = '';
  const rows = cartBody.querySelectorAll('tr');
  if (rows.length === 0){ e.preventDefault(); formError.textContent = 'El carrito está vacío.'; formError.classList.remove('d-none'); return; }
  const errors = [];
  rows.forEach(tr=>{
    const qty = parseInt(tr.querySelector('.qty').value) || 0;
    const price = parseFloat(tr.querySelector('.buyprice').value) || 0;
    if (qty <= 0) errors.push('Cantidad inválida para ' + (tr.querySelector('.name').textContent || 'producto'));
    if (price <= 0) errors.push('Precio de compra inválido para ' + (tr.querySelector('.name').textContent || 'producto'));
  });
  if (errors.length){ e.preventDefault(); formError.innerHTML = errors.map(x=>'<div>'+escapeHtml(x)+'</div>').join(''); formError.classList.remove('d-none'); return; }

  // remove existing
  document.querySelectorAll('input[name^="qty["]').forEach(n=>n.remove());
  document.querySelectorAll('input[name^="purchase_price["]').forEach(n=>n.remove());
  rows.forEach(tr=>{
    const id = tr.dataset.id; const qty = tr.querySelector('.qty').value; const price = tr.querySelector('.buyprice').value;
    const i1 = document.createElement('input'); i1.type='hidden'; i1.name=`qty[${id}]`; i1.value = qty; this.appendChild(i1);
    const i2 = document.createElement('input'); i2.type='hidden'; i2.name=`purchase_price[${id}]`; i2.value = price; this.appendChild(i2);
  });
  // tax
  document.querySelectorAll('input[name="tax_percent"]').forEach(n=>n.remove());
  const ti = document.createElement('input'); ti.type='hidden'; ti.name='tax_percent'; ti.value = document.getElementById('tax_percent').value; this.appendChild(ti);
});

qEl.addEventListener('keydown', e=>{ if (e.key === 'Enter'){ e.preventDefault(); search(qEl.value); } });
</script>
<?php require_once __DIR__ . '/_footer.php'; ?>