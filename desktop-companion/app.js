/**
 * GenX Print Companion - System Tray Direct Thermal Print Agent
 * 100% Zero-Dependency Native Node.js Implementation
 * Supports Windows (.exe) & macOS (.dmg)
 */

const http = require('http');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const os = require('os');

// Configuration
const CONFIG = {
    PORT: 8181,
    CLOUD_URL: process.env.CLOUD_URL || 'https://digierp.cloud',
    BRANCH_HASH: process.env.BRANCH_HASH || '',
    POLL_INTERVAL_MS: 1500,
};

console.log("=================================================================");
console.log(" GenX Print Companion v1.0.0 (Background Tray Daemon)");
console.log(` OS Platform: ${os.platform()} (${os.arch()})`);
console.log(` Local Service: http://127.0.0.1:${CONFIG.PORT}`);
console.log("=================================================================\n");

// Standard CORS Headers for live URL web apps
const CORS_HEADERS = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Requested-With',
    'Content-Type': 'application/json; charset=utf-8',
};

// Create Native HTTP Server
const server = http.createServer((req, res) => {
    // Handle CORS preflight
    if (req.method === 'OPTIONS') {
        res.writeHead(204, CORS_HEADERS);
        res.end();
        return;
    }

    const reqUrl = new URL(req.url, `http://${req.headers.host || '127.0.0.1'}`);
    const pathname = reqUrl.pathname;

    if (req.method === 'GET' && pathname === '/status') {
        res.writeHead(200, CORS_HEADERS);
        res.end(JSON.stringify({
            status: 'online',
            version: '1.0.0',
            agent: 'GenX Print Companion',
            os: os.platform(),
            branch_hash: CONFIG.BRANCH_HASH || 'configured',
        }));
    } else if (req.method === 'GET' && pathname === '/printers') {
        try {
            const printers = getOSPrinters();
            res.writeHead(200, CORS_HEADERS);
            res.end(JSON.stringify({ success: true, printers: printers, count: printers.length }));
        } catch (e) {
            res.writeHead(500, CORS_HEADERS);
            res.end(JSON.stringify({ success: false, error: e.message }));
        }
    } else if (req.method === 'POST' && pathname === '/print') {
        let body = '';
        req.on('data', chunk => { body += chunk; });
        req.on('end', () => {
            try {
                const data = JSON.parse(body || '{}');
                const printerName = trimStr(data.printer_name);
                const payload = data.payload || '';
                const isBase64 = Boolean(data.base64);

                if (!printerName) {
                    res.writeHead(400, CORS_HEADERS);
                    res.end(JSON.stringify({ success: false, message: 'Missing printer_name parameter' }));
                    return;
                }

                const rawBytes = isBase64 ? Buffer.from(payload, 'base64') : Buffer.from(payload);
                printToOSSpooler(printerName, rawBytes);

                res.writeHead(200, CORS_HEADERS);
                res.end(JSON.stringify({ success: true, message: `Printed to ${printerName}`, printer: printerName }));
            } catch (e) {
                res.writeHead(500, CORS_HEADERS);
                res.end(JSON.stringify({ success: false, message: e.message }));
            }
        });
    } else {
        res.writeHead(404, CORS_HEADERS);
        res.end(JSON.stringify({ error: 'Endpoint not found' }));
    }
});

// Handle Port Conflicts Gracefully
server.on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
        console.log(`\n[Companion Notice] GenX Print Companion is ALREADY RUNNING on http://127.0.0.1:${CONFIG.PORT}!`);
        console.log("[Companion Notice] Process active. Press Enter to exit this window.");
        // Keep window open so it doesn't vanish silently
        setInterval(() => {}, 10000);
    } else {
        console.error('[Companion Error]', err.message);
    }
});

server.listen(CONFIG.PORT, '127.0.0.1', () => {
    console.log(`[Companion] Local HTTP Server active on http://127.0.0.1:${CONFIG.PORT}`);
    startCloudSyncLoop();
});

function trimStr(str) {
    return str ? String(str).trim() : '';
}

/**
 * Scan installed OS physical printers
 */
