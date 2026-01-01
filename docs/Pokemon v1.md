# **Specifica Tecnica: WebApp Image Cropping & OCR**

Versione: 1.0.0  
Data: 29 Dicembre 2025  
Tecnologia: Laravel / JavaScript / Tesseract

## ---

**1\. Introduzione e Obiettivi**

Il progetto prevede lo sviluppo di un modulo web che permetta agli utenti di caricare un'immagine, definire un'area di ritaglio (crop) specifica direttamente dal browser e inviare la porzione ritagliata al server. Successivamente, il server elaborerà l'immagine salvata per estrarre il contenuto testuale tramite tecnologia OCR (Optical Character Recognition).

### **1.1 Flusso Funzionale**

1. **Selezione:** L'utente seleziona un file immagine dal proprio dispositivo.  
2. **Anteprima e Ritaglio:** L'immagine viene mostrata in un canvas interattivo dove l'utente seleziona l'area da mantenere.  
3. **Invio:** L'utente conferma il ritaglio; il frontend genera un blob dell'area selezionata e lo invia al backend.  
4. **Salvataggio:** Il server Laravel riceve il file e lo salva su disco.  
5. **Elaborazione OCR:** Il server lancia il processo di riconoscimento del testo sull'immagine salvata.  
6. **Persistenza:** Il testo estratto e il percorso del file vengono salvati nel database.

## ---

**2\. Stack Tecnologico**

### **2.1 Backend**

* **Framework:** Laravel 10.x / 11.x  
* **Linguaggio:** PHP 8.2+  
* **Database:** MySQL o PostgreSQL  
* **Engine OCR:** Tesseract OCR (Binario di sistema)  
* **Wrapper PHP:** thiagoalessio/tesseract\_ocr (Libreria Composer)

### **2.2 Frontend**

* **Libreria UI:** Blade Templates (o framework JS a scelta come Vue/React)  
* **Libreria Cropping:** **Cropper.js** (v1.6+)  
  * *Motivazione:* Standard industriale, open source, supporta touch, zoom, rotazione e generazione di canvas ottimizzati.  
* **Comunicazione:** Axios o Fetch API per l'invio asincrono (FormData).

### **2.3 Requisiti di Sistema (Server)**

Per il funzionamento dell'OCR, il server ospitante deve avere installato Tesseract:

Bash

\# Esempio per Ubuntu/Debian  
sudo apt-get install tesseract-ocr  
sudo apt-get install tesseract-ocr-ita \# Pacchetto lingua italiana

## ---

**3\. Architettura dei Dati**

### **3.1 Schema Database**

Tabella proposta: ocr\_documents

| Nome Colonna | Tipo Dati | Note |
| :---- | :---- | :---- |
| id | BIGINT (PK) | Auto-increment |
| original\_filename | VARCHAR(255) | Nome originale del file (opzionale) |
| storage\_path | VARCHAR(255) | Percorso relativo (es. uploads/images/xyz.jpg) |
| extracted\_text | TEXT / LONGTEXT | Il risultato dell'OCR |
| status | ENUM | pending, completed, failed (utile per code asincrone) |
| created\_at | TIMESTAMP | Laravel default |
| updated\_at | TIMESTAMP | Laravel default |

## ---

**4\. Dettagli Implementativi Frontend**

### **4.1 Inizializzazione Cropper.js**

Il frontend deve gestire un input di tipo file. Al cambiamento (change event):

1. Leggere il file tramite FileReader.  
2. Impostare l'URL risultante come src di un tag \<img\> (target image).  
3. Distruggere eventuali istanze precedenti di Cropper e crearne una nuova.

JavaScript

// Configurazione base consigliata  
const cropper \= new Cropper(imageElement, {  
  aspectRatio: NaN, // Libero, o 16/9, 1/1 a seconda dei requisiti  
  viewMode: 1,      // Restringe il crop box dentro il canvas  
  autoCropArea: 1,  
});

### **4.2 Generazione del Payload**

Al click del pulsante "Salva":

