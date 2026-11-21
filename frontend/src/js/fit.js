const page = document.body.firstElementChild;
//οι εικονες υπολογιζονται με συναρτηση καθε φορα που ο router αλλαζει σελιδα
function collectImages() {
  return Array.from(page.querySelectorAll('img')).map(function (img) {
    const center = img.offsetLeft + img.offsetWidth / 2;
    return { img: img, side: center < 600 ? -1 : center > 1300 ? 1 : 0 };
  });
}
let images = collectImages();

function fit() {
  if (!window.innerWidth || !window.innerHeight) return;
  const scale = Math.min(window.innerWidth / 1920, window.innerHeight / 1080);
  const x = (window.innerWidth - 1920 * scale) / 2;
  const y = (window.innerHeight - 1080 * scale) / 2;
  const extra = x / scale;
  page.style.position = 'absolute';
  page.style.left = '0';
  page.style.top = '0';
  page.style.overflow = 'visible';
  page.style.clipPath = 'inset(0 -' + extra + 'px)';
  page.style.transformOrigin = 'top left';
  page.style.transform = 'translate(' + x + 'px, ' + y + 'px) scale(' + scale + ')';
  images.forEach(function (item) {
    item.img.style.transform = 'translateX(' + item.side * extra + 'px)';
  });
}

document.documentElement.style.overflow = 'hidden';
document.body.style.margin = '0';
document.documentElement.style.background = 'linear-gradient(to bottom, #cc7d00, #fff098) no-repeat';
document.documentElement.style.height = '100%';
//ο router καλει συναρτηση μετα απο καθε αλλαγη σελιδας
window.fitSetup = function () {
  const frame = page.querySelector(':scope > div');
  if (frame) frame.style.overflow = 'visible';
  images = collectImages();
  fit();
};
window.addEventListener('resize', fit);
window.addEventListener('load', fit);
fit();
