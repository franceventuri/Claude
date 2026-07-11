-- Schema database Preventivi Henoto / Emvisia
-- Creato automaticamente al primo avvio dell'app (vedi db.php)

CREATE TABLE companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    legal_name TEXT NOT NULL,
    short_name TEXT NOT NULL,
    address TEXT,
    zip TEXT,
    city TEXT,
    province TEXT,
    country TEXT NOT NULL DEFAULT 'Italia',
    piva TEXT,
    cf TEXT,
    rea TEXT,
    share_capital TEXT,
    pec TEXT,
    email TEXT,
    phone TEXT,
    website TEXT,
    iban TEXT,
    bank_name TEXT,
    logo_path TEXT,
    quote_prefix TEXT NOT NULL,
    next_quote_seq INTEGER NOT NULL DEFAULT 1,
    quote_seq_year INTEGER,
    default_vat_rate REAL NOT NULL DEFAULT 22,
    default_validity_days INTEGER NOT NULL DEFAULT 30,
    default_payment_terms_id INTEGER,
    footer_notes TEXT
);

CREATE TABLE clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ragione_sociale TEXT NOT NULL,
    referente TEXT,
    indirizzo TEXT,
    cap TEXT,
    citta TEXT,
    provincia TEXT,
    nazione TEXT NOT NULL DEFAULT 'Italia',
    piva TEXT,
    cf TEXT,
    telefono TEXT,
    email TEXT,
    pec TEXT,
    sdi_code TEXT,
    note TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE articles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    code TEXT,
    name TEXT NOT NULL,
    description TEXT,
    unit TEXT NOT NULL DEFAULT 'pz',
    sell_price REAL NOT NULL DEFAULT 0,
    cost_price REAL NOT NULL DEFAULT 0,
    supplier TEXT,
    notes TEXT,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE payment_terms_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    body TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE quotes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quote_number TEXT NOT NULL UNIQUE,
    company_id INTEGER NOT NULL REFERENCES companies(id),
    client_id INTEGER NOT NULL REFERENCES clients(id),
    title TEXT NOT NULL,
    event_location TEXT,
    event_start TEXT,
    event_end TEXT,
    issue_date TEXT NOT NULL,
    validity_days INTEGER NOT NULL DEFAULT 30,
    status TEXT NOT NULL DEFAULT 'bozza',
    discount_type TEXT NOT NULL DEFAULT 'percent',
    discount_value REAL NOT NULL DEFAULT 0,
    vat_rate REAL NOT NULL DEFAULT 22,
    vat_label TEXT DEFAULT 'IVA 22%',
    subtotal REAL NOT NULL DEFAULT 0,
    discount_amount REAL NOT NULL DEFAULT 0,
    taxable REAL NOT NULL DEFAULT 0,
    vat_amount REAL NOT NULL DEFAULT 0,
    total REAL NOT NULL DEFAULT 0,
    total_cost REAL NOT NULL DEFAULT 0,
    margin REAL NOT NULL DEFAULT 0,
    margin_pct REAL NOT NULL DEFAULT 0,
    payment_terms TEXT,
    notes TEXT,
    internal_notes TEXT,
    share_token TEXT UNIQUE,
    accepted_at TEXT,
    accepted_by TEXT,
    accepted_ip TEXT,
    revision_of INTEGER REFERENCES quotes(id),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE quote_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quote_id INTEGER NOT NULL REFERENCES quotes(id) ON DELETE CASCADE,
    article_id INTEGER REFERENCES articles(id) ON DELETE SET NULL,
    section TEXT,
    sort_order INTEGER NOT NULL DEFAULT 0,
    description TEXT NOT NULL,
    unit TEXT NOT NULL DEFAULT 'pz',
    quantity REAL NOT NULL DEFAULT 1,
    unit_price REAL NOT NULL DEFAULT 0,
    unit_cost REAL NOT NULL DEFAULT 0,
    discount_percent REAL NOT NULL DEFAULT 0,
    optional INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE settings (
    key TEXT PRIMARY KEY,
    value TEXT
);

