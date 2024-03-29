"use strict";

let wgoAjaxUrl;
let wgoDatatables;
let wgoMapPainted = false;

// Starts all JS..
window.addEventListener('load', () => {
    if (typeof weAreInWhatsGoingOn !== 'undefined') {
        wgoMain();
    }
});

function wgoMain() {
    wgoAjaxUrl = document.getElementById('wgo_form').dataset.wgo_ajax_url;

    paintMainChart();
    wgoLoadDatatables();

    // Auto-reload Datatables..
    let currentAutoreloadDatatables = document.getElementById('autoreload_datatables').value;
    if (currentAutoreloadDatatables > 0) {
        setInterval(function () {
            wgoDatatables.ajax.reload();
            jQuery('.wgo-box-main-chart').load(wgoAjaxUrl + '?action=wgo_main_chart', paintMainChart);
        }, currentAutoreloadDatatables * 1000);
    }

    jQuery('body').on('click', '#wgo-btn-show-ban-rules', function () {
        jQuery('.wgo-box').addClass('wgo-d-none');
        jQuery('.wgo-box-ban-rules').removeClass('wgo-d-none');
    });

    jQuery('body').on('click', '#wgo-btn-show-banned-ips', function () {
        jQuery('.wgo-box').addClass('wgo-d-none');
        jQuery('.wgo-box-banned-ips').removeClass('wgo-d-none');
    });

    jQuery('body').on('click', '#wgo-btn-show-ip-lists', function () {
        jQuery('.wgo-box').addClass('wgo-d-none');
        jQuery('.wgo-box-ip-lists').removeClass('wgo-d-none');
    });

    jQuery('body').on('click', '#wgo-btn-show-dos-and-ddos', function () {
        jQuery('.wgo-box').addClass('wgo-d-none');
        jQuery('.wgo-box-dos-and-ddos').removeClass('wgo-d-none');
    });

    jQuery('body').on('click', '#wgo-btn-show-regexes', function () {
        jQuery('.wgo-box').addClass('wgo-d-none');
        jQuery('.wgo-box-regexes').removeClass('wgo-d-none');
    });

    jQuery('body').on('click', '#wgo-btn-show-countries', function () {
        jQuery('.wgo-box').addClass('wgo-d-none');
        jQuery('.wgo-box-countries').removeClass('wgo-d-none');
        if (!wgoMapPainted) {
            paintCountriesAndContinents();
            wgoMapPainted = true;
        }
    });

    jQuery('body').on('click', '#wgo-btn-show-last-blocks', function () {
        jQuery('.wgo-box').addClass('wgo-d-none');
        jQuery('.wgo-box-last-blocks').removeClass('wgo-d-none');
    });

    jQuery('body').on('click', '#wgo-btn-show-suspicious-behaviors', function () {
        jQuery('.wgo-box').addClass('wgo-d-none');
        jQuery('.wgo-box-suspicious-behaviors').removeClass('wgo-d-none');
    });
}

function wgoLoadDatatables() {
    jQuery('#wgo-datatable tfoot th').each(function () {
        //let title = jQuery(this).text();

        jQuery(this).html('<input type="text" placeholder="Filter.." />');
    });

    wgoDatatables = jQuery('#wgo-datatable').DataTable({
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
            { 'name': 'last_hour', 'targets': 8 },
            { 'name': 'is_404', 'targets': 9 }
        ]
    });

    console.log('Datatable loaded!');
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
