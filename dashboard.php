<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require 'conexion.php';


$nombreUsuario = $_SESSION['nombre'];


$usuario_id = $_SESSION['usuario_id'];
$usuario_info = [];
try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :usuario_id");
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    $usuario_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
   
}


$sedes = [];
try {
    $stmt = $conn->query("SELECT * FROM sedes WHERE activa = TRUE");
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error al obtener sedes: " . $e->getMessage());
}

$salones = [];
if (isset($_GET['sede_id'])) {
    $sede_id = $_GET['sede_id'];
    
    // Verificar si la sede todavía existe
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM sedes WHERE id = :sede_id AND activa = TRUE");
        $stmt->bindParam(':sede_id', $sede_id);
        $stmt->execute();
        $sede_existe = $stmt->fetchColumn();
        
        if ($sede_existe) {
            $stmt = $conn->prepare("SELECT * FROM salones WHERE sede_id = :sede_id");
            $stmt->bindParam(':sede_id', $sede_id);
            $stmt->execute();
            $salones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Si la sede no existe, limpiar el parámetro de la URL
            unset($_GET['sede_id']);
        }
    } catch(PDOException $e) {
        die("Error al obtener salones: " . $e->getMessage());
    }
}

$computadoras = [];
if (isset($_GET['salon_id']) || (isset($_GET['section']) && $_GET['section'] == 'reparaciones')) {
    $salon_id = isset($_GET['salon_id']) ? $_GET['salon_id'] : null;
    
    // Verificar si el salón todavía existe
    if ($salon_id) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM salones WHERE id = :salon_id");
            $stmt->bindParam(':salon_id', $salon_id);
            $stmt->execute();
            $salon_existe = $stmt->fetchColumn();
            
            if (!$salon_existe) {
                // Si el salón no existe, limpiar el parámetro
                unset($_GET['salon_id']);
                $salon_id = null;
            }
        } catch(PDOException $e) {
            // Manejar error si es necesario
        }
    }
    
    try {
        $sql = "SELECT * FROM computadores";
        if ($salon_id) {
            $sql .= " WHERE salon_id = :salon_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':salon_id', $salon_id);
        } else {
            $stmt = $conn->prepare($sql);
        }
        $stmt->execute();
        $computadoras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error al obtener computadoras: " . $e->getMessage());
    }
}


$tecnicos = [];
try {
    $stmt = $conn->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'tecnico'");
    $tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // No detenemos la ejecución si hay error al obtener técnicos
}

$reparaciones = [];
if (isset($_GET['computadora_id'])) {
    $computadora_id = $_GET['computadora_id'];
    try {
        $stmt = $conn->prepare("SELECT r.id, r.computadora_id, r.fecha_reparacion, 
                                      r.fecha_completada, r.persona_reporto, 
                                      r.persona_realizo, r.descripcion, r.solucion,
                                      r.estado_reparacion, 
                                      c.codigo_patrimonio 
                               FROM reparaciones r
                               JOIN computadores c ON r.computadora_id = c.id
                               WHERE r.computadora_id = :computadora_id
                               ORDER BY r.fecha_reparacion DESC");
        $stmt->bindParam(':computadora_id', $computadora_id);
        $stmt->execute();
        $reparaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error al obtener reparaciones: " . $e->getMessage());
    }
} else {
    // Obtener todas las reparaciones cuando se accede a la sección de reparaciones
    try {
        $stmt = $conn->query("SELECT r.id, r.computadora_id, r.fecha_reparacion, 
                                     r.fecha_completada, r.persona_reporto, 
                                     r.persona_realizo, r.descripcion, r.solucion,
                                     r.estado_reparacion, 
                                     c.codigo_patrimonio 
                              FROM reparaciones r
                              JOIN computadores c ON r.computadora_id = c.id
                              ORDER BY r.fecha_reparacion DESC");
        $reparaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error al obtener reparaciones: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>PeopleHub | Sistema de Gestión de Computadoras</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="estilos2.css">
 <style>
      :root {
            --color-primary: #168a40;     
            --color-primary-light: #168a40;
            --color-secondary: #168a40;    
            --color-secondary-light: #74b9ff;
            --color-accent: #00cec9;      
            --color-accent-light: #55efc4;
            --color-orange: #e67e22;       
            --color-orange-light: #fab1a0;
            --color-green: #2ecc71;       
            --color-green-light: #55efc4;
            --color-light: #f8f9fa;
            --color-dark: #2d3436;
            --color-dark-light: #636e72;
            --color-success: #00b894;
            --color-warning: #fdcb6e;
            --color-danger: #d63031;
            --color-danger-light: #ff7675;
            --color-dark-blue: #168a40;    
            --color-pink: #e84393;
            --color-purple: #6c5ce7;
            --gradient-primary: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            --gradient-accent: linear-gradient(135deg, var(--color-accent), var(--color-green));
            --gradient-warning: linear-gradient(135deg, var(--color-orange), var(--color-warning));
            --gradient-danger: linear-gradient(135deg, var(--color-danger), var(--color-pink));
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.15);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.2);
            --shadow-xl: 0 12px 32px rgba(0,0,0,0.25);
        }
    .pc-detail-popup {
        text-align: center;
    }
    
    .pc-detail-container {
        max-width: 100%;
        text-align: center;
    }
    
    .pc-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .pc-icon {
        font-size: 3rem;
        color: #6c5ce7;
        margin-bottom: 10px;
    }
    
    .pc-title h3 {
        margin: 0;
        color: #2d3436;
        font-size: 1.5rem;
    }
    
    .pc-subtitle {
        margin: 5px 0 0;
        color: #636e72;
        font-size: 1rem;
    }
    
    .pc-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .pc-detail-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: left;
        margin-top:20px;
    }
    
    .section-title {
        color: #6c5ce7;
        font-size: 1rem;
        margin-top: 0;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 1px solid #dfe6e9;
    }
    
    .detail-item {
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
    }
    
    .detail-label {
        font-weight: 500;
        color: #2d3436;
    }
    
    .detail-value {
        color: #636e72;
        text-align: right;
    }
    
    .pc-observations {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: left;
    }
    
    .observations-content {
        color: #636e72;
        line-height: 1.5;
    }
    
    /* Estilos para los badges */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
        gap: 5px;
    }
    
    .badge i {
        font-size: 0.8rem;
    }
    
    .badge-success {
        background-color: #d4edda;
        color: #155724;
    }
    
    .badge-warning {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .badge-danger {
        background-color: #f8d7da;
        color: #721c24;
    }
    .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .badge-secondary {
            background-color: #e2e3e5;
            color: #383d41;
        }
     #reparaciones-section {
        display: none;
        margin-top:20px;
    }
    .progress {
    width: 100% !important; /* Fuerza el ancho al 100% */
    height: 100%;
    border-radius: 10px;
    position: relative;
    transition: none; /* Elimina la transición si no es necesaria */
    background: var(--color-primary); /* Color por defecto, se sobrescribirá */
}

/* Colores específicos para cada tarjeta */
.card:nth-child(1) .progress { background: var(--color-orange); }
.card:nth-child(2) .progress { background: var(--color-green); }
.card:nth-child(3) .progress { background: var(--color-primary); }
.card:nth-child(4) .progress { background: var(--color-secondary); }
.image-preview-container {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    margin-bottom: 15px;
    background: #f9f9f9;
    transition: all 0.3s ease;
}

.image-preview-container:hover {
    border-color: var(--color-primary);
    background: #f0f8ff;
}

.image-preview {
    max-width: 100%;
    max-height: 200px;
    margin-bottom: 15px;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.image-upload-btn {
    display: inline-block;
    padding: 10px 20px;
    background: var(--color-primary);
    color: white;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.image-upload-btn:hover {
    background: var(--color-primary-light);
    transform: translateY(-2px);
}

.remove-image-btn {
    display: inline-block;
    padding: 10px 15px;
    background: var(--color-danger);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-left: 10px;
    transition: all 0.3s ease;
}

.remove-image-btn:hover {
    background: var(--color-danger-light);
}
 .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
    }
    
    .stat-card.large-card {
        grid-column: span 2;
    }
    
    .stat-card .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .stat-card .card-header h3 {
        margin: 0;
        font-size: 1rem;
        color: var(--color-dark);
    }
    
    .stat-card .card-header i {
        font-size: 1.5rem;
        color: var(--color-primary);
    }
    
    .stat-card .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        text-align: center;
        margin: 15px 0;
        color: var(--color-primary);
    }
    
    .stat-card .card-footer {
        border-top: 1px solid #eee;
        padding-top: 15px;
        font-size: 0.85rem;
        color: var(--color-dark-light);
    }
    
    .chart-container {
        height: 200px;
        position: relative;
    }
    
    /* Colores específicos para cada tarjeta */
    .stat-card:nth-child(1) .card-header i { color: var(--color-primary); }
    .stat-card:nth-child(2) .card-header i { color: var(--color-success); }
    .stat-card:nth-child(3) .card-header i { color: var(--color-warning); }
    .stat-card:nth-child(4) .card-header i { color: var(--color-danger); }
    
    .stat-card:nth-child(2) .stat-number { color: var(--color-success); }
    .stat-card:nth-child(3) .stat-number { color: var(--color-warning); }
    .stat-card:nth-child(4) .stat-number { color: var(--color-danger); }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .dashboard-cards {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-cards {
            grid-template-columns: 1fr;
        }
        
        .stat-card.large-card {
            grid-column: span 1;
        }
    }
     .notifications-container {
    position: relative;
    margin-left: auto; /* Empuja a la derecha */
    margin-right: 15px;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    padding: 10px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.notification-bell:hover {
    background-color: rgba(108, 92, 231, 0.1);
}

.notification-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background-color: var(--color-danger);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.notifications-popup {
    position: absolute;
    top: 50px;
    right: 0;
    width: 350px;
    background: white;
    border-radius: 8px;
    box-shadow: var(--shadow-xl);
    z-index: 1000;
    display: none;
    max-height: 400px;
    overflow-y: auto;
}

.notifications-popup.show {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notifications-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notifications-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.mark-all-read {
    color: var(--color-primary);
    cursor: pointer;
    font-size: 0.8rem;
}

.notifications-list {
    padding: 0;
    margin: 0;
    list-style: none;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
}

.notification-item.unread {
    background-color: #f8f9fa;
}

.notification-icon {
    margin-right: 10px;
    color: var(--color-warning);
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 500;
    margin: 0 0 5px 0;
}

.notification-message {
    margin: 0;
    font-size: 0.85rem;
    color: var(--color-dark-light);
}

.notification-time {
    font-size: 0.7rem;
    color: var(--color-dark-light);
    margin-top: 5px;
}

.no-notifications {
    padding: 20px;
    text-align: center;
    color: var(--color-dark-light);
}
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 20px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
}

.pagination-btn {
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    margin: 0 10px;
    transition: all 0.3s ease;
}

.pagination-btn:hover:not(:disabled) {
    background: var(--color-primary-light);
    transform: scale(1.05);
}

.pagination-btn:disabled {
    background: var(--color-dark-light);
    cursor: not-allowed;
    opacity: 0.5;
}

.pagination-info {
    font-weight: 500;
    color: var(--color-dark);
    margin: 0 15px;
}

.pagination-select {
    margin-left: 15px;
    padding: 5px 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
}
 .site-footer {
        background: linear-gradient(135deg, var(--color-dark), var(--color-dark-light));
        color: white;
        padding: 20px 0;
        margin-top: auto;
        border-top: 3px solid var(--color-primary);
    }
    
    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .footer-logo img {
        max-height: 50px;
        width: auto;
    }
    
    .footer-info {
        text-align: right;
        font-size: 0.9rem;
    }
    
    .footer-info p {
        margin: 5px 0;
        color: rgba(255, 255, 255, 0.8);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        
        .footer-info {
            text-align: center;
        }
    }
    .site-footer {
    position: relative;
    z-index: 1000; 
}


.animate__animated {
    z-index: 1;
    position: relative;
}


.main-content {
    position: relative;
    z-index: 1;
}


.data-section {
    position: relative;
    z-index: 2;
}


.table-container {
    position: relative;
    z-index: 2;
}
</style>
</head>
<body>
    <!-- 🎀 Menú Lateral -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-desktop"></i>
            <span>CompuControl</span>
        </div>

        <div class="sidebar-menu">
            <div class="menu-item active" data-section="inicio">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </div>
            <div class="menu-item" data-section="incidencias">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Incidencias</span>
            </div>
            <div class="menu-item" data-section="reparaciones">
                <i class="fas fa-tools"></i>
                <span>Reparaciones</span>
            </div>
            
            
        </div>
    </aside>

    <!-- Contenido Principal -->
    <div class="main-content">
        <!-- 🎀 Header -->
        <header class="header">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="logo">
                <i class="fas fa-desktop"></i>
                <span>CompuControl</span>
            </div>
            
            <div class="search-bar" style="margin-left: auto;">
                <input type="text" placeholder="Buscar salones, computadoras...">
                <button><i class="fas fa-search"></i></button>
            </div>
            <div class="notifications-container">
    <div class="notification-bell ripple" onclick="toggleNotifications()">
        <i class="fas fa-bell"></i>
        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
    </div>
    <div class="notifications-popup" id="notificationsPopup">
        <div class="notifications-header">
            <h3>Notificaciones</h3>
            <span class="mark-all-read" onclick="markAllAsRead()">Marcar todo como leído</span>
        </div>
        <div class="notifications-list" id="notificationsList">
            <!-- Las notificaciones se cargarán aquí dinámicamente -->
        </div>
    </div>
</div>
            
            <div class="user-menu ripple" onclick="confirmLogout()">
                <span><?php echo htmlspecialchars($nombreUsuario); ?></span>
                <div class="user-avatar" data-name="<?php echo htmlspecialchars($nombreUsuario); ?>"></div>
            </div>
        </header>

        <!-- Sección de Inicio -->
        <section id="inicio-section">
            <!-- 📊 Tarjetas Dashboard con las sedes -->
            <section class="dashboard">
                <?php if (!empty($sedes)): ?>
                    <?php foreach ($sedes as $sede): ?>
                    <div class="card floating" onclick="window.location.href='dashboard.php?sede_id=<?php echo $sede['id']; ?>'">
                        <div class="card-header">
                            <div>
                                <h3><?php echo htmlspecialchars($sede['nombre']); ?></h3>
                                <p><?php echo htmlspecialchars($sede['direccion']); ?></p>
                            </div>
                            <i class="fas fa-building"></i>
                        </div>
                        
                        <div class="progress-container">
                            <div class="progress-info">
                                <span>Disponible</span>
                                
                            </div>
                            <div class="progress-bar">
                                <div class="progress"></div>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <i class="fas fa-user-tie"></i>
                            <span><?php echo htmlspecialchars($sede['responsable']); ?></span>
                            <button class="delete-btn ripple" onclick="eliminarSede(<?php echo $sede['id']; ?>, event)" 
                                    style="margin-left: auto; padding: 0.5rem 1rem;">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Tarjeta para agregar nueva sede -->
                    <div class="card" onclick="mostrarModalNuevaSede()" style="background: rgba(108, 92, 231, 0.05); border: 2px dashed rgba(108, 92, 231, 0.3);">
                        <div class="card-header">
                            <div>
                                <h3 style="color: var(--color-primary);">Agregar Nueva Sede</h3>
                                <p style="color: var(--color-primary-light);">Haz clic para registrar una nueva sede</p>
                            </div>
                            <i class="fas fa-plus" style="color: var(--color-primary); opacity: 0.5;"></i>
                        </div>
                        <div style="text-align: center; margin: 2rem 0;">
                            <i class="fas fa-plus-circle" style="font-size: 3rem; color: var(--color-primary-light);"></i>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-building"></i>
                        <h3>No hay sedes registradas</h3>
                        <p>Para comenzar, agrega una nueva sede haciendo clic en el botón inferior</p>
                        <button class="btn-primary ripple" onclick="mostrarModalNuevaSede()" 
        style="margin-top: 1.5rem; 
               cursor: pointer;
               transition: all 0.3s ease;
               transform: scale(1);
               box-shadow: 0 2px 5px rgba(0,0,0,0.1);"
        onmouseover="this.style.transform='scale(1.03)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)'"
        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.1)'"> Agregar Primera Sede