CREATE INDEX idx_quotes_client ON quotes(client_id);
CREATE INDEX idx_quotes_company ON quotes(company_id);
CREATE INDEX idx_quote_items_quote ON quote_items(quote_id);
CREATE INDEX idx_articles_category ON articles(category_id);

-- =====================================================================
-- Dati iniziali
-- =====================================================================

INSERT INTO payment_terms_templates (id, name, body, sort_order) VALUES
(1, '30/40/30', '30% dell''importo a conferma dell''ordine, 40% a inizio allestimento, 30% a saldo entro 30 giorni data fattura.', 0),
(2, '50/50', '50% dell''importo a conferma dell''ordine, 50% a saldo a fine lavori/consegna.', 1),
(3, 'Saldo anticipato', '100% dell''importo a conferma dell''ordine, prima dell''avvio dei lavori.', 2),
(4, '30 gg data fattura fine mese', 'Pagamento a 30 giorni data fattura fine mese (DF FM 30), tramite bonifico bancario.', 3),
(5, 'Acconto + saldo a consegna', '40% dell''importo a conferma dell''ordine come acconto, 60% a saldo alla consegna/apertura evento.', 4);

INSERT INTO companies (
    id, code, legal_name, short_name, address, zip, city, province, country,
    piva, cf, rea, pec, email, phone, website, iban, bank_name, logo_path,
    quote_prefix, next_quote_seq, default_vat_rate, default_validity_days,
    default_payment_terms_id, footer_notes
) VALUES (
    1, 'HEN', 'Henoto S.p.A.', 'Henoto',
    'Via Tolomeo, 14/16', '35028', 'Piove di Sacco', 'PD', 'Italia',
    '03275590283', '03275590283', 'PD-297316', 'henoto@pec.it', 'info@henoto.com', '+39 049 5800133', 'www.henoto.com',
    '', '', 'assets/img/henoto-logo.svg',
    'HEN', 1, 22, 30, 1,
    'Henoto S.p.A. - Strutture e attrezzature per fiere, eventi, retail e allestimenti temporanei.'
);

INSERT INTO companies (
    id, code, legal_name, short_name, address, zip, city, province, country,
    piva, cf, rea, pec, email, phone, website, iban, bank_name, logo_path,
    quote_prefix, next_quote_seq, default_vat_rate, default_validity_days,
    default_payment_terms_id, footer_notes
) VALUES (
    2, 'EMV', 'Emvisia S.r.l.', 'Emvisia',
    'Via Tolomeo, 14/16', '35028', 'Piove di Sacco', 'PD', 'Italia',
    '03743520284', '03743520284', '', '', 'info@emvisia.com', '+39 049 5800133', 'www.emvisia.com',
    '', '', 'assets/img/emvisia-logo.svg',
    'EMV', 1, 22, 30, 1,
    'Emvisia S.r.l. - Grafica, stampa e allestimenti per il retail e la comunicazione visiva.'
);

INSERT INTO settings (key, value) VALUES
('app_installed_at', datetime('now')),
('vat_rates', '22|22%;10|10%;4|4%;0|Esente/non imponibile (specificare in nota)');
-- Categorie
INSERT INTO categories (id, name, sort_order) VALUES (1, 'Progettazione e servizi tecnici', 0);
INSERT INTO categories (id, name, sort_order) VALUES (2, 'Strutture espositive e stand', 1);
INSERT INTO categories (id, name, sort_order) VALUES (3, 'Pannelli e superfici', 2);
INSERT INTO categories (id, name, sort_order) VALUES (4, 'Arredi', 3);
INSERT INTO categories (id, name, sort_order) VALUES (5, 'Impianto elettrico e illuminotecnica', 4);
INSERT INTO categories (id, name, sort_order) VALUES (6, 'Audio, video e multimedia', 5);
INSERT INTO categories (id, name, sort_order) VALUES (7, 'Grafica e stampa', 6);
INSERT INTO categories (id, name, sort_order) VALUES (8, 'Pavimentazioni', 7);
INSERT INTO categories (id, name, sort_order) VALUES (9, 'Sollevamento, ponteggi e carpenteria', 8);
INSERT INTO categories (id, name, sort_order) VALUES (10, 'Trasporti, logistica e manodopera', 9);
INSERT INTO categories (id, name, sort_order) VALUES (11, 'Verde e allestimento scenico', 10);
INSERT INTO categories (id, name, sort_order) VALUES (12, 'Servizi accessori e sicurezza', 11);

