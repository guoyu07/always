$(window).load(function() {
    var availableTags = [
        'database',
        'pull',
        'of',
        'names'
    ];
    $('#always-search').autocomplete({
        source: availableTags
    });
});