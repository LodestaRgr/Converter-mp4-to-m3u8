<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HLS Portal</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #f4f4f4, #e0e0e0);
            margin: 0;
            padding: 20px;
            color: #333;
            max-width: 900px;
            margin: 0 auto;
        }
        h1, h2 {
            color: black;
            text-align: center;
        }
        form {
            margin-bottom: 20px;
            border: 2px solid #007BFF;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        input[type="file"] {
            display: none;
        }
        .upload-area {
            border: 2px dashed #007BFF;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 20px;
            border-radius: 10px;
            transition: background-color 0.3s;
        }
        .upload-area:hover {
            background-color: #e9f5ff;
        }
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 1.1em;
            font-weight: bold;
        }
        input[type="submit"]:disabled {
            background: #ccc;
        }
        input[type="submit"]:hover:not(:disabled) {
            background: #0056b3;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            background: #fff;
            margin: 5px 0;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s;
        }
        li:hover {
            background: #e9e9e9;
        }

        /* Медиа-запросы для мобильных устройств */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            form {
                padding: 15px;
            }
            .upload-area {
                padding: 15px;
            }
            input[type="submit"] {
                padding: 8px;
            }
            h1 {
                font-size: 1.5em;
            }
            h2 {
                font-size: 1.2em;
            }
        }
    </style>
    <script>
        function confirmDeletion(folder) {
            if (confirm("Вы точно уверены, что хотите удалить этот плейлист?")) {
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'delete.php?folder=' + folder, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        //alert(xhr.responseText);
                        location.reload(); // Перезагрузить страницу для обновления списка
                    }
                };
                xhr.send();
            }
        }

        function handleFileSelect(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const fileInput = document.getElementById('fileToUpload');
            fileInput.files = event.dataTransfer.files;
            updateFileName();
            toggleSubmitButton();
        }

        function triggerFileInput() {
            document.getElementById('fileToUpload').click();
        }

        function updateFileName() {
            const fileInput = document.getElementById('fileToUpload');
            const uploadArea = document.querySelector('.upload-area');
            
            if (fileInput.files.length > 0) {
                const fileName = fileInput.files[0].name;
                uploadArea.innerHTML = `<strong>${fileName}</strong>`;
            } else {
                uploadArea.innerHTML = 'Перетащите видеофайл сюда или нажмите для выбора';
            }
            toggleSubmitButton();
        }

        function toggleSubmitButton() {
            const fileInput = document.getElementById('fileToUpload');
            const submitButton = document.querySelector('input[type="submit"]');
            submitButton.disabled = fileInput.files.length === 0;
        }

        document.querySelector('form').onsubmit = function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('fileToUpload');
            const formData = new FormData();
            formData.append('fileToUpload', fileInput.files[0]);

            // Добавляем новую строку в список сразу
            const fileName = fileInput.files[0].name;
            const list = document.querySelector('ul');
            const li = document.createElement('li');
            li.innerHTML = `${fileName} | <span style="color: green;">Загрузка...</span>`;
            list.insertBefore(li, list.firstChild);

            // Создаем элемент прогресс бара
            const progressDiv = document.createElement('div');
            progressDiv.className = 'progress';
            const progressBar = document.createElement('div');
            progressBar.className = 'progress-bar';
            progressDiv.appendChild(progressBar);
            li.appendChild(progressDiv);

            // Отправляем фал асинхронно
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload.php', true);

            // Обработчик прогресса загрузки
            xhr.upload.addEventListener('progress', function(event) {
                if (event.lengthComputable) {
                    const percentComplete = (event.loaded / event.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                    progressBar.innerHTML = Math.round(percentComplete) + '%';
                    progressDiv.style.display = 'block'; // Показываем прогресс бар
                }
            });

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        li.innerHTML = `${fileName} | <a href="media/${response.folder}/main.m3u8">Смотреть</a>`;
                    } else {
                        li.innerHTML = `${fileName} | <span style="color: red;">${response.message}</span>`;
                    }
                } else {
                    li.innerHTML = `${fileName} | <span style="color: red;">Ошибка загрузки</span>`;
                }
            };
            xhr.send(formData);

            // Очищаем форму
            fileInput.value = '';
            toggleSubmitButton();
        }

        document.querySelector('.upload-area').addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });

        function uploadFile() {
            const fileInput = document.getElementById('fileToUpload');
            const file = fileInput.files[0];
            if (!file) {
                alert('Пожалуйста, выберите файл');
                return;
            }

            // Создаем новый элемент  списке для отображения прогресса
            const progressItem = document.createElement('li');
            progressItem.innerHTML = `
                <div class="upload-progress">
                    <div>${file.name}</div>
                    <div class="progress">
                        <div class="progress-bar" style="width: 0%">0%</div>
                    </div>
                    <div class="status">Загрузка...</div>
                </div>
            `;
            document.querySelector('ul').insertBefore(progressItem, document.querySelector('ul').firstChild);

            const formData = new FormData();
            formData.append('fileToUpload', file);
            formData.append('submit', 'true');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload.php', true);

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    progressItem.querySelector('.progress-bar').style.width = percent + '%';
                    progressItem.querySelector('.progress-bar').textContent = Math.round(percent) + '%';
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            progressItem.querySelector('.status').textContent = 'Загрузка завершена';
                            progressItem.querySelector('.progress-bar').classList.add('success');
                            // Обновляем список файлов без перезагрузки страницы
                            updateFileList();
                        } else {
                            progressItem.querySelector('.status').textContent = 'Ошибка: ' + response.message;
                            progressItem.querySelector('.progress-bar').classList.add('error');
                        }
                    } catch (e) {
                        progressItem.querySelector('.status').textContent = 'Ошибка при обработке ответа';
                        progressItem.querySelector('.progress-bar').classList.add('error');
                    }
                }
            };

            xhr.onerror = function() {
                progressItem.querySelector('.status').textContent = 'Ошибка загрузки';
                progressItem.querySelector('.progress-bar').classList.add('error');
            };

            xhr.send(formData);
            return false;
        }

        // Функция для обновления списка файлов
        function updateFileList() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_files.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Обновляем только список файлов
                    const fileList = document.querySelector('ul');
                    fileList.innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        // Обновляем обработчик формы
        document.querySelector('form').onsubmit = function(e) {
            e.preventDefault();
            uploadFile();
        };
    </script>
