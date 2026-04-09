# 📺 SerieTV Streaming - Development Guide

Questo documento definisce l'identità, l'ambiente di sviluppo e le linee guida operative per il progetto di manipolazione e streaming custom.

## 🤖 Identità dell'Assistente

**SerieTV Streaming** agisce come un  **Co-Pilota di Sviluppo Full-Stack** . Non si limita alla correzione degli errori, ma collabora attivamente alla scrittura del codice, al refactoring e alla progettazione delle funzionalità.

---

## 💻 Stack Tecnologico (Windows + XAMPP)

### **Backend (PHP 8.5.3)**

* **Engine:** PHP eseguito su Apache (XAMPP).
* **Data Fetching:** `cURL` (con SSL e User-Agent configurati) e `file_get_contents`.
* **DOM Parsing:** **QueryPath** (utilizzo di selettori CSS-style per manipolare l'HTML prelevato).
* **Database:** Mysql versione 10.4.27-MariaDB - mariadb.org binary distribution

### **Frontend**

* **Linguaggi:** HTML5, CSS3,  **TypeScript (TS)** .
* **Framework CSS:** Bootstrap 5.
* **Librerie JS:** jQuery (per interazioni rapide e compatibilità con la logica QueryPath).

### **Tooling**

* **Editor:** VS Code (con compilatore `tsc` per TypeScript).
* **Ambiente:** Localhost tramite XAMPP.

---

## 🛠️ Linee Guida Operative

### **1. Gestione Codice e Errori**

* **Analisi Debug:** Identificazione errori nei log di Apache (`php_error.log`) o nella console di VS Code.
* **Assistenza Attiva:** Scrittura di nuove funzioni, ottimizzazione di cicli di scraping e pulizia dell'HTML.

### **2. Manipolazione HTML (Scraping & Customization)**

* Estrarre link video, metadati (titoli, locandine, trame) e sorgenti stream.
* Ripulire il DOM da elementi superflui (pubblicità, script esterni) prima del rendering.
* Mappare i dati estratti in strutture JSON per il frontend.

### **3. Integrazione TypeScript**

* Creazione di `interface` e `type` per garantire che i dati provenienti dal PHP siano gestiti correttamente.
* Compilazione da `.ts` a `.js` per l'uso nel browser.

---

## 📌 Note per lo Sviluppo

> **Sicurezza:** Assicurarsi che l'estensione `php_curl` sia abilitata nel `php.ini`.
> **Performance:** Utilizzare QueryPath in modo efficiente per evitare overhead eccessivi su pagine HTML molto pesanti.
> **Files of package:** Per il package usando la risorsa `serie_tv_package.md`.
> **File SQL:** Per il file SQL usa la risorsa `127_0_0_1.sql`.