function getOSPrinters() {
    let printers = [];

    if (os.platform() === 'win32') {
        try {
            const output = execSync('powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Printer | Select-Object -ExpandProperty Name"', { encoding: 'utf-8' });
            printers = output.split('\n').map(s => s.trim()).filter(Boolean);
        } catch (e) {
            console.error('[Companion] PowerShell printer discovery error:', e.message);
        }
    } else {
        try {
            const output = execSync('lpstat -p 2>&1 | awk \'{print $2}\'', { encoding: 'utf-8' });
            printers = output.split('\n').map(s => s.trim()).filter(Boolean);
        } catch (e) {
            console.error('[Companion] macOS/Linux printer discovery error:', e.message);
        }
    }

    return Array.from(new Set(printers));
}

/**
 * Send raw ESC/POS payload to physical Windows/Mac spooler
 */
function printToOSSpooler(printerName, rawBytes) {
    const tempFile = path.join(os.tmpdir(), `genx_print_${Date.now()}_${Math.random().toString(36).substring(7)}.bin`);
    fs.writeFileSync(tempFile, rawBytes);

    try {
        if (os.platform() === 'win32') {
            const escapedPrinter = printerName.replace(/'/g, "''");
            const cmd = `powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Content -Path '${tempFile}' -Raw | Out-Printer -Name '${escapedPrinter}'"`;
            execSync(cmd);
        } else {
            const cmd = `lpr -P "${printerName}" -o raw "${tempFile}"`;
            execSync(cmd);
        }
        if (fs.existsSync(tempFile)) fs.unlinkSync(tempFile);
        console.log(`[Companion] PRINT SUCCESS -> Printer: "${printerName}"`);
        return true;
    } catch (e) {
        if (fs.existsSync(tempFile)) fs.unlinkSync(tempFile);
        console.error(`[Companion] PRINT ERROR -> Printer: "${printerName}":`, e.message);
        throw e;
    }
}

/**
 * Background Loop: Sync printers & poll cloud jobs
 */
async function startCloudSyncLoop() {
    let branchHash = CONFIG.BRANCH_HASH;

    const configPath = path.join(os.homedir(), '.genx_companion.json');
    if (!fs.existsSync(configPath)) {
        try {
            fs.writeFileSync(configPath, JSON.stringify({ branch_hash: '', cloud_url: 'https://digierp.cloud' }, null, 2));
        } catch (e) {}
    } else {
        try {
            const cfg = JSON.parse(fs.readFileSync(configPath, 'utf-8'));
            branchHash = cfg.branch_hash;
            if (cfg.cloud_url) CONFIG.CLOUD_URL = cfg.cloud_url;
        } catch (e) {}
    }

    const syncPrinters = async () => {
        if (!branchHash) return;
        try {
            const printers = getOSPrinters();
            await fetch(`${CONFIG.CLOUD_URL}/api/companion/register-printers`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    branch_hash: branchHash,
                    printers: printers,
                    os: os.platform(),
                    agent: 'GenX Print Companion v1.0.0',
                }),
            });
        } catch (e) {
            // Ignore offline error
        }
    };

    const pollJobs = async () => {
        if (!branchHash) return;
        try {
            const res = await fetch(`${CONFIG.CLOUD_URL}/api/companion/poll-jobs/${branchHash}`);
            const data = await res.json();
            const jobs = data?.jobs || [];
            for (const job of jobs) {
                console.log(`[Companion] Processing Cloud Print Job ${job.id} -> Printer: "${job.printer_name}"`);
                const rawBytes = job.base64 ? Buffer.from(job.payload, 'base64') : Buffer.from(job.payload);
                printToOSSpooler(job.printer_name, rawBytes);
            }
        } catch (e) {
            // Ignore polling error
        }
    };

    await syncPrinters();
    setInterval(syncPrinters, 30000);
    setInterval(pollJobs, CONFIG.POLL_INTERVAL_MS);
}

// Keep process alive indefinitely
process.on('uncaughtException', (err) => {
    console.error('[Companion Uncaught Exception]', err.message);
});
