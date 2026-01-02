# Usage Guide - Card Scanner

Welcome to Card Scanner! This guide will help you get the most out of your card collection manager.

## 🚀 Getting Started

Once you have installed the application (see README.md), access it via your browser at `http://localhost:8000`.

## 📸 Scanning & Adding Cards

The core feature of Card Scanner is its AI-powered card recognition.

1.  **Navigate to "Scansiona" (Scan)**: Click the camera icon in the top navigation bar.
2.  **Upload Images**:
    *   **Drag & Drop**: Drag your card images directly onto the upload zone.
    *   **Click**: Click the upload zone to select files from your computer.
    *   *Tip: You can upload multiple images at once!*
3.  **Crop for Precision**:
    *   Click the **Crop** button on any uploaded image.
    *   Adjust the frame to surround just the card. This helps the AI work accurately.
    *   Click "Save Crop".
4.  **Process**:
    *   **AI Auto-Scan**: Click the "Magic Wand" icon (Riconosci con AI). The system will send the image to Gemini AI and automatically fill in:
        *   Name
        *   HP
        *   Type
        *   Evolution Stage
        *   Attacks/Abilities
        *   Weakness/Resistance/Retreat
        *   Set Number & Rarity
    *   **Manual Entry**: If you prefer, click the "Pencil" icon to type details yourself.
5.  **Review & Save**: Check the data. If everything looks good, click **Save to Collection**.

## 💎 Managing Your Collection

View your digital binder in the **"Collezione" (Collection)** tab.

*   **View Details**: Click on any card to see its full stats and attacks.
*   **Search**: Use the search bar to find specific Pokemon or sets.
*   **Delete**: Remove cards you no longer own.

## 💰 Market Data & Valuation

Track the real-world value of your collection.

1.  **Import Prices**:
    *   Go to **Market Data**.
    *   Upload a CSV file containing pricing data (e.g., from TCGPlayer exports or similar).
    *   The system will import the prices into the database.
2.  **Match Cards**:
    *   Go to **Matching**.
    *   The system tries to link your scanned cards with the imported prices based on Card Name and Set Number.
    *   Review suggested matches and confirm them.
3.  **Check Value**:
    *   Go to **Valore** (Value).
    *   See your total collection value, portfolio charts, and top-valued cards.

## 👤 User Profile

*   **Register/Login**: Create an account to save your collection securely.
*   **Avatar**: Upload a custom avatar in your Profile settings.

## 💡 Pro Tips for Best AI Results

*   **Good Lighting**: Ensure the card is well-lit without too much glare.
*   **Plain Background**: When cropping, try to exclude background clutter.
*   **Language**: The AI is optimized for English and Italian cards, but works best with English text for global databases.

---
**Enjoy collecting!**
