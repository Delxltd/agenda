<?php
// /calendar.php  —  Kalenderview (oude code + fixes)  ✅ klik/drag in Dag/Week werkt weer
require __DIR__.'/includes/auth.php'; require_login();
require __DIR__.'/includes/helpers.php';

$u           = current_user();
$instructors = all_instructors();    // id, name
$students    = all_students();       // id, first_name, last_name
$labels      = appt_labels();        // type => label

$pre = [];
if (($u['role'] ?? '') === 'instructor') $pre = [(int)$u['id']];
$selectedJson = json_encode($pre);

$page_title = 'Agenda'; $nav_active = 'agenda';
require __DIR__ . '/includes/app_header.php';
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css">
<style>
:root{
  --card:#ffffff; --muted:#6b7280; --line:#e5e7eb;
  --primary:#2563eb; --primary-50:#eef2ff; --accent:#0ea5e9;
  --ok:#16a34a; --warn:#f59e0b; --danger:#dc2626; --bg:#f8fafc;
}
body{background:var(--bg)}
/* Layout */
.pro{display:grid;grid-template-columns:320px 1fr;gap:16px}
@media (max-width:1140px){ .pro{grid-template-columns:1fr} .side{order:2} .main{order:1} }
/* Fullscreen: hide sidebar, give calendar all space */
.pro.pro-full{grid-template-columns:1fr}
.pro.pro-full .side{display:none}
.side{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px;position:sticky;top:86px;align-self:start;max-height:calc(100vh - 120px);overflow:auto}
.main{display:flex;flex-direction:column;min-height:calc(100vh - 120px)}
.top{display:flex;align-items:center;justify-content:space-between;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:8px 10px;margin-bottom:12px;gap:8px;flex-wrap:wrap}

