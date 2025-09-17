<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit;
}

$sede_id = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : 0;

require 'conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salones - Gestión de Salones</title>
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
        .salon-card {
            cursor: pointer;
            border-left: 5px solid #4895ef;
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-3 col-lg-2 sidebar p-0">
            
            </div>

         
            <div class="col-md-9 col-lg-10 ms-sm-auto main-content">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Salones</li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Salones</h1>
                    <?php if ($_SESSION['rol'] == 'admin'): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoSalonModal">
                        <i class="fas fa-plus me-1"></i> Nuevo Salón
                    </button>
                    <?php endif; ?>
                </div>

                <?php
                try {
                 
                    $stmt = $conn->prepare("SELECT * FROM sedes WHERE id = :sede_id");
                    $stmt->bindParam(':sede_id', $sede_id);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $sede = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo "<h4 class='mb-4'>Sede: " . htmlspecialchars($sede['nombre']) . "</h4>";
                        
                      
                        $stmt = $conn->prepare("SELECT * FROM salones WHERE sede_id = :sede_id ORDER BY piso, codigo_salon");
                        $stmt->bindParam(':sede_id', $sede_id);
                        $stmt->execute();
                        
                        if ($stmt->rowCount() > 0) {
                            echo '<div class="row">';
                            while ($salon = $stmt->fetch(PDO::FETCH_ASSOC)) {
                               
                                $count = $conn->prepare("SELECT COUNT(*) as total FROM computadores WHERE salon_id = :salon_id AND estado = 'operativo'");
                                $count->bindParam(':salon_id', $salon['id']);
                                $count->execute();
                                $total_computadoras = $count->fetch(PDO::FETCH_ASSOC)['total'];
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card salon-card" onclick="window.location.href='computadoras.php?salon_id=<?php echo $salon['id']; ?>'">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($salon['codigo_salon']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">Piso <?php echo $salon['piso']; ?></h6>
                            <p class="card-text"><?php echo htmlspecialchars($salon['descripcion']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary">Capacidad: <?php echo $salon['capacidad']; ?></span>
                                <span class="badge bg-success"><?php echo $total_computadoras; ?> / <?php echo $salon['numero_computadores']; ?> PCs</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-info">No hay salones registrados en esta sede.</div>';
                        }
                    } else {
                        echo '<div class="alert alert-danger">Sede no encontrada.</div>';
                    }
                } catch(PDOException $e) {
                    echo "<div class='alert alert-danger'>Error al cargar los salones: " . $e->getMessage() . "</div>";
                }
                ?>
            </div>
        </div>
    </div>

  
    <div class="modal fade" id="nuevoSalonModal" tabindex="-1" aria-labelledby="nuevoSalonModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nuevoSalonModalLabel">Agregar Nuevo Salón</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevoSalon" action="guardar_salon.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="sede_id" value="<?php echo $sede_id; ?>">
                        <div class="mb-3">
                            <label for="codigo_salon" class="form-label">Código del Salón</label>
                            <input type="text" class="form-control" id="codigo_salon" name="codigo_salon" required>
                        </div>
                        <div class="mb-3">
                            <label for="piso" class="form-label">Piso</label>
                            <input type="number" class="form-control" id="piso" name="piso" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="capacidad" class="form-label">Capacidad</label>
                            <input type="number" class="form-control" id="capacidad" name="capacidad" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="numero_computadores" class="form-label">Número de Computadoras</label>
                            <input type="number" class="form-control" id="numero_computadores" name="numero_computadores" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
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
    <script>
      
    </script>
</body>
</html>