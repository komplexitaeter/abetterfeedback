if (getBrowserLanguage().startsWith('de'))
    translateElements('global', 'de');
else
    translateElements('global','en');

document.getElementById('takePhotoButton').onclick = function() {
    document.getElementById('cameraInput').click();
};

document.getElementById('textInputOverlay').style.display = 'none';

document.getElementById('writeTextButton').onclick = function() {
    document.getElementById('textInputOverlay').style.display = 'flex';
    const feedbackTxtArea = document.getElementById("feedbackTxt");
    feedbackTxtArea.focus();
    feedbackTxtArea.select();
    feedbackTxtArea.setSelectionRange(0, feedbackTxtArea.value.length);
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

let recorder;
let audioStream;

document.getElementById('recordAudioButton').addEventListener('click', () => {
    console.log('Record Audio button clicked');
    document.getElementById('audioRecordOverlay').style.display = 'flex';
});

document.getElementById('startRecordBtn').addEventListener('click', () => {
    document.getElementById('recordingIndicator').style.visibility = 'visible'; // Show the grey indicator
    startRecording();
});
document.getElementById('stopRecordBtn').addEventListener('click', stopRecording);
document.getElementById('sendAudioBtn').addEventListener('click', sendAudioFeedback);
document.getElementById('cancelAudioBtn').addEventListener('click', cancelRecording);

function startRecording() {
    console.log('Attempting to start recording');
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(function(stream) {
            console.log('Microphone access granted');
            audioStream = stream;
            recorder = RecordRTC(stream, {
                type: 'audio',
                mimeType: 'audio/wav',
                recorderType: RecordRTC.StereoAudioRecorder,
                numberOfAudioChannels: 1
            });
            recorder.startRecording();

            document.getElementById('startRecordBtn').style.display = 'none';
            document.getElementById('stopRecordBtn').style.display = 'inline-block';
            document.getElementById('recordingIndicator').style.backgroundColor = 'red'; // Change to red
            document.getElementById('recordingIndicator').style.animation = 'blinking 1s infinite'; // Start blinking
        })
        .catch(function(error) {
            console.error('Error accessing the microphone:', error);
            alert('Fehler beim Zugriff auf das Mikrofon. Bitte überprüfen Sie Ihre Berechtigungen.');
            document.getElementById('recordingIndicator').style.visibility = 'hidden'; // Hide the indicator on error
        });
}

function stopRecording() {
    console.log('Attempting to stop recording');
    if (recorder) {
        recorder.stopRecording(function() {
            console.log('Recording stopped');
            document.getElementById('stopRecordBtn').style.display = 'none';
            document.getElementById('sendAudioBtn').style.display = 'inline-block';
            document.getElementById('recordingIndicator').style.visibility = 'hidden'; // Hide the blinking indicator
            document.getElementById('recordingIndicator').style.backgroundColor = 'grey'; // Reset to grey
            document.getElementById('recordingIndicator').style.animation = 'none'; // Stop blinking
        });
    } else {
        console.warn('Recorder is not defined');
    }
}

function sendAudioFeedback() {
    console.log('Sending audio feedback');
    if (recorder) {
        recorder.stopRecording(function() {
            const blob = recorder.getBlob();
            const file = new File([blob], 'audio_feedback.wav', { type: 'audio/wav' });
            showSpinner();
            uploadFile(file, 'audio');
            document.getElementById('audioRecordOverlay').style.display = 'none';
            resetAudioRecording();
        });
    } else {
        console.error('No audio data available to send');
        alert('Es sind keine Audiodaten zum Senden verfügbar. Bitte nehmen Sie erneut auf.');
    }
}

function cancelRecording() {
    console.log('Cancelling recording');
    stopMediaTracks();
    document.getElementById('audioRecordOverlay').style.display = 'none';
    resetAudioRecording();
}

function resetAudioRecording() {
    console.log('Resetting audio recording');
    if (recorder) {
        recorder.reset();
        recorder = null;
    }
    document.getElementById('startRecordBtn').style.display = 'inline-block';
    document.getElementById('stopRecordBtn').style.display = 'none';
    document.getElementById('sendAudioBtn').style.display = 'none';
    document.getElementById('recordingIndicator').style.visibility = 'hidden'; // Ensure indicator is hidden
    document.getElementById('recordingIndicator').style.backgroundColor = 'grey'; // Reset to grey
    document.getElementById('recordingIndicator').style.animation = 'none'; // Stop blinking
}

function stopMediaTracks() {
    console.log('Stopping media tracks');
    if (audioStream) {
        audioStream.getTracks().forEach(track => {
            track.stop();
        });
        audioStream = null;
    }
}

function uploadText(text) {
    const formData = new FormData();
    formData.append('text_content', text);

    const url = `api/upload_feedback.php?context=${encodeURIComponent(getContext())}`;
    console.log("Uploading text to URL: ", url);

    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(response => {
            console.log('Response status:', response.status);
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
    formData.append(fileType, file);

    const mimeType = file.type;
    const fileName = file.name;

    const url = `api/upload_feedback.php?context=${encodeURIComponent(getContext())}&mime_type=${encodeURIComponent(mimeType)}&file_name=${encodeURIComponent(fileName)}`;
    console.log("Uploading file to URL: ", url);

    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(response => {
            console.log('Response status:', response.status);
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
