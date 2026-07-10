# Preventivi Allestimenti Fieristici

App web a file singolo per creare preventivi di allestimenti per stand fieristici.

## Uso

Apri `index.html` in un browser (doppio click, oppure ospitalo su GitHub Pages / qualsiasi hosting statico). Non richiede installazione, build o server: tutto (HTML, CSS, JS) è in un unico file e i dati vengono salvati nel `localStorage` del browser.

## Funzionalità

- **Dati cliente ed evento**: anagrafica cliente, fiera, stand, superficie, date.
- **Voci di preventivo**: righe raggruppate per categoria (Struttura, Grafica, Arredi, Impianti, Trasporti, Manodopera, ecc.), con quantità, unità di misura, prezzo unitario e sconto per riga.
- **Listino prezzi**: catalogo di voci riutilizzabili (già precompilato con voci tipiche di allestimento) da cui aggiungere rapidamente righe al preventivo.
- **Calcolo automatico**: subtotale, sconto globale, imponibile, IVA e totale.
- **Preventivi salvati**: elenco con ricerca, apertura, duplicazione ed eliminazione.
- **Stampa / PDF**: layout dedicato per la stampa (usa "Stampa" del browser e scegli "Salva come PDF").
- **Dati azienda**: intestazione personalizzabile che compare nei preventivi stampati.

## Dati

Tutti i dati (preventivi, listino, dati azienda) sono salvati localmente nel browser (`localStorage`). Non c'è backend: per condividere i preventivi tra più persone/dispositivi serve esportarli manualmente (es. stampa PDF) o, in futuro, aggiungere un backend condiviso.
