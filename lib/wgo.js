"use strict";

let wgoAjaxUrl

function wgoLoadDatatables() {
    

    tsm_datatables = jQuery('#wgo-datatable').DataTable({
        dom: '<"float-left"i><"float-right"f>t<"float-left"l>B<"float-right"p><"clearfix">',
        responsive: true,
        order: [[0, "desc"]],
        buttons: ['csv', 'excel', 'pdf'],
        initComplete: function () {
            this.api().columns().every(function () {
                let that = this

                jQuery('input', this.footer()).on('keyup change', function () {
                    if (that.search() !== this.value) {
                        that
                            .search(this.value)
                            .draw()
                    }
                })
            })
        },
        processing: true,
        serverSide: true,
        ajax: {
            url: wgoAjaxUrl + '?action=wgo_main_server_processing',
            type: 'POST'
        },
        columnDefs: [
            { 'name': 'time', 'targets': 0 },
            { 'name': 'url', 'targets': 1 },
            { 'name': 'remote_ip', 'targets': 2 },
            { 'name': 'remote_port', 'targets': 3 },
            { 'name': 'country_code', 'targets': 4 },
            { 'name': 'user_agent', 'targets': 5 },
            { 'name': 'method', 'targets': 6 },
            { 'name': 'last_minute', 'targets': 7 },
            { 'name': 'last_hour', 'targets': 8 }
        ]
    })

    console.log('Datatable loaded!')
}

function doAjaxPopup(action) {
    let xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function (response) {
        if (xhr.readyState === 4) {
            document.getElementById('wgo-popup-info-content').innerHTML = xhr.responseText;
        }
    };

    xhr.open('POST', wgoAjaxUrl);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send('action=' + action);

    document.getElementById('wgo-popup-info').style.visibility = 'initial';
}

// Starts all JS..
window.addEventListener('load', () => {
    if (typeof weAreInWhatsGoingOn !== 'undefined') {
        wgoAjaxUrl = document.getElementById('wgo_form').dataset.wgo_ajax_url;

        paintMainChart()
        paintCountriesAndContinents()
        wgoLoadDatatables()
    }
})
