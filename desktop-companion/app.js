/**
 * GenX Print Companion - Interactive System Tray Direct Thermal Print Agent
 * 100% Zero-Dependency Native Node.js Implementation
 * Supports Windows (.exe) & macOS (.dmg)
 */

const http = require('http');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const os = require('os');
const readline = require('readline');

// CLI Arguments
const args = process.argv.slice(2);
const cliUrl = args.find(a => a.startsWith('http://') || a.startsWith('https://')) || '';
const cliHash = args.find(a => !a.startsWith('http://') && !a.startsWith('https://') && a.length > 5) || '';

const CONFIG = {
    PORT: 8181,
    CLOUD_URL: process.env.CLOUD_URL || cliUrl || '',
    BRANCH_HASH: process.env.BRANCH_HASH || cliHash || '',
    POLL_INTERVAL_MS: 1500,
};

// In-Memory Log Stream & Last Receipt Preview
const LOGS = [];
let LAST_RECEIPT = '';

function formatRawBytesToPreview(rawBytes) {
    let str = rawBytes.toString('utf-8');
    // Clean ESC/POS binary codes for preview
    str = str.replace(/\x1B@/g, '')
             .replace(/\x1Ba\x01/g, '')
             .replace(/\x1Ba\x00/g, '')
             .replace(/\x1BE\x01/g, '')
             .replace(/\x1BE\x00/g, '')
             .replace(/\x1D!\x11/g, '')
             .replace(/\x1D!\x00/g, '')
             .replace(/\x1DV\x41\x03/g, '\n[----------------- PAPER CUT -----------------]');
    return str.trim();
}

function addLog(type, message) {
    const time = new Date().toLocaleTimeString();
    const entry = { time, type, message };
    LOGS.push(entry);
    if (LOGS.length > 100) LOGS.shift();

    const prefix = `[${time}] [${type}]`;
    if (type === 'ERROR') {
        console.error(`\x1b[31m${prefix} ${message}\x1b[0m`);
    } else if (type === 'SUCCESS' || type === 'PRINT') {
        console.log(`\x1b[32m${prefix} ${message}\x1b[0m`);
    } else {
        console.log(`${prefix} ${message}`);
    }
}

// Standard CORS Headers
const CORS_HEADERS = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Requested-With',
    'Content-Type': 'application/json; charset=utf-8',
};

