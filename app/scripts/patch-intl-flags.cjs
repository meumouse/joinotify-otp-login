const fs = require('node:fs');
const path = require('node:path');

const distDir = path.resolve(__dirname, '..', '..', 'dist');
const cssFiles = fs.readdirSync(path.join(distDir, 'assets')).filter((file) => file.endsWith('.css'));

if (cssFiles.length === 0) {
  process.exit(0);
}

const cssPath = path.join(distDir, 'assets', cssFiles[0]);
let css = fs.readFileSync(cssPath, 'utf8');

const scale = 2;

css = css
  .replace(/--iti-flag-height:\s*12px/g, '--iti-flag-height: 36px')
  .replace(/--iti-flag-width:\s*16px/g, '--iti-flag-width: 48px')
  .replace(/--iti-flag-sprite-width:\s*3904px/g, '--iti-flag-sprite-width: 7808px')
  .replace(/--iti-flag-sprite-height:\s*12px/g, '--iti-flag-sprite-height: 24px')
  .replace(/--iti-flag-offset:\s*(-?\d+)px/g, (_, value) => `--iti-flag-offset: ${Number(value) * scale}px`);

fs.writeFileSync(cssPath, css, 'utf8');
