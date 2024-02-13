document.getElementById('takePhotoButton').onclick = function() {
    document.getElementById('cameraInput').click();
};

document.getElementById('cameraInput').onchange = function(event) {
    const file = event.target.files[0];
    if (file) {
        // Hier können Sie das Foto per API an Ihren Server senden.
        uploadPhoto(file);
    }
};

function uploadPhoto(file) {
    const formData = new FormData();
    formData.append('photo', file);

    fetch('api/upload_image.php', {
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
}