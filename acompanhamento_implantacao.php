<?php
require_once 'config.php';
require_once 'api/auth_check.php';

$cronogramaXml = __DIR__ . '/cronograma.xml';

// O próprio módulo entrega o XML somente após passar pela autenticação da intranet.
if (isset($_GET['xml'])) {
    if (!is_file($cronogramaXml)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'cronograma.xml não encontrado na raiz da intranet.';
        exit;
    }

    header('Content-Type: application/xml; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($cronogramaXml);
    exit;
}

$cronogramaVersion = is_file($cronogramaXml) ? (string) filemtime($cronogramaXml) : (string) time();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="flex-1 overflow-y-auto bg-slate-50 p-4 md:p-6 lg:p-8">
    <div class="max-w-[1700px] mx-auto">
        <div id="cronograma-app">
<style>
#cronograma-app{
 --bg:#f2f5f9;--surface:#fff;--ink:#172238;--muted:#657188;--line:#dce4ee;
 --primary:#1f5ed5;--primary-soft:#eaf1ff;--done:#149765;--warning:#e88a08;
 --danger:#d94444;--idle:#8b99ad;--shadow:0 15px 40px rgba(25,48,82,.09);
}
#cronograma-app *{box-sizing:border-box}
#cronograma-app{scroll-behavior:smooth}
#cronograma-app{margin:0;background:var(--bg);color:var(--ink);font-family:Inter,"Segoe UI",Roboto,Arial,sans-serif}
#cronograma-app .full{width:100%;padding-left:clamp(14px,1.6vw,30px);padding-right:clamp(14px,1.6vw,30px)}
#cronograma-app .brand-header{
 display:grid;grid-template-columns:minmax(170px,250px) minmax(0,1fr) minmax(170px,250px);
 align-items:center;gap:24px;padding:18px 26px;background:#fff;border-bottom:1px solid var(--line)
}
#cronograma-app .brand-box{height:72px;display:flex;align-items:center;justify-content:center;padding:8px 14px;border:1px solid var(--line);border-radius:14px;background:#fff}
#cronograma-app .brand-box img{max-width:100%;max-height:52px;object-fit:contain}
#cronograma-app .brand-fallback{display:none;font-weight:950;font-size:19px}
#cronograma-app .brand-title{text-align:center}
#cronograma-app .brand-title .eyebrow{font-size:11px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#68758a}
#cronograma-app .brand-title h1{margin:6px 0 4px;font-size:clamp(25px,2.4vw,39px);line-height:1.08;letter-spacing:-.035em}
#cronograma-app .brand-title h1 strong{color:var(--primary)}
#cronograma-app .brand-title p{margin:0;color:var(--muted);font-size:13px;font-weight:700}
#cronograma-app .hero{background:linear-gradient(115deg,#102a55,#16498a 58%,#2479ca);color:#fff;padding:25px 0 90px}
#cronograma-app .hero-inner{display:flex;align-items:center;justify-content:space-between;gap:24px}
#cronograma-app .kicker{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.15em;opacity:.73}
#cronograma-app .hero h2{font-size:clamp(27px,3vw,44px);margin:7px 0 5px;line-height:1.08}
#cronograma-app .hero p{margin:0;color:rgba(255,255,255,.76)}
#cronograma-app .date-position{border:1px solid rgba(255,255,255,.24);background:rgba(255,255,255,.1);border-radius:999px;padding:10px 15px;font-size:12px;white-space:nowrap}
#cronograma-app .cronograma-main{margin-top:-66px;padding-bottom:42px}
#cronograma-app .panel{background:var(--surface);border:1px solid rgba(220,228,238,.96);border-radius:18px;box-shadow:var(--shadow)}
#cronograma-app .timeline-panel{padding:25px 24px 18px}
#cronograma-app .heading{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:16px}
#cronograma-app .heading h2{margin:0;font-size:23px}
#cronograma-app .heading p{margin:6px 0 0;font-size:13px;color:var(--muted)}
#cronograma-app .current-chip{padding:10px 14px;border-radius:999px;background:var(--primary-soft);color:var(--primary);font-size:12px;font-weight:900;white-space:nowrap}
#cronograma-app .timeline{display:flex;overflow-x:auto;gap:8px;padding:16px 4px 27px;scrollbar-width:thin}
#cronograma-app .step{position:relative;flex:1 0 245px;min-width:245px;text-align:center;padding:0 16px;cursor:pointer}
#cronograma-app .step:before{content:"";position:absolute;left:-50%;right:50%;top:26px;height:5px;background:#dce5ef}
#cronograma-app .step:first-child:before{display:none}
#cronograma-app .step.completed:before,#cronograma-app .step.current:before{background:var(--done)}
#cronograma-app .node{position:relative;z-index:2;width:54px;height:54px;border-radius:50%;display:grid;place-items:center;margin:auto;background:#fff;border:5px solid #d5dfea;color:#758398;font-size:12px;font-weight:950}
#cronograma-app .step.completed .node{background:var(--done);border-color:var(--done);color:#fff}
#cronograma-app .step.current .node{border-color:var(--primary);color:var(--primary);box-shadow:0 0 0 8px rgba(31,94,213,.13)}
#cronograma-app .step.late .node{border-color:var(--danger);color:var(--danger)}
#cronograma-app .step.selected .node,#cronograma-app .step:hover .node{transform:translateY(-3px);box-shadow:0 10px 24px rgba(25,48,82,.18)}
#cronograma-app .step-name{font-size:16px;line-height:1.48;min-height:76px;margin-top:15px;font-weight:900;color:#1c2940}
#cronograma-app .step-date{font-size:12px;line-height:1.4;margin-top:7px;color:#5d6a7e;font-weight:750}
#cronograma-app .badge{display:inline-block;margin-top:9px;padding:6px 10px;border-radius:999px;background:#eef2f6;color:#657287;font-size:10.5px;font-weight:900}
#cronograma-app .step.completed .badge{background:#e6f7ef;color:#087a4a}
#cronograma-app .step.current .badge{background:var(--primary-soft);color:var(--primary)}
#cronograma-app .step.late .badge{background:#ffe8e8;color:#ad2828}
#cronograma-app .kpis{display:grid;grid-template-columns:1.35fr repeat(4,1fr);gap:13px;margin-top:14px}
#cronograma-app .kpi{padding:19px 20px;min-height:122px}
#cronograma-app .kpi-label{font-size:11px;font-weight:900;letter-spacing:.09em;text-transform:uppercase;color:var(--muted)}
#cronograma-app .kpi-value{font-size:29px;font-weight:950;margin:8px 0 4px}
#cronograma-app .kpi-note{font-size:12px;line-height:1.4;color:var(--muted)}
#cronograma-app .progress-kpi{display:flex;align-items:center;gap:18px}
#cronograma-app .ring{--value:0;width:82px;height:82px;min-width:82px;border-radius:50%;display:grid;place-items:center;position:relative;background:conic-gradient(var(--primary) calc(var(--value)*1%),#e3e9f0 0)}
#cronograma-app .ring:after{content:"";position:absolute;inset:8px;background:#fff;border-radius:50%}
#cronograma-app .ring span{z-index:1;font-size:20px;font-weight:950}
#cronograma-app .layout{display:grid;grid-template-columns:minmax(0,3.7fr) minmax(290px,1fr);gap:14px;margin-top:14px;align-items:start}
#cronograma-app .detail{padding:24px}
#cronograma-app .phase-head{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;border-bottom:1px solid var(--line);padding-bottom:17px}
#cronograma-app .phase-head h2{font-size:25px;line-height:1.3;margin:0 0 5px}
#cronograma-app .phase-head p{font-size:13px;margin:0;color:var(--muted)}
#cronograma-app .phase-value{text-align:right}
#cronograma-app .phase-value strong{font-size:32px}
#cronograma-app .bar{height:11px;background:#e6ecf3;border-radius:999px;overflow:hidden;margin-top:16px}
#cronograma-app .bar span{height:100%;display:block;background:linear-gradient(90deg,var(--primary),#67a7f3)}
#cronograma-app .submenu-title{margin:22px 0 10px;font-size:12px;font-weight:950;letter-spacing:.1em;text-transform:uppercase;color:#657188}
#cronograma-app .submenu-list{display:flex;gap:10px;overflow-x:auto;padding:2px 2px 13px;scrollbar-width:thin}
#cronograma-app .submenu{
 min-width:220px;max-width:320px;border:1px solid var(--line);background:#f8fafc;border-radius:13px;
 padding:13px 14px;cursor:pointer;text-align:left;color:var(--ink)
}
#cronograma-app .submenu:hover,#cronograma-app .submenu.selected{border-color:#9dbaf3;background:var(--primary-soft);box-shadow:0 8px 18px rgba(31,94,213,.08)}
#cronograma-app .submenu-name{font-size:13px;line-height:1.4;font-weight:900}
#cronograma-app .submenu-meta{font-size:10.5px;color:var(--muted);margin-top:6px;font-weight:700}
#cronograma-app .submenu-progress{display:flex;align-items:center;gap:8px;margin-top:9px;font-size:11px;font-weight:900}
#cronograma-app .submenu-mini{flex:1;height:6px;border-radius:999px;background:#dfe7f0;overflow:hidden}
#cronograma-app .submenu-mini i{display:block;height:100%;background:var(--primary)}
#cronograma-app .activity-grid{display:grid;grid-template-columns:1fr;gap:14px;margin-top:18px}
#cronograma-app .activity{border:1px solid var(--line);border-radius:15px;background:#fff;padding:18px 22px;min-height:112px;display:grid;grid-template-columns:46px minmax(0,1fr) minmax(145px,190px);gap:18px;align-items:center;box-shadow:0 8px 20px rgba(25,48,82,.04)}
#cronograma-app .activity:hover{transform:translateY(-2px);box-shadow:0 14px 28px rgba(25,48,82,.09);transition:.2s}
#cronograma-app .icon{width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:#edf2f7;color:#718096;font-size:18px;font-weight:900}
#cronograma-app .activity.done .icon{background:#e5f7ef;color:var(--done)}
#cronograma-app .activity.run .icon{background:#fff2dc;color:#ad6800}
#cronograma-app .activity.late .icon{background:#ffe8e8;color:var(--danger)}
#cronograma-app .activity-name{font-size:17px;font-weight:900;line-height:1.5;color:#172238}
#cronograma-app .meta{font-size:12.5px;color:var(--muted);margin-top:8px;line-height:1.5}
#cronograma-app .activity-dates{display:flex;flex-wrap:wrap;gap:9px 18px;margin-top:12px;font-size:13px;line-height:1.5;color:#536278;font-weight:750}
#cronograma-app .activity-dates strong{font-size:11px;letter-spacing:.035em;text-transform:uppercase;color:#25334b}
#cronograma-app .activity-progress{text-align:right;padding-top:0;align-self:center}
#cronograma-app .activity-progress b{font-size:18px}
#cronograma-app .mini{height:9px;background:#e5ebf2;border-radius:999px;overflow:hidden;margin-top:10px}
#cronograma-app .mini i{height:100%;display:block;background:var(--primary)}
#cronograma-app .side{padding:20px;position:sticky;top:10px}
#cronograma-app .side h3{margin:0 0 14px;font-size:17px}
#cronograma-app .summary{display:grid;grid-template-columns:1fr 1fr;gap:9px}
#cronograma-app .summary-item{padding:13px;border:1px solid var(--line);border-radius:12px;background:#f8fafc}
#cronograma-app .summary-top{display:flex;align-items:center;gap:7px;font-size:10.5px;font-weight:850;color:var(--muted)}
#cronograma-app .dot{width:9px;height:9px;border-radius:50%}
#cronograma-app .summary-item b{display:block;font-size:22px;margin-top:6px}
#cronograma-app .alert{margin-top:14px;border:1px solid #f0d79f;background:#fff8e9;border-radius:12px;padding:14px;font-size:12px;line-height:1.6;color:#75520e}
#cronograma-app .nav{display:flex;gap:8px;margin-top:14px}
#cronograma-app .nav button{flex:1;border:0;border-radius:10px;padding:11px;font-weight:900;background:#e9eff7;color:#29405f;cursor:pointer}
#cronograma-app .nav .primary{background:var(--primary);color:#fff}
#cronograma-app .empty{grid-column:1/-1;padding:40px;text-align:center;color:var(--muted)}
#cronograma-app .cronograma-footer{text-align:center;padding:23px;font-size:10.5px;color:var(--muted)}
@media (max-width:1180px){#cronograma-app .kpis{grid-template-columns:1fr 1fr 1fr}
#cronograma-app .progress-kpi{grid-column:span 2}
#cronograma-app .layout{grid-template-columns:1fr}
#cronograma-app .side{position:static}
#cronograma-app .activity-grid{grid-template-columns:1fr}}
@media (max-width:760px){#cronograma-app .brand-header{grid-template-columns:1fr 1fr;padding:14px}
#cronograma-app .brand-title{grid-column:1/-1;grid-row:1}
#cronograma-app .brand-box{height:58px}
#cronograma-app .hero-inner{align-items:flex-start}
#cronograma-app .date-position{display:none}
#cronograma-app .kpis{grid-template-columns:1fr 1fr}
#cronograma-app .progress-kpi{grid-column:1/-1}
#cronograma-app .step{min-width:215px}
#cronograma-app .heading{display:block}
#cronograma-app .current-chip{display:inline-block;margin-top:10px}
#cronograma-app .activity{grid-template-columns:42px minmax(0,1fr)}
#cronograma-app .activity-progress{grid-column:2;text-align:left}}
@media (max-width:480px){#cronograma-app .kpis{grid-template-columns:1fr}
#cronograma-app .progress-kpi{grid-column:auto}
#cronograma-app .summary{grid-template-columns:1fr}
#cronograma-app .submenu{min-width:190px}}
/* Destaque do grupo selecionado na linha do tempo */
#cronograma-app .step{
  border-radius:18px;
  padding-top:14px;
  padding-bottom:16px;
  transition:
    background .22s ease,
    border-color .22s ease,
    box-shadow .22s ease,
    transform .22s ease,
    opacity .22s ease;
  border:2px solid transparent;
}
#cronograma-app .step:not(.selected){
  opacity:.88;
}
#cronograma-app .step.selected{
  background:linear-gradient(180deg,#eef4ff 0%,#e4edff 100%);
  border-color:#2f6de0;
  box-shadow:0 16px 34px rgba(31,94,213,.18);
  transform:translateY(-4px);
  opacity:1;
}
#cronograma-app .step.selected .node{
  width:60px;
  height:60px;
  border-color:#1f5ed5;
  background:#fff;
  color:#1f5ed5;
  box-shadow:0 0 0 9px rgba(31,94,213,.12),0 12px 26px rgba(31,94,213,.22);
}
#cronograma-app .step.selected.completed .node{
  background:var(--done);
  border-color:var(--done);
  color:#fff;
}
#cronograma-app .step.selected .step-name{
  color:#173d86;
}
#cronograma-app .step.selected .step-date{
  color:#405f92;
}
#cronograma-app .step.selected .badge{
  background:#1f5ed5;
  color:#fff;
}
#cronograma-app .step.selected.completed .badge{
  background:var(--done);
  color:#fff;
}
#cronograma-app .step.selected.late .badge{
  background:var(--danger);
  color:#fff;
}
#cronograma-app .step.selected:before{
  background:#1f5ed5;
}
#cronograma-app .step.selected + .step:before{
  background:#1f5ed5;
}
@media (max-width:760px){#cronograma-app .step.selected{
    transform:translateY(-2px);
  }}
