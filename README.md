# Preventivi Henoto / Emvisia

Applicazione web per la gestione dei preventivi di allestimenti fieristici, retail, strutture e attrezzature per eventi e architettura temporanea, per **Henoto S.p.A.** ed **Emvisia S.r.l.**

Tecnologia: PHP + SQLite (nessun database esterno da configurare). Pensata per essere caricata così com'è su un hosting condiviso con **cPanel**.

## Cosa include

- **Preventivi multi-azienda**: ogni preventivo viene emesso a nome di Henoto o Emvisia, con logo e dati fiscali corretti in automatico, numerazione separata per azienda/anno (`HEN-2026-001`, `EMV-2026-001`, ...).
- **Libreria articoli**: oltre 110 voci precompilate, organizzate in 12 categorie (strutture, pannelli, arredi, impianto elettrico e illuminotecnica, audio/video, grafica e stampa, pavimentazioni, sollevamento e carpenteria, trasporti e manodopera, verde scenico, servizi e sicurezza, progettazione), con prezzo di vendita e costo interno per il calcolo del margine. Completamente modificabile: aggiungi, disattiva o correggi articoli e categorie da "Libreria articoli".
- **Archivio clienti**: anagrafica completa (P.IVA, PEC, SDI, referente...), ricerca, storico preventivi e valore per cliente.
- **Editor preventivo**: sezioni personalizzabili, righe con ricerca rapida dalla libreria o voci libere, sconto per riga e sconto complessivo, aliquota IVA selezionabile, voci opzionali (escluse dal totale ma visibili al cliente), calcolo live di subtotale/sconto/imponibile/IVA/totale e del margine interno.
- **Termini di pagamento variabili**: template riutilizzabili (30/40/30, 50/50, saldo anticipato, ecc.) modificabili in Impostazioni, oppure testo libero per ogni preventivo.
- **Archivio preventivi**: elenco con filtri per azienda/stato/cliente, stato del preventivo (bozza, inviato, accettato, rifiutato, scaduto), duplicazione con nuova numerazione (utile per fiere ricorrenti), eliminazione.
- **Stampa / PDF**: vista di stampa dedicata con intestazione aziendale, dati cliente, dettaglio voci per sezione, totali, termini di pagamento, note e spazio firma — si genera il PDF con "Stampa" del browser, senza dipendenze server.
- **Link di accettazione online**: ogni preventivo ha un link pubblico univoco (senza bisogno di login) che il cliente può aprire per visualizzare il preventivo e accettarlo o rifiutarlo con un click; lo stato in archivio si aggiorna automaticamente.
- **Dashboard**: preventivi per stato, valore preventivi accettati nell'anno, valore preventivi ancora aperti, preventivi in scadenza nei prossimi 7 giorni, ripartizione per azienda.
- **Accesso protetto** da password condivisa (modificabile in `config.php`).

## Deploy su cPanel

1. **Carica i file**: tramite File Manager o FTP, copia l'intero contenuto di questa cartella dentro `public_html` (o in una sottocartella, es. `public_html/preventivi`, se vuoi tenerlo separato dal sito principale).
2. **Verifica i permessi**: la cartella `data/` deve essere scrivibile dal server web (permessi 755 o 775). Al primo accesso l'app crea automaticamente il database `data/app.sqlite` con la libreria articoli, i template di pagamento e i dati delle due aziende già precompilati.
3. **Verifica l'estensione PHP**: serve `pdo_sqlite` abilitata (quasi sempre disponibile di default sull'hosting cPanel; in caso contrario abilitala da "Seleziona versione PHP" > "Estensioni PHP" nel pannello cPanel).
4. **Cambia la password di accesso**: apri `config.php` sul server e sostituisci `APP_PASSWORD_HASH`. Per generare un nuovo hash, da un terminale con PHP disponibile:
   ```
   php -r "echo password_hash('la_tua_nuova_password', PASSWORD_DEFAULT);"
   ```
   Password di default preimpostata: `preventivi2026` — **cambiala subito dopo il primo caricamento**.
5. **Apri il sito**: vai su `https://tuodominio.it/(percorso)/login.php`, inserisci la password e inizia a usare l'app.
6. **Logo aziendali**: sono già inclusi in `assets/img/henoto-logo.svg` (testuale, segnaposto) ed `emvisia-logo.svg` (estratto dal file originale fornito). Per usare il logo Henoto definitivo, carica il file immagine reale (PNG/SVG) nello stesso percorso con lo stesso nome, oppure aggiorna il percorso in Impostazioni.
7. **Backup**: il database è un singolo file, `data/app.sqlite`. Scaricalo periodicamente via FTP/File Manager per avere un backup completo di clienti, articoli e preventivi.

## Struttura del progetto

```
config.php, db.php, auth.php     -> configurazione, connessione DB, login
login.php, logout.php            -> autenticazione
index.php                        -> dashboard
clients.php, client_form.php, client_view.php     -> archivio clienti
articles.php, article_form.php   -> libreria articoli
quotes.php, quote_form.php, quote_view.php        -> archivio e editor preventivi
settings.php                     -> dati fiscali aziende, template pagamento, numerazione
print/quote_print.php            -> vista di stampa/PDF e pagina pubblica di accettazione
api/                             -> endpoint JSON usati dall'editor (ricerca articoli/clienti, salvataggio, stato, duplicazione, accettazione)
install/schema.sql               -> struttura database e dati iniziali (creato automaticamente al primo avvio)
assets/                          -> CSS, JS, loghi
data/app.sqlite                  -> database (creato al primo accesso, non versionato)
```

## Idee per evolvere lo strumento

Alcuni sviluppi naturali, se in futuro vorrai portare lo strumento a un livello ancora superiore:

- **Utenze individuali** con login separato per persona, per tracciare chi ha creato/modificato ogni preventivo (oggi l'accesso è a password condivisa).
- **Invio email automatico** del preventivo/link dal server (oggi si usa un link "mailto:" precompilato), tramite SMTP dell'hosting.
- **Generazione PDF lato server** (es. libreria mPDF) per allegare automaticamente il file invece di affidarsi al "Stampa > Salva PDF" del browser.
- **Gestione fornitori** collegata agli articoli, per confrontare più preventivi di acquisto sullo stesso articolo.
- **Versionamento preventivi** più strutturato (revisione A/B/C dello stesso numero, invece di un numero nuovo ad ogni duplicazione).
- **Export CSV/Excel** dell'archivio preventivi e clienti per analisi esterne.
- **Integrazione con fatturazione elettronica** (per generare la fattura direttamente da un preventivo accettato).
- **Notifiche automatiche** (es. promemoria quando un preventivo sta per scadere) via email.
