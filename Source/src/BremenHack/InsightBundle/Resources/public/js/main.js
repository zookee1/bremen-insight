function loadDataset(id, level) {
    selectedDataset = id;
    selectedLevel = level;
    $.ajax({
        url: '/geojson/' + selectedDataset + '/' + selectedLevel,
        data: 'json',
        error: function(err){
            console.log(err);
        },
        beforeSend: function(){
            $('#loading').show();
        },
        success: function(){
            $('#loading').hide();
        }
    }).done(function(data){
        currentData = data;
        selectedCategory = data.categories[0];
        refreshDataPoints();
        $('#categorySelect option').remove();
        $('#datasetSelect option').remove();
        $('#columnSelect option').remove();

        for (var i=0; i<data.categories.length; i++) {
            var category = data.categories[i];
            $('#categorySelect').append('<option value="' + category + '"' + (category === selectedCategory ? ' selected' : '') + '>' + category + '</option>');
        }
        var columnOptions = [];
        for(var i=0; i<data.columns.length; i++) {
            var column = data.columns[i];
            $('#columnSelect').append('<option value="' + i + '"' + (i === selectedColumn ? ' selected' : '') + '>' + column + '</option>');
        }
        var datasetOptions = [];
        for(var i=0; i<data.datasets.length; i++) {
            var dataset = data.datasets[i];
            $('#datasetSelect').append('<option value="' + dataset.id + '"' + (selectedDataset == dataset.id ? ' selected' : '') + '>' + dataset.label + '</option>');
        }
        $('#categorySelect').change(function() {
            selectedCategory = $('#categorySelect').val();
            refreshDataPoints();
        });
        $('#columnSelect').change(function() {
            var val = $('#columnSelect').val();
            selectedColumn = val;
            refreshDataPoints();
        });
        $('#datasetSelect').change(function() {
            var val = $('#datasetSelect').val();
            loadDataset(val, selectedLevel);
        });
        $('#bar').show();
    });
}

function levelSwitcher(){
    $('#levelSwitcher').click(function() {
        loadDataset(selectedDataset, selectedLevel == 10 ? 11 : 10);
    });
}

$(document).ready(function(){
    loadDataset('17397', 10);
    levelSwitcher();
    addDevsMarker();
});