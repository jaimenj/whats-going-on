"use strict";

window.addEventListener('load', () => {
    if(typeof paintMainChart !== 'undefined') paintMainChart();
    if(typeof paintCountriesAndContinents !== 'undefined') paintCountriesAndContinents();
});

function doAjaxPopup(action) {
    let xhr = new XMLHttpRequest();
    let ajaxUrl = document.getElementById('wgo_form').dataset.ajaxurl;

    xhr.onreadystatechange = function (response) {
        if (xhr.readyState === 4) {
            document.getElementById('wgo-popup-info-content').innerHTML = xhr.responseText;
        }
    };
    
    xhr.open('POST', ajaxUrl);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send('action=' + action);

    document.getElementById('wgo-popup-info').style.visibility = 'initial';
}