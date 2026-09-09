(() => {
  'use strict';
  const root = document.getElementById('iww-history');
  if (!root) return;
  const cities = JSON.parse(document.getElementById('iww-map-data').textContent);
  const cityIds = new Set(cities.map(city => city.id));
  const events = [...root.querySelectorAll('.iww-event')];
  const form = root.querySelector('.iww-filters');
  const cityControl = root.querySelector('#iww-city');
  const kind = root.querySelector('#iww-kind');
  const period = root.querySelector('#iww-period');
  const search = root.querySelector('#iww-search');
  const expand = root.querySelector('[data-expand]');
  const markers = new Map();
  let map;
  let mappedCity;
  form.hidden = false;
  expand.hidden = false;

  function readLocation() {
    const params = new URL(window.location.href).searchParams;
    cityControl.value = cityIds.has(params.get('city')) ? params.get('city') : 'all';
    kind.value = ['organizing', 'strike', 'speech', 'repression'].includes(params.get('theme')) ? params.get('theme') : 'all';
    period.value = ['early', 'growth', 'war'].includes(params.get('period')) ? params.get('period') : 'all';
    search.value = (params.get('q') || '').slice(0, 160);
  }
  function saveLocation(replace = false) {
    const url = new URL(window.location.href);
    for (const [key, value] of Object.entries({city:cityControl.value, theme:kind.value, period:period.value, q:search.value.trim()})) {
      if (value && value !== 'all') url.searchParams.set(key, value);
      else url.searchParams.delete(key);
    }
    if (url.href !== window.location.href) history[replace ? 'replaceState' : 'pushState']({}, '', url);
  }
  function updateExpandLabel() {
    const visible = events.filter(event => !event.hidden);
    expand.hidden = visible.length === 0;
    expand.textContent = visible.length && visible.every(event => event.open) ? 'Collapse visible events' : 'Expand visible events';
  }
  function render(refit = false) {
    const terms = search.value.trim().toLocaleLowerCase().split(/\s+/).filter(Boolean);
    let count = 0;
    events.forEach(event => {
      const year = Number(event.dataset.year);
      const inPeriod = period.value === 'all' || (period.value === 'early' && year <= 1911) || (period.value === 'growth' && year >= 1912 && year <= 1916) || (period.value === 'war' && year >= 1917);
      const match = (cityControl.value === 'all' || event.dataset.city === cityControl.value) && (kind.value === 'all' || event.dataset.kind === kind.value) && inPeriod && terms.every(term => event.textContent.toLocaleLowerCase().includes(term));
      event.hidden = !match;
      if (match) count++;
    });
    const selected = cities.find(city => city.id === cityControl.value);
    root.querySelector('.iww-results').textContent = `${count} of ${events.length} milestones${selected ? ' · ' + selected.name : ''}`;
    root.querySelector('.iww-empty').hidden = count !== 0;
    markers.forEach((marker, id) => marker.getElement()?.classList.toggle('is-selected', id === cityControl.value));
    if (map && (refit || mappedCity !== cityControl.value)) {
      if (selected) map.setView([selected.lat, selected.lng], 9, {animate:false});
      else map.fitBounds(cities.map(city => [city.lat, city.lng]), {padding:[30, 30], animate:false});
      mappedCity = cityControl.value;
    }
    updateExpandLabel();
  }
  function reset() { cityControl.value = 'all'; kind.value = 'all'; period.value = 'all'; search.value = ''; saveLocation(); render(true); }
  form.addEventListener('submit', event => event.preventDefault());
  form.addEventListener('reset', event => { event.preventDefault(); reset(); });
  root.querySelector('[data-reset]').addEventListener('click', reset);
  [cityControl, kind, period].forEach(control => control.addEventListener('change', () => { saveLocation(); render(); }));
  search.addEventListener('input', () => { saveLocation(true); render(); });
  window.addEventListener('popstate', () => { readLocation(); render(); });
  expand.addEventListener('click', () => {
    const visible = events.filter(event => !event.hidden);
    const open = !visible.every(event => event.open);
    visible.forEach(event => { event.open = open; });
    updateExpandLabel();
  });
  events.forEach(event => event.addEventListener('toggle', updateExpandLabel));
  readLocation();
  if (window.L) {
    root.querySelector('.iww-map-wrap').hidden = false;
    map = L.map('iww-map', {scrollWheelZoom:false});
    map.on('resize', () => render(true));
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:18, attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}).addTo(map);
    cities.forEach(city => {
      const label = `${city.name}, ${city.state}`;
      const marker = L.marker([city.lat, city.lng], {icon:L.divIcon({className:'iww-map-dot', iconSize:[14,14], iconAnchor:[7,7]}), title:label, keyboard:true}).addTo(map);
      marker.bindTooltip(label, {direction:'top'});
      marker.on('click', () => { cityControl.value = city.id; saveLocation(); render(true); events.filter(event => !event.hidden).forEach(event => { event.open = true; }); });
      markers.set(city.id, marker);
    });
  }
  render();
  const fragment = document.getElementById(window.location.hash.slice(1));
  if (fragment && events.includes(fragment) && !fragment.hidden) fragment.open = true;

  const load = root.querySelector('#iww-load-locals');
  const container = root.querySelector('#iww-locals-frame');
  load.hidden = false;
  load.addEventListener('click', () => {
    if (container.querySelector('iframe')) return;
    const frame = document.createElement('iframe');
    frame.title = 'University of Washington: historical IWW local unions, branches and councils';
    frame.src = container.dataset.mapUrl;
    container.append(frame);
    container.hidden = false;
    load.hidden = true;
  });
})();