// Create Native HTTP Server
const server = http.createServer((req, res) => {
    if (req.method === 'OPTIONS') {
        res.writeHead(204, CORS_HEADERS);
        res.end();
        return;
    }

    const reqUrl = new URL(req.url, `http://${req.headers.host || '127.0.0.1'}`);
    const pathname = reqUrl.pathname;

    // Interactive Web Dashboard
    if (req.method === 'GET' && (pathname === '/' || pathname === '/index.html')) {
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end(`<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GenX Print Companion Dashboard</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; margin: 0; padding: 30px; color: #f8fafc; }
        .card { max-width: 680px; margin: 0 auto; background: #1e293b; padding: 28px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); border: 1px solid #334155; }
        h2 { margin-top: 0; color: #f8fafc; font-size: 22px; display: flex; align-items: center; gap: 10px; }
        label { display: block; margin-top: 14px; font-weight: 600; font-size: 13px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        input { width: 100%; padding: 12px; margin-top: 6px; background: #0f172a; border: 1px solid #475569; border-radius: 8px; font-size: 14px; color: #f8fafc; box-sizing: border-box; }
        input:focus { outline: none; border-color: #3b82f6; }
        button { margin-top: 20px; width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #1d4ed8; }
        .status { margin-top: 16px; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .online { background: #14532d; color: #4ade80; border: 1px solid #166534; }
        .offline { background: #451a03; color: #fbbf24; border: 1px solid #78350f; }
        .logs-box { margin-top: 24px; background: #090d16; padding: 16px; border-radius: 10px; border: 1px solid #1e293b; font-family: monospace; font-size: 12px; height: 180px; overflow-y: auto; color: #cbd5e1; }
        .log-entry { margin-bottom: 4px; word-break: break-all; }
        .log-ERROR { color: #f87171; }
        .log-PRINT { color: #60a5fa; font-weight: bold; }
        .log-SUCCESS { color: #4ade80; }
        .printers { margin-top: 20px; background: #0f172a; padding: 16px; border-radius: 10px; border: 1px solid #334155; }
        .printers h3 { margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px; }
        .printers ul { margin: 0; padding-left: 20px; font-size: 13px; color: #cbd5e1; }
        .receipt-paper { background: #ffffff; color: #0f172a; font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.35; padding: 24px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); max-width: 440px; margin: 12px auto 0 auto; white-space: pre-wrap; word-break: break-all; border-bottom: 6px dashed #cbd5e1; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🖨️ GenX Print Companion Dashboard</h2>
        <div id="statusBadge" class="status offline">● Disconnected / Awaiting Branch Configuration</div>
        
        <form id="configForm">
            <label>Server URL (e.g. https://digierp.cloud)</label>
            <input type="url" id="cloudUrl" placeholder="https://digierp.cloud" required>

            <label>Branch Key (Copy from Printer Settings page)</label>
            <input type="text" id="branchHash" placeholder="e.g. 5f8a9c2b3e..." required>

            <button type="submit">Save & Connect to Cloud</button>
        </form>

        <div class="printers">
            <h3>Connected Physical OS Printers (<span id="printerCount">0</span>)</h3>
            <ul id="printerList"><li>Scanning OS Spooler...</li></ul>
        </div>

        <label>📄 Virtual Thermal Receipt Paper Roll Preview (Live Direct Print Output)</label>
        <div id="receiptPreview" class="receipt-paper">
            <div style="text-align: center; color: #64748b; padding: 20px 0;">No receipts printed yet. Click "Print KOT" or "Bill & Print" in your web app to preview the exact thermal paper output here!</div>
        </div>

        <label>Real-Time Application & Print Logs</label>
        <div id="logsBox" class="logs-box">
            <div class="log-entry">Waiting for system logs...</div>
        </div>
    </div>

    <script>
        async function loadConfig() {
            try {
                const res = await fetch('/status');
                const data = await res.json();
                if (data.status === 'online') {
                    if (data.cloud_url) document.getElementById('cloudUrl').value = data.cloud_url;
                    if (data.branch_hash && data.branch_hash !== 'not_configured') {
                        document.getElementById('branchHash').value = data.branch_hash;
                    }
                    if (data.cloud_url && data.branch_hash && data.branch_hash !== 'not_configured') {
                        document.getElementById('statusBadge').className = 'status online';
                        document.getElementById('statusBadge').innerHTML = '● Connected & Active: ' + data.cloud_url;
                    }
                }
            } catch(e) {}
            loadPrinters();
            loadLogs();
        }

        async function loadPrinters() {
            try {
                const res = await fetch('/printers');
                const data = await res.json();
                if (data.success && Array.isArray(data.printers)) {
                    document.getElementById('printerCount').textContent = data.printers.length;
                    const ul = document.getElementById('printerList');
                    ul.innerHTML = '';
                    if (data.printers.length === 0) {
                        ul.innerHTML = '<li>No physical thermal printers detected on OS spooler.</li>';
                    } else {
                        data.printers.forEach(p => {
                            const li = document.createElement('li');
                            li.textContent = p;
                            ul.appendChild(li);
                        });
                    }
                }
            } catch(e) {}
        }

        async function loadLogs() {
            try {
                const res = await fetch('/logs');
                const data = await res.json();
                if (data.success && Array.isArray(data.logs)) {
                    const box = document.getElementById('logsBox');
                    box.innerHTML = '';
                    data.logs.forEach(l => {
                        const div = document.createElement('div');
                        div.className = 'log-entry log-' + l.type;
                        div.textContent = '[' + l.time + '] [' + l.type + '] ' + l.message;
                        box.appendChild(div);
                    });
                    box.scrollTop = box.scrollHeight;
                }
            } catch(e) {}
        }

        document.getElementById('configForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const cloudUrl = document.getElementById('cloudUrl').value.trim();
            const branchHash = document.getElementById('branchHash').value.trim();

            const res = await fetch('/configure', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cloud_url: cloudUrl, branch_hash: branchHash })
            });

            const data = await res.json();
            if (data.success) {
                alert('Connected successfully to ' + cloudUrl + '!');
                loadConfig();
            } else {
                alert('Error: ' + data.message);
            }
        });

        async function loadReceiptPreview() {
            try {
                const res = await fetch('/receipt-preview');
                const data = await res.json();
                if (data.success && data.receipt) {
                    document.getElementById('receiptPreview').textContent = data.receipt;
                }
            } catch(e) {}
        }

        loadConfig();
        setInterval(loadLogs, 2000);
        setInterval(loadReceiptPreview, 2000);
    </script>
</body>
</html>`);
        return;
    }

    if (req.method === 'GET' && pathname === '/status') {
        res.writeHead(200, CORS_HEADERS);
        res.end(JSON.stringify({
            status: 'online',
            version: '1.0.0',
            agent: 'GenX Print Companion',
            os: os.platform(),
            cloud_url: CONFIG.CLOUD_URL || '',
            branch_hash: CONFIG.BRANCH_HASH || 'not_configured',
        }));
    } else if (req.method === 'GET' && pathname === '/logs') {
        res.writeHead(200, CORS_HEADERS);
        res.end(JSON.stringify({ success: true, logs: LOGS }));
    } else if (req.method === 'GET' && pathname === '/receipt-preview') {
        res.writeHead(200, CORS_HEADERS);
        res.end(JSON.stringify({ success: true, receipt: LAST_RECEIPT }));
    } else if (req.method === 'GET' && pathname === '/printers') {
        try {
            const printers = getOSPrinters();
            res.writeHead(200, CORS_HEADERS);
            res.end(JSON.stringify({ success: true, printers: printers, count: printers.length }));
        } catch (e) {
            res.writeHead(500, CORS_HEADERS);
            res.end(JSON.stringify({ success: false, error: e.message }));
        }
    } else if (req.method === 'POST' && pathname === '/configure') {
        let body = '';
        req.on('data', chunk => { body += chunk; });
        req.on('end', () => {
            try {
                const data = JSON.parse(body || '{}');
                const branchHash = trimStr(data.branch_hash);
                const cloudUrl = trimStr(data.cloud_url);

                if (branchHash) {
                    CONFIG.BRANCH_HASH = branchHash;
                    CONFIG.CLOUD_URL = cloudUrl;

                    saveConfigFile(branchHash, cloudUrl);
                    addLog('SUCCESS', `Configured successfully: Cloud="${cloudUrl}", Branch="${branchHash}"`);

                    if (global.triggerCompanionSync) {
                        global.triggerCompanionSync();
                    }
                }

                res.writeHead(200, CORS_HEADERS);
                res.end(JSON.stringify({ success: true, message: 'Companion configured successfully', branch_hash: CONFIG.BRANCH_HASH }));
            } catch (e) {
                addLog('ERROR', `Configuration error: ${e.message}`);
                res.writeHead(500, CORS_HEADERS);
                res.end(JSON.stringify({ success: false, message: e.message }));
            }
        });
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

                addLog('PRINT', `Receiving direct print job for physical printer "${printerName}"...`);
                const rawBytes = isBase64 ? Buffer.from(payload, 'base64') : Buffer.from(payload);
                printToOSSpooler(printerName, rawBytes);

                res.writeHead(200, CORS_HEADERS);
                res.end(JSON.stringify({ success: true, message: `Printed to ${printerName}`, printer: printerName }));
            } catch (e) {
                addLog('ERROR', `Print failure for printer "${data.printer_name}": ${e.message}`);
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
        addLog('ERROR', `Port ${CONFIG.PORT} is already in use by another instance!`);
        console.log(`\n[Companion Notice] GenX Print Companion is ALREADY RUNNING on http://127.0.0.1:${CONFIG.PORT}!`);
        setInterval(() => {}, 10000);
    } else {
        addLog('ERROR', err.message);
    }
});