/* Correção das conexões da linha do tempo */
#cronograma-app .timeline{
  position:relative;
  align-items:stretch;
  gap:8px;
}
#cronograma-app .step{
  position:relative;
  z-index:1;
}
/* Remove as ligações anteriores, que usavam metade do card */
#cronograma-app .step:before,#cronograma-app .step.selected:before,#cronograma-app .step.selected + .step:before{
  content:none !important;
}
/* Cada grupo desenha uma única linha, do centro do seu círculo
   até o centro do círculo do próximo grupo */
#cronograma-app .step:after{
  content:"";
  position:absolute;
  z-index:0;
  left:50%;
  top:39px;
  width:calc(100% + 8px);
  height:5px;
  border-radius:999px;
  background:#dce5ef;
  pointer-events:none;
}
#cronograma-app .step:last-child:after{
  display:none;
}
/* Mantém as conexões no mesmo eixo e diferencia o avanço */
#cronograma-app .step.completed:after{
  background:var(--done);
}
#cronograma-app .step.current:after,#cronograma-app .step.selected:after{
  background:var(--primary);
}
/* Os círculos ficam sempre acima das linhas */
#cronograma-app .node{
  position:relative;
  z-index:3;
}
/* O card selecionado não desloca verticalmente a geometria */
#cronograma-app .step.selected{
  transform:none;
}
#cronograma-app .step.selected:hover{
  transform:none;
}
/* Compensa o círculo maior do grupo selecionado sem alterar o eixo central */
#cronograma-app .step.selected .node{
  width:60px;
  height:60px;
  margin-top:-3px;
  margin-bottom:-3px;
}
@media (max-width:760px){#cronograma-app .step.selected{
    transform:none;
  }}
