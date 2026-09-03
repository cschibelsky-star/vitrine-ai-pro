(() => {
  const root = document.body;
  const hero = document.querySelector('.salesHero');
  if (!root?.classList.contains('salesHome') || !hero) return;

  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduced) return;

  const canvas = document.createElement('canvas');
  canvas.className = 'salesDataCanvas';
  canvas.setAttribute('aria-hidden', 'true');
  hero.prepend(canvas);

  const neon = document.createElement('div');
  neon.className = 'salesNeonScene';
  neon.setAttribute('aria-hidden', 'true');
  neon.innerHTML = '<i></i><i></i><i></i><i></i><i></i><i></i>';
  hero.append(neon);

  const signal = document.createElement('div');
  signal.className = 'salesSignalHud';
  signal.setAttribute('aria-hidden', 'true');
  signal.innerHTML = '<span></span><span></span><span></span><span></span>';
  hero.append(signal);

  const ctx = canvas.getContext('2d', { alpha: true });
  if (!ctx) return;

  let width = 0;
  let height = 0;
  let dpr = 1;
  let streams = [];
  let stars = [];
  let last = 0;
  let running = true;

  const isMobile = () => window.innerWidth < 720;

  const buildScene = () => {
    const rect = hero.getBoundingClientRect();
    width = Math.max(1, Math.round(rect.width));
    height = Math.max(1, Math.round(rect.height));
    dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    canvas.width = Math.round(width * dpr);
    canvas.height = Math.round(height * dpr);
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    const columns = isMobile() ? 18 : Math.min(54, Math.max(28, Math.floor(width / 28)));
    streams = Array.from({ length: columns }, (_, index) => ({
      x: (index + Math.random() * .8) * (width / columns),
      y: -Math.random() * height,
      speed: .32 + Math.random() * .58,
      length: 55 + Math.random() * 150,
      alpha: .11 + Math.random() * .25,
      magenta: Math.random() < .12,
      pulse: Math.random() * Math.PI * 2
    }));

    const count = isMobile() ? 24 : 52;
    stars = Array.from({ length: count }, () => ({
      x: Math.random() * width,
      y: Math.random() * height * .8,
      r: .5 + Math.random() * 1.35,
      a: .08 + Math.random() * .32,
      drift: (Math.random() - .5) * .08
    }));
  };

  const drawPerspectiveGrid = (time) => {
    const horizon = height * .69;
    const bottom = height + 16;
    const cx = width * .62;
    const amplitude = 8 + Math.sin(time * .00055) * 3;

    ctx.save();
    ctx.globalCompositeOperation = 'screen';
    ctx.lineWidth = 1;

    for (let i = -10; i <= 10; i++) {
      const spread = i * width * .052;
      const gradient = ctx.createLinearGradient(cx, horizon, cx + spread, bottom);
      gradient.addColorStop(0, 'rgba(30,125,255,0)');
      gradient.addColorStop(.22, 'rgba(30,125,255,.08)');
      gradient.addColorStop(1, 'rgba(28,145,255,.28)');
      ctx.strokeStyle = gradient;
      ctx.beginPath();
      ctx.moveTo(cx + spread * .04, horizon);
      ctx.lineTo(cx + spread, bottom);
      ctx.stroke();
    }

    const rows = isMobile() ? 8 : 12;
    for (let r = 0; r < rows; r++) {
      const p = r / (rows - 1);
      const eased = p * p;
      const y = horizon + eased * (bottom - horizon);
      const wave = Math.sin(time * .0012 + r * .72) * amplitude * eased;
      ctx.strokeStyle = `rgba(35,145,255,${.05 + p * .2})`;
      ctx.beginPath();
      for (let x = 0; x <= width; x += 18) {
        const yy = y + Math.sin(x * .018 + time * .0007) * (2 + 5 * eased) + wave;
        if (x === 0) ctx.moveTo(x, yy); else ctx.lineTo(x, yy);
      }
      ctx.stroke();
    }
    ctx.restore();
  };

  const draw = (time) => {
    if (!running) return;
    if (time - last < 32) {
      requestAnimationFrame(draw);
      return;
    }
    last = time;
    ctx.clearRect(0, 0, width, height);

    const fade = ctx.createLinearGradient(0, 0, 0, height);
    fade.addColorStop(0, 'rgba(3,10,30,.18)');
    fade.addColorStop(.58, 'rgba(3,12,34,.03)');
    fade.addColorStop(1, 'rgba(3,12,34,0)');
    ctx.fillStyle = fade;
    ctx.fillRect(0, 0, width, height);

    ctx.save();
    ctx.globalCompositeOperation = 'screen';
    for (const s of streams) {
      s.y += s.speed * (isMobile() ? 1.15 : 1);
      s.pulse += .018;
      if (s.y - s.length > height * .72) {
        s.y = -Math.random() * height * .5;
        s.x = Math.random() * width;
      }
      const g = ctx.createLinearGradient(s.x, s.y - s.length, s.x, s.y);
      if (s.magenta) {
        g.addColorStop(0, 'rgba(255,45,190,0)');
        g.addColorStop(.75, `rgba(255,45,190,${s.alpha * .35})`);
        g.addColorStop(1, `rgba(255,100,220,${s.alpha})`);
      } else {
        g.addColorStop(0, 'rgba(0,130,255,0)');
        g.addColorStop(.72, `rgba(0,130,255,${s.alpha * .32})`);
        g.addColorStop(1, `rgba(70,185,255,${s.alpha})`);
      }
      ctx.strokeStyle = g;
      ctx.lineWidth = .7 + Math.sin(s.pulse) * .2;
      ctx.beginPath();
      ctx.moveTo(s.x, s.y - s.length);
      ctx.lineTo(s.x, s.y);
      ctx.stroke();
      if (Math.random() > .86) {
        ctx.fillStyle = s.magenta ? 'rgba(255,90,220,.62)' : 'rgba(110,210,255,.72)';
        ctx.fillRect(s.x - .8, s.y - 1, 1.6, 2.8);
      }
    }

    for (const p of stars) {
      p.x += p.drift;
      if (p.x < 0) p.x = width;
      if (p.x > width) p.x = 0;
      const flicker = .55 + Math.sin(time * .0015 + p.y) * .45;
      ctx.fillStyle = `rgba(80,178,255,${p.a * flicker})`;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.restore();

    drawPerspectiveGrid(time);
    requestAnimationFrame(draw);
  };

  const resizeObserver = new ResizeObserver(buildScene);
  resizeObserver.observe(hero);
  buildScene();
  requestAnimationFrame(draw);

  document.addEventListener('visibilitychange', () => {
    running = !document.hidden;
    if (running) {
      last = 0;
      requestAnimationFrame(draw);
    }
  });
})();