1. Utilizzare il metodo cropper.getCroppedCanvas() per ottenere il canvas del ritaglio.  
2. Convertire il canvas in **Blob** (formato binario) per simulare un file upload.  
3. Appendere il blob a un oggetto FormData.

JavaScript

cropper.getCroppedCanvas().toBlob((blob) \=\> {  
  const formData \= new FormData();  
  formData.append('cropped\_image', blob, 'capture.jpg'); // 'cropped\_image' è la chiave per Laravel  
  // Inviare formData via Axios/Fetch  
}, 'image/jpeg', 0.9); // Formato e qualità

## ---

**5\. Dettagli Implementativi Backend (Laravel)**

### **5.1 Rotte (Routes)**

Definire le rotte in routes/web.php (o api.php se SPA).

* GET /ocr/upload \-\> Mostra la vista con il cropper.  
* POST /ocr/process \-\> Riceve l'immagine, salva ed esegue OCR.

### **5.2 Controller (OcrController)**

Il metodo di gestione del POST deve eseguire le seguenti operazioni atomiche:

#### **A. Validazione**

Validare che il file in ingresso sia un'immagine.

PHP

$request\-\>validate(\[  
    'cropped\_image' \=\> 'required|image|mimes:jpeg,png,jpg|max:10240', // Max 10MB  
\]);

#### **B. Storage**

Salvare il file nel disco public o local. Si consiglia di generare un nome univoco (UUID o Timestamp).

PHP

$path \= $request\-\>file('cropped\_image')-\>store('ocr\_uploads', 'public');

#### **C. Esecuzione OCR**

Utilizzare il wrapper per lanciare il comando Tesseract sul file appena salvato.  
Nota: È fondamentale passare il percorso assoluto del file al wrapper.

PHP

use thiagoalessio\\TesseractOCR\\TesseractOCR;

// Ottenere path assoluto  
$fullPath \= storage\_path('app/public/' . $path);

try {  
    $text \= (new TesseractOCR($fullPath))  
        \-\>lang('ita', 'eng') // Supporto multilingua  
        \-\>run();  
} catch (\\Exception $e) {  
    // Gestione errore (es. immagine non chiara o tesseract non trovato)  
    return response()-\>json(\['error' \=\> 'OCR Failed'\], 500);  
}

#### **D. Salvataggio DB e Risposta**

Creare il record nel database e ritornare il testo al frontend (JSON o Redirect).

## ---

**6\. Ottimizzazioni e Code (Code Jobs)**

L'operazione di OCR è **CPU-intensive** e bloccante. Se l'immagine è ad alta risoluzione, la richiesta HTTP potrebbe andare in timeout.

**Design Pattern Consigliato:**

1. Il Controller salva l'immagine e crea il record nel DB con status pending.  
2. Viene dispacciato un **Laravel Job** (es. ProcessOcrImage).  
3. Il Controller risponde immediatamente all'utente: *"Immagine caricata, elaborazione in corso..."*.  
4. Il Job (eseguito in background da un worker):  
   * Esegue Tesseract.  
   * Aggiorna il record DB con il testo e status completed.  
   * (Opzionale) Invia una notifica (WebSocket/Email) all'utente.

## ---

**7\. Sicurezza**

1. **File Type Verification:** Non fidarsi mai dell'estensione del file. Laravel controlla i MIME type, ma assicurarsi che la configurazione del server non esegua script PHP nella cartella di upload.  
2. **Input Sanitization:** Il testo estratto dall'OCR potrebbe contenere caratteri strani. Prima di visualizzarlo nell'HTML, usare sempre l'escaping ({{ $text }} in Blade).  
3. **Gestione Errori:** Tesseract può fallire se l'immagine è completamente nera o corrotta. Il codice deve gestire le eccezioni UnsuccessfulCommandException.

## ---

**8\. Riepilogo Installazione Dipendenze**

Bash

\# 1\. Installazione Tesseract (Sistema Operativo)  
sudo apt update && sudo apt install tesseract-ocr tesseract-ocr-ita

\# 2\. Installazione Wrapper PHP  
composer require thiagoalessio/tesseract\_ocr

\# 3\. Installazione Frontend (via NPM o CDN)  
npm install cropperjs  
