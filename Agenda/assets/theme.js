// Adjust CSS var for header height (fallback if header resizes)
(function(){
  function setHeaderVar(){
    var h = document.getElementById('appHeader');
    if (!h) return;
    document.documentElement.style.setProperty('--header-h', h.offsetHeight + 'px');
  }
  window.addEventListener('load', setHeaderVar);
  window.addEventListener('resize', setHeaderVar);
})();
