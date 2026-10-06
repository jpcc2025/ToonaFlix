// Replaces HomeFrame's shelf engine, search, category tabs and popups
(() => {
  const C = window.TOONAFLIX;
  const items = C.items;
  const $ = (s, r = document) => r.querySelector(s);
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const shelves = $('#shelves'), search = $('#search'), tabs = [...document.querySelectorAll('#tabs button')];
  let cat = C.category || 'ALL';

  const shuffle = (a) => { a = a.slice(); for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };

  function card(it) {
    const img = C.thumbs && it.image
      ? `<img src="${esc(it.image)}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.replaceWith(Object.assign(document.createElement('span'),{textContent:'No Preview'}))">`
      : `<span>${C.thumbs ? 'No Image' : 'Thumbnails off'}</span>`;
    return `<button class="card" data-id="${it.id}" style="--base:${it.base}">
      <div class="thumb">${img}</div>
      <div class="meta"><b>${esc(it.title)}</b><em style="background:${it.badgeColor}">${esc(it.badge)}</em></div></button>`;
  }
  const row = (title, list) => `<section class="shelf"><h2 class="px">${esc(title)}</h2><div class="grid">${list.map(card).join('')}</div></section>`;

  function render() {
    const q = search.value.trim().toLowerCase();
    let html;
    if (q) {
      const found = items.filter((i) => i.title.toLowerCase().includes(q) || i.category.toLowerCase().includes(q));
      html = found.length
        ? row(`Search Results for "${search.value.trim()}" (${found.length})`, found)
        : `<div class="empty"><div class="big">?</div><h2>No matches found for "${esc(search.value.trim())}"</h2>
           <p>Check your spelling or explore the categories above.</p><button class="btn" id="emptyClear">Clear Search</button></div>`;
    } else if (cat !== 'ALL') {
      const f = items.filter((i) => i.category.toLowerCase() === cat.toLowerCase());
      html = row(`${cat} Collection (${f.length})`, f);
    } else {
      html = row('Trending Now', shuffle(items).slice(0, 5)) + row('Popular & Recommended', shuffle(items).slice(0, 5)) + row('Binge-Worthy Classics', shuffle(items).slice(0, 5));
    }
    shelves.innerHTML = html;
    tabs.forEach((b) => b.classList.toggle('on', b.dataset.cat.toLowerCase() === cat.toLowerCase()));
  }

  tabs.forEach((b) => b.addEventListener('click', () => { cat = b.dataset.cat; render(); }));
  search.addEventListener('input', render);
  $('#clearSearch').addEventListener('click', () => { search.value = ''; render(); search.focus(); });
  $('#brandLink').addEventListener('click', (e) => { e.preventDefault(); search.value = ''; cat = 'ALL'; render(); });
  shelves.addEventListener('click', (e) => {
    if (e.target.id === 'emptyClear') { search.value = ''; render(); return; }
    const c = e.target.closest('.card');
    if (c) openModal(items.find((i) => i.id === +c.dataset.id));
  });

  // ---- Popups ----
  const modal = $('#modal');
  const close = () => { modal.hidden = true; modal.innerHTML = ''; document.body.classList.remove('lock'); };
  modal.addEventListener('click', (e) => { if (e.target === modal || e.target.closest('[data-close]')) close(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) close(); });

  function openLink(link) {
    if (!link || !link.trim()) { alert('No link has been added for this title yet.'); return; }
    window.open(link, '_blank', 'noopener');
  }
  const preview = (it) => `<div class="video" style="--base:${it.base}">
      <button class="play" data-link="${esc(it.trailer || '')}" aria-label="Play trailer">&#9654;</button><b>${esc(it.title)}</b></div>`;

  function openModal(it) {
    fetch('track.php', { method: 'POST', body: new URLSearchParams({ csrf: C.csrf, category: it.category, title: it.title }), keepalive: true });
    const isMovie = it.category === 'Movie', isManga = it.category === 'Manga';
    if (isMovie) {
      const nf = it.stream || 'https://www.netflix.com/browse';
      modal.innerHTML = `<div class="modal movie" role="dialog" aria-modal="true" aria-label="${esc(it.title)}">
        ${preview(it)}
        <div class="info"><div><h2 class="px">${esc(it.title)}</h2><h3>About...</h3><p>${esc(it.desc)}</p><small>${esc(it.badge)}</small></div>
        <div class="watch"><button class="btn" data-link="${esc(nf)}">Watch on Netflix</button>
          <button class="btn" data-link="https://www.apps.disneyplus.com/ph/onboarding?ref=%2Fbrowse%2Fsearch">Watch on Disney+</button>
          <button class="btn" data-link="https://www.hbo.com/movies/a-z">Watch on HBO Max</button></div></div>
        <button class="btn dark" data-close>Close</button></div>`;
    } else {
      const noun = isManga ? 'Chapter' : 'Episode';
      const eps = [1, 2, 3, 4, 5, 6].map((n) => `<div class="ep"><div><b>${noun} ${n}</b><small>${isManga ? 'Read' : 'Watch'} ${noun} ${n}</small></div>
        <button class="btn sm" style="background:${it.badgeColor}" data-ep="${n}" aria-label="Play ${noun} ${n}">&#9654;</button></div>`).join('');
      modal.innerHTML = `<div class="modal series" role="dialog" aria-modal="true" aria-label="${esc(it.title)}">
        <header><span class="tag" style="background:${it.badgeColor}">${it.category === 'Manhwa' ? 'WEBTOON' : 'ANIME &amp; MANGA'}</span><h2 class="px">${esc(it.title)}</h2></header>
        <div class="body">${preview(it)}<div class="eps"><h3 class="px">${noun}s</h3>${eps}</div></div>
        <footer><span>${isManga ? 'Read Now' : 'Watch Now'}</span><button class="btn dark" data-close>Close</button></footer></div>`;
    }
    modal.hidden = false;
    document.body.classList.add('lock');
    modal.querySelector('[data-close]').focus();
    modal.onclick = (e) => {
      if (e.target === modal || e.target.closest('[data-close]')) return close();
      const l = e.target.closest('[data-link]');
      if (l) return openLink(l.dataset.link);
      const ep = e.target.closest('[data-ep]');
      if (ep) { it.stream ? openLink(it.stream) : alert(`Playing ${it.title} - ${noun_(it)} ${ep.dataset.ep}`); }
    };
  }
  const noun_ = (it) => (it.category === 'Manga' ? 'Chapter' : 'Episode');

  render();
})();
