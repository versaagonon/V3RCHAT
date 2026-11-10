import http from 'node:http';
import { spawn } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const serverPhp = path.join(__dirname, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');
const publicDir = path.join(__dirname, 'public');

const NUM_WORKERS = 4;
const BASE_PORT = 8001;
const PROXY_PORT = 8000;
const workers = [];

console.log('====================================================');
console.log(` [V3RCHAT] Multi-Worker Cluster Server starting...`);
console.log(` Spawning ${NUM_WORKERS} concurrent PHP workers on ports ${BASE_PORT}-${BASE_PORT + NUM_WORKERS - 1}`);
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
  headers['host'] = `127.0.0.1:${PROXY_PORT}`;
  headers['x-forwarded-for'] = req.socket.remoteAddress || '127.0.0.1';
  headers['x-forwarded-host'] = `127.0.0.1:${PROXY_PORT}`;
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

proxy.listen(PROXY_PORT, '127.0.0.1', () => {
  console.log(`[SUCCESS] V3RCHAT Cluster active on http://127.0.0.1:${PROXY_PORT}`);
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

