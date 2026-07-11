(function () {
  const root = document.getElementById('quote-editor');
  const data = window.QE_INITIAL;
  const companies = window.QE_COMPANIES;
  const paymentTemplates = window.QE_PAYMENT_TEMPLATES;
  const csrf = window.QE_CSRF;
  let rowSeq = 0;
  let sectionSeq = 0;

  function esc(s) {
    return (s ?? '').toString().replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }
  function money(v) {
    return (isFinite(v) ? v : 0).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
  }
  function num(v) { const n = parseFloat(String(v).replace(',', '.')); return isFinite(n) ? n : 0; }

  // ---- Ricostruisce le sezioni a partire dall'elenco piatto di righe ----
  function groupItemsToSections(items) {
    const sections = [];
    let current = null;
    (items || []).forEach(it => {
      const title = it.section || '';
      if (!current || current._title !== title) {
        current = { id: 's' + (sectionSeq++), _title: title, items: [] };
        sections.push(current);
      }
      current.items.push(it);
    });
    if (sections.length === 0) {
      sections.push({ id: 's' + (sectionSeq++), _title: 'Struttura e allestimento', items: [] });
    }
    return sections;
  }

  const initialSections = groupItemsToSections(data.items);

  const unitOptions = ['pz', 'mq', 'ml', 'cad', 'gg', 'h', 'forfait', 'mese'];

  function headerHtml() {
    const companyButtons = companies.map(c => `
      <label class="company-pick" style="display:flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:8px;padding:10px 14px;cursor:pointer">
        <input type="radio" name="company_id" value="${c.id}" ${String(data.company_id) === String(c.id) ? 'checked' : ''} style="width:auto">
        <span>${esc(c.short_name)}</span>
      </label>`).join('');

    const templateOptions = paymentTemplates.map(t => `<option value="${t.id}">${esc(t.name)}</option>`).join('');

    return `
    <fieldset>
      <legend>Azienda emittente</legend>
      <div class="flex gap-12">${companyButtons}</div>
    </fieldset>

    <div class="grid grid-2">
      <fieldset>
        <legend>Cliente</legend>
        <div class="field qe-picker">
          <label>Cerca cliente esistente *</label>
          <input type="text" id="client-search" autocomplete="off" placeholder="Digita ragione sociale…" value="${esc(data.client_label || '')}">
          <input type="hidden" id="client_id" value="${data.client_id || ''}">
          <div class="qe-picker-results" id="client-results" style="display:none"></div>
        </div>
        <a href="client_form.php" target="_blank" class="small">+ Crea nuovo cliente in un'altra scheda</a>
      </fieldset>

      <fieldset>
        <legend>Dati evento</legend>
        <div class="field"><label>Oggetto / evento *</label><input type="text" id="f-title" value="${esc(data.title)}" placeholder="Es. Allestimento stand Fiera XY 2026"></div>
        <div class="grid grid-2">
          <div class="field"><label>Luogo</label><input type="text" id="f-location" value="${esc(data.event_location)}"></div>
          <div class="field"><label>Date evento</label>
            <div class="flex gap-8">
              <input type="date" id="f-event-start" value="${esc(data.event_start)}">
              <input type="date" id="f-event-end" value="${esc(data.event_end)}">
            </div>
          </div>
        </div>
      </fieldset>
    </div>

    <div class="grid grid-3">
      <div class="field"><label>Data emissione</label><input type="date" id="f-issue-date" value="${esc(data.issue_date)}"></div>
      <div class="field"><label>Validità offerta (giorni)</label><input type="number" id="f-validity" value="${esc(data.validity_days)}" min="1"></div>
      <div class="field"><label>Aliquota IVA</label>
        <select id="f-vat-rate">
          <option value="22" ${Number(data.vat_rate) === 22 ? 'selected' : ''}>22%</option>
          <option value="10" ${Number(data.vat_rate) === 10 ? 'selected' : ''}>10%</option>
          <option value="4" ${Number(data.vat_rate) === 4 ? 'selected' : ''}>4%</option>
          <option value="0" ${Number(data.vat_rate) === 0 ? 'selected' : ''}>0% (esente / N.I.)</option>
        </select>
      </div>
    </div>
    <div class="field"><label>Dicitura IVA stampata</label><input type="text" id="f-vat-label" value="${esc(data.vat_label)}"></div>

    <div id="qe-sections"></div>

    <div class="flex gap-8" style="margin-bottom:20px">
      <button type="button" class="btn btn-outline btn-sm" id="btn-add-section">+ Aggiungi sezione</button>
    </div>

    <div class="grid grid-2">
      <fieldset>
        <legend>Sconto complessivo</legend>
        <div class="flex gap-8">
          <select id="f-discount-type" style="max-width:140px">
            <option value="percent" ${data.discount_type === 'percent' ? 'selected' : ''}>Percentuale %</option>
            <option value="amount" ${data.discount_type === 'amount' ? 'selected' : ''}>Importo fisso €</option>
          </select>
          <input type="text" inputmode="decimal" id="f-discount-value" value="${esc(data.discount_value)}">
        </div>
      </fieldset>

      <div class="qe-totals card" style="margin:0;box-shadow:none">
        <table id="qe-totals-table"></table>
      </div>
    </div>

    <div class="qe-margin-box" id="qe-margin-box" style="margin-bottom:20px"></div>

    <fieldset>
      <legend>Termini di pagamento</legend>
      <div class="field">
        <label>Template rapido</label>
        <select id="f-payment-template">
          <option value="">— seleziona un template per compilare —</option>
          ${templateOptions}
        </select>
      </div>
      <div class="field"><label>Testo termini di pagamento (modificabile)</label><textarea id="f-payment-terms">${esc(data.payment_terms)}</textarea></div>
    </fieldset>

    <div class="field"><label>Note per il cliente (stampate nel preventivo)</label><textarea id="f-notes" rows="4">${esc(data.notes)}</textarea></div>
    <div class="field"><label>Note interne <span class="small muted">— non stampate, visibili solo al team</span></label><textarea id="f-internal-notes">${esc(data.internal_notes)}</textarea></div>

    <div id="qe-alert-inner"></div>
    <div class="flex gap-8">
      <button type="button" class="btn btn-primary" id="btn-save">💾 Salva preventivo</button>
      <a href="quotes.php" class="btn btn-ghost">Annulla</a>
    </div>
    `;
  }

  function unitSelectHtml(selected) {
    return unitOptions.map(u => `<option value="${u}" ${u === selected ? 'selected' : ''}>${u}</option>`).join('');
  }

  function rowHtml(item) {
    const id = 'r' + (rowSeq++);
    return `
    <tr class="item-row ${item.optional ? 'qe-optional-row' : ''}" data-row-id="${id}" data-article-id="${item.article_id || ''}">
      <td class="col-desc qe-picker">
        <input type="text" class="row-desc" placeholder="Descrizione voce (cerca in libreria o scrivi liberamente)" value="${esc(item.description)}" autocomplete="off">
        <div class="qe-picker-results" style="display:none"></div>
      </td>
      <td class="col-num"><select class="row-unit">${unitSelectHtml(item.unit || 'pz')}</select></td>
      <td class="col-num"><input type="text" inputmode="decimal" class="row-qty" value="${esc(item.quantity ?? 1)}"></td>
      <td class="col-num"><input type="text" inputmode="decimal" class="row-price" value="${esc(item.unit_price ?? 0)}"></td>
      <td class="col-num"><input type="text" inputmode="decimal" class="row-disc" value="${esc(item.discount_percent ?? 0)}" title="Sconto riga %"></td>
      <td class="col-num"><input type="text" inputmode="decimal" class="row-cost" value="${esc(item.unit_cost ?? 0)}" title="Costo unitario (interno)"></td>
      <td class="col-num text-center"><input type="checkbox" class="row-optional" ${item.optional ? 'checked' : ''} title="Voce opzionale (non entra nel totale)" style="width:auto"></td>
      <td class="col-total row-total">0,00 €</td>
      <td><button type="button" class="qe-remove" title="Rimuovi riga">✕</button></td>
    </tr>`;
  }

  function sectionHtml(section) {
    const rowsHtml = (section.items.length ? section.items : [{}]).map(rowHtml).join('');
    return `
    <div class="qe-section" data-section-id="${section.id}">
      <div class="qe-section-header">
        <input type="text" class="section-title" value="${esc(section._title)}" placeholder="Titolo sezione (es. Struttura, Grafica, Impianti…)">
        <button type="button" class="btn btn-ghost btn-sm section-remove">Rimuovi sezione</button>
      </div>
      <div class="table-wrap">
        <table class="qe-items-table">
          <thead><tr>
            <th class="col-desc">Descrizione</th><th>UM</th><th>Qtà</th><th>Prezzo €</th><th>Sconto %</th><th>Costo €</th><th class="text-center">Opz.</th><th class="text-right">Totale</th><th></th>
          </tr></thead>
          <tbody class="section-body">${rowsHtml}</tbody>
        </table>
      </div>
      <button type="button" class="btn btn-outline btn-sm section-add-row">+ Aggiungi voce</button>
    </div>`;
  }

  root.innerHTML = headerHtml();
  const sectionsContainer = document.getElementById('qe-sections');
  initialSections.forEach(s => sectionsContainer.insertAdjacentHTML('beforeend', sectionHtml(s)));

  // ---------------- Ricalcolo totali ----------------
  function recalc() {
    let subtotal = 0, totalCost = 0;
    document.querySelectorAll('.item-row').forEach(row => {
      const qty = num(row.querySelector('.row-qty').value);
      const price = num(row.querySelector('.row-price').value);
      const disc = num(row.querySelector('.row-disc').value);
      const cost = num(row.querySelector('.row-cost').value);
      const optional = row.querySelector('.row-optional').checked;
      const lineGross = qty * price;
      const lineNet = lineGross * (1 - disc / 100);
      row.querySelector('.row-total').textContent = money(lineNet) + (optional ? ' (opz.)' : '');
      row.classList.toggle('qe-optional-row', optional);
      if (!optional) {
        subtotal += lineNet;
        totalCost += qty * cost;
      }
    });

    const discountType = document.getElementById('f-discount-type').value;
    const discountValue = num(document.getElementById('f-discount-value').value);
    const discountAmount = Math.min(discountType === 'percent' ? subtotal * (discountValue / 100) : discountValue, subtotal);
    const taxable = subtotal - discountAmount;
    const vatRate = num(document.getElementById('f-vat-rate').value);
    const vatAmount = taxable * (vatRate / 100);
    const total = taxable + vatAmount;
    const margin = taxable - totalCost;
    const marginPct = taxable > 0 ? (margin / taxable) * 100 : 0;

    document.getElementById('qe-totals-table').innerHTML = `
      <tr><td class="label">Subtotale</td><td class="val">${money(subtotal)}</td></tr>
      <tr><td class="label">Sconto</td><td class="val">− ${money(discountAmount)}</td></tr>
      <tr><td class="label">Imponibile</td><td class="val">${money(taxable)}</td></tr>
      <tr><td class="label">${esc(document.getElementById('f-vat-label').value || 'IVA')}</td><td class="val">${money(vatAmount)}</td></tr>
      <tr class="total-row"><td class="label">Totale</td><td class="val">${money(total)}</td></tr>
    `;
    document.getElementById('qe-margin-box').innerHTML =
      `📊 <strong>Analisi interna (non stampata):</strong> costo totale stimato ${money(totalCost)} · margine ${money(margin)} (${marginPct.toFixed(1)}%)`;
  }

  // ---------------- Autocomplete articoli ----------------
  let debounceTimer;
  function bindArticlePicker(row) {
    const input = row.querySelector('.row-desc');
    const results = row.querySelector('.qe-picker-results');
    input.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      const q = input.value.trim();
      if (q.length < 2) { results.style.display = 'none'; return; }
      debounceTimer = setTimeout(async () => {
        const res = await fetch('api/articles_search.php?q=' + encodeURIComponent(q));
        const list = await res.json();
        if (!list.length) { results.style.display = 'none'; return; }
        results.innerHTML = list.map(a => `<div data-id="${a.id}" data-name="${esc(a.name)}" data-unit="${a.unit}" data-price="${a.sell_price}" data-cost="${a.cost_price}">
            ${esc(a.name)}<div class="cat">${esc(a.category_name || '')} · ${money(a.sell_price)} / ${a.unit}</div></div>`).join('');
        results.style.display = 'block';
      }, 220);
    });
    results.addEventListener('mousedown', e => {
      const div = e.target.closest('div[data-id]');
      if (!div) return;
      input.value = div.dataset.name;
      row.dataset.articleId = div.dataset.id;
      row.querySelector('.row-unit').value = div.dataset.unit;
      row.querySelector('.row-price').value = div.dataset.price;
      row.querySelector('.row-cost').value = div.dataset.cost;
      results.style.display = 'none';
      recalc();
    });
    input.addEventListener('blur', () => setTimeout(() => results.style.display = 'none', 150));
    input.addEventListener('input', () => { row.dataset.articleId = ''; });
  }
  document.querySelectorAll('.item-row').forEach(bindArticlePicker);

  // ---------------- Autocomplete cliente ----------------
  const clientSearch = document.getElementById('client-search');
  const clientId = document.getElementById('client_id');
  const clientResults = document.getElementById('client-results');
  clientSearch.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const q = clientSearch.value.trim();
    clientId.value = '';
    if (q.length < 2) { clientResults.style.display = 'none'; return; }
    debounceTimer = setTimeout(async () => {
      const res = await fetch('api/clients_search.php?q=' + encodeURIComponent(q));
      const list = await res.json();
      if (!list.length) { clientResults.style.display = 'none'; return; }
      clientResults.innerHTML = list.map(c => `<div data-id="${c.id}" data-name="${esc(c.ragione_sociale)}">
          ${esc(c.ragione_sociale)}<div class="cat">${esc(c.citta || '')} ${c.piva ? '· P.IVA ' + esc(c.piva) : ''}</div></div>`).join('');
      clientResults.style.display = 'block';
    }, 220);
  });
  clientResults.addEventListener('mousedown', e => {
    const div = e.target.closest('div[data-id]');
    if (!div) return;
    clientSearch.value = div.dataset.name;
    clientId.value = div.dataset.id;
    clientResults.style.display = 'none';
  });
  clientSearch.addEventListener('blur', () => setTimeout(() => clientResults.style.display = 'none', 150));

  // ---------------- Template pagamento ----------------
  document.getElementById('f-payment-template').addEventListener('change', e => {
    const tpl = paymentTemplates.find(t => String(t.id) === e.target.value);
    if (tpl) document.getElementById('f-payment-terms').value = tpl.body;
  });

  document.getElementById('f-vat-rate').addEventListener('change', e => {
    const labels = { '22': 'IVA 22%', '10': 'IVA 10%', '4': 'IVA 4%', '0': 'Operazione esente/non imponibile IVA' };
    document.getElementById('f-vat-label').value = labels[e.target.value] || ('IVA ' + e.target.value + '%');
    recalc();
  });

  // ---------------- Aggiunta/rimozione righe e sezioni ----------------
  document.getElementById('btn-add-section').addEventListener('click', () => {
    sectionsContainer.insertAdjacentHTML('beforeend', sectionHtml({ id: 's' + (sectionSeq++), _title: '', items: [{}] }));
    const newSection = sectionsContainer.lastElementChild;
    bindArticlePicker(newSection.querySelector('.item-row'));
    recalc();
  });

  sectionsContainer.addEventListener('click', e => {
    if (e.target.classList.contains('section-add-row')) {
      const tbody = e.target.closest('.qe-section').querySelector('.section-body');
      tbody.insertAdjacentHTML('beforeend', rowHtml({}));
      bindArticlePicker(tbody.lastElementChild);
      recalc();
    }
    if (e.target.classList.contains('qe-remove')) {
      const tbody = e.target.closest('.section-body');
      e.target.closest('tr').remove();
      if (!tbody.querySelector('.item-row')) {
        tbody.insertAdjacentHTML('beforeend', rowHtml({}));
        bindArticlePicker(tbody.lastElementChild);
      }
      recalc();
    }
    if (e.target.classList.contains('section-remove')) {
      if (sectionsContainer.querySelectorAll('.qe-section').length <= 1) {
        alert('Deve rimanere almeno una sezione.');
        return;
      }
      if (confirm('Rimuovere questa sezione e tutte le sue voci?')) {
        e.target.closest('.qe-section').remove();
        recalc();
      }
    }
  });

  root.addEventListener('input', e => {
    if (e.target.matches('.row-qty, .row-price, .row-disc, .row-cost, .row-optional, #f-discount-type, #f-discount-value, #f-vat-label')) {
      recalc();
    }
  });
  root.addEventListener('change', e => {
    if (e.target.matches('.row-optional, #f-discount-type, #f-vat-rate')) recalc();
  });

  // ---------------- Salvataggio ----------------
  document.getElementById('btn-save').addEventListener('click', async () => {
    const alertBox = document.getElementById('qe-alert-inner');
    alertBox.innerHTML = '';

    if (!clientId.value) {
      alertBox.innerHTML = '<div class="alert alert-error">Seleziona un cliente dall\'archivio (digita e scegli dal menù a tendina).</div>';
      window.scrollTo(0, 0);
      return;
    }
    const title = document.getElementById('f-title').value.trim();
    if (!title) {
      alertBox.innerHTML = '<div class="alert alert-error">Indica l\'oggetto del preventivo.</div>';
      return;
    }

    const items = [];
    let sortOrder = 0;
    document.querySelectorAll('.qe-section').forEach(section => {
      const sectionTitle = section.querySelector('.section-title').value.trim();
      section.querySelectorAll('.item-row').forEach(row => {
        const description = row.querySelector('.row-desc').value.trim();
        if (!description) return;
        items.push({
          article_id: row.dataset.articleId || null,
          section: sectionTitle,
          sort_order: sortOrder++,
          description,
          unit: row.querySelector('.row-unit').value,
          quantity: num(row.querySelector('.row-qty').value),
          unit_price: num(row.querySelector('.row-price').value),
          unit_cost: num(row.querySelector('.row-cost').value),
          discount_percent: num(row.querySelector('.row-disc').value),
          optional: row.querySelector('.row-optional').checked ? 1 : 0,
        });
      });
    });

    if (!items.length) {
      alertBox.innerHTML = '<div class="alert alert-error">Aggiungi almeno una voce con descrizione.</div>';
      return;
    }

    const payload = {
      id: data.id,
      company_id: document.querySelector('input[name=company_id]:checked')?.value,
      client_id: clientId.value,
      title,
      event_location: document.getElementById('f-location').value,
      event_start: document.getElementById('f-event-start').value || null,
      event_end: document.getElementById('f-event-end').value || null,
      issue_date: document.getElementById('f-issue-date').value,
      validity_days: parseInt(document.getElementById('f-validity').value, 10) || 30,
      discount_type: document.getElementById('f-discount-type').value,
      discount_value: num(document.getElementById('f-discount-value').value),
      vat_rate: num(document.getElementById('f-vat-rate').value),
      vat_label: document.getElementById('f-vat-label').value,
      payment_terms: document.getElementById('f-payment-terms').value,
      notes: document.getElementById('f-notes').value,
      internal_notes: document.getElementById('f-internal-notes').value,
      items,
    };

    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    btn.textContent = 'Salvataggio…';
    try {
      const res = await fetch('api/quote_save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify(payload),
      });
      const out = await res.json();
      if (!res.ok) throw new Error(out.error || 'Errore nel salvataggio');
      window.location.href = window.QE_VIEW_URL + '?id=' + out.id + '&saved=1';
    } catch (err) {
      alertBox.innerHTML = '<div class="alert alert-error">' + esc(err.message) + '</div>';
      btn.disabled = false;
      btn.textContent = '💾 Salva preventivo';
    }
  });

  recalc();
})();
