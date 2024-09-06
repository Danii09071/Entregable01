<?php
$carpetaNombre = isset($_GET['-']) ? $_GET['-'] : '';
$carpetaNombreCorta = substr($carpetaNombre, 0, 3);
$carpetaRuta = "./descarga/" . $carpetaNombre;

try {
    if (!file_exists($carpetaRuta)) {
        mkdir($carpetaRuta, 0755, true);
        $mensaje = "Carpeta '$carpetaNombre' creada con éxito.";
    } else {
        $mensaje = "La carpeta '$carpetaNombre' ya existe.";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['archivo'])) {
            foreach ($_FILES['archivo']['tmp_name'] as $key => $tmp_name) {
                $archivoNombre = $_FILES['archivo']['name'][$key];
                $archivoNombre = str_replace(' ', '_', $archivoNombre);
                $archivoTmp = $_FILES['archivo']['tmp_name'][$key];
                
                if (move_uploaded_file($archivoTmp, $carpetaRuta . '/' . $archivoNombre)) {
                    $subido = true;
                    $mensaje = "Archivo(s) subido(s) con éxito.";
                } else {
                    throw new Exception("Error al subir el archivo.");
                }
            }
        }
    }

    if (isset($_POST['eliminarArchivo'])) {
        $archivoAEliminar = $_POST['eliminarArchivo'];
        $archivoRutaAEliminar = $carpetaRuta . '/' . $archivoAEliminar;

        if (file_exists($archivoRutaAEliminar)) {
            if (unlink($archivoRutaAEliminar)) {
                $mensaje = "Archivo '$archivoAEliminar' eliminado con éxito.";
            } else {
                throw new Exception("Error al eliminar el archivo.");
            }
        } else {
            throw new Exception("El archivo '$archivoAEliminar' no existe.");
        }
    }

    if (isset($_POST['descargarTodo'])) {
        $zip = new ZipArchive();
        $zipNombre = $carpetaNombre . '.zip';
        $zipRuta = './descarga/' . $zipNombre;

        if ($zip->open($zipRuta, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("No se pudo crear el archivo ZIP.");
        }

        $files = scandir($carpetaRuta);
        $files = array_diff($files, array('.', '..'));

        foreach ($files as $file) {
            $zip->addFile($carpetaRuta . '/' . $file, $file);
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipNombre . '"');
        header('Content-Length: ' . filesize($zipRuta));
        readfile($zipRuta);
        unlink($zipRuta); // Opcional: Eliminar el archivo zip después de descargar
        exit();
    }

    if (isset($_POST['eliminarTodo'])) {
        $files = scandir($carpetaRuta);
        $files = array_diff($files, array('.', '..'));

        foreach ($files as $file) {
            unlink($carpetaRuta . '/' . $file);
        }

        $mensaje = "Todos los archivos han sido eliminados.";
    }
}catch (Exception $e) {
    $mensaje = "Error: " . htmlspecialchars($e->getMessage());
}
?>



