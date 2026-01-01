# 🎴 Pokemon Card Scanner

> **AI-powered Pokemon Card Collection Manager**  
> Scan, organize, and track the value of your Pokemon TCG collection with the power of Google Gemini AI.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

---

## ✨ Features

### 🤖 **AI-Powered Card Recognition**
- Automatic card identification using **Google Gemini 2.5 Flash**
- Extracts card name, HP, type, attacks, rarity, and more
- Smart image analysis for accurate data extraction

### 📸 **Intuitive Upload Interface**
- **Multi-image upload** with drag & drop support
- **Built-in image cropper** for perfect card framing
- **Gallery view** to manage multiple cards simultaneously

### 💎 **Collection Management**
- Browse your complete card collection
- Track card conditions (Near Mint, Lightly Played, etc.)
- Link cards to market pricing data
- Calculate collection value and profit/loss

### 📊 **Market Data Integration**
- Import market prices from CSV files
- Track real-time collection value
- Monitor price variations over time
- Auto-matching system for cards and market data

### 🎨 **Beautiful Modern UI**
- **Dark themed** Pokemon-inspired design
- **Glassmorphism** effects and smooth animations
- Fully **responsive** (mobile, tablet, desktop)
- Custom Pokemon type badges and colors

---

## 🚀 How It Works

### 1. **Upload Your Cards**
Take photos of your Pokemon cards or select existing images from your device.

### 2. **Crop & Process**
Use the built-in cropper to isolate each card for better AI recognition.

### 3. **AI Recognition or Manual Entry**
- **Option A**: Let Gemini AI automatically extract all card details
- **Option B**: Manually enter card information

### 4. **Save & Organize**
Review the extracted data, make any necessary edits, and save to your collection.

### 5. **Track Value**
Link cards to market data and monitor your collection's value over time.

---

## 🛠️ Technology Stack

### Backend
- **Laravel 12** - PHP framework
- **PHP 8.2+** - Modern PHP features
- **MySQL** - Database
- **Google Gemini API** - AI card recognition

### Frontend
- **Blade Templates** - Server-side rendering
- **Vue.js 3 + Inertia.js** - Interactive components
- **Bootstrap 5** - UI framework
- **Cropper.js** - Image cropping functionality

---

## 📦 Installation

### Prerequisites

- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL or PostgreSQL
- Google Gemini API Key ([Get one here](https://ai.google.dev/))

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/lib-pokemon.git
   cd lib-pokemon
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Set up your database**
   
   Edit `.env` and configure your database connection:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pokemon_cards
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Add your Gemini API Key**
   
   Edit `.env` and add:
   ```env
   GEMINI_API_KEY=your_gemini_api_key_here
   ```

7. **Run database migrations**
   ```bash
   php artisan migrate
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```

10. **Access the application**
    
    Open your browser and navigate to:  
    **http://localhost:8000**

---

## 🎯 Usage Guide

### Uploading Cards

1. Navigate to **Scansiona** (Scan) from the navigation menu
2. Click or drag images into the upload zone
3. For each image:
   - Click **Ritaglia** (Crop) to frame the card
   - Confirm the crop to upload the image
4. Choose your processing method:
   - **Riconosci con AI** (AI Recognition) - Automated
   - **Inserimento Manuale** (Manual Entry) - Manual input
5. Review the extracted data and click **Salva** (Save)

### Managing Your Collection

- View all cards in **Collezione** (Collection)
- Delete unwanted cards with the trash icon
- Track collection value in **Valore** (Value)

### Market Data

1. Navigate to **Market Data**
2. Upload a CSV file with pricing information
3. Use **Matching** to link your cards to market prices
4. View updated values in the **Collection Value** section

---

## 🖼️ Screenshots

### Upload Interface
AI-powered card upload with cropping and gallery view

### Collection View
Browse and manage your complete Pokemon card collection

### Market Value
Track your collection's value with real-time price data

---

## 🔧 Configuration

### Gemini AI Settings

The app uses Google Gemini 2.5 Flash for card recognition. Configure the API  in `app/Services/GeminiService.php`:

```php
protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
```

### Customization

- **Pokemon Type Colors**: Edit CSS variables in `resources/views/layouts/app.blade.php`
- **Card Fields**: Modify `pokemon_cards` table migration
- **AI Prompt**: Customize recognition prompt in `GeminiService.php`

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📝 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- **Google Gemini AI** for powerful card recognition
- **Pokemon Company** for the amazing TCG
- **Laravel Community** for the excellent framework
- **Bootstrap** for beautiful UI components

---

## 📧 Contact

For questions or support, please open an issue on GitHub.

---

<div align="center">

**Made with ❤️ for Pokemon TCG collectors**

⭐ Star this repo if you find it useful!

</div>
