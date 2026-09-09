(() => {
  'use strict';
  const root = document.getElementById('bpp-history');
  if (!root) return;
  const cities = JSON.parse(document.getElementById('bpp-map-data').textContent);
  const cityIds = new Set(cities.map(city => city.id));
  const buttons = [...root.querySelectorAll('[data-city]')].filter(el => el.tagName === 'BUTTON');
  const stories = [...root.querySelectorAll('[data-story]')];
  const events = [...root.querySelectorAll('.bpp-event')];
  const form = root.querySelector('.bpp-filters');
  const search = root.querySelector('#bpp-search');
  const kind = root.querySelector('#bpp-kind');
  const period = root.querySelector('#bpp-period');
  const intro = root.querySelector('.bpp-atlas-intro');
  let selectedCity = 'all';
  let map;
  const markers = new Map();
  root.classList.add('bpp-enhanced');
  form.hidden = false;
  root.querySelector('.bpp-city-buttons').hidden = false;

  function readLocation() {
    const params = new URL(window.location.href).searchParams;
    selectedCity = cityIds.has(params.get('city')) ? params.get('city') : 'all';
    kind.value = ['organizing', 'community', 'repression'].includes(params.get('theme')) ? params.get('theme') : 'all';
    period.value = ['early', 'middle', 'later'].includes(params.get('period')) ? params.get('period') : 'all';
    search.value = (params.get('q') || '').slice(0, 160);
  }
  function saveLocation(replace = false) {
    const url = new URL(window.location.href);
    for (const [key, value] of Object.entries({ city: selectedCity, theme: kind.value, period: period.value, q: search.value.trim() })) {
      if (value && value !== 'all') url.searchParams.set(key, value);
      else url.searchParams.delete(key);
    }
    if (url.href !== window.location.href) history[replace ? 'replaceState' : 'pushState']({}, '', url);
  }
  function render() {
    buttons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.city === selectedCity)));
    stories.forEach(story => { story.hidden = story.dataset.story !== selectedCity; });
    intro.hidden = selectedCity !== 'all';
    const terms = search.value.trim().toLocaleLowerCase().split(/\s+/).filter(Boolean);
    let count = 0;
    events.forEach(event => {
      const year = Number(event.dataset.year);
      const inPeriod = period.value === 'all' || (period.value === 'early' && year <= 1968) || (period.value === 'middle' && year >= 1969 && year <= 1971) || (period.value === 'later' && year >= 1972);
      const match = (selectedCity === 'all' || event.dataset.city === selectedCity) && (kind.value === 'all' || event.dataset.kind === kind.value) && inPeriod && terms.every(term => event.textContent.toLocaleLowerCase().includes(term));
      event.hidden = !match;
      if (match) count++;
    });
    const cityName = cities.find(city => city.id === selectedCity)?.name;
    root.querySelector('.bpp-results').textContent = `${count} of ${events.length} milestones${cityName ? ' · ' + cityName : ''}`;
    root.querySelector('.bpp-empty').hidden = count !== 0;
    markers.forEach((marker, id) => marker.getElement()?.classList.toggle('is-selected', id === selectedCity));
    if (map) {
      const city = cities.find(item => item.id === selectedCity);
      if (city) map.setView([city.lat, city.lng], 6, { animate: false });
      else map.fitBounds(cities.map(item => [item.lat, item.lng]), { padding: [30, 30], animate: false });
    }
  }
  function chooseCity(id) { selectedCity = id; saveLocation(); render(); }
  buttons.forEach(button => button.addEventListener('click', () => chooseCity(button.dataset.city)));
  form.addEventListener('submit', event => event.preventDefault());
  search.addEventListener('input', () => { saveLocation(true); render(); });
  [kind, period].forEach(control => control.addEventListener('change', () => { saveLocation(); render(); }));
  function reset() { selectedCity = 'all'; search.value = ''; kind.value = 'all'; period.value = 'all'; saveLocation(); render(); }
  form.addEventListener('reset', event => { event.preventDefault(); reset(); });
  root.querySelector('[data-reset]').addEventListener('click', reset);
  window.addEventListener('popstate', () => { readLocation(); render(); });
  readLocation();
  if (window.L) {
    root.querySelector('.bpp-map-wrap').hidden = false;
    map = L.map('bpp-map', { scrollWheelZoom: false });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 18
    }).addTo(map);
    cities.forEach(city => {
      const marker = L.marker([city.lat, city.lng], { icon: L.divIcon({ className: 'bpp-map-dot', iconSize: [14, 14], iconAnchor: [7, 7] }), title: city.name, alt: `Select ${city.name}`, keyboard: true }).addTo(map);
      marker.bindTooltip(city.name, { direction: 'top' });
      marker.on('click', () => chooseCity(city.id));
      markers.set(city.id, marker);
    });
  } else {
    root.querySelector('.bpp-atlas').style.display = 'block';
  }
  render();
})();
