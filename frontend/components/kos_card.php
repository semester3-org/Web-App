<?php
function kosCard($kos) {
    $facilities = !empty($kos['facilities']) ? explode(',', $kos['facilities']) : [];
    $price = number_format($kos['price'], 0, ',', '.');
    
    $type_badge = match($kos['type']) {
        'male' => '<span class="type-badge male">Putra</span>',
        'female' => '<span class="type-badge female">Putri</span>',
        'mixed' => '<span class="type-badge mixed">Campur</span>',
        default => ''
    };
    
    return "
    <div class='kos-card'>
        <div class='kos-image-container'>
            <img src='../../uploads/kos/{$kos['image_url']}' alt='Gambar kos {$kos['name']}' class='kos-image'>
            {$type_badge}
            <button class='bookmark-btn' onclick='bookmarkKos({$kos['id']})'>
                <i class='far fa-heart'></i>
            </button>
        </div>
        
        <div class='kos-content'>
            <h3 class='kos-title'>{$kos['name']}</h3>
            <p class='kos-location'>
                <i class='fas fa-map-marker-alt'></i>
                {$kos['address']}
            </p>
            
            <div class='kos-features'>
                " . implode('', array_map(fn($facility) => 
                    "<span class='feature-badge'>{$facility}</span>", $facilities)) . "
            </div>
            
            <div class='kos-price'>Rp {$price}/bulan</div>
            
            <div class='kos-actions'>
                <a href='kos_detail.php?id={$kos['id']}' class='btn btn-primary'>Lihat Detail</a>
                <button class='btn btn-outline' onclick='shareKos({$kos['id']})'>
                    <i class='fas fa-share'></i>
                </button>
            </div>
        </div>
    </div>";
}
?>

<style>
.kos-image-container {
    position: relative;
    overflow: hidden;
}

.type-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: white;
    font-size: 0.875rem;
    font-weight: 500;
}

.type-badge.male { background: #3b82f6; }
.type-badge.female { background: #ec4899; }
.type-badge.mixed { background: #10b981; }

.bookmark-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    transition: all 0.3s;
}

.bookmark-btn:hover {
    background: #f3f4f6;
    transform: scale(1.1);
}

.kos-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: space-between;
}
</style>