server.listen(CONFIG.PORT, '127.0.0.1', () => {
    addLog('INFO', `Local HTTP Service active on http://127.0.0.1:${CONFIG.PORT}`);
    startCloudSyncLoop();
    initInteractiveCLI();
});

function trimStr(str) {
    return str ? String(str).trim() : '';
}

function saveConfigFile(branchHash, cloudUrl) {
    const configPath = path.join(os.homedir(), '.genx_companion.json');
    try {
        fs.writeFileSync(configPath, JSON.stringify({ branch_hash: branchHash, cloud_url: cloudUrl }, null, 2));
    } catch (e) {
        addLog('ERROR', `Failed to save config file: ${e.message}`);
    }
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
            addLog('ERROR', `PowerShell printer discovery error: ${e.message}`);
        }
    } else {
        try {
            const output = execSync('lpstat -p 2>&1 | awk \'{print $2}\'', { encoding: 'utf-8' });
            printers = output.split('\n').map(s => s.trim()).filter(Boolean);
        } catch (e) {
            addLog('ERROR', `macOS printer discovery error: ${e.message}`);
        }
    }

    return Array.from(new Set(printers));
}

/**
 * Send raw ESC/POS payload to physical Windows/Mac spooler
 */
function printToOSSpooler(printerName, rawBytes) {
    try {
        LAST_RECEIPT = formatRawBytesToPreview(rawBytes);
    } catch (e) {}

    const tempFile = path.join(os.tmpdir(), `genx_print_${Date.now()}_${Math.random().toString(36).substring(7)}.bin`);
    fs.writeFileSync(tempFile, rawBytes);

    try {
        if (os.platform() === 'win32') {
            const escapedPrinter = printerName.replace(/'/g, "''");
            const escapedFile = tempFile.replace(/'/g, "''");
            const psScriptFile = path.join(os.tmpdir(), `genx_raw_print_${Date.now()}.ps1`);

            const psScriptContent = `
$code = @"
using System;
using System.IO;
using System.Runtime.InteropServices;
public class RawPrinterHelper {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public class DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }
    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);
    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool ClosePrinter(IntPtr hPrinter);
    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);
    [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);
    [DllImport("winspool.Drv", EntryPoint = "StartPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);
    [DllImport("winspool.Drv", EntryPoint = "EndPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);
    [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);
    public static bool PrintFile(string printerName, string filePath) {
        byte[] bytes = File.ReadAllBytes(filePath);
        IntPtr hPrinter = IntPtr.Zero;
        DOCINFOA di = new DOCINFOA { pDocName = "GenX POS Receipt", pDataType = "RAW" };
        if (OpenPrinter(printerName, out hPrinter, IntPtr.Zero)) {
            if (StartDocPrinter(hPrinter, 1, di)) {
                if (StartPagePrinter(hPrinter)) {
                    IntPtr pBytes = Marshal.AllocHGlobal(bytes.Length);
                    Marshal.Copy(bytes, 0, pBytes, bytes.Length);
                    Int32 dwWritten = 0;
                    bool success = WritePrinter(hPrinter, pBytes, bytes.Length, out dwWritten);
                    Marshal.FreeHGlobal(pBytes);
                    EndPagePrinter(hPrinter);
                }
                EndDocPrinter(hPrinter);
            }
            ClosePrinter(hPrinter);
            return true;
        }
        return false;
    }
}
"@
if (-not ([System.Management.Automation.PSTypeName]'RawPrinterHelper').Type) {
    Add-Type -TypeDefinition $code
}
[RawPrinterHelper]::PrintFile('${escapedPrinter}', '${escapedFile}')
`;

            fs.writeFileSync(psScriptFile, psScriptContent, 'utf-8');
            try {
                const cmd = `powershell -NoProfile -ExecutionPolicy Bypass -File "${psScriptFile}"`;
                execSync(cmd);
            } finally {
                if (fs.existsSync(psScriptFile)) fs.unlinkSync(psScriptFile);
            }
        } else {
            const cmd = `lpr -P "${printerName}" -o raw "${tempFile}"`;
            execSync(cmd);
        }
        if (fs.existsSync(tempFile)) fs.unlinkSync(tempFile);
        addLog('SUCCESS', `PRINT SUCCESS -> Physical Printer: "${printerName}" (RAW Direct Bytecode)`);
        return true;
    } catch (e) {
        if (fs.existsSync(tempFile)) fs.unlinkSync(tempFile);
        addLog('ERROR', `PRINT ERROR -> Physical Printer: "${printerName}": ${e.message}`);
        throw e;
    }
}

/**
 * Background Loop: Sync printers & poll cloud jobs
 */
async function startCloudSyncLoop() {
    const configPath = path.join(os.homedir(), '.genx_companion.json');
    if (!fs.existsSync(configPath)) {
        try {
            fs.writeFileSync(configPath, JSON.stringify({ branch_hash: '', cloud_url: '' }, null, 2));
        } catch (e) {}
    } else {
        try {
            const cfg = JSON.parse(fs.readFileSync(configPath, 'utf-8'));
            if (cfg.branch_hash) CONFIG.BRANCH_HASH = cfg.branch_hash;
            if (cfg.cloud_url) CONFIG.CLOUD_URL = cfg.cloud_url;
        } catch (e) {}
    }

    const syncPrinters = async () => {
        const activeBranchHash = CONFIG.BRANCH_HASH;
        const activeCloudUrl = CONFIG.CLOUD_URL;
        if (!activeBranchHash || !activeCloudUrl) return;

        try {
            const printers = getOSPrinters();
            await fetch(`${activeCloudUrl}/api/companion/register-printers`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    branch_hash: activeBranchHash,
                    printers: printers,
                    os: os.platform(),
                    agent: 'GenX Print Companion v1.0.0',
                }),
            });
            addLog('INFO', `Synced ${printers.length} physical printers with ${activeCloudUrl}`);
        } catch (e) {
            addLog('ERROR', `Cloud sync error with ${activeCloudUrl}: ${e.message}`);
        }
    };

    global.triggerCompanionSync = syncPrinters;

    const pollJobs = async () => {
        const activeBranchHash = CONFIG.BRANCH_HASH;
        const activeCloudUrl = CONFIG.CLOUD_URL;
        if (!activeBranchHash || !activeCloudUrl) return;

        try {
            const res = await fetch(`${activeCloudUrl}/api/companion/poll-jobs/${activeBranchHash}`);
            const data = await res.json();
            const jobs = data?.jobs || [];
            for (const job of jobs) {
                addLog('PRINT', `Processing Cloud Print Job ${job.id} -> Printer: "${job.printer_name}"`);
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

/**
 * Interactive Command Line Menu & Prompts
 */
function initInteractiveCLI() {
    const rl = readline.createInterface({
        input: process.stdin,
        output: process.stdout,
    });

    const askConfig = () => {
        rl.question('\n👉 Enter Server URL (e.g. https://digierp.cloud or http://genx.local): ', (answerUrl) => {
            let url = answerUrl.trim();
            if (url && !url.startsWith('http://') && !url.startsWith('https://')) {
                url = 'https://' + url;
            }
            if (url) CONFIG.CLOUD_URL = url;
            askBranchHash();
        });
    };

    const askBranchHash = () => {
        rl.question('👉 Enter Branch Key (copy from Printer Settings page): ', (answerHash) => {
            const hash = answerHash.trim();
            if (hash) {
                CONFIG.BRANCH_HASH = hash;
                saveConfigFile(CONFIG.BRANCH_HASH, CONFIG.CLOUD_URL);
                addLog('SUCCESS', `Configured via CLI: ${CONFIG.CLOUD_URL} (${CONFIG.BRANCH_HASH})`);
                if (global.triggerCompanionSync) global.triggerCompanionSync();
            }
            showMenu();
        });
    };

    const showMenu = () => {
        console.log("\n-----------------------------------------------------------------");
        console.log(` SERVER URL : ${CONFIG.CLOUD_URL || '[ Not Configured - Open http://127.0.0.1:8181 ]'}`);
        console.log(` BRANCH KEY : ${CONFIG.BRANCH_HASH || '[ Not Configured - Open http://127.0.0.1:8181 ]'}`);
        console.log("-----------------------------------------------------------------");
        console.log(" Interactive Shortcuts:");
        console.log("   Press [C] + Enter ➔ Re-configure Server URL & Branch Key");
        console.log("   Press [R] + Enter ➔ Re-scan OS Physical Printers");
        console.log("   Press [H] + Enter ➔ Show Help & Web Settings Link");
        console.log("-----------------------------------------------------------------\n");
    };

    rl.on('line', (line) => {
        const input = line.trim().toUpperCase();
        if (input === 'C') {
            CONFIG.CLOUD_URL = '';
            CONFIG.BRANCH_HASH = '';
            askConfig();
        } else if (input === 'R') {
            const printers = getOSPrinters();
            addLog('INFO', `Scanned ${printers.length} physical printers: ${printers.join(', ')}`);
            if (global.triggerCompanionSync) global.triggerCompanionSync();
        } else if (input === 'H') {
            console.log(`\nOpen http://127.0.0.1:8181 in your browser for the full interactive settings dashboard & error logs!`);
        }
    });

    if (!CONFIG.CLOUD_URL || !CONFIG.BRANCH_HASH) {
        askConfig();
    } else {
        showMenu();
    }
}

// Suppress Node.js ExperimentalWarning logs
process.on('warning', (warning) => {
    if (warning.name === 'ExperimentalWarning') return;
    console.warn(warning.name, warning.message);
});

// Keep process alive indefinitely
process.on('uncaughtException', (err) => {
    addLog('ERROR', `Uncaught Exception: ${err.message}`);
});