<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compartir Archivos</title>
    <script src="parametro.js"></script>
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>
    <h1>Compartir Archivos<sup class="beta">BETA</sup></h1>
    <div class="content">
        <h3>Sube tus archivos y comparte este enlace temporal: <span>kellyuwaraisolar.online/?-=<?php echo htmlspecialchars($carpetaNombreCorta); ?></span></h3>
        <div class="link-container">
            <button onclick="copiarAlPortapapeles()">Copiar enlace</button>
        </div>
        <div class="container">
        <div class="drop-area" id="drop-area">
            <form action="" id="form" method="POST" enctype="multipart/form-data">
                <input type="file" class="file-input" name="archivo[]" id="archivo" multiple onchange="document.getElementById('form').submit()">
                <label for="archivo">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" style="fill:#0730c5;transform: ;msFilter:;">
                    <path d="M13 19v-4h3l-4-5-4 5h3v4z"></path>
                    <path d="M7 19h2v-2H7c-1.654 0-3-1.346-3-3 0-1.404 1.199-2.756 2.673-3.015l.581-.102.192-.558C8.149 8.274 9.895 7 12 7c2.757 0 5 2.243 5 5v1h1c1.103 0 2 .897 2 2s-.897 2-2 2h-3v2h3c2.206 0 4-1.794 4-4a4.01 4.01 0 0 0-3.056-3.888C18.507 7.67 15.56 5 12 5 9.244 5 6.85 6.611 5.757 9.15 3.609 9.792 2 11.82 2 14c0 2.757 2.243 5 5 5z"></path>
                </svg> 
                </label>
                <p>Arrastra tus archivos aquí</p>
                <p class="drop-message">Suelta los archivos aquí</p>
            </form>
                <!-- Barra de progreso -->
                <div id="progress-container" style="display: none;">
                    <progress id="progress-bar" value="0" max="100"></progress>
                    <span id="progress-text">0%</span>
                </div>
        </div>

            <div class="container2">
                <div id="file-list" class="pila">
                    <?php
                    $targetDir = $carpetaRuta;
                    $files = scandir($targetDir);
                    $files = array_diff($files, array('.', '..'));

                    if (count($files) > 0) {
                        echo "<h3 style='margin-bottom:10px;'>Archivos Subidos:</h3>";

                        foreach ($files as $file) {
                            echo "<div class='archivos_subidos'>
                            <div><a href='$carpetaRuta/$file' download class='boton-descargar'>$file</a></div>
                            <div>
                            <form action='' method='POST' style='display:inline;'>
                                <input type='hidden' name='eliminarArchivo' value='$file'>
                                <button type='submit' class='btn_delete'>
                                    <svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-trash' width='24' height='24' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' fill='none' stroke-linecap='round' stroke-linejoin='round'>
                                        <path stroke='none' d='M0 0h24v24H0z' fill='none'/>
                                        <path d='M4 7l16 0' />
                                        <path d='M10 11l0 6' />
                                        <path d='M14 11l0 6' />
                                        <path d='M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12' />
                                        <path d='M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3' />
                                    </svg>
                                </button>
                            </form>
                        </div>
                        </div>";
                        }
                    } else {
                        echo "No se han subido archivos.";
                    }
                    ?>
                </div>
            </div>
        </div>
        <form action="" method="POST">
            <button type="submit" name="descargarTodo" class="btn_download">Descargar Todo</button>
            <button type="submit" name="eliminarTodo" class="btn_delete">Eliminar Todo</button>
        </form>
    </div>

    <div class="credits">
        <p>© By Dani Solar 2024</p>
    </div>

    <script>
        function copiarAlPortapapeles() {
            const enlace = "kellyuwaraisolar.online/?-=<?php echo htmlspecialchars($carpetaNombreCorta); ?>";
            navigator.clipboard.writeText(enlace).then(() => {
                alert('Enlace copiado al portapapeles!');
            }).catch(err => {
                console.error('Error al copiar el enlace: ', err);
            });
            }

            
        document.addEventListener('DOMContentLoaded', function() {
        const dropArea = document.getElementById('drop-area');
        const archivoInput = document.getElementById('archivo');
        const form = document.getElementById('form');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const fileListContainer = document.getElementById('file-list-container');

        // Manejador para el evento de arrastrar y soltar
        dropArea.addEventListener('dragover', function(event) {
            event.preventDefault();
            dropArea.classList.add('dragging');
            dropArea.querySelector('.drop-message').style.display = 'block';
        });

        dropArea.addEventListener('dragleave', function() {
            dropArea.classList.remove('dragging');
            dropArea.querySelector('.drop-message').style.display = 'none';
        });

        dropArea.addEventListener('drop', function(event) {
            event.preventDefault();
            dropArea.classList.remove('dragging');
            dropArea.querySelector('.drop-message').style.display = 'none';

            const files = event.dataTransfer.files;
            if (files.length > 0) {
                // Actualiza el input de archivo
                archivoInput.files = files;
                handleFileUpload(); // Llama a la función para manejar la subida de archivos
            }
        });

        // Manejador para el cambio en el input de archivos
        archivoInput.addEventListener('change', function() {
            handleFileUpload();
        });

        function handleFileUpload() {
            if (archivoInput.files.length > 0) {
                progressContainer.style.display = 'block';
                const formData = new FormData(form);
                const xhr = new XMLHttpRequest();

                xhr.open('POST', form.action, true);

                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBar.value = percentComplete;
                        progressText.textContent = percentComplete + '%';
                    }
                };

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        progressText.textContent = '¡Subida completada!';
                        // Actualiza automáticamente la vista de archivos
                        updateFileList();
                    } else {
                        progressText.textContent = 'Error en la subida.';
                    }
                };

                xhr.onerror = function() {
                    progressText.textContent = 'Error en la subida.';
                };

                xhr.send(formData);
            }
        }

        function updateFileList() {
            const files = archivoInput.files;
            fileListContainer.innerHTML = ''; // Limpia la lista de archivos anterior

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileItem = document.createElement('div');
                fileItem.textContent = file.name;
                fileListContainer.appendChild(fileItem);
            }
        }
    });
    </script>
</body>
</html>