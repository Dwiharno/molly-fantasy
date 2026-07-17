import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const sourceRoot = path.join(root, 'node_modules');
const targetRoot = path.join(root, 'public', 'vendor');

const assets = [
  ['bootstrap/dist', 'bootstrap'],
  ['jquery/dist', 'jquery'],
  ['datatables.net/js', 'datatables.net/js'],
  ['datatables.net-bs5/js', 'datatables.net-bs5/js'],
  ['datatables.net-bs5/css', 'datatables.net-bs5/css'],
  ['select2/dist', 'select2'],
  ['sweetalert2/dist', 'sweetalert2'],
  ['chart.js/dist', 'chart.js'],
  ['@fortawesome/fontawesome-free/css', 'fontawesome-free/css'],
  ['@fortawesome/fontawesome-free/webfonts', 'fontawesome-free/webfonts'],
];

fs.rmSync(targetRoot, { recursive: true, force: true });
fs.mkdirSync(targetRoot, { recursive: true });

for (const [from, to] of assets) {
  const sourcePath = path.join(sourceRoot, from);
  const targetPath = path.join(targetRoot, to);

  if (!fs.existsSync(sourcePath)) {
    continue;
  }

  fs.mkdirSync(path.dirname(targetPath), { recursive: true });
  fs.cpSync(sourcePath, targetPath, { recursive: true });
}