</button>
                    </div>
                <?php endif; ?>
            </section>

            <!-- 📜 Tabla de Salones o Computadoras -->
            <section class="data-section">
                <div class="table-container">
                    <div class="table-header">
                        <?php if (isset($_GET['sede_id'])): ?>
                        <h2 class="table-title">Salones de la Sede</h2>
                        <div class="table-actions">
                            <button class="btn-primary ripple" id="btnAgregarSalon">
                                <i class="fas fa-plus"></i> Agregar Salón
                            </button>
                        </div>
                        <?php elseif (isset($_GET['salon_id'])): ?>
                        <h2 class="table-title">Computadoras del Salón</h2>
                        <div class="table-actions">
                            <button class="btn-primary ripple" id="btnAgregarComputadora">
                                <i class="fas fa-plus"></i> Agregar Computadora
                            </button>
                        </div>
                        <?php else: ?>
                        <h2 class="table-title"><?php echo empty($sedes) ? 'Registra una sede' : 'Seleccione una sede para ver los salones'; ?></h2>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($_GET['sede_id']) && !empty($salones)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Piso</th>
                                <th>Capacidad</th>
                                <th>Computadoras</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salones as $salon): ?>
                            <tr onclick="window.location.href='dashboard.php?salon_id=<?php echo $salon['id']; ?>'">
                                <td>
                                    <div class="pc-info">
                                        <i class="fas fa-door-open" style="font-size: 1.8rem; color: var(--color-primary);"></i>
                                        <div class="pc-details">
                                            <strong><?php echo htmlspecialchars($salon['codigo_salon']); ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($salon['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($salon['piso']); ?></td>
                                <td><?php echo htmlspecialchars($salon['capacidad']); ?></td>
                                <td>
                                    <div class="badge" style="background: rgba(108, 92, 231, 0.1); color: var(--color-primary);">
                                        <i class="fas fa-laptop"></i>
                                        <?php echo htmlspecialchars($salon['numero_computadores']); ?>
                                    </div>
                                </td>
                                <td class="actions">
                                    <button class="view-btn ripple" onclick="event.stopPropagation(); verSalon(<?php echo $salon['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="edit-btn ripple" onclick="event.stopPropagation(); editarSalon(<?php echo $salon['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-btn ripple" onclick="event.stopPropagation(); eliminarSalon(<?php echo $salon['id']; ?>)">
                                        <i class="fas fa-trash" style="color:#f93b3b;"></i>
                                    </button>
                                </td>
                                
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
    <button class="pagination-btn" onclick="changePage('salones', 'prev')" disabled>
        <i class="fas fa-chevron-left"></i>
    </button>
    <span class="pagination-info">Página <span id="salones-current-page">1</span></span>
    <button class="pagination-btn" onclick="changePage('salones', 'next')">
        <i class="fas fa-chevron-right"></i>
    </button>
</div>
                    
                    <?php elseif (isset($_GET['sede_id']) && empty($salones)): ?>
                    <div style="text-align: center; padding: 3rem 0;">
                        <i class="fas fa-door-open" style="font-size: 4rem; color: rgba(108, 92, 231, 0.2); margin-bottom: 1.5rem;"></i>
                        <h3 style="color: var(--color-dark-light); margin-bottom: 1rem;">No hay salones registrados</h3>
                        <p style="color: var(--color-dark-light); margin-bottom: 2rem;">Agrega un nuevo salón haciendo clic en el botón superior</p>
                        <button class="btn-primary ripple" id="btnAgregarPrimerSalon" style="padding: 0.8rem 1.8rem; cursor: pointer;">
                            <i class="fas fa-plus"></i> Agregar Primer Salón
                        </button>
                    </div>
                    <?php elseif (isset($_GET['salon_id']) && !empty($computadoras)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Marca/Modelo</th>
                                <th>Especificaciones</th>
                                <th>Estado</th>
                                <th>Últ. Mantenimiento</th>
                                <th>Acciones</th>

                        </thead>
                        <tbody>
                            <?php foreach ($computadoras as $pc): ?>
                            <tr>
                                <td>
                                    <div class="pc-info">
                                        <i class="fas fa-laptop" style="font-size: 2rem; color: var(--color-primary);"></i>
                                        <div class="pc-details">
                                            <strong><?php echo htmlspecialchars($pc['codigo_patrimonio']); ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="pc-info">
                                        <div class="pc-details">
                                            <strong><?php echo htmlspecialchars($pc['marca']); ?></strong>
                                            <p><?php echo htmlspecialchars($pc['modelo']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="pc-details">
                                        <p><strong>SO:</strong> <?php echo htmlspecialchars($pc['sistema_operativo']); ?></p>
                                        <p><strong>RAM:</strong> <?php echo htmlspecialchars($pc['ram_gb']); ?>GB</p>
                                        <p><strong>Alm.:</strong> <?php echo htmlspecialchars($pc['almacenamiento_gb']); ?>GB <?php echo htmlspecialchars($pc['tipo_almacenamiento']); ?></p>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $badge_class = '';
                                    if ($pc['estado'] == 'operativo') {
                                        $badge_class = 'badge-success';
                                    } elseif ($pc['estado'] == 'mantenimiento') {
                                        $badge_class = 'badge-warning';
                                    } else {
                                        $badge_class = 'badge-danger';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <i class="fas fa-<?php echo $pc['estado'] == 'operativo' ? 'check-circle' : ($pc['estado'] == 'mantenimiento' ? 'tools' : 'exclamation-triangle'); ?>"></i>
                                        <?php echo ucfirst($pc['estado']); ?>
                                    </span>
                                </td>
                               <td><?php echo $pc['ultimo_mantenimiento'] ? htmlspecialchars($pc['ultimo_mantenimiento']) : 'Nunca'; ?></td>
                                <td class="actions">
                                    <button class="view-btn ripple" onclick="verComputadora(<?php echo $pc['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="edit-btn ripple" onclick="editarComputadora(<?php echo $pc['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-btn ripple" onclick="eliminarComputadora(<?php echo $pc['id']; ?>)">
                                        <i class="fas fa-trash" style="color:#f93b3b;"></i>
                                    </button>
                                </td>
                        


                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
    <button class="pagination-btn" onclick="changePage('computadoras', 'prev')" disabled>
        <i class="fas fa-chevron-left"></i>
    </button>
    <span class="pagination-info">Página <span id="computadoras-current-page">1</span></span>
    <button class="pagination-btn" onclick="changePage('computadoras', 'next')">
        <i class="fas fa-chevron-right"></i>
    </button>
</div>

                    <?php elseif (isset($_GET['salon_id']) && empty($computadoras)): ?>
                    <div style="text-align: center; padding: 3rem 0;">
                        <i class="fas fa-laptop" style="font-size: 4rem; color: rgba(108, 92, 231, 0.2); margin-bottom: 1.5rem;"></i>
                        <h3 style="color: var(--color-dark-light); margin-bottom: 1rem;">No hay computadoras registradas</h3>
                        <p style="color: var(--color-dark-light); margin-bottom: 2rem;">Agrega una nueva computadora haciendo clic en el botón superior</p>
                        <button class="btn-primary ripple" id="btnAgregarPrimeraComputadora" style="padding: 0.8rem 1.8rem; cursor: pointer;">
                            <i class="fas fa-plus"></i> Agregar Primera Computadora
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </section>

        <section class="data-section" id="incidencias-section">
               <div class="dashboard-cards">
        <!-- Tarjeta con gráfica (más grande) -->
        <div class="card stat-card large-card">
            <div class="card-header">
                <h3>Incidencias últimos 7 días</h3>
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="chart-container">
                <canvas id="incidentTrendChart"></canvas>
            </div>
            <div class="card-footer">
                <span>Tendencia de incidencias reportadas</span>
            </div>
        </div>

        <!-- Tarjeta de incidencias resueltas -->
        <div class="card stat-card">
            <div class="card-header">
                <h3>Resueltas</h3>
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-number" id="resueltas-count">0</div>
            <div class="card-footer">
                <span>Incidencias solucionadas</span>
            </div>
        </div>

        <!-- Tarjeta de incidencias pendientes -->
        <div class="card stat-card">
            <div class="card-header">
                <h3>Pendientes</h3>
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number" id="pendientes-count">0</div>
            <div class="card-footer">
                <span>Esperando asignación</span>
            </div>
        </div>

        <!-- Tarjeta de incidencias en proceso -->
        <div class="card stat-card">
            <div class="card-header">
                <h3>En Proceso</h3>
                <i class="fas fa-tools"></i>
            </div>
            <div class="stat-number" id="proceso-count">0</div>
            <div class="card-footer">
                <span>En reparación</span>
            </div>
        </div>

        <div class="card stat-card">
    <div class="card-header">
        <h3>Asignadas</h3>
        <i class="fas fa-user-check"></i>
    </div>
    <div class="stat-number" id="asignadas-count">0</div>
    <div class="card-footer">
        <span>Asignadas a técnicos</span>
    </div>
</div>
    </div>
            <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">Incidencias Reportadas</h2>
                        <div class="table-actions">
                            <button class="btn-primary ripple" id="btnReportarIncidencia">
                                <i class="fas fa-plus"></i> Reportar Incidencia
                            </button>
                        </div>
                    </div>
                    
                    <table id="tablaIncidencias">
                        <thead>
                            <tr>
                                
                                <th>Computadora</th>
                                <th>Título</th>
                                <th>Reportado por</th>
                                <th>Asignado a</th>
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Fecha Reporte</th>
                                <th>Fecha Asignación</th>
                                <th>Fecha Resolución</th>
                                <th>Acciones</th>

                        </thead>
                        <tbody>
                            <!-- Las incidencias se cargarán aquí mediante JavaScript -->
                        </tbody>
                    </table>
                    <div class="pagination">
    <button class="pagination-btn" onclick="changePage('incidencias', 'prev')" disabled>
        <i class="fas fa-chevron-left"></i>
    </button>
    <span class="pagination-info">Página <span id="incidencias-current-page">1</span></span>
    <button class="pagination-btn" onclick="changePage('incidencias', 'next')">
        <i class="fas fa-chevron-right"></i>
    </button>
</div>
                </div>
        </section>
        <section class="data-section" id="reparaciones-section">
            <div class="dashboard-cards">
    <!-- Tarjeta con gráfica circular -->
    

    <!-- Tarjeta de reparaciones pendientes -->
    <div class="card stat-card">
        <div class="card-header">
            <h3>Pendientes</h3>
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-number" id="reparaciones-pendientes-count">0</div>
        <div class="card-footer">
            <span>Reparaciones pendientes</span>
        </div>
    </div>

    <!-- Tarjeta de reparaciones en proceso -->
    <div class="card stat-card">
        <div class="card-header">
            <h3>En Proceso</h3>
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-number" id="reparaciones-proceso-count">0</div>
        <div class="card-footer">
            <span>En reparación</span>
        </div>
    </div>
    <div class="card stat-card large-card">
        <div class="card-header">
            <h3>Distribución de Reparaciones</h3>
            <i class="fas fa-chart-pie"></i>
        </div>
        <div class="chart-container">
            <canvas id="reparacionesPieChart"></canvas>
        </div>
        <div class="card-footer">
            <span>Por estado de reparación</span>
        </div>
    </div>
    

    <!-- Tarjeta de reparaciones completadas -->
    <div class="card stat-card">
        <div class="card-header">
            <h3>Completadas</h3>
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-number" id="reparaciones-completadas-count">0</div>
        <div class="card-footer">
            <span>Reparaciones finalizadas</span>
        </div>
    </div>
    
</div>
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Historial de Reparaciones</h2>
                <div class="table-actions">
                    <button class="btn-primary ripple" id="btnAgregarReparacion">
                        <i class="fas fa-plus"></i> Agregar Reparación
                    </button>
                </div>
            </div>
            
            <table id="tablaReparaciones">
    <thead>
        <tr>
            <th>Computadora</th>
            <th>Fecha Reporte</th>
            <th>Fecha Completada</th>
            <th>Estado Reparación</th>
            <th>Técnico</th>
            <th>Problema</th>
            <th>Solución</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($reparaciones)): ?>
            <?php foreach ($reparaciones as $reparacion): ?>
            <tr>
                <td><?php echo htmlspecialchars($reparacion['codigo_patrimonio']); ?></td>
                <td><?php echo htmlspecialchars($reparacion['fecha_reparacion']); ?></td>
                <td><?php echo htmlspecialchars($reparacion['fecha_completada'] ?? 'Pendiente'); ?></td>
                <td>
    <?php 
    $badge_class = '';
    switch($reparacion['estado_reparacion']) {
        case 'completada':
            $badge_class = 'badge-success';
            break;
        case 'en_proceso':
            $badge_class = 'badge-warning';
            break;
        case 'pendiente':
        default:
            $badge_class = 'badge-danger';
    }
    
    // Formatear el texto para mostrar espacios
    $estado_texto = str_replace('_', ' ', $reparacion['estado_reparacion']);
    $estado_texto = ucfirst($estado_texto);
    ?>
    <span class="badge <?php echo $badge_class; ?>">
        <?php echo $estado_texto; ?>
    </span>
</td>
                <td><?php echo htmlspecialchars($reparacion['persona_realizo']); ?></td>
                <td><?php echo htmlspecialchars(substr($reparacion['descripcion'], 0, 50)); ?>...</td>
                <td><?php echo htmlspecialchars(substr($reparacion['solucion'], 0, 50)); ?>...</td>
                <td class="actions">
                    <button class="view-btn ripple" onclick="verReparacion(<?php echo $reparacion['id']; ?>)">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="edit-btn ripple" onclick="editarReparacion(<?php echo $reparacion['id']; ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="delete-btn ripple" onclick="eliminarReparacion(<?php echo $reparacion['id']; ?>, event)">
                        <i class="fas fa-trash" style="color:#f93b3b;"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center;">
                    No hay reparaciones registradas
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<div class="pagination">
    <button class="pagination-btn" onclick="changePage('reparaciones', 'prev')" disabled>
        <i class="fas fa-chevron-left"></i>
    </button>
    <span class="pagination-info">Página <span id="reparaciones-current-page">1</span></span>
    <button class="pagination-btn" onclick="changePage('reparaciones', 'next')">
        <i class="fas fa-chevron-right"></i>
    </button>
</div>
        </div>
    </section>
     <!-- Modal para reparaciones - Código modificado -->
<div class="modal" id="modalReparacion">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-tools"></i> <span id="modalReparacionTitulo">Agregar Reparación</span></h3>
            <button class="close-modal" id="closeModalReparacion">&times;</button>
        </div>
        
        <form id="formReparacion" action="guardar_reparacion.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="id" id="reparacion_id">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="computadora_reparacion">Computadora</label>
                    <select id="computadora_reparacion" name="computadora_id" required>
                        <option value="">Seleccione una computadora</option>
                        <?php 
                        // Obtener todas las computadoras con información de salón
                        $stmt = $conn->query("SELECT c.id, c.codigo_patrimonio, c.marca, c.modelo, s.codigo_salon as salon 
                                             FROM computadores c
                                             JOIN salones s ON c.salon_id = s.id
                                             ORDER BY s.codigo_salon, c.codigo_patrimonio");
                        $allComputers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $currentSalon = '';
                        foreach ($allComputers as $pc): 
                            if ($pc['salon'] != $currentSalon) {
                                $currentSalon = $pc['salon'];
                                echo '<optgroup label="Salón: ' . htmlspecialchars($currentSalon) . '">';
                            }
                        ?>
                            <option value="<?php echo $pc['id']; ?>">
                                <?php echo htmlspecialchars($pc['codigo_patrimonio'] . ' - ' . $pc['marca'] . ' ' . $pc['modelo']); ?>
                            </option>
                        <?php 
                            if ($pc['salon'] != $currentSalon) {
                                echo '</optgroup>';
                            }
                        endforeach; 
                        ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_reparacion">Fecha de Reporte</label>
                        <input type="date" id="fecha_reparacion" name="fecha_reparacion" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_completada">Fecha de Reparación</label>
                        <input type="date" id="fecha_completada" name="fecha_completada">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="persona_reporto">Persona que Reportó</label>
                        <input type="text" id="persona_reporto" name="persona_reporto" required>
                    </div>
                                        
                    <div class="form-group">
                        <label for="persona_realizo">Técnico</label>
                        <input type="text" id="persona_realizo" name="persona_realizo" required>
                    </div>
                </div>
                
                <!-- Nuevo campo: Estado de Reparación -->
                <div class="form-group">
                    <label for="estado_reparacion">Estado de Reparación</label>
                    <select id="estado_reparacion" name="estado_reparacion" required>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="completada">Completada</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="descripcion_reparacion">Descripción del Problema</label>
                    <textarea id="descripcion_reparacion" name="descripcion" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="solucion_reparacion">Solución Aplicada</label>
                    <textarea id="solucion_reparacion" name="solucion" rows="4" required></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel ripple" id="cancelarFormReparacion">Cancelar</button>
                <button type="submit" class="btn-submit ripple">
                    <span class="btn-text" id="btnSubmitReparacion">Guardar Reparación</span>
                </button>
            </div>
        </form>
    </div>
</div>
    
    <!-- Modal para agregar salones -->
    <div class="modal" id="modalSalon">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-laptop-house"></i> Agregar Nuevo Salón</h3>
                <button class="close-modal" id="closeModalSalon">&times;</button>
            </div>
            
            <form id="formSalon" action="guardar_salon.php" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="sede_id" value="<?php echo isset($_GET['sede_id']) ? $_GET['sede_id'] : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="codigo_salon">Código del Salón</label>
                        <input type="text" id="codigo_salon" name="codigo_salon" placeholder="Ej: A101" required>
                        <div class="invalid-feedback">Por favor ingrese el código del salón</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="piso">Piso</label>
                            <input type="number" id="piso" name="piso" min="1" required>
                            <div class="invalid-feedback">Por favor ingrese el piso</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="capacidad">Capacidad</label>
                            <input type="number" id="capacidad" name="capacidad" min="1" required>
                            <div class="invalid-feedback">Por favor ingrese la capacidad</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_computadores">Número de Computadoras</label>
                        <input type="number" id="numero_computadores" name="numero_computadores" min="0" required>
                        <div class="invalid-feedback">Por favor ingrese el número de computadoras</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3" placeholder="Descripción del salón..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel ripple" id="cancelarFormSalon">Cancelar</button>
                    <button type="submit" class="btn-submit ripple">
                        <span class="btn-text">Guardar Salón</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
   <!-- Modal para agregar computadoras -->
<div class="modal" id="modalComputadora">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-laptop"></i> <span id="modalComputadoraTitulo">Agregar Nueva Computadora</span></h3>
            <button class="close-modal" id="closeModalComputadora">&times;</button>
        </div>
        
        <form id="formComputadora" action="guardar_computadora.php" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="salon_id" value="<?php echo isset($_GET['salon_id']) ? $_GET['salon_id'] : ''; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" id="computadora_id" value="">
                <input type="hidden" name="imagen_actual" id="imagen_actual" value="">
                
                <div class="form-group">
                    <label for="codigo_patrimonio">Código Patrimonial</label>
                    <input type="text" id="codigo_patrimonio" name="codigo_patrimonio" placeholder="Ej: PAT-001" required>
                    <div class="invalid-feedback">Por favor ingrese el código patrimonial</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="marca">Marca</label>
                        <input type="text" id="marca" name="marca" placeholder="Ej: Dell, HP" required>
                        <div class="invalid-feedback">Por favor ingrese la marca</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modelo">Modelo</label>
                        <input type="text" id="modelo" name="modelo" placeholder="Ej: OptiPlex 7080" required>
                        <div class="invalid-feedback">Por favor ingrese el modelo</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="sistema_operativo">Sistema Operativo</label>
                    <input type="text" id="sistema_operativo" name="sistema_operativo" placeholder="Ej: Windows 10 Pro">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ram_gb">RAM (GB)</label>
                        <input type="number" id="ram_gb" name="ram_gb" min="1" step="1" required>
                        <div class="invalid-feedback">Por favor ingrese la cantidad de RAM</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="almacenamiento_gb">Almacenamiento (GB)</label>
                        <input type="number" id="almacenamiento_gb" name="almacenamiento_gb" min="1" step="1" required>
                        <div class="invalid-feedback">Por favor ingrese el almacenamiento</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tipo_almacenamiento">Tipo de Almacenamiento</label>
                    <select id="tipo_almacenamiento" name="tipo_almacenamiento" required>
                        <option value="HDD">HDD</option>
                        <option value="SSD">SSD</option>
                        <option value="NVMe">NVMe</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" required>
                        <option value="operativo">Operativo</option>
                        <option value="mantenimiento">En Mantenimiento</option>
                        <option value="dañado">Dañado</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_instalacion">Fecha de Instalación</label>
                        <input type="date" id="fecha_instalacion" name="fecha_instalacion">
                    </div>
                    
                    <div class="form-group">
                        <label for="ultimo_mantenimiento">Último Mantenimiento</label>
                        <input type="date" id="ultimo_mantenimiento" name="ultimo_mantenimiento">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="3" placeholder="Notas adicionales..."></textarea>
                </div>
                
                
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel ripple" id="cancelarFormComputadora">Cancelar</button>
                <button type="submit" class="btn-submit ripple">
                    <span class="btn-text" id="btnSubmitComputadora">Guardar Computadora</span>
                </button>
            </div>
        </form>
    </div>
</div>
    
    <!-- Modal para agregar nueva sede -->
    <div class="modal" id="modalNuevaSede">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-building"></i> Agregar Nueva Sede</h3>
                <button class="close-modal" id="closeModalNuevaSede">&times;</button>
            </div>
            
            <form id="formNuevaSede" action="guardar_sede.php" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="nombre_sede">Nombre de la Sede</label>
                        <input type="text" id="nombre_sede" name="nombre_sede" placeholder="Ej: Sede Principal" required>
                        <div class="invalid-feedback">Por favor ingrese el nombre de la sede</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="direccion_sede">Dirección</label>
                        <input type="text" id="direccion_sede" name="direccion_sede" placeholder="Ej: Av. Principal 123" required>
                        <div class="invalid-feedback">Por favor ingrese la dirección</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="responsable_sede">Responsable</label>
                        <input type="text" id="responsable_sede" name="responsable_sede" placeholder="Ej: Juan Pérez" required>
                        <div class="invalid-feedback">Por favor ingrese el responsable</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono_sede">Teléfono</label>
                        <input type="text" id="telefono_sede" name="telefono_sede" placeholder="Ej: +51 987654321">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion_sede">Descripción</label>
                        <textarea id="descripcion_sede" name="descripcion_sede" rows="3" placeholder="Información adicional sobre la sede..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel ripple" id="cancelarFormNuevaSede">Cancelar</button>
                    <button type="submit" class="btn-submit ripple">
                        <span class="btn-text">Guardar Sede</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
<!-- Modal para reportar/editar incidencias -->
<div class="modal" id="modalIncidencia">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-exclamation-triangle"></i> <span id="modalIncidenciaTitulo">Reportar Incidencia</span></h3>
            <button class="close-modal" id="closeModalIncidencia">&times;</button>
        </div>
        
        <form id="formIncidencia" action="guardar_incidencia.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="id" id="incidencia_id">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="sede_incidencia">Sede</label>
                    <select id="sede_incidencia" name="sede_id" required>
                        <option value="">Seleccione una sede</option>
                        <?php foreach ($sedes as $sede): ?>
                            <option value="<?php echo $sede['id']; ?>"><?php echo htmlspecialchars($sede['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="salon_incidencia">Salón</label>
                    <select id="salon_incidencia" name="salon_id" required disabled>
                        <option value="">Seleccione un salón</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="computadora_incidencia">Computadora</label>
                    <select id="computadora_incidencia" name="computador_id" required disabled>
                        <option value="">Seleccione una computadora</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="titulo_incidencia">Título</label>
                    <input type="text" id="titulo_incidencia" name="titulo" placeholder="Ej: No enciende, Problema de red..." required>
                </div>
                
                <div class="form-group">
                    <label for="descripcion_incidencia">Descripción detallada</label>
                    <textarea id="descripcion_incidencia" name="descripcion" rows="4" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prioridad_incidencia">Prioridad</label>
                        <select id="prioridad_incidencia" name="prioridad" required>
                            <option value="baja">Baja</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Crítica</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado_incidencia">Estado</label>
                        <select id="estado_incidencia" name="estado" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="asignado">Asignado</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="resuelto">Resuelto</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="asignado_nombre_incidencia">Nombre de la persona asignada</label>
                    <input type="text" id="asignado_nombre_incidencia" name="asignado_nombre" placeholder="Nombre del técnico">
                </div>
                
                <div class="form-group">
                    <label for="solucion_incidencia">Solución (si aplica)</label>
                    <textarea id="solucion_incidencia" name="solucion" rows="3" placeholder="Describa la solución aplicada..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_reporte_incidencia">Fecha de Reporte</label>
                        <input type="date" id="fecha_reporte_incidencia" name="fecha_reporte">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_asignacion_incidencia">Fecha de Asignación</label>
                        <input type="date" id="fecha_asignacion_incidencia" name="fecha_asignacion">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_resolucion_incidencia">Fecha de Resolución</label>
                        <input type="date" id="fecha_resolucion_incidencia" name="fecha_resolucion">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel ripple" id="cancelarFormIncidencia">Cancelar</button>
                <button type="submit" class="btn-submit ripple">
                    <span class="btn-text" id="btnSubmitIncidencia">Reportar Incidencia</span>
                </button>
            </div>
        </form>
    </div>
</div>
<footer class="site-footer">
    <div class="footer-content">
        <a href="https://www.unisimon.edu.co/" target="_blank" rel="noopener">
        <div class="footer-logo">
            <img src="img/uni.png" alt="Logo Universidad" class="university-logo">
        </div>
        </a>
        <div class="footer-info">
            <p>Sistema de Gestión de Computadoras - CompuControl</p>
            <p>© 2025 Universidad simon bolivar </p>
            <p>kevin alexander ortiz ramirez - ingenieria de software</p>
        </div>
    </div>
</footer>
    
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Función para generar avatares con iniciales
    function generarAvatares() {
        document.querySelectorAll('.user-avatar').forEach(avatar => {
            const nombre = avatar.getAttribute('data-name') || '';
            const partesNombre = nombre.trim().split(/\s+/);
            
            let iniciales = '';
            if (partesNombre.length >= 2) {
                iniciales = partesNombre[0].charAt(0).toUpperCase() + partesNombre[1].charAt(0).toUpperCase();
            } else if (partesNombre.length === 1) {
                iniciales = partesNombre[0].charAt(0).toUpperCase();
            }
            
            avatar.textContent = iniciales;
            avatar.setAttribute('data-initial', iniciales.charAt(0));
        });
    }

    // Efecto ripple para botones
    function setupRippleEffects() {
        document.querySelectorAll('.ripple').forEach(button => {
            button.addEventListener('click', function(e) {
                const x = e.clientX - e.target.getBoundingClientRect().left;
                const y = e.clientY - e.target.getBoundingClientRect().top;
                
                const ripple = document.createElement('span');
                ripple.classList.add('ripple-effect');
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    // Animaciones cuando los elementos son visibles
    function setupAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.card, .table-container').forEach(el => {
            observer.observe(el);
        });
        
        // Animación para las tarjetas de sedes
        document.querySelectorAll('.card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    }

    // Validación de formularios
    function setupFormValidation() {
        document.querySelectorAll('.needs-validation').forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!this.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.classList.add('was-validated');
                    
                    // Enfocar el primer campo inválido
                    const invalidField = this.querySelector(':invalid');
                    if (invalidField) {
                        invalidField.focus();
                    }
                    
                    return false;
                }
                
                const formData = new FormData(this);
                const action = this.getAttribute('action');
                
                Swal.fire({
                    title: 'Guardando...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                        
                        fetch(action, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error en la respuesta del servidor');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: '¡Éxito!',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar'
                                }).then(() => {
                                    // Cerrar el modal si existe
                                    const modal = this.closest('.modal');
                                    if (modal) {
                                        modal.classList.remove('show');
                                        document.body.style.overflow = 'auto';
                                    }
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(data.message || 'Error al guardar los datos');
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Error',
                                text: error.message,
                                icon: 'error',
                                confirmButtonText: 'Entendido'
                            });
                        });
                    }
                });
                
                event.preventDefault();
            }, false);
        });
    }

    // Toggle del menú en móvil
    function setupMobileMenu() {
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    }

    // Función de búsqueda
    function setupSearch() {
        document.querySelector('.search-bar button').addEventListener('click', buscar);
        document.querySelector('.search-bar input').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') buscar();
        });

        function buscar() {
            const termino = document.querySelector('.search-bar input').value.toLowerCase();
            const filas = document.querySelectorAll('tbody tr');
            
            filas.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                fila.style.display = textoFila.includes(termino) ? '' : 'none';
            });
        }
    }

    // Cambiar ítem activo del menú y mostrar sección correspondiente
    function setupMenuNavigation() {
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            // Ocultar todas las secciones principales
            document.querySelectorAll('#inicio-section, #incidencias-section, #reparaciones-section').forEach(sec => {
                sec.style.display = 'none';
            });
            
            const section = this.getAttribute('data-section');
            
            // Mostrar solo la sección seleccionada
            if (section === 'inicio') {
                document.getElementById('inicio-section').style.display = 'block';
            } else if (section === 'incidencias') {
                document.getElementById('incidencias-section').style.display = 'block';
                cargarIncidencias(); // Cargar datos de incidencias si es necesario
            } else if (section === 'reparaciones') {
                document.getElementById('reparaciones-section').style.display = 'block'; // Solo visible aquí
            }
            
            // Resaltar ítem del menú activo
            menuItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

    // Función para cerrar sesión
    function setupLogout() {
        window.confirmLogout = function() {
            Swal.fire({
                title: 'Cerrar Sesión',
                html: '¿Estás seguro de que deseas salir de tu cuenta?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Cerrar Sesión',
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp animate__faster'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Cerrando sesión...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'logout.php';
                    }, 800);
                }
            });
        }
    }

    // Manejo de modales
    function setupModals() {
        // Modal para salones
        const modalSalon = document.getElementById('modalSalon');
        const btnAgregarSalon = document.getElementById('btnAgregarSalon');
        const btnAgregarPrimerSalon = document.getElementById('btnAgregarPrimerSalon');
        const closeModalSalon = document.getElementById('closeModalSalon');
        const cancelarFormSalon = document.getElementById('cancelarFormSalon');

        window.mostrarModalSalon = function() {
            modalSalon.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Resetear el formulario
            const form = document.getElementById('formSalon');
            form.reset();
            form.classList.remove('was-validated');
        }

        if (btnAgregarSalon) {
            btnAgregarSalon.addEventListener('click', mostrarModalSalon);
        }

        if (btnAgregarPrimerSalon) {
            btnAgregarPrimerSalon.addEventListener('click', mostrarModalSalon);
        }

        closeModalSalon.addEventListener('click', () => {
            modalSalon.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        cancelarFormSalon.addEventListener('click', () => {
            modalSalon.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        modalSalon.addEventListener('click', (e) => {
            if (e.target === modalSalon) {
                modalSalon.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });

        // Modal para computadoras
        const modalComputadora = document.getElementById('modalComputadora');
        const btnAgregarComputadora = document.getElementById('btnAgregarComputadora');
        const btnAgregarPrimeraComputadora = document.getElementById('btnAgregarPrimeraComputadora');
        const closeModalComputadora = document.getElementById('closeModalComputadora');
        const cancelarFormComputadora = document.getElementById('cancelarFormComputadora');

        window.mostrarModalComputadora = function() {
            modalComputadora.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Resetear el formulario
            const form = document.getElementById('formComputadora');
            form.reset();
            form.classList.remove('was-validated');
        }

        if (btnAgregarComputadora) {
            btnAgregarComputadora.addEventListener('click', mostrarModalComputadora);
        }

        if (btnAgregarPrimeraComputadora) {
            btnAgregarPrimeraComputadora.addEventListener('click', mostrarModalComputadora);
        }

        closeModalComputadora.addEventListener('click', () => {
            modalComputadora.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        cancelarFormComputadora.addEventListener('click', () => {
            modalComputadora.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        modalComputadora.addEventListener('click', (e) => {
            if (e.target === modalComputadora) {
                modalComputadora.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });

        // Modal para nueva sede
        const modalNuevaSede = document.getElementById('modalNuevaSede');
        const closeModalNuevaSede = document.getElementById('closeModalNuevaSede');
        const cancelarFormNuevaSede = document.getElementById('cancelarFormNuevaSede');

        window.mostrarModalNuevaSede = function() {
            modalNuevaSede.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Resetear el formulario
            const form = document.getElementById('formNuevaSede');
            form.reset();
            form.classList.remove('was-validated');
        }

        closeModalNuevaSede.addEventListener('click', () => {
            modalNuevaSede.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        cancelarFormNuevaSede.addEventListener('click', () => {
            modalNuevaSede.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        modalNuevaSede.addEventListener('click', (e) => {
            if (e.target === modalNuevaSede) {
                modalNuevaSede.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });

        // Modal para incidencias
        const modalIncidencia = document.getElementById('modalIncidencia');
        const btnReportarIncidencia = document.getElementById('btnReportarIncidencia');
        const closeModalIncidencia = document.getElementById('closeModalIncidencia');
        const cancelarFormIncidencia = document.getElementById('cancelarFormIncidencia');

        window.mostrarModalIncidencia = function() {
            modalIncidencia.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Resetear el formulario
            const form = document.getElementById('formIncidencia');
            form.reset();
            form.classList.remove('was-validated');
        };

        if (btnReportarIncidencia) {
            btnReportarIncidencia.addEventListener('click', mostrarModalIncidencia);
        }

        closeModalIncidencia.addEventListener('click', () => {
            modalIncidencia.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        cancelarFormIncidencia.addEventListener('click', () => {
            modalIncidencia.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        modalIncidencia.addEventListener('click', (e) => {
            if (e.target === modalIncidencia) {
                modalIncidencia.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });
    }

    // Funciones para CRUD de salones
    function setupSalonesFunctions() {
       window.verSalon = function(id) {
    fetch(`obtener_salon.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al obtener los datos del salón');
            }
            return response.json();
        })
        .then(salon => {
            // Crear el HTML con diseño centrado y mejorado
            let htmlContent = `
                <div class="pc-detail-container">
                    <div class="pc-header">
                        <i class="fas fa-door-open pc-icon"></i>
                        <div class="pc-title">
                            <h3>${salon.codigo_salon}</h3>
                            <p class="pc-subtitle">Piso ${salon.piso} - ${salon.capacidad} personas</p>
                        </div>
                    </div>
                    
                    <div class="pc-detail-grid">
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-info-circle"></i> Información Básica</h4>
                            <div class="detail-item">
                                <span class="detail-label">Código:</span>
                                <span class="detail-value">${salon.codigo_salon}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Piso:</span>
                                <span class="detail-value">${salon.piso}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Capacidad:</span>
                                <span class="detail-value">${salon.capacidad} personas</span>
                            </div>
                        </div>
                        
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-laptop"></i> Equipamiento</h4>
                            <div class="detail-item">
                                <span class="detail-label">Computadoras:</span>
                                <span class="detail-value">
                                    <span class="badge" style="background: rgba(108, 92, 231, 0.1); color: var(--color-primary);">
                                        <i class="fas fa-laptop"></i> 
                                        ${salon.numero_computadores}
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value">
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i> 
                                        Activo
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pc-observations">
                        <h4 class="section-title"><i class="fas fa-clipboard"></i> Descripción</h4>
                        <div class="observations-content">${salon.descripcion || 'No hay descripción disponible'}</div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'Detalles del Salón',
                html: htmlContent,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                width: '800px',
                customClass: {
                    popup: 'pc-detail-popup',
                    container: 'pc-detail-container'
                }
            });
        })
        .catch(error => {
            Swal.fire({
                title: 'Error',
                text: error.message,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        });
};

        window.editarSalon = function(id) {
            fetch(`obtener_salon.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al obtener los datos del salón');
                    }
                    return response.json();
                })
                .then(salon => {
                    // Clonar el formulario de agregar
                    const modal = document.getElementById('modalSalon');
                    const clone = modal.cloneNode(true);
                    clone.id = 'modalEditarSalon';
                    
                    // Cambiar título
                    clone.querySelector('.modal-title').innerHTML = '<i class="fas fa-edit"></i> Editar Salón';
                    
                    // Llenar formulario con datos
                    const form = clone.querySelector('form');
                    form.action = 'actualizar_salon.php';
                    form.querySelector('[name="codigo_salon"]').value = salon.codigo_salon;
                    form.querySelector('[name="piso"]').value = salon.piso;
                    form.querySelector('[name="capacidad"]').value = salon.capacidad;
                    form.querySelector('[name="numero_computadores"]').value = salon.numero_computadores;
                    form.querySelector('[name="descripcion"]').value = salon.descripcion || '';
                    
                    // Agregar campo oculto con el ID
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'id';
                    inputId.value = id;
                    form.appendChild(inputId);
                    
                    // Mostrar modal
                    document.body.appendChild(clone);
                    clone.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    
                    // Manejar cierre del modal
                    clone.querySelector('.close-modal').addEventListener('click', () => {
                        clone.classList.remove('show');
                        document.body.style.overflow = 'auto';
                        setTimeout(() => document.body.removeChild(clone), 300);
                    });
                    
                    clone.querySelector('.btn-cancel').addEventListener('click', () => {
                        clone.classList.remove('show');
                        document.body.style.overflow = 'auto';
                        setTimeout(() => document.body.removeChild(clone), 300);
                    });
                    
                    clone.addEventListener('click', (e) => {
                        if (e.target === clone) {
                            clone.classList.remove('show');
                            document.body.style.overflow = 'auto';
                            setTimeout(() => document.body.removeChild(clone), 300);
                        }
                    });
                    
                    // Validación del formulario
                    form.classList.add('needs-validation');
                    form.addEventListener('submit', function(e) {
                        if (!this.checkValidity()) {
                            e.preventDefault();
                            e.stopPropagation();
                            this.classList.add('was-validated');
                            return false;
                        }
                        
                        const formData = new FormData(this);
                        
                        Swal.fire({
                            title: 'Actualizando salón...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                                
                                fetch(this.action, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Error en la respuesta del servidor');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: '¡Éxito!',
                                            text: data.message,
                                            icon: 'success'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        throw new Error(data.message || 'Error al actualizar el salón');
                                    }
                                })
                                .catch(error => {
                                    Swal.fire('Error', error.message, 'error');
                                });
                            }
                        });
                        
                        e.preventDefault();
                    }, false);
                })
                .catch(error => {
                    Swal.fire('Error', error.message, 'error');
                });
        };

        window.eliminarSalon = function(id) {
            Swal.fire({
                title: '¿Eliminar Salón?',
                html: '¿Estás seguro de que deseas eliminar este salón?<br><b>Esta acción también eliminará todas las computadoras asociadas.</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d63031'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Eliminando...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            
                            fetch('eliminar_salon.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Error en la respuesta del servidor');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Eliminado!',
                                        text: data.message,
                                        icon: 'success'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    throw new Error(data.message || 'Error al eliminar el salón');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error', error.message, 'error');
                            });
                        }
                    });
                }
            });
        };
    }

    // Funciones para CRUD de sedes
    function setupSedesFunctions() {
        window.eliminarSede = function(id, event) {
    event.stopPropagation();
    
    Swal.fire({
        title: '¿Eliminar Sede?',
        html: '¿Estás seguro de que deseas eliminar esta sede?<br><b>Esta acción también eliminará todos los salones y computadoras asociadas.</b>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d63031'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                    
                    fetch('eliminar_sede.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Eliminada!',
                                text: data.message,
                                icon: 'success'
                            }).then(() => {
                                // Redirigir a dashboard.php sin parámetros
                                window.location.href = 'dashboard.php';
                            });
                        } else {
                            throw new Error(data.message || 'Error al eliminar la sede');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', error.message, 'error');
                    });
                }
            });
        }
    });
};
    }

    // Funciones para CRUD de computadoras
    function setupComputadorasFunctions() {
   window.verComputadora = function(id) {
    fetch(`obtener_computadora.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al obtener los datos de la computadora');
            }
            return response.json();
        })
        .then(pcData => {
            // Determinar el color del badge según el estado
            let badgeClass, badgeIcon;
            switch(pcData.estado) {
                case 'operativo':
                    badgeClass = 'badge-success';
                    badgeIcon = 'fa-check-circle';
                    break;
                case 'mantenimiento':
                    badgeClass = 'badge-warning';
                    badgeIcon = 'fa-tools';
                    break;
                default:
                    badgeClass = 'badge-danger';
                    badgeIcon = 'fa-exclamation-triangle';
            }

            // Función corregida para formatear fechas
            const formatDate = (dateStr) => {
                if (!dateStr) return 'N/A';
                
                const date = new Date(dateStr);
                const timezoneOffset = date.getTimezoneOffset() * 60000;
                const adjustedDate = new Date(date.getTime() + timezoneOffset);
                
                return adjustedDate.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            };

            // Crear el HTML con diseño centrado y mejorado
            let htmlContent = `
                <div class="pc-detail-container">
                    <div class="pc-header">
                        <i class="fas fa-laptop pc-icon"></i>
                        <div class="pc-title">
                            <h3>${pcData.codigo_patrimonio}</h3>
                            <p class="pc-subtitle">${pcData.marca} ${pcData.modelo}</p>
                        </div>
                    </div>
                    
                    <div class="pc-detail-grid">
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-microchip"></i> Especificaciones</h4>
                            <div class="detail-item">
                                <span class="detail-label">Sistema Operativo:</span>
                                <span class="detail-value">${pcData.sistema_operativo || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">RAM:</span>
                                <span class="detail-value">${pcData.ram_gb} GB</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Almacenamiento:</span>
                                <span class="detail-value">${pcData.almacenamiento_gb} GB ${pcData.tipo_almacenamiento}</span>
                            </div>
                        </div>
                        
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-info-circle"></i> Estado</h4>
                            <div class="detail-item">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value">
                                    <span class="badge ${badgeClass}">
                                        <i class="fas ${badgeIcon}"></i> 
                                        ${pcData.estado.charAt(0).toUpperCase() + pcData.estado.slice(1)}
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Instalación:</span>
                                <span class="detail-value">${formatDate(pcData.fecha_instalacion)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Últ. Mantenimiento:</span>
                                <span class="detail-value">${formatDate(pcData.ultimo_mantenimiento)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pc-observations">
                        <h4 class="section-title"><i class="fas fa-clipboard"></i> Observaciones</h4>
                        <div class="observations-content">${pcData.observaciones || 'No hay observaciones registradas'}</div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'Detalles de la Computadora',
                html: htmlContent,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                width: '800px',
                customClass: {
                    popup: 'pc-detail-popup',
                    container: 'pc-detail-container'
                }
            });
        })
        .catch(error => {
            Swal.fire({
                title: 'Error',
                text: error.message,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        });
};

        window.editarComputadora = function(id) {
            fetch(`obtener_computadora.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al obtener los datos de la computadora');
                    }
                    return response.json();
                })
                .then(pcData => {
                    // Clonar el formulario de agregar
                    const modal = document.getElementById('modalComputadora');
                    const clone = modal.cloneNode(true);
                    clone.id = 'modalEditarComputadora';
                    
                    // Cambiar título
                    clone.querySelector('.modal-title').innerHTML = '<i class="fas fa-edit"></i> Editar Computadora';
                    
                    // Llenar formulario con datos reales
                    const form = clone.querySelector('form');
                    form.action = 'actualizar_computadora.php';
                    form.querySelector('[name="codigo_patrimonio"]').value = pcData.codigo_patrimonio;
                    form.querySelector('[name="marca"]').value = pcData.marca;
                    form.querySelector('[name="modelo"]').value = pcData.modelo;
                    form.querySelector('[name="sistema_operativo"]').value = pcData.sistema_operativo;
                    form.querySelector('[name="ram_gb"]').value = pcData.ram_gb;
                    form.querySelector('[name="almacenamiento_gb"]').value = pcData.almacenamiento_gb;
                    form.querySelector('[name="tipo_almacenamiento"]').value = pcData.tipo_almacenamiento;
                    form.querySelector('[name="estado"]').value = pcData.estado;
                    form.querySelector('[name="fecha_instalacion"]').value = pcData.fecha_instalacion || '';
                    form.querySelector('[name="ultimo_mantenimiento"]').value = pcData.ultimo_mantenimiento || '';
                    form.querySelector('[name="observaciones"]').value = pcData.observaciones || '';
                    
                    // Agregar campo oculto con el ID
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'id';
                    inputId.value = id;
                    form.appendChild(inputId);
                    
                    // Mostrar modal
                    document.body.appendChild(clone);
                    clone.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    
                    // Manejar cierre del modal
                    clone.querySelector('.close-modal').addEventListener('click', () => {
                        clone.classList.remove('show');
                        document.body.style.overflow = 'auto';
                        setTimeout(() => document.body.removeChild(clone), 300);
                    });
                    
                    clone.querySelector('.btn-cancel').addEventListener('click', () => {
                        clone.classList.remove('show');
                        document.body.style.overflow = 'auto';
                        setTimeout(() => document.body.removeChild(clone), 300);
                    });
                    
                    clone.addEventListener('click', (e) => {
                        if (e.target === clone) {
                            clone.classList.remove('show');
                            document.body.style.overflow = 'auto';
                            setTimeout(() => document.body.removeChild(clone), 300);
                        }
                    });
                    
                    // Manejar envío del formulario
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        const formData = new FormData(this);
                        
                        Swal.fire({
                            title: 'Actualizando computadora...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                                
                                fetch(this.action, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Error en la respuesta del servidor');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: '¡Actualizado!',
                                            text: data.message,
                                            icon: 'success'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        throw new Error(data.message || 'Error al actualizar la computadora');
                                    }
                                })
                                .catch(error => {
                                    Swal.fire('Error', error.message, 'error');
                                });
                            }
                        });
                    });
                })
                .catch(error => {
                    Swal.fire('Error', error.message, 'error');
                });
        };

        window.eliminarComputadora = function(id) {
            Swal.fire({
                title: '¿Eliminar Computadora?',
                text: '¿Estás seguro de que deseas eliminar esta computadora?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d63031'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Eliminando...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            
                            fetch('eliminar_computadora.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Error en la respuesta del servidor');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Eliminada!',
                                        text: data.message,
                                        icon: 'success'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    throw new Error(data.message || 'Error al eliminar la computadora');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error', error.message, 'error');
                            });
                        }
                    });
                }
            });
        };
    }

    // Funciones para manejar incidencias
function setupIncidenciasFunctions() {
    // Cargar incidencias desde la base de datos
window.cargarIncidencias = function() {
    const tbody = document.querySelector('#tablaIncidencias tbody');
    tbody.innerHTML = '<tr><td colspan="10" style="text-align: center;"><div class="loader"></div></td></tr>';

    fetch('obtener_incidencias.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            tbody.innerHTML = '';
            
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" style="text-align: center;">No hay incidencias registradas</td></tr>';
                return;
            }

            const formatDate = (dateString) => {
                if (!dateString) return 'N/A';
                const date = new Date(dateString + 'T00:00:00');
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}/${month}/${year}`;
            };

            const translateEstado = (estado) => {
                const estados = {
                    'pendiente': 'Pendiente',
                    'asignado': 'Asignado',
                    'en_proceso': 'En Proceso',
                    'resuelto': 'Resuelto'
                };
                return estados[estado] || estado;
            };

            const translatePrioridad = (prioridad) => {
                const prioridades = {
                    'baja': 'Baja',
                    'media': 'Media',
                    'alta': 'Alta',
                    'critica': 'Crítica'
                };
                return prioridades[prioridad] || prioridad;
            };

            data.forEach(incidencia => {
                const tr = document.createElement('tr');
                
                let estadoClass = '';
                switch(incidencia.estado) {
                    case 'pendiente': estadoClass = 'badge-danger'; break;
                    case 'asignado': estadoClass = 'badge-info'; break;
                    case 'en_proceso': estadoClass = 'badge-warning'; break;
                    case 'resuelto': estadoClass = 'badge-success'; break;
                    default: estadoClass = 'badge-secondary';
                }
                
                let prioridadClass = '';
                switch(incidencia.prioridad) {
                    case 'baja': prioridadClass = 'badge-success'; break;
                    case 'media': prioridadClass = 'badge-info'; break;
                    case 'alta': prioridadClass = 'badge-warning'; break;
                    case 'critica': prioridadClass = 'badge-danger'; break;
                    default: prioridadClass = 'badge-secondary';
                }

                tr.innerHTML = `
                    <td>${incidencia.computadora_codigo || 'N/A'}</td>
                    <td>${incidencia.titulo}</td>
                    <td>${incidencia.reportador_nombre || 'N/A'}</td>
                    <td>${incidencia.asignado_nombre || 'N/A'}</td>
                    <td><span class="badge ${estadoClass}">${translateEstado(incidencia.estado)}</span></td>
                    <td><span class="badge ${prioridadClass}">${translatePrioridad(incidencia.prioridad)}</span></td>
                    <td>${formatDate(incidencia.fecha_reporte)}</td>
                    <td>${formatDate(incidencia.fecha_asignacion)}</td>
                    <td>${formatDate(incidencia.fecha_resolucion)}</td>
                    <td class="actions">
                        <button class="view-btn ripple" onclick="verIncidencia(${incidencia.id})" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="edit-btn ripple" onclick="editarIncidencia(${incidencia.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="delete-btn ripple" onclick="eliminarIncidencia(${incidencia.id}, event)" title="Eliminar">
                            <i class="fas fa-trash" style="color:#f93b3b;"></i>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(tr);
            });
        })
        .catch(error => {
            console.error('Error al cargar incidencias:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" style="text-align: center; color: red;">
                        Error al cargar incidencias: ${error.message}
                        <br><br>
                        <button onclick="cargarIncidencias()" class="btn-retry ripple">
                            <i class="fas fa-sync-alt"></i> Reintentar
                        </button>
                    </td>
                </tr>
            `;
        });
};

    
   window.verIncidencia = function(id) {
    fetch(`obtener_incidencia.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al obtener los datos de la incidencia');
            }
            return response.json();
        })
        .then(incidencia => {
            // Badge para estado
            let estadoClass, estadoIcon;
            switch (incidencia.estado) {
                case 'pendiente':
                    estadoClass = 'badge-danger';
                    estadoIcon = 'fa-hourglass-start';
                    break;
                case 'asignado':
                    estadoClass = 'badge-info';
                    estadoIcon = 'fa-user-check';
                    break;
                case 'en_proceso':
                    estadoClass = 'badge-warning';
                    estadoIcon = 'fa-spinner';
                    break;
                case 'resuelto':
                    estadoClass = 'badge-success';
                    estadoIcon = 'fa-check-circle';
                    break;
                default:
                    estadoClass = 'badge-secondary';
                    estadoIcon = 'fa-question-circle';
            }

            // Badge para prioridad
            let prioridadClass, prioridadIcon;
            switch (incidencia.prioridad) {
                case 'baja':
                    prioridadClass = 'badge-success';
                    prioridadIcon = 'fa-arrow-down';
                    break;
                case 'media':
                    prioridadClass = 'badge-info';
                    prioridadIcon = 'fa-arrow-right';
                    break;
                case 'alta':
                    prioridadClass = 'badge-warning';
                    prioridadIcon = 'fa-arrow-up';
                    break;
                case 'critica':
                    prioridadClass = 'badge-danger';
                    prioridadIcon = 'fa-exclamation-triangle';
                    break;
                default:
                    prioridadClass = 'badge-secondary';
                    prioridadIcon = 'fa-question-circle';
            }

            // Formatear fechas
            const formatDate = (dateStr) => {
                if (!dateStr) return 'N/A';
                
                const dateObj = new Date(dateStr.includes(' ') ? dateStr : dateStr + 'T00:00:00');
                const hasTime = dateStr.includes(' ') 
                    ? dateStr.split(' ')[1] !== '00:00:00'
                    : false;
                
                return dateObj.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    ...(hasTime && {
                        hour: '2-digit',
                        minute: '2-digit'
                    })
                });
            };

            // HTML con diseño similar a verComputadora
            let htmlContent = `
                <div class="pc-detail-container">
                    <div class="pc-header">
                        <i class="fas fa-exclamation-triangle pc-icon" style="color: #e74c3c;"></i>
                        <div class="pc-title">
                            <h3>Incidencia #${incidencia.id}</h3>
                            <p class="pc-subtitle">${incidencia.titulo}</p>
                        </div>
                    </div>
                    
                    <div class="pc-detail-grid">
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-info-circle"></i> Información Básica</h4>
                            <div class="detail-item">
                                <span class="detail-label">Computadora:</span>
                                <span class="detail-value">${incidencia.computadora_codigo || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Reportado por:</span>
                                <span class="detail-value">${incidencia.reportador_nombre || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Asignado a:</span>
                                <span class="detail-value">${incidencia.asignado_nombre || 'No asignado'}</span>
                            </div>
                        </div>
                        
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-chart-line"></i> Estado y Prioridad</h4>
                            <div class="detail-item">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value">
                                    <span class="badge ${estadoClass}">
                                        <i class="fas ${estadoIcon}"></i> 
                                        ${incidencia.estado.charAt(0).toUpperCase() + incidencia.estado.slice(1).replace('_', ' ')}
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Prioridad:</span>
                                <span class="detail-value">
                                    <span class="badge ${prioridadClass}">
                                        <i class="fas ${prioridadIcon}"></i> 
                                        ${incidencia.prioridad.charAt(0).toUpperCase() + incidencia.prioridad.slice(1)}
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fecha Reporte:</span>
                                <span class="detail-value">${formatDate(incidencia.fecha_reporte)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pc-observations">
                        <h4 class="section-title"><i class="fas fa-align-left"></i> Descripción</h4>
                        <div class="observations-content">${incidencia.descripcion || 'No hay descripción disponible'}</div>
                    </div>
                    
                    ${incidencia.solucion ? `
                    <div class="pc-observations">
                        <h4 class="section-title"><i class="fas fa-check-circle"></i> Solución</h4>
                        <div class="observations-content">${incidencia.solucion}</div>
                    </div>
                    ` : ''}
                    
                    <div class="pc-detail-grid">
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-calendar-alt"></i> Fechas</h4>
                            <div class="detail-item">
                                <span class="detail-label">Asignación:</span>
                                <span class="detail-value">${formatDate(incidencia.fecha_asignacion)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Resolución:</span>
                                <span class="detail-value">${formatDate(incidencia.fecha_resolucion)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'Detalles de la Incidencia',
                html: htmlContent,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                width: '800px',
                customClass: {
                    popup: 'pc-detail-popup',
                    container: 'pc-detail-container'
                }
            });
        })
        .catch(error => {
            Swal.fire({
                title: 'Error',
                text: error.message,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        });
};

    // Editar incidencia
   window.editarIncidencia = function(id) {
    fetch(`obtener_incidencia.php?id=${id}`)
        .then(response => response.json())
        .then(incidencia => {
            document.getElementById('modalIncidenciaTitulo').textContent = 'Editar Incidencia';
            document.getElementById('btnSubmitIncidencia').textContent = 'Actualizar Incidencia';
            document.getElementById('formIncidencia').action = 'actualizar_incidencia.php';
            document.getElementById('incidencia_id').value = incidencia.id;
            
            // Configurar selects bloqueados
            document.getElementById('sede_incidencia').innerHTML = `
                <option value="${incidencia.sede_id}" selected>${incidencia.sede_nombre}</option>
            `;
            document.getElementById('sede_incidencia').disabled = true;
            
            document.getElementById('salon_incidencia').innerHTML = `
                <option value="${incidencia.salon_id}" selected>${incidencia.salon_codigo}</option>
            `;
            document.getElementById('salon_incidencia').disabled = true;
            
            document.getElementById('computadora_incidencia').innerHTML = `
                <option value="${incidencia.computador_id}" selected>${incidencia.computadora_codigo}</option>
            `;
            document.getElementById('computadora_incidencia').disabled = true;
            
            // Llenar campos editables
            document.getElementById('titulo_incidencia').value = incidencia.titulo;
            document.getElementById('descripcion_incidencia').value = incidencia.descripcion;
            document.getElementById('prioridad_incidencia').value = incidencia.prioridad;
            document.getElementById('estado_incidencia').value = incidencia.estado;
            document.getElementById('asignado_nombre_incidencia').value = incidencia.asignado_nombre || '';
            document.getElementById('solucion_incidencia').value = incidencia.solucion || '';
            
            // Configurar fechas
            if (incidencia.fecha_reporte) {
                document.getElementById('fecha_reporte_incidencia').value = incidencia.fecha_reporte.split(' ')[0];
            }
            
            if (incidencia.fecha_asignacion) {
                document.getElementById('fecha_asignacion_incidencia').value = incidencia.fecha_asignacion.split(' ')[0];
            }
            
            if (incidencia.fecha_resolucion) {
                document.getElementById('fecha_resolucion_incidencia').value = incidencia.fecha_resolucion.split(' ')[0];
            }
            
            // Mostrar modal
            document.getElementById('modalIncidencia').classList.add('show');
            document.body.style.overflow = 'hidden';
        });
};

window.eliminarIncidencia = function(id, event) {
    event.stopPropagation();
    
    Swal.fire({
        title: '¿Eliminar Incidencia?',
        text: '¿Estás seguro de que deseas eliminar esta incidencia permanentemente?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d63031'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                    
                    fetch('eliminar_incidencia.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Eliminada!',
                                text: data.message,
                                icon: 'success',
                                showConfirmButton: true,        // Mostrar botón
                                confirmButtonText: 'OK'         // Texto del botón
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Actualizar la tabla de incidencias
                                    cargarIncidencias();
                                    
                                    // Actualizar las estadísticas y la gráfica
                                    actualizarEstadisticasYGrafica();
                                }
                            });
                        } else {
                            throw new Error(data.message || 'Error al eliminar la incidencia');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', error.message, 'error');
                    });
                }
            });
        }
    });
};
    
    // Configurar eventos para el modal de incidencias
    const modalIncidencia = document.getElementById('modalIncidencia');
    const btnReportarIncidencia = document.getElementById('btnReportarIncidencia');
    const closeModalIncidencia = document.getElementById('closeModalIncidencia');
    const cancelarFormIncidencia = document.getElementById('cancelarFormIncidencia');
    
    // Función para resetear el modal
function resetearModalIncidencia() {
    document.getElementById('modalIncidenciaTitulo').textContent = 'Reportar Incidencia';
    document.getElementById('btnSubmitIncidencia').textContent = 'Reportar Incidencia';
    document.getElementById('formIncidencia').action = 'guardar_incidencia.php';
    document.getElementById('formIncidencia').reset();
    document.getElementById('incidencia_id').value = '';
    document.getElementById('formIncidencia').classList.remove('was-validated');
    
    // Reactivar selects y limpiar opciones
    document.getElementById('sede_incidencia').disabled = false;
    document.getElementById('sede_incidencia').innerHTML = `
        <option value="">Seleccione una sede</option>
        <?php foreach ($sedes as $sede): ?>
            <option value="<?php echo $sede['id']; ?>"><?php echo htmlspecialchars($sede['nombre']); ?></option>
        <?php endforeach; ?>
    `;
    
    // Restablecer valores por defecto
    document.getElementById('prioridad_incidencia').value = 'media';
    document.getElementById('estado_incidencia').value = 'reportado';
}
    
    // Mostrar modal para nueva incidencia
    btnReportarIncidencia.addEventListener('click', () => {
        resetearModalIncidencia();
        modalIncidencia.classList.add('show');
        document.body.style.overflow = 'hidden';
    });
    
    // Cerrar modal
    closeModalIncidencia.addEventListener('click', () => {
        modalIncidencia.classList.remove('show');
        document.body.style.overflow = 'auto';
    });
    
    cancelarFormIncidencia.addEventListener('click', () => {
        modalIncidencia.classList.remove('show');
        document.body.style.overflow = 'auto';
    });
    
    modalIncidencia.addEventListener('click', (e) => {
        if (e.target === modalIncidencia) {
            modalIncidencia.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    });
    
    // Cargar salones cuando se selecciona una sede
    document.getElementById('sede_incidencia').addEventListener('change', function() {
        const sedeId = this.value;
        const salonSelect = document.getElementById('salon_incidencia');
        
        if (sedeId) {
            fetch(`obtener_salones.php?sede_id=${sedeId}`)
                .then(response => response.json())
                .then(data => {
                    salonSelect.innerHTML = '<option value="">Seleccione un salón</option>';
                    salonSelect.disabled = false;
                    
                    data.forEach(salon => {
                        const option = document.createElement('option');
                        option.value = salon.id;
                        option.textContent = salon.codigo_salon;
                        salonSelect.appendChild(option);
                    });
                });
        } else {
            salonSelect.innerHTML = '<option value="">Seleccione un salón</option>';
            salonSelect.disabled = true;
            document.getElementById('computadora_incidencia').innerHTML = '<option value="">Seleccione una computadora</option>';
            document.getElementById('computadora_incidencia').disabled = true;
        }
    });
    
    // Cargar computadoras cuando se selecciona un salón
    document.getElementById('salon_incidencia').addEventListener('change', function() {
        const salonId = this.value;
        const pcSelect = document.getElementById('computadora_incidencia');
        
        if (salonId) {
            fetch(`obtener_computadoras.php?salon_id=${salonId}`)
                .then(response => response.json())
                .then(data => {
                    pcSelect.innerHTML = '<option value="">Seleccione una computadora</option>';
                    pcSelect.disabled = false;
                    
                    data.forEach(pc => {
                        const option = document.createElement('option');
                        option.value = pc.id;
                        option.textContent = `${pc.codigo_patrimonio} - ${pc.marca} ${pc.modelo}`;
                        pcSelect.appendChild(option);
                    });
                });
        } else {
            pcSelect.innerHTML = '<option value="">Seleccione una computadora</option>';
            pcSelect.disabled = true;
        }
    });
}
 function setupReparacionesFunctions() {
       // Mostrar modal para nueva reparación
    document.getElementById('btnAgregarReparacion').addEventListener('click', function() {
        resetearModalReparacion();
        document.getElementById('modalReparacion').classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Establecer fecha actual en el campo de fecha de reporte
        const today = new Date();
        const formattedDate = formatDateForInput(today);
        document.getElementById('fecha_reparacion').value = formattedDate;
    });

    // Cerrar modal
    document.getElementById('closeModalReparacion').addEventListener('click', function() {
        document.getElementById('modalReparacion').classList.remove('show');
        document.body.style.overflow = 'auto';
    });

    document.getElementById('cancelarFormReparacion').addEventListener('click', function() {
        document.getElementById('modalReparacion').classList.remove('show');
        document.body.style.overflow = 'auto';
    });

    document.getElementById('modalReparacion').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    });

    // Función para formatear la fecha como YYYY-MM-DD (formato input date)
    function formatDateForInput(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    // Resetear modal
    function resetearModalReparacion() {
        document.getElementById('modalReparacionTitulo').textContent = 'Agregar Reparación';
        document.getElementById('btnSubmitReparacion').textContent = 'Guardar Reparación';
        document.getElementById('formReparacion').action = 'guardar_reparacion.php';
        document.getElementById('formReparacion').reset();
        document.getElementById('reparacion_id').value = '';
        document.getElementById('formReparacion').classList.remove('was-validated');
    }

        // Ver detalles de reparación
      window.verReparacion = function(id) {
    fetch(`obtener_reparacion.php?id=${id}`)
        .then(response => response.json())
        .then(reparacion => {
            // Función corregida para formatear fechas
            const formatDate = (dateStr) => {
                if (!dateStr) return 'N/A';
                
                const date = new Date(dateStr);
                const timezoneOffset = date.getTimezoneOffset() * 60000;
                const adjustedDate = new Date(date.getTime() + timezoneOffset);
                
                return adjustedDate.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            };

            let htmlContent = `
                <div class="pc-detail-container">
                    <div class="pc-header">
                        <i class="fas fa-tools pc-icon" style="color: #6c5ce7;"></i>
                        <div class="pc-title">
                            <h3>Reparación #${reparacion.id}</h3>
                            <p class="pc-subtitle">${formatDate(reparacion.fecha_reparacion)} - ${formatDate(reparacion.fecha_completada)}</p>
                        </div>
                    </div>
                    
                    <div class="pc-detail-grid">
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-laptop"></i> Computadora</h4>
                            <div class="detail-item">
                                <span class="detail-label">Código:</span>
                                <span class="detail-value">${reparacion.codigo_patrimonio}</span>
                            </div>
                        </div>
                        
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-users"></i> Personas</h4>
                            <div class="detail-item">
                                <span class="detail-label">Reportó:</span>
                                <span class="detail-value">${reparacion.persona_reporto}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Reparó:</span>
                                <span class="detail-value">${reparacion.persona_realizo}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pc-observations">
                        <h4 class="section-title"><i class="fas fa-exclamation-circle"></i> Problema Reportado</h4>
                        <div class="observations-content">${reparacion.descripcion}</div>
                    </div>
                    
                    <div class="pc-observations">
                        <h4 class="section-title"><i class="fas fa-check-circle"></i> Solución Aplicada</h4>
                        <div class="observations-content">${reparacion.solucion || 'No se registró solución'}</div>
                    </div>
                    
                    <div class="pc-detail-grid">
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-calendar-alt"></i> Fechas</h4>
                            <div class="detail-item">
                                <span class="detail-label">Reporte:</span>
                                <span class="detail-value">${formatDate(reparacion.fecha_reparacion)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Completada:</span>
                                <span class="detail-value">${formatDate(reparacion.fecha_completada)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'Detalles de la Reparación',
                html: htmlContent,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                width: '800px',
                customClass: {
                    popup: 'pc-detail-popup',
                    container: 'pc-detail-container'
                }
            });
        })
        .catch(error => {
            Swal.fire('Error', error.message, 'error');
        });
};

        // Editar reparación
      window.editarReparacion = function(id) {
    fetch(`obtener_reparacion.php?id=${id}`)
        .then(response => response.json())
        .then(reparacion => {
            document.getElementById('modalReparacionTitulo').textContent = 'Editar Reparación';
            document.getElementById('btnSubmitReparacion').textContent = 'Actualizar Reparación';
            document.getElementById('formReparacion').action = 'actualizar_reparacion.php';
            document.getElementById('reparacion_id').value = reparacion.id;
            
            // Llenar formulario con los nuevos campos
            document.getElementById('computadora_reparacion').value = reparacion.computadora_id;
            document.getElementById('fecha_reparacion').value = reparacion.fecha_reparacion;
            document.getElementById('fecha_completada').value = reparacion.fecha_completada || '';
            document.getElementById('persona_reporto').value = reparacion.persona_reporto || '';
            document.getElementById('persona_realizo').value = reparacion.persona_realizo || '';
            document.getElementById('estado_reparacion').value = reparacion.estado_reparacion || 'pendiente'; // Nuevo campo
            document.getElementById('descripcion_reparacion').value = reparacion.descripcion || '';
            document.getElementById('solucion_reparacion').value = reparacion.solucion || '';
            
            // Mostrar modal
            document.getElementById('modalReparacion').classList.add('show');
            document.body.style.overflow = 'hidden';
        })
        .catch(error => {
            Swal.fire('Error', 'No se pudo cargar la reparación: ' + error.message, 'error');
        });
};

        // Eliminar reparación
        window.eliminarReparacion = function(id, event) {
            event.stopPropagation();
            
            Swal.fire({
                title: '¿Eliminar Reparación?',
                text: '¿Estás seguro de que deseas eliminar este registro de reparación?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d63031'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Eliminando...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            
                            fetch('eliminar_reparacion.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Error en la respuesta del servidor');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Eliminado!',
                                        text: data.message,
                                        icon: 'success'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    throw new Error(data.message || 'Error al eliminar la reparación');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error', error.message, 'error');
                            });
                        }
                    });
                }
            });
        };
    }

    // ... (código existente del DOMContentLoaded) ...
    document.addEventListener('DOMContentLoaded', function() {
        // ... (otras inicializaciones existentes) ...
        setupReparacionesFunctions();
        
        // Manejar clic en menú de reparaciones
        document.querySelector('.menu-item[data-section="reparaciones"]').addEventListener('click', function() {
            // Ocultar todas las secciones
            document.querySelectorAll('#inicio-section, #incidencias-section, #reparaciones-section').forEach(sec => {
                sec.style.display = 'none';
            });
            
            // Mostrar sección de reparaciones
            document.getElementById('reparaciones-section').style.display = 'block';
            
            // Actualizar menú activo
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
    

    // Inicialización cuando el DOM está listo
    document.addEventListener('DOMContentLoaded', function() {
        generarAvatares();
        setupRippleEffects();
        setupAnimations();
        setupFormValidation();
        setupMobileMenu();
        setupSearch();
        setupMenuNavigation();
        setupLogout();
        setupModals();
        setupSalonesFunctions();
        setupSedesFunctions();
        setupComputadorasFunctions();
        setupIncidenciasFunctions();
        
        // Mostrar sección de inicio por defecto
        document.getElementById('inicio-section').style.display = 'block';
    });
    
</script>
<script>
function cargarEstadisticasIncidencias() {
    fetch('obtener_estadisticas_incidencias.php')
        .then(response => response.json())
        .then(data => {
            // Actualizar contadores
            document.getElementById('resueltas-count').textContent = data.resueltas || 0;
            document.getElementById('pendientes-count').textContent = data.pendientes || 0;
            document.getElementById('proceso-count').textContent = data.en_proceso || 0;
            document.getElementById('asignadas-count').textContent = data.asignadas || 0;
            
            // Generar gráfica de tendencia
            actualizarGraficaTendencia(data.tendencia);
        })
        .catch(error => {
            console.error('Error al cargar estadísticas:', error);
        });
}

// Función para generar la gráfica de tendencia
function generarGraficaTendencia(datosTendencia) {
    const ctx = document.getElementById('incidentTrendChart').getContext('2d');
    
    // Si no hay datos, mostrar mensaje
    if (!datosTendencia || datosTendencia.length === 0) {
        ctx.font = "16px Arial";
        ctx.fillStyle = "#999";
        ctx.textAlign = "center";
        ctx.fillText("No hay datos disponibles", ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }
    
    // Configurar datos para la gráfica
    const ultimos7Dias = obtenerUltimos7Dias();
    const datos = ultimos7Dias.map(dia => {
        const encontrado = datosTendencia.find(d => d.fecha === dia);
        return encontrado ? encontrado.cantidad : 0;
    });
    
    // Crear la gráfica
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ultimos7Dias.map(dia => formatearFechaCorta(dia)),
            datasets: [{
                label: 'Incidencias reportadas',
                data: datos,
                borderColor: 'var(--color-primary)',
                backgroundColor: 'rgba(108, 92, 231, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

let incidentTrendChartInstance = null;

// Función para actualizar estadísticas y gráfica
function actualizarEstadisticasYGrafica() {
    fetch('obtener_estadisticas_incidencias.php')
        .then(response => response.json())
        .then(data => {
            // Actualizar contadores
            document.getElementById('resueltas-count').textContent = data.resueltas || 0;
            document.getElementById('pendientes-count').textContent = data.pendientes || 0;
            document.getElementById('proceso-count').textContent = data.en_proceso || 0;
            document.getElementById('asignadas-count').textContent = data.asignadas || 0;
            
            // Actualizar gráfica de tendencia
            actualizarGraficaTendencia(data.tendencia);
        })
        .catch(error => {
            console.error('Error al actualizar estadísticas:', error);
        });
}
function cargarEstadisticasReparaciones() {
    fetch('obtener_estadisticas_reparaciones.php')
        .then(response => response.json())
        .then(data => {
            // Actualizar contadores
            document.getElementById('reparaciones-pendientes-count').textContent = data.pendientes || 0;
            document.getElementById('reparaciones-proceso-count').textContent = data.en_proceso || 0;
            document.getElementById('reparaciones-completadas-count').textContent = data.completadas || 0;
            
            // Generar gráfica circular
            generarGraficaCircularReparaciones(data);
        })
        .catch(error => {
            console.error('Error al cargar estadísticas de reparaciones:', error);
        });
}

function cargarEstadisticasReparaciones() {
    fetch('obtener_estadisticas_reparaciones.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al cargar estadísticas');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error('Error del servidor:', data.error);
                // Mostrar valores por defecto
                document.getElementById('reparaciones-pendientes-count').textContent = '0';
                document.getElementById('reparaciones-proceso-count').textContent = '0';
                document.getElementById('reparaciones-completadas-count').textContent = '0';
                return;
            }
            
            // Actualizar contadores
            document.getElementById('reparaciones-pendientes-count').textContent = data.pendientes || 0;
            document.getElementById('reparaciones-proceso-count').textContent = data.en_proceso || 0;
            document.getElementById('reparaciones-completadas-count').textContent = data.completadas || 0;
            
            // Generar gráfica circular
            generarGraficaCircularReparaciones(data);
        })
        .catch(error => {
            console.error('Error al cargar estadísticas de reparaciones:', error);
            // Mostrar valores por defecto en caso de error
            document.getElementById('reparaciones-pendientes-count').textContent = '0';
            document.getElementById('reparaciones-proceso-count').textContent = '0';
            document.getElementById('reparaciones-completadas-count').textContent = '0';
        });
}

function generarGraficaCircularReparaciones(datos) {
    const ctx = document.getElementById('reparacionesPieChart').getContext('2d');
    
    // Destruir la instancia anterior si existe
    if (window.reparacionesPieChartInstance) {
        window.reparacionesPieChartInstance.destroy();
    }
    
    // Si no hay datos, mostrar mensaje
    if (!datos || (datos.pendientes === 0 && datos.en_proceso === 0 && datos.completadas === 0)) {
        ctx.font = "16px Arial";
        ctx.fillStyle = "#999";
        ctx.textAlign = "center";
        ctx.fillText("No hay datos disponibles", ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }
    
    // Paleta de colores mejorada
    const colores = [
        '#FF6384', // Rojo rosado para pendientes
        '#36A2EB', // Azul para en proceso
        '#4BC0C0', // Verde azulado para completadas
        '#FFCD56', // Amarillo para otros estados (si los hubiera)
        '#9966FF'  // Púrpura para otros estados (si los hubiera)
    ];
    
    // Configurar datos para la gráfica
    const chartData = {
        labels: ['Pendientes', 'En Proceso', 'Completadas'],
        datasets: [{
            data: [datos.pendientes, datos.en_proceso, datos.completadas],
            backgroundColor: colores,
            borderColor: '#fff',
            borderWidth: 2,
            hoverOffset: 15
        }]
    };
    
    // Crear la gráfica con opciones mejoradas
    window.reparacionesPieChartInstance = new Chart(ctx, {
        type: 'pie',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#2d3436',
                        font: {
                            size: 12,
                            family: 'Poppins'
                        },
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(45, 52, 54, 0.9)',
                    titleFont: {
                        family: 'Poppins',
                        size: 13
                    },
                    bodyFont: {
                        family: 'Poppins',
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });
}

// Cargar estadísticas de reparaciones al mostrar la sección
document.querySelector('.menu-item[data-section="reparaciones"]').addEventListener('click', function() {
    setTimeout(function() {
        fetch('obtener_estadisticas_reparaciones.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('reparaciones-pendientes-count').textContent = data.pendientes || 0;
                document.getElementById('reparaciones-proceso-count').textContent = data.en_proceso || 0;
                document.getElementById('reparaciones-completadas-count').textContent = data.completadas || 0;
                generarGraficaCircularReparaciones(data);
            })
            .catch(error => {
                console.error('Error al cargar estadísticas de reparaciones:', error);
            });
    }, 100);
});

// Función para actualizar la gráfica de tendencia
function actualizarGraficaTendencia(datosTendencia) {
    const ctx = document.getElementById('incidentTrendChart').getContext('2d');
    
    // Destruir la instancia anterior del gráfico si existe
    if (incidentTrendChartInstance) {
        incidentTrendChartInstance.destroy();
    }
    
    // Si no hay datos, mostrar mensaje
    if (!datosTendencia || datosTendencia.length === 0) {
        ctx.font = "16px Arial";
        ctx.fillStyle = "#999";
        ctx.textAlign = "center";
        ctx.fillText("No hay datos disponibles", ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }
    
    // Configurar datos para la gráfica
    const ultimos7Dias = obtenerUltimos7Dias();
    const datos = ultimos7Dias.map(dia => {
        const encontrado = datosTendencia.find(d => d.fecha === dia);
        return encontrado ? encontrado.cantidad : 0;
    });
    
    // Crear la nueva gráfica
    incidentTrendChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ultimos7Dias.map(dia => formatearFechaCorta(dia)),
            datasets: [{
                label: 'Incidencias reportadas',
                data: datos,
                borderColor: 'var(--color-primary)',
                backgroundColor: 'rgba(108, 92, 231, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Función para obtener los últimos 7 días (ya existente)
function obtenerUltimos7Dias() {
    const dias = [];
    for (let i = 6; i >= 0; i--) {
        const fecha = new Date();
        fecha.setDate(fecha.getDate() - i);
        
        // Ajustar a la fecha local sin la componente de tiempo
        const año = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        
        dias.push(`${año}-${mes}-${dia}`);
    }
    return dias;
}

// Función para formatear fechas (ya existente)
function formatearFechaCorta(fechaStr) {
    // Parsear la fecha en formato YYYY-MM-DD
    const [año, mes, dia] = fechaStr.split('-').map(Number);
    const fecha = new Date(año, mes - 1, dia); // Los meses en JavaScript van de 0 a 11
    
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return `${fecha.getDate()} ${meses[fecha.getMonth()]}`;
}

// Cargar incidencias
window.cargarIncidencias = function() {
    cargarEstadisticasIncidencias();
};

// Event listener para la sección de incidencias
document.querySelector('.menu-item[data-section="incidencias"]').addEventListener('click', function() {
    setTimeout(cargarEstadisticasIncidencias, 100);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Función para verificar y generar notificaciones de mantenimiento
function checkMaintenanceNotifications() {
    fetch('check_maintenance_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                // Mostrar badge con el número de notificaciones
                const badge = document.getElementById('notificationBadge');
                badge.textContent = data.count;
                badge.style.display = 'flex';
                
                // Guardar notificaciones para mostrarlas en el popup
                window.maintenanceNotifications = data.notifications;
            }
        })
        .catch(error => {
            console.error('Error al verificar notificaciones:', error);
        });
}

// Función para alternar la visibilidad del popup de notificaciones
function toggleNotifications() {
    const popup = document.getElementById('notificationsPopup');
    const isVisible = popup.classList.contains('show');
    
    if (isVisible) {
        popup.classList.remove('show');
    } else {
        popup.classList.add('show');
        loadNotifications();
    }
}

// Función para cargar las notificaciones en el popup
function loadNotifications() {
    const list = document.getElementById('notificationsList');
    
    if (!window.maintenanceNotifications || window.maintenanceNotifications.length === 0) {
        list.innerHTML = '<div class="no-notifications">No hay notificaciones</div>';
        return;
    }
    
    let html = '';
    window.maintenanceNotifications.forEach(notification => {
        html += `
            <div class="notification-item unread">
                <div class="notification-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">Mantenimiento Requerido</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">Hace ${notification.time_ago}</div>
                </div>
            </div>
        `;
    });
    
    list.innerHTML = html;
}

// Función para marcar todas las notificaciones como leídas
function markAllAsRead() {
    fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ocultar el badge
            document.getElementById('notificationBadge').style.display = 'none';
            
            // Recargar las notificaciones
            checkMaintenanceNotifications();
            
            // Cerrar el popup
            document.getElementById('notificationsPopup').classList.remove('show');
            
            Swal.fire({
                title: 'Notificaciones marcadas como leídas',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }
    })
    .catch(error => {
        console.error('Error al marcar notificaciones como leídas:', error);
    });
}

// Verificar notificaciones al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Verificar notificaciones cada 5 minutos
    checkMaintenanceNotifications();
    setInterval(checkMaintenanceNotifications, 300000);
    
    // Cerrar el popup al hacer clic fuera de él
    document.addEventListener('click', function(e) {
        const popup = document.getElementById('notificationsPopup');
        const bell = document.querySelector('.notification-bell');
        
        if (popup.classList.contains('show') && 
            !popup.contains(e.target) && 
            !bell.contains(e.target)) {
            popup.classList.remove('show');
        }
    });
});
document.querySelector('.menu-item[data-section="reparaciones"]').addEventListener('click', function() {
    // Esperar un poco para que la sección se muestre completamente
    setTimeout(function() {
        cargarEstadisticasReparaciones();
    }, 100);
});

// También ejecutar al cargar la página si ya estamos en la sección de reparaciones
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en la sección de reparaciones
    if (window.location.search.includes('section=reparaciones') || 
        document.getElementById('reparaciones-section').style.display === 'block') {
        setTimeout(function() {
            cargarEstadisticasReparaciones();
        }, 300);
    }
});

</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Variables para controlar la paginación
const paginationState = {
    salones: { currentPage: 1, rowsPerPage: 10 },
    computadoras: { currentPage: 1, rowsPerPage: 10 },
    incidencias: { currentPage: 1, rowsPerPage: 10 },
    reparaciones: { currentPage: 1, rowsPerPage: 10 }
};

// Función para cambiar de página
function changePage(tableType, direction) {
    const state = paginationState[tableType];
    
    if (direction === 'next') {
        state.currentPage++;
    } else if (direction === 'prev') {
        state.currentPage--;
    }
    
    updatePagination(tableType);
    showTablePage(tableType);
}

// Función para actualizar los controles de paginación
function updatePagination(tableType) {
    const state = paginationState[tableType];
    const table = document.getElementById(`tabla${tableType.charAt(0).toUpperCase() + tableType.slice(1)}`);
    
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const totalPages = Math.ceil(rows.length / state.rowsPerPage);
    
    // Actualizar número de página
    const pageElement = document.getElementById(`${tableType}-current-page`);
    if (pageElement) {
        pageElement.textContent = state.currentPage;
    }
    
    // Habilitar/deshabilitar botones
    const prevBtn = document.querySelector(`.pagination-btn[onclick="changePage('${tableType}', 'prev')"]`);
    const nextBtn = document.querySelector(`.pagination-btn[onclick="changePage('${tableType}', 'next')"]`);
    
    if (prevBtn) prevBtn.disabled = state.currentPage === 1;
    if (nextBtn) nextBtn.disabled = state.currentPage === totalPages || totalPages === 0;
}

// Función para mostrar la página actual de la tabla
function showTablePage(tableType) {
    const state = paginationState[tableType];
    const table = document.getElementById(`tabla${tableType.charAt(0).toUpperCase() + tableType.slice(1)}`);
    
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const startIndex = (state.currentPage - 1) * state.rowsPerPage;
    const endIndex = startIndex + state.rowsPerPage;
    
    rows.forEach((row, index) => {
        if (index >= startIndex && index < endIndex) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Inicializar paginación para todas las tablas
function initPagination() {
    // Inicializar paginación para cada tipo de tabla
    ['salones', 'computadoras', 'incidencias', 'reparaciones'].forEach(tableType => {
        updatePagination(tableType);
        showTablePage(tableType);
    });
}

// Llamar a initPagination cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Esperar un breve momento para que las tablas se carguen completamente
    setTimeout(initPagination, 100);
});

// También inicializar cuando se cambie de sección
const menuItems = document.querySelectorAll('.menu-item');
menuItems.forEach(item => {
    item.addEventListener('click', function() {
        setTimeout(initPagination, 300);
    });
});
</script>
</body>
</html>