document.getElementById('takePhotoButton').onclick = function() {
    document.getElementById('cameraInput').click();
};

document.getElementById('writeTextButton').onclick = function() {
    document.getElementById('textInputOverlay').style.display = 'flex';
    const feedbackTxtArea = document.getElementById("feedbackTxt");
    feedbackTxtArea.focus();
    feedbackTxtArea.select();
};

document.getElementById('cancelTextBtn').onclick = function() {
    document.getElementById('textInputOverlay').style.display = 'none';
};

document.getElementById('sendTextBtn').onclick = function() {
    document.getElementById('textInputOverlay').style.display = 'none';
    uploadText(document.getElementById("feedbackTxt").value);
};
document.getElementById('continueBtn').addEventListener('click', function() {
    document.getElementById('thankYouOverlay').style.display = 'none';
});

document.getElementById('cameraInput').onchange = function(event) {
    const file = event.target.files[0];
    if (file) {
        // Hier können Sie das Foto per API an Ihren Server senden.
        uploadFile(file);
    }
};

document.getElementById('recordAudioButton').onclick = function() {
    document.getElementById('audioInput').click();
};

document.getElementById('audioInput').onchange = function(event) {
    const file = event.target.files[0];
    if (file) {
        // Hier können Sie die Audiodatei per API an Ihren Server senden.
        uploadFile(file);
    }
};

function uploadText(text){
    const formData = new FormData();
    formData.append('text_content', text);

    fetch('api/upload_text.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            console.log('Erfolg:', data);
        })
        .catch((error) => {
            console.error('Fehler:', error);
        });
    document.getElementById('thankYouOverlay').style.display = 'flex';
}

function uploadFile(file) {
    const formData = new FormData();
    formData.append('photo', file);

    // MIME-Typ und Dateiname der Datei abrufen
    const mimeType = file.type;
    const fileName = file.name;

    // URL mit MIME-Typ und Dateiname als GET-Parameter zusammenbauen
    const url = `api/upload_file.php?mime_type=${encodeURIComponent(mimeType)}&file_name=${encodeURIComponent(fileName)}`;

    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            console.log('Erfolg:', data);
            document.getElementById('thankYouOverlay').style.display = 'flex'; // Overlay anzeigen nach Erfolg
        })
        .catch((error) => {
            console.error('Fehler:', error);
        });
}