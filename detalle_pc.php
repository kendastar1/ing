<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit;
}

$pc_id = isset($_GET['pc_id']) ? intval($_GET['pc_id']) : 0;

require 'conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Computadora - Gestión de Salones</title>
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
        .pc-detail-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .pc-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .pc-title {
            color: #4361ee;
        }
        .specs-list dt {
            font-weight: 500;
            color: #6c757d;
        }
        .specs-list dd {
            margin-bottom: 15px;
        }
        .badge-estado {
            font-size: 1rem;
            padding: 0.35em 0.65em;
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
        }
        .tab-content {
            padding: 20px 0;
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
                                $stmt = $conn->prepare("SELECT s.sede_id FROM computadores c JOIN salones s ON c.salon_id = s.id WHERE c.id = :pc_id");
                                $stmt->bindParam(':pc_id', $pc_id);
                                $stmt->execute();
                                if ($stmt->rowCount() > 0) {
                                    $sede_id = $stmt->fetch(PDO::FETCH_ASSOC)['sede_id'];
                                    echo $sede_id;
                                }
                            } catch(PDOException $e) {
                                echo '0';
                            }
                        ?>">Salones</a></li>
                        <li class="breadcrumb-item"><a href="computadoras.php?salon_id=<?php 
                            try {
                                $stmt = $conn->prepare("SELECT salon_id FROM computadores WHERE id = :pc_id");
                                $stmt->bindParam(':pc_id', $pc_id);
                                $stmt->execute();
                                if ($stmt->rowCount() > 0) {
                                    $salon_id = $stmt->fetch(PDO::FETCH_ASSOC)['salon_id'];
                                    echo $salon_id;
                                }
                            } catch(PDOException $e) {
                                echo '0';
                            }
                        ?>">Computadoras</a></li>
                        <li class="breadcrumb-item active">Detalle</li>
                    </ol>
                </nav>

                <?php
                try {
                    // Obtener información detallada de la computadora
                    $stmt = $conn->prepare("SELECT c.*, s.codigo_salon, se.nombre as sede_nombre 
                                          FROM computadores c 
                                          JOIN salones s ON c.salon_id = s.id 
                                          JOIN sedes se ON s.sede_id = se.id 
                                          WHERE c.id = :pc_id");
                    $stmt->bindParam(':pc_id', $pc_id);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $pc = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Determinar clase CSS según el estado
                        $estado_badge = '';
                        switch ($pc['estado']) {
                            case 'operativo':
                                $estado_badge = 'bg-success';
                                break;
                            case 'mantenimiento':
                                $estado_badge = 'bg-warning text-dark';
                                break;
                            case 'dañado':
                                $estado_badge = 'bg-danger';
                                break;
                            case 'retirado':
                                $estado_badge = 'bg-secondary';
                                break;
                        }
                ?>
                <div class="card pc-detail-card">
                    <div class="card-body">
                        <div class="pc-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h2 class="pc-title"><?php echo htmlspecialchars($pc['codigo_patrimonio']); ?></h2>
                                    <h5 class="text-muted"><?php echo htmlspecialchars($pc['marca']) . ' ' . htmlspecialchars($pc['modelo']); ?></h5>
                                </div>
                                <span class="badge <?php echo $estado_badge; ?> badge-estado"><?php echo ucfirst($pc['estado']); ?></span>
                            </div>
                            <p class="mb-0"><i class="fas fa-laptop-house me-1"></i> <?php echo htmlspecialchars($pc['sede_nombre']) . ' - ' . htmlspecialchars($pc['codigo_salon']); ?></p>
                        </div>

                        <ul class="nav nav-tabs" id="pcTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="especificaciones-tab" data-bs-toggle="tab" data-bs-target="#especificaciones" type="button" role="tab">Especificaciones</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="mantenimientos-tab" data-bs-toggle="tab" data-bs-target="#mantenimientos" type="button" role="tab">Mantenimientos</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="incidencias-tab" data-bs-toggle="tab" data-bs-target="#incidencias" type="button" role="tab">Incidencias</button>
                            </li>
                            <?php if ($_SESSION['rol'] == 'admin' || $_SESSION['rol'] == 'tecnico'): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="editar-tab" data-bs-toggle="tab" data-bs-target="#editar" type="button" role="tab">Editar</button>
                            </li>
                            <?php endif; ?>
                        </ul>

                        <div class="tab-content" id="pcTabsContent">
                            <!-- Pestaña de Especificaciones -->
                            <div class="tab-pane fade show active" id="especificaciones" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <dl class="specs-list">
                                            <dt>Sistema Operativo</dt>
                                            <dd><?php echo htmlspecialchars($pc['sistema_operativo'] . ' ' . $pc['version_so']); ?></dd>
                                            
                                            <dt>Arquitectura</dt>
                                            <dd><?php echo htmlspecialchars($pc['arquitectura'] ?: 'No especificado'); ?></dd>
                                            
                                            <dt>Procesador</dt>
                                            <dd><?php echo htmlspecialchars($pc['procesador'] ?: 'No especificado'); ?></dd>
                                            
                                            <dt>Memoria RAM</dt>
                                            <dd><?php echo $pc['ram_gb'] ? $pc['ram_gb'] . ' GB' : 'No especificado'; ?></dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                        <dl class="specs-list">
                                            <dt>Almacenamiento</dt>
                                            <dd><?php 
                                                echo $pc['almacenamiento_gb'] ? $pc['almacenamiento_gb'] . ' GB ' . htmlspecialchars($pc['tipo_almacenamiento']) : 'No especificado';
                                            ?></dd>
                                            
                                            <dt>Dirección IP</dt>
                                            <dd><?php echo htmlspecialchars($pc['direccion_ip'] ?: 'No asignada'); ?></dd>
                                            
                                            <dt>Dirección MAC</dt>
                                            <dd><?php echo htmlspecialchars($pc['direccion_mac'] ?: 'No asignada'); ?></dd>
                                            
                                            <dt>Fecha de Instalación</dt>
                                            <dd><?php echo $pc['fecha_instalacion'] ? date('d/m/Y', strtotime($pc['fecha_instalacion'])) : 'No especificada'; ?></dd>
                                        </dl>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <h5>Último Mantenimiento</h5>
                                        <p><?php 
                                            echo $pc['ultimo_mantenimiento'] ? 
                                                date('d/m/Y', strtotime($pc['ultimo_mantenimiento'])) : 
                                                'No se ha realizado mantenimiento';
                                        ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Próximo Mantenimiento</h5>
                                        <p><?php 
                                            echo $pc['proximo_mantenimiento'] ? 
                                                date('d/m/Y', strtotime($pc['proximo_mantenimiento'])) : 
                                                'No programado';
                                        ?></p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($pc['observaciones'])): ?>
                                <div class="mt-4">
                                    <h5>Observaciones</h5>
                                    <p><?php echo nl2br(htmlspecialchars($pc['observaciones'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Pestaña de Mantenimientos -->
                            <div class="tab-pane fade" id="mantenimientos" role="tabpanel">
                                <?php
                                try {
                                    $stmt = $conn->prepare("SELECT m.*, u.nombre as tecnico 
                                                           FROM mantenimientos m 
                                                           JOIN usuarios u ON m.usuario_id = u.id 
                                                           WHERE m.computador_id = :pc_id 
                                                           ORDER BY m.fecha DESC");
                                    $stmt->bindParam(':pc_id', $pc_id);
                                    $stmt->execute();
                                    
                                    if ($stmt->rowCount() > 0) {
                                ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Tipo</th>
                                                <th>Técnico</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($mantenimiento['fecha'])); ?></td>
                                                <td><?php echo ucfirst($mantenimiento['tipo_mantenimiento']); ?></td>
                                                <td><?php echo htmlspecialchars($mantenimiento['tecnico']); ?></td>
                                                <td><?php echo htmlspecialchars($mantenimiento['descripcion']); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php
                                    } else {
                                        echo '<div class="alert alert-info">No hay registros de mantenimiento para esta computadora.</div>';
                                    }
                                } catch(PDOException $e) {
                                    echo '<div class="alert alert-danger">Error al cargar los mantenimientos: ' . $e->getMessage() . '</div>';
                                }
                                ?>
                                
                                <?php if ($_SESSION['rol'] == 'admin' || $_SESSION['rol'] == 'tecnico'): ?>
                                <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#nuevoMantenimientoModal">
                                    <i class="fas fa-plus me-1"></i> Registrar Mantenimiento
                                </button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Pestaña de Incidencias -->
                            <div class="tab-pane fade" id="incidencias" role="tabpanel">
                                <?php
                                try {
                                    $stmt = $conn->prepare("SELECT i.*, ur.nombre as reportante, ua.nombre as asignado 
                                                           FROM incidencias i 
                                                           JOIN usuarios ur ON i.usuario_reporte_id = ur.id 
                                                           LEFT JOIN usuarios ua ON i.usuario_asignado_id = ua.id 
                                                           WHERE i.computador_id = :pc_id 
                                                           ORDER BY i.fecha_reporte DESC");
                                    $stmt->bindParam(':pc_id', $pc_id);
                                    $stmt->execute();
                                    
                                    if ($stmt->rowCount() > 0) {
                                ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Título</th>
                                                <th>Estado</th>
                                                <th>Prioridad</th>
                                                <th>Reportante</th>
                                                <th>Asignado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($incidencia = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                                // Determinar clase CSS según el estado
                                                $estado_class = '';
                                                switch ($incidencia['estado']) {
                                                    case 'reportada':
                                                        $estado_class = 'text-primary';
                                                        break;
                                                    case 'asignada':
                                                        $estado_class = 'text-info';
                                                        break;
                                                    case 'en_proceso':
                                                        $estado_class = 'text-warning';
                                                        break;
                                                    case 'resuelta':
                                                        $estado_class = 'text-success';
                                                        break;
                                                    case 'cerrada':
                                                        $estado_class = 'text-secondary';
                                                        break;
                                                }
                                                
                                                // Determinar clase CSS según la prioridad
                                                $prioridad_class = '';
                                                switch ($incidencia['prioridad']) {
                                                    case 'baja':
                                                        $prioridad_class = 'text-success';
                                                        break;
                                                    case 'media':
                                                        $prioridad_class = 'text-warning';
                                                        break;
                                                    case 'alta':
                                                        $prioridad_class = 'text-danger';
                                                        break;
                                                    case 'critica':
                                                        $prioridad_class = 'text-danger fw-bold';
                                                        break;
                                                }
                                            ?>
                                            <tr onclick="window.location.href='detalle_incidencia.php?id=<?php echo $incidencia['id']; ?>'" style="cursor: pointer;">
                                                <td><?php echo date('d/m/Y H:i', strtotime($incidencia['fecha_reporte'])); ?></td>
                                                <td><?php echo htmlspecialchars($incidencia['titulo']); ?></td>
                                                <td class="<?php echo $estado_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $incidencia['estado'])); ?></td>
                                                <td class="<?php echo $prioridad_class; ?>"><?php echo ucfirst($incidencia['prioridad']); ?></td>
                                                <td><?php echo htmlspecialchars($incidencia['reportante']); ?></td>
                                                <td><?php echo $incidencia['asignado'] ? htmlspecialchars($incidencia['asignado']) : 'No asignado'; ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php
                                    } else {
                                        echo '<div class="alert alert-info">No hay incidencias reportadas para esta computadora.</div>';
                                    }
                                } catch(PDOException $e) {
                                    echo '<div class="alert alert-danger">Error al cargar las incidencias: ' . $e->getMessage() . '</div>';
                                }
                                ?>
                                
                                <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#nuevaIncidenciaModal">
                                    <i class="fas fa-plus me-1"></i> Reportar Incidencia
                                </button>
                            </div>
                            
                            <!-- Pestaña de Edición (solo para admin/tecnico) -->
                            <?php if ($_SESSION['rol'] == 'admin' || $_SESSION['rol'] == 'tecnico'): ?>
                            <div class="tab-pane fade" id="editar" role="tabpanel">
                                <form id="formEditarPc" action="actualizar_pc.php" method="POST">
                                    <input type="hidden" name="pc_id" value="<?php echo $pc_id; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="codigo_patrimonio" class="form-label">Código Patrimonial</label>
                                            <input type="text" class="form-control" id="codigo_patrimonio" name="codigo_patrimonio" value="<?php echo htmlspecialchars($pc['codigo_patrimonio']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="estado" class="form-label">Estado</label>
                                            <select class="form-select" id="estado" name="estado" required>
                                                <option value="operativo" <?php echo $pc['estado'] == 'operativo' ? 'selected' : ''; ?>>Operativo</option>
                                                <option value="mantenimiento" <?php echo $pc['estado'] == 'mantenimiento' ? 'selected' : ''; ?>>En Mantenimiento</option>
                                                <option value="dañado" <?php echo $pc['estado'] == 'dañado' ? 'selected' : ''; ?>>Dañado</option>
                                                <option value="retirado" <?php echo $pc['estado'] == 'retirado' ? 'selected' : ''; ?>>Retirado</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="marca" class="form-label">Marca</label>
                                            <input type="text" class="form-control" id="marca" name="marca" value="<?php echo htmlspecialchars($pc['marca']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="modelo" class="form-label">Modelo</label>
                                            <input type="text" class="form-control" id="modelo" name="modelo" value="<?php echo htmlspecialchars($pc['modelo']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="sistema_operativo" class="form-label">Sistema Operativo</label>
                                            <input type="text" class="form-control" id="sistema_operativo" name="sistema_operativo" value="<?php echo htmlspecialchars($pc['sistema_operativo']); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="version_so" class="form-label">Versión</label>
                                            <input type="text" class="form-control" id="version_so" name="version_so" value="<?php echo htmlspecialchars($pc['version_so']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="procesador" class="form-label">Procesador</label>
                                        <input type="text" class="form-control" id="procesador" name="procesador" value="<?php echo htmlspecialchars($pc['procesador']); ?>">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="ram_gb" class="form-label">RAM (GB)</label>
                                            <input type="number" class="form-control" id="ram_gb" name="ram_gb" min="1" step="1" value="<?php echo $pc['ram_gb']; ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="almacenamiento_gb" class="form-label">Almacenamiento (GB)</label>
                                            <input type="number" class="form-control" id="almacenamiento_gb" name="almacenamiento_gb" min="1" step="1" value="<?php echo $pc['almacenamiento_gb']; ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="tipo_almacenamiento" class="form-label">Tipo</label>
                                            <select class="form-select" id="tipo_almacenamiento" name="tipo_almacenamiento">
                                                <option value="HDD" <?php echo $pc['tipo_almacenamiento'] == 'HDD' ? 'selected' : ''; ?>>HDD</option>
                                                <option value="SSD" <?php echo $pc['tipo_almacenamiento'] == 'SSD' ? 'selected' : ''; ?>>SSD</option>
                                                <option value="NVMe" <?php echo $pc['tipo_almacenamiento'] == 'NVMe' ? 'selected' : ''; ?>>NVMe</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="direccion_ip" class="form-label">Dirección IP</label>
                                            <input type="text" class="form-control" id="direccion_ip" name="direccion_ip" value="<?php echo htmlspecialchars($pc['direccion_ip']); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="direccion_mac" class="form-label">Dirección MAC</label>
                                            <input type="text" class="form-control" id="direccion_mac" name="direccion_mac" value="<?php echo htmlspecialchars($pc['direccion_mac']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="ultimo_mantenimiento" class="form-label">Último Mantenimiento</label>
                                            <input type="date" class="form-control" id="ultimo_mantenimiento" name="ultimo_mantenimiento" value="<?php echo $pc['ultimo_mantenimiento']; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="proximo_mantenimiento" class="form-label">Próximo Mantenimiento</label>
                                            <input type="date" class="form-control" id="proximo_mantenimiento" name="proximo_mantenimiento" value="<?php echo $pc['proximo_mantenimiento']; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="observaciones" class="form-label">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($pc['observaciones']); ?></textarea>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                    } else {
                        echo '<div class="alert alert-danger">Computadora no encontrada.</div>';
                    }
                } catch(PDOException $e) {
                    echo '<div class="alert alert-danger">Error al cargar los detalles de la computadora: ' . $e->getMessage() . '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal para nuevo mantenimiento -->
    <div class="modal fade" id="nuevoMantenimientoModal" tabindex="-1" aria-labelledby="nuevoMantenimientoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nuevoMantenimientoModalLabel">Registrar Mantenimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevoMantenimiento" action="guardar_mantenimiento.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="computador_id" value="<?php echo $pc_id; ?>">
                        <input type="hidden" name="usuario_id" value="<?php echo $_SESSION['usuario_id']; ?>">
                        
                        <div class="mb-3">
                            <label for="tipo_mantenimiento" class="form-label">Tipo de Mantenimiento</label>
                            <select class="form-select" id="tipo_mantenimiento" name="tipo_mantenimiento" required>
                                <option value="">Seleccionar...</option>
                                <option value="preventivo">Preventivo</option>
                                <option value="correctivo">Correctivo</option>
                                <option value="actualizacion">Actualización</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="acciones_realizadas" class="form-label">Acciones Realizadas</label>
                            <textarea class="form-control" id="acciones_realizadas" name="acciones_realizadas" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="componentes_cambiados" class="form-label">Componentes Cambiados (opcional)</label>
                            <textarea class="form-control" id="componentes_cambiados" name="componentes_cambiados" rows="2"></textarea>
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

    <!-- Modal para nueva incidencia -->
    <div class="modal fade" id="nuevaIncidenciaModal" tabindex="-1" aria-labelledby="nuevaIncidenciaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nuevaIncidenciaModalLabel">Reportar Incidencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevaIncidencia" action="reportar_incidencia.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="computador_id" value="<?php echo $pc_id; ?>">
                        <input type="hidden" name="usuario_reporte_id" value="<?php echo $_SESSION['usuario_id']; ?>">
                        
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="prioridad" class="form-label">Prioridad</label>
                            <select class="form-select" id="prioridad" name="prioridad" required>
                                <option value="media">Media</option>
                                <option value="baja">Baja</option>
                                <option value="alta">Alta</option>
                                <option value="critica">Crítica</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Reportar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>