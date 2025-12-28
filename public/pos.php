<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
require_once __DIR__ . '/_header.php';
?>
<div class="row mb-3">
  <div class="col-12">
    <h1 class="h3">Punto de Venta</h1>
  </div>
</div>

<div class="row mb-3">
  <div class="col-md-8">
    <div class="input-group">
      <input id="q" type="text" class="form-control" placeholder="escribir nombre o código y presionar Enter">
      <button id="btnSearch" class="btn btn-outline-secondary">Buscar</button>
    </div>
  </div>
</div>

<div id="results" class="mb-3"></div>

  <div class="card">
    <div class="card-body">
      <h2 class="h5 mb-3">Carrito</h2>
      <form id="saleForm" method="post" action="process_sale.php">
        <div class="table-responsive">
          <table id="cart" class="table table-sm table-bordered">
            <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Descuento (%)</th><th>Subtotal</th><th></th></tr></thead>
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
            <option value="tarjeta">Tarjeta</option>
          </select>
        </div>

        <div class="text-end">
          <button class="btn btn-primary" type="submit">Registrar Venta</button>
        </div>
      </form>
    </div>
  </div> 

  <script>
    const resultsEl = document.getElementById('results');
    const qEl = document.getElementById('q');
    const btnSearch = document.getElementById('btnSearch');
    const cartBody = document.querySelector('#cart tbody');
    const totalEl = document.getElementById('total');

    function formatMoney(n){return n.toFixed(2)}

    async function search(q){
      const res = await fetch('products_search.php?q=' + encodeURIComponent(q));
      const data = await res.json();
      renderResults(data);
    }

    function renderResults(items){
      if (!items || items.length === 0){ resultsEl.innerHTML = '<p>No se encontraron productos.</p>'; return; }
      let html = '<table><thead><tr><th>Código</th><th>Nombre</th><th>Precio</th><th>Stock</th><th></th></tr></thead><tbody>';
      for (const p of items){
        html += `<tr><td>${escapeHtml(p.code||'')}</td><td>${escapeHtml(p.name)}</td><td class="right">${formatMoney(parseFloat(p.price))}</td><td class="right">${p.stock}</td><td><button data-id="${p.id}" data-price="${p.price}">Agregar</button></td></tr>`;
      }
      html += '</tbody></table>';
      resultsEl.innerHTML = html;
      resultsEl.querySelectorAll('button[data-id]').forEach(b=>b.addEventListener('click', ()=>{
        addToCart(b.dataset.id, b.dataset.price);
      }));
    }

    function escapeHtml(s){return String(s).replace(/[&<>\"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}

    function addToCart(id, price){
      id = parseInt(id);
      price = parseFloat(price);
      const existing = cartBody.querySelector(`tr[data-id="${id}"]`);
      if (existing){
        const qtyEl = existing.querySelector('.qty');
        qtyEl.value = parseInt(qtyEl.value)+1;
        updateRow(existing);
        updateTotal();
        return;
      }
      const tr = document.createElement('tr');
      tr.dataset.id = id;
      tr.innerHTML = `<td class="name">Producto ${id}</td><td><input class="qty" type="number" value="1" min="1" style="width:60px"></td><td class="price right">${formatMoney(price)}</td><td><input class="disc" type="number" step="0.01" value="0" style="width:60px"> %</td><td class="subtotal right">${formatMoney(price)}</td><td><button class="remove">X</button></td>`;
      cartBody.appendChild(tr);
      tr.querySelector('.qty').addEventListener('change', ()=>{ updateRow(tr); updateTotal(); });
      tr.querySelector('.disc').addEventListener('input', ()=>{ updateRow(tr); updateTotal(); });
      tr.querySelector('.remove').addEventListener('click', ()=>{ tr.remove(); updateTotal(); });
      // fetch product name
      fetch('products_search.php?id='+id).then(r=>r.json()).then(json=>{
        if (json && json.name) tr.querySelector('.name').textContent = json.name;
        if (json && json.price) tr.querySelector('.price').textContent = formatMoney(parseFloat(json.price));
        updateRow(tr); updateTotal();
      }).catch(()=>{});
      updateTotal();
    }

    function updateRow(tr){
      const qty = parseInt(tr.querySelector('.qty').value) || 0;
      const price = parseFloat(tr.querySelector('.price').textContent.replace(',','.')) || 0;
      const discPercent = parseFloat(tr.querySelector('.disc').value) || 0;
      const lineWithoutDisc = qty * price;
      const discAmount = lineWithoutDisc * (discPercent/100);
      const subtotalLine = lineWithoutDisc - discAmount;
      tr.querySelector('.subtotal').textContent = formatMoney(subtotalLine);
    }

    function updateTotal(){
      let subtotal = 0;
      cartBody.querySelectorAll('tr').forEach(tr=>{
        const st = parseFloat(tr.querySelector('.subtotal').textContent) || 0;
        subtotal += st;
      });
      const taxPercent = parseFloat(document.getElementById('tax_percent').value) || 0;
      const taxAmount = subtotal * (taxPercent/100);
      const total = subtotal + taxAmount;

      document.getElementById('subtotal').textContent = formatMoney(subtotal);
      document.getElementById('tax_amount').textContent = formatMoney(taxAmount);
      totalEl.textContent = formatMoney(total);
    }

    // submit: create hidden qty inputs like qty[ID]=Q and include discount/tax
    document.getElementById('saleForm').addEventListener('submit', function(e){
      if (cartBody.querySelectorAll('tr').length === 0){ e.preventDefault(); alert('Carrito vacío'); return; }
      // remove existing hidden inputs
      document.querySelectorAll('input[name^="qty["]').forEach(n=>n.remove());
      cartBody.querySelectorAll('tr').forEach(tr=>{
        const id = tr.dataset.id;
        const qty = tr.querySelector('.qty').value;
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = `qty[${id}]`; inp.value = qty;
        this.appendChild(inp);
      });
      // add tax hidden input
      document.querySelectorAll('input[name="tax_percent"]').forEach(n=>n.remove());
      const ti = document.createElement('input'); ti.type='hidden'; ti.name='tax_percent'; ti.value = document.getElementById('tax_percent').value; this.appendChild(ti);
      // add per-item discount hidden inputs
      document.querySelectorAll('input[name^="item_discount["]').forEach(n=>n.remove());
      cartBody.querySelectorAll('tr').forEach(tr=>{
        const id = tr.dataset.id;
        const disc = tr.querySelector('.disc').value || 0;
        const inp = document.createElement('input'); inp.type='hidden'; inp.name = `item_discount[${id}]`; inp.value = disc; this.appendChild(inp);
      });
    });

    btnSearch.addEventListener('click', ()=>search(qEl.value));
    qEl.addEventListener('keydown', e=>{ if (e.key === 'Enter'){ e.preventDefault(); search(qEl.value); } });
    document.getElementById('tax_percent').addEventListener('input', updateTotal);
  </script>
<?php require_once __DIR__ . '/_footer.php'; ?>
