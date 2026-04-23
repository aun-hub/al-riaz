<?php
/**
 * Al-Riaz Associates — Inquiries Management
 */
$pageTitle = 'Inquiries';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = Database::getInstance();

// ── AJAX Handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $inqId  = (int)($_POST['inquiry_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['new','assigned','contacted','qualified','closed_won','closed_lost'];
        if (!in_array($status, $allowed)) { echo json_encode(['ok'=>false,'msg'=>'Invalid status']); exit; }
        try {
            $db->prepare("UPDATE inquiries SET status=?, updated_at=NOW() WHERE id=?")->execute([$status, $inqId]);
            auditLog('update_status','inquiries',$inqId,'Status changed to: '.$status);
            echo json_encode(['ok'=>true,'msg'=>'Status updated.']);
        } catch(Exception $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    if ($action === 'assign_agent') {
        $inqId   = (int)($_POST['inquiry_id'] ?? 0);
        $agentId = (int)($_POST['agent_id'] ?? 0) ?: null;
        try {
            $db->prepare("UPDATE inquiries SET assigned_to=?, updated_at=NOW() WHERE id=?")->execute([$agentId, $inqId]);
            echo json_encode(['ok'=>true,'msg'=>'Agent assigned.']);
        } catch(Exception $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    if ($action === 'add_note') {
        $inqId = (int)($_POST['inquiry_id'] ?? 0);
        $note  = trim($_POST['note'] ?? '');
        if ($note === '') { echo json_encode(['ok'=>false,'msg'=>'Note cannot be empty.']); exit; }
        try {
            $db->prepare("INSERT INTO inquiry_notes (inquiry_id, admin_id, note, created_at) VALUES (?,?,?,NOW())")
               ->execute([$inqId, $_SESSION['admin_id'], $note]);
            echo json_encode(['ok'=>true,'msg'=>'Note saved.']);
        } catch(Exception $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    if ($action === 'load_inquiry') {
        $inqId = (int)($_POST['inquiry_id'] ?? 0);
        try {
            $stmt = $db->prepare(
                "SELECT i.*, i.name AS visitor_name, p.title AS property_title, p.id AS property_id,
                        p.city AS property_city,
                        (SELECT pm.url FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.sort_order LIMIT 1) AS prop_thumb,
                        u.name AS assigned_name
                 FROM inquiries i
                 LEFT JOIN properties p ON i.property_id=p.id
                 LEFT JOIN users u ON i.assigned_to=u.id
                 WHERE i.id=?"
            );
            $stmt->execute([$inqId]);
            $inq = $stmt->fetch();
            if (!$inq) { echo json_encode(['ok'=>false,'msg'=>'Not found']); exit; }
            // Normalize name field
            if (isset($inq['name']) && !isset($inq['visitor_name'])) {
                $inq['visitor_name'] = $inq['name'];
            }
            // Normalize preferred_contact_time to preferred_time
            if (isset($inq['preferred_contact_time'])) {
                $inq['preferred_time'] = $inq['preferred_contact_time'];
            }

            // Notes
            $nStmt = $db->prepare("SELECT n.*, u.name as admin_name FROM inquiry_notes n LEFT JOIN users u ON n.admin_id=u.id WHERE n.inquiry_id=? ORDER BY n.created_at ASC");
            $nStmt->execute([$inqId]);
            $notes = $nStmt->fetchAll();

            // Agents
            $aStmt = $db->query("SELECT id, name FROM users WHERE role IN ('agent','admin') ORDER BY name ASC");
            $agents = $aStmt->fetchAll();

            echo json_encode(['ok'=>true,'inquiry'=>$inq,'notes'=>$notes,'agents'=>$agents]);
        } catch(Exception $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Unknown action']); exit;
}

// ── CSV Export ────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    requireLogin();
    try {
        $stmt = $db->query(
            "SELECT i.id, i.name AS visitor_name, i.phone, i.email, i.status, i.message, i.created_at,
                    p.title AS property_title, u.name AS assigned_to
             FROM inquiries i
             LEFT JOIN properties p ON i.property_id=p.id
             LEFT JOIN users u ON i.assigned_to=u.id
             ORDER BY i.created_at DESC"
        );
        $rows = $stmt->fetchAll();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inquiries_' . date('Ymd') . '.csv"');
        $out = fopen('php://output','w');
        fputcsv($out, ['ID','Name','Phone','Email','Status','Property','Assigned To','Date','Message']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'],$r['visitor_name'],$r['phone'],$r['email'],$r['status'],$r['property_title'],$r['assigned_to'],date('Y-m-d H:i',strtotime($r['created_at'])),$r['message']]);
        }
        fclose($out);
    } catch(Exception $e) { die('Export failed.'); }
    exit;
}

// ── Filters ──────────────────────────────────────────────────
$viewMode    = $_GET['view_mode'] ?? 'table';
$filterStatus= $_GET['status'] ?? '';
$search      = trim($_GET['q'] ?? '');
$dateFrom    = $_GET['date_from'] ?? '';
$dateTo      = $_GET['date_to']   ?? '';
$viewInqId   = (int)($_GET['view'] ?? 0);
$page        = max(1,(int)($_GET['page'] ?? 1));
$perPage     = 20;

$where = []; $params = [];
if ($filterStatus !== '') { $where[] = 'i.status=?'; $params[] = $filterStatus; }
if ($search !== '') { $where[] = '(i.name LIKE ? OR i.phone LIKE ? OR i.email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($dateFrom !== '') { $where[] = 'i.created_at >= ?'; $params[] = $dateFrom . ' 00:00:00'; }
if ($dateTo   !== '') { $where[] = 'i.created_at <= ?'; $params[] = $dateTo   . ' 23:59:59'; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM inquiries i $whereSQL");
    $countStmt->execute($params);
    $totalRows  = (int)$countStmt->fetchColumn();
    $totalPages = max(1,(int)ceil($totalRows/$perPage));
    $offset     = ($page-1)*$perPage;

    $listStmt = $db->prepare(
        "SELECT i.*, p.title AS property_title, u.name AS assigned_name
         FROM inquiries i
         LEFT JOIN properties p ON i.property_id=p.id
         LEFT JOIN users u ON i.assigned_to=u.id
         $whereSQL
         ORDER BY i.created_at DESC
         LIMIT $perPage OFFSET $offset"
    );
    $listStmt->execute($params);
    $inquiries = $listStmt->fetchAll();

    // For Kanban: get all (no limit) when in kanban mode
    if ($viewMode === 'kanban') {
        $allStmt = $db->prepare("SELECT i.*, p.title AS property_title FROM inquiries i LEFT JOIN properties p ON i.property_id=p.id ORDER BY i.created_at DESC");
        $allStmt->execute();
        $allInquiries = $allStmt->fetchAll();
        $kanbanCols = ['new'=>[],'assigned'=>[],'contacted'=>[],'qualified'=>[],'closed'=>[]];
        foreach ($allInquiries as $inq) {
            $s = $inq['status'];
            if (in_array($s,['closed_won','closed_lost'])) $kanbanCols['closed'][] = $inq;
            elseif (isset($kanbanCols[$s])) $kanbanCols[$s][] = $inq;
        }
    }

    // Agents for assign dropdown
    $agStmt = $db->query("SELECT id, name FROM users WHERE role IN ('agent','admin') ORDER BY name ASC");
    $agents = $agStmt->fetchAll();

    $dbOk = true;
} catch(Exception $e) {
    error_log('[Inquiries] ' . $e->getMessage());
    $inquiries = []; $agents = []; $totalRows = $totalPages = 0; $dbOk = false;
    $kanbanCols = ['new'=>[],'assigned'=>[],'contacted'=>[],'qualified'=>[],'closed'=>[]];
}

$statusColors = ['new'=>'primary','assigned'=>'warning','contacted'=>'info','qualified'=>'success','closed_won'=>'success','closed_lost'=>'danger'];

function inqBadge(string $status, array $colors): string {
    $cls = $colors[$status] ?? 'secondary';
    $lbl = ucfirst(str_replace('_',' ',$status));
    return '<span class="badge bg-'.$cls.'">'.$lbl.'</span>';
}
function paginateUrl(int $p): string { $ps = $_GET; $ps['page']=$p; return '?'.http_build_query($ps); }

$csrf = csrfToken();

include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/admin-sidebar.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-inbox me-2" style="color:var(--gold)"></i>Inquiries</h1>
    <p class="text-muted mb-0 fs-13"><?= number_format($totalRows) ?> total</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <!-- View Toggle -->
    <div class="btn-group">
      <a href="?<?= http_build_query(array_merge($_GET, ['view_mode'=>'table'])) ?>"
         class="btn btn-sm <?= $viewMode==='table'?'btn-dark':'btn-outline-secondary' ?>">
        <i class="fa-solid fa-table"></i> Table
      </a>
      <a href="?<?= http_build_query(array_merge($_GET, ['view_mode'=>'kanban'])) ?>"
         class="btn btn-sm <?= $viewMode==='kanban'?'btn-dark':'btn-outline-secondary' ?>">
        <i class="fa-solid fa-table-columns"></i> Kanban
      </a>
    </div>
    <a href="<?= BASE_PATH ?>/admin/inquiries.php?export=csv" class="btn btn-sm btn-outline-success">
      <i class="fa-solid fa-file-csv me-1"></i> Export CSV
    </a>
  </div>
</div>

<!-- Filters -->
<div class="filter-card">
  <form method="GET" class="row g-2 align-items-end">
    <input type="hidden" name="view_mode" value="<?= htmlspecialchars($viewMode, ENT_QUOTES, 'UTF-8') ?>">
    <div class="col-12 col-md-3">
      <label class="form-label fw-600 fs-12">Search</label>
      <input type="text" name="q" class="form-control form-control-sm"
             placeholder="Name, phone, email..."
             value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">Status</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">All Status</option>
        <?php foreach (['new'=>'New','assigned'=>'Assigned','contacted'=>'Contacted','qualified'=>'Qualified','closed_won'=>'Closed Won','closed_lost'=>'Closed Lost'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $filterStatus===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">Date From</label>
      <input type="date" name="date_from" class="form-control form-control-sm"
             value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label fw-600 fs-12">Date To</label>
      <input type="date" name="date_to" class="form-control form-control-sm"
             value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-12 col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-dark w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a href="<?= BASE_PATH ?>/admin/inquiries.php" class="btn btn-sm btn-outline-secondary w-100"><i class="fa-solid fa-rotate"></i></a>
    </div>
  </form>
</div>

<?php if ($viewMode === 'table'): ?>
<!-- ── Table View ─────────────────────────────────────────── -->
<div class="admin-table-wrapper">
  <div class="table-responsive">
    <table class="table table-admin table-striped table-hover mb-0">
      <thead>
        <tr>
          <th>Name</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Property/Project</th>
          <th>Status</th>
          <th>Assigned To</th>
          <th>Date</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($inquiries)): ?>
        <tr>
          <td colspan="8" class="text-center py-5 text-muted">
            <i class="fa-regular fa-envelope-open fa-2x mb-2 d-block"></i>
            No inquiries found.
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($inquiries as $inq): ?>
        <tr style="cursor:pointer;" onclick="openInquiryPanel(<?= (int)$inq['id'] ?>)">
          <td><div class="fw-600"><?= htmlspecialchars($inq['name'] ?? $inq['visitor_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div></td>
          <td>
            <a href="tel:<?= htmlspecialchars($inq['phone'], ENT_QUOTES, 'UTF-8') ?>"
               class="text-decoration-none" onclick="event.stopPropagation()">
              <?= htmlspecialchars($inq['phone'], ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td style="font-size:0.82rem;"><?= htmlspecialchars($inq['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if ($inq['property_title']): ?>
              <span style="font-size:0.82rem;"><?= htmlspecialchars($inq['property_title'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php else: ?>
              <span class="text-muted fs-12">General Inquiry</span>
            <?php endif; ?>
          </td>
          <td><?= inqBadge($inq['status'], $statusColors) ?></td>
          <td style="font-size:0.82rem;"><?= htmlspecialchars($inq['assigned_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td style="font-size:0.77rem;color:#6c757d;white-space:nowrap;">
            <?= htmlspecialchars(date('d M Y', strtotime($inq['created_at'])), ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td class="text-end" onclick="event.stopPropagation()">
            <button class="btn btn-outline-success btn-action btn-view"
                    onclick="openInquiryPanel(<?= (int)$inq['id'] ?>)" title="View Details">
              <i class="fa-solid fa-eye"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="d-flex justify-content-center py-3">
    <nav><ul class="pagination pagination-sm mb-0">
      <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($page-1),ENT_QUOTES,'UTF-8') ?>">&laquo;</a></li><?php endif; ?>
      <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
        <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($p),ENT_QUOTES,'UTF-8') ?>"><?= $p ?></a></li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars(paginateUrl($page+1),ENT_QUOTES,'UTF-8') ?>">&raquo;</a></li><?php endif; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- ── Kanban View ────────────────────────────────────────── -->
<div class="kanban-board">
  <?php
  $kanbanDefs = [
    'new'       => ['label'=>'New',       'color'=>'primary',  'icon'=>'fa-star'],
    'assigned'  => ['label'=>'Assigned',  'color'=>'warning',  'icon'=>'fa-user-check'],
    'contacted' => ['label'=>'Contacted', 'color'=>'info',     'icon'=>'fa-phone'],
    'qualified' => ['label'=>'Qualified', 'color'=>'success',  'icon'=>'fa-circle-check'],
    'closed'    => ['label'=>'Closed',    'color'=>'secondary','icon'=>'fa-flag'],
  ];
  foreach ($kanbanDefs as $colKey => $colDef):
    $colItems = $kanbanCols[$colKey] ?? [];
  ?>
  <div class="kanban-col kanban-col-<?= $colKey ?>">
    <div class="kanban-col-header">
      <span><i class="fa-solid <?= $colDef['icon'] ?> me-1"></i><?= $colDef['label'] ?></span>
      <span class="badge bg-<?= $colDef['color'] ?> text-<?= $colDef['color']==='warning'?'dark':'white' ?>"><?= count($colItems) ?></span>
    </div>
    <?php foreach ($colItems as $inq): ?>
    <div class="kanban-card" onclick="openInquiryPanel(<?= (int)$inq['id'] ?>)">
      <div class="name"><?= htmlspecialchars($inq['name'] ?? $inq['visitor_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
      <div class="phone"><i class="fa-solid fa-phone fa-xs me-1"></i><?= htmlspecialchars($inq['phone'], ENT_QUOTES, 'UTF-8') ?></div>
      <?php if ($inq['property_title']): ?>
      <div class="prop"><i class="fa-solid fa-house fa-xs me-1"></i><?= htmlspecialchars(mb_strimwidth($inq['property_title'],0,45,'…'), ENT_QUOTES,'UTF-8') ?></div>
      <?php endif; ?>
      <div class="date"><?= htmlspecialchars(date('d M Y', strtotime($inq['created_at'])), ENT_QUOTES,'UTF-8') ?></div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($colItems)): ?>
    <p class="text-center text-muted" style="font-size:0.78rem;padding:1rem 0;">No inquiries</p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Inquiry Detail Offcanvas ───────────────────────────── -->
<div class="offcanvas offcanvas-end offcanvas-inquiry" tabindex="-1" id="inquiryOffcanvas"
     style="width: min(480px, 100vw);">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title mb-0">
      <i class="fa-solid fa-inbox me-2"></i>Inquiry Details
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0" id="offcanvasBody">
    <div class="d-flex justify-content-center align-items-center py-5 text-muted" id="offcanvasLoading">
      <div class="spinner-border spinner-border-sm me-2"></div> Loading...
    </div>
  </div>
</div>

<script>
var CSRF_TOKEN = '<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>';

function openInquiryPanel(id) {
  var offcanvas = new bootstrap.Offcanvas(document.getElementById('inquiryOffcanvas'));
  var body = document.getElementById('offcanvasBody');
  body.innerHTML = '<div class="d-flex justify-content-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Loading...</div>';
  offcanvas.show();

  window.ajaxPost('/admin/inquiries.php', {
    action: 'load_inquiry',
    inquiry_id: id,
    csrf_token: CSRF_TOKEN
  }, function(data) {
    if (!data.ok) { body.innerHTML = '<div class="p-3 text-danger">Error: ' + data.msg + '</div>'; return; }
    var inq = data.inquiry;
    var notes = data.notes || [];
    var agents = data.agents || [];
    var statusOptions = ['new','assigned','contacted','qualified','closed_won','closed_lost'];
    var statusLabels  = {'new':'New','assigned':'Assigned','contacted':'Contacted','qualified':'Qualified','closed_won':'Closed Won','closed_lost':'Closed Lost'};
    var waNum = (inq.phone || '').replace(/[^0-9]/g,'');
    if (waNum.startsWith('0')) waNum = '92' + waNum.slice(1);

    var notesHtml = notes.length ? notes.map(function(n) {
      return '<div class="border rounded p-2 mb-2 bg-light" style="font-size:0.82rem;">' +
             '<div class="fw-600 text-dark">' + escH(n.admin_name) + '</div>' +
             '<div class="text-muted" style="font-size:0.75rem;">' + escH(n.created_at) + '</div>' +
             '<p class="mb-0 mt-1">' + escH(n.note) + '</p></div>';
    }).join('') : '<p class="text-muted fs-12">No notes yet.</p>';

    var agentOptions = agents.map(function(a) {
      return '<option value="'+a.id+'"'+(inq.assigned_to==a.id?' selected':'')+'>'+escH(a.name)+'</option>';
    }).join('');

    var statusOpts = statusOptions.map(function(s) {
      return '<option value="'+s+'"'+(inq.status===s?' selected':'')+'>'+statusLabels[s]+'</option>';
    }).join('');

    function resolveUploadUrl(u) {
      if (!u) return '';
      if (/^(https?:)?\/\//i.test(u)) return u;               // absolute or protocol-relative
      var base = window.BASE_PATH || '';
      if (u.charAt(0) === '/') return base + u;               // already rooted (e.g. /assets/uploads/...)
      return base + '/assets/uploads/' + u;                   // bare filename
    }

    var propHtml = inq.property_title
      ? '<a href="<?= BASE_PATH ?>/admin/listing-form.php?id='+inq.property_id+'" target="_blank" class="text-decoration-none d-flex align-items-center gap-2">' +
        (inq.prop_thumb ? '<img src="'+escH(resolveUploadUrl(inq.prop_thumb))+'" style="width:50px;height:50px;object-fit:cover;border-radius:6px;">' : '') +
        '<span class="fw-600" style="font-size:0.85rem;">'+escH(inq.property_title)+'</span></a>'
      : '<span class="text-muted fs-12">General Inquiry</span>';

    body.innerHTML = '<div class="p-3">' +
      // Visitor Info
      '<div class="mb-3 pb-3 border-bottom">' +
      '<div class="d-flex align-items-center gap-3 mb-2">' +
      '<div style="width:48px;height:48px;border-radius:50%;background:var(--sidebar-bg);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;">' + escH((inq.visitor_name||'?').charAt(0).toUpperCase()) + '</div>' +
      '<div><div class="fw-700" style="font-size:1rem;">'+escH(inq.visitor_name)+'</div>' +
      '<div class="text-muted fs-12">'+escH(inq.email||'No email')+'</div></div></div>' +
      '<div class="d-flex flex-wrap gap-2 mb-2">' +
      '<a href="tel:'+escH(inq.phone)+'" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-phone me-1"></i>'+escH(inq.phone)+'</a>' +
      '<a href="https://wa.me/'+waNum+'" target="_blank" class="btn btn-sm btn-success"><i class="fa-brands fa-whatsapp me-1"></i>WhatsApp</a>' +
      '<button class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText(\''+escH(inq.phone)+'\');this.textContent=\'Copied!\'"><i class="fa-regular fa-copy"></i></button>' +
      '</div>' +
      (inq.preferred_time ? '<div class="fs-12 text-muted"><i class="fa-regular fa-clock me-1"></i>Preferred time: <strong>'+escH(inq.preferred_time)+'</strong></div>' : '') +
      '</div>' +
      // Message
      (inq.message ? '<div class="mb-3 pb-3 border-bottom"><div class="fw-600 mb-1 fs-13">Message:</div><p class="fs-13 text-muted mb-0">'+escH(inq.message)+'</p></div>' : '') +
      // Property
      '<div class="mb-3 pb-3 border-bottom"><div class="fw-600 mb-1 fs-13">Property / Project:</div>' + propHtml + '</div>' +
      // Status + Assign
      '<div class="mb-3 pb-3 border-bottom">' +
      '<div class="row g-2">' +
      '<div class="col-12"><label class="fw-600 fs-13">Update Status:</label>' +
      '<select class="form-select form-select-sm mt-1" id="statusSelect">'+statusOpts+'</select>' +
      '<button class="btn btn-sm btn-gold mt-2 w-100" onclick="updateStatus('+inq.id+')"><i class="fa-solid fa-check me-1"></i>Update Status</button></div>' +
      '<div class="col-12"><label class="fw-600 fs-13">Assign Agent:</label>' +
      '<select class="form-select form-select-sm mt-1" id="agentSelect"><option value="">— Unassigned —</option>'+agentOptions+'</select>' +
      '<button class="btn btn-sm btn-outline-dark mt-2 w-100" onclick="assignAgent('+inq.id+')"><i class="fa-solid fa-user-check me-1"></i>Assign</button></div>' +
      '</div></div>' +
      // Notes
      '<div class="mb-3">' +
      '<div class="fw-600 mb-2 fs-13">Notes <span class="badge bg-secondary">'+notes.length+'</span></div>' +
      '<div id="notesContainer">'+notesHtml+'</div>' +
      '<textarea class="form-control form-control-sm mt-2" id="newNote" rows="3" placeholder="Add a note..."></textarea>' +
      '<button class="btn btn-sm btn-outline-dark mt-2" onclick="addNote('+inq.id+')"><i class="fa-solid fa-plus me-1"></i>Add Note</button>' +
      '</div>' +
      '</div>';
  });
}

function updateStatus(id) {
  var status = document.getElementById('statusSelect').value;
  window.ajaxPost('/admin/inquiries.php', { action:'update_status', inquiry_id:id, status:status, csrf_token:CSRF_TOKEN }, function(r) {
    if (r.ok) { showToast('Status updated.','success'); location.reload(); }
    else showToast(r.msg, 'danger');
  });
}

function assignAgent(id) {
  var agentId = document.getElementById('agentSelect').value;
  window.ajaxPost('/admin/inquiries.php', { action:'assign_agent', inquiry_id:id, agent_id:agentId, csrf_token:CSRF_TOKEN }, function(r) {
    if (r.ok) showToast('Agent assigned.','success');
    else showToast(r.msg,'danger');
  });
}

function addNote(id) {
  var note = document.getElementById('newNote').value.trim();
  if (!note) return;
  window.ajaxPost('/admin/inquiries.php', { action:'add_note', inquiry_id:id, note:note, csrf_token:CSRF_TOKEN }, function(r) {
    if (r.ok) {
      document.getElementById('newNote').value = '';
      var nc = document.getElementById('notesContainer');
      var div = document.createElement('div');
      div.className = 'border rounded p-2 mb-2 bg-light';
      div.style.fontSize = '0.82rem';
      div.innerHTML = '<div class="fw-600"><?= htmlspecialchars($_SESSION['admin_name']??'You',ENT_QUOTES,'UTF-8') ?></div><div class="text-muted" style="font-size:0.75rem;">Just now</div><p class="mb-0 mt-1">'+escH(note)+'</p>';
      nc.appendChild(div);
      showToast('Note saved.','success');
    } else showToast(r.msg,'danger');
  });
}

function escH(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type) {
  var d = document.createElement('div');
  d.className = 'alert alert-'+type+' shadow position-fixed';
  d.style.cssText = 'bottom:1rem;right:1rem;z-index:9999;min-width:250px;';
  d.textContent = msg;
  document.body.appendChild(d);
  setTimeout(function(){ d.remove(); }, 3000);
}

// Auto-open inquiry if ?view=id
<?php if ($viewInqId > 0): ?>
window.addEventListener('load', function(){ openInquiryPanel(<?= $viewInqId ?>); });
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