/* Buttons */
.btn{border:1px solid var(--line);background:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;font-weight:600}
.btn:hover{box-shadow:0 1px 0 rgba(2,6,23,.06)}
.btn-primary{background:var(--primary);border-color:var(--primary);color:#fff}
.btn-warning{background:var(--warn);border-color:var(--warn);color:#fff}
.btn-danger{background:var(--danger);border-color:var(--danger);color:#fff}
.btn-ghost{background:transparent}

/* Inputs */
.select,.input,textarea{border:1px solid var(--line);padding:10px 12px;border-radius:10px;background:#fff;width:100%}
.select:focus,.input:focus,textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.15)}

/* Calendar card */
.cal{background:var(--card);border:1px solid var(--line);border-radius:14px;flex:1;min-height:600px;position:relative;overflow:hidden}
#bubble{position:absolute;top:10px;right:12px;background:#111827;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;opacity:0;transition:opacity .2s;z-index:50}

/* Sidebar */
h3.label{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin:10px 0 8px}
.chips{display:flex;gap:6px;flex-wrap:wrap}

/* Cleaner chips */
.chip{border:1px solid var(--line);background:#fff;padding:7px 11px;border-radius:999px;font-size:12px;cursor:pointer;display:inline-flex;gap:8px;align-items:center}
.chip.active{border-color:var(--primary); background:var(--primary-50); color:#1d4ed8}
.chip .minidot{width:8px;height:8px;border-radius:999px;box-shadow:inset 0 0 0 2px rgba(255,255,255,.9)}
.chip.ins{padding:7px 10px 7px 8px}

/* FullCalendar base polish */
.fc{--fc-border-color:#eef2f7;--fc-page-bg-color:#fff;--fc-now-indicator-color:#ef4444}
.fc .fc-toolbar{display:none}
.fc .fc-timegrid-slot{height:2.2rem}
.fc .fc-daygrid-event{border:none; display:block}
.fc .fc-daygrid-event-harness{margin:1px 0}
.fc .fc-daygrid-more-link{display:block;margin-top:2px;font-size:11px;color:var(--primary)}
.fc .fc-event{border:none;border-left:6px solid var(--primary);border-radius:12px;padding:3px 6px;box-shadow:0 1px 0 rgba(2,6,23,.04);cursor:pointer;background:#fff}
.fc .is-cancelled{opacity:.6;text-decoration:line-through;filter:grayscale(.4)}
.fc .fc-hide{display:none !important}

/* Better day layout: avoid event overlap */
.fc .fc-timegrid-col .fc-event{margin:2px 0}
.fc .fc-timegrid-event .fc-event-main{padding:2px 2px}

/* Event rendering */
.evt-title{font-weight:700;font-size:12px;display:flex;gap:6px;align-items:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.evt-title .dot{width:9px;height:9px;border-radius:99px;background:var(--primary);box-shadow:inset 0 0 0 2px rgba(255,255,255,.9)}
.evt-meta{margin-top:2px;font-size:11px;opacity:.95;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.badge{display:inline-flex;gap:6px;align-items:center;padding:2px 6px;border-radius:999px;background:#eef2ff;color:#1d4ed8;font-size:11px}
.type-pill{background:#f1f5f9;color:#0f172a;border-radius:999px;padding:1px 6px;font-size:10.5px}

/* Month view compacter */
.fc-daygrid .evt-meta{display:none}
.fc-daygrid .evt-title .dot{transform:translateY(.5px)}

/* Instructor colors (1..12) */
.fc .ins-1{border-left-color:#2563eb}.dot.ins-1{background:#2563eb}
.fc .ins-2{border-left-color:#10b981}.dot.ins-2{background:#10b981}
.fc .ins-3{border-left-color:#f59e0b}.dot.ins-3{background:#f59e0b}
.fc .ins-4{border-left-color:#ef4444}.dot.ins-4{background:#ef4444}
.fc .ins-5{border-left-color:#8b5cf6}.dot.ins-5{background:#8b5cf6}
.fc .ins-6{border-left-color:#14b8a6}.dot.ins-6{background:#14b8a6}
.fc .ins-7{border-left-color:#e11d48}.dot.ins-7{background:#e11d48}
.fc .ins-8{border-left-color:#0ea5e9}.dot.ins-8{background:#0ea5e9}
.fc .ins-9{border-left-color:#22c55e}.dot.ins-9{background:#22c55e}
.fc .ins-10{border-left-color:#f97316}.dot.ins-10{background:#f97316}
.fc .ins-11{border-left-color:#a855f7}.dot.ins-11{background:#a855f7}
.fc .ins-12{border-left-color:#06b6d4}.dot.ins-12{background:#06b6d4}

/* Tooltip */
#tt{position:absolute;z-index:40;background:#0f172a;color:#fff;border-radius:10px;padding:10px 12px;font-size:12px;box-shadow:0 8px 24px rgba(2,6,23,.28);display:none;max-width:320px;pointer-events:none}
#tt .t{font-weight:800;margin-bottom:4px}
#tt .row{display:flex;gap:8px;opacity:.96;line-height:1.35}

/* Modal shell */
.modal{position:fixed;inset:0;background:rgba(17,24,39,.55);display:none;z-index:60}
.modal.show{display:block}
.modal .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:16px}

/* Little UI polish */
.kbd{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;background:#f8fafc;border:1px solid var(--line);padding:1px 5px;border-radius:6px;font-size:12px}
.helper{font-size:12px;color:var(--muted)}

/* === INSTRUCTEUR-KOLOMMEN (illusie) voor Dag/Week) ===
   Belangrijke fix: lane krijgt pointer-events:auto zodat events klikbaar blijven. */
.fc-timegrid-col-frame { position: relative; }
.fc-timegrid-col-frame .ins-lanes {
  position:absolute; inset:0; display:grid; grid-auto-flow:column; gap:6px;
  pointer-events:none;   /* container blokkeert niets */
}
.fc-timegrid-col-frame .ins-lane {
  position:relative; border-left:1px dashed rgba(2,6,23,.06);
  pointer-events:auto;   /* ✅ events in deze lane zijn weer klikbaar */
}
/* Zorg dat events in lanes hun volle breedte nemen */
.fc-timegrid-col-frame .ins-lane .fc-event{ left:0 !important; right:0 !important; width:auto !important; }

/* Compacte modus */
#calendar.compact .fc-timegrid-slot{height:2rem}
#calendar.compact .evt-title{font-size:11px}
#calendar.compact .evt-meta{font-size:10px}
</style>

<div class="pro" id="wrap">
  <!-- SIDEBAR -->
  <aside class="side">
    <h3 class="label">Zoeken</h3>
    <input id="q" class="input" type="search" placeholder="Zoek leerling / instructeur / titel">

    <h3 class="label">Type</h3>
    <div id="typeChips" class="chips">
      <?php foreach ($labels as $k=>$v): ?>
        <button class="chip active" data-type="<?= h($k) ?>"><span class="minidot" style="background:#CBD5E1"></span><?= h($v) ?></button>
      <?php endforeach; ?>
    </div>

    <h3 class="label">Instructeurs</h3>
    <div id="insChips" class="chips" style="gap:8px">
      <?php foreach ($instructors as $idx=>$ins): $i=$idx+1; $active = in_array((int)$ins['id'],$pre,true); ?>
        <button class="chip ins<?= $active?' active':'' ?>" data-id="<?= (int)$ins['id'] ?>" title="<?= h($ins['name']) ?>">
          <span class="minidot ins-<?= $i ?>"></span>
          <span style="font-weight:600"><?= h($ins['name']) ?></span>
        </button>
      <?php endforeach; ?>
      <?php if (empty($instructors)): ?><div class="helper">Geen instructeurs</div><?php endif; ?>
    </div>

    <h3 class="label">Datum</h3>
    <input id="jumpDate" class="input" type="date">

    <h3 class="label">Opties</h3>
    <label><input type="checkbox" id="toggleWeekends" checked> Weekenden tonen</label><br>
    <label><input type="checkbox" id="toggleCompact"> Compacte weergave</label><br>
    <button id="btnPrint" class="btn" style="margin-top:8px">Print</button>

    <h3 class="label">Export</h3>
    <a id="icsLink" class="btn" href="/calendar_feed.php" target="_blank">ICS‑feed</a>
  </aside>

  <!-- MAIN -->
  <section class="main">
    <div class="top">
      <div>
        <button id="btnPrev" class="btn" title="Vorige (J)">‹</button>
        <button id="btnToday" class="btn" title="Vandaag (T)">Vandaag</button>
        <button id="btnNext" class="btn" title="Volgende (K)">›</button>
        <span id="viewTitle" style="margin-left:8px;font-weight:800">Agenda</span>
        <span class="helper" style="margin-left:8px">Sneltoetsen: <span class="kbd">1</span>/<span class="kbd">2</span>/<span class="kbd">3</span> &nbsp; <span class="kbd">J</span>/<span class="kbd">K</span>/<span class="kbd">T</span></span>
      </div>
      <div>
        <div style="display:inline-flex;gap:6px;margin-right:8px">
          <button data-view="timeGridDay" class="btn" title="Dag (1)">Dag</button>
          <button data-view="timeGridWeek" class="btn btn-primary" title="Week (2)">Week</button>
          <button data-view="dayGridMonth" class="btn" title="Maand (3)">Maand</button>
        </div>
        <button id="btnFull" class="btn" title="Volledig scherm">Volledig scherm</button>
        <button id="openCreate" class="btn btn-primary" title="Nieuwe afspraak (N)">Nieuwe afspraak</button>
      </div>
    </div>

    <div class="cal">
      <div id="bubble"></div>
      <div id="tt"></div>
      <?php if ($flash): ?><script>window.__flash = <?= json_encode($flash) ?>;</script><?php endif; ?>
      <div id="calendar" style="height:100%"></div>
    </div>
  </section>
</div>

<!-- Create -->
<div id="createModal" class="modal" aria-hidden="true" role="dialog" aria-label="Nieuwe afspraak">
  <div class="card" style="max-width:720px;margin:8vh auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <strong>Nieuwe afspraak</strong>
      <button id="closeModal" class="btn" aria-label="Sluiten">×</button>
    </div>
    <form method="post" action="/appointments/create.php" id="createForm">
      <?= csrf_input() ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <label>Type
          <select id="type" name="type" class="select" required>
            <?php foreach ($labels as $k=>$v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label>Duur
          <select id="duration" class="select">
            <option value="60">60 minuten</option>
            <option value="90" selected>90 minuten</option>
            <option value="120">120 minuten</option>
          </select>
        </label>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
        <div>
          <?php if (($u['role'] ?? '') === 'admin'): ?>
          <label>Instructeur
            <select id="insSelect" name="instructor_id" class="select">
              <?php foreach ($instructors as $ins): ?><option value="<?= (int)$ins['id'] ?>"><?= h($ins['name']) ?></option><?php endforeach; ?>
            </select>
          </label>
          <?php else: ?>
            <input type="hidden" name="instructor_id" value="<?= (int)($u['id'] ?? 0) ?>">
          <?php endif; ?>
        </div>
        <label>Leerling (leeg = blokkade)
          <select name="student_id" class="select">
            <option value="">— Geen —</option>
            <?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= h(($s['first_name']??'').' '.($s['last_name']??'')) ?></option><?php endforeach; ?>
          </select>
        </label>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
        <label>Start
          <input id="start" name="start" type="datetime-local" class="select" required>
        </label>
        <label>Einde
          <input id="end" name="end" type="datetime-local" class="select" required>
        </label>
      </div>

      <label style="margin-top:10px">Titel (voor blokkade)
        <input name="title" class="select">
      </label>

      <label style="margin-top:10px">Notities
        <textarea name="notes" class="select" style="height:80px"></textarea>
      </label>

      <div style="display:flex;justify-content:space-between;gap:8px;margin-top:12px">
        <div class="helper">Tip: klik in <strong>Maand</strong> op een dag – de datum wordt nu automatisch overgenomen.</div>
        <div>
          <button type="button" id="btnCancel" class="btn">Annuleer</button>
          <button class="btn btn-primary">Opslaan</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Acties / detail -->
<div id="evtModal" class="modal" aria-hidden="true" role="dialog" aria-label="Afspraak acties">
  <div class="card" style="max-width:620px;margin:12vh auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <strong id="evtTitleHead">Afspraak</strong>
      <button id="evtClose" class="btn" aria-label="Sluiten">×</button>
    </div>
    <div id="evtMeta" class="helper" style="margin-bottom:10px"></div>

    <div style="border-top:1px solid var(--line);padding-top:10px;margin-top:6px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <label>Titel
          <input type="text" id="editEvtTitle" class="select" placeholder="Bijv. 60 minuten rijles — Jaden">
        </label>
        <label>Instructeur
          <select id="editEvtInstructor" class="select">
            <?php foreach ($instructors as $ins): ?><option value="<?= (int)$ins['id'] ?>"><?= h($ins['name']) ?></option><?php endforeach; ?>
          </select>
        </label>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
        <label>Start
          <input type="datetime-local" id="editEvtStart" class="select">
        </label>
        <label>Einde
          <input type="datetime-local" id="editEvtEnd" class="select">
        </label>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-top:8px">
        <button id="quickPlus30" class="btn">+30 min</button>
        <button id="quickTomorrow" class="btn">Morgen</button>
        <button id="quickNextWeek" class="btn">Volgende week</button>
        <div class="helper">Snel aanpassen</div>
      </div>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
      <button id="evtSave" class="btn btn-primary">Opslaan</button>
      <button id="evtCancelRestore" class="btn btn-warning">Annuleer</button>
      <button id="evtDelete" class="btn btn-danger">Verwijder</button>
      <button id="evtDismiss" class="btn">Sluit</button>
    </div>
  </div>
</div>

<script>
(function ensureFC(){
  if (window.FullCalendar) return;
  var s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js';
  s.onerror=function(){ var s2=document.createElement('script'); s2.src='https://unpkg.com/fullcalendar@6.1.10/index.global.min.js'; document.head.appendChild(s2); };
  document.head.appendChild(s);
})();

document.addEventListener('DOMContentLoaded', function(){
  (function wait(i){ if (window.FullCalendar && window.FullCalendar.Calendar) return init();
    if (i>100){ console.error('FullCalendar kon niet laden'); alert('FullCalendar kon niet laden'); return; }
    setTimeout(()=>wait(i+1),100);
  })(0);

  function toast(msg){
    const b=document.getElementById('bubble'); if(!b || !msg) return;
    b.textContent = msg; b.style.opacity='.95'; setTimeout(()=>b.style.opacity='0', 1800);
  }
  function isoLocal(datetime){
    const d = new Date(datetime);
    const pad=n=>String(n).padStart(2,'0');
    return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes());
  }
  function clamp(v,min,max){ return Math.max(min, Math.min(max, v)); }

  function init(){
    const CSRF = "<?= h(csrf_token()) ?>";
    const currentUserId = <?= (int)($u['id'] ?? 0) ?>;
    const currentUserRole = "<?= h($u['role'] ?? '') ?>";
    const initialSelected = new Set(<?= $selectedJson ?>);
    const typesEnabled = new Set(<?= json_encode(array_keys($labels)) ?>);

    if (window.__flash){ toast(window.__flash.msg || ''); }

    const wrap   = document.getElementById('wrap');
    const el     = document.getElementById('calendar');
    const tt     = document.getElementById('tt');

    const initialView = localStorage.getItem('cal.view') || (window.innerWidth<900?'timeGridDay':'timeGridWeek');
    const showWeekends = localStorage.getItem('cal.weekends') !== '0';
    const compact = localStorage.getItem('cal.compact') === '1'; if (compact) el.classList.add('compact');
    const scroll = new Date(); scroll.setHours(Math.max(7, scroll.getHours()-1),0,0,0);

    const cal = new FullCalendar.Calendar(el, {
      initialView: initialView,
      firstDay:1,
      locale:'nl',
      height:'100%',
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

      // EVENTS FEED
      events: function(fetchInfo, success, failure){
        const params = new URLSearchParams();
        params.set('start', fetchInfo.startStr);
        params.set('end',   fetchInfo.endStr);
        if (initialSelected.size) params.set('instructor_ids', Array.from(initialSelected).join(','));
        const enabledTypes = Array.from(typesEnabled).join(',');
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
        document.getElementById('viewTitle').textContent = arg.view.title;
        const p = new URLSearchParams();
        p.set('from', arg.startStr.slice(0,10)); p.set('to', arg.endStr.slice(0,10));
        if (initialSelected.size) p.set('instructor_id', Array.from(initialSelected)[0]);
        document.getElementById('icsLink').href = '/calendar_feed.php?'+p.toString();
        setTimeout(buildInstructorLanes, 0);
      },

      eventAllow: (dropInfo, ev)=>
        currentUserRole==='admin'
          ? ev.extendedProps.status!=='cancelled'
          : (ev.extendedProps.instructorId===currentUserId && ev.extendedProps.status!=='cancelled'),

      eventDrop: async (info)=>{
        const ok = await api({action:'move',id:info.event.id,start:info.event.start.toISOString(),end:info.event.end.toISOString()});
        if(!ok) info.revert(); else setTimeout(buildInstructorLanes, 0);
      },

      eventResize: async (info)=>{
        const ok = await api({action:'resize',id:info.event.id,start:info.event.start.toISOString(),end:info.event.end.toISOString()});
        if(!ok) info.revert(); else setTimeout(buildInstructorLanes, 0);
      },

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
      eventMouseLeave: function(){ tt.style.display='none'; },
      eventDragStart: ()=>{ tt.style.display='none'; },
      eventResizeStart: ()=>{ tt.style.display='none'; },

      eventContent: function(arg){
        const e = arg.event, p = e.extendedProps || {};
        const isMonth = arg.view && arg.view.type==='dayGridMonth';
        const wrap = document.createElement('div'); wrap.className='evt';
        const t = document.createElement('div'); t.className='evt-title';
        const dot = document.createElement('span'); dot.className='dot ins-'+(p.instructorColorIdx||1); t.appendChild(dot);
        const title = document.createElement('span'); title.textContent = (e.title||'').replace(/\s+—\s+/g,' — '); t.appendChild(title); wrap.appendChild(t);
        if (!isMonth){
          const m = document.createElement('div'); m.className='evt-meta';
          const badge = document.createElement('span'); badge.className='badge'; badge.textContent = p.instructorName || '—'; m.appendChild(badge);
          const pill = document.createElement('span'); pill.className='type-pill'; pill.textContent = p.typeLabel || p.type || ''; m.appendChild(pill);
          wrap.appendChild(m);
        } else {
          const shortI = p.instructorName ? (p.instructorName.split(' ')[0]) : '';
          if (arg.timeText) {
            title.textContent = arg.timeText + ' · ' + (shortI ? (shortI + ' · ') : '') + title.textContent;
          } else if (shortI) {
            title.textContent = shortI + ' · ' + title.textContent;
          }
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

    // ===== Tooltip helpers =====
    function renderTooltipFor(event, targetEl){
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
    window.addEventListener('scroll', ()=>{ if (tt.style.display==='block') positionTTByRect(tt.getBoundingClientRect()); }, true);
    window.addEventListener('resize', ()=>{ tt.style.display='none'; queueLaneBuild(); });

    // ===== API helper =====
    async function api(payload){
      try{
        const r=await fetch('/api/update_event.php',{
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},
          body:JSON.stringify(payload)
        });
        const j=await r.json().catch(()=>null);
        if(!r.ok || !j || j.ok!==true){ alert((j && j.error) || 'Actie mislukt'); return false; }
        return true;
      }catch(e){ console.error(e); alert('Serverfout'); return false; }
    }

    // ===== Toolbar =====
    const on=(id,cb)=>{ const n=document.getElementById(id); if(n) n.addEventListener('click',cb); };
    on('btnPrev', ()=>cal.prev()); on('btnToday', ()=>cal.today()); on('btnNext', ()=>cal.next());
    document.querySelectorAll('[data-view]').forEach(b=>{
      b.addEventListener('click',()=>{
        document.querySelectorAll('[data-view]').forEach(x=>x.classList.remove('btn-primary'));
        b.classList.add('btn-primary');
        localStorage.setItem('cal.view', b.dataset.view);
        cal.changeView(b.dataset.view);
        setTimeout(buildInstructorLanes, 0);
      });
    });
    const w=document.getElementById('toggleWeekends'); w.checked=showWeekends; w.addEventListener('change',()=>{ localStorage.setItem('cal.weekends', w.checked?'1':'0'); cal.setOption('weekends', w.checked); setTimeout(buildInstructorLanes,0); });
    const c=document.getElementById('toggleCompact'); c.checked=compact; c.addEventListener('change',()=>{ localStorage.setItem('cal.compact', c.checked?'1':'0'); el.classList.toggle('compact', c.checked); });

    on('btnPrint', ()=>window.print());
    on('openCreate', ()=>openCreateModal());
    const jump=document.getElementById('jumpDate'); jump.addEventListener('change', ()=>{ if (jump.value) cal.gotoDate(jump.value); });

    // ===== Filters =====
    const q=document.getElementById('q'); q.addEventListener('input', applyClientFilter);
    document.getElementById('typeChips').addEventListener('click',(e)=>{
      const chip=e.target.closest('.chip'); if(!chip) return; const t=chip.dataset.type;
      if (chip.classList.contains('active')) { chip.classList.remove('active'); typesEnabled.delete(t); } else { chip.classList.add('active'); typesEnabled.add(t); }
      cal.refetchEvents();
    });
    document.getElementById('insChips').addEventListener('click',(e)=>{
      const chip=e.target.closest('.chip'); if(!chip) return; const id=+chip.dataset.id;
      if (chip.classList.contains('active')) { chip.classList.remove('active'); initialSelected.delete(id); } else { chip.classList.add('active'); initialSelected.add(id); }
      cal.refetchEvents();
      setTimeout(buildInstructorLanes, 50);
    });
    function applyClientFilter(){
      const text=q.value.toLowerCase().trim();
      cal.getEvents().forEach(ev=>{
        const hay=[ev.title||'', ev.extendedProps.studentName||'', ev.extendedProps.instructorName||''].join(' ').toLowerCase();
        const hide= !!text && !hay.includes(text);
        const cls=new Set(ev.classNames||[]); if(hide) cls.add('fc-hide'); else cls.delete('fc-hide'); ev.setProp('classNames', Array.from(cls));
      });
    }

    // ===== Keyboard =====
    document.addEventListener('keydown', (ev)=>{
      if (['INPUT','TEXTAREA','SELECT'].includes((ev.target||{}).tagName)) return;
      if (ev.key==='j' || ev.key==='J') { cal.next(); setTimeout(buildInstructorLanes,0); }
      if (ev.key==='k' || ev.key==='K') { cal.prev(); setTimeout(buildInstructorLanes,0); }
      if (ev.key==='t' || ev.key==='T') { cal.today(); setTimeout(buildInstructorLanes,0); }
      if (ev.key==='1') { cal.changeView('timeGridDay'); setTimeout(buildInstructorLanes,0); }
      if (ev.key==='2') { cal.changeView('timeGridWeek'); setTimeout(buildInstructorLanes,0); }
      if (ev.key==='3') { cal.changeView('dayGridMonth'); }
      if (ev.key==='n' || ev.key==='N') { openCreateModal(); }
    });

    // ===== Create modal =====
    const m=document.getElementById('createModal');
    const s=document.getElementById('start'), e=document.getElementById('end'), dur=document.getElementById('duration');
    function updateEnd(){ if(!s.value) return; const d=new Date(s.value); const mins=parseInt(dur.value||'90',10)||90; const end=new Date(d.getTime()+mins*60000); e.value=isoLocal(end); }
    dur.addEventListener('change', updateEnd); s.addEventListener('change', updateEnd);

    function openCreateModal(startDT, endDT){
      let start = startDT ? new Date(startDT) : new Date();
      if (!startDT){
        const m = start.getMinutes(); start.setMinutes(m - (m%15), 0, 0);
      }
      let end   = endDT ? new Date(endDT) : new Date(start.getTime() + (parseInt(dur.value||'90',10)||90)*60000);
      s.value = isoLocal(start);
      e.value = isoLocal(end);

      m.classList.add('show'); m.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden';
      <?php if (($u['role'] ?? '') === 'admin'): ?>
      const firstSel = Array.from(document.querySelectorAll('#insChips .chip.active')).map(c=>+c.dataset.id)[0] || <?= (int)($instructors[0]['id'] ?? 0) ?>;
      const sel=document.getElementById('insSelect'); if (firstSel && sel) sel.value = String(firstSel);
      <?php endif; ?>
    }
    function closeCreateModal(){ m.classList.remove('show'); m.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }
    document.getElementById('closeModal').addEventListener('click', closeCreateModal);
    document.getElementById('btnCancel').addEventListener('click', closeCreateModal);
    m.addEventListener('click', (e)=>{ if(e.target===m) closeCreateModal(); });

    // ===== Event acties & wijzigen =====
    const evtModal = document.getElementById('evtModal');
    const evtTitleHead = document.getElementById('evtTitleHead');
    const evtMeta = document.getElementById('evtMeta');
    const evtCancelRestoreBtn = document.getElementById('evtCancelRestore');
    const evtDeleteBtn = document.getElementById('evtDelete');
    const evtCloseBtn = document.getElementById('evtClose');
    const evtDismissBtn = document.getElementById('evtDismiss');
    const evtSaveBtn = document.getElementById('evtSave');

    const editEvtTitle = document.getElementById('editEvtTitle');
    const editEvtStart = document.getElementById('editEvtStart');
    const editEvtEnd   = document.getElementById('editEvtEnd');
    const editEvtIns   = document.getElementById('editEvtInstructor');
    const quickPlus30  = document.getElementById('quickPlus30');
    const quickTomorrow= document.getElementById('quickTomorrow');
    const quickNextWeek= document.getElementById('quickNextWeek');

    let activeEvent = null;

    function fmt(dt){ try { return new Date(dt).toLocaleString('nl-NL',{dateStyle:'medium', timeStyle:'short'}); } catch(e){ return dt; } }

    function openEvtActionModal(event){
      activeEvent = event;
      const p = event.extendedProps||{};
      evtTitleHead.textContent = (event.title || 'Afspraak');
      evtMeta.innerHTML = [
        p.instructorName ? 'Instructeur: <strong>'+p.instructorName+'</strong>' : '',
        p.studentName    ? 'Leerling: <strong>'+p.studentName+'</strong>'   : '',
        'Van: <strong>'+fmt(event.start)+'</strong>',
        'Tot: <strong>'+fmt(event.end)+'</strong>'
      ].filter(Boolean).join(' • ');

      editEvtTitle.value = event.title || '';
      editEvtStart.value = isoLocal(event.start);
      const fallbackEnd = new Date(event.start.getTime()+60*60000);
      editEvtEnd.value = isoLocal(event.end || fallbackEnd);
      if (p.instructorId) editEvtIns.value = String(p.instructorId);

      if (p.status === 'cancelled') {
        evtCancelRestoreBtn.textContent = 'Herstellen';
        evtCancelRestoreBtn.classList.remove('btn-warning');
        evtCancelRestoreBtn.classList.add('btn-primary');
      } else {
        evtCancelRestoreBtn.textContent = 'Annuleren';
        evtCancelRestoreBtn.classList.remove('btn-primary');
        evtCancelRestoreBtn.classList.add('btn-warning');
      }

      evtModal.classList.add('show'); evtModal.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden';
    }
    function closeEvtActionModal(){ evtModal.classList.remove('show'); evtModal.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }

    // Quick helpers
    function setEditStartEnd(start, end){
      editEvtStart.value = isoLocal(start);
      editEvtEnd.value   = isoLocal(end);
    }
    quickPlus30.addEventListener('click', ()=>{
      const start = new Date(editEvtStart.value);
      const end   = new Date(editEvtEnd.value);
      end.setMinutes(end.getMinutes()+30);
      setEditStartEnd(start,end);
    });
    quickTomorrow.addEventListener('click', ()=>{
      const start = new Date(editEvtStart.value); start.setDate(start.getDate()+1);
      const end   = new Date(editEvtEnd.value);   end.setDate(end.getDate()+1);
      setEditStartEnd(start,end);
    });
    quickNextWeek.addEventListener('click', ()=>{
      const start = new Date(editEvtStart.value); start.setDate(start.getDate()+7);
      const end   = new Date(editEvtEnd.value);   end.setDate(end.getDate()+7);
      setEditStartEnd(start,end);
    });

    evtSaveBtn.addEventListener('click', async ()=>{
      if (!activeEvent) return;
      const payload = {
        action: 'reschedule',
        id: activeEvent.id,
        title: editEvtTitle.value.trim(),
        start: new Date(editEvtStart.value).toISOString(),
        end:   new Date(editEvtEnd.value).toISOString(),
        instructor_id: parseInt(editEvtIns.value || '0', 10) || null
      };
      if (!payload.title) { alert('Titel is verplicht.'); return; }
      const ok = await api(payload);
      if (ok) {
        activeEvent.setProp('title', payload.title);
        if (payload.instructor_id && activeEvent.extendedProps) {
          activeEvent.setExtendedProp('instructorId', payload.instructor_id);
        }
        activeEvent.setStart(payload.start);
        activeEvent.setEnd(payload.end);
        closeEvtActionModal();
        toast('Opgeslagen');
        cal.refetchEvents();
        setTimeout(buildInstructorLanes, 0);
      }
    });

    evtCancelRestoreBtn.addEventListener('click', async ()=>{
      if (!activeEvent) return;
      const isCancelled = (activeEvent.extendedProps||{}).status === 'cancelled';
      const ok = await api({ action: isCancelled ? 'restore' : 'cancel', id: activeEvent.id });
      if (ok) { closeEvtActionModal(); cal.refetchEvents(); toast(isCancelled?'Hersteld':'Geannuleerd'); setTimeout(buildInstructorLanes,0); }
    });
    evtDeleteBtn.addEventListener('click', async ()=>{
      if (!activeEvent) return;
      if (!confirm('Weet je zeker dat je deze afspraak wilt verwijderen?')) return;
      const ok = await api({ action:'delete', id: activeEvent.id });
      if (ok) { closeEvtActionModal(); activeEvent.remove(); toast('Verwijderd'); setTimeout(buildInstructorLanes,0); }
    });
    evtCloseBtn.addEventListener('click', closeEvtActionModal);
    evtDismissBtn.addEventListener('click', closeEvtActionModal);
    evtModal.addEventListener('click', (e)=>{ if(e.target===evtModal) closeEvtActionModal(); });

    // Debug helper
    window.calDebug = ()=>({ view: cal.view.type, events: cal.getEvents().length });

    /* ============================
       INSTRUCTEUR-LANE LOGICA (Fix)
       ============================ */
    let laneBuildTimer = null;
    function queueLaneBuild(){ clearTimeout(laneBuildTimer); laneBuildTimer = setTimeout(buildInstructorLanes, 30); }

    function buildInstructorLanes(){
      const v = cal.view.type;
      if (v!=='timeGridDay' && v!=='timeGridWeek') { removeAllLanes(); return; }

      const dayCols = document.querySelectorAll('.fc-timegrid-col');
      dayCols.forEach(col=>{
        const frame = col.querySelector('.fc-timegrid-col-frame');
        if (!frame) return;

        const evts = Array.from(col.querySelectorAll('.fc-timegrid-event'));
        const insSet = new Map(); // id -> index
        evts.forEach(e=>{
          const id = e.getAttribute('data-ins') || '0';
          if (!insSet.has(id)) insSet.set(id, insSet.size);
        });
        const count = Math.max(1, insSet.size || 1);

        let lanes = frame.querySelector('.ins-lanes');
        if (!lanes){
          lanes = document.createElement('div');
          lanes.className = 'ins-lanes';
          frame.appendChild(lanes);
        }
        lanes.style.gridTemplateColumns = `repeat(${count}, minmax(0, 1fr))`;

        const wanted = count;
        const cur = lanes.children.length;
        if (cur < wanted){
          for (let i=0;i<wanted-cur;i++){ const d=document.createElement('div'); d.className='ins-lane'; lanes.appendChild(d); }
        } else if (cur > wanted){
          for (let i=cur-1;i>=wanted;i--){ lanes.children[i].remove(); }
        }
        const laneEls = Array.from(lanes.children);

        evts.forEach(e=>{
          const id = e.getAttribute('data-ins') || '0';
          const laneIdx = insSet.get(id) ?? 0;
          const lane = laneEls[laneIdx];
          if (lane && e.parentElement !== lane){
            lane.appendChild(e);
          }
          e.style.left = '0px';
          e.style.right = '0px';
          e.style.width = 'auto';
        });
      });
    }

    function removeAllLanes(){
      document.querySelectorAll('.ins-lanes').forEach(n=>n.remove());
    }
  }
});
</script>
<?php require __DIR__ . '/includes/app_footer.php'; ?>