#cronograma-app .loading-overlay,#cronograma-app .error-overlay{
  position:fixed;
  inset:0;
  z-index:9999;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:24px;
  background:rgba(242,245,249,.96);
}
#cronograma-app .loading-card,#cronograma-app .error-card{
  width:min(620px,100%);
  background:#fff;
  border:1px solid var(--line);
  border-radius:20px;
  box-shadow:0 20px 55px rgba(25,48,82,.16);
  padding:30px;
  text-align:center;
}
#cronograma-app .loading-spinner{
  width:54px;
  height:54px;
  margin:0 auto 18px;
  border:6px solid #e4eaf2;
  border-top-color:var(--primary);
  border-radius:50%;
  animation:spin .9s linear infinite;
}
@keyframes spin{to{transform:rotate(360deg)}}
#cronograma-app .loading-card h2,#cronograma-app .error-card h2{margin:0 0 10px;font-size:24px}
#cronograma-app .loading-card p,#cronograma-app .error-card p{margin:0;color:var(--muted);line-height:1.6}
#cronograma-app .error-icon{
  width:62px;height:62px;margin:0 auto 18px;border-radius:50%;
  display:grid;place-items:center;background:#ffe8e8;color:var(--danger);
  font-size:30px;font-weight:950;
}
#cronograma-app .error-file{
  display:inline-block;margin-top:15px;padding:9px 12px;border-radius:10px;
  background:#f2f5f9;color:#24334b;font-family:Consolas,monospace;font-size:13px;
}
#cronograma-app .error-help{margin-top:16px!important;font-size:13px}
#cronograma-app .hidden{display:none!important}
/* ===== Responsividade móvel aprimorada — preserva o desktop ===== */
@media (max-width: 760px){#cronograma-app,#cronograma-app{max-width:100%;overflow-x:hidden}
#cronograma-app .full{padding-left:10px;padding-right:10px}
#cronograma-app .brand-header{
    grid-template-columns:1fr 1fr;
    gap:10px;
    padding:12px 10px;
  }
