"use strict";

window.addEventListener('load', () => {
    if(typeof paintMainChart !== 'undefined') paintMainChart();
    if(typeof paintCountriesAndContinents !== 'undefined') paintCountriesAndContinents();
});

function showPayloadsLog() {
    let xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function (response) {
        if (xhr.readyState === 4) {
            document.getElementById('wgo-popup-info-content').innerHTML = xhr.responseText;
        }
    };

    xhr.open('POST', '/wp-admin/admin-ajax.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send('action=wgo_show_payloads');

    document.getElementById('wgo-popup-info').style.visibility = 'initial';
}

function showAllIpsAndCounters() {
    let xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function (response) {
        if (xhr.readyState === 4) {
            document.getElementById('wgo-popup-info-content').innerHTML = xhr.responseText;
        }
    };

    xhr.open('POST', '/wp-admin/admin-ajax.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send('action=wgo_all_ips_and_counters');

    document.getElementById('wgo-popup-info').style.visibility = 'initial';
}

function showAllIps404s() {
    let xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function (response) {
        if (xhr.readyState === 4) {
            document.getElementById('wgo-popup-info-content').innerHTML = xhr.responseText;
        }
    };

    xhr.open('POST', '/wp-admin/admin-ajax.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send('action=wgo_all_ips_404s');

    document.getElementById('wgo-popup-info').style.visibility = 'initial';
}

function showAllUrls404s() {
    let xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function (response) {
        if (xhr.readyState === 4) {
            document.getElementById('wgo-popup-info-content').innerHTML = xhr.responseText;
        }
    };

    xhr.open('POST', '/wp-admin/admin-ajax.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send('action=wgo_all_urls_404s');

    document.getElementById('wgo-popup-info').style.visibility = 'initial';
}

function showAllBlocks() {
    let xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function (response) {
        if (xhr.readyState === 4) {
            document.getElementById('wgo-popup-info-content').innerHTML = xhr.responseText;
        }
    };

    xhr.open('POST', '/wp-admin/admin-ajax.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send('action=wgo_all_blocks');

    document.getElementById('wgo-popup-info').style.visibility = 'initial';
}