-- Articoli
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (1, 'PRG-001', 'Progettazione preliminare stand (concept + planimetria)', 'forfait', 450, 200);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (1, 'PRG-002', 'Progettazione esecutiva 3D fotorealistica', 'forfait', 650, 280);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (1, 'PRG-003', 'Rendering 3D aggiuntivo (vista singola)', 'cad', 120, 50);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (1, 'PRG-004', 'Disegno tecnico esecutivo per ufficio tecnico ente fiera', 'cad', 180, 70);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (1, 'PRG-005', 'Pratiche tecniche e permessi (SUAP, VVF, ente fiera)', 'forfait', 380, 150);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (1, 'PRG-006', 'Sopralluogo tecnico in loco', 'cad', 150, 60);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (1, 'PRG-007', 'Direzione lavori in cantiere', 'gg', 320, 150);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (1, 'PRG-008', 'Project management evento', 'gg', 380, 180);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (1, 'PRG-009', 'Computo metrico e capitolato tecnico', 'forfait', 280, 100);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-001', 'Struttura stand sistema Octanorm/Maxima', 'mq', 85, 40);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-002', 'Parete divisoria tamburata h.250cm', 'ml', 65, 28);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-003', 'Parete divisoria tamburata h.300cm', 'ml', 78, 34);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-004', 'Parete curva raggiata su misura', 'ml', 110, 50);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-005', 'Soppalco calpestabile con struttura portante', 'mq', 180, 85);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-006', 'Torre/totem espositivo h.300cm', 'pz', 420, 190);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-007', 'Passerella/pedana rialzata', 'mq', 55, 24);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-008', 'Gazebo pieghevole 3x3m completo di teli', 'pz', 180, 80);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-009', 'Tensostruttura per esterno', 'mq', 95, 42);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-010', 'Struttura truss alluminio quadra 290mm', 'ml', 22, 9);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-011', 'Arco/portale ingresso scenografico', 'pz', 650, 290);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-012', 'Deposito/magazzino retro stand', 'mq', 60, 26);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-013', 'Cabina ufficio/reception chiusa con porta', 'pz', 780, 350);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-014', 'Scala di accesso a soppalco/palco', 'pz', 280, 120);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-015', 'Corrimano/parapetto di sicurezza a norma', 'ml', 35, 15);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (2, 'STR-016', 'Controsoffitto/velario decorativo', 'mq', 48, 20);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-001', 'Pannello tamburato melaminico bianco 122x244 sp.10mm', 'pz', 38, 18);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-002', 'Pannello tamburato melaminico colore 122x244 sp.19mm', 'pz', 52, 24);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-003', 'Pannello MDF verniciato a campione RAL', 'mq', 42, 19);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-004', 'Pannello Forex 5mm', 'mq', 18, 8);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-005', 'Pannello Forex 10mm', 'mq', 26, 12);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-006', 'Pannello Plexiglass trasparente 5mm', 'mq', 48, 22);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-007', 'Lastra policarbonato alveolare', 'mq', 32, 14);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-008', 'Rivestimento laminato HPL', 'mq', 44, 20);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-009', 'Carta da parati / rivestimento tessile su parete', 'mq', 28, 12);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-010', 'Specchio su misura con cornice', 'mq', 95, 45);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-011', 'Vetro temperato/satinato 8mm', 'mq', 120, 58);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (3, 'PAN-012', 'Cartongesso curvo su centine', 'mq', 58, 26);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-001', 'Sedia design impilabile (noleggio)', 'pz', 12, 5);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-002', 'Poltroncina imbottita (noleggio)', 'pz', 35, 15);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-003', 'Sgabello alto da bancone (noleggio)', 'pz', 18, 8);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-004', 'Divano 2 posti design (noleggio)', 'pz', 120, 55);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-005', 'Tavolino basso salotto (noleggio)', 'pz', 28, 12);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-006', 'Tavolo alto reception gambe metalliche', 'pz', 65, 28);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-007', 'Banco reception/cassa su misura', 'pz', 680, 300);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-008', 'Vetrina espositiva illuminata', 'pz', 390, 175);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-009', 'Espositore da terra per prodotti', 'pz', 180, 80);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-010', 'Scaffalatura/libreria espositiva su misura', 'ml', 150, 68);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-011', 'Appendiabiti/guardaroba a stelo', 'pz', 22, 9);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-012', 'Fioriera con pianta ornamentale', 'pz', 45, 20);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-013', 'Cestino/porta rifiuti design', 'pz', 15, 6);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-014', 'Contenitore/magazzino con serratura', 'pz', 95, 42);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (4, 'ARR-015', 'Bancone bar/catering allestito', 'pz', 450, 200);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-001', 'Quadro elettrico da cantiere con differenziale', 'pz', 180, 80);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-002', 'Allaccio elettrico e pratica ente fiera', 'forfait', 220, 90);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-003', 'Punto luce/presa aggiuntiva', 'cad', 28, 12);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-004', 'Faretto LED orientabile su binario 20W', 'pz', 24, 10);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-005', 'Barra/binario elettrificato trifase', 'ml', 18, 7);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-006', 'Strip LED RGB con alimentatore', 'ml', 16, 6);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-007', 'Proiettore architetturale RGB DMX', 'pz', 65, 28);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-008', 'Insegna luminosa scatolata a lettere', 'cad', 420, 190);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-009', 'Gruppo elettrogeno silenziato (per esterno)', 'gg', 180, 80);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-010', 'Cablaggio dati/rete Wi-Fi dedicata stand', 'forfait', 280, 110);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (5, 'ELE-011', 'Consumo energetico stimato (kWh evento)', 'forfait', 150, 60);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (6, 'AVM-001', 'Diffusore audio attivo su stativo', 'pz', 65, 28);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (6, 'AVM-002', 'Mixer audio con operatore', 'gg', 180, 80);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (6, 'AVM-003', 'Radiomicrofono ad archetto/gelato', 'pz', 35, 15);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (6, 'AVM-004', 'Monitor LED 55" con supporto a parete', 'pz', 95, 42);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (6, 'AVM-005', 'Videowall LED modulare indoor', 'mq', 380, 170);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (6, 'AVM-006', 'Proiettore video full HD con schermo', 'pz', 220, 95);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (6, 'AVM-007', 'Totem multimediale interattivo touch screen', 'pz', 350, 155);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (6, 'AVM-008', 'Regia audio/video con operatore', 'gg', 320, 150);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (6, 'AVM-009', 'Collegamento streaming diretta evento', 'forfait', 280, 120);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-001', 'Stampa digitale UV su forex', 'mq', 32, 14);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-002', 'Stampa su PVC banner con occhielli', 'mq', 22, 9);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-003', 'Stampa su tessuto per tensostruttura', 'mq', 38, 17);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-004', 'Adesivo prespaziato/vinile taglio sagomato', 'mq', 28, 12);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-005', 'Vetrofania/oscurante per vetrine', 'mq', 26, 11);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-006', 'Roll-up/totem grafico personalizzato completo', 'pz', 85, 36);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-007', 'Pannello backlight retroilluminato', 'mq', 95, 42);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-008', 'Grafica pavimento calpestabile antiscivolo', 'mq', 30, 13);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-009', 'Lettere scatolate 3D personalizzate', 'cad', 95, 40);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-010', 'Applicazione grafiche in loco (tecnico)', 'gg', 280, 120);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (7, 'GRA-011', 'Progetto grafico stand (impaginazione pannelli)', 'forfait', 380, 150);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (8, 'PAV-001', 'Moquette fiera colori a scelta (posa inclusa)', 'mq', 14, 6);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (8, 'PAV-002', 'Pedana in legno grezzo con travetti', 'mq', 32, 14);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (8, 'PAV-003', 'Pavimento laminato/parquet flottante', 'mq', 42, 19);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (8, 'PAV-004', 'Pavimento in resina autolivellante', 'mq', 65, 28);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (8, 'PAV-005', 'Rialzo pavimento tecnico con passacavi', 'mq', 48, 21);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (9, 'SOL-001', 'Carrello elevatore/muletto con operatore', 'gg', 380, 180);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (9, 'SOL-002', 'Piattaforma aerea (PLE) con operatore', 'gg', 420, 200);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (9, 'SOL-003', 'Transpallet manuale', 'gg', 25, 10);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (9, 'SOL-004', 'Ponteggio metallico (nolo + montaggio)', 'mq', 28, 12);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (9, 'SOL-005', 'Autogru per sollevamento carichi pesanti', 'gg', 780, 380);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (9, 'SOL-006', 'Manodopera carpentiere specializzato', 'gg', 280, 130);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (10, 'TRA-001', 'Trasporto materiali A/R con furgone', 'cad', 180, 80);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (10, 'TRA-002', 'Trasporto con bilico/motrice', 'cad', 450, 220);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (10, 'TRA-003', 'Facchinaggio movimentazione merci', 'gg', 220, 100);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (10, 'TRA-004', 'Montaggio e smontaggio stand (squadra)', 'gg', 380, 180);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (10, 'TRA-005', 'Magazzinaggio materiali (mensile)', 'mese', 150, 60);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (10, 'TRA-006', 'Manodopera generica allestimento', 'h', 32, 15);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (10, 'TRA-007', 'Supervisore di cantiere', 'gg', 320, 150);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (10, 'TRA-008', 'Smaltimento materiali di risulta', 'forfait', 180, 80);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (11, 'VER-001', 'Pianta ornamentale da interno (medio formato)', 'pz', 35, 15);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (11, 'VER-002', 'Composizione floreale scenografica', 'pz', 65, 28);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (11, 'VER-003', 'Prato sintetico calpestabile', 'mq', 22, 9);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (11, 'VER-004', 'Elemento scenografico su misura (polistirolo/legno)', 'forfait', 480, 210);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (11, 'VER-005', 'Pellicola oscurante/decorativa per vetri', 'mq', 24, 10);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (11, 'VER-006', 'Fondale scenico fotografico personalizzato', 'pz', 280, 120);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (12, 'SRV-001', 'Hostess/steward evento', 'gg', 180, 90);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (12, 'SRV-002', 'Interprete/hostess plurilingue', 'gg', 260, 130);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (12, 'SRV-003', 'Pulizie stand pre/post evento', 'forfait', 150, 65);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (12, 'SRV-004', 'Assicurazione RCT allestimento', 'forfait', 180, 90);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (12, 'SRV-005', 'Estintore a noleggio con cartellonistica', 'pz', 35, 15);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (12, 'SRV-006', 'Vigilanza/security stand', 'gg', 220, 110);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (12, 'SRV-007', 'Coordinamento catering/coffee break', 'forfait', 150, 60);
INSERT INTO articles (category_id, code, name, unit, sell_price, cost_price) VALUES (12, 'SRV-008', 'Assistenza tecnica on-site durante l''evento', 'gg', 280, 130);
