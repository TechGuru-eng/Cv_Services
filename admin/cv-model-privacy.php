<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/cv-image-privacy.php';
header('Cache-Control: no-store');
$privacyBeforeAfter = isset($privacyBeforeAfter) && $privacyBeforeAfter === true;
$side = ($_GET['side'] ?? $_POST['side'] ?? 'before') === 'after' ? 'after' : 'before';
$table = $privacyBeforeAfter ? 'before_after_cvs' : 'cv_designs';
$imageColumn = $privacyBeforeAfter ? ($side === 'after' ? 'after_img' : 'before_img') : 'image_path';
$titleColumn = $privacyBeforeAfter ? 'category' : 'title';
$imageFolder = $privacyBeforeAfter ? 'uploads/before_after' : 'uploads/designs';
$returnPage = $privacyBeforeAfter ? 'before-after.php' : 'cv-models.php';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$selectImage = "SELECT *, `$imageColumn` AS image_path, `$titleColumn` AS title FROM `$table` WHERE id=?";
$stmt = $pdo->prepare($selectImage); $stmt->execute([$id]); $design=$stmt->fetch();
if (!$design) { http_response_code(404); exit('Modèle introuvable.'); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $newFile=null; $backup=null; $source=null; $replaced=false;
    try {
        $pdo->beginTransaction();
        $stmt=$pdo->prepare($selectImage . ' FOR UPDATE'); $stmt->execute([$id]); $design=$stmt->fetch();
        if (!$design || ($_POST['source_version'] ?? '') !== hash('sha256',$design['image_path'])) throw new RuntimeException('Le modèle a changé. Rechargez la page.');
        $root=realpath(__DIR__ . '/..');
        $source=realpath($root . '/' . $design['image_path']);
        $allowed=realpath($root . '/' . $imageFolder);
        if (!$source || !$allowed || !str_starts_with($source,$allowed . DIRECTORY_SEPARATOR)) throw new RuntimeException('Cette image doit se trouver dans son dossier de téléchargement autorisé.');
        $boxes=json_decode($_POST['boxes'] ?? '', true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($boxes)) throw new RuntimeException('Zones invalides.');
        $private=$root . '/_ops/cv-originals';
        if (!is_dir($private) && !mkdir($private,0700,true)) throw new RuntimeException('Impossible de créer le dossier privé.');
        if (file_put_contents($private . '/.htaccess', "Require all denied\n")===false) throw new RuntimeException('Impossible de protéger les originaux.');
        $backup=$private . '/' . $table . '-' . $imageColumn . '-' . $id . '-' . bin2hex(random_bytes(16)) . '.original';
        if (!copy($source,$backup)) throw new RuntimeException('Impossible de sauvegarder l’original en privé.');
        chmod($backup,0600);
        $path=$imageFolder . '/masked-' . bin2hex(random_bytes(16)) . '.png';
        $newFile=$root . '/' . $path;
        maskCvImage($source,$boxes,$newFile);
        // Replace the old URL too: visitors must not retrieve the unmasked original.
        if (!copy($newFile,$source)) throw new RuntimeException('Impossible de remplacer l’ancienne image publique.');
        $replaced=true;
        $pdo->prepare("UPDATE `$table` SET `$imageColumn`=? WHERE id=?")->execute([$path,$id]);
        $pdo->commit();
        header('Location: ' . $returnPage . '?masked=1'); exit;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($replaced && $backup && $source) copy($backup,$source);
        if ($newFile && is_file($newFile)) unlink($newFile);
        error_log('CV masking failed: ' . $exception->getMessage());
        $error=$exception instanceof PDOException ? 'Impossible de mettre à jour le modèle.' : $exception->getMessage();
    }
}
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Masquer les informations du CV</title>
<style>body{font:16px system-ui;margin:0;background:#f1f5f9;color:#172033}main{max-width:1100px;margin:auto;padding:20px}button,a{padding:10px 14px}button{cursor:pointer;border:1px solid #cbd5e1;border-radius:8px;background:white}button:disabled{opacity:.5;cursor:default}.toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;position:sticky;top:0;background:#f1f5f9;padding:12px 0;z-index:2}canvas{display:block;width:100%;height:auto;background:white;touch-action:none;cursor:crosshair;box-shadow:0 3px 20px #0002}.publish{background:#2563eb;color:white}.error{background:#fee2e2;padding:15px}.hint{color:#475569;font-size:14px}</style></head><body><main>
<a href="<?= $returnPage ?>">← Retour à la galerie</a><h1>Masquer les informations personnelles<?= $privacyBeforeAfter ? ($side === 'after' ? ' · CV après' : ' · CV avant') : '' ?></h1><p><?= htmlspecialchars($design['title']) ?></p>
<p>Passez le stylo sur les noms, emails et numéros à cacher. Choisissez une couleur et une épaisseur adaptées. Les zones noires seront enregistrées dans l’image publiée.</p>
<?php if($error): ?><p class="error"><?= htmlspecialchars($error,ENT_QUOTES,'UTF-8') ?></p><?php endif; ?>
<form method="post" id="mask-form"><?php csrf_field(); ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="side" value="<?= $side ?>"><input type="hidden" name="source_version" value="<?= hash('sha256',$design['image_path']) ?>"><input type="hidden" name="boxes" id="boxes">
<div class="toolbar"><label>Outil <select id="tool"><option value="pen">Stylo</option><option value="box">Rectangle</option></select></label><label>Couleur <input id="color" type="color" value="#ffffff"></label><label>Épaisseur <input id="width" type="range" min="4" max="80" value="20"><span id="width-label">20 px</span></label><button type="button" id="undo">Annuler la dernière zone</button><button type="button" id="clear">Effacer les zones</button><button type="button" id="preview">Aperçu</button><span id="count" role="status">Chargement…</span><button type="submit" class="publish" id="publish" disabled>Publier l’image masquée</button></div>
<p class="hint">Vérifiez tout le CV avant de publier. Les zones ne peuvent pas être retirées depuis la galerie publique. Une copie privée est conservée. Pensez aussi à modifier le titre du modèle s’il contient un nom.</p>
<canvas id="cv" aria-label="Image du CV : dessinez les zones à masquer"></canvas></form></main>
<script>
const canvas=document.getElementById('cv'),ctx=canvas.getContext('2d'),image=new Image();let boxes=[],start=null,draft=null,preview=false;
const publish=document.getElementById('publish'),tool=document.getElementById('tool'),color=document.getElementById('color'),width=document.getElementById('width');
width.oninput=()=>document.getElementById('width-label').textContent=width.value+' px';
function paint(){
 ctx.clearRect(0,0,canvas.width,canvas.height);ctx.drawImage(image,0,0);
 for(const mark of [...boxes,...(draft?[draft]:[])]){
  ctx.fillStyle=mark.color;ctx.strokeStyle=mark.color;
  if(mark.type==='pen'){
   const radius=mark.width*canvas.width/2;ctx.lineWidth=radius*2;ctx.lineCap='round';ctx.lineJoin='round';
   ctx.beginPath();mark.points.forEach((p,i)=>{if(i===0)ctx.moveTo(p.x*canvas.width,p.y*canvas.height);else ctx.lineTo(p.x*canvas.width,p.y*canvas.height);});ctx.stroke();
   for(const p of mark.points){ctx.beginPath();ctx.arc(p.x*canvas.width,p.y*canvas.height,radius,0,Math.PI*2);ctx.fill();}
  }else ctx.fillRect(mark.x*canvas.width,mark.y*canvas.height,mark.w*canvas.width,mark.h*canvas.height);
 }
 document.getElementById('count').textContent=boxes.length+' trait(s) / zone(s)';publish.disabled=!boxes.length;
 for(const id of ['undo','clear'])document.getElementById(id).disabled=!boxes.length||preview;
}
function point(event){const r=canvas.getBoundingClientRect();return {x:Math.max(0,Math.min(1,(event.clientX-r.left)/r.width)),y:Math.max(0,Math.min(1,(event.clientY-r.top)/r.height))};}
image.onload=()=>{canvas.width=image.naturalWidth;canvas.height=image.naturalHeight;paint();};image.onerror=()=>{document.getElementById('count').textContent='Impossible de charger l’image.';};
image.src=<?php echo json_encode('../'.$design['image_path'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
canvas.onpointerdown=e=>{
 if(preview||start||!image.complete||!image.naturalWidth||boxes.length>=100)return;
 start=point(e);canvas.setPointerCapture(e.pointerId);
 const thickness=Math.min(.25,Number(width.value)/canvas.getBoundingClientRect().width);
 draft=tool.value==='pen'?{type:'pen',color:color.value,width:thickness,points:[start]}:{type:'box',color:color.value,x:start.x,y:start.y,w:0,h:0};paint();
};
canvas.onpointermove=e=>{
 if(!start)return;const p=point(e);
 if(draft.type==='pen'){if(draft.points.length<2000)draft.points.push(p);}
 else Object.assign(draft,{x:Math.min(start.x,p.x),y:Math.min(start.y,p.y),w:Math.abs(p.x-start.x),h:Math.abs(p.y-start.y)});
 paint();
};
canvas.onpointerup=()=>{if(draft&&(draft.type==='pen'||(draft.w*canvas.width>=2&&draft.h*canvas.height>=2)))boxes.push(draft);start=null;draft=null;paint();};
canvas.onpointercancel=()=>{start=null;draft=null;paint();};
document.getElementById('undo').onclick=()=>{boxes.pop();paint();};document.getElementById('clear').onclick=()=>{boxes=[];paint();};
document.getElementById('preview').onclick=e=>{preview=!preview;e.target.textContent=preview?'Continuer à masquer':'Aperçu';canvas.style.cursor=preview?'default':'crosshair';paint();};
document.getElementById('mask-form').onsubmit=()=>{if(!boxes.length)return false;document.getElementById('boxes').value=JSON.stringify(boxes);publish.disabled=true;publish.textContent='Publication…';};
</script></body></html>
