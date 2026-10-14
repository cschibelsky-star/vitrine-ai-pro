const { chromium } = require('playwright');
const AxeBuilder = require('@axe-core/playwright').default;
const fs = require('node:fs');
(async () => {
  fs.mkdirSync('preview-evidence', { recursive: true });
  const browser = await chromium.launch();
  try {
    for (const [name, width, height] of [['reference-1024',1024,1536],['desktop',1440,1000],['mobile',390,844]]) {
      const context = await browser.newContext({ viewport: { width, height }, reducedMotion: 'reduce' });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', e => errors.push(e.message));
      await page.goto('http://127.0.0.1:8088/', { waitUntil: 'networkidle' });
      await page.getByRole('heading', { level: 1 }).waitFor();
      if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) {
        console.log(await page.evaluate(() => Array.from(document.querySelectorAll('body *')).map(el => ({tag:el.tagName, cls:el.className, left:el.getBoundingClientRect().left, right:el.getBoundingClientRect().right})).filter(el => el.right > innerWidth + 1 || el.left < -1)));
        await page.screenshot({ path: 'preview-evidence/' + name + '-overflow.png', fullPage: true });
        throw new Error(name + ': horizontal overflow');
      }
      const logo = page.locator('.header .brand-official');
      await logo.waitFor();
      if ((await logo.getAttribute('src')) !== '/assets/img/logo-vitrine-approved.webp') {
        throw new Error(name + ': official logo asset not applied');
      }
      if (name === 'reference-1024') {
        const required = ['.header', '.hero', '#projetos', '#aplicacoes', '#ecossistema', '.footer'];
        const rects = [];
        for (const selector of required) {
          const el = page.locator(selector);
          await el.waitFor();
          const r = await el.boundingBox();
          rects.push({ selector, y: Math.round(r.y), height: Math.round(r.height), width: Math.round(r.width) });
        }
        fs.writeFileSync('preview-evidence/reference-1024-layout.json', JSON.stringify(rects, null, 2));
      }
      const images = await page.locator('img').evaluateAll(imgs => imgs.map(img => ({ src: img.getAttribute('src'), ok: img.complete && img.naturalWidth > 0 })));
      // Load lazy images before checking them.
      await page.locator('.footer').scrollIntoViewIfNeeded();
      await page.waitForTimeout(500);
      const broken = await page.locator('img').evaluateAll(imgs => imgs.filter(img => !img.complete || img.naturalWidth === 0).map(img => img.src));
      if (broken.length) throw new Error('Broken images: ' + broken.join(', '));
      await page.evaluate(() => scrollTo(0,0));
      if (name === 'mobile') {
        const menu = page.getByRole('button', { name: /Menu/ });
        await menu.click();
        if (await menu.getAttribute('aria-expanded') !== 'true') throw new Error('Menu did not open');
        await page.keyboard.press('Escape');
        if (await menu.getAttribute('aria-expanded') !== 'false') throw new Error('Menu did not close');
      }
      const axe = await new AxeBuilder({ page }).withTags(['wcag2a','wcag2aa','wcag21aa']).analyze();
      fs.writeFileSync('preview-evidence/' + name + '-accessibility.json', JSON.stringify(axe.violations, null, 2));
      if (axe.violations.length) throw new Error(name + ': accessibility violations ' + axe.violations.map(v => v.id).join(', '));
      if (errors.length) throw new Error(errors.join('; '));
      await page.screenshot({ path: 'preview-evidence/' + name + '.png', fullPage: true });
      console.log(name + ': layout, images, navigation and accessibility PASS');
      await context.close();
    }
  } finally { await browser.close(); }
})().catch(e => { console.error(e); process.exit(1); });
