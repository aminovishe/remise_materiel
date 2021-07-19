function showLoading() {
    $('#loading-bar-spinner').fadeIn();
}
function hideLoading() {
    $('#loading-bar-spinner').fadeOut();
}
$(document).ajaxStart(function() {
    showLoading();
});
$( document ).ajaxStop(function() {
    hideLoading();
});
$(document).on("change", '.custom-file-input', function (e) {
    let fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').html(fileName);
});
function makeid(length) {
    var result           = [];
    var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for ( var i = 0; i < length; i++ ) {
        result.push(characters.charAt(Math.floor(Math.random() *
            charactersLength)));
    }
    return result.join('');
}
function generateRandomUniqueFileName() {
    return makeid(25) + Date.now()
}
function getFrenchMonths(monthNumber, longFormat = true) {
    var frenchMonthsLongFormat = {1 : 'Janvier', 2 : 'Février', 3 : 'Mars', 4 : 'Avril', 5 : 'Mai', 6 : 'Juin', 7 : 'Juillet', 8 : 'Août', 9 : 'Septembre', 10 : 'Octobre', 11 : 'Novembre', 12 : 'Décembre'};
    var frenchMonthsShortFormat = {1 : 'Janv', 2 : 'Fév', 3 : 'Mar', 4 : 'Avr', 5 : 'Mai', 6 : 'Juin', 7 : 'Juil', 8 : 'Août', 9 : 'Sept', 10 : 'Oct', 11 : 'Nov', 12 : 'Déc'};
    return longFormat ? frenchMonthsLongFormat[parseInt(monthNumber)] : frenchMonthsShortFormat[parseInt(monthNumber)];
}
function lineChart_OrdersPerMonth(apexChartId,ordersPerMonth) {
    apexChartId = "#" + apexChartId;
    var data = [];
    var xAxisCategories = [];
    $.each(ordersPerMonth, function (index, obj) {
        data.push(obj.nbOrder);
    });
    $.each(ordersPerMonth, function (index, obj) {
        xAxisCategories.push(getFrenchMonths(obj.adMonth,false) + "-" + obj.adYear.slice(-2));
    });

    var options = {
        series: [{
            name: "Nbr lignes",
            data: data
        }],
        chart: {
            height: 350,
            type: 'line',
            zoom: {
                enabled: true
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'straight'
        },
        grid: {
            row: {
                colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
                opacity: 0.5
            },
        },
        xaxis: {
            categories: xAxisCategories,
        },
        colors: ['#6993FF'],
    };

    var chart = new ApexCharts(document.querySelector(apexChartId), options);
    chart.render();
}

// ---------------------- BEGIN::SELECT2 -------------------------
$(".select2-without-add").select2();
$('.select2-with-add').select2({
    tags: true,
    createTag: function (params) {
        return {
            id: 'new_' + params.term,
            text: params.term,
            newOption: true
        }
    },
    templateResult: function (data) {
        var $result = $("<span></span>");
        $result.text(data.text);
        if (data.newOption) {
            $result.append(" <em>(nouveau)</em>");
        }
        return $result;
    }
});
$('.select2-multiple').select2({
    placeholder: "Sélection multiple",
    language: {
        noResults: function(){
            return "Aucun résultat trouvé";
        }
    },
});
function initSelect2(element) {
    if ($(element).hasClass('select2-without-add')){
        $(element).select2();
    }
    else if ($(element).hasClass('select2-with-add')){
        $(element).select2({
            tags: true,
            createTag: function (params) {
                return {
                    id: 'new_' + params.term,
                    text: params.term,
                    newOption: true
                }
            },
            templateResult: function (data) {
                var $result = $("<span></span>");
                $result.text(data.text);
                if (data.newOption) {
                    $result.append(" <em>(nouveau)</em>");
                }
                return $result;
            }
        });
    }
    else if ($(element).hasClass('select2-multiple')){
        $(element).select2({
            placeholder: "Sélection multiple",
            language: {
                noResults: function(){
                    return "Aucun résultat trouvé";
                }
            },
        });
    }
}
// ---------------------- END::SELECT2 -------------------------

// ---------------------- BEGIN::Daterangepicker -------------------------
function initDaterangepicker(startDate = null,endDate = null,daterangeClass = "daterange-period",emptyInitDate = false) {
    var start = startDate === null ? moment('2020/01/01', 'YYYY-MM-DD') : startDate,
        end = endDate === null ? moment() : endDate;

    function changePeriod(start, end) {
        $('.'+ daterangeClass +' span').html('<span id="startDate">' + start.format('DD-MM-YYYY') + '</span>' + '&nbsp;&nbsp;&nbsp;<i class="fas fa-arrow-right"></i>&nbsp;&nbsp;&nbsp;' + '<span id="endDate">' + end.format('DD-MM-YYYY') + '</span>');
    }

    $('.'+ daterangeClass).daterangepicker({
        locale: {
            format: "DD-MM-YYYY",
            applyLabel: "Appliquer",
            cancelLabel: "Annuler",
            customRangeLabel: "Personnaliser",
        },
        buttonClasses: ' btn',
        applyClass: 'btn-primary',
        cancelClass: 'btn-secondary',
        startDate: start,
        endDate: end,
        ranges: {
            'Ajourd\'hui': [moment(), moment()],
            'Hier': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Derniers sept jours ': [moment().subtract(6, 'days'), moment()],
            'Derniers trente jours': [moment().subtract(29, 'days'), moment()],
            'Ce mois': [moment().startOf('month'), moment().endOf('month')],
            'Dernier mois': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Cette année': [moment().startOf('year'), moment().endOf('year')]
        }
    },changePeriod);

    if (!emptyInitDate){
        changePeriod(start, end);
    }
}
// ---------------------- END::Daterangepicker -------------------------