#cronograma-app .brand-title{grid-column:1/-1;grid-row:1;padding:2px 0 4px}
#cronograma-app .brand-title h1{font-size:24px;line-height:1.14}
#cronograma-app .brand-title p{font-size:12px;line-height:1.4}
#cronograma-app .brand-box{height:56px;min-width:0;padding:7px 10px}
#cronograma-app .brand-box img{max-width:100%;max-height:42px}
#cronograma-app .hero{padding:20px 0 78px}
#cronograma-app .hero-inner{display:block}
#cronograma-app .hero h2{font-size:27px;line-height:1.16}
#cronograma-app .hero p{font-size:13px;line-height:1.45}
#cronograma-app .cronograma-main{margin-top:-58px}
#cronograma-app .timeline-panel{padding:18px 12px 12px}
#cronograma-app .heading{display:block;margin-bottom:10px}
#cronograma-app .heading h2{font-size:20px;line-height:1.3}
#cronograma-app .heading p{font-size:12px;line-height:1.45}
#cronograma-app .current-chip{display:inline-flex;white-space:normal;line-height:1.35;margin-top:10px;max-width:100%}
#cronograma-app .timeline{padding:14px 2px 22px;gap:6px}
#cronograma-app .step{flex:0 0 205px;min-width:205px;padding-left:10px;padding-right:10px}
#cronograma-app .step-name{font-size:14px;line-height:1.42;min-height:unset}
#cronograma-app .step-date{font-size:10.5px}
#cronograma-app .kpis{grid-template-columns:1fr 1fr;gap:9px}
#cronograma-app .kpi{min-width:0;min-height:108px;padding:15px}
#cronograma-app .progress-kpi{grid-column:1/-1}
#cronograma-app .kpi-value{font-size:25px}
#cronograma-app .layout{display:block;margin-top:10px}
#cronograma-app .detail{padding:16px 12px}
#cronograma-app .phase-head{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;padding-bottom:14px}
#cronograma-app .phase-head h2{font-size:20px;line-height:1.35;overflow-wrap:anywhere}
#cronograma-app .phase-head p{font-size:12px;line-height:1.45}
#cronograma-app .phase-value strong{font-size:26px}
#cronograma-app .submenu-title{margin-top:18px;font-size:11px;line-height:1.4}
#cronograma-app .submenu-list{
    display:grid;
    grid-template-columns:1fr;
    gap:9px;
    overflow-x:visible;
    width:100%;
    padding:2px 0 10px;
  }
