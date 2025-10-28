angularAppAQ.factory('servicioGeneral', function ($rootScope, $http, configuracionGlobal) {
    var servicioGeneral = {
        'enviarAjax': function (information) {
            var options = angular.extend({}, {
                'method': 'POST',
                'url': '',
                'params': '',
                'data': {},
                'async': true,
                'success': function () {},
                'service': false,
                'error': function () {},
                'spinner': true
            }, information);
            
            var sUrl = options.servicio ? options.url : configuracionGlobal.URL + 'test/php/' + options.url;
            
            $http({
                'method': options.method,
                'async': options.async,
                'url': sUrl,
                'params': options.params,
                'data': options.data,
                'headers': {
                    'Content-type': 'application/json'
                }
            }).then(function (success) {
                options.success(success.data);
            }, function (error) {
                options.error(error);
            });
        },

        mostrarCargando: function() {
            $('#ajax-loading').removeClass('hidden');
            $('body').css('overflow', 'hidden');
        },

        ocultarCargando: function() {
            $('#ajax-loading').addClass('hidden');
            $('body').css('overflow', 'auto');
        },

        select2: function () {
            setTimeout(function () {
                $(".select2").select2();
            }, 300)
        },

        generarCSV: function(aFilasDatosCSV, sNombreArchivo) {
            //let csvContent = "data:text/csv;charset=utf-8,";
            let csvContent = "";
            aFilasDatosCSV.forEach(function(rowArray) {
                aRow = [];

                rowArray.forEach(function(sContent) {
                    sContent = angular.isNumber(sContent) ? servicioGeneral.redondearDecimal(sContent) : sContent;
                    //sContent = angular.isNumber(sContent) ? sContent.toString().replace(".", ",") : sContent;
                    aRow.push("\"" + sContent + "\"");
                });

                let row = aRow.join(";");

                csvContent += row + "\r\n";
            });

            var encodedUri = encodeURIComponent("\uFEFF" + csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", "data:text/csv; charset=utf-8; sep=;;," + encodedUri);
            link.setAttribute("download", sNombreArchivo + ".csv");
            document.body.appendChild(link);
            link.click();
        },

        alert: function (sClase, sTexto, iDuracion) {
            switch (sClase) {
                case 'error': 
                    $(".snackbar_error").html(sTexto);
                    $(".snackbar_error").show();

                    setTimeout(function(){ 
                        $(".snackbar_error").hide(); 
                    }, iDuracion); 
                break;
                case 'success': 
                    $(".snackbar_success").html(sTexto);
                    $(".snackbar_success").show();

                    setTimeout(function(){ 
                        $(".snackbar_success").hide();
                    }, iDuracion); 
                break;
            }
        },

        /* Formatting function for row details - modify as you need */
        formatDT: function ( d ) {
            // `d` is the original data object for the row
            return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
                '<tr>'+
                    '<td>Full name:</td>'+
                    '<td>'+d.name+'</td>'+
                '</tr>'+
                '<tr>'+
                    '<td>Extension number:</td>'+
                    '<td>'+d.extn+'</td>'+
                '</tr>'+
                '<tr>'+
                    '<td>Extra info:</td>'+
                    '<td>And any further details here (images etc)...</td>'+
                '</tr>'+
            '</table>';
        },

        selectize: function (id, information) {
            setTimeout(function() {
                var $select = $('#' + id).selectize();
                var selectize = $select[0].selectize;
                selectize.destroy();

                var options = angular.extend({}, {
                    persist: false,
                    maxItems: 1,
                    items: [],
                    valueField: 'id',
                    labelField: 'nombre',
                    searchField: ['nombre'],
                    sortField: [
                        {field: 'nombre', direction: 'asc'}
                    ],
                    options: [],
                    onChange: function () {}
                }, information);

                $('#' + id).selectize(options);
            }, 100);
        }
    };

    return servicioGeneral;
});