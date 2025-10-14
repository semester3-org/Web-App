<?php
/**
 * ============================================
 * GET PROPERTY DETAIL API
 * File: backend/admin/classes/get_property_detail.php
 * ============================================
 * API untuk mendapatkan detail property lengkap
 */

session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$property_id = intval($_GET['id'] ?? 0);

if ($property_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

try {
    // Get property with owner info
    $sql = "SELECT 
                k.*,
                u.full_name as owner_name,
                u.email as owner_email,
                u.phone as owner_phone,
                admin.full_name as verifier_name
            FROM kos k
            LEFT JOIN users u ON k.owner_id = u.id
            LEFT JOIN users admin ON k.verified_by = admin.id
            WHERE k.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $property_id);
    $stmt->execute();
    $property = $stmt->get_result()->fetch_assoc();
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Property tidak ditemukan']);
        exit();
    }
    
    // Get images
    $img_sql = "SELECT image_url FROM kos_images WHERE kos_id = ?";
    $img_stmt = $conn->prepare($img_sql);
    $img_stmt->bind_param('i', $property_id);
    $img_stmt->execute();
    $images_result = $img_stmt->get_result();
    
    $images = [];
    while ($img = $images_result->fetch_assoc()) {
        $images[] = $img['image_url'];
    }
    
    // Get facilities
    $fac_sql = "SELECT f.name, f.icon, f.category 
                FROM kos_facilities kf
                JOIN facilities f ON kf.facility_id = f.id
                WHERE kf.kos_id = ?";
    $fac_stmt = $conn->prepare($fac_sql);
    $fac_stmt->bind_param('i', $property_id);
    $fac_stmt->execute();
    $facilities_result = $fac_stmt->get_result();
    
    $facilities = [];
    while ($fac = $facilities_result->fetch_assoc()) {
        $facilities[$fac['category']][] = $fac;
    }
    
    // Get rejection reason if rejected
    $rejection_reason = null;
    if ($property['status'] === 'rejected') {
        $rej_sql = "SELECT reason FROM property_rejections WHERE kos_id = ? ORDER BY created_at DESC LIMIT 1";
        $rej_stmt = $conn->prepare($rej_sql);
        $rej_stmt->bind_param('i', $property_id);
        $rej_stmt->execute();
        $rej_result = $rej_stmt->get_result();
        if ($rej_row = $rej_result->fetch_assoc()) {
            $rejection_reason = $rej_row['reason'];
        }
    }
    
    // Build HTML
    ob_start();
    ?>
    
    <!-- Image Gallery -->
    <?php if (!empty($images)): ?>
        <div id="propertyCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($images as $index => $image): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="../../../<?php echo htmlspecialchars($image); ?>" 
                             class="d-block w-100" 
                             style="height: 400px; object-fit: cover; border-radius: 10px;">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($images) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Basic Info -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h4 class="mb-3" style="color: #10b981;">
                <i class="bi bi-house-door"></i> Informasi Property
            </h4>
            <table class="table table-borderless">
                <tr>
                    <td width="40%" class="text-muted">Nama:</td>
                    <td><strong><?php echo htmlspecialchars($property['name']); ?></strong></td>
                </tr>
                <tr>
                    <td class="text-muted">Jenis Kos:</td>
                    <td><span class="badge bg-info"><?php echo strtoupper($property['kos_type']); ?></span></td>
                </tr>
                <tr>
                    <td class="text-muted">Total Kamar:</td>
                    <td><?php echo $property['total_rooms']; ?> kamar</td>
                </tr>
                <tr>
                    <td class="text-muted">Kamar Tersedia:</td>
                    <td><?php echo $property['available_rooms']; ?> kamar</td>
                </tr>
                <tr>
                    <td class="text-muted">Harga/Bulan:</td>
                    <td><strong>Rp <?php echo number_format($property['price_monthly'], 0, ',', '.'); ?></strong></td>
                </tr>
                <?php if ($property['price_daily']): ?>
                    <tr>
                        <td class="text-muted">Harga/Hari:</td>
                        <td>Rp <?php echo number_format($property['price_daily'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div class="col-md-6">
            <h4 class="mb-3" style="color: #10b981;">
                <i class="bi bi-person"></i> Informasi Owner
            </h4>
            <table class="table table-borderless">
                <tr>
                    <td width="40%" class="text-muted">Nama:</td>
                    <td><?php echo htmlspecialchars($property['owner_name']); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Email:</td>
                    <td><?php echo htmlspecialchars($property['owner_email']); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Telepon:</td>
                    <td><?php echo htmlspecialchars($property['owner_phone'] ?? '-'); ?></td>
                </tr>
            </table>
            
            <h4 class="mb-3 mt-4" style="color: #10b981;">
                <i class="bi bi-clock-history"></i> Status
            </h4>
            <table class="table table-borderless">
                <tr>
                    <td width="40%" class="text-muted">Status:</td>
                    <td>
                        <span class="badge 
                            <?php echo $property['status'] === 'pending' ? 'bg-warning' : 
                                       ($property['status'] === 'approved' ? 'bg-success' : 'bg-danger'); ?>">
                            <?php echo strtoupper($property['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php if ($property['verified_at']): ?>
                    <tr>
                        <td class="text-muted">Diverifikasi:</td>
                        <td><?php echo date('d M Y, H:i', strtotime($property['verified_at'])); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Oleh:</td>
                        <td><?php echo htmlspecialchars($property['verifier_name']); ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td class="text-muted">Dibuat:</td>
                    <td><?php echo date('d M Y, H:i', strtotime($property['created_at'])); ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Rejection Reason -->
    <?php if ($rejection_reason): ?>
        <div class="alert alert-danger">
            <h5><i class="bi bi-exclamation-triangle"></i> Alasan Penolakan:</h5>
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($rejection_reason)); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Location -->
    <div class="mb-4">
        <h4 class="mb-3" style="color: #10b981;">
            <i class="bi bi-geo-alt"></i> Lokasi
        </h4>
        <table class="table table-borderless">
            <tr>
                <td width="20%" class="text-muted">Alamat:</td>
                <td><?php echo htmlspecialchars($property['address']); ?></td>
            </tr>
            <tr>
                <td class="text-muted">Kota:</td>
                <td><?php echo htmlspecialchars($property['city']); ?></td>
            </tr>
            <tr>
                <td class="text-muted">Provinsi:</td>
                <td><?php echo htmlspecialchars($property['province']); ?></td>
            </tr>
            <tr>
                <td class="text-muted">Kode Pos:</td>
                <td><?php echo htmlspecialchars($property['postal_code'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="text-muted">Koordinat:</td>
                <td>
                    <?php if ($property['latitude'] && $property['longitude']): ?>
                        <a href="https://www.google.com/maps?q=<?php echo $property['latitude']; ?>,<?php echo $property['longitude']; ?>" 
                           target="_blank" 
                           class="btn btn-sm btn-outline-success">
                            <i class="bi bi-map"></i> Lihat di Peta
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Description -->
    <?php if ($property['description']): ?>
        <div class="mb-4">
            <h4 class="mb-3" style="color: #10b981;">
                <i class="bi bi-file-text"></i> Deskripsi
            </h4>
            <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Facilities -->
    <?php if (!empty($facilities)): ?>
        <div class="mb-4">
            <h4 class="mb-3" style="color: #10b981;">
                <i class="bi bi-star"></i> Fasilitas
            </h4>
            <div class="row">
                <?php foreach ($facilities as $category => $fac_list): ?>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted text-uppercase mb-2">
                            <?php 
                            $cat_names = [
                                'room' => 'Fasilitas Kamar',
                                'bathroom' => 'Fasilitas Kamar Mandi',
                                'common' => 'Fasilitas Umum',
                                'parking' => 'Parkir',
                                'security' => 'Keamanan'
                            ];
                            echo $cat_names[$category] ?? $category;
                            ?>
                        </h6>
                        <ul class="list-unstyled">
                            <?php foreach ($fac_list as $fac): ?>
                                <li class="mb-1">
                                    <i class="bi <?php echo $fac['icon']; ?> text-success"></i>
                                    <?php echo htmlspecialchars($fac['name']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Rules -->
    <?php if ($property['rules']): ?>
        <div class="mb-4">
            <h4 class="mb-3" style="color: #10b981;">
                <i class="bi bi-shield-check"></i> Aturan
            </h4>
            <?php
            $rules = json_decode($property['rules'], true);
            if ($rules && is_array($rules)):
            ?>
                <ul>
                    <?php foreach ($rules as $rule): ?>
                        <li><?php echo htmlspecialchars($rule); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">Tidak ada aturan khusus</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>