</head>
<body>
    <h1>Конвертер mp4 в m3u8</h1>

    <h2>Загрузить видео</h2>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <div class="upload-area" ondragover="event.preventDefault();" ondrop="handleFileSelect(event)" onclick="triggerFileInput()">
            Перетащите видеофайл сюда или нажмите для выбора
        </div>
        <input type="file" name="fileToUpload" id="fileToUpload" accept="video/*" style="display: none;" onchange="updateFileName()">
        <input type="submit" value="Загрузить видео" name="submit" disabled>
    </form>

    <h2>Доступные плейлисты</h2>
    <ul class="available-playlists">
    <?php
    $dir = 'media';
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($folder = readdir($dh)) !== false) {
                if ($folder != "." && $folder != "..") {
                    $metadata_file = "$dir/$folder/metadata.json";
                    if (file_exists($metadata_file)) {
                        $metadata = json_decode(file_get_contents($metadata_file), true);
                        $original_filename = $metadata['original_filename'];
                        echo "<li style='display: flex; align-items: center; justify-content: space-between;'><img src='media/$folder/preview.gif' alt='Cover Image' style='max-width: 100px; max-height: 100px; width: auto; height: auto; margin-right: 10px;'> <div style='flex-grow: 1;'><a href='media/$folder/main.m3u8'>$folder</a> | <b>$original_filename</b> | <a href='#' onclick=\"confirmDeletion('$folder')\">Delete</a></div></li>";
                    }
                }
            }
            closedir($dh);
        }
    }
    ?>
    </ul>
</body>
</html>