#cronograma-app .submenu{
    width:100%;
    min-width:0;
    max-width:none;
    padding:14px;
    overflow:hidden;
  }
#cronograma-app .submenu-name{
    font-size:14px;
    line-height:1.45;
    overflow-wrap:anywhere;
    word-break:normal;
  }
#cronograma-app .submenu-meta{
    font-size:11px;
    line-height:1.45;
    overflow-wrap:anywhere;
  }
#cronograma-app .submenu-progress{width:100%;min-width:0}
#cronograma-app .submenu-mini{min-width:0}
#cronograma-app .activity-grid{display:grid;grid-template-columns:1fr;gap:10px;margin-top:12px;min-width:0}
#cronograma-app .activity{
    width:100%;
    min-width:0;
    min-height:0;
    padding:15px;
    display:grid;
    grid-template-columns:40px minmax(0,1fr);
    gap:12px;
    align-items:start;
    overflow:hidden;
  }
#cronograma-app .icon{width:40px;height:40px;font-size:16px}
#cronograma-app .activity > div:nth-child(2){min-width:0}
#cronograma-app .activity-name{
    font-size:15px;
    line-height:1.48;
    overflow-wrap:anywhere;
    word-break:normal;
  }
#cronograma-app .meta{
    font-size:11.5px;
    line-height:1.45;
    overflow-wrap:anywhere;
  }
#cronograma-app .activity-dates{
    display:grid;
    grid-template-columns:1fr;
    gap:5px;
    margin-top:9px;
    font-size:12px;
    min-width:0;
  }
#cronograma-app .activity-dates span{min-width:0;white-space:normal;overflow-wrap:anywhere}
#cronograma-app .activity-progress{
    grid-column:2;
    width:100%;
    min-width:0;
    text-align:left;
    padding-top:2px;
  }
