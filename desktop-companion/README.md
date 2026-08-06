# GenX Print Companion (Background System Tray Agent)

GenX Print Companion is a lightweight desktop tray application for **Windows** and **macOS**. It runs silently in the system tray, auto-starts on boot, syncs connected physical printers to your live web app (`https://digierp.cloud/`), and executes silent direct thermal prints with **0 browser popups**.

---

## 🚀 How to Build Standalone Installers (.exe & .dmg)

### Prerequisites:
- Node.js (v18 or higher) installed on your build machine.

### Build Steps:

1. **Install Dependencies**:
   ```bash
   cd desktop-companion
   npm install
   ```

2. **Build Windows Executable (`.exe`)**:
   ```bash
   npm run build:win
   ```
   This generates `dist/GenX_Companion_Windows.exe`.

3. **Build macOS Executable (`.dmg`)**:
   ```bash
   npm run build:mac
   ```
   This generates `dist/GenX_Companion_Mac`.

---

## ⚙️ Configuration for Client Computers

On the client PC/laptop, the companion app reads the branch key from `~/.genx_companion.json`:

```json
{
  "branch_hash": "YOUR_BRANCH_UNIQUE_HASH",
  "cloud_url": "https://digierp.cloud"
}
```

When started, it will:
1. Reside silently in the system tray.
2. Auto-sync connected USB/Bluetooth/Network printers to `https://digierp.cloud`.
3. Process silent direct print requests instantly with **0 browser popups**.
