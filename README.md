# 📺 SerieTV Streaming

**SerieTV Streaming** è una web application full-stack progettata per la consultazione, la gestione e lo streaming di serie TV. Il progetto integra tecnologie moderne lato frontend e backend, offrendo un'esperienza utente intuitiva e performante.

🔗 **Repository GitHub:** https://github.com/ThommyYT/serie-tv

---

## 🚀 Caratteristiche Principali

* 🔍 Ricerca avanzata delle serie TV
* 📚 Archivio completo e organizzato
* 🎬 Visualizzazione di episodi e video
* ⭐ Gestione dei preferiti
* 👤 Sistema di autenticazione utenti
* 🔐 Verifica email e gestione account
* 🤖 Scraping automatico dei contenuti
* 🛡️ Risoluzione CAPTCHA tramite integrazione dedicata
* 📱 Interfaccia responsive con Bootstrap
* ⚡ Architettura modulare e scalabile

---

## 🛠️ Stack Tecnologico

### 🔹 Frontend

* **HTML5** – Struttura delle pagine
* **CSS3** – Stile e layout
* **TypeScript** – Logica lato client
* **JavaScript** – Codice compilato
* **Bootstrap 5** – Design responsive
* **jQuery** – Manipolazione del DOM

### 🔹 Backend

* **PHP 8.x** – Logica server-side
* **MariaDB / MySQL** – Database relazionale
* **Composer** – Gestione delle dipendenze
* **cURL** – Richieste HTTP
* **QueryPath** – Parsing del DOM

### 🔹 Strumenti di Sviluppo

* **Node.js & npm**
* **TypeScript Compiler (tsc)**
* **XAMPP**
* **Visual Studio Code**
* **Git & GitHub**

---

## 📁 Struttura del Progetto

```
serie-tv/
│
├── css/                  # Fogli di stile
├── html/                 # Componenti e template HTML
│   └── template/         # Template riutilizzabili
├── js/
│   ├── src/              # File TypeScript
│   └── dist/             # File JavaScript compilati
├── php/
│   ├── classes/          # Classi PHP
│   ├── composer.json     # Dipendenze Composer
│   └── *.php             # Script backend
├── .vscode/              # Configurazioni VS Code
├── 127_0_0_1.sql        # Schema del database
├── DEV_GUIDE.md         # Guida per lo sviluppo
├── LICENSE              # Licenza del progetto
└── index.html           # Entry point dell'applicazione
```

---

## ⚙️ Installazione

### 🔧 Prerequisiti

Assicurati di avere installato:

* XAMPP (Apache e MySQL)
* PHP 8 o superiore
* Composer
* Node.js e npm
* Git

---

### 📥 Clonare il Repository

```bash
git clone https://github.com/ThommyYT/serie-tv.git
cd serie-tv
```

---

### 📦 Configurazione del Backend

1. Sposta il progetto nella cartella `htdocs` di XAMPP:

   ```
   C:\xampp\htdocs\serie-tv
   ```

2. Installa le dipendenze PHP:

   ```bash
   cd php
   composer install
   ```

3. Importa il database:

   * Apri **phpMyAdmin**
   * Crea un database
   * Importa il file:

     ```
     127_0_0_1.sql
     ```

4. Configura i parametri di connessione nel file PHP dedicato al database.

---

### 🎨 Configurazione del Frontend

Installa le dipendenze Node.js:

```bash
cd js
npm install
```

Compila il codice TypeScript:

```bash
npx tsc
```

Oppure in modalità automatica:

```bash
npx tsc --watch
```

---

### ▶️ Avvio del Progetto

1. Avvia **Apache** e **MySQL** da XAMPP.
2. Apri il browser e visita:

```
http://localhost/serie-tv
```

---

## 📸 Funzionalità del Sistema

| Funzionalità  | Descrizione                             |
| ------------- | --------------------------------------- |
| Home          | Visualizzazione delle serie più recenti |
| Archivio      | Elenco completo delle serie             |
| Ricerca       | Sistema di ricerca dinamico             |
| Streaming     | Riproduzione degli episodi              |
| Account       | Gestione del profilo utente             |
| Preferiti     | Salvataggio delle serie                 |
| Aggiornamenti | Monitoraggio delle novità               |

---

## 🔐 Sicurezza

* Protezione tramite sessioni PHP
* Validazione e sanitizzazione degli input
* Password crittografate
* Controlli di autenticazione
* Supporto per CAPTCHA
* Utilizzo di prepared statements per prevenire SQL Injection

---

## 📄 Documentazione

* **DEV_GUIDE.md** – Linee guida per lo sviluppo
* **LICENSE** – Informazioni sulla licenza del progetto

---

## 🔮 Sviluppi Futuri

* Implementazione di un'API REST
* Integrazione con Docker
* Sistema di caching avanzato
* Test automatici (PHPUnit e Jest)
* Pipeline CI/CD con GitHub Actions
* Migrazione a framework moderni (Laravel o Angular)
* Supporto PWA

---

## 🤝 Contributi

I contributi sono benvenuti! Per partecipare:

1. Effettua un fork del repository
2. Crea un nuovo branch

   ```bash
   git checkout -b feature/nuova-funzionalita
   ```
3. Effettua il commit delle modifiche

   ```bash
   git commit -m "Aggiunta nuova funzionalità"
   ```
4. Invia una Pull Request

---

## 📜 Licenza

Questo progetto è distribuito sotto la licenza **MIT**.
Per maggiori dettagli, consulta il file `LICENSE`.

---

## 👨‍💻 Autore

**Thomas Merlini**
🔗 GitHub: https://github.com/ThommyYT

---

## ⭐ Supporto

Se il progetto ti è utile, lascia una ⭐ su GitHub!
