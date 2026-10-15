// Block visual homologation until original approved artwork is actually integrated.
// Checking HTTP 200 for placeholder JPEGs is not visual acceptance.
const fs = require('node:fs');
const crypto = require('node:crypto');
const path = require('node:path');

const required = [
  ['assets/img/home/hero-approved.webp', 'Globo e skyline oficiais da arte aprovada'],
  ['assets/img/home/guide-approved.webp', 'Arte oficial Guia Digital'],
  ['assets/img/home/tv-project-approved.webp', 'Arte oficial TV Digital'],
  ['assets/img/home/gov-approved.webp', 'Arte oficial Governo Digital'],
  ['assets/img/home/sismed-approved.webp', 'Arte oficial SISMED'],
  ['assets/img/cases/conheca-sumare-approved.webp', 'Arte original Conheça Sumaré'],
  ['assets/img/cases/tv-sumare-approved.webp', 'Arte original TV Sumaré'],
];
let failures = [];
for (const [filename, description] of required) {
  if (!fs.existsSync(filename) || fs.statSync(filename).size < 1000) {
    failures.push(filename + ': missing original artwork — ' + description);
  }
}
const source = 'assets/reference/vitrine-home-approved-1024x1536.png';
const expected = 'c9b53dda1b4deb67a124955bcd84da978eea70f08a348cce78ff4162bf7a63b3';
if (!fs.existsSync(source)) failures.push(source + ': original reference is missing');
else {
  const sha = crypto.createHash('sha256').update(fs.readFileSync(source)).digest('hex');
  if (sha !== expected) failures.push(source + ': reference SHA-256 mismatch');
}
const html = fs.readFileSync('index.php', 'utf8');
for (const [filename] of required) {
  if (!html.includes('/' + filename)) failures.push(filename + ': not referenced by HTML');
}
if (failures.length) {
  console.error('VISUAL HOMOLOGATION BLOCKED — required approved files:');
  failures.forEach(s => console.error(' - ' + s));
  process.exit(1);
}
console.log('Original image asset manifest gate PASS. Pixel comparison remains required.');
