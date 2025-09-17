<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit;
}

$salon_id = isset($_GET['salon_id']) ? intval($_GET['salon_id']) : 0;

require 'conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computadoras - Gestión de Salones</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(to bottom, #4361ee, #3f37c9);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .pc-card {
            border-left: 5px solid;
        }
        .pc-operativo {
            border-left-color: #4cc9f0;
        }
        .pc-mantenimiento {
            border-left-color: #f8961e;
        }
        .pc-dañado {
            border-left-color: #f72585;
        }
        .pc-retirado {
            border-left-color: #6c757d;
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
        }
        .badge-estado {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (igual que en dashboard.php) -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <!-- ... mismo código del sidebar ... -->
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto main-content">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="salones.php?sede_id=<?php 
                            try {
                                $stmt = $conn->prepare("SELECT sede_id FROM salones WHERE id = :salon_id");
                                $stmt->bindParam(':salon_id', $salon_id);
                                $stmt->execute();
                                if ($stmt->rowCount() > 0) {
                                    $sede_id = $stmt->fetch(PDO::FETCH_ASSOC)['sede_id'];
                                    echo $sede_id;
                                }
                            } catch(PDOException $e) {
                                echo '0';
                            }
                        ?>">Salones</a></li>
                        <li class="breadcrumb-item active">Computadoras</li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Computadoras</h1>
                    <?php if ($_SESSION['rol'] == 'admin' || $_SESSION['rol'] == 'tecnico'): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaPcModal">
                        <i class="fas fa-plus me-1"></i> Nueva Computadora
                    </button>
                    <?php endif; ?>
                </div>

                <?php
                try {
                    // Obtener información del salón
                    $stmt = $conn->prepare("SELECT s.*, se.nombre as sede_nombre FROM salones s JOIN sedes se ON s.sede_id = se.id WHERE s.id = :salon_id");
                    $stmt->bindParam(':salon_id', $salon_id);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $salon = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo "<h4 class='mb-4'>Salón: " . htmlspecialchars($salon['codigo_salon']) . " - Sede: " . htmlspecialchars($salon['sede_nombre']) . "</h4>";
                        
                        // Obtener computadoras de este salón
                        $stmt = $conn->prepare("SELECT * FROM computadores WHERE salon_id = :salon_id ORDER BY codigo_patrimonio");
                        $stmt->bindParam(':salon_id', $salon_id);
                        $stmt->execute();
                        
                        if ($stmt->rowCount() > 0) {
                            echo '<div class="row">';
                            while ($pc = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                // Determinar clase CSS según el estado
                                $estado_class = '';
                                $estado_badge = '';
                                switch ($pc['estado']) {
                                    case 'operativo':
                                        $estado_class = 'pc-operativo';
                                        $estado_badge = 'bg-success';
                                        break;
                                    case 'mantenimiento':
                                        $estado_class = 'pc-mantenimiento';
                                        $estado_badge = 'bg-warning text-dark';
                                        break;
                                    case 'dañado':
                                        $estado_class = 'pc-dañado';
                                        $estado_badge = 'bg-danger';
                                        break;
                                    case 'retirado':
                                        $estado_class = 'pc-retirado';
                                        $estado_badge = 'bg-secondary';
                                        break;
                                }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card pc-card <?php echo $estado_class; ?>" onclick="window.location.href='detalle_pc.php?pc_id=<?php echo $pc['id']; ?>'">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($pc['codigo_patrimonio']); ?></h5>
                                <span class="badge <?php echo $estado_badge; ?> badge-estado"><?php echo ucfirst($pc['estado']); ?></span>
                            </div>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($pc['marca']) . ' ' . htmlspecialchars($pc['modelo']); ?></h6>
                            <p class="card-text small">
                                <strong>SO:</strong> <?php echo htmlspecialchars($pc['sistema_operativo'] . ' ' . $pc['version_so']); ?><br>
                                <strong>Procesador:</strong> <?php echo htmlspecialchars($pc['procesador']); ?><br>
                                <strong>RAM:</strong> <?php echo $pc['ram_gb']; ?> GB<br>
                                <strong>Almacenamiento:</strong> <?php echo $pc['almacenamiento_gb']; ?> GB <?php echo htmlspecialchars($pc['tipo_almacenamiento']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Últ. mantenimiento: <?php 
                                    echo $pc['ultimo_mantenimiento'] ? date('d/m/Y', strtotime($pc['ultimo_mantenimiento'])) : 'Nunca'; 
                                ?></small>
                                <?php if ($_SESSION['rol'] == 'admin' || $_SESSION['rol'] == 'tecnico'): ?>
                                <a href="reportar_incidencia.php?pc_id=<?php echo $pc['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation();">
                                    <i class="fas fa-exclamation-circle"></i> Reportar
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-info">No hay computadoras registradas en este salón.</div>';
                        }
                    } else {
                        echo '<div class="alert alert-danger">Salón no encontrado.</div>';
                    }
                } catch(PDOException $e) {
                    echo "<div class='alert alert-danger'>Error al cargar las computadoras: " . $e->getMessage() . "</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal para nueva computadora (solo visible para admin/tecnico) -->
    <div class="modal fade" id="nuevaPcModal" tabindex="-1" aria-labelledby="nuevaPcModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nuevaPcModalLabel">Agregar Nueva Computadora</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevaPc" action="guardar_pc.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="salon_id" value="<?php echo $salon_id; ?>">
                        <div class="mb-3">
                            <label for="codigo_patrimonio" class="form-label">Código Patrimonial</label>
                            <input type="text" class="form-control" id="codigo_patrimonio" name="codigo_patrimonio" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="marca" class="form-label">Marca</label>
                                <input type="text" class="form-control" id="marca" name="marca" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modelo" class="form-label">Modelo</label>
                                <input type="text" class="form-control" id="modelo" name="modelo" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sistema_operativo" class="form-label">Sistema Operativo</label>
                                <select class="form-select" id="sistema_operativo" name="sistema_operativo" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Windows">Windows</option>
                                    <option value="macOS">macOS</option>
                                    <option value="Linux">Linux</option>
                                    <option value="Chrome OS">Chrome OS</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="version_so" class="form-label">Versión</label>
                                <input type="text" class="form-control" id="version_so" name="version_so">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="procesador" class="form-label">Procesador</label>
                            <input type="text" class="form-control" id="procesador" name="procesador">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ram_gb" class="form-label">RAM (GB)</label>
                                <input type="number" class="form-control" id="ram_gb" name="ram_gb" min="1" step="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="almacenamiento_gb" class="form-label">Almacenamiento (GB)</label>
                                <input type="number" class="form-control" id="almacenamiento_gb" name="almacenamiento_gb" min="1" step="1">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="tipo_almacenamiento" class="form-label">Tipo de Almacenamiento</label>
                            <select class="form-select" id="tipo_almacenamiento" name="tipo_almacenamiento">
                                <option value="HDD">HDD</option>
                                <option value="SSD">SSD</option>
                                <option value="NVMe">NVMe</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="direccion_ip" class="form-label">Dirección IP</label>
                                <input type="text" class="form-control" id="direccion_ip" name="direccion_ip" placeholder="192.168.1.1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="direccion_mac" class="form-label">Dirección MAC</label>
                                <input type="text" class="form-control" id="direccion_mac" name="direccion_mac" placeholder="00:1A:2B:3C:4D:5E">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_instalacion" class="form-label">Fecha de Instalación</label>
                            <input type="date" class="form-control" id="fecha_instalacion" name="fecha_instalacion">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>