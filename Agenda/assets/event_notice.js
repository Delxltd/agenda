/* Drop-in helper: call after calendar.render() */
function attachEventCountNotice(calendar){
  const bar = document.createElement('div');
  bar.style.cssText = 'position:absolute;top:10px;right:12px;background:#111827;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;opacity:.0;transition:opacity .2s;z-index:5';
  bar.id = 'evtCountBar';
  bar.textContent = '';
  calendar.el.parentElement.style.position='relative';
  calendar.el.parentElement.appendChild(bar);

  calendar.setOption('eventSourceSuccess', function(content, xhr){
    try{
      const count = (xhr && xhr.getResponseHeader) ? (xhr.getResponseHeader('X-Event-Count')||content.length) : content.length;
      if (Number(count) === 0){
        bar.textContent = 'Geen afspraken in dit bereik';
        bar.style.opacity = '.9';
      } else {
        bar.textContent = count+' afspraken geladen';
        bar.style.opacity = '.5';
        setTimeout(()=>{bar.style.opacity='0';}, 1600);
      }
    }catch(e){}
    return content;
  });
}
