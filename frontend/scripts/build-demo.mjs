#!/usr/bin/env node
import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const frontendRoot = path.resolve(__dirname, '..');
const outRoot = path.join(frontendRoot, 'dist-demo', 'familyMeals');
const demoBase = '/familyMeals/';

function run(cmd, cwd, extraEnv = {}) {
  execSync(cmd, {
    cwd,
    stdio: 'inherit',
    env: {
      ...process.env,
      VITE_DEMO_MODE: '1',
      VITE_BFF_BASE_URL: `${demoBase}bff/v1`,
      ...extraEnv,
    },
  });
}

function copyDir(src, dest) {
  fs.mkdirSync(dest, { recursive: true });
  for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
    const from = path.join(src, entry.name);
    const to = path.join(dest, entry.name);
    if (entry.isDirectory()) {
      copyDir(from, to);
    } else {
      fs.copyFileSync(from, to);
    }
  }
}

function rmDir(dir) {
  if (fs.existsSync(dir)) {
    fs.rmSync(dir, { recursive: true, force: true });
  }
}

console.log('Building demo bundle for danilmakes (base: /familyMeals/)…');

run('npm run build --workspace=@meal/ui-tokens', frontendRoot);

const remotes = [
  { pkg: '@meal/mf-recipes', folder: 'mf-recipes', sub: 'mf-recipes' },
  { pkg: '@meal/mf-planner', folder: 'mf-planner', sub: 'mf-planner' },
  { pkg: '@meal/mf-shopping', folder: 'mf-shopping', sub: 'mf-shopping' },
];

for (const remote of remotes) {
  run(`npm run build --workspace=${remote.pkg}`, frontendRoot, {
    VITE_DEMO_BASE: `${demoBase}${remote.sub}/`,
  });
}

run('npm run build --workspace=@meal/host', frontendRoot, {
  VITE_DEMO_BASE: demoBase,
  VITE_MF_RECIPES_URL: `${demoBase}mf-recipes/assets/remoteEntry.js`,
  VITE_MF_PLANNER_URL: `${demoBase}mf-planner/assets/remoteEntry.js`,
  VITE_MF_SHOPPING_URL: `${demoBase}mf-shopping/assets/remoteEntry.js`,
});

rmDir(outRoot);
fs.mkdirSync(outRoot, { recursive: true });

copyDir(path.join(frontendRoot, 'apps/host/dist'), outRoot);
for (const remote of remotes) {
  copyDir(path.join(frontendRoot, 'apps', remote.folder, 'dist'), path.join(outRoot, remote.sub));
}

console.log(`Demo ready: ${outRoot}`);
