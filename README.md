# 🎴 Card Scanner

> **AI-Powered Trading Card Collection Manager**  
> Scan, organize, and track the value of your trading card collection with the power of Google Gemini AI.

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)
![License](https://img.shields.io/badge/license-Personal_Use_Only-red.svg)

---

## ⚠️ Important Notice

**This application is intended for PERSONAL USE ONLY.**

This software is released exclusively for private, non-commercial use. **Commercial use, resale, or any profit-generating activity is strictly prohibited** and violates the license terms.

If you wish to use this application, please install it on your own server or use the demo instance available at:

**🔗 [https://gmapsextractor.altervista.org/](https://gmapsextractor.altervista.org/)**

> **Note:** The demo instance automatically resets all data (cards, collections, market data) daily at midnight (00:00) to prevent resource saturation. Do not use it for long-term data storage.

---

## 📖 What is Card Scanner?

Card Scanner is a **free, open-source web application** designed to help trading card collectors:

- 📊 **Catalog** your collection of trading cards (Pokemon, Magic: The Gathering, Yu-Gi-Oh!, Force of Will, and more)
- 🤖 **Automatically recognize** card information printed on the card using AI
- 💰 **Track the market value** of your cards using imported market data
- 📈 **Calculate profit and loss** for your collection over time
- 🎮 **Manage multiple games** in a single application with automatic game detection

---

## ✨ Key Features

### 🤖 AI-Powered Card Recognition
- Automatic card data extraction using **Google Gemini 2.5 Flash**
- Extracts visible information: name, HP, type, attacks, rarity, card number
- **Automatic game detection** (Pokemon, Magic, Yu-Gi-Oh!, etc.)
- Creates new games automatically if not already in your collection

### 📸 Intuitive Upload Workflow
- **Multi-image upload** with drag & drop support
- **Built-in image cropper** for precise card framing
- **Gallery view** to manage multiple cards at once
- Individual card deletion before saving

### 💎 Collection Management
- Browse your complete card collection organized by set
- Track card **conditions** (Near Mint, Lightly Played, Moderately Played, etc.)
- **Manual set assignment** via collection interface
- Multi-game support in a single application
- User authentication with private collections

### 📊 Market Data Integration
- Import market prices from **TCGPlayer** (JSON format)
- **Auto-matching system** to link cards with market data
- Track real-time collection value
- Calculate **P&L (Profit & Loss)** in EUR and percentage

### 🎨 Modern, Responsive UI
- **Dark-themed** modern design
- Built with **Vue.js 3 + Inertia.js** for a seamless SPA experience
- **TailwindCSS v4** for styling
- Fully **responsive** (mobile, tablet, desktop)
- Custom modals and confirmation dialogs

---

## 🛠️ Technology Stack

### Backend
- **Laravel 12** - Modern PHP framework
- **PHP 8.2+** - Latest PHP features
- **MySQL/MariaDB** - Database
- **Google Gemini 2.5 Flash API** - AI card recognition
- **Intervention Image** - Image processing

### Frontend
- **Vue.js 3** - Progressive JavaScript framework
- **Inertia.js** - Modern monolith (no separate API needed)
- **TailwindCSS v4** - Utility-first CSS framework
- **Bootstrap 5** - UI components
- **Cropper.js** - Advanced image cropping

---

## 📦 Installation

### Prerequisites

- **PHP** >= 8.2
- **Composer** - PHP dependency manager
- **Node.js** >= 18 + npm
- **MySQL** or **MariaDB**
- **Google Gemini API Key** ([Get one here](https://aistudio.google.com/apikey))

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/card-scanner.git
   cd card-scanner
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set up database**
   
   Edit `.env` and configure your database:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=card_scanner
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Add your Gemini API Key**
   
   Edit `.env` and add:
   ```env
   GEMINI_API_KEY=your_gemini_api_key_here
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

8. **Start the server**
   ```bash
   php artisan serve
   ```

9. **Access the application**
   
   Open your browser at: **http://localhost:8000**

---

## 📚 User Guide

For a **complete, step-by-step guide** on how to use the application, please refer to:

**📖 [Complete User Guide (Italian)](docs/GUIDA-COMPLETA.md)**

The guide covers:
- ✅ How to retrieve and import Market Data from TCGPlayer
- ✅ How to scan and upload cards
- ✅ How to assign sets and card conditions
- ✅ How to link cards to market prices
- ✅ How to monitor your collection's value
- ✅ Troubleshooting and FAQs

---

## 🚀 Quick Start

### 1. Import Market Data (Required First Step)

Before adding cards, you need market pricing data:

1. Navigate to **Market Data** in the app
2. Follow the [detailed instructions in the user guide](docs/GUIDA-COMPLETA.md#1%EF%B8%8F⃣-market-data-importare-i-prezzi-delle-carte) to extract JSON data from TCGPlayer
3. Upload the JSON file to import prices

### 2. Scan Your Cards

1. Go to **Scansiona** (Scan)
2. Upload card images (drag & drop or click to select)
3. Optionally crop each image
4. Click **AI Recognition** (magic wand icon) to extract card data
5. Review and click **Save**

### 3. Organize Your Collection

1. Go to **Collezione** (Collection)
2. For each card, assign:
   - **Set** (which expansion/set it belongs to)
   - **Condition** (Near Mint, Lightly Played, etc.)
3. Save your changes

### 4. Link to Market Prices

1. Go to **Matching**
2. Click **Auto-Match** to automatically link cards to market data
3. Manually match any unmatched cards

### 5. Track Value

1. Go to **Valore** (Value)
2. View your collection's current value, cost basis, and P&L

---

## 🔧 Configuration

### Gemini AI Settings

The app uses Google Gemini 2.5 Flash for card recognition. You can customize the AI behavior in `app/Services/GeminiService.php`.

### Multi-User Support

The application supports multiple users with private collections. Each user can only see and manage their own:
- Cards
- Market Data
- Collection statistics

### Customization

You can customize various aspects:
- **Card fields**: Modify the `pokemon_cards` table migration
- **AI prompt**: Edit the prompt in `GeminiService.php`
- **UI styling**: Modify TailwindCSS classes or add custom CSS

---

## 📝 License

This project is licensed under a **Personal Use Only License** - see the [LICENSE](LICENSE) file for details.

**⚠️ Commercial use is strictly prohibited.**

You are free to:
- ✅ Use the application for personal card collection management
- ✅ Install it on your own server
- ✅ Modify it for your own needs
- ✅ Share it with friends (non-commercially)

You may NOT:
- ❌ Sell the application or access to it
- ❌ Offer it as a commercial service
- ❌ Use it for any profit-generating activity

---

## 🤝 Contributing

Contributions are welcome! If you'd like to improve the application:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 🙏 Acknowledgments

- **Google Gemini AI** - For powerful and affordable card recognition
- **TCGPlayer** - For market price data
- **Laravel Community** - For the excellent framework and ecosystem
- **Trading card game publishers** - Pokemon Company, Wizards of the Coast (Magic: The Gathering), Konami (Yu-Gi-Oh!), and others

---

## 📧 Support

For questions, issues, or feature requests:

- 📖 Check the [User Guide](docs/GUIDA-COMPLETA.md)
- 🐛 Open an issue on GitHub
- 💬 Join discussions in the Issues section

---

<div align="center">

**Made with ❤️ for trading card collectors worldwide**

⭐ Star this repo if you find it useful!

</div>
