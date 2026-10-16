// Fail-closed asset integrity gate for the Vitrine IA Pro approved Home.
// Passing this check never replaces screenshot comparison on the official HML.
const fs = require('node:fs');
const crypto = require('node:crypto');

const expected = new Map([
  ['assets/reference/vitrine-home-approved-1024x1536.png','c9b53dda1b4deb67a124955bcd84da978eea70f08a348cce78ff4162bf7a63b3'],
  ['assets/img/home/hero-approved.webp','64d74c5af7c066fdf08f7b142923538b7629bb094bcce8b8dcc6a62291dceb1c'],
  ['assets/img/home/guide-approved.webp','abda4d0a89903c005eb0eea385c95bae17896ec09ac81240df587b17debde901'],
  ['assets/img/home/tv-project-approved.webp','c3c63c4528f55b2b4c60c9483206d914c1108e2eba0cb61b1daa1425c6fd5a55'],
  ['assets/img/home/gov-approved.webp','a07e0a057ae1287953a3990031279b5503e1113190e3c4679fde020ec310a37a'],
  ['assets/img/home/sismed-approved.webp','9cf45178857d8dd11729ec094b891cddc332731dd1695a2f8c50c928d4eb5abd'],
  ['assets/img/cases/conheca-sumare-approved.webp','b01274962eb59149012a927ca0bcb43b1783855914cdba17c8a9257efa95cfb9'],
  ['assets/img/cases/tv-sumare-approved.webp','cb3dad8c88f82d78ae37fd921744eb037f3cc5dceba0972efc057d832ad982f5'],
]);
const errors = [];
for (const [filename, sha] of expected) {
  let bytes;
  try { bytes = fs.readFileSync(filename); }
  catch { errors.push(`${filename}: arquivo oficial ausente`); continue; }
  if (bytes.length < 1000) { errors.push(`${filename}: arquivo vazio ou incompleto`); continue; }
  const actual = crypto.createHash('sha256').update(bytes).digest('hex');
  if (actual !== sha) errors.push(`${filename}: SHA-256 divergente`);
}
let html = '';
try { html = fs.readFileSync('index.php', 'utf8'); }
catch { errors.push('index.php indisponivel'); }
for (const filename of expected.keys()) {
  if (filename.startsWith('assets/reference/')) continue;
  if (!html.includes(`/${filename}`)) errors.push(`${filename}: nao referenciado na Home`);
}
if (errors.length) {
  console.error('HOMOLOGACAO VISUAL BLOQUEADA');
  errors.forEach(e => console.error(` - ${e}`));
  process.exit(1);
}
console.log('Integridade dos oito arquivos e referencias HTML: OK. Comparacao visual HML ainda obrigatoria.');
