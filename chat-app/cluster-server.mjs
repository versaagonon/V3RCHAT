import http from 'node:http';
import { spawn } from 'node:child_process';
import path from 'node:path';
import os from 'node:os';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const serverPhp = path.join(__dirname, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');
const publicDir = path.join(__dirname, 'public');

function getLanIp() {
  const ifaces = os.networkInterfaces();
  for (const [name, addrs] of Object.entries(ifaces)) {
    if (/wi-?fi|wireless|wlan/i.test(name) && addrs) {
      const ipv4 = addrs.find((a) => a.family === 'IPv4' && !a.internal);
      if (ipv4) return ipv4.address;
    }
  }
  for (const [name, addrs] of Object.entries(ifaces)) {
    if (/vmware|virtual|veth|loopback|wsl/i.test(name) || !addrs) continue;
    const ipv4 = addrs.find((a) => a.family === 'IPv4' && !a.internal);
    if (ipv4) return ipv4.address;
  }
  return '127.0.0.1';
}

const LAN_IP = getLanIp();
const NUM_WORKERS = 4;
const BASE_PORT = 8001;
const PROXY_PORT = 8000;
const workers = [];

console.log('====================================================');
console.log(` [V3RCHAT] Multi-Worker Cluster Server starting...`);
console.log(` Spawning ${NUM_WORKERS} concurrent PHP workers on ports ${BASE_PORT}-${BASE_PORT + NUM_WORKERS - 1}`);
console.log(` Binding proxy to 0.0.0.0:${PROXY_PORT} (Local PC & WiFi LAN access)`);
console.log('====================================================');

for (let i = 0; i < NUM_WORKERS; i++) {
  const port = BASE_PORT + i;
  const php = spawn('php', ['-S', `127.0.0.1:${port}`, serverPhp], {
    cwd: publicDir,
    stdio: 'ignore'
  });
  workers.push({ port, process: php });
}

let rr = 0;

const proxy = http.createServer((req, res) => {
  const worker = workers[rr++ % workers.length];
  
  const headers = { ...req.headers };
  // Preserve real client host so Laravel root/asset URLs resolve properly on phone
  headers['host'] = req.headers.host || `127.0.0.1:${PROXY_PORT}`;
  headers['x-forwarded-for'] = req.socket.remoteAddress || '127.0.0.1';
  headers['x-forwarded-host'] = req.headers.host || `127.0.0.1:${PROXY_PORT}`;
  headers['x-forwarded-proto'] = 'http';

  const proxyReq = http.request({
    hostname: '127.0.0.1',
    port: worker.port,
    path: req.url,
    method: req.method,
    headers
  }, (proxyRes) => {
    res.writeHead(proxyRes.statusCode, proxyRes.headers);
    proxyRes.pipe(res);
  });

  proxyReq.on('error', (err) => {
    if (!res.headersSent) {
      res.writeHead(502, { 'Content-Type': 'text/plain' });
      res.end(`[V3RCHAT Cluster] Worker on port ${worker.port} busy or unavailable: ${err.message}`);
    }
  });

  req.pipe(proxyReq);
});

proxy.listen(PROXY_PORT, '0.0.0.0', () => {
  console.log(`[SUCCESS] V3RCHAT Cluster active on port ${PROXY_PORT}`);
  console.log(`- Local PC   : http://127.0.0.1:${PROXY_PORT}`);
  console.log(`- LAN / WiFi : http://${LAN_IP}:${PROXY_PORT}`);
  console.log(`Concurrent requests will be load-balanced across all ${NUM_WORKERS} workers.`);
});

function cleanup() {
  console.log('\n[V3RCHAT] Stopping all PHP cluster workers...');
  workers.forEach((w) => {
    try {
      w.process.kill();
    } catch {}
  });
  process.exit(0);
}

process.on('SIGINT', cleanup);
process.on('SIGTERM', cleanup);
process.on('exit', cleanup);

