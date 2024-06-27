if (getBrowserLanguage().startsWith('de'))
    translateElements('global', 'de');
else
    translateElements('global','en');


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
    showSpinner();
    uploadText(document.getElementById("feedbackTxt").value);
};

document.getElementById('continueBtn').addEventListener('click', function() {
    document.getElementById('thankYouOverlay').style.display = 'none';
});

document.getElementById('cameraInput').onchange = function(event) {
    const file = event.target.files[0];
    if (file) {
        showSpinner();
        uploadFile(file, 'photo');
        document.getElementById('cameraInput').value = "";  // Reset the input value
    }
};

document.getElementById('recordAudioButton').onclick = function() {
    document.getElementById('audioInput').click();
};

document.getElementById('audioInput').onchange = function(event) {
    const file = event.target.files[0];
    if (file) {
        showSpinner();
        uploadFile(file, 'audio');
        document.getElementById('audioInput').value = "";  // Reset the input value
    }
};

function uploadText(text) {
    const formData = new FormData();
    formData.append('text_content', text);

    fetch('api/upload_file.php?context=' + getContext(), {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Netzwerkantwort war nicht ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Erfolg:', data);
            document.getElementById('thankYouOverlay').style.display = 'flex';
        })
        .catch((error) => {
            console.error('Fehler:', error);
            alert(document.getElementById('api_error_msg').value);
        })
        .finally(() => {
            hideSpinner();
        });
}

function uploadFile(file, fileType) {
    const formData = new FormData();
    formData.append(fileType, file); // Verwenden Sie den Datei-Typ als Schlüssel

    const mimeType = file.type;
    const fileName = file.name;

    const url = `api/upload_file.php?context=${getContext()}&mime_type=${encodeURIComponent(mimeType)}&file_name=${encodeURIComponent(fileName)}`;

    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Netzwerkantwort war nicht ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Erfolg:', data);
            document.getElementById('thankYouOverlay').style.display = 'flex';
        })
        .catch((error) => {
            console.error('Fehler:', error);
            alert(document.getElementById('api_error_msg').value);
        })
        .finally(() => {
            hideSpinner();
        });
}

function getContext() {
    return new URLSearchParams(new URL(window.location.href).search).get('context');
}

function showSpinner() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideSpinner() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

function getBrowserLanguage() {
    return (navigator.languages && navigator.languages[0]) || navigator.language || navigator.userLanguage;
}



function translateElements(file_prefix, language_code){

    let url = "./"+file_prefix+"_translations.json";
    fetch(url)
        .then((response) => {
            return response.json();
        })
        .then((myJson) => {
            myJson.forEach( def => {
                let element = document.getElementById(def.id);
                if(def[language_code].hidden) {
                    if (element !== null) {
                        element.value = def[language_code].hidden;
                    } else {
                        element = document.createElement("input");
                        element.type = "hidden";
                        element.id = def.id;
                        element.value = def[language_code].hidden;
                        document.body.appendChild(element);
                    }
                }
                if(element !== null) {
                    if(def[language_code].text) {
                        element.textContent = def[language_code].text;
                    }
                    if(def[language_code].placeholder) {
                        element.placeholder = def[language_code].placeholder;
                    }
                    if(def[language_code].value) {
                        element.value = def[language_code].value;
                    }
                    if(def[language_code].href) {
                        element.href = def[language_code].href;
                    }
                    if(def[language_code].src) {
                        element.src = def[language_code].src;
                    }
                    if(def[language_code].alt) {
                        element.alt = def[language_code].alt;
                    }
                    if(def[language_code].title) {
                        element.title = def[language_code].title;
                    }
                    if(def[language_code].innerHTML) {
                        element.innerHTML = def[language_code].innerHTML;
                    }
                }
                else{
                    console.log(def.id);
                }
            });
        });
}