#cronograma-app .activity-progress b{font-size:16px}
#cronograma-app .mini{width:100%;height:9px;margin-top:7px}
#cronograma-app .side{position:static;margin-top:10px;padding:16px 12px}
#cronograma-app .summary{grid-template-columns:1fr 1fr;gap:8px}
#cronograma-app .summary-item{min-width:0;padding:11px}
#cronograma-app .summary-top{font-size:10px;line-height:1.3}
#cronograma-app .nav{display:grid;grid-template-columns:1fr 1fr}
#cronograma-app .nav button{min-width:0}}
@media (max-width: 430px){#cronograma-app .brand-title h1{font-size:21px}
#cronograma-app .hero h2{font-size:24px}
#cronograma-app .kpis{grid-template-columns:1fr}
#cronograma-app .progress-kpi{grid-column:auto}
#cronograma-app .phase-head{grid-template-columns:1fr}
#cronograma-app .phase-value{text-align:left;display:flex;align-items:baseline;gap:7px}
#cronograma-app .summary{grid-template-columns:1fr}
#cronograma-app .activity{grid-template-columns:36px minmax(0,1fr);padding:13px 12px;gap:10px}
#cronograma-app .icon{width:36px;height:36px}
#cronograma-app .submenu{padding:12px}}
#cronograma-app{position:relative;width:100%;border-radius:1rem;overflow:hidden;background:var(--bg);}
#cronograma-app .loading-overlay,#cronograma-app .error-overlay{position:absolute;min-height:620px;border-radius:1rem;}
#cronograma-app .hero{border-radius:1rem 1rem 0 0;}
#cronograma-app .cronograma-main{padding-top:0;}
#cronograma-app .cronograma-footer{text-align:center;padding:23px;font-size:10.5px;color:var(--muted);}

</style>
<div class="loading-overlay" id="loadingOverlay">
  <div class="loading-card">
    <div class="loading-spinner"></div>
    <h2>Carregando cronograma</h2>
    <p>Leitura dinâmica do arquivo XML em andamento.</p>
  </div>
</div>
<div class="error-overlay hidden" id="errorOverlay">
  <div class="error-card">
    <div class="error-icon">!</div>
    <h2>Não foi possível carregar o cronograma</h2>
    <p id="errorMessage">O arquivo XML não foi encontrado.</p>
    <span class="error-file">cronograma.xml</span>
    <p class="error-help">Confirme que o arquivo está na mesma pasta do painel e mantenha exatamente esse nome.</p>
  </div>
</div>
<header class="hero"><div class="full hero-inner"><div><div class="kicker">Painel executivo de implantação</div><h2 id="title"></h2><p>Grupos principais, subetapas e atividades do cronograma atualizado.</p></div><div class="date-position" id="datePosition"></div></div></header>
<div class="full cronograma-main">
 <section class="panel timeline-panel">
  <div class="heading"><div><h2>Linha do tempo — grupos principais</h2><p>Clique em um grupo principal para abrir suas subetapas.</p></div><div class="current-chip" id="currentChip"></div></div>
  <div class="timeline" id="timeline"></div>
 </section>
 <section class="kpis">
  <article class="panel kpi progress-kpi"><div class="ring" id="ring"><span id="overall"></span></div><div><div class="kpi-label">Conclusão por grupos</div><div class="kpi-value" id="overallValue"></div><div class="kpi-note" id="phaseCompletionNote"></div></div></article>
  <article class="panel kpi"><div class="kpi-label">Início</div><div class="kpi-value" id="start" style="font-size:20px"></div><div class="kpi-note">Início planejado</div></article>
  <article class="panel kpi"><div class="kpi-label">Previsão final</div><div class="kpi-value" id="finish" style="font-size:20px"></div><div class="kpi-note">Encerramento previsto</div></article>
  <article class="panel kpi"><div class="kpi-label">Atividades</div><div class="kpi-value" id="activitiesCount"></div><div class="kpi-note">Atividades finais do cronograma</div></article>
  <article class="panel kpi"><div class="kpi-label">Concluídas</div><div class="kpi-value" id="completed"></div><div class="kpi-note">Atividades executadas em 100%</div></article>
 </section>
 <section class="layout">
  <article class="panel detail">
   <div class="phase-head"><div><h2 id="phaseName"></h2><p id="phaseDates"></p></div><div class="phase-value"><strong id="phasePercent"></strong><div class="kpi-note">concluído</div></div></div>
   <div class="bar"><span id="phaseBar"></span></div>
   <div class="submenu-title">Subetapas do grupo selecionado</div>
   <div class="submenu-list" id="submenuList"></div>
   <div class="submenu-title" id="activityTitle">Atividades</div>
   <div class="activity-grid" id="activityGrid"></div>
  </article>
  <aside class="panel side">
   <h3>Resumo geral</h3>
   <div class="summary">
    <div class="summary-item"><div class="summary-top"><span class="dot" style="background:var(--done)"></span>Concluídas</div><b id="sumDone"></b></div>
    <div class="summary-item"><div class="summary-top"><span class="dot" style="background:var(--warning)"></span>Em andamento</div><b id="sumRun"></b></div>
    <div class="summary-item"><div class="summary-top"><span class="dot" style="background:var(--danger)"></span>Atrasadas</div><b id="sumLate"></b></div>
    <div class="summary-item"><div class="summary-top"><span class="dot" style="background:var(--idle)"></span>Não iniciadas</div><b id="sumIdle"></b></div>
   </div>
   <div class="alert" id="alert"></div>
   <div class="nav"><button id="prev">← Anterior</button><button class="primary" id="next">Próximo →</button></div>
  </aside>
 </section>
</div>
<div class="cronograma-footer">Dados atualizados a partir do arquivo Microsoft Project XML v4.</div>
<script>
(() => {

const XML_FILE = 'acompanhamento_implantacao.php?xml=1&v=<?php echo $cronogramaVersion; ?>';
let DATA = null;
let selectedGroup = 0;
let selectedSub = 0;

const $ = id => document.getElementById(id);
const fmt = s => s ? new Intl.DateTimeFormat('pt-BR', {
  day: '2-digit', month: 'short', year: 'numeric'
}).format(new Date(s)).replace('.', '') : '—';
const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({
  '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
}[m]));
const clean = s => String(s || '').replace(/^\d+(?:\.\d+)*\.\s*/, '');

function directChild(node, localName) {
  return [...node.children].find(el => el.localName === localName) || null;
}
function directText(node, localName, fallback = '') {
  const el = directChild(node, localName);
  return el && el.textContent != null ? el.textContent.trim() : fallback;
}
function toInt(value, fallback = 0) {
  const parsed = Number.parseInt(value, 10);
  return Number.isFinite(parsed) ? parsed : fallback;
}
function statusFor(percent, start, finish, currentDate, isCurrent = false) {
  if (percent >= 100) return 'Concluído';
  if (isCurrent || percent > 0) return 'Em andamento';
  if (currentDate && finish && finish < currentDate) return 'Atrasado';
  return 'Não iniciado';
}
function leafDescendants(node) {
  const result = [];
  const walk = item => {
    if (!item.children.length) result.push(item);
    else item.children.forEach(walk);
  };
  walk(node);
  return result;
}

function parseProjectXml(xmlText) {
  const xml = new DOMParser().parseFromString(xmlText, 'application/xml');
  if (xml.querySelector('parsererror')) {
    throw new Error('O arquivo cronograma.xml existe, porém contém um XML inválido ou corrompido.');
  }

  const project = xml.documentElement;
  if (!project || project.localName !== 'Project') {
    throw new Error('O arquivo encontrado não possui a estrutura esperada do Microsoft Project.');
  }

  const currentDate = directText(project, 'CurrentDate');
  const title = directText(project, 'Title', 'Implantação WinThor ERP/WMS — Comercial Souza');
  const lastSaved = directText(project, 'LastSaved');
  const tasksContainer = directChild(project, 'Tasks');
  if (!tasksContainer) throw new Error('O XML não contém a seção Tasks do Microsoft Project.');

  const taskElements = [...tasksContainer.children].filter(el => el.localName === 'Task');
  if (!taskElements.length) throw new Error('Nenhuma tarefa foi encontrada no cronograma XML.');

  const tasks = taskElements.map(el => ({
    uid: toInt(directText(el, 'UID')),
    id: toInt(directText(el, 'ID')),
    name: directText(el, 'Name', 'Sem nome'),
    wbs: directText(el, 'WBS'),
    level: toInt(directText(el, 'OutlineLevel')),
    summary: directText(el, 'Summary') === '1',
    start: directText(el, 'Start') || null,
    finish: directText(el, 'Finish') || null,
    percent: Math.max(0, Math.min(100, toInt(directText(el, 'PercentComplete')))),
    milestone: directText(el, 'Milestone') === '1',
    critical: directText(el, 'Critical') === '1',
    notes: directText(el, 'Notes'),
    children: []
  }));

  const stack = [];
  tasks.forEach(task => {
    while (stack.length && stack[stack.length - 1].level >= task.level) stack.pop();
    if (stack.length) stack[stack.length - 1].children.push(task);
    stack.push(task);
  });

  const rootTask = tasks.find(t => t.uid === 0) || tasks[0];
  const projectGroup = tasks.find(t => t.level === 1);
  const mainGroups = projectGroup
    ? projectGroup.children.filter(t => t.level === 2)
    : tasks.filter(t => t.level === 2);
  if (!mainGroups.length) throw new Error('Não foram encontrados grupos principais (OutlineLevel 2) no XML.');

  const uniqueLeaves = new Map();
  const groups = mainGroups.map(group => {
    const groupCurrent = Boolean(
      currentDate && group.start && group.finish &&
      group.start <= currentDate && currentDate <= group.finish && group.percent < 100
    );

    const submenus = group.children.map(child => {
      const leaves = child.children.length ? leafDescendants(child) : [child];
      const activities = leaves.map(activity => {
        const activityCurrent = Boolean(
          currentDate && activity.start && activity.finish &&
          activity.start <= currentDate && currentDate <= activity.finish && activity.percent < 100
        );
        const item = {
          uid: activity.uid,
          id: activity.id,
          name: activity.name,
          wbs: activity.wbs,
          start: activity.start,
          finish: activity.finish,
          percent: activity.percent,
          status: statusFor(activity.percent, activity.start, activity.finish, currentDate, activityCurrent),
          milestone: activity.milestone,
          critical: activity.critical,
          notes: activity.notes
        };
        uniqueLeaves.set(activity.uid, item);
        return item;
      });

      const submenuCurrent = activities.some(a => a.status === 'Em andamento');
      return {
        uid: child.uid,
        name: child.name,
        wbs: child.wbs,
        start: child.start,
        finish: child.finish,
        percent: child.percent,
        status: statusFor(child.percent, child.start, child.finish, currentDate, submenuCurrent),
        isCurrent: submenuCurrent,
        activities
      };
    });

    return {
      uid: group.uid,
      name: group.name,
      wbs: group.wbs,
      start: group.start,
      finish: group.finish,
      percent: group.percent,
      status: statusFor(group.percent, group.start, group.finish, currentDate, groupCurrent),
      isCurrent: groupCurrent,
      submenus
    };
  });

  const leaves = [...uniqueLeaves.values()];
  const completedGroups = groups.filter(g => g.percent >= 100).length;
  return {
    title,
    currentDate,
    lastSaved,
    project: {
      start: rootTask.start,
      finish: rootTask.finish,
      sourcePercent: rootTask.percent,
      percent: Math.round((completedGroups / groups.length) * 100),
      activities: leaves.length,
      completed: leaves.filter(a => a.percent >= 100).length,
      inProgress: leaves.filter(a => a.status === 'Em andamento').length,
      late: leaves.filter(a => a.status === 'Atrasado').length,
      notStarted: leaves.filter(a => a.status === 'Não iniciado').length,
      completedPhases: completedGroups,
      totalPhases: groups.length
    },
    groups
  };
}

function stepClass(g) {
  if (g.percent >= 100) return 'completed';
  if (g.isCurrent) return 'current';
  if (g.status === 'Atrasado') return 'late';
  return '';
}
function renderTimeline() {
  $('timeline').innerHTML = DATA.groups.map((g, i) => `<div class="step ${stepClass(g)} ${i === selectedGroup ? 'selected' : ''}" data-i="${i}">
    <div class="node">${g.percent >= 100 ? '✓' : g.percent + '%'}</div>
    <div class="step-name">${esc(clean(g.name))}</div>
    <div class="step-date">${fmt(g.start)} — ${fmt(g.finish)}</div>
    <span class="badge">${g.isCurrent ? 'MOMENTO ATUAL' : g.status.toUpperCase()}</span>
  </div>`).join('');

  document.querySelectorAll('.step').forEach(el => el.addEventListener('click', () => {
    selectedGroup = Number(el.dataset.i);
    selectedSub = 0;
    renderAll();
  }));
  setTimeout(() => document.querySelector('.step.selected')?.scrollIntoView({
    behavior: 'smooth', block: 'nearest', inline: 'center'
  }), 20);
}
function submenuClass(s) {
  if (s.percent >= 100) return 'completed';
  if (s.isCurrent) return 'current';
  if (s.status === 'Atrasado') return 'late';
  return '';
}
function renderSubmenus() {
  const group = DATA.groups[selectedGroup];
  $('submenuList').innerHTML = group.submenus.length ? group.submenus.map((s, i) => `<button class="submenu ${submenuClass(s)} ${i === selectedSub ? 'selected' : ''}" data-si="${i}">
    <div class="submenu-name">${esc(clean(s.name))}</div>
    <div class="submenu-meta">${fmt(s.start)} — ${fmt(s.finish)} · ${s.activities.length} atividade(s)</div>
    <div class="submenu-progress"><span>${s.percent}%</span><div class="submenu-mini"><i style="width:${s.percent}%"></i></div></div>
  </button>`).join('') : '<div class="empty">Este grupo não possui subetapas cadastradas.</div>';

  document.querySelectorAll('.submenu').forEach(el => el.addEventListener('click', () => {
    selectedSub = Number(el.dataset.si);
    renderSubmenus();
    renderActivities();
  }));
}
function klass(status) {
  return status === 'Concluído' ? 'done' : status === 'Em andamento' ? 'run' : status === 'Atrasado' ? 'late' : 'idle';
}
function icon(a) {
  return a.milestone ? '◆' : a.percent >= 100 ? '✓' : a.percent > 0 ? '↻' : a.status === 'Atrasado' ? '!' : '•';
}
function renderActivities() {
  const group = DATA.groups[selectedGroup];
  const sub = group.submenus[selectedSub];
  if (!sub) {
    $('activityTitle').textContent = 'Atividades';
    $('activityGrid').innerHTML = '<div class="empty">Nenhuma atividade disponível.</div>';
    return;
  }

  $('activityTitle').textContent = 'Atividades — ' + clean(sub.name);
  const ordered = [...sub.activities].sort((a, b) => a.percent - b.percent || a.name.localeCompare(b.name, 'pt-BR'));
  $('activityGrid').innerHTML = ordered.length ? ordered.map(a => `<div class="activity ${klass(a.status)}">
    <div class="icon">${icon(a)}</div>
    <div><div class="activity-name">${esc(a.name)}</div>
      <div class="meta">${esc(a.wbs)}${a.wbs ? ' • ' : ''}${a.status}${a.critical ? ' • Crítica' : ''}${a.milestone ? ' • Marco' : ''}</div>
      <div class="activity-dates"><span><strong>Início</strong> ${fmt(a.start)}</span><span><strong>Término</strong> ${fmt(a.finish)}</span></div>
    </div>
    <div class="activity-progress"><b>${a.percent}%</b><div class="mini"><i style="width:${a.percent}%"></i></div></div>
  </div>`).join('') : '<div class="empty">Nenhuma atividade final vinculada a esta subetapa.</div>';
}
function renderHeader() {
  const g = DATA.groups[selectedGroup];
  $('phaseName').textContent = g.name;
  $('phaseDates').textContent = `${fmt(g.start)} — ${fmt(g.finish)} • ${g.submenus.length} subetapas`;
  $('phasePercent').textContent = g.percent + '%';
  $('phaseBar').style.width = g.percent + '%';
  const current = DATA.groups.find(x => x.isCurrent);
  $('currentChip').textContent = 'Grupo atual: ' + clean(current?.name || g.name);
  const late = g.submenus.flatMap(s => s.activities).filter(a => a.status === 'Atrasado').length;
  $('alert').innerHTML = g.isCurrent
    ? `<strong>Momento atual do projeto.</strong><br>Este grupo coincide com a data de posição.${late ? ` Há ${late} atividade(s) atrasada(s) neste grupo.` : ''}`
    : late ? `<strong>Atenção:</strong> existem ${late} atividade(s) vencida(s) e ainda não concluídas neste grupo.`
    : g.percent >= 100 ? 'Grupo principal integralmente concluído.' : 'Grupo planejado conforme as datas exibidas.';
  $('prev').disabled = selectedGroup === 0;
  $('next').disabled = selectedGroup === DATA.groups.length - 1;
}
function renderKpis() {
  $('title').textContent = DATA.title;
  $('datePosition').textContent = 'Posição: ' + fmt(DATA.currentDate);
  $('ring').style.setProperty('--value', DATA.project.percent);
  $('overall').textContent = DATA.project.percent + '%';
  $('overallValue').textContent = DATA.project.percent + '%';
  $('start').textContent = fmt(DATA.project.start);
  $('finish').textContent = fmt(DATA.project.finish);
  $('activitiesCount').textContent = DATA.project.activities;
  $('completed').textContent = DATA.project.completed;
  $('phaseCompletionNote').textContent = `${DATA.project.completedPhases} de ${DATA.project.totalPhases} grupos principais concluídos`;
  $('sumDone').textContent = DATA.project.completed;
  $('sumRun').textContent = DATA.project.inProgress;
  $('sumLate').textContent = DATA.project.late;
  $('sumIdle').textContent = DATA.project.notStarted;
}
function renderAll() {
  renderTimeline();
  renderHeader();
  renderSubmenus();
  renderActivities();
}
function showError(message) {
  $('loadingOverlay').classList.add('hidden');
  $('errorMessage').textContent = message;
  $('errorOverlay').classList.remove('hidden');
}

$('prev').addEventListener('click', () => {
  if (selectedGroup > 0) { selectedGroup--; selectedSub = 0; renderAll(); }
});
$('next').addEventListener('click', () => {
  if (selectedGroup < DATA.groups.length - 1) { selectedGroup++; selectedSub = 0; renderAll(); }
});

async function initialize() {
  try {
    const response = await fetch(XML_FILE, { cache: 'no-store' });
    if (!response.ok) {
      throw new Error(`O arquivo ${XML_FILE} não foi encontrado na pasta do painel (erro HTTP ${response.status}).`);
    }
    DATA = parseProjectXml(await response.text());
    selectedGroup = Math.max(0, DATA.groups.findIndex(g => g.isCurrent));
    selectedSub = 0;
    renderKpis();
    renderAll();
    $('loadingOverlay').classList.add('hidden');
  } catch (error) {
    console.error(error);
    showError(error.message || 'Erro inesperado ao processar o cronograma XML.');
  }
}
initialize();

})();
</script>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
