<?php
require_once "db_market.php";

// Handle selected map
$map_id = isset($_GET['map_id']) ? (int)$_GET['map_id'] : null;
$map = null;
$stalls = [];

// Fetch maps for dropdown
$mapsList = $pdo->query("SELECT * FROM maps ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$mapUrl = '';

if ($map_id) {
    // Get map info
    $stmt = $pdo->prepare("SELECT * FROM maps WHERE id = ?");
    $stmt->execute([$map_id]);
    $map = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($map) {
        // Get stalls for this map
        $stallsStmt = $pdo->prepare("SELECT * FROM stalls WHERE map_id = ?");
        $stallsStmt->execute([$map_id]);
        $stalls = $stallsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Use the path as stored in DB
        $mapUrl = $map['image_path'];
        if (!preg_match('#^https?://#', $mapUrl)) {
            $mapUrl = "http://localhost/revenue/" . ltrim($mapUrl, '/');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Market Portal</title>
<link rel="stylesheet" href="portal.css">
</head>
<body>

<div class="header">
    <h1>Market Portal</h1>
    <form method="GET" class="map-selector">
        <label for="mapSelect">Select Market Map:</label>
        <select name="map_id" id="mapSelect" onchange="this.form.submit()">
            <option value="">-- Choose Map --</option>
            <?php foreach($mapsList as $m): ?>
                <option value="<?= $m['id'] ?>" <?= ($map_id == $m['id']) ? 'selected' : '' ?> >
                    <?= htmlspecialchars($m['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if($map && $map_id): ?>
<div class="main-container">
    <div class="content-wrapper">
        <div class="map-container">
            <h2><?= htmlspecialchars($map['name']) ?></h2>
            <div class="market-map" id="marketMap" style="background-image: url('<?= htmlspecialchars($mapUrl) ?>');">
                <?php foreach($stalls as $stall):
                    // Correct status handling with maintenance
                    if ($stall['status'] === 'available') {
                        $statusClass = 'available';
                    } elseif ($stall['status'] === 'reserved') {
                        $statusClass = 'reserved';
                    } elseif ($stall['status'] === 'occupied') {
                        $statusClass = 'occupied';
                    } elseif ($stall['status'] === 'maintenance') {
                        $statusClass = 'maintenance';
                    } else {
                        $statusClass = 'available'; // fallback
                    }
                ?>
                    <div class="stall <?= $statusClass ?>"
                         data-stall-id="<?= $stall['id'] ?>"
                         data-stall-name="<?= htmlspecialchars($stall['name']) ?>"
                         data-price="<?= $stall['price'] ?>"
                         data-status="<?= $stall['status'] ?>"
                         data-height="<?= $stall['height'] ?>"
                         data-length="<?= $stall['length'] ?>"
                         data-width="<?= $stall['width'] ?>"
                         style="left: <?= $stall['pos_x'] ?>px; top: <?= $stall['pos_y'] ?>px;">
                         
                        <div class="stall-content">
                            <div class="stall-name"><?= htmlspecialchars($stall['name']) ?></div>
                            <?php if($stall['price'] > 0): ?>
                                <div class="stall-price">₱<?= number_format($stall['price'], 2) ?></div>
                            <?php endif; ?>
                            <?php if($stall['status'] === 'maintenance'): ?>
                                <div class="maintenance-badge">🔧 Maintenance</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-container">
            <div class="reservation-card">
                <h3>Reserve Stall</h3>
                <div class="form-placeholder" id="formPlaceholder">
                    <div class="placeholder-content">
                        <span class="placeholder-icon">📋</span>
                        <p>Click on an available stall to start reservation</p>
                    </div>
                </div>
                <form id="renterForm" style="display: none;">
                    <input type="hidden" name="stall_id" id="stall_id">
                    <input type="hidden" name="map_id" value="<?= $map_id ?>">
                    
                    <div class="selected-stall-info">
                        <h4 id="selectedStallName"></h4>
                        <p id="selectedStallPrice"></p>
                        <p id="selectedStallDimensions"></p>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" name="full_name" id="full_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" name="contact_number" id="contact_number">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <textarea name="address" id="address" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Reserve Stall</button>
                        <button type="button" onclick="clearSelection()" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
let selectedStall = null;

document.querySelectorAll('.stall').forEach(stall => {
    stall.addEventListener('click', () => {
        const status = stall.getAttribute('data-status');
        
        // Prevent selection of reserved, occupied, or maintenance stalls
        if(status === 'reserved' || status === 'occupied' || status === 'maintenance'){
            let message = 'This stall is not available!';
            if(status === 'maintenance') {
                message = 'This stall is under maintenance and cannot be reserved.';
            }
            alert(message);
            return;
        }

        document.querySelectorAll('.stall').forEach(s => s.classList.remove('selected'));
        stall.classList.add('selected');
        selectedStall = stall;

        // Update form with stall details
        document.getElementById('selectedStallName').textContent = 
            'Reserving: ' + stall.getAttribute('data-stall-name');
        document.getElementById('stall_id').value = stall.getAttribute('data-stall-id');
        
        // Price display
        const price = parseFloat(stall.getAttribute('data-price'));
        document.getElementById('selectedStallPrice').textContent =
            'Price: ₱' + (price > 0 ? price.toLocaleString() : '0.00');

        // Dimensions display (still shown in form but not in stall box)
        const length = stall.getAttribute('data-length');
        const width = stall.getAttribute('data-width');
        const height = stall.getAttribute('data-height');
        let dimensionsText = '';
        if (length > 0 && width > 0) {
            dimensionsText = `Size: ${length}m × ${width}m`;
            if (height > 0) {
                dimensionsText += ` × ${height}m`;
            }
        }
        document.getElementById('selectedStallDimensions').textContent = dimensionsText;

        // Show form
        document.getElementById('formPlaceholder').style.display = 'none';
        document.getElementById('renterForm').style.display = 'block';

        // Clear and focus form
        document.getElementById('full_name').value = '';
        document.getElementById('contact_number').value = '';
        document.getElementById('email').value = '';
        document.getElementById('address').value = '';
        document.getElementById('full_name').focus();
    });
});

function clearSelection() {
    if (selectedStall) {
        selectedStall.classList.remove('selected');
        selectedStall = null;
    }
    document.getElementById('formPlaceholder').style.display = 'block';
    document.getElementById('renterForm').style.display = 'none';
}

document.getElementById('renterForm').addEventListener('submit', async function(e){
    e.preventDefault();
    
    if (!selectedStall) {
        alert('Please select a stall first!');
        return;
    }

    const formData = new FormData(this);

    try {
        const res = await fetch('reserve_stall.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if(data.status === 'success'){
            alert('Stall reserved successfully!');
            clearSelection();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch(err){
        alert('Error: ' + err.message);
    }
});

document.getElementById('marketMap').addEventListener('click', function(e) {
    if (e.target === this) {
        clearSelection();
    }
});
</script>

</body>
</html>