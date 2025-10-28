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
            
            var sUrl = options.servicio ? options.url : configuracionGlobal.URL + 'php/' + options.url;
            
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
                if ($('#' + id).length > 0) {
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
                }                
            }, 100);
        },

        obtenerFecha: function (valor) {
            let aPartesFecha = [];
            let sFecha = null;
            
            if (valor !== null) {
                if (valor.toString().length > 0) {
                    valor = valor.substring(0, 10);
        
                    if (valor.toString().indexOf('/') !== -1) {
                        aPartesFecha = valor.toString().split('/');
                    } else if (valor.toString().indexOf('-') !== -1) {
                        aPartesFecha = valor.toString().split('-');
                    }
                }
            }
            
            if (aPartesFecha.length === 3) {
                if (aPartesFecha[0].toString().length === 4) {
                    sFecha = aPartesFecha[0] + '-' + aPartesFecha[1] + '-' + aPartesFecha[2];
                } else if (aPartesFecha[2].toString().length === 4) {
                    sFecha = aPartesFecha[2] + '-' + aPartesFecha[1] + '-' + aPartesFecha[0];
                }
            }

            return sFecha;
        },

        obtenerHora: function(valor) {
            if (valor !== null) {
                if (valor.toString().length === 16) {
                    return valor.substring(11, 16) + ':00';
                } else if (valor.toString().length >= 19) {
                    return valor.substring(11, 19);
                }
            }
            
            return null;
        },

        generarIdLetras: function (length) {
            var result = '';
            var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            var charactersLength = characters.length;

            for ( var i = 0; i < length; i++ ) {
              result += characters.charAt(Math.floor(Math.random() * charactersLength));
            }

            return result;
        },

        fechaUtc: function(sFecha) {
            var iAnio = parseInt(sFecha.substring(0, 4));
            var iMes = parseInt(sFecha.substring(5, 7));
            var iDia = parseInt(sFecha.substring(8, 10));
            var offset = new Date().getTimezoneOffset();

            if (sFecha.length === 19) {
                var iHora = parseInt(sFecha.substring(11, 13));
                var iMinuto = parseInt(sFecha.substring(14, 16));
                var iSegundo = parseInt(sFecha.substring(17));

                return parseInt(Date.UTC(iAnio, (iMes - 1), iDia, (iHora + (offset / 60)), iMinuto, iSegundo));
            } else if (sFecha.length === 10) {
                var iHora = 0;
                var iMinuto = 0;
                var iSegundo = 0;

                return parseInt(Date.UTC(iAnio, (iMes - 1), iDia, (iHora + (offset / 60)), iMinuto, iSegundo));
            }

            return 0;
        },

        restarFechas: function(sFecha, iHora, iMinuto, iSegundo, bRetornarHora) {
            bRetornarHora = typeof bRetornarHora === 'undefined' ? true : bRetornarHora;
            var d1 = new Date(servicioGeneral.fechaUtc(sFecha)),
                d2 = new Date(d1);
            d2.setHours(d1.getHours() - iHora);
            d2.setMinutes(d1.getMinutes() - iMinuto);
            d2.setSeconds(d1.getSeconds() - iSegundo);

            var dia = new String(d2.getDate());
            var mes = new String(d2.getMonth());
            var anio = new String(d2.getFullYear());
            var hora = new String(d2.getHours());
            var minuto = new String(d2.getMinutes());
            var segundo = new String(d2.getSeconds());

            dia = dia.length === 1 ? "0" + dia : dia;
            mes = parseInt(mes) + 1;
            mes = mes <= 9 ? "0" + mes : mes;
            hora = hora.length === 1 ? "0" + hora : hora;
            minuto = minuto.length === 1 ? "0" + minuto : minuto;
            segundo = segundo.length === 1 ? "0" + segundo : segundo;

            return bRetornarHora ? anio + "-" + mes + "-" + dia + " " + hora + ":" + minuto + ":" + segundo : anio + "-" + mes + "-" + dia;
        },

        getBase64Image: function(img) {
            var canvas = document.createElement("canvas");
            canvas.width = img.width;
            canvas.height = img.height;
        
            var ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0);
        
            var dataURL = canvas.toDataURL("image/png");
        
            return dataURL.replace(/^data:image\/(png|jpg);base64,/, "");
        },

        ubicacion: function () {
            if (navigator.geolocation){
                var success = function(position){
                    var latitud = position.coords.latitude,
                    longitud = position.coords.longitude;
                }

                navigator.geolocation.getCurrentPosition(success, function(msg){
                    console.error( msg );
                });
            }
        },

        fechaActual: function() {
            var today = new Date();
            var dd = today.getDate() < 10 ? '0' + today.getDate() : today.getDate();
            var mm = (today.getMonth() + 1) < 10 ? '0' + (today.getMonth() + 1) : (today.getMonth() + 1);
            var yyyy = today.getFullYear();
            return yyyy + '-' + mm + '-' + dd;
        },

        horaActual: function(bMostrarSegundos) {
            var d = new Date();
            var hora = d.getHours() < 10 ? '0' + d.getHours() : d.getHours();
            var minutos = d.getMinutes() < 10 ? '0' + d.getMinutes() : d.getMinutes();
            var segundos = d.getSeconds() < 10 ? '0' + d.getSeconds() : d.getSeconds();
            return bMostrarSegundos ? hora + ':' + minutos + ':' + segundos : hora + ':' + minutos;
        },

        fechaHoraActual: function(bMostrarSegundos) {
            return servicioGeneral.fechaActual() + ' ' + servicioGeneral.horaActual(bMostrarSegundos);
        },

        isNumeric: function(n) {
            return !isNaN(parseFloat(n)) && isFinite(n);
        },

        graficaBarra: function (etiquetas, valores, sTitulo, sTipo, id) {
            const config = {
                type: sTipo,
                data: {
                    labels: etiquetas,
                    datasets: [{
                        label: 'Cantidad',
                        data: valores,
                        borderWidth: 1,
                        backgroundColor: [
                            '#197091',
                            '#29a398',
                            '#c1d043',
                            '#535353',
                            '#9b57cc',
                            '#197091',
                            '#29a398',
                            '#c1d043',
                            '#535353',
                            '#9b57cc',
                        ],
                        borderColor: [
                            '#197091',
                            '#29a398',
                            '#c1d043',
                            '#535353',
                            '#9b57cc',
                            '#197091',
                            '#29a398',
                            '#c1d043',
                            '#535353',
                            '#9b57cc',
                        ],
                    }]
                },
                options: {
                    responsive: true,                    
                    plugins: {
                        legend: {
                            position: 'top',
                            display: true,
                            labels: {
                                // Modificar las etiquetas de la leyenda para incluir los valores
                                generateLabels: function(chart) {
                                    const original = Chart.overrides.doughnut.plugins.legend.labels.generateLabels;
                                    const labels = original.call(this, chart);
                                    
                                    labels.forEach((label, i) => {
                                        const value = chart.data.datasets[0].data[i];
                                        label.text = `${chart.data.labels[i]}: ${value.toFixed(1)}`;
                                    });
                                    
                                    return labels;
                                }
                            }
                        },
                        tooltip: {"enabled": true, "intersect":true},
                        title: {
                            display: true,
                            text: sTitulo,
                            font: {
                                size: 18
                            }
                        }
                    }
                },
            };
        
            const ctx = document.getElementById(id);
            return new Chart(ctx, config);
        },

        graficaDona: function (etiquetas, valores, sTitulo) {
            const config = {
                type: 'doughnut',
                data: {
                    labels: etiquetas,
                    datasets: [{
                        label: 'Cantidad',
                        data: valores,
                        borderWidth: 1,
                        backgroundColor: [
                            '#197091',
                            '#29a398',
                            '#c1d043',
                            '#535353',
                            '#9b57cc',
                            '#197091',
                            '#29a398',
                            '#c1d043',
                            '#535353',
                            '#9b57cc',
                          ],
                          borderColor: [
                            '#197091',
                            '#29a398',
                            '#c1d043',
                            '#535353',
                            '#9b57cc',
                            '#197091',
                            '#29a398',
                            '#c1d043',
                            '#535353',
                            '#9b57cc',
                        ],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            display: false,
                        },
                        title: {
                            display: true,
                            text: sTitulo
                        }
                    }
                },
            };
        }
    };

    return servicioGeneral;
});