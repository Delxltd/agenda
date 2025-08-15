/* /assets/agenda-calendar.v4.js — all logic (no inline). */
(function(){
  function $(sel, root){ return (root||document).querySelector(sel); }
  function $all(sel, root){ return Array.from((root||document).querySelectorAll(sel)); }
  function toast(msg){
    const b=$("#bubble"); if(!b || !msg) return; b.textContent=msg; b.style.opacity=".95"; setTimeout(()=>b.style.opacity="0",1800);
  }
  function isoLocal(datetime){
    const d = new Date(datetime);
    const pad=n=>String(n).padStart(2,'0');
    return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes());
  }
  function clamp(v,min,max){ return Math.max(min, Math.min(max, v)); }

  // Hard overlay cleanup (in case previous page left modals open)
  document.addEventListener('DOMContentLoaded', ()=>{
    $all('.ins-lanes').forEach(n=>n.remove());
    $all('.modal.show').forEach(m=>m.classList.remove('show'));
    document.body.style.overflow='';
  });

  // Robust loader wait
  function waitForFC(i, done){
    if (window.FullCalendar && window.FullCalendar.Calendar) return done();
    if (i>100){ console.error('FullCalendar kon niet laden'); showLoaderFailure(); return; }
    setTimeout(()=>waitForFC(i+1, done), 100);
  }
  function showLoaderFailure(){
    const el = $("#calendar");
    if(!el) return;
    const div=document.createElement('div');
    div.style.padding="16px";
    div.innerHTML = [
      '<div style="padding:12px;border:1px solid #fca5a5;color:#7f1d1d;background:#fee2e2;border-radius:10px">',
      '<strong>Kalender kon niet laden.</strong><br>',
      'Mogelijke oorzaken: Content Security Policy blokkeert scripts, of je netwerk blokkeert externe CDNs.',
      '<br>Oplossing: plaats lokaal <code>/vendor/fullcalendar/index.global.min.js</code> en <code>/vendor/fullcalendar/main.min.css</code> en laat <code>script-src \'self\'</code> toe.',
      '</div>'
    ].join('');
    el.appendChild(div);
  }

  document.addEventListener('DOMContentLoaded', function(){
    const cfgNode = $("#cal-config");
    const cfg = {
      csrf: (cfgNode?.dataset.csrf)||'',
      currentUserId: parseInt(cfgNode?.dataset.currentUserId||'0',10)||0,
      currentUserRole: (cfgNode?.dataset.currentUserRole)||'',
      initialSelected: new Set(JSON.parse(cfgNode?.dataset.initialSelected||'[]')),
      typesEnabled: new Set(JSON.parse(cfgNode?.dataset.types||'[]')),
      flash: (cfgNode?.dataset.flash? JSON.parse(cfgNode.dataset.flash): null)
    };

    if (cfg.flash && cfg.flash.msg) toast(cfg.flash.msg);

    waitForFC(0, init);

    function init(){
      const wrap   = $("#wrap");
      const el     = $("#calendar");
      const tt     = $("#tt");

      const initialView = localStorage.getItem('cal.view') || (window.innerWidth<900?'timeGridDay':'timeGridWeek');
      const showWeekends = localStorage.getItem('cal.weekends') !== '0';
      const compact = localStorage.getItem('cal.compact') === '1'; if (compact) el.classList.add('compact');
      const scroll = new Date(); scroll.setHours(Math.max(7, scroll.getHours()-1),0,0,0);

      const cal = new FullCalendar.Calendar(el, {
        initialView: initialView,
        firstDay:1,
        locale:'nl',
        height:'auto',
        contentHeight:'auto',
        weekends: showWeekends,
        nowIndicator:true,
        selectable:true,
        editable:true,
        eventOverlap:true,
        slotEventOverlap:false,
        dayMaxEvents:true,
        expandRows:true,
        weekNumbers:false,
        eventDisplay:'block',
        slotMinTime:'07:00:00',
        slotMaxTime:'21:00:00',
        scrollTime: scroll.toTimeString().slice(0,8),
        slotDuration:'00:30:00',
        slotLabelInterval:'01:00',
        slotLabelFormat:{hour:'2-digit', minute:'2-digit', hour12:false},
        stickyHeaderDates:true,
        eventOrder: (a,b)=>{
          const ai=(a.extendedProps.instructorName||'').toLowerCase();
          const bi=(b.extendedProps.instructorName||'').toLowerCase();
          if (ai<bi) return -1; if (ai>bi) return 1;
          return a.start - b.start;
        },

        events: function(fetchInfo, success, failure){
          const params = new URLSearchParams();
          params.set('start', fetchInfo.startStr);
          params.set('end',   fetchInfo.endStr);
          if (cfg.initialSelected.size) params.set('instructor_ids', Array.from(cfg.initialSelected).join(','));
          const enabledTypes = Array.from(cfg.typesEnabled).join(',');
          if (enabledTypes) params.set('types', enabledTypes);

          fetch('/api/events.php?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(async (r) => {
              let data;
              try { data = await r.json(); }
              catch { throw new Error('Ongeldige JSON (mogelijk PHP-warning in output)'); }
              if (!r.ok) {
                const msg = (data && data.error) ? data.error : ('HTTP ' + r.status + ' ' + r.statusText);
                throw new Error(msg);
              }
              if (!Array.isArray(data)) throw new Error('Ongeldig antwoord (geen array)');
              return data.map(e=>{
                e.instructorId   = e.instructor_id   ?? e.instructorId   ?? null;
                e.instructorName = e.instructor_name ?? e.instructorName ?? null;
                e.studentName    = e.student_name    ?? e.studentName    ?? null;
                e.typeLabel      = e.typeLabel || e.type || '';
                return e;
              });
            })
            .then(list => success(list))
            .catch(err => { console.error('Events feed error:', err); failure(err); alert('Events laden mislukt: ' + err.message); });
        },

        datesSet: (arg)=> {
          const title = $("#viewTitle"); if(title) title.textContent = arg.view.title;
          const p = new URLSearchParams();
          p.set('from', arg.startStr.slice(0,10)); p.set('to', arg.endStr.slice(0,10));
          if (cfg.initialSelected.size) p.set('instructor_id', Array.from(cfg.initialSelected)[0]);
          const ics = $("#icsLink"); if (ics) ics.href = '/calendar_feed.php?'+p.toString();
          queueLaneBuild();
          setTimeout(()=>cal.updateSize(), 0);
        },

        eventAllow: (dropInfo, ev)=>
          cfg.currentUserRole==='admin'
            ? ev.extendedProps.status!=='cancelled'
            : (ev.extendedProps.instructorId===cfg.currentUserId && ev.extendedProps.status!=='cancelled'),

        eventDrop: async (info)=>{ const ok = await api({action:'move',id:info.event.id,start:info.event.start.toISOString(),end:info.event.end.toISOString()}); if(!ok) info.revert(); else queueLaneBuild(); },
        eventResize: async (info)=>{ const ok = await api({action:'resize',id:info.event.id,start:info.event.start.toISOString(),end:info.event.end.toISOString()}); if(!ok) info.revert(); else queueLaneBuild(); },

        select: (info)=>openCreateModal(info.start, info.end),
        dateClick: (info)=>{
          const start = new Date(info.date);
          const defaultHour = +(localStorage.getItem('cal.defaultHour') || '9');
          start.setHours(defaultHour, 0, 0, 0);
          const end   = new Date(start.getTime() + 90*60000);
          openCreateModal(start, end);
        },

        eventClick: (info)=>{ info.jsEvent.preventDefault(); openEvtActionModal(info.event); },

        eventMouseEnter: function(info){ renderTooltipFor(info.event, info.el); },
        eventMouseLeave: function(){ const tt=$("#tt"); if(tt) tt.style.display='none'; },
        eventDragStart: ()=>{ const tt=$("#tt"); if(tt) tt.style.display='none'; },
        eventResizeStart: ()=>{ const tt=$("#tt"); if(tt) tt.style.display='none'; },

        eventContent: function(arg){
          const e = arg.event, p = e.extendedProps || {};
          const isMonth = arg.view && arg.view.type==='dayGridMonth';
          const wrap = document.createElement('div'); wrap.className='evt';
          const t = document.createElement('div'); t.className='evt-title';
          const idx = (p.instructorId ? ((p.instructorId % 12) || 12) : 1);
          const dot = document.createElement('span'); dot.className='dot ins-'+idx; t.appendChild(dot);
          const title = document.createElement('span'); title.textContent = (e.title||'').replace(/\s+—\s+/g,' — '); t.appendChild(title); wrap.appendChild(t);
          if (!isMonth){
            const m = document.createElement('div'); m.className='evt-meta';
            const badge = document.createElement('span'); badge.className='badge'; badge.textContent = p.instructorName || '—'; m.appendChild(badge);
            const pill = document.createElement('span'); pill.className='type-pill'; pill.textContent = p.typeLabel || p.type || ''; m.appendChild(pill);
            wrap.appendChild(m);
          } else {
            const shortI = p.instructorName ? (p.instructorName.split(' ')[0]) : '';
            if (arg.timeText) { title.textContent = arg.timeText + ' · ' + (shortI ? (shortI + ' · ') : '') + title.textContent; }
            else if (shortI) { title.textContent = shortI + ' · ' + title.textContent; }
          }
          return { domNodes:[wrap] };
        },

        eventDidMount: function(info){
          const el = info.el; const p = info.event.extendedProps||{};
          const idx = (p.instructorId ? ((p.instructorId % 12) || 12) : 1);
          el.classList.add('ins-'+idx);
          if (p.status === 'cancelled') el.classList.add('is-cancelled');
          if (p.instructorId) el.setAttribute('data-ins', String(p.instructorId));
          queueLaneBuild();
        }
      });
      cal.render();

      // Tooltip helpers
      function renderTooltipFor(event, targetEl){
        const tt=$("#tt"); if(!tt) return;
        const p = event.extendedProps || {};
        const dt = (d)=>{ try{ return new Date(d).toLocaleString('nl-NL', { dateStyle:'medium', timeStyle:'short' }); }catch(e){ return d; } }
        tt.innerHTML = '<div class="t">'+ (event.title||'Afspraak') +'</div>' +
                       '<div class="row">Instructeur: <strong>'+ (p.instructorName||'—') +'</strong></div>' +
                       (p.studentName? '<div class="row">Leerling: <strong>'+ p.studentName +'</strong></div>':'' ) +
                       '<div class="row">Van: <strong>'+ dt(event.start) +'</strong></div>' +
                       '<div class="row">Tot: <strong>'+ dt(event.end) +'</strong></div>' +
                       (p.notes? '<div class="row">Notities: '+ p.notes +'</div>' : '');
        positionTTByRect(targetEl.getBoundingClientRect());
        tt.style.display='block';
      }
      function positionTTByRect(r){
        const tt=$("#tt"); if(!tt) return;
        const padding=10, W=window.innerWidth, H=window.innerHeight;
        const tw = tt.offsetWidth || 260, th = tt.offsetHeight || 120;
        let top = r.top + window.scrollY - th - 8;
        if (top < window.scrollY + padding) top = r.bottom + window.scrollY + 8;
        top = clamp(top, window.scrollY + padding, window.scrollY + H - th - padding);
        let left = r.left + window.scrollX + 8;
        if (left + tw > window.scrollX + W - padding) left = window.scrollX + W - tw - padding;
        tt.style.top  = top + 'px';
        tt.style.left = left + 'px';
      }
      window.addEventListener('scroll', ()=>{ const tt=$("#tt"); if (tt && tt.style.display==='block') positionTTByRect(tt.getBoundingClientRect()); }, true);
      window.addEventListener('resize', ()=>{ const tt=$("#tt"); if(tt) tt.style.display='none'; queueLaneBuild(); });

      // API helper
      async function api(payload){
        try{
          const r=await fetch('/api/update_event.php',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-Token':cfg.csrf},
            body:JSON.stringify(payload)
          });
          const j=await r.json().catch(()=>null);
          if(!r.ok || !j || j.ok!==true){ alert((j && j.error) || 'Actie mislukt'); return false; }
          return true;
        }catch(e){ console.error(e); alert('Serverfout'); return false; }
      }

      // Toolbar
      function onId(id,cb){ const n=$(id.startsWith('#')?id:'#'+id); if(n) n.addEventListener('click',cb); }
      onId('btnPrev', ()=>{ cal.prev(); queueLaneBuild(); });
      onId('btnToday', ()=>{ cal.today(); queueLaneBuild(); });
      onId('btnNext', ()=>{ cal.next(); queueLaneBuild(); });
      $all('[data-view]').forEach(b=>{
        b.addEventListener('click',()=>{
          $all('[data-view]').forEach(x=>x.classList.remove('btn-primary'));
          b.classList.add('btn-primary');
          localStorage.setItem('cal.view', b.dataset.view);
          cal.changeView(b.dataset.view);
          queueLaneBuild();
        });
      });
      const w=$("#toggleWeekends"); if(w){ w.checked=(localStorage.getItem('cal.weekends')!=='0'); w.addEventListener('change',()=>{ localStorage.setItem('cal.weekends', w.checked?'1':'0'); cal.setOption('weekends', w.checked); queueLaneBuild(); }); }
      const c=$("#toggleCompact"); if(c){ c.checked=(localStorage.getItem('cal.compact')==='1'); c.addEventListener('change',()=>{ localStorage.setItem('cal.compact', c.checked?'1':'0'); $("#calendar").classList.toggle('compact', c.checked); }); }

      onId('btnPrint', ()=>window.print());
      onId('openCreate', ()=>openCreateModal());
      const jump=$("#jumpDate"); if(jump){ jump.addEventListener('change', ()=>{ if (jump.value) { cal.gotoDate(jump.value); queueLaneBuild(); } }); }

      // Fullscreen
      const btnFull=$("#btnFull");
      async function toggleFull() {
        const isFull = !!document.fullscreenElement;
        try{
          if (!isFull) {
            $("#wrap").classList.add('pro-full');
            if (document.documentElement.requestFullscreen) await document.documentElement.requestFullscreen();
          } else {
            if (document.exitFullscreen) await document.exitFullscreen();
            $("#wrap").classList.remove('pro-full');
          }
        }catch(e){ $("#wrap").classList.toggle('pro-full'); }
        setTimeout(()=>cal.updateSize(), 120);
      }
      if(btnFull) btnFull.addEventListener('click', toggleFull);
      document.addEventListener('fullscreenchange', ()=>{ setTimeout(()=>cal.updateSize(),100); });

      // Filters
      const q=$("#q"); if(q) q.addEventListener('input', applyClientFilter);
      const typeChips = $("#typeChips"); if(typeChips) typeChips.addEventListener('click',(e)=>{
        const chip=e.target.closest('.chip'); if(!chip) return; const t=chip.dataset.type;
        if (chip.classList.contains('active')) { chip.classList.remove('active'); cfg.typesEnabled.delete(t); } else { chip.classList.add('active'); cfg.typesEnabled.add(t); }
        cal.refetchEvents();
      });
      const insChips = $("#insChips"); if(insChips) insChips.addEventListener('click',(e)=>{
        const chip=e.target.closest('.chip'); if(!chip) return; const id=+chip.dataset.id;
        if (chip.classList.contains('active')) { chip.classList.remove('active'); cfg.initialSelected.delete(id); } else { chip.classList.add('active'); cfg.initialSelected.add(id); }
        cal.refetchEvents();
        queueLaneBuild();
      });
      function applyClientFilter(){
        const text=(q && q.value || '').toLowerCase().trim();
        cal.getEvents().forEach(ev=>{
          const hay=[ev.title||'', ev.extendedProps.studentName||'', ev.extendedProps.instructorName||''].join(' ').toLowerCase();
          const hide= !!text && !hay.includes(text);
          const cls=new Set(ev.classNames||[]); if(hide) cls.add('fc-hide'); else cls.delete('fc-hide'); ev.setProp('classNames', Array.from(cls));
        });
        queueLaneBuild();
      }

      // Keyboard
      document.addEventListener('keydown', (ev)=>{
        if (['INPUT','TEXTAREA','SELECT'].includes((ev.target||{}).tagName)) return;
        if (ev.key==='j' || ev.key==='J') { cal.next(); queueLaneBuild(); }
        if (ev.key==='k' || ev.key==='K') { cal.prev(); queueLaneBuild(); }
        if (ev.key==='t' || ev.key==='T') { cal.today(); queueLaneBuild(); }
        if (ev.key==='1') { cal.changeView('timeGridDay'); queueLaneBuild(); }
        if (ev.key==='2') { cal.changeView('timeGridWeek'); queueLaneBuild(); }
        if (ev.key==='3') { cal.changeView('dayGridMonth'); }
        if (ev.key==='n' || ev.key==='N') { openCreateModal(); }
      });

      // Create modal
      const createModal=$("#createModal");
      const s=$("#start"), e=$("#end"), dur=$("#duration");
      function updateEnd(){ if(!s || !s.value) return; const d=new Date(s.value); const mins=parseInt(dur && dur.value || '90',10)||90; const end=new Date(d.getTime()+mins*60000); if(e) e.value=isoLocal(end); }
      if(dur) dur.addEventListener('change', updateEnd); if(s) s.addEventListener('change', updateEnd);

      function openCreateModal(startDT, endDT){
        if(!createModal) return;
        let start = startDT ? new Date(startDT) : new Date();
        if (!startDT){ const mm = start.getMinutes(); start.setMinutes(mm - (mm%15), 0, 0); }
        let end   = endDT ? new Date(endDT) : new Date(start.getTime() + (parseInt(dur && dur.value || '90',10))*60000);
        if(s) s.value = isoLocal(start);
        if(e) e.value = isoLocal(end);

        createModal.classList.add('show'); createModal.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden';
        const insSel=$("#insSelect");
        if (insSel){
          const firstSel = Array.from(document.querySelectorAll('#insChips .chip.active')).map(c=>+c.dataset.id)[0];
          if (firstSel) insSel.value = String(firstSel);
        }
      }
      function closeCreateModal(){ if(!createModal) return; createModal.classList.remove('show'); createModal.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }
      const closeModalBtn=$("#closeModal"); if(closeModalBtn) closeModalBtn.addEventListener('click', closeCreateModal);
      const btnCancel=$("#btnCancel"); if(btnCancel) btnCancel.addEventListener('click', closeCreateModal);
      if(createModal) createModal.addEventListener('click', (e)=>{ if(e.target===createModal) closeCreateModal(); });

      // Event acties & wijzigen
      const evtModal = $("#evtModal");
      const evtTitleHead = $("#evtTitleHead");
      const evtMeta = $("#evtMeta");
      const evtCancelRestoreBtn = $("#evtCancelRestore");
      const evtDeleteBtn = $("#evtDelete");
      const evtCloseBtn = $("#evtClose");
      const evtDismissBtn = $("#evtDismiss");
      const evtSaveBtn = $("#evtSave");

      const editEvtTitle = $("#editEvtTitle");
      const editEvtStart = $("#editEvtStart");
      const editEvtEnd   = $("#editEvtEnd");
      const editEvtIns   = $("#editEvtInstructor");
      const quickPlus30  = $("#quickPlus30");
      const quickTomorrow= $("#quickTomorrow");
      const quickNextWeek= $("#quickNextWeek");

      let activeEvent = null;
      function fmt(dt){ try { return new Date(dt).toLocaleString('nl-NL',{dateStyle:'medium', timeStyle:'short'}); } catch(e){ return dt; } }

      function openEvtActionModal(event){
        if(!evtModal) return;
        activeEvent = event;
        const p = event.extendedProps||{};
        if(evtTitleHead) evtTitleHead.textContent = (event.title || 'Afspraak');
        if(evtMeta) evtMeta.innerHTML = [
          p.instructorName ? 'Instructeur: <strong>'+p.instructorName+'</strong>' : '',
          p.studentName    ? 'Leerling: <strong>'+p.studentName+'</strong>'   : '',
          'Van: <strong>'+fmt(event.start)+'</strong>',
          'Tot: <strong>'+fmt(event.end)+'</strong>'
        ].filter(Boolean).join(' • ');

        if(editEvtTitle) editEvtTitle.value = event.title || '';
        if(editEvtStart) editEvtStart.value = isoLocal(event.start);
        const fallbackEnd = new Date(event.start.getTime()+60*60000);
        if(editEvtEnd) editEvtEnd.value = isoLocal(event.end || fallbackEnd);
        if (p.instructorId && editEvtIns) editEvtIns.value = String(p.instructorId);

        if (evtCancelRestoreBtn){
          if (p.status === 'cancelled') {
            evtCancelRestoreBtn.textContent = 'Herstellen';
            evtCancelRestoreBtn.classList.remove('btn-warning');
            evtCancelRestoreBtn.classList.add('btn-primary');
          } else {
            evtCancelRestoreBtn.textContent = 'Annuleren';
            evtCancelRestoreBtn.classList.remove('btn-primary');
            evtCancelRestoreBtn.classList.add('btn-warning');
          }
        }

        evtModal.classList.add('show'); evtModal.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden';
      }
      function closeEvtActionModal(){ if(!evtModal) return; evtModal.classList.remove('show'); evtModal.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }

      function setEditStartEnd(start, end){
        if(editEvtStart) editEvtStart.value = isoLocal(start);
        if(editEvtEnd)   editEvtEnd.value   = isoLocal(end);
      }
      if(quickPlus30) quickPlus30.addEventListener('click', ()=>{ const start=new Date(editEvtStart.value); const end=new Date(editEvtEnd.value); end.setMinutes(end.getMinutes()+30); setEditStartEnd(start,end); });
      if(quickTomorrow) quickTomorrow.addEventListener('click', ()=>{ const start=new Date(editEvtStart.value); start.setDate(start.getDate()+1); const end=new Date(editEvtEnd.value); end.setDate(end.getDate()+1); setEditStartEnd(start,end); });
      if(quickNextWeek) quickNextWeek.addEventListener('click', ()=>{ const start=new Date(editEvtStart.value); start.setDate(start.getDate()+7); const end=new Date(editEvtEnd.value); end.setDate(end.getDate()+7); setEditStartEnd(start,end); });

      if(evtSaveBtn) evtSaveBtn.addEventListener('click', async ()=>{
        if (!activeEvent) return;
        const payload = {
          action: 'reschedule',
          id: activeEvent.id,
          title: (editEvtTitle && editEvtTitle.value || '').trim(),
          start: new Date(editEvtStart.value).toISOString(),
          end:   new Date(editEvtEnd.value).toISOString(),
          instructor_id: parseInt(editEvtIns && editEvtIns.value || '0', 10) || null
        };
        if (!payload.title) { alert('Titel is verplicht.'); return; }
        const ok = await api(payload);
        if (ok) {
          activeEvent.setProp('title', payload.title);
          if (payload.instructor_id && activeEvent.extendedProps) activeEvent.setExtendedProp('instructorId', payload.instructor_id);
          activeEvent.setStart(payload.start);
          activeEvent.setEnd(payload.end);
          closeEvtActionModal();
          toast('Opgeslagen');
          cal.refetchEvents();
          queueLaneBuild();
        }
      });

      if(evtCancelRestoreBtn) evtCancelRestoreBtn.addEventListener('click', async ()=>{
        if (!activeEvent) return;
        const isCancelled = (activeEvent.extendedProps||{}).status === 'cancelled';
        const ok = await api({ action: isCancelled ? 'restore' : 'cancel', id: activeEvent.id });
        if (ok) { closeEvtActionModal(); cal.refetchEvents(); toast(isCancelled?'Hersteld':'Geannuleerd'); queueLaneBuild(); }
      });
      if(evtDeleteBtn) evtDeleteBtn.addEventListener('click', async ()=>{
        if (!activeEvent) return;
        if (!confirm('Weet je zeker dat je deze afspraak wilt verwijderen?')) return;
        const ok = await api({ action:'delete', id: activeEvent.id });
        if (ok) { closeEvtActionModal(); activeEvent.remove(); toast('Verwijderd'); queueLaneBuild(); }
      });
      if(evtCloseBtn) evtCloseBtn.addEventListener('click', closeEvtActionModal);
      if(evtDismissBtn) evtDismissBtn.addEventListener('click', closeEvtActionModal);
      if(evtModal) evtModal.addEventListener('click', (e)=>{ if(e.target===evtModal) closeEvtActionModal(); });

      // LANE LOGICA — harness positioning
      let laneTimer=null;
      function queueLaneBuild(){ clearTimeout(laneTimer); laneTimer=setTimeout(buildInstructorLanes, 32); }
      function isHiddenHarness(h){
        if (!h) return true;
        if (h.classList.contains('fc-hide')) return true;
        const ev = h.querySelector('.fc-timegrid-event'); if (!ev) return true;
        if (ev.classList.contains('fc-hide')) return true;
        if (getComputedStyle(h).display==='none') return true;
        return false;
      }
      function buildInstructorLanes(){
        const v = cal.view.type;
        $all('.fc-timegrid-event-harness').forEach(h=>{ h.style.left=''; h.style.width=''; h.style.right=''; });
        $all('.lane-sep').forEach(n=>n.remove());
        if (v!=='timeGridDay' && v!=='timeGridWeek') return;

        const dayCols = $all('.fc-timegrid-col');
        dayCols.forEach(col=>{
          const harnesses = Array.from(col.querySelectorAll('.fc-timegrid-event-harness'));
          const ids = new Set();
          harnesses.forEach(h=>{
            if (isHiddenHarness(h)) return;
            const ev = h.querySelector('.fc-timegrid-event[data-ins]');
            const id = ev ? ev.getAttribute('data-ins') : null;
            ids.add(id || '0');
          });
          const count = ids.size;
          if (count<=1) return;
          const sorted = Array.from(ids).sort((a,b)=> (parseInt(a||'0',10) - parseInt(b||'0',10)));
          const map = new Map(sorted.map((id,idx)=>[id, idx]));

          harnesses.forEach(h=>{
            if (isHiddenHarness(h)) return;
            const ev = h.querySelector('.fc-timegrid-event[data-ins]');
            const id = ev ? ev.getAttribute('data-ins') : '0';
            const idx = map.get(id) ?? 0;
            const leftPct  = (idx * 100 / count);
            const widthPct = (100 / count);
            h.style.right = 'auto';
            h.style.left  = leftPct + '%';
            h.style.width = widthPct + '%';
          });

          const frame = col.querySelector('.fc-timegrid-col-frame');
          if (frame){
            for (let i=1;i<count;i++){
              const sep = document.createElement('div');
              sep.className = 'lane-sep';
              sep.style.left = (i*100/count)+'%';
              frame.appendChild(sep);
            }
          }
        });
      }

      // Expose debug
      window.calDebug = ()=>({ view: cal.view.type, events: cal.getEvents().length });
    }